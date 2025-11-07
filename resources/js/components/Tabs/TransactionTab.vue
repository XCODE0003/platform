<script setup>
import { computed, defineProps } from 'vue';
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';

const props = defineProps({
    withdraws: {
        type: Array,
        default: () => [],
    },
});

const transactions = computed(() => props.withdraws ?? []);

function iconPath(withdraw) {
    const icon = withdraw.currency?.icon ?? withdraw.currency?.symbol ?? 'btc';

    return `/images/coin_icons/${String(icon).toLowerCase()}.svg`;
}

function formatAmount(value) {
    const number = Number.parseFloat(value ?? '0');

    return Number.isFinite(number) ? number.toFixed(8) : '0.00000000';
}

function formatStatus(status) {
    if (!status) {
        return 'pending';
    }

    return status.charAt(0).toUpperCase() + status.slice(1);
}

function statusClass(status) {
    switch (status) {
        case 'completed':
            return 'status-success';
        case 'rejected':
            return 'status-danger';
        case 'processing':
            return 'status-warning';
        default:
            return 'status-pending';
    }
}

function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}
</script>

<template>
    <div class="tab-item">
        <div class="assets-title-block">
            <div class="assets-title-block_start">
                <h1 class="h1_25">Transaction history</h1>
                <button class="btn clear">
                    <img src="/images/transaction-history.svg" alt="" />
                </button>
            </div>
            <ModalButtons />
        </div>
        <div class="assets-search pt40 pb40">
            <label class="assets-search_input">
                <input
                    type="text"
                    class="clear text_small_14"
                    placeholder="Search"
                />
            </label>
        </div>
        <div class="assets-overview-grid assets-history-grid pb60">
            <div class="grid-head text_small_12 color-dark">
                <div>Coin</div>
                <div>Quantity</div>
                <div>Type</div>
                <div>Status</div>
                <div>Date, time</div>
            </div>

            <div
                v-for="withdraw in transactions"
                :key="withdraw.id"
                class="grid-line"
            >
                <div class="flex-center gap6">
                    <img :src="iconPath(withdraw)" width="30" height="30" alt="" />
                    <span>{{ withdraw.currency?.symbol }}</span>
                </div>
                <div>
                    <span class="text_16">{{ formatAmount(withdraw.net_amount ?? withdraw.amount) }}</span>
                </div>
                <div>
                    <span class="text_16">Withdraw</span>
                </div>
                <div>
                    <span class="text_16" :class="statusClass(withdraw.status)">
                        {{ formatStatus(withdraw.status) }}
                    </span>
                </div>
                <div>
                    <span class="text_16">{{ formatDate(withdraw.created_at) }}</span>
                </div>
            </div>

            <p v-if="!transactions.length" class="notfound">
                Nothing found
                <img src="/images/notfound.svg" alt="" />
            </p>
        </div>
    </div>
</template>

<style scoped>
.notfound {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px 0 20px;
    color: rgba(255, 255, 255, 0.55);
}

.status-success {
    color: #79f995;
}

.status-danger {
    color: #f97777;
}

.status-warning {
    color: #ffe560;
}

.status-pending {
    color: rgba(255, 255, 255, 0.65);
}
</style>
