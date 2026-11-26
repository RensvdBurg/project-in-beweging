@extends('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/activities.js', 'resources/js/footer-nav.js']])

@section('title', 'Activiteiten | UpMove')

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
                <h1 class="text-[17px] font-extrabold tracking-tight">Activiteiten</h1>
            </header>

            {{-- Introductie boven het formulier --}}
            <div class="mb-5 px-0.5">
                <h2 class="text-[20px] font-extrabold tracking-tight">Activiteiten</h2>
                <p class="settings-description mt-1 text-[13px] leading-relaxed text-[#656c77]">Deel wat je hebt gedaan, van een hardlooprondje tot een nieuwe sportieve mijlpaal.</p>
            </div>

            @if ($statusMessage !== '')
                <p
                    class="mb-4 rounded-xl px-4 py-3 text-[12px] font-medium {{ $statusType === 'error' ? 'bg-red-50 text-red-600' : 'bg-teal-soft text-[#18796f]' }}"
                    role="{{ $statusType === 'error' ? 'alert' : 'status' }}"
                    aria-live="polite">{{ $statusMessage }}</p>
            @endif

            {{-- Formulier: nieuwe activiteit toevoegen --}}
            <section class="mb-6">
                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Activiteit toevoegen</h2>
                <form method="post" action="{{ route('activities.store') }}" enctype="multipart/form-data" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                    @csrf
                    <label class="block p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Titel</span>
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            maxlength="150"
                            required
                            placeholder="Bijvoorbeeld: Rondje fietsen"
                            class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition placeholder:font-medium placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                    </label>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <label class="block p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Beschrijving</span>
                        <textarea
                            name="description"
                            rows="4"
                            maxlength="2000"
                            required
                            placeholder="Wat heb je gedaan?"
                            class="account-input block w-full resize-y rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-medium leading-relaxed outline-none transition placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">{{ old('description') }}</textarea>
                    </label>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <div class="p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Afbeelding <span class="font-medium">(optioneel)</span></span>

                        @if ($isNative)
                            {{-- In de app: native galerijkiezer (NativePHP camera-plugin). --}}
                            <input type="hidden" name="native_image_path" id="activity-native-image" value="">
                            <button
                                type="button"
                                id="activity-pick-image"
                                class="settings-button flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-[#d5d9df] bg-[#f4f6f8] px-3 py-3 text-[12px] font-bold text-[#5c616a] transition active:scale-[0.99]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7" />
                                    <circle cx="9" cy="10" r="1.6" stroke="currentColor" stroke-width="1.5" />
                                    <path d="m4 17 5-4.5 4 3.5 3-2.5 4 3.5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                </svg>
                                <span id="activity-pick-label">Kies een afbeelding</span>
                            </button>
                        @else
                            <input
                                type="file"
                                name="image"
                                id="activity-image"
                                accept="image/jpeg,image/png,image/webp,image/gif,image/heic"
                                class="account-input block w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2 text-[12px] font-medium text-[#5c616a] file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-orange-soft file:px-3 file:py-1.5 file:text-[12px] file:font-bold file:text-orange">
                        @endif

                        <img id="activity-image-preview" alt="" class="mt-3 hidden h-40 w-full rounded-xl object-cover">
                    </div>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <div class="flex justify-end p-4">
                        <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Activiteit opslaan</button>
                    </div>
                </form>
            </section>

            {{-- Overzicht: de laatste vijf eigen activiteiten --}}
            <section class="mb-6">
                <div class="mb-2 flex items-baseline justify-between px-0.5">
                    <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Mijn activiteiten</h2>
                    <span class="settings-description text-[11px] font-semibold text-[#656c77]">{{ $activityCount }} totaal</span>
                </div>

                @if ($activities->isEmpty())
                    <div class="account-surface rounded-[18px] bg-white p-5 text-center shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <p class="text-[13px] font-bold">Nog geen activiteiten</p>
                        <p class="settings-description mt-1 text-[11px] text-[#656c77]">Voeg hierboven je eerste activiteit toe.</p>
                    </div>
                @else
                    <ul class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        @foreach ($activities as $activity)
                            @if (! $loop->first)
                                <li class="settings-divider mx-4 h-px bg-[#eceef2]" aria-hidden="true"></li>
                            @endif
                            <li class="flex items-start gap-3 p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-orange account-avatar" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-bold">{{ $activity->title }}</span>
                                    <span class="settings-description mt-0.5 line-clamp-2 text-[11px] leading-snug text-[#656c77]">{{ $activity->description }}</span>
                                </span>
                                <time datetime="{{ $activity->created_at->toIso8601String() }}" class="settings-description shrink-0 text-[10px] font-semibold text-[#656c77]">
                                    {{ $activity->created_at->locale('nl')->translatedFormat('j M') }}
                                </time>
                            </li>
                        @endforeach
                    </ul>

                    @if ($activityCount > \App\Http\Controllers\ActivityController::PER_PAGE)
                        <a
                            href="{{ route('activities.all') }}"
                            class="settings-card group mt-3 flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-bold">Alle activiteiten bekijken</span>
                                <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">Met afbeeldingen en volledige beschrijving</span>
                            </span>
                            <svg class="shrink-0 text-[#656c77] transition group-hover:translate-x-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    @endif
                @endif
            </section>
        </main>

        <x-footer-nav />
    </div>
@endsection
