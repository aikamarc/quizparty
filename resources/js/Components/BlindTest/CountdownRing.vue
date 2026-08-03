<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    startedAt: { type: String, required: true },
    duration: { type: Number, required: true },
});

const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = setInterval(() => { now.value = Date.now(); }, 100);
});

onUnmounted(() => clearInterval(timer));

const elapsedSeconds = computed(() => (now.value - new Date(props.startedAt).getTime()) / 1000);
const remaining = computed(() => Math.max(0, props.duration - elapsedSeconds.value));
const fraction = computed(() => Math.max(0, Math.min(1, remaining.value / props.duration)));

const radius = 54;
const circumference = 2 * Math.PI * radius;
const dashOffset = computed(() => circumference * (1 - fraction.value));
</script>

<template>
    <div class="relative flex size-32 items-center justify-center">
        <svg class="size-32 -rotate-90" viewBox="0 0 120 120">
            <circle cx="60" cy="60" :r="radius" fill="none" stroke-width="8" class="stroke-gray-200 dark:stroke-gray-700" />
            <circle
                cx="60"
                cy="60"
                :r="radius"
                fill="none"
                stroke-width="8"
                stroke-linecap="round"
                class="stroke-violet-500 transition-[stroke-dashoffset] duration-100 ease-linear"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
            />
        </svg>
        <span class="absolute text-2xl font-extrabold text-gray-900 dark:text-white">{{ Math.ceil(remaining) }}</span>
    </div>
</template>
