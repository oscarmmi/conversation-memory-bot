<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

type ChatMessage = {
    id: number;
    role: 'user' | 'assistant';
    body: string;
};

type MemoryFact = {
    key: string;
    value: string;
};

const props = defineProps<{
    messages: ChatMessage[];
    memories: MemoryFact[];
}>();

const messages = ref<ChatMessage[]>([...props.messages]);
const memories = ref<MemoryFact[]>([...props.memories]);
const draft = ref('');
const sending = ref(false);
const resetting = ref(false);
const error = ref('');
const conversation = ref<HTMLElement | null>(null);

const csrfToken =
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';
const memoryLabels: Record<string, string> = {
    name: 'Name',
    order_id: 'Order',
    product: 'Product',
};

const hasMessages = computed(() => messages.value.length > 0);

async function scrollToLatest() {
    await nextTick();
    if (conversation.value) {
        conversation.value.scrollTop = conversation.value.scrollHeight;
    }
}

async function sendMessage(text = draft.value) {
    const message = text.trim();
    if (!message || sending.value) return;

    draft.value = '';
    sending.value = true;
    error.value = '';

    try {
        const response = await fetch('/chat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message }),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ??
                    'Your message could not be sent. Please try again.',
            );
        }

        messages.value.push(...(data.messages as ChatMessage[]));
        memories.value = data.memories as MemoryFact[];
        await scrollToLatest();
    } catch (exception) {
        draft.value = message;
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Something went wrong. Please try again.';
    } finally {
        sending.value = false;
    }
}

async function resetConversation() {
    if (resetting.value || sending.value) return;

    resetting.value = true;
    error.value = '';

    try {
        const response = await fetch('/chat/reset', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ?? 'The conversation could not be reset.',
            );
        }

        messages.value = [];
        memories.value = [];
        draft.value = '';
        await scrollToLatest();
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Something went wrong. Please try again.';
    } finally {
        resetting.value = false;
    }
}

onMounted(scrollToLatest);
</script>

<template>
    <Head title="Harbor Desk Support" />

    <main class="min-h-screen bg-[#f6f7f8] text-slate-900">
        <header
            class="sticky top-0 z-10 border-b border-slate-200/80 bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Harbor Desk Support home"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-[#163b35] text-white shadow-sm"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 18.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V15a2 2 0 0 1-2 2H8l-4 2.5v-1Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M8 9h8M8 12.5h5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold tracking-tight"
                            >Harbor Desk</span
                        >
                        <span class="block text-xs text-slate-500"
                            >Customer care</span
                        >
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 sm:inline-flex"
                    >
                        <span
                            class="size-1.5 rounded-full bg-emerald-500"
                        ></span>
                        Memory is on
                    </span>
                    <button
                        type="button"
                        :disabled="resetting || sending"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="resetConversation"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="none"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M3.5 5.5h13M8 8.5v6m4-6v6M5 5.5l.7 10.1a1.5 1.5 0 0 0 1.5 1.4h5.6a1.5 1.5 0 0 0 1.5-1.4L15 5.5M8 5.5V3.8a.8.8 0 0 1 .8-.8h2.4a.8.8 0 0 1 .8.8v1.7"
                                stroke="currentColor"
                                stroke-width="1.4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>{{
                            resetting ? 'Resetting…' : 'Reset chat'
                        }}</span>
                    </button>
                </div>
            </div>
        </header>

        <section
            class="border-b border-slate-200 bg-[#fbfcfb] px-5 py-3 lg:hidden"
            aria-label="Conversation memory"
        >
            <div
                class="mx-auto flex max-w-[860px] items-center gap-2 overflow-x-auto"
            >
                <span
                    class="mr-1 shrink-0 text-[10px] font-semibold tracking-wider text-slate-400 uppercase"
                    >Memory</span
                >
                <span
                    v-for="key in ['name', 'order_id', 'product']"
                    :key="key"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs"
                >
                    <span class="text-slate-400">{{ memoryLabels[key] }}</span>
                    <span
                        class="max-w-24 truncate font-medium"
                        :class="
                            memories.find((fact) => fact.key === key)
                                ? 'text-[#204d43]'
                                : 'text-slate-300'
                        "
                    >
                        {{
                            memories.find((fact) => fact.key === key)?.value ??
                            '—'
                        }}
                    </span>
                </span>
            </div>
        </section>

        <div
            class="mx-auto grid min-h-[calc(100vh-72px)] max-w-[1440px] grid-cols-1 lg:grid-cols-[minmax(230px,300px)_minmax(0,1fr)]"
        >
            <aside
                class="hidden border-r border-slate-200 bg-white px-7 py-10 lg:block"
            >
                <div class="sticky top-28">
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-[#557a70] uppercase"
                    >
                        A little more personal
                    </p>
                    <h1
                        class="mt-4 max-w-[240px] text-[27px] leading-[1.18] font-semibold tracking-[-0.04em] text-slate-900"
                    >
                        Support that remembers the details.
                    </h1>
                    <p class="mt-4 text-sm leading-6 text-slate-500">
                        Share your name, order, or product once. Your assistant
                        will carry it through the conversation.
                    </p>

                    <section
                        class="mt-9 rounded-2xl border border-slate-200 bg-[#fafbfa] p-4"
                        aria-labelledby="memory-heading"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h2
                                id="memory-heading"
                                class="text-sm font-semibold text-slate-800"
                            >
                                Conversation memory
                            </h2>
                            <span
                                class="rounded-full bg-white px-2 py-1 text-[10px] font-semibold tracking-wider text-slate-500 uppercase ring-1 ring-slate-200"
                                >Live</span
                            >
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Facts noticed in this chat
                        </p>

                        <div class="mt-4 space-y-2.5">
                            <div
                                v-for="key in ['name', 'order_id', 'product']"
                                :key="key"
                                class="flex min-h-10 items-center justify-between gap-2 rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200/80"
                            >
                                <span
                                    class="text-xs font-medium text-slate-500"
                                    >{{ memoryLabels[key] }}</span
                                >
                                <span
                                    v-if="
                                        memories.find(
                                            (fact) => fact.key === key,
                                        )
                                    "
                                    class="max-w-[130px] truncate text-right text-xs font-semibold text-[#204d43]"
                                >
                                    {{
                                        memories.find(
                                            (fact) => fact.key === key,
                                        )?.value
                                    }}
                                </span>
                                <span v-else class="text-xs text-slate-300"
                                    >Waiting…</span
                                >
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-start gap-2.5 border-t border-slate-100 pt-3"
                        >
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="mt-0.5 size-4 shrink-0 text-[#557a70]"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 2.5 12 7l4.5 2-4.5 2-2 4.5L8 11l-4.5-2L8 7l2-4.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <p
                                class="text-[11px] leading-[17px] text-slate-500"
                            >
                                Stored facts are scoped to this browser session
                                and cleared when you reset.
                            </p>
                        </div>
                    </section>

                    <div class="mt-7 border-t border-slate-200 pt-5">
                        <p
                            class="text-[11px] font-semibold tracking-[0.14em] text-slate-400 uppercase"
                        >
                            Try asking
                        </p>
                        <button
                            type="button"
                            class="mt-3 block text-left text-sm text-slate-600 transition hover:text-[#204d43]"
                            @click="sendMessage('What is the Harbor Desk?')"
                        >
                            “What is the Harbor Desk?”
                        </button>
                        <button
                            type="button"
                            class="mt-2.5 block text-left text-sm text-slate-600 transition hover:text-[#204d43]"
                            @click="sendMessage('How long does shipping take?')"
                        >
                            “How long does shipping take?”
                        </button>
                    </div>
                </div>
            </aside>

            <section class="flex min-h-[calc(100vh-72px)] min-w-0 flex-col">
                <div
                    class="border-b border-slate-200/80 bg-white px-5 py-4 sm:px-8 lg:px-12"
                >
                    <div class="mx-auto flex max-w-[860px] items-center gap-3">
                        <div
                            class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-[#e7efec] text-[#204d43]"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                class="size-5"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <span
                                class="absolute right-0 bottom-0 size-2.5 rounded-full border-2 border-white bg-emerald-500"
                            ></span>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Harbor Desk assistant
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Here to help, and built to remember
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    ref="conversation"
                    class="flex-1 overflow-y-auto px-5 py-8 sm:px-8 lg:px-12"
                    aria-live="polite"
                >
                    <div class="mx-auto flex min-h-full max-w-[860px] flex-col">
                        <div v-if="!hasMessages" class="my-auto py-8">
                            <div class="mx-auto max-w-xl text-center">
                                <div
                                    class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#e7efec] text-[#204d43]"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        class="size-7"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M4 18.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V15a2 2 0 0 1-2 2H8l-4 2.5v-1Z"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M8 9h8M8 12.5h5"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </div>
                                <p
                                    class="mt-5 text-xs font-semibold tracking-[0.16em] text-[#557a70] uppercase"
                                >
                                    Harbor Desk support
                                </p>
                                <h3
                                    class="mt-3 text-3xl font-semibold tracking-[-0.045em] text-slate-900 sm:text-[38px]"
                                >
                                    How can I help today?
                                </h3>
                                <p
                                    class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500"
                                >
                                    Ask about your desk, shipping, or returns.
                                    If you share your order details, I’ll
                                    remember them as we go.
                                </p>
                                <div
                                    class="mt-7 flex flex-wrap justify-center gap-2"
                                >
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 shadow-sm transition hover:border-[#9bb8ae] hover:text-[#204d43]"
                                        @click="
                                            sendMessage(
                                                'What is the Harbor Desk?',
                                            )
                                        "
                                    >
                                        Tell me about the desk
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 shadow-sm transition hover:border-[#9bb8ae] hover:text-[#204d43]"
                                        @click="
                                            sendMessage(
                                                'How long does shipping take?',
                                            )
                                        "
                                    >
                                        Shipping & returns
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-6 pb-6">
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                class="flex gap-3"
                                :class="
                                    message.role === 'user'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    v-if="message.role === 'assistant'"
                                    class="mt-1 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e7efec] text-[#204d43]"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        class="size-4"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </div>
                                <p
                                    class="max-w-[min(78%,620px)] rounded-2xl px-4 py-3 text-sm leading-6 whitespace-pre-wrap shadow-sm"
                                    :class="
                                        message.role === 'user'
                                            ? 'rounded-br-md bg-[#204d43] text-white'
                                            : 'rounded-bl-md border border-slate-200 bg-white text-slate-700'
                                    "
                                >
                                    {{ message.body }}
                                </p>
                            </div>
                            <div v-if="sending" class="flex items-center gap-3">
                                <span
                                    class="flex size-8 items-center justify-center rounded-full bg-[#e7efec] text-[#204d43]"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        class="size-4"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </span>
                                <span
                                    class="rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-400"
                                    >Thinking…</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="border-t border-slate-200 bg-white px-5 pt-4 pb-5 sm:px-8 lg:px-12"
                >
                    <div class="mx-auto max-w-[860px]">
                        <p
                            v-if="error"
                            class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"
                            role="alert"
                        >
                            {{ error }}
                        </p>
                        <form
                            class="flex items-end gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_6px_24px_-16px_rgba(15,23,42,0.35)] transition focus-within:border-[#9bb8ae] focus-within:ring-4 focus-within:ring-[#204d43]/5"
                            @submit.prevent="sendMessage()"
                        >
                            <label for="message" class="sr-only"
                                >Your message</label
                            >
                            <textarea
                                id="message"
                                v-model="draft"
                                rows="1"
                                maxlength="2000"
                                placeholder="Message Harbor Desk support…"
                                class="max-h-32 min-h-11 flex-1 resize-y border-0 bg-transparent px-3 py-3 text-sm leading-5 text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                                @keydown.enter.exact.prevent="sendMessage()"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="!draft.trim() || sending"
                                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#204d43] text-white transition hover:bg-[#163b35] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                                aria-label="Send message"
                            >
                                <svg
                                    v-if="!sending"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m5 12 14-7-4.5 14-2.8-5.2L5 12Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m11.7 13.8 3.8-4.1"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-5 animate-spin"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="8"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        class="opacity-25"
                                    />
                                    <path
                                        d="M20 12a8 8 0 0 0-8-8"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </form>
                        <p
                            class="mt-2.5 text-center text-[11px] text-slate-400"
                        >
                            Demo conversation · Your memory stays in this
                            browser session
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
