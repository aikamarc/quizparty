<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active: Boolean,
    href: String,
    as: String,
});

const classes = computed(() => {
    return props.active
        ? 'mx-3 block rounded-xl bg-violet-100 px-4 py-3 text-start text-base font-extrabold text-violet-700 dark:bg-violet-500/20 dark:text-violet-200'
        : 'mx-3 block rounded-xl px-4 py-3 text-start text-base font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10';
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" :class="classes" class="w-full text-start">
            <slot />
        </button>

        <a v-else-if="as == 'a'" :class="classes" class="w-full text-start" :href="href">
            <slot />
        </a>

        <Link v-else :href="href" :class="classes">
            <slot />
        </Link>
    </div>
</template>
