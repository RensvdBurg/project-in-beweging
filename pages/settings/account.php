<?php

// Start de sessie met dezelfde cookiebeveiliging als de rest van de app.
$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Alleen ingelogde gebruikers mogen accountgegevens bekijken of wijzigen.
if (empty($_SESSION['user']['id'])) {
    header('Location: /project-in-beweging/index.php', true, 303);
    exit;
}

// Het CSRF-token voorkomt dat een externe pagina namens de gebruiker formulieren verstuurt.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Houd formulierstatus en accountgegevens op één plek bij voor de paginaweergave.
$accountUserId = (int) $_SESSION['user']['id'];
$csrfToken = $_SESSION['csrf_token'];
$statusMessage = '';
$statusType = 'success';
$accountUser = null;
$aboutMeInput = '';
$aboutMeWasSubmitted = false;
$passwordFormOpen = false;
$db = null;

// Lees alleen tekstwaarden uit POST; andere invoertypen worden als leeg behandeld.
$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
};

require_once __DIR__ . '/../../database/database.php';

try {
        $db = getDatabaseConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verwerk alleen formulieracties met het juiste sessietoken.
            $submittedToken = $postString('csrf_token');
            if (!hash_equals($csrfToken, $submittedToken)) {
                http_response_code(400);
                $statusMessage = 'Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.';
                $statusType = 'error';
            } else {
                $action = $postString('action');

                if ($action === 'profile') {
                    // Controleer naam en e-mailadres voordat ze in de users-tabel worden opgeslagen.
                    $name = trim($postString('name'));
                    $email = trim($postString('email'));

                    if ($name === '' || !preg_match('/\A.{1,150}\z/us', $name)) {
                        $statusMessage = 'Vul een naam van maximaal 150 tekens in.';
                        $statusType = 'error';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                        $statusMessage = 'Vul een geldig e-mailadres in.';
                        $statusType = 'error';
                    } else {
                        // E-mailadressen moeten uniek blijven voor alle accounts.
                        $statement = $db->prepare(
                            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
                        );
                        $statement->execute([
                            'email' => $email,
                            'id' => $accountUserId,
                        ]);

                        if ($statement->fetchColumn() !== false) {
                            $statusMessage = 'Dit e-mailadres is al in gebruik.';
                            $statusType = 'error';
                        } else {
                            $statement = $db->prepare(
                                'UPDATE users SET full_name = :full_name, email = :email WHERE id = :id'
                            );
                            $statement->execute([
                                'full_name' => $name,
                                'email' => $email,
                                'id' => $accountUserId,
                            ]);
                            // Werk ook de sessienaam bij zodat de app direct de nieuwe naam toont.
                            $_SESSION['user']['full_name'] = $name;
                            $statusMessage = 'Je profiel is bijgewerkt.';
                        }
                    }
                } elseif ($action === 'about_me') {
                    $aboutMeWasSubmitted = true;
                    $aboutMeInput = trim($postString('about_me'));

                    if (!preg_match('/\A.{0,500}\z/us', $aboutMeInput)) {
                        $statusMessage = 'Je stukje over jezelf mag maximaal 500 tekens bevatten.';
                        $statusType = 'error';
                    } else {
                        $statement = $db->prepare(
                            'UPDATE users SET about_me = :about_me WHERE id = :id'
                        );
                        $statement->execute([
                            'about_me' => $aboutMeInput,
                            'id' => $accountUserId,
                        ]);
                        $statusMessage = 'Je stukje over jezelf is opgeslagen.';
                    }
                } elseif ($action === 'password') {
                    $passwordFormOpen = true;
                    $currentPassword = $postString('current_password');
                    $newPassword = $postString('new_password');
                    $confirmPassword = $postString('confirm_password');

                    // Vergelijk het huidige wachtwoord met de opgeslagen hash; toon de hash nooit.
                    $statement = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
                    $statement->execute(['id' => $accountUserId]);
                    $passwordHash = $statement->fetchColumn();

                    if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
                        $statusMessage = 'Je huidige wachtwoord is onjuist.';
                        $statusType = 'error';
                    } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
                        $statusMessage = 'Je nieuwe wachtwoord moet minimaal 8 en maximaal 72 bytes bevatten.';
                        $statusType = 'error';
                    } elseif ($newPassword !== $confirmPassword) {
                        $statusMessage = 'De nieuwe wachtwoorden komen niet overeen.';
                        $statusType = 'error';
                    } else {
                        $statement = $db->prepare(
                            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
                        );
                        $statement->execute([
                            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                            'id' => $accountUserId,
                        ]);
                        $statusMessage = 'Je wachtwoord is gewijzigd.';
                    }
                } elseif ($action === 'logout') {
                    // Verwijder de login uit de sessie en vernieuw de sessie-ID.
                    unset($_SESSION['user']);
                    session_regenerate_id(true);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: /project-in-beweging/index.php', true, 303);
                    exit;
                } elseif ($action === 'delete') {
                    // Verwijderen vereist naast CSRF ook de expliciete checkboxbevestiging.
                    if ($postString('confirm_delete') !== '1') {
                        http_response_code(400);
                        $statusMessage = 'Bevestig dat je je account permanent wilt verwijderen.';
                        $statusType = 'error';
                    } else {
                        $statement = $db->prepare('DELETE FROM users WHERE id = :id');
                        $statement->execute(['id' => $accountUserId]);

                        if ($statement->rowCount() !== 1) {
                            throw new RuntimeException('Account deletion did not remove exactly one user.');
                        }

                        unset($_SESSION['user']);
                        session_regenerate_id(true);
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: /project-in-beweging/index.php', true, 303);
                        exit;
                    }
                } else {
                    http_response_code(400);
                    $statusMessage = 'Ongeldige aanvraag.';
                    $statusType = 'error';
                }
            }
        }

        // Haal na opslaan opnieuw de profielgegevens uit de database op.
        $statement = $db->prepare('SELECT email, username, full_name, about_me FROM users WHERE id = :id');
        $statement->execute(['id' => $accountUserId]);
        $accountUser = $statement->fetch();
        if (!$aboutMeWasSubmitted || $statusType !== 'error') {
            $aboutMeInput = is_array($accountUser) ? $accountUser['about_me'] : $aboutMeInput;
        }

        if (!$accountUser) {
            unset($_SESSION['user']);
            session_regenerate_id(true);
            header('Location: /project-in-beweging/index.php', true, 303);
            exit;
        }
    } catch (PDOException $exception) {
        // Geef een bruikbare melding zonder databasefouten aan de gebruiker te tonen.
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            $statusMessage = 'Dit e-mailadres is al in gebruik.';
        } else {
            error_log('Accountinstellingen/databasefout: ' . $exception->getMessage());
            $statusMessage = 'Je accountinstellingen konden niet worden opgeslagen. Probeer het later opnieuw.';
        }
        $statusType = 'error';
    } catch (RuntimeException $exception) {
        error_log('Accountverwijdering mislukt: ' . $exception->getMessage());
        $statusMessage = 'Je account kon niet worden verwijderd. Probeer het later opnieuw.';
        $statusType = 'error';
    }

    if (!is_array($accountUser) && $db instanceof PDO) {
        // Bij een fout tijdens opslaan proberen we de huidige gegevens alsnog te tonen.
        try {
            $statement = $db->prepare('SELECT email, username, full_name, about_me FROM users WHERE id = :id');
            $statement->execute(['id' => $accountUserId]);
            $accountUser = $statement->fetch() ?: null;
            if (!$aboutMeWasSubmitted || $statusType !== 'error') {
                $aboutMeInput = is_array($accountUser) ? $accountUser['about_me'] : $aboutMeInput;
            }
        } catch (PDOException $exception) {
            error_log('Accountgegevens ophalen mislukt: ' . $exception->getMessage());
        }
    }
// Escape waarden uit database of sessie voordat ze in HTML terechtkomen.
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// De bestaande database bevat geen profielfoto; gebruik initialen als avatar.
$initials = '';
if (is_array($accountUser)) {
    foreach (preg_split('/\s+/', trim($accountUser['full_name'])) as $part) {
        if ($part !== '') {
            $characters = preg_split('//u', $part, -1, PREG_SPLIT_NO_EMPTY);
            $initials .= $characters[0] ?? '?';
            if (preg_match('/\A.{2}/us', $initials)) {
                break;
            }
        }
    }
}
$initials = $initials !== '' ? strtoupper($initials) : '?';

$enableSettingsPageTransitions = true;
?>
<!DOCTYPE html>
<html lang="nl" class="settings-page-transition">
<head>
    <!-- Deel de globale font-, CSS- en dark-mode-instellingen met de rest van de app. -->
    <?php $pageTitle = 'Account | UpMove'; ?>
    <?php require __DIR__ . '/../../components/header.php'; ?>
</head>
<body class="min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased">
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-account flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <!-- Navigatie en profielkaart vormen de kop van de accountinstellingen. -->
            <header class="relative mb-6 flex min-h-11 items-center justify-center">
                <a
                    href="/project-in-beweging/pages/settings/settings.php?transition=back"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar instellingen">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h1 class="text-[17px] font-extrabold tracking-tight">Account</h1>
            </header>

            <?php if ($statusMessage !== ''): ?>
                <p
                    class="mb-4 rounded-xl px-4 py-3 text-[12px] font-medium <?= $statusType === 'error' ? 'bg-red-50 text-red-600' : 'bg-teal-soft text-[#18796f]' ?>"
                    role="<?= $statusType === 'error' ? 'alert' : 'status' ?>"
                    aria-live="polite"><?= $escape($statusMessage) ?></p>
            <?php endif; ?>

            <?php if (is_array($accountUser)): ?>
                <!-- Profielnaam en e-mail blijven zichtbaar boven de bewerkvelden. -->
                <section class="account-surface mb-6 flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)]" aria-label="Huidig profiel">
                    <span class="account-avatar grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange-soft text-[17px] font-extrabold text-orange" aria-hidden="true"><?= $escape($initials) ?></span>
                    <span class="min-w-0">
                        <span class="block truncate text-[15px] font-extrabold"><?= $escape($accountUser['full_name']) ?></span>
                        <span class="settings-description mt-0.5 block truncate text-[12px] text-[#9299a3]"><?= $escape($accountUser['email']) ?></span>
                    </span>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">Over mij</h2>
                    <form method="post" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <fieldset class="contents">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="about_me">
                            <label class="block p-4">
                                <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">Vertel iets over jezelf</span>
                                <textarea
                                    name="about_me"
                                    rows="5"
                                    maxlength="500"
                                    aria-describedby="about-me-visibility"
                                    class="account-input w-full resize-y rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30"><?= $escape($aboutMeInput) ?></textarea>
                                <span id="about-me-visibility" class="settings-description mt-2 block text-[11px] text-[#9299a3]">Dit stukje is zichtbaar voor andere ingelogde gebruikers die je profiel via de ranglijst bekijken. Maximaal 500 tekens.</span>
                            </label>
                            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                            <div class="flex justify-end p-4">
                                <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Over mij opslaan</button>
                            </div>
                        </fieldset>
                    </form>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">Profiel</h2>
                    <!-- Profielgegevens worden bijgewerkt in de bestaande users-tabel. -->
                    <form method="post" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <fieldset class="contents">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="action" value="profile">
                        <label class="block p-4">
                            <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">Naam</span>
                            <input
                                type="text"
                                name="name"
                                value="<?= $escape($accountUser['full_name']) ?>"
                                maxlength="150"
                                autocomplete="name"
                                required
                                class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                        </label>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <label class="block p-4">
                            <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">E-mailadres</span>
                            <input
                                type="email"
                                name="email"
                                value="<?= $escape($accountUser['email']) ?>"
                                maxlength="254"
                                autocomplete="email"
                                required
                                class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                        </label>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <div class="flex justify-end p-4">
                            <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Profiel opslaan</button>
                        </div>
                        </fieldset>
                    </form>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">Beveiliging</h2>
                    <!-- Inklapbaar wachtwoordformulier houdt de mobiele pagina compact. -->
                    <details class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]" <?= $passwordFormOpen ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 transition hover:bg-[#fafbfc] focus-visible:outline-2 focus-visible:outline-orange">
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-bold">Wachtwoord wijzigen</span>
                                <span class="settings-description mt-0.5 block text-[11px] text-[#9299a3]">Werk je wachtwoord bij</span>
                            </span>
                            <svg class="account-chevron shrink-0 text-[#9299a3]" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </summary>
                        <form method="post" class="border-t border-[#eceef2] p-4">
                            <fieldset class="contents">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="password">
                            <div class="space-y-3">
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">Huidig wachtwoord</span>
                                    <input type="password" name="current_password" autocomplete="current-password" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">Nieuw wachtwoord</span>
                                    <input type="password" name="new_password" autocomplete="new-password" minlength="8" maxlength="72" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">Nieuw wachtwoord bevestigen</span>
                                    <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" maxlength="72" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Wachtwoord opslaan</button>
                            </div>
                            </fieldset>
                        </form>
                    </details>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">Account</h2>
                    <!-- Uitloggen en verwijderen zijn gescheiden, expliciete accountacties. -->
                    <div class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="flex w-full items-center gap-3 p-4 text-left transition hover:bg-[#fafbfc] active:bg-[#f4f6f8] focus-visible:outline-2 focus-visible:outline-orange">
                                <span class="min-w-0 flex-1 text-[13px] font-bold">Uitloggen</span>
                                <svg class="shrink-0 text-[#9299a3]" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <button
                            type="button"
                            id="account-delete-open"
                            class="flex w-full items-center gap-3 p-4 text-left text-red-600 transition hover:bg-red-50 active:bg-red-100 focus-visible:outline-2 focus-visible:outline-red-600 disabled:cursor-not-allowed disabled:opacity-50">
                            <span class="min-w-0 flex-1 text-[13px] font-bold">Account verwijderen</span>
                            <svg class="shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </section>

                <!-- De browserdialoog vraagt om bevestiging voordat het account wordt verwijderd. -->
                <dialog id="account-delete-dialog" class="account-dialog w-[calc(100%-2rem)] max-w-sm rounded-[20px] bg-white p-0 text-[#20242a] shadow-2xl backdrop:bg-black/45">
                    <form method="post" id="account-delete-form" class="p-5">
                        <fieldset class="contents">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete">
                        <h2 class="text-[16px] font-extrabold">Account verwijderen?</h2>
                        <p class="settings-description mt-2 text-[12px] leading-relaxed text-[#777f8a]">Dit verwijdert je account en alle bijbehorende gegevens permanent.</p>
                        <label class="mt-4 flex cursor-pointer items-start gap-2 text-[12px] leading-relaxed">
                            <input type="checkbox" name="confirm_delete" value="1" required class="mt-0.5 accent-red-600">
                            <span>Ik begrijp dat mijn account permanent wordt verwijderd.</span>
                        </label>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" id="account-delete-cancel" class="account-dialog-cancel rounded-xl px-3 py-2 text-[12px] font-semibold text-[#66707d] transition hover:bg-[#f4f6f8] focus-visible:outline-2 focus-visible:outline-orange">Annuleren</button>
                            <button type="submit" class="rounded-xl bg-red-600 px-3 py-2 text-[12px] font-bold text-white transition hover:bg-red-700 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Definitief verwijderen</button>
                        </div>
                        </fieldset>
                    </form>
                </dialog>
            <?php endif; ?>
        </main>

        <?php require __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- Koppelt de verwijderdialoog en client-side wachtwoordcontrole. -->
    <script src="/project-in-beweging/assets/js/account-page.js"></script>
</body>
</html>
