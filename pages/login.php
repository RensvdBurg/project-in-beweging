<?php
$months = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maart',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Augustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'December',
];
$currentYear = (int) date('Y');
?>

<main class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-[#f4f7f6] px-4 py-8 sm:py-12">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <span class="absolute -right-32 -top-36 h-[26rem] w-[26rem] rounded-full bg-[#dcefe9] opacity-70 blur-3xl"></span>
        <span class="absolute -bottom-40 -left-36 h-[25rem] w-[25rem] rounded-full bg-[#f9e9df] opacity-60 blur-3xl"></span>
    </div>

    <div class="relative z-10 w-full max-w-[402px] sm:max-w-[440px]">
        <div id="logo" class="mb-6 flex items-center justify-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#17685b] shadow-[0_8px_20px_rgba(23,104,91,0.2)]" aria-hidden="true">
                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
            </span>
            <span class="text-2xl font-extrabold tracking-tight text-foreground">UpMove</span>
        </div>

        <section id="login" class="mx-auto w-full max-w-[322px] rounded-[24px] border border-[#e6ece9] bg-white/95 px-[32px] pb-[42px] pt-[36px] shadow-[0_24px_70px_rgba(16,54,46,0.12)] sm:max-w-[360px]<?= $activeAuthMode === 'register' ? ' hidden' : '' ?>" aria-labelledby="loginTitle">
            <?php if ($currentUser !== null): ?>
                <h1 id="loginTitle" class="mb-[16px] text-[20px] font-semibold text-card-foreground">Je bent ingelogd</h1>
                <p class="mb-[20px] text-center text-sm text-card-foreground">Welkom, <?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?>).</p>
                <form method="post">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="h-[39px] w-full cursor-pointer rounded-[18px] bg-primary text-[13px] font-semibold text-ink shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] hover:bg-primary/90">Uitloggen</button>
                </form>
            <?php else: ?>
                <h1 id="loginTitle" class="mb-[16px] text-[20px] font-semibold text-card-foreground">Aanmelden</h1>
                <form id="loginForm" method="post" class="flex flex-col gap-[21px]">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="flex flex-col gap-[22px]">
                        <label class="sr-only" for="loginEmail">Gebruikersnaam of e-mailadres</label>
                        <input id="loginEmail" required name="email" autocomplete="username" placeholder="Gebruikersnaam/e-mailadres" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                        <label class="sr-only" for="loginPassword">Wachtwoord</label>
                        <input id="loginPassword" required type="password" name="password" autocomplete="current-password" placeholder="Wachtwoord" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    </div>
                    <?php if ($authMessage !== '' && $activeAuthMode === 'login'): ?>
                        <p id="loginStatus" role="<?= $authMessageType === 'success' ? 'status' : 'alert' ?>" aria-live="polite" class="text-center text-xs <?= $authMessageType === 'success' ? 'text-[#17685b]' : 'text-flag-red' ?>"><?= htmlspecialchars($authMessage, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <button type="submit" class="h-[39px] w-full cursor-pointer rounded-[18px] bg-primary text-[13px] font-semibold text-ink shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] hover:bg-primary/90">Aanmelden</button>
                </form>
                <p class="mt-[14px] text-center text-[10px] font-medium text-[#595959]">Heb je nog geen account? <button type="button" data-mode="register" class="cursor-pointer font-semibold text-[#17685b] hover:underline">Registreren</button></p>
            <?php endif; ?>
        </section>

        <section id="register" class="mx-auto w-full max-w-[322px] rounded-[24px] border border-[#e6ece9] bg-white/95 px-[28px] pb-[24px] pt-[24px] shadow-[0_24px_70px_rgba(16,54,46,0.12)] sm:max-w-[360px]<?= $activeAuthMode === 'register' && $currentUser === null ? '' : ' hidden' ?>" aria-labelledby="registerTitle">
            <h1 id="registerTitle" class="mb-[11px] text-[20px] font-semibold text-card-foreground">Registreren</h1>
            <form id="registerForm" method="post" class="flex flex-col gap-[21px]">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="flex flex-col gap-[15px]">
                    <div>
                        <label class="sr-only" for="registerEmail">E-mailadres</label>
                        <input id="registerEmail" required type="email" name="email" autocomplete="email" placeholder="E-mailadres" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                        <p class="mt-1 pl-[14px] text-[10px] font-medium text-card-foreground">Je ontvangt mogelijk meldingen van ons.</p>
                    </div>
                    <label class="sr-only" for="registerPassword">Wachtwoord</label>
                    <input id="registerPassword" required type="password" name="password" autocomplete="new-password" placeholder="Wachtwoord" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <label class="sr-only" for="confirmPassword">Wachtwoord herhalen</label>
                    <input id="confirmPassword" required type="password" name="confirmPassword" autocomplete="new-password" placeholder="Wachtwoord herhalen" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <fieldset>
                        <legend class="mb-[12px] pl-[8px] text-[16px] font-semibold text-card-foreground">Geboortedatum</legend>
                        <div class="flex gap-[12px]">
                            <label class="sr-only" for="birthDay">Dag</label>
                            <select id="birthDay" required name="day" class="h-[47px] min-w-0 flex-1 rounded-[18px] bg-card px-[10px] text-[12px] font-medium text-card-foreground shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]">
                                <option value="" disabled selected>Dag</option>
                                <?php for ($day = 1; $day <= 31; $day++): ?>
                                    <option value="<?= $day ?>"><?= $day ?></option>
                                <?php endfor; ?>
                            </select>
                            <label class="sr-only" for="birthMonth">Maand</label>
                            <select id="birthMonth" required name="month" class="h-[47px] min-w-0 flex-1 rounded-[18px] bg-card px-[10px] text-[12px] font-medium text-card-foreground shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]">
                                <option value="" disabled selected>Maand</option>
                                <?php foreach ($months as $monthNumber => $monthName): ?>
                                    <option value="<?= $monthNumber ?>"><?= htmlspecialchars($monthName, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="sr-only" for="birthYear">Jaar</label>
                            <select id="birthYear" required name="year" class="h-[47px] min-w-0 flex-1 rounded-[18px] bg-card px-[10px] text-[12px] font-medium text-card-foreground shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]">
                                <option value="" disabled selected>Jaar</option>
                                <?php for ($year = $currentYear; $year >= $currentYear - 110; $year--): ?>
                                    <option value="<?= $year ?>"><?= $year ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </fieldset>
                    <label class="sr-only" for="fullName">Volledige naam</label>
                    <input id="fullName" required name="fullName" autocomplete="name" placeholder="Volledige naam" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <label class="sr-only" for="username">Gebruikersnaam</label>
                    <input id="username" required name="username" autocomplete="username" placeholder="Gebruikersnaam" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-[#595959] focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                </div>
                <?php if ($authMessage !== '' && $activeAuthMode === 'register'): ?>
                    <p id="registerStatus" role="<?= $authMessageType === 'success' ? 'status' : 'alert' ?>" aria-live="polite" class="text-center text-xs <?= $authMessageType === 'success' ? 'text-[#17685b]' : 'text-flag-red' ?>"><?= htmlspecialchars($authMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <button type="submit" class="h-[39px] w-full cursor-pointer rounded-[18px] bg-primary text-[13px] font-semibold text-ink shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] hover:bg-primary/90">Registreren</button>
            </form>
            <p class="mt-[14px] text-center text-[10px] font-medium text-[#595959]">Heb je al een account? <button type="button" data-mode="login" class="cursor-pointer font-semibold text-[#17685b] hover:underline">Aanmelden</button></p>
        </section>
    </div>
</main>
