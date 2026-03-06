<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { computed, defineProps, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    depositWallets: {
        type: Array,
        default: () => [],
    },
});

const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('deposit'),
    set: (v) => (v ? modal.open('deposit') : modal.close('deposit')),
});

const selectedTab = ref('DepositCrypto');
const selectedCurrency = ref(null);
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const wallets = computed(() => props.depositWallets ?? []);

const filteredWallets = computed(() => wallets.value);

watch(wallets, (newVal) => {
    if (newVal?.length) {
        selectedCurrency.value = newVal[0];
    } else {
        selectedCurrency.value = null;
    }
}, { immediate: true });

function changeTab(tab) {
    selectedTab.value = tab;
    if (tab === 'DepositCrypto' && !selectedCurrency.value && wallets.value.length) {
        selectedCurrency.value = wallets.value[0];
    }
}

function toggleDropdown() {
    if (!wallets.value.length) return;
    isDropdownOpen.value = !isDropdownOpen.value;
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function selectCurrency(currency) {
    selectedCurrency.value = currency;
    closeDropdown();
}

function handleClickOutside(event) {
    if (!dropdownRef.value) return;
    if (!dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

async function copyToClipboard(value) {
    if (!value) return;
    try {
        await navigator.clipboard.writeText(value);
        toast.success('Address copied to clipboard');
    } catch (e) {
        console.warn('[Deposit] copy failed', e);
        toast.error('Failed to copy address');
    }
}

const hasAddress = computed(() => !!selectedCurrency.value?.address);
const minDeposit = computed(() => selectedCurrency.value?.currency?.min_deposit_amount ?? '0.001');
const currencySymbol = computed(() => selectedCurrency.value?.currency?.symbol ?? '');
const currencyIcon = computed(() => {
    const icon = selectedCurrency.value?.currency?.icon ?? selectedCurrency.value?.currency?.symbol;
    return icon ? `/images/coin_icons/${String(icon).toLowerCase()}.svg` : null;
});

function iconPathFor(wallet) {
    const icon = wallet.currency?.icon ?? wallet.currency?.symbol;
    if (!icon) return null;
    return `/images/coin_icons/${String(icon).toLowerCase()}.svg`;
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
            <h2 class="h1_25 pb15">Deposit</h2>
            <p class="text_18 pb25">
                Deposit your existing crypto assets via the blockchain
            </p>
            <p class="text_16 _115 color-gray2 pb10">
                What cryptocurrency do you want to deposit?
            </p>
            <div id="tabsDeposit">
                <ul class="tabs-navDeposit gap6 pb20 flex">
                    <li>
                        <button
                            :class="{ active: selectedTab === 'DepositCrypto' }"
                            @click="changeTab('DepositCrypto')"
                            class="text_small_14 assets-menu_btn"
                        >
                            Crypto
                        </button>
                    </li>
                    <li>
                        <button
                            :class="{ active: selectedTab === 'DepositFiat' }"
                            @click="changeTab('DepositFiat')"
                            class="text_small_14 assets-menu_btn"
                        >
                            Card
                        </button>
                    </li>
                </ul>
                <div class="tabs-itemsDeposit">
                    <!-- Crypto Deposit Tab -->
                    <div v-if="selectedTab === 'DepositCrypto'">
                        <div class="currency-selector pb20" ref="dropdownRef">
                            <div
                                class="simple-select"
                                :class="{ open: isDropdownOpen }"
                                @click.stop="toggleDropdown"
                            >
                                <div class="simple-select__value" v-if="selectedCurrency">
                                    {{ selectedCurrency.currency?.symbol }}
                                </div>
                                <div class="simple-select__placeholder" v-else>
                                    Select cryptocurrency
                                </div>
                                <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>

                            <transition name="fade">
                                <div v-if="isDropdownOpen" class="simple-select__dropdown" @click.stop>
                                    <div class="dropdown-list">
                                        <button
                                            v-for="wallet in filteredWallets"
                                            :key="wallet.id"
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ active: selectedCurrency?.id === wallet.id }"
                                            @click="selectCurrency(wallet)"
                                        >
                                            <span class="symbol">{{ wallet.currency?.symbol }}</span>
                                        </button>
                                        <p v-if="!filteredWallets.length" class="dropdown-empty">
                                            Nothing found
                                        </p>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <div v-if="selectedCurrency" class="deposit-info">
                            <div class="deposit-details flex justify-between items-center">
                                <p class="text_16 ">Deposit Address:</p>
                                <p class="text_small_14 color-gray2">
                                    Minimum deposit:
                                    {{ minDeposit }} {{ currencySymbol }}
                                </p>


                            </div>
                            <div class="deposit-address ">

                                <div class="input-container flex">
                                    <input
                                        type="text"
                                        :value="hasAddress ? selectedCurrency.address : 'Generating address...'"
                                        readonly
                                    />
                                    <button
                                        class="btn small_btn"
                                        type="button"
                                        :disabled="!hasAddress"
                                        @click.prevent="copyToClipboard(selectedCurrency.address)"
                                    >
                                        Copy
                                    </button>
                                </div>
                                <p v-if="selectedCurrency.memo" class="text_small_12 color-gray2 pt10">
                                    Memo / Tag: {{ selectedCurrency.memo }}
                                </p>
                                <p v-else-if="!hasAddress" class="text_small_12 color-gray2 pt10">
                                    Address generation may take a few minutes. Please refresh later.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Fiat Deposit Tab -->
                    <div v-if="selectedTab === 'DepositFiat'">
                        <p class="text_16 pb20">
                            Buy cryptocurrency with your credit/debit card
                        </p>
                        <button class="btn btn_action btn_16 color-dark">
                            Buy with Card
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.currency-selector {
    position: relative;
}

.simple-select {
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.simple-select__value {
    font-size: 15px;
    color: white;
    font-weight: 600;
}

.simple-select__placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.selected-info {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.network {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.52);
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
    background: #0A1F2B;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 8px;
    z-index: 30;
}

.dropdown-search input {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.04);
    color: #fff;
    transition: border 0.2s ease;
}

.dropdown-search input:focus {
    outline: none;
    border-color: rgba(121, 249, 149, 0.5);
}

.dropdown-list {
    max-height: 220px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
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
    align-items: center;
    gap: 12px;
}

.item-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-text .symbol {
    font-size: 14px;
    font-weight: 600;
    color: white;
}

.item-text .name {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.item-network {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
}

.dropdown-empty {
    text-align: center;
    padding: 20px 0 10px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.45);
}

.deposit-info {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.deposit-address .input-container {
    position: relative;
    gap: 8px;
}

.deposit-address input {
    flex: 1;
    padding: 12px 14px;
    border-radius: 10px;
    color: #fff;
}

.deposit-address input:focus {
    outline: none;
    border-color: rgba(121, 249, 149, 0.4);
}

.deposit-address .small_btn {
    min-width: 70px;
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
