@extends('admin.layout')

@section('title', 'Activiteiten | Admin | UpMove')

@section('content')
    <div class="mb-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Activiteiten ({{ $activities->total() }})</h2>
    </div>

    @include('admin.partials.search', ['action' => route('admin.activities.index'), 'search' => $search, 'placeholder' => 'Zoek op titel, beschrijving of gebruikersnaam'])

    <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
        @forelse ($activities as $activity)
            @php $imageUrl = $activity->imageUrl(); @endphp
            <li class="flex flex-wrap items-start gap-3 border-t border-[#eceef2] p-4 first:border-t-0">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" loading="lazy" class="h-16 w-16 shrink-0 rounded-xl bg-[#f4f6f8] object-cover">
                @else
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-teal-soft text-teal" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M4 16l4.5-4.5a2 2 0 0 1 2.8 0L16 16m-2-2 1.5-1.5a2 2 0 0 1 2.8 0L20 14M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                @endif
                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-bold">{{ $activity->title }}</span>
                    <span class="settings-description block text-[11px] text-[#656c77]">
                        door
                        @if ($activity->user)
                            <a href="{{ route('admin.users.edit', $activity->user) }}" class="font-bold text-orange hover:underline">{{ $activity->user->username }}</a>
                        @else
                            onbekend
                        @endif
                        · {{ $activity->created_at?->format('d-m-Y H:i') }} · {{ $activity->likes_count }} likes · {{ $activity->comments_count }} reacties
                    </span>
                    <span class="mt-1 block whitespace-pre-line text-[12px] leading-relaxed">{{ \Illuminate\Support\Str::limit($activity->description, 300) }}</span>
                </span>
                <form method="post" action="{{ route('admin.activities.destroy', $activity) }}" class="shrink-0" data-confirm="De activiteit &quot;{{ $activity->title }}&quot; wordt met afbeelding, likes en reacties verwijderd.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl px-3 py-2 text-[12px] font-bold text-red-600 transition hover:bg-red-50 active:scale-95 focus-visible:outline-2 focus-visible:outline-red-600">Verwijderen</button>
                </form>
            </li>
        @empty
            <li class="settings-description p-4 text-[12px] text-[#656c77]">Geen activiteiten gevonden.</li>
        @endforelse
    </ul>

    @include('admin.partials.pagination', ['paginator' => $activities])
@endsection
