<script setup>
import Select from '@/app/select/index.js';
import { onMounted, ref } from 'vue';
const selectedValue = ref(null);
onMounted(() => {
    const select = new Select('#selectDepositFiat');
    select._el.addEventListener('itc.select.change', (e) => {
        selectedValue.value = select.value;
    });
});
</script>

<template>
    <div class="tabs-itemDeposit" id="selectDepositFiat">
        <div class="itc-select assets pb20" id="select-10">
            <button
                type="button"
                class="itc-select__toggle"
                name="cryptocurrency"
                value=""
                data-select="toggle"
                data-index="-1"
            >
                Choose a payment method
            </button>
            <div class="itc-select__dropdown">
                <ul class="itc-select__options"></ul>
            </div>
        </div>
        <button type="submit" class="btn btn_action btn_16 color-dark">
            Next
        </button>
    </div>
</template>

<style scoped></style>
