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
## CI en reproduceerbare installatie

Gebruik Node.js 24 en PHP 8.2 met PDO MySQL en mbstring. De CI-database is expliciet
MariaDB 10.4.32, dezelfde versie als deze XAMPP-installatie. Dit is een compatibiliteits-
baseline, geen aanbeveling voor nieuwe hosting; plan een afzonderlijke upgrade naar
een ondersteunde MariaDB-versie en voer deze tests daarbij opnieuw uit.

```sh
npm ci --no-audit
npm run lint:php
npm run build
npm run check:build
npm audit --audit-level=high
```

In PowerShell kan PHP worden geselecteerd met:

```powershell
$env:PHP_BINARY = 'C:\xampp\php\php.exe'
```

`npm run dev` kopieert eerst de Iconoir-assets en start vervolgens de CSS-watcher.
`npm run build` bouwt eenmalig geminificeerde Tailwind-CSS en kopieert de vastgelegde
Iconoir-versie met licentie. De applicatie laadt Iconoir lokaal in plaats van vanaf
een veranderlijke CDN-mainbranch. `check:build` controleert alle gegenereerde assets.
`node_modules` werd eerder al gevolgd voordat de ignore-regel bestond; deze bestanden
zijn nu gericht uit de index verwijderd. De lokale bestanden blijven behouden.
Ook `css/output.css` wordt voortaan gegenereerd. Commit package.json, package-lock.json
en src/input.css; voer na een schone checkout altijd `npm ci` en `npm run build` uit.
Lever bij toekomstige deployment de gegenereerde CSS en Iconoir-licentie mee.

### Databasebaseline

Importeer in een nieuwe, vooraf geselecteerde lege database achtereenvolgens:

1. `database/schema.sql`
2. `database/challenge_completions.sql`
3. `database/migrations/001_login_rate_limits.sql`

Het users-schema is hersteld uit commit `5f163ba`; het completions-schema uit
`7b12dbd`. De samengestelde primaire sleutel op gebruiker, opdracht en periode is
nodig voor de bestaande afhandeling van dubbele voltooiing. `xp` en de challenges-
kolommen zijn afgeleid uit de actuele queries. De gekozen tekstlengtes en unsigned
numerieke typen voor challenges vormen een expliciete nieuwe baseline; er is geen
bestaand challenges-schema teruggevonden in de onderzochte SQL-historie. Instellingen
en activiteiten volgen de bestaande CREATE TABLE-definities. Er zijn geen nieuwe
foreign keys of verwijderregels geïntroduceerd. De historische BIGINT-gebruikers-ID
versus INT in instellingen/activiteiten is behouden; een wijziging daarvan vraagt
om een aparte migratie voor bestaande databases.

Deze SQL is bedoeld voor lege databases, bevat geen CREATE DATABASE, USE of DROP
en faalt op bestaande tabellen. Het is geen migratie van een bestaande installatie.
`tests/fixtures.sql` bevat alleen een synthetische opdracht en wordt uitsluitend door
de tests geïmporteerd. Echte challenge-inhoud moet voor een nieuwe installatie nog
worden toegevoegd door het team.

### Functionele tests lokaal

Met de standaard Windows-XAMPP-installatie:

```sh
npm run build
npx playwright install chromium
npm run test:local
```

Deze opdracht initialiseert een eigen tijdelijke MariaDB-datamap onder `work/`, kiest
een vrije loopbackpoort, start de server en voert beide testsuites uit. Na afloop
worden de testserver en datamap verwijderd, ook bij een testfout. Bestaande XAMPP-
servers en databases worden niet gebruikt. Optioneel: `XAMPP_MYSQL_BIN`, `PHP_BINARY`
en `TEST_TMP_DIR` voor afwijkende paden. Gebruik voor de tijdelijke map een kort pad;
de oudere MariaDB Windows-bootstrap werkt niet betrouwbaar met lange temp-paden.

Voor een reeds gestarte, uitsluitend voor tests bestemde loopback-databaseserver:

```powershell
$env:TEST_DB_PORT = '33317' # vervang door de poort van de testserver
$env:TEST_DB_USER = 'root'
# TEST_DB_PASSWORD alleen indien de testserver een wachtwoord vereist
npm test
```

Op Linux stel je dezelfde variabelen in via de shell. Gebruik hiervoor nooit een
lokale ontwikkel- of productieserver. De testgebruiker moet databases kunnen aanmaken
én verwijderen. Elke run maakt een willekeurige `in_beweging_test_<16 hextekens>`-
database aan zonder IF NOT EXISTS, importeert het schema en verwijdert uitsluitend
deze eigen database na afloop. Er worden geen echte gebruikersgegevens gebruikt.

Afzonderlijk: `npm run test:smoke` vereist geen database; `npm run test:integration`
vereist de expliciete TEST_DB-configuratie. Er zijn geen stilzwijgende skips.

Getest via echte HTTP-routes met afzonderlijke sessies:

- Anonieme afscherming van home en instellingen.
- Registratie, veilige wachtwoordhash en apart dubbele e-mail/gebruikersnaam.
- Geldige en ongeldige login, uitloggen en blijvende instellingen na opnieuw inloggen.
- Ongeldige CSRF bij registratie, instellingen en opdrachtvoltooiing.
- Exacte XP-toekenning en geen tweede toekenning binnen dezelfde periode.
- Opslaan en teruglezen van persoonlijke instellingen.

De accounttests vormen één geïsoleerde lifecycle; iedere run begint met een nieuwe
database en sessies. Eén synthetische opdracht voorkomt willekeurige selectie.
Syntaxchecks zijn geen uitgebreide statische analyse. De aanvullende browser-, upload-,
concurrentie- en migratiecontroles staan hieronder beschreven. De npm-audit scant dependencies, geen PHP-code.

### GitHub Actions en branchbeveiliging

`.github/workflows/ci.yml` draait bij pushes en PR's naar develop, test, release en
main, dus ook PR's vanuit feature/*, feat/* en fix/*. De workflow heeft alleen
contents: read, tijdslimieten, annulering van verouderde runs en een tijdelijke
MariaDB-service. Tests, buildfouten en high/critical-auditbevindingen blokkeren CI.

Stel na de eerste echte Actions-run verplichte PR-review en deze statuschecks in:

- `PHP syntax, CSS build and login smoke` (omvat ook de database-integratietests)
- `npm security audit`

Bescherm develop, test, release en main. Promoveer via develop -> test -> release ->
main en breng fixes terug naar develop. Maak de PR actueel met de doelbranch.
De workflow wijzigt zelf geen repositoryregels en dwingt de bronbranch niet af.
Actions en de gebruikte Actions moeten zijn toegestaan binnen de organisatie.

Action-configuratie is gecontroleerd bij de officiële repositories:
[checkout](https://github.com/actions/checkout),
[setup-node](https://github.com/actions/setup-node) en
[setup-php](https://github.com/shivammathur/setup-php).
De workflow is lokaal te valideren met [actionlint](https://github.com/rhysd/actionlint):
`actionlint .github/workflows/ci.yml`.

### Deployment

Er is geen deploymentworkflow. Daarvoor ontbreken hostingdoel, staging/production,
authenticatie, secretbeheer, migratie- en rollbackafspraken. Deploy pas na succesvolle
CI. Behoud uploads en lever de buildassets mee. Tests, scripts en databaseschema's
horen niet in een publiek toegankelijke productie-documentroot.

## Uitgebreide CI-controles

### Eenmalige database-update voor bestaande installaties

Importeer in de bestaande appdatabase de migraties die nog ontbreken; importeer niet
opnieuw schema.sql:

1. `database/migrations/001_login_rate_limits.sql` voegt de tabel `login_rate_limits`
   toe voor het begrenzen van inlogpogingen.
2. `database/migrations/002_activity_interactions.sql` voegt likes en reacties op
   activiteiten toe.

Beide migraties kunnen veilig opnieuw worden uitgevoerd en wijzigen geen bestaande
accounts of activiteiten. De tabellen voor activiteitlikes en reacties worden ook
automatisch aangemaakt wanneer ze nog ontbreken; de databasegebruiker heeft daarvoor
CREATE TABLE-rechten nodig.

Inloggen is nu begrensd op vijf pogingen per genormaliseerde loginnaam en dertig
pogingen per IP in een vast venster van vijftien minuten. Een geldige login reset
alleen de limiet voor de loginnaam; de IP-limiet blijft gelden. Overschrijding geeft
HTTP 429 en Retry-After. De registratie-route heeft nog geen eigen rate limiter.
De code vertrouwt REMOTE_ADDR; achter een proxy vraagt dit om hostingconfiguratie.

### Wat de aanvullende tests controleren

| Gebied | Automatische controle |
| --- | --- |
| Browser en responsive | Chromium op 375, 768 en 1440 px: login/registratie wisselen, toetsenbordfocus, echte login, home, navigatie en zoekfilter; geen JavaScript-exceptions of kapotte lokale assets. |
| Toegankelijkheid | Axe WCAG 2 A/AA en 2.1 AA op login, registratie en home. Gevonden contrastproblemen worden niet onderdrukt. |
| Uiterlijk | Horizontale overflow wordt gecontroleerd; screenshots van login, registratie en home worden opgeslagen voor beoordeling. Geen pixelvergelijking met een goedgekeurde ontwerpbaseline. |
| Uploads | Geldige PNG, leeg bestand, PHP-inhoud vermomd als PNG en bestand boven 5 MB. Bestanden gaan naar een eigen tijdelijke uploadmap; afgewezen uploads mogen niets opslaan. |
| Account | Profiel aanpassen, XSS-escaping, verkeerd en juist huidig wachtwoord, oude versus nieuwe login en expliciete bevestiging bij verwijderen. |
| Isolatie | Twee accounts mogen elkaars instellingen niet lezen of wijzigen door een andere gebruikers-ID mee te sturen. |
| Opdrachten | Twee directe wissels toegestaan, derde geweigerd. Twee onafhankelijke sessies op twee PHP-processen voltooien tegelijk dezelfde opdracht: precies eenmaal XP. |
| Ranglijst | 1.000 synthetische accounts, sortering op XP en vaste volgorde bij gelijke XP; controle via de echte profielpagina. |
| Beveiliging | SQL-injectiepoging bij login, opgeslagen XSS in profiel/activiteit, uploadvalidatie, CSRF, sessietoegang en databasegestuurde inloglimiet over meerdere sessies. Dit is geen volledige pentest. |
| Prestaties | Dertig loginpagina-aanvragen met concurrency vijf: p95 onder 2 seconden. Tien ranglijstpagina-aanvragen met 1.000 accounts: elk onder 2 seconden. Dit zijn CI-regressiegrenzen, geen productiecapaciteitsmeting. |
| Migraties | Upgrade van gevulde baseline en herhaalde uitvoering van de nieuwe migratie zonder verlies van account- of instellingenvelden. Alleen de aanwezige migratie wordt getest. |

Alle controles draaien binnen de bestaande verplichte CI-job, zodat de ingestelde
branchbeveiliging blijft werken. De job installeert Chromium automatisch. Lokaal:

```sh
npm ci
npx playwright install chromium
npm run build
npm run test:local
```

Bij een andere browsercache kan `PLAYWRIGHT_BROWSERS_PATH` op een vaste map worden
gezet; gebruik dezelfde waarde bij installeren en testen. Tests werken met eigen
sessies, uploads en database. Screenshots en prestatiemetingen blijven bewust staan
in `work/reports/` en zijn op GitHub te downloaden als artifact
`ci-browser-and-performance` (zeven dagen bewaard). Ze bevatten uitsluitend testdata.

De gegenereerde screenshots ondersteunen visuele review; geautomatiseerde checks
bewijzen niet dat iedere pagina er goed uitziet of dat schermlezers overal prettig
werken. Handmatige toegankelijkheidstests en extra browsers blijven nuttig.

### Hostingcontrole voor later

Er is nog geen hosting. `.github/workflows/hosting.yml` draait daarom uitsluitend
handmatig via workflow_dispatch en is geen verplichte CI-check. Zodra er een
geautoriseerde stagingomgeving is: stel repositoryvariable STAGING_URL in op de
HTTPS-basis-URL van de app, inclusief afsluitende slash en eventuele submap. Start
vervolgens **Staging hosting check** via Actions (de workflow moet daarvoor op de
default branch staan). Lokaal: `npm run check:hosting` met dezelfde omgevingsvariabele.

De controle faalt zonder URL en controleert HTTPS met normale certificaatvalidatie,
HTTP 200, HSTS, Secure/HttpOnly/SameSite op de sessiecookie, de publieke loginpagina,
gemengde HTTP-assets in HTML en bereikbare CSS. Hij deployt niets, maakt geen accounts
aan en verandert geen serverinstellingen. Deze externe controle is nog niet tegen
een echte host uitgevoerd. TLS-configuratie buiten deze checks, proxies, backups,
serverrechten en deployment/rollback vragen later aanvullende configuratie.

### Geen inline JavaScript of CSS in PHP-templates

`npm run lint:templates` blokkeert `<script>` zonder externe `src`, scriptinhoud
naast een `src`, `<style>`, `style=`, inline eventhandlers zoals onclick/onchange,
JavaScript-URL's en script-data-URL's. De melding noemt het bestand en regelnummer.
Externe script- en stylesheetverwijzingen, Tailwind-klassen en data-attributen blijven
toegestaan. Gebruik addEventListener in een .js-bestand in plaats van eventattributen.

De controle draait verplicht in de bestaande CI-job; de ingestelde statuscheck hoeft
niet gewijzigd te worden. `npm run test:templates` test de controle zelf, inclusief
hoofdletters, meerdere regels, ongequote attributen en gecodeerde JavaScript-URL's.
De PHP-tokenizer scheidt PHP-expressies van letterlijk HTML in templates. HTML-comments
worden genegeerd. Dynamisch met PHP opgebouwde HTML is geen letterlijke template en
vraagt aanvullend code-review; deze controle is geen algemene PHP-code-analyse.
