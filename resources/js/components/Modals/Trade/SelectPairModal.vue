<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { VueFinalModal } from 'vue-final-modal';
import { useTradeStore } from '@/stores/tradeStore.js';
const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('selectPair'),
    set: (v) => (v ? modal.open('selectPair') : modal.close('selectPair')),
});

const tradeStore = useTradeStore();
const tradingPairs = computed(() => tradeStore.tradingPairs);
const selectedGroup = ref(null);
const search = ref('');
const selectGroupPair = (pairGroup) => {
    selectedGroup.value = pairGroup;
};

watch(tradingPairs, (pairs) => {
    if (pairs.length > 0 && selectedGroup.value === null) {
        selectedGroup.value = pairs[0];
    }
}, { immediate: true });

// While a query is typed, search across ALL groups by ticker or name; the
// group tabs only scope the list when the search box is empty.
const displayedPairs = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return selectedGroup.value?.pairs ?? [];
    }
    const all = (tradingPairs.value ?? []).flatMap((g) => g.pairs ?? []);
    return all.filter((p) => {
        const sym  = String(p.currency_in?.symbol ?? '').toLowerCase();
        const name = String(p.currency_in?.name ?? '').toLowerCase();
        const out  = String(p.currency_out?.symbol ?? '').toLowerCase();
        return sym.includes(q) || name.includes(q) || out.includes(q);
    });
});


const handleSelectPair = (pair) => {
    tradeStore.setSelectedPair(pair);
    isOpen.value = false;
};

/** Cache key for "icon failed to load" (one symbol - one file path). */
function coinIconKey(symbol) {
    return String(symbol ?? '').toUpperCase();
}

const brokenCoinIcons = ref({});

function onCoinIconError(symbol) {
    const k = coinIconKey(symbol);
    if (!k) {
        return;
    }
    brokenCoinIcons.value = { ...brokenCoinIcons.value, [k]: true };
}

function coinInitial(symbol) {
    const s = String(symbol ?? '').trim();
    return s ? s.charAt(0).toUpperCase() : '?';
}

// Asset classes where the quote currency is part of the pair identity
// (BTC/USDT vs BTC/USDC, EURUSD vs EURJPY, XAUUSD vs XAUEUR).
// For stocks / commodities / indices the quote is implicitly USD on this
// platform and rendering 'AAPL/USD' is just noise.
const PAIR_LABEL_WITH_QUOTE = new Set(['crypto', 'forex', 'metal', 'fiat']);
function hasQuoteInLabel(pair) {
    const cls = pair?.asset_class;
    return cls ? PAIR_LABEL_WITH_QUOTE.has(cls) : false;
}
</script>

<template>
    <VueFinalModal
        v-model="isOpen"
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
                <label class="select-pair-search">
                    <input
                        type="text"
                        v-model="search"
                        class="clear"
                        placeholder="Search by ticker or name&hellip;"
                    />
                </label>
                <div v-show="!search.trim()" class="flex items-center gap-1.5 flex-wrap ">
                    <div v-for="value in tradingPairs" :class="{ 'active': selectedGroup === value }" class="select-none tabs__header-item tabs__header-item-2 " @click="selectGroupPair(value)">
                      {{ value.name }}
                    </div>
                </div>
                <div :class="{ 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3': displayedPairs.length > 0 }" class="items-center gap-2 max-h-[60vh] overflow-y-auto pr-1 -mr-1">
                    <div v-if="displayedPairs.length > 0" v-for="pair in displayedPairs" :key="pair.id" class="bg-[#1D323E] p-2 rounded-lg cursor-pointer hover:bg-[#273D4A] transition-all duration-300 hover:shadow-md hover:!text-white" @click="handleSelectPair(pair)">
                        <div class="flex items-center gap-2 ">
                            <template v-if="!brokenCoinIcons[coinIconKey(pair.currency_in?.symbol)]">
                                <img
                                    :src="`/images/coin_icons/${(pair.currency_in?.symbol || '').toUpperCase()}.png`"
                                    class="h-6 w-6 shrink-0 rounded-full object-cover"
                                    alt=""
                                    @error="onCoinIconError(pair.currency_in?.symbol)"
                                />
                            </template>
                            <div
                                v-else
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15 text-[10px] font-semibold uppercase text-white"
                                aria-hidden="true"
                            >
                                {{ coinInitial(pair.currency_in?.symbol) }}
                            </div>
                            <div class="text-white text-sm text-nowrap">
                             {{ pair.currency_in?.symbol }}<template v-if="hasQuoteInLabel(pair)"><span class="text-white/50">/{{ pair.currency_out?.symbol }}</span></template>
                            </div>
                            <template v-if="!brokenCoinIcons[coinIconKey(pair.currency_out?.symbol)]">
                                <img
                                    :src="`/images/coin_icons/${(pair.currency_out?.symbol || '').toUpperCase()}.png`"
                                    class="h-6 w-6 shrink-0 rounded-full object-cover ml-auto"
                                    alt=""
                                    @error="onCoinIconError(pair.currency_out?.symbol)"
                                />
                            </template>
                            <div
                                v-else
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15 text-[10px] font-semibold uppercase text-white ml-auto"
                                aria-hidden="true"
                            >
                                {{ coinInitial(pair.currency_out?.symbol) }}
                            </div>
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

<style scoped>
.select-pair-search { display: block; width: 100%; }
.select-pair-search input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    background: #1D323E;
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 14px;
    outline: none;
    transition: border 0.2s;
}
.select-pair-search input::placeholder { color: rgba(255, 255, 255, 0.45); }
.select-pair-search input:focus { border-color: rgba(121, 249, 149, 0.5); }
</style>
