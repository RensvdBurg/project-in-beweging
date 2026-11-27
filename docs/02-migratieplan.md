# 02 · Migratieplan vanilla PHP → Laravel 13 + NativePHP Mobile

> **Historisch.** Dit plan ging uit van een SQLite-database op elk toestel. Inmiddels staan alle gegevens
> in MySQL op de server en praat de app via de API (zie `08-api.md`).

## Uitgangspunten

1. **Functioneel gelijk**: alle bestaande functionaliteit (zie `01-analyse-vanilla-php.md`) blijft werken,
   met dezelfde Nederlandse meldingen.
2. **Styling 1-op-1**: dezelfde HTML-structuur en Tailwind-klassen; `src/input.css` wordt
   `resources/css/app.css` met alleen aangepaste `@source`-paden (zie `04-styling.md`).
3. **Mobile first**: de app moet als native app op iOS en Android draaien via NativePHP Mobile.
   Daarom: SQLite, geen externe CDN's nodig voor de basisweergave, geen hardgecodeerde paden.
4. **Laravel-conventies**: routes, controllers, Eloquent-modellen, migraties, Blade-componenten.

## Mapping oud → nieuw

| Oud | Nieuw |
|---|---|
| `index.php` | `routes/web.php` (`/` → home, `/login` → login) |
| `pages/auth.php` (login/register/logout) | `app/Http/Controllers/AuthController.php` |
| `pages/auth.php` (next/complete challenge) | `app/Http/Controllers/ChallengeController.php` + `app/Services/ChallengeService.php` |
| `pages/login.php` | `resources/views/auth/login.blade.php` |
| `pages/login.js` | `resources/js/login.js` |
| `pages/home/home.php` | `HomeController` + `resources/views/home.blade.php` |
| `pages/settings/settings.php` | `SettingsController@index` + `resources/views/settings/index.blade.php` |
| `pages/settings/account.php` | `AccountController` + `resources/views/settings/account.blade.php` |
| `components/header.php` | `resources/views/layouts/app.blade.php` (`<head>`) |
| `components/footer.php` | `resources/views/components/footer-nav.blade.php` + `resources/js/footer-nav.js` |
| `assets/js/app-settings.js` | `resources/js/app-settings.js` (via Vite, in `<head>`) |
| `assets/js/settings-page.js` | `resources/js/settings-page.js` |
| `assets/js/account-page.js` | `resources/js/account-page.js` |
| `database/database.php` (PDO/MySQL) | `config/database.php` + `.env` (SQLite) |
| ontbrekende `schema.sql` | `database/migrations/*` |
| ontbrekende challenge-data | migratie `create_challenges_table` met voorbeelddata |
| `src/input.css` | `resources/css/app.css` |
| `css/output.css` | `public/build/` (gegenereerd door `npm run build`) |
| `assets/logo-icon.svg` | `public/images/logo-icon.svg` |

## Stappen

1. Nieuw Laravel 13-project (`composer create-project laravel/laravel:^13.0`).
2. `composer require nativephp/mobile` en NativePHP-config publiceren.
3. Migraties voor `users` (uitgebreid), `challenges`, `challenge_completions`.
4. Modellen `User`, `Challenge`, `ChallengeCompletion` met relaties.
5. `ChallengeService` met de 90-minutenlogica, de wissellimiet en het voltooien.
6. Controllers, routes en middleware (`auth`/`guest`).
7. Blade-layout + views: markup uit de oude PHP-bestanden letterlijk overnemen, PHP-echo's vervangen door
   Blade (`{{ }}`), formulieren krijgen `@csrf` en `route()`-acties.
8. CSS en JS naar `resources/`, Vite configureren (Tailwind 4 + NativePHP-plugin + lokaal font).
9. Exception handler voor verlopen CSRF-tokens (nette melding i.p.v. 419-pagina).
10. Feature-tests voor login, registratie, challenges en account.
11. Oude vanilla-bestanden verwijderen en de docs bijwerken.

## Bewust gemaakte keuzes

- **SQLite i.p.v. MySQL**: NativePHP Mobile draait PHP óp het toestel; daar is geen MySQL-server.
  Lokaal ontwikkelen kan nog steeds met MySQL door `DB_CONNECTION=mysql` in `.env` te zetten.
- **Kolom `password` i.p.v. `password_hash`** en Laravel's `hashed`-cast: standaard Laravel-auth.
- **Challenge-staat blijft in de sessie** (zoals voorheen); op een telefoon is er maar één gebruiker,
  en de database-sessiedriver bewaart hem in SQLite.
- **Instellingen blijven in `localStorage`** (zoals voorheen); dat werkt in de WebView van NativePHP.
- **Uitloggen** is één route (`POST /logout`) voor zowel home als accountpagina.
- **Formulierinvoer** (behalve wachtwoorden) blijft na een validatiefout ingevuld (`old()`).

## Status (19-11-2026)

Alle stappen zijn uitgevoerd. De vanilla-bestanden (`index.php`, `pages/`, `components/`, `assets/`, `css/`,
`src/`, `database/database.php`) zijn verwijderd; een backup staat naast de projectmap in
`project-in-beweging-vanilla-backup.tar.gz`. 16 feature-tests (login, registratie, challenges, account) slagen.

Nog te doen:
- `php artisan native:install` en de app testen op een echte emulator/toestel.
- De voorbeeldchallenges in de migratie vervangen door de echte opdrachten en afbeeldingen
  (de oorspronkelijke `challenges`-data zat niet in de repo).
- Schermen voor Bewegen, Leren en Statistieken bouwen (de navigatie bestaat al).
