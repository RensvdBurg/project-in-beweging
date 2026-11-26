# 07 · Admin-dashboard

Beheeromgeving op `/admin` in dezelfde stijl als de app. Alle admin-code staat in eigen mappen:

```
app/Http/Controllers/Admin/   DashboardController, UserController, ChallengeController,
                              ActivityController, LogController
app/Http/Requests/Admin/      UserRequest, ChallengeRequest (validatie)
app/Http/Middleware/          EnsureUserIsAdmin (alias "admin")
app/Models/AuditLog.php       logregels + AuditLog::record()
app/Console/Commands/         MakeAdminCommand (upmove:admin)
routes/admin.php              alle admin-routes (geladen in bootstrap/app.php)
resources/views/admin/        layout, dashboard, users/, challenges/, activities/, logs/, partials/
resources/js/admin/admin.js   bevestigdialoog bij verwijderen + afbeeldingsvoorbeeld
tests/Feature/Admin/          tests voor toegang, gebruikers, challenges, activiteiten en logs
```

## Toegang

- Alleen gebruikers met `users.is_admin = true`; gasten gaan naar `/login`, anderen krijgen 403.
- `is_admin` is niet mass-assignable: registreren of het profiel aanpassen kan het niet aanzetten.
- Iemand beheerder maken (of met `--remove` weer weghalen):

  ```bash
  php artisan upmove:admin sanne            # gebruikersnaam of e-mailadres
  php artisan upmove:admin sanne --remove
  ```

- Daarna kan een beheerder in het dashboard andere gebruikers beheerder maken.
- Beheerders zien in **Instellingen** een kaart "Admin dashboard".
- Je kunt je eigen beheerdersrechten niet weghalen en je eigen account hier niet verwijderen
  (dat laatste kan via Instellingen › Account).

## Routes (prefix `/admin`, namen `admin.*`, middleware `web` + `auth` + `admin`)

| Methode | URL | Naam | Actie |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | totalen, nieuwste gebruikers, laatste logs |
| GET | `/admin/gebruikers` | `admin.users.index` | lijst + zoeken (`?q=`), 15 per pagina |
| GET/POST | `/admin/gebruikers/create`, `/admin/gebruikers` | `admin.users.create`/`store` | gebruiker aanmaken |
| GET/PUT | `/admin/gebruikers/{user}/edit`, `/admin/gebruikers/{user}` | `admin.users.edit`/`update` | bewerken (wachtwoord leeg = ongewijzigd) |
| DELETE | `/admin/gebruikers/{user}` | `admin.users.destroy` | `User::deleteAccount()` (ook afbeeldingen) |
| GET | `/admin/challenges` | `admin.challenges.index` | lijst + zoeken |
| GET/POST | `/admin/challenges/create`, `/admin/challenges` | `admin.challenges.create`/`store` | toevoegen |
| GET/PUT | `/admin/challenges/{challenge}/edit`, `/admin/challenges/{challenge}` | `admin.challenges.edit`/`update` | bewerken |
| DELETE | `/admin/challenges/{challenge}` | `admin.challenges.destroy` | `ChallengeService::delete()` |
| GET | `/admin/activiteiten` | `admin.activities.index` | activiteiten van iedereen + zoeken |
| DELETE | `/admin/activiteiten/{activity}` | `admin.activities.destroy` | verwijderen (met afbeelding, likes, reacties) |
| GET | `/admin/logs` | `admin.logs.index` | logboek, filter op soort (`?action=`) en zoeken, 30 per pagina |

## Challenges

- Moeilijkheid: kies Beginner, Gevorderd of Expert (`Challenge::DIFFICULTIES`); andere waarden worden geweigerd.
- Afbeelding: een bestand in `public/` (bijv. `images/challenges/foto.jpg`) of een https-URL.
  Uploaden kan (nog) niet: de NativePHP-app serveert alleen bestanden uit `public/`.
- Verwijderen: voltooiingen verdwijnen mee, verdiende XP blijft staan; wie de challenge open had,
  krijgt bij het volgende bezoek een nieuwe.

## Logs (`audit_logs`)

Wordt bijgehouden bij: inloggen, mislukte login, uitloggen, registreren, eigen account verwijderen,
challenge voltooien, activiteit plaatsen en elke wijziging in het dashboard. Logregels blijven bestaan
als het account wordt verwijderd (`user_id` wordt `null`). Er worden geen wachtwoorden gelogd.

## Let op: één database per toestel

In de NativePHP-app heeft elk toestel een eigen SQLite-database. Het dashboard toont dus alleen de
gegevens van de database waarop het draait (bijv. de browserversie via `php artisan serve`, of één
telefoon). Een centraal overzicht van álle gebruikers vraagt om een gedeelde server/API.
