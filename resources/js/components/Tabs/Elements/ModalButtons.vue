<script setup>
import { useModalStore } from '@/stores/modal.js';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const modal = useModalStore();
const page = usePage();

const kycApproved = computed(() => page.props.auth?.kyc_approved ?? false);
</script>

<template>
    <div class="assets-title-block_end flex-center gap10">

        <button class="btn small_btn btn_16 deposit" @click="modal.open('deposit')">
            Deposit
        </button>
        <button class="btn small_btn btn_16" @click="modal.open('promocode')">
            Promocode
        </button>

        <button
            class="btn small_btn btn_16"
            :disabled="!kycApproved"
            :title="kycApproved ? '' : 'KYC verification required for withdrawals'"
            @click="kycApproved && modal.open('withdraw')"
        >
            Withdraw
        </button>

    </div>
</template>

<style scoped></style>
