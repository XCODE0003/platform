<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const { props } = usePage();

const isOpen = computed({
    get: () => modal.isOpen('ticket'),
    set: (v) => (v ? modal.open('ticket') : modal.close('ticket')),
});

const ticketExists = ref(false);
const messages = ref([]);

// Форма для создания тикета
const ticketForm = useForm({
    title: '',
    description: '',
    category: '',
});

// Форма для отправки сообщения
const messageForm = useForm({
    message: '',
    ticket_id: null,
});

function createTicket() {
    ticketForm.post('/support/tickets', {
        onSuccess: (response) => {
            ticketExists.value = true;
            messageForm.ticket_id = response.props.ticket?.id;
            ticketForm.reset();
        },
    });
}

function sendMessage() {
    messageForm.post('/support/messages', {
        onSuccess: () => {
            messageForm.reset('message');
            // Reload messages
        },
    });
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
        <div class="modal show">
            <button class="closemodal clear" @click="isOpen = false">
                <img src="/images/modal_close.svg" alt="" />
            </button>

            <!-- Create new ticket -->
            <div v-if="!ticketExists">
                <h2 class="h1_25 pb15">Create Support Ticket</h2>
                <p class="text_16 _115 color-gray2 pb25">
                    Describe your issue and we'll help you resolve it
                </p>

                <form @submit.prevent="createTicket">
                    <div class="pb15">
                        <select
                            v-model="ticketForm.category"
                            class="input"
                            required
                        >
                            <option value="">Select category</option>
                            <option value="technical">Technical Issue</option>
                            <option value="account">Account Problem</option>
                            <option value="trading">Trading Question</option>
                            <option value="other">Other</option>
                        </select>
                        <div
                            v-if="ticketForm.errors.category"
                            class="error-message text-red"
                        >
                            {{ ticketForm.errors.category }}
                        </div>
                    </div>

                    <div class="pb15">
                        <input
                            type="text"
                            v-model="ticketForm.title"
                            class="input"
                            placeholder="Ticket title"
                            :class="{ error: ticketForm.errors.title }"
                            required
                        />
                        <div
                            v-if="ticketForm.errors.title"
                            class="error-message text-red"
                        >
                            {{ ticketForm.errors.title }}
                        </div>
                    </div>

                    <div class="pb20">
                        <textarea
                            v-model="ticketForm.description"
                            class="input"
                            placeholder="Describe your issue..."
                            rows="4"
                            :class="{ error: ticketForm.errors.description }"
                            required
                        ></textarea>
                        <div
                            v-if="ticketForm.errors.description"
                            class="error-message text-red"
                        >
                            {{ ticketForm.errors.description }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn_action btn_16 color-dark"
                        :disabled="
                            !ticketForm.title ||
                            !ticketForm.description ||
                            !ticketForm.category ||
                            ticketForm.processing
                        "
                    >
                        {{
                            ticketForm.processing
                                ? 'Creating...'
                                : 'Create Ticket'
                        }}
                    </button>
                </form>
            </div>

            <!-- Existing ticket chat -->
            <div v-else>
                <h2 class="h1_25 pb15">Support Chat</h2>

                <div
                    class="messages-container pb20"
                    style="max-height: 300px; overflow-y: auto"
                >
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="message pb10"
                    >
                        <div class="message-author text_small_12 color-gray2">
                            {{ message.is_admin ? 'Support' : 'You' }}
                        </div>
                        <div class="message-content text_16">
                            {{ message.content }}
                        </div>
                    </div>
                </div>

                <form @submit.prevent="sendMessage">
                    <div class="pb15">
                        <textarea
                            v-model="messageForm.message"
                            class="input"
                            placeholder="Type your message..."
                            rows="3"
                            :class="{ error: messageForm.errors.message }"
                            required
                        ></textarea>
                        <div
                            v-if="messageForm.errors.message"
                            class="error-message text-red"
                        >
                            {{ messageForm.errors.message }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn_action btn_16 color-dark"
                        :disabled="
                            !messageForm.message || messageForm.processing
                        "
                    >
                        {{
                            messageForm.processing
                                ? 'Sending...'
                                : 'Send Message'
                        }}
                    </button>
                </form>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
