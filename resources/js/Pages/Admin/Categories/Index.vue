<script setup>
import { router, useForm, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminTabs from '@/Components/Admin/AdminTabs.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({ categories: { type: Array, required: true } });

const form = useForm({
    name: '',
    answer_mode: 'artist_title',
    image: null,
});

const addCategory = () => {
    form.post(route('admin.categories.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const removeCategory = (category) => {
    if (confirm(trans('Delete this category and its tracks association?'))) {
        router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :title="$t('Categories')">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $t('Categories') }}</h2>
            <AdminTabs />
        </template>

        <div class="qp-shell space-y-8 py-10">
            <section class="rounded-3xl border border-violet-100 bg-white/90 p-6 shadow-xl shadow-violet-950/5 dark:border-white/10 dark:bg-gray-800/90">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $t('New category') }}</h3>
                <form class="mt-5 space-y-5" @submit.prevent="addCategory">
                    <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
                        <div>
                            <TextInput v-model="form.name" :placeholder="$t('Example: Movies')" class="block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <input type="file" accept="image/*" class="block w-full text-sm font-bold text-slate-500 file:me-3 file:rounded-xl file:border-0 file:bg-violet-100 file:px-4 file:py-2 file:font-black file:text-violet-700" @change="form.image = $event.target.files[0]" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.answer_mode === 'artist_title' ? 'border-violet-500 bg-violet-50 dark:bg-violet-500/10' : 'border-gray-200 dark:border-gray-700'">
                            <input v-model="form.answer_mode" type="radio" value="artist_title" class="me-2 text-violet-600">
                            <span class="font-black dark:text-white">{{ $t('Classic category') }}</span>
                            <p class="mt-1 text-xs text-gray-500">{{ $t('Players find the song title and artist.') }}</p>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.answer_mode === 'title_only' ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10' : 'border-gray-200 dark:border-gray-700'">
                            <input v-model="form.answer_mode" type="radio" value="title_only" class="me-2 text-amber-600">
                            <span class="font-black dark:text-white">{{ $t('Custom category') }}</span>
                            <p class="mt-1 text-xs text-gray-500">{{ $t('Players find one custom answer, such as a movie title.') }}</p>
                        </label>
                    </div>
                    <InputError :message="form.errors.answer_mode" />
                    <div class="flex justify-end"><PrimaryButton :disabled="form.processing">{{ $t('Add category') }}</PrimaryButton></div>
                </form>
            </section>

            <p v-if="!categories.length" class="rounded-2xl bg-white p-6 text-sm text-gray-500 shadow dark:bg-gray-800">{{ $t('No categories yet.') }}</p>

            <section v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="category in categories" :key="category.id" class="group overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-lg transition hover:-translate-y-1 hover:shadow-xl dark:border-white/10 dark:bg-gray-800">
                    <Link :href="route('admin.categories.show', category.id)" class="block">
                        <div class="h-40 bg-gradient-to-br from-violet-500 to-fuchsia-500">
                            <img v-if="category.image_url" :src="category.image_url" :alt="category.name" class="size-full object-cover">
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div><p v-if="category.parent" class="text-xs font-black uppercase text-slate-400">{{ category.parent.name }}</p><h3 class="text-xl font-black text-slate-900 dark:text-white">{{ category.name }}</h3></div>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase" :class="category.answer_mode === 'title_only' ? 'bg-amber-100 text-amber-700' : 'bg-violet-100 text-violet-700'">
                                    {{ category.answer_mode === 'title_only' ? $t('Custom') : $t('Classic') }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm font-bold text-slate-400">{{ $t(':count tracks', { count: category.tracks_count }) }}</p>
                        </div>
                    </Link>
                    <div class="border-t border-gray-100 px-5 py-3 text-right dark:border-white/10">
                        <DangerButton @click="removeCategory(category)">{{ $t('Delete') }}</DangerButton>
                    </div>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
