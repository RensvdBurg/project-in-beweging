(() => {
    const DARK_KEY = 'upmove:dark-mode';
    const WORK_KEY = 'upmove:work-hours';

    /*
     * DARK MODE
     * Wordt op elke pagina uitgevoerd waar dit bestand geladen wordt.
     */
    function applyDarkMode() {
        try {
            const darkMode =
                localStorage.getItem(DARK_KEY) === 'true';

            document.documentElement.classList.toggle(
                'upmove-dark',
                darkMode
            );
        } catch {
            // localStorage niet beschikbaar
        }
    }


    /*
     * Werk/schoolinstellingen ophalen.
     */
    function getWorkHours() {
        const defaults = {
            enabled: false,
            start: '08:30',
            end: '17:00',
            days: [1, 2, 3, 4, 5]
        };

        try {
            const saved = JSON.parse(
                localStorage.getItem(WORK_KEY)
            );

            if (!saved || typeof saved !== 'object') {
                return defaults;
            }

            return {
                enabled:
                    typeof saved.enabled === 'boolean'
                        ? saved.enabled
                        : defaults.enabled,

                start:
                    typeof saved.start === 'string'
                        ? saved.start
                        : defaults.start,

                end:
                    typeof saved.end === 'string'
                        ? saved.end
                        : defaults.end,

                days:
                    Array.isArray(saved.days)
                        ? saved.days
                        : defaults.days
            };
        } catch {
            return defaults;
        }
    }


    /*
     * Zet "08:30" om naar minuten:
     *
     * 8 * 60 + 30 = 510
     */
    function timeToMinutes(time) {
        const [hours, minutes] =
            time.split(':').map(Number);

        return (hours * 60) + minutes;
    }


    /*
     * Controleert of UpMove op dit moment actief mag zijn.
     *
     * true  = reminders mogen
     * false = buiten werk/school
     */
    function isActiveNow(date = new Date()) {
        const settings = getWorkHours();

        /*
         * Beperking staat uit:
         * UpMove mag altijd reminders geven.
         */
        if (!settings.enabled) {
            return true;
        }

        const currentDay = date.getDay();

        /*
         * Vandaag is geen geselecteerde werk/schooldag.
         */
        if (!settings.days.includes(currentDay)) {
            return false;
        }

        const currentMinutes =
            (date.getHours() * 60) +
            date.getMinutes();

        const start =
            timeToMinutes(settings.start);

        const end =
            timeToMinutes(settings.end);


        /*
         * Normale tijd:
         * bijvoorbeeld 08:30 - 17:00
         */
        if (start <= end) {
            return (
                currentMinutes >= start &&
                currentMinutes <= end
            );
        }


        /*
         * Werkt ook met nachtdiensten:
         * bijvoorbeeld 22:00 - 06:00
         */
        return (
            currentMinutes >= start ||
            currentMinutes <= end
        );
    }


    /*
     * Beschikbaar maken voor andere scripts.
     */
    window.UpMoveSettings = {
        applyDarkMode,
        getWorkHours,
        isActiveNow
    };


    /*
     * Meteen dark mode toepassen.
     */
    applyDarkMode();


    /*
     * Wanneer instellingen veranderen,
     * dark mode opnieuw toepassen.
     */
    window.addEventListener(
        'upmove:settings-changed',
        applyDarkMode
    );
})();