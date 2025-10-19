<script setup>
import MainLayout from '@/layouts/MainLayout.vue';
import { onMounted, ref, computed, defineProps } from 'vue';
import { useModalStore } from '@/stores/modal.js';
import SelectPairModal from '@/components/Modals/Trade/SelectPairModal.vue';
import { useTradeStore } from '@/stores/tradeStore.js';
import { storeToRefs } from 'pinia'; // добавьте этот импорт
import { watch } from 'vue';
import TvChart from '@/components/TvChart.vue'
const props = defineProps({
    tradingPairs: Array,
});

const tradeStore = useTradeStore();
const { selectedPair, tradingPairs } = storeToRefs(tradeStore); // используйте storeToRefs

onMounted(() => {
    tradeStore.setTradingPairs(props.tradingPairs);

    tradeStore.setSelectedPair(tradingPairs.value[0].pairs[0]);
});
watch(selectedPair, () => {
    console.log(selectedPair.value.currency_in?.code + selectedPair.value.currency_out?.code)
});

const modal = useModalStore();
const pair = ref('_ETH');


const activeHistoryTab = ref('openOrders'); // 'openOrders' | 'tradeHistory'
const activeRightTab = ref('recentTrades'); // 'recentTrades' | 'orderBook'
const activeOrderTab = ref('buy'); // 'buy' | 'sell'
const activeOrderType = ref('market'); // 'market' | 'limit'




function switchHistoryTab(tab) {
    activeHistoryTab.value = tab;

    // Переключаем CSS классы для табов
    document.querySelectorAll('.tabs__header-item-1').forEach((el) => {
        el.classList.remove('active');
    });

    if (tab === 'openOrders') {
        document
            .querySelectorAll('.tabs__header-item-1')[0]
            .classList.add('active');
        document.querySelectorAll('.tabs__content-item-1')[0].style.display =
            'block';
        document.querySelectorAll('.tabs__content-item-1')[1].style.display =
            'none';
    } else {
        document
            .querySelectorAll('.tabs__header-item-1')[1]
            .classList.add('active');
        document.querySelectorAll('.tabs__content-item-1')[0].style.display =
            'none';
        document.querySelectorAll('.tabs__content-item-1')[1].style.display =
            'block';
    }
}

function switchRightTab(tab) {
    activeRightTab.value = tab;

    // Переключаем CSS классы
    document.querySelectorAll('.tabs__header-item-2').forEach((el) => {
        el.classList.remove('active');
    });

    if (tab === 'recentTrades') {
        document
            .querySelectorAll('.tabs__header-item-2')[0]
            .classList.add('active');
        document.querySelectorAll('.tabs__content-item-2')[0].style.display =
            'block';
        document.querySelectorAll('.tabs__content-item-2')[1].style.display =
            'none';
    } else {
        document
            .querySelectorAll('.tabs__header-item-2')[1]
            .classList.add('active');
        document.querySelectorAll('.tabs__content-item-2')[0].style.display =
            'none';
        document.querySelectorAll('.tabs__content-item-2')[1].style.display =
            'block';
    }
}

function switchOrderTab(tab) {
    activeOrderTab.value = tab;

    // Переключаем CSS классы
    document.querySelectorAll('.tabs__header-item-3').forEach((el) => {
        el.classList.remove('active');
    });

    if (tab === 'buy') {
        document
            .querySelectorAll('.tabs__header-item-3')[0]
            .classList.add('active');
        document.querySelector('#CreateOrderBuy').style.display = 'block';
        document.querySelector('#CreateOrderSell').style.display = 'none';
    } else {
        document
            .querySelectorAll('.tabs__header-item-3')[1]
            .classList.add('active');
        document.querySelector('#CreateOrderBuy').style.display = 'none';
        document.querySelector('#CreateOrderSell').style.display = 'block';
    }
}

function handleSelectPair(pair) {
    selectedPair.value = pair;



}

// Инициализация табов
function initializeTabs() {
    // Скрываем неактивные табы
    switchHistoryTab('openOrders');
    switchRightTab('recentTrades');
    switchOrderTab('buy');
}

const tvSymbol = computed(() => {
    const pair = selectedPair.value
    return pair ? (`PAIR:${pair.id}`) : 'PAIR:1'
});

onMounted(() => {
    initializeTabs();
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
                                    <button class="btn btn_start_2 !bg-[#1D323E] !p-2 !rounded-lg !min-w-[100px]" @click="modal.open('selectPair')">
                                        {{ selectedPair ? selectedPair?.currency_in?.symbol + '/' + selectedPair?.currency_out?.symbol : 'Select pair' }}
                                    </button>
                                    <div class="pair-price">
                                        <span
                                            class="title text_small_14 color-gray2"
                                            >Price</span
                                        >
                                        <span
                                            class="text_17 color-green transition_trade"
                                            id="valueInfo_price"
                                        >
                                            <span class="loader"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="right">
                                    <div class="pair-info">
                                        <div class="pair-change">
                                            <span
                                                class="title text_small_14 color-gray2"
                                                >24h change</span
                                            >
                                            <span
                                                class="text_17 color-green transition_trade"
                                                id="valueInfo_change"
                                            >
                                                <span class="loader"></span>
                                            </span>
                                        </div>
                                        <div class="pair-high">
                                            <span
                                                class="title text_small_14 color-gray2"
                                                >24h high</span
                                            >
                                            <span
                                                class="text_17 color-black transition_trade"
                                                id="valueInfo_high"
                                            >
                                                <span class="loader"></span>
                                            </span>
                                        </div>
                                        <div class="pair-low">
                                            <span
                                                class="title text_small_14 color-gray2"
                                                >24h low</span
                                            >
                                            <span
                                                class="text_17 color-black transition_trade"
                                                id="valueInfo_low"
                                                ><span class="loader"></span
                                            ></span>
                                        </div>
                                        <div class="pair-volume">
                                            <span
                                                class="title text_small_14 color-gray2"
                                                >24h volume (BTC)</span
                                            >
                                            <span
                                                class="text_17 color-black transition_trade"
                                                id="valueInfo_volume"
                                                ><span class="loader"></span
                                            ></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <TvChart :symbol="tvSymbol" :pair="selectedPair" interval="5" theme="dark" />
                            </div>
                            <div class="trade-history">
                                <div class="tabs tabs-1">
                                    <div class="tabs__header tabs__header-1">
                                        <div
                                            @click="
                                                switchHistoryTab('openOrders')
                                            "
                                            class="tabs__header-item tabs__header-item-1 active"
                                        >
                                            Open orders
                                        </div>
                                        <div
                                            @click="
                                                switchHistoryTab('tradeHistory')
                                            "
                                            class="tabs__header-item tabs__header-item-1"
                                        >
                                            Trade history
                                        </div>
                                    </div>
                                    <div class="tabs__content tabs__content-1">
                                        <div
                                            class="tabs__content-item tabs__content-item-1 hide-scroll openOrders"
                                        >
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
                                            <div
                                                class="overflow"
                                                id="openOrders"
                                            >
                                                <div v-for="value in 10" class="grid-line active">
                                                    <div>
                                                        05/21/23, 13:43:57
                                                    </div>
                                                    <div>BTC/USDT</div>
                                                    <div>Limit</div>
                                                    <div>Buy</div>
                                                    <div>26,481.13</div>
                                                    <div>0.685630</div>
                                                    <div>18,156.16</div>
                                                </div>

                                            </div>
                                        </div>
                                        <div
                                            class="tabs__content-item hide-scroll tabs__content-item-1"
                                        >
                                            <div class="grid-head">
                                                <div>Date, time</div>
                                                <div>Pair</div>
                                                <div>Type</div>
                                                <div>Side</div>
                                                <div>Price</div>
                                                <div>Quantity</div>
                                                <div>Total (USDT)</div>
                                                <div>Status</div>
                                            </div>
                                            <div
                                                class="overflow"
                                                id="closedOrders"
                                            >
                                                <div v-for="value in 10" class="grid-line active">
                                                    <div>
                                                        05/21/23, 13:43:57
                                                    </div>
                                                    <div>BTC/USDT</div>
                                                    <div>Limit</div>
                                                    <div>Buy</div>
                                                    <div>26,481.13</div>
                                                    <div>0.685630</div>
                                                    <div>18,156.16</div>
                                                    <div>Completed</div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="trade-right">
                            <div class="trade-table">
                                <div class="tabs tabs-2">
                                    <div class="tabs__header tabs__header-2">
                                        <div
                                            @click="
                                                switchRightTab('recentTrades')
                                            "
                                            class="tabs__header-item tabs__header-item-2 active"
                                        >
                                            Recent trades
                                        </div>
                                        <div
                                            @click="switchRightTab('orderBook')"
                                            class="tabs__header-item tabs__header-item-2"
                                        >
                                            Order book
                                        </div>
                                    </div>
                                    <div class="tabs__content tabs__content-2">
                                        <div
                                            class="tabs__content-item tabs__content-item-2"
                                        >
                                            <div class="grid-head">
                                                <div>Price (USDT)</div>
                                                <div>Quantity</div>
                                                <div>Time</div>
                                            </div>
                                            <div id="recentTrade"></div>
                                        </div>
                                        <div
                                            class="tabs__content-item tabs__content-item-2 orderBook"
                                        >
                                            <div class="grid-head">
                                                <div>Price (USDT)</div>
                                                <div>Quantity</div>
                                                <div>Total (USDT)</div>
                                            </div>
                                            <div id="OrderBookBuy">
                                                <div v-for="value in 5" class=" active grid-line green create">
                                                    <div
                                                        class="bg"
                                                        style="width: 80%"
                                                    ></div>
                                                    <div class="color-green2">
                                                        31,113.04
                                                    </div>
                                                    <div class="color-white">
                                                        0.229460
                                                    </div>
                                                    <div class="color-gray2">
                                                        15:23:57
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="grid-separator">
                                                <span
                                                    class="text_17 color-green flex-center"
                                                    id="valueInfo_price1"
                                                >
                                                </span>
                                            </div>
                                            <div id="OrderBookSell">

                                                <div v-for="value in 5" class=" active grid-line red">
                                                    <div
                                                        class="bg"
                                                        style="width: 80%"
                                                    ></div>
                                                    <div class="color-red">
                                                        31,113.04
                                                    </div>
                                                    <div class="color-white">
                                                        0.229460
                                                    </div>
                                                    <div class="color-gray2">
                                                        15:23:57
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-form">
                                <div class="tabs tabs-3">
                                    <div class="tabs__header tabs__header-3">
                                        <div
                                            @click="switchOrderTab('buy')"
                                            class="tabs__header-item tabs__header-item-3 active text_16 buy"
                                        >
                                            Buy
                                        </div>
                                        <div
                                            @click="switchOrderTab('sell')"
                                            class="tabs__header-item tabs__header-item-3 text_16 sell"
                                        >
                                            Sell
                                        </div>
                                    </div>
                                    <div class="tabs__content tabs__content-3">
                                        <form id="CreateOrderBuy">
                                            <input
                                                hidden=""
                                                name="type_order"
                                                value="market"
                                            />
                                            <div
                                                class="tabs__content-item tabs__content-item-3"
                                                id="LimitOrderBuy"
                                            >
                                                <div class="flex-center">
                                                    <div class="way-select">
                                                        <button
                                                            type="button"
                                                            @click="
                                                                activeOrderType =
                                                                    'limit'
                                                            "
                                                            :class="{
                                                                active:
                                                                    activeOrderType ===
                                                                    'limit',
                                                            }"
                                                            class="btn way text_small_14"
                                                        >
                                                            Limit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="
                                                                activeOrderType =
                                                                    'market'
                                                            "
                                                            :class="{
                                                                active:
                                                                    activeOrderType ===
                                                                    'market',
                                                            }"
                                                            class="btn way text_small_14"
                                                        >
                                                            Market
                                                        </button>
                                                    </div>
                                                    <div class="balance">
                                                        <span
                                                            class="text_small_12 color-gray2"
                                                            >Available
                                                            balance</span
                                                        >
                                                        <span
                                                            class="balanceUsdt text_16 color-blue"
                                                            >120 USDT</span
                                                        >
                                                    </div>
                                                </div>
                                                <label class="order-label">
                                                    <span
                                                        class="text_small_12 color-dark"
                                                        >Market price</span
                                                    >
                                                    <input
                                                        type="text"
                                                        disabled
                                                        id="quantityPriceBuy"
                                                        class="order-input text_17"
                                                        value="≈ ?"
                                                    />

                                                    <span
                                                        class="сurrency text_17 color-gray2"
                                                        >BTC</span
                                                    >
                                                </label>
                                                <label class="order-label">
                                                    <span
                                                        class="text_small_12 color-dark"
                                                        >Quantity</span
                                                    >
                                                    <input
                                                        type="text"
                                                        id="quantityBuy"
                                                        name="amount"
                                                        oninput="delayedCalculateBuy()"
                                                        class="order-input text_17"
                                                        placeholder="0"
                                                    />
                                                    <span
                                                        class="сurrency text_17 color-gray2"
                                                        >USDT</span
                                                    >
                                                </label>

                                                <!-- <button class="btn btn-buy btn_16 notauth">Buy</button> -->
                                                <button
                                                    type="submit"
                                                    class="btn btn-buy btn_16"
                                                >
                                                    Buy
                                                </button>
                                            </div>
                                        </form>
                                        <form id="CreateOrderSell">
                                            <input
                                                hidden=""
                                                name="type_order"
                                                value="market"
                                            />
                                            <div
                                                class="tabs__content-item tabs__content-item-3"
                                                id="LimitOrderSell"
                                            >
                                                <div class="flex-center">
                                                    <div class="way-select">
                                                        <button
                                                            type="button"
                                                            @click="
                                                                activeOrderType =
                                                                    'limit'
                                                            "
                                                            :class="{
                                                                active:
                                                                    activeOrderType ===
                                                                    'limit',
                                                            }"
                                                            class="btn way text_small_14"
                                                        >
                                                            Limit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="
                                                                activeOrderType =
                                                                    'market'
                                                            "
                                                            :class="{
                                                                active:
                                                                    activeOrderType ===
                                                                    'market',
                                                            }"
                                                            class="btn way text_small_14"
                                                        >
                                                            Market
                                                        </button>
                                                    </div>
                                                    <div class="balance">
                                                        <span
                                                            class="text_small_12 color-gray2"
                                                            >Available
                                                            balance</span
                                                        >
                                                        <span
                                                            class="balanceCoin text_16 color-blue"
                                                            >ASDASD</span
                                                        >
                                                    </div>
                                                </div>
                                                <label class="order-label">
                                                    <span
                                                        class="text_small_12 color-dark"
                                                        >Market price</span
                                                    >
                                                    <input
                                                        type="text"
                                                        disabled
                                                        id="quantityPriceSell"
                                                        class="order-input text_17"
                                                        value="≈ ?"
                                                    />
                                                    <span
                                                        class="сurrency text_17 color-gray2"
                                                        >USDT</span
                                                    >
                                                </label>
                                                <label class="order-label">
                                                    <span
                                                        class="text_small_12 color-dark"
                                                        >Quantity</span
                                                    >
                                                    <input
                                                        type="text"
                                                        id="quantitySell"
                                                        name="amount"
                                                        oninput="delayedCalculateSell()"
                                                        class="order-input text_17"
                                                        placeholder="0"
                                                        value=""
                                                    />
                                                    <span
                                                        class="сurrency text_17 color-gray2"
                                                        >ASDAS</span
                                                    >
                                                </label>

                                                <button
                                                    class="btn btn-sell btn_16"
                                                >
                                                    Sell
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <SelectPairModal  />
    </MainLayout>
</template>

<style scoped>
.tradingview-widget-container iframe {
    border-radius: 10px;
}

.grid-line {
    opacity: 0;
    transform: translateY(-20px);
    transition:
        opacity 0.5s ease,
        transform 0.5s ease;
}

.grid-line.active {
    opacity: 1;
    transform: translateY(0);
}

.grid-line.create {
    opacity: 1;
    transform: translateY(0);
}
</style>
