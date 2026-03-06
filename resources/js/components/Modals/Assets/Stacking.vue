<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    stakingPlans:    { type: Array, default: () => [] },
    portfolioWallets: { type: Array, default: () => [] },
});

const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('stacking'),
    set: (v) => (v ? modal.open('stacking') : modal.close('stacking')),
});

const selectedPlan = ref(null);

const walletBalance = computed(() => {
    if (!selectedPlan.value) return 0;
    const w = props.portfolioWallets.find(w => w.currency_id === selectedPlan.value.currency_id);
    return parseFloat(w?.balance ?? '0');
});

const estimatedReward = computed(() => {
    if (!selectedPlan.value || !form.amount) return 0;
    const amt = parseFloat(form.amount || '0');
    return Number((amt * (selectedPlan.value.apy_percent / 100) * (selectedPlan.value.duration_days / 365)).toFixed(10));
});

const form = useForm({ plan_id: '', amount: '' });

watch(isOpen, (v) => { if (!v) { form.reset(); selectedPlan.value = null; } });

function selectPlan(plan) {
    selectedPlan.value = plan;
    form.plan_id = plan.id;
    form.amount  = '';
}

function submit() {
    form.post('/assets/staking/start', {
        preserveScroll: true,
        onSuccess: () => {
            toast.showSuccess('Staking started successfully!');
            isOpen.value = false;
        },
        onError: (e) => { Object.values(e).forEach(m => toast.showError(m)); },
    });
}
</script>

<template>
    <VueFinalModal
        v-model="isOpen"
        overlay-transition="vfm-fade"
        content-transition="vfm-fade"
        click-to-close esc-to-close
        background="non-interactive"
        lock-scroll
        class="flex items-center justify-center"
        content-class="max-w-xl mx-4 p-4 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-lg"
    >
        <div class="modal show">
            <button class="closemodal clear" @click="isOpen = false">
                <img src="/images/modal_close.svg" alt="" />
            </button>
            <h2 class="h1_25 pb15">Staking</h2>
            <p class="text_18 pb25">Earn rewards by locking your crypto assets</p>

            <form @submit.prevent="submit" class="staking-form">
                <!-- Plan selection -->
                <div class="pb20">
                    <p class="text_16 color-gray2 pb10">Choose a plan</p>
                    <div v-if="stakingPlans.length" class="plans-grid">
                        <button
                            v-for="plan in stakingPlans"
                            :key="plan.id"
                            type="button"
                            class="plan-card"
                            :class="{ active: selectedPlan?.id === plan.id }"
                            @click.stop="selectPlan(plan)"
                        >
                            <div class="plan-currency">
                                <img
                                    :src="'/images/coin_icons/' + (plan.currency?.icon ?? plan.currency?.symbol ?? '').toLowerCase() + '.svg'"
                                    width="28" height="28" alt=""
                                />
                                <span class="plan-symbol">{{ plan.currency?.symbol }}</span>
                            </div>
                            <div class="plan-name">{{ plan.name }}</div>
                            <div class="plan-apy">{{ plan.apy_percent }}% APY</div>
                            <div class="plan-duration">{{ plan.duration_days }} days</div>
                            <div class="plan-min" v-if="plan.min_amount > 0">
                                Min: {{ plan.min_amount }}
                            </div>
                        </button>
                    </div>
                    <p v-else class="text_small_12 color-gray2">No staking plans available</p>
                </div>

                <!-- Amount -->
                <div class="pb20" v-if="selectedPlan">
                    <div class="amount-head">
                        <p class="text_16 color-gray2">Amount ({{ selectedPlan.currency?.symbol }})</p>
                        <button type="button" class="max-btn" @click.stop="form.amount = walletBalance.toFixed(8)">Max</button>
                    </div>
                    <input
                        type="number" step="0.00000001"
                        v-model="form.amount"
                        class="input" :class="{ error: form.errors.amount }"
                        placeholder="0.00000000" min="0"
                    />
                    <p v-if="form.errors.amount" class="error-msg">{{ form.errors.amount }}</p>
                    <p class="text_small_12 color-gray2 pt5">
                        Available in portfolio: {{ walletBalance.toFixed(8) }} {{ selectedPlan.currency?.symbol }}
                    </p>
                </div>

                <!-- Reward preview -->
                <div class="reward-box pb20" v-if="selectedPlan && form.amount">
                    <div class="reward-row">
                        <span class="text_small_12 color-gray2">Est. reward</span>
                        <span class="text_small_12">{{ estimatedReward.toFixed(8) }} {{ selectedPlan.currency?.symbol }}</span>
                    </div>
                    <div class="reward-row">
                        <span class="text_small_12 color-gray2">Total at maturity</span>
                        <span class="text_small_12">
                            {{ (parseFloat(form.amount || 0) + estimatedReward).toFixed(8) }} {{ selectedPlan.currency?.symbol }}
                        </span>
                    </div>
                    <div class="reward-row">
                        <span class="text_small_12 color-gray2">Unlock date</span>
                        <span class="text_small_12">
                            {{ new Date(Date.now() + selectedPlan.duration_days * 86400000).toLocaleDateString() }}
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn_action btn_16 color-dark"
                    :disabled="!selectedPlan || !form.amount || form.processing">
                    {{ form.processing ? 'Processing...' : 'Start Staking' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.staking-form { display: flex; flex-direction: column; }

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
}

.plan-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.plan-card:hover { border-color: rgba(121,249,149,0.3); background: rgba(255,255,255,0.06); }
.plan-card.active { border-color: rgba(121,249,149,0.7); background: rgba(121,249,149,0.12); }

.plan-currency { display: flex; align-items: center; gap: 6px; }
.plan-symbol { font-weight: 700; font-size: 15px; }
.plan-name { font-size: 11px; color: rgba(255,255,255,0.6); }
.plan-apy { font-size: 18px; font-weight: 700; color: #79f995; }
.plan-duration { font-size: 12px; color: rgba(255,255,255,0.7); }
.plan-min { font-size: 11px; color: rgba(255,255,255,0.45); }

.amount-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.max-btn { font-size: 12px; padding: 5px 10px; border-radius: 6px; background: rgba(255,255,255,0.08); color: white; cursor: pointer; }
.max-btn:hover { background: rgba(121,249,149,0.2); }

.reward-box {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex; flex-direction: column; gap: 8px;
}
.reward-row { display: flex; justify-content: space-between; }
.error-msg { margin-top: 4px; font-size: 12px; color: #ef4444; }
</style>
