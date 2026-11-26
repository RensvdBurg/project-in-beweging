# 04 · Styling (1-op-1 overgenomen)

De styling van de vanilla-versie is **ongewijzigd** overgenomen. Er is geen klasse, kleur of maat aangepast.

## Wat is er gebeurd

| Vroeger | Nu |
|---|---|
| `src/input.css` | `resources/css/app.css` — inhoud identiek, alleen de `@source`-regels wijzen naar `resources/views` en `resources/js` |
| `npx @tailwindcss/cli … --watch` → `css/output.css` | Vite + `@tailwindcss/vite` → `public/build/` (`npm run dev` / `npm run build`) |
| `<link href="/project-in-beweging/css/output.css?v=…">` | `@vite('resources/css/app.css')` (cache-busting via hash in bestandsnaam) |
| Montserrat via Google Fonts-CDN | Montserrat 400/500/600/700 lokaal gebundeld via `laravel-vite-plugin/fonts` (`@fonts`) — zelfde lettertype, werkt offline |
| iconoir via jsDelivr-CDN | ongewijzigd CDN-link in de layout (werd nergens gebruikt; zie hieronder) |
| Tailwind 4.3.3 | Tailwind 4.x (zelfde major/minor-lijn) |

De HTML-structuur en `class`-attributen in de Blade-views zijn letterlijk gekopieerd uit de oude
PHP-bestanden. Alleen de PHP-echo's (`<?= … ?>`) zijn vervangen door Blade (`{{ … }}`).

## Thema (uit `@theme`)

Gebruik de kleuren als Tailwind-klassen, bijvoorbeeld `bg-orange`, `text-muted`, `bg-teal-soft`:

| Token | Waarde | Gebruik |
|---|---|---|
| `orange` | `#ff6b4a` | primaire knoppen, actieve navigatie |
| `orange-soft` | `#ffe8e1` | boost-kaart, avatar |
| `teal` | `oklch(0.7 0.13 181)` | Voltooid-knop, timerkaart |
| `teal-soft` | `#e8faf7` | icoonachtergrond |
| `muted` | `#656c77` | subtitels (5,3:1 op wit; was `#8a8f98`, te licht) |
| `ink` | `#1a1a1a` | hoofdtekst |
| `surface` / `card` | wit | kaarten |
| `background` / `app-bg` | lichtgrijs | pagina-achtergrond |
| `primary` | `oklch(0.705 0.183 33)` | login-/registratieknoppen |
| `alert-*` | geel | waarschuwingskaart |
| `shadow-card` | `0 8px 28px rgba(20,24,40,.06)` | kaartschaduw |

## Dark mode

Dark mode werkt met de klasse `upmove-dark` op `<html>` (gezet door `resources/js/app-settings.js`
op basis van `localStorage['upmove:dark-mode']`). De overrides staan onderaan `resources/css/app.css`
en gebruiken vaste klassen: `.upmove-footer`, `.settings-card`, `.settings-divider`, `.settings-label`,
`.settings-description`, `.settings-button`, `.settings-input`, `.settings-work-options`,
`.settings-day-label`, `.account-surface`, `.account-input`, `.account-avatar`, `.account-dialog`.
**Gebruik deze klassen ook op nieuwe kaarten**, anders blijven ze wit in dark mode.

Daarnaast zet de laag **"DARK MODE: VASTE LICHTE KLEUREN OMZETTEN"** onderaan `app.css` elke vaste lichte
kleur (`text-[#656c77]`, `border-[#eceef2]`, `bg-red-50`, …) om naar één donkere tegenhanger. Gebruik je
een nieuwe vaste kleur, voeg die daar dan ook toe.

### Leesbaarheid (contrast)

Tekst moet minimaal **4,5:1** contrast hebben (WCAG AA). Gebruik in licht thema voor grijze tekst:

| Klasse | Op wit | Gebruik |
|---|---|---|
| `text-[#5f6672]` | 5,8:1 | kopjes in hoofdletters (sectielabels) |
| `text-[#656c77]` / `text-muted` | 5,3:1 | beschrijvingen, datums, inactieve navigatie |
| `placeholder:text-[#8f96a0]` | 3:1 | alleen voor voorbeeldtekst in velden |

Niet meer gebruiken (te licht): `#9299a3`, `#858c97`, `#7d858f`, `#777f8a`, `#9aa0a8`, `#b3b8bf`.
In dark mode is gedempte tekst `#a3acba` (6,6:1 op een kaart).

De aanmeldpagina is bewust altijd licht (`.auth-page`).

## Mobiel

- `viewport-fit=cover` + `env(safe-area-inset-*)` voor notch/home-indicator (al in het design).
- `min-h-dvh` voor de volle schermhoogte in de WebView.
- Inhoud is begrensd op `max-w-lg` en gecentreerd.

## Iconen

De README vroeg om iconoir-iconen (`<i class="iconoir-…"></i>`, zie https://iconoir.com/). Het CDN-stylesheet
staat nog in de layout, zodat dit blijft werken als het toestel online is. Voor volledig offline gebruik:
`npm i iconoir` en in `resources/css/app.css` `@import "iconoir/css/iconoir.css";` toevoegen
(maakt de CSS-bundel groter). De huidige schermen gebruiken alleen inline SVG's.

## Werken aan de styling

```bash
npm run dev     # Vite dev-server met hot reload
npm run build   # productiebuild (nodig vóór native:run)
```

Klassen die alleen dynamisch (in JS) worden toegevoegd, moeten in de `@source inline(...)`-regel
in `resources/css/app.css` staan, anders genereert Tailwind ze niet.

## Controle na de migratie

De gegenereerde CSS is vergeleken met de oude `css/output.css` (beide Tailwind 4.3.3, dezelfde bron-CSS):

- Alle klassen die in de views gebruikt worden, zitten in de nieuwe build.
- De oude `output.css` bleek **verouderd**: er ontbrak `mt-2` (wel gebruikt in de verwijderdialoog op de
  accountpagina) en er stonden nog klassen in van code die niet meer bestond (`left-1/2`, `bg-[#fff0ec]`, …).
  In de nieuwe build krijgt de tekst in die dialoog dus wél de bedoelde `margin-top`.
- Tailwind is in `package.json` exact vastgezet op `4.3.3`, zodat de uitvoer gelijk blijft.
