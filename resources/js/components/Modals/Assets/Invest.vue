<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { VueFinalModal } from 'vue-final-modal';
import { useUserStore } from '../../../stores/userStore.js';
const userStore = useUserStore()

const wallets = computed(() => (userStore.bills ?? []).filter((i: any) => i.demo == 0));
const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('invest'),
    set: (value) => (value ? modal.open('invest') : modal.close('invest')),
});

const selectedBill = ref(null);
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const form = useForm({
    bill_id: '',
    amount: '',
    portfolio_id: '',
    address: '',
});

watch(wallets, (newVal) => {
    const list = newVal ?? [];
    if (list.length) {
        selectedBill.value = list[0];
        form.bill_id = list[0].id;
    } else {
        selectedBill.value = null;
        form.reset();
    }
}, { immediate: true });

function toggleDropdown() {
    if (!wallets.value.length) return;
    isDropdownOpen.value = !isDropdownOpen.value;
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function selectBill(bill) {
    selectedBill.value = bill;
    form.bill_id = bill.id;
    closeDropdown();
}

function handleClickOutside(event: Event) {
    if (!dropdownRef.value) {
        return;
    }

    const target = event.target as Node | null;
    if (target && !dropdownRef.value.contains(target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside as EventListener);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside as EventListener);
});

const amountNumber = computed(() => Number.parseFloat(form.amount || '0'));
const feePercent = computed(() => Number.parseFloat(selectedBill.value?.currency?.send_percent ?? selectedBill.value?.currency?.withdraw_fee ?? 0) || 0);
const feeFixed = computed(() => Number.parseFloat(selectedBill.value?.currency?.send_fixed ?? selectedBill.value?.currency?.withdraw_fee_fixed ?? 0) || 0);

const fee = computed(() => {
    if (!selectedBill.value || !form.amount) {
        return 0;
    }

    const percentFee = amountNumber.value * (feePercent.value / 100);

    return Number((percentFee + feeFixed.value).toFixed(8));
});

const receiveAmount = computed(() => {
    if (!selectedBill.value) {
        return 0;
    }

    const result = amountNumber.value - fee.value;

    if (!Number.isFinite(result) || result <= 0) {
        return 0;
    }

    return Number(result.toFixed(8));
});

const availableBalance = computed(() => Number.parseFloat(selectedBill.value?.balance ?? '0'));
const minWithdraw = computed(() => Number.parseFloat(selectedBill.value?.currency?.min_withdraw ?? '0'));
const currencySymbol = computed(() => selectedBill.value?.currency?.symbol ?? '');
const currencyName = computed(() => selectedBill.value?.currency?.name ?? '');
const iconPath = computed(() => {
    const icon = selectedBill.value?.currency?.icon ?? currencySymbol.value;

    return icon ? `/images/coin_icons/${String(icon).toLowerCase()}.svg` : null;
});

function submitWithdraw() {
    form.post('/account/withdraw', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Withdrawal request submitted successfully');
            form.reset('address', 'amount');
            isOpen.value = false;
            closeDropdown();
        },
        onError: (errors: any) => {
            if (errors.amount) {
                toast.error(errors.amount);
            }

            if (errors.address) {
                toast.error(errors.address);
            }

            if (errors.bill_id) {
                toast.error(errors.bill_id);
            }
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
            <h2 class="h1_25 pb15">Invest</h2>
      <p class="text_18 pb25">Select an active portfolio</p>

            <form @submit.prevent="submitWithdraw" class="withdraw-form">
                <div class="pb20">
                    <p class="text_16 _115 color-gray2 pb10">Select cryptocurrency</p>
                    <div class="currency-selector" ref="dropdownRef">
                        <div
                            class="simple-select"
                            :class="{ open: isDropdownOpen, error: form.errors.bill_id }"
                            @click.stop="toggleDropdown"
                        >
                            <div v-if="selectedBill" class="selected-info">
                                <img v-if="iconPath" :src="iconPath" alt="" />
                                <div class="item-text">
                                    <span class="symbol">{{ currencySymbol }}</span>
                                    <span class="balance">Balance: {{ availableBalance.toFixed(8) }}</span>
                                </div>
                            </div>
                            <div v-else class="simple-select__placeholder">
                                Choose cryptocurrency
                            </div>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <transition name="fade">
                            <div v-if="isDropdownOpen" class="simple-select__dropdown" @click.stop>
                                <div class="dropdown-list">
                                    <button
                                        v-for="bill in wallets"
                                        :key="bill.id"
                                        type="button"
                                        class="dropdown-item"
                                        :class="{ active: selectedBill?.id === bill.id }"
                                        @click="selectBill(bill)"
                                    >
                                        <div class="item-info">
                                            <span class="symbol">{{ bill.name }}</span>
                                            <span class="name">{{ bill.currency?.name }}</span>
                                        </div>
                                        <span class="amount">{{ Number.parseFloat(bill.balance ?? '0').toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!wallets.length" class="dropdown-empty">No balances found</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div v-if="form.errors.bill_id" class="error-message text-red">
                        {{ form.errors.bill_id }}
                    </div>
                </div>

                <div class="pb20" v-if="selectedBill">
                    <p class="text_16 _115 color-gray2 pb10"></p>
                    <input
                        type="text"
                        v-model="form.address"
                        class="input"
                        :class="{ error: form.errors.address }"
                        placeholder="Enter wallet address"
                        autocomplete="off"
                    />
                    <div v-if="form.errors.address" class="error-message text-red">
                        {{ form.errors.address }}
                    </div>
                </div>

                <div class="pb20" v-if="selectedBill">
                    <div class="amount-head">
                        <p class="text_16 _115 color-gray2">
                            Amount {{ currencyName }}
                        </p>
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
                    <p v-if="minWithdraw" class="text_small_12 color-gray2 pt5">
                        Minimum withdrawal: {{ minWithdraw.toFixed(8) }} {{ currencySymbol }}
                    </p>
                </div>

                <div class="withdraw-info pb20" v-if="selectedBill && form.amount">
                    <div class="flex-between">
                        <div>
                            <p class="text_small_14 color-gray2">Fee</p>
                            <p class="text_16">
                                {{ fee.toFixed(8) }} {{ currencySymbol }}
                            </p>
                            <p class="text_small_12 color-gray2">
                                ({{ feePercent.toFixed(2) }}% + {{ feeFixed.toFixed(8) }} {{ currencySymbol }})
                            </p>
                        </div>
                        <div>
                            <p class="text_small_14 color-gray2">You will receive</p>
                            <p class="text_16">
                                {{ receiveAmount.toFixed(8) }} {{ currencySymbol }}
                            </p>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="!selectedBill || !form.address || !form.amount || form.processing"
                >
                    {{ form.processing ? 'Processing...' : 'Withdraw' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped>
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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
