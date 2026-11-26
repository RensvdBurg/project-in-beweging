# UpMove – richtlijnen voor AI-assistenten

Laravel 13 + NativePHP Mobile 4 app (iOS/Android). Alle UI-teksten en code-commentaar zijn in het **Nederlands**.

Lees eerst `docs/03-architectuur.md` (routes, schema, bedrijfsregels) en `docs/04-styling.md`.

## Regels

- **Styling niet wijzigen** tenzij expliciet gevraagd: het design is 1-op-1 overgenomen uit de vanilla-versie.
  Gebruik de bestaande Tailwind-themakleuren (`bg-orange`, `text-muted`, …) en de dark-mode-klassen
  (`settings-card`, `settings-divider`, …) op nieuwe kaarten.
- De database is **SQLite** (draait op het toestel). Schemawijzigingen altijd via een migratie; standaarddata
  die op het toestel nodig is hoort in een migratie, niet (alleen) in een seeder.
- Challenge-logica hoort in `app/Services/ChallengeService.php`, niet in controllers of views.
- Geen hardgecodeerde URL's: gebruik `route()` en `asset()`; assets via `@vite`.
- Nieuwe JS-entrypoints toevoegen aan `input` in `vite.config.js`.
- Geen externe CDN's nodig maken voor kernfunctionaliteit: de app moet offline werken.
- Na wijzigingen: `php artisan test` en `vendor/bin/pint`.
