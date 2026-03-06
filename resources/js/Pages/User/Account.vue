<script setup>
import Change2FA from '@/components/Modals/Account/change2FA.vue';
import ChangeEmail from '@/components/Modals/Account/changeEmail.vue';
import ChangePassword from '@/components/Modals/Account/changePassword.vue';
import Kyc from '@/components/Modals/Account/kyc.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { useModalStore } from '@/stores/modal.js';
import { useSupportChatStore } from '@/stores/supportChatStore.js';
import { randomUUID } from '@/utils/system';
import QRCode from "qrcode";
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/userStore.js';
const userStore = useUserStore();

const props = defineProps({
    qrText:     String,
    kycData:    Object,
    userTicket: { type: Object, default: null },
})
const user = computed(() => usePage().props.auth.user);
const qrCode = ref('');
const kycData = computed(
    () =>  props.kycData ||
       {
            sex: '',
            first_name: '',
            last_name: '',
            phone: '',
            date_of_birth: '',
            country: '',
            city: '',
            address: '',
            zip_code: '',
            status: 'start',
            error_message: '',

    },
);
const generateQRCode = async () => {

    try {
        const qrDataURL = await QRCode.toDataURL(props.qrText, {
            width: 160,
            margin: 0,
            color: {
                dark: '#212E5A',
                light: '#E8EDFF'
            },
            errorCorrectionLevel: 'M'
        });
        qrCode.value = qrDataURL;
    } catch (error) {
        console.error('Ошибка генерации QR-кода:', error);
    } finally {

    }
};

onMounted(() => {
    generateQRCode();
});

const Email = ref(user.value.email);
const is_2fa = computed(() => userStore.user?.google_2fa_enabled);



function updateModel(newData) {
    kycData.value = newData;

    router.reload({ only: ['kycData'] });
}

function updateEmail(email) {
    Email.value = email;

    router.reload({ only: ['user'] });
}

function UpdateInfo2fa(value) {
    is_2fa.value = value;
    router.reload({ only: ['user'] });
}

const modal = useModalStore();
const supportChat = useSupportChatStore();

const kycStatus = computed(() => kycData.value?.status ?? 'start');
const kycPassed = computed(() => kycStatus.value === 'approved');
const kycPending = computed(() => kycStatus.value === 'pending');
const kycRejected = computed(() => kycStatus.value === 'rejected');

</script>

<template>
    <MainLayout>
        <main class="account h100">
            <section class="account">
                <div class="container">
                    <div class="account-content">
                        <div class="account-block account-head">
                            <div class="line">
                                <div class="account-name">
                                    <h1 class="h1_25" id="account-name">
                                        Account

                                    </h1>
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 18 18"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M9 18C4.02943 18 0 13.9705 0 9C0 4.02943 4.02943 0 9 0C13.9705 0 18 4.02943 18 9C18 13.9705 13.9705 18 9 18ZM4.5 9C4.5 11.4853 6.51472 13.5 9 13.5C11.4853 13.5 13.5 11.4853 13.5 9H11.7C11.7 10.4912 10.4912 11.7 9 11.7C7.50879 11.7 6.3 10.4912 6.3 9H4.5Z"
                                            fill="white"
                                        />
                                    </svg>
                                </div>
                                <div class="account-info">
                                    <!-- <div class="info-block">
                                        <span class="title">UID</span>
                                        <span class="context" id="uid">{{
                                           randomUUID( user.id)
                                        }}</span>
                                    </div> -->
                                    <!-- <div class="info-block">
                                        <span class="title">Status</span>
                                        <span class="context">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="12"
                                                height="11"
                                                viewBox="0 0 12 11"
                                                fill="none"
                                            >
                                                <path
                                                    d="M0.867468 1.98557L3.21037 3.58285L5.54601 0.238967C5.72511 -0.0174442 6.07359 -0.076833 6.32432 0.10632C6.37443 0.142915 6.41822 0.187738 6.45404 0.238967L8.78971 3.58285L11.1326 1.98557C11.389 1.81078 11.7354 1.88163 11.9063 2.14382C11.9798 2.25656 12.0116 2.39238 11.9962 2.52697L11.0794 10.4961C11.0463 10.7835 10.8082 11 10.5253 11H1.47479C1.19188 11 0.953741 10.7835 0.920683 10.4961L0.00387518 2.52697C-0.0321281 2.21402 0.18677 1.93048 0.492795 1.89366C0.6244 1.87783 0.757215 1.9104 0.867468 1.98557ZM6.00005 7.57669C6.61629 7.57669 7.11592 7.06582 7.11592 6.43559C7.11592 5.80541 6.61629 5.29448 6.00005 5.29448C5.38376 5.29448 4.88419 5.80541 4.88419 6.43559C4.88419 7.06582 5.38376 7.57669 6.00005 7.57669Z"
                                                    fill="#FFE560"
                                                />
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="info-block">
                                        <span class="title">Timezone</span>
                                        <span class="context" id="timezone">{{
                                            user.timezone
                                        }}</span>
                                    </div> -->
                                </div>
                            </div>
                        </div>

                        <div class="account-block account-settings">
                            <div class="line">
                                <div class="title text_17">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#344955"/>
                                    </svg>
                                    Name
                                </div>
                                <div class="content">
                                    <input
                                        type="text"
                                        readonly
                                        class="clear text_17"
                                        :value="[user.first_name, user.last_name].filter(Boolean).join(' ') || '—'"
                                    />
                                </div>
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.62 10.79C8.06 13.62 10.38 15.94 13.21 17.38L15.41 15.18C15.69 14.9 16.08 14.82 16.43 14.93C17.55 15.3 18.75 15.5 20 15.5C20.55 15.5 21 15.95 21 16.5V20C21 20.55 20.55 21 20 21C10.61 21 3 13.39 3 4C3 3.45 3.45 3 4 3H7.5C8.05 3 8.5 3.45 8.5 4C8.5 5.25 8.7 6.45 9.07 7.57C9.18 7.92 9.1 8.31 8.82 8.59L6.62 10.79Z" fill="#344955"/>
                                    </svg>
                                    Phone
                                </div>
                                <div class="content">
                                    <input
                                        type="text"
                                        readonly
                                        class="clear text_17"
                                        :value="user.phone || '—'"
                                    />
                                </div>
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="12"
                                        height="11"
                                        viewBox="0 0 12 11"
                                        fill="none"
                                    >
                                        <path
                                            d="M0.6 0H11.4C11.7314 0 12 0.273607 12 0.611111V10.3889C12 10.7264 11.7314 11 11.4 11H0.6C0.268632 11 0 10.7264 0 10.3889V0.611111C0 0.273607 0.268632 0 0.6 0ZM6.03636 5.30622L2.18833 1.97859L1.41167 2.91029L6.04386 6.91601L10.5926 2.90654L9.80736 1.98235L6.03636 5.30622Z"
                                            fill="#344955"
                                        />
                                    </svg>
                                    E-mail
                                </div>

                                <div class="content">
                                    <input
                                        type="text"
                                        id="account-email"
                                        readonly
                                        class="clear account-email text_17"
                                        :value="user.email"
                                    />
                                    <button
                                        id="toggleButton"
                                        class="hidemail d-none"
                                    >
                                        <svg
                                            width="20"
                                            height="17"
                                            viewBox="0 0 20 17"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M0 8.5C0.869343 3.66422 5.01587 0 10 0C14.9841 0 19.1307 3.66422 20 8.5C19.1307 13.3357 14.9841 17 10 17C5.01587 17 0.869343 13.3357 0 8.5ZM10 13.2222C12.5525 13.2222 14.6217 11.108 14.6217 8.5C14.6217 5.89199 12.5525 3.77778 10 3.77778C7.44752 3.77778 5.37833 5.89199 5.37833 8.5C5.37833 11.108 7.44752 13.2222 10 13.2222ZM10 11.3333C8.4685 11.3333 7.227 10.0648 7.227 8.5C7.227 6.93515 8.4685 5.66667 10 5.66667C11.5315 5.66667 12.773 6.93515 12.773 8.5C12.773 10.0648 11.5315 11.3333 10 11.3333Z"
                                                fill="#606E76"
                                            />
                                            <rect
                                                x="0.40625"
                                                y="4.11719"
                                                width="2"
                                                height="21"
                                                rx="1"
                                                transform="rotate(-60 0.40625 4.11719)"
                                                fill="white"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <!-- <div class="action">
                                    <button
                                        @click="modal.open('changeMail')"
                                        class="btn small_btn btn_16"
                                    >
                                        Change
                                    </button>
                                </div> -->
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg
                                        width="12"
                                        height="14"
                                        viewBox="0 0 12 14"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M10 4.66667H11.3333C11.7015 4.66667 12 4.96515 12 5.33333V13.3333C12 13.7015 11.7015 14 11.3333 14H0.666667C0.29848 14 0 13.7015 0 13.3333V5.33333C0 4.96515 0.29848 4.66667 0.666667 4.66667H2V4C2 1.79086 3.79086 0 6 0C8.20913 0 10 1.79086 10 4V4.66667ZM8.66667 4.66667V4C8.66667 2.52724 7.47273 1.33333 6 1.33333C4.52724 1.33333 3.33333 2.52724 3.33333 4V4.66667H8.66667ZM5.33333 8.66667V10H6.66667V8.66667H5.33333ZM2.66667 8.66667V10H4V8.66667H2.66667ZM8 8.66667V10H9.33333V8.66667H8Z"
                                            fill="#344955"
                                        />
                                    </svg>

                                    Password
                                </div>
                                <div class="content">
                                    <input
                                        type="text"
                                        readonly
                                        class="clear text_17"
                                        value="************"
                                    />
                                </div>
                                <div class="action">
                                    <button
                                        class="btn small_btn btn_16"
                                        @click="modal.open('changePassword')"
                                    >
                                        Change
                                    </button>
                                </div>
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg
                                        width="10"
                                        height="12"
                                        viewBox="0 0 10 12"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M0.435039 0.995989L5 0L9.56495 0.995989C9.81917 1.05145 10 1.2728 10 1.52846V6.97576C10 8.07 9.443 9.09186 8.51567 9.69884L5 12L1.48433 9.69884C0.557006 9.09186 0 8.07 0 6.97576V1.52846C0 1.2728 0.18085 1.05145 0.435039 0.995989Z"
                                            fill="#344955"
                                        />
                                    </svg>

                                    2FA
                                </div>
                                <div class="content">
                                    <input
                                        type="text"
                                        readonly
                                        class="clear text_17"
                                        value=""
                                    />
                                </div>
                                <div class="action">
                                    <button
                                        :class="{ bg_danger: is_2fa }"
                                        class="btn small_btn btn_16"
                                        @click="modal.open('change2fa')"
                                    >
                                        {{ is_2fa ? 'Disable' : 'Enable' }}
                                    </button>
                                </div>
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg
                                        width="13"
                                        height="13"
                                        viewBox="0 0 13 13"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M5.21001 0.0946591C4.30286 -0.199004 3.31669 0.209474 2.88287 1.0586L2.36133 2.07942C2.29936 2.20072 2.20072 2.29936 2.07942 2.36133L1.0586 2.88287C0.209474 3.31669 -0.199004 4.30286 0.0946591 5.21001L0.447707 6.30063C0.489655 6.43021 0.489655 6.56975 0.447707 6.69933L0.0946591 7.78995C-0.199004 8.69713 0.209474 9.68328 1.0586 10.1171L2.07942 10.6386C2.20072 10.7006 2.29936 10.7993 2.36133 10.9206L2.88287 11.9414C3.31669 12.7905 4.30286 13.199 5.21001 12.9053L6.30063 12.5523C6.43021 12.5103 6.56975 12.5103 6.69933 12.5523L7.78995 12.9053C8.69713 13.199 9.68328 12.7905 10.1171 11.9414L10.6386 10.9206C10.7006 10.7993 10.7993 10.7006 10.9206 10.6386L11.9414 10.1171C12.7905 9.68328 13.199 8.69713 12.9053 7.78995L12.5523 6.69933C12.5103 6.56975 12.5103 6.43021 12.5523 6.30063L12.9053 5.21001C13.199 4.30286 12.7905 3.31669 11.9414 2.88287L10.9206 2.36133C10.7993 2.29936 10.7006 2.20072 10.6386 2.07942L10.1171 1.0586C9.68328 0.209474 8.69713 -0.199004 7.78995 0.0946591L6.69933 0.447707C6.56975 0.489649 6.43021 0.489655 6.30063 0.447707L5.21001 0.0946591ZM3.10825 6.34289L4.0236 5.42749L5.85428 7.25823L9.5157 3.59684L10.431 4.51219L5.85428 9.08891L3.10825 6.34289Z"
                                            fill="#344955"
                                        />
                                    </svg>
                                    KYC
                                </div>
                                <div class="content">
                                    <div class="kyc-status-row flex-center gap8">
                                        <span
                                            v-if="kycPassed"
                                            class="kyc-badge kyc-badge-passed"
                                        >
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>
                                            </svg>
                                            Passed
                                        </span>
                                        <span
                                            v-else-if="kycPending"
                                            class="kyc-badge kyc-badge-pending"
                                        >
                                            Pending verification
                                        </span>
                                        <span
                                            v-else-if="kycRejected"
                                            class="kyc-badge kyc-badge-rejected"
                                        >
                                            Rejected
                                        </span>
                                        <span v-else class="kyc-badge kyc-badge-not-passed">
                                            Not passed
                                        </span>
                                    </div>
                                    <p
                                        v-if="kycRejected && kycData.error_message"
                                        class="text-red2 text_small_12 mt5"
                                    >
                                        {{ kycData.error_message }}
                                    </p>
                                </div>
                                <div class="action">
                                    <button
                                        class="btn small_btn btn_16"
                                        :class="{
                                            bg_success: kycPassed,
                                            bg_danger: kycRejected,
                                        }"
                                        :disabled="kycPassed"
                                        @click="modal.open('verify')"
                                    >
                                        {{
                                            kycPassed
                                                ? 'Verified'
                                                : kycPending
                                                  ? 'View application'
                                                  : kycRejected
                                                    ? 'Resend application'
                                                    : 'Send KYC application'
                                        }}
                                    </button>
                                </div>
                            </div>
                            <div class="line">
                                <div class="title text_17">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="#344955"/>
                                    </svg>
                                    Support
                                </div>
                                <div class="content"></div>
                                <div class="action">
                                    <button class="btn small_btn btn_16" @click="supportChat.openChat()">
                                        Contact Support
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <ChangeEmail @update:updateEmail="updateEmail" />
            <ChangePassword />
            <Change2FA :qr="qrCode" @update:UpdateInfo2fa="UpdateInfo2fa" />
            <Kyc :kycData="kycData" @update:modelValue="updateModel" />
        </main>
    </MainLayout>
</template>

<style scoped>
.kyc-status-row { flex-wrap: wrap; }
.kyc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 20px;
}
.kyc-badge-passed {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}
.kyc-badge-pending {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}
.kyc-badge-rejected {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}
.kyc-badge-not-passed {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.6);
}
.mt5 { margin-top: 5px; }
</style>
