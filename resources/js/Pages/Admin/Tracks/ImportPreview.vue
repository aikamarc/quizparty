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
const answers = reactive(Object.fromEntries(props.results.map((result, index) => [index, result.custom_answer ?? ''])));

const selectedCount = computed(() => Object.values(selected).filter(Boolean).length);

const form = useForm({
    tracks: [],
});

const confirmImport = () => {
    form.transform(() => ({
        tracks: props.results
            .map((result, index) => (result.match && selected[index] ? {
                ...result.match,
                answer_mode: result.answer_mode,
                custom_answer: result.answer_mode === 'title_only' ? answers[index] : null,
            } : null))
            .filter(Boolean),
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

        <div class="mx-auto max-w-3xl space-y-4 px-4 py-10 sm:px-6 lg:px-8">
            <div v-if="categories.length" class="overflow-hidden bg-white p-4 shadow sm:rounded-lg dark:bg-gray-800">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ $t('Categories for this import') }}</h3>

                <CategoryPicker v-model="selectedCategoryIds" :categories="categories" class="mt-3" />
            </div>

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white/90 shadow-xl shadow-violet-950/5 dark:border-white/10 dark:bg-gray-800/90">
                <div class="flex items-center justify-between border-b border-violet-100 px-5 py-4 dark:border-white/10">
                    <h3 class="font-black text-slate-900 dark:text-white">{{ $t('Tracks to import') }}</h3>
                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">{{ selectedCount }} / {{ results.length }}</span>
                </div>

                <div class="max-h-[32rem] divide-y divide-violet-100 overflow-y-auto overscroll-contain dark:divide-white/10">
                    <div
                        v-for="(result, index) in results"
                        :key="index"
                        class="flex items-center gap-4 p-4 transition hover:bg-violet-50/50 dark:hover:bg-white/[.03]"
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
                                <label v-if="result.answer_mode === 'title_only'" class="mt-3 block text-xs font-black text-violet-600 dark:text-violet-300">{{ $t('Answer to find') }}<input v-model="answers[index]" required class="mt-1 block w-full rounded-xl border-gray-200 px-3 py-2 text-sm font-bold text-slate-900 dark:border-white/10 dark:bg-white/5 dark:text-white"></label>
                                <audio :src="result.match.preview_url" controls preload="none" class="mt-1 h-8 w-full max-w-[260px]" />
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
                </div>
            </section>

            <div class="sticky bottom-4 z-20 flex items-center justify-between rounded-2xl border border-violet-100 bg-white/95 p-4 shadow-2xl backdrop-blur-xl dark:border-white/10 dark:bg-[#171329]/95">
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
