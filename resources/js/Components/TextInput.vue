<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="qp-focus rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 font-bold text-slate-900 shadow-none transition placeholder:font-semibold placeholder:text-slate-400 focus:border-violet-400 focus:ring-violet-500/15 dark:border-white/15 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-slate-500"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
