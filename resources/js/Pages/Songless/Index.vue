<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    ArrowRight01Icon,
    Calendar03Icon,
    CancelCircleIcon,
    ChartUpIcon,
    CheckmarkCircle02Icon,
    HeadphonesIcon,
    LockIcon,
    MusicNote01Icon,
    PauseIcon,
    PlayIcon,
    ReloadIcon,
    UserIcon,
} from '@hugeicons/core-free-icons';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    date: { type: String, required: true },
    stages: { type: Array, required: true },
    songs: { type: Array, required: true },
    completed: { type: Boolean, required: true },
    available: { type: Boolean, required: true },
});

const activeSong = computed(() => props.songs.find((song) => song.state === 'active'));
const completedSongs = computed(() => props.songs.filter((song) => song.state === 'complete'));
const guess = ref('');
const suggestions = ref([]);
const selectedTrack = ref(null);
const suggestionsOpen = ref(false);
const suggestionsLoading = ref(false);
const activeSuggestionIndex = ref(-1);
const audio = ref(null);
const playing = ref(false);
const playbackError = ref(false);
const playbackElapsed = ref(0);
const volume = ref(1);
const processing = ref(false);
const resetting = ref(false);
let stopTimer;
let progressFrame;
let playbackStartedAt;
let suggestionTimer;

const currentDuration = computed(() => props.stages[activeSong.value?.stage ?? 0]);
const actualPlaybackDuration = computed(() => activeSong.value?.stage === 0 ? 0.3 : currentDuration.value);
const progressPercent = computed(() => Math.min(100, ((playing.value ? playbackElapsed.value : currentDuration.value) / 10) * 100));
const categories = computed(() => activeSong.value?.categories.length
    ? activeSong.value.categories.join(' · ')
    : '—');

const stopAudio = () => {
    clearTimeout(stopTimer);
    cancelAnimationFrame(progressFrame);
    if (audio.value) {
        audio.value.pause();
        audio.value.currentTime = 0;
    }
    playing.value = false;
    playbackElapsed.value = 0;
};

const updatePlaybackProgress = () => {
    playbackElapsed.value = Math.min(actualPlaybackDuration.value, (performance.now() - playbackStartedAt) / 1000);
    if (playing.value && playbackElapsed.value < actualPlaybackDuration.value) {
        progressFrame = requestAnimationFrame(updatePlaybackProgress);
    }
};

const playClip = async () => {
    if (!audio.value || playing.value) return;
    stopAudio();
    playbackError.value = false;
    playing.value = true;
    try {
        audio.value.currentTime = 0;
        await audio.value.play();
        playbackStartedAt = performance.now();
        progressFrame = requestAnimationFrame(updatePlaybackProgress);
        stopTimer = setTimeout(stopAudio, actualPlaybackDuration.value * 1000);
    } catch {
        playing.value = false;
        playbackError.value = true;
    }
};

const handleAudioError = () => {
    stopAudio();
    playbackError.value = true;
};

watch(volume, (value) => {
    if (audio.value) audio.value.volume = value;
    localStorage.setItem('songless-volume', String(value));
});

const postAction = (routeName, data = {}) => {
    if (!activeSong.value || processing.value) return;
    processing.value = true;
    stopAudio();
    router.post(route(routeName, activeSong.value.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            guess.value = '';
            selectedTrack.value = null;
            suggestions.value = [];
        },
        onFinish: () => { processing.value = false; },
    });
};

const submitGuess = () => {
    if (selectedTrack.value) postAction('songless.guess', { track_id: selectedTrack.value.id });
};
const skip = () => postAction('songless.skip');

const guessClasses = (result) => ({
    correct: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200',
    artist: 'border-orange-300 bg-orange-50 text-orange-800 dark:border-orange-400/20 dark:bg-orange-400/10 dark:text-orange-200',
    wrong: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200',
}[result]);
const guessIcon = (result) => result === 'correct' ? CheckmarkCircle02Icon : (result === 'artist' ? UserIcon : CancelCircleIcon);
const guessLabel = (result) => result === 'correct' ? 'Found!' : (result === 'artist' ? 'Artist found, wrong title' : 'Not quite');

const trackLabel = (track) => `${track.artist} — ${track.title}`;
const selectSuggestion = (track) => {
    selectedTrack.value = track;
    guess.value = trackLabel(track);
    suggestionsOpen.value = false;
    activeSuggestionIndex.value = -1;
};

const fetchSuggestions = async (query) => {
    suggestionsLoading.value = true;
    try {
        const response = await axios.get(route('songless.suggestions'), { params: { q: query } });
        if (guess.value.trim() === query) {
            suggestions.value = response.data;
            suggestionsOpen.value = true;
            activeSuggestionIndex.value = response.data.length ? 0 : -1;
        }
    } finally {
        suggestionsLoading.value = false;
    }
};

watch(guess, (value) => {
    clearTimeout(suggestionTimer);
    if (selectedTrack.value && value === trackLabel(selectedTrack.value)) return;
    selectedTrack.value = null;
    const query = value.trim();
    if (query.length < 2) {
        suggestions.value = [];
        suggestionsOpen.value = false;
        return;
    }
    suggestionTimer = setTimeout(() => fetchSuggestions(query), 180);
});

const handleSuggestionKeys = (event) => {
    if (!suggestionsOpen.value || !suggestions.value.length) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeSuggestionIndex.value = (activeSuggestionIndex.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeSuggestionIndex.value = (activeSuggestionIndex.value - 1 + suggestions.value.length) % suggestions.value.length;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        selectSuggestion(suggestions.value[activeSuggestionIndex.value]);
    } else if (event.key === 'Escape') {
        suggestionsOpen.value = false;
    }
};

const closeSuggestions = () => setTimeout(() => { suggestionsOpen.value = false; }, 120);

const resetToday = () => {
    if (!window.confirm(trans('Reset today’s Songless? All player progress and statistics for today will be deleted.'))) return;
    resetting.value = true;
    stopAudio();
    router.delete(route('admin.songless.reset-today'), {
        onFinish: () => { resetting.value = false; },
    });
};

watch(() => activeSong.value?.id, () => {
    stopAudio();
    playbackError.value = false;
    guess.value = '';
    selectedTrack.value = null;
    suggestions.value = [];
});
onMounted(() => {
    const savedVolume = Number(localStorage.getItem('songless-volume'));
    if (Number.isFinite(savedVolume) && savedVolume >= 0 && savedVolume <= 1) {
        volume.value = savedVolume;
    }
    if (audio.value) audio.value.volume = volume.value;
});
onUnmounted(() => { stopAudio(); clearTimeout(suggestionTimer); });
</script>

<template>
    <AppLayout title="Songless">
        <main class="qp-shell py-8 sm:py-12">
            <section class="relative overflow-hidden rounded-[2.25rem] bg-violet-950 p-7 text-white shadow-[0_12px_0_#4c1d95] sm:p-10">
                <HugeiconsIcon :icon="HeadphonesIcon" :size="190" class="absolute -right-8 -top-12 rotate-12 text-white/[0.06]" aria-hidden="true" />
                <div class="relative flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[.22em] text-violet-300">{{ $t('Daily solo challenge') }}</div>
                        <h1 class="mt-3 text-5xl font-black tracking-[-.055em] sm:text-6xl">Songless</h1>
                        <p class="mt-4 max-w-xl font-bold leading-relaxed text-white/55">{{ $t('Three songs. A fraction of a second. How fast can you recognize them?') }}</p>
                    </div>
                    <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 text-sm font-black ring-1 ring-white/10"><HugeiconsIcon :icon="Calendar03Icon" :size="20" class="text-violet-300" />{{ date }}</div>
                        <button v-if="$page.props.auth.user?.is_admin" type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-black text-white ring-1 ring-white/10 transition hover:bg-white/20 disabled:opacity-50" :disabled="resetting" @click="resetToday"><HugeiconsIcon :icon="ReloadIcon" :size="19" :class="resetting ? 'animate-spin' : ''" />{{ $t('Reset today') }}</button>
                    </div>
                </div>
            </section>

            <nav class="mt-7 grid grid-cols-3 gap-3" :aria-label="$t('Daily songs')">
                <div v-for="song in songs" :key="song.id" class="flex items-center gap-3 rounded-2xl border p-3 backdrop-blur-xl" :class="song.state === 'active' ? 'border-violet-400 bg-violet-100/80 dark:bg-violet-500/20' : 'border-violet-200/60 bg-white/60 dark:border-white/10 dark:bg-white/5'">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl text-sm font-black" :class="song.state === 'complete' ? 'bg-cyan-400 text-cyan-950' : (song.state === 'active' ? 'bg-violet-600 text-white' : 'bg-slate-200 text-slate-400 dark:bg-white/10')">
                        <HugeiconsIcon v-if="song.state === 'complete'" :icon="CheckmarkCircle02Icon" :size="19" />
                        <HugeiconsIcon v-else-if="song.state === 'locked'" :icon="LockIcon" :size="17" />
                        <span v-else>{{ song.position }}</span>
                    </span>
                    <div class="min-w-0"><div class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Song :number', { number: song.position }) }}</div><div class="truncate text-sm font-black text-slate-800 dark:text-white">{{ song.state === 'complete' ? song.result.title : $t(song.state === 'active' ? 'Now playing' : 'Locked') }}</div></div>
                </div>
            </nav>

            <section v-if="!available" class="qp-card mt-8 text-center sm:p-10">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-200"><HugeiconsIcon :icon="MusicNote01Icon" :size="32" /></div>
                <div class="qp-kicker mt-5">{{ $t('Challenge unavailable') }}</div>
                <h2 class="qp-title mt-2">{{ $t('Songless needs at least three tracks.') }}</h2>
                <p class="qp-muted mx-auto mt-3 max-w-lg">{{ $t('Add tracks with playable previews to generate today’s challenge.') }}</p>
            </section>

            <section v-if="activeSong" class="mt-8 grid gap-6 lg:grid-cols-[1.35fr_.65fr]">
                <div class="qp-card relative overflow-hidden sm:p-9">
                    <div class="absolute right-5 top-5 rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">{{ activeSong.stage + 1 }} / {{ stages.length }}</div>
                    <div class="qp-kicker">{{ $t('Category') }}</div>
                    <h2 class="mt-2 max-w-lg text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ categories }}</h2>

                    <div class="mt-9 flex flex-col items-center">
                        <audio ref="audio" :src="activeSong.preview_url" preload="auto" class="hidden" @ended="stopAudio" @error="handleAudioError" />
                        <button type="button" class="flex size-28 items-center justify-center rounded-[2rem] bg-violet-600 text-white shadow-[0_7px_0_#5b21b6] transition hover:-translate-y-0.5 hover:bg-violet-500 active:translate-y-1 active:shadow-none" :aria-label="$t('Listen to :duration seconds', { duration: currentDuration })" @click="playing ? stopAudio() : playClip()">
                            <HugeiconsIcon :icon="playing ? PauseIcon : PlayIcon" :size="38" />
                        </button>
                        <label class="mt-6 w-full max-w-64 text-center">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Volume') }} · {{ Math.round(volume * 100) }}%</span>
                            <input v-model.number="volume" type="range" min="0" max="1" step="0.05" class="mt-2 h-2 w-full cursor-pointer accent-violet-600" :aria-label="$t('Volume')">
                        </label>
                        <div class="mt-6 text-center"><div class="text-4xl font-black tracking-[-.04em] text-slate-950 dark:text-white">{{ currentDuration }}s</div><p class="qp-muted mt-1">{{ $t('Maximum listening time') }}</p></div>
                        <p v-if="playbackError" class="mt-4 max-w-sm text-center text-sm font-bold text-rose-500 dark:text-rose-300">{{ $t('The audio preview could not be loaded. Please refresh the page and try again.') }}</p>
                    </div>

                    <div class="mt-9" :aria-label="$t('Listening progress')">
                        <div class="mb-3 flex items-center justify-between text-xs font-black uppercase tracking-wider text-slate-400"><span>{{ $t('Listening progress') }}</span><span>10s</span></div>
                        <div class="relative pb-12">
                            <div class="relative h-5 overflow-hidden rounded-lg bg-violet-100 ring-1 ring-violet-200 dark:bg-white/10 dark:ring-white/10">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-violet-600 to-cyan-400 transition-[width] duration-200 ease-linear" :class="playing ? 'shadow-[0_0_18px_rgb(139_92_246/0.65)]' : ''" :style="{ width: `${progressPercent}%` }" />
                                <span v-for="stage in stages" :key="stage" class="absolute inset-y-0 z-10 w-0.5 -translate-x-1/2 bg-white/80 shadow-[0_0_0_1px_rgb(76_29_149/0.12)] dark:bg-white/45" :style="{ left: `${Math.max(1, stage / 10 * 100)}%` }" />
                            </div>
                            <div v-for="(stage, index) in stages" :key="`label-${stage}`" class="absolute top-6 -translate-x-1/2 whitespace-nowrap text-[10px] font-black" :class="[index % 2 ? 'mt-5' : 'mt-1', index <= activeSong.stage ? 'text-violet-600 dark:text-violet-300' : 'text-slate-300 dark:text-slate-600']" :style="{ left: `${Math.max(1, stage / 10 * 100)}%` }">
                                {{ stage }}s
                            </div>
                        </div>
                    </div>

                    <form class="mt-8" @submit.prevent="submitGuess">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <div class="relative min-w-0 flex-1">
                                <TextInput v-model="guess" class="block w-full pe-11" :class="selectedTrack ? 'border-cyan-400 focus:border-cyan-400' : ''" :placeholder="$t('Search for a song or artist')" :disabled="processing" autocomplete="off" role="combobox" :aria-expanded="suggestionsOpen" aria-autocomplete="list" autofocus @focus="suggestions.length && (suggestionsOpen = true)" @blur="closeSuggestions" @keydown="handleSuggestionKeys" />
                                <HugeiconsIcon v-if="selectedTrack" :icon="CheckmarkCircle02Icon" :size="22" class="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-cyan-500" />
                                <span v-else-if="suggestionsLoading" class="absolute end-4 top-1/2 size-4 -translate-y-1/2 animate-spin rounded-full border-2 border-violet-200 border-t-violet-600" />

                                <div v-if="suggestionsOpen" class="absolute inset-x-0 bottom-full z-30 mb-2 max-h-[356px] overflow-y-auto overscroll-contain rounded-2xl border border-violet-200 bg-white/95 p-2 shadow-2xl backdrop-blur-xl dark:border-violet-300/15 dark:bg-[#171329]/95" role="listbox">
                                    <button v-for="(track, index) in suggestions" :key="track.id" type="button" class="flex w-full items-center gap-3 rounded-xl p-3 text-start transition" :class="index === activeSuggestionIndex ? 'bg-violet-100 dark:bg-violet-500/20' : 'hover:bg-violet-50 dark:hover:bg-white/5'" role="option" :aria-selected="index === activeSuggestionIndex" @mousedown.prevent="selectSuggestion(track)" @mouseenter="activeSuggestionIndex = index">
                                        <img v-if="track.cover_url" :src="track.cover_url" alt="" class="size-11 shrink-0 rounded-xl object-cover">
                                        <span v-else class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-500 dark:bg-violet-500/20"><HugeiconsIcon :icon="MusicNote01Icon" :size="21" /></span>
                                        <span class="min-w-0"><span class="block truncate text-sm font-black text-slate-900 dark:text-white">{{ track.title }}</span><span class="block truncate text-xs font-bold text-slate-400">{{ track.artist }}</span></span>
                                    </button>
                                    <div v-if="!suggestions.length && !suggestionsLoading" class="p-4 text-center text-sm font-bold text-slate-400">{{ $t('No matching tracks') }}</div>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-violet-600 px-6 py-3 text-sm font-black text-white shadow-[0_5px_0_#5b21b6] active:translate-y-1 active:shadow-none disabled:cursor-not-allowed disabled:opacity-40" :disabled="processing || !selectedTrack">{{ $t('Guess') }} <HugeiconsIcon :icon="ArrowRight01Icon" :size="18" /></button>
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-violet-200 bg-white/70 px-5 py-3 text-sm font-black text-slate-500 transition hover:border-violet-400 hover:text-violet-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:border-violet-400 dark:hover:text-violet-300" :disabled="processing" @click="skip">{{ $t('Skip') }} <HugeiconsIcon :icon="ArrowRight01Icon" :size="18" /></button>
                            </div>
                        </div>
                        <p v-if="guess.trim() && !selectedTrack" class="mt-2 text-xs font-bold text-slate-400">{{ $t('Select a track from the suggestions to submit.') }}</p>
                        <InputError :message="$page.props.errors?.song || $page.props.errors?.track_id" class="mt-2" />
                    </form>
                </div>

                <aside class="qp-card h-fit">
                    <div class="flex items-center justify-between"><div><div class="qp-kicker">{{ $t('Your guesses') }}</div><h2 class="mt-1 text-xl font-black">{{ activeSong.guesses.length }} / {{ stages.length }}</h2></div><HugeiconsIcon :icon="MusicNote01Icon" :size="28" class="text-violet-400" /></div>
                    <div v-if="activeSong.guesses.length" class="mt-5 space-y-3">
                        <div v-for="item in activeSong.guesses" :key="item.id" class="rounded-2xl border p-3" :class="guessClasses(item.result)">
                            <div class="flex items-center gap-2"><HugeiconsIcon :icon="guessIcon(item.result)" :size="19" /><span class="min-w-0 flex-1 truncate text-sm font-black">{{ item.guess }}</span><span class="text-xs font-black opacity-60">{{ stages[item.stage] }}s</span></div>
                            <div class="mt-1 pl-7 text-xs font-bold opacity-70">{{ $t(guessLabel(item.result)) }}</div>
                        </div>
                    </div>
                    <div v-else class="mt-6 rounded-2xl border-2 border-dashed border-violet-200 p-6 text-center dark:border-violet-500/20"><HugeiconsIcon :icon="HeadphonesIcon" :size="34" class="mx-auto text-violet-400" /><p class="qp-muted mt-3">{{ $t('Listen carefully, then make your first guess.') }}</p></div>
                </aside>
            </section>

            <section v-if="completed" class="qp-card mt-8 text-center sm:p-10">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600 dark:bg-cyan-400/10 dark:text-cyan-300"><HugeiconsIcon :icon="CheckmarkCircle02Icon" :size="34" /></div>
                <div class="qp-kicker mt-5">{{ $t('Challenge complete') }}</div><h2 class="qp-title mt-2">{{ $t('Come back tomorrow for three new songs.') }}</h2>
            </section>

            <section v-if="completedSongs.length" class="mt-12">
                <div class="qp-kicker">{{ $t('Today’s results') }}</div><h2 class="qp-title mt-2">{{ $t('Your performance') }}</h2>
                <div class="mt-6 grid gap-5 lg:grid-cols-3">
                    <article v-for="song in completedSongs" :key="song.id" class="qp-card overflow-hidden">
                        <div class="flex items-center gap-4"><img v-if="song.result.cover_url" :src="song.result.cover_url" :alt="song.result.title" class="size-16 rounded-2xl object-cover ring-2 ring-violet-100 dark:ring-violet-500/20"><div class="min-w-0"><div class="truncate text-lg font-black">{{ song.result.title }}</div><div class="truncate text-sm font-bold text-slate-400">{{ song.result.artist }}</div></div></div>
                        <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-violet-50 p-3 dark:bg-white/5"><div class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $t('Result') }}</div><div class="mt-1 font-black" :class="song.result.won ? 'text-cyan-600 dark:text-cyan-300' : 'text-rose-500'">{{ song.result.won ? `${stages[song.result.stage]}s` : $t('Not found') }}</div></div><div class="rounded-2xl bg-violet-50 p-3 dark:bg-white/5"><div class="flex items-center gap-1 text-xs font-black uppercase tracking-wider text-slate-400"><HugeiconsIcon :icon="ChartUpIcon" :size="14" /> {{ $t('Players beaten') }}</div><div class="mt-1 font-black text-violet-600 dark:text-violet-300">{{ song.result.percentile }}%</div></div></div>
                        <div v-if="song.guesses.length" class="mt-4 space-y-2"><div v-for="item in song.guesses" :key="item.id" class="flex items-center gap-2 rounded-xl border px-3 py-2" :class="guessClasses(item.result)"><HugeiconsIcon :icon="guessIcon(item.result)" :size="16" /><span class="min-w-0 flex-1 truncate text-xs font-black">{{ item.guess }}</span><span class="text-[10px] font-black opacity-60">{{ stages[item.stage] }}s</span></div></div>
                    </article>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
