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






function handleSelectPair(pair) {
    selectedPair.value = pair;



}

// Инициализация табов




</script>

<template>

        <TvChart symbol="ETHUSDT" interval="5" theme="dark" />

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
