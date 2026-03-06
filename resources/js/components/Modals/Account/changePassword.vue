<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('changePassword'),
    set: (v) =>
        v ? modal.open('changePassword') : modal.close('changePassword'),
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function changePassword() {
    form.post('/account/change-password', {
        preserveScroll: true,
        onSuccess: () => {
            toast.showSuccess('Password changed successfully');
            form.reset();
            isOpen.value = false;
        },
        onError: (errors) => {
            Object.values(errors).forEach((msg) => toast.showError(msg));
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
            <h2 class="h1_25 pb15">Change password</h2>

            <input
                type="password"
                class="input mb10"
                v-model="form.current_password"
                :class="{ 'input-wrong': form.errors.current_password }"
                name="current_password"
                placeholder="Current password"
                required
            />
            <input
                type="password"
                name="password"
                class="input mb10"
                v-model="form.password"
                :class="{ 'input-wrong': form.errors.password }"
                placeholder="New password"
                required
                title="8 - 30 characters"
            />
            <input
                type="password"
                name="password_confirmation"
                v-model="form.password_confirmation"
                :class="{ 'input-wrong': form.errors.password_confirmation }"
                class="input mb25"
                placeholder="Confirm new password"
                required
            />
            <button
                type="submit"
                @click="changePassword"
                :disabled="
                    !form.current_password ||
                    !form.password ||
                    !form.password_confirmation ||
                    form.processing
                "
                class="btn btn_action btn_16 color-dark trigger-changepassword"
            >
                Confirm
            </button>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
