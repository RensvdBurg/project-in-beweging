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


    const initialSettings =
        window.upmoveInitialSettings || {};



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

    const workNotificationStatus =
        document.getElementById(
            'work-notification-status'
        );



    /*
     * VOORKEUREN
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

        frequency: 60,

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



    Object.assign(settings, initialSettings.settings || {});

    window.addEventListener('upmove:settings-loaded', (event) => {
        if (event.detail && typeof event.detail === 'object') {
            Object.assign(settings, event.detail);
            render();
        }
    });



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

    function renderWorkNotificationStatus() {
        if (!window.isSecureContext) {
            workNotificationStatus.textContent =
                'Browsermeldingen werken alleen via HTTPS of localhost.';
            return;
        }

        if (!('Notification' in window)) {
            workNotificationStatus.textContent =
                'Deze browser ondersteunt geen browsermeldingen.';
            return;
        }

        if (!settings.workOnly) {
            workNotificationStatus.textContent =
                'Zet deze instelling aan om meldingen bij nieuwe opdrachten te ontvangen.';
            return;
        }

        if (Notification.permission === 'granted') {
            workNotificationStatus.textContent =
                'Browsermeldingen zijn toegestaan. UpMove moet open zijn om nieuwe opdrachten te signaleren.';
        } else if (Notification.permission === 'denied') {
            workNotificationStatus.textContent =
                'Browsermeldingen zijn geblokkeerd. Pas dit aan via de site-instellingen van je browser.';
        } else {
            workNotificationStatus.textContent =
                'Geef toestemming in je browser om meldingen bij nieuwe opdrachten te ontvangen.';
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

        renderWorkNotificationStatus();



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

    let saveTimer;
    let saveInProgress = false;
    let savePending = false;
    let saveVersion = 0;

    async function persistSettings(version) {
        if (saveInProgress) {
            savePending = true;
            return;
        }

        saveInProgress = true;
        const snapshot = {
            ...settings,
            workDays: [...settings.workDays],
        };

        try {
            const response = await fetch(
                '/project-in-beweging/pages/settings/settings-api.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': initialSettings.csrfToken,
                    },
                    keepalive: true,
                    body: JSON.stringify({ settings: snapshot }),
                }
            );
            const result = await response.json();
            if (!response.ok) {
                throw new Error(
                    result.error || 'De instellingen konden niet worden opgeslagen.'
                );
            }

            if (version === saveVersion) {
                storageError.classList.add('hidden');
            }
        } catch (error) {
            console.error('Instellingen konden niet in de database worden opgeslagen.', error);
            if (version === saveVersion) {
                storageError.textContent =
                    error.message || 'Je instellingen konden niet in de database worden opgeslagen.';
                storageError.classList.remove('hidden');
            }
        } finally {
            saveInProgress = false;
            if (savePending || version !== saveVersion) {
                savePending = false;
                persistSettings(saveVersion);
            }
        }
    }

    function save(immediate = false) {
        render();
        saveVersion += 1;
        window.dispatchEvent(
            new CustomEvent('upmove:settings-changed', {
                detail: {
                    ...settings,
                    workDays: [...settings.workDays],
                },
            })
        );

        window.clearTimeout(saveTimer);
        if (immediate) {
            persistSettings(saveVersion);
        } else {
            saveTimer = window.setTimeout(
                () => persistSettings(saveVersion),
                250
            );
        }
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

    frequency.addEventListener(
        'change',
        () => save(true)
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

            if (
                settings.workOnly &&
                window.isSecureContext &&
                'Notification' in window &&
                Notification.permission === 'default'
            ) {
                try {
                    Notification.requestPermission()
                        .then(renderWorkNotificationStatus)
                        .catch((error) => {
                            console.error(
                                'Browsermeldingstoestemming kon niet worden aangevraagd.',
                                error
                            );
                            workNotificationStatus.textContent =
                                'Browsermeldingstoestemming kon niet worden aangevraagd.';
                        });
                } catch (error) {
                    console.error(
                        'Browsermeldingstoestemming kon niet worden aangevraagd.',
                        error
                    );
                    workNotificationStatus.textContent =
                        'Browsermeldingstoestemming kon niet worden aangevraagd.';
                }
            }

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
