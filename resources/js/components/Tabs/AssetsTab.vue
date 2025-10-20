<script setup>
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';
import { calculateInUsd, calculateRate } from '@/utils/rates';
import { defineProps, ref, watch } from 'vue';

const props = defineProps({
    bills: Array,
    totalBalanceAssets: Number,
});

const bills = ref(props.bills);
const search = ref('');
const isHiddenZero = ref(false);

// Объединяем логику фильтрации в одну функцию
function applyFilters() {
    let filteredBills = props.bills;

    // Применяем фильтр по поиску
    if (search.value) {
        filteredBills = filteredBills.filter(bill =>
            bill.name.toLowerCase().includes(search.value.toLowerCase())
        );
    }

    // Применяем фильтр по нулевым балансам
    if (isHiddenZero.value) {
        filteredBills = filteredBills.filter(bill => bill.balance > 0);
    }

    bills.value = filteredBills;
}

// Следим за изменениями поиска и фильтра нулевых балансов
watch([search, isHiddenZero], applyFilters);

function toggleZeroBalance(event) {
    isHiddenZero.value = event.target.checked;
}
</script>

<template>
    <div class="tab-item">
        <div class="assets-title-block">
            <div class="assets-title-block_start">
                <h1 class="h1_25">Assets overview</h1>
            </div>
            <ModalButtons />
        </div>
        <div class="assets-balances flex-center gap10 pt15">
            <div class="text_17 block">
                <img src="/images/balance_icon-available.svg" alt="" />
                <p>Available balance:</p>
                <span> {{ props.totalBalanceAssets }} USD</span>

            </div>
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
                    v-model="search"
                    placeholder="Search"
                />
            </label>
        </div>
        <div class="assets-overview pt10 pb20">
            <div class="hide-container">
                <div class="form-check">
                    <input
                        type="checkbox"
                        :checked="isHiddenZero"
                        @change="toggleZeroBalance"
                        id="hidezero"
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
                <div>Name bill</div>
                <div>Available balance</div>

                <div>On orders</div>
                <div>Total balance</div>
            </div>
            <div
                v-for="bill in bills"
                class="grid-line"
                data-balance_coin=""
            >
                <div class="flex-center gap6">
                    <!-- <img
                        width="30px"
                        :src="
                            '/images/coin_icons/' +
                            bill.currency.icon.toLowerCase() +
                            '.svg'
                        "
                        alt=""
                    /> -->
                    <span>{{ bill.name }}</span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16"> {{ bill.balance }} {{ bill.currency.symbol }}</span>

                </div>

                <div class="flex-column gap10">
                    <span class="text_16">{{ bill.pending_balance ?? 0 }} {{ bill.currency.symbol }}</span>

                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        {{ (Number(bill.balance) + Number(bill.pending_balance ?? 0)).toFixed(2) }} {{ bill.currency.symbol }}
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
