<?php

namespace App\Services\Chat;

use App\Models\ChatSession;
use App\Models\ChatTurn;
use App\Models\Faq;
use App\Models\MemoryFact;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemoryBot
{
    public function __construct(private readonly LlmClient $llm) {}

    public function respond(ChatSession $session, string $message): ChatTurn
    {
        $userTurn = DB::transaction(function () use ($session, $message): ChatTurn {
            $turn = $session->turns()->create([
                'role' => 'user',
                'body' => $message,
            ]);

            foreach ($this->extractFacts($message) as $key => $value) {
                MemoryFact::query()->updateOrCreate(
                    ['session_id' => $session->id, 'key' => $key],
                    ['value' => $value, 'source_turn_id' => $turn->id],
                );
            }

            return $turn;
        });

        $facts = $session->memoryFacts()->get()->mapWithKeys(
            fn (MemoryFact $fact): array => [$fact->key => $fact->value],
        )->all();

        $faq = array_values(Faq::query()->orderBy('id')->get(['question', 'answer'])->map(
            fn (Faq $entry): array => ['question' => $entry->question, 'answer' => $entry->answer],
        )->all());

        try {
            $answer = $this->llm->reply($message, $facts, $faq);
        } catch (Throwable) {
            $answer = $this->fallbackAnswer($message, $facts, $faq);
        }

        return $session->turns()->create([
            'role' => 'assistant',
            'body' => $answer,
        ]);
    }

    /** @return array<string, string> */
    private function extractFacts(string $message): array
    {
        $facts = [];

        if (preg_match('/\b(?:my name is|i am|i’m|i\'m)\s+([\p{L}][\p{L}\x{27}-]{1,29})/iu', $message, $nameMatch) === 1) {
            $name = trim($nameMatch[1]);

            if (! in_array(mb_strtolower($name), ['looking', 'trying', 'calling', 'wondering', 'happy', 'sorry', 'interested'], true)) {
                $facts['name'] = $name;
            }
        }

        if (preg_match('/\border(?:\s+(?:id|number))?\s*(?:is\s+)?[:#-]?\s*([A-Z0-9][A-Z0-9-]{2,})\b/i', $message, $orderMatch) === 1) {
            $facts['order_id'] = strtoupper($orderMatch[1]);
        }

        $productMatch = preg_match('/\b(?:product|item)\s+(?:is|:)\s*([^.!?,\n]{2,80})/i', $message, $match) === 1
            ? $match
            : (preg_match('/\bi\s+(?:bought|ordered|purchased)\s+(?:(?:a|an|the)\s+)?([^.!?,\n]{2,80})/i', $message, $match) === 1 ? $match : null);

        if ($productMatch !== null) {
            $product = trim($productMatch[1]);

            if ($product !== '' && preg_match('/^[A-Z0-9][A-Z0-9-]{2,}$/i', $product) !== 1) {
                $facts['product'] = $product;
            }
        }

        return $facts;
    }

    /**
     * @param  array<string, string>  $facts
     * @param  list<array{question: string, answer: string}>  $faq
     */
    private function fallbackAnswer(string $message, array $facts, array $faq): string
    {
        $normalized = mb_strtolower($message);

        if (str_contains($normalized, 'order') && isset($facts['order_id'])) {
            return 'Your order number is '.$facts['order_id'].'.';
        }

        if ((str_contains($normalized, 'name') || str_contains($normalized, 'who am i')) && isset($facts['name'])) {
            return 'You told me your name is '.$facts['name'].'.';
        }

        if (str_contains($normalized, 'product') && isset($facts['product'])) {
            return 'The product you mentioned is '.$facts['product'].'.';
        }

        foreach ($faq as $entry) {
            if (str_contains($normalized, 'harbor desk') || str_contains($normalized, 'shipping') || str_contains($normalized, 'return')) {
                return $entry['answer'];
            }
        }

        return 'I’m here to help with the Harbor Desk, shipping, and returns. You can also share your name or order number and I’ll remember it for this conversation.';
    }
}
