<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
 * Alleen toegankelijk wanneer de gebruiker is ingelogd.
 */
if (empty($_SESSION['user'])) {
    header('Location: ../../index.php', true, 303);
    exit;
}

/*
 * Unieke waarde voor localStorage.
 */
$userId =
    $_SESSION['user']['id']
    ?? $_SESSION['user']['email']
    ?? $_SESSION['user']['full_name']
    ?? 'user';

?>

<!DOCTYPE html>
<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta
        name="theme-color"
        content="#f7f8fa"
        id="theme-color">

    <title>Instellingen | UpMove</title>


    <!-- Tailwind CSS -->
    <link
        rel="stylesheet"
        href="/project-in-beweging/css/output.css">


    <!--
        Dark mode zo vroeg mogelijk toepassen.
        Hierdoor krijg je minder witte "flash"
        voordat JavaScript geladen is.
    -->
    <script>
        (() => {
            try {
                const darkMode =
                    localStorage.getItem('upmove:dark-mode') === 'true';

                document.documentElement.classList.toggle(
                    'upmove-dark',
                    darkMode
                );
            } catch {
                // localStorage niet beschikbaar
            }
        })();
    </script>


    <style>
        /*
         * ==========================================
         * RANGE SLIDER
         * ==========================================
         */

        .upmove-range {
            --range-progress: 28.5714%;

            width: 100%;
            height: 6px;

            border-radius: 999px;

            appearance: none;
            -webkit-appearance: none;

            background:
                linear-gradient(to right,
                    #ff6b4a 0 var(--range-progress),
                    #e8ebef var(--range-progress) 100%);

            cursor: pointer;
        }


        .upmove-range::-webkit-slider-thumb {
            width: 18px;
            height: 18px;

            border: 3px solid white;
            border-radius: 999px;

            appearance: none;
            -webkit-appearance: none;

            background: #ff6b4a;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.14);
        }


        .upmove-range::-moz-range-thumb {
            width: 12px;
            height: 12px;

            border: 3px solid white;
            border-radius: 999px;

            background: #ff6b4a;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.14);
        }



        /*
         * ==========================================
         * DARK MODE
         * ==========================================
         */

        html.upmove-dark {
            color-scheme: dark;

            --color-background: #151920;
            --color-app-bg: #151920;

            --color-surface: #202630;
            --color-card: #202630;

            --color-foreground: #f4f5f7;
            --color-card-foreground: #dce1e8;

            --color-muted: #a3acba;

            --color-orange-soft: #382823;
            --color-orange-streak: #382823;

            --color-teal-soft: #203a37;

            --color-alert-bg: #302b20;
            --color-alert-border: #71603b;
        }


        html.upmove-dark body {
            background: #151920;
            color: #f4f5f7;
        }


        html.upmove-dark .settings-card {
            background: #202630;
        }


        html.upmove-dark .settings-divider {
            background: #343b46;
        }


        html.upmove-dark .settings-description,
        html.upmove-dark .settings-label {
            color: #a3acba;
        }


        html.upmove-dark .settings-button {
            background: #2a313c;
            color: #f4f5f7;
        }


        html.upmove-dark .settings-input {
            background: #2a313c;
            border-color: #3c4654;
            color: #f4f5f7;
        }


        html.upmove-dark .settings-work-options {
            background: #1b2028;
        }


        html.upmove-dark .settings-day-label {
            background: #2a313c;
            color: #a3acba;
        }


        /*
         * Wanneer de checkbox van een dag
         * geselecteerd is.
         */
        html.upmove-dark .work-day:checked+.settings-day-label {
            background: #ff6b4a;
            color: white;
        }


        html.upmove-dark .premium-card {
            background: #382823;
        }


        html.upmove-dark .upmove-footer {
            background: rgba(32, 38, 48, 0.95);
            border-color: #343b46;
        }
    </style>

</head>


<body
    class="min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased">


    <div
        class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">


        <!-- ========================================= -->
        <!-- SETTINGS -->
        <!-- ========================================= -->

        <main
            id="screen-settings"
            class="upmove-settings flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
            data-user-id="<?= htmlspecialchars(
                                (string) $userId,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">


            <!-- ===================================== -->
            <!-- HEADER -->
            <!-- ===================================== -->

            <header
                class="relative mb-6 flex min-h-11 items-center justify-center">

                <h1
                    class="text-[17px] font-extrabold tracking-tight">
                    Instellingen
                </h1>


                <button
                    type="button"
                    class="settings-card absolute right-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition active:scale-95"
                    aria-label="Meldingen">

                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true">

                        <path
                            d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round" />

                        <path
                            d="M10 21h4"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round" />

                    </svg>

                </button>

            </header>



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


                        <div
                            class="mb-3 flex items-center justify-between gap-3">

                            <div>

                                <label
                                    for="setting-frequency"
                                    class="text-[13px] font-bold">
                                    Herinneringsfrequentie
                                </label>

                                <p
                                    class="settings-description mt-0.5 text-[11px] text-[#9299a3]">
                                    Hoe vaak wil je een beweegpauze?
                                </p>

                            </div>


                            <span
                                id="settings-frequency-label"
                                class="shrink-0 text-[12px] font-bold text-orange">
                                Elke 45 min
                            </span>

                        </div>


                        <input
                            id="setting-frequency"
                            type="range"
                            min="15"
                            max="120"
                            step="15"
                            value="45"
                            class="upmove-range block"
                            aria-label="Herinneringsfrequentie">

                    </div>



                    <div
                        class="settings-divider mx-4 h-px bg-[#eceef2]"></div>



                    <!-- DAGDOEL -->

                    <div
                        class="flex items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Dagelijks sta-doel
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Hoe vaak wil je per dag opstaan?
                            </p>

                        </div>


                        <div
                            class="flex shrink-0 items-center gap-2">


                            <button
                                type="button"
                                id="settings-goal-minus"
                                class="settings-button grid h-8 w-8 place-items-center rounded-lg bg-[#f4f6f8] text-base font-bold transition active:scale-90 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Dagdoel verlagen">
                                −
                            </button>


                            <span
                                id="settings-goal-value"
                                class="min-w-6 text-center text-[14px] font-extrabold">
                                12
                            </span>


                            <button
                                type="button"
                                id="settings-goal-plus"
                                class="settings-button grid h-8 w-8 place-items-center rounded-lg bg-[#f4f6f8] text-base font-bold transition active:scale-90 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Dagdoel verhogen">
                                +
                            </button>


                        </div>

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

                    <label
                        class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Alleen tijdens werk/school
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Herinneringen alleen binnen
                                jouw ingestelde tijden
                            </p>

                        </div>


                        <!-- SWITCH -->

                        <div
                            class="relative shrink-0">

                            <input
                                id="setting-work-only"
                                type="checkbox"
                                class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]"></div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>

                        </div>

                    </label>



                    <!-- WERKTIJD OPTIES -->

                    <div
                        id="work-hours-options"
                        class="settings-work-options border-t border-[#eceef2] bg-[#fafbfc] p-4 transition">


                        <!-- TIJDEN -->

                        <div
                            class="grid grid-cols-2 gap-3">


                            <!-- START -->

                            <div>

                                <label
                                    for="setting-work-start"
                                    class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">
                                    Starttijd
                                </label>

                                <input
                                    id="setting-work-start"
                                    type="time"
                                    value="08:30"
                                    class="settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange">

                            </div>



                            <!-- EIND -->

                            <div>

                                <label
                                    for="setting-work-end"
                                    class="settings-label mb-1.5 block text-[11px] font-semibold text-[#858c97]">
                                    Eindtijd
                                </label>

                                <input
                                    id="setting-work-end"
                                    type="time"
                                    value="17:00"
                                    class="settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange">

                            </div>

                        </div>



                        <!-- DAGEN -->

                        <div class="mt-4">


                            <p
                                class="settings-label mb-2 text-[11px] font-semibold text-[#858c97]">
                                Werk- / schooldagen
                            </p>


                            <div
                                class="grid grid-cols-7 gap-1.5">


                                <!-- MA -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="1"
                                        checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Ma
                                    </span>

                                </label>


                                <!-- DI -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="2"
                                        checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Di
                                    </span>

                                </label>


                                <!-- WO -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="3"
                                        checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Wo
                                    </span>

                                </label>


                                <!-- DO -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="4"
                                        checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Do
                                    </span>

                                </label>


                                <!-- VR -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="5"
                                        checked>

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Vr
                                    </span>

                                </label>


                                <!-- ZA -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="6">

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Za
                                    </span>

                                </label>


                                <!-- ZO -->

                                <label class="cursor-pointer">

                                    <input
                                        type="checkbox"
                                        class="work-day peer sr-only"
                                        value="0">

                                    <span
                                        class="settings-day-label grid h-9 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#7d858f] transition peer-checked:bg-orange peer-checked:text-white">
                                        Zo
                                    </span>

                                </label>

                            </div>

                        </div>



                        <!-- STATUS -->

                        <div
                            id="work-hours-summary"
                            class="mt-4 rounded-xl bg-[#eef8f7] px-3 py-2.5 text-[11px] font-semibold leading-relaxed text-[#268f85]">
                            Ma t/m Vr · 08:30 - 17:00
                        </div>


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


                    <!-- HERINNERINGEN -->

                    <label
                        class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Bewegingsherinneringen
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Ontvang meldingen wanneer het
                                tijd is om te bewegen
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input
                                id="setting-reminders"
                                type="checkbox"
                                class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]"></div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>

                        </div>

                    </label>



                    <div
                        class="settings-divider mx-4 h-px bg-[#eceef2]"></div>



                    <!-- STILLE UREN -->

                    <label
                        class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Stille uren
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Geen meldingen tussen
                                21:00 - 08:00
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input
                                id="setting-quiet-hours"
                                type="checkbox"
                                class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]"></div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>

                        </div>

                    </label>



                    <div
                        class="settings-divider mx-4 h-px bg-[#eceef2]"></div>



                    <!-- GELUID -->

                    <label
                        class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Geluidseffecten
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Speel een vriendelijke toon
                                bij een melding
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input
                                id="setting-sound"
                                type="checkbox"
                                class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]"></div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>

                        </div>

                    </label>



                    <div
                        class="settings-divider mx-4 h-px bg-[#eceef2]"></div>



                    <!-- DARK MODE -->

                    <label
                        class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p
                                class="text-[13px] font-bold">
                                Donkere modus
                            </p>

                            <p
                                class="settings-description mt-0.5 text-[11px] leading-snug text-[#9299a3]">
                                Gebruik het donkere thema
                                in heel UpMove
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input
                                id="setting-dark"
                                type="checkbox"
                                class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]"></div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>

                        </div>

                    </label>


                </div>

            </section>



            <!-- ===================================== -->
            <!-- PREMIUM -->
            <!-- ===================================== -->

            <section
                class="premium-card rounded-[17px] bg-[#fff0ec] px-4 py-4">

                <p
                    class="text-[13px] font-extrabold text-orange">
                    Ontgrendel UpMove Premium
                </p>

                <p
                    class="settings-description mt-1 text-[11px] leading-relaxed text-[#777f8a]">
                    Krijg slimme smartwatch-sync,
                    aanpasbare widgets en
                    50+ premium stretches.
                </p>

            </section>



            <!-- ===================================== -->
            <!-- LOCALSTORAGE FOUT -->
            <!-- ===================================== -->

            <p
                id="settings-storage-error"
                class="mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-[12px] font-medium text-red-600"></p>


        </main>



        <!-- ========================================= -->
        <!-- FOOTER -->
        <!-- ========================================= -->

        <?php

        require_once __DIR__ . '/../../components/footer.php';

        ?>


    </div>



    <script>
        (() => {

            /*
             * ==========================================
             * ELEMENTEN
             * ==========================================
             */

            const screen =
                document.getElementById(
                    'screen-settings'
                );

            if (!screen) {
                return;
            }


            const userKey =
                `upmove:settings:${screen.dataset.userId}`;

            const darkModeKey =
                'upmove:dark-mode';

            const workHoursKey =
                'upmove:work-hours';



            /*
             * BEWEGINGSDOELEN
             */

            const frequency =
                document.getElementById(
                    'setting-frequency'
                );

            const frequencyLabel =
                document.getElementById(
                    'settings-frequency-label'
                );

            const goalValue =
                document.getElementById(
                    'settings-goal-value'
                );

            const goalMinus =
                document.getElementById(
                    'settings-goal-minus'
                );

            const goalPlus =
                document.getElementById(
                    'settings-goal-plus'
                );



            /*
             * WERK / SCHOOL
             */

            const workOnly =
                document.getElementById(
                    'setting-work-only'
                );

            const workStart =
                document.getElementById(
                    'setting-work-start'
                );

            const workEnd =
                document.getElementById(
                    'setting-work-end'
                );

            const workDays =
                document.querySelectorAll(
                    '.work-day'
                );

            const workOptions =
                document.getElementById(
                    'work-hours-options'
                );

            const workSummary =
                document.getElementById(
                    'work-hours-summary'
                );



            /*
             * VOORKEUREN
             */

            const switches = {

                reminders: document.getElementById(
                    'setting-reminders'
                ),

                quietHours: document.getElementById(
                    'setting-quiet-hours'
                ),

                sound: document.getElementById(
                    'setting-sound'
                ),

                darkMode: document.getElementById(
                    'setting-dark'
                )

            };


            const storageError =
                document.getElementById(
                    'settings-storage-error'
                );



            /*
             * ==========================================
             * STANDAARDINSTELLINGEN
             * ==========================================
             */

            const settings = {

                frequency: 45,

                dailyGoal: 12,

                reminders: true,

                quietHours: true,

                sound: false,

                darkMode: false,

                workOnly: false,

                workStart: '08:30',

                workEnd: '17:00',

                workDays: [
                    1,
                    2,
                    3,
                    4,
                    5
                ]

            };



            /*
             * ==========================================
             * OPGESLAGEN INSTELLINGEN LADEN
             * ==========================================
             */

            try {

                const saved =
                    JSON.parse(
                        localStorage.getItem(
                            userKey
                        )
                    );


                if (
                    saved &&
                    typeof saved === 'object'
                ) {


                    /*
                     * FREQUENTIE
                     */
                    if (
                        Number.isInteger(
                            saved.frequency
                        ) &&
                        saved.frequency >= 15 &&
                        saved.frequency <= 120 &&
                        saved.frequency % 15 === 0
                    ) {

                        settings.frequency =
                            saved.frequency;

                    }



                    /*
                     * DAGDOEL
                     */
                    if (
                        Number.isInteger(
                            saved.dailyGoal
                        ) &&
                        saved.dailyGoal >= 1 &&
                        saved.dailyGoal <= 30
                    ) {

                        settings.dailyGoal =
                            saved.dailyGoal;

                    }



                    /*
                     * SWITCHES
                     */
                    [
                        'reminders',
                        'quietHours',
                        'sound',
                        'darkMode',
                        'workOnly'
                    ].forEach((name) => {

                        if (
                            typeof saved[name] ===
                            'boolean'
                        ) {

                            settings[name] =
                                saved[name];

                        }

                    });



                    /*
                     * WERKTIJD START
                     */
                    if (
                        typeof saved.workStart ===
                        'string' &&
                        /^\d{2}:\d{2}$/.test(
                            saved.workStart
                        )
                    ) {

                        settings.workStart =
                            saved.workStart;

                    }



                    /*
                     * WERKTIJD EIND
                     */
                    if (
                        typeof saved.workEnd ===
                        'string' &&
                        /^\d{2}:\d{2}$/.test(
                            saved.workEnd
                        )
                    ) {

                        settings.workEnd =
                            saved.workEnd;

                    }



                    /*
                     * WERKDAGEN
                     */
                    if (
                        Array.isArray(
                            saved.workDays
                        )
                    ) {

                        const validDays =
                            saved.workDays
                            .map(Number)
                            .filter(
                                day =>
                                Number.isInteger(day) &&
                                day >= 0 &&
                                day <= 6
                            );


                        if (validDays.length > 0) {

                            settings.workDays = [...new Set(validDays)];

                        }

                    }

                }

            } catch {

                /*
                 * Gebruik standaardwaarden wanneer
                 * localStorage niet gelezen kan worden.
                 */

            }



            /*
             * ==========================================
             * WERKDAGEN TEKST
             * ==========================================
             */

            function getWorkDaysText() {

                const names = {
                    0: 'Zo',
                    1: 'Ma',
                    2: 'Di',
                    3: 'Wo',
                    4: 'Do',
                    5: 'Vr',
                    6: 'Za'
                };


                /*
                 * Ma t/m Vr
                 */
                const weekdays = [
                    1,
                    2,
                    3,
                    4,
                    5
                ];


                if (
                    JSON.stringify(
                        [...settings.workDays].sort()
                    ) ===
                    JSON.stringify(
                        [...weekdays].sort()
                    )
                ) {

                    return 'Ma t/m Vr';

                }


                /*
                 * Alle dagen
                 */
                if (
                    settings.workDays.length === 7
                ) {

                    return 'Elke dag';

                }


                return settings.workDays
                    .map(
                        day =>
                        names[day]
                    )
                    .join(', ');

            }



            /*
             * ==========================================
             * DARK MODE
             * ==========================================
             */

            function applyDarkMode() {

                document.documentElement
                    .classList.toggle(
                        'upmove-dark',
                        settings.darkMode
                    );


                const themeColor =
                    document.getElementById(
                        'theme-color'
                    );


                if (themeColor) {

                    themeColor.setAttribute(
                        'content',
                        settings.darkMode ?
                        '#151920' :
                        '#f7f8fa'
                    );

                }

            }



            /*
             * ==========================================
             * RENDER
             * ==========================================
             */

            function render() {


                /*
                 * FREQUENTIE
                 */

                frequency.value =
                    settings.frequency;


                frequencyLabel.textContent =
                    `Elke ${settings.frequency} min`;


                frequency.setAttribute(
                    'aria-valuetext',
                    `Elke ${settings.frequency} minuten`
                );


                const rangeProgress =
                    (
                        (
                            settings.frequency - 15
                        ) /
                        (120 - 15)
                    ) *
                    100;


                frequency.style.setProperty(
                    '--range-progress',
                    `${rangeProgress}%`
                );



                /*
                 * DAGDOEL
                 */

                goalValue.textContent =
                    settings.dailyGoal;


                goalMinus.disabled =
                    settings.dailyGoal <= 1;


                goalPlus.disabled =
                    settings.dailyGoal >= 30;



                /*
                 * ALGEMENE SWITCHES
                 */

                Object.entries(
                    switches
                ).forEach(
                    ([name, input]) => {

                        input.checked =
                            settings[name];

                    }
                );



                /*
                 * WERK / SCHOOL
                 */

                workOnly.checked =
                    settings.workOnly;


                workStart.value =
                    settings.workStart;


                workEnd.value =
                    settings.workEnd;


                workDays.forEach(
                    (input) => {

                        input.checked =
                            settings.workDays.includes(
                                Number(
                                    input.value
                                )
                            );

                    }
                );



                /*
                 * Werkopties minder opvallend wanneer
                 * "alleen tijdens werk/school" uit staat.
                 */

                workOptions.classList.toggle(
                    'opacity-45',
                    !settings.workOnly
                );


                workStart.disabled = !settings.workOnly;


                workEnd.disabled = !settings.workOnly;


                workDays.forEach(
                    (input) => {

                        input.disabled = !settings.workOnly;

                    }
                );



                /*
                 * Samenvatting
                 */

                if (settings.workOnly) {

                    workSummary.textContent =
                        `${getWorkDaysText()} · ${settings.workStart} - ${settings.workEnd}`;


                    workSummary.classList.remove(
                        'hidden'
                    );

                } else {

                    workSummary.textContent =
                        'UpMove is de hele dag actief';

                }



                /*
                 * DARK MODE
                 */

                applyDarkMode();



                /*
                 * Andere scripts kunnen alles
                 * via window.upmoveSettings uitlezen.
                 */

                window.upmoveSettings =
                    Object.freeze({
                        ...settings,
                        workDays: [
                            ...settings.workDays
                        ]
                    });

            }



            /*
             * ==========================================
             * OPSLAAN
             * ==========================================
             */

            function save() {

                render();


                try {


                    /*
                     * ALLE SETTINGS PER ACCOUNT
                     */

                    localStorage.setItem(
                        userKey,
                        JSON.stringify(
                            settings
                        )
                    );



                    /*
                     * DARK MODE GLOBAAL
                     *
                     * Andere pagina's hoeven hierdoor
                     * niet te weten welke gebruiker-key
                     * gebruikt wordt.
                     */

                    localStorage.setItem(
                        darkModeKey,
                        String(
                            settings.darkMode
                        )
                    );



                    /*
                     * WERKTIJDEN GLOBAAL
                     */

                    localStorage.setItem(
                        workHoursKey,
                        JSON.stringify({
                            enabled: settings.workOnly,

                            start: settings.workStart,

                            end: settings.workEnd,

                            days: [
                                ...settings.workDays
                            ]
                        })
                    );



                    storageError.classList.add(
                        'hidden'
                    );


                } catch {

                    storageError.textContent =
                        'Je browser kan deze instellingen niet bewaren. Ze blijven actief totdat je de pagina herlaadt.';


                    storageError.classList.remove(
                        'hidden'
                    );

                }



                /*
                 * Andere scripts vertellen dat
                 * instellingen veranderd zijn.
                 */

                window.dispatchEvent(
                    new CustomEvent(
                        'upmove:settings-changed', {
                            detail: {
                                ...settings,
                                workDays: [
                                    ...settings.workDays
                                ]
                            }
                        }
                    )
                );

            }



            /*
             * ==========================================
             * EVENT LISTENERS
             * ==========================================
             */


            /*
             * FREQUENTIE
             */

            frequency.addEventListener(
                'input',
                () => {

                    settings.frequency =
                        Number(
                            frequency.value
                        );

                    save();

                }
            );



            /*
             * DAGDOEL -
             */

            goalMinus.addEventListener(
                'click',
                () => {

                    settings.dailyGoal =
                        Math.max(
                            1,
                            settings.dailyGoal - 1
                        );

                    save();

                }
            );



            /*
             * DAGDOEL +
             */

            goalPlus.addEventListener(
                'click',
                () => {

                    settings.dailyGoal =
                        Math.min(
                            30,
                            settings.dailyGoal + 1
                        );

                    save();

                }
            );



            /*
             * NORMALE SWITCHES
             */

            Object.entries(
                switches
            ).forEach(
                ([name, input]) => {

                    input.addEventListener(
                        'change',
                        () => {

                            settings[name] =
                                input.checked;

                            save();

                        }
                    );

                }
            );



            /*
             * WERK/SCHOOL AAN / UIT
             */

            workOnly.addEventListener(
                'change',
                () => {

                    settings.workOnly =
                        workOnly.checked;

                    save();

                }
            );



            /*
             * STARTTIJD
             */

            workStart.addEventListener(
                'change',
                () => {

                    settings.workStart =
                        workStart.value;

                    save();

                }
            );



            /*
             * EINDTIJD
             */

            workEnd.addEventListener(
                'change',
                () => {

                    settings.workEnd =
                        workEnd.value;

                    save();

                }
            );



            /*
             * WERKDAGEN
             */

            workDays.forEach(
                (input) => {

                    input.addEventListener(
                        'change',
                        () => {

                            settings.workDays =
                                Array
                                .from(
                                    workDays
                                )
                                .filter(
                                    day =>
                                    day.checked
                                )
                                .map(
                                    day =>
                                    Number(
                                        day.value
                                    )
                                );


                            /*
                             * Zorg dat minimaal één dag
                             * geselecteerd blijft.
                             */

                            if (
                                settings.workDays.length ===
                                0
                            ) {

                                input.checked =
                                    true;


                                settings.workDays = [
                                    Number(
                                        input.value
                                    )
                                ];

                            }


                            save();

                        }
                    );

                }
            );



            /*
             * ==========================================
             * START
             * ==========================================
             */

            render();

        })();
    </script>


</body>

</html>