<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { useUserStore } from '@/stores/userStore.js';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    bills: {
        type: Array,
        default: () => [],
    },
    portfolioWallets: {
        type: Array,
        default: () => [],
    },
});

const modal = useModalStore();
const toast = useToast();
const userStore = useUserStore();

const isOpen = computed({
    get: () => modal.isOpen('transfer'),
    set: (v) => (v ? modal.open('transfer') : modal.close('transfer')),
});

// Always read from userStore (kept fresh after transfer); fall back to prop.
const allBills = computed(() => {
    const list = userStore.bills ?? props.bills ?? [];
    return Array.isArray(list) ? list : [];
});

const allPortfolioWallets = computed(() => {
    const list = props.portfolioWallets ?? [];
    return Array.isArray(list) ? list : [];
});

const fromBill = ref(null);
// Destination is a tagged option: { kind: 'bill' | 'wallet', ref: <bill|wallet> }
const toTarget = ref(null);
const fromOpen = ref(false);
const toOpen = ref(false);
// Active tab inside the destination dropdown.
const toTab = ref('bill');

const form = useForm({
    target_kind: 'bill',
    from_bill_id: '',
    to_bill_id: '',
    to_wallet_id: '',
    amount: '',
});

// Eligible destination bills: same currency + same demo flag, but not the same row.
const eligibleBillTargets = computed(() => {
    if (!fromBill.value) return [];
    return allBills.value.filter((b) =>
        b.id !== fromBill.value.id
        && Number(b.currency_id) === Number(fromBill.value.currency_id)
        && Boolean(b.demo) === Boolean(fromBill.value.demo)
    );
});

// Portfolio destinations: only for real (non-demo) source bills; backend
// converts currency automatically, so any wallet is eligible.
const eligibleWalletTargets = computed(() => {
    if (!fromBill.value || fromBill.value.demo) return [];
    return allPortfolioWallets.value;
});

const hasAnyEligibleTarget = computed(() =>
    eligibleBillTargets.value.length > 0 || eligibleWalletTargets.value.length > 0,
);

function isTargetEligible(target) {
    if (!target) return false;
    if (target.kind === 'bill') {
        return eligibleBillTargets.value.some((b) => b.id === target.ref.id);
    }
    if (target.kind === 'wallet') {
        return eligibleWalletTargets.value.some((w) => w.id === target.ref.id);
    }
    return false;
}

watch(isOpen, (open) => {
    if (!open) return;
    // When opening, prefill source with first available bill.
    fromBill.value = allBills.value[0] ?? null;
    toTarget.value = null;
    toTab.value = 'bill';
    form.reset();
    form.clearErrors();
    form.from_bill_id = fromBill.value?.id ?? '';
});

function selectFrom(bill) {
    fromBill.value = bill;
    form.from_bill_id = bill.id;
    fromOpen.value = false;
    // Reset destination if it's no longer eligible.
    if (!isTargetEligible(toTarget.value)) {
        toTarget.value = null;
        form.to_bill_id = '';
        form.to_wallet_id = '';
    }
}

function selectToBill(bill) {
    toTarget.value = { kind: 'bill', ref: bill };
    form.target_kind = 'bill';
    form.to_bill_id = bill.id;
    form.to_wallet_id = '';
    toOpen.value = false;
}

function selectToWallet(wallet) {
    toTarget.value = { kind: 'wallet', ref: wallet };
    form.target_kind = 'wallet';
    form.to_wallet_id = wallet.id;
    form.to_bill_id = '';
    toOpen.value = false;
}

const availableBalance = computed(() => Number.parseFloat(fromBill.value?.balance ?? '0'));
const currencySymbol = computed(() => fromBill.value?.currency?.symbol ?? '');
const amountNumber = computed(() => Number.parseFloat(form.amount || '0'));
const isAmountValid = computed(() =>
    Number.isFinite(amountNumber.value)
    && amountNumber.value > 0
    && amountNumber.value <= availableBalance.value
);

function submit() {
    const onError = (errors) => {
        const msg = Object.values(errors)[0];
        if (msg) toast.showError(msg);
    };
    const onSuccess = async () => {
        toast.showSuccess('Transfer completed successfully');
        await userStore.fetchBills();
        isOpen.value = false;
    };

    if (toTarget.value?.kind === 'wallet') {
        form
            .transform((data) => ({
                bill_id: data.from_bill_id,
                wallet_id: data.to_wallet_id,
                amount: data.amount,
            }))
            .post('/portfolio/from-account', { preserveScroll: true, onSuccess, onError });
        return;
    }

    form
        .transform((data) => ({
            from_bill_id: data.from_bill_id,
            to_bill_id: data.to_bill_id,
            amount: data.amount,
        }))
        .post('/assets/bill/transfer', { preserveScroll: true, onSuccess, onError });
}

function walletDisplaySymbol(wallet) {
    return wallet?.currency?.symbol ?? wallet?.currency?.code ?? '';
}
function walletDisplayName(wallet) {
    return wallet?.currency?.name ?? walletDisplaySymbol(wallet);
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
            <h2 class="h1_25 pb15">Transfer between bills</h2>
            <p class="text_18 pb25">Move funds between your accounts</p>

            <form @submit.prevent="submit" class="transfer-form">
                <!-- From -->
                <div class="pb20">
                    <p class="text_16 _115 color-gray2 pb10">From</p>
                    <div class="currency-selector">
                        <div
                            class="simple-select"
                            :class="{ open: fromOpen, error: form.errors.from_bill_id }"
                            @click.stop="fromOpen = !fromOpen; toOpen = false"
                        >
                            <div v-if="fromBill" class="selected-info">
                                <div class="item-text">
                                    <span class="symbol">{{ fromBill.name }}</span>
                                    <span class="balance">
                                        {{ availableBalance.toFixed(8) }} {{ fromBill.currency?.symbol ?? '' }}
                                        <span v-if="fromBill.demo" class="demo-tag">DEMO</span>
                                    </span>
                                </div>
                            </div>
                            <div v-else class="simple-select__placeholder">
                                Choose source bill
                            </div>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <transition name="fade">
                            <div v-if="fromOpen" class="simple-select__dropdown">
                                <div class="dropdown-list">
                                    <button
                                        v-for="bill in allBills"
                                        :key="bill.id"
                                        type="button"
                                        class="dropdown-item"
                                        :class="{ active: fromBill?.id === bill.id }"
                                        @click.stop="selectFrom(bill)"
                                    >
                                        <div class="item-info">
                                            <span class="symbol">
                                                {{ bill.name }}
                                                <span v-if="bill.demo" class="demo-tag">DEMO</span>
                                            </span>
                                            <span class="name">{{ bill.currency?.symbol }}</span>
                                        </div>
                                        <span class="amount">{{ Number.parseFloat(bill.balance ?? '0').toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!allBills.length" class="dropdown-empty">No bills available</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div v-if="form.errors.from_bill_id" class="error-message text-red">
                        {{ form.errors.from_bill_id }}
                    </div>
                </div>

                <!-- To -->
                <div class="pb20">
                    <p class="text_16 _115 color-gray2 pb10">To</p>
                    <div class="currency-selector">
                        <div
                            class="simple-select"
                            :class="{
                                open: toOpen,
                                error: form.errors.to_bill_id || form.errors.to_wallet_id || form.errors.wallet_id,
                                disabled: !fromBill,
                            }"
                            @click.stop="fromBill && (toOpen = !toOpen, fromOpen = false)"
                        >
                            <div v-if="toTarget?.kind === 'bill'" class="selected-info">
                                <div class="item-text">
                                    <span class="symbol">{{ toTarget.ref.name }}</span>
                                    <span class="balance">
                                        {{ Number.parseFloat(toTarget.ref.balance ?? '0').toFixed(8) }} {{ toTarget.ref.currency?.symbol ?? '' }}
                                        <span v-if="toTarget.ref.demo" class="demo-tag">DEMO</span>
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="toTarget?.kind === 'wallet'" class="selected-info">
                                <div class="item-text">
                                    <span class="symbol">
                                        {{ walletDisplayName(toTarget.ref) }}
                                        <span class="portfolio-tag">PORTFOLIO</span>
                                    </span>
                                    <span class="balance">
                                        {{ Number.parseFloat(toTarget.ref.balance ?? '0').toFixed(8) }} {{ walletDisplaySymbol(toTarget.ref) }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="simple-select__placeholder">
                                {{ fromBill ? 'Choose destination' : 'Select source bill first' }}
                            </div>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <transition name="fade">
                            <div v-if="toOpen" class="simple-select__dropdown">
                                <div class="dropdown-tabs" @click.stop>
                                    <button
                                        type="button"
                                        class="dropdown-tab"
                                        :class="{ 'dropdown-tab--active': toTab === 'bill' }"
                                        @click="toTab = 'bill'"
                                    >
                                        Bills
                                        <span class="dropdown-tab__count">{{ eligibleBillTargets.length }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="dropdown-tab"
                                        :class="{ 'dropdown-tab--active': toTab === 'wallet' }"
                                        @click="toTab = 'wallet'"
                                    >
                                        Portfolio
                                        <span class="dropdown-tab__count">{{ eligibleWalletTargets.length }}</span>
                                    </button>
                                </div>
                                <div class="dropdown-list">
                                    <template v-if="toTab === 'bill'">
                                        <button
                                            v-for="bill in eligibleBillTargets"
                                            :key="`bill-${bill.id}`"
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ active: toTarget?.kind === 'bill' && toTarget.ref.id === bill.id }"
                                            @click.stop="selectToBill(bill)"
                                        >
                                            <div class="item-info">
                                                <span class="symbol">
                                                    {{ bill.name }}
                                                    <span v-if="bill.demo" class="demo-tag">DEMO</span>
                                                </span>
                                                <span class="name">{{ bill.currency?.symbol }}</span>
                                            </div>
                                            <span class="amount">{{ Number.parseFloat(bill.balance ?? '0').toFixed(8) }}</span>
                                        </button>
                                        <p v-if="!eligibleBillTargets.length" class="dropdown-empty">
                                            No matching bills (same currency &amp; type)
                                        </p>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-for="wallet in eligibleWalletTargets"
                                            :key="`wallet-${wallet.id}`"
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ active: toTarget?.kind === 'wallet' && toTarget.ref.id === wallet.id }"
                                            @click.stop="selectToWallet(wallet)"
                                        >
                                            <div class="item-info">
                                                <span class="symbol">
                                                    {{ walletDisplayName(wallet) }}
                                                </span>
                                                <span class="name">{{ walletDisplaySymbol(wallet) }}</span>
                                            </div>
                                            <span class="amount">{{ Number.parseFloat(wallet.balance ?? '0').toFixed(8) }}</span>
                                        </button>
                                        <p v-if="!eligibleWalletTargets.length" class="dropdown-empty">
                                            {{ fromBill?.demo ? 'Portfolio transfers unavailable for DEMO accounts' : 'No portfolio wallets available' }}
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div v-if="form.errors.to_bill_id || form.errors.to_wallet_id || form.errors.wallet_id" class="error-message text-red">
                        {{ form.errors.to_bill_id || form.errors.to_wallet_id || form.errors.wallet_id }}
                    </div>
                </div>

                <!-- Amount -->
                <div class="pb20" v-if="fromBill">
                    <div class="amount-head">
                        <p class="text_16 _115 color-gray2">Amount</p>
                        <button
                            type="button"
                            class="max-button"
                            @click="form.amount = availableBalance.toFixed(8)"
                        >
                            Max
                        </button>
                    </div>
                    <input
                        type="number"
                        step="0.00000001"
                        v-model="form.amount"
                        class="input"
                        :class="{ error: form.errors.amount }"
                        placeholder="0.00000000"
                        min="0"
                    />
                    <div v-if="form.errors.amount" class="error-message text-red">
                        {{ form.errors.amount }}
                    </div>
                    <p class="text_small_12 color-gray2 pt5">
                        Available: {{ availableBalance.toFixed(8) }} {{ currencySymbol }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="!fromBill || !toTarget || !isAmountValid || form.processing"
                >
                    {{ form.processing ? 'Transferring...' : 'Transfer' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.transfer-form {
    display: flex;
    flex-direction: column;
}

.currency-selector {
    position: relative;
}

.simple-select {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    cursor: pointer;
    transition: border 0.2s ease, background 0.2s ease;
}

.simple-select.disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.simple-select:hover:not(.disabled) {
    border-color: rgba(121, 249, 149, 0.4);
    background: rgba(255, 255, 255, 0.06);
}

.simple-select.open {
    border-color: rgba(121, 249, 149, 0.6);
}

.simple-select.error {
    border-color: rgba(239, 68, 68, 0.6);
}

.selected-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-text .symbol {
    font-weight: 600;
    color: white;
}

.item-text .balance {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.simple-select__placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.chevron {
    transition: transform 0.2s ease;
    color: rgba(255, 255, 255, 0.7);
}

.simple-select.open .chevron {
    transform: rotate(180deg);
}

.simple-select__dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #0a1f2b;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 8px;
    z-index: 40;
}

.dropdown-list {
    max-height: 240px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.dropdown-item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: white;
    transition: background 0.2s ease, border 0.2s ease;
}

.dropdown-item:hover {
    background: rgba(121, 249, 149, 0.12);
}

.dropdown-item.active {
    border-color: rgba(121, 249, 149, 0.35);
    background: rgba(121, 249, 149, 0.16);
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-align: left;
}

.item-info .symbol {
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.item-info .name {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.dropdown-item .amount {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
}

.dropdown-empty {
    text-align: center;
    padding: 18px 0 10px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.45);
}

.dropdown-tabs {
    display: flex;
    gap: 4px;
    padding: 0 0 8px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.dropdown-tab {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border: none;
    background: transparent;
    color: rgba(255, 255, 255, 0.55);
    font-size: 13px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.dropdown-tab:hover {
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.85);
}

.dropdown-tab--active {
    background: rgba(121, 249, 149, 0.16);
    color: #79F995;
}

.dropdown-tab__count {
    font-size: 11px;
    padding: 1px 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.7);
}

.dropdown-tab--active .dropdown-tab__count {
    background: rgba(121, 249, 149, 0.25);
    color: #79F995;
}

.portfolio-tag {
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 3px;
    background: rgba(121, 249, 149, 0.18);
    color: #79F995;
    letter-spacing: 0.5px;
}

.demo-tag {
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 3px;
    background: rgba(255, 184, 0, 0.18);
    color: #ffb800;
    letter-spacing: 0.5px;
}

.amount-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.max-button {
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.08);
    color: white;
    cursor: pointer;
    transition: background 0.2s ease;
}

.max-button:hover {
    background: rgba(121, 249, 149, 0.2);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
