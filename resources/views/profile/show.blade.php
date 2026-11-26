{{-- Profiel (vroeger pages/ranglijst/profile.php) --}}
@extends('layouts.app', ['bodyClass' => 'upmove-profile min-h-dvh bg-app-bg font-sans text-ink', 'scripts' => ['resources/js/community.js']])

@section('title', $profile->full_name.' | Profiel')

@section('body')
    <main class="mx-auto min-h-dvh w-full max-w-lg px-4 pb-10 pt-[max(1.5rem,env(safe-area-inset-top))] sm:px-6">
        <a
            href="{{ $returnToHome ? route('home', ['screen' => 'home']).'#community-title' : route('home', ['screen' => 'ranglijst']) }}"
            class="mb-6 inline-flex items-center gap-2 rounded-full bg-surface px-4 py-2.5 text-sm font-bold text-ink shadow-card transition hover:-translate-x-1 focus-visible:outline-2 focus-visible:outline-orange">
            <span aria-hidden="true">&larr;</span>
            {{ $returnToHome ? 'Terug naar activiteiten' : 'Terug naar ranglijst' }}
        </a>

        <header class="mb-5">
            <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
            <h1 class="text-2xl font-extrabold tracking-tight">Profiel</h1>
        </header>

        <section class="rounded-[22px] bg-surface p-6 text-center shadow-card" aria-label="Profiel van {{ $profile->full_name }}">
            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-orange-soft text-2xl font-extrabold text-orange" aria-hidden="true">
                {{ $profile->initials() }}
            </span>
            <h2 class="mt-4 text-xl font-extrabold">{{ $profile->full_name }}</h2>
            <p class="mt-1 text-sm font-medium text-muted">{{ '@'.$profile->username }}</p>
            @if (($profile->about_me ?? '') !== '')
                <div class="mt-4 border-t border-[#eceef2] pt-4 text-left">
                    <h3 class="text-sm font-bold">Over mij</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted">{{ $profile->about_me }}</p>
                </div>
            @endif
            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-app-bg p-4">
                    <p class="text-xs font-semibold text-muted">Totaal XP</p>
                    <p class="mt-1 text-lg font-extrabold text-orange">{{ (int) $profile->xp }}</p>
                </div>
                <div class="rounded-2xl bg-app-bg p-4">
                    <p class="text-xs font-semibold text-muted">Rang</p>
                    <p class="mt-1 text-lg font-extrabold text-orange">#{{ $profileRank }}</p>
                </div>
            </div>
        </section>

        <section class="mt-6" aria-labelledby="profile-activities-title">
            <header class="mb-3">
                <p class="mb-1 text-sm font-bold text-orange">Gedeeld door {{ $profile->full_name }}</p>
                <h2 id="profile-activities-title" class="scroll-mt-4 text-xl font-extrabold tracking-tight">Activiteiten</h2>
            </header>

            @if ($activities->isEmpty())
                <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
                    {{ $profile->full_name }} heeft nog geen activiteiten gedeeld.
                </p>
            @else
                <ul class="space-y-4">
                    @foreach ($activities as $activity)
                        <li>
                            <x-community-card :activity="$activity" :open-comments="$openComments" :comment-error="$commentError" :profile-from="$from" />
                        </li>
                    @endforeach
                </ul>

                @if ($activities->lastPage() > 1)
                    <nav class="mt-5 flex flex-wrap justify-center gap-2" aria-label="Pagina's met activiteiten van {{ $profile->full_name }}">
                        @for ($page = 1; $page <= $activities->lastPage(); $page++)
                            <a
                                href="{{ $activities->url($page) }}"
                                class="activity-feed-page-link grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange {{ $page === $activities->currentPage() ? 'bg-orange text-white' : 'bg-surface text-ink shadow-card hover:bg-orange-soft' }}"
                                @if ($page === $activities->currentPage()) aria-current="page" @endif>
                                {{ $page }}
                            </a>
                        @endfor
                    </nav>
                @endif
            @endif
        </section>
    </main>
@endsection
