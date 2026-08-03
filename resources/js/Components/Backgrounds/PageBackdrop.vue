<script setup>
import LightRays from './LightRays.vue';
</script>

<template>
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-[#f7f5ff] dark:bg-[#0d0b18]" aria-hidden="true">
        <LightRays rays-origin="top-center" rays-color="#8b5cf6" :rays-speed="0.55" :light-spread="0.75" :ray-length="2.4" :fade-distance="1.2" :saturation="0.75" :mouse-influence="0.06" :noise-amount="0.025" :distortion="0.018" class="opacity-[0.16] dark:opacity-[0.42]" />
        <div class="absolute inset-0 bg-gradient-to-b from-white/25 via-white/65 to-[#f7f5ff] dark:from-[#0d0b18]/5 dark:via-[#0d0b18]/50 dark:to-[#0d0b18]" />
    </div>
</template>
