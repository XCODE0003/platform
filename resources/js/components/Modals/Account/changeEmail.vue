<script setup>
import { useModalStore } from '@/stores/modal.js';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const page = usePage();
const currentEmail = page.props.user?.email ?? '';

const isOpen = computed({
    get: () => modal.isOpen('changeMail'),
    set: (v) => (v ? modal.open('changeMail') : modal.close('changeMail')),
});

function emailValidator(email) {
    return /[^@\s]+@[^@\s]+\.[^@\s]+/.test(email);
}

const step = ref('changeMail');
const loading = ref(false);

const changeEmailModel = ref({
    email: '',
    code: '',
});

onMounted(() => {
    let isEmailChanging = localStorage.getItem('EmailChanging');
    if (isEmailChanging) {
        isEmailChanging = JSON.parse(isEmailChanging);
        if (
            isEmailChanging.changing === true &&
            isEmailChanging.date > Date.now() - 60000
        ) {
            step.value = 'confirmEmail';
            changeEmailModel.value.email = isEmailChanging.email;
        }
    }
});

const emits = defineEmits(['update:updateEmail']);

function updateEmail(value) {
    emits('update:updateEmail', value);
}

function changeStep(newStep) {
    step.value = newStep;
}

async function changeEmail() {
    loading.value = true;
    if (step.value === 'changeMail') {
        changeStep('confirmEmail');
        localStorage.setItem(
            'EmailChanging',
            JSON.stringify({
                changing: true,
                date: Date.now(),
                email: changeEmailModel.value.email,
            }),
        );
        loading.value = false;
        return;
    }
    updateEmail(changeEmailModel.value.email);
    localStorage.removeItem('EmailChanging');
    loading.value = false;
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
            <div v-if="step === 'changeMail'">
                <button class="closemodal clear" @click="isOpen = false">
                    <img src="/images/modal_close.svg" alt="" />
                </button>
                <h2 class="h1_25 pb15">Change e-mail address</h2>
                <p class="text_16 _115 color-gray2 pb25">
                    Enter a new address and we will send a confirmation code for
                    changing the e-mail address for your account
                </p>

                <input
                    type="email"
                    class="input mb25"
                    v-model="changeEmailModel.email"
                    placeholder="address@email.com"
                />
                <button
                    :disabled="
                        !emailValidator(changeEmailModel.email) ||
                        loading === true
                    "
                    type="button"
                    @click="changeEmail()"
                    class="btn btn_action btn_16 color-dark"
                >
                    {{ loading ? 'Loading...' : 'Next' }}
                </button>
            </div>
            <div v-if="step === 'confirmEmail'">
                <button class="closemodal clear" @click="isOpen = false">
                    <img src="/images/modal_close.svg" alt="" />
                </button>
                <h2 class="h1_25 pb15">Check your e-mail</h2>
                <p class="text_16 _115 color-gray2 pb25">
                    We have sent the email change confirmation code to your
                    email address
                    <span>{{ currentEmail }}</span>
                </p>
                <input
                    type="text"
                    class="input mb25"
                    v-model="changeEmailModel.code"
                    placeholder="2sF8GH"
                />
                <button
                    type="button"
                    @click="changeEmail()"
                    :disabled="
                        changeEmailModel.code.length < 6 ||
                        changeEmailModel.code.length > 6
                    "
                    class="btn btn_action btn_16 color-dark"
                >
                    Confirm
                </button>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
