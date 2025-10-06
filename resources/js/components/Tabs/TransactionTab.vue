<script setup>
import {onMounted} from "vue";
import Modal from "@/app/modal/index.js";
import ModalButtons from "@/components/Tabs/Elements/ModalButtons.vue";

onMounted(() => {
  const modals = [new Modal("stacking"), new Modal("transfer"), new Modal("convert"), new Modal("promocode"), new Modal("deposit")];
  modals.forEach(modal => modal.init());

});
</script>

<template>
  <div class="tab-item">
    <div class="assets-title-block">
      <div class="assets-title-block_start">
        <h1 class="h1_25">Transaction history</h1>
        <button class="btn clear">
          <img
              src="/images/transaction-history.svg"
              alt=""
          />
        </button>
      </div>
      <ModalButtons/>
    </div>
    <div class="assets-search pt40 pb40">
      <label class="assets-search_input">
        <input
            type="text"
            class="clear text_small_14"
            placeholder="Search"
        />
      </label>
    </div>
    <div class="assets-overview-grid assets-history-grid pb60">
      <div class="grid-head text_small_12 color-dark">
        <div>Coin</div>
        <div>Quantity</div>
        <div>Type</div>
        <div>Status</div>
        <div>Date, time</div>
      </div>

      <div class="grid-line">
        <div class="flex-center gap6">
          <img
              width="30px"
              src="/images/coin_icons/btc.svg"
              alt=""/>
          <span></span>
        </div>
        <div class="">
          <span class="text_16"></span>

        </div>
        <div class="">
          <span class="text_16"></span>
        </div>
        <div class="">
          <span class="text_16"></span>
        </div>
        <div class="">
                                            <span
                                                class="text_16">123</span>
        </div>
      </div>


      <!--                  @if(count($transactions) == 0)-->
      <!--                  <p class="notfound ">-->
      <!--                    Nothing found-->
      <!--                    <img src="/images/notfound.svg" alt=""/>-->
      <!--                  </p>-->
      <!--                  @endif-->
    </div>
  </div>
</template>

<style scoped>

</style>