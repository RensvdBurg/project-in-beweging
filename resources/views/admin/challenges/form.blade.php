@extends('admin.layout')

@php $editing = $challenge->exists; @endphp

@section('title', ($editing ? 'Challenge bewerken' : 'Challenge toevoegen').' | Admin | UpMove')

@section('content')
    <div class="mb-3 flex items-center justify-between gap-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
            {{ $editing ? 'Challenge #'.$challenge->id.' bewerken' : 'Nieuwe challenge' }}
        </h2>
        <a href="{{ route('admin.challenges.index') }}" class="text-[11px] font-bold text-orange hover:underline">Terug naar challenges</a>
    </div>

    @if ($errors->any())
        <p class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-[12px] font-medium text-red-600" role="alert">Controleer de gemarkeerde velden.</p>
    @endif

    <form method="post" action="{{ $editing ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" class="max-w-2xl">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="account-surface mb-6 overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
            @include('admin.partials.field', ['name' => 'title', 'label' => 'Titel', 'value' => $challenge->title, 'extraAttributes' => 'maxlength="255" required'])
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            @include('admin.partials.field', [
                'name' => 'description_extra',
                'label' => 'Beschrijving',
                'textarea' => true,
                'rows' => 5,
                'value' => $challenge->description_extra,
                'hint' => 'Wordt getoond op de pagina "Wat is dit?" van de challenge.',
                'extraAttributes' => 'maxlength="5000"',
            ])
        </section>

        <section class="account-surface mb-6 overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    @include('admin.partials.field', [
                        'name' => 'img',
                        'label' => 'Afbeelding',
                        'value' => $challenge->img,
                        'hint' => 'Een bestand in public/ (bijv. images/challenges/foto.jpg) of een https-URL.',
                        'extraAttributes' => 'maxlength="2048" required data-image-preview="challenge-image-preview" data-asset-base="'.e(asset('')).'"',
                    ])
                </div>
                <img
                    id="challenge-image-preview"
                    src="{{ $challenge->img ? $challenge->imageUrl(160) : '' }}"
                    alt="Voorbeeld van de afbeelding"
                    @class(['mr-4 mt-4 h-20 w-20 shrink-0 rounded-xl bg-[#f4f6f8] object-cover', 'hidden' => ! $challenge->img])>
            </div>
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            <div class="grid sm:grid-cols-3">
                @php
                    $difficultyError = $errors->first('difficulty');
                    $selectedDifficulty = old('difficulty', $challenge->difficulty);
                @endphp
                <label class="block p-4" for="field-difficulty">
                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Moeilijkheid</span>
                    <select
                        id="field-difficulty"
                        name="difficulty"
                        required
                        @if ($difficultyError) aria-invalid="true" aria-describedby="field-difficulty-error" @endif
                        class="account-input w-full rounded-xl border bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30 {{ $difficultyError ? 'border-red-600' : 'border-[#e5e8ec]' }}">
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty }}" @selected($selectedDifficulty === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                    @if ($difficultyError)
                        <span id="field-difficulty-error" class="mt-1.5 block text-[11px] font-semibold text-red-600">{{ $difficultyError }}</span>
                    @endif
                </label>
                @include('admin.partials.field', ['name' => 'kcal_burned', 'label' => 'Kcal', 'type' => 'number', 'value' => $challenge->kcal_burned, 'extraAttributes' => 'min="0" max="10000" step="1" required'])
                @include('admin.partials.field', ['name' => 'xp_reward', 'label' => 'XP-beloning', 'type' => 'number', 'value' => $challenge->xp_reward, 'extraAttributes' => 'min="1" max="10000" step="1" required'])
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.challenges.index') }}" class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold transition hover:brightness-95 active:scale-95">Annuleren</a>
            <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">
                {{ $editing ? 'Wijzigingen opslaan' : 'Challenge toevoegen' }}
            </button>
        </div>
    </form>
@endsection
