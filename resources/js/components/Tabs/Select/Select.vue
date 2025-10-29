<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, withDefaults, nextTick } from 'vue';

type Value = string | number;

interface Option {
    value: Value;
    label: string;
    icon?: string;
}

const props = withDefaults(
    defineProps<{
        id: string;
        placeholder?: string;
        options: Option[];
        modelValue: Value | null;
        showSearch?: boolean;
        buttonClass?: string;
        buttonText?: string;
        showButton?: boolean;
        disabled?: boolean;
        maxHeight?: number; // px
    }>(),
    {
        placeholder: 'Choose an option',
        options: () => [],
        showSearch: false,
        buttonClass: 'btn btn_action btn_16 color-dark',
        buttonText: 'Next',
        showButton: true,
        disabled: false,
        maxHeight: 240,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: Value | null): void;
    (e: 'change', value: Value | null): void;
    (e: 'submit', value: Value | null): void;
}>();

const isOpen = ref(false);
const dropUp = ref(false);
const searchQuery = ref('');
const activeIndex = ref<number>(-1);

const rootEl = ref<HTMLElement | null>(null);
const buttonEl = ref<HTMLButtonElement | null>(null);
const listEl = ref<HTMLUListElement | null>(null);
const searchEl = ref<HTMLInputElement | null>(null);

const selectedValue = ref<Value | null>(props.modelValue ?? null);
watch(
    () => props.modelValue,
    (v) => (selectedValue.value = v ?? null)
);

const equals = (a: Value | null, b: Value | null) =>
    a !== null && b !== null ? String(a) === String(b) : a === b;

const selectedOption = computed<Option | null>(() => {
    return props.options.find((o) => equals(o.value, selectedValue.value)) ?? null;
});

const filteredOptions = computed<Option[]>(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!props.showSearch || q === '') return props.options;
    return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

watch(filteredOptions, (opts) => {
    if (!isOpen.value) return;
    if (!opts.length) {
        activeIndex.value = -1;
        return;
    }
    const idx = opts.findIndex((o) => equals(o.value, selectedValue.value));
    activeIndex.value = idx >= 0 ? idx : 0;
});

function computePlacement() {
    if (!buttonEl.value) {
        dropUp.value = false;
        return;
    }
    const rect = buttonEl.value.getBoundingClientRect();
    const viewportBottom = window.innerHeight - rect.bottom;
    const viewportTop = rect.top;
    const want = props.maxHeight + 20; // список + отступы
    dropUp.value = viewportBottom < want && viewportTop > viewportBottom;
}

async function openDropdown(): Promise<void> {
    if (props.disabled) return;
    computePlacement();
    isOpen.value = true;

    const idx = filteredOptions.value.findIndex((o) => equals(o.value, selectedValue.value));
    activeIndex.value = idx >= 0 ? idx : (filteredOptions.value.length ? 0 : -1);

    await nextTick();
    if (props.showSearch && searchEl.value) {
        searchEl.value.focus();
        searchEl.value.select?.();
    } else {
        listEl.value?.focus();
    }
}

function closeDropdown(): void {
    isOpen.value = false;
    activeIndex.value = -1;
    searchQuery.value = '';
}

function toggleDropdown(): void {
    isOpen.value ? closeDropdown() : openDropdown();
}

function onSelect(option: Option): void {
    if (props.disabled) return;
    selectedValue.value = option.value;
    emit('update:modelValue', option.value);
    emit('change', option.value);
    closeDropdown();
    buttonEl.value?.focus();
}

function onOutsideClick(e: MouseEvent): void {
    if (!rootEl.value) return;
    if (isOpen.value && !rootEl.value.contains(e.target as Node)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', onOutsideClick);
    window.addEventListener('resize', computePlacement, { passive: true });
    window.addEventListener('scroll', computePlacement, { passive: true });
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onOutsideClick);
    window.removeEventListener('resize', computePlacement);
    window.removeEventListener('scroll', computePlacement);
});

function ensureActiveVisible(): void {
    const el = listEl.value;
    if (!el) return;
    const item = el.querySelector<HTMLElement>(`[data-index="${activeIndex.value}"]`);
    if (!item) return;

    const itemTop = item.offsetTop;
    const itemBottom = itemTop + item.offsetHeight;
    if (itemTop < el.scrollTop) {
        el.scrollTop = itemTop;
    } else if (itemBottom > el.scrollTop + el.clientHeight) {
        el.scrollTop = itemBottom - el.clientHeight;
    }
}

function onButtonKeydown(e: KeyboardEvent): void {
    if (props.disabled) return;
    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        if (!isOpen.value) openDropdown();
    }
}

function onListKeydown(e: KeyboardEvent): void {
    if (e.key === 'Escape') {
        e.preventDefault();
        closeDropdown();
        buttonEl.value?.focus();
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!filteredOptions.value.length) return;
        activeIndex.value = Math.min(activeIndex.value + 1, filteredOptions.value.length - 1);
        ensureActiveVisible();
        return;
    }
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!filteredOptions.value.length) return;
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
        ensureActiveVisible();
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        const option = filteredOptions.value[activeIndex.value];
        if (option) onSelect(option);
    }
}

const displayText = computed<string>(() => {
    return selectedOption.value?.label ?? props.placeholder ?? 'Choose an option';
});
</script>

<template>
    <div :id="id">
        <div
            class="itc-select"
            :class="{ disabled, 'is-open': isOpen, 'drop-up': dropUp }"
            ref="rootEl"
        >
            <button
                ref="buttonEl"
                type="button"
                class="itc-select__toggle"
                name="select"
                :disabled="disabled"
                :aria-expanded="isOpen"
                aria-haspopup="listbox"
                @click="toggleDropdown"
                @keydown="onButtonKeydown"
            >
                <div class="toggle-content">
                    <template v-if="selectedOption">
                        <img
                            v-if="selectedOption.icon"
                            width="20"
                            height="20"
                            :src="selectedOption.icon"
                            :alt="selectedOption.label"
                            class="select-img"
                        />
                        <span class="label">{{ selectedOption.label }}</span>
                    </template>
                    <template v-else>
                        <span class="placeholder">{{ displayText }}</span>
                    </template>
                </div>
                <svg
                    class="chevron"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <transition name="itc-dd">
                <div v-show="isOpen" class="itc-select__dropdown" :style="{ zIndex: 30 }">
                    <div v-if="showSearch" class="search">
                        <input
                            ref="searchEl"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search"
                            @keydown.stop
                        />
                    </div>
                    <ul
                        class="itc-select__options hide-scroll"
                        role="listbox"
                        tabindex="-1"
                        ref="listEl"
                        :style="{ maxHeight: `${maxHeight}px`, overflowY: 'auto' }"
                        @keydown="onListKeydown"
                    >
                        <li
                            v-for="(option, idx) in filteredOptions"
                            :key="String(option.value)"
                            class="itc-select__option"
                            :class="{
                                'is-active': idx === activeIndex,
                                'is-selected': equals(option.value, selectedValue)
                            }"
                            :data-select="'option'"
                            :data-value="option.value"
                            :data-index="idx"
                            role="option"
                            :aria-selected="equals(option.value, selectedValue)"
                            @mouseenter="activeIndex = idx"
                            @mousedown.prevent
                            @click="onSelect(option)"
                        >
                            <div class="option-left">
                                <img
                                    v-if="option.icon"
                                    width="20"
                                    height="20"
                                    :src="option.icon"
                                    :alt="option.label"
                                    class="select-img"
                                />
                                <span>{{ option.label }}</span>
                            </div>
                            <svg
                                v-if="equals(option.value, selectedValue)"
                                class="check"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </li>
                        <li v-if="!filteredOptions.length" class="itc-select__option empty">
                            Nothing found
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>
.disabled {
    opacity: 0.6;
    pointer-events: none;
}

.itc-select {
    position: relative;
    min-width: 160px;
}

.itc-select__toggle {
    position: relative;
    width: 100%;
    border-radius: 6px;
    background: var(--Dark_3, #1D323E);
    padding: 10px 38px 10px 12px;
    color: var(--White);
    font-family: SF Pro Display;
    font-size: 16px;
    font-weight: 400;
    line-height: 100%;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
}

.itc-select__toggle .toggle-content {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.itc-select__toggle .label,
.itc-select__toggle .placeholder {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.itc-select__toggle .placeholder {
    color: var(--Dark, #344955);
}

.itc-select__toggle .chevron {
    position: absolute;
    right: 12px;
    color: var(--Gray_2);
    transition: transform .15s ease;
}
.is-open .itc-select__toggle .chevron {
    transform: rotate(180deg);
}

.itc-select__dropdown {
    position: absolute;
    left: 0;
    right: 0;
    margin-top: 8px;
    border-radius: 8px;
    background: var(--Dark_3, #1D323E);
    border: 1px solid rgba(255,255,255,0.06);
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
    overflow: hidden;
}
.drop-up .itc-select__dropdown {
    bottom: calc(100% + 8px);
    top: auto;
    margin-top: 0;
}

/* список */
.itc-select__options {
    color: var(--White, #FFF);
    font-family: SF Pro Display;
    font-size: 16px;
    font-weight: 400;
    line-height: 100%;
}

.itc-select__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    cursor: pointer;
    transition: background-color .15s ease, color .15s ease;
}
.itc-select__option .option-left {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.itc-select__option:hover,
.itc-select__option.is-active {
    background-color: rgba(255, 255, 255, 0.06);
}
.itc-select__option.is-selected {
    font-weight: 600;
    color: var(--White);
}
.itc-select__option.empty {
    color: var(--Gray_2);
    cursor: default;
}

.check {
    color: var(--Blue, #C4E9FC);
}

/* поиск уже стилизован в app.css через .search и .search input */
.search {
    padding: 12px 12px 4px 12px;
}

/* анимация выпада */
.itc-dd-enter-from {
    opacity: 0;
    transform: translateY(-6px) scaleY(0.98);
}
.itc-dd-enter-to {
    opacity: 1;
    transform: translateY(0) scaleY(1);
}
.itc-dd-leave-from {
    opacity: 1;
    transform: translateY(0) scaleY(1);
}
.itc-dd-leave-to {
    opacity: 0;
    transform: translateY(-6px) scaleY(0.98);
}
.itc-dd-enter-active,
.itc-dd-leave-active {
    transition: opacity .12s ease, transform .12s ease;
}

@media (max-width: 500px) {
    .itc-select__dropdown {
        min-width: 85vw;
    }
}
</style>