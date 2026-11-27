@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/settings-page.js', 'resources/js/footer-nav.js']])

@section('title', 'Instellingen | UpMove')

@section('body')
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">


        <!-- ========================================= -->
        <!-- SETTINGS -->
        <!-- ========================================= -->

        <main id="screen-settings"
            class="upmove-settings flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
            data-user-id="{{ $user->id }}"
            @if ($settings !== null) data-settings='@json($settings)' @endif
            data-update-url="{{ route('settings.update') }}"
            data-csrf-token="{{ csrf_token() }}">


            <!-- ===================================== -->
            <!-- HEADER -->
            <!-- ===================================== -->

            <header class="relative mb-6 flex min-h-11 items-center justify-center">

                <h1 class="text-[17px] font-extrabold tracking-tight">
                    Instellingen
                </h1>

            </header>


            <section class="mb-6">
                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
                    Account
                </h2>

                <a href="{{ route('account') }}"
                    class="settings-card group flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-orange"
                        aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M5 20c.5-3.1 3.2-5 7-5s6.5 1.9 7 5" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-bold">Accountinstellingen</span>
                        <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">Beheer je profiel en
                            account</span>
                    </span>

                    <svg class="shrink-0 text-[#656c77] transition group-hover:translate-x-0.5" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </a>
            </section>


            @if ($user->is_admin)
                <!-- Alleen voor beheerders: naar het admin-dashboard. -->
                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
                        Beheer
                    </h2>

                    <a href="{{ route('admin.dashboard') }}"
                        class="settings-card group flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-orange"
                            aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                                <rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                                <rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                                <rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-[13px] font-bold">Admin dashboard</span>
                            <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">Gebruikers, challenges, activiteiten en logs</span>
                        </span>

                        <svg class="shrink-0 text-[#656c77] transition group-hover:translate-x-0.5" width="18" height="18"
                            viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </a>
                </section>
            @endif


            <!-- ===================================== -->
            <!-- BEWEGINGSDOELEN -->
            <!-- ===================================== -->

            <section class="mb-6">


                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
                    Mijn bewegingsdoelen
                </h2>


                <div class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- HERINNERINGSFREQUENTIE -->

                    <div class="p-4">


                        <div class="mb-3 flex items-center justify-between gap-3">

                            <div>

                                <label for="setting-frequency" class="text-[13px] font-bold">
                                    Herinneringsfrequentie
                                </label>

                                <p class="settings-description mt-0.5 text-[11px] text-[#656c77]">
                                    Hoe vaak wil je een beweegpauze?
                                </p>

                            </div>


                            <span id="settings-frequency-label" class="shrink-0 text-[12px] font-bold text-orange">
                                Elke 45 min
                            </span>

                        </div>


                        <input id="setting-frequency" type="range" min="15" max="120" step="15" value="45"
                            class="upmove-range block" aria-label="Herinneringsfrequentie">

                    </div>



                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>

                </div>

            </section>



            <!-- ===================================== -->
            <!-- WERK / SCHOOL -->
            <!-- ===================================== -->

            <section class="mb-6">


                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
                    Werk / school
                </h2>


                <div class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- ALLEEN TIJDENS WERK/SCHOOL -->

                    <label class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p class="text-[13px] font-bold">
                                Alleen tijdens werk/school
                            </p>

                            <p class="settings-description mt-0.5 text-[11px] leading-snug text-[#656c77]">
                                Meldingen op je telefoon (beweegherinneringen en nieuwe opdrachten), alleen binnen jouw ingestelde dagen en tijden
                            </p>

                        </div>


                        <!-- SWITCH -->

                        <div class="relative shrink-0">

                            <input id="setting-work-only" type="checkbox" class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]">
                            </div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>

                        </div>

                    </label>



                    <!-- WERKTIJD OPTIES -->

                    <div id="work-hours-options"
                        class="settings-work-options border-t border-[#eceef2] bg-[#fafbfc] p-4 transition">


                        <!-- DAGEN EN TIJDEN (per dag een eigen start- en eindtijd) -->

                        @php
                            // Volgorde maandag t/m zondag; waarde = JavaScript getDay() (0 = zondag).
                            $workWeekdays = [1 => 'Ma', 2 => 'Di', 3 => 'Wo', 4 => 'Do', 5 => 'Vr', 6 => 'Za', 0 => 'Zo'];
                            $workDayNames = [1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag', 6 => 'zaterdag', 0 => 'zondag'];
                        @endphp

                        <div>

                            <p class="settings-label mb-2 text-[11px] font-semibold text-[#5f6672]">
                                Werk- / schooldagen en tijden
                            </p>

                            <div class="mb-1 grid grid-cols-[2.5rem_minmax(0,1fr)_minmax(0,1fr)] items-center gap-2 px-1 text-[10px] font-semibold text-[#656c77]" aria-hidden="true">
                                <span>Dag</span>
                                <span>Starttijd</span>
                                <span>Eindtijd</span>
                            </div>

                            <div class="space-y-2">
                                @foreach ($workWeekdays as $dayNumber => $dayLabel)
                                    <div class="work-day-row grid grid-cols-[2.5rem_minmax(0,1fr)_minmax(0,1fr)] items-center gap-2 transition" data-day="{{ $dayNumber }}">
                                        <label class="cursor-pointer">
                                            <input type="checkbox" class="work-day peer sr-only" value="{{ $dayNumber }}" aria-label="{{ ucfirst($workDayNames[$dayNumber]) }}" @checked($dayNumber >= 1 && $dayNumber <= 5)>
                                            <span
                                                class="settings-day-label grid h-10 place-items-center rounded-lg bg-[#f0f2f5] text-[11px] font-bold text-[#656c77] transition peer-checked:bg-orange peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-orange">
                                                {{ $dayLabel }}
                                            </span>
                                        </label>
                                        <div>
                                            <label class="sr-only" for="setting-work-start-{{ $dayNumber }}">Starttijd {{ $workDayNames[$dayNumber] }}</label>
                                            <input id="setting-work-start-{{ $dayNumber }}" type="time" value="08:30" data-day="{{ $dayNumber }}"
                                                class="work-time work-time-start settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-2 py-2.5 text-[12px] font-semibold outline-none transition focus:border-orange disabled:cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="sr-only" for="setting-work-end-{{ $dayNumber }}">Eindtijd {{ $workDayNames[$dayNumber] }}</label>
                                            <input id="setting-work-end-{{ $dayNumber }}" type="time" value="17:00" data-day="{{ $dayNumber }}"
                                                class="work-time work-time-end settings-input w-full rounded-xl border border-[#e5e8ec] bg-white px-2 py-2.5 text-[12px] font-semibold outline-none transition focus:border-orange disabled:cursor-not-allowed">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="settings-description mt-2 px-1 text-[10px] leading-snug text-[#656c77]">
                                Eindtijd vóór de starttijd (bijv. 22:00 - 06:00)? Dan loopt het door tot de volgende ochtend.
                            </p>

                        </div>



                        <!-- STATUS -->

                        <div id="work-hours-summary"
                            class="mt-4 rounded-xl bg-[#eef8f7] px-3 py-2.5 text-[11px] font-semibold leading-relaxed text-[#1f7a72]">
                            Ma t/m Vr · 08:30 - 17:00
                        </div>

                        {{-- Status van de meldingen op deze telefoon --}}
                        <p id="work-notification-status" class="settings-description mt-2 px-1 text-[11px] leading-relaxed text-[#656c77]" role="status">
                            @if (! $phoneNotifications)
                                Meldingen krijg je alleen in de UpMove-app op je telefoon.
                            @elseif ($notificationPermission === 'denied')
                                Meldingen staan uit voor UpMove. Zet ze aan in de instellingen van je telefoon.
                            @else
                                Staat dit aan, dan krijg je binnen deze tijden elke <span data-work-frequency>{{ $settings['frequency'] ?? 45 }}</span> minuten een beweegherinnering en een melding bij elke nieuwe opdracht, ook als de app dicht is.
                            @endif
                        </p>


                    </div>

                </div>

            </section>



            <!-- ===================================== -->
            <!-- VOORKEUREN -->
            <!-- ===================================== -->

            <section class="mb-5">


                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
                    Voorkeuren
                </h2>


                <div class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">


                    <!-- DARK MODE -->

                    <label class="flex cursor-pointer items-center gap-3 p-4">


                        <div class="min-w-0 flex-1">

                            <p class="text-[13px] font-bold">
                                Donkere modus
                            </p>

                            <p class="settings-description mt-0.5 text-[11px] leading-snug text-[#656c77]">
                                Gebruik het donkere thema
                                in heel UpMove
                            </p>

                        </div>


                        <div class="relative shrink-0">

                            <input id="setting-dark" type="checkbox" class="peer sr-only">

                            <div
                                class="h-[28px] w-[48px] rounded-full bg-[#e6ebee] transition-colors peer-checked:bg-[#2ec4b6]">
                            </div>

                            <div
                                class="absolute left-[3px] top-[3px] h-[22px] w-[22px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5">
                            </div>

                        </div>

                    </label>


                </div>

            </section>
            <!-- ===================================== -->
            <!-- LOCALSTORAGE FOUT -->
            <!-- ===================================== -->

            <p id="settings-storage-error"
                class="mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-[12px] font-medium text-red-600"></p>


        </main>



        <!-- ========================================= -->
        <!-- FOOTER -->
        <!-- ========================================= -->

        <x-footer-nav />


    </div>
@endsection