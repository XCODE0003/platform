<script setup>
import {ref, watchEffect} from "vue";
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { useUserStore } from '@/stores/userStore.js';
import { computed } from 'vue';
const userStore = useUserStore();

const user = computed(() => usePage().props.auth.user);
userStore.setUser(user.value);


const page = usePage();

const isAuth = ref(page.props.auth.user);

</script>

<template>
  <div class="container">
    <div class="header-content">
      <Link class="header-link" href="/">
        <img class="header-img" src="/images/logo.svg" alt="logo"/>

      </Link>
      <nav class="navbar">
        <ul class="nav-list">
          <li class="list-item">
            <Link  class="item-link" href="/trade">Trade</Link>
          </li>
          <li class="list-item ">
            <Link class="item-link" href="/assets">Staking</Link>
          </li>
          <li class="list-item">
            <a class="item-link" href="/about">About</a>
          </li>

        </ul>
      </nav>
      <div v-if="!isAuth" class="header-buttons not-authed">

        <Link href="/login" class="link_15">Log in</Link>
        <Link href="/register" class="btn btn_sign link_15">Sign up</Link>
      </div>
      <div v-else class="header-buttons authed">
        <div class="header-buttons authed ">

          <Link href="/assets" class="link_15">Assets</Link>
          <div class="dropdown-container">
            <Link href="/account" class="link_15 account">
              Account
              <svg
                  width="4"
                  height="2"
                  viewBox="0 0 4 2"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
              >
                <path
                    d="M1.78787 0.212132C1.90503 0.0949744 2.09497 0.0949747 2.21213 0.212132L3.48787 1.48787C3.67686 1.67686 3.54301 2 3.27574 2L0.724264 2C0.456993 2 0.323143 1.67686 0.512132 1.48787L1.78787 0.212132Z"
                    fill="white"
                />
              </svg>
            </Link>
            <div class="dropdown">

              <Link href="/account" class="link_12 settings">Settings</Link>
              <Link href="/logout"  class="link_12 pointer">Log out</Link>
            </div>
          </div>
        </div>
      </div>

    </div>
    <a class="header-link header_mobile" href="/public">
      <img class="header-img" src="/images/logo.svg" alt=""/>
    </a>
    <div class="burger">
      <span></span>
    </div>
  </div>


</template>

<style scoped>
.dropdown .settings{
  width: unset;
  height: unset;
}
</style>