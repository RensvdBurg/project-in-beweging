# UpMove (project-in-beweging)

Jongeren die leren voor een zittend beroep, zoals programmeren, lopen risico op gezondheidsproblemen door
langdurig zitten. UpMove stimuleert gezonde gewoontes door leerdoelen te combineren met korte beweegpauzes.

Gebouwd met **Laravel 13**, **Tailwind CSS 4** en **NativePHP Mobile 4** (iOS + Android).

## Installeren

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # maakt database/database.sqlite + testaccount
```

Testaccount: gebruikersnaam `test` (of `test@example.com`), wachtwoord `password`.

## Ontwikkelen in de browser

```bash
php artisan serve      # http://127.0.0.1:8000
npm run dev            # Vite + Tailwind met hot reload (altijd aan laten staan tijdens het coderen)
```

Of alles tegelijk: `composer run dev`.

## Draaien als mobiele app

```bash
php artisan native:install     # eenmalig, kies android/ios
npm run build
php artisan native:run
```

## Testen

```bash
php artisan test
```

## Iconen

Wil je een icoon gebruiken, gebruik dan `<i class="iconoir-naam"></i>` (te vinden op https://iconoir.com/).
