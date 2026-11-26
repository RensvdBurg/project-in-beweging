{{-- Zoekveld boven een lijst. Verwacht $action (URL), $search en $placeholder. --}}
<form method="get" action="{{ $action }}" class="mb-4 flex flex-wrap gap-2" role="search">
    <label class="min-w-0 flex-1">
        <span class="sr-only">Zoeken</span>
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ $placeholder }}"
            class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
    </label>
    <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Zoeken</button>
    @if ($search !== '')
        <a href="{{ $action }}" class="settings-button rounded-xl bg-[#f4f6f8] px-4 py-2.5 text-[12px] font-bold transition hover:brightness-95 active:scale-95">Wissen</a>
    @endif
</form>
