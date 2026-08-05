<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="mt-3 flex gap-4 border-b border-gray-200 text-sm font-medium dark:border-gray-700">
        <Link
            :href="route('admin.culture-quiz.index')"
            class="border-b-2 pb-2"
            :class="route().current('admin.culture-quiz.*') ? 'border-violet-600 text-violet-600' : 'border-transparent text-gray-500'"
        >
            Culture Quiz
        </Link>
        <Link
            :href="route('admin.tracks.index')"
            class="border-b-2 pb-2"
            :class="route().current('admin.tracks.*')
                ? 'border-violet-600 text-violet-600 dark:text-violet-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
        >
            Musiques
        </Link>
        <Link
            :href="route('admin.categories.index')"
            class="border-b-2 pb-2"
            :class="route().current('admin.categories.*')
                ? 'border-violet-600 text-violet-600 dark:text-violet-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
        >
            Catégories
        </Link>
    </div>
</template>
