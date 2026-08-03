<script setup>
import PageBackdrop from '@/Components/Backgrounds/PageBackdrop.vue';
</script>

<template>
    <div class="relative isolate flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <PageBackdrop />
        <div>
            <slot name="logo" />
        </div>

        <div class="qp-card mt-7 w-full sm:max-w-md sm:p-8">
            <slot />
        </div>
    </div>
</template>
