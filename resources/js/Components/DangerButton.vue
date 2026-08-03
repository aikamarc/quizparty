<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="qp-focus inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-500 px-5 py-3 text-sm font-black text-white shadow-[0_5px_0_#be123c] transition hover:-translate-y-0.5 hover:bg-rose-400 active:translate-y-1 active:shadow-none disabled:pointer-events-none disabled:opacity-50">
        <slot />
    </button>
</template>
