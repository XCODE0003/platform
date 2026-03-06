<script setup>
import TicketChat from '@/components/TicketChat.vue';
import { useSupportChatStore } from '@/stores/supportChatStore.js';
import { storeToRefs } from 'pinia';

const supportChat = useSupportChatStore();
const { open: chatOpen } = storeToRefs(supportChat);
</script>

<template>
  <div class="tickets-toggle" :class="{ active: chatOpen }" @click="supportChat.toggleChat()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path
          d="M17 18.43H13L8.54999 21.39C7.88999 21.83 7 21.36 7 20.56V18.43C4 18.43 2 16.43 2 13.43V7.42993C2 4.42993 4 2.42993 7 2.42993H17C20 2.42993 22 4.42993 22 7.42993V13.43C22 16.43 20 18.43 17 18.43Z"
          stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
          stroke-linejoin="round"></path>
      <path opacity="1"
            d="M12 11.3599V11.1499C12 10.4699 12.42 10.1099 12.84 9.81989C13.25 9.53989 13.66 9.1799 13.66 8.5199C13.66 7.5999 12.92 6.85986 12 6.85986C11.08 6.85986 10.34 7.5999 10.34 8.5199"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
      <path opacity="0.5" d="M11.9955 13.75H12.0045" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round"></path>
    </svg>
  </div>

  <div class="container">
    <div class="footer-content">
      <div class="footer-logo">
        <a href="/">
          <img src="/images/logo.svg" alt=""/>
          CRYPTOHOUSE
        </a>
        <div class="copyright text_small_12">
          © 2023. All rights reserved by CRYPTOHOUSE
        </div>
      </div>
      <div class="footer-links">
        <a href="/privacy" class="link_15">Privacy policy</a>
        <a href="/risk" class="link_15">Risk warning</a>
        <a href="/security" class="link_15">Security</a>
        <a href="/terms" class="link_15">Terms of use</a>
        <a href="/referral" class="link_15">Referral</a>
        <a rel="nofollow" class="trustpolot" href="https://trustpilotn.com/php/api.php?link={{$_SERVER['SERVER_NAME']}}&type=4">
          <img height="22px" width="83px" class="dark" src="/images/trustpilot.svg" alt="trustpilot">
        </a>
      </div>
      <div class="footer-disclamer">
        <p class="text_small_12 _120">
          The Transactions offered by this Website can be executed only by
          fully competent adults. Transactions with financial instruments
          offered on the Website involve substantial risk and trading may be
          very risky
        </p>
      </div>
    </div>
  </div>

  <TicketChat :open="chatOpen" @close="supportChat.closeChat()" />
</template>

<style scoped>
.tickets-toggle {
    cursor: pointer;
    transition: color 0.2s;
}
.tickets-toggle.active {
    color: #79f995;
}
</style>
