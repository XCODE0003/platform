<script setup>
import { useModalStore } from '@/stores/modal.js';
import { computed } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('confirmEmail'),
    set: (v) => (v ? modal.open('confirmEmail') : modal.close('confirmEmail')),
});
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
            <h2 class="h1_25 pb15">Email Confirmation</h2>
            <p class="text_16 _115 color-gray2 pb25">
                Please check your email and click the confirmation link to
                verify your email address.
            </p>
            <button
                class="btn btn_action btn_16 color-dark"
                @click="isOpen = false"
            >
                Close
            </button>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
