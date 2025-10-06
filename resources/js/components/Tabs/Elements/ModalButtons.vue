<script setup>
import {onMounted} from "vue";
import Modal from "@/app/modal/index.js";

onMounted(() => {
  const modals = [new Modal("stacking"), new Modal("promocode"), new Modal("deposit")];
  modals.forEach(modal => modal.init());

});
</script>

<template>
  <div class="assets-title-block_end flex-center gap10">
    <button
        class="btn small_btn btn_16 deposit"
        data-izimodal-open="#deposit"
    >
      Deposit
    </button>
    <button
        class="btn small_btn btn_16"
        data-izimodal-open="#promocode"
    >
      Promocode
    </button>


    <button
        class="btn small_btn btn_16"
        data-izimodal-open="#withdraw"
    >
      Withdraw
    </button>
  </div>
</template>

<style scoped>

</style>