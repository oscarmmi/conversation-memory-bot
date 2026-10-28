<?php

use App\Services\Llm\FakeLlmClient;

it('answers order questions from supplied conversation memory', function (): void {
    $client = new FakeLlmClient;

    expect($client->reply('what is my order?', ['order_id' => 'HD-22'], []))
        ->toBe('Your order number is HD-22.');
});

it('uses the supplied FAQ when answering a support question', function (): void {
    $client = new FakeLlmClient;
    $faq = [[
        'question' => 'What should I know about the Harbor Desk?',
        'answer' => 'It ships in 2–3 business days and includes a 30-day return window.',
    ]];

    expect($client->reply('How long does shipping take?', [], $faq))
        ->toBe('It ships in 2–3 business days and includes a 30-day return window.');
});
