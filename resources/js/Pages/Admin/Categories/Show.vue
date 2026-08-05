<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminTabs from '@/Components/Admin/AdminTabs.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    category: { type: Object, required: true },
    tracks: { type: Object, required: true },
});

const removeTrack = (trackId) => {
    if (confirm('Supprimer définitivement cette piste ?')) {
        router.delete(route('admin.tracks.destroy', trackId), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :title="category.name">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <Link :href="route('admin.categories.index')" class="text-sm font-bold text-violet-600">← {{ $t('Categories') }}</Link>
                    <h2 class="mt-1 text-2xl font-black text-gray-800 dark:text-white">{{ category.name }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ category.answer_mode === 'title_only' ? $t('Custom answer category') : $t('Title and artist category') }}</p>
                </div>
                <Link :href="route('admin.categories.import.create', category.id)"><PrimaryButton>{{ $t('Import tracks') }}</PrimaryButton></Link>
            </div>
            <AdminTabs />
        </template>

        <div class="qp-shell py-10">
            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white/90 shadow-xl dark:border-white/10 dark:bg-gray-800/90">
                <div class="border-b border-violet-100 px-5 py-4 dark:border-white/10">
                    <h3 class="font-black text-slate-900 dark:text-white">{{ $t('Imported elements') }} · {{ tracks.total }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-violet-100 dark:divide-white/10">
                        <thead><tr class="text-left text-xs font-black uppercase text-slate-400"><th class="px-5 py-3">{{ category.answer_mode === 'title_only' ? $t('Answer') : $t('Title') }}</th><th class="px-5 py-3">{{ $t('Artist') }}</th><th class="px-5 py-3">{{ $t('Preview') }}</th><th class="px-5 py-3"></th></tr></thead>
                        <tbody class="divide-y divide-violet-50 dark:divide-white/5">
                            <tr v-for="track in tracks.data" :key="track.id">
                                <td class="px-5 py-3 font-bold text-slate-900 dark:text-white">{{ category.answer_mode === 'title_only' ? track.custom_answer : track.title }}<div v-if="category.answer_mode === 'title_only'" class="text-xs font-normal text-slate-400">{{ track.title }}</div></td>
                                <td class="px-5 py-3 text-sm text-slate-500">{{ track.artist }}</td>
                                <td class="px-5 py-3"><audio :src="track.preview_url" controls preload="none" class="h-8 w-56" /></td>
                                <td class="px-5 py-3"><div class="flex justify-end gap-2"><Link :href="route('admin.tracks.edit', track.id)"><SecondaryButton>{{ $t('Edit') }}</SecondaryButton></Link><DangerButton @click="removeTrack(track.id)">{{ $t('Delete') }}</DangerButton></div></td>
                            </tr>
                            <tr v-if="!tracks.data.length"><td colspan="4" class="px-6 py-14 text-center text-sm font-bold text-slate-400">{{ $t('No tracks in this category yet.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <nav v-if="tracks.links.length > 3" class="mt-6 flex justify-center gap-1"><template v-for="(link, index) in tracks.links" :key="index"><Link v-if="link.url" :href="link.url" class="rounded-xl px-3 py-2 text-sm font-black" :class="link.active ? 'bg-violet-600 text-white' : 'bg-white text-slate-500'" v-html="link.label" /></template></nav>
        </div>
    </AppLayout>
</template>
