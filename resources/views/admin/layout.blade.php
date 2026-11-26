@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/admin/admin.js']])

@php
    $adminNav = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'Gebruikers'],
        ['route' => 'admin.challenges.index', 'active' => 'admin.challenges.*', 'label' => 'Challenges'],
        ['route' => 'admin.activities.index', 'active' => 'admin.activities.*', 'label' => 'Activiteiten'],
        ['route' => 'admin.logs.index', 'active' => 'admin.logs.*', 'label' => 'Logs'],
    ];
    $adminStatus = (string) session('admin_status', '');
    $adminStatusType = session('admin_status_type', 'success');
@endphp

@section('body')
    <div class="mx-auto flex min-h-dvh w-full max-w-5xl flex-col">
        <main class="upmove-admin flex-1 px-4 pb-12 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <!-- Kop: terug naar de app, titel en de beheerder die is ingelogd. -->
            <header class="relative mb-5 flex min-h-11 items-center justify-center">
                <a
                    href="{{ route('home') }}"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar de app">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <div class="text-center">
                    <h1 class="text-[17px] font-extrabold tracking-tight">Admin dashboard</h1>
                    <p class="settings-description text-[11px] font-semibold text-[#656c77]">Ingelogd als {{ auth()->user()->username }}</p>
                </div>
            </header>

            <!-- Navigatie tussen de beheeronderdelen; scrolt horizontaal op een smal scherm. -->
            <nav class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Admin-navigatie">
                <ul class="flex w-max gap-2 sm:w-auto">
                    @foreach ($adminNav as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($isActive) aria-current="page" @endif
                                @class([
                                    'block whitespace-nowrap rounded-xl px-4 py-2.5 text-[12px] font-bold transition active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange',
                                    'bg-orange text-white' => $isActive,
                                    'settings-card bg-white text-[#656c77] shadow-sm hover:bg-[#fafbfc]' => ! $isActive,
                                ])>{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @if ($adminStatus !== '')
                <p
                    class="mb-4 rounded-xl px-4 py-3 text-[12px] font-medium {{ $adminStatusType === 'error' ? 'bg-red-50 text-red-600' : 'bg-teal-soft text-[#18796f]' }}"
                    role="{{ $adminStatusType === 'error' ? 'alert' : 'status' }}"
                    aria-live="polite">{{ $adminStatus }}</p>
            @endif

            @yield('content')
        </main>

        <!-- Gedeelde bevestiging voor verwijderknoppen (formulieren met data-confirm, zie resources/js/admin/admin.js). -->
        <dialog id="admin-confirm-dialog" class="account-dialog w-[calc(100%-2rem)] max-w-sm rounded-[20px] bg-white p-0 text-[#20242a] shadow-2xl backdrop:bg-black/45">
            <div class="p-5">
                <h2 class="text-[16px] font-extrabold">Weet je het zeker?</h2>
                <p id="admin-confirm-message" class="settings-description mt-2 text-[12px] leading-relaxed text-[#656c77]"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="admin-confirm-cancel" class="account-dialog-cancel rounded-xl px-3 py-2 text-[12px] font-semibold text-[#66707d] transition hover:bg-[#f4f6f8] focus-visible:outline-2 focus-visible:outline-orange">Annuleren</button>
                    <button type="button" id="admin-confirm-ok" class="rounded-xl bg-red-600 px-3 py-2 text-[12px] font-bold text-white transition hover:bg-red-700 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Verwijderen</button>
                </div>
            </div>
        </dialog>
    </div>
@endsection
