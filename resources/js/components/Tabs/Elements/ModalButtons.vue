<script setup>
import { useModalStore } from '@/stores/modal.js';
const modal = useModalStore();
</script>

<template>
    <div class="assets-title-block_end flex-center gap10">
        <button
            class="btn small_btn btn_16 deposit"
            @click="modal.open('deposit')"
        >
            Deposit
        </button>
        <button class="btn small_btn btn_16" @click="modal.open('promocode')">
            Promocode
        </button>

        <button class="btn small_btn btn_16" @click="modal.open('withdraw')">
            Withdraw
        </button>
    </div>
</template>

<style scoped></style>
