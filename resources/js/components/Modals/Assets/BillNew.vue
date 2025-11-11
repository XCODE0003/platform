<script setup lang="js">
import { useModalStore } from '@/stores/modal.js';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { VueFinalModal } from 'vue-final-modal';
import { useUserStore } from '../../../stores/userStore.js';
const modal = useModalStore();
const userStore = useUserStore();
const isOpen = computed({
    get: () => modal.isOpen('bill'),
    set: (v) => (v ? modal.open('bill') : modal.close('bill')),
});
const form = useForm({
    bill_name: '',
    is_demo: false,
});

function submit() {
    form.post('/assets/bill/create', {
        onSuccess: (data) => {
            form.reset();
            isOpen.value = false;
            userStore.fetchBills()
        },
        onError: (errors) => {
            console.log(errors);
        },
    });
}
</script>

<template>
    <VueFinalModal v-model="isOpen" overlay-transition="vfm-fade" content-transition="vfm-fade" click-to-close esc-to-close background="non-interactive" lock-scroll class="flex items-center justify-center" :content-class="`max-w-xl `">
        <div class="modal show">
            <form style="width: 100%" @submit.prevent="submit">
                <button type="button" class="closemodal clear" @click="isOpen = false">
                    <img src="/images/modal_close.svg" alt="" />
                </button>
                <h2 class="h1_25 pb15">Create bill</h2>
                <p class="text_18 pb25">Create new bill</p>

                <label class="flex-column gap6 pb-2">
                    <input
                        type="text"
                        class="input bill-input"
                        v-model="form.bill_name"
                        placeholder="Enter bill name"
                        :class="{ error: form.errors.bill_name }"
                    />
                    <div v-if="form.errors.bill_name" class="error-message text-red">
                        {{ form.errors.bill_name }}
                    </div>
                </label>

                <div class="form-check pb-3">
                    <input type="checkbox" id="demo_bill" name="demo_bill" v-model="form.is_demo" class="checkbox" />
                    <label for="demo_bill" class="text_small_12 color-gray2">Create demo bill?</label>
                </div>

                <button type="submit" class="btn btn_action btn_16 color-dark" :disabled="form.processing || !form.bill_name">
                    {{ form.processing ? 'Creating bill...' : 'Create bill' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
