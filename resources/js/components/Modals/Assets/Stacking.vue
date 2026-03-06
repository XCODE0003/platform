<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    stakingEnabled: { type: Boolean, default: true },
    stakingYearBasisDays: { type: Number, default: 365 },
    stakingPlans:    { type: Array, default: () => [] },
    portfolioWallets: { type: Array, default: () => [] },
});

const modal = useModalStore();
const toast = useToast();

const isOpen = computed({
    get: () => modal.isOpen('stacking') || modal.isOpen('staking'),
    set: (v) => {
        if (v) {
            modal.open('stacking');
            return;
        }

        modal.close('stacking');
        modal.close('staking');
    },
});

const selectedPlan = ref(null);

const walletBalance = computed(() => {
    if (!selectedPlan.value) return 0;
    const w = props.portfolioWallets.find(w => w.currency_id === selectedPlan.value.currency_id);
    return parseFloat(w?.balance ?? '0');
});

const estimatedReward = computed(() => {
    if (!selectedPlan.value || !form.amount) return 0;
    const yearBasisDays = props.stakingYearBasisDays > 0
        ? props.stakingYearBasisDays
        : 365;
    const amt = parseFloat(form.amount || '0');
    return Number((amt * (selectedPlan.value.apy_percent / 100) * (selectedPlan.value.duration_days / yearBasisDays)).toFixed(10));
});

const form = useForm({ plan_id: '', amount: '' });

watch(isOpen, (v) => {
    if (!v) {
        form.reset();
        selectedPlan.value = null;
        return;
    }

    const planId = modal.payload?.planId;
    if (!planId) {
        return;
    }

    selectPlanById(planId);
});

function selectPlan(plan) {
    selectedPlan.value = plan;
    form.plan_id = plan.id;
    form.amount  = '';
}

function selectPlanById(planId) {
    const matchedPlan = props.stakingPlans.find(
        plan => Number(plan.id) === Number(planId)
    );

    if (matchedPlan) {
        selectPlan(matchedPlan);
    }
}

function handlePlanChange() {
    selectPlanById(form.plan_id);
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
                    <p v-if="!props.stakingEnabled" class="text_small_12 color-gray2 pb10">
                        New staking is currently disabled by administrator
                    </p>
                    <select
                        v-model="form.plan_id"
                        class="input"
                        :class="{ error: form.errors.plan_id }"
                        :disabled="!stakingPlans.length"
                        @change="handlePlanChange"
                    >
                        <option value="">Select plan</option>
                        <option
                            v-for="plan in stakingPlans"
                            :key="plan.id"
                            :value="plan.id"
                        >
                            {{ plan.currency?.symbol }} — {{ plan.name }} — {{ plan.apy_percent }}% / {{ plan.duration_days }}d
                        </option>
                    </select>
                    <p v-if="form.errors.plan_id" class="error-msg">{{ form.errors.plan_id }}</p>
                    <p v-if="selectedPlan" class="text_small_12 color-gray2 pt8">
                        Min: {{ selectedPlan.min_amount }} {{ selectedPlan.currency?.symbol }}
                        <span v-if="selectedPlan.max_amount"> • Max: {{ selectedPlan.max_amount }} {{ selectedPlan.currency?.symbol }}</span>
                    </p>
                    <p v-if="!stakingPlans.length" class="text_small_12 color-gray2">No staking plans available</p>
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
                    :disabled="!props.stakingEnabled || !selectedPlan || !form.amount || form.processing">
                    {{ form.processing ? 'Processing...' : 'Start Staking' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped>
.staking-form { display: flex; flex-direction: column; }

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
.pt8 { padding-top: 8px; }
</style>
