<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Aurora from '@/Components/Aurora.vue';
import GameCard from '@/Components/GameCard.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import PageBackdrop from '@/Components/Backgrounds/PageBackdrop.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HeadphonesIcon, MusicNote01Icon, StarIcon } from '@hugeicons/core-free-icons';

defineProps({
    games: { type: Array, required: true },
    publicRooms: { type: Array, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roomCode = ref('');
const joiningByCode = ref(false);
const joinByCode = () => {
    const code = roomCode.value.trim().toUpperCase();
    if (code.length !== 6 || joiningByCode.value) return;
    joiningByCode.value = true;
    router.post(route('blindtest.rooms.resolve-code'), { code }, {
        preserveScroll: true,
        onFinish: () => { joiningByCode.value = false; },
    });
};
</script>

<template>
    <Head title="QuizParty" />

    <div class="relative isolate min-h-screen">
        <PageBackdrop />
        <SiteHeader />

        <section class="relative overflow-hidden border-b border-slate-200/70 dark:border-white/10">
            <div class="pointer-events-none absolute -right-16 top-4 size-72 rounded-full bg-violet-400/20 blur-3xl dark:bg-violet-500/10" />

            <div class="qp-shell relative grid items-center gap-12 py-20 lg:grid-cols-[1fr_.72fr] lg:py-28">
                <div class="text-center lg:text-left">
                    <div class="qp-kicker mb-5">{{ $t('Pick a game. Gather your crew.') }}</div>
                <h1 class="max-w-3xl text-5xl font-black leading-[.98] tracking-[-0.055em] text-slate-950 dark:text-white sm:text-7xl">
                    {{ $t('Mini-games to play with friends.') }}
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-lg font-semibold leading-relaxed text-slate-500 dark:text-slate-400 lg:mx-0">
                    {{ $t('Create a room, invite your friends, and let the best player win.') }}
                </p>

                <div v-if="!user" class="mt-9 flex flex-col items-center justify-center gap-4 sm:flex-row lg:justify-start">
                    <Link
                        v-if="!user"
                        :href="route('register')"
                        class="w-full rounded-2xl border-2 border-slate-200 bg-white px-8 py-3.5 text-sm font-black text-slate-700 transition hover:border-violet-300 dark:border-white/15 dark:bg-white/10 dark:text-white sm:w-auto"
                    >
                        {{ $t('Sign up') }}
                    </Link>
                </div></div>

                <div class="relative mx-auto hidden h-80 w-80 lg:block" aria-hidden="true">
                    <div class="qp-float absolute left-7 top-7 flex size-52 items-center justify-center rounded-[3.5rem] bg-violet-600 text-8xl shadow-[0_14px_0_#5b21b6]">?</div>
                    <div class="absolute bottom-5 right-2 flex size-32 rotate-12 items-center justify-center rounded-[2.5rem] bg-violet-300 text-violet-950 shadow-[0_10px_0_#7c3aed]"><HugeiconsIcon :icon="MusicNote01Icon" :size="52" :stroke-width="1.8" /></div>
                    <div class="absolute bottom-10 left-0 flex size-20 -rotate-12 items-center justify-center rounded-3xl bg-cyan-300 text-cyan-950 shadow-[0_7px_0_#0891b2]"><HugeiconsIcon :icon="StarIcon" :size="32" :stroke-width="1.8" /></div>
                </div>
            </div>
        </section>

        <main id="games" class="qp-shell py-16 sm:py-20">
            <div class="mb-8">
                <div class="qp-kicker">{{ $t('Games') }}</div>
                <h2 class="qp-title mt-2">{{ $t('Choose your next challenge') }}</h2>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <GameCard
                    v-for="game in games"
                    :key="game.slug"
                    :slug="game.slug"
                    :color="game.color"
                    :icon="game.icon"
                    :players="game.players"
                    :coming-soon="game.status === 'coming_soon'"
                    :href="game.route ? route(game.route) : null"
                />
            </div>

            <section class="mt-16 rounded-[2rem] border border-violet-200 bg-violet-950 p-6 text-white shadow-xl sm:flex sm:items-center sm:justify-between sm:gap-8 sm:p-8 dark:border-violet-400/20">
                <div><div class="text-xs font-black uppercase tracking-[.18em] text-violet-300">{{ $t('Private room') }}</div><h2 class="mt-2 text-2xl font-black">{{ $t('Join with a 6-character code') }}</h2><p class="mt-2 text-sm font-bold text-white/55">{{ $t('Enter the code shared by the host.') }}</p></div>
                <form class="mt-6 w-full sm:mt-0 sm:max-w-md" @submit.prevent="joinByCode">
                    <div class="flex gap-2"><input v-model="roomCode" type="text" maxlength="6" autocomplete="off" class="min-w-0 flex-1 rounded-2xl border-white/15 bg-white px-4 py-3 text-center text-lg font-black uppercase tracking-[.2em] text-slate-950 focus:border-violet-400 focus:ring-violet-400" placeholder="A3F9K2"><button type="submit" class="rounded-2xl bg-violet-500 px-5 py-3 text-sm font-black text-white transition hover:bg-violet-400 disabled:opacity-40" :disabled="roomCode.trim().length !== 6 || joiningByCode">{{ $t('Join') }}</button></div>
                    <p v-if="page.props.errors?.code" class="mt-2 text-sm font-bold text-rose-300">{{ page.props.errors.code }}</p>
                </form>
            </section>

            <section v-if="publicRooms.length" class="mt-16 border-t border-violet-100 pt-12 dark:border-white/10">
                <div class="mb-8"><div class="qp-kicker">{{ $t('Open now') }}</div><h2 class="qp-title mt-2">{{ $t('Public rooms') }}</h2></div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="room in publicRooms" :key="room.id" class="qp-card flex items-center gap-4 transition hover:-translate-y-1 hover:border-violet-300">
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300"><HugeiconsIcon :icon="HeadphonesIcon" :size="27" /></span>
                        <div class="min-w-0 flex-1"><h3 class="font-black text-slate-950 dark:text-white">Blind Test</h3><p class="mt-1 text-sm font-bold text-slate-400">{{ $t(':count player(s)', { count: room.players_count }) }}</p></div>
                        <Link :href="route('blindtest.rooms.show', room.public_id)" class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-black text-white transition hover:bg-violet-500">{{ $t('Join') }}</Link>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
