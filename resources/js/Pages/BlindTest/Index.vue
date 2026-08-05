<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Door01Icon, HeadphonesIcon, MusicNote01Icon, Settings01Icon } from '@hugeicons/core-free-icons';

defineProps({ rooms: { type: Array, required: true }, publicRooms: { type: Array, required: true } });

const isPublic = ref(false);
const joinCode = ref('');
const createRoom = () => router.post(route('blindtest.rooms.store'), { is_public: isPublic.value });
const joinByCode = () => {
    if (joinCode.value.trim()) router.post(route('blindtest.rooms.resolve-code'), { code: joinCode.value.trim().toUpperCase() });
};
const statusLabel = (status) => (status === 'lobby' ? trans('Waiting') : trans('In progress'));
let roomsRefreshTimer;
onMounted(() => { roomsRefreshTimer = setInterval(() => router.reload({ only: ['rooms', 'publicRooms'], preserveScroll: true }), 15000); });
onUnmounted(() => clearInterval(roomsRefreshTimer));
</script>

<template>
    <AppLayout title="Blind Test">
        <main class="qp-shell py-8 sm:py-12">
            <section class="relative overflow-hidden rounded-[2.25rem] bg-violet-600 p-7 text-white shadow-[0_12px_0_#5b21b6] sm:p-10">
                <HugeiconsIcon :icon="MusicNote01Icon" :size="180" class="absolute -right-8 -top-12 rotate-12 text-white/10" aria-hidden="true" />
                <div class="relative max-w-2xl">
                    <div class="text-xs font-black uppercase tracking-[.22em] text-violet-200">{{ $t('Music arena') }}</div>
                    <h1 class="mt-3 text-5xl font-black tracking-[-.055em] sm:text-6xl">Blind Test</h1>
                    <p class="mt-4 max-w-xl text-lg font-bold leading-relaxed text-violet-100/75">{{ $t('Recognize the song before your friends and take the top spot.') }}</p>
                </div>
            </section>

            <section class="mt-9 grid gap-6 lg:grid-cols-2">
                <div class="qp-card sm:p-8">
                    <div class="flex items-start justify-between gap-4"><div><HugeiconsIcon :icon="Settings01Icon" :size="40" class="text-violet-600 dark:text-violet-300" :stroke-width="1.7" /><h2 class="mt-4 text-2xl font-black">{{ $t('Host a game') }}</h2><p class="qp-muted mt-2 max-w-md">{{ $t('Create the room, choose the playlist and invite your crew.') }}</p></div><span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">{{ $t('Host') }}</span></div>
                    <label class="mt-6 flex cursor-pointer items-center gap-3 rounded-2xl bg-violet-50 p-4 text-sm font-black dark:bg-white/5"><input v-model="isPublic" type="checkbox" class="size-5 rounded-lg border-0 text-violet-600 focus:ring-violet-500">{{ $t('Open room to everyone') }}</label>
                    <button class="mt-5 w-full rounded-2xl bg-violet-600 px-5 py-4 text-sm font-black text-white shadow-[0_5px_0_#5b21b6] transition active:translate-y-1 active:shadow-none" @click="createRoom">＋ {{ $t('Create the room') }}</button>
                </div>

                <div class="qp-card border-violet-400/30 bg-violet-950/[0.92] text-white sm:p-8">
                    <HugeiconsIcon :icon="Door01Icon" :size="40" class="text-violet-200" :stroke-width="1.7" /><h2 class="mt-4 text-2xl font-black">{{ $t('Join the party') }}</h2><p class="mt-2 text-sm font-bold text-white/55">{{ $t('Enter the 6-character code shared by the host.') }}</p>
                    <form class="mt-8" @submit.prevent="joinByCode"><TextInput v-model="joinCode" maxlength="6" placeholder="A3F9K2" class="block w-full text-center text-2xl font-black uppercase tracking-[.25em] text-slate-950" /><button type="submit" class="mt-5 w-full rounded-2xl bg-violet-500 px-5 py-4 text-sm font-black text-white shadow-[0_5px_0_#5b21b6] transition active:translate-y-1 active:shadow-none">{{ $t('Enter the room') }} →</button></form>
                </div>
            </section>

            <section v-if="rooms.length" class="mt-12">
                <div class="qp-kicker">{{ $t('Continue playing') }}</div><h2 class="qp-title mt-2">{{ $t('Your rooms') }}</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="room in rooms" :key="room.id" :href="route('blindtest.rooms.show', room.public_id)" class="group qp-card flex items-center gap-4 transition hover:-translate-y-1 hover:border-violet-300"><span class="flex size-14 shrink-0 -rotate-3 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 transition group-hover:rotate-3 dark:bg-violet-500/20 dark:text-violet-200"><HugeiconsIcon :icon="HeadphonesIcon" :size="27" :stroke-width="1.8" /></span><div class="min-w-0"><div class="font-black text-slate-950 dark:text-white">Blind Test</div><div class="mt-1 truncate text-xs font-bold text-slate-400">{{ room.host.name }} · {{ room.players_count }} {{ $t('players') }}</div><span class="mt-2 inline-block text-xs font-black text-violet-600 dark:text-violet-300">{{ statusLabel(room.status) }} →</span></div></Link>
                </div>
            </section>

            <section v-if="publicRooms.length" class="mt-12">
                <div class="flex items-end justify-between"><div><div class="qp-kicker">{{ $t('Open now') }}</div><h2 class="qp-title mt-2">{{ $t('Public rooms') }}</h2></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">● {{ $t('Live') }}</span></div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><Link v-for="room in publicRooms" :key="room.id" :href="route('blindtest.rooms.show', room.public_id)" class="qp-card group"><div class="flex items-center justify-between"><HugeiconsIcon :icon="MusicNote01Icon" :size="26" class="text-violet-500" :stroke-width="1.8" /><span class="text-xs font-black text-violet-600 dark:text-violet-300">{{ $t('Join') }} →</span></div><div class="mt-5 text-xl font-black">Blind Test</div><div class="mt-1 text-sm font-bold text-slate-400">{{ room.host.name }} · {{ room.players_count }} {{ $t('players') }}</div></Link></div>
            </section>
        </main>
    </AppLayout>
</template>
