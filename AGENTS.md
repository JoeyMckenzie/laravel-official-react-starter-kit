# Repository Guidelines

## Project Structure & Module Organization
- `app/` Laravel backend (HTTP, console, domain logic); configure via `config/`.
- `routes/` HTTP routes (Inertia controllers) and API endpoints.
- `resources/js/` React + TypeScript app: `pages/`, `components/`, `layouts/`, `routes/`, `hooks/`, `lib/`, `ssr.tsx`, `app.tsx`.
- `resources/views/` Blade stubs for Inertia entry.
- `public/` public assets; `database/` migrations, seeders, factories.
- `tests/` PHPUnit tests (`Unit/`, `Feature/`); `phpunit.xml` uses in‑memory sqlite.

## Build, Test, and Development Commands
- Install: `composer install` and `npm ci`.
- Dev (app + queue + logs + Vite): `composer dev`.
- Dev with SSR: `composer dev:ssr`.
- Frontend only dev server: `npm run dev`.
- Build assets: `npm run build` (SSR bundle: `npm run build:ssr`).
- PHP tests: `composer test` (runs `php artisan test`).
- PHP lint/format: `composer lint`, `composer fix`.
- JS/TS lint/format/types: `npm run lint`, `npm run format`, `npm run format:check`, `npm run types`.

## Coding Style & Naming Conventions
- PHP: PSR‑12, 4‑space indent, organized imports; format with Pint via `composer fix`.
- TypeScript/React: 2‑space indent, Prettier + ESLint. Components `PascalCase` (e.g., `resources/js/components/Button.tsx`); hooks `camelCase` starting with `use` (e.g., `hooks/useAuth.ts`).
- Tailwind: follow utility-first; Prettier plugin orders classes. Keep files colocated with features.

## Testing Guidelines
- Framework: PHPUnit; suites in `tests/Unit` and `tests/Feature`.
- Naming: `*Test.php`, one concern per test class. Extend `Tests\\AbstractTestCase` where applicable.
- Run locally: `composer test`. Prefer feature tests for controllers/routes; database uses in‑memory sqlite.

## Commit & Pull Request Guidelines
- Commits: imperative, scoped, concise (e.g., "Add user profile page").
- Before PR: `composer test`, `composer lint`, `npm run lint`, `npm run types`, and `npm run build` for UI changes.
- PRs: include description, linked issues, and screenshots/GIFs for UI; note migrations and config changes; update README if commands change.

## Security & Configuration Tips
- Do not commit `.env`. Copy `.env.example` to `.env` and run `php artisan key:generate`.
- Apply migrations with `php artisan migrate`. Store secrets in `.env`; tests run against sqlite memory DB.
