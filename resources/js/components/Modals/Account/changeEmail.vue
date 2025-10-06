<script setup>
import {onMounted, ref} from "vue";
import Modal from "@/app/modal/index.js";
import FunctionsModal from "@/app/modal/functions.js";
import {user} from "@/main.js";
import {emailValidator} from "@/app/Validator/index.js";
import commonOptions from "@/app/modal/options.js";

const step = ref('changeMail');
const loading = ref(false);
onMounted(() => {
  let isEmailChanging = localStorage.getItem('EmailChanging');
  if(isEmailChanging){
    isEmailChanging = JSON.parse(isEmailChanging);
    if(isEmailChanging.changing === true && isEmailChanging.date > Date.now() - 60000){
      step.value = 'confirmEmail';
      changeEmailModel.value.email = isEmailChanging.email;
    }
  }
});


const emits = defineEmits(['update:updateEmail']);

function updateEmail(value) {
  emits('update:updateEmail', value);
}
const changeEmailModel = ref({
  email: '',
  code: ''
});

function changeStep(newStep) {
  step.value = newStep;
}

async function changeEmail() {
  loading.value = true;
  const response = await user.changeEmail(changeEmailModel.value);
  if(response.status !== 0){
    loading.value = false;
  }
  if (response.status === 422) {
    const errors = response.data.errors;
    for (const key in errors) {
      iziToast.show({
        ...commonOptions,
        message: errors[key][0],
        iconUrl: "/images/fail.svg",
      });
    }
  }
  if(response.status === 401){
    iziToast.show({
      ...commonOptions,
      message: response.data.message,
      iconUrl: "/images/fail.svg",
    });
  }
  if(response.status === 201){
    changeStep('confirmEmail');
    localStorage.setItem('EmailChanging', JSON.stringify({changing: true, date: Date.now(), email: changeEmailModel.value.email}));
    iziToast.show({
      ...commonOptions,
      message: response.data.message,
      iconUrl: "/images/success.svg",
    });
  }
  if (response.status === 200) {
    updateEmail(changeEmailModel.value.email);
    localStorage.removeItem('EmailChanging');
    iziToast.show({
      ...commonOptions,
      message: response.data.message,
      iconUrl: "/images/success.svg",
    });

  }
}
</script>

<template>
  <div class="modal" id="changeMail">
    <div v-if="step === 'changeMail'">
      <button class="closemodal clear ">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <h2 class="h1_25 pb15">Change e-mail address</h2>
      <p class="text_16 _115 color-gray2 pb25">
        Enter a new address and we will send a confirmation code for changing
        the e-mail address for your account
      </p>

      <input
          type="email"
          class="input mb25"
          v-model="changeEmailModel.email"
          placeholder="address@email.com"
      />
      <button :disabled="!emailValidator(changeEmailModel.email) || loading === true" type="button" @click="changeEmail()"
              class="btn  btn_action btn_16 color-dark">
        {{ loading ? 'Loading...' : 'Next'}}
      </button>
    </div>
    <div v-if="step === 'confirmEmail'">

      <button class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <h2 class="h1_25 pb15">Check your e-mail</h2>
      <p class="text_16 _115 color-gray2 pb25">
        We have sent the email change confirmation code to your email address
        <span>{{ user.email }}</span>
      </p>
      <input
          type="text"
          class="input mb25"
          v-model="changeEmailModel.code"
          placeholder="2sF8GH"
      />
      <button
          type="button"
          @click="changeEmail()"
          :disabled="changeEmailModel.code.length < 6 || changeEmailModel.code.length > 6 "
          class="btn btn_action btn_16 color-dark ">
        Confirm
      </button>
    </div>
  </div>
</template>

<style scoped>

</style>