<script setup>

</script>

<template>
  <div class="modal" id="promocode">
    <form id="promocode_form">
      <button type="button" class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <h2 class="h1_25 pb15">Activate promocode</h2>
      <p class="text_18 pb25">
        Activate promocode to credit your account balance
      </p>
      <label class="pb20 flex-column gap6">
        <input
            type="text"
            class="input promocode-input "
            name="promocode"
            placeholder="Enter promocode"

        />
        Ò
      </label>
      <!-- disabled class="process" -->
      <button
          id="button_submit"
          type="submit"
          class="btn btn_action btn_16 color-dark ">
        Activate
      </button>
    </form>
  </div>
</template>

<style scoped>

</style>