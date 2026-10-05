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

<main class="relative flex min-h-dvh flex-col items-center overflow-hidden bg-background px-4 pb-12 pt-[71px] sm:justify-center sm:py-12">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <svg class="absolute left-0 top-0 h-[260px] w-full fill-primary drop-shadow-[0_5px_8px_oklch(0.25_0_0/0.18)] min-[700px]:h-[34%]" viewBox="0 0 402 260" preserveAspectRatio="none">
            <path d="M0 0H402C376 0 343 7 309 72C287 111 275 134 252 177C223 219 199 232 146 233C77 232 0 175 0 175Z" />
        </svg>
        <svg class="absolute bottom-0 left-0 h-[min(42.2%,370px)] w-full fill-primary drop-shadow-[0_-4px_8px_oklch(0.25_0_0/0.13)] min-[700px]:h-[47%]" viewBox="0 0 402 370" preserveAspectRatio="none">
            <path d="M0 222C10 232 64 279 134 279C220 279 236 243 258 212C278 175 314 107 314 107L346 56C370 25 382 15 402 0V370H0Z" />
        </svg>
    </div>

    <div class="relative z-10 w-full max-w-[402px] sm:max-w-[440px]">
        <div id="logo" class="mb-[133px] ml-[calc(50%-147px)] flex items-center gap-[6px] sm:ml-[22px]">
            <span class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-[10px] bg-card shadow-[0_5px_19px_oklch(0.25_0_0/0.18)]" aria-hidden="true">
                <svg class="h-5 w-5 text-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
            </span>
            <span class="text-[20px] font-bold text-foreground">UpMove</span>
        </div>

        <section id="login" class="mx-auto w-full max-w-[322px] rounded-[24px] bg-card px-[32px] pb-[49px] pt-[42px] shadow-[0_5px_19px_oklch(0.25_0_0/0.18)] sm:max-w-[360px]" aria-labelledby="loginTitle">
            <h1 id="loginTitle" class="mb-[16px] text-[20px] font-semibold text-card-foreground">Aanmelden</h1>
            <form id="loginForm" method="post" class="flex flex-col gap-[21px]">
                <div class="flex flex-col gap-[22px]">
                    <label class="sr-only" for="loginEmail">Gebruikersnaam of e-mailadres</label>
                    <input id="loginEmail" required name="email" autocomplete="username" placeholder="Gebruikersnaam/email" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <label class="sr-only" for="loginPassword">Wachtwoord</label>
                    <input id="loginPassword" required type="password" name="password" autocomplete="current-password" placeholder="Wachtwoord" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                </div>
                <p id="loginStatus" role="status" aria-live="polite" class="hidden text-center text-xs text-card-foreground">De inlogfunctie is nog niet gekoppeld aan accounts.</p>
                <button type="submit" class="h-[39px] w-full cursor-pointer rounded-[18px] bg-primary text-[13px] font-semibold text-primary-foreground shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] hover:bg-primary/90">Aanmelden</button>
            </form>
            <p class="mt-[14px] text-center text-[10px] font-medium text-placeholder">Heb je nog geen account? <button type="button" data-mode="register" class="cursor-pointer font-semibold text-teal hover:underline">Registreren</button></p>
        </section>

        <section id="register" class="relative left-[3px] mx-auto hidden w-full max-w-[322px] rounded-[24px] bg-card pb-[23px] pl-[26px] pr-[32px] pt-[20px] shadow-[0_5px_19px_oklch(0.25_0_0/0.18)] sm:max-w-[360px]" aria-labelledby="registerTitle">
            <h1 id="registerTitle" class="mb-[11px] text-[20px] font-semibold text-card-foreground">Registreren</h1>
            <form id="registerForm" method="post" class="flex flex-col gap-[21px]">
                <div class="flex flex-col gap-[15px]">
                    <div>
                        <label class="sr-only" for="registerEmail">E-mailadres</label>
                        <input id="registerEmail" required type="email" name="email" autocomplete="email" placeholder="E-mailadres" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                        <p class="mt-1 pl-[14px] text-[10px] font-medium text-card-foreground">Je ontvangt mogelijk meldingen van ons.</p>
                    </div>
                    <label class="sr-only" for="registerPassword">Wachtwoord</label>
                    <input id="registerPassword" required type="password" name="password" autocomplete="new-password" placeholder="Wachtwoord" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <label class="sr-only" for="confirmPassword">Wachtwoord herhalen</label>
                    <input id="confirmPassword" required type="password" name="confirmPassword" autocomplete="new-password" placeholder="Wachtwoord herhalen" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
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
                    <input id="fullName" required name="fullName" autocomplete="name" placeholder="Volledige naam" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                    <label class="sr-only" for="username">Gebruikersnaam</label>
                    <input id="username" required name="username" autocomplete="username" placeholder="Gebruikersnaam" class="h-[47px] w-full rounded-[18px] bg-card px-[19px] text-[13px] font-medium shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] outline-none placeholder:text-placeholder focus-visible:ring-2 focus-visible:ring-ring sm:h-[52px]" />
                </div>
                <p id="registerError" role="alert" class="hidden text-center text-xs text-flag-red"></p>
                <p id="registerStatus" role="status" aria-live="polite" class="hidden text-center text-xs text-card-foreground">De registratie is nog niet gekoppeld aan accounts.</p>
                <button type="submit" class="h-[39px] w-full cursor-pointer rounded-[18px] bg-primary text-[13px] font-semibold text-primary-foreground shadow-[0_7px_19px_oklch(0.25_0_0/0.16)] hover:bg-primary/90">Registreren</button>
            </form>
            <p class="mt-[14px] text-center text-[10px] font-medium text-placeholder">Heb je al een account? <button type="button" data-mode="login" class="cursor-pointer font-semibold text-teal hover:underline">Aanmelden</button></p>
        </section>
    </div>
</main>
