<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\Chat\MemoryBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        $chatSession = $this->currentSession($request);

        return Inertia::render('Welcome', [
            'messages' => $chatSession->turns()->orderBy('id')->get(['id', 'role', 'body'])->map(
                fn ($turn): array => [
                    'id' => $turn->id,
                    'role' => $turn->role,
                    'body' => $turn->body,
                ],
            )->values(),
            'memories' => $chatSession->memoryFacts()->orderBy('id')->get(['key', 'value'])->map(
                fn ($fact): array => ['key' => $fact->key, 'value' => $fact->value],
            )->values(),
        ]);
    }

    public function send(Request $request, MemoryBot $bot): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $session = $this->currentSession($request);
            $assistantTurn = $bot->respond($session, $validated['message']);

            return response()->json([
                'reply' => $assistantTurn->body,
                'messages' => $session->turns()->latest('id')->limit(2)->get(['id', 'role', 'body'])->reverse()->values(),
                'memories' => $session->memoryFacts()->orderBy('id')->get(['key', 'value']),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Conversation memory request could not be completed.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'reply' => 'I’m sorry, I couldn’t save that message just now. Please try again.',
                'messages' => [],
                'memories' => [],
            ]);
        }
    }

    public function reset(Request $request): JsonResponse
    {
        try {
            $session = $this->currentSession($request);
            $session->memoryFacts()->delete();
            $session->turns()->delete();
            $session->delete();
            $request->session()->put('conversation_memory_session_key', (string) Str::uuid());

            return response()->json(['ok' => true, 'messages' => [], 'memories' => []]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'The conversation could not be reset. Please try again.',
            ], 503);
        }
    }

    private function currentSession(Request $request): ChatSession
    {
        $sessionKey = $request->session()->get('conversation_memory_session_key');

        if (! is_string($sessionKey) || ! Str::isUuid($sessionKey)) {
            $sessionKey = (string) Str::uuid();
            $request->session()->put('conversation_memory_session_key', $sessionKey);
        }

        return ChatSession::query()->firstOrCreate(['session_key' => $sessionKey]);
    }
}
