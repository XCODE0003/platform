<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useForm } from '@inertiajs/vue3';
import { computed, defineEmits, defineProps, watchEffect } from 'vue';
import { VueFinalModal } from 'vue-final-modal';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast.js';
const { showError } = useToast();
import { watchErrors, fieldNamesPresets, disabledButton } from '@/utils/system.js';
const page = usePage();
const modal = useModalStore();
const errors = computed(() => page.props.errors);
watchErrors(errors, showError, fieldNamesPresets.kyc);
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
    date_of_birth: '',
    country: '',
    city: '',
    address: '',
    zip_code: '',
    document_front: null,
    document_back: null,
    document_selfie: null,
    status: props.kycData.status ?? 0,
    error_message: '',
});

watchEffect(() => {
    if (props.kycData) {
        form.sex = props.kycData.sex ?? form.sex;
        form.first_name = props.kycData.first_name ?? form.first_name;
        form.last_name = props.kycData.last_name ?? form.last_name;
        form.phone = props.kycData.phone ?? form.phone;
        form.date_of_birth = props.kycData.date_of_birth ?? form.date_of_birth;
        form.country = props.kycData.country ?? form.country;
        form.city = props.kycData.city ?? form.city;
        form.address = props.kycData.address ?? form.address;
        form.zip_code = props.kycData.zip_code ?? form.zip_code;
        form.status = props.kycData.status ?? form.status;
        form.error_message = props.kycData.error_message ?? form.error_message;
    }
});

function submitKyc() {
    form.post('/kyc', {
        preserveScroll: true,
        onSuccess: () => {
            form.status = 'pending';
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
                style="max-height: 500px; overflow: auto"
                class="flex-column flex hide-scroll"
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
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    v-model="form.first_name"
                    :class="{ 'input-wrong': errors.first_name }"
                    name="first_name"
                    class="input mb10"
                    placeholder="First name"
                    required
                />
                <div
                    v-if="errors.first_name"
                    class="error-message text-red mb10"
                >
                    {{ errors.first_name }}
                </div>
                <input
                    type="text"
                    v-model="form.last_name"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    :class="{ 'input-wrong': errors.last_name }"
                    name="last_name"
                    class="input mb10"
                    placeholder="Last name"
                    required
                />
                <div
                    v-if="errors.last_name"
                    class="error-message text-red mb10"
                >
                    {{ errors.last_name }}
                </div>
                <input
                    type="text"
                    v-model="form.phone"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                        :class="{ 'input-wrong': errors.phone }"
                    name="phone"
                    class="input mb10"
                    placeholder="Phone number"
                    required
                />
                <div
                    v-if="errors.phone"
                    class="error-message text-red mb10"
                >
                    {{ errors.phone }}
                </div>
                <input
                    type="text"
                    v-model="form.date_of_birth"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    :class="{ 'input-wrong': errors.date_of_birth }"
                    name="dateOfBrith"
                    class="input mb10"
                    placeholder="DD.MM.YYYY"
                    required
                />
                <div
                    v-if="errors.date_of_birth"
                    class="error-message text-red mb10"
                >
                    {{ errors.date_of_birth }}
                </div>
                <input
                    type="text"
                    v-model="form.country"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    :class="{ 'input-wrong': errors.country }"
                    class="input mb10"
                    name="country"
                    placeholder="Country"
                    required
                />
                <div
                    v-if="errors.country"
                    class="error-message text-red mb10"
                >
                    {{ errors.country }}
                </div>
                <input
                    v-model="form.city"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    :class="{ 'input-wrong': errors.city }"
                    type="text"
                    name="city"
                    class="input mb10"
                    placeholder="City"
                    required
                />
                <div
                        v-if="errors.city"
                    class="error-message text-red mb10"
                >
                    {{ errors.city }}
                </div>
                <input
                    type="text"
                    v-model="form.address"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                    :class="{ 'input-wrong': errors.address }"
                    name="address"
                    class="input mb10"
                    placeholder="Street address, house"
                    required
                />
                <div
                    v-if="errors.address"
                    class="error-message text-red mb10"
                >
                    {{ errors.address }}
                </div>
                <input
                    type="text"
                    class="input mb25"
                    :readonly="kycData.status === 'approved' || kycData.status === 'pending'"
                        :class="{ 'input-wrong': errors.zip_code }"
                    placeholder="ZIP code"
                    v-model="form.zip_code"
                    required
                    name="zip_code"
                />
                <div
                    v-if="errors.zip_code"
                    class="error-message text-red mb10"
                >
                    {{ errors.zip_code }}
                </div>

                <div class="mb10">
                    <label class="text_16 color-gray2 mb5" style="display:block">ID / Passport (front)</label>
                    <template v-if="kycData.status === 'approved' || kycData.status === 'pending'">
                        <span class="text_16">{{ kycData.documents?.front ? 'Uploaded' : 'Not uploaded' }}</span>
                    </template>
                    <template v-else>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,application/pdf"
                            class="input mb5"
                            @change="e => form.document_front = e.target.files[0]"
                        />
                        <span v-if="form.document_front" class="text_16 color-gray2">{{ form.document_front.name }}</span>
                        <span v-else-if="kycData.documents?.front" class="text_16 color-gray2">Uploaded</span>
                    </template>
                    <div v-if="errors.document_front" class="error-message text-red mb5">{{ errors.document_front }}</div>
                </div>

                <div class="mb10">
                    <label class="text_16 color-gray2 mb5" style="display:block">ID / Passport (back)</label>
                    <template v-if="kycData.status === 'approved' || kycData.status === 'pending'">
                        <span class="text_16">{{ kycData.documents?.back ? 'Uploaded' : 'Not uploaded' }}</span>
                    </template>
                    <template v-else>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,application/pdf"
                            class="input mb5"
                            @change="e => form.document_back = e.target.files[0]"
                        />
                        <span v-if="form.document_back" class="text_16 color-gray2">{{ form.document_back.name }}</span>
                        <span v-else-if="kycData.documents?.back" class="text_16 color-gray2">Uploaded</span>
                    </template>
                    <div v-if="errors.document_back" class="error-message text-red mb5">{{ errors.document_back }}</div>
                </div>

                <div class="mb25">
                    <label class="text_16 color-gray2 mb5" style="display:block">Selfie with document</label>
                    <template v-if="kycData.status === 'approved' || kycData.status === 'pending'">
                        <span class="text_16">{{ kycData.documents?.selfie ? 'Uploaded' : 'Not uploaded' }}</span>
                    </template>
                    <template v-else>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,application/pdf"
                            class="input mb5"
                            @change="e => form.document_selfie = e.target.files[0]"
                        />
                        <span v-if="form.document_selfie" class="text_16 color-gray2">{{ form.document_selfie.name }}</span>
                        <span v-else-if="kycData.documents?.selfie" class="text_16 color-gray2">Uploaded</span>
                    </template>
                    <div v-if="errors.document_selfie" class="error-message text-red mb5">{{ errors.document_selfie }}</div>
                </div>

                <button
                    type="submit"
                    v-if="kycData.status !== 'approved' && kycData.status !== 'pending'"

                    :disabled="
                        form.processing || disabledButton(form.data(), ['sex', 'first_name', 'last_name', 'phone', 'date_of_birth', 'country', 'city', 'address', 'zip_code'])"
                    class="btn btn_action btn_16 color-dark"
                >
                    {{
                        form.processing
                            ? 'Submitting...'
                            : form.status === 'rejected'
                              ? 'Resend application'
                              : 'Send kyc application'
                    }}
                </button>
            </form>


        </div>
    </VueFinalModal>
</template>

<style scoped></style>
