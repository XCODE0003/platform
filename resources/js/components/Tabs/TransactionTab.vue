<script setup>
import { computed, defineProps } from 'vue';
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';

const props = defineProps({
    withdraws: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Array,
        default: () => [],
    },
});

// Human-readable labels for Transaction.type values coming from the backend.
const TYPE_LABELS = {
    deposit: 'Deposit',
    withdraw: 'Withdraw',
    transfer_in: 'Transfer in',
    transfer_out: 'Transfer out',
};

// Unified history: account top-ups / transfers (transactions) + withdrawals,
// newest first. Each row carries an optional manager comment shown to the user.
const history = computed(() => {
    const fromWithdraws = (props.withdraws ?? []).map((w) => ({
        key: `w-${w.id}`,
        currency: w.currency,
        amount: w.net_amount ?? w.amount,
        type: 'Withdraw',
        status: w.status,
        created_at: w.created_at,
        comment: w.comment ?? null,
    }));
    const fromTransactions = (props.transactions ?? []).map((t) => ({
        key: `t-${t.id}`,
        currency: t.currency,
        amount: t.amount,
        type: TYPE_LABELS[t.type] ?? t.type,
        status: t.status,
        created_at: t.created_at,
        comment: t.comment ?? null,
    }));
    return [...fromTransactions, ...fromWithdraws].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    );
});

function iconPath(item) {
    const icon = item.currency?.icon ?? item.currency?.symbol ?? 'btc';

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
                v-for="item in history"
                :key="item.key"
                class="grid-line"
            >
                <div class="flex-center gap6">
                    <img :src="iconPath(item)" width="30" height="30" alt="" />
                    <span>{{ item.currency?.symbol }}</span>
                </div>
                <div>
                    <span class="text_16">{{ formatAmount(item.amount) }}</span>
                </div>
                <div>
                    <span class="text_16">{{ item.type }}</span>
                    <span v-if="item.comment" class="text_small_12 color-gray2 transaction-comment">
                        {{ item.comment }}
                    </span>
                </div>
                <div>
                    <span class="text_16" :class="statusClass(item.status)">
                        {{ formatStatus(item.status) }}
                    </span>
                </div>
                <div>
                    <span class="text_16">{{ formatDate(item.created_at) }}</span>
                </div>
            </div>

            <p v-if="!history.length" class="notfound">
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

.transaction-comment {
    display: block;
    margin-top: 2px;
    line-height: 1.3;
    word-break: break-word;
}
</style>
