<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
});

const checked = defineModel({ type: Boolean, required: true });
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="checked"
        :disabled="disabled"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :class="checked ? 'bg-violet-600' : 'bg-gray-300 dark:bg-gray-600'"
        @click="checked = !checked"
    >
        <span
            class="inline-block size-4 transform rounded-full bg-white shadow transition-transform"
            :class="checked ? 'translate-x-6' : 'translate-x-1'"
        />
    </button>
</template>
