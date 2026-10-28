<?php

use App\Models\ChatSession;
use App\Models\Faq;
use App\Models\MemoryFact;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;

beforeEach(function (): void {
    app()->instance(LlmClient::class, new FakeLlmClient);
});

it('renders the guest chat and its current memory', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('messages')
            ->has('memories'));
});

it('remembers an order from an earlier turn and uses it in a later answer', function (): void {
    $firstResponse = $this->postJson(route('chat.send'), [
        'message' => 'I am Maya, order HD-22',
    ])->assertOk();

    $firstResponse->assertJsonFragment(['key' => 'name', 'value' => 'Maya']);
    $firstResponse->assertJsonFragment(['key' => 'order_id', 'value' => 'HD-22']);

    $orderFact = MemoryFact::query()->where('key', 'order_id')->firstOrFail();
    expect($orderFact->sourceTurn->body)->toBe('I am Maya, order HD-22');

    $this->postJson(route('chat.send'), ['message' => 'what is my order?'])
        ->assertOk()
        ->assertJsonPath('reply', 'Your order number is HD-22.');

    expect(ChatSession::query()->count())->toBe(1)
        ->and(MemoryFact::query()->where('key', 'order_id')->value('value'))->toBe('HD-22');
});

it('extracts a name, an order ID, and a product from one message', function (): void {
    $this->postJson(route('chat.send'), [
        'message' => 'My name is Maya, my order is HD-22 and the product is Harbor Desk.',
    ])->assertOk();

    expect(MemoryFact::query()->pluck('value', 'key')->all())->toMatchArray([
        'name' => 'Maya',
        'order_id' => 'HD-22',
        'product' => 'Harbor Desk',
    ]);
});

it('accepts a guest chat request and persists the conversation', function (): void {
    $this->postJson(route('chat.send'), ['message' => 'Hello there'])
        ->assertOk()
        ->assertJsonPath('messages.0.role', 'user')
        ->assertJsonPath('messages.1.role', 'assistant');

    expect(ChatSession::query()->count())->toBe(1)
        ->and(ChatSession::query()->firstOrFail()->turns()->count())->toBe(2);
});

it('answers support questions from the seeded FAQ', function (): void {
    Faq::query()->create([
        'slug' => 'harbor-desk-shipping',
        'question' => 'What should I know about the Harbor Desk?',
        'answer' => 'The Harbor Desk ships in 2–3 business days and includes a 30-day return window.',
    ]);

    $this->postJson(route('chat.send'), ['message' => 'How long does shipping take?'])
        ->assertOk()
        ->assertJsonPath('reply', 'The Harbor Desk ships in 2–3 business days and includes a 30-day return window.');
});

it('clears stored turns and facts when the guest resets their chat', function (): void {
    $this->postJson(route('chat.send'), ['message' => 'I am Maya, order HD-22'])->assertOk();
    $this->postJson(route('chat.reset'))->assertOk()->assertJsonPath('ok', true);

    expect(ChatSession::query()->count())->toBe(0)
        ->and(MemoryFact::query()->count())->toBe(0);
});

it('falls back to a useful reply if the configured LLM fails', function (): void {
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function reply(string $message, array $facts, array $faq): string
        {
            throw new RuntimeException('LLM request timed out');
        }
    });

    $this->postJson(route('chat.send'), ['message' => 'I am Maya, order HD-22'])
        ->assertOk()
        ->assertJsonPath('reply', 'Your order number is HD-22.');

    expect(MemoryFact::query()->where('key', 'order_id')->value('value'))->toBe('HD-22');
});
