@extends('admin.layout')

@php
    $editing = $user->exists;
    $isSelf = $editing && $user->is(auth()->user());
@endphp

@section('title', ($editing ? 'Gebruiker bewerken' : 'Gebruiker aanmaken').' | Admin | UpMove')

@section('content')
    <div class="mb-3 flex items-center justify-between gap-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">
            {{ $editing ? 'Gebruiker '.$user->username.' bewerken' : 'Nieuwe gebruiker' }}
        </h2>
        <a href="{{ route('admin.users.index') }}" class="text-[11px] font-bold text-orange hover:underline">Terug naar gebruikers</a>
    </div>

    @if ($errors->any())
        <p class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-[12px] font-medium text-red-600" role="alert">Controleer de gemarkeerde velden.</p>
    @endif

    <form method="post" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-2xl">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="account-surface mb-6 overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
            @include('admin.partials.field', ['name' => 'full_name', 'label' => 'Naam', 'value' => $user->full_name, 'extraAttributes' => 'maxlength="150" required'])
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            @include('admin.partials.field', ['name' => 'username', 'label' => 'Gebruikersnaam', 'value' => $user->username, 'hint' => '3 tot 30 tekens: letters, cijfers, punt, streepje of underscore.', 'extraAttributes' => 'minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" autocapitalize="none" spellcheck="false" required'])
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            @include('admin.partials.field', ['name' => 'email', 'label' => 'E-mailadres', 'type' => 'email', 'value' => $user->email, 'extraAttributes' => 'maxlength="254" required'])
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            @include('admin.partials.field', [
                'name' => 'password',
                'label' => $editing ? 'Nieuw wachtwoord' : 'Wachtwoord',
                'type' => 'password',
                'value' => '',
                'hint' => $editing ? 'Leeg laten om het huidige wachtwoord te houden.' : 'Minimaal 8 en maximaal 72 tekens.',
                'extraAttributes' => 'autocomplete="new-password" minlength="8" maxlength="72"'.($editing ? '' : ' required'),
            ])
        </section>

        <section class="account-surface mb-6 overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
            <div class="grid sm:grid-cols-2">
                @include('admin.partials.field', ['name' => 'birth_date', 'label' => 'Geboortedatum', 'type' => 'date', 'value' => $user->birth_date?->toDateString(), 'extraAttributes' => 'max="'.now()->toDateString().'" required'])
                @include('admin.partials.field', ['name' => 'xp', 'label' => 'XP', 'type' => 'number', 'value' => $user->xp ?? 0, 'extraAttributes' => 'min="0" max="10000000" step="1" required'])
            </div>
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            @include('admin.partials.field', ['name' => 'about_me', 'label' => 'Over mij', 'textarea' => true, 'rows' => 3, 'value' => $user->about_me, 'extraAttributes' => 'maxlength="500"'])
            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
            <label class="flex cursor-pointer items-start gap-3 p-4">
                <input type="hidden" name="is_admin" value="0">
                <input type="checkbox" name="is_admin" value="1" class="mt-0.5 h-4 w-4 accent-orange" @checked(old('is_admin', $user->is_admin)) @disabled($isSelf)>
                <span>
                    <span class="block text-[13px] font-bold">Beheerder</span>
                    <span class="settings-description block text-[11px] text-[#656c77]">
                        {{ $isSelf ? 'Je kunt je eigen beheerdersrechten niet weghalen.' : 'Mag dit admin-dashboard openen en alles beheren.' }}
                    </span>
                </span>
            </label>
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.users.index') }}" class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold transition hover:brightness-95 active:scale-95">Annuleren</a>
            <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">
                {{ $editing ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' }}
            </button>
        </div>
    </form>
@endsection
