<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTradeStore } from '@/stores/tradeStore.js';
import { useToast } from '@/composables/useToast.js';

const tradeStore = useTradeStore();
const toast = useToast();
const activeHistoryTab = ref('openOrders');
const cancellingOrders = ref(new Set());
const closingPositions = ref(new Set());

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

async function handleClosePosition(positionId) {
    if (closingPositions.value.has(positionId)) return;
    const price = tradeStore.price;
    if (!price) {
        toast.showError('No market price available');
        return;
    }
    closingPositions.value.add(positionId);
    try {
        await tradeStore.closePosition(positionId, price);
        toast.showSuccess('Position closed');
    } catch (e) {
        toast.showError(e?.response?.data?.message || 'Failed to close position');
    } finally {
        closingPositions.value.delete(positionId);
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

const selectedId = computed(() => tradeStore.selectedHistoricalTrade?.id ?? null);

function selectHistoricalTrade(pos) {
    tradeStore.setSelectedHistoricalTrade(pos);
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
                        <div class="grid-head">
                            <div>Date, time</div>
                            <div>Pair</div>
                            <div>Side</div>
                            <div>Entry price</div>
                            <div>Quantity</div>
                            <div>Total (USDT)</div>
                            <div>TP / SL</div>
                            <div>Close</div>
                        </div>
                        <div class="overflow">
                            <div v-for="pos in openPositions" :key="'pos-' + pos.id" class="grid-line active">
                                <div>{{ new Date(pos.created_at).toLocaleString() }}</div>
                                <div>PAIR:{{ pos.pair_id }}</div>
                                <div :class="pos.side === 'buy' ? 'text-green-300' : 'text-red'">{{ pos.side }}</div>
                                <div>{{ Number(pos.entry_price).toFixed(4) }}</div>
                                <div>{{ pos.quantity }}</div>
                                <div>{{ pos.entry_total ? Number(pos.entry_total).toFixed(4) : '—' }}</div>
                                <div>
                                    <span v-if="pos.take_profit">TP {{ Number(pos.take_profit).toFixed(4) }}</span>
                                    <span v-if="pos.take_profit && pos.stop_loss"> / </span>
                                    <span v-if="pos.stop_loss">SL {{ Number(pos.stop_loss).toFixed(4) }}</span>
                                    <span v-if="!pos.take_profit && !pos.stop_loss">—</span>
                                </div>
                                <div>
                                    <button class="icon-btn" @click="handleClosePosition(pos.id)" :disabled="closingPositions.has(pos.id)" title="Close position">
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
                        <div class="grid-head" :class="{ 'mt-2': openPositions.length > 0 }">
                            <div>Date, time</div>
                            <div>Pair</div>
                            <div>Type</div>
                            <div>Side</div>
                            <div>Price</div>
                            <div>Quantity</div>
                            <div>Total (USDT)</div>
                            <div>Cancel</div>
                        </div>
                        <div class="overflow" id="openOrders">
                            <div v-for="ord in openOrders" :key="ord.id" class="grid-line active">
                                <div>{{ new Date(ord.created_at).toLocaleString() }}</div>
                                <div>PAIR:{{ ord.pair_id }}</div>
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
                        <div class="grid-head">
                            <div>Date, time</div>
                            <div>Pair</div>
                            <div>Side</div>
                            <div>Entry</div>
                            <div>Exit</div>
                            <div>PnL</div>
                            <div>Total</div>
                        </div>
                        <div class="overflow" id="closedPositions">
                            <div
                                v-for="pos in closedPositions"
                                :key="'cp-' + pos.id"
                                class="grid-line active history-row"
                                :class="{ 'history-row--selected': selectedId === pos.id }"
                                @click="selectHistoricalTrade(pos)"
                                title="Click to show on chart"
                            >
                                <div>{{ new Date(pos.updated_at || pos.created_at).toLocaleString() }}</div>
                                <div>PAIR:{{ pos.pair_id }}</div>
                                <div :class="pos.side === 'buy' ? 'text-green-300' : 'text-red'">{{ pos.side }}</div>
                                <div>{{ Number(pos.entry_price).toFixed(4) }}</div>
                                <div>{{ pos.close_price ? Number(pos.close_price).toFixed(4) : '—' }}</div>
                                <div :class="{ 'text-green-300': Number(pos.realized_pnl) > 0, 'text-red': Number(pos.realized_pnl) < 0 }">
                                    {{ pos.realized_pnl ? (Number(pos.realized_pnl) > 0 ? '+' : '') + Number(pos.realized_pnl).toFixed(4) : '—' }}
                                </div>
                                <div>{{ pos.entry_total ? Number(pos.entry_total).toFixed(4) : '—' }}</div>
                            </div>
                        </div>
                    </template>

                    <!-- Cancelled / rejected orders -->
                    <template v-if="closedOrders.length > 0">
                        <div class="grid-head" :class="{ 'mt-2': closedPositions.length > 0 }">
                            <div>Date, time</div>
                            <div>Pair</div>
                            <div>Type</div>
                            <div>Side</div>
                            <div>Price</div>
                            <div>Quantity</div>
                            <div>Total</div>
                            <div>Status</div>
                        </div>
                        <div class="overflow">
                            <div v-for="ord in closedOrders" :key="'co-' + ord.id" class="grid-line active">
                                <div>{{ new Date(ord.updated_at || ord.created_at).toLocaleString() }}</div>
                                <div>PAIR:{{ ord.pair_id }}</div>
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
</template>

<style scoped>
/* Smooth transitions */
.grid-line {
    transition: all 0.2s ease-in-out;
}

.grid-line:hover {
    background: rgba(255, 255, 255, 0.03);
    transform: translateX(2px);
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

.mt-2 {
    margin-top: 8px;
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