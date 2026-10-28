<?php

namespace App\Services\Llm;

interface LlmClient
{
    /**
     * @param  array<string, string>  $facts
     * @param  list<array{question: string, answer: string}>  $faq
     */
    public function reply(string $message, array $facts, array $faq): string;
}
