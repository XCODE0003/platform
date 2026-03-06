<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal  = useModalStore();
const page   = usePage();

const isOpen = computed({
    get: () => modal.isOpen('ticket'),
    set: (v) => (v ? modal.open('ticket') : modal.close('ticket')),
});

// Ticket from server (Inertia prop)
const userTicket = computed(() => page.props.userTicket ?? null);
const messages   = computed(() => userTicket.value?.messages ?? []);
const ticketOpen = computed(() => !!userTicket.value);

// Scroll to bottom of messages on open / update
const messagesEl = ref(null);
function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}
watch(isOpen, (v) => { if (v) scrollToBottom(); });
watch(messages, () => { if (isOpen.value) scrollToBottom(); }, { deep: true });

// Form: create ticket
const ticketForm = useForm({
    title:       '',
    description: '',
    category:    '',
});

function createTicket() {
    ticketForm.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: () => ticketForm.reset(),
    });
}

// Form: send message
const messageForm = useForm({
    ticket_id: null,
    message:   '',
});

watch(userTicket, (t) => {
    if (t) messageForm.ticket_id = t.id;
}, { immediate: true });

function sendMessage() {
    if (!messageForm.message.trim()) return;
    messageForm.post('/support/messages', {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset('message');
            scrollToBottom();
        },
    });
}

const categoryLabels = {
    technical: 'Technical Issue',
    account:   'Account Problem',
    trading:   'Trading Question',
    other:     'Other',
};

function formatTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString();
}
</script>

<template>
    <VueFinalModal
        v-model="isOpen"
        overlay-transition="vfm-fade"
        content-transition="vfm-fade"
        click-to-close
        esc-to-close
        background="non-interactive"
        lock-scroll
        class="flex items-center justify-center"
        content-class="max-w-xl mx-4 p-4 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg"
    >
        <div class="modal show ticket-modal">
            <button class="closemodal clear" @click="isOpen = false">
                <img src="/images/modal_close.svg" alt="" />
            </button>

            <!-- Create new ticket -->
            <div v-if="!ticketOpen">
                <h2 class="h1_25 pb15">Create Support Ticket</h2>
                <p class="text_16 _115 color-gray2 pb25">
                    Describe your issue and we'll help you resolve it
                </p>

                <form @submit.prevent="createTicket">
                    <div class="pb15">
                        <select v-model="ticketForm.category" class="input" :class="{ error: ticketForm.errors.category }" required>
                            <option value="">Select category</option>
                            <option value="technical">Technical Issue</option>
                            <option value="account">Account Problem</option>
                            <option value="trading">Trading Question</option>
                            <option value="other">Other</option>
                        </select>
                        <p v-if="ticketForm.errors.category" class="err-msg">{{ ticketForm.errors.category }}</p>
                    </div>

                    <div class="pb15">
                        <input
                            type="text"
                            v-model="ticketForm.title"
                            class="input"
                            :class="{ error: ticketForm.errors.title }"
                            placeholder="Ticket title"
                            required
                        />
                        <p v-if="ticketForm.errors.title" class="err-msg">{{ ticketForm.errors.title }}</p>
                    </div>

                    <div class="pb20">
                        <textarea
                            v-model="ticketForm.description"
                            class="input"
                            :class="{ error: ticketForm.errors.description }"
                            placeholder="Describe your issue..."
                            rows="4"
                            required
                        ></textarea>
                        <p v-if="ticketForm.errors.description" class="err-msg">{{ ticketForm.errors.description }}</p>
                    </div>

                    <button
                        type="submit"
                        class="btn btn_action btn_16 color-dark"
                        :disabled="!ticketForm.title || !ticketForm.description || !ticketForm.category || ticketForm.processing"
                    >
                        {{ ticketForm.processing ? 'Creating...' : 'Create Ticket' }}
                    </button>
                </form>
            </div>

            <!-- Active ticket chat -->
            <div v-else class="chat-view">
                <div class="chat-header pb15">
                    <h2 class="h1_25">Support Chat</h2>
                    <div class="ticket-meta pt5">
                        <span class="ticket-badge" :class="'badge-' + userTicket.status">
                            {{ userTicket.status === 'in_progress' ? 'In Progress' : userTicket.status === 'open' ? 'Open' : 'Closed' }}
                        </span>
                        <span class="text_small_12 color-gray2">{{ categoryLabels[userTicket.category] ?? userTicket.category }}</span>
                        <span class="text_small_12 color-gray2">·</span>
                        <span class="text_small_12 color-gray2">{{ userTicket.title }}</span>
                    </div>
                </div>

                <div class="messages-wrap" ref="messagesEl">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="msg-row"
                        :class="msg.is_admin ? 'msg-admin' : 'msg-user'"
                    >
                        <div class="msg-bubble">
                            <div class="msg-sender text_small_12">{{ msg.is_admin ? 'Support' : 'You' }}</div>
                            <div class="msg-text text_16">{{ msg.content }}</div>
                            <div class="msg-time text_small_12 color-gray2">{{ formatTime(msg.created_at) }}</div>
                        </div>
                    </div>
                    <p v-if="!messages.length" class="no-messages text_small_12 color-gray2">No messages yet</p>
                </div>

                <form v-if="userTicket.status !== 'closed'" @submit.prevent="sendMessage" class="reply-form">
                    <textarea
                        v-model="messageForm.message"
                        class="input reply-input"
                        :class="{ error: messageForm.errors.message }"
                        placeholder="Type your message..."
                        rows="3"
                        required
                    ></textarea>
                    <p v-if="messageForm.errors.message" class="err-msg">{{ messageForm.errors.message }}</p>
                    <button
                        type="submit"
                        class="btn btn_action btn_16 color-dark mt10"
                        :disabled="!messageForm.message.trim() || messageForm.processing"
                    >
                        {{ messageForm.processing ? 'Sending...' : 'Send Message' }}
                    </button>
                </form>
                <p v-else class="text_small_12 color-gray2 pt10 text-center">This ticket is closed</p>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.ticket-modal { min-width: 420px; }

/* Meta row */
.ticket-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ticket-badge { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: capitalize; }
.badge-open        { background: rgba(251,191,36,0.15); color: #fbbf24; }
.badge-in_progress { background: rgba(59,130,246,0.15); color: #60a5fa; }
.badge-closed      { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5); }

/* Messages */
.messages-wrap {
    max-height: 300px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px 0 10px;
    margin-bottom: 16px;
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.msg-row { display: flex; }
.msg-user  { justify-content: flex-end; }
.msg-admin { justify-content: flex-start; }

.msg-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.msg-user  .msg-bubble { background: rgba(121,249,149,0.1); border: 1px solid rgba(121,249,149,0.15); border-bottom-right-radius: 4px; }
.msg-admin .msg-bubble { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-bottom-left-radius: 4px; }

.msg-sender { font-weight: 600; font-size: 11px; opacity: 0.7; }
.msg-text   { font-size: 14px; white-space: pre-wrap; word-break: break-word; }
.msg-time   { font-size: 10px; text-align: right; }

.no-messages { text-align: center; padding: 20px 0; }

/* Reply form */
.reply-form { display: flex; flex-direction: column; }
.reply-input { resize: none; }
.mt10 { margin-top: 10px; }
.text-center { text-align: center; }

/* Errors */
.err-msg { margin-top: 4px; font-size: 12px; color: #ef4444; }
</style>
