# Repository Guidelines

## Project Structure & Module Organization

QuizParty is a Laravel 13 application with an Inertia.js/Vue 3 frontend. Backend code lives in `app/`: controllers handle requests, models represent data, services contain domain logic, and events/jobs support real-time games. Keep game-specific code namespaced, as shown by `app/Services/BlindTest/`, `app/Models/BlindTest/`, and `app/Http/Controllers/BlindTest/`.

Vue pages are in `resources/js/Pages`, components in `resources/js/Components`, layouts in `resources/js/Layouts`, and styles in `resources/css`. Routes are under `routes/`; migrations, factories, and seeders belong in `database/`. Tests are split between `tests/Feature` and `tests/Unit`. Do not edit generated files in `public/build`, `storage/framework`, or dependency directories.

## Build, Test, and Development Commands

- `composer setup` installs dependencies, creates `.env`, generates the key, migrates, and builds assets.
- `composer dev` runs Laravel, the queue listener, Vite, Reverb, and the scheduler concurrently.
- `npm run dev` starts only the Vite development server.
- `npm run build` creates production frontend assets.
- `composer test` clears cached configuration and runs the full test suite.
- `php artisan test --filter=AuthenticationTest` runs a focused test.
- `vendor/bin/pint` formats PHP using Laravel Pint.

Local Windows/Laragon installations may require explicit PHP, Composer, and Node paths. Tests use in-memory SQLite; ensure the CLI PHP build has `pdo_sqlite` enabled.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF endings, four-space indentation, final newlines, and two spaces for YAML. Follow PSR-12/Laravel conventions in PHP: `PascalCase` classes, `camelCase` methods, and `snake_case` database columns. Vue files use `PascalCase.vue`, `<script setup>`, and the `@/` alias for `resources/js`. Keep controllers thin and place reusable game rules in services.

## Testing Guidelines

Use PHPUnit 12. Feature tests cover HTTP, authentication, persistence, and game flows; unit tests cover isolated rules and services. Name files `*Test.php` and methods descriptively, such as `test_guest_cannot_start_a_round`. Add regression coverage for every bug fix. No minimum coverage threshold is configured, but new behavior should test success, validation, and authorization paths.

## Commit & Pull Request Guidelines

Git history is unavailable in this checkout. Use concise, imperative commit subjects, optionally with a conventional prefix, for example `feat: add blind-test round timer` or `fix: reject duplicate guesses`. Keep commits focused. Pull requests should explain behavior changes, list verification commands, link related issues, call out migrations or environment changes, and include screenshots for visible UI updates.

## Security & Configuration

Never commit `.env`, credentials, API tokens, or third-party secrets. Document new variables in `.env.example`, validate authorization server-side, and review broadcasting channel rules in `routes/channels.php` when exposing real-time events.
