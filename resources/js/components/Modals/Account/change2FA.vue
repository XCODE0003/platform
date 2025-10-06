<script setup>
import {onMounted, ref} from "vue";
import Modal from "@/app/modal/index.js";
import user from "@/app/api/User/user.js";
import commonOptions from "@/app/modal/options.js";
import router from "@/router/router.js";

const is_2fa = ref(user.is_2fa);
const ga2fa = ref({
  qr: '/images/qrsample.svg',
  code: ''
})

onMounted(async () => {
  const qrUser = await user.getQrCode();
  ga2fa.value.qr = qrUser.data.url;
});


async function enable2fa() {
  const googleRequest = await user.enable2fa(ga2fa.value.code);
  if (googleRequest.status === 422) {
    const errors = googleRequest.data.errors;
    for (const key in errors) {
      iziToast.show({
        ...commonOptions,
        message: errors[key][0],
        iconUrl: "/images/fail.svg",
      });
    }
  }
  if (googleRequest.status === 200) {
    is_2fa.value = true;
    UpdateInfo2fa(is_2fa.value);
    iziToast.show({
      ...commonOptions,
      message: googleRequest.data.message,
      iconUrl: "/images/success.svg",
    });

  }
}
const emits = defineEmits(['update:UpdateInfo2fa']);

function UpdateInfo2fa(value) {
  emits('update:UpdateInfo2fa', value);
}
async function disable2fa() {
  const googleRequest = await user.disable2fa(ga2fa.value.code);
  if (googleRequest.status === 422) {
    const errors = googleRequest.data.errors;
    for (const key in errors) {
      iziToast.show({
        ...commonOptions,
        message: errors[key][0],
        iconUrl: "/images/fail.svg",
      });
    }
  }
  if (googleRequest.status === 200) {
    is_2fa.value = false;
    UpdateInfo2fa(is_2fa.value);
    iziToast.show({
      ...commonOptions,
      message: googleRequest.data.message,
      iconUrl: "/images/success.svg",
    });

  }
}


</script>

<template>
  <div class="modal" id="change2fa">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <div v-if="!is_2fa" class="first">
      <div class="flex gap">
        <div class="text " style="max-width: 300px">
          <h2 class="h1_25 pb15">
            Two-Factor Authentication is <b class="color-red">disabled</b>
          </h2>
          <p class="text_16 _115 color-gray2 pb25">
            Scan the SQ code with the Google Authenticator app and enter the
            6-digit 2FA code to enable 2FA verification
          </p>
        </div>
        <div class="qr">
          <img width="100px" :src="ga2fa.qr" alt=""/>
        </div>
      </div>
      <form id="ga_form" action="#">
        <input
            type="text"
            class="input mb20"
            v-model="ga2fa.code"
            placeholder="Enter 6-digit code"
        />
        <button
            type="button"
            :disabled="ga2fa.code === ''"
            @click="enable2fa"
            class="btn btn_action btn_16 color-dark trigger-enable2fa">
          Confirm
        </button>
      </form>
    </div>
    <div v-else class="second">
      <h2 class="h1_25 pb15">
        Two-Factor Authentication is <b class="color-green">enabled</b>
      </h2>
      <p class="text_16 _115 color-gray2 pb25">
        Attention! Your account is now protected. If you disable two-factor
        authentication, your account will be vulnerable. We do not recommend
        doing this. But if you decide, then enter here the 6-digit code from
        your Google Authenticator app
      </p>
      <form action="#">
        <input
            type="text"
            class="input mb20"
            name="code"
            v-model="ga2fa.code"
            placeholder="Enter 6-digit code"
        />
        <button
            @click="disable2fa"
            :disabled="ga2fa.code === ''"
            type="button"
            class="btn btn_action bg_danger  btn_16 color-dark trigger-disable2fa"
        >
          Disable
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>

</style>