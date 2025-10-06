<script setup>
import {onMounted, ref} from 'vue';
import AssetsTab from "@/components/Tabs/AssetsTab.vue";
import StackingTab from "@/components/Tabs/StackingTab.vue";
import TransactionTab from "@/components/Tabs/TransactionTab.vue";
import Deposit from "@/components/Modals/Assets/Deposit.vue";
import Promocode from "@/components/Modals/Assets/Promocode.vue";
import Stacking from "@/components/Modals/Assets/Stacking.vue";
import WithdrawModal from "@/components/Modals/Assets/WithdrawModal.vue";

const selectedTab = ref('AssetsTab');


function changeTab(tab) {
  selectedTab.value = tab;
}

</script>

<template>
  <main class="assets h100">
    <section class="assets">
      <div class="container">
        <div class="assets-content">
          <div class="tabs-wrapper">
            <div class="tabs assets-menu">
              <span @click="changeTab('AssetsTab')" :class="{'active': selectedTab === 'AssetsTab'}"
                    class="tab btn_16 assets-menu_btn ">Overview</span>
              <span @click="changeTab('StackingTab')" :class="{'active': selectedTab === 'StackingTab'}"
                    class="tab btn_16 assets-menu_btn">Staking</span>
              <span @click="changeTab('TransactionTab')" :class="{'active': selectedTab === 'TransactionTab'}"
                    class="tab btn_16 assets-menu_btn">Transaction history</span>
            </div>
            <div class="tabs-content">
                <AssetsTab v-if="selectedTab === 'AssetsTab'"/>
                <StackingTab v-if="selectedTab === 'StackingTab'"/>
                <TransactionTab v-if="selectedTab === 'TransactionTab'"/>



            </div>
          </div>
        </div>
      </div>
    </section>
    <Deposit/>
    <Promocode/>
    <Stacking/>
    <WithdrawModal/>

    <div class="modal" style="overflow: hidden;" id="deposit2">
      <button class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <h2 class="h1_25 pb15">Deposit <span class="coinName"></span></h2>
      <p class="text_18 pb25 color-red">
        Confirm that your network is <span class="coinName"></span>. Sending any other asset to this
        address may result in loss of your deposit!
      </p>
      <p class="text_16 _115 color-red pb10">
        <svg
            width="14"
            height="12"
            viewBox="0 0 14 12"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
          <path
              d="M13.9202 11.1357L7.51067 0.288078C7.28374 -0.0960261 6.7163 -0.0960261 6.48932 0.288078L0.0798921 11.1357C-0.147088 11.5198 0.136603 12 0.590509 12H13.4094C13.8634 12 14.1471 11.5199 13.9202 11.1357ZM7.57517 10.479C7.57517 10.5506 7.31765 10.6086 7.00005 10.6086C6.68234 10.6086 6.42482 10.5506 6.42482 10.479V9.57974C6.42482 9.50813 6.68234 9.45007 7.00005 9.45007C7.31765 9.45007 7.57517 9.50816 7.57517 9.57974V10.479ZM7.4793 8.38088C7.47952 8.38355 7.48111 8.38609 7.48111 8.3889C7.48111 8.50496 7.26567 8.59906 7.00002 8.59906C6.73421 8.59906 6.51888 8.50496 6.51888 8.3889C6.51888 8.38617 6.52041 8.38361 6.52069 8.38088L6.22166 4.62694C6.22166 4.51087 6.57013 4.41677 7 4.41677C7.42987 4.41677 7.77828 4.51087 7.77828 4.62694L7.4793 8.38088Z"
              fill="#FF6868"
          />
        </svg>
        Send <span class="coinName"></span> only to this address
      </p>
      <label class="deposit-label mb25">
        <input
            type="text"
            class="input deposit-input"
            id="deposit-adress"
            readonly
            value="1QB4XCxPZtvxEbcewQFEBrtLfiEMCYfwpg"
        />
        <button
            class="clear clipboard"
            data-clipboard-target="#deposit-adress"
        >
          <svg
              width="16"
              height="17"
              viewBox="0 0 16 17"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path
                d="M2.66667 1.7V5.1H13.3333V1.7H15.117C15.6046 1.7 16 2.07821 16 2.54439V16.1556C16 16.6219 15.6045 17 15.117 17H0.883022C0.395342 17 0 16.6218 0 16.1556V2.54439C0 2.07805 0.395511 1.7 0.883022 1.7H2.66667ZM4.44444 0H11.5556V3.4H4.44444V0Z"
                fill="#344955"
            />
          </svg>
        </button>
      </label>
      <div class="deposit-info">
        <div class="qr-container">
          <p class="small_14 color-gray2 pb10">Or scan QR-code</p>
          <div id="depositQR" alt=""></div>
        </div>
        <div class="deposit-details">
          <p class="small_14 color-gray2 pb10">Minimum Deposit Amount</p>
          <p class="text_18 pb25"><span id="min_deposit"></span> <span class="coinName"></span></p>
          <p class="small_14 color-gray2 pb10">Deposit Confirmation</p>
          <p class="text_18 pb25">2 Block(s)</p>
          <p class="small_14 color-gray2">
            You can close this window after sending. Cryptocurrency will be
            deposited after the specified number of network confirmations of
            the transaction.
          </p>
        </div>
      </div>
    </div>

    <div class="modal" id="withdraw2">
      <form id="withdraw_form"></form>
      <button type="button" class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <h2 class="h1_25 pb15">Withdraw <span class="CoinNameWithdraw"></span></h2>
      <p class="text_16 pb25 color-red">
        Do not send <span class="CoinNameWithdraw"></span> unless you are certain the destination supports
        <span class="CoinNameWithdraw"></span> transactions. If it does not, you could permanently lose
        access to your coins
      </p>
      <p class="text_16 _115 color-gray2 pb10">Enter wallet address</p>
      <label class="withdraw-label mb20">
        <input
            type="text"
            class="input withdraw-input"
            id="withdraw-adress"
            value=""
            placeholder="Your address"
        />
      </label>
      <p class="text_16 _115 color-gray2 pb10">Quantity <span class="CoinNameWithdraw"></span></p>
      <label class="withdraw-label mb25">
        <input
            type="text"
            class="input withdraw-input"
            id="amountWithdraw"
            oninput="validateInput(this);updateDataWithdraw()"
            placeholder="0.1234"
        />
      </label>
      <div class="withdraw-info flex-center flex-between pb25">
        <div class="fees">
          <span class="text_small_14 color-gray2">Fees</span>
          <span class="text_18" id="fees">0.5%</span>
        </div>
        <div class="receive">
          <span class="text_small_14 color-gray2">You will receive</span>
          <span class="text_18" id="reciveWithdraw"></span>
        </div>
      </div>
      <button
          type="submit"
          class="btn btn_action btn_16 color-dark"
          id="withdrawBtn"
          onclick="nextWithdrawInputData()"
      >
        Withdraw
      </button>

    </div>
    <div class="modal" id="withdraw-fail">
      <button class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      @if(!$withdraw_error)
      <div class="flex-column flex-center pb25 text-center">
        <h2 class="h1_25 color-red pb20">
          <svg
              width="22"
              height="22"
              viewBox="0 0 22 22"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path
                d="M11 22C4.92487 22 0 17.0751 0 11C0 4.92487 4.92487 0 11 0C17.0751 0 22 4.92487 22 11C22 17.0751 17.0751 22 11 22ZM9.9 14.3V16.5H12.1V14.3H9.9ZM9.9 5.5V12.1H12.1V5.5H9.9Z"
                fill="#FF6868"
            />
          </svg>
          Oops, your wallet needs to be activated!
        </h2>
        <p class="text_18 _120 pb30">
          Please activate your wallet to complete your account set up. <br/>
          To activate the wallet you need to make a minimum deposit of <br/>
          0.015 BTC
        </p>
        <p class="h2_20">
          Your deposit: <span class="color-red">0.00 / 0.015 BTC</span>
        </p>
      </div>
      @else
      <div class="text_18">
        {!! $withdraw_error !!}
      </div>
      @endif
      <button
          type="submit"
          class="btn btn_action btn_16 color-dark"
          data-izimodal-close=""
      >
        Return to wallet
      </button>
    </div>
    <div class="modal" id="withdraw-succes">
      <button class="closemodal clear" data-izimodal-close="">
        <img src="/images/modal_close.svg" alt=""/>
      </button>
      <div class="flex-column flex-center pb25 text-center">
        <h2 class="h1_25 color-green2 pb20">
          <svg
              width="22"
              height="22"
              viewBox="0 0 22 22"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path
                d="M11 22C4.92487 22 0 17.0751 0 11C0 4.92487 4.92487 0 11 0C17.0751 0 22 4.92487 22 11C22 17.0751 17.0751 22 11 22ZM9.90286 15.4L17.6811 7.62182L16.1255 6.06619L9.90286 12.2888L6.79163 9.17741L5.23599 10.7331L9.90286 15.4Z"
                fill="#79F995"
            />
          </svg>
          Successful withdrawal
        </h2>
        <p class="text_18 _110 pb25 color-gray2">
          Please wait for the funds to be <br/>
          credited to your wallet
        </p>
        <div class="withdraw-status_container pb25 flex-between">
          <div class="status">
            <span class="smal_14 color-gray2">Withdrawal status</span>
            <span class="text_18 color-green2">Success</span>
          </div>
          <div class="transaction">
            <span class="smal_14 color-gray2">Transaction ID</span>
            <span class="text_18 color-green2"></span>
          </div>
        </div>


      </div>
      <button
          type="submit"
          class="btn btn_action btn_16 color-dark"
          data-izimodal-close=""
      >
        Return to wallet
      </button>
    </div>






  </main>
</template>

<style scoped>

</style>