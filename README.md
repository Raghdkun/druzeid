# منظمة الهوية الدرزية — Druze Identity Organization

A Laravel + React rebuild of [druze.id](https://druze.id), replacing the WordPress/Colibri
original with an RTL-first, multilingual institutional site and a custom CMS.

## Stack

- **Laravel 13** · Inertia 3 · React 19 · TypeScript · Tailwind CSS 4 · Vite 8
- Rich text: **Tiptap 3** (HTML) sanitised with **stevebauman/purify**
- Auth: **Laravel Fortify** (2FA + passkeys) — staff-only, public registration disabled
- Testing: **Pest 4** · PHPStan/Larastan level 7 · Pint · ESLint
- Database: **PostgreSQL 14**

## What Phase 1 delivers

- **Public site** — the 11 Arabic pages rebuilt from real data, in an institutional
  design (Druze five-colour palette, Cairo + IBM Plex Sans Arabic, chrome-free photo
  cards, restrained blue-as-structure). Light-only, like un.org.
- **i18n** — Arabic (default, RTL, no URL prefix) plus English, French and Dutch
  (`/en`, `/fr`, `/nl`). JSON-column translations with an `ar → en → key` fallback.
  Every UI string, including `alt`/`aria-label`, is translatable — nothing hardcoded.
- **CMS** (`/cms`) — Pages (section builder, per-locale field tabs, completeness
  indicators), Media (with a required-Arabic-alt review queue), Menus, Settings, Users
  (admin/editor roles). RTL, dark-mode capable.
- **Schema for Phase 2** — News, Directory, Archives, Business, Madafa, Projects,
  Memberships and Partners tables exist; their CMS UI is Phase 2.

## Getting started

```bash
composer install && npm install
cp .env.example .env      # then set DB_* and APP_KEY (php artisan key:generate)
php artisan migrate:fresh --seed     # imports content + 153 images from _extracted/
php artisan storage:link
npm run dev                          # or: npm run build
php artisan serve
```

Local admin (seeded): `admin@druze.id`. Set a password with
`php artisan tinker` before signing in.

## Content provenance

`_extracted/` holds everything pulled from the WordPress original (165 images,
per-page text, the REST/manifest JSON). The seeders read from it, so the database
can be rebuilt from source at any time — see `database/seeders/`.

## Quality gates

```bash
php artisan test          # Pest
./vendor/bin/pint --test  # formatting
./vendor/bin/phpstan analyse
npm run types:check && npm run lint
```

## Architecture notes

- `app/Concerns/HasTranslations.php` — JSON-column translations for models.
- `app/Support/{Locale,Translate,ArabicText}.php` — locale resolution, nested
  locale-map collapsing, and Arabic text normalisation (tatweel/harakat).
- `app/Services/SiteChrome.php` — cached nav + settings shared with every public page.
- `resources/js/components/sections/` — one component per section type, wired through
  `section-renderer.tsx`. Adding a type is one component plus one registry entry.
