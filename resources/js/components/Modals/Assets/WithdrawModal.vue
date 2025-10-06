<script setup>

</script>

<template>
  <div class="modal" id="withdraw">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb15">Withdraw</h2>
    <p class="text_18 pb25">Send your cryptocurrency to any wallets</p>
    <p class="text_16 _115 color-gray2 pb10">Select cryptocurrency</p>
    <div class="itc-select assets pb20" id="select-3">
      <button
          type="button"
          class="itc-select__toggle"
          name="cryptocurrency"
          value=""
          data-select="toggle"
          data-index="1">
        Choose cryptocurrency
      </button>
      <div class="itc-select__dropdown">
        <div class="search"><input type="text" placeholder="Search"/></div>
        <ul class="itc-select__options">
          @yield("selectCoin")
        </ul>
      </div>
    </div>
    <button
        type="submit"
        class="btn btn_action btn_16 color-dark"
        onclick="nextWithdrawSelectCoin()"
    >
      Next
    </button>
  </div>
</template>

<style scoped>

</style>