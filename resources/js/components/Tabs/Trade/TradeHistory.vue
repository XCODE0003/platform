<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTradeStore } from '@/stores/tradeStore.js';
import { useToast } from '@/composables/useToast.js';
import ClosePositionModal from '@/components/Modals/Trade/ClosePositionModal.vue';

const tradeStore = useTradeStore();
const toast = useToast();
const activeHistoryTab = ref('openOrders');
const cancellingOrders = ref(new Set());
const closingPositions = ref(new Set());

// TP/SL inline editing
const editingTpSl = ref(null); // position id being edited
const tpSlDraft = ref({ tp: '', sl: '' });
const savingTpSl = ref(new Set());

function openTpSlEdit(pos) {
    editingTpSl.value = pos.id;
    tpSlDraft.value = {
        tp: pos.take_profit ? Number(pos.take_profit).toFixed(5) : '',
        sl: pos.stop_loss   ? Number(pos.stop_loss).toFixed(5)   : '',
    };
}

function cancelTpSlEdit() {
    editingTpSl.value = null;
}

async function saveTpSl(posId) {
    if (savingTpSl.value.has(posId)) return;
    savingTpSl.value.add(posId);
    try {
        await tradeStore.updateTpSl(
            posId,
            tpSlDraft.value.tp || null,
            tpSlDraft.value.sl || null,
        );
        editingTpSl.value = null;
        toast.showSuccess('TP/SL updated');
    } catch (e) {
        toast.showError(e?.response?.data?.message || 'Failed to update TP/SL');
    } finally {
        savingTpSl.value.delete(posId);
    }
}

// Close position modal
const closingPosition = ref(null); // position object being closed

// Price used for the Est. PnL preview. Must be the price of the POSITION's
// pair, not whatever pair the user is currently viewing on the chart —
// otherwise closing a non-active pair shows a nonsense PnL computed off the
// currently-selected-pair price.
const closeModalPrice = computed(() => {
    const pos = closingPosition.value;
    if (!pos) return null;
    const perPair = tradeStore.prices?.[pos.pair_id];
    if (perPair != null) return perPair;
    // Fallback: only use the store's global price when it's for THIS pair.
    if (tradeStore.selectedPair?.id === pos.pair_id) return tradeStore.price;
    return null;
});

function openCloseModal(pos) {
    closingPosition.value = pos;
}

function onCloseModalDismiss() {
    closingPosition.value = null;
}

async function onCloseConfirm({ quantity }) {
    const pos = closingPosition.value;
    if (!pos) return;
    const price = tradeStore.price;
    if (!price) {
        toast.showError('No market price available');
        return;
    }
    closingPositions.value.add(pos.id);
    closingPosition.value = null;
    try {
        await tradeStore.closePosition(pos.id, price, quantity ?? null);
        toast.showSuccess(quantity ? 'Partial close executed' : 'Position closed');
    } catch (e) {
        toast.showError(e?.response?.data?.message || 'Failed to close position');
    } finally {
        closingPositions.value.delete(pos.id);
    }
}

function switchHistoryTab(tab) {
    activeHistoryTab.value = tab;
}

async function handleCancel(orderId) {
    if (cancellingOrders.value.has(orderId)) return;
    cancellingOrders.value.add(orderId);
    try {
        await tradeStore.cancelOrder(orderId);
        toast.showSuccess('Order cancelled');
    } catch (e) {
        toast.showError(e?.response?.data?.message || 'Failed to cancel order');
    } finally {
        cancellingOrders.value.delete(orderId);
    }
}


// Pending limit/stop orders waiting to be filled
const openOrders = computed(() => tradeStore.orders.filter(o => o.status === 'queued'));
// Open positions (market orders fill immediately and become positions)
const openPositions = computed(() => tradeStore.positions.filter(p => p.status === 'open'));
// Closed positions — have both entry_price and close_price for chart display
const closedPositions = computed(() => [...tradeStore.positions]
    .filter(p => p.status === 'closed')
    .sort((a, b) => b.id - a.id));
const closedOrders = computed(() => tradeStore.orders.filter(o => o.status === 'cancelled' || o.status === 'rejected'));

const selectedHistId = computed(() => tradeStore.selectedHistoricalTrade?.id ?? null);
const selectedOpenId = computed(() => tradeStore.selectedOpenPositionId ?? null);

function selectHistoricalTrade(pos) {
    tradeStore.setSelectedHistoricalTrade(pos);
}

function selectOpenPosition(pos) {
    if (tradeStore.selectedOpenPositionId === pos.id) {
        tradeStore.setSelectedOpenPosition(null);
    } else {
        tradeStore.setSelectedOpenPosition(pos.id);
    }
}

// pair_id -> "BTC/USDT" lookup from store
const pairLabelMap = computed(() => {
    const map = {};
    for (const group of tradeStore.tradingPairs) {
        for (const pair of group.pairs ?? []) {
            const base = pair.currency_in?.symbol ?? '?';
            const quote = pair.currency_out?.symbol ?? '?';
            map[pair.id] = `${base}/${quote}`;
        }
    }
    return map;
});

// pair_id -> quote currency symbol (e.g. "USDT", "USD")
const pairQuoteMap = computed(() => {
    const map = {};
    for (const group of tradeStore.tradingPairs) {
        for (const pair of group.pairs ?? []) {
            map[pair.id] = pair.currency_out?.symbol ?? '';
        }
    }
    return map;
});

function pairLabel(pairId) {
    return pairLabelMap.value[pairId] ?? `PAIR:${pairId}`;
}

const closeModalQuote = computed(() => {
    const pos = closingPosition.value;
    if (!pos) return '';
    return pairQuoteMap.value[pos.pair_id] ?? '';
});

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    const yy = String(dt.getFullYear()).slice(-2);
    const mo = String(dt.getMonth() + 1).padStart(2, '0');
    const da = String(dt.getDate()).padStart(2, '0');
    const hh = String(dt.getHours()).padStart(2, '0');
    const mm = String(dt.getMinutes()).padStart(2, '0');
    return `${da}.${mo}.${yy} ${hh}:${mm}`;
}

function unrealizedPnl(pos) {
    const currentPrice = tradeStore.prices[pos.pair_id];
    if (!currentPrice) return null;
    const diff = pos.side === 'buy'
        ? currentPrice - Number(pos.entry_price)
        : Number(pos.entry_price) - currentPrice;
    return diff * Number(pos.quantity);
}

onMounted(() => {
    tradeStore.fetchOrders();
});
</script>
<template>
    <div class="trade-history">
        <div class="tabs tabs-1">
            <div class="tabs__header tabs__header-1">
                <div @click="switchHistoryTab('openOrders')" :class="{ active: activeHistoryTab === 'openOrders' }" class="tabs__header-item tabs__header-item-1">
                    Open orders
                </div>
                <div @click="switchHistoryTab('tradeHistory')" :class="{ active: activeHistoryTab === 'tradeHistory' }" class="tabs__header-item tabs__header-item-1">
                    Trade history
                </div>
            </div>
            <div class="tabs__content tabs__content-1">
                <div v-if="activeHistoryTab === 'openOrders'" class="tabs__content-item tabs__content-item-1 hide-scroll openOrders">
                    <!-- Open Positions (market orders) -->
                    <template v-if="openPositions.length > 0">
                        <div class="grid-head grid-open-pos">
                            <div>Date</div>
                            <div>Pair</div>
                            <div>Side</div>
                            <div>Entry</div>
                            <div>Qty</div>
                            <div>Total</div>
                            <div>TP / SL</div>
                            <div>Swap</div>
                            <div>Profit</div>
                            <div></div>
                        </div>
                        <div class="overflow">
                            <div
                                v-for="pos in openPositions"
                                :key="'pos-' + pos.id"
                                class="grid-line active grid-open-pos history-row"
                                :class="{ 'history-row--selected': selectedOpenId === pos.id }"
                                @click.self="selectOpenPosition(pos)"
                                title="Click to show on chart"
                            >
                                <div>{{ fmtDate(pos.created_at) }}</div>
                                <div>{{ pairLabel(pos.pair_id) }}</div>
                                <div :class="pos.side === 'buy' ? 'text-green-300' : 'text-red'">{{ pos.side }}</div>
                                <div>{{ Number(pos.entry_price).toFixed(5) }}</div>
                                <div>{{ pos.quantity }}</div>
                                <div>{{ pos.entry_total ? Number(pos.entry_total).toFixed(2) : '—' }}</div>
                                <div>
                                    <!-- Inline TP/SL editor -->
                                    <template v-if="editingTpSl === pos.id">
                                        <div class="tpsl-edit">
                                            <input
                                                v-model="tpSlDraft.tp"
                                                type="number"
                                                step="any"
                                                placeholder="TP"
                                                class="tpsl-input"
                                            />
                                            <input
                                                v-model="tpSlDraft.sl"
                                                type="number"
                                                step="any"
                                                placeholder="SL"
                                                class="tpsl-input"
                                            />
                                            <button
                                                class="tpsl-btn tpsl-btn--save"
                                                :disabled="savingTpSl.has(pos.id)"
                                                @click="saveTpSl(pos.id)"
                                                title="Save"
                                            >
                                                <span v-if="savingTpSl.has(pos.id)" class="btn-loader-sm"></span>
                                                <svg v-else width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M1.5 5L4 7.5L8.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <button class="tpsl-btn tpsl-btn--cancel" @click="cancelTpSlEdit" title="Cancel">
                                                <svg width="8" height="8" viewBox="0 0 10 10" fill="none"><path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span
                                            class="tpsl-display"
                                            @click="openTpSlEdit(pos)"
                                            title="Click to edit TP/SL"
                                        >
                                            <span v-if="pos.take_profit" class="text-green-300">TP&nbsp;{{ Number(pos.take_profit).toFixed(5) }}</span>
                                            <span v-if="pos.take_profit && pos.stop_loss" class="text-gray-500">&nbsp;/&nbsp;</span>
                                            <span v-if="pos.stop_loss" class="text-red">SL&nbsp;{{ Number(pos.stop_loss).toFixed(5) }}</span>
                                            <span v-if="!pos.take_profit && !pos.stop_loss" class="tpsl-empty">+ Add</span>
                                        </span>
                                    </template>
                                </div>
                                <div :class="Number(pos.swap) > 0 ? 'text-red' : ''">
                                    {{ Number(pos.swap) > 0 ? '-' + Number(pos.swap).toFixed(4) : '—' }}
                                </div>
                                <div :class="unrealizedPnl(pos) === null ? '' : unrealizedPnl(pos) >= 0 ? 'text-green-300' : 'text-red'">
                                    <template v-if="unrealizedPnl(pos) !== null">
                                        {{ unrealizedPnl(pos) >= 0 ? '+' : '' }}{{ unrealizedPnl(pos).toFixed(4) }}
                                    </template>
                                    <template v-else>—</template>
                                </div>
                                <div>
                                    <button class="icon-btn" @click="openCloseModal(pos)" :disabled="closingPositions.has(pos.id)" title="Close position">
                                        <span v-if="closingPositions.has(pos.id)" class="btn-loader-sm"></span>
                                        <svg v-else width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Pending Limit / Stop Orders -->
                    <template v-if="openOrders.length > 0">
                        <div class="grid-head grid-pending" :class="{ 'mt-2': openPositions.length > 0 }">
                            <div>Date</div>
                            <div>Pair</div>
                            <div>Type</div>
                            <div>Side</div>
                            <div>Price</div>
                            <div>Qty</div>
                            <div>Total</div>
                            <div>Cancel</div>
                        </div>
                        <div class="overflow" id="openOrders">
                            <div v-for="ord in openOrders" :key="ord.id" class="grid-line active grid-pending">
                                <div>{{ fmtDate(ord.created_at) }}</div>
                                <div>{{ pairLabel(ord.pair_id) }}</div>
                                <div>{{ ord.type }}</div>
                                <div>{{ ord.side }}</div>
                                <div>{{ ord.price ? Number(ord.price).toFixed(4) : (ord.stop_price ? Number(ord.stop_price).toFixed(4) : '—') }}</div>
                                <div>{{ ord.amount }}</div>
                                <div>{{ ord.total ? Number(ord.total).toFixed(4) : '—' }}</div>
                                <div>
                                    <button class="small_btn btn-with-loader" @click="handleCancel(ord.id)" :disabled="cancellingOrders.has(ord.id)">
                                        <span v-if="!cancellingOrders.has(ord.id)">Cancel</span>
                                        <span v-else class="btn-loader-sm"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div v-if="openPositions.length === 0 && openOrders.length === 0" class="empty-state">
                        No open positions or orders
                    </div>
                </div>
                <div v-if="activeHistoryTab === 'tradeHistory'" class="tabs__content-item tabs__content-item-1 hide-scroll tradeHistory">
                    <!-- Closed positions: clickable rows show entry/exit on chart -->
                    <template v-if="closedPositions.length > 0">
                        <div class="grid-head grid-closed-pos">
                            <div>Opened</div>
                            <div>Closed</div>
                            <div>Pair</div>
                            <div>Side</div>
                            <div>Entry</div>
                            <div>Exit</div>
                            <div>Qty</div>
                            <div>Total</div>
                            <div>Swap</div>
                            <div>PnL</div>
                            <div>Comment</div>
                        </div>
                        <div class="overflow" id="closedPositions">
                            <div
                                v-for="pos in closedPositions"
                                :key="'cp-' + pos.id"
                                class="grid-line active history-row grid-closed-pos"
                                :class="{ 'history-row--selected': selectedHistId === pos.id }"
                                @click="selectHistoricalTrade(pos)"
                                title="Click to show on chart"
                            >
                                <div>{{ fmtDate(pos.created_at) }}</div>
                                <div>{{ fmtDate(pos.closed_at) }}</div>
                                <div>{{ pairLabel(pos.pair_id) }}</div>
                                <div :class="pos.side === 'buy' ? 'text-green-300' : 'text-red'">{{ pos.side }}</div>
                                <div>{{ Number(pos.entry_price).toFixed(5) }}</div>
                                <div>{{ pos.close_price ? Number(pos.close_price).toFixed(5) : '—' }}</div>
                                <div>{{ Number(pos.quantity).toFixed(4) }}</div>
                                <div>{{ pos.close_total ? Number(pos.close_total).toFixed(4) : (pos.entry_total ? Number(pos.entry_total).toFixed(4) : '—') }}</div>
                                <div :class="Number(pos.swap) > 0 ? 'text-red' : ''">
                                    {{ Number(pos.swap) > 0 ? '-' + Number(pos.swap).toFixed(4) : '—' }}
                                </div>
                                <div :class="{ 'text-green-300': Number(pos.realized_pnl) > 0, 'text-red': Number(pos.realized_pnl) < 0 }">
                                    {{ pos.realized_pnl ? (Number(pos.realized_pnl) > 0 ? '+' : '') + Number(pos.realized_pnl).toFixed(4) : '—' }}
                                </div>
                                <div>
                                    <span v-if="pos.close_reason === 'take_profit'" class="reason-badge reason-badge--tp">Take Profit</span>
                                    <span v-else-if="pos.close_reason === 'stop_loss'" class="reason-badge reason-badge--sl">Stop Loss</span>
                                    <span v-else class="reason-badge reason-badge--manual">Manual</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Cancelled / rejected orders -->
                    <template v-if="closedOrders.length > 0">
                        <div class="grid-head grid-pending" :class="{ 'mt-2': closedPositions.length > 0 }">
                            <div>Date</div>
                            <div>Pair</div>
                            <div>Type</div>
                            <div>Side</div>
                            <div>Price</div>
                            <div>Qty</div>
                            <div>Total</div>
                            <div>Status</div>
                        </div>
                        <div class="overflow">
                            <div v-for="ord in closedOrders" :key="'co-' + ord.id" class="grid-line active grid-pending">
                                <div>{{ fmtDate(ord.updated_at || ord.created_at) }}</div>
                                <div>{{ pairLabel(ord.pair_id) }}</div>
                                <div>{{ ord.type }}</div>
                                <div>{{ ord.side }}</div>
                                <div>{{ ord.price ? Number(ord.price).toFixed(4) : (ord.stop_price ? Number(ord.stop_price).toFixed(4) : '—') }}</div>
                                <div>{{ ord.amount }}</div>
                                <div>{{ ord.total ? Number(ord.total).toFixed(4) : '—' }}</div>
                                <div>{{ ord.status }}</div>
                            </div>
                        </div>
                    </template>

                    <div v-if="closedPositions.length === 0 && closedOrders.length === 0" class="empty-state">
                        No trade history
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ClosePositionModal
        :position="closingPosition"
        :currentPrice="closeModalPrice"
        :quoteCurrency="closeModalQuote"
        @close="onCloseModalDismiss"
        @confirm="onCloseConfirm"
    />
</template>

<style scoped>
/* Smooth transitions */
.grid-line {
    transition: all 0.2s ease-in-out;
}

.grid-line:hover {
    background: rgba(255, 255, 255, 0.03);
}

.history-row {
    cursor: pointer;
}

.history-row:hover {
    background: rgba(41, 98, 255, 0.08) !important;
}

.history-row--selected {
    background: rgba(41, 98, 255, 0.15) !important;
    border-left: 2px solid #2962ff;
}

.tabs__header-item {
    transition: all 0.2s ease-in-out;
    cursor: pointer;
}

.tabs__header-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.tabs__header-item.active {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.small_btn {
    transition: all 0.2s ease-in-out;
}

.small_btn:hover:not(:disabled) {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.small_btn:active:not(:disabled) {
    transform: scale(0.98);
}

.small_btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    padding: 0;
    border: none;
    border-radius: 3px;
    background: transparent;
    color: #5d606b;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease;
}

.icon-btn:hover:not(:disabled) {
    color: #f23645;
    background: rgba(242, 54, 69, 0.12);
}

.icon-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Button loader for small buttons */
.btn-loader-sm {
    display: inline-block;
    width: 12px;
    height: 12px;
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

.empty-state {
    padding: 16px;
    text-align: center;
    color: #787b86;
    font-size: 13px;
}

/* TP/SL inline editing */
.tpsl-display {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    border-radius: 4px;
    padding: 2px 4px;
    font-size: 12px;
    transition: background 0.15s;
}
.tpsl-display:hover {
    background: rgba(255,255,255,0.06);
}
.tpsl-empty {
    color: #4b5563;
    font-size: 11px;
}
.tpsl-display:hover .tpsl-empty {
    color: #2962ff;
}

.tpsl-edit {
    display: flex;
    align-items: center;
    gap: 4px;
}
.tpsl-input {
    width: 80px;
    background: #1e222d;
    border: 1px solid #2a2e39;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
    color: #d1d4dc;
    outline: none;
    /* Убираем стрелки у type="number" для Chrome, Safari, Edge */
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: textfield;
}
.tpsl-input:focus {
    border-color: #2962ff;
}
/* Удаляем стрелки у type="number" для Chrome/Opera/Safari */
.tpsl-input::-webkit-outer-spin-button,
.tpsl-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
/* Удаляем стрелки у type="number" для Firefox */
.tpsl-input[type="number"] {
    -moz-appearance: textfield;
}
.tpsl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 3px;
    border: none;
    cursor: pointer;
    transition: background 0.15s;
}
.tpsl-btn--save {
    background: rgba(41, 98, 255, 0.2) !important;
    color: #2962ff !important;
}
.tpsl-btn--save:hover:not(:disabled) {
    background: rgba(41, 98, 255, 0.4) !important;
}
.tpsl-btn--cancel {
    background: rgba(242, 54, 69, 0.15) !important;
    color: #f23645 !important;
}
.tpsl-btn--cancel:hover {
    background: rgba(242, 54, 69, 0.3) !important;
}

.mt-2 {
    margin-top: 8px;
}

.reason-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.reason-badge--tp {
    background: rgba(121, 249, 149, 0.12) !important;
    color: #79F995 !important;
}
.reason-badge--sl {
    background: rgba(244, 75, 75, 0.12) !important;
    color: #F44B4B !important;
}
.reason-badge--manual {
    background: rgba(255, 255, 255, 0.06) !important;
    color: #787b86 !important;
}

/* Profit/Loss colors with smooth transitions */
.text-green-300 {
    color: #79F995;
    transition: color 0.3s ease-in-out;
}

.text-red {
    color: #F44B4B;
    transition: color 0.3s ease-in-out;
}
</style>