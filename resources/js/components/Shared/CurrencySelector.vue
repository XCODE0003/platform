<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  wallets: { type: Array, default: () => [] },
  modelValue: { type: [Object, null], default: null },
  error: { type: [Boolean, Object, String], default: false },
  placeholder: { type: String, default: 'Choose cryptocurrency' },
});
const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

function toggle() {
  if (!props.wallets || !props.wallets.length) return;
  isOpen.value = !isOpen.value;
}

function close() {
  isOpen.value = false;
}

function selectWallet(bill: any) {
  emit('update:modelValue', bill);
  close();
}

function handleClickOutside(e: Event) {
  if (!dropdownRef.value) return;
  const target = e.target as Node | null;
  if (target && !dropdownRef.value.contains(target)) close();
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));

// если модель пустая, но в wallets есть элементы — устанавливаем первый как выбранный
watch(() => props.wallets, (val) => {
  const list = val ?? [];
  if ((!props.modelValue || Object.keys(props.modelValue).length === 0) && list.length) {
    emit('update:modelValue', list[0]);
  }
}, { immediate: true });
</script>

<template>
  <div class="currency-selector" ref="dropdownRef">
    <div class="simple-select" :class="{ open: isOpen, error: !!props.error }" @click.stop="toggle">
      <div v-if="modelValue" class="selected-info">
        <img v-if="modelValue.currency?.icon" :src="`/images/coin_icons/${String(modelValue.currency.icon).toLowerCase()}.svg`" alt="" />
        <div class="item-text">
          <span class="symbol">{{ modelValue.currency?.symbol ?? modelValue.name }}</span>
          <span class="balance">Balance: {{ Number.parseFloat(modelValue.balance ?? '0').toFixed(8) }}</span>
        </div>
      </div>
      <div v-else class="simple-select__placeholder">{{ placeholder }}</div>
      <svg class="chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>

    <transition name="fade">
      <div v-if="isOpen" class="simple-select__dropdown" @click.stop>
        <div class="dropdown-list">
          <button
            v-for="bill in wallets"
            :key="bill.id"
            type="button"
            class="dropdown-item"
            :class="{ active: modelValue?.id === bill.id }"
            @click="selectWallet(bill)"
          >
            <div class="item-info">
              <span class="symbol">{{ bill.name ?? bill.currency?.symbol }}</span>
              <span class="name">{{ bill.currency?.name }}</span>
            </div>
            <span class="amount">{{ Number.parseFloat(bill.balance ?? '0').toFixed(8) }}</span>
          </button>
          <p v-if="!wallets.length" class="dropdown-empty">No balances found</p>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* Переиспользуемые стили — копия тех, что были в Invest.vue */
.currency-selector { position: relative; }
.simple-select { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 10px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); cursor: pointer; }
.simple-select.open { border-color: rgba(121, 249, 149, 0.6); }
.simple-select.error { border-color: rgba(239, 68, 68, 0.6); }
.selected-info { display:flex; align-items:center; gap:12px; }
.selected-info img { width:28px; height:28px; }
.item-text { display:flex; flex-direction:column; gap:2px; }
.item-text .symbol { font-weight:600; color: white; }
.item-text .balance { font-size:12px; color: rgba(255,255,255,0.6); }
.simple-select__dropdown { position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #0a1f2b; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding:8px; z-index:40; }
.dropdown-list { max-height:240px; overflow-y:auto; display:flex; flex-direction:column; gap:6px; }
.dropdown-item { width:100%; display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:8px; background:transparent; color:white; }
.dropdown-item.active { border-color: rgba(121,249,149,0.35); background: rgba(121,249,149,0.16); }
.dropdown-empty { text-align:center; padding:18px 0 10px; font-size:13px; color: rgba(255,255,255,0.45); }
</style>

