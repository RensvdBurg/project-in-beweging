@extends('admin.layout')

@section('title', 'Logs | Admin | UpMove')

@section('content')
    <div class="mb-3 px-0.5">
        <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Logs ({{ $logs->total() }})</h2>
    </div>

    <!-- Filter op soort en zoeken in de omschrijving. -->
    <form method="get" action="{{ route('admin.logs.index') }}" class="mb-4 flex flex-wrap gap-2" role="search">
        <label class="sm:w-60">
            <span class="sr-only">Soort</span>
            <select name="action" class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                <option value="">Alle soorten</option>
                @foreach ($actions as $value => $label)
                    <option value="{{ $value }}" @selected($action === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="min-w-0 flex-1">
            <span class="sr-only">Zoeken</span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Zoek in de logs" class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
        </label>
        <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Filteren</button>
        @if ($search !== '' || $action !== '')
            <a href="{{ route('admin.logs.index') }}" class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold transition hover:brightness-95 active:scale-95">Wissen</a>
        @endif
    </form>

    <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
        @forelse ($logs as $log)
            @include('admin.partials.log-row', ['log' => $log])
        @empty
            <li class="settings-description p-4 text-[12px] text-[#656c77]">Geen logregels gevonden.</li>
        @endforelse
    </ul>

    @include('admin.partials.pagination', ['paginator' => $logs])
@endsection
