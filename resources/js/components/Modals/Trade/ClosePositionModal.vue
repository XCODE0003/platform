<script setup>
import { ref, computed, watch } from 'vue';
import { VueFinalModal } from 'vue-final-modal';

const props = defineProps({
    position: { type: Object, default: null }, // position row
    currentPrice: { type: [Number, String], default: null },
});

const emit = defineEmits(['close', 'confirm']);

const isOpen = computed(() => props.position !== null);

const maxQty = computed(() => Number(props.position?.quantity ?? 0));
const closeQty = ref(0);

// Reset when modal opens with a new position
watch(() => props.position?.id, () => {
    closeQty.value = maxQty.value;
});

const isFullClose = computed(() => closeQty.value >= maxQty.value);

const pnlPreview = computed(() => {
    const price = Number(props.currentPrice);
    const entry = Number(props.position?.entry_price ?? 0);
    const qty   = Number(closeQty.value);
    if (!price || !entry || !qty) return null;
    const diff = props.position?.side === 'buy' ? price - entry : entry - price;
    return (diff * qty).toFixed(4);
});

const pnlClass = computed(() => {
    const v = Number(pnlPreview.value);
    if (v > 0) return 'text-green-400';
    if (v < 0) return 'text-red-400';
    return 'text-gray-400';
});

const sliderPct = computed({
    get: () => maxQty.value > 0 ? Math.round((closeQty.value / maxQty.value) * 100) : 100,
    set: (pct) => {
        closeQty.value = parseFloat(((pct / 100) * maxQty.value).toFixed(8));
    },
});

function setPercent(pct) {
    sliderPct.value = pct;
}

function onConfirm() {
    emit('confirm', { quantity: isFullClose.value ? null : String(closeQty.value) });
}
</script>

<template>
    <VueFinalModal
        :modelValue="isOpen"
        overlay-transition="vfm-fade"
        content-transition="vfm-fade"
        click-to-close
        esc-to-close
        background="non-interactive"
        lock-scroll
        class="flex items-center justify-center"
        content-class="w-full max-w-sm mx-4 bg-[#131722] border border-[#2a2e39] rounded-xl p-5"
        @closed="emit('close')"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-semibold text-base">Close position</h3>
                <button style="background:transparent!important;border:none!important;color:#6b7280!important;" @click="emit('close')">
                    <svg width="12" height="12" viewBox="0 0 10 10" fill="none">
                        <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Position summary -->
            <div class="bg-[#1e222d] rounded-lg px-3 py-2 text-sm flex gap-4">
                <span :class="position?.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="font-medium uppercase">
                    {{ position?.side }}
                </span>
                <span class="text-gray-400">Entry: <span class="text-white">{{ Number(position?.entry_price).toFixed(4) }}</span></span>
                <span class="text-gray-400">Qty: <span class="text-white">{{ maxQty }}</span></span>
            </div>

            <!-- Quick percent buttons -->
            <div class="flex gap-2">
                <button
                    v-for="pct in [25, 50, 75, 100]"
                    :key="pct"
                    @click="setPercent(pct)"
                    :style="sliderPct === pct
                        ? 'background:#2962ff!important;color:#fff!important;border:1px solid #2962ff!important;'
                        : 'background:transparent!important;color:#9ca3af!important;border:1px solid #2a2e39!important;'"
                    class="flex-1 rounded-lg py-1.5 text-xs font-semibold transition-all duration-150"
                >
                    {{ pct === 100 ? 'Full' : pct + '%' }}
                </button>
            </div>

            <!-- Slider -->
            <div class="flex flex-col gap-1">
                <input
                    type="range"
                    min="1"
                    max="100"
                    :value="sliderPct"
                    @input="sliderPct = Number($event.target.value)"
                    class="w-full accent-[#2962ff] cursor-pointer"
                />
                <div class="flex justify-between text-xs text-gray-500">
                    <span>1%</span>
                    <span class="text-white font-medium">{{ sliderPct }}% — {{ closeQty }} units</span>
                    <span>100%</span>
                </div>
            </div>

            <!-- Manual qty input -->
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400 shrink-0">Quantity:</label>
                <input
                    type="number"
                    :max="maxQty"
                    :min="0"
                    step="any"
                    :value="closeQty"
                    @input="closeQty = Math.min(Number($event.target.value), maxQty)"
                    class="flex-1 !text-white bg-[#1e222d] border border-[#2a2e39] rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-[#2962ff]"
                />
            </div>

            <!-- PnL preview -->
            <div v-if="pnlPreview !== null" class="flex justify-between text-sm">
                <span class="text-gray-400">Est. PnL:</span>
                <span :class="pnlClass" class="font-medium">
                    {{ Number(pnlPreview) > 0 ? '+' : '' }}{{ pnlPreview }} USDT
                </span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
                <button
                    style="background:transparent!important;color:#9ca3af!important;border:1px solid #2a2e39!important;"
                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-all duration-150"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    :style="isFullClose
                        ? 'background:#ef4444!important;color:#fff!important;border:none!important;'
                        : 'background:#2962ff!important;color:#fff!important;border:none!important;'"
                    class="flex-1 py-2 rounded-lg text-sm font-semibold transition-all duration-150"
                    @click="onConfirm"
                >
                    {{ isFullClose ? 'Close full position' : `Close ${sliderPct}%` }}
                </button>
            </div>
        </div>
    </VueFinalModal>
</template>
