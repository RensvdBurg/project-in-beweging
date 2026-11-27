# 01 · Analyse van de oorspronkelijke vanilla PHP-versie

Dit document beschrijft de app **UpMove** (repo `project-in-beweging`) zoals die in vanilla PHP gebouwd was,
vóór de omzetting naar Laravel 13 + NativePHP Mobile. Het dient als naslag: welke functionaliteit er was,
waar die zat, en welke keuzes en problemen we tegenkwamen.

## Doel van de app

Jongeren die leren voor een zittend beroep (zoals programmeren) lopen risico op gezondheidsproblemen door
langdurig zitten. UpMove combineert leerdoelen met korte beweegpauzes: de gebruiker krijgt elke 90 minuten
een beweegopdracht ("challenge") en verdient XP door die te voltooien.

## Oude mappenstructuur

```
index.php                  Entry point: login óf home (+ footer)
pages/auth.php             Sessie, CSRF en ALLE POST-acties (login, register, logout,
                           next_challenge, complete_challenge)
pages/login.php            Login- en registratieformulier (één pagina, twee panelen)
pages/login.js             Wisselen tussen login- en registratiepaneel
pages/home/home.php        Home-scherm: challenge van het moment, XP, aftelklok, melding
pages/settings/settings.php  Instellingen (alleen client-side, localStorage)
pages/settings/account.php   Accountbeheer: profiel, wachtwoord, uitloggen, verwijderen
pages/assets/logo-icon.svg   Dubbele kopie van assets/logo-icon.svg (ongebruikt)
components/header.php      <head>-inhoud: meta, app-settings.js, iconoir, CSS, font
components/footer.php      Onderste navigatiebalk + JS voor schermwissel
assets/js/app-settings.js  Globaal: dark mode + werk/school-tijden (localStorage)
assets/js/settings-page.js Logica van de instellingenpagina (localStorage)
assets/js/account-page.js  Verwijderdialoog + wachtwoordbevestiging
assets/logo-icon.svg       Logo (ongebruikt in de pagina's)
database/database.php      PDO-verbinding met MySQL (env DB_HOST/DB_NAME/DB_USER/DB_PASSWORD)
src/input.css              Tailwind 4 bron-CSS (thema, dark mode, custom componenten)
css/output.css             Gecompileerde Tailwind-CSS (via `npm run dev`)
package.json               @tailwindcss/cli, tailwindcss 4.3.3, iconoir
```

## Functionaliteit per scherm

### Login / registratie (`index.php` als niet ingelogd)
- Inloggen met **e-mailadres óf gebruikersnaam** + wachtwoord (`password_verify`).
- Registreren met e-mail, wachtwoord (2×), geboortedatum (dag/maand/jaar-selects), volledige naam, gebruikersnaam.
- Validatie (server-side):
  - e-mail geldig en max. 254 tekens;
  - gebruikersnaam `^[A-Za-z0-9_.-]{3,30}$`;
  - naam 1–150 tekens;
  - wachtwoord 8–72 **bytes** (bcrypt-limiet), herhaling gelijk;
  - geboortedatum bestaand, niet in de toekomst, max. 110 jaar terug.
- Dubbele e-mail/gebruikersnaam → melding "Dit e-mailadres of deze gebruikersnaam is al in gebruik."
- Na registratie direct ingelogd.
- Meldingen via sessie-flash (`auth_message`, `auth_message_type`, `auth_mode`); `auth_mode=register`
  zorgt dat het registratiepaneel open staat na een fout.

### Home (`pages/home/home.php`)
- Begroeting met volledige naam en totaal aantal XP (XP wordt bij elke paginaweergave opnieuw uit de DB gelezen).
- Uitlogknop.
- Waarschuwingskaart "Te lang gezeten!"; wegklikken verbergt hem 60 minuten (localStorage, per gebruiker).
- **Challenge van het moment**:
  - de tijd is verdeeld in vaste blokken van 90 minuten (`intdiv(time(), 5400)`);
  - per blok wordt een willekeurige challenge gekozen en in de sessie bewaard (`home_challenge`),
    waarbij de vorige challenge wordt overgeslagen;
  - toont afbeelding, titel, moeilijkheid, kcal en te verdienen XP.
- Knop **Voltooid**: registreert een rij in `challenge_completions` (uniek per gebruiker + challenge + blok)
  en telt `xp_reward` op bij `users.xp`, in één transactie. Knop is disabled als al voltooid.
- Aftelklok tot het volgende blok; bij 0 wordt de pagina herladen.
- **Direct wisselen**: max. 2× per dag een andere challenge laden (teller in de sessie per gebruiker per datum).

### Instellingen (`pages/settings/settings.php`)
Volledig client-side, opgeslagen in `localStorage`:
- herinneringsfrequentie (15–120 min), dagelijks sta-doel (+/−);
- "alleen tijdens werk/school" met start-/eindtijd en werkdagen;
- toggles: bewegingsherinneringen, stille uren, geluidseffecten, **donkere modus**.
- Link naar accountinstellingen.

### Account (`pages/settings/account.php`)
- Profielkaart met initialen-avatar, naam en e-mail.
- Naam en e-mail wijzigen (e-mail uniek).
- Wachtwoord wijzigen (huidig wachtwoord verplicht, inklapbaar `<details>`-blok).
- Uitloggen.
- Account verwijderen via `<dialog>` met verplichte bevestigingscheckbox.

### Navigatie (`components/footer.php`)
- Vaste onderbalk met 5 items: Home, Bewegen, Leren, Statistieken, Instellingen.
- Bewegen/Leren/Statistieken bestonden nog niet als scherm; de JS valt dan terug op Home
  (`?screen=…` in de URL).

## Database (MySQL `bewegingsapp`)

De README verwees naar `database/schema.sql` en `database/challenge_completions.sql`, maar **die bestanden
zaten niet in het project**. Het schema is daarom gereconstrueerd uit de SQL-queries in de code:

| Tabel | Kolommen (afgeleid) |
|---|---|
| `users` | `id`, `email` (uniek), `username` (uniek), `password_hash`, `full_name`, `birth_date`, `xp` |
| `challenges` | `id`, `title`, `description`, `img` (URL), `kcal_burned`, `difficulty`, `xp_reward` |
| `challenge_completions` | `user_id`, `challenge_id`, `challenge_interval`, uniek op deze drie samen |

De inhoud van de `challenges`-tabel ontbrak ook; in de Laravel-versie zitten voorbeeldopdrachten.

## Gevonden problemen en aandachtspunten

| # | Probleem | Gevolg | Oplossing in Laravel-versie |
|---|---|---|---|
| 1 | Hardgecodeerde paden `/project-in-beweging/...` | Werkt alleen in die ene htdocs-map | `route()`- en `@vite`-helpers |
| 2 | Alle POST-logica in één `auth.php` | Moeilijk te onderhouden | Aparte controllers + `ChallengeService` |
| 3 | SQL-schema ontbrak in de repo | Niet reproduceerbaar | Laravel-migraties |
| 4 | MySQL vereist | Draait niet op een telefoon | SQLite (standaard voor NativePHP Mobile) |
| 5 | `settings.php` laadde `header.php` niet | Geen Montserrat-font en geen iconoir op die pagina | Eén gedeelde layout voor alle pagina's |
| 6 | Font en iconoir via CDN | Werkt niet offline in een app | Montserrat lokaal gebundeld via Vite (iconoir: zie styling-doc) |
| 7 | `<head><title>` midden in de `<body>` (home.php) | Ongeldige HTML | Titel via layout `@section('title')` |
| 8 | `pages/assets/logo-icon.svg` dubbel, `assets/logo-icon.svg` ongebruikt | Rommel | Eén logo in `public/images/` |
| 9 | `login.php` had een "Je bent ingelogd"-tak die nooit werd bereikt | Dode code | Weggelaten (login alleen voor gasten) |
| 10 | Divs in de challenge-kaart werden binnen een `if/else` geopend en gesloten | Ongebalanceerde HTML als er geen challenge is | Structuur 1-op-1 behouden met gebalanceerde tags |
| 11 | CSRF-fout gaf bij sommige acties een kale 400-pagina | Slechte UX | Globale handler: terug met melding "Je sessie is verlopen…" |
