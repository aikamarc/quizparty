<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';

const page = usePage();

const labels = {
    en: 'EN',
    fr: 'FR',
};

const switchLocale = async (locale) => {
    if (locale === page.props.locale) return;

    await loadLanguageAsync(locale);
    document.documentElement.lang = locale;

    router.get(route('locale.switch', locale), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div class="inline-flex items-center rounded-full bg-gray-100 p-1 text-xs font-semibold dark:bg-gray-800">
        <button
            v-for="locale in page.props.availableLocales"
            :key="locale"
            type="button"
            class="rounded-full px-2.5 py-1 transition"
            :class="locale === page.props.locale
                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            @click="switchLocale(locale)"
        >
            {{ labels[locale] }}
        </button>
    </div>
</template>
