<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    list: '',
});

const submit = () => {
    form.post(route('admin.tracks.import.preview'));
};
</script>

<template>
    <AppLayout :title="$t('Import tracks')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ $t('Import tracks') }}
            </h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('Enter one track per line, for example') }} <span class="font-mono">Daft Punk - One More Time</span>.
                    {{ $t('Tracks are searched automatically via Deezer. You can review every result before importing.') }}
                </p>

                <form class="mt-6" @submit.prevent="submit">
                    <InputLabel for="list" :value="$t('Track list')" />
                    <textarea
                        id="list"
                        v-model="form.list"
                        rows="10"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        placeholder="Daft Punk - One More Time&#10;Queen - Bohemian Rhapsody&#10;..."
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
