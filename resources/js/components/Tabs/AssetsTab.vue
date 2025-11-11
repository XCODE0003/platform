<script setup lang="ts">
import ModalButtons from '@/components/Tabs/Elements/ModalButtons.vue';
import { computed, defineProps, ref } from 'vue';
import { useUserStore } from '@/stores/userStore.js';
import { useModalStore } from '@/stores/modal.js';

const props = defineProps({
    totalBalanceAssets: Number,
});

const userStore = useUserStore();
const modal = useModalStore();

const search = ref('');
const isHiddenZero = ref(false);

// Вычисляемый — всегда актуален, не мутирует пропсы и стор
const bills = computed(() => {
    const list = userStore.bills ?? [];
    let filtered = Array.isArray(list) ? list : [];

    if (search.value) {
        const q = search.value.toString().toLowerCase();
        filtered = filtered.filter(bill => {
            const name = (bill.name ?? bill.bill_name ?? '').toString().toLowerCase();
            return name.includes(q);
        });
    }

    if (isHiddenZero.value) {
        filtered = filtered.filter(bill => Number(bill.balance ?? 0) > 0);
    }

    return filtered;
});
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
                <span> {{ props.totalBalanceAssets ?? 0 }} USD</span>

            </div>
             <button @click="modal.open('bill')" class="btn small_btn btn_16">
                Create new bill
            </button>
            <!-- <div class="text_17 block">
                <img src="/images/balance_icon-spot.svg" alt="" />
                <p>Spot balance:</p>
                <span> USD</span>
                <span class="color-gray2">≈ BTC</span>
            </div> -->
        </div>
        <div class="assets-search pt40 flex items-center gap-2">
            <label class="assets-search_input">
                <input type="text" class="clear text_small_14" v-model="search" placeholder="Search" />
            </label>

        </div>
        <div class="assets-overview pt10 pb20">
            <div class="hide-container">
                <div class="form-check">
                    <input type="checkbox" v-model="isHiddenZero" id="hidezero" class="checkbox" />
                    <label for="hidezero" class="text_small_12 color-gray2">Hide zero balances</label>
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
            <div v-for="bill in bills" :key="bill.id || bill.name || bill.bill_name" class="grid-line" data-balance_coin="">
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
                    <span>{{ bill.name ?? bill.bill_name }}</span>
                </div>
                <div class="flex-column gap10">
                    <span class="text_16"> {{ Number(bill.balance ?? 0).toFixed(2) }} {{ bill.currency?.symbol ?? '' }}</span>

                </div>

                <div class="flex-column gap10">
                    <span class="text_16">{{ Number(bill.pending_balance ?? 0).toFixed(2) }} {{ bill.currency?.symbol ?? '' }}</span>

                </div>
                <div class="flex-column gap10">
                    <span class="text_16">
                        {{ (Number(bill.balance ?? 0) + Number(bill.pending_balance ?? 0)).toFixed(2) }} {{ bill.currency?.symbol ?? '' }}
                    </span>
                </div>
            </div>
            <p v-if="bills.length === 0" class="notfound">
                Nothing found
                <img src="/images/modal_close.svg" alt="" />
            </p>
        </div>
    </div>
</template>

<style scoped></style>
