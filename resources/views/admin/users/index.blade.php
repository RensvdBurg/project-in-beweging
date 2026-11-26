@extends('admin.layout')

@section('title', 'Gebruikers | Admin | UpMove')

@section('content')
    <div class="mb-3 flex items-center justify-between gap-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Gebruikers ({{ $users->total() }})</h2>
        <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Gebruiker aanmaken</a>
    </div>

    @include('admin.partials.search', ['action' => route('admin.users.index'), 'search' => $search, 'placeholder' => 'Zoek op naam, gebruikersnaam of e-mail'])

    <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
        @forelse ($users as $listedUser)
            <li class="flex flex-wrap items-center gap-3 border-t border-[#eceef2] p-4 first:border-t-0">
                <span class="account-avatar grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-[13px] font-extrabold text-orange" aria-hidden="true">{{ $listedUser->initials() }}</span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2">
                        <span class="truncate text-[13px] font-bold">{{ $listedUser->full_name }}</span>
                        @if ($listedUser->is_admin)
                            <span class="shrink-0 rounded-full bg-orange-soft px-2 py-0.5 text-[10px] font-extrabold text-orange">Admin</span>
                        @endif
                    </span>
                    <span class="settings-description block truncate text-[11px] text-[#656c77]">{{ $listedUser->username }} · {{ $listedUser->email }}</span>
                    <span class="settings-description block text-[11px] text-[#656c77]">
                        {{ number_format($listedUser->xp, 0, ',', '.') }} XP · {{ $listedUser->activities_count }} {{ $listedUser->activities_count === 1 ? 'activiteit' : 'activiteiten' }} · lid sinds {{ $listedUser->created_at?->format('d-m-Y') }}
                    </span>
                </span>
                <span class="flex shrink-0 gap-2">
                    <a href="{{ route('admin.users.edit', $listedUser) }}" class="settings-button rounded-xl bg-[#f4f6f8] px-3 py-2 text-[12px] font-bold transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-orange">Bewerken</a>
                    @unless ($listedUser->is(auth()->user()))
                        <form method="post" action="{{ route('admin.users.destroy', $listedUser) }}" data-confirm="Gebruiker {{ $listedUser->username }} en alle activiteiten, reacties en voltooide challenges worden permanent verwijderd.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl px-3 py-2 text-[12px] font-bold text-red-600 transition hover:bg-red-50 active:scale-95 focus-visible:outline-2 focus-visible:outline-red-600">Verwijderen</button>
                        </form>
                    @endunless
                </span>
            </li>
        @empty
            <li class="settings-description p-4 text-[12px] text-[#656c77]">Geen gebruikers gevonden.</li>
        @endforelse
    </ul>

    @include('admin.partials.pagination', ['paginator' => $users])
@endsection
