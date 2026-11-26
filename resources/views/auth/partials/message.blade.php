{{-- Melding boven een aanmeld- of registratieformulier (succes of fout). --}}
@php
    $isSuccess = $type === 'success';
@endphp
<div
    id="{{ $id }}"
    role="{{ $isSuccess ? 'status' : 'alert' }}"
    aria-live="{{ $isSuccess ? 'polite' : 'assertive' }}"
    @class([
        'auth-message flex items-start gap-3 rounded-[14px] border px-3.5 py-3 text-left',
        'border-[#b8ddd4] bg-[#edf7f4] text-[#17685b]' => $isSuccess,
        'border-[#f2c9c7] bg-[#fff3f2] text-[#b4231a]' => ! $isSuccess,
    ])>
    <span @class([
        'mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-extrabold text-white',
        'bg-[#17685b]' => $isSuccess,
        'bg-[#d6453b]' => ! $isSuccess,
    ]) aria-hidden="true">{{ $isSuccess ? '✓' : '!' }}</span>
    <div class="min-w-0">
        <p class="text-[13px] font-bold">{{ $title }}</p>
        <p class="mt-0.5 text-[12px] leading-relaxed">{{ $message }}</p>
    </div>
</div>
