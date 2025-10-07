<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const { props } = usePage();

const isOpen = computed({
    get: () => modal.isOpen('convert'),
    set: (v) => (v ? modal.open('convert') : modal.close('convert')),
});

const currencies = computed(() => props.currencies || []);
const fromCurrency = ref(null);
const toCurrency = ref(null);

const form = useForm({
    from_currency_id: '',
    to_currency_id: '',
    amount: '',
});

function selectFromCurrency(currency) {
    fromCurrency.value = currency;
    form.from_currency_id = currency.id;
}

function selectToCurrency(currency) {
    toCurrency.value = currency;
    form.to_currency_id = currency.id;
}

function submitConvert() {
    form.post('/account/convert', {
        onSuccess: () => {
            isOpen.value = false;
            form.reset();
            fromCurrency.value = null;
            toCurrency.value = null;
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
            <h2 class="h1_25 pb15">Convert cryptocurrency</h2>

            <form @submit.prevent="submitConvert">
                <div class="convert-container pb20">
                    <div class="convert-input_label pb10">
                        <p class="text_16 _115 color-gray2 pb10">From</p>
                        <select
                            v-model="fromCurrency"
                            @change="selectFromCurrency(fromCurrency)"
                            class="input"
                            :class="{ error: form.errors.from_currency_id }"
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
                            v-if="form.errors.from_currency_id"
                            class="error-message text-red"
                        >
                            {{ form.errors.from_currency_id }}
                        </div>
                    </div>

                    <div class="convert-input_label pb10">
                        <p class="text_16 _115 color-gray2 pb10">To</p>
                        <select
                            v-model="toCurrency"
                            @change="selectToCurrency(toCurrency)"
                            class="input"
                            :class="{ error: form.errors.to_currency_id }"
                        >
                            <option value="">Choose cryptocurrency</option>
                            <option
                                v-for="currency in currencies"
                                :key="currency.id"
                                :value="currency"
                                :disabled="currency.id === fromCurrency?.id"
                            >
                                {{ currency.currency.name }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.to_currency_id"
                            class="error-message text-red"
                        >
                            {{ form.errors.to_currency_id }}
                        </div>
                    </div>

                    <div class="convert-input_label" v-if="fromCurrency">
                        <p class="text_16 _115 color-gray2 pb10">Amount</p>
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
                            Available: {{ fromCurrency.balance }}
                            {{ fromCurrency.currency.name }}
                        </p>
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="
                        !fromCurrency ||
                        !toCurrency ||
                        !form.amount ||
                        form.processing
                    "
                >
                    {{ form.processing ? 'Converting...' : 'Convert' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
