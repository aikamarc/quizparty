<script setup>
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { SparklesIcon, WavingHand01Icon } from '@hugeicons/core-free-icons';

defineProps({ friends: { type: Array, required: true }, receivedRequests: { type: Array, required: true }, sentRequests: { type: Array, required: true } });
const form = useForm({ email: '' });
const sendRequest = () => form.post(route('friends.store'), { preserveScroll: true, onSuccess: () => form.reset() });
const respond = (id, action) => router.patch(route('friends.update', id), { action }, { preserveScroll: true });
const remove = (id) => router.delete(route('friends.destroy', id), { preserveScroll: true });
</script>

<template>
    <AppLayout :title="$t('Friends')">
        <main class="qp-shell py-8 sm:py-12">
            <section class="relative overflow-hidden rounded-[2.25rem] bg-slate-950 p-7 text-white shadow-[0_12px_0_rgb(15_23_42/0.14)] sm:p-10">
                <HugeiconsIcon :icon="SparklesIcon" :size="160" class="absolute -right-6 -top-12 opacity-10" aria-hidden="true" />
                <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                    <div><div class="text-xs font-black uppercase tracking-[.22em] text-rose-300">{{ $t('Your crew') }}</div><h1 class="mt-3 text-5xl font-black tracking-[-.055em] sm:text-6xl">{{ $t('Friends') }}</h1><p class="mt-3 max-w-lg font-bold text-white/55">{{ $t('Find your teammates, build your crew and challenge them.') }}</p></div>
                    <form class="flex w-full max-w-xl flex-col gap-3 rounded-[1.5rem] bg-white/10 p-3 ring-1 ring-white/15 sm:flex-row" @submit.prevent="sendRequest"><TextInput v-model="form.email" type="email" :placeholder="$t(`Friend's email address`)" class="min-w-0 flex-1 border-0 bg-white text-slate-950" required /><button class="rounded-2xl bg-violet-500 px-5 py-3 text-sm font-black text-white shadow-[0_4px_0_#5b21b6] active:translate-y-1 active:shadow-none" :disabled="form.processing">＋ {{ $t('Add') }}</button></form>
                </div>
                <InputError :message="form.errors.email" class="relative mt-3 lg:text-right" />
            </section>

            <section v-if="receivedRequests.length" class="qp-card mt-9 border-cyan-300/40">
                <div class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-2xl bg-cyan-400 text-cyan-950">!</span><div><div class="text-xs font-black uppercase tracking-wider text-cyan-600 dark:text-cyan-300">{{ $t('New teammates') }}</div><h2 class="text-xl font-black">{{ $t('Friend requests') }}</h2></div></div>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <div v-for="request in receivedRequests" :key="request.id" class="flex flex-col gap-4 rounded-2xl bg-violet-50/80 p-4 dark:bg-white/5 sm:flex-row sm:items-center">
                        <Link :href="route('players.show', request.user.id)" class="flex min-w-0 flex-1 items-center gap-3"><img :src="request.user.profile_photo_url" :alt="request.user.name" class="size-12 rounded-2xl object-cover ring-2 ring-white"><div class="min-w-0"><div class="truncate font-black">{{ request.user.name }}</div><div class="truncate text-xs font-bold text-amber-900/50">{{ request.user.email }}</div></div></Link>
                        <div class="flex gap-2"><button class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white" @click="respond(request.id, 'accept')">✓ {{ $t('Accept') }}</button><button class="rounded-xl bg-white/60 px-3 py-2 text-xs font-black dark:bg-white/10" @click="respond(request.id, 'decline')">×</button></div>
                    </div>
                </div>
            </section>

            <section class="mt-12">
                <div class="flex items-end justify-between gap-4"><div><div class="qp-kicker">{{ $t('Ready to play') }}</div><h2 class="qp-title mt-2">{{ $t('My crew') }}</h2></div><div class="rounded-full bg-violet-100 px-4 py-2 text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">{{ friends.length }} {{ $t('friends') }}</div></div>
                <div v-if="friends.length" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="(friend, index) in friends" :key="friend.id" class="group qp-card relative overflow-hidden transition hover:-translate-y-1">
                        <div class="absolute right-4 top-4 text-4xl font-black text-slate-100 dark:text-white/5">{{ String(index + 1).padStart(2, '0') }}</div>
                        <Link :href="route('players.show', friend.id)" class="relative flex items-center gap-4"><img :src="friend.profile_photo_url" :alt="friend.name" class="size-16 -rotate-2 rounded-[1.35rem] object-cover ring-4 ring-violet-100 transition group-hover:rotate-2 dark:ring-violet-500/20"><div class="min-w-0"><h3 class="truncate text-lg font-black text-slate-950 dark:text-white">{{ friend.name }}</h3><div class="mt-1 text-xs font-black text-emerald-500">● {{ $t('In your crew') }}</div></div></Link>
                        <div class="relative mt-5 flex gap-2"><Link :href="route('players.show', friend.id)" class="flex-1 rounded-xl bg-violet-100 px-3 py-2 text-center text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">{{ $t('View profile') }}</Link><button class="rounded-xl px-3 py-2 text-xs font-black text-slate-400 hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10" @click="remove(friend.friendship_id)">×</button></div>
                    </article>
                </div>
                <div v-else class="mt-6 rounded-[2rem] border-2 border-dashed border-violet-200 p-12 text-center dark:border-violet-500/20"><HugeiconsIcon :icon="WavingHand01Icon" :size="52" class="mx-auto text-violet-500" :stroke-width="1.7" /><h3 class="mt-4 text-xl font-black">{{ $t('Your crew is waiting') }}</h3><p class="qp-muted mt-2">{{ $t('Add a friend above to start playing together.') }}</p></div>
            </section>

            <section v-if="sentRequests.length" class="mt-12"><div class="qp-kicker">{{ $t('Invitations sent') }}</div><div class="mt-4 flex flex-wrap gap-3"><div v-for="request in sentRequests" :key="request.id" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5"><img :src="request.user.profile_photo_url" :alt="request.user.name" class="size-9 rounded-xl object-cover"><span class="text-sm font-black">{{ request.user.name }}</span><button class="text-xs font-black text-slate-400 hover:text-rose-500" @click="remove(request.id)">×</button></div></div></section>
        </main>
    </AppLayout>
</template>
