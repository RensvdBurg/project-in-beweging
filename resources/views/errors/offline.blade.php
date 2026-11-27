@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased'])

@section('title', 'Geen verbinding | UpMove')

@section('body')
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col justify-center px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
        <!-- De app haalt alles bij de server; zonder verbinding tonen we dit scherm. -->
        <section class="settings-card rounded-[18px] bg-white p-6 text-center shadow-[0_4px_14px_rgba(20,28,38,0.05)]" role="alert">
            <span class="account-avatar mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-orange-soft text-orange" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                    <path d="M2 8.5a15 15 0 0 1 20 0M5.5 12a10 10 0 0 1 13 0M9 15.5a5 5 0 0 1 6 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                    <circle cx="12" cy="19" r="1.2" fill="currentColor" />
                    <path d="m3 3 18 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
            </span>
            <h1 class="text-[17px] font-extrabold tracking-tight">Geen verbinding</h1>
            <p class="settings-description mt-2 text-[13px] leading-relaxed text-[#656c77]">{{ $message }}</p>
            <a href="{{ $retryUrl }}" class="mt-5 inline-block rounded-xl bg-orange px-5 py-2.5 text-[13px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Opnieuw proberen</a>
        </section>
    </div>
@endsection
