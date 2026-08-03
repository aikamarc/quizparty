# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

QuizParty: a hub of multiplayer/solo mini-games to play with friends (à la Kahoot/Jackbox). Laravel 13 + PHP 8.3 backend, Inertia.js + Vue 3 frontend, Jetstream for auth scaffolding, Tailwind CSS for styling, MySQL for storage. Local dev runs under Laragon at `http://quizparty.test`.

**Product vision, game specs, and roadmap status live in Claude's memory (`project_overview.md`, `design_preferences.md`), not here** — this file is for engineering/architecture facts derivable from the code. Ask if you need the product context.

## Local environment (Windows/Laragon)

`php`, `composer`, and `npm` are not on the default shell `PATH` in this environment. Prepend before running any command below:

```bash
export PATH="/c/laragon/bin/php/php_v8.3.15:/c/laragon/bin/nodejs/node-v22:$PATH"
```

Composer itself has no `composer` binary on `PATH` either — invoke it as `php /c/laragon/bin/composer/composer.phar <command>`. The Laragon PHP 8.3 CLI build has no `pdo_sqlite` extension enabled, so `php artisan test` (which runs against in-memory SQLite per `phpunit.xml`) fails locally with "could not find driver" — this is an environment gap, not a code issue; verify PHP changes with `php -l` and manual/browser testing instead, or ask the user before touching `php.ini` to enable the extension.

`pcntl` is not available on **any** Windows PHP build (it's a POSIX-only extension, not something `php.ini` can enable) — `laravel/pail` requires it and will crash immediately with "The [pcntl] extension is required to run Pail." It's deliberately left out of the `composer dev` script below for this reason; don't re-add it there. Use `storage/logs/laravel.log` (or the app's own broadcasting/queue logs) instead of Pail on this environment.

## Commands

```bash
composer dev          # runs server + queue listener + reverb + scheduler + vite concurrently — primary way to develop
php artisan serve     # backend only
npm run dev            # vite only
npm run build           # production frontend build

composer test          # clears config cache then runs the full artisan test suite
php artisan test --filter=TestName          # run a single test
php artisan test tests/Feature/SomeTest.php  # run a single test file

vendor/bin/pint         # PHP code style fixer (Laravel Pint)
```

Tests run against in-memory SQLite (`phpunit.xml` overrides `DB_CONNECTION`/`DB_DATABASE`) regardless of the app's normal MySQL connection — no DB setup needed to run the suite.

## Architecture

- **Inertia + Jetstream, not a JSON API.** Controllers return `Inertia::render('Page/Name', [...])`; pages live in `resources/js/Pages/**.vue` and map 1:1 to routes. There's no separate SPA build or REST API layer for the app itself (Sanctum/`routes/api.php` exists for token-based API access, currently unused/empty — Jetstream's `Features::api()` is disabled in `config/jetstream.php`).
- **Jetstream feature flags** (`config/jetstream.php`): only `Features::accountDeletion()` is enabled. Teams, API tokens, profile photos, and terms/privacy are commented out — enable there (and re-publish/adjust the corresponding Vue pages under `resources/js/Pages/`) before relying on them.
- **Auth stack**: Laravel Fortify (via Jetstream) handles login/register/2FA/password reset actions in `app/Actions/Fortify/`. `HandleInertiaRequests` middleware (`app/Http/Middleware/HandleInertiaRequests.php`) shares data (e.g. `auth.user`) to every Inertia page — add new global shared props there.
- **No Socialite installed yet** — needed for the planned Google login.
- **No `lang/` directory / i18n scaffolding yet** — Laravel 11+ ships without one by default; needed for the planned FR/EN support.
- **Frontend aliasing**: `@/*` maps to `resources/js/*` (see `jsconfig.json` and `vite.config.js`). Vue components are `<script setup>` SFCs; routing helper is Ziggy (`route()` available client-side).
- **Styling**: Tailwind v3 config at `tailwind.config.js`, scanning Jetstream's own Blade views plus `resources/js/**/*.vue`. `@tailwindcss/forms` and `@tailwindcss/typography` plugins are enabled. Font is Figtree.
- **DB**: MySQL in dev (`.env`, database `quizparty`), SQLite in tests. Only Jetstream/Fortify/Sanctum tables exist so far (`users`, `sessions`, `personal_access_tokens`, `passkeys`, two-factor columns) — no game-related tables yet.
- **Games are meant to be modular**: as new mini-games (blind test, songless, culture quiz, ...) get built, keep each game's logic/models/pages namespaced separately rather than coupling them to the core app, since the game list is expected to keep growing.
