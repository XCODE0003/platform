<script setup>
import { ref, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

// Phase: 'loading' | 'new' | 'chat'
const phase    = ref('loading');
const ticket   = ref(null);
const messages = ref([]);
const error    = ref('');

// New ticket form
const category = ref('');
const message  = ref('');
const creating = ref(false);

// Chat message form
const newMessage = ref('');
const sending    = ref(false);

const messagesEl = ref(null);

const CATEGORIES = [
    { value: 'technical', label: 'Technical Issue' },
    { value: 'account',   label: 'Account Problem' },
    { value: 'trading',   label: 'Trading Question' },
    { value: 'other',     label: 'Other' },
];

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}

watch(() => props.open, async (v) => {
    if (!v) return;
    await fetchTicket();
});

async function fetchTicket() {
    phase.value = 'loading';
    error.value = '';
    try {
        const res = await axios.get('/api/support/ticket');
        if (res.data.ticket) {
            ticket.value   = res.data.ticket;
            messages.value = res.data.messages ?? [];
            phase.value    = 'chat';
            scrollToBottom();
        } else {
            phase.value = 'new';
        }
    } catch {
        phase.value = 'new';
    }
}

async function createTicket() {
    if (!category.value || !message.value.trim()) return;
    error.value  = '';
    creating.value = true;
    try {
        const res = await axios.post('/api/support/tickets', {
            category: category.value,
            message:  message.value.trim(),
        });
        ticket.value   = res.data.ticket;
        messages.value = res.data.messages ?? [];
        message.value  = '';
        phase.value    = 'chat';
        scrollToBottom();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Something went wrong.';
    } finally {
        creating.value = false;
    }
}

async function sendMessage() {
    const content = newMessage.value.trim();
    if (!content || sending.value) return;
    newMessage.value = '';
    sending.value    = true;
    error.value      = '';
    try {
        const res = await axios.post('/api/support/messages', {
            ticket_id: ticket.value.id,
            message:   content,
        });
        messages.value = res.data.messages ?? messages.value;
        scrollToBottom();
    } catch (e) {
        newMessage.value = content;
        error.value = e.response?.data?.error ?? 'Failed to send.';
    } finally {
        sending.value = false;
    }
}

function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function formatTime(str) {
    if (!str) return '';
    return new Date(str).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Transition name="chat-slide">
        <div v-if="open" class="chat-widget">
            <!-- Header -->
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-avatar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <div class="chat-header-name">Support</div>
                        <div class="chat-header-status">Online</div>
                    </div>
                </div>
                <button class="chat-close" @click="emit('close')">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Loading -->
            <div v-if="phase === 'loading'" class="chat-loading">
                <div class="chat-spinner"></div>
            </div>

            <!-- New ticket form -->
            <div v-else-if="phase === 'new'" class="chat-body">
                <div class="chat-intro">
                    <p class="chat-intro-title">How can we help?</p>
                    <p class="chat-intro-sub">Select a topic and describe your issue</p>
                </div>

                <div class="chat-categories">
                    <button
                        v-for="cat in CATEGORIES"
                        :key="cat.value"
                        class="cat-btn"
                        :class="{ active: category === cat.value }"
                        @click="category = cat.value"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <div class="chat-input-wrap">
                    <textarea
                        v-model="message"
                        class="chat-textarea"
                        placeholder="Describe your issue..."
                        rows="3"
                        @keydown="(e) => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), createTicket())"
                    ></textarea>
                    <p v-if="error" class="chat-error">{{ error }}</p>
                    <button
                        class="chat-send-btn"
                        :disabled="!category || !message.trim() || creating"
                        @click="createTicket"
                    >
                        <span v-if="creating">Sending...</span>
                        <span v-else>
                            Start Chat
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Chat view -->
            <div v-else-if="phase === 'chat'" class="chat-body">
                <div class="chat-topic" v-if="ticket">
                    <span class="chat-topic-badge">{{ ticket.category }}</span>
                    <span class="chat-topic-status" :class="'status-' + ticket.status">
                        {{ ticket.status === 'in_progress' ? 'In Progress' : ticket.status }}
                    </span>
                </div>

                <div class="chat-messages" ref="messagesEl">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="msg"
                        :class="msg.is_admin ? 'msg--admin' : 'msg--user'"
                    >
                        <div class="msg__bubble">
                            <div class="msg__text">{{ msg.content }}</div>
                            <div class="msg__time">{{ formatTime(msg.created_at) }}</div>
                        </div>
                    </div>
                    <div v-if="!messages.length" class="chat-empty">No messages yet</div>
                </div>

                <div class="chat-input-wrap" v-if="ticket?.status !== 'closed'">
                    <div class="chat-input-row">
                        <textarea
                            v-model="newMessage"
                            class="chat-textarea chat-textarea--sm"
                            placeholder="Type a message..."
                            rows="2"
                            @keydown="onKeydown"
                        ></textarea>
                        <button
                            class="chat-send-icon"
                            :disabled="!newMessage.trim() || sending"
                            @click="sendMessage"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <p v-if="error" class="chat-error">{{ error }}</p>
                </div>
                <div v-else class="chat-closed">Ticket is closed</div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.chat-widget {
    position: fixed;
    bottom: 80px;
    right: 24px;
    width: 400px;
    background: #0e1e28;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
    max-height: 720px;
}

/* Transitions */
.chat-slide-enter-active, .chat-slide-leave-active {
    transition: opacity 0.2s, transform 0.2s;
}
.chat-slide-enter-from, .chat-slide-leave-to {
    opacity: 0;
    transform: translateY(16px) scale(0.97);
}

/* Header */
.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: rgba(121,249,149,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}
.chat-header-info { display: flex; align-items: center; gap: 10px; }
.chat-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(121,249,149,0.15);
    display: flex; align-items: center; justify-content: center;
    color: #79f995;
}
.chat-header-name { font-size: 14px; font-weight: 600; color: #fff; }
.chat-header-status { font-size: 11px; color: #79f995; }
.chat-close {
    background: transparent; border: none; color: rgba(255,255,255,0.4);
    cursor: pointer; padding: 4px; border-radius: 4px;
    display: flex; align-items: center;
}
.chat-close:hover { color: #fff; background: rgba(255,255,255,0.06); }

/* Loading */
.chat-loading {
    display: flex; align-items: center; justify-content: center;
    height: 120px;
}
.chat-spinner {
    width: 24px; height: 24px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.1);
    border-top-color: #79f995;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Body */
.chat-body {
    display: flex; flex-direction: column;
    flex: 1; overflow: hidden;
}

/* Intro */
.chat-intro { padding: 16px 16px 8px; }
.chat-intro-title { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.chat-intro-sub { font-size: 12px; color: rgba(255,255,255,0.4); }

/* Categories */
.chat-categories {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 0 16px 12px;
}
.cat-btn {
    padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.6);
    cursor: pointer; transition: all 0.15s;
}
.cat-btn:hover { border-color: rgba(121,249,149,0.3); color: #fff; }
.cat-btn.active {
    border-color: rgba(121,249,149,0.5);
    background: rgba(121,249,149,0.1);
    color: #79f995;
}

/* Topic bar */
.chat-topic {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    flex-shrink: 0;
}
.chat-topic-badge {
    font-size: 11px; padding: 2px 8px; border-radius: 20px;
    background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.5);
    text-transform: capitalize;
}
.chat-topic-status {
    font-size: 11px; font-weight: 600; text-transform: capitalize;
}
.status-open        { color: #fbbf24; }
.status-in_progress { color: #60a5fa; }
.status-closed      { color: rgba(255,255,255,0.35); }

/* Messages */
.chat-messages {
    flex: 1; overflow-y: auto;
    padding: 12px 16px;
    min-height: 400px;
    max-height: 400px;
    display: flex; flex-direction: column; gap: 8px;
}
.chat-empty {
    text-align: center; font-size: 12px;
    color: rgba(255,255,255,0.3); padding: 20px 0;
}
.msg { display: flex; }
.msg--user  { justify-content: flex-end; }
.msg--admin { justify-content: flex-start; }

.msg__bubble {
    max-width: 75%;
    padding: 8px 12px;
    border-radius: 12px;
    display: flex; flex-direction: column; gap: 3px;
}
.msg--user  .msg__bubble {
    background: rgba(121,249,149,0.12);
    border: 1px solid rgba(121,249,149,0.18);
    border-bottom-right-radius: 3px;
}
.msg--admin .msg__bubble {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    border-bottom-left-radius: 3px;
}
.msg__text { font-size: 13px; color: #fff; white-space: pre-wrap; word-break: break-word; line-height: 1.4; }
.msg__time { font-size: 10px; color: rgba(255,255,255,0.35); text-align: right; }

/* Input */
.chat-input-wrap { padding: 10px 12px; border-top: 1px solid rgba(255,255,255,0.07); flex-shrink: 0; }
.chat-input-row { display: flex; gap: 8px; align-items: flex-end; }

.chat-textarea {
    width: 100%; background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px; padding: 10px 12px;
    color: #fff; font-size: 13px; resize: none;
    outline: none; transition: border-color 0.2s;
}
.chat-textarea:focus { border-color: rgba(121,249,149,0.4); }
.chat-textarea::placeholder { color: rgba(255,255,255,0.3); }
.chat-textarea--sm { flex: 1; }
.chat-send-btn span{
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.chat-send-btn {
    width: 100%; margin-top: 10px;
    padding: 10px; border-radius: 10px;
    background: rgba(121,249,149,0.15); border: 1px solid rgba(121,249,149,0.3);
    color: #79f995; font-size: 13px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background 0.2s;
}
.chat-send-btn:hover:not(:disabled) { background: rgba(121,249,149,0.25); }
.chat-send-btn:disabled { opacity: 0.4; cursor: default; }

.chat-send-icon {
    flex-shrink: 0; width: 36px; height: 36px; border-radius: 8px;
    background: rgba(121,249,149,0.15); border: 1px solid rgba(121,249,149,0.25);
    color: #79f995; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.chat-send-icon:hover:not(:disabled) { background: rgba(121,249,149,0.3); }
.chat-send-icon:disabled { opacity: 0.3; cursor: default; }

.chat-error { font-size: 11px; color: #ef4444; margin-top: 6px; }
.chat-closed {
    text-align: center; font-size: 12px;
    color: rgba(255,255,255,0.35);
    padding: 12px;
    border-top: 1px solid rgba(255,255,255,0.07);
}
</style>
