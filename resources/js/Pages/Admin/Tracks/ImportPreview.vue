<script setup>
import { computed, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HelpCircleIcon } from '@hugeicons/core-free-icons';
import AppLayout from '@/Layouts/AppLayout.vue';
import CategoryPicker from '@/Components/CategoryPicker.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    results: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const selected = reactive(
    Object.fromEntries(props.results.map((result, index) => [index, !!result.match]))
);

const selectedCategoryIds = ref([]);

const selectedCount = computed(() => Object.values(selected).filter(Boolean).length);

const form = useForm({
    tracks: [],
});

const confirmImport = () => {
    form.transform(() => ({
        tracks: props.results
            .filter((result, index) => result.match && selected[index])
            .map((result) => result.match),
        category_ids: selectedCategoryIds.value,
    })).post(route('admin.tracks.import.store'));
};
</script>

<template>
    <AppLayout :title="$t('Review import')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ $t('Review before importing') }}
            </h2>
        </template>

        <div class="mx-auto max-w-2xl space-y-4 px-4 py-10 sm:px-6 lg:px-8">
            <div v-if="categories.length" class="overflow-hidden bg-white p-4 shadow sm:rounded-lg dark:bg-gray-800">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ $t('Categories for this import') }}</h3>

                <CategoryPicker v-model="selectedCategoryIds" :categories="categories" class="mt-3" />
            </div>

            <div
                v-for="(result, index) in results"
                :key="index"
                class="flex items-center gap-4 rounded-lg bg-white p-4 shadow sm:rounded-lg dark:bg-gray-800"
            >
                <template v-if="result.match">
                    <input v-model="selected[index]" type="checkbox" class="size-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900">

                    <img
                        v-if="result.match.cover_url"
                        :src="result.match.cover_url"
                        :alt="result.match.title"
                        class="size-14 shrink-0 rounded-md object-cover"
                    >

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs text-gray-400 dark:text-gray-500">« {{ result.input }} »</div>
                        <div class="truncate font-medium text-gray-900 dark:text-white">{{ result.match.title }}</div>
                        <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ result.match.artist }}</div>
                        <audio :src="result.match.preview_url" controls class="mt-1 h-8 w-full max-w-[260px]" />
                    </div>
                </template>

                <template v-else>
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700">
                        <HugeiconsIcon :icon="HelpCircleIcon" :size="24" class="text-gray-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs text-gray-400 dark:text-gray-500">« {{ result.input }} »</div>
                        <div class="text-sm font-medium text-red-600 dark:text-red-400">{{ $t('No result found — this track will be skipped.') }}</div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t(':selected of :total track(s) selected.', { selected: selectedCount, total: results.length }) }}
                </p>

                <PrimaryButton :disabled="form.processing || selectedCount === 0" @click="confirmImport">
                    {{ $t('Confirm import') }}
                </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>
