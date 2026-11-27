# 05 · NativePHP Mobile

UpMove draait als native iOS- en Android-app via [NativePHP Mobile](https://nativephp.com) (`nativephp/mobile` v4).
NativePHP bundelt PHP en de volledige Laravel-app in de app; de schermen worden in een WebView getoond.
De gegevens (accounts, XP, challenges, activiteiten) staan **op de server in MySQL**; de app haalt alles via
de API (`docs/08-api.md`). De app werkt dus alleen met internet.

## Vereisten

| Platform | Nodig |
|---|---|
| Android | Android Studio + SDK (min. SDK 33), een emulator of toestel met USB-debugging |
| iOS | macOS met Xcode + iOS-simulator (een fysiek toestel vereist een Apple Developer-account) |
| Beide | PHP 8.3+, Composer, Node 20+ |

## Eenmalige installatie

```bash
# .env: zet een unieke app-id (omgekeerde domeinnaam)
NATIVEPHP_APP_ID=nl.upmove.app
NATIVEPHP_APP_VERSION=DEBUG        # tijdens ontwikkelen ALTIJD DEBUG laten staan (zie hieronder)
NATIVEPHP_APP_VERSION_CODE=1

# Native projecten (Android Studio / Xcode) en PHP-binaries downloaden
php artisan native:install            # kies android, ios of both
```

`native:install` maakt de map `nativephp/` aan (staat in `.gitignore`). Doe dit op iedere ontwikkelmachine.

## Draaien op emulator / toestel

```bash
npm run build                 # assets bouwen (verplicht!)
php artisan native:run        # kies platform en toestel
```

Handige commando's:

```bash
php artisan native:devices    # beschikbare toestellen/emulators
php artisan native:emulator   # Android-emulator starten
php artisan native:watch      # bestanden synchroniseren naar de draaiende app
php artisan native:jump       # app testen in de NativePHP Jump-app op je telefoon (zonder Xcode/Android Studio)
php artisan native:tail       # Laravel-log van het toestel bekijken
php artisan native:debug      # omgevingsinformatie
```

Hot reload tijdens ontwikkelen:

```bash
npm run dev -- --mode=android   # of --mode=ios
```

> **Let op – `NATIVEPHP_APP_VERSION`:** de app pakt de Laravel-code (en draait de migraties) alleen
> opnieuw uit als het versienummer verandert. Met `DEBUG` gebeurt dat bij élke build. Staat er een vast
> nummer (bv. `1.0.0`), dan blijft de app na `native:run` de oude code tonen. Zet pas een echt
> versienummer bij het publiceren (`php artisan native:release`).

## Configuratie

- `config/nativephp.php` — app-id, versie, start-URL, permissies, Android/iOS-opties.
- `vite.config.js` — bevat `nativephpMobile()` en `nativephpHotFile()` zodat assets in de app goed laden.
- `axios` moet als dependency in `package.json` staan (vereist door de NativePHP-Vite-plugin).
- Gevoelige `.env`-sleutels (`*_SECRET`, `DB_PASSWORD`, …) worden bij het bundelen automatisch verwijderd
  (`cleanup_env_keys`).

## Server en database

- Zet in `.env` vóór het bouwen `UPMOVE_API_URL` op de server, bijv. `https://upmove.example.nl/api/v1`.
  Tijdens ontwikkelen op hetzelfde wifi-netwerk: `http://<ip-van-je-computer>:8124/api/v1` (Android-emulator:
  `http://10.0.2.2:8124/api/v1`). Een `http://`-adres werkt alleen tijdens ontwikkelen; gebruik in een echte
  release altijd HTTPS.
- NativePHP zet op de telefoon zelf `DB_CONNECTION=sqlite` en draait bij het opstarten `migrate --force`. Dat is
  niet uit te zetten. Alle migraties met app-gegevens gebruiken daarom `App\Support\ServerOnlyMigration` en worden
  op de telefoon overgeslagen; die SQLite-database blijft leeg (alleen Laravels cache- en jobs-tabellen).
- `2026_11_28_090000_drop_local_app_data_on_phone` draait alleen op de telefoon en verwijdert de tabellen die
  oudere versies van de app daar aanmaakten.
- Het API-token staat in de sessie (een bestand) en in `SecureStorage` (Keychain/Keystore). Verloopt de sessie,
  dan logt de app met dat token automatisch opnieuw in.

## Plugins

| Plugin | Waarvoor |
|---|---|
| `nativephp/mobile-camera` | Galerijkiezer voor de afbeelding bij een activiteit (`Camera.PickMedia`) |
| `ikromjon/nativephp-mobile-local-notifications` | Meldingen op de telefoon: beweegherinneringen en nieuwe opdrachten (gratis, MIT) |

Plugins worden pas in de app meegebouwd als ze in `app/Providers/NativeServiceProvider.php` → `plugins()`
staan (`php artisan native:plugin:register <pakket>`). Een gewone `<input type="file">` werkt **niet** in de
Android-WebView van NativePHP; daarom gebruikt `resources/js/activities.js` in de app de native kiezer
(via `import('#nativephp')`, zie `imports` in `package.json`) en in de browser een gewone bestandsupload.

## Meldingen (werk/school)

Staat **Alleen tijdens werk/school** aan, dan plant de app meldingen op de telefoon, alleen binnen de
ingestelde dagen en tijden (ook als de app dicht is):

- **Beweegherinnering** ("Te lang gezeten!") elke *herinneringsfrequentie* minuten, vanaf de starttijd van die dag.
- **Nieuwe opdracht** aan het begin van elk challenge-blok van 90 minuten (`ChallengeService::INTERVAL_SECONDS`).
  Valt een herinnering binnen 5 minuten van een nieuwe opdracht, dan vervalt de herinnering.

Code: `App\Services\NotificationPlanner` (berekent het plan, getest in `NotificationPlannerTest`) en
`App\Services\PhoneNotifications` (zet het plan op de telefoon via de plugin). Er wordt opnieuw gepland na
het opslaan van instellingen en bij het openen van home (alleen als er iets veranderd is of het plan binnen
een dag opraakt). Bij uitloggen en account verwijderen worden alle meldingen gewist.

- **iOS-limiet:** iOS bewaart maximaal 64 geplande meldingen. We plannen er hooguit 60, over hooguit 7 dagen.
  Open je de app een week niet, dan stoppen de meldingen tot je hem weer opent.
- **Zomer-/wintertijd:** de tijden worden per melding als exact tijdstip berekend, dus dat klopt altijd.
- **Toestemming** wordt gevraagd zodra er voor het eerst iets te plannen valt (na het aanzetten van werk/school).
- In de browser doet dit niets (`config('upmove.phone_notifications')`: `null` = automatisch, alleen op het toestel).
- Na het installeren van de plugin moet de app opnieuw gebouwd worden (`php artisan native:run`), anders
  zitten de meldingsrechten (`POST_NOTIFICATIONS`, `SCHEDULE_EXACT_ALARM`) er niet in.

## Uitbreidingsideeën met native functies

NativePHP biedt PHP-API's voor native functies, bijvoorbeeld:
- **haptische feedback** bij het voltooien van een challenge;
- de **camera** voor een profielfoto.

Zie de NativePHP-documentatie voor de beschikbare facades en plugins (`php artisan native:plugin:list`).

## Publiceren

```bash
php artisan native:release            # versienummer ophogen
php artisan native:package android    # ondertekende .aab/.apk
php artisan native:package ios        # .ipa
```

Ondertekening (keystore, certificaten) wordt ingesteld met `php artisan native:credentials`.
