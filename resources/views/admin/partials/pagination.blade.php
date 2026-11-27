{{-- Vorige / Pagina x van y / Volgende, zelfde opmaak als activiteiten en community. Verwacht $paginator. --}}
@if ($paginator->hasPages())
    <nav class="mt-5 flex items-center justify-between gap-3" aria-label="Paginering">
        @if ($paginator->onFirstPage())
            <span class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold opacity-40" aria-disabled="true">Vorige</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="settings-card rounded-xl bg-white px-4 py-2.5 text-[12px] font-bold shadow-sm transition hover:bg-[#fafbfc] active:scale-95">Vorige</a>
        @endif

        <span class="settings-description text-[12px] font-semibold text-[#656c77]">
            Pagina {{ $paginator->currentPage() }} van {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95">Volgende</a>
        @else
            <span class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold opacity-40" aria-disabled="true">Volgende</span>
        @endif
    </nav>
@endif
