<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import axios from 'axios';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Clock01Icon, CrownIcon, FavouriteIcon, Globe02Icon, HeadphonesIcon, Key01Icon, MusicNote01Icon, Shield01Icon, UserRemove01Icon, ViewIcon, ViewOffIcon } from '@hugeicons/core-free-icons';
import AppLayout from '@/Layouts/AppLayout.vue';
import CategoryPicker from '@/Components/CategoryPicker.vue';
import GameScreen from '@/Components/BlindTest/GameScreen.vue';
import NumberStepper from '@/Components/NumberStepper.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Toggle from '@/Components/Toggle.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    room: { type: Object, required: true },
    viewerIsPlayer: { type: Boolean, required: true },
    players: { type: Array, required: true },
    categories: { type: Array, required: true },
    selectedCategoryIds: { type: Array, required: true },
    invitableFriends: { type: Array, default: () => [] },
    currentRound: { type: Object, default: null },
});

const page = usePage();
const isHost = computed(() => page.props.auth.user?.id === props.room.host_id);
const isPlayer = ref(props.viewerIsPlayer);

const status = ref(props.room.status);
const players = ref([...props.players]);
const friends = ref([...props.invitableFriends]);
const finalScores = ref(null);

const toFoundState = (found) => (found ? { value: found.value, foundBy: found.found_by } : null);

const buildGameRoundFromProp = (round) => (round ? {
    roundNumber: round.round_number,
    roundsTotal: props.room.rounds_total,
    secondsPerRound: props.room.seconds_per_round,
    startedAt: round.started_at,
    previewUrl: round.preview_url,
    category: round.category,
    livesRemaining: round.lives_remaining ?? props.room.lives_per_round,
    disqualified: round.disqualified ?? false,
    antiCheat: props.room.anti_cheat,
    artistFound: toFoundState(round.artist_found),
    titleFound: toFoundState(round.title_found),
    revealed: round.revealed ? {
        track: round.track,
        artistFoundBy: round.artist_found?.found_by ?? null,
        titleFoundBy: round.title_found?.found_by ?? null,
    } : null,
} : null);

const gameRound = ref(buildGameRoundFromProp(props.currentRound));

// Inertia keeps this component mounted across visits to the same page (e.g. after
// join/invite/start redirect back here), so props change without a remount — resync.
// Real-time updates (Echo) still drive fine-grained state (guesses, reveals) once a
// round is underway, but the room/round transitions themselves must not depend on the
// WebSocket connection being up — the HTTP response is the source of truth.
watch(() => props.viewerIsPlayer, (value) => { isPlayer.value = value; });
watch(() => props.players, (value) => { players.value = value.map((player) => ({ ...player })); });
watch(() => props.invitableFriends, (value) => { friends.value = [...value]; });
watch(() => props.room.status, (value) => { status.value = value; });
watch(() => props.currentRound, (value) => { gameRound.value = buildGameRoundFromProp(value); });

const settingsForm = useForm({
    category_ids: [...props.selectedCategoryIds],
    rounds_total: props.room.rounds_total,
    seconds_per_round: props.room.seconds_per_round,
    is_public: props.room.is_public,
    anti_cheat: props.room.anti_cheat,
    lives_per_round: props.room.lives_per_round,
});

const savedFlash = ref(false);
const settingsSaving = ref(false);
const settingsSaveError = ref(false);
const starting = ref(false);
const startError = ref(null);
const joining = ref(false);
const joiningAsGuest = ref(false);
const linkCopied = ref(false);
const codeCopied = ref(false);
const linkHidden = ref(true);
const inviteLink = computed(() => route('blindtest.rooms.show', props.room.public_id));
const renamingGuest = ref(false);
const guestNameForm = useForm({ name: page.props.auth.user?.name ?? '' });
const kickingPlayerId = ref(null);

const kickPlayer = (player) => {
    if (!window.confirm(trans('Remove :name from the room?', { name: player.user.name }))) return;
    kickingPlayerId.value = player.user.id;
    router.delete(route('blindtest.rooms.players.destroy', [props.room.public_id, player.user.id]), {
        preserveScroll: true,
        onFinish: () => { kickingPlayerId.value = null; },
    });
};

const categoryNames = computed(() => {
    const flat = props.categories.flatMap((category) => [category, ...category.children]);
    return flat
        .filter((category) => settingsForm.category_ids.includes(category.id))
        .map((category) => category.name);
});

const categoriesSummary = computed(() => (categoryNames.value.length ? categoryNames.value.join(', ') : trans('No categories yet')));

const statusLabel = computed(() => {
    if (status.value === 'lobby') return trans('Waiting');
    if (status.value === 'playing') return trans('In progress');
    return trans('Finished');
});

const joinBlockedMessage = computed(() => (status.value === 'playing'
    ? trans("This game is already in progress — you can't join.")
    : trans("This game has finished — you can't join.")));

let suppressAutosave = false;
let debounceTimer = null;
let saveQueued = false;
let activeSavePromise = null;
let savedFlashTimer = null;

const settingsPayload = () => ({
    category_ids: [...settingsForm.category_ids],
    rounds_total: settingsForm.rounds_total,
    seconds_per_round: settingsForm.seconds_per_round,
    is_public: settingsForm.is_public,
    anti_cheat: settingsForm.anti_cheat,
    lives_per_round: settingsForm.lives_per_round,
});

const saveSettings = () => {
    saveQueued = true;
    if (activeSavePromise) return activeSavePromise;

    activeSavePromise = (async () => {
        settingsSaving.value = true;
        settingsSaveError.value = false;

        while (saveQueued) {
            saveQueued = false;
            try {
                await axios.patch(route('blindtest.rooms.update', props.room.public_id), settingsPayload());
            } catch {
                settingsSaveError.value = true;
                saveQueued = false;
            }
        }
    })().finally(() => {
        settingsSaving.value = false;
        activeSavePromise = null;
        if (!settingsSaveError.value) {
            savedFlash.value = true;
            clearTimeout(savedFlashTimer);
            savedFlashTimer = setTimeout(() => { savedFlash.value = false; }, 1500);
        }
    });

    return activeSavePromise;
};

watch(
    () => [settingsForm.category_ids.slice(), settingsForm.rounds_total, settingsForm.seconds_per_round, settingsForm.is_public, settingsForm.anti_cheat, settingsForm.lives_per_round],
    () => {
        if (! isHost.value || suppressAutosave) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(saveSettings, 250);
    },
    { deep: true },
);

const joinRoom = () => {
    joining.value = true;
    router.post(route('blindtest.rooms.join', props.room.public_id), {}, {
        preserveScroll: true,
        onFinish: () => { joining.value = false; },
    });
};

const joinAsGuest = () => {
    joiningAsGuest.value = true;
    router.post(route('blindtest.rooms.guest-join', props.room.public_id), {}, {
        preserveScroll: true,
        onFinish: () => { joiningAsGuest.value = false; },
    });
};

const copyText = async (text) => {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Fall through to the legacy method when permission is denied.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.inset = '0 auto auto -9999px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } finally {
        document.body.removeChild(textarea);
    }

    return copied;
};

const copyInviteLink = async () => {
    try {
        if (await copyText(route('blindtest.rooms.show', props.room.public_id))) {
            linkCopied.value = true;
            setTimeout(() => { linkCopied.value = false; }, 1500);
        }
    } catch {
        linkCopied.value = false;
    }
};

const copySecretCode = async () => {
    try {
        const response = await axios.get(route('blindtest.rooms.secret-code', props.room.public_id));
        if (await copyText(response.data.code)) {
            codeCopied.value = true;
            setTimeout(() => { codeCopied.value = false; }, 1500);
        }
    } catch {
        codeCopied.value = false;
    }
};

const saveGuestName = () => {
    guestNameForm.patch(route('guest.name.update'), {
        preserveScroll: true,
        onSuccess: () => { renamingGuest.value = false; },
    });
};

const onSelfFound = ({ field, value }) => {
    if (! gameRound.value || gameRound.value.revealed) return;

    const foundBy = { id: page.props.auth.user.id, name: page.props.auth.user.name };

    if (field === 'artist' && ! gameRound.value.artistFound) {
        gameRound.value.artistFound = { value, foundBy };
    } else if (field === 'title' && ! gameRound.value.titleFound) {
        gameRound.value.titleFound = { value, foundBy };
    }
};

const inviteFriend = (friend) => {
    router.post(route('blindtest.rooms.invite', props.room.public_id), { friend_id: friend.id }, {
        preserveScroll: true,
    });
};

const startGame = async () => {
    starting.value = true;
    startError.value = null;

    clearTimeout(debounceTimer);
    await saveSettings();
    if (settingsSaveError.value) {
        startError.value = trans('The settings could not be saved. Please try again.');
        starting.value = false;
        return;
    }

    router.post(route('blindtest.rooms.start', props.room.public_id), {}, {
        preserveScroll: true,
        onError: (errors) => { startError.value = errors.start; },
        onFinish: () => { starting.value = false; },
    });
};

let channelName;
let subscribed = false;

const subscribeToChannel = () => {
    if (subscribed) return;
    subscribed = true;

    channelName = `blindtest.room.${props.room.id}`;

    window.Echo.private(channelName)
        .listen('.player.joined', (event) => {
            if (! players.value.some((player) => player.user.id === event.player.user.id)) {
                players.value.push(event.player);
            }
            friends.value = friends.value.filter((f) => f.id !== event.player.user.id);
        })
        .listen('.player.removed', (event) => {
            players.value = players.value.filter((player) => player.user.id !== event.user_id);
            if (page.props.auth.user?.id === event.user_id) {
                isPlayer.value = false;
                router.visit(route('blindtest.index'));
            }
        })
        .listen('.settings.updated', (event) => {
            // The host owns the editable local state. Reapplying broadcasts from
            // an earlier autosave can otherwise undo rapid category clicks.
            if (isHost.value) return;

            suppressAutosave = true;
            settingsForm.rounds_total = event.rounds_total;
            settingsForm.seconds_per_round = event.seconds_per_round;
            settingsForm.anti_cheat = event.anti_cheat;
            settingsForm.lives_per_round = event.lives_per_round;
            settingsForm.category_ids = event.categories.map((category) => category.id);
            nextTick(() => { suppressAutosave = false; });
        })
        .listen('.game.started', (event) => {
            status.value = event.status;
        })
        .listen('.round.started', (event) => {
            gameRound.value = {
                roundNumber: event.round_number,
                roundsTotal: props.room.rounds_total,
                secondsPerRound: props.room.seconds_per_round,
                startedAt: event.started_at,
                previewUrl: event.preview_url,
                category: event.category,
                livesRemaining: settingsForm.lives_per_round,
                disqualified: false,
                antiCheat: settingsForm.anti_cheat,
                artistFound: null,
                titleFound: null,
                revealed: null,
            };
        })
        .listen('.guess.revealed', (event) => {
            if (! gameRound.value) return;

            if (event.field === 'artist') {
                gameRound.value.artistFound = { value: event.value, foundBy: event.found_by };
            } else {
                gameRound.value.titleFound = { value: event.value, foundBy: event.found_by };
            }

            const player = players.value.find((p) => p.user.id === event.found_by.id);
            if (player) player.score += 1;
        })
        .listen('.round.ended', (event) => {
            if (gameRound.value) {
                gameRound.value.revealed = {
                    track: event.track,
                    artistFoundBy: event.artist_found_by,
                    titleFoundBy: event.title_found_by,
                };
            }
            players.value = event.players.map((player) => ({ ...player }));
        })
        .listen('.game.ended', (event) => {
            status.value = 'finished';
            gameRound.value = null;
            finalScores.value = event.players.map((player) => ({ ...player }));
        })
        .listen('.room.deleted', () => {
            router.visit(route('blindtest.index'));
        });
};

let heartbeatTimer = null;

const sendHeartbeat = () => {
    axios.post(route('blindtest.rooms.heartbeat', props.room.public_id)).catch(() => {});
};

const startHeartbeat = () => {
    if (heartbeatTimer) return;
    sendHeartbeat();
    heartbeatTimer = setInterval(sendHeartbeat, 30000);
};

const stopHeartbeat = () => {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
};

// Gate on isPlayer, not merely on being authenticated: the private channel's
// authorization requires an existing game_room_players row, so subscribing before
// that row exists (e.g. an authenticated visitor previewing a room before joining)
// gets rejected — and since a join doesn't remount this component (Inertia keeps
// the same instance across a visit to the same route), nothing would ever retry.
// Browsers only allow unmuted audio to autoplay after the page has received a
// genuine user gesture. The host always provides one (clicking "Start game"), but a
// player who was added to the room without clicking anything here themselves (e.g.
// invited straight in) hasn't — so their first round's autoplay gets blocked and
// GameScreen falls back to a "tap to play" button. Priming playback on the very
// first interaction with this page, however unrelated (join, rename, guess box…),
// gets that gesture registered as early as possible so it's already unlocked by the
// time a round actually starts.
const unlockAudioEl = ref(null);
const unlockAudio = () => {
    if (! unlockAudioEl.value) return;
    unlockAudioEl.value.play().then(() => unlockAudioEl.value.pause()).catch(() => {});
    document.removeEventListener('pointerdown', unlockAudio, true);
};

onMounted(() => {
    if (isPlayer.value) subscribeToChannel();
    document.addEventListener('pointerdown', unlockAudio, true);
});

watch(isPlayer, (value) => {
    if (value) subscribeToChannel();
});

watch([isHost, status], ([host, currentStatus]) => {
    if (host && currentStatus === 'lobby') {
        startHeartbeat();
    } else {
        stopHeartbeat();
    }
}, { immediate: true });

// Resilient fallback to the WebSocket broadcasts: polls the room's actual state over
// plain HTTP every couple seconds. Real-time push (above) is what makes this feel
// instant when Reverb is reachable, but this poll is what guarantees the game
// actually progresses (start, countdown, reveal, players joining) even when it isn't —
// broadcasting reliability has proven environment-dependent in practice.
const applyState = (data) => {
    status.value = data.status;
    players.value = data.players.map((player) => ({ ...player }));

    if (data.status === 'playing' && data.round) {
        if (! gameRound.value || gameRound.value.roundNumber !== data.round.round_number) {
            gameRound.value = {
                roundNumber: data.round.round_number,
                roundsTotal: props.room.rounds_total,
                secondsPerRound: props.room.seconds_per_round,
                startedAt: data.round.started_at,
                previewUrl: data.round.preview_url,
                category: data.round.category,
                livesRemaining: data.round.lives_remaining,
                disqualified: data.round.disqualified,
                antiCheat: settingsForm.anti_cheat,
                artistFound: null,
                titleFound: null,
                revealed: null,
            };
        }

        if (gameRound.value && ! gameRound.value.revealed) {
            // Live-reveal each field the moment it's found — not only at full
            // reveal — so "everyone sees it" holds even when relying on polling.
            if (data.round.artist_found && ! gameRound.value.artistFound) {
                gameRound.value.artistFound = toFoundState(data.round.artist_found);
            }
            if (data.round.title_found && ! gameRound.value.titleFound) {
                gameRound.value.titleFound = toFoundState(data.round.title_found);
            }

            if (data.round.revealed) {
                gameRound.value.revealed = {
                    track: data.round.track,
                    artistFoundBy: data.round.artist_found?.found_by ?? null,
                    titleFoundBy: data.round.title_found?.found_by ?? null,
                };
            }
        }
    } else if (data.status === 'finished') {
        gameRound.value = null;
        finalScores.value = data.players.map((player) => ({ ...player }));
    }
};

let pollTimer = null;

const pollState = () => {
    axios.get(route('blindtest.rooms.state', props.room.public_id)).then((response) => {
        applyState(response.data);
    }).catch(() => {});
};

const startPolling = () => {
    if (pollTimer) return;
    pollState();
    pollTimer = setInterval(pollState, 2000);
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

watch(isPlayer, (value) => {
    if (value) startPolling(); else stopPolling();
}, { immediate: true });

watch(status, (value) => {
    if (value === 'finished') stopPolling();
});

onUnmounted(() => {
    clearTimeout(debounceTimer);
    clearTimeout(savedFlashTimer);
    if (channelName) {
        window.Echo.leave(channelName);
    }
    stopHeartbeat();
    stopPolling();
    document.removeEventListener('pointerdown', unlockAudio, true);
});
</script>

<template>
    <AppLayout title="Blind Test">
        <audio ref="unlockAudioEl" src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=" class="hidden" />

        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div><div class="qp-kicker">Blind Test</div><h2 class="qp-title mt-1">{{ $t('Game room') }}</h2></div>
                <span class="rounded-full bg-violet-100 px-4 py-2 text-xs font-black text-violet-700 dark:bg-violet-500/20 dark:text-violet-200">
                    {{ statusLabel }}
                </span>
            </div>
        </template>

        <div v-if="! isPlayer" class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="qp-card text-center sm:p-9">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-200"><HugeiconsIcon :icon="HeadphonesIcon" :size="30" :stroke-width="1.8" /></div>
                <h3 class="mt-5 text-2xl font-black text-gray-900 dark:text-white">Blind Test</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t(':count player(s)', { count: players.length }) }} · {{ $t(':count song(s)', { count: room.rounds_total }) }} · {{ $t(':count sec. per song', { count: room.seconds_per_round }) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('Categories: :names', { names: categoriesSummary }) }}
                </p>

                <div v-if="status === 'lobby'" class="mt-6 flex flex-wrap items-center gap-4">
                    <PrimaryButton v-if="page.props.auth.user" :disabled="joining" @click="joinRoom">
                        {{ $t('Join the room') }}
                    </PrimaryButton>
                    <template v-else>
                        <PrimaryButton :disabled="joiningAsGuest" @click="joinAsGuest">
                            {{ $t('Join as guest') }}
                        </PrimaryButton>
                        <Link :href="route('login')" class="text-sm font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                            {{ $t('Log in instead') }}
                        </Link>
                    </template>
                </div>
                <p v-else class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    {{ joinBlockedMessage }}
                </p>
            </div>
        </div>

        <div v-else-if="status === 'finished'" class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="qp-card p-8 text-center sm:p-10">
                <div class="mx-auto flex size-20 items-center justify-center rounded-[1.5rem] bg-violet-100 dark:bg-violet-500/20"><HugeiconsIcon :icon="CrownIcon" :size="40" class="text-violet-600 dark:text-violet-300" /></div>
                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ $t('Game over!') }}</p>

                <ul class="mx-auto mt-6 max-w-sm divide-y divide-gray-100 text-left dark:divide-gray-700">
                    <li
                        v-for="(player, index) in (finalScores ?? players)"
                        :key="player.user.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-5 text-sm font-bold text-gray-400">{{ index + 1 }}</span>
                            <img :src="player.user.profile_photo_url" :alt="player.user.name" class="size-9 rounded-full object-cover">
                            <span class="font-medium text-gray-900 dark:text-white">{{ player.user.name }}</span>
                        </div>
                        <span class="font-bold text-violet-600 dark:text-violet-400">{{ $t(':count pt(s)', { count: player.score }) }}</span>
                    </li>
                </ul>

                <Link :href="route('blindtest.index')" class="mt-6 inline-block">
                    <SecondaryButton>{{ $t('Back to Blind Test') }}</SecondaryButton>
                </Link>
            </div>
        </div>

        <div v-else class="mx-auto grid max-w-6xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="space-y-6 lg:col-span-2">
                <GameScreen v-if="status === 'playing' && gameRound" :round="gameRound" :room-id="room.public_id" @found="onSelfFound" />

                <div v-else-if="status === 'playing'" class="qp-card p-16 text-center">
                    <p class="text-lg font-semibold text-gray-500 dark:text-gray-400">{{ $t('Getting the first song ready…') }}</p>
                </div>

                <div class="qp-card">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Players (:count)', { count: players.length }) }}</h3>

                    <div v-if="page.props.auth.user?.is_guest && status === 'lobby'" class="mt-3">
                        <div v-if="! renamingGuest" class="flex items-center gap-2 text-sm">
                            <span class="text-violet-600 dark:text-violet-400">{{ $t('Playing as :name', { name: page.props.auth.user.name }) }}</span>
                            <button type="button" class="font-semibold text-violet-600 underline dark:text-violet-400" @click="renamingGuest = true">
                                {{ $t('Edit') }}
                            </button>
                        </div>
                        <form v-else class="flex gap-2" @submit.prevent="saveGuestName">
                            <TextInput v-model="guestNameForm.name" class="flex-1" autofocus />
                            <PrimaryButton type="submit" :disabled="guestNameForm.processing">{{ $t('Save') }}</PrimaryButton>
                        </form>
                    </div>

                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="player in players" :key="player.user.id" class="flex items-center gap-3 py-2">
                            <img :src="player.user.profile_photo_url" :alt="player.user.name" class="size-9 rounded-full object-cover">
                            <span class="font-medium text-gray-900 dark:text-white">{{ player.user.name }}</span>
                            <span v-if="player.user.id === room.host_id" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                {{ $t('Host') }}
                            </span>
                            <span v-if="status === 'playing'" class="ms-auto text-sm font-bold text-violet-600 dark:text-violet-400">
                                {{ $t(':count pt(s)', { count: player.score }) }}
                            </span>
                            <button v-if="isHost && player.user.id !== room.host_id" type="button" class="ms-auto inline-flex items-center gap-1.5 rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-500 transition hover:bg-rose-50 disabled:opacity-40 dark:border-rose-400/20 dark:hover:bg-rose-400/10" :disabled="kickingPlayerId === player.user.id" @click="kickPlayer(player)">
                                <HugeiconsIcon :icon="UserRemove01Icon" :size="16" /> {{ $t('Remove') }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div v-if="status === 'lobby' && isHost" class="qp-card">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Game settings') }}</h3>
                        <span v-if="savedFlash" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $t('Saved') }} ✓</span>
                        <span v-else-if="settingsSaving" class="text-xs text-gray-400">{{ $t('Saving…') }}</span>
                        <span v-else-if="settingsSaveError" class="text-xs font-bold text-rose-500">{{ $t('Save failed') }}</span>
                    </div>

                    <div class="mt-5 space-y-8">
                        <div class="cursor-pointer rounded-2xl border border-violet-100 p-4 transition hover:border-violet-300 hover:bg-violet-50/50 dark:border-white/10 dark:hover:border-violet-400/30 dark:hover:bg-white/[.03]" role="switch" tabindex="0" :aria-checked="settingsForm.is_public" @click="settingsForm.is_public = !settingsForm.is_public" @keydown.space.prevent="settingsForm.is_public = !settingsForm.is_public" @keydown.enter.prevent="settingsForm.is_public = !settingsForm.is_public">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex gap-3"><HugeiconsIcon :icon="Globe02Icon" :size="24" class="shrink-0 text-violet-500" /><div><span class="block text-sm font-black text-gray-700 dark:text-gray-200">{{ $t('Public room') }}</span><p class="mt-1 text-xs font-bold text-slate-400">{{ $t('Anyone can discover and join this room.') }}</p></div></div>
                                <Toggle v-model="settingsForm.is_public" @click.stop />
                            </div>
                        </div>

                        <div class="cursor-pointer rounded-2xl border border-violet-100 p-4 transition hover:border-violet-300 hover:bg-violet-50/50 dark:border-white/10 dark:hover:border-violet-400/30 dark:hover:bg-white/[.03]" role="switch" tabindex="0" :aria-checked="settingsForm.anti_cheat" @click="settingsForm.anti_cheat = !settingsForm.anti_cheat" @keydown.space.prevent="settingsForm.anti_cheat = !settingsForm.anti_cheat" @keydown.enter.prevent="settingsForm.anti_cheat = !settingsForm.anti_cheat">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex gap-3"><HugeiconsIcon :icon="Shield01Icon" :size="24" class="shrink-0 text-violet-500" /><div><span class="block text-sm font-black text-gray-700 dark:text-gray-200">{{ $t('Anti-cheat') }}</span><p class="mt-1 text-xs font-bold text-slate-400">{{ $t('Leaving the page blocks answers for the current round.') }}</p></div></div>
                                <Toggle v-model="settingsForm.anti_cheat" @click.stop />
                            </div>
                        </div>

                        <div>
                            <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Categories') }}</p>

                            <p v-if="! categories.length" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $t("No categories exist yet on the admin side.") }}
                            </p>

                            <CategoryPicker v-else v-model="settingsForm.category_ids" :categories="categories" class="mt-3" />
                        </div>

                        <div class="flex flex-wrap gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Number of songs') }}</label>
                                <NumberStepper
                                    v-model="settingsForm.rounds_total"
                                    :min="1"
                                    :max="50"
                                    :icon="MusicNote01Icon"
                                    class="mt-2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Seconds per song') }}</label>
                                <NumberStepper
                                    v-model="settingsForm.seconds_per_round"
                                    :min="5"
                                    :max="30"
                                    :step="5"
                                    :icon="Clock01Icon"
                                    class="mt-2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('Lives per round') }}</label>
                                <NumberStepper v-model="settingsForm.lives_per_round" :min="1" :max="10" :icon="FavouriteIcon" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="status === 'lobby'" class="qp-card">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Settings') }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ categoriesSummary }}
                        · {{ $t(':count song(s)', { count: settingsForm.rounds_total }) }} · {{ $t(':count sec. per song', { count: settingsForm.seconds_per_round }) }}
                    </p>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $t('Waiting for the host to start the game…') }}</p>
                </div>

                <div v-if="status === 'lobby' && isHost">
                    <InputError :message="startError" class="mb-2" />
                    <PrimaryButton
                        :disabled="starting || settingsForm.category_ids.length === 0"
                        @click="startGame"
                    >
                        {{ $t('Start the game') }}
                    </PrimaryButton>
                </div>
            </div>

            <div class="qp-card h-fit lg:sticky lg:top-24">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('Invite friends') }}</h3>

                <div class="mt-3 flex items-center gap-2">
                    <input
                        type="text"
                        :value="inviteLink"
                        readonly
                        class="w-full min-w-0 flex-1 cursor-pointer truncate rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 transition dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-300"
                        :class="linkHidden ? 'blur-sm select-none' : ''"
                        @click="copyInviteLink"
                    >
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        @click="linkHidden = ! linkHidden"
                    >
                        <HugeiconsIcon :icon="linkHidden ? ViewIcon : ViewOffIcon" :size="18" />
                    </button>
                    <SecondaryButton type="button" class="shrink-0" @click="copyInviteLink">
                        {{ linkCopied ? $t('Copied!') : $t('Copy') }}
                    </SecondaryButton>
                </div>
                <button v-if="isHost" type="button" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-violet-200 px-4 py-3 text-sm font-black text-violet-600 transition hover:bg-violet-50 dark:border-violet-400/20 dark:text-violet-300 dark:hover:bg-violet-500/10" @click="copySecretCode">
                    <HugeiconsIcon :icon="Key01Icon" :size="18" /> {{ codeCopied ? $t('Code copied!') : $t('Copy secret code') }}
                </button>

                <p v-if="! friends.length" class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('All your friends are already in the room (or you have none yet).') }}
                </p>

                <ul v-else class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="friend in friends" :key="friend.id" class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-3">
                            <img :src="friend.profile_photo_url" :alt="friend.name" class="size-9 rounded-full object-cover">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ friend.name }}</span>
                        </div>
                        <SecondaryButton @click="inviteFriend(friend)">{{ $t('Invite') }}</SecondaryButton>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
