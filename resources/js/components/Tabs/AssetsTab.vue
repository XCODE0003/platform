<script setup>
import {onMounted} from "vue";
import Modal from "@/app/modal/index.js";
import ModalButtons from "@/components/Tabs/Elements/ModalButtons.vue";




</script>

<template>
  <div class="tab-item">
    <div class="assets-title-block">
      <div class="assets-title-block_start">
        <h1 class="h1_25">Assets overview</h1>

      </div>
      <ModalButtons/>
    </div>
    <div class="assets-balances flex-center gap10 pt15">
      <div class="block text_17">
        <img
            src="/images/balance_icon-available.svg"
            alt=""
        />
        <p>Available balance:</p>
        <span> USD</span>
        <span class="color-gray2">≈  <span id="">BTC</span></span>
      </div>
      <div class="block text_17">
        <img src="/images/balance_icon-spot.svg" alt=""/>
        <p>Spot balance:</p>
        <span> USD</span>
        <span class="color-gray2">≈  BTC</span>
      </div>
    </div>
    <div class="assets-search pt40">
      <label class="assets-search_input">
        <input
            type="text"
            class="clear text_small_14"
            placeholder="Search"
        />
      </label>
    </div>
    <div class="assets-overview pt10 pb20">
      <div class="hide-container">
        <div class="form-check">
          <input type="checkbox" onchange="toggleZeroBalance(this)" id="hidezero" class="checkbox"/>
          <label for="hidezero" class="text_small_12 color-gray2"
          >Hide zero balances</label
          >
        </div>
      </div>
    </div>
    <div class="assets-overview-grid pb60">
      <div class="grid-head text_small_12 color-dark">
        <div>Coin</div>
        <div>Available balance</div>
        <div>Spot balance</div>
        <div>On orders</div>
        <div>Total balance</div>
      </div>
      <div class="grid-line" data-balance_coin = "">
        <div class="flex-center gap6">
          <img
              width="30px"
              src="/images/coin_icons/btc.svg"
              alt=""
          />
          <span></span>
        </div>
        <div class="flex-column gap10">
          <span class="text_16"></span>
          <span class="text_small_12 color-gray2">
                                          (≈  USD)
                                        </span>
        </div>
        <div class="flex-column gap10">
          <span class="text_16"></span>
          <span class="text_small_12 color-gray2">
                                          (≈  USD)
                                        </span>
        </div>
        <div class="flex-column gap10">
          <span class="text_16">0</span>
          <span class="text_small_12 color-gray2">
                                          (≈ 0 USD)
                                        </span>
        </div>
        <div class="flex-column gap10">
          <span class="text_16"></span>
          <span class="text_small_12 color-gray2">
                                          (≈  USD)
                                        </span>
        </div>
      </div>
      <p class="notfound " id="assetsZero" style="display: none">
        Nothing found
        <img src="/images/modal_close.svg" alt=""/>
      </p>
    </div>
  </div>
</template>

<style scoped>

</style>