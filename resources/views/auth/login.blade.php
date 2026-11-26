{{--
    Aanmelden en registreren (vroeger pages/login.php): één pagina met twee panelen,
    gewisseld door resources/js/login.js. Bewust altijd licht, ook als dark mode
    op het toestel bewaard staat: de gebruiker is hier nog niet ingelogd.
--}}
@extends('layouts.app', ['scripts' => ['resources/js/login.js']])

@section('title', ($activeAuthMode === 'register' ? 'Registreren' : 'Aanmelden').' | UpMove')

@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maart', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Augustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December',
    ];
    $currentYear = (int) now()->format('Y');

    // Gedeelde opmaak, zodat alle velden er hetzelfde uitzien.
    $labelClass = 'mb-1.5 block text-[12px] font-semibold text-[#3d4a47]';
    $inputClass = 'auth-input h-12 w-full rounded-[14px] border border-[#dfe6e3] bg-white px-4 text-[14px] font-medium text-[#1f2a28] outline-none transition placeholder:font-normal placeholder:text-[#8a9592] hover:border-[#c9d4d0] focus:border-[#17685b] focus-visible:ring-4 focus-visible:ring-[#17685b]/12';
    $selectClass = 'auth-input h-12 w-full min-w-0 appearance-none rounded-[14px] border border-[#dfe6e3] bg-white bg-[length:16px] bg-[right_0.75rem_center] bg-no-repeat pl-3.5 pr-8 text-[14px] font-medium text-[#1f2a28] outline-none transition hover:border-[#c9d4d0] focus:border-[#17685b] focus-visible:ring-4 focus-visible:ring-[#17685b]/12';
    $buttonClass = 'flex h-12 w-full cursor-pointer items-center justify-center rounded-[14px] bg-primary text-[14px] font-bold text-white shadow-[0_8px_20px_rgba(255,107,74,0.28)] transition hover:brightness-95 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
    $switchClass = 'cursor-pointer rounded font-bold text-[#17685b] underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#17685b]';
@endphp

@section('body')
<main class="auth-page relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-[#f4f7f6] px-4 pb-[max(2rem,env(safe-area-inset-bottom))] pt-[max(2rem,env(safe-area-inset-top))] text-[#1f2a28] sm:py-12">
    {{-- Zachte kleurvlakken op de achtergrond --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <span class="absolute -right-32 -top-36 h-[26rem] w-[26rem] rounded-full bg-[#dcefe9] opacity-70 blur-3xl"></span>
        <span class="absolute -bottom-40 -left-36 h-[25rem] w-[25rem] rounded-full bg-[#f9e9df] opacity-60 blur-3xl"></span>
    </div>

    <div class="relative z-10 w-full max-w-[400px]">
        {{-- Logo --}}
        <div class="mb-6 flex items-center justify-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#17685b] shadow-[0_8px_20px_rgba(23,104,91,0.22)]" aria-hidden="true">
                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
            </span>
            <span class="text-2xl font-extrabold tracking-tight">UpMove</span>
        </div>

        {{-- ========================= AANMELDEN ========================= --}}
        <section id="login" @class(['auth-panel rounded-[24px] border border-[#e6ece9] bg-white/95 px-6 pb-7 pt-8 shadow-[0_24px_70px_rgba(16,54,46,0.12)] backdrop-blur sm:px-8', 'hidden' => $activeAuthMode === 'register']) aria-labelledby="loginTitle">
            <header class="mb-6">
                <h1 id="loginTitle" class="text-[22px] font-extrabold tracking-tight">Welkom terug</h1>
                <p class="mt-1 text-[13px] text-[#66736f]">Meld je aan om verder te gaan met bewegen.</p>
            </header>

            <form id="loginForm" method="post" action="{{ route('login.store') }}" class="flex flex-col gap-4" novalidate>
                @csrf

                @if ($authMessage !== '' && $activeAuthMode === 'login')
                    @include('auth.partials.message', [
                        'id' => 'loginStatus',
                        'type' => $authMessageType,
                        'title' => $authMessageType === 'success' ? 'Gelukt' : 'Aanmelden niet gelukt',
                        'message' => $authMessage,
                    ])
                @endif

                <div>
                    <label class="{{ $labelClass }}" for="loginEmail">E-mailadres of gebruikersnaam</label>
                    <input id="loginEmail" required name="email" value="{{ $activeAuthMode === 'login' ? old('email') : '' }}" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="naam@voorbeeld.nl" class="{{ $inputClass }}" @if ($activeAuthMode === 'login') autofocus @endif>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="loginPassword">Wachtwoord</label>
                    @include('auth.partials.password', ['id' => 'loginPassword', 'name' => 'password', 'autocomplete' => 'current-password', 'inputClass' => $inputClass])
                </div>

                <button type="submit" class="{{ $buttonClass }} mt-2">Aanmelden</button>
            </form>

            <p class="mt-6 border-t border-[#edf1ef] pt-5 text-center text-[13px] text-[#66736f]">
                Nog geen account?
                <button type="button" data-mode="register" class="{{ $switchClass }}">Account aanmaken</button>
            </p>
        </section>

        {{-- ========================= REGISTREREN ========================= --}}
        <section id="register" @class(['auth-panel rounded-[24px] border border-[#e6ece9] bg-white/95 px-6 pb-7 pt-8 shadow-[0_24px_70px_rgba(16,54,46,0.12)] backdrop-blur sm:px-8', 'hidden' => $activeAuthMode !== 'register']) aria-labelledby="registerTitle">
            <header class="mb-6">
                <h1 id="registerTitle" class="text-[22px] font-extrabold tracking-tight">Registreren</h1>
                <p class="mt-1 text-[13px] text-[#66736f]">Maak een account aan en verdien XP met korte beweegmomenten.</p>
            </header>

            <form id="registerForm" method="post" action="{{ route('register') }}" class="flex flex-col gap-4" novalidate>
                @csrf

                @if ($authMessage !== '' && $activeAuthMode === 'register')
                    @include('auth.partials.message', [
                        'id' => 'registerStatus',
                        'type' => $authMessageType,
                        'title' => $authMessageType === 'success' ? 'Gelukt' : 'Registreren niet gelukt',
                        'message' => $authMessage,
                    ])
                @endif

                <div>
                    <label class="{{ $labelClass }}" for="fullName">Volledige naam</label>
                    <input id="fullName" required name="fullName" value="{{ old('fullName') }}" maxlength="150" autocomplete="name" placeholder="Sanne de Vries" class="{{ $inputClass }}" @if ($activeAuthMode === 'register') autofocus @endif>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="username">Gebruikersnaam</label>
                    <input id="username" required name="username" value="{{ old('username') }}" minlength="3" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="sanne_01" aria-describedby="usernameHint" class="{{ $inputClass }}">
                    <p id="usernameHint" class="mt-1.5 text-[11px] text-[#5f6b68]">3 tot 30 tekens: letters, cijfers, punt, streepje of underscore.</p>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="registerEmail">E-mailadres</label>
                    <input id="registerEmail" required type="email" name="email" value="{{ $activeAuthMode === 'register' ? old('email') : '' }}" maxlength="254" autocomplete="email" autocapitalize="none" spellcheck="false" placeholder="naam@voorbeeld.nl" aria-describedby="emailHint" class="{{ $inputClass }}">
                    <p id="emailHint" class="mt-1.5 text-[11px] text-[#5f6b68]">Je ontvangt mogelijk meldingen van ons.</p>
                </div>

                <fieldset>
                    <legend class="{{ $labelClass }}">Geboortedatum</legend>
                    <div class="grid grid-cols-[1fr_1.6fr_1.2fr] gap-2">
                        <div>
                            <label class="sr-only" for="birthDay">Dag</label>
                            <select id="birthDay" required name="day" class="{{ $selectClass }} auth-select">
                                <option value="" disabled @selected(old('day') === null)>Dag</option>
                                @for ($day = 1; $day <= 31; $day++)
                                    <option value="{{ $day }}" @selected((int) old('day') === $day)>{{ $day }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="birthMonth">Maand</label>
                            <select id="birthMonth" required name="month" class="{{ $selectClass }} auth-select">
                                <option value="" disabled @selected(old('month') === null)>Maand</option>
                                @foreach ($months as $monthNumber => $monthName)
                                    <option value="{{ $monthNumber }}" @selected((int) old('month') === $monthNumber)>{{ $monthName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="birthYear">Jaar</label>
                            <select id="birthYear" required name="year" class="{{ $selectClass }} auth-select">
                                <option value="" disabled @selected(old('year') === null)>Jaar</option>
                                @for ($year = $currentYear; $year >= $currentYear - 110; $year--)
                                    <option value="{{ $year }}" @selected((int) old('year') === $year)>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label class="{{ $labelClass }}" for="registerPassword">Wachtwoord</label>
                    @include('auth.partials.password', ['id' => 'registerPassword', 'name' => 'password', 'autocomplete' => 'new-password', 'inputClass' => $inputClass, 'describedBy' => 'passwordHint'])
                    <p id="passwordHint" class="mt-1.5 text-[11px] text-[#5f6b68]">Minimaal 8 tekens.</p>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="confirmPassword">Wachtwoord herhalen</label>
                    @include('auth.partials.password', ['id' => 'confirmPassword', 'name' => 'confirmPassword', 'autocomplete' => 'new-password', 'inputClass' => $inputClass])
                </div>

                <button type="submit" class="{{ $buttonClass }} mt-2">Account aanmaken</button>
            </form>

            <p class="mt-6 border-t border-[#edf1ef] pt-5 text-center text-[13px] text-[#66736f]">
                Heb je al een account?
                <button type="button" data-mode="login" class="{{ $switchClass }}">Aanmelden</button>
            </p>
        </section>
    </div>
</main>
@endsection
