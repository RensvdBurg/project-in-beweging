# 08 · API (`/api/v1`)

De server bewaart alles in **MySQL**; de app praat met de server via deze API. Zie ook het plan
"UpMove naar een centrale server" en `docs/07-admin-dashboard.md`.

## Lokaal draaien

- XAMPP: start MySQL (MariaDB 10.4) via het XAMPP Control Panel.
- Databases: `upmove` (app) en `upmove_testing` (tests), beide `utf8mb4_unicode_ci`.
  `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=upmove`, gebruiker `root` zonder wachtwoord.
- PHP 8.5 (`C:\php85\cli`) laadt `pdo_mysql` nog niet standaard. Zet in `C:\php85\cli\php.ini` de regel
  `;extension=pdo_mysql` aan (puntkomma weg), of draai commando's met `php -d extension=pdo_mysql …`.
- Tests: `php artisan test` (na het aanzetten van `pdo_mysql`), anders
  `php -d extension=pdo_mysql vendor/phpunit/phpunit/phpunit`.

## Inloggen

`POST /api/v1/login` met `login` (e-mail of gebruikersnaam) + `password` (+ optioneel `device_name`) geeft
`{token, user}`. Daarna elk verzoek met `Authorization: Bearer <token>` en `Accept: application/json`.
Tokens zijn Laravel Sanctum-tokens (tabel `personal_access_tokens`), één per telefoon.

- Zelfde regels als in de app: max. 5 mislukte pogingen per account en 30 per IP per 15 minuten (429 +
  `Retry-After`). Algemene limiet: 120 verzoeken per minuut per gebruiker.
- Fouten: 422 `{message, errors: {veld: [melding]}}` met dezelfde Nederlandse meldingen als de app;
  401 zonder (geldig) token; 403 als iets niet mag.
- `is_admin` kan niet via de API worden gezet.

## Endpoints

| Methode | URL | Wat |
|---|---|---|
| POST | `/register` | `email`, `username`, `full_name`, `password`, `password_confirmation`, `birth_date` (Y-m-d) → 201 `{token, user}` |
| POST | `/login` | `login`, `password` → `{token, user}` |
| POST | `/logout` | trekt alleen het token van deze telefoon in |
| GET | `/home` | alles voor het home-scherm in één verzoek |
| GET | `/me` | eigen gegevens (incl. e-mail, geboortedatum, instellingen) + `rank` |
| PUT | `/me/profile` | `full_name`, `username`, `email` |
| PUT | `/me/about` | `about_me` (max. 500) |
| PUT | `/me/password` | `current_password`, `new_password`, `new_password_confirmation`; logt andere telefoons uit |
| PUT | `/me/settings` | zelfde velden als de instellingenpagina (`frequency`, `dailyGoal`, `darkMode`, …) |
| DELETE | `/me` | `confirm_delete: true`; verwijdert account, activiteiten, afbeeldingen en tokens |
| GET | `/me/activities` | eigen activiteiten, 5 per pagina (`?page=`, `?per_page=` max. 50) |
| GET | `/challenges` | alle challenges |
| GET | `/challenges/{id}` | één challenge |
| GET | `/challenges/current` | challenge van het blok, `completed`, `changes_remaining`, `next_change_at`, `xp` |
| POST | `/challenges/next` | direct wisselen (max. 2 per dag) → `message` + nieuwe stand |
| POST | `/challenges/complete` | voltooien → `message` + nieuwe stand (XP één keer per blok) |
| GET | `/leaderboard` | top 10 (`rankings`), `my_rank`, `me` |
| GET | `/users/{id}` | publiek profiel + `rank` (geen e-mail of geboortedatum) |
| GET | `/users/{id}/activities` | activiteiten van die gebruiker, gepagineerd |
| GET | `/activities` | community-feed van iedereen, nieuwste eerst, gepagineerd |
| POST | `/activities` | multipart: `title`, `description`, optioneel `image` (JPG/PNG/WebP/GIF/HEIC, max. 10 MB) → 201 |
| GET | `/activities/{id}` | één activiteit met reacties |
| POST | `/activities/{id}/like` | like aan/uit → bijgewerkte activiteit |
| POST | `/activities/{id}/comments` | `body` (max. 1000) → 201 + bijgewerkte activiteit |
| DELETE | `/comments/{id}` | eigen reactie verwijderen (anders 403) |

Een activiteit bevat `user`, `likes_count`, `comments_count`, `liked_by_viewer`, `image_url` en `comments`
(met `can_delete`). Een challenge bevat `img` (pad of URL) én `image_url` (volledige URL).

## Code

`routes/api.php`, `app/Http/Controllers/Api/V1/`, `app/Http/Resources/`, tests in `tests/Feature/Api/`.
De challengeregels blijven in `ChallengeService`; de API roept die aan.

## Hoe de app de API gebruikt

De schermen (controllers in `app/Http/Controllers`, views ongewijzigd) lezen niets uit een database:

| Onderdeel | Wat het doet |
|---|---|
| `App\Services\Api\UpMoveApi` | één methode per actie (`home()`, `toggleLike()`, `updateProfile()`, …) |
| `App\Services\Api\ApiClient` | HTTP naar `UPMOVE_API_URL` met het token; fouten worden `ApiException` |
| `App\Services\Api\ApiHydrator` | zet JSON om naar `User`/`Challenge`/`Activity`-modellen (alleen in het geheugen) zodat de views hetzelfde blijven |
| `App\Auth\ApiSessionGuard` | de `web`-guard (`config/auth.php`): token + gebruiker in de sessie, op de telefoon ook in SecureStorage |
| `bootstrap/app.php` | `ApiException`: 401 → uitloggen en naar login · 403/404 → foutpagina · geen verbinding → `errors.offline` |

`GET /api/v1/home` geeft alles voor het home-scherm in één verzoek (gebruiker, challenge, eerste 5 van de
community, ranglijst, alle challenges). Het admin-dashboard gebruikt dezelfde login maar leest op de server
rechtstreeks uit MySQL; `EnsureUserIsAdmin` controleert `is_admin` in de database.

## Lokaal ontwikkelen in de browser

PHP's ingebouwde server verwerkt op Windows maar één verzoek tegelijk; een pagina die zijn eigen API aanroept
blijft dan wachten. Start daarom twee servers (vanuit de map `public`):

```bash
# API
php -d extension=pdo_mysql -S 127.0.0.1:8124 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
# App, die de API hierboven gebruikt (in .env: UPMOVE_API_URL=http://127.0.0.1:8124/api/v1)
php -d extension=pdo_mysql -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Op een echte server (Apache/Nginx) is dit niet nodig.

## Tests

In tests (`UPMOVE_API_IN_PROCESS=true` in `phpunit.xml`) handelt `ApiClient` API-verzoeken af in hetzelfde
proces; `Tests\TestCase::actingAs()` geeft de gebruiker een echt token. Zo test elke schermtest het hele pad
scherm → API → MySQL. `tests/Feature/AppUsesApiTest.php` test de app met echte (nagebootste) HTTP: o.a. dat het
home-scherm geen enkele databasequery doet, de offline-pagina en het opnieuw inloggen via SecureStorage.
