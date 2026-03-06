<x-filament-panels::page>
    @php
        $ticket = $this->record;
        $messages = $ticket->messages;
    @endphp

    {{-- Ticket header --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-semibold text-gray-950 dark:text-white">
                        #{{ $ticket->id }} — {{ $ticket->title }}
                    </h2>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            {{ $ticket->user?->email ?? '—' }}
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ \App\Models\Ticket::CATEGORIES[$ticket->category] ?? $ticket->category }}
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <x-filament::badge :color="match($ticket->status) {
                            'open' => 'warning',
                            'in_progress' => 'info',
                            'closed' => 'gray',
                            default => 'gray',
                        }">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </x-filament::badge>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $ticket->created_at->format('M j, Y H:i') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @if($ticket->description)
            <div class="px-6 py-4">
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $ticket->description }}</p>
            </div>
        @endif
    </div>

    {{-- Chat --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-gray-800">
            <svg class="h-5 w-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            <h3 class="text-sm font-medium text-gray-950 dark:text-white">Conversation</h3>
        </div>

        <div class="relative bg-[linear-gradient(to_bottom,rgba(0,0,0,0.02),transparent_80px)] p-4 dark:bg-[linear-gradient(to_bottom,rgba(255,255,255,0.02),transparent_80px)]">
            <div
                class="flex max-h-[440px] flex-col gap-4 overflow-y-auto pr-2"
                id="ticket-chat-messages"
                x-data
                x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
            >
                @forelse($messages as $msg)
                    <div class="flex {{ $msg->is_admin ? 'justify-end' : 'justify-start' }}">
                        <div class="group flex max-w-[80%] {{ $msg->is_admin ? 'flex-row-reverse' : 'flex-row' }} gap-3">
                            {{-- Avatar --}}
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $msg->is_admin
                                ? 'bg-primary-500 text-white'
                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                @if($msg->is_admin)
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @endif
                            </div>
                            {{-- Bubble --}}
                            <div class="flex min-w-0 flex-col {{ $msg->is_admin ? 'items-end' : 'items-start' }}">
                                <div class="flex items-center gap-2 {{ $msg->is_admin ? 'flex-row-reverse' : '' }}">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        {{ $msg->is_admin ? 'Support' : ($msg->user?->email ?? 'User') }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $msg->created_at->format('M j, H:i') }}
                                    </span>
                                </div>
                                <div class="ticket-chat-bubble mt-1 px-4 py-2.5 {{ $msg->is_admin
                                    ? 'ticket-chat-bubble--admin bg-primary-500 text-white'
                                    : 'ticket-chat-bubble--user bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                    <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $msg->content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No messages yet</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Send a reply below to start the conversation</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Reply form --}}
        @if($ticket->status !== 'closed')
            <div class="border-t border-gray-200 bg-gray-50/80 px-4 py-4 dark:border-white/10 dark:bg-gray-800/30">
                <form wire:submit="sendReply" class="space-y-4">
                    <div>
                        <textarea
                            wire:model="replyMessage"
                            rows="3"
                            class="fi-input block w-full rounded-xl border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                            placeholder="Type your reply..."
                        ></textarea>
                        @error('replyMessage')
                            <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-500 dark:text-gray-400">Status</label>
                            <select
                                wire:model="replyStatus"
                                class="fi-select block rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-500"
                            >
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <x-filament::button type="submit" color="success" icon="heroicon-o-paper-airplane">
                            Send Reply
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @else
            <div class="border-t border-gray-200 bg-gray-50/80 px-4 py-6 dark:border-white/10 dark:bg-gray-800/30">
                <div class="flex flex-col items-center justify-center text-center">
                    <svg class="mb-2 h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This ticket is closed</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Reopen it to send new messages</p>
                    <x-filament::button wire:click="reopenTicket" color="warning" size="sm" class="mt-4">
                        Reopen Ticket
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .ticket-chat-bubble {
            border-radius: 1rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .ticket-chat-bubble--admin {
            border-bottom-right-radius: 0.375rem;
        }
        .ticket-chat-bubble--user {
            border-bottom-left-radius: 0.375rem;
        }
    </style>
</x-filament-panels::page>
