<script setup>
import { onMounted, ref } from 'vue';

const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const toggle = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};
</script>

<template>
    <button
        type="button"
        class="flex size-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggle"
    >
        <svg v-if="isDark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m8.485-8.485h-1.5m-13.97 0H3.515m14.85-6.336l-1.06 1.06M6.696 17.303l-1.06 1.06m0-13.727l1.06 1.06m11.607 11.607l1.06 1.06M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
        </svg>
        <svg v-else class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
    </button>
</template>
