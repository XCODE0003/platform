<script setup>
import { useModalStore } from '@/stores/modal.js';
import { computed, onMounted, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('change2fa'),
    set: (v) => (v ? modal.open('change2fa') : modal.close('change2fa')),
});

const is_2fa = ref(false);
const ga2fa = ref({ qr: '/images/qrsample.svg', code: '' });

onMounted(async () => {
    // TODO: fetch QR code from backend when ready
});

async function enable2fa() {
    is_2fa.value = true;
    UpdateInfo2fa(is_2fa.value);
}

const emits = defineEmits(['update:UpdateInfo2fa']);

function UpdateInfo2fa(value) {
    emits('update:UpdateInfo2fa', value);
}

async function disable2fa() {
    is_2fa.value = false;
    UpdateInfo2fa(is_2fa.value);
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
            <div v-if="!is_2fa" class="first">
                <div class="gap flex">
                    <div class="text" style="max-width: 300px">
                        <h2 class="h1_25 pb15">
                            Two-Factor Authentication is
                            <b class="color-red">disabled</b>
                        </h2>
                        <p class="text_16 _115 color-gray2 pb25">
                            Scan the QR code with the Google Authenticator app
                            and enter the 6-digit 2FA code to enable 2FA
                            verification
                        </p>
                    </div>
                    <div class="qr">
                        <img width="100px" :src="ga2fa.qr" alt="" />
                    </div>
                </div>
                <form @submit.prevent="enable2fa">
                    <input
                        type="text"
                        class="input mb20"
                        v-model="ga2fa.code"
                        placeholder="Enter 6-digit code"
                    />
                    <button
                        type="submit"
                        :disabled="ga2fa.code === ''"
                        class="btn btn_action btn_16 color-dark trigger-enable2fa"
                    >
                        Confirm
                    </button>
                </form>
            </div>
            <div v-else class="second">
                <h2 class="h1_25 pb15">
                    Two-Factor Authentication is
                    <b class="color-green">enabled</b>
                </h2>
                <p class="text_16 _115 color-gray2 pb25">
                    Attention! Your account is now protected. If you disable
                    two-factor authentication, your account will be vulnerable.
                    We do not recommend doing this. But if you decide, then
                    enter here the 6-digit code from your Google Authenticator
                    app
                </p>
                <form @submit.prevent="disable2fa">
                    <input
                        type="text"
                        class="input mb20"
                        name="code"
                        v-model="ga2fa.code"
                        placeholder="Enter 6-digit code"
                    />
                    <button
                        type="submit"
                        :disabled="ga2fa.code === ''"
                        class="btn btn_action bg_danger btn_16 color-dark trigger-disable2fa"
                    >
                        Disable
                    </button>
                </form>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
