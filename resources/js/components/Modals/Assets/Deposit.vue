<script setup>
import { useModalStore } from '@/stores/modal.js';
import { computed, defineProps, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    depositWallets: Array,
});

const modal = useModalStore();
const isOpen = computed({
    get: () => modal.isOpen('deposit'),
    set: (v) => (v ? modal.open('deposit') : modal.close('deposit')),
});

const selectedTab = ref('DepositCrypto');
const selectedCurrency = ref(props.depositWallets[0]);
const currencies = computed(() => props.currencies || []);

function changeTab(tab) {
    selectedTab.value = tab;
}

function selectCurrency(currency) {
    selectedCurrency.value = currency;
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
            <h2 class="h1_25 pb15">Deposit</h2>
            <p class="text_18 pb25">
                Deposit your existing crypto assets via the blockchain
            </p>
            <p class="text_16 _115 color-gray2 pb10">
                What cryptocurrency do you want to deposit?
            </p>
            <div id="tabsDeposit">
                <ul class="tabs-navDeposit gap6 pb20 flex">
                    <li>
                        <button
                            :class="{ active: selectedTab === 'DepositCrypto' }"
                            @click="changeTab('DepositCrypto')"
                            class="text_small_14 assets-menu_btn"
                        >
                            Crypto
                        </button>
                    </li>
                    <li>
                        <button
                            :class="{ active: selectedTab === 'DepositFiat' }"
                            @click="changeTab('DepositFiat')"
                            class="text_small_14 assets-menu_btn"
                        >
                            Card
                        </button>
                    </li>
                </ul>
                <div class="tabs-itemsDeposit">
                    <!-- Crypto Deposit Tab -->
                    <div v-if="selectedTab === 'DepositCrypto'">
                        <div class="currency-selector pb20">
                            <select
                                v-model="selectedCurrency"
                                class="input"
                                @change="selectCurrency"
                            >
                                <option value="">Select cryptocurrency</option>
                                <option
                                    v-for="currency in depositWallets"
                                    :key="currency.id"
                                    :value="currency"
                                >
                                    {{ currency.currency.symbol }}
                                </option>
                            </select>
                        </div>

                        <div v-if="selectedCurrency" class="deposit-info">
                            <div class="deposit-details">
                                <p class="text_small_14 color-gray2">
                                    Minimum deposit:
                                    {{
                                        selectedCurrency.currency
                                            ?.min_deposit_amount || '0.001'
                                    }}
                                    {{ selectedCurrency.currency?.symbol }}
                                </p>
                            </div>
                            <div class="deposit-address pb20">
                                <p class="text_16 pb10">Deposit Address:</p>
                                <div class="input-container flex">
                                    <input
                                        type="text"
                                        :value="
                                            selectedCurrency.address ||
                                            'Generating address...'
                                        "
                                        readonly
                                    />
                                    <button class="btn small_btn">Copy</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fiat Deposit Tab -->
                    <div v-if="selectedTab === 'DepositFiat'">
                        <p class="text_16 pb20">
                            Buy cryptocurrency with your credit/debit card
                        </p>
                        <button class="btn btn_action btn_16 color-dark">
                            Buy with Card
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
