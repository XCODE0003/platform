<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    bills: { type: Array, default: () => [] },
    portfolioWallets: { type: Array, default: () => [] },
    portfolioFeePercent: { type: Number, default: 0 },
    portfolioFeeFixed:   { type: Number, default: 0 },
});

const modal = useModalStore();
const toast = useToast();

// 'to-account'   = Portfolio → Trading account  (открывается кнопкой 'portfolio-withdraw')
// 'to-portfolio' = Trading account → Portfolio  (открывается кнопкой 'invest')
const mode = ref('to-portfolio');

const isOpen = computed({
    get: () => modal.isOpen('invest') || modal.isOpen('portfolio-withdraw'),
    set: (v) => {
        if (!v) {
            modal.close('invest');
            modal.close('portfolio-withdraw');
        }
    },
});

watch(() => modal.isOpen('invest'),             (v) => { if (v) mode.value = 'to-portfolio'; });
watch(() => modal.isOpen('portfolio-withdraw'), (v) => { if (v) mode.value = 'to-account'; });

// ── Mode: Portfolio → Account ─────────────────────────────────────────────
const realBills = computed(() => (props.bills ?? []).filter((b) => !b.demo));
const selectedWallet    = ref(null);
const selectedBill      = ref(null);
const dropdownWallet    = ref(false);
const dropdownBillDst   = ref(false);

watch(selectedWallet, (w) => {
    if (!w) { selectedBill.value = null; formToAccount.wallet_id = ''; return; }
    formToAccount.wallet_id = w.id;
    // Auto-select matching bill, but allow manual override
    const match = realBills.value.find((b) => b.currency_id === w.currency_id);
    selectedBill.value    = match ?? null;
    formToAccount.bill_id = match?.id ?? '';
});

function selectBillDst(b) {
    selectedBill.value    = b;
    formToAccount.bill_id = b.id;
    dropdownBillDst.value = false;
}

const formToAccount = useForm({ wallet_id: '', bill_id: '', amount: '' });
const walletBalance    = computed(() => parseFloat(selectedWallet.value?.balance ?? '0'));
const walletSymbol     = computed(() => selectedWallet.value?.currency?.symbol ?? '');
const walletFeePercent = computed(() => props.portfolioFeePercent ?? 0);
const walletFeeFixed   = computed(() => props.portfolioFeeFixed   ?? 0);
const transferFee = computed(() => {
    const amt = parseFloat(formToAccount.amount || '0');
    if (!amt) return 0;
    return Number((amt * (walletFeePercent.value / 100) + walletFeeFixed.value).toFixed(8));
});
const transferNet = computed(() => {
    const amt = parseFloat(formToAccount.amount || '0');
    const net = amt - transferFee.value;
    return net > 0 ? Number(net.toFixed(8)) : 0;
});

function selectWallet(w) {
    selectedWallet.value = w;
    dropdownWallet.value = false;
}

function submitToAccount() {
    formToAccount.post('/portfolio/to-account', {
        preserveScroll: true,
        onSuccess: () => {
            toast.showSuccess('Successfully transferred to trading account');
            formToAccount.reset('amount');
            isOpen.value = false;
        },
        onError: (e) => { Object.values(e).forEach((msg) => toast.showError(msg)); },
    });
}

// ── Mode: Account → Portfolio ─────────────────────────────────────────────
const selectedBillSrc   = ref(null);
const selectedWalletDst = ref(null);
const dropdownBill      = ref(false);
const dropdownWalletDst = ref(false);

watch(selectedBillSrc, (b) => {
    if (!b) { formToPortfolio.bill_id = ''; return; }
    formToPortfolio.bill_id = b.id;
});

const formToPortfolio = useForm({ bill_id: '', wallet_id: '', amount: '' });
const billBalance = computed(() => parseFloat(selectedBillSrc.value?.balance ?? '0'));
const billSymbol  = computed(() => selectedBillSrc.value?.currency?.symbol ?? '');

// Conversion preview: how many wallet-currency units user will receive
const dstSymbol = computed(() => selectedWalletDst.value?.currency?.symbol ?? '');
const willReceive = computed(() => {
    const amt = parseFloat(formToPortfolio.amount || '0');
    if (!amt || !selectedBillSrc.value || !selectedWalletDst.value) return 0;
    if (selectedBillSrc.value.currency_id === selectedWalletDst.value.currency_id) return amt;
    const billRate   = parseFloat(selectedBillSrc.value?.currency?.exchange_rate   ?? '1');
    const walletRate = parseFloat(selectedWalletDst.value?.currency?.exchange_rate ?? '1');
    return walletRate > 0 ? Number((amt * billRate / walletRate).toFixed(8)) : amt;
});

function selectBillSrc(b) {
    selectedBillSrc.value = b;
    dropdownBill.value    = false;
}

function selectWalletDst(w) {
    selectedWalletDst.value   = w;
    formToPortfolio.wallet_id = w.id;
    dropdownWalletDst.value   = false;
}

function submitToPortfolio() {
    formToPortfolio.post('/portfolio/from-account', {
        preserveScroll: true,
        onSuccess: () => {
            toast.showSuccess('Successfully transferred to portfolio');
            formToPortfolio.reset('amount');
            isOpen.value = false;
        },
        onError: (e) => { Object.values(e).forEach((msg) => toast.showError(msg)); },
    });
}

// ── helpers ───────────────────────────────────────────────────────────────
function iconFor(obj) {
    const icon = obj?.currency?.icon ?? obj?.currency?.symbol ?? '';
    return icon ? `/images/coin_icons/${String(icon).toLowerCase()}.svg` : null;
}

function resetAll() {
    selectedWallet.value    = null;
    selectedBill.value      = null;
    selectedBillSrc.value   = null;
    selectedWalletDst.value = null;
    dropdownWallet.value    = false;
    dropdownBill.value      = false;
    dropdownWalletDst.value = false;
    formToAccount.reset();
    formToPortfolio.reset();
}

watch(isOpen, (v) => { if (!v) resetAll(); });
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

            <h2 class="h1_25 pb15">Transfer</h2>

            <!-- Mode tabs -->
            <div class="tabs-nav flex gap6 pb20">
                <button type="button" class="text_small_14 assets-menu_btn"
                    :class="{ active: mode === 'to-portfolio' }" @click="mode = 'to-portfolio'">
                    Account → Portfolio
                </button>
                <button type="button" class="text_small_14 assets-menu_btn"
                    :class="{ active: mode === 'to-account' }" @click="mode = 'to-account'">
                    Portfolio → Account
                </button>
            </div>

            <!-- ── Portfolio → Account ── -->
            <form v-if="mode === 'to-account'" @submit.prevent="submitToAccount" class="transfer-form">

                <!-- From: portfolio wallet (custom dropdown) -->
                <div class="pb20">
                    <p class="text_16 color-gray2 pb10">From portfolio</p>
                    <div class="custom-select" :class="{ open: dropdownWallet, error: formToAccount.errors.wallet_id }">
                        <div class="custom-select__trigger" @click="dropdownWallet = !dropdownWallet">
                            <template v-if="selectedWallet">
                                <img v-if="iconFor(selectedWallet)" :src="iconFor(selectedWallet)" width="24" height="24" alt="" />
                                <div class="select-item-text">
                                    <span class="symbol">{{ selectedWallet.currency?.symbol }}</span>
                                    <span class="balance">{{ walletBalance.toFixed(8) }}</span>
                                </div>
                            </template>
                            <span v-else class="placeholder">Choose currency</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <transition name="fade">
                            <div v-if="dropdownWallet" class="custom-select__dropdown" @click.stop>
                                <div class="dropdown-list">
                                    <button v-for="w in portfolioWallets" :key="w.id" type="button"
                                        class="dropdown-item" :class="{ active: selectedWallet?.id === w.id }"
                                        @click="selectWallet(w)">
                                        <div class="di-left">
                                            <img v-if="iconFor(w)" :src="iconFor(w)" width="22" height="22" alt="" />
                                            <div class="di-info">
                                                <span class="di-symbol">{{ w.currency?.symbol }}</span>
                                                <span class="di-name">{{ w.currency?.name }}</span>
                                            </div>
                                        </div>
                                        <span class="di-balance">{{ parseFloat(w.balance).toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!portfolioWallets.length" class="dropdown-empty">No portfolio wallets</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <p v-if="formToAccount.errors.wallet_id" class="error-msg">{{ formToAccount.errors.wallet_id }}</p>
                </div>

                <!-- To: trading account (auto-matched, manual override) -->
                <div class="pb20" v-if="selectedWallet">
                    <p class="text_16 color-gray2 pb10">To trading account</p>
                    <div class="custom-select" :class="{ open: dropdownBillDst, error: formToAccount.errors.bill_id }">
                        <div class="custom-select__trigger" @click="dropdownBillDst = !dropdownBillDst">
                            <template v-if="selectedBill">
                                <img v-if="iconFor(selectedBill)" :src="iconFor(selectedBill)" width="24" height="24" alt="" />
                                <div class="select-item-text">
                                    <span class="symbol">{{ selectedBill.name ?? selectedBill.currency?.symbol }}</span>
                                    <span class="balance">Balance: {{ parseFloat(selectedBill.balance).toFixed(8) }} {{ selectedBill.currency?.symbol }}</span>
                                </div>
                            </template>
                            <span v-else class="placeholder">Select trading account</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <transition name="fade">
                            <div v-if="dropdownBillDst" class="custom-select__dropdown" @click.stop>
                                <div class="dropdown-list">
                                    <button v-for="b in realBills" :key="b.id" type="button"
                                        class="dropdown-item" :class="{ active: selectedBill?.id === b.id }"
                                        @click="selectBillDst(b)">
                                        <div class="di-left">
                                            <img v-if="iconFor(b)" :src="iconFor(b)" width="22" height="22" alt="" />
                                            <div class="di-info">
                                                <span class="di-symbol">{{ b.currency?.symbol }}</span>
                                                <span class="di-name">{{ b.name ?? b.currency?.name }}</span>
                                            </div>
                                        </div>
                                        <span class="di-balance">{{ parseFloat(b.balance ?? '0').toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!realBills.length" class="dropdown-empty">No trading accounts found</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <p v-if="formToAccount.errors.bill_id" class="error-msg">{{ formToAccount.errors.bill_id }}</p>
                </div>

                <!-- Amount -->
                <div class="pb20" v-if="selectedWallet">
                    <div class="amount-head">
                        <p class="text_16 color-gray2">Amount ({{ walletSymbol }})</p>
                        <button type="button" class="max-button"
                            @click="formToAccount.amount = walletBalance.toFixed(8)">Max</button>
                    </div>
                    <input type="number" step="0.00000001" v-model="formToAccount.amount"
                        class="input" :class="{ error: formToAccount.errors.amount }"
                        placeholder="0.00000000" min="0" />
                    <p v-if="formToAccount.errors.amount" class="error-msg">{{ formToAccount.errors.amount }}</p>
                    <p class="text_small_12 color-gray2 pt5">
                        Available: {{ walletBalance.toFixed(8) }} {{ walletSymbol }}
                    </p>
                </div>

                <!-- Fee info -->
                <div class="fee-box pb20" v-if="selectedWallet && formToAccount.amount">
                    <div class="fee-row">
                        <span class="text_small_12 color-gray2">Fee</span>
                        <span class="text_small_12">
                            {{ transferFee.toFixed(8) }} {{ walletSymbol }}
                            <span class="color-gray2">({{ walletFeePercent }}% + {{ walletFeeFixed.toFixed(8) }})</span>
                        </span>
                    </div>
                    <div class="fee-row">
                        <span class="text_small_12 color-gray2">You will receive</span>
                        <span class="text_small_12">{{ transferNet.toFixed(8) }} {{ walletSymbol }}</span>
                    </div>
                </div>

                <button type="submit" class="btn btn_action btn_16 color-dark"
                    :disabled="!selectedWallet || !selectedBill || !formToAccount.amount || formToAccount.processing">
                    {{ formToAccount.processing ? 'Transferring...' : 'Transfer to account' }}
                </button>
            </form>

            <!-- ── Account → Portfolio ── -->
            <form v-else @submit.prevent="submitToPortfolio" class="transfer-form">

                <!-- From: trading account (custom dropdown) -->
                <div class="pb20">
                    <p class="text_16 color-gray2 pb10">From trading account</p>
                    <div class="custom-select" :class="{ open: dropdownBill, error: formToPortfolio.errors.bill_id }">
                        <div class="custom-select__trigger" @click="dropdownBill = !dropdownBill">
                            <template v-if="selectedBillSrc">
                                <img v-if="iconFor(selectedBillSrc)" :src="iconFor(selectedBillSrc)" width="24" height="24" alt="" />
                                <div class="select-item-text">
                                    <span class="symbol">{{ selectedBillSrc.currency?.symbol }}</span>
                                    <span class="balance">{{ billBalance.toFixed(8) }}</span>
                                </div>
                            </template>
                            <span v-else class="placeholder">Choose account</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <transition name="fade">
                            <div v-if="dropdownBill" class="custom-select__dropdown" @click.stop>
                                <div class="dropdown-list">
                                    <button v-for="b in realBills" :key="b.id" type="button"
                                        class="dropdown-item" :class="{ active: selectedBillSrc?.id === b.id }"
                                        @click="selectBillSrc(b)">
                                        <div class="di-left">
                                            <img v-if="iconFor(b)" :src="iconFor(b)" width="22" height="22" alt="" />
                                            <div class="di-info">
                                                <span class="di-symbol">{{ b.currency?.symbol }}</span>
                                                <span class="di-name">{{ b.name ?? b.currency?.name }}</span>
                                            </div>
                                        </div>
                                        <span class="di-balance">{{ parseFloat(b.balance ?? '0').toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!realBills.length" class="dropdown-empty">No trading accounts</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <p v-if="formToPortfolio.errors.bill_id" class="error-msg">{{ formToPortfolio.errors.bill_id }}</p>
                </div>

                <!-- To: portfolio wallet (manual dropdown) -->
                <div class="pb20" v-if="selectedBillSrc">
                    <p class="text_16 color-gray2 pb10">To portfolio</p>
                    <div class="custom-select" :class="{ open: dropdownWalletDst, error: formToPortfolio.errors.wallet_id }">
                        <div class="custom-select__trigger" @click="dropdownWalletDst = !dropdownWalletDst">
                            <template v-if="selectedWalletDst">
                                <img v-if="iconFor(selectedWalletDst)" :src="iconFor(selectedWalletDst)" width="24" height="24" alt="" />
                                <div class="select-item-text">
                                    <span class="symbol">{{ selectedWalletDst.currency?.symbol }}</span>
                                    <span class="balance">Balance: {{ parseFloat(selectedWalletDst.balance).toFixed(8) }} {{ selectedWalletDst.currency?.symbol }}</span>
                                </div>
                            </template>
                            <span v-else class="placeholder">Choose portfolio wallet</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <transition name="fade">
                            <div v-if="dropdownWalletDst" class="custom-select__dropdown" @click.stop>
                                <div class="dropdown-list">
                                    <button v-for="w in (props.portfolioWallets ?? [])" :key="w.id" type="button"
                                        class="dropdown-item" :class="{ active: selectedWalletDst?.id === w.id }"
                                        @click="selectWalletDst(w)">
                                        <div class="di-left">
                                            <img v-if="iconFor(w)" :src="iconFor(w)" width="22" height="22" alt="" />
                                            <div class="di-info">
                                                <span class="di-symbol">{{ w.currency?.symbol }}</span>
                                                <span class="di-name">{{ w.currency?.name }}</span>
                                            </div>
                                        </div>
                                        <span class="di-balance">{{ parseFloat(w.balance).toFixed(8) }}</span>
                                    </button>
                                    <p v-if="!(props.portfolioWallets ?? []).length" class="dropdown-empty">No portfolio wallets</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <p v-if="formToPortfolio.errors.wallet_id" class="error-msg">{{ formToPortfolio.errors.wallet_id }}</p>
                </div>

                <!-- Amount -->
                <div class="pb20" v-if="selectedBillSrc && selectedWalletDst">
                    <div class="amount-head">
                        <p class="text_16 color-gray2">Amount ({{ billSymbol }})</p>
                        <button type="button" class="max-button"
                            @click="formToPortfolio.amount = billBalance.toFixed(8)">Max</button>
                    </div>
                    <input type="number" step="0.00000001" v-model="formToPortfolio.amount"
                        class="input" :class="{ error: formToPortfolio.errors.amount }"
                        placeholder="0.00000000" min="0" />
                    <p v-if="formToPortfolio.errors.amount" class="error-msg">{{ formToPortfolio.errors.amount }}</p>
                    <p class="text_small_12 color-gray2 pt5">
                        Available: {{ billBalance.toFixed(8) }} {{ billSymbol }}
                    </p>
                </div>

                <!-- Conversion preview -->
                <div class="fee-box pb20" v-if="selectedBillSrc && selectedWalletDst && formToPortfolio.amount && selectedBillSrc.currency_id !== selectedWalletDst.currency_id">
                    <div class="fee-row">
                        <span class="text_small_12 color-gray2">You will receive</span>
                        <span class="text_small_12">{{ willReceive.toFixed(8) }} {{ dstSymbol }}</span>
                    </div>
                    <div class="fee-row">
                        <span class="text_small_12 color-gray2">Rate</span>
                        <span class="text_small_12">1 {{ dstSymbol }} = {{ parseFloat(selectedWalletDst?.currency?.exchange_rate ?? 1).toFixed(2) }} {{ billSymbol }}</span>
                    </div>
                </div>

                <button type="submit" class="btn btn_action btn_16 color-dark"
                    :disabled="!selectedBillSrc || !selectedWalletDst || !formToPortfolio.amount || formToPortfolio.processing">
                    {{ formToPortfolio.processing ? 'Transferring...' : 'Transfer to portfolio' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.transfer-form { display: flex; flex-direction: column; }

/* ── Custom select ──────────────────────────────────────── */
.custom-select { position: relative; }

.custom-select__trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    cursor: pointer;
    transition: border 0.2s, background 0.2s;
}
.custom-select__trigger:hover {
    border-color: rgba(121, 249, 149, 0.4);
    background: rgba(255, 255, 255, 0.06);
}
.custom-select.open .custom-select__trigger {
    border-color: rgba(121, 249, 149, 0.6);
}
.custom-select.error .custom-select__trigger {
    border-color: rgba(239, 68, 68, 0.6);
}

.placeholder { color: rgba(255,255,255,0.45); flex: 1; }

.select-item-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.select-item-text .symbol { font-weight: 600; font-size: 14px; color: white; }
.select-item-text .balance { font-size: 12px; color: rgba(255,255,255,0.55); }

.chevron {
    margin-left: auto;
    color: rgba(255,255,255,0.7);
    transition: transform 0.2s;
    flex-shrink: 0;
}
.custom-select.open .chevron { transform: rotate(180deg); }

.custom-select__dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0; right: 0;
    background: #0a1f2b;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 8px;
    z-index: 50;
}

.dropdown-list {
    max-height: 220px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
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
    cursor: pointer;
    transition: background 0.2s, border 0.2s;
}
.dropdown-item:hover { background: rgba(121,249,149,0.1); }
.dropdown-item.active {
    border-color: rgba(121,249,149,0.35);
    background: rgba(121,249,149,0.14);
}

.di-left { display: flex; align-items: center; gap: 10px; }
.di-info { display: flex; flex-direction: column; gap: 1px; text-align: left; }
.di-symbol { font-size: 14px; font-weight: 600; }
.di-name   { font-size: 12px; color: rgba(255,255,255,0.55); }
.di-balance { font-size: 13px; color: rgba(255,255,255,0.65); }

.dropdown-empty {
    text-align: center;
    padding: 14px 0;
    font-size: 13px;
    color: rgba(255,255,255,0.4);
}

/* ── Info box ───────────────────────────────────────────── */
.info-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

/* ── Amount row ─────────────────────────────────────────── */
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
    background: rgba(255,255,255,0.08);
    color: white;
    cursor: pointer;
    transition: background 0.2s;
}
.max-button:hover { background: rgba(121,249,149,0.2); }

.error-msg { margin-top: 4px; font-size: 12px; color: #ef4444; }

/* ── Fee box ────────────────────────────────────────────── */
.fee-box {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.fee-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* ── Fade transition ────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
