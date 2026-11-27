# 03 · Architectuur van de Laravel-versie

> Alle gegevens staan in **MySQL** op de server. De controllers hieronder (de schermen van de app) halen ze
> via de API op met `App\Services\Api\UpMoveApi`; de API-controllers staan in `app/Http/Controllers/Api/V1`.
> Zie `docs/08-api.md`.

## Overzicht

```
app/
  Http/Controllers/
    AuthController.php        login, registratie, uitloggen
    HomeController.php        home-scherm
    ChallengeController.php   volgende challenge, challenge voltooien
    ActivityController.php    eigen activiteiten toevoegen, overzicht, detailpagina, afbeelding
    SettingsController.php    instellingenpagina
    AccountController.php     profiel, wachtwoord, account verwijderen
  Models/
    User.php                  + username, full_name, birth_date, xp
    Challenge.php
    ChallengeCompletion.php
    Activity.php
  Services/
    ChallengeService.php      90-minutenblokken, wissellimiet, XP toekennen
bootstrap/app.php             redirects voor gast/ingelogd + CSRF-afhandeling
database/migrations/          users, challenges, challenge_completions
resources/
  css/app.css                 Tailwind 4 (1-op-1 uit src/input.css)
  js/                         app-settings, footer-nav, login, home, settings-page, account-page
  views/
    layouts/app.blade.php     gedeelde <head> (vroeger components/header.php)
    components/footer-nav.blade.php
    auth/login.blade.php
    home.blade.php
    settings/index.blade.php
    settings/account.blade.php
routes/web.php
tests/Feature/                AuthTest, ChallengeTest, AccountTest, ActivityTest
```

## Routes

| Methode | URL | Naam | Middleware | Actie |
|---|---|---|---|---|
| GET | `/login` | `login` | guest | `AuthController@showLogin` |
| POST | `/login` | `login.store` | guest | `AuthController@login` |
| POST | `/register` | `register` | guest | `AuthController@register` |
| POST | `/logout` | `logout` | auth | `AuthController@logout` |
| GET | `/` | `home` | auth | `HomeController` (`?screen=home\|ranglijst\|leren`) |
| GET | `/leren/{challenge}` | `challenges.show` | auth | `ChallengeController@show` (beschrijvingspagina) |
| POST | `/challenges/next` | `challenges.next` | auth | `ChallengeController@next` |
| POST | `/challenges/complete` | `challenges.complete` | auth | `ChallengeController@complete` |
| GET | `/activiteiten` | `activities.index` | auth | `ActivityController@index` (formulier + laatste 5) |
| POST | `/activiteiten` | `activities.store` | auth | `ActivityController@store` |
| GET | `/activiteiten/overzicht` | `activities.all` | auth | `ActivityController@all` (5 per pagina, alleen bij > 5 activiteiten) |
| GET | `/activiteiten/{activity}/afbeelding` | `activities.image` | signed | `ActivityController@image` (zonder sessie; alleen in de app gebruikt) |
| GET | `/profiel/{user}` | `profile.show` | auth | `ProfileController@show` (`?from=home\|ranglijst`, `?activity_page=n`) |
| GET | `/statistieken` | `statistics` | auth | view `statistics.index` (nog zonder gegevens, zoals in de vanilla-versie) |
| GET | `/settings` | `settings` | auth | `SettingsController@index` |
| GET | `/settings/account` | `account` | auth | `AccountController@show` |
| PUT | `/settings/account/profile` | `account.profile` | auth | `AccountController@updateProfile` |
| PUT | `/settings/account/about` | `account.about` | auth | `AccountController@updateAboutMe` ("Over mij", max. 500 tekens) |
| PUT | `/settings/account/password` | `account.password` | auth | `AccountController@updatePassword` |
| DELETE | `/settings/account` | `account.destroy` | auth | `AccountController@destroy` |

Een gast die `/` opent, gaat naar `/login`; een ingelogde gebruiker die `/login` opent, gaat naar `/`.

## Databaseschema

### `users`
| Kolom | Type | Opmerking |
|---|---|---|
| id | integer PK | |
| email | string(254), uniek | |
| username | string(30), uniek | `^[A-Za-z0-9_.-]{3,30}$` |
| password | string | bcrypt-hash (cast `hashed`) |
| full_name | string(150) | |
| about_me | string(500), standaard `''` | uit de oude MySQL-database |
| birth_date | date | |
| xp | unsigned integer, standaard 0 | totaal verdiende XP |
| is_admin | boolean, standaard false | mag het admin-dashboard openen (zie `docs/07-admin-dashboard.md`) |
| remember_token, timestamps | | Laravel-standaard |

### `challenges`
| Kolom | Type |
|---|---|
| id | integer PK |
| title | string |
| description_extra | text, nullable (uitleg op de beschrijvingspagina; id 52–101 komen uit `bewegingsapp`). De oude kolom `description` is verwijderd (migratie `2026_11_27_090000`). |
| img | string (URL of pad onder `public/`) |
| kcal_burned | unsigned integer |
| difficulty | string: "Beginner", "Gevorderd" of "Expert" (`Challenge::DIFFICULTIES`; in het admin-dashboard een keuzelijst) |
| xp_reward | unsigned integer |

### `challenge_completions`
| Kolom | Type |
|---|---|
| id | integer PK |
| user_id | FK → users, cascade delete |
| challenge_id | FK → challenges, cascade delete |
| challenge_interval | unsigned big integer (`intdiv(timestamp, 5400)`) |
| timestamps | |
| **uniek** | (`user_id`, `challenge_id`, `challenge_interval`) |

### `activities`
| Kolom | Type |
|---|---|
| id | integer PK |
| user_id | FK → users, cascade delete |
| title | string(150) |
| description | text (max. 2000 tekens) |
| image_path | string, nullable (pad op de `local`-schijf: `activities/{userId}/{uuid}.{ext}`) |
| timestamps | |

### `activity_comments` en `activity_likes`
Reacties (`activity_id`, `user_id`, `body` max. 1000, timestamps) en likes (PK `activity_id` + `user_id`,
timestamps), beide met cascade delete. Overgenomen uit de oude MySQL-database; er is nog geen UI voor.

### `audit_logs`
Logboek voor het admin-dashboard: `user_id` (FK → users, wordt `null` als het account verdwijnt), `action`
(zie `AuditLog::ACTIONS`), `description`, `ip_address`, `created_at`. Schrijven via `AuditLog::record()`.
Zie `docs/07-admin-dashboard.md`.

### Import uit de oude MySQL-database
`php -d extension=pdo_mysql artisan upmove:import-mysql [--images=map]` kopieert gebruikers, voltooiingen,
posts (met afbeeldingen), reacties en likes uit XAMPP-database `bewegingsapp` (verbinding `bewegingsapp` in
`config/database.php`). Alleen voor lokaal gebruik; bestaande gebruikers worden overgeslagen.

## Eigen activiteiten (`ActivityController`)

- Via de knop **Activiteiten** rechtsboven op home.
- Titel en beschrijving zijn verplicht, de afbeelding is optioneel (JPG, PNG, WebP, GIF of HEIC, max. 10 MB).
- Het overzicht onder het formulier toont de **laatste 5**. Bij **meer dan 5** verschijnt de link naar
  `/activiteiten/overzicht`: alle activiteiten met afbeelding en volledige beschrijving, **5 per pagina**.
- Afbeeldingen staan in `storage/app/public/activities/{user_id}` (schijf `public`). `Activity::imageUrl()`
  geeft in de browser (met `storage:link`) direct `/storage/...` terug, zonder PHP-request. In de NativePHP-app
  (die geen bestanden buiten `public/` serveert) is het een ondertekende URL naar `activities.image`; die route
  draait zonder `web`-middleware (geen sessie/cookies), zodat elke afbeelding zo licht mogelijk is.

## Samen in beweging (`CommunityController`)

- Op home, onder de kaart "Direct de volgende opdracht starten?": de **laatste 5** activiteiten van
  **alle** gebruikers (scope `Activity::forFeed()`), als card (`<x-community-card>`): afbeelding,
  like- en reactieknop, gebruikersnaam, beschrijving en datum.
- Bij **meer dan 5** activiteiten verschijnt de link naar `/community` (`community.index`), 5 per pagina.
- `POST /activiteiten/{activity}/like` (`activities.like`) zet een like aan/uit (`activity_likes`).
- `POST /activiteiten/{activity}/reacties` (`activities.comment`) plaatst een reactie (max. 1000 tekens).
  Het reactiepaneel klapt open via `resources/js/community.js`; na plaatsen blijft het open.
- `DELETE /reacties/{comment}` (`comments.destroy`) verwijdert een reactie; alleen de schrijver (anders 403).
  Het kruisje rechts naast een reactie is alleen zichtbaar voor de schrijver.
- Alle drie sturen terug naar dezelfde pagina, bij de card (`#activiteit-{id}`).
- **Zonder herladen:** `community.js` verstuurt deze formulieren via `fetch` (`Accept: application/json`).
  De controller geeft dan `{html, error}` terug: de bijgewerkte `<x-community-card>` (422 bij een lege
  reactie), en het script vervangt de kaart op zijn plek. Mislukt de fetch, dan wordt het formulier
  gewoon verstuurd (met herladen).
- Activiteiten staan op de server: iedereen ziet dezelfde feed, op elke telefoon.
- **Afbeelding kiezen**: in de browser een gewone bestandsupload; in de NativePHP-app de native
  galerijkiezer (`nativephp/mobile-camera`). Die geeft een pad op het toestel terug (`native_image_path`).
  Dat pad wordt alleen geaccepteerd in de app, alleen uit de map `Gallery/` van de plugin met de naam
  `gallery_selected_*`, en alleen als het een echte afbeelding is. Daarna wordt de tijdelijke kopie verwijderd.
- Bij het verwijderen van een account worden ook de afbeeldingen verwijderd.

## Aanmelden en registreren (`AuthController`)

- Eén pagina (`resources/views/auth/login.blade.php`) met twee panelen; `resources/js/login.js` wisselt
  zonder herladen en regelt de knop "Wachtwoord tonen". Meldingen via `auth/partials/message.blade.php`.
- Inloggen met e-mailadres of gebruikersnaam, **zonder op hoofdletters te letten** (`LOWER()`, werkt
  ongeacht de collatie van de database). Registreren weigert ook "Sanne" als "sanne" al bestaat.
- Inlogbeveiliging (zoals vroeger `login_rate_limits`): max. 5 mislukte pogingen per account en 30 per
  IP-adres per 15 minuten, via Laravels `RateLimiter` (cache-driver `database`). Een geslaagde login zet
  de teller van dat account terug.
- Bij een fout bij registreren blijven de ingevulde velden staan, behalve de wachtwoorden en het bezette
  of ongeldige e-mailadres/gebruikersnaam.

## Leren (`HomeController`, `ChallengeController@show`)

- Scherm `#screen-leren` op home (`resources/views/challenges/screen.blade.php`, `?screen=leren`): alle
  challenges op ID met zoekveld (`resources/js/learn.js`, zoekt in titel, beschrijving en moeilijkheid).
- `GET /leren/{challenge}` (`challenges.show`): beschrijvingspagina met afbeelding, moeilijkheid, kcal, XP
  en `description_extra`. Navbar-item "Leren" is daar actief.
- Op home staat onder "Voltooien" de knop **Wat is dit?** naar de beschrijving van de huidige opdracht.
- Animaties: kaarten schuiven in (`#screen-leren` in `app.css`) en een slide-overgang tussen lijst en
  beschrijving (`resources/css/learn-page-transition.css`, cross-document view transitions). `learn.js`
  laat die overgang alleen toe bij links met `data-learn-transition`; terug via "Alle challenges"
  (`?transition=back`) zet `learn-page-transition-back` op `<html>` zodat de pagina van links inschuift.
- Unsplash-afbeeldingen worden in het juiste formaat geladen via `Challenge::imageUrl($width)`.

## Ranglijst en profiel (`HomeController`, `ProfileController`)

- Scherm `#screen-ranglijst` op home (`resources/views/leaderboard/screen.blade.php`), geopend via de
  navbar of `?screen=ranglijst`: podium (2-1-3), top 10 en "Jouw positie" als je buiten de top 10 valt.
- Volgorde: `User::leaderboard()` — meeste XP, dan gebruikersnaam (hoofdletterongevoelig via `LOWER()`),
  dan ID.
  `User::leaderboardRank()` telt met dezelfde regels.
- Profiel `/profiel/{user}`: initialen, "Over mij", totaal XP, rang en de activiteiten van die gebruiker
  (`<x-community-card>`, 5 per pagina). De gebruikersnaam op een activiteitkaart linkt naar het profiel.
- De ranglijst komt van de server en is voor iedereen gelijk.

## Bedrijfsregels (`ChallengeService`)

| Constante | Waarde | Betekenis |
|---|---|---|
| `INTERVAL_SECONDS` | 5400 | Een challenge-blok duurt 90 minuten |
| `DAILY_CHANGE_LIMIT` | 2 | Max. aantal keer per dag direct wisselen |

De challenge van het moment en de wissels per dag staan **per gebruiker in de database** (niet in de
sessie, zoals vroeger in `home_challenge` / `manual_challenge_changes_{userId}`): anders gaven uitloggen
en weer inloggen een nieuwe challenge en weer 2 wissels.

| Kolom (`users`) | Inhoud |
|---|---|
| `current_challenge_id`, `current_challenge_interval` | gekozen challenge en het blok waarvoor die geldt |
| `challenge_changes_date`, `challenge_changes_count` | datum (`Y-m-d`) en aantal directe wissels op die dag |

Een wissel gebeurt in een transactie (`lockForUpdate`), zodat twee snelle klikken allebei meetellen.

Sessiesleutels:

| Sleutel | Inhoud |
|---|---|
| `challenge_notice` (flash) | melding bij wisselen |
| `challenge_completion_notice` (flash) | melding bij voltooien |
| `auth_message`, `auth_message_type`, `auth_mode` (flash) | meldingen op het loginscherm |
| `status_message`, `status_type`, `password_form_open` (flash) | meldingen op de accountpagina |

Voltooien gebeurt in één databasetransactie: completion-rij invoegen (een dubbele rij geeft
"Je hebt deze opdracht al voltooid.") en daarna `users.xp` ophogen.

## Instellingen per gebruiker (`SettingsController`)

- Opgeslagen in `users.settings` (JSON); standaardwaarden en controle in `App\Support\UserSettings`
  (`User::preferences()` geeft altijd een volledige, geldige set).
- `settings-page.js` laadt ze uit `data-settings` en slaat elke wijziging op via
  `PUT /settings` (`settings.update`, JSON, 400 ms vertraging bij de schuifregelaar).
- De layout zet dark mode en werktijden uit de database op `<html>` (`class="upmove-dark"`,
  `data-dark-mode`, `data-work-hours`); `app-settings.js` geeft die voorrang boven localStorage.
- Werk/school: per dag een eigen start- en eindtijd in `workSchedule` (7 items, index = JavaScript
  `getDay()`, 0 = zondag), plus `workDays` (welke dagen meetellen). Instellingen van vóór de tijden per
  dag krijgen op elke dag `workStart`/`workEnd`. `UpMoveSettings.isActiveNow()` (in `app-settings.js`)
  kijkt naar de tijden van vandaag en ook naar een nachtdienst van gisteren (bijv. 22:00 - 06:00).
- Home: de melding "Te lang gezeten! (X min)" gebruikt de herinneringsfrequentie. Na het vinkje
  bewaart `home.js` het moment van wegklikken en toont de melding na X minuten opnieuw.

## Client-side

| Bestand | Doel | localStorage-sleutels |
|---|---|---|
| `app-settings.js` | dark mode toepassen, `window.UpMoveSettings` | `upmove:dark-mode`, `upmove:work-hours` (reserve; server gaat voor) |
| `settings-page.js` | instellingenpagina, opslaan via `settings.update` | `upmove:settings:{userId}` (reserve) |
| `home.js` | melding wegklikken + aftelklok | `project-in-beweging:alert-dismissed-at:{userId}` |
| `community.js` | reactiepaneel open/dicht | – |
| `footer-nav.js` | schermwissel zonder herladen | – |
| `login.js` | login ↔ registratie | – |
| `account-page.js` | verwijderdialoog, wachtwoordcontrole | – |
| `activities.js` | afbeelding kiezen (native galerij of bestandsupload + voorbeeld) | – |

`app-settings.js` wordt in de `<head>` geladen (niet als module), zodat dark mode wordt toegepast
vóórdat de pagina wordt getekend, net als vroeger.
