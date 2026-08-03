<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="qp-focus inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-600 px-5 py-3 text-sm font-black text-white shadow-[0_5px_0_#5b21b6] transition hover:-translate-y-0.5 hover:bg-violet-500 active:translate-y-1 active:shadow-none disabled:pointer-events-none disabled:opacity-50">
        <slot />
    </button>
</template>
