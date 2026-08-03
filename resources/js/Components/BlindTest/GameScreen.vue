<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { HugeiconsIcon } from '@hugeicons/vue';
import { FavouriteIcon, Mic01Icon, MusicNote01Icon, PlayIcon, Shield01Icon, VolumeHighIcon } from '@hugeicons/core-free-icons';
import CountdownRing from './CountdownRing.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    round: { type: Object, required: true },
    roomId: { type: String, required: true },
});

const emit = defineEmits(['found']);

const guess = ref('');
const guessInput = ref(null);
const audioEl = ref(null);
const sending = ref(false);
const isPlaying = ref(false);
const audioBlocked = ref(false);
const listening = ref(false);
const microphoneError = ref(false);
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const microphoneSupported = Boolean(SpeechRecognition);
let recognition = null;
const volume = ref(Number(localStorage.getItem('blindtest-volume') ?? 1));
const answerBlocked = computed(() => props.round.disqualified || props.round.livesRemaining < 1);
let reportedRoundNumber = null;

const reportPageLeave = () => {
    if (!props.round.antiCheat || props.round.revealed || reportedRoundNumber === props.round.roundNumber) return;
    reportedRoundNumber = props.round.roundNumber;
    props.round.disqualified = true;
    axios.post(route('blindtest.rooms.disqualify', props.roomId)).catch(() => {});
};

const onVisibilityChange = () => {
    if (document.visibilityState === 'hidden') reportPageLeave();
};

onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('pagehide', reportPageLeave);
});
onUnmounted(() => {
    recognition?.abort();
    document.removeEventListener('visibilitychange', onVisibilityChange);
    window.removeEventListener('pagehide', reportPageLeave);
});

const attemptPlay = () => {
    if (! audioEl.value) return;

    // Different clients learn a round has started at different times (instant via
    // WebSocket, up to a couple seconds late via the polling fallback), so
    // starting playback from 0 every time would leave everyone's audio adrift.
    // Seeking to the actual elapsed time since the round started keeps everyone
    // on the same position in the track regardless of when their client caught up.
    const elapsed = (Date.now() - new Date(props.round.startedAt).getTime()) / 1000;
    audioEl.value.currentTime = Math.max(0, elapsed);

    audioEl.value.volume = volume.value;
    audioEl.value.play()
        .then(() => { audioBlocked.value = false; })
        .catch(() => { audioBlocked.value = true; });
};

const onVolumeChange = () => {
    if (audioEl.value) audioEl.value.volume = volume.value;
    localStorage.setItem('blindtest-volume', volume.value);
};

watch(() => props.round.roundNumber, () => {
    guess.value = '';
    audioBlocked.value = false;
    recognition?.abort();

    // The <audio> template ref isn't bound yet during this component's very first
    // setup pass (the watch runs before mount), so wait a tick before touching it.
    nextTick(() => {
        attemptPlay();
        guessInput.value?.focus();
    });
}, { immediate: true });

const submitGuess = async () => {
    if (! guess.value.trim() || sending.value || props.round.revealed || answerBlocked.value) return;

    sending.value = true;

    try {
        const response = await axios.post(route('blindtest.rooms.guess', props.roomId), { guess: guess.value });

        // Apply our own find immediately rather than waiting for the next poll
        // cycle or a WebSocket event — the person who just guessed shouldn't have
        // to wait to see their own result.
        if (response.data.found_artist) emit('found', { field: 'artist', value: response.data.artist });
        if (response.data.found_title) emit('found', { field: 'title', value: response.data.title });
        props.round.livesRemaining = response.data.lives_remaining;
        props.round.disqualified = response.data.disqualified;

        guess.value = '';
    } catch (error) {
        if (error.response?.data) {
            props.round.livesRemaining = error.response.data.lives_remaining ?? props.round.livesRemaining;
            props.round.disqualified = error.response.data.disqualified ?? props.round.disqualified;
        }
    } finally {
        sending.value = false;
    }
};

const toggleMicrophone = () => {
    if (!microphoneSupported || answerBlocked.value || props.round.revealed) return;

    if (listening.value) {
        recognition?.stop();
        return;
    }

    microphoneError.value = false;
    recognition = new SpeechRecognition();
    recognition.lang = document.documentElement.lang?.startsWith('fr') ? 'fr-FR' : 'en-US';
    recognition.interimResults = false;
    recognition.continuous = false;
    recognition.maxAlternatives = 1;
    recognition.onstart = () => { listening.value = true; };
    recognition.onend = () => { listening.value = false; };
    recognition.onerror = () => {
        listening.value = false;
        microphoneError.value = true;
    };
    recognition.onresult = (event) => {
        guess.value = event.results[0][0].transcript.trim();
        nextTick(() => submitGuess());
    };
    recognition.start();
};
</script>

<template>
    <div class="qp-card relative flex flex-col items-center gap-6 overflow-hidden p-8 sm:p-10">
        <div class="pointer-events-none absolute -right-10 -top-12 size-40 rounded-full bg-violet-400/15 blur-3xl" />
        <audio
            ref="audioEl"
            :src="round.previewUrl"
            class="hidden"
            @play="isPlaying = true"
            @pause="isPlaying = false"
            @ended="isPlaying = false"
        />

        <p class="relative rounded-full bg-violet-100 px-4 py-2 text-xs font-black uppercase tracking-wider text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">
            {{ $t('Round :current / :total', { current: round.roundNumber, total: round.roundsTotal }) }}
        </p>
        <p v-if="round.category" class="relative -mt-3 text-center text-sm font-black text-cyan-600 dark:text-cyan-300">
            {{ $t('Category: :name', { name: round.category }) }}
        </p>
        <div class="relative -mt-3 flex items-center gap-1" :aria-label="$t(':count lives remaining', { count: round.livesRemaining })">
            <HugeiconsIcon v-for="life in round.livesRemaining" :key="life" :icon="FavouriteIcon" :size="20" class="fill-rose-500 text-rose-500" />
            <span class="ms-1 text-xs font-black text-slate-400">{{ round.livesRemaining }}</span>
        </div>

        <div v-if="answerBlocked && !round.revealed" class="relative flex max-w-md items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-black" :class="round.disqualified ? 'border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300' : 'border-rose-300 bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300'">
            <HugeiconsIcon :icon="round.disqualified ? Shield01Icon : FavouriteIcon" :size="21" />
            {{ round.disqualified ? $t('You left the page and cannot answer this round.') : $t('You have no lives left for this round.') }}
        </div>

        <div v-if="! round.revealed" class="flex flex-col items-center gap-6">
            <div class="relative flex items-center justify-center">
                <CountdownRing :started-at="round.startedAt" :duration="round.secondsPerRound" />
                <button
                    v-if="! isPlaying"
                    type="button"
                    class="absolute inline-flex size-14 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-[0_5px_0_#5b21b6] transition hover:scale-105 active:translate-y-1 active:shadow-none"
                    @click="attemptPlay"
                >
                    <HugeiconsIcon :icon="PlayIcon" :size="24" />
                </button>
            </div>

            <p v-if="audioBlocked" class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                {{ $t('Tap the button to start the music') }}
            </p>

            <div class="flex w-full max-w-[200px] items-center gap-2">
                <HugeiconsIcon :icon="VolumeHighIcon" :size="18" class="shrink-0 text-gray-400 dark:text-gray-500" />
                <input
                    v-model.number="volume"
                    type="range"
                    min="0"
                    max="1"
                    step="0.01"
                    class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-gray-200 accent-violet-600 dark:bg-gray-700"
                    @input="onVolumeChange"
                >
            </div>

            <div class="flex flex-col items-center gap-2 text-sm">
                <p v-if="round.artistFound" class="flex items-center gap-2 font-bold text-cyan-600 dark:text-cyan-300">
                    <HugeiconsIcon :icon="Mic01Icon" :size="16" />
                    {{ round.artistFound.value }} — {{ round.artistFound.foundBy.name }}
                </p>
                <p v-if="round.titleFound" class="flex items-center gap-2 font-bold text-cyan-600 dark:text-cyan-300">
                    <HugeiconsIcon :icon="MusicNote01Icon" :size="16" />
                    {{ round.titleFound.value }} — {{ round.titleFound.foundBy.name }}
                </p>
            </div>
        </div>

        <div v-else class="flex flex-col items-center gap-3 text-center">
            <img v-if="round.revealed.track.cover_url" :src="round.revealed.track.cover_url" :alt="round.revealed.track.title" class="size-32 -rotate-2 rounded-[1.5rem] object-cover shadow-xl ring-4 ring-violet-100 dark:ring-violet-500/20">
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ round.revealed.track.title }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ round.revealed.track.artist }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('Next song coming up…') }}</p>
        </div>

        <form class="flex w-full max-w-md gap-3" @submit.prevent="submitGuess">
            <TextInput
                ref="guessInput"
                v-model="guess"
                :placeholder="$t('Artist, title, or both…')"
                class="block w-full"
                :disabled="!! round.revealed || answerBlocked"
                autofocus
            />
            <button type="button" class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl border-2 transition" :class="listening ? 'animate-pulse border-rose-400 bg-rose-500 text-white' : 'border-violet-200 bg-violet-50 text-violet-600 hover:border-violet-400 dark:border-white/10 dark:bg-white/5 dark:text-violet-300'" :disabled="!microphoneSupported || !!round.revealed || answerBlocked" :aria-label="listening ? $t('Stop listening') : $t('Answer with microphone')" :title="!microphoneSupported ? $t('Voice recognition is not supported by this browser.') : ''" @click="toggleMicrophone">
                <HugeiconsIcon :icon="Mic01Icon" :size="22" />
            </button>
            <PrimaryButton type="submit" :disabled="!! round.revealed || sending || answerBlocked">
                {{ $t('Guess') }}
            </PrimaryButton>
        </form>
        <p v-if="listening" class="-mt-3 text-xs font-black text-rose-500">{{ $t('Listening… Speak now.') }}</p>
        <p v-else-if="microphoneError" class="-mt-3 text-xs font-bold text-amber-600 dark:text-amber-400">{{ $t('Microphone access was denied or unavailable.') }}</p>
    </div>
</template>
