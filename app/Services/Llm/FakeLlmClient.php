<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    public function reply(string $message, array $facts, array $faq): string
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

        if ($facts !== []) {
            return 'Thanks for sharing that. I’ll keep it in mind while we chat. What can I help you with?';
        }

        return 'Hi! I’m the Harbor Desk support assistant. Ask me about the Harbor Desk, shipping, or returns—or tell me your name or order number and I’ll remember it.';
    }
}
