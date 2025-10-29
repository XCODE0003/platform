<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useTradeStore } from '@/stores/tradeStore.js';

const activeTab = ref('orderBook');
const isOrderBookOpen = ref(false);
const bids = ref([]);
const asks = ref([]);
const updateTimer = ref(null);
const lastBasePrice = ref(100);

const tradeStore = useTradeStore();

const basePrice = computed(() => {
    const price = Number(tradeStore.price);
    if (Number.isFinite(price) && price > 0) {
        lastBasePrice.value = price;
        return price;
    }
    return lastBasePrice.value;
});

function randomBetween(min, max, decimals = 4) {
    const value = Math.random() * (max - min) + min;
    return Number(value.toFixed(decimals));
}

function buildOrderBook() {
    const price = basePrice.value || 100;
    const depth = 5;
    const priceStep = Math.max(price * 0.0015, 0.05); // ~0.15% per level
    const quantityMax = price > 1000 ? 0.8 : 5;

    const newBids = [];
    const newAsks = [];

    for (let i = 0; i < depth; i += 1) {
        const offset = (i + Math.random()) * priceStep;
        const bidPrice = Math.max(price - offset, price * 0.2);
        const askPrice = price + offset;
        const bidQty = randomBetween(0.01, quantityMax, 4);
        const askQty = randomBetween(0.01, quantityMax, 4);

        newBids.push({
            id: `bid-${i}`,
            price: Number(bidPrice.toFixed(2)),
            quantity: bidQty,
            total: Number((bidPrice * bidQty).toFixed(2)),
        });

        newAsks.push({
            id: `ask-${i}`,
            price: Number(askPrice.toFixed(2)),
            quantity: askQty,
            total: Number((askPrice * askQty).toFixed(2)),
        });
    }

    bids.value = newBids.sort((a, b) => b.price - a.price);
    asks.value = newAsks.sort((a, b) => a.price - b.price);
}

const maxBidTotal = computed(() => {
    if (!bids.value.length) return 1;
    return Math.max(...bids.value.map((row) => row.total), 1);
});

const maxAskTotal = computed(() => {
    if (!asks.value.length) return 1;
    return Math.max(...asks.value.map((row) => row.total), 1);
});

function switchTab(tab) {
    activeTab.value = tab;
}

function toggleOrderBook() {
    isOrderBookOpen.value = !isOrderBookOpen.value;
}

function formatNumber(value, minimumFractionDigits = 2, maximumFractionDigits = 2) {
    return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits,
        maximumFractionDigits,
    });
}

onMounted(() => {
    buildOrderBook();
    updateTimer.value = setInterval(buildOrderBook, 1200);
});

onBeforeUnmount(() => {
    if (updateTimer.value) {
        clearInterval(updateTimer.value);
        updateTimer.value = null;
    }
});

watch(basePrice, () => {
    buildOrderBook();
});

</script>
<template>
    <div class="trade-table">
        <div class="tabs tabs-2">
            <div class="orderbook-toggle" @click="toggleOrderBook">
                <span class="text_small_14 color-white">Order Book</span>
                <svg
                    class="toggle-icon"
                    :class="{ 'rotated': isOrderBookOpen }"
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                >
                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div class="tabs__content tabs__content-2" :class="{ 'collapsed': !isOrderBookOpen }">
                <div v-if="activeTab === 'recentTrades'" class="tabs__content-item tabs__content-item-2">
                    <div class="grid-head">
                        <div>Price (USDT)</div>
                        <div>Quantity</div>
                        <div>Time</div>
                    </div>
                    <div id="recentTrade"></div>
                </div>
                <div v-if="activeTab === 'orderBook'" class="tabs__content-item tabs__content-item-2 orderBook">
                    <div class="grid-head">
                        <div>Price (USDT)</div>
                        <div>Quantity</div>
                        <div>Total (USDT)</div>
                    </div>
                    <div id="OrderBookBuy">
                        <div
                            v-for="row in bids"
                            :key="row.id"
                            class="active grid-line green create"
                        >
                            <div
                                class="bg"
                                :style="{ width: Math.max((row.total / maxBidTotal) * 100, 4) + '%' }"
                            ></div>
                            <div class="color-green2">
                                {{ formatNumber(row.price, 2, 2) }}
                            </div>
                            <div class="color-white">
                                {{ formatNumber(row.quantity, 4, 4) }}
                            </div>
                            <div class="color-gray2">
                                {{ formatNumber(row.total, 2, 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="grid-separator">
                        <!-- <span class="text_17 color-green flex-center" id="valueInfo_price1">
                            {{ formatNumber(basePrice, 2, 2) }}
                        </span> -->
                    </div>
                    <div id="OrderBookSell">
                        <div
                            v-for="row in asks"
                            :key="row.id"
                            class="active grid-line red"
                        >
                            <div
                                class="bg"
                                :style="{ width: Math.max((row.total / maxAskTotal) * 100, 4) + '%' }"
                            ></div>
                            <div class="color-red">
                                {{ formatNumber(row.price, 2, 2) }}
                            </div>
                            <div class="color-white">
                                {{ formatNumber(row.quantity, 4, 4) }}
                            </div>
                            <div class="color-gray2">
                                {{ formatNumber(row.total, 2, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.orderbook-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    margin-bottom: 8px;
}

.orderbook-toggle:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-1px);
}

.orderbook-toggle:active {
    transform: translateY(0);
}

.toggle-icon {
    transition: transform 0.3s ease-in-out;
    color: #79F995;
}

.toggle-icon.rotated {
    transform: rotate(180deg);
}

.tabs__content-2 {
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.4s ease-in-out, opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    opacity: 1;
    transform: translateY(0);
}

.tabs__content-2.collapsed {
    max-height: 0;
    opacity: 0;
    transform: translateY(-10px);
}

/* Smooth animations for grid lines */
.grid-line {
    transition: all 0.2s ease-in-out;
}

.grid-line:hover {
    background: rgba(255, 255, 255, 0.03);
    cursor: pointer;
}

.grid-line.green:hover .bg {
    opacity: 0.3;
}

.grid-line.red:hover .bg {
    opacity: 0.3;
}

/* Animated background bars */
.grid-line .bg {
    transition: width 0.3s ease-in-out, opacity 0.2s ease-in-out;
}

/* Price separator animation */
.grid-separator {
    transition: all 0.2s ease-in-out;
}


</style>