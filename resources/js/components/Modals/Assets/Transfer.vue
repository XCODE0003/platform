<script setup>

</script>

<template>
  <div class="modal" id="transfer">
    <button class="closemodal clear" data-izimodal-close="">
      <img src="/images/modal_close.svg" alt=""/>
    </button>
    <h2 class="h1_25 pb25">Transfer cryptocurrency</h2>
    <div id="tabs">
      <ul class="tabs-nav flex gap6 pb20">
        <li>
          <a href="#tab-1" class="text_small_14 assets-menu_btn">From balance to spot</a>
        </li>
        <li>
          <a href="#tab-2" class="text_small_14 assets-menu_btn"
          >From spot to balance</a
          >
        </li>
        <li>
          <a href="#tab-3" class="text_small_14 assets-menu_btn">To another user</a>
        </li>
      </ul>
      <div class="tabs-items">

        <div class="tabs-item" id="tab-1">
          <form id="transferBalanceToSpot">
            <div class="transfer-container fail pb20">

              <div class="transfer-input_label">
                <div class="itc-select" id="select-6">
                  <button
                      type="button"
                      class="itc-select__toggle transfer"
                      name="cryptocurrency"
                      value=""
                      data-select="toggle"
                      data-index="-1"
                  >
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
                    class="clear convert-input text_18 "
                    oninput="validateInput(this)"
                    id="TransferToSpotInput"
                    placeholder="0.00001"
                    name="amount"
                />
                <button type="button" class="btn_max" onclick="selectMaxTransferToSpot()">Max</button>
              </div>

            </div>
            <div class="transfer-info flex-between pb20">
                <span class="text_small_14 color-gray2"
                >You receive on spot balance</span
                >
              <span class="text_small_14" id="balanceCoinTransferToSpot"></span>
            </div>
            <button
                type="submit"
                class="btn btn_action btn_16 color-dark trigger-transfer"
            >
              Transfer
            </button>
          </form>
        </div>

        <div class="tabs-item" id="tab-2">
          <form id="transferSpotToBalance">


            <div class="transfer-container fail pb20">
              <div class="transfer-input_label">
                <div class="itc-select" id="select-7">
                  <button
                      type="button"
                      class="itc-select__toggle transfer"
                      name="cryptocurrency"

                      value=""
                      data-select="toggle"
                      data-index="-1"
                  >
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
                    class="clear convert-input text_18 "
                    name="amount"
                    id="inputSpotToBalance"
                    oninput="validateInput(this)"
                    placeholder="0.00001"
                />
                <button type="button" onclick="selectMaxTransferBalanceToSpot()" class="btn_max">Max
                </button>
              </div>

            </div>
            <div class="transfer-info flex-between pb20">
              <span class="text_small_14 color-gray2">You receive on available balance</span>
              <span class="text_small_14" id="balanceSpot"></span>
            </div>
            <button
                type="submit"
                class="btn btn_action btn_16 color-dark trigger-transfer">
              Transfer
            </button>
          </form>
        </div>

        <div class="tabs-item" id="tab-3">
          <form id="transferToAnotherUser">
            <div class="transfer-container ">
              <div class="transfer-input_label ">
                <div class="itc-select" id="select-8">
                  <button
                      type="button"
                      class="itc-select__toggle transfer"
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
                    type="text"
                    oninput="validateInput(this)"
                    class="clear convert-input text_18 "
                    name="amount"
                    id="TransferAmountToUser"
                    placeholder="0.00001"
                />
                <button type="button" onclick="selectMaxTransferToUser()" class="btn_max">Max</button>
              </div>

            </div>
            <div class="pb10 pt10">
              <label class="form-item ">
                <input
                    required=""
                    class="input"
                    type="email"
                    name="email"
                    placeholder="Email@email.com"/>

              </label>
            </div>
            <div class="transfer-info flex-between pb20">
              <span class="text_small_14 color-gray2">You will send to another user</span>
              <span class="text_small_14" id="balanceToAnotherUser"></span>
            </div>

            <button
                type="submit"
                class="btn btn_action btn_16 color-dark trigger-transfer">
              Transfer
            </button>
          </form>
        </div>
      </div>
    </div>


  </div>
</template>

<style scoped>

</style>