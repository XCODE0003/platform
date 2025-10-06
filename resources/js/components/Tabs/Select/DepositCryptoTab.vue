<script setup>
import {onMounted, ref} from "vue";
import Select from "@/app/select/index.js";

const selectedValue = ref(null);
onMounted(() => {
  const select = new Select("#selectDepositCrypto");
  select._el.addEventListener('itc.select.change', (e) => {
    selectedValue.value = select.value
  });
});
</script>

<template>
  <div class="tabs-itemDeposit" id="DepositCrypto">
    <div class="itc-select assets pb20" id="selectDepositCrypto">
      <button
          type="button"
          class="itc-select__toggle"
          name="cryptocurrency"
          value=""
          data-select="toggle"
          data-index="-1"
      >
        Choose cryptocurrency
      </button>
      <div class="itc-select__dropdown">
        <div class="search"><input type="text" placeholder="Search"/></div>
        <ul class="itc-select__options">
          <li
              class="itc-select__option"
              data-select="option"
              data-value="BTC">
            <img
                width="24"
                src="/images/coin_icons/btc.svg"
                alt=""
                class="select-img"
            />
            BTC
          </li>
        </ul>
      </div>
    </div>
    <button
        type="submit"
        id="btn_deposit_next"
        class="btn btn_action btn_16 color-dark"
    >
      Next
    </button>
  </div>

</template>

<style scoped>

</style>