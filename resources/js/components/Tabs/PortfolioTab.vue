<script setup>
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';
import { calculateInUsd, calculateRate } from '@/utils/rates';
import { formatAmount } from '@/utils/formatAmount.js';
import { useModalStore } from '@/stores/modal.js';
import { computed, defineProps, ref } from 'vue';
const props = defineProps({
    portfolioWallets: Array,
    totalBalancePortfolio: Number,
});
const search = ref('');
const isHiddenZero = ref(false);
const modal = useModalStore();

const ASSET_CLASS_TABS = [
    { key: 'crypto', label: 'Crypto', classes: ['crypto'] },
    { key: 'stock',  label: 'Stocks', classes: ['stock'] },
    { key: 'index',  label: 'Indices', classes: ['index'] },
];
const activeAssetClass = ref('crypto');

const filteredWallets = computed(() => {
    const tab = ASSET_CLASS_TABS.find(t => t.key === activeAssetClass.value);
    const allowed = new Set(tab?.classes ?? []);
    const q = search.value.trim().toLowerCase();

    return (props.portfolioWallets ?? []).filter((wallet) => {
        if (!allowed.has(wallet.asset_class ?? 'crypto')) return false;
        if (isHiddenZero.value && !(Number(wallet.balance) > 0)) return false;
        if (q && !(wallet.currency?.name ?? '').toLowerCase().includes(q)) return false;
        return true;
    });
});

function isInvested(wallet) {
    return Number(wallet?.total_invested_usd ?? 0) > 0;
}

// Assets the user has actually invested in are shown first (largest first),
// then a separator, then the rest of the assets in this category.
const investedWallets = computed(() =>
    filteredWallets.value
        .filter(isInvested)
        .slice()
        .sort((a, b) => Number(b.total_invested_usd ?? 0) - Number(a.total_invested_usd ?? 0)),
);
const otherWallets = computed(() => filteredWallets.value.filter((w) => !isInvested(w)));

// Combined list with a divider sentinel between invested and the rest.
const portfolioWallets = computed(() => {
    const invested = investedWallets.value;
    const others   = otherWallets.value;
    if (invested.length && others.length) {
        return [...invested, { __divider: true }, ...others];
    }
    return [...invested, ...others];
});

function toggleZeroBalance(event) {
    isHiddenZero.value = event.target.checked;
}

function profitClass(wallet) {
    const p = Number(wallet?.profit_usd ?? 0);
    if (p > 0) return 'color-green';
    if (p < 0) return 'color-red';
    return 'color-gray2';
}

function profitDisplay(wallet) {
    const p = Number(wallet?.profit_usd);
    if (!Number.isFinite(p)) return '—';
    const sign = p > 0 ? '+' : '';
    return `${sign}${formatAmount(p, 'USD')} USD`;
}

function profitPercentDisplay(wallet) {
    const invested = Number(wallet?.total_invested_usd);
    const profit   = Number(wallet?.profit_usd);
    if (!Number.isFinite(invested) || invested <= 0 || !Number.isFinite(profit)) return '';
    const pct = (profit / invested) * 100;
    const sign = pct > 0 ? '+' : '';
    return `${sign}${pct.toFixed(2)}%`;
}
</script>

<template>
    <div class="tab-item">
        <div class="assets-title-block">
            <div class="assets-title-block_start">
                <h1 class="h1_25">Portfolio</h1>
            </div>
            <ModalButtons />
        </div>
        <div class="assets-balances flex-center gap10 pt15" style="justify-content: space-between;">
            <div class="flex-center gap10">
                <div class="text_17 block">
                    <img src="/images/balance_icon-available.svg" alt="" />
                    <p>Available balance:</p>
                    <span> {{ formatAmount(props.totalBalancePortfolio, 'USD') }} USD</span>
                </div>
                <button @click="modal.open('invest')" class="btn small_btn btn_16">
                    Invest
                </button>
                <button @click="modal.open('portfolio-withdraw')" class="btn small_btn btn_16">
                    Withdraw
                </button>
            </div>
            <div class="assets-search">
                <label class="assets-search_input">
                    <input
                        type="text"
                        class="clear text_small_14"
                        placeholder="Search"
                        v-model="search"
                    />
                </label>
            </div>
            <!-- <div class="text_17 block">
                <img src="/images/balance_icon-spot.svg" alt="" />
                <p>Spot balance:</p>
                <span> USD</span>
                <span class="color-gray2">≈ BTC</span>
            </div> -->
        </div>
        <div class="portfolio-asset-tabs pt15">
            <button
                v-for="t in ASSET_CLASS_TABS"
                :key="t.key"
                type="button"
                class="btn small_btn btn_16"
                :class="{ deposit: activeAssetClass === t.key }"
                @click="activeAssetClass = t.key"
            >
                {{ t.label }}
            </button>
        </div>
        <div class="assets-overview pt15 pb20">
            <div class="hide-container">
                <div class="form-check">
                    <input
                        type="checkbox"
                        id="hidezero"
                        :checked="isHiddenZero"
                        @change="toggleZeroBalance"
                        class="checkbox"
                    />
                    <label for="hidezero" class="text_small_12 color-gray2"
                        >Hide zero balances</label
                    >
                </div>
            </div>
        </div>
        <div class="assets-overview-grid pb60">
            <div class="grid-head text_small_12 color-dark">
                <div>Coin</div>
                <div>Available balance</div>
                <div>On orders</div>
                <div>Avg buy price</div>
                <div>Current price</div>
                <div>Profit</div>
                <div>Total balance</div>
            </div>
            <template v-for="(wallet, idx) in portfolioWallets" :key="wallet.__divider ? 'divider-' + idx : wallet.id">
            <div v-if="wallet.__divider" class="portfolio-divider"></div>
            <div
                v-else
                class="grid-line"
                data-balance_coin=""
            >
                <div class="flex-center gap6">
                    <img
                        width="30px"
                        :src="
                            '/images/coin_icons/' +
                            wallet.currency.icon.toLowerCase() +
                            '.svg'
                        "
                        alt=""
                    />
                    <span>{{ wallet.currency.name }}</span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16"> {{ formatAmount(wallet.balance, wallet.currency.symbol) }}</span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            formatAmount(
                                calculateRate(
                                    wallet.balance,
                                    wallet.currency.exchange_rate,
                                ),
                                'USD',
                            )
                        }}
                        USD
                    </span>
                </div>

                <div class="flex-column gap10">
                    <span class="text_16">{{ formatAmount(wallet.pending_balance, wallet.currency.symbol) }}</span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            formatAmount(
                                wallet.currency.exchange_rate *
                                    wallet.pending_balance,
                                'USD',
                            )
                        }}
                        USD
                    </span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        <template v-if="wallet.avg_buy_price_usd != null">
                            {{ formatAmount(wallet.avg_buy_price_usd, 'USD') }} USD
                        </template>
                        <template v-else>—</template>
                    </span>
                    <span class="text_small_12 color-gray2">
                        Invested: {{ formatAmount(wallet.total_invested_usd ?? 0, 'USD') }} USD
                    </span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        {{ formatAmount(wallet.currency.exchange_rate, 'USD') }} USD
                    </span>
                </div>
                <div class="flex-column gap10">
                    <span
                        class="text_16"
                        :class="profitClass(wallet)"
                    >
                        {{ profitDisplay(wallet) }}
                    </span>
                    <span
                        class="text_small_12"
                        :class="profitClass(wallet)"
                    >
                        {{ profitPercentDisplay(wallet) }}
                    </span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        {{ formatAmount(
                            parseFloat(wallet.balance) + parseFloat(wallet.pending_balance),
                            wallet.currency.symbol,
                        ) }}
                    </span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            formatAmount(
                                calculateRate(
                                    parseFloat(wallet.balance) + parseFloat(wallet.pending_balance),
                                    wallet.currency.exchange_rate,
                                ),
                                'USD',
                            )
                        }}
                        USD
                    </span>
                </div>
            </div>
            </template>
            <p class="notfound" id="assetsZero" style="display: none">
                Nothing found
                <img src="/images/modal_close.svg" alt="" />
            </p>
        </div>
    </div>
</template>

<style scoped>
.portfolio-asset-tabs {
    display: flex;
    gap: 10px;
}

/* Separator between invested assets (top) and the rest of the list. */
.portfolio-divider {
    height: 1px;
    margin: 6px 0;
    background: var(--Gray_2, #606E76);
    opacity: 0.45;
}
</style>
