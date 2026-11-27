  (() => {

    const navItems =
      document.querySelectorAll('.nav-item[data-screen]');

    const screens =
      document.querySelectorAll('.screen');


    /*
     * Op Home kunnen we zonder reload tussen
     * Home, Ranglijst, Leren en Statistieken wisselen.
     *
     * Op settings.php bestaan deze schermen niet,
     * dus werkt de href gewoon als normale link.
     */
    function openScreen(screenName, updateUrl = true) {

      const target =
        document.getElementById(`screen-${screenName}`);

      if (!target) {
        return false;
      }


      screens.forEach((screen) => {

        screen.classList.toggle(
          'hidden',
          screen !== target
        );

      });


      navItems.forEach((item) => {

        const active =
          item.dataset.screen === screenName;

        item.classList.toggle(
          'text-orange',
          active
        );

        item.classList.toggle(
          'text-[#656c77]',
          !active
        );


        if (active) {

          item.setAttribute(
            'aria-current',
            'page'
          );

        } else {

          item.removeAttribute(
            'aria-current'
          );

        }

      });


      /*
       * URL veranderen naar bijvoorbeeld:
       *
       * ?screen=ranglijst
       *
       * Daardoor kan je dezelfde pagina
       * ook rechtstreeks openen.
       */
      if (updateUrl) {

        const url =
          new URL(window.location.href);

        url.searchParams.set(
          'screen',
          screenName
        );

        history.replaceState({},
          '',
          url
        );

      }

      // Bij wisselen van scherm altijd bovenaan beginnen.
      window.scrollTo(0, 0);

      return true;
    }


    navItems.forEach((item) => {

      item.addEventListener('click', (event) => {

        const screenName =
          item.dataset.screen;

        /*
         * Bestaat het scherm op deze pagina?
         * Dan blijven we op dezelfde pagina.
         */
        if (
          document.getElementById(
            `screen-${screenName}`
          )
        ) {

          event.preventDefault();

          openScreen(
            screenName,
            true
          );

        }

        /*
         * Bestaat het scherm niet?
         * Bijvoorbeeld als we op settings.php zitten.
         *
         * Dan wordt event.preventDefault NIET uitgevoerd
         * en volgt de browser gewoon de href.
         */

      });

    });


    /*
     * Bij het openen van de homepage kijken
     * of bijvoorbeeld ?screen=leren is meegegeven.
     */
    if (screens.length > 0) {

      const params =
        new URLSearchParams(
          window.location.search
        );

      const requestedScreen =
        params.get('screen') || 'home';


      if (
        document.getElementById(
          `screen-${requestedScreen}`
        )
      ) {

        openScreen(
          requestedScreen,
          false
        );

      } else {

        openScreen(
          'home',
          false
        );

      }

    }

  })();
