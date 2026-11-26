# UpMove – richtlijnen voor AI-assistenten

Laravel 13 + NativePHP Mobile 4 app (iOS/Android). Alle UI-teksten en code-commentaar zijn in het **Nederlands**.

Lees eerst `docs/03-architectuur.md` (routes, schema, bedrijfsregels) en `docs/04-styling.md`.

## Regels

- **Styling niet wijzigen** tenzij expliciet gevraagd: het design is 1-op-1 overgenomen uit de vanilla-versie.
  Gebruik de bestaande Tailwind-themakleuren (`bg-orange`, `text-muted`, …) en de dark-mode-klassen
  (`settings-card`, `settings-divider`, …) op nieuwe kaarten.
- De database is **MySQL** op de server (lokaal: XAMPP, database `upmove`; tests: `upmove_testing`).
  De app praat met de server via de API in `routes/api.php` (zie `docs/08-api.md`); geen gegevens in de
  SQLite-database die NativePHP op de telefoon aanmaakt. Schemawijzigingen altijd via een migratie;
  standaarddata (zoals challenges) hoort in een migratie, niet (alleen) in een seeder.
- Challenge-logica hoort in `app/Services/ChallengeService.php`, niet in controllers of views.
- Geen hardgecodeerde URL's: gebruik `route()` en `asset()`; assets via `@vite`.
- Nieuwe JS-entrypoints toevoegen aan `input` in `vite.config.js`.
- Geen externe CDN's nodig maken voor kernfunctionaliteit: de app moet offline werken.
- Na wijzigingen: `php artisan test`, `vendor/bin/pint` en `vendor/bin/phpstan analyse --memory-limit=1G`
  (MySQL moet draaien en PHP moet `pdo_mysql` laden; zie `docs/08-api.md`).
