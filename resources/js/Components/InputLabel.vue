<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label class="block text-sm font-extrabold text-slate-700 dark:text-slate-200">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
