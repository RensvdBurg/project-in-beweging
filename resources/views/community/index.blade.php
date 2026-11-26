@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/community.js', 'resources/js/footer-nav.js']])

@section('title', 'Samen in beweging | UpMove')

@section('body')
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-activities flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <header class="relative mb-6 flex min-h-11 items-center justify-center">
                <a
                    href="{{ route('home') }}"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar home">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h1 class="text-[17px] font-extrabold tracking-tight">Samen in beweging</h1>
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
                    <x-community-card :activity="$activity" :open-comments="$openComments" :comment-error="$commentError" />
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
