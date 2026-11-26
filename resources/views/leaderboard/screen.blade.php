{{-- Ranglijst (vroeger pages/ranglijst/ranglijst.php). Alleen zichtbaar wanneer de gebruiker de ranglijst in de hoofdnavigatie kiest. --}}
@php
  // Plaats de tweede gebruiker links, de eerste in het midden en de derde rechts.
  $podiumLayout = match ($rankings->count()) {
    1 => 'leaderboard-podium--single',
    2 => 'leaderboard-podium--two',
    default => '',
  };
@endphp
<main
  @class([
    'screen mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6',
    'hidden' => $screen !== 'ranglijst',
  ])
  id="screen-ranglijst">
  <header class="mb-5 pt-2">
    <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
    <h1 class="text-2xl font-extrabold tracking-tight">Ranglijst</h1>
    <p class="mt-1 text-sm text-muted">Bekijk wie de meeste XP heeft verdiend.</p>
  </header>

  @if ($rankings->isEmpty())
    <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
      Er staan nog geen deelnemers op de ranglijst.
    </p>
  @else
    <section class="leaderboard-podium {{ $podiumLayout }}" aria-label="Top drie">
      @foreach ([1 => 2, 0 => 1, 2 => 3] as $podiumIndex => $rank)
        @isset($rankings[$podiumIndex])
          @php
            $podiumUser = $rankings[$podiumIndex];
          @endphp
          <a
            href="{{ route('profile.show', $podiumUser) }}"
            class="leaderboard-podium-card leaderboard-podium-card--{{ $rank }}"
            data-reveal-rank="{{ $rank }}"
            aria-label="Bekijk het profiel van {{ $podiumUser->full_name }}">
            <span class="leaderboard-podium-rank" aria-label="Positie {{ $rank }}">#{{ $rank }}</span>
            <span class="leaderboard-podium-avatar" aria-hidden="true">
              {{ $podiumUser->initials() }}
            </span>
            <span class="leaderboard-podium-name">
              {{ $podiumUser->full_name }}
            </span>
            <span class="leaderboard-podium-username">
              {{ '@'.$podiumUser->username }}
            </span>
            <span class="leaderboard-xp">
              {{ (int) $podiumUser->xp }} XP
            </span>
          </a>
        @endisset
      @endforeach
    </section>

    {{-- Het podium bevat posities 1-3; deze genummerde lijst toont de resterende posities. --}}
    <section class="rounded-[22px] bg-surface p-4 shadow-card sm:p-5" aria-labelledby="rankingTitle">
      <h2 id="rankingTitle" class="mb-4 text-lg font-extrabold tracking-tight">Top 10</h2>
      @if ($rankings->count() > 3)
        <ol class="space-y-2">
          @foreach ($rankings->slice(3, 7)->values() as $index => $rankedUser)
            @php
              $rank = $index + 4;
              $isCurrentUser = $rankedUser->id === $user->id;
            @endphp
            <li data-reveal-rank="{{ $rank }}">
              <a
                href="{{ route('profile.show', $rankedUser) }}"
                class="leaderboard-user-link flex items-center gap-3 rounded-2xl px-3 py-3 {{ $isCurrentUser ? 'bg-orange-soft' : 'bg-app-bg/60' }}"
                aria-label="Bekijk het profiel van {{ $rankedUser->full_name }}">
                <span class="leaderboard-rank" role="img" aria-label="Positie {{ $rank }}">
                  {{ $rank }}
                </span>
                <span class="leaderboard-avatar" aria-hidden="true">
                  {{ $rankedUser->initials() }}
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-bold">
                    {{ $rankedUser->full_name }}
                    @if ($isCurrentUser)
                      <span class="text-orange">(jij)</span>
                    @endif
                  </span>
                  <span class="block truncate text-xs text-muted">
                    {{ '@'.$rankedUser->username }}
                  </span>
                </span>
                <span class="leaderboard-xp shrink-0">
                  {{ (int) $rankedUser->xp }} XP
                </span>
              </a>
            </li>
          @endforeach
        </ol>
      @else
        <p class="text-sm text-muted">De overige plekken vullen zich zodra er meer deelnemers zijn.</p>
      @endif
    </section>

    {{-- Toon de eigen positie apart als de ingelogde gebruiker buiten de top 10 valt. --}}
    @if ($currentUserRank > $rankings->count())
      <section class="leaderboard-your-rank" aria-label="Jouw positie" data-reveal-rank="0">
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold uppercase tracking-wide text-orange">Jouw positie</p>
          <p class="truncate text-base font-extrabold">
            {{ $user->full_name }}
            <span class="text-sm font-semibold text-muted">
              {{ '@'.$user->username }}
            </span>
          </p>
          <p class="mt-0.5 text-xs text-muted">Je staat buiten de top 10</p>
        </div>
        <span class="leaderboard-your-rank-number">#{{ $currentUserRank }}</span>
        <span class="leaderboard-xp">{{ (int) $user->xp }} XP</span>
      </section>
    @endif
  @endif
</main>
