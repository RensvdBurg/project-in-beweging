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
