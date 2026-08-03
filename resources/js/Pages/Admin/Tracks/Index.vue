<script setup>
import { onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { MusicNote01Icon, Search01Icon } from '@hugeicons/core-free-icons';
import { trans } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminTabs from '@/Components/Admin/AdminTabs.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    tracks: { type: Object, required: true },
    filters: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const perPage = ref(Number(props.filters.per_page ?? 25));
const categoryId = ref(props.filters.category_id ?? '');
const cleanupProcessing = ref(false);
const frCleanupProcessing = ref(false);
let searchTimer;

const loadTracks = () => {
    router.get(route('admin.tracks.index'), {
        search: search.value || undefined,
        per_page: perPage.value,
        category_id: categoryId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tracks', 'filters'],
    });
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadTracks, 300);
});
watch(perPage, loadTracks);
watch(categoryId, loadTracks);
onUnmounted(() => clearTimeout(searchTimer));

const removeTrack = (trackId) => {
    if (confirm(trans('Remove this track from the library?'))) {
        router.delete(route('admin.tracks.destroy', trackId), { preserveScroll: true });
    }
};

const removeBrokenTracks = () => {
    if (!confirm(trans('Check all Deezer tracks and remove those without a playable preview?'))) return;

    cleanupProcessing.value = true;
    router.delete(route('admin.tracks.destroy-broken'), {
        preserveScroll: true,
        onFinish: () => { cleanupProcessing.value = false; },
    });
};

const removeFrenchTracks = () => {
    if (!confirm(trans('Permanently delete every track assigned to the FR category?'))) return;

    frCleanupProcessing.value = true;
    router.delete(route('admin.tracks.destroy-fr-category'), {
        preserveScroll: true,
        onFinish: () => { frCleanupProcessing.value = false; },
    });
};
</script>

<template>
    <AppLayout :title="$t('Tracks')">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $t('Music library') }}</h2>
                    <p class="mt-1 text-sm font-bold text-slate-400">{{ $t(':count tracks in the library', { count: tracks.total }) }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <DangerButton :disabled="frCleanupProcessing" @click="removeFrenchTracks">{{ $t('Delete FR tracks') }}</DangerButton>
                    <DangerButton :disabled="cleanupProcessing" @click="removeBrokenTracks">{{ $t('Remove broken tracks') }}</DangerButton>
                    <Link :href="route('admin.tracks.import.create')"><PrimaryButton>{{ $t('Import tracks') }}</PrimaryButton></Link>
                </div>
            </div>
            <AdminTabs />
        </template>

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
            <div v-if="page.props.flash?.imported !== undefined" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                {{ $t(':imported of :total track(s) imported.', { imported: page.props.flash.imported, total: page.props.flash.total }) }}
            </div>
            <div v-if="page.props.flash?.broken_tracks_scan_queued !== undefined" class="rounded-2xl bg-violet-50 px-4 py-3 text-sm font-bold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                {{ $t(':count tracks queued for verification. The cleanup continues in the background.', { count: page.props.flash.broken_tracks_scan_queued }) }}
            </div>
            <div v-if="page.props.flash?.fr_tracks_deleted !== undefined" class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                {{ $t(':count FR track(s) deleted.', { count: page.props.flash.fr_tracks_deleted }) }}
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="relative block w-full sm:max-w-md">
                    <span class="sr-only">{{ $t('Search tracks') }}</span>
                    <HugeiconsIcon :icon="Search01Icon" :size="20" class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" class="w-full rounded-2xl border-violet-200 bg-white/80 py-3 pe-4 ps-12 text-sm font-bold shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-white/10 dark:bg-white/5" :placeholder="$t('Search by title, artist or album')">
                </label>
                <label class="block sm:min-w-56"><span class="sr-only">{{ $t('Filter by category') }}</span><select v-model="categoryId" class="w-full rounded-2xl border-violet-200 bg-white/80 py-3 text-sm font-black text-slate-600 focus:border-violet-500 focus:ring-violet-500 dark:border-white/10 dark:bg-[#171329] dark:text-slate-200"><option value="">{{ $t('All categories') }}</option><template v-for="category in categories" :key="category.id"><option :value="category.id">{{ category.name }}</option><option v-for="child in category.children" :key="child.id" :value="child.id">— {{ child.name }}</option></template></select></label>
                <label class="flex items-center gap-2 text-sm font-bold text-slate-500 dark:text-slate-300">
                    {{ $t('Rows per page') }}
                    <select v-model="perPage" class="rounded-xl border-violet-200 bg-white py-2 text-sm font-black focus:border-violet-500 focus:ring-violet-500 dark:border-white/10 dark:bg-[#171329]">
                        <option :value="25">25</option><option :value="50">50</option><option :value="100">100</option>
                    </select>
                </label>
            </div>

            <div class="overflow-hidden rounded-3xl border border-violet-100 bg-white/85 shadow-xl shadow-violet-950/5 backdrop-blur-xl dark:border-white/10 dark:bg-[#171329]/85">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] border-collapse text-start">
                        <thead class="border-b border-violet-100 bg-violet-50/70 text-xs font-black uppercase tracking-wider text-slate-400 dark:border-white/10 dark:bg-white/5">
                            <tr><th class="px-5 py-4 text-start">{{ $t('Track') }}</th><th class="px-5 py-4 text-start">{{ $t('Album') }}</th><th class="px-5 py-4 text-start">{{ $t('Categories') }}</th><th class="px-5 py-4 text-start">{{ $t('Preview') }}</th><th class="px-5 py-4 text-end">{{ $t('Actions') }}</th></tr>
                        </thead>
                        <tbody class="divide-y divide-violet-100 dark:divide-white/10">
                            <tr v-for="track in tracks.data" :key="track.id" class="transition hover:bg-violet-50/50 dark:hover:bg-white/[.03]">
                                <td class="px-5 py-3"><div class="flex items-center gap-3"><img v-if="track.cover_url" :src="track.cover_url" alt="" loading="lazy" class="size-11 shrink-0 rounded-xl object-cover"><span v-else class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-500 dark:bg-violet-500/20"><HugeiconsIcon :icon="MusicNote01Icon" :size="20" /></span><div class="min-w-0"><div class="max-w-72 truncate text-sm font-black text-slate-900 dark:text-white">{{ track.title }}</div><div class="max-w-72 truncate text-xs font-bold text-slate-400">{{ track.artist }}</div></div></div></td>
                                <td class="max-w-52 truncate px-5 py-3 text-sm font-bold text-slate-500 dark:text-slate-300">{{ track.album || '—' }}</td>
                                <td class="px-5 py-3"><div class="flex max-w-64 flex-wrap gap-1"><span v-for="category in track.categories" :key="category.id" class="rounded-full bg-violet-100 px-2 py-1 text-[10px] font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">{{ category.name }}</span><span v-if="!track.categories.length" class="text-sm text-slate-300">—</span></div></td>
                                <td class="px-5 py-3"><audio :src="track.preview_url" controls preload="none" class="h-8 w-48" /></td>
                                <td class="px-5 py-3"><div class="flex justify-end gap-2"><Link :href="route('admin.tracks.edit', track.id)"><SecondaryButton>{{ $t('Edit') }}</SecondaryButton></Link><DangerButton @click="removeTrack(track.id)">{{ $t('Remove') }}</DangerButton></div></td>
                            </tr>
                            <tr v-if="!tracks.data.length"><td colspan="5" class="px-6 py-14 text-center text-sm font-bold text-slate-400">{{ search || categoryId ? $t('No tracks match your filters.') : $t('No tracks yet.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="tracks.total" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-sm font-bold text-slate-400">{{ $t('Showing :from–:to of :total', { from: tracks.from, to: tracks.to, total: tracks.total }) }}</p>
                <nav v-if="tracks.links.length > 3" class="flex flex-wrap justify-center gap-1" :aria-label="$t('Pagination')">
                    <template v-for="(link, index) in tracks.links" :key="index"><Link v-if="link.url" :href="link.url" preserve-scroll preserve-state class="min-w-9 rounded-xl px-3 py-2 text-center text-sm font-black transition" :class="link.active ? 'bg-violet-600 text-white' : 'bg-white/80 text-slate-500 hover:bg-violet-100 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'" v-html="link.label" /><span v-else class="min-w-9 rounded-xl px-3 py-2 text-center text-sm font-black text-slate-300 dark:text-slate-600" v-html="link.label" /></template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
