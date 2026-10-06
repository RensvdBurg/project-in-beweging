<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}


/*
 * home.php hoort via de hoofdapp geladen te worden.
 * Niet ingelogd = terug naar index.
 */
if (
  realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__
  ||
  empty($_SESSION['user'])
) {

  header(
    'Location: ../../index.php',
    true,
    303
  );

  exit;
}


/*
 * CSRF token voor bijvoorbeeld uitloggen.
 */
if (empty($_SESSION['csrf_token'])) {

  $_SESSION['csrf_token'] =
    bin2hex(
      random_bytes(32)
    );
}


$homeCsrfToken =
  $_SESSION['csrf_token'];

?>


<div
  class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">


  <!-- ======================================== -->
  <!-- HOME -->
  <!-- ======================================== -->

  <main
    class="screen flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-home">


    <!-- HEADER -->
    <header
      class="mb-4 flex flex-wrap items-start justify-between gap-3 sm:mb-5">


      <div class="min-w-0">

        <p
          class="mb-0.5 text-sm font-medium text-muted">
          Welkom terug
        </p>


        <h1
          class="text-2xl font-extrabold tracking-tight sm:text-[1.75rem]">
          Hé,
          <?= htmlspecialchars(
            $_SESSION['user']['full_name'],
            ENT_QUOTES,
            'UTF-8'
          ) ?>!
        </h1>

      </div>



      <!-- STREAK -->
      <button
        type="button"
        id="btn-streak"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-orange-streak px-3 py-2 text-xs font-bold text-orange-streak-text transition active:scale-95 sm:text-[13px]"
        aria-label="Streak: 5 dagen">

        <span aria-hidden="true">
          🔥
        </span>

        <span id="streak-text">
          5 Dagen Reeks
        </span>

      </button>

    </header>



    <!-- UITLOGGEN -->
    <form
      method="post"
      class="mb-4 flex justify-end">

      <input
        type="hidden"
        name="action"
        value="logout">

      <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
                  $homeCsrfToken,
                  ENT_QUOTES,
                  'UTF-8'
                ) ?>">

      <button
        type="submit"
        class="rounded-full px-3 py-2 text-xs font-semibold text-muted hover:bg-surface">
        Uitloggen
      </button>

    </form>



    <!-- WAARSCHUWING -->
    <button
      type="button"
      id="btn-alert"
      class="alert-card mb-4 flex w-full items-center gap-3 rounded-[18px] border-[1.5px] border-alert-border bg-alert-bg p-3.5 text-left transition active:scale-[0.99] sm:p-4"
      aria-label="Waarschuwing: te lang gezeten">


      <span
        class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-alert-icon"
        aria-hidden="true">

        <svg
          width="22"
          height="22"
          viewBox="0 0 24 24"
          fill="none">

          <path
            d="M12 3.5L21.5 20H2.5L12 3.5Z"
            fill="#1A1A1A" />

          <rect
            x="11"
            y="9"
            width="2"
            height="6"
            rx="1"
            fill="#F5D76E" />

          <circle
            cx="12"
            cy="17.5"
            r="1.2"
            fill="#F5D76E" />

        </svg>

      </span>



      <span
        class="flex min-w-0 flex-col gap-0.5">

        <strong
          class="text-[15px] font-bold tracking-tight">
          Te lang gezeten! (45 min)
        </strong>

        <span
          class="text-[13px] leading-snug text-[#5c616a]">
          Tijd om op te staan en een
          snelle 2 min stretch te doen.
        </span>

      </span>

    </button>



    <!-- ==================================== -->
    <!-- DAGELIJKSE VOORTGANG -->
    <!-- ==================================== -->

    <section
      class="mb-3.5 rounded-[22px] bg-surface p-5 shadow-card sm:p-7 sm:pb-5"
      aria-label="Dagelijkse voortgang">


      <!-- CIRKEL -->
      <div
        class="relative mx-auto mb-5 h-[190px] w-[190px] sm:mb-6 sm:h-[210px] sm:w-[210px]">

        <svg
          class="progress-ring h-full w-full"
          viewBox="0 0 200 200"
          aria-hidden="true">

          <circle
            class="progress-track"
            cx="100"
            cy="100"
            r="82" />

          <circle
            class="progress-value"
            id="progress-circle"
            cx="100"
            cy="100"
            r="82" />

        </svg>


        <div
          class="absolute inset-0 flex flex-col items-center justify-center text-center">

          <span
            class="text-4xl font-extrabold tracking-tighter sm:text-[42px]"
            id="standup-count">
            12/16
          </span>

          <span
            class="mt-1.5 text-[11px] font-semibold tracking-widest text-muted">
            KEER OPSTAAN
          </span>

        </div>

      </div>



      <!-- STATS -->
      <div
        class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">


        <div
          class="flex flex-col items-center gap-1 text-center">

          <span
            class="text-xl font-extrabold tracking-tight sm:text-[22px]"
            id="moved-time">
            15m
          </span>

          <span
            class="text-[13px] font-medium text-muted">
            Totaal bewogen
          </span>

        </div>



        <div
          class="h-9 w-px bg-[#e6e8ec]"
          aria-hidden="true"></div>



        <div
          class="flex flex-col items-center gap-1 text-center">

          <span
            class="text-xl font-extrabold tracking-tight sm:text-[22px]"
            id="calories">
            120 kcal
          </span>

          <span
            class="text-[13px] font-medium text-muted">
            Verbrand sch.
          </span>

        </div>

      </div>

    </section>



    <!-- ==================================== -->
    <!-- TIMER -->
    <!-- ==================================== -->

    <section
      class="mb-5 flex items-center gap-3 rounded-[18px] border-2 border-teal bg-surface p-3 sm:p-3.5">


      <div
        class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-teal-soft"
        aria-hidden="true">

        <svg
          width="22"
          height="22"
          viewBox="0 0 24 24"
          fill="none">

          <circle
            cx="12"
            cy="13"
            r="8"
            stroke="#2EC4B6"
            stroke-width="2" />

          <path
            d="M12 9v4l2.5 1.5"
            stroke="#2EC4B6"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round" />

          <path
            d="M9 3h6"
            stroke="#2EC4B6"
            stroke-width="2"
            stroke-linecap="round" />

        </svg>

      </div>



      <div
        class="min-w-0 flex-1">

        <p
          class="text-xs font-medium text-muted">
          Volgende bewegingspauze
        </p>

        <p
          class="text-base font-extrabold tracking-tight"
          id="next-break">
          Start over 14m 20s
        </p>

      </div>



      <button
        type="button"
        id="btn-postpone"
        class="shrink-0 rounded-full bg-ink px-3.5 py-2.5 text-[13px] font-bold text-white transition hover:bg-[#2a2a2a] active:scale-95">
        Uitstellen
      </button>

    </section>



    <!-- ==================================== -->
    <!-- BOOST -->
    <!-- ==================================== -->

    <section>


      <h2
        class="mb-3 text-lg font-extrabold tracking-tight">
        Klaar voor een snelle boost?
      </h2>



      <div
        class="flex items-center gap-3 rounded-[20px] bg-orange-soft p-4 sm:p-[18px]">


        <div class="min-w-0 flex-1">

          <p
            class="text-base font-extrabold text-orange">
            Direct 2 min stretchen
          </p>

          <p
            class="text-[13px] leading-snug text-[#6a707a]">
            Wacht niet op de timer.
            Activeer je spieren nu!
          </p>

        </div>



        <button
          type="button"
          id="btn-play"
          class="btn-play grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange shadow-[0_8px_18px_rgba(255,107,74,0.35)] transition hover:shadow-[0_10px_22px_rgba(255,107,74,0.45)] active:scale-95"
          aria-label="Start stretch">

          <svg
            class="ml-0.5"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true">

            <path
              d="M8 5.5v13l11-6.5L8 5.5z"
              fill="#fff" />

          </svg>

        </button>

      </div>

    </section>


  </main>



  <!-- ======================================== -->
  <!-- BEWEGEN -->
  <!-- ======================================== -->

  <section
    class="screen hidden flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-bewegen"
    aria-label="Bewegen">

    <h2
      class="mb-2 mt-2 text-2xl font-extrabold tracking-tight sm:text-[28px]">
      Bewegen
    </h2>


    <p
      class="mb-5 text-[15px] leading-relaxed text-muted">
      Kies een korte bewegingssessie
      om je dag een boost te geven.
    </p>


    <button
      type="button"
      class="panel-btn mb-2.5 w-full rounded-2xl bg-surface p-4 text-left text-[15px] font-bold shadow-card transition active:scale-[0.98] sm:px-[18px]"
      data-action="stretch">
      2 min stretchen
    </button>


    <button
      type="button"
      class="panel-btn mb-2.5 w-full rounded-2xl bg-surface p-4 text-left text-[15px] font-bold shadow-card transition active:scale-[0.98] sm:px-[18px]"
      data-action="walk">
      5 min wandelen
    </button>


    <button
      type="button"
      class="panel-btn mb-2.5 w-full rounded-2xl bg-surface p-4 text-left text-[15px] font-bold shadow-card transition active:scale-[0.98] sm:px-[18px]"
      data-action="desk">
      Bureau-oefeningen
    </button>

  </section>



  <!-- ======================================== -->
  <!-- LEREN -->
  <!-- ======================================== -->

  <section
    class="screen hidden flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-leren"
    aria-label="Leren">

    <h2
      class="mb-2 mt-2 text-2xl font-extrabold tracking-tight sm:text-[28px]">
      Leren
    </h2>


    <p
      class="mb-5 text-[15px] leading-relaxed text-muted">
      Tips om gezonder te zitten
      en vaker te bewegen.
    </p>



    <article
      class="mb-2.5 flex flex-col gap-1 rounded-2xl bg-surface p-4 shadow-card sm:px-[18px]">

      <strong class="text-[15px]">
        Waarom opstaan helpt
      </strong>

      <span
        class="text-[13px] leading-snug text-muted">
        Elke 30–45 minuten even staan
        verlaagt stijfheid.
      </span>

    </article>



    <article
      class="mb-2.5 flex flex-col gap-1 rounded-2xl bg-surface p-4 shadow-card sm:px-[18px]">

      <strong class="text-[15px]">
        Ademhaling
      </strong>

      <span
        class="text-[13px] leading-snug text-muted">
        3 diepe ademhalingen resetten
        je focus snel.
      </span>

    </article>

  </section>



  <!-- ======================================== -->
  <!-- STATISTIEKEN -->
  <!-- ======================================== -->

  <section
    class="screen hidden flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-stats"
    aria-label="Statistieken">

    <h2
      class="mb-2 mt-2 text-2xl font-extrabold tracking-tight sm:text-[28px]">
      Statistieken
    </h2>


    <p
      class="mb-5 text-[15px] leading-relaxed text-muted">
      Jouw weekoverzicht in één oogopslag.
    </p>



    <div
      class="flex h-[180px] items-end justify-between gap-2 rounded-[20px] bg-surface px-4 pb-8 pt-5 shadow-card"
      aria-hidden="true">

      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:45%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Ma
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:70%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Di
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:55%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Wo
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:90%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Do
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:75%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Vr
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:40%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Za
        </span>
      </div>


      <div
        class="week-bar relative min-h-6 flex-1 rounded-t-[10px] rounded-b-md bg-gradient-to-t from-orange to-[#ff9a7f]"
        style="--h:30%">
        <span
          class="absolute -bottom-[22px] left-1/2 -translate-x-1/2 text-[11px] font-semibold text-muted">
          Zo
        </span>
      </div>

    </div>

  </section>


  <!--
        GEEN SETTINGS MEER HIER.

        Settings staat nu als losse pagina op:

        /pages/settings/settings.php
    -->


</div>