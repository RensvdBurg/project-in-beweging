@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/footer-nav.js']])

@section('title', 'Alle activiteiten | UpMove')

@section('body')
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-activities flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <header class="relative mb-6 flex min-h-11 items-center justify-center">
                <a
                    href="{{ route('activities.index') }}"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar activiteiten">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h1 class="text-[17px] font-extrabold tracking-tight">Alle activiteiten</h1>
            </header>

            <p class="settings-description mb-3 px-0.5 text-[11px] font-semibold text-[#656c77]">
                @if ($activities->firstItem() === $activities->lastItem())
                    {{ $activities->firstItem() }} van {{ $activities->total() }} activiteiten
                @else
                    {{ $activities->firstItem() }}–{{ $activities->lastItem() }} van {{ $activities->total() }} activiteiten
                @endif
            </p>

            <div class="space-y-4">
                @foreach ($activities as $activity)
                    <article class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        @if ($activity->imageUrl() !== null)
                            <img
                                src="{{ $activity->imageUrl() }}"
                                alt="{{ $activity->title }}"
                                class="h-48 w-full object-cover"
                                loading="lazy">
                        @else
                            <div class="account-avatar grid h-28 w-full place-items-center bg-orange-soft text-orange" aria-hidden="true">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                </svg>
                            </div>
                        @endif
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="min-w-0 text-[15px] font-extrabold tracking-tight">{{ $activity->title }}</h2>
                                <time datetime="{{ $activity->created_at->toIso8601String() }}" class="settings-description shrink-0 pt-0.5 text-[11px] font-semibold text-[#656c77]">
                                    {{ $activity->created_at->locale('nl')->translatedFormat('j F Y, H:i') }}
                                </time>
                            </div>
                            <p class="settings-description mt-2 whitespace-pre-line text-[13px] leading-relaxed text-[#5c616a]">{{ $activity->description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($activities->hasPages())
                <nav class="mt-5 flex items-center justify-between gap-3" aria-label="Paginering">
                    @if ($activities->onFirstPage())
                        <span class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold opacity-40" aria-disabled="true">Vorige</span>
                    @else
                        <a href="{{ $activities->previousPageUrl() }}" rel="prev" class="settings-card rounded-xl bg-white px-4 py-2.5 text-[12px] font-bold shadow-sm transition hover:bg-[#fafbfc] active:scale-95">Vorige</a>
                    @endif

                    <span class="settings-description text-[12px] font-semibold text-[#656c77]">
                        Pagina {{ $activities->currentPage() }} van {{ $activities->lastPage() }}
                    </span>

                    @if ($activities->hasMorePages())
                        <a href="{{ $activities->nextPageUrl() }}" rel="next" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95">Volgende</a>
                    @else
                        <span class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold opacity-40" aria-disabled="true">Volgende</span>
                    @endif
                </nav>
            @endif
        </main>

        <x-footer-nav />
    </div>
@endsection
