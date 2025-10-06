<script setup>
import {onMounted, ref, defineProps, watchEffect} from "vue";
import Modal from "@/app/modal/index.js";
import {user} from '/src/main.js';
import commonOptions from "@/app/modal/options.js";


const kycModel = ref({
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
  kyc_status: '',
  error_message: '',

});

const errors = ref({
  sex: '',
  first_name: '',
  last_name: '',
  phone: '',
  dateOfBrith: '',
  country: '',
  city: '',
  address: '',
  zip_code: '',
});


const emits = defineEmits(['update:modelValue']);

function updateModel(newData) {
  emits('update:modelValue', newData);
}

const props = defineProps({
  kycData: Object
});


watchEffect(() => {
  if (props.kycData) {
    kycModel.value = {
      ...kycModel.value,
      ...props.kycData
    };
  }
});


async function kyc() {
  const kycRequest = await user.kyc(kycModel.value);
  if (kycRequest.status === 422) {
    const errorsRequest = kycRequest.data.errors;
    for (const key in errorsRequest) {

      errors.value[key] = errorsRequest[key][0];

      iziToast.show({
        ...commonOptions,
        message: errorsRequest[key][0],
        iconUrl: "/images/fail.svg",
      });
    }

  }
  if (kycRequest.status === 200) {
    (new Modal).closeModelName('verify')
    kycModel.value.kyc_status = 1;
    updateModel(kycModel.value);
    iziToast.show({
      ...commonOptions,
      message: 'Application has been successfully submitted, pending review',
      iconUrl: '/images/success.svg',
    });
  }
}

</script>

<template>
  <div class="modal" id="verify">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb15">Verify your identity </h2>
    <p class="text_16 _115 color-gray2 pb25">
      To ensure account security, provide the required personal information
      to complete verification
    </p>

    <div style="max-height: 300px; overflow: auto" class="flex flex-column">
      <select v-model="kycModel.sex" class="input mb10" name="sex" id="sex">
        <option value="male">I am male</option>
        <option value="female">I am female</option>
      </select>
      <input
          type="text"
          v-model="kycModel.first_name"
          :class="{ 'input-wrong': errors.first_name }"
          name="first_name"
          class="input mb10"
          placeholder="First name"
          required
      />
      <input
          type="text"
          v-model="kycModel.last_name"
          :class="{ 'input-wrong': errors.last_name }"
          name="last_name"
          class="input mb10"
          placeholder="Last name"
          required
      />
      <input
          type="text"
          v-model="kycModel.phone"
          :class="{ 'input-wrong': errors.phone }"
          name="phone"
          class="input mb10"
          placeholder="Phone number"
          required
      />
      <input
          type="text"
          v-model="kycModel.dateOfBrith"
          :class="{ 'input-wrong': errors.dateOfBrith }"
          name="dateOfBrith"
          class="input mb10"
          placeholder="DD.MM.YYYY"
          required
      />
      <input
          type="text"
          v-model="kycModel.country"
          :class="{ 'input-wrong': errors.country }"
          class="input mb10"
          name="country"
          placeholder="Country"
          required
      />
      <input v-model="kycModel.city"
             :class="{ 'input-wrong': errors.city }"
             type="text" name="city" class="input mb10" placeholder="City" required/>
      <input
          type="text"
          v-model="kycModel.address"
          :class="{ 'input-wrong': errors.address }"
          name="address"
          class="input mb10"
          placeholder="Street address, house"
          required
      />
      <input
          type="text"
          class="input mb25"
          :class="{ 'input-wrong': errors.zip_code }"
          placeholder="ZIP code"
          v-model="kycModel.zip_code"
          required
          name="zip_code"
      />
    </div>
    <button
        @click="kyc"
        :class="{'d-none': kycModel.kyc_status === 1 || kycModel.kyc_status === 3}"
        :disabled="!kycModel.sex || !kycModel.first_name || !kycModel.last_name || !kycModel.phone || !kycModel.dateOfBrith || !kycModel.country || !kycModel.city || !kycModel.address || !kycModel.zip_code"
        class="btn btn_action btn_16 color-dark ">
      {{ kycModel.kyc_status === 2 ? 'Resend application' : 'Send kyc application' }}
    </button>
  </div>
</template>

<style scoped>

</style>