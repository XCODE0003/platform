<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const { props } = usePage();

const isOpen = computed({
    get: () => modal.isOpen('stacking'),
    set: (v) => (v ? modal.open('stacking') : modal.close('stacking')),
});

const currencies = computed(() => props.currencies || []);
const selectedCurrency = ref(null);

const form = useForm({
    currency_id: '',
    amount: '',
    duration: '30', // days
});

function selectCurrency(currency) {
    selectedCurrency.value = currency;
    form.currency_id = currency.id;
}

function submitStaking() {
    form.post('/account/staking', {
        onSuccess: () => {
            isOpen.value = false;
            form.reset();
            selectedCurrency.value = null;
        },
    });
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
            <h2 class="h1_25 pb15">Staking</h2>
            <p class="text_18 pb25">
                Earn rewards by staking your cryptocurrency
            </p>

            <form @submit.prevent="submitStaking">
                <div class="pb20">
                    <p class="text_16 _115 color-gray2 pb10">
                        Select cryptocurrency
                    </p>
                    <select
                        v-model="selectedCurrency"
                        @change="selectCurrency(selectedCurrency)"
                        class="input"
                        :class="{ error: form.errors.currency_id }"
                    >
                        <option value="">Choose cryptocurrency</option>
                        <option
                            v-for="currency in currencies"
                            :key="currency.id"
                            :value="currency"
                        >
                            {{ currency.currency.name }} ({{
                                currency.balance
                            }})
                        </option>
                    </select>
                    <div
                        v-if="form.errors.currency_id"
                        class="error-message text-red"
                    >
                        {{ form.errors.currency_id }}
                    </div>
                </div>

                <div class="pb20" v-if="selectedCurrency">
                    <p class="text_16 _115 color-gray2 pb10">
                        Amount {{ selectedCurrency.currency.name }}
                    </p>
                    <input
                        type="number"
                        step="0.00000001"
                        v-model="form.amount"
                        class="input"
                        :class="{ error: form.errors.amount }"
                        placeholder="0.00000000"
                    />
                    <div
                        v-if="form.errors.amount"
                        class="error-message text-red"
                    >
                        {{ form.errors.amount }}
                    </div>
                    <p class="text_small_12 color-gray2 pt5">
                        Available: {{ selectedCurrency.balance }}
                        {{ selectedCurrency.currency.name }}
                    </p>
                </div>

                <div class="pb20" v-if="selectedCurrency">
                    <p class="text_16 _115 color-gray2 pb10">
                        Staking duration
                    </p>
                    <select v-model="form.duration" class="input">
                        <option value="30">30 days (5% APY)</option>
                        <option value="60">60 days (7% APY)</option>
                        <option value="90">90 days (10% APY)</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="
                        !selectedCurrency || !form.amount || form.processing
                    "
                >
                    {{ form.processing ? 'Processing...' : 'Start Staking' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
