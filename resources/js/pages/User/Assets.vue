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
import { defineProps, ref } from 'vue';
const selectedTab = ref('AssetsTab');
const props = defineProps({
    portfolioWallets: Array,
    depositWallets: Array,
    totalBalancePortfolio: Number,
    totalBalanceAssets: Number,
    bills: Array,
    withdraws: {
        type: Array,
        default: () => [],
    },
});
function changeTab(tab) {
    selectedTab.value = tab;
}
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
                                <AssetsTab v-if="selectedTab === 'AssetsTab'" :bills="props.bills" :totalBalanceAssets="props.totalBalanceAssets" />
                                <StackingTab v-if="selectedTab === 'StackingTab'" />
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
            <Stacking />
            <WithdrawModal :bills="props.bills" />
        </main>
    </MainLayout>
</template>

<style scoped></style>
