<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    bills: {
        type: Array,
        default: () => [],
    },
});

const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('portfolio-withdraw'),
    set: (value) => (value ? modal.open('portfolio-withdraw') : modal.close('portfolio-withdraw')),
});

// Step 1: choose type. Step 2: fill in the form.
const step = ref(1);
const withdrawType = ref('');

const TYPES = [
    {
        key: 'crypto',
        label: 'Crypto wallet',
        description: 'Send to any cryptocurrency address',
        icon: '₿',
    },
    {
        key: 'bank',
        label: 'Bank account',
        description: 'Transfer to your bank account',
        icon: '🏦',
    },
    {
        key: 'card',
        label: 'Card',
        description: 'Withdraw to a debit or credit card',
        icon: '💳',
    },
];

const ALL_NETWORKS = ['USDTTRC20', 'USDTERC20', 'USDTBEP20', 'BTC', 'ETH', 'BNB'];

const wallets = computed(() => (props.bills ?? []).filter((b) => !b.demo));
const selectedBill = ref(null);
const isDropdownOpen = ref(false);

const form = useForm({
    bill_id:      '',
    withdraw_type: '',
    network:      '',
    address:      '',
    amount:       '',
    holder_name:  '',
    bank_name:    '',
    iban:         '',
});

watch(wallets, (newVal) => {
    if (newVal?.length) {
        selectedBill.value = newVal[0];
        form.bill_id = newVal[0].id;
    } else {
        selectedBill.value = null;
        form.bill_id = '';
    }
}, { immediate: true });

watch(isOpen, (val) => {
    if (!val) {
        step.value = 1;
        withdrawType.value = '';
        form.reset();
        isDropdownOpen.value = false;
        if (wallets.value?.length) {
            selectedBill.value = wallets.value[0];
            form.bill_id = wallets.value[0].id;
        }
    }
});

function selectType(key) {
    withdrawType.value = key;
    form.withdraw_type = key;
    form.network = '';
    form.address = '';
    form.amount = '';
    form.holder_name = '';
    form.bank_name = '';
    form.iban = '';
    step.value = 2;
}

function goBack() {
    step.value = 1;
    withdrawType.value = '';
}

function selectBill(bill) {
    selectedBill.value = bill;
    form.bill_id = bill.id;
    isDropdownOpen.value = false;
}

const amountNumber   = computed(() => Number.parseFloat(form.amount || '0'));
const feePercent     = computed(() => Number.parseFloat(selectedBill.value?.currency?.send_percent ?? selectedBill.value?.currency?.withdraw_fee ?? 0) || 0);
const feeFixed       = computed(() => Number.parseFloat(selectedBill.value?.currency?.send_fixed ?? selectedBill.value?.currency?.withdraw_fee_fixed ?? 0) || 0);

const fee = computed(() => {
    if (!selectedBill.value || !form.amount) return 0;
    const percentFee = amountNumber.value * (feePercent.value / 100);
    return Number((percentFee + feeFixed.value).toFixed(8));
});

const receiveAmount = computed(() => {
    const result = amountNumber.value - fee.value;
    if (!Number.isFinite(result) || result <= 0) return 0;
    return Number(result.toFixed(8));
});

const availableBalance = computed(() => Number.parseFloat(selectedBill.value?.balance ?? '0'));
const minWithdraw      = computed(() => Number.parseFloat(selectedBill.value?.currency?.min_withdraw ?? '0'));
const currencySymbol   = computed(() => selectedBill.value?.currency?.symbol ?? '');
const currencyName     = computed(() => selectedBill.value?.currency?.name ?? '');
const iconPath         = computed(() => {
    const icon = selectedBill.value?.currency?.icon ?? currencySymbol.value;
    return icon ? `/images/coin_icons/${String(icon).toLowerCase()}.svg` : null;
});

const canSubmit = computed(() => {
    if (!selectedBill.value || !form.amount || form.processing) return false;
    if (withdrawType.value === 'crypto') return !!(form.network && form.address);
    if (withdrawType.value === 'bank')   return !!(form.address && form.holder_name && form.bank_name);
    if (withdrawType.value === 'card')   return !!(form.address && form.holder_name);
    return false;
});

function submitWithdraw() {
    form.post('/account/withdraw', {
        preserveScroll: true,
        onSuccess: () => {
            toast.showSuccess('Withdrawal request submitted successfully');
            isOpen.value = false;
        },
        onError: (errors) => {
            const msg = Object.values(errors)[0];
            if (msg) toast.showError(msg);
        },
    });
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

            <!-- Step 1: Choose destination type -->
            <template v-if="step === 1">
                <h2 class="h1_25 pb10">Withdraw</h2>
                <p class="text_18 pb25">Choose where to send your funds</p>

                <div class="type-grid">
                    <button
                        v-for="t in TYPES"
                        :key="t.key"
                        type="button"
                        class="type-card"
                        @click="selectType(t.key)"
                    >
                        <span class="type-icon">{{ t.icon }}</span>
                        <span class="type-label">{{ t.label }}</span>
                        <span class="type-desc">{{ t.description }}</span>
                        <span class="type-arrow">→</span>
                    </button>
                </div>
            </template>

            <!-- Step 2: Form -->
            <template v-else>
                <div class="step2-header pb20">
                    <button type="button" class="back-btn clear" @click="goBack">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                            <path d="M11 14L6 9L11 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Back
                    </button>
                    <h2 class="h1_25">
                        {{ TYPES.find(t => t.key === withdrawType)?.label }}
                    </h2>
                </div>

                <form @submit.prevent="submitWithdraw" class="withdraw-form">
                    <!-- Bill selector (shared for all types) -->
                    <div class="pb20">
                        <p class="text_16 _115 color-gray2 pb10">Select balance</p>
                        <div class="currency-selector">
                            <div
                                class="simple-select"
                                :class="{ open: isDropdownOpen, error: form.errors.bill_id }"
                                @click.stop="isDropdownOpen = !isDropdownOpen"
                            >
                                <div v-if="selectedBill" class="selected-info">
                                    <img v-if="iconPath" :src="iconPath" alt="" />
                                    <div class="item-text">
                                        <span class="symbol">{{ currencySymbol }}</span>
                                        <span class="balance">Balance: {{ availableBalance.toFixed(8) }}</span>
                                    </div>
                                </div>
                                <div v-else class="simple-select__placeholder">Choose balance</div>
                                <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <transition name="fade">
                                <div v-if="isDropdownOpen" class="simple-select__dropdown">
                                    <div class="dropdown-list">
                                        <button
                                            v-for="bill in wallets"
                                            :key="bill.id"
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ active: selectedBill?.id === bill.id }"
                                            @click.stop="selectBill(bill)"
                                        >
                                            <div class="item-info">
                                                <span class="symbol">{{ bill.currency?.symbol }}</span>
                                                <span class="name">{{ bill.currency?.name }}</span>
                                            </div>
                                            <span class="amount">{{ Number.parseFloat(bill.balance ?? '0').toFixed(8) }}</span>
                                        </button>
                                        <p v-if="!wallets.length" class="dropdown-empty">No balances found</p>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <div v-if="form.errors.bill_id" class="error-message">{{ form.errors.bill_id }}</div>
                    </div>

                    <!-- CRYPTO fields -->
                    <template v-if="withdrawType === 'crypto'">
                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Network</p>
                            <div class="network-grid" :class="{ error: form.errors.network }">
                                <button
                                    v-for="net in ALL_NETWORKS"
                                    :key="net"
                                    type="button"
                                    class="network-btn"
                                    :class="{ active: form.network === net }"
                                    @click.stop="form.network = net"
                                >
                                    {{ net }}
                                </button>
                            </div>
                            <div v-if="form.errors.network" class="error-message">{{ form.errors.network }}</div>
                        </div>

                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Wallet address</p>
                            <input
                                type="text"
                                v-model="form.address"
                                class="input"
                                :class="{ error: form.errors.address }"
                                placeholder="Enter wallet address"
                                autocomplete="off"
                            />
                            <div v-if="form.errors.address" class="error-message">{{ form.errors.address }}</div>
                        </div>
                    </template>

                    <!-- BANK fields -->
                    <template v-else-if="withdrawType === 'bank'">
                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Account holder name</p>
                            <input
                                type="text"
                                v-model="form.holder_name"
                                class="input"
                                :class="{ error: form.errors.holder_name }"
                                placeholder="Full name as on account"
                                autocomplete="off"
                            />
                            <div v-if="form.errors.holder_name" class="error-message">{{ form.errors.holder_name }}</div>
                        </div>

                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Bank name</p>
                            <input
                                type="text"
                                v-model="form.bank_name"
                                class="input"
                                :class="{ error: form.errors.bank_name }"
                                placeholder="Bank name"
                                autocomplete="off"
                            />
                            <div v-if="form.errors.bank_name" class="error-message">{{ form.errors.bank_name }}</div>
                        </div>

                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Account number / IBAN</p>
                            <input
                                type="text"
                                v-model="form.address"
                                class="input"
                                :class="{ error: form.errors.address }"
                                placeholder="Enter IBAN or account number"
                                autocomplete="off"
                            />
                            <div v-if="form.errors.address" class="error-message">{{ form.errors.address }}</div>
                        </div>
                    </template>

                    <!-- CARD fields -->
                    <template v-else-if="withdrawType === 'card'">
                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Cardholder name</p>
                            <input
                                type="text"
                                v-model="form.holder_name"
                                class="input"
                                :class="{ error: form.errors.holder_name }"
                                placeholder="Name on card"
                                autocomplete="off"
                            />
                            <div v-if="form.errors.holder_name" class="error-message">{{ form.errors.holder_name }}</div>
                        </div>

                        <div class="pb20">
                            <p class="text_16 _115 color-gray2 pb10">Card number</p>
                            <input
                                type="text"
                                v-model="form.address"
                                class="input"
                                :class="{ error: form.errors.address }"
                                placeholder="0000 0000 0000 0000"
                                maxlength="19"
                                autocomplete="off"
                                @input="(e) => { form.address = e.target.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim(); }"
                            />
                            <div v-if="form.errors.address" class="error-message">{{ form.errors.address }}</div>
                        </div>
                    </template>

                    <!-- Amount (shared) -->
                    <div class="pb20" v-if="selectedBill">
                        <div class="amount-head">
                            <p class="text_16 _115 color-gray2">Amount {{ currencyName }}</p>
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
                        <div v-if="form.errors.amount" class="error-message">{{ form.errors.amount }}</div>
                        <p class="text_small_12 color-gray2 pt5">
                            Available: {{ availableBalance.toFixed(8) }} {{ currencySymbol }}
                        </p>
                        <p v-if="minWithdraw" class="text_small_12 color-gray2 pt5">
                            Minimum: {{ minWithdraw.toFixed(8) }} {{ currencySymbol }}
                        </p>
                    </div>

                    <!-- Fee info -->
                    <div class="withdraw-info pb20" v-if="selectedBill && form.amount">
                        <div class="flex-between">
                            <div>
                                <p class="text_small_14 color-gray2">Fee</p>
                                <p class="text_16">{{ fee.toFixed(8) }} {{ currencySymbol }}</p>
                                <p class="text_small_12 color-gray2">
                                    ({{ feePercent.toFixed(2) }}% + {{ feeFixed.toFixed(8) }} {{ currencySymbol }})
                                </p>
                            </div>
                            <div>
                                <p class="text_small_14 color-gray2">You will receive</p>
                                <p class="text_16">{{ receiveAmount.toFixed(8) }} {{ currencySymbol }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.errors.kyc" class="error-message pb15">{{ form.errors.kyc }}</div>

                    <button
                        type="submit"
                        class="btn btn_action btn_16 color-dark"
                        :disabled="!canSubmit"
                    >
                        {{ form.processing ? 'Processing...' : 'Withdraw' }}
                    </button>
                </form>
            </template>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.type-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.type-card {
    display: grid;
    grid-template-columns: 40px 1fr auto;
    grid-template-rows: auto auto;
    align-items: center;
    gap: 0 12px;
    padding: 18px 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    color: white;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
}

.type-card:hover {
    border-color: rgba(121, 249, 149, 0.45);
    background: rgba(121, 249, 149, 0.06);
}

.type-icon {
    grid-row: 1 / 3;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.type-label {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.2;
}

.type-desc {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    line-height: 1.3;
}

.type-arrow {
    grid-row: 1 / 3;
    font-size: 18px;
    color: rgba(255, 255, 255, 0.4);
    transition: color 0.2s ease, transform 0.2s ease;
}

.type-card:hover .type-arrow {
    color: rgba(121, 249, 149, 0.9);
    transform: translateX(3px);
}

.step2-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: color 0.2s ease;
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    flex-shrink: 0;
}

.back-btn:hover {
    color: white;
    border-color: rgba(255, 255, 255, 0.2);
}

.withdraw-form {
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

.simple-select:hover {
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

.selected-info img {
    width: 28px;
    height: 28px;
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
    max-height: 220px;
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

.withdraw-info {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 18px;
}

.network-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.network-grid.error .network-btn {
    border-color: rgba(239, 68, 68, 0.4);
}

.network-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.network-btn:hover {
    border-color: rgba(121, 249, 149, 0.4);
    color: white;
}

.network-btn.active {
    border-color: rgba(121, 249, 149, 0.7);
    background: rgba(121, 249, 149, 0.15);
    color: white;
    font-weight: 600;
}

.error-message {
    margin-top: 6px;
    font-size: 12px;
    color: rgba(239, 68, 68, 0.9);
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
