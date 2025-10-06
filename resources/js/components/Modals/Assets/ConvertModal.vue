<script setup>

</script>

<template>
  <div class="modal" id="convert">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb15">Convert cryptocurrency</h2>

    <form id="SwapCryptoCurrency">
      <div class="convert-container ">
        <div class="convert-input_label">
          <div class="itc-select" id="select-4">
            <button
                type="button"
                class="itc-select__toggle convert"
                name="cryptocurrency"
                value=""
                data-select="toggle"
                data-index="2">
              Choose
            </button>
            <div class="itc-select__dropdown">
              <div class="search">
                <input type="text" placeholder="Search"/>
              </div>
              <ul class="itc-select__options">
                @yield("selectCoin")
              </ul>
            </div>
          </div>
          <input
              type="text"
              name="AmountFrom"
              id="AmountFromConvert"
              oninput="validateInput(this); updatePriceConvert()"
              class="clear text_18 convert-input"
              placeholder="0.00001"/>
          <button type="button" onclick="selectMaxConvert()" class="btn_max">Max</button>
        </div>
        <span class="convert-container_sub">Enter a valid value</span>
      </div>
      <div class="convert-container pb20">
        <div class="convert-input_label">
          <div class="itc-select" data-dz-name="" id="select-5">
            <button
                type="button"
                class="itc-select__toggle convert"
                name="cryptocurrency"
                value=""

                data-select="toggle"
                data-index="-1"
            >
              Choose
            </button>
            <div class="itc-select__dropdown">
              <div class="search">
                <input type="text" oninput="validateInput(this)" placeholder="Search"/>
              </div>
              <ul class="itc-select__options">
                @yield("selectCoin")
              </ul>
            </div>
          </div>
          <input
              type="text"
              name="amountTo"
              readonly
              id="amountToConvert"
              class="clear text_18 convert-input"
              placeholder="0.00001"
          />
          <span class="loader d-none" style="margin-left: 15px" id="loader_swap"></span>
          <button type="button" onclick="swapCoinFields()" class="clear convert-button">
            <img src="/images/convert-button.svg" alt=""/>
          </button>
        </div>
      </div>
      <div class="convert-info flex-between pb20">
        <span class="text_small_14 color-gray2">You receive</span>
        <span class="text_small_14" id="balance_coin"></span>
      </div>
      <!-- disabled class="process" -->
      <button
          type="submit"
          class="btn btn_action btn_16 color-dark "
      >
        Convert
      </button>
    </form>
  </div>
</template>

<style scoped>

</style>