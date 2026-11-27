@extends('admin.layout')

@section('title', 'Admin dashboard | UpMove')

@section('content')
    <!-- Totalen; elke kaart linkt naar het bijbehorende overzicht. -->
    <section class="mb-6" aria-label="Totalen">
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($stats as $stat)
                <li>
                    <a href="{{ route($stat['route']) }}" class="settings-card block rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                        <span class="block text-[24px] font-extrabold tracking-tight">{{ number_format($stat['value'], 0, ',', '.') }}</span>
                        <span class="settings-description mt-0.5 block text-[11px] font-semibold text-[#656c77]">{{ $stat['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="settings-description mt-3 px-0.5 text-[11px] text-[#656c77]">
            {{ $newUsersThisWeek }} nieuwe {{ $newUsersThisWeek === 1 ? 'gebruiker' : 'gebruikers' }} in de afgelopen 7 dagen ·
            {{ number_format($totalXp, 0, ',', '.') }} XP verdiend in totaal
        </p>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <section>
            <div class="mb-2 flex items-center justify-between px-0.5">
                <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Nieuwste gebruikers</h2>
                <a href="{{ route('admin.users.index') }}" class="text-[11px] font-bold text-orange hover:underline">Alle gebruikers</a>
            </div>
            <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                @forelse ($latestUsers as $latestUser)
                    @if (! $loop->first)
                        <li class="settings-divider mx-4 h-px bg-[#eceef2]" aria-hidden="true"></li>
                    @endif
                    <li>
                        <a href="{{ route('admin.users.edit', $latestUser) }}" class="flex items-center gap-3 p-4 transition hover:bg-[#fafbfc] focus-visible:outline-2 focus-visible:outline-orange">
                            <span class="account-avatar grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-[13px] font-extrabold text-orange" aria-hidden="true">{{ $latestUser->initials() }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-bold">{{ $latestUser->full_name }}</span>
                                <span class="settings-description block truncate text-[11px] text-[#656c77]">{{ $latestUser->username }} · {{ $latestUser->created_at?->format('d-m-Y') }}</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="settings-description p-4 text-[12px] text-[#656c77]">Nog geen gebruikers.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <div class="mb-2 flex items-center justify-between px-0.5">
                <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Laatste logs</h2>
                <a href="{{ route('admin.logs.index') }}" class="text-[11px] font-bold text-orange hover:underline">Alle logs</a>
            </div>
            <ul class="settings-card overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                @forelse ($latestLogs as $log)
                    @include('admin.partials.log-row', ['log' => $log])
                @empty
                    <li class="settings-description p-4 text-[12px] text-[#656c77]">Nog geen logregels.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
