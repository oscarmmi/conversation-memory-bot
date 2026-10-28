Build Conversation Memory Bot in THIS folder: C:\xampp\htdocs\conversation-memory-bot

A recruiter-ready support chatbot that remembers facts extracted from earlier turns and uses them later.

Laravel 13 + Vue 3 starter kit is already here if present. Reuse it. Do not create a second app inside NewPortfolio.

## Must satisfy

- Models: `ChatSession`, `ChatTurn` (role user|assistant, body), `MemoryFact` (session_id, key, value, source_turn_id).
- `App\Services\Chat\MemoryBot`: extract facts (name, order_id, product) from the user turn, store them, then answer using memory + a small FAQ. Timeout 12s. Never 500.
- `App\Services\Llm\LlmClient` with `FakeLlmClient` in tests and when no `GEMINI_API_KEY`. Optional Gemini when key is set.
- Seed 1 Harbor Desk FAQ. UI: chat, visible memory chips (name / order / product), reset session.
- Pest: user says "I am Maya, order HD-22" then "what is my order?" → reply contains HD-22 and a MemoryFact is stored; FakeLlm bound; guest POST 200.
- README mermaid: Turn → fact extract → memory store → answer with memory. How to run without API key.

## GitHub

- git config user.name "Oscar Munoz" / user.email "oscarxxi@gmail.com"
- Commit: feat: add conversation memory bot
- Public https://github.com/oscarmmi/conversation-memory-bot
- gh repo edit --description "Support chatbot that extracts and reuses conversation facts (Laravel + Vue)." --add-topic laravel --add-topic vue --add-topic ai --add-topic php --add-topic chatbot
- Stop. Print the URL.

No Impeccable. No Redis. No real customers.
