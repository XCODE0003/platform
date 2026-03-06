<script setup>
import Deposit from '@/components/Modals/Assets/Deposit.vue';
import Promocode from '@/components/Modals/Assets/Promocode.vue';
import Stacking from '@/components/Modals/Assets/Stacking.vue';
import WithdrawModal from '@/components/Modals/Assets/WithdrawModal.vue';
import AssetsTab from '@/components/Tabs/AssetsTab.vue';
import StackingTab from '@/components/Tabs/StackingTab.vue';
import TransactionTab from '@/components/Tabs/TransactionTab.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import PortfolioTab from '@/components/Tabs/PortfolioTab.vue';
import { defineProps, onMounted, ref } from 'vue';
import BillNew from '../../components/Modals/Assets/BillNew.vue';
const selectedTab = ref('AssetsTab');
import { useModalStore } from '@/stores/modal.js';
import { useUserStore } from '../../stores/userStore.js';
import Invest from '../../components/Modals/Assets/Invest.vue';
const userStore = useUserStore()
const modal = useModalStore();
const props = defineProps({
    portfolioWallets: Array,
    depositWallets: Array,
    totalBalancePortfolio: Number,
    totalBalanceAssets: Number,
    bills: Object,
    withdraws: {
        type: Array,
        default: () => [],
    },
    portfolioFeePercent: { type: Number, default: 0 },
    portfolioFeeFixed:   { type: Number, default: 0 },
    stakingPlans:  { type: Array, default: () => [] },
    userStakings:  { type: Array, default: () => [] },
});
function changeTab(tab) {
    selectedTab.value = tab;
}

onMounted(() => {
    userStore.bills = props.bills;
})
</script>

<template>
    <MainLayout>
        <main class="assets h100">
            <section class="assets">
                <div class="container">
                    <div class="assets-content">
                        <div class="tabs-wrapper">
                            <div class="tabs assets-menu">

                                <span @click="changeTab('AssetsTab')" :class="{
                                    active: selectedTab === 'AssetsTab',
                                }" class="tab btn_16 assets-menu_btn">Overview</span>
                                <span @click="changeTab('PortfolioTab')" :class="{
                                    active: selectedTab === 'PortfolioTab',
                                }" class="tab btn_16 assets-menu_btn">Portfolio</span>
                                <span @click="changeTab('StackingTab')" :class="{
                                    active: selectedTab === 'StackingTab',
                                }" class="tab btn_16 assets-menu_btn">Staking</span>
                                <span @click="changeTab('TransactionTab')" :class="{
                                    active:
                                        selectedTab === 'TransactionTab',
                                }" class="tab btn_16 assets-menu_btn">Transaction history</span>
                            </div>
                            <div class="tabs-content">
                                <AssetsTab v-if="selectedTab === 'AssetsTab'"  :totalBalanceAssets="props.totalBalanceAssets" />
                                <StackingTab v-if="selectedTab === 'StackingTab'"
                                    :stakingPlans="props.stakingPlans"
                                    :userStakings="props.userStakings"
                                    :portfolioWallets="props.portfolioWallets"
                                />
                                <TransactionTab v-if="selectedTab === 'TransactionTab'" :withdraws="props.withdraws" />
                                <PortfolioTab v-if="selectedTab === 'PortfolioTab'" :portfolioWallets="props.portfolioWallets" :totalBalancePortfolio="props.totalBalancePortfolio" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Модальные окна -->
            <Deposit :depositWallets="props.depositWallets" />
            <Promocode />
            <Stacking
                :stakingPlans="props.stakingPlans"
                :portfolioWallets="props.portfolioWallets"
            />
            <WithdrawModal :bills="props.bills" />
            <BillNew :bills="props.bills" />
            <Invest
                :bills="props.bills"
                :portfolioWallets="props.portfolioWallets"
                :portfolioFeePercent="props.portfolioFeePercent"
                :portfolioFeeFixed="props.portfolioFeeFixed"
            />
        </main>
    </MainLayout>
</template>

<style scoped></style>
