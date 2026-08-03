<script setup>
import { HugeiconsIcon } from '@hugeicons/vue';
import { MinusSignIcon, PlusSignIcon } from '@hugeicons/core-free-icons';

const props = defineProps({
    min: { type: Number, required: true },
    max: { type: Number, required: true },
    step: { type: Number, default: 1 },
    icon: { type: [Object, Function], default: null },
});

const value = defineModel({ type: Number, required: true });

const clamp = (n) => Math.min(props.max, Math.max(props.min, n));

const decrement = () => { value.value = clamp(value.value - props.step); };
const increment = () => { value.value = clamp(value.value + props.step); };
const onInput = (event) => {
    const n = Number(event.target.value);
    if (! Number.isNaN(n)) value.value = n;
};
const onBlur = () => { value.value = clamp(value.value); };
</script>

<template>
    <div class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white py-1 pl-1 pr-1.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <button
            type="button"
            class="flex size-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-800"
            :disabled="value <= min"
            @click="decrement"
        >
            <HugeiconsIcon :icon="MinusSignIcon" :size="16" />
        </button>

        <div class="flex items-center gap-1.5 px-1">
            <HugeiconsIcon v-if="icon" :icon="icon" :size="15" class="shrink-0 text-violet-500" />
            <input
                type="number"
                :min="min"
                :max="max"
                :value="value"
                class="w-10 border-0 bg-transparent p-0 text-center text-sm font-bold text-gray-900 focus:ring-0 [appearance:textfield] dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                @input="onInput"
                @blur="onBlur"
            >
        </div>

        <button
            type="button"
            class="flex size-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-800"
            :disabled="value >= max"
            @click="increment"
        >
            <HugeiconsIcon :icon="PlusSignIcon" :size="16" />
        </button>
    </div>
</template>
