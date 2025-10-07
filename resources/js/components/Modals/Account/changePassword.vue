<script setup>
import { useModalStore } from '@/stores/modal.js';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('changePassword'),
    set: (v) =>
        v ? modal.open('changePassword') : modal.close('changePassword'),
});

const ChangePasswordModel = ref({
    old_password: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({
    old_password: '',
    password: '',
    password_confirmation: '',
});

async function changePassword() {
    // Replace with real Inertia POST when backend route is ready
    errors.value.old_password = '';
    errors.value.password = '';
    errors.value.password_confirmation = '';
    isOpen.value = false;
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
                v-model="ChangePasswordModel.old_password"
                :class="{ 'input-wrong': errors.old_password }"
                name="old_password"
                placeholder="Current password"
                required
            />
            <input
                type="password"
                name="password"
                class="input mb10"
                v-model="ChangePasswordModel.password"
                :class="{ 'input-wrong': errors.password }"
                placeholder="New password"
                required
                title="8 - 30 characters"
            />
            <input
                type="password"
                name="password_confirmation"
                v-model="ChangePasswordModel.password_confirmation"
                :class="{ 'input-wrong': errors.password_confirmation }"
                class="input mb25"
                placeholder="Confirm new password"
                required
            />
            <button
                type="submit"
                @click="changePassword"
                :disabled="
                    !ChangePasswordModel.old_password ||
                    !ChangePasswordModel.password ||
                    !ChangePasswordModel.password_confirmation
                "
                class="btn btn_action btn_16 color-dark trigger-changepassword"
            >
                Confirm
            </button>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
