<script setup>
import {onMounted, ref} from "vue";
import Tab from "@/app/tab/index.js";
import DepositCryptoTab from "@/components/Tabs/Select/DepositCryptoTab.vue";
import DepositFiatTab from "@/components/Tabs/Select/DepositFiatTab.vue";


const tab = new Tab('DepositCrypto', ['DepositCrypto', 'DepositFiat']);
let selectedTab = ref(tab.getSelectedTab());



tab.onTabChange = (newTab) => {
  selectedTab.value = newTab;
};
</script>

<template>
  <div class="modal" id="deposit">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb15">Deposit</h2>
    <p class="text_18 pb25">
      Deposit your existing crypto assets via the blockchain
    </p>
    <p class="text_16 _115 color-gray2 pb10">
      What cryptocurrency do you want to deposit?
    </p>
    <div id="tabsDeposit">
      <ul class="tabs-navDeposit flex gap6 pb20">
        <li>
          <button :class="{'active': selectedTab === 'DepositCrypto'}" @click="tab.changeTab('DepositCrypto')" class="text_small_14 assets-menu_btn">Crypto</button>
        </li>
        <li>
          <button :class="{'active': selectedTab === 'DepositFiat'}" @click="tab.changeTab('DepositFiat')" class="text_small_14 assets-menu_btn">Card</button>
        </li>

      </ul>
      <div class="tabs-itemsDeposit">

          <DepositCryptoTab v-if="selectedTab === 'DepositCrypto'"/>
          <DepositFiatTab v-if="selectedTab === 'DepositFiat'" />

      </div>
    </div>


  </div>
</template>

<style scoped>

</style>