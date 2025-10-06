<script setup>

</script>

<template>
  <div class="modal" id="stacking">
    <form id="stackingForm">
      <button class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>

      <p class="text_16 _115 color-gray2 pb10">Input cryptocurrency</p>
      <div class="stacking-container  pb20">
        <div class="stacking-input_label">
          <div class="itc-select" id="select-9">
            <button
                type="button"
                class="itc-select__toggle stacking"
                name="cryptocurrency"
                value=""
                data-select="toggle"
                data-index="-1">
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
              oninput="validateInput(this); calculateStaking(this)"
              type="text"
              id="amountStaking"
              name="amount"
              class="clear text_18 stacking-input"
              placeholder="0.00001"
          />
          <button oninput="selectMaxStakingOrder()" class="btn_max">Max</button>
        </div>

      </div>
      <p class="text_16 _115 color-gray2 pb10">
        Select stake days (from 7 to 365)
      </p>
      <div class="stacking-days pb25">
        <ul class="flex gap6">
          <li>
            <input id="day7"  value="7"  checked type="radio"
                   class="hide"/>
            <label for="day7"  class="stacking-day">7</label>
          </li>
          <li>
            <input id="day14"  value="14"  type="radio"
                   class="hide"/>
            <label for="day14"  class="stacking-day">14</label>
          </li>
          <li>
            <input id="day30"  value="30"  type="radio"
                   class="hide"/>
            <label for="day30"  class="stacking-day">30</label>
          </li>
          <li>
            <input id="day60"  value="60"  type="radio"
                   class="hide"/>
            <label for="day60"  class="stacking-day">60</label>
          </li>
          <li>
            <input id="day90"  value="90"  type="radio"
                   class="hide"/>
            <label for="day90"  class="stacking-day">90</label>
          </li>
          <li>
            <input id="day120"  value="120"  type="radio"
                   class="hide"/>
            <label for="day120"  class="stacking-day">120</label>
          </li>
          <li>
            <input id="day365"  value="365"  type="radio"
                   class="hide"/>
            <label for="day365"  class="stacking-day">365</label>
          </li>

        </ul>
      </div>
      <div class="stacking-info flex-between pb20">
        <span class="text_small_14 color-gray2">You will receive</span>
        <span class="text_small_14" id="receiveStacking"></span>
      </div>

      <!-- disabled class="process" -->
      <button
          type="submit"
          class="btn btn_action btn_16 color-dark trigger-stacking">
        Go stake
      </button>
    </form>
  </div>
</template>

<style scoped>

</style>