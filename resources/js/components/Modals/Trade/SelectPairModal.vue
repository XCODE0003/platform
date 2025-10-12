<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';
import { useTradeStore } from '@/stores/tradeStore.js';
import { onMounted } from 'vue';
const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('selectPair'),
    set: (v) => (v ? modal.open('selectPair') : modal.close('selectPair')),
});

const tradeStore = useTradeStore();
const tradingPairs = computed(() => tradeStore.tradingPairs);
const selectedGroup = ref(null);
const selectGroupPair = (pairGroup) => {
    selectedGroup.value = pairGroup;
};

onMounted(() => {
    if (tradingPairs.value.length > 0) {
        selectedGroup.value = tradingPairs.value[0];

    }
});


const handleSelectPair = (pair) => {
    tradeStore.setSelectedPair(pair);
    isOpen.value = false;
};
</script>

<template>
    <VueFinalModal
        :modelValue="isOpen"
        overlay-transition="vfm-fade"
        content-transition="vfm-fade"
        click-to-close
        esc-to-close
        background="non-interactive"
        lock-scroll
        class="flex items-center justify-center"
        content-class="max-w-xl mx-4 p-4 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg"
    >
        <div class="modal show">
            <button class="closemodal clear" @click="isOpen = false">
                <img src="/images/modal_close.svg" alt="" />
            </button>
            <h2 class="h1_25 pb15">Select pair</h2>
            <div class="flex flex-col gap-4 flex-1 ">
                <div class="flex items-center gap-1.5 flex-wrap ">
                    <div v-for="value in tradingPairs" :class="{ 'active': selectedGroup === value }" class="select-none tabs__header-item tabs__header-item-2 " @click="selectGroupPair(value)">
                      {{ value.name }}
                    </div>
                </div>
                <div :class="{ 'grid grid-cols-4 gap-3': selectedGroup !== null && selectedGroup.pairs.length > 0 }" class="flex items-center gap-2">
                    <div v-if="selectedGroup !== null && selectedGroup.pairs.length > 0" v-for="pair in selectedGroup?.pairs" class="bg-[#1D323E] p-2 rounded-lg cursor-pointer hover:bg-[#273D4A] transition-all duration-300 hover:shadow-md hover:!text-white" @click="handleSelectPair(pair)">
                        <div class="flex items-center gap-2 ">
                            <img :src="`/images/coin_icons/${pair.currency_in?.symbol.toLowerCase()}.svg`" class="w-6 h-6 rounded-full" alt="" />
                            <div class="text-white text-sm text-nowrap">
                             {{ pair.currency_in?.symbol }} <span class="text-white/50">/</span> {{ pair.currency_out?.symbol }}
                            </div>
                            <img :src="`/images/coin_icons/${pair.currency_out?.symbol.toLowerCase()}.svg`" class="w-6 h-6 rounded-full" alt="" />
                        </div>
                    </div>
                    <div v-else class="bg-[#1D323E] text-white p-2 rounded-lg cursor-pointer text-center transition-all duration-300  w-full">
                        No pairs found
                    </div>

                </div>
            </div>
            <form>


            </form>
        </div>

        </VueFinalModal>
</template>

<style scoped></style>
