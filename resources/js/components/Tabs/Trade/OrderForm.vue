<script setup>
import { ref, computed, watch } from 'vue';
import { useUserStore } from '@/stores/userStore.js';
import { useTradeStore } from '@/stores/tradeStore.js';
import { useToast } from '@/composables/useToast.js';
import axiosClient from '@/api/axios';

const userStore = useUserStore();
const user = userStore.user;

const tradeStore = useTradeStore();
const toast = useToast();

const selectedBill = computed(() => tradeStore.selectedBill);
const selectedPair = computed(() => tradeStore.selectedPair);
const livePrice = computed(() => {
    const p = Number(tradeStore.price);
    return Number.isFinite(p) && p > 0 ? p : null;
});

const baseSymbol = computed(() => selectedPair.value?.currency_in?.symbol ?? '');
const quoteSymbol = computed(() => selectedPair.value?.currency_out?.symbol ?? '');

// табы: buy/sell, тип ордера: limit/market
const activeTab = ref('buy'); // 'buy' | 'sell'
const typeOrder = ref('market'); // 'limit' | 'market' | 'stop'
const tif = ref('GTC'); // GTC | IOC | FOK (для лимитных)
const postOnly = ref(false); // только для лимитных

// BUY state
const buyPrice = ref('');     // quote per base
const buyAmount = ref('');    // base
const buyTotal = ref('');     // quote
const buyStopPrice = ref(''); // trigger price for stop-market
// SELL state
const sellPrice = ref('');    // quote per base
const sellAmount = ref('');   // base
const sellTotal = ref('');    // quote
const sellStopPrice = ref('');

// что редактировали последним, чтобы не зациклить пересчет
const lastBuyEdited = ref(null);  // 'price' | 'amount' | 'total'
const lastSellEdited = ref(null); // 'price' | 'amount' | 'total'

// комиссия (оценочная), можно вынести в настройки
const FEE_RATE = 0.001; // 0.1%
const submitting = ref(false);

function toNum(v) {
    const n = Number((v ?? '').toString().replace(',', '.'));
    return Number.isFinite(n) ? n : null;
}

function getDecimals(sym) {
    if (!sym) return 8;
    const s = String(sym).toUpperCase();
    return ['USD', 'USDT', 'EUR', 'RUB'].includes(s) ? 2 : 8;
}

const quoteDecimals = computed(() => getDecimals(quoteSymbol.value));
const baseDecimals = computed(() => getDecimals(baseSymbol.value));

function entryPriceFor(side) {
    if (typeOrder.value === 'market') return livePrice.value;
    if (typeOrder.value === 'limit') return side === 'buy' ? toNum(buyPrice.value) : toNum(sellPrice.value);
    // stop-market: используем стоп-цену как ориентир
    return side === 'buy' ? toNum(buyStopPrice.value) : toNum(sellStopPrice.value);
}

// Stops per side
const buyStopsMode = ref('none'); // 'none' | 'pips' | 'price'
const buyTpInput = ref('');
const buySlInput = ref('');
const sellStopsMode = ref('none'); // 'none' | 'pips' | 'price'
const sellTpInput = ref('');
const sellSlInput = ref('');

function computeStops(side, mode, tpIn, slIn) {
    const entry = entryPriceFor(side);
    if (!entry || entry <= 0) return { tp: null, sl: null };
    if (mode === 'none') return { tp: null, sl: null };
    if (mode === 'price') {
        const tp = toNum(tpIn.value);
        const sl = toNum(slIn.value);
        return { tp: tp ?? null, sl: sl ?? null };
    }
    // by pips
    const pipsTp = toNum(tpIn.value);
    const pipsSl = toNum(slIn.value);
    const pipSize = Math.pow(10, -quoteDecimals.value);
    let tp = null;
    let sl = null;
    if (pipsTp !== null) {
        tp = side === 'buy' ? entry + pipsTp * pipSize : entry - pipsTp * pipSize;
    }
    if (pipsSl !== null) {
        sl = side === 'buy' ? entry - pipsSl * pipSize : entry + pipsSl * pipSize;
    }
    return { tp, sl };
}

const buyStops = computed(() => computeStops('buy', buyStopsMode.value, buyTpInput, buySlInput));
const sellStops = computed(() => computeStops('sell', sellStopsMode.value, sellTpInput, sellSlInput));

// Синхронизация BUY
watch([buyPrice, buyAmount, buyTotal, typeOrder, livePrice, buyStopPrice], () => {
    const price = typeOrder.value === 'market'
        ? livePrice.value
        : (typeOrder.value === 'limit' ? toNum(buyPrice.value) : toNum(buyStopPrice.value));
    if (!price || price <= 0) return; // нет цены — не считаем

    if (lastBuyEdited.value === 'amount') {
        const amount = toNum(buyAmount.value);
        if (amount !== null) buyTotal.value = (amount * price).toString();
    } else if (lastBuyEdited.value === 'total') {
        const total = toNum(buyTotal.value);
        if (total !== null) buyAmount.value = (total / price).toString();
    } else if (lastBuyEdited.value === 'price') {
        const amount = toNum(buyAmount.value);
        if (amount !== null) buyTotal.value = (amount * price).toString();
    }
});

// Синхронизация SELL
watch([sellPrice, sellAmount, sellTotal, typeOrder, livePrice, sellStopPrice], () => {
    const price = typeOrder.value === 'market'
        ? livePrice.value
        : (typeOrder.value === 'limit' ? toNum(sellPrice.value) : toNum(sellStopPrice.value));
    if (!price || price <= 0) return;

    if (lastSellEdited.value === 'amount') {
        const amount = toNum(sellAmount.value);
        if (amount !== null) sellTotal.value = (amount * price).toString();
    } else if (lastSellEdited.value === 'total') {
        const total = toNum(sellTotal.value);
        if (total !== null) sellAmount.value = (total / price).toString();
    } else if (lastSellEdited.value === 'price') {
        const amount = toNum(sellAmount.value);
        if (amount !== null) sellTotal.value = (amount * price).toString();
    }
});

// Быстрые проценты от баланса
function applyPercent(side, percent) {
    const bal = Number(selectedBill.value?.balance ?? 0);
    if (!Number.isFinite(bal) || bal <= 0) return;

    const priceBuy = typeOrder.value === 'market' ? livePrice.value : toNum(buyPrice.value);
    const priceSell = typeOrder.value === 'market' ? livePrice.value : toNum(sellPrice.value);

    if (side === 'buy') {
        if ((selectedBill.value?.currency?.symbol ?? '') === quoteSymbol.value) {
            const total = bal * percent;
            buyTotal.value = total.toString();
            lastBuyEdited.value = 'total';
        } else if ((selectedBill.value?.currency?.symbol ?? '') === baseSymbol.value && priceBuy) {
            const amount = bal * percent;
            buyAmount.value = amount.toString();
            lastBuyEdited.value = 'amount';
        }
    } else {
        if ((selectedBill.value?.currency?.symbol ?? '') === baseSymbol.value) {
            const amount = bal * percent;
            sellAmount.value = amount.toString();
            lastSellEdited.value = 'amount';
        } else if ((selectedBill.value?.currency?.symbol ?? '') === quoteSymbol.value && priceSell) {
            const total = bal * percent;
            sellTotal.value = total.toString();
            lastSellEdited.value = 'total';
        }
    }
}

// Сводки по вводу и комиссиям
const buySummary = computed(() => {
    const price = entryPriceFor('buy');
    const amount = toNum(buyAmount.value);
    const totalEntered = toNum(buyTotal.value);
    const total = totalEntered ?? (price && amount ? amount * price : null);
    const fee = total ? total * FEE_RATE : null;
    const totalWithFee = total && fee ? total + fee : null;
    return { price, amount, total, fee, totalWithFee };
});

const sellSummary = computed(() => {
    const price = entryPriceFor('sell');
    const amount = toNum(sellAmount.value);
    const totalEntered = toNum(sellTotal.value);
    const total = totalEntered ?? (price && amount ? amount * price : null);
    const fee = total ? total * FEE_RATE : null;
    const totalAfterFee = total && fee ? total - fee : null;
    return { price, amount, total, fee, totalAfterFee };
});

// Валидация
const canSubmitBuy = computed(() => {
    const priceOk = typeOrder.value === 'market'
        ? !!livePrice.value
        : (typeOrder.value === 'limit' ? (toNum(buyPrice.value) ?? 0) > 0 : (toNum(buyStopPrice.value) ?? 0) > 0);
    const amountOk = (toNum(buyAmount.value) ?? 0) > 0 || (toNum(buyTotal.value) ?? 0) > 0;
    return priceOk && amountOk && !!selectedPair.value && !!selectedBill.value;
});

const canSubmitSell = computed(() => {
    const priceOk = typeOrder.value === 'market'
        ? !!livePrice.value
        : (typeOrder.value === 'limit' ? (toNum(sellPrice.value) ?? 0) > 0 : (toNum(sellStopPrice.value) ?? 0) > 0);
    const amountOk = (toNum(sellAmount.value) ?? 0) > 0 || (toNum(sellTotal.value) ?? 0) > 0;
    return priceOk && amountOk && !!selectedPair.value && !!selectedBill.value;
});

// Сабмит (заглушка - здесь дергать API)
function submitBuy(e) {
    e?.preventDefault?.();
    if (!canSubmitBuy.value) return;
    if (submitting.value) return;
    submitting.value = true;
    const payload = {
        side: 'buy',
        type: typeOrder.value,
        ...(typeOrder.value === 'limit' ? { tif: tif.value, post_only: postOnly.value } : {}),
        ...(typeOrder.value === 'market' ? { price: livePrice.value } : {}),
        ...(typeOrder.value === 'limit' ? { price: toNum(buyPrice.value) } : {}),
        ...(typeOrder.value === 'stop' ? { stop_price: toNum(buyStopPrice.value) } : {}),
        amount: toNum(buyAmount.value),
        ...(toNum(buyTotal.value) ? { total: toNum(buyTotal.value) } : {}),
        pair_id: selectedPair.value?.id,
        bill_id: selectedBill.value?.id,
        ...(buyStopsMode.value !== 'none' ? { stops_mode: buyStopsMode.value } : {}),
        ...(buyStops.value.tp ? { take_profit: buyStops.value.tp } : {}),
        ...(buyStops.value.sl ? { stop_loss: buyStops.value.sl } : {}),
    };
    tradeStore.placeOrder(payload).then(() => {
        toast.success('Order placed');
        // reset inputs
        if (typeOrder.value === 'limit') {
            buyPrice.value = '';
        } else if (typeOrder.value === 'stop') {
            buyStopPrice.value = '';
        }
        buyAmount.value = '';
        buyTotal.value = '';
    }).catch((err) => {
        toast.error(err?.response?.data?.message || 'Failed to place order');
    }).finally(() => {
        submitting.value = false;
    });
}

function submitSell(e) {
    e?.preventDefault?.();
    if (!canSubmitSell.value) return;
    if (submitting.value) return;
    submitting.value = true;
    const payload = {
        side: 'sell',
        type: typeOrder.value,
        ...(typeOrder.value === 'limit' ? { tif: tif.value, post_only: postOnly.value } : {}),
        ...(typeOrder.value === 'market' ? { price: livePrice.value } : {}),
        ...(typeOrder.value === 'limit' ? { price: toNum(sellPrice.value) } : {}),
        ...(typeOrder.value === 'stop' ? { stop_price: toNum(sellStopPrice.value) } : {}),
        amount: toNum(sellAmount.value),
        ...(toNum(sellTotal.value) ? { total: toNum(sellTotal.value) } : {}),
        pair_id: selectedPair.value?.id,
        bill_id: selectedBill.value?.id,
        ...(sellStopsMode.value !== 'none' ? { stops_mode: sellStopsMode.value } : {}),
        ...(sellStops.value.tp ? { take_profit: sellStops.value.tp } : {}),
        ...(sellStops.value.sl ? { stop_loss: sellStops.value.sl } : {}),
    };
    tradeStore.placeOrder(payload).then(() => {
        toast.success('Order placed');
        if (typeOrder.value === 'limit') {
            sellPrice.value = '';
        } else if (typeOrder.value === 'stop') {
            sellStopPrice.value = '';
        }
        sellAmount.value = '';
        sellTotal.value = '';
    }).catch((err) => {
        toast.error(err?.response?.data?.message || 'Failed to place order');
    }).finally(() => {
        submitting.value = false;
    });
}

// Переключатели
function switchTab(tab) {
    activeTab.value = tab;
    lastBuyEdited.value = null;
    lastSellEdited.value = null;
}
</script>
<template>
    <div class="order-form">
        <div class="tabs tabs-3">
            <div class="tabs__header tabs__header-3">
                <div @click="switchTab('buy')" :class="{ active: activeTab === 'buy' }" class="tabs__header-item tabs__header-item-3 text_16 buy">
                    Buy
                </div>
                <div @click="switchTab('sell')" :class="{ active: activeTab === 'sell' }" class="tabs__header-item tabs__header-item-3 text_16 sell">
                    Sell
                </div>
            </div>

            <div class="tabs__content tabs__content-3">
                <!-- BUY FORM -->
                <form v-if="activeTab === 'buy'" @submit="submitBuy" id="CreateOrderBuy">
                    <div class="tabs__content-item tabs__content-item-3" id="LimitOrderBuy">
                        <div class="flex-center">
                            <div class="way-select">
                                <!-- <button type="button" @click="typeOrder = 'limit'" :class="{ active: typeOrder === 'limit' }" class="btn way text_small_14">
                                    Limit
                                </button> -->
                                <button type="button" @click="typeOrder = 'market'" :class="{ active: typeOrder === 'market' }" class="btn way text_small_14">
                                    Market
                                </button>
                                <!-- <button type="button" @click="typeOrder = 'stop'" :class="{ active: typeOrder === 'stop' }" class="btn way text_small_14">
                                    Stop
                                </button> -->
                            </div>
                            <div class="balance">
                                <span class="text_small_12 color-gray2">Available balance</span>
                                <span class="balanceUsdt text_16 color-blue">{{ selectedBill?.balance }} {{ selectedBill?.currency?.symbol }}</span>
                            </div>
                        </div>

                        <!-- Цена/стоп-цена -->
                        <label class="order-label" v-if="typeOrder === 'limit'">
                            <span class="text_small_12 color-dark">Price</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="buyPrice"
                                @input="lastBuyEdited = 'price'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>
                        <label class="order-label" v-else-if="typeOrder === 'market'">
                            <span class="text_small_12 color-dark">Market price</span>
                            <input type="text" class="order-input text_17" :value="livePrice ?? '≈ ?'" disabled />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>
                        <label class="order-label" v-else>
                            <span class="text_small_12 color-dark">Stop price</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="buyStopPrice"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>

                        <label class="order-label">
                            <span class="text_small_12 color-dark">Amount</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="buyAmount"
                                @input="lastBuyEdited = 'amount'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ baseSymbol }}</span>
                        </label>

                        <div class="way-select" style="margin: 6px 0;">
                            <button type="button" class="btn way text_small_14" @click="applyPercent('buy', 0.25)">25%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('buy', 0.5)">50%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('buy', 0.75)">75%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('buy', 1)">100%</button>
                        </div>

                        <label class="order-label">
                            <span class="text_small_12 color-dark">Total</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="buyTotal"
                                @input="lastBuyEdited = 'total'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>

                        <label v-if="typeOrder === 'limit'" class="order-label">
                            <span class="text_small_12 color-dark">Time in force</span>
                            <select class="order-input text_17" v-model="tif">
                                <option value="GTC">GTC</option>
                                <option value="IOC">IOC</option>
                                <option value="FOK">FOK</option>
                            </select>
                        </label>

                        <div v-if="typeOrder === 'limit'" class="way-select" style="margin: 6px 0;">
                            <button type="button" class="btn way text_small_14" :class="{ active: postOnly }" @click="postOnly = !postOnly">Post Only</button>
                        </div>

                        <!-- Stops (TP/SL) -->
                        <div class="way-select" style="margin: 6px 0;">
                            <span class="text_small_12 color-dark" style="margin-right: 6px;">Stops:</span>
                            <button type="button" class="btn way text_small_14" @click="buyStopsMode = 'none'" :class="{ active: buyStopsMode === 'none' }">No</button>
                            <!-- <button type="button" class="btn way text_small_14" @click="buyStopsMode = 'pips'" :class="{ active: buyStopsMode === 'pips' }">By pips</button> -->
                            <button type="button" class="btn way text_small_14" @click="buyStopsMode = 'price'" :class="{ active: buyStopsMode === 'price' }">By price</button>
                        </div>
                        <div v-if="buyStopsMode === 'pips'" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Take Profit (pips)</span>
                                <input type="text" class="order-input text_17" v-model="buyTpInput" inputmode="decimal" autocomplete="off" />
                            </label>
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Stop Loss (pips)</span>
                                <input type="text" class="order-input text_17" v-model="buySlInput" inputmode="decimal" autocomplete="off" />
                            </label>
                        </div>
                        <div v-else-if="buyStopsMode === 'price'" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Take Profit (price)</span>
                                <input type="text" class="order-input text_17" v-model="buyTpInput" inputmode="decimal" autocomplete="off" />
                                <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                            </label>
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Stop Loss (price)</span>
                                <input type="text" class="order-input text_17" v-model="buySlInput" inputmode="decimal" autocomplete="off" />
                                <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                            </label>
                        </div>

                        <div v-if="buyStopsMode !== 'none'" class="order-summary text_small_12 color-dark" style="display: flex; gap: 10px; margin: 6px 0 10px;">
                            <div v-if="buyStops.tp !== null">TP: <b class="color-white">{{ buyStops.tp.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                            <div v-if="buyStops.sl !== null">SL: <b class="color-white">{{ buyStops.sl.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                        </div>

                        <!-- Summary -->
                        <div class="order-summary text_small_12 color-dark" style="display: flex; flex-direction: column; gap: 6px; margin: 6px 0 10px;">
                            <div v-if="buySummary.price">Price used: <b class="color-white">{{ buySummary.price }}</b> {{ quoteSymbol }}</div>
                            <div v-if="buySummary.total">Fee (est.): <b class="color-white">{{ (buySummary.fee ?? 0).toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                            <div v-if="buySummary.totalWithFee">Cost incl. fee: <b class="color-white">{{ buySummary.totalWithFee.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                        </div>

                        <button type="submit" class="btn btn-buy btn_16 btn-with-loader" :disabled="!canSubmitBuy || submitting">
                            <span v-if="!submitting">Buy {{ baseSymbol || '' }}</span>
                            <span v-else class="btn-loader-wrapper">
                                <span class="btn-loader"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>

                <!-- SELL FORM -->
                <form v-if="activeTab === 'sell'" @submit="submitSell" id="CreateOrderSell">
                    <div class="tabs__content-item tabs__content-item-3" id="LimitOrderSell">
                        <div class="flex-center">
                            <div class="way-select">
                                <!-- <button type="button" @click="typeOrder = 'limit'" :class="{ active: typeOrder === 'limit' }" class="btn way text_small_14">
                                    Limit
                                </button> -->
                                <button type="button" @click="typeOrder = 'market'" :class="{ active: typeOrder === 'market' }" class="btn way text_small_14">
                                    Market
                                </button>
                                <!-- <button type="button" @click="typeOrder = 'stop'" :class="{ active: typeOrder === 'stop' }" class="btn way text_small_14">
                                    Stop
                                </button> -->
                            </div>
                            <div class="balance">
                                <span class="text_small_12 color-gray2">Available balance</span>
                                <span class="balanceCoin text_16 color-blue">{{ selectedBill?.balance }} {{ selectedBill?.currency?.symbol }}</span>
                            </div>
                        </div>

                        <label class="order-label" v-if="typeOrder === 'limit'">
                            <span class="text_small_12 color-dark">Price</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="sellPrice"
                                @input="lastSellEdited = 'price'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>
                        <label class="order-label" v-else-if="typeOrder === 'market'">
                            <span class="text_small_12 color-dark">Market price</span>
                            <input type="text" class="order-input text_17" :value="livePrice ?? '≈ ?'" disabled />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>
                        <label class="order-label" v-else>
                            <span class="text_small_12 color-dark">Stop price</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="sellStopPrice"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>

                        <label class="order-label">
                            <span class="text_small_12 color-dark">Amount</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="sellAmount"
                                @input="lastSellEdited = 'amount'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ baseSymbol }}</span>
                        </label>

                        <div class="way-select" style="margin: 6px 0;">
                            <button type="button" class="btn way text_small_14" @click="applyPercent('sell', 0.25)">25%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('sell', 0.5)">50%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('sell', 0.75)">75%</button>
                            <button type="button" class="btn way text_small_14" @click="applyPercent('sell', 1)">100%</button>
                        </div>

                        <label class="order-label">
                            <span class="text_small_12 color-dark">Total</span>
                            <input
                                type="text"
                                class="order-input text_17"
                                v-model="sellTotal"
                                @input="lastSellEdited = 'total'"
                                placeholder="0"
                                inputmode="decimal"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                            <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                        </label>

                        <label v-if="typeOrder === 'limit'" class="order-label">
                            <span class="text_small_12 color-dark">Time in force</span>
                            <select class="order-input text_17" v-model="tif">
                                <option value="GTC">GTC</option>
                                <option value="IOC">IOC</option>
                                <option value="FOK">FOK</option>
                            </select>
                        </label>

                        <div v-if="typeOrder === 'limit'" class="way-select" style="margin: 6px 0;">
                            <button type="button" class="btn way text_small_14" :class="{ active: postOnly }" @click="postOnly = !postOnly">Post Only</button>
                        </div>

                        <!-- Stops (TP/SL) -->
                        <div class="way-select" style="margin: 6px 0;">
                            <span class="text_small_12 color-dark" style="margin-right: 6px;">Stops:</span>
                            <button type="button" class="btn way text_small_14" @click="sellStopsMode = 'none'" :class="{ active: sellStopsMode === 'none' }">No</button>
                            <button type="button" class="btn way text_small_14" @click="sellStopsMode = 'pips'" :class="{ active: sellStopsMode === 'pips' }">By pips</button>
                            <button type="button" class="btn way text_small_14" @click="sellStopsMode = 'price'" :class="{ active: sellStopsMode === 'price' }">By price</button>
                        </div>
                        <div v-if="sellStopsMode === 'pips'" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Take Profit (pips)</span>
                                <input type="text" class="order-input text_17" v-model="sellTpInput" inputmode="decimal" autocomplete="off" />
                            </label>
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Stop Loss (pips)</span>
                                <input type="text" class="order-input text_17" v-model="sellSlInput" inputmode="decimal" autocomplete="off" />
                            </label>
                        </div>
                        <div v-else-if="sellStopsMode === 'price'" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Take Profit (price)</span>
                                <input type="text" class="order-input text_17" v-model="sellTpInput" inputmode="decimal" autocomplete="off" />
                                <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                            </label>
                            <label class="order-label">
                                <span class="text_small_12 color-dark">Stop Loss (price)</span>
                                <input type="text" class="order-input text_17" v-model="sellSlInput" inputmode="decimal" autocomplete="off" />
                                <span class="сurrency text_17 color-gray2">{{ quoteSymbol }}</span>
                            </label>
                        </div>

                        <div v-if="sellStopsMode !== 'none'" class="order-summary text_small_12 color-dark" style="display: flex; gap: 10px; margin: 6px 0 10px;">
                            <div v-if="sellStops.tp !== null">TP: <b class="color-white">{{ sellStops.tp.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                            <div v-if="sellStops.sl !== null">SL: <b class="color-white">{{ sellStops.sl.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                        </div>

                        <!-- Summary -->
                        <div class="order-summary text_small_12 color-dark" style="display: flex; flex-direction: column; gap: 6px; margin: 6px 0 10px;">
                            <div v-if="sellSummary.price">Price used: <b class="color-white">{{ sellSummary.price }}</b> {{ quoteSymbol }}</div>
                            <div v-if="sellSummary.total">Fee (est.): <b class="color-white">{{ (sellSummary.fee ?? 0).toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                            <div v-if="sellSummary.totalAfterFee">You receive (est.): <b class="color-white">{{ sellSummary.totalAfterFee.toFixed(quoteDecimals) }}</b> {{ quoteSymbol }}</div>
                        </div>

                        <button type="submit" class="btn btn-sell btn_16 btn-with-loader" :disabled="!canSubmitSell || submitting">
                            <span v-if="!submitting">Sell {{ baseSymbol || '' }}</span>
                            <span v-else class="btn-loader-wrapper">
                                <span class="btn-loader"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Button loader */
.btn-with-loader {
    position: relative;
    transition: all 0.3s ease-in-out;
}

.btn-loader-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-loader {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Smooth transitions */
.btn, button, input, select {
    transition: all 0.2s ease-in-out;
}

.btn:hover, button:hover {
    transform: translateY(-1px);
    filter: brightness(1.1);
}

.btn:active, button:active {
    transform: translateY(0);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

input:focus, select:focus {
    outline: none;
}

/* Tab transitions */
.tabs__header-item {
    transition: all 0.2s ease-in-out;
}

.tabs__header-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.tabs__header-item.active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Way select button transitions */
.way-select .btn {
    transition: all 0.2s ease-in-out;
}

.way-select .btn.active {
    box-shadow: 0 0 0 2px rgba(121, 249, 149, 0.4);
}
</style>