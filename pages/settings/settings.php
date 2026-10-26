<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Alleen ingelogde gebruikers mogen hun persoonlijke instellingen openen.
if (empty($_SESSION['user'])) {
    header('Location: /project-in-beweging/index.php', true, 303);
    exit;
}

$userId = $_SESSION['user']['id']
    ?? $_SESSION['user']['email']
    ?? $_SESSION['user']['full_name']
    ?? 'user';

?>
<!DOCTYPE html>
<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta name="theme-color" content="#f7f8fa" id="theme-color">

    <title>Instellingen | UpMove</title>


    <!-- Tailwind CSS -->
    <link
        rel="stylesheet"
        href="/project-in-beweging/css/output.css?v=<?= filemtime(__DIR__ . '/../../css/output.css') ?>">

    <script src="/project-in-beweging/assets/js/app-settings.js"></script>

</head>


<body class="min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased">


    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">


        <!-- ========================================= -->
        <!-- SETTINGS -->
        <!-- ========================================= -->

        <main id="screen-settings"
            class="upmove-settings flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
            data-user-id="<?= htmlspecialchars(
                (string) $userId,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">


            <!-- ===================================== -->
            <!-- HEADER -->
            <!-- ===================================== -->

            <header class="relative mb-6 flex min-h-11 items-center justify-center">

                <h1 class="text-[17px] font-extrabold tracking-tight">
                    Instellingen
                </h1>

            </header>


                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">
                        Account
                    </h2>

                    <a
                        href="/project-in-beweging/pages/settings/account.php"
                        class="settings-card group flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-orange" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7" />
                                <path d="M5 20c.5-3.1 3.2-5 7-5s6.5 1.9 7 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-[13px] font-bold">Accountinstellingen</span>
                            <span class="settings-description mt-0.5 block text-[11px] text-[#9299a3]">Beheer je profiel en account</span>
                        </span>

                        <svg class="shrink-0 text-[#9299a3] transition group-hover:translate-x-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </section>


                <!-- ===================================== -->
                <!-- BEWEGINGSDOELEN -->
            <!-- ===================================== -->

            <section class="mb-6">


                <h2
                    class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">
                    Mijn bewegingsdoelen
                </h2>


                <div
                    class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- HERINNERINGSFREQUENTIE -->

                    <div class="p-4">


                        <div class="mb-3 flex items-center justify-between gap-3">

                            <div>

                                <label for="setting-frequency" class="text-[13px] font-bold">
                                    Herinneringsfrequentie
                                </label>

                                <p class="settings-description mt-0.5 text-[11px] text-[#9299a3]">
                                    Hoe vaak wil je een beweegpauze?
                                </p>

                            </div>


                            <span id="settings-frequency-label" class="shrink-0 text-[12px] font-bold text-orange">
                                Elke 45 min
                            </span>

                        </div>


                        <input id="setting-frequency" type="range" min="15" max="120" step="15" value="60"
                            class="upmove-range block" aria-label="Herinneringsfrequentie">

                    </div>
                </div>

            </section>



            <!-- ===================================== -->
            <!-- WERK / SCHOOL -->
            <!-- ===================================== -->

            <section class="mb-6">


                <h2
                    class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">
                    Werk / school
                </h2>


                <div
                    class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- ALLEEN TIJDENS WERK/SCHOOL -->

                    <label class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p class="text-[13px] font-bold">
                                Alleen tijdens werk/school
                            </p>

                            <p class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Herinneringen en meldingen bij nieuwe opdrachten alleen binnen jouw ingestelde tijden.
                                UpMove moet open zijn.
                            </p>

                        </div>


                        <!-- SWITCH -->

                        <div class="relative shrink-0">

                            <input id="setting-work-only" type="checkbox" class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]">
                            </div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>

                        </div>

                    </label>



                    <!-- WERKTIJD OPTIES -->

                    <div id="work-hours-options"
                        class="settings-work-options border-t border-[#eceef2] bg-[#fafbfc] p-4 transition">


                        <!-- TIJDEN -->

                        <div class="grid grid-cols-2 gap-3">


                            <!-- START -->

                            <div>

                                <label for="setting-work-start"
                                    class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">
                                    Starttijd
                                </label>

                                <input id="setting-work-start" type="time" value="08:30"
                                    class="settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange">

                            </div>



                            <!-- EIND -->

                            <div>

                                <label for="setting-work-end"
                                    class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">
                                    Eindtijd
                                </label>

                                <input id="setting-work-end" type="time" value="17:00"
                                    class="settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange">

                            </div>

                        </div>



                        <!-- DAGEN -->

                        <div class="mt-4">


                            <p class="settings-label mb-2 text-[11px] font-semibold text-[#858c97]">
                                Werk- / schooldagen
                            </p>


                            <div class="grid grid-cols-7 gap-1.5">


                                <!-- MA -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="1" checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Ma
                                    </span>

                                </label>


                                <!-- DI -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="2" checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Di
                                    </span>

                                </label>


                                <!-- WO -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="3" checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Wo
                                    </span>

                                </label>


                                <!-- DO -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="4" checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Do
                                    </span>

                                </label>


                                <!-- VR -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="5" checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Vr
                                    </span>

                                </label>


                                <!-- ZA -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="6">

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Za
                                    </span>

                                </label>


                                <!-- ZO -->

                                <label class="cursor-pointer">

                                    <input type="checkbox" class="work-day peer sr-only" value="0">

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Zo
                                    </span>

                                </label>

                            </div>

                        </div>



                        <!-- STATUS -->

                        <div id="work-hours-summary"
                            class="mt-4 rounded-xl bg-[#eef8f7] px-3 py-2.5 text-[11px] font-semibold leading-relaxed text-[#268f85]">
                            Ma t/m Vr · 08:30 - 17:00
                        </div>

                        <p id="work-notification-status"
                            class="mt-2 text-[11px] leading-relaxed text-[#858c97]"
                            role="status"
                            aria-live="polite"></p>


                    </div>

                </div>

            </section>



            <!-- ===================================== -->
            <!-- VOORKEUREN -->
            <!-- ===================================== -->

            <section class="mb-5">


                <h2
                    class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#858c97]">
                    Voorkeuren
                </h2>


                <div
                    class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- DARK MODE -->

                    <label class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p class="text-[13px] font-bold">
                                Donkere modus
                            </p>

                            <p class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Gebruik het donkere thema
                                in heel UpMove
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input id="setting-dark" type="checkbox" class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]">
                            </div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>

                        </div>

                    </label>


                </div>

            </section>
            <!-- ===================================== -->
            <!-- LOCALSTORAGE FOUT -->
            <!-- ===================================== -->

            <p id="settings-storage-error"
                class="mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-[12px] font-medium text-red-600"></p>


        </main>



        <!-- ========================================= -->
        <!-- FOOTER -->
        <!-- ========================================= -->

        <?php

        require_once __DIR__ . '/../../components/footer.php';

        ?>


    </div>



    <script
        src="/project-in-beweging/assets/js/settings-page.js?v=<?= filemtime(__DIR__ . '/../../assets/js/settings-page.js') ?>"></script>


</body>

</html>