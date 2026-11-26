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
     * '1' zolang de laatste wijziging nog niet door de server
     * bevestigd is (zie public/js/app-settings.js).
     */
    const pendingKey =
        `upmove:settings-pending:${screen.dataset.userId}`;

    let hasPending = false;

    try {

        hasPending =
            localStorage.getItem(pendingKey) === '1';

    } catch {

        // localStorage niet beschikbaar

    }



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

    /*
     * Start- en eindtijd per dag (data-day: 0 = zondag … 6 = zaterdag).
     */
    const workTimeInputs =
        document.querySelectorAll(
            '.work-time'
        );

    const workDayRows =
        document.querySelectorAll(
            '.work-day-row'
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

    /*
     * Bewegingsherinneringen, stille uren en geluidseffecten staan niet meer
     * op de pagina. Hun waarden blijven bewaard (UserSettings), maar zijn
     * hier niet meer aan te passen.
     */
    const switches = {

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
        ],

        /*
         * Tijden per dag; index = getDay() (0 = zondag).
         */
        workSchedule: Array.from(
            { length: 7 },
            () => ({
                start: '08:30',
                end: '17:00'
            })
        )

    };


    const isTime = (value) =>
        typeof value === 'string' &&
        /^([01]\d|2[0-3]):[0-5]\d$/.test(value);


    /*
     * Geldige tijden per dag; ontbreekt een dag (instellingen van vóór
     * de tijden per dag), dan geldt de algemene start-/eindtijd.
     */
    function normalizeWorkSchedule(source) {

        return Array.from(
            { length: 7 },
            (_, day) => {

                const hours =
                    Array.isArray(source.workSchedule) ?
                    source.workSchedule[day] :
                    null;

                return {
                    start: isTime(hours?.start) ? hours.start : settings.workStart,
                    end: isTime(hours?.end) ? hours.end : settings.workEnd
                };

            }
        );

    }


    /*
     * Kopie voor andere scripts (nooit het eigen object doorgeven).
     */
    function copyWorkSchedule() {

        return settings.workSchedule.map(
            (hours) => ({ ...hours })
        );

    }



    /*
     * ==========================================
     * OPGESLAGEN INSTELLINGEN LADEN
     * ==========================================
     */

    try {

        /*
         * Bron: de database (data-settings, per gebruiker).
         * localStorage alleen als reserve, of wanneer daar een
         * wijziging staat die de server nog niet bevestigd heeft.
         */
        const saved =
            screen.dataset.settings && !hasPending ?
            JSON.parse(
                screen.dataset.settings
            ) :
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



            /*
             * TIJDEN PER DAG
             */
            settings.workSchedule =
                normalizeWorkSchedule(
                    saved
                );

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


        return sortedWorkDays()
            .map(
                day =>
                names[day]
            )
            .join(', ');

    }


    /*
     * Werkdagen in de volgorde maandag t/m zondag.
     */
    function sortedWorkDays() {

        return [...settings.workDays].sort(
            (first, second) =>
            (first + 6) % 7 - (second + 6) % 7
        );

    }


    /*
     * Samenvatting: dezelfde tijden op alle dagen? Dan kort
     * ("Ma t/m Vr · 08:30 - 17:00"), anders per dag
     * ("Ma 08:30 - 17:00 · Wo 08:30 - 12:30").
     */
    function getWorkSummaryText() {

        const names = {
            0: 'Zo',
            1: 'Ma',
            2: 'Di',
            3: 'Wo',
            4: 'Do',
            5: 'Vr',
            6: 'Za'
        };

        const days = sortedWorkDays();

        const first = settings.workSchedule[days[0]];

        const sameTimes = days.every(
            (day) =>
            settings.workSchedule[day].start === first.start &&
            settings.workSchedule[day].end === first.end
        );

        if (sameTimes) {

            return `${getWorkDaysText()} · ${first.start} - ${first.end}`;

        }

        return days
            .map(
                (day) =>
                `${names[day]} ${settings.workSchedule[day].start} - ${settings.workSchedule[day].end}`
            )
            .join(' · ');

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


        /*
         * public/js/app-settings.js leest dit attribuut
         * (komt van de server); bijwerken zodat het klopt.
         */
        document.documentElement.dataset.darkMode =
            String(
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
         * DAGDOEL (staat niet altijd op de pagina)
         */

        if (goalValue && goalMinus && goalPlus) {

            goalValue.textContent =
                settings.dailyGoal;


            goalMinus.disabled =
                settings.dailyGoal <= 1;


            goalPlus.disabled =
                settings.dailyGoal >= 30;

        }



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


        workTimeInputs.forEach(
            (input) => {

                const period =
                    input.classList.contains('work-time-start') ?
                    'start' :
                    'end';

                input.value =
                    settings.workSchedule[Number(input.dataset.day)][period];

                input.disabled = !settings.workOnly;

            }
        );


        /*
         * Rijen van dagen die niet aangevinkt zijn iets lichter.
         */
        workDayRows.forEach(
            (row) => {

                row.classList.toggle(
                    'opacity-55',
                    !settings.workDays.includes(
                        Number(
                            row.dataset.day
                        )
                    )
                );

            }
        );


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
                getWorkSummaryText();


            /*
             * Meldingstekst: "elke X minuten" meteen bijwerken.
             */
            document
                .querySelectorAll('[data-work-frequency]')
                .forEach(
                    (element) => {

                        element.textContent =
                            String(settings.frequency);

                    }
                );


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
                ],
                workSchedule: copyWorkSchedule()
            });

    }



    /*
     * ==========================================
     * OPSLAAN IN DE DATABASE
     * ==========================================
     *
     * Kort wachten, zodat schuiven met de
     * frequentie niet tientallen verzoeken geeft.
     */

    let serverSaveTimer = null;

    /*
     * Telt elke wijziging; alleen het antwoord op de nieuwste
     * mag de openstaande-markering weghalen.
     */
    let saveVersion = 0;

    function showSaveError(message) {

        storageError.textContent =
            message;

        storageError.classList.remove(
            'hidden'
        );

    }


    async function sendToServer() {

        serverSaveTimer = null;

        const version =
            saveVersion;

        try {

            const response =
                await fetch(
                    screen.dataset.updateUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': screen.dataset.csrfToken
                        },
                        body: JSON.stringify(
                            settings
                        ),
                        // Verzoek loopt door als je meteen wegnavigeert.
                        keepalive: true
                    }
                );


            if (!response.ok) {

                throw new Error(
                    String(
                        response.status
                    )
                );

            }


            /*
             * Database is bij: de serverwaarden gelden weer.
             */
            if (version === saveVersion) {

                try {

                    localStorage.removeItem(
                        pendingKey
                    );

                } catch {

                    // negeren

                }

            }


            storageError.classList.add(
                'hidden'
            );

        } catch {

            showSaveError(
                'Je instellingen konden niet worden opgeslagen. Controleer je verbinding en probeer het opnieuw.'
            );

        }

    }


    function scheduleServerSave() {

        window.clearTimeout(
            serverSaveTimer
        );


        serverSaveTimer =
            window.setTimeout(
                sendToServer,
                400
            );

    }


    /*
     * Wegnavigeren (bijv. via het footermenu) terwijl er nog een
     * opslag in de wachtrij staat? Dan die meteen versturen.
     */
    function flushServerSave() {

        if (serverSaveTimer === null) {
            return;
        }

        window.clearTimeout(
            serverSaveTimer
        );

        sendToServer();

    }


    window.addEventListener(
        'pagehide',
        flushServerSave
    );


    document.addEventListener(
        'visibilitychange',
        () => {

            if (document.visibilityState === 'hidden') {

                flushServerSave();

            }

        }
    );



    /*
     * ==========================================
     * OPSLAAN
     * ==========================================
     */

    function save() {

        render();


        /*
         * Werktijden ook bijwerken voor
         * public/js/app-settings.js.
         */
        document.documentElement.dataset.workHours =
            JSON.stringify({
                enabled: settings.workOnly,
                start: settings.workStart,
                end: settings.workEnd,
                days: [
                    ...settings.workDays
                ],
                schedule: copyWorkSchedule()
            });


        saveVersion++;

        scheduleServerSave();


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
             * Tot de server dit bevestigt, gelden deze
             * waarden op elke pagina (dark mode, melding).
             */

            localStorage.setItem(
                pendingKey,
                '1'
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
                    ],

                    schedule: copyWorkSchedule()
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
                        ],
                        workSchedule: copyWorkSchedule()
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

    goalMinus?.addEventListener(
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

    goalPlus?.addEventListener(
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
     * START- EN EINDTIJD PER DAG
     */

    workTimeInputs.forEach(
        (input) => {

            input.addEventListener(
                'change',
                () => {

                    // Leeggemaakt veld: de vorige tijd terugzetten.
                    if (!isTime(input.value)) {

                        render();

                        return;

                    }

                    const period =
                        input.classList.contains('work-time-start') ?
                        'start' :
                        'end';

                    settings.workSchedule[Number(input.dataset.day)][period] =
                        input.value;

                    save();

                }
            );

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


    /*
     * Nog niets in de database (geen data-settings)?
     * Dan de instellingen van hierboven (uit localStorage
     * of de standaard) meteen opslaan.
     */
    if (!screen.dataset.settings || hasPending) {

        scheduleServerSave();

    }

})();
