<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="qp-focus inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-[0_4px_0_rgb(15_23_42/0.10)] transition hover:-translate-y-0.5 hover:border-violet-300 hover:text-violet-700 active:translate-y-1 active:shadow-none disabled:pointer-events-none disabled:opacity-50 dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:border-violet-400">
        <slot />
    </button>
</template>
