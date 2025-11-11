<script setup>
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';
import { calculateInUsd, calculateRate } from '@/utils/rates';
import { useModalStore } from '@/stores/modal.js';
import { defineProps, ref, watch } from 'vue';
const props = defineProps({
    portfolioWallets: Array,
    totalBalancePortfolio: Number,
});
const portfolioWallets = ref(props.portfolioWallets);
const search = ref('');
const isHiddenZero = ref(false);
const modal = useModalStore();

watch(search, searchPortfolioWallets);
watch(isHiddenZero, (newValue) => {
    if (newValue) {
        portfolioWallets.value = props.portfolioWallets.filter(wallet => wallet.balance > 0);
    } else {
        portfolioWallets.value = props.portfolioWallets;
    }
});

function toggleZeroBalance(event) {
    isHiddenZero.value = event.target.checked;
}
function searchPortfolioWallets() {
    if(isHiddenZero.value) {
        portfolioWallets.value = props.portfolioWallets.filter(wallet => wallet.balance > 0 && wallet.currency.name.toLowerCase().includes(search.value.toLowerCase()));
    } else {
        portfolioWallets.value = props.portfolioWallets.filter(wallet => wallet.currency.name.toLowerCase().includes(search.value.toLowerCase()));
    }

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
        <div class="assets-balances flex-center gap10 pt15">
            <div class="text_17 block">
                <img src="/images/balance_icon-available.svg" alt="" />
                <p>Available balance:</p>
                <span> {{ props.totalBalancePortfolio ?? 0 }} USD</span>

            </div>
            <button @click="modal.open('invest')" class="btn small_btn btn_16">
                Invest
            </button>
            <!-- <div class="text_17 block">
                <img src="/images/balance_icon-spot.svg" alt="" />
                <p>Spot balance:</p>
                <span> USD</span>
                <span class="color-gray2">≈ BTC</span>
            </div> -->
        </div>
        <div class="assets-search pt40">
            <label class="assets-search_input">
                <input
                    type="text"
                    class="clear text_small_14"
                    placeholder="Search"
                    v-model="search"
                />
            </label>
        </div>
        <div class="assets-overview pt10 pb20">
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
                <div>Total balance</div>
            </div>
            <div
                v-for="wallet in portfolioWallets"
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
                    <span class="text_16"> {{ wallet.balance }}</span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            calculateRate(
                                wallet.balance,
                                wallet.currency.exchange_rate,
                            )
                        }}
                        USD
                    </span>
                </div>

                <div class="flex-column gap10">
                    <span class="text_16">{{ wallet.pending_balance }}</span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            wallet.currency.exchange_rate *
                            wallet.pending_balance
                        }}
                        USD
                    </span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        {{
                            (
                                parseFloat(wallet.balance) +
                                parseFloat(wallet.pending_balance)
                            ).toFixed(2)
                        }}
                    </span>
                    <span class="text_small_12 color-gray2">
                        ≈
                        {{
                            calculateRate(
                                wallet.balance + wallet.pending_balance,
                                wallet.currency.exchange_rate,
                            )
                        }}
                        USD
                    </span>
                </div>
            </div>
            <p class="notfound" id="assetsZero" style="display: none">
                Nothing found
                <img src="/images/modal_close.svg" alt="" />
            </p>
        </div>
    </div>
</template>

<style scoped></style>
