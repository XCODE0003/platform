<script setup>
import {onMounted, ref} from "vue";
import {user} from '/src/main.js';
import commonOptions from "@/app/modal/options.js";

const ChangePasswordModel = ref({
  old_password: '',
  password: '',
  password_confirmation: '',
})
const errors = ref({
  old_password: '',
  password: '',
  password_confirmation: '',
})

  async function changePassword() {
    const changePasswordRequest = await user.changePassword(ChangePasswordModel.value);
    if (changePasswordRequest.status === 422) {
      const errorsRequest = changePasswordRequest.data.errors;
      for (const key in errorsRequest) {

        errors.value[key] = errorsRequest[key][0];

        iziToast.show({
          ...commonOptions,
          message: errorsRequest[key][0],
          iconUrl: "/images/fail.svg",
        });
      }

    }
    if (changePasswordRequest.status === 200) {
      errors.value.old_password = '';
      errors.value.password = '';
      errors.value.password_confirmation = '';

      iziToast.show({
        ...commonOptions,
        message: 'Password changed',
        iconUrl: '/images/success.svg',
      });
    }
  }
</script>

<template>
  <div class="modal" id="changePassword">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb15">Change password</h2>

    <input
        type="password"
        class="input mb10"
        v-model="ChangePasswordModel.old_password"
        :class="{'input-wrong': errors.old_password}"
        name="old_password"
        placeholder="Current password"
        required
    />
    <input
        type="password"
        name="password"
        class="input mb10"
        v-model="ChangePasswordModel.password"
        :class="{'input-wrong': errors.password}"
        placeholder="New password"
        required
        title="8 - 30 characters"
    />
    <input
        type="password"
        name="password_confirmation"
        v-model="ChangePasswordModel.password_confirmation"
        :class="{'input-wrong': errors.password_confirmation}"
        class="input mb25"
        placeholder="Confirm new password"
        required
    />
    <button
        type="submit"
        @click="changePassword"
        :disabled="!ChangePasswordModel.old_password || !ChangePasswordModel.password || !ChangePasswordModel.password_confirmation"
        class="btn btn_action btn_16 color-dark trigger-changepassword"
    >
      Confirm
    </button>
  </div>
</template>

<style scoped>

</style>