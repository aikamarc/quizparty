<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Brain01Icon, HeadphonesIcon, MusicNote01Icon } from '@hugeicons/core-free-icons';

const props = defineProps({
    slug: { type: String, required: true },
    color: { type: String, required: true },
    icon: { type: String, required: true },
    players: { type: String, required: true },
    comingSoon: { type: Boolean, default: false },
    href: { type: String, default: null },
});

const rootComponent = computed(() => (props.href ? Link : 'div'));

const icons = {
    'music-note': MusicNote01Icon,
    headphones: HeadphonesIcon,
    brain: Brain01Icon,
};

const iconComponent = computed(() => icons[props.icon] ?? MusicNote01Icon);

// Literal class names below (kept spelled out so Tailwind's scanner picks them up).
const palette = {
    violet: {
        badge: 'bg-violet-500',
        chip: 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
        ring: 'hover:ring-violet-200 dark:hover:ring-violet-800',
    },
    orange: {
        badge: 'bg-violet-400',
        chip: 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
        ring: 'hover:ring-violet-200 dark:hover:ring-violet-800',
    },
    teal: {
        badge: 'bg-cyan-500',
        chip: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300',
        ring: 'hover:ring-cyan-200 dark:hover:ring-cyan-800',
    },
};

const colors = computed(() => palette[props.color] ?? palette.violet);
const name = computed(() => trans(`games.${props.slug}.name`));
const description = computed(() => trans(`games.${props.slug}.description`));
const playersLabel = computed(() => (props.players === 'solo' ? trans('Solo') : trans('Multiplayer')));
</script>

<template>
    <component
        :is="rootComponent"
        :href="href"
        class="group relative flex min-h-64 flex-col gap-5 overflow-hidden rounded-[2rem] border-2 border-slate-200/80 bg-white p-6 shadow-[0_8px_0_rgb(15_23_42/0.07)] transition hover:-translate-y-1.5 hover:shadow-[0_12px_0_rgb(15_23_42/0.08)] dark:border-white/10 dark:bg-white/[0.06]"
        :class="colors.ring"
    >
        <div class="flex items-start justify-between">
            <div class="flex size-16 -rotate-3 items-center justify-center rounded-[1.4rem] shadow-lg transition group-hover:rotate-3 group-hover:scale-105" :class="colors.badge">
                <HugeiconsIcon :icon="iconComponent" :size="28" color="white" :stroke-width="1.5" />
            </div>

            <span v-if="comingSoon" class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                {{ $t('Coming soon') }}
            </span>
        </div>

        <div>
            <h3 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ name }}</h3>
            <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">{{ description }}</p>
        </div>

        <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold" :class="colors.chip">
            {{ playersLabel }}
        </span>
    </component>
</template>
