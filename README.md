# UpMove (project-in-beweging)

Jongeren die leren voor een zittend beroep, zoals programmeren, lopen risico op gezondheidsproblemen door
langdurig zitten. UpMove stimuleert gezonde gewoontes door leerdoelen te combineren met korte beweegpauzes.

Gebouwd met **Laravel 13**, **Tailwind CSS 4** en **NativePHP Mobile 4** (iOS + Android). Alle gegevens staan
in **MySQL** op de server; de app haalt ze via de API (`docs/08-api.md`).

## Installeren

1. Start MySQL (XAMPP Control Panel) en maak een lege database `upmove` aan
   (`utf8mb4_unicode_ci`, bijv. in phpMyAdmin). Voor de tests ook `upmove_testing`.
2. Zorg dat PHP `pdo_mysql` laadt: haal in je `php.ini` de `;` weg voor `extension=pdo_mysql`.
3. Daarna:

```bash
composer install
npm install
cp .env.example .env           # DB_CONNECTION=mysql, DB_DATABASE=upmove
php artisan key:generate
php artisan migrate --seed     # tabellen + challenges + testaccount
php artisan upmove:admin test  # optioneel: testaccount beheerder maken (/admin)
```

Testaccount: gebruikersnaam `test` (of `test@example.com`), wachtwoord `password`.

## Ontwikkelen in de browser

De pagina's halen hun gegevens bij de API. `php artisan serve` verwerkt maar één verzoek tegelijk, dus start
de API en de app als twee servers (zet in `.env`: `UPMOVE_API_URL=http://127.0.0.1:8124/api/v1`):

```bash
php artisan serve --port=8124  # API (+ admin-dashboard)
php artisan serve              # de app: http://127.0.0.1:8000
npm run dev                    # Vite + Tailwind met hot reload (altijd aan laten staan tijdens het coderen)
```

Het admin-dashboard staat op `/admin`.

## Draaien als mobiele app

```bash
php artisan native:install     # eenmalig, kies android/ios
npm run build
php artisan native:run
```

## Testen

```bash
php artisan test               # gebruikt de MySQL-database upmove_testing (zie phpunit.xml)
```

## Iconen

Wil je een icoon gebruiken, gebruik dan `<i class="iconoir-naam"></i>` (te vinden op https://iconoir.com/).


# project-in-beweging

Jongeren die leren voor een zittend beroep, zoals programmeren, lopen risico op gezondheidsproblemen door langdurig zitten. De app die wij gaan maken stimuleert gezonde gewoontes door leerdoelen te combineren met korte beweegpauzes.

# Icons

Wil je een icon gebruiken doe dan (iconoir-_naam-icon te vinden op: https://iconoir.com/)

# install

npm ci
npm run build
Tijdens ontwikkelen: npm run dev

# Database instellen

Voor een nieuwe installatie: maak een lege database `bewegingsapp` aan met utf8mb4_unicode_ci, selecteer deze en importeer via phpMyAdmin achtereenvolgens `database/schema.sql`, `database/challenge_completions.sql`, `database/migrations/001_login_rate_limits.sql`, `database/migrations/002_activity_interactions.sql` en `database/migrations/003_user_about_me.sql`. Importeer `database/schema.sql` niet over een bestaande database. Voer bij een bestaande installatie de nieuwe migratie `003_user_about_me.sql` eenmalig uit.

`database/challenge_completions.sql` registreert voltooide opdrachten, zodat dezelfde opdracht binnen de actieve opdrachtperiode maar één keer XP oplevert. De XP wordt opgeslagen in `users.xp`.

De standaardverbinding gebruikt `localhost`, database `bewegingsapp`, gebruiker `root` en een leeg wachtwoord. Stel voor een andere omgeving de variabelen `DB_HOST`, `DB_PORT` (standaard 3306), `DB_NAME`, `DB_USER` en `DB_PASSWORD` in.

Registratie bewaart het e-mailadres, de gebruikersnaam, volledige naam en geboortedatum. Het wachtwoord wordt als hash opgeslagen; het herhaalde wachtwoord wordt alleen gebruikt om de invoer te controleren.

Persoonlijke instellingen (bewegingsdoel/herinneringsfrequentie, werk- of schooldagen en -tijden, en dark mode) worden per account opgeslagen in de tabel `user_settings`. De tabel wordt automatisch aangemaakt bij het eerste bezoek van een ingelogde gebruiker; de databasegebruiker heeft daarvoor toestemming nodig om tabellen aan te maken. Bestaande instellingen uit de browser worden eenmalig naar het account overgezet wanneer daarvoor nog geen database-instellingen bestaan.

Gebruikers kunnen een optioneel stukje van maximaal 500 tekens over zichzelf opslaan in hun account. Deze tekst wordt bij hun ranglijstprofiel aan andere ingelogde gebruikers getoond.

# github push

voordat je begint doe je:

git switch develop
git pull origin develop
git switch -c feature/NaamVanFeature

dan als je klaar bent doe je git add .
git commit -m "korte naam die uitlegt wat je gedaan hebt"
git push -u origin feature/NaamVanFeature

daarna kan je op github een pull request aanmaken *base: develop* <- compare(je eigen branch)* van develop naar test daar ga je testen werkt dat merge je hem door naar release. eind van elke week word release naar main gemerged.
dan kan iemand de code revieuwen testen en mergen

# github error

als je dit krijgt tijdens het pushen: remote: Invalid username or token. Password authentication is not supported for Git operations. fatal: Authentication failed for '
RensvdBurg/project-in-beweging.git'

doe dan
git credential-manager github login --force
en log in via de browser

# github test

git switch test
git pull origin test
git switch -c fix/NaamVanFix

voor de rest is het hetzelfe als een normale push

# github merge

als je merged maak je ook een pull request aan. Nadat iemand dan je pr heeft gecontroleerd kan hij worden gemerged. we werken op develop. En testen op test. het gaat dus
Develop -> test -> release -> main
als je van develop iets naar test merged moet je er ook voor zorgen dat het op develop komt zodat die up to date blijft.
dit doe je door voor je merged te zorgen dat je base op develop staat en als je dan iets wilt testen merge je develop weer naar test.

# logica lars

develop -> git pull origin -> git switch -c feat/naam feature -> pull request aanmaken naar develop dan merge je dat -> merge het naar een test branch source test maak je daar een branch op aan is dat goed gegaan -> merge je het naar test branch -> merge je het naar release.

eind week merge je naar main
