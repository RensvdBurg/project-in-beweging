{{-- Challengebeschrijving (vroeger pages/learn/learn-beschrijving.php) --}}
@extends('layouts.app', ['scripts' => ['resources/css/learn-page-transition.css', 'resources/js/learn.js', 'resources/js/footer-nav.js']])

@section('title', $challenge->title.' | UpMove')

@section('body')
    <main class="mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6">
        <a href="{{ route('home', ['screen' => 'leren', 'transition' => 'back']) }}#challenge-list"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange"
            aria-label="Terug naar alle challenges"
            data-learn-transition>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Alle challenges
        </a>

        <article class="overflow-hidden rounded-[22px] bg-surface shadow-card">
            @if (! empty($challenge->img))
                <img src="{{ $challenge->imageUrl(1200) }}"
                    alt="{{ $challenge->title }}"
                    class="h-56 w-full object-cover"
                    width="672"
                    height="224"
                    fetchpriority="high"
                    decoding="async">
            @endif
            <div class="p-5 sm:p-7">
                <p class="mb-2 text-sm font-bold text-orange">Challenge</p>
                <h1 class="mb-3 text-2xl font-extrabold tracking-tight">
                    {{ $challenge->title }}
                </h1>

                <div class="mb-5 flex flex-wrap gap-2 text-sm font-semibold text-muted">
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        {{ $challenge->difficulty }}
                    </span>
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        {{ (int) $challenge->kcal_burned }} kcal
                    </span>
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        {{ (int) $challenge->xp_reward }} XP
                    </span>
                </div>

                <h2 class="mb-2 text-lg font-extrabold">Over deze challenge</h2>
                {{-- Oudere challenges hebben geen uitgebreide uitleg: dan de korte beschrijving --}}
                <p class="whitespace-pre-line text-sm leading-relaxed text-muted">{{ $challenge->description_extra ?: $challenge->description }}</p>
            </div>
        </article>
    </main>

    <x-footer-nav />
@endsection
