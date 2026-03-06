<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTradeStore } from '@/stores/tradeStore.js';
import { useToast } from '@/composables/useToast.js';

const tradeStore = useTradeStore();
const toast = useToast();
const activeHistoryTab = ref('positions');
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
        toast.showError('Current price unavailable');
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

function unrealizedPnl(pos) {
    const price = Number(tradeStore.price);
    if (!price) return null;
    const entry = Number(pos.entry_price);
    const qty = Number(pos.quantity);
    return pos.side === 'buy' ? (price - entry) * qty : (entry - price) * qty;
}

function pairName(pairId) {
    const pair = tradeStore.tradingPairs.find(p => p.id === pairId);
    if (!pair) return `#${pairId}`;
    return `${pair.currency_in?.symbol ?? ''}/${pair.currency_out?.symbol ?? ''}`;
}

const openPositions = computed(() => tradeStore.positions.filter(p => p.status === 'open'));
const openOrders = computed(() => tradeStore.orders.filter(o => o.status === 'open' || o.status === 'queued'));
const closedOrders = computed(() => tradeStore.orders.filter(o => o.status === 'filled' || o.status === 'cancelled' || o.status === 'rejected'));

onMounted(() => {
    tradeStore.fetchOrders();
});
</script>
<template>
    <div class="trade-history">
        <div class="tabs tabs-1">
            <div class="tabs__header tabs__header-1">
                <div @click="switchHistoryTab('positions')" :class="{ active: activeHistoryTab === 'positions' }" class="tabs__header-item tabs__header-item-1">
                    Positions <span v-if="openPositions.length" class="tab-badge">{{ openPositions.length }}</span>
                </div>
                <div @click="switchHistoryTab('openOrders')" :class="{ active: activeHistoryTab === 'openOrders' }" class="tabs__header-item tabs__header-item-1">
                    Open orders
                </div>
                <div @click="switchHistoryTab('tradeHistory')" :class="{ active: activeHistoryTab === 'tradeHistory' }" class="tabs__header-item tabs__header-item-1">
                    Trade history
                </div>
            </div>
            <div class="tabs__content tabs__content-1">
                <!-- Positions tab -->
                <div v-if="activeHistoryTab === 'positions'" class="tabs__content-item tabs__content-item-1 hide-scroll openOrders">
                    <div class="grid-head grid-positions">
                        <div>Pair</div>
                        <div>Side</div>
                        <div>Entry price</div>
                        <div>Qty</div>
                        <div>Unrealized PnL</div>
                        <div>TP / SL</div>
                        <div>Close</div>
                    </div>
                    <div class="overflow">
                        <div v-if="!openPositions.length" class="empty-state">No open positions</div>
                        <div v-for="pos in openPositions" :key="pos.id" class="grid-line active grid-positions">
                            <div>{{ pairName(pos.pair_id) }}</div>
                            <div :class="pos.side === 'buy' ? 'text-green-300' : 'text-red'">{{ pos.side.toUpperCase() }}</div>
                            <div>{{ Number(pos.entry_price).toFixed(4) }}</div>
                            <div>{{ Number(pos.quantity).toFixed(8) }}</div>
                            <div v-if="unrealizedPnl(pos) !== null" :class="{ 'text-green-300': unrealizedPnl(pos) >= 0, 'text-red': unrealizedPnl(pos) < 0 }">
                                {{ unrealizedPnl(pos) >= 0 ? '+' : '' }}{{ unrealizedPnl(pos).toFixed(2) }}$
                            </div>
                            <div v-else>—</div>
                            <div class="tp-sl-cell">
                                <span v-if="pos.take_profit" class="text-green-300">TP {{ Number(pos.take_profit).toFixed(2) }}</span>
                                <span v-if="pos.stop_loss" class="text-red">SL {{ Number(pos.stop_loss).toFixed(2) }}</span>
                                <span v-if="!pos.take_profit && !pos.stop_loss">—</span>
                            </div>
                            <div>
                                <button class="small_btn btn-with-loader close-btn" @click="handleClosePosition(pos.id)" :disabled="closingPositions.has(pos.id)">
                                    <span v-if="!closingPositions.has(pos.id)">Close</span>
                                    <span v-else class="btn-loader-sm"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeHistoryTab === 'openOrders'" class="tabs__content-item tabs__content-item-1 hide-scroll openOrders">
                    <div class="grid-head">
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
                            <div>{{ (Number(ord.price) ?? Number(ord.stop_price)).toFixed(4) ?? '—' }}</div>
                            <div>{{ ord.amount }}</div>
                            <div>{{ (Number(ord.total) ?? '—').toFixed(4) ?? '—' }}</div>
                            <div>
                                <button class="small_btn btn-with-loader" @click="handleCancel(ord.id)" :disabled="cancellingOrders.has(ord.id)">
                                    <span v-if="!cancellingOrders.has(ord.id)">Cancel</span>
                                    <span v-else class="btn-loader-sm"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="activeHistoryTab === 'tradeHistory'" class="tabs__content-item tabs__content-item-1 hide-scroll tradeHistory">
                    <div class="grid-head">
                        <div>Date, time</div>
                        <div>Pair</div>
                        <div>Type</div>
                        <div>Side</div>
                        <div>Price</div>
                        <div>Profit</div>
                        <div>Total (USDT)</div>
                        <div>Status</div>
                    </div>
                    <div class="overflow" id="closedOrders">
                        <div v-for="ord in closedOrders" :key="ord.id" class="grid-line active">
                            <div>{{ new Date(ord.updated_at || ord.created_at).toLocaleString() }}</div>
                            <div>PAIR:{{ ord.pair_id }}</div>
                            <div>{{ ord.type }}</div>
                            <div>{{ ord.side }}</div>
                            <div>{{ (Number(ord.price) ?? Number(ord.stop_price)).toFixed(4) ?? '—' }}</div>
                            <div :class="{ 'text-green-300': Number(ord.take_profit) > 0, 'text-red': Number(ord.take_profit) < 0 }">{{ (Number(ord.take_profit) ?? '—').toFixed(4) ?? '—' }}$</div>
                            <div>{{ (Number(ord.total) ?? '—').toFixed(4) ?? '—' }}</div>
                            <div>{{ ord.status }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.grid-positions {
    grid-template-columns: 1.2fr 0.7fr 1fr 1fr 1fr 1.2fr 0.8fr !important;
}

.tp-sl-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 11px;
}

.tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #79F995;
    color: #000;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    margin-left: 4px;
}

.close-btn {
    border-color: rgba(244, 75, 75, 0.5);
    color: #F44B4B;
}

.close-btn:hover:not(:disabled) {
    background: rgba(244, 75, 75, 0.15);
    border-color: rgba(244, 75, 75, 0.8);
}

.empty-state {
    text-align: center;
    padding: 24px 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.35);
}

/* Smooth transitions */
.grid-line {
    transition: all 0.2s ease-in-out;
}

.grid-line:hover {
    background: rgba(255, 255, 255, 0.03);
    transform: translateX(2px);
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