<script setup>
import { useModalStore } from '@/stores/modal.js';
import { useToast } from '@/composables/useToast.js';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const modal = useModalStore();
const toast = useToast();
const isOpen = computed({
    get: () => modal.isOpen('promocode'),
    set: (v) => (v ? modal.open('promocode') : modal.close('promocode')),
});

const form = useForm({ promocode: '' });

function activatePromocode() {
    form.post('/account/activate-promocode', {
        onSuccess: () => {
            toast.showSuccess('Promocode successfully activated');
            isOpen.value = false;
            form.reset();
        },
        onError: (errors) => {
            const msg = errors.promocode ?? Object.values(errors)[0] ?? 'Activation failed';
            toast.showError(msg);
        },
    });
}
</script>

<template>
    <VueFinalModal
        v-model="isOpen"
        overlay-transition="vfm-fade"
        content-transition="vfm-fade"
        click-to-close
        esc-to-close
        background="non-interactive"
        lock-scroll
        class="flex items-center justify-center"
        :content-class="`max-w-xl `"
    >
        <div class="modal show">
            <form @submit.prevent="activatePromocode" style="width: 100%">
                <button
                    type="button"
                    class="closemodal clear"
                    @click="isOpen = false"
                >
                    <img src="/images/modal_close.svg" alt="" />
                </button>
                <h2 class="h1_25 pb15">Activate promocode</h2>
                <p class="text_18 pb25">
                    Activate promocode to credit your account balance
                </p>
                <label class="pb20 flex-column gap6">
                    <input
                        type="text"
                        class="input promocode-input"
                        v-model="form.promocode"
                        placeholder="Enter promocode"
                        :class="{ error: form.errors.promocode }"
                    />
                    <div
                        v-if="form.errors.promocode"
                        class="error-message text-red"
                    >
                        {{ form.errors.promocode }}
                    </div>
                </label>
                <button
                    type="submit"
                    class="btn btn_action btn_16 color-dark"
                    :disabled="form.processing || !form.promocode"
                >
                    {{ form.processing ? 'Activating...' : 'Activate' }}
                </button>
            </form>
        </div>
    </VueFinalModal>
</template>

<style scoped></style>
