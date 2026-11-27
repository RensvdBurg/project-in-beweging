{{-- Eén logregel. Verwacht $log (App\Models\AuditLog). --}}
@php
    $logTone = match (true) {
        $log->action === 'login_failed' => 'bg-red-50 text-red-600',
        str_starts_with($log->action, 'admin_') => 'bg-orange-soft text-orange',
        default => 'bg-teal-soft text-[#18796f]',
    };
@endphp
<li class="flex flex-col gap-1.5 border-t border-[#eceef2] p-4 first:border-t-0 sm:flex-row sm:items-start sm:gap-3">
    <span class="shrink-0 sm:w-44">
        <span class="inline-block rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $logTone }}">{{ $log->actionLabel() }}</span>
    </span>
    <span class="min-w-0 flex-1 text-[13px] font-semibold">{{ $log->description }}</span>
    <span class="settings-description shrink-0 text-[11px] text-[#656c77] sm:text-right">
        <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d-m-Y H:i') }}</time>
        @if ($log->ip_address)
            <span class="block">{{ $log->ip_address }}</span>
        @endif
    </span>
</li>
