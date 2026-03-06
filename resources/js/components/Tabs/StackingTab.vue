<script setup>
import { useModalStore } from '@/stores/modal.js';

const props = defineProps({
    stakingEnabled:  { type: Boolean, default: true },
    stakingPlans:    { type: Array, default: () => [] },
});

const modal = useModalStore();

function openStaking() {
    modal.open('stacking');
}
</script>

<template>
    <div class="tab-item stacking-tab">
        <div class="assets-title-block">
            <div class="assets-title-block_start">
                <h1 class="h1_25">Earn with Cryptohouse</h1>
                <p class="text_18 _110" style="max-width: 280px">
                    Invest to earn stable profits through a professional asset
                    manager
                </p>
            </div>
        </div>

        <div class="stacking-btn-container pt20 flex-jcenter">
            <button class="btn btn_start_2" :disabled="!props.stakingEnabled" @click="openStaking">
                Staking
            </button>
        </div>

        <h2 class="h2_20 pb25 pt40">Locked Staking</h2>
        <div class="assets-overview-grid assets-stacking-grid pb60">
            <div class="grid-head text_small_12 color-dark">
                <div>Coin</div>
                <div>Est. APY</div>
                <div>Duration (Days)</div>
                <div>Final Amount</div>
                <div>Date, time</div>
            </div>
            <div v-for="plan in props.stakingPlans" :key="plan.id" class="grid-line">
                <div class="flex-center gap6">
                    <img
                        width="30px"
                        :src="'/images/coin_icons/' + (plan.currency?.icon ?? plan.currency?.symbol ?? '').toLowerCase() + '.svg'"
                        alt=""
                    />
                    <span>{{ plan.currency?.symbol }}</span>
                </div>
                <div>
                    <span class="text_small_14">{{ plan.apy_percent }}%</span>
                </div>
                <div>
                    <span class="text_16">{{ plan.duration_days }}</span>
                </div>
                <div>
                    <span class="text_small_14">-</span>
                </div>
                <div>
                    <span class="text_16">-</span>
                </div>
            </div>

            <p v-if="!props.stakingPlans.length" class="notfound">
                Nothing found
                <img src="/images/notfound.svg" alt="" />
            </p>
        </div>

        <p v-if="!props.stakingEnabled" class="text_small_12 color-gray2 pt10">
            New staking is currently disabled by administrator
        </p>
    </div>
</template>

<style scoped></style>
