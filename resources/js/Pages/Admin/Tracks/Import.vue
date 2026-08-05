<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    category: { type: Object, default: null },
});

const form = useForm({
    list: '',
    answer_mode: props.category?.answer_mode ?? 'artist_title',
});

const submit = () => {
    form.post(props.category
        ? route('admin.categories.import.preview', props.category.id)
        : route('admin.tracks.import.preview'));
};
</script>

<template>
    <AppLayout :title="$t('Import tracks')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ $t('Import tracks') }}<span v-if="category"> · {{ category.name }}</span>
            </h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('Enter one track per line, for example') }} <span class="font-mono">Daft Punk || One More Time</span>.
                    {{ $t('Tracks are searched automatically via Deezer. You can review every result before importing.') }}
                </p>

                <form class="mt-6" @submit.prevent="submit">
                    <div v-if="!category" class="mb-6 grid gap-3 sm:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.answer_mode === 'artist_title' ? 'border-violet-500 bg-violet-50 dark:bg-violet-500/10' : 'border-gray-200 dark:border-gray-700'"><input v-model="form.answer_mode" type="radio" value="artist_title" class="me-2 text-violet-600"><span class="font-black">{{ $t('Artist and title') }}</span><p class="mt-1 text-xs text-gray-500">{{ $t('Players must find the artist and the song title.') }}</p></label>
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.answer_mode === 'title_only' ? 'border-violet-500 bg-violet-50 dark:bg-violet-500/10' : 'border-gray-200 dark:border-gray-700'"><input v-model="form.answer_mode" type="radio" value="title_only" class="me-2 text-violet-600"><span class="font-black">{{ $t('Custom answer only') }}</span><p class="mt-1 text-xs text-gray-500">{{ $t('Use the format: answer || track title || artist (optional).') }}</p></label>
                    </div>
                    <div v-else class="mb-6 rounded-2xl bg-violet-50 p-4 text-sm font-bold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                        {{ category.answer_mode === 'title_only' ? $t('Custom format: answer || track title || artist (optional).') : $t('Classic format: artist || track title.') }}
                    </div>
                    <InputLabel for="list" :value="$t('Track list')" />
                    <textarea
                        id="list"
                        v-model="form.list"
                        rows="10"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        :placeholder="form.answer_mode === 'title_only' ? 'Toy Story || You’ve Got a Friend in Me || Randy Newman\nToy Story || You’ve Got a Friend in Me' : 'Daft Punk || One More Time\nQueen || Bohemian Rhapsody'"
                    />
                    <InputError :message="form.errors.list" class="mt-2" />

                    <div class="mt-4 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            {{ $t('Search') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
