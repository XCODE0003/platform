<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm } from '@inertiajs/vue3';
import { computed, defineEmits, defineProps, watchEffect } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();

const isOpen = computed({
    get: () => modal.isOpen('verify'),
    set: (v) => (v ? modal.open('verify') : modal.close('verify')),
});

const emits = defineEmits(['update:modelValue']);
function updateModel(newData) {
    emits('update:modelValue', newData);
}

const props = defineProps({
    kycData: Object,
});

const form = useForm({
    sex: '',
    first_name: '',
    last_name: '',
    phone: '',
    dateOfBrith: '',
    country: '',
    city: '',
    address: '',
    zip_code: '',
    kyc_documents: [],
    kyc_status: 0,
    error_message: '',
});

watchEffect(() => {
    if (props.kycData) {
        form.sex = props.kycData.sex ?? form.sex;
        form.first_name = props.kycData.first_name ?? form.first_name;
        form.last_name = props.kycData.last_name ?? form.last_name;
        form.phone = props.kycData.phone ?? form.phone;
        form.dateOfBrith = props.kycData.dateOfBrith ?? form.dateOfBrith;
        form.country = props.kycData.country ?? form.country;
        form.city = props.kycData.city ?? form.city;
        form.address = props.kycData.address ?? form.address;
        form.zip_code = props.kycData.zip_code ?? form.zip_code;
        form.kyc_status = props.kycData.kyc_status ?? form.kyc_status;
        form.error_message = props.kycData.error_message ?? form.error_message;
    }
});

function submitKyc() {
    form.post('/kyc', {
        preserveScroll: true,
        onSuccess: () => {
            form.kyc_status = 1;
            updateModel({ ...form.data() });
            isOpen.value = false;
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
            <h2 class="h1_25 pb15">Verify your identity</h2>
            <p class="text_16 _115 color-gray2 pb25">
                To ensure account security, provide the required personal
                information to complete verification
            </p>

            <form
                @submit.prevent="submitKyc"
                style="max-height: 300px; overflow: auto"
                class="flex-column flex"
            >
                <select
                    v-model="form.sex"
                    class="input mb10"
                    name="sex"
                    id="sex"
                >
                    <option value="male">I am male</option>
                    <option value="female">I am female</option>
                </select>
                <input
                    type="text"
                    v-model="form.first_name"
                    :class="{ 'input-wrong': form.errors.first_name }"
                    name="first_name"
                    class="input mb10"
                    placeholder="First name"
                    required
                />
                <div
                    v-if="form.errors.first_name"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.first_name }}
                </div>
                <input
                    type="text"
                    v-model="form.last_name"
                    :class="{ 'input-wrong': form.errors.last_name }"
                    name="last_name"
                    class="input mb10"
                    placeholder="Last name"
                    required
                />
                <div
                    v-if="form.errors.last_name"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.last_name }}
                </div>
                <input
                    type="text"
                    v-model="form.phone"
                    :class="{ 'input-wrong': form.errors.phone }"
                    name="phone"
                    class="input mb10"
                    placeholder="Phone number"
                    required
                />
                <div
                    v-if="form.errors.phone"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.phone }}
                </div>
                <input
                    type="text"
                    v-model="form.dateOfBrith"
                    :class="{ 'input-wrong': form.errors.dateOfBrith }"
                    name="dateOfBrith"
                    class="input mb10"
                    placeholder="DD.MM.YYYY"
                    required
                />
                <div
                    v-if="form.errors.dateOfBrith"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.dateOfBrith }}
                </div>
                <input
                    type="text"
                    v-model="form.country"
                    :class="{ 'input-wrong': form.errors.country }"
                    class="input mb10"
                    name="country"
                    placeholder="Country"
                    required
                />
                <div
                    v-if="form.errors.country"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.country }}
                </div>
                <input
                    v-model="form.city"
                    :class="{ 'input-wrong': form.errors.city }"
                    type="text"
                    name="city"
                    class="input mb10"
                    placeholder="City"
                    required
                />
                <div
                    v-if="form.errors.city"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.city }}
                </div>
                <input
                    type="text"
                    v-model="form.address"
                    :class="{ 'input-wrong': form.errors.address }"
                    name="address"
                    class="input mb10"
                    placeholder="Street address, house"
                    required
                />
                <div
                    v-if="form.errors.address"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.address }}
                </div>
                <input
                    type="text"
                    class="input mb25"
                    :class="{ 'input-wrong': form.errors.zip_code }"
                    placeholder="ZIP code"
                    v-model="form.zip_code"
                    required
                    name="zip_code"
                />
                <div
                    v-if="form.errors.zip_code"
                    class="error-message text-red mb10"
                >
                    {{ form.errors.zip_code }}
                </div>
                <button
                    type="submit"
                    :class="{
                        'd-none':
                            form.kyc_status === 1 || form.kyc_status === 3,
                    }"
                    :disabled="
                        !form.sex ||
                        !form.first_name ||
                        !form.last_name ||
                        !form.phone ||
                        !form.dateOfBrith ||
                        !form.country ||
                        !form.city ||
                        !form.address ||
                        !form.zip_code ||
                        form.processing
                    "
                    class="btn btn_action btn_16 color-dark"
                >
                    {{
                        form.processing
                            ? 'Submitting...'
                            : form.kyc_status === 2
                              ? 'Resend application'
                              : 'Send kyc application'
                    }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
