<script setup>
import { computed } from 'vue';
import { useModalStore } from '@/stores/modal.js';

const props = defineProps({
    stakingEnabled:  { type: Boolean, default: true },
    stakingPlans:    { type: Array, default: () => [] },
    userStakings:    { type: Array, default: () => [] },
});

const modal = useModalStore();

function openStaking() {
    modal.open('stacking');
}

const activeStakings = computed(() =>
    props.userStakings.filter(s => s.status === 'active')
);

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString();
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
            <div v-for="staking in activeStakings" :key="staking.id" class="grid-line">
                <div class="flex-center gap6">
                    <img
                        width="30px"
                        :src="'/images/coin_icons/' + (staking.plan?.currency?.icon ?? staking.plan?.currency?.symbol ?? '').toLowerCase() + '.svg'"
                        alt=""
                    />
                    <span>{{ staking.plan?.currency?.symbol }}</span>
                </div>
                <div>
                    <span class="text_small_14">{{ staking.apy_rate }}%</span>
                </div>
                <div>
                    <span class="text_16">{{ staking.duration_days }}</span>
                </div>
                <div>
                    <span class="text_small_14">{{ (staking.amount + staking.reward_amount).toFixed(8) }}</span>
                </div>
                <div>
                    <span class="text_16">{{ formatDate(staking.started_at) }}</span>
                </div>
            </div>

            <p v-if="!activeStakings.length" class="notfound">
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
