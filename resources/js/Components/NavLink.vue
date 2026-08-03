<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'inline-flex items-center rounded-xl bg-violet-100 px-3 py-2 text-sm font-extrabold text-violet-700 transition dark:bg-violet-500/20 dark:text-violet-200'
        : 'inline-flex items-center rounded-xl px-3 py-2 text-sm font-bold text-slate-500 transition hover:bg-white hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white';
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
