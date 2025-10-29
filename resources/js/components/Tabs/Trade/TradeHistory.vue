<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTradeStore } from '@/stores/tradeStore.js';

const tradeStore = useTradeStore();
const activeHistoryTab = ref('openOrders');
const cancellingOrders = ref(new Set());

function switchHistoryTab(tab) {
    activeHistoryTab.value = tab;
}

async function handleCancel(orderId) {
    if (cancellingOrders.value.has(orderId)) return;
    cancellingOrders.value.add(orderId);
    try {
        await tradeStore.cancelOrder(orderId);
    } finally {
        cancellingOrders.value.delete(orderId);
    }
}

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
                <div @click="switchHistoryTab('openOrders')" :class="{ active: activeHistoryTab === 'openOrders' }" class="tabs__header-item tabs__header-item-1">
                    Open orders
                </div>
                <div @click="switchHistoryTab('tradeHistory')" :class="{ active: activeHistoryTab === 'tradeHistory' }" class="tabs__header-item tabs__header-item-1">
                    Trade history
                </div>
            </div>
            <div class="tabs__content tabs__content-1">
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