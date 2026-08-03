<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TrackController;
use App\Http\Controllers\Admin\TrackImportController;
use App\Http\Controllers\Admin\SonglessController as AdminSonglessController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BlindTest\GuessController;
use App\Http\Controllers\BlindTest\RoomController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\SonglessController;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;

Route::get('/', HomeController::class)->name('home');
Route::get('/players/{user}', [PlayerProfileController::class, 'show'])->name('players.show');

Route::get('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', ['en', 'fr'])
    ->name('locale.switch');

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Public: a shared room link must work even for visitors without an account —
// they land on the same preview/lobby page and can join as a guest from there.
Route::prefix('blindtest')->name('blindtest.')->group(function () {
    Route::post('/rooms/join-by-code', [RoomController::class, 'resolveCode'])->name('rooms.resolve-code');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('/rooms/{room}/guest-join', [RoomController::class, 'guestJoin'])->name('rooms.guest-join');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');

    Route::get('/user/profile', [PlayerProfileController::class, 'own'])->name('profile.show');
    Route::get('/user/profile/settings', [UserProfileController::class, 'show'])->name('profile.settings');

    Route::patch('/guest/name', [GuestController::class, 'updateName'])->name('guest.name.update');

    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends', [FriendController::class, 'store'])->name('friends.store');
    Route::patch('/friends/{friendship}', [FriendController::class, 'update'])->name('friends.update');
    Route::delete('/friends/{friendship}', [FriendController::class, 'destroy'])->name('friends.destroy');

    Route::prefix('songless')->name('songless.')->group(function () {
        Route::get('/', [SonglessController::class, 'index'])->name('index');
        Route::get('/suggestions', [SonglessController::class, 'suggestions'])->name('suggestions');
        Route::post('/songs/{song}/guess', [SonglessController::class, 'guess'])->name('guess');
        Route::post('/songs/{song}/skip', [SonglessController::class, 'skip'])->name('skip');
    });

    Route::prefix('blindtest')->name('blindtest.')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::patch('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::post('/rooms/{room}/invite', [RoomController::class, 'invite'])->name('rooms.invite');
        Route::delete('/rooms/{room}/players/{player}', [RoomController::class, 'kick'])->name('rooms.players.destroy');
        Route::post('/rooms/{room}/start', [RoomController::class, 'start'])->name('rooms.start');
        Route::post('/rooms/{room}/heartbeat', [RoomController::class, 'heartbeat'])->name('rooms.heartbeat');
        Route::get('/rooms/{room}/state', [RoomController::class, 'state'])->name('rooms.state');
        Route::get('/rooms/{room}/secret-code', [RoomController::class, 'secretCode'])->name('rooms.secret-code');
        Route::post('/rooms/{room}/guess', [GuessController::class, 'store'])->name('rooms.guess');
        Route::post('/rooms/{room}/disqualify', [GuessController::class, 'disqualify'])->name('rooms.disqualify');
    });
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'can:admin',
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');
    Route::delete('/tracks/{track}', [TrackController::class, 'destroy'])->name('tracks.destroy');

    Route::get('/tracks/import', [TrackImportController::class, 'create'])->name('tracks.import.create');
    Route::post('/tracks/import/preview', [TrackImportController::class, 'preview'])->name('tracks.import.preview');
    Route::post('/tracks/import/store', [TrackImportController::class, 'store'])->name('tracks.import.store');

    Route::get('/tracks/{track}/edit', [TrackController::class, 'edit'])->name('tracks.edit');
    Route::put('/tracks/{track}', [TrackController::class, 'update'])->name('tracks.update');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::delete('/songless/today', [AdminSonglessController::class, 'resetToday'])->name('songless.reset-today');
});
