<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CategoryPicker from '@/Components/CategoryPicker.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    track: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const selectedCategoryIds = ref(props.track.categories.map((category) => category.id));

const form = useForm({
    answer_mode: props.track.answer_mode ?? 'artist_title',
    custom_answer: props.track.custom_answer ?? '',
});

const submit = () => {
    form.transform(() => ({
        category_ids: selectedCategoryIds.value,
        answer_mode: form.answer_mode,
        custom_answer: form.answer_mode === 'title_only' ? form.custom_answer : null,
    })).put(route('admin.tracks.update', props.track.id));
};
</script>

<template>
    <AppLayout :title="$t('Edit a track')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ $t('Edit a track') }}
            </h2>
        </template>

        <div class="mx-auto max-w-2xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <img
                    v-if="track.cover_url"
                    :src="track.cover_url"
                    :alt="track.title"
                    class="size-16 shrink-0 rounded-md object-cover"
                >
                <div>
                    <div class="font-bold text-gray-900 dark:text-white">{{ track.title }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ track.artist }}</div>
                    <audio :src="track.preview_url" controls class="mt-2 h-9" />
                </div>
            </div>

            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Expected answer') }}</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="rounded-2xl border p-4"><input v-model="form.answer_mode" type="radio" value="artist_title" class="me-2 text-violet-600"><span class="font-black">{{ $t('Artist and title') }}</span></label><label class="rounded-2xl border p-4"><input v-model="form.answer_mode" type="radio" value="title_only" class="me-2 text-violet-600"><span class="font-black">{{ $t('Custom answer only') }}</span></label></div>
                <label v-if="form.answer_mode === 'title_only'" class="mt-4 block text-sm font-black">{{ $t('Answer to find') }}<input v-model="form.custom_answer" required class="mt-2 block w-full rounded-2xl border-gray-200 px-4 py-3 dark:border-white/10 dark:bg-white/5"><span v-if="form.errors.custom_answer" class="mt-1 block text-xs text-rose-500">{{ form.errors.custom_answer }}</span></label>
            </div>

            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Categories') }}</h3>

                <p v-if="! categories.length" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('No categories exist yet.') }}
                </p>

                <CategoryPicker v-else v-model="selectedCategoryIds" :categories="categories" class="mt-4" />

                <div class="mt-6 flex justify-end">
                    <PrimaryButton :disabled="form.processing" @click="submit">{{ $t('Save') }}</PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
