<script setup>
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import SiteHeader from '@/Components/SiteHeader.vue';
import PageBackdrop from '@/Components/Backgrounds/PageBackdrop.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div class="relative isolate min-h-screen">
        <Head :title="title" />
        <PageBackdrop />

        <Banner />

        <div class="min-h-screen">
            <SiteHeader />

            <!-- Page Heading -->
            <header v-if="$slots.header" class="border-b border-violet-200/50 bg-white/35 backdrop-blur-xl dark:border-white/10 dark:bg-[#0d0b18]/35">
                <div class="qp-shell py-7">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
