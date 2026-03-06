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

onMounted(() => {
    tradeStore.setTradingPairs(props.tradingPairs);
    tradeStore.setSelectedPair(tradingPairs.value[0].pairs[0]);
    tradeStore.setBills(props.bills);
    if (!tradeStore.selectedBillId && props.bills?.length) {
        tradeStore.setSelectedBill(props.bills[0].id);
    }
    tradeStore.fetchOrders();
    ordersPoller.value = setInterval(() => tradeStore.fetchOrders(), 3000);
});

watch(selectedPair, () => {
    tradeStore.setPrice(null);
    tradeStore.setHigh(null);
    tradeStore.setLow(null);
    tradeStore.setVolume(null);
});

const modal = useModalStore();
const pair = ref('_ETH');

function handleSelectPair(pair) {
    selectedPair.value = pair;
}

const tvSymbol = computed(() => {
    const pair = selectedPair.value;
    return pair ? `PAIR:${pair.id}` : 'PAIR:1';
});

const openPosition = computed(() => {
    const pair = selectedPair.value;
    if (!pair) return null;
    // If we've been asked to hide open position for this pair (e.g., after cancel), suppress it
    if (tradeStore.hiddenOpenPairId && Number(tradeStore.hiddenOpenPairId) === Number(pair.id)) {
        return null;
    }
    const pos = tradeStore.positions.find((p) => p.pair_id === pair.id && p.status === 'open');
    if (!pos) return null;
    return {
        price: pos.entry_price,
        side: pos.side,
        amount: pos.quantity,
        created_at: pos.created_at,
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
                                    <button class="btn btn_start_2 !bg-[#1D323E] !p-2 !rounded-lg !min-w-[100px]" @click="modal.open('selectPair')">
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
                                <TvChart :symbol="tvSymbol" :pair="selectedPair" :position="openPosition" :closedPosition="closedPosition" interval="5" theme="dark" />
                            </div>

                            <TradeHistory />
                        </div>

                        <TradeRight :bill="selectedBill" />
                    </div>
                </div>
            </section>
        </main>
        <SelectPairModal />
    </MainLayout>
</template>

<style scoped>
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
</style>