<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const showingMobileMenu = ref(false);

const liveRoomsCount = ref(page.props.blindTestRooms?.length ?? 0);

let userChannelName;

onMounted(() => {
    if (! user.value) return;

    userChannelName = `App.Models.User.${user.value.id}`;

    window.Echo.private(userChannelName).listen('.blindtest.invited', () => {
        liveRoomsCount.value += 1;
    });
});

onUnmounted(() => {
    if (userChannelName) {
        window.Echo.leave(userChannelName);
    }
});

const logout = () => router.post(route('logout'));
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-violet-200/50 bg-[#f7f5ff]/75 backdrop-blur-xl dark:border-white/10 dark:bg-[#0d0b18]/75">
        <div class="qp-shell flex h-[4.5rem] items-center justify-between">
            <div class="flex items-center gap-8">
                <Link :href="route('home')" class="group flex items-center gap-2.5">
                    <span class="size-10 overflow-hidden rounded-full shadow-[0_3px_0_#5b21b6] transition group-hover:rotate-3">
                        <ApplicationMark class="block size-full" />
                    </span>
                    <span class="text-xl font-black tracking-[-0.04em] text-slate-950 dark:text-white">
                        Quiz<span class="text-violet-600 dark:text-violet-400">Party!</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 sm:flex">
                    <NavLink :href="route('home')" :active="route().current('home')">{{ $t('Home') }}</NavLink>
                    <NavLink :href="route('friends.index')" :active="route().current('friends.index')">{{ $t('Friends') }}</NavLink>
                    <NavLink v-if="user" :href="route('blindtest.index')" :active="route().current('blindtest.*')" class="relative">
                        Blind Test
                        <span v-if="liveRoomsCount > 0" class="absolute -end-3 -top-1 flex size-4 items-center justify-center rounded-full bg-pink-500 text-[10px] font-bold text-white">
                            {{ liveRoomsCount }}
                        </span>
                    </NavLink>
                    <NavLink v-if="user" :href="route('songless.index')" :active="route().current('songless.*')">Songless</NavLink>
                    <NavLink v-if="user?.is_admin" :href="route('admin.tracks.index')" :active="route().current('admin.*')">Admin</NavLink>
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <ThemeToggle />
                <LocaleSwitcher class="hidden sm:inline-flex" />

                <template v-if="user">
                    <Dropdown align="right" width="48" class="hidden sm:block">
                        <template #trigger>
                            <button type="button" class="ms-2 flex items-center gap-2 rounded-full border-2 border-transparent transition hover:border-violet-200 dark:hover:border-violet-800">
                                <img class="size-9 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.show')">{{ $t('Profile') }}</DropdownLink>
                            <form @submit.prevent="logout">
                                <DropdownLink as="button">{{ $t('Log out') }}</DropdownLink>
                            </form>
                        </template>
                    </Dropdown>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="hidden rounded-xl px-4 py-2 text-sm font-extrabold text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-white/10 sm:inline-flex">
                        {{ $t('Log in') }}
                    </Link>
                    <Link :href="route('register')" class="hidden rounded-xl bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-[0_4px_0_#5b21b6] transition active:translate-y-1 active:shadow-none sm:inline-flex">
                        {{ $t('Sign up') }}
                    </Link>
                </template>

                <button
                    type="button"
                    class="ms-1 flex size-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 sm:hidden"
                    @click="showingMobileMenu = !showingMobileMenu"
                >
                    <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path v-if="!showingMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-show="showingMobileMenu" class="border-t border-violet-200 bg-[#f7f5ff]/95 py-2 backdrop-blur-xl dark:border-white/10 dark:bg-[#0d0b18]/95 sm:hidden">
            <div class="space-y-1 py-2">
                <ResponsiveNavLink :href="route('home')" :active="route().current('home')">{{ $t('Home') }}</ResponsiveNavLink>
                <ResponsiveNavLink :href="route('friends.index')" :active="route().current('friends.index')">{{ $t('Friends') }}</ResponsiveNavLink>
                <ResponsiveNavLink v-if="user" :href="route('blindtest.index')" :active="route().current('blindtest.*')">
                    Blind Test <span v-if="liveRoomsCount > 0">({{ liveRoomsCount }})</span>
                </ResponsiveNavLink>
                <ResponsiveNavLink v-if="user" :href="route('songless.index')" :active="route().current('songless.*')">Songless</ResponsiveNavLink>
                <ResponsiveNavLink v-if="user?.is_admin" :href="route('admin.tracks.index')" :active="route().current('admin.*')">Admin</ResponsiveNavLink>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                <LocaleSwitcher />

                <template v-if="user">
                    <div class="flex items-center gap-3">
                        <Link :href="route('profile.show')" class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $t('Profile') }}</Link>
                        <button type="button" class="text-sm font-semibold text-gray-600 dark:text-gray-300" @click="logout">{{ $t('Log out') }}</button>
                    </div>
                </template>
                <template v-else>
                    <div class="flex items-center gap-3">
                        <Link :href="route('login')" class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $t('Log in') }}</Link>
                        <Link :href="route('register')" class="rounded-full bg-violet-600 px-3 py-1.5 text-sm font-semibold text-white">{{ $t('Sign up') }}</Link>
                    </div>
                </template>
            </div>
        </div>
    </header>
</template>
