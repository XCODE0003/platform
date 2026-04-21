<script setup>
import MainLayout from '@/layouts/MainLayout.vue';
import { onMounted, onBeforeUnmount, ref, computed, defineProps, watch } from 'vue';
import { useModalStore } from '@/stores/modal.js';
import SelectPairModal from '@/components/Modals/Trade/SelectPairModal.vue';
import { useTradeStore } from '@/stores/tradeStore.js';
import { storeToRefs } from 'pinia';
import TradeRight from '@/components/Tabs/Trade/TradeRight.vue';
import TradeHistory from '@/components/Tabs/Trade/TradeHistory.vue';
import TvChart from '@/components/TvChart.vue';
import Select from '@/components/Tabs/Select/Select.vue';

const props = defineProps({
    tradingPairs: Array,
    bills: Array,
});

const tradeStore = useTradeStore();
const { selectedPair, tradingPairs, selectedBill } = storeToRefs(tradeStore);

// Price change animation
const prevPrice = ref(null);
const priceChangeClass = ref('');

watch(() => tradeStore.price, (newPrice, oldPrice) => {
    if (oldPrice !== null && newPrice !== null && oldPrice !== newPrice) {
        priceChangeClass.value = newPrice > oldPrice ? 'price-up' : 'price-down';
        setTimeout(() => {
            priceChangeClass.value = '';
        }, 800);
    }
    prevPrice.value = newPrice;
});

const formattedBills = computed(() => {
    return (props.bills ?? []).map((bill) => ({
        label: bill.name,
        value: bill.id,
    }));
});

// v-model для Select: работаем с id
const selectedBillId = computed({
    get: () => tradeStore.selectedBillId,
    set: (v) => tradeStore.setSelectedBill(v),
});

const STORAGE_KEY_RECENT = 'platform.trade.recentPairIds';
const STORAGE_KEY_SELECTED = 'platform.trade.selectedPairId';

const MAX_RECENT_PAIRS = 6;

function flattenTradingPairs(groups) {
    if (!groups?.length) return [];
    return groups.flatMap((g) => g.pairs ?? []);
}

function loadRecentPairIdsFromStorage() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY_RECENT);
        if (!raw) return [];
        const ids = JSON.parse(raw);
        return Array.isArray(ids) ? ids : [];
    } catch {
        return [];
    }
}

function resolvePairsFromIds(ids, flat) {
    const out = [];
    for (const id of ids) {
        const p = flat.find((x) => Number(x.id) === Number(id));
        if (p) out.push(p);
    }
    return out.slice(0, MAX_RECENT_PAIRS);
}

function persistTradeTabs() {
    try {
        localStorage.setItem(
            STORAGE_KEY_RECENT,
            JSON.stringify(recentPairs.value.map((x) => x.id)),
        );
        const sid = selectedPair.value?.id;
        if (sid != null) {
            localStorage.setItem(STORAGE_KEY_SELECTED, String(sid));
        }
    } catch {
        // quota / private mode
    }
}

// Инициализируем сразу из store — при SPA-навигации store уже содержит пары,
// поэтому watch(selectedPair, { immediate: true }) видит восстановленный список
// и не затирает его вызовом persistTradeTabs() с пустым массивом.
const recentPairs = ref(
    resolvePairsFromIds(
        loadRecentPairIdsFromStorage(),
        flattenTradingPairs(tradeStore.tradingPairs),
    ),
);

onMounted(() => {
    tradeStore.setTradingPairs(props.tradingPairs);
    const flat = flattenTradingPairs(tradingPairs.value);
    const defaultPair = flat[0] ?? null;

    // Восстанавливаем табы только если они ещё не были загружены из store
    // (при SPA-навигации recentPairs уже заполнен в момент setup)
    if (recentPairs.value.length === 0) {
        const storedIds = loadRecentPairIdsFromStorage();
        const restored = resolvePairsFromIds(storedIds, flat);
        if (restored.length) {
            recentPairs.value = restored;
        }
    }

    // Не меняем selectedPair если он уже установлен (SPA-навигация)
    if (!tradeStore.selectedPair) {
        let savedSelectedId = null;
        try {
            const s = localStorage.getItem(STORAGE_KEY_SELECTED);
            if (s !== null && s !== '') {
                const n = Number(s);
                savedSelectedId = Number.isNaN(n) ? null : n;
            }
        } catch {
            savedSelectedId = null;
        }

        let initialPair = null;
        if (savedSelectedId != null) {
            initialPair = flat.find((p) => Number(p.id) === savedSelectedId) ?? null;
        }
        if (!initialPair && recentPairs.value.length) {
            initialPair = recentPairs.value[0];
        }
        if (!initialPair) {
            initialPair = defaultPair;
        }
        if (initialPair) {
            tradeStore.setSelectedPair(initialPair);
        }
    }

    tradeStore.setBills(props.bills);
    if (!tradeStore.selectedBillId && props.bills?.length) {
        tradeStore.setSelectedBill(props.bills[0].id);
    }
    tradeStore.fetchOrders();
    ordersPoller.value = setInterval(() => tradeStore.fetchOrders(), 3000);
});

const modal = useModalStore();

watch(selectedPair, (p) => {
    tradeStore.setPrice(null);
    tradeStore.setHigh(null);
    tradeStore.setLow(null);
    tradeStore.setVolume(null);
    if (!p?.id) {
        return;
    }
    recentPairs.value = [
        p,
        ...recentPairs.value.filter((x) => x.id !== p.id),
    ].slice(0, MAX_RECENT_PAIRS);
    persistTradeTabs();
}, { immediate: true });

function selectRecentPair(pair) {
    tradeStore.setSelectedPair(pair);
}

function fallbackFirstPair() {
    const groups = tradingPairs.value;
    return groups?.[0]?.pairs?.[0] ?? null;
}

function removeRecentPair(pair) {
    const id = pair.id;
    const wasActive = selectedPair.value?.id === id;
    recentPairs.value = recentPairs.value.filter((x) => x.id !== id);
    if (!wasActive) {
        persistTradeTabs();
        return;
    }
    const next = recentPairs.value[0] ?? fallbackFirstPair();
    if (next) {
        tradeStore.setSelectedPair(next);
    }
    persistTradeTabs();
}

const tvSymbol = computed(() => {
    const pair = selectedPair.value;
    return pair ? `PAIR:${pair.id}` : 'PAIR:1';
});

// All open positions for the current pair (multiple trades allowed)
const pairPositions = computed(() => {
    const pair = selectedPair.value;
    if (!pair) return [];
    if (tradeStore.hiddenOpenPairId && Number(tradeStore.hiddenOpenPairId) === Number(pair.id)) return [];
    return tradeStore.positions.filter((p) => p.pair_id === pair.id && p.status === 'open');
});

// Index of the position currently shown on the chart
const selectedPositionIdx = ref(0);

// Sync index when store's selectedOpenPositionId changes (e.g. clicked from TradeHistory)
watch(() => tradeStore.selectedOpenPositionId, (id) => {
    if (id === null) return;
    const idx = pairPositions.value.findIndex((p) => p.id === id);
    if (idx !== -1) selectedPositionIdx.value = idx;
});

// Reset index when pair changes or positions shrink
watch(pairPositions, (positions) => {
    if (selectedPositionIdx.value >= positions.length) {
        selectedPositionIdx.value = Math.max(0, positions.length - 1);
    }
});

function prevPosition() {
    if (selectedPositionIdx.value > 0) {
        selectedPositionIdx.value--;
        tradeStore.setSelectedOpenPosition(pairPositions.value[selectedPositionIdx.value]?.id ?? null);
    }
}
function nextPosition() {
    if (selectedPositionIdx.value < pairPositions.value.length - 1) {
        selectedPositionIdx.value++;
        tradeStore.setSelectedOpenPosition(pairPositions.value[selectedPositionIdx.value]?.id ?? null);
    }
}

const openPosition = computed(() => {
    // While viewing a historical trade, suppress open-position lines entirely
    if (tradeStore.selectedHistoricalTrade) return null;
    const pos = pairPositions.value[selectedPositionIdx.value];
    if (!pos) return null;
    return {
        id: pos.id,
        price: pos.entry_price,
        side: pos.side,
        amount: pos.quantity,
        created_at: pos.created_at,
        take_profit: pos.take_profit,
        stop_loss: pos.stop_loss,
    };
});

const closedPosition = computed(() => {
    // Show temporary lastClosedPosition when it matches current pair
    const pair = selectedPair.value;
    const pos = tradeStore.lastClosedPosition;
    if (!pair || !pos || (pos.pair_id && pos.pair_id !== pair.id)) return null;
    return {
        entry_price: pos.entry_price,
        close_price: pos.close_price,
        side: pos.side,
        amount: pos.quantity,
        closed_at: pos.closed_at || pos.updated_at,
    };
});

const historicalTrade = computed(() => {
    const pair = selectedPair.value;
    const t = tradeStore.selectedHistoricalTrade;
    if (!pair || !t) return null;
    // Only show if the trade belongs to the currently selected pair
    if (Number(t.pair_id) !== Number(pair.id)) return null;
    return t;
});


const ordersPoller = ref(null);
onBeforeUnmount(() => {
    if (ordersPoller.value) {
        clearInterval(ordersPoller.value);
        ordersPoller.value = null;
    }
});
</script>

<template>
    <MainLayout>
        <main class="trade h100 container">
            <section class="trade">
                <div class="trade-container container">
                    <div class="trade-content">
                        <div class="trade-left">
                            <div class="pair-head">
                                <div class="left">
                                    <button class="btn btn_start_2 !bg-[#1D323E] !p-2 !rounded-lg !min-w-[100px]" @mousedown.prevent="modal.open('selectPair')">
                                        {{ selectedPair ? selectedPair?.currency_in?.symbol + '/' + selectedPair?.currency_out?.symbol : 'Select pair' }}
                                    </button>

                                    <div class="pair-price">
                                        <span class="title text_small_14 color-gray2">Price</span>
                                        <span class="text_17 transition_trade price-animated" :class="priceChangeClass" id="valueInfo_price">
                                            <span class="loader" v-if="!tradeStore.price"></span>
                                            <span v-else>{{ tradeStore.price }}</span>
                                        </span>
                                    </div>
                                    <div class="pair-price">
                                        <span class="title text_small_14 color-gray2">High</span>
                                        <div class="text_17 color-black transition_trade smooth-fade" id="valueInfo_high">
                                            <span class="loader" v-if="!tradeStore.high"></span>
                                            <span v-else>{{ tradeStore.high }}</span>
                                        </div>
                                    </div>
                                    <div class="pair-price">
                                        <span class="title text_small_14 color-gray2">Low</span>
                                        <div class="text_17 color-black transition_trade smooth-fade" id="valueInfo_low">
                                            <span class="loader" v-if="!tradeStore.low"></span>
                                            <span v-else>{{ tradeStore.low }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="right">
                                    <Select id="selectBill" :options="formattedBills" v-model="selectedBillId" />
                                </div>
                            </div>

                            <div class="chart-wrapper">
                                <div v-if="recentPairs.length" class="chart-pair-tabs tv-tabs-bar">
                                    <div
                                        v-for="p in recentPairs"
                                        :key="p.id"
                                        class="pair-tab"
                                        :class="{ 'pair-tab--active': selectedPair?.id === p.id }"
                                    >
                                        <button
                                            type="button"
                                            class="pair-tab__label"
                                            @click="selectRecentPair(p)"
                                        >
                                            {{ p.currency_in?.symbol }}/{{ p.currency_out?.symbol }}
                                        </button>
                                        <button
                                            type="button"
                                            class="pair-tab__close"
                                            aria-label="Закрыть"
                                            @click.stop="removeRecentPair(p)"
                                        >
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="chart-wrap">
                                    <TvChart :symbol="tvSymbol" :pair="selectedPair" :position="openPosition" :closedPosition="closedPosition" :historicalTrade="historicalTrade" :currentPrice="tradeStore.price" interval="5" theme="dark" />
                                    <!-- Position switcher overlay -->
                                    <div v-if="pairPositions.length > 0" class="pos-switcher">
                                        <button class="pos-nav" :disabled="selectedPositionIdx === 0" @click="prevPosition">‹</button>
                                        <div class="pos-info">
                                            <span :class="openPosition.side === 'buy' ? 'pos-side--buy' : 'pos-side--sell'">
                                                {{ openPosition.side.toUpperCase() }}
                                            </span>
                                            <span class="pos-price">@ {{ Number(openPosition.price).toFixed(4) }}</span>
                                            <span class="pos-count">{{ selectedPositionIdx + 1 }}/{{ pairPositions.length }}</span>
                                        </div>
                                        <button class="pos-nav" :disabled="selectedPositionIdx === pairPositions.length - 1" @click="nextPosition">›</button>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <TradeRight :bill="selectedBill" />
                    </div>
                    <TradeHistory />
                </div>
            </section>
        </main>
        <SelectPairModal />
    </MainLayout>
</template>

<style scoped>
/* Панель вкладок в духе TradingView (dark toolbar) */
.tv-tabs-bar {
    display: flex;
    flex-wrap: nowrap;
    gap: 0;
    margin: 0 0 1px;
    padding: 0px;
    align-items: flex-end;
    background: #131722;
    border-bottom: 1px solid #2a2e39;
    -webkit-overflow-scrolling: touch;
}

.tv-tabs-bar::-webkit-scrollbar {
    height: 4px;
}
.tv-tabs-bar::-webkit-scrollbar-thumb {
    background: #2a2e39;
    border-radius: 2px;
}

.pair-tab {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    max-width: 160px;
    margin-right: 1px;
    border-radius: 3px 3px 0 0;
    border: 1px solid transparent;
    border-bottom: none;
    background: transparent;
    color: #787b86;
    transition: background 0.15s ease, color 0.15s ease;
}

.pair-tab:hover {
    background: rgba(255, 255, 255, 0.04);
    color: #d1d4dc;
}

.pair-tab--active {
    background: #1e222d;
    border-color: #2a2e39;
    color: #d1d4dc;
    box-shadow: 0 1px 0 #1e222d;
    position: relative;
    z-index: 1;
}

.pair-tab--active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 2px;
    background: #2962ff;
    border-radius: 1px 1px 0 0;
}

.pair-tab__label {
    flex: 1;
    min-width: 0;
    padding: 7px 4px 7px 10px;
    border: none;
    background: transparent;
    color: inherit;
    font-family: 'Trebuchet MS', Roboto, sans-serif;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.01em;
    cursor: pointer;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pair-tab__close {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 100%;
    min-height: 28px;
    padding: 0;
    border: none;
    background: transparent;
    color: #5d606b;
    cursor: pointer;
    border-radius: 0 2px 0 0;
    transition: color 0.15s ease, background 0.15s ease;
}

.pair-tab__close:hover {
    color: #d1d4dc;
    background: rgba(255, 255, 255, 0.06);
}

.pair-tab--active .pair-tab__close {
    color: #787b86;
}

.pair-tab--active .pair-tab__close:hover {
    color: #f23645;
    background: rgba(242, 54, 69, 0.12);
}

.price-animated {
    transition: all 0.3s ease-in-out;
}

.price-up {
    color: #237936 !important;
    animation: priceFlash 0.8s ease-in-out;
}

.price-down {
    color: #F44B4B !important;
    animation: priceFlash 0.8s ease-in-out;
}

@keyframes priceFlash {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.9;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Smooth transitions for all interactive elements */
:deep(.btn), :deep(button), :deep(input), :deep(select) {
    transition: all 0.2s ease-in-out;
}

:deep(.btn:hover), :deep(button:hover) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

:deep(.btn:active), :deep(button:active) {
    transform: translateY(0);
}


.smooth-fade {
    animation: smoothFadeIn 0.4s ease-in-out;
}

@keyframes smoothFadeIn {
    from {
        opacity: 0.7;
    }
    to {
        opacity: 1;
    }
}

/* Loader animation */
:deep(.loader) {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid rgba(121, 249, 149, 0.3);
    border-top-color: #79F995;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.chart-wrap {
    position: relative;
    width: 100%;
    height: 100%;
}

.pos-switcher {
    position: absolute;
    bottom: 36px;
    left: 12px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(10, 14, 23, 0.82);
    backdrop-filter: blur(4px);
    border: 1px solid #2a2e39;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 11px;
    color: #d1d4dc;
    user-select: none;
}

.pos-nav {
    background: none !important;
    border: none !important;
    color: #787b86 !important;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    padding: 0 2px;
    transition: color 0.15s;
}
.pos-nav:not(:disabled):hover {
    color: #d1d4dc !important;
}
.pos-nav:disabled {
    opacity: 0.3;
    cursor: default;
}

.pos-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pos-side--buy  { color: #79F995; font-weight: 600; }
.pos-side--sell { color: #F44B4B; font-weight: 600; }
.pos-price      { color: #d1d4dc; }
.pos-count      { color: #4b5563; font-size: 10px; }
</style>