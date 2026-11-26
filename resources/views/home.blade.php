@extends('layouts.app', ['scripts' => ['resources/css/learn-page-transition.css', 'resources/js/home.js', 'resources/js/community.js', 'resources/js/learn.js', 'resources/js/footer-nav.js']])

@section('title', 'Home | UpMove')

@section('body')
<div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">

  <main @class([
      'screen flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6',
      'hidden' => in_array($screen, ['ranglijst', 'leren'], true),
    ])
    id="screen-home">

    <header class="home-motion mb-4 flex flex-wrap items-start justify-between gap-3 sm:mb-5">
      <div class="min-w-0 flex-1">
        <p class="mb-0.5 text-sm font-medium text-muted">Welkom terug</p>
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-[1.75rem]">
          Hé, {{ $user->full_name }}
        </h1>
        <p class="text-sm font-bold text-orange">Totaal: {{ (int) $user->xp }} XP</p>
      </div>
      <div class="mb-4 flex shrink-0 justify-end">
        <a href="{{ route('activities.index') }}"
          class="home-action rounded-full px-3 py-2 text-xs font-bold text-white hover:bg-[#e02e06] bg-[#ff6b4a] cursor-pointer">Activiteiten</a>
      </div>
    </header>

    <div id="alert-card"
      data-user-id="{{ $user->id }}"
      data-frequency="{{ $reminderMinutes }}"
      class="alert-card home-motion mb-4 flex w-full items-center gap-3 rounded-[18px] border-[1.5px] border-alert-border bg-alert-bg p-3.5 text-left sm:p-4"
      role="alert">
      <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-alert-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <path d="M12 3.5L21.5 20H2.5L12 3.5Z" fill="#1A1A1A" />
          <rect x="11" y="9" width="2" height="6" rx="1" fill="#F5D76E" />
          <circle cx="12" cy="17.5" r="1.2" fill="#F5D76E" />
        </svg>
      </span>
      <span class="flex min-w-0 flex-col gap-0.5">
        <strong id="alert-title" class="text-[15px] font-bold tracking-tight">Te lang gezeten! ({{ $reminderMinutes }} min)</strong>
        <span class="text-[13px] leading-snug text-card-foreground">Tijd om op te staan en een snelle 2 min stretch te
          doen.</span>
      </span>
      <button type="button" id="btn-dismiss-alert"
        class="home-action cursor-pointer grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-teal transition hover:bg-teal-soft active:scale-95"
        aria-label="Melding sluiten voor {{ $reminderMinutes }} minuten" title="Sluiten voor {{ $reminderMinutes }} minuten">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>




    <section class="home-motion home-card-interactive mb-3.5 rounded-[22px] bg-surface p-5 shadow-card sm:p-7 sm:pb-5" aria-label="Dagelijkse voortgang">


      <div class="relative mx-auto mb-5 h-[190px] w-[190px] sm:mb-6 sm:h-[210px] sm:w-[210px]">


        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
          @if ($selectedChallenge === null)
            <p class="text-sm text-muted">
              {{ $noDifferentChallengeAvailable
                ? 'Er is geen andere opdracht beschikbaar.'
                : 'Er is nog geen opdracht beschikbaar.' }}
            </p>
          @else
            <img src="{{ $selectedChallenge->imageUrl() }}"
              alt="{{ $selectedChallenge->title }}"
              class="h-[210px] w-[210px] rounded-full object-cover" loading="lazy">
          @endif
        </div>
      </div>

      @if ($selectedChallenge !== null)
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">

          <div class="flex flex-col items-center gap-1 text-center">
            <span class="text-l font-extrabold tracking-tight sm:text-[22px]">
              {{ $selectedChallenge->title }}
            </span>
            <span class="text-[13px] font-bold text-muted">Moeilijkheid:
              {{ $selectedChallenge->difficulty }}</span>
          </div>

          <div class="h-9 w-px bg-[#e6e8ec]" aria-hidden="true"></div>

          <div class="flex flex-col items-center gap-1 text-center">
            <span
              class="text-l font-extrabold tracking-tight sm:text-[22px]">{{ (int) $selectedChallenge->kcal_burned }}
              kcal</span>
            <span class="text-[13px] font-bold text-muted">{{ (int) $selectedChallenge->xp_reward }} XP te
              verdienen</span>
          </div>
        </div>
      @endif

      <div class="my-4 h-px bg-[#e6e8ec]" aria-hidden="true"></div>

      @if ($selectedChallenge !== null)
        <form method="post" action="{{ route('challenges.complete') }}" class="space-y-3">
          @csrf
          {{-- Na voltooien blijft "Lekker bezig!" staan tot er een nieuwe opdracht is (staat in de database) --}}
          @if ($challengeCompleted)
            <button type="submit" disabled
              class="completion-button--success flex w-full cursor-default items-center justify-center gap-2 rounded-xl bg-teal px-4 py-3 text-sm font-extrabold text-white">
              <svg class="completion-button-check" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              Lekker bezig!
            </button>
          @else
            <button type="submit"
              class="home-action w-full rounded-xl bg-teal px-4 py-3 text-sm font-extrabold text-white transition hover:brightness-95 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
              Voltooien
            </button>
          @endif
          {{-- Uitleg over de opdracht van nu (beschrijvingspagina, met slide-overgang) --}}
          <a
            href="{{ route('challenges.show', $selectedChallenge) }}"
            class="home-action block w-full rounded-xl border border-teal px-4 py-3 text-center text-sm font-extrabold text-teal transition hover:bg-teal-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
            data-learn-transition>
            Wat is dit?
          </a>
          @if ($challengeCompletionNotice !== '')
            @if ($challengeCompleted)
              <p class="completion-feedback completion-feedback--success flex items-center gap-3 rounded-xl border-[1.5px] border-teal bg-teal-soft px-3 py-2.5 text-sm font-bold" role="status">
                <span class="completion-check grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal text-white" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <span class="min-w-0">{{ $challengeCompletionNotice }}</span>
              </p>
            @else
              <p class="rounded-xl bg-white/70 px-3 py-2 text-sm font-semibold text-ink" role="status">
                {{ $challengeCompletionNotice }}
              </p>
            @endif
          @endif
        </form>
      @endif

    </section>


    <section class="home-motion home-card-interactive mb-5 flex items-center gap-3 rounded-[18px] border-2 border-teal bg-surface p-3 sm:p-3.5">

      <div class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-teal-soft" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="13" r="8" stroke="#2EC4B6" stroke-width="2" />
          <path d="M12 9v4l2.5 1.5" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M9 3h6" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" />
        </svg>
      </div>

      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium text-muted">Volgende opdracht</p>
        <p class="text-base font-extrabold tracking-tight" id="next-break"
          @if ($selectedChallenge !== null) data-changes-at="{{ $challengeChangeAt * 1000 }}" @endif>
          {{ intdiv($challengeChangeAt - now()->getTimestamp(), 60) }}m {{ sprintf('%02d', ($challengeChangeAt - now()->getTimestamp()) % 60) }}s
        </p>
      </div>

    </section>


    <section class="home-motion">
      <h2 class="mb-3 text-lg font-extrabold tracking-tight">Klaar voor de volgende opdracht?</h2>
      <form method="post" action="{{ route('challenges.next') }}" class="rounded-[20px] bg-orange-soft p-4 sm:p-[18px]">
        @csrf
        <div class="flex items-center gap-3">
          <div class="min-w-0 flex-1">
            <p class="text-base font-extrabold text-orange">Direct de volgende opdracht starten?</p>
            <p class="home-change-hint text-[13px] leading-snug text-[#6a707a]">
              Je kunt vandaag nog {{ $challengeChangesRemaining }} keer direct wisselen.
            </p>
          </div>

          <button type="submit" id="btn-play"
            class="home-action cursor-pointer btn-play grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange shadow-[0_8px_18px_rgba(255,107,74,0.35)] transition hover:shadow-[0_10px_22px_rgba(255,107,74,0.45)] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Laad een andere opdracht" @disabled($challengeChangesRemaining === 0)>
            <svg class="ml-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M8 5.5v13l11-6.5L8 5.5z" fill="#fff" />
            </svg>
          </button>
        </div>
        @if ($challengeNotice !== '')
          <p class="mt-3 rounded-xl bg-white/70 px-3 py-2 text-sm font-semibold text-ink" role="status">
            {{ $challengeNotice }}
          </p>
        @endif
      </form>
    </section>

    {{-- Samen in beweging: activiteiten van alle gebruikers --}}
    <section class="home-motion mt-7" id="activity-feed" aria-labelledby="community-title">
      <p class="text-base font-extrabold text-orange">Samen in beweging</p>
      <h2 id="community-title" class="text-xl font-extrabold tracking-tight">Activiteiten van iedereen</h2>
      <p class="mb-4 text-[13px] leading-snug text-[#6a707a]">Bekijk wat de UpMove-community allemaal heeft gedaan.</p>

      @if ($communityActivities->isEmpty())
        <div class="account-surface rounded-[18px] bg-white p-5 text-center shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
          <p class="text-[13px] font-bold">Nog geen activiteiten</p>
          <p class="settings-description mt-1 text-[11px] text-[#656c77]">Deel als eerste wat je hebt gedaan via Activiteiten.</p>
        </div>
      @else
        <div class="space-y-4">
          @foreach ($communityActivities as $activity)
            <x-community-card :activity="$activity" :open-comments="$openComments" :comment-error="$commentError" />
          @endforeach
        </div>

        @if ($communityActivityCount > \App\Http\Controllers\CommunityController::PER_PAGE)
          <a
            href="{{ route('community.index') }}"
            class="settings-card group mt-4 flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
            <span class="min-w-0 flex-1">
              <span class="block text-[13px] font-bold">Alle activiteiten bekijken</span>
              <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">{{ $communityActivityCount }} activiteiten van de community</span>
            </span>
            <svg class="shrink-0 text-[#656c77] transition group-hover:translate-x-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </a>
        @endif
      @endif
    </section>

  </main>

  @include('leaderboard.screen')

  @include('challenges.screen')

</div>

<x-footer-nav />
@endsection
