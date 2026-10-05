

<nav
      class="fixed bottom-0 left-0 right-0 z-10 border-t border-[#eceef2] bg-white/95 backdrop-blur-md"
      aria-label="Hoofdnavigatie"
    >
      <div class="mx-auto grid max-w-lg grid-cols-5 px-1.5 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-2">
        <!-- Actieve tab = text-orange, inactief = text-[#9aa0a8] -->
        <button type="button" class="nav-item flex flex-col items-center gap-1 px-0.5 py-1 text-[10px] font-semibold text-orange transition active:scale-95" data-screen="home">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
          <span>Home</span>
        </button>
        <button type="button" class="nav-item flex flex-col items-center gap-1 px-0.5 py-1 text-[10px] font-semibold text-[#9aa0a8] transition active:scale-95" data-screen="bewegen">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M10 8.5v7l6-3.5-6-3.5z" fill="currentColor"/></svg>
          <span>Bewegen</span>
        </button>
        <button type="button" class="nav-item flex flex-col items-center gap-1 px-0.5 py-1 text-[10px] font-semibold text-[#9aa0a8] transition active:scale-95" data-screen="leren">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5c2.2-1 4.3-1.2 6.5 0v13c-2.2-1.2-4.3-1-6.5 0v-13zm16 0c-2.2-1-4.3-1.2-6.5 0v13c2.2-1.2 4.3-1 6.5 0v-13z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
          <span>Leren</span>
        </button>
        <button type="button" class="nav-item flex flex-col items-center gap-1 px-0.5 py-1 text-[10px] font-semibold text-[#9aa0a8] transition active:scale-95" data-screen="stats">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 19V11M12 19V5M19 19v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <span>Statistieken</span>
        </button>
        <button type="button" class="nav-item flex flex-col items-center gap-1 px-0.5 py-1 text-[10px] font-semibold text-[#9aa0a8] transition active:scale-95" data-screen="settings">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.2M12 19.3v2.2M4.2 6.5l1.6 1.6M18.2 15.9l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 17.5l1.6-1.6M18.2 8.1l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <span>Instellingen</span>
        </button>
      </div>
    </nav>
