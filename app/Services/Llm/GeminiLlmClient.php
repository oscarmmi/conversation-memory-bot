<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLlmClient implements LlmClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function reply(string $message, array $facts, array $faq): string
    {
        $context = [
            'Known conversation facts: '.json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Support FAQ: '.json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Customer message: '.$message,
        ];

        $response = Http::connectTimeout(3)
            ->timeout(12)
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post(sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                urlencode($this->model),
            ), [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => 'You are a concise, friendly Harbor Desk support assistant. Use the supplied conversation facts and FAQ to answer. Do not invent order status or product policies. If the context does not answer the question, say so and ask one useful follow-up question.',
                    ]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => implode("\n", $context)]],
                ]],
                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 250],
            ]);

        $response->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned no answer text.');
        }

        return trim($text);
    }
}
