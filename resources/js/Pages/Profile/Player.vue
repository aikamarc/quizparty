<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Award01Icon, MusicNote01Icon, StarIcon } from '@hugeicons/core-free-icons';

defineProps({
    player: { type: Object, required: true },
    isOwnProfile: { type: Boolean, default: false },
    isFriend: { type: Boolean, default: false },
});
</script>

<template>
    <AppLayout :title="player.name">
        <main class="qp-shell py-8 sm:py-12">
            <section class="relative overflow-hidden rounded-[2.25rem] bg-slate-950 p-6 text-white shadow-[0_12px_0_rgb(15_23_42/0.14)] sm:p-10">
                <div class="absolute -right-12 -top-16 size-52 rounded-full bg-violet-500/40 blur-3xl" />
                <div class="absolute bottom-0 left-1/3 size-40 rounded-full bg-cyan-400/15 blur-3xl" />

                <div class="relative flex flex-col items-center gap-6 text-center sm:flex-row sm:text-left">
                    <div class="relative shrink-0">
                        <div class="absolute -inset-2 rotate-6 rounded-[2rem] bg-violet-300" />
                        <img :src="player.profile_photo_url" :alt="player.name" class="relative size-28 -rotate-2 rounded-[1.7rem] object-cover ring-4 ring-white sm:size-32">
                        <span class="absolute -bottom-3 -right-3 flex size-11 rotate-6 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg"><HugeiconsIcon :icon="StarIcon" :size="22" :stroke-width="2" /></span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-black uppercase tracking-[.22em] text-violet-300">{{ isOwnProfile ? $t('Your player card') : $t('Player card') }}</div>
                        <h1 class="mt-2 truncate text-4xl font-black tracking-[-.05em] sm:text-5xl">{{ player.name }}</h1>
                        <p class="mt-2 font-bold text-white/55">{{ $t('QuizParty player since') }} {{ player.joined_at }}</p>
                    </div>

                    <Link v-if="isOwnProfile" :href="route('profile.settings')" class="rounded-2xl bg-white/10 px-5 py-3 text-sm font-black ring-1 ring-white/20 transition hover:bg-white/20">
                        ⚙ {{ $t('Settings') }}
                    </Link>
                    <span v-else-if="isFriend" class="rounded-2xl bg-emerald-400/15 px-5 py-3 text-sm font-black text-emerald-300 ring-1 ring-emerald-300/20">
                        ✓ {{ $t('In your crew') }}
                    </span>
                </div>
            </section>

            <section class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="qp-card text-center">
                    <div class="text-3xl font-black text-violet-600 dark:text-violet-300">{{ player.games_count }}</div>
                    <div class="mt-1 text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Games') }}</div>
                </div>
                <div class="qp-card text-center">
                    <div class="text-3xl font-black text-violet-500">{{ player.best_score }}</div>
                    <div class="mt-1 text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Best score') }}</div>
                </div>
                <div class="qp-card text-center">
                    <div class="text-3xl font-black text-cyan-500">{{ player.total_score }}</div>
                    <div class="mt-1 text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Total points') }}</div>
                </div>
                <div class="qp-card text-center">
                    <div class="text-3xl font-black text-violet-400">{{ player.friends_count }}</div>
                    <div class="mt-1 text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Friends') }}</div>
                </div>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-[1.35fr_.65fr]">
                <div class="qp-card relative overflow-hidden">
                    <div class="relative">
                        <div class="qp-kicker">{{ $t('Favorite arena') }}</div>
                        <h2 class="qp-title mt-2">Blind Test</h2>
                        <p class="qp-muted mt-2 max-w-lg">{{ $t('Recognize the track, buzz before everyone else, and climb the scoreboard.') }}</p>
                        <Link :href="route('blindtest.index')" class="mt-6 inline-flex rounded-2xl bg-violet-600 px-5 py-3 text-sm font-black text-white shadow-[0_5px_0_#5b21b6] active:translate-y-1 active:shadow-none">
                            <HugeiconsIcon :icon="MusicNote01Icon" :size="18" /> {{ $t('Play now') }}
                        </Link>
                    </div>
                    <HugeiconsIcon :icon="MusicNote01Icon" :size="110" class="absolute -bottom-10 -right-7 rotate-12 opacity-10" aria-hidden="true" />
                </div>

                <div class="rounded-[1.75rem] border border-cyan-300/30 bg-cyan-100/70 p-6 text-cyan-950 shadow-[0_8px_30px_rgb(8_145_178/0.10)] backdrop-blur-xl dark:bg-cyan-400/10 dark:text-cyan-100">
                    <HugeiconsIcon :icon="Award01Icon" :size="42" :stroke-width="1.7" />
                    <h2 class="mt-4 text-xl font-black">{{ $t('Next badge') }}</h2>
                    <p class="mt-2 text-sm font-bold text-cyan-900/60 dark:text-cyan-100/60">{{ $t('Play more games to reveal new achievements.') }}</p>
                    <div class="mt-5 h-3 overflow-hidden rounded-full bg-cyan-950/10"><div class="h-full w-1/3 rounded-full bg-cyan-500" /></div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
