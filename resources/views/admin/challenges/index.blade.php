@extends('admin.layout')

@section('title', 'Challenges | Admin | UpMove')

@section('content')
    <div class="mb-3 flex items-center justify-between gap-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Challenges ({{ $challenges->total() }})</h2>
        <a href="{{ route('admin.challenges.create') }}" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Challenge toevoegen</a>
    </div>

    @include('admin.partials.search', ['action' => route('admin.challenges.index'), 'search' => $search, 'placeholder' => 'Zoek op titel, beschrijving of moeilijkheid'])

    <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
        @forelse ($challenges as $challenge)
            <li class="flex flex-wrap items-center gap-3 border-t border-[#eceef2] p-4 first:border-t-0">
                <img src="{{ $challenge->imageUrl(160) }}" alt="" loading="lazy" class="h-14 w-14 shrink-0 rounded-xl bg-[#f4f6f8] object-cover">
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-bold"><span class="settings-description text-[#656c77]">#{{ $challenge->id }}</span> {{ $challenge->title }}</span>
                    <span class="settings-description block text-[11px] text-[#656c77]">
                        {{ $challenge->difficulty }} · {{ $challenge->kcal_burned }} kcal · {{ $challenge->xp_reward }} XP · {{ $challenge->completions_count }}× voltooid
                    </span>
                    @if ($challenge->description_extra)
                        <span class="settings-description mt-0.5 block truncate text-[11px] text-[#656c77]">{{ $challenge->description_extra }}</span>
                    @endif
                </span>
                <span class="flex shrink-0 gap-2">
                    <a href="{{ route('admin.challenges.edit', $challenge) }}" class="settings-button rounded-xl bg-[#f4f6f8] px-3 py-2 text-[12px] font-bold transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-orange">Bewerken</a>
                    <form method="post" action="{{ route('admin.challenges.destroy', $challenge) }}" data-confirm="De challenge &quot;{{ $challenge->title }}&quot; en de {{ $challenge->completions_count }} voltooiingen worden verwijderd. Verdiende XP blijft staan.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl px-3 py-2 text-[12px] font-bold text-red-600 transition hover:bg-red-50 active:scale-95 focus-visible:outline-2 focus-visible:outline-red-600">Verwijderen</button>
                    </form>
                </span>
            </li>
        @empty
            <li class="settings-description p-4 text-[12px] text-[#656c77]">Geen challenges gevonden.</li>
        @endforelse
    </ul>

    @include('admin.partials.pagination', ['paginator' => $challenges])
@endsection
