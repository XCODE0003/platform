<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const { props } = usePage();
const isOpen = computed({
    get: () => modal.isOpen('withdraw'),
    set: (v) => (v ? modal.open('withdraw') : modal.close('withdraw')),
});

const selectedCurrency = ref(null);
const currencies = computed(() => props.currencies || []);

// Форма для вывода средств
const form = useForm({
    currency_id: '',
    address: '',
    amount: '',
});

function selectCurrency(currency) {
    selectedCurrency.value = currency;
    form.currency_id = currency.id;
}

function submitWithdraw() {
    form.post('/account/withdraw', {
        onSuccess: () => {
            isOpen.value = false;
            form.reset();
            selectedCurrency.value = null;
        },
    });
}

// Вычисляем комиссию и сумму к получению
const fee = computed(() => {
    if (!form.amount || !selectedCurrency.value) return 0;
    const feePercent = selectedCurrency.value.currency.withdraw_fee || 0.5;
    return ((parseFloat(form.amount) * feePercent) / 100).toFixed(8);
});

const receiveAmount = computed(() => {
    if (!form.amount) return 0;
    return (parseFloat(form.amount) - parseFloat(fee.value)).toFixed(8);
});
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
            <h2 class="h1_25 pb15">Withdraw</h2>
            <p class="text_18 pb25">Send your cryptocurrency to any wallets</p>

            <form @submit.prevent="submitWithdraw">
                <!-- Выбор криптовалюты -->
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

                <!-- Адрес кошелька -->
                <div class="pb20" v-if="selectedCurrency">
                    <p class="text_16 _115 color-gray2 pb10">Wallet address</p>
                    <input
                        type="text"
                        v-model="form.address"
                        class="input"
                        :class="{ error: form.errors.address }"
                        placeholder="Enter wallet address"
                    />
                    <div
                        v-if="form.errors.address"
                        class="error-message text-red"
                    >
                        {{ form.errors.address }}
                    </div>
                </div>

                <!-- Сумма -->
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

                <!-- Информация о комиссии -->
                <div
                    class="withdraw-info pb20"
                    v-if="selectedCurrency && form.amount"
                >
                    <div class="flex-between">
                        <div>
                            <p class="text_small_14 color-gray2">Fee</p>
                            <p class="text_16">
                                {{ fee }} {{ selectedCurrency.currency.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text_small_14 color-gray2">
                                You will receive
                            </p>
                            <p class="text_16">
                                {{ receiveAmount }}
                                {{ selectedCurrency.currency.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="
                        !selectedCurrency ||
                        !form.address ||
                        !form.amount ||
                        form.processing
                    "
                >
                    {{ form.processing ? 'Processing...' : 'Withdraw' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
