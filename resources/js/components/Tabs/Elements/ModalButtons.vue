<script setup>
import { useModalStore } from '@/stores/modal.js';

const modal = useModalStore();

function openPortfolioWithdraw() {
    // Ensure transfer dialog is closed before opening withdraw.
    modal.close('transfer');
    modal.close('withdraw');
    modal.open('portfolio-withdraw');
}
</script>

<template>
    <div class="assets-title-block_end flex-center gap10">

        <button class="btn small_btn btn_16 deposit" @click="modal.open('deposit')">
            Deposit
        </button>
        <button class="btn small_btn btn_16" @click="modal.open('promocode')">
            Promocode
        </button>

        <button class="btn small_btn btn_16" @click="modal.open('transfer')">
            Transfer
        </button>

        <button
            class="btn small_btn btn_16"
            @click="openPortfolioWithdraw"
        >
            Withdraw
        </button>

    </div>
</template>

<style scoped></style>
