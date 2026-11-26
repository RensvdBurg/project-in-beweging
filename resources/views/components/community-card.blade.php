@props(['activity', 'openComments' => 0, 'commentError' => '', 'profileFrom' => 'home'])

@php
  // Reactiepaneel staat open na het plaatsen van een reactie (of bij een fout).
  $commentsOpen = $openComments === $activity->id;
  $commentsId = 'reacties-'.$activity->id;
@endphp

<article id="activiteit-{{ $activity->id }}" data-community-card data-profile-from="{{ $profileFrom }}"class="home-card-interactive account-surface scroll-mt-4 overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
  @if ($activity->imageUrl() !== null)
    <img
      src="{{ $activity->imageUrl() }}"
      alt="{{ $activity->title }}"
      class="h-56 w-full object-cover"
      loading="lazy">
  @else
    <div class="account-avatar grid h-28 w-full place-items-center bg-orange-soft text-orange" aria-hidden="true">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
      </svg>
    </div>
  @endif

  <div class="p-4">
    {{-- Like- en reactieknop --}}
    <div class="-ml-2 mb-2 flex items-center gap-1">
      <form method="post" action="{{ route('activities.like', $activity) }}">
        @csrf
        <button type="submit"
          class="flex cursor-pointer items-center gap-1.5 rounded-full px-2 py-1.5 text-[13px] font-bold transition active:scale-95 {{ $activity->liked_by_viewer ? 'text-orange' : 'settings-description text-[#5c616a]' }}"
          aria-pressed="{{ $activity->liked_by_viewer ? 'true' : 'false' }}"
          aria-label="{{ $activity->liked_by_viewer ? 'Vind ik niet meer leuk' : 'Vind ik leuk' }}">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ $activity->liked_by_viewer ? 'currentColor' : 'none' }}" aria-hidden="true">
            <path d="M12 20.5s-7.5-4.6-9.2-9.4C1.6 7.6 3.9 4 7.4 4c2 0 3.6 1.1 4.6 2.7C13 5.1 14.6 4 16.6 4c3.5 0 5.8 3.6 4.6 7.1-1.7 4.8-9.2 9.4-9.2 9.4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
          </svg>
          <span>{{ $activity->likes_count }}</span>
        </button>
      </form>

      <button type="button"
        class="settings-description flex cursor-pointer items-center gap-1.5 rounded-full px-2 py-1.5 text-[13px] font-bold text-[#5c616a] transition active:scale-95"
        data-comments-toggle
        aria-controls="{{ $commentsId }}"
        aria-expanded="{{ $commentsOpen ? 'true' : 'false' }}"
        aria-label="Reacties ({{ $activity->comments_count }})">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l.9-4.4A8 8 0 1 1 20 12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
        </svg>
        <span>{{ $activity->comments_count }}</span>
      </button>
    </div>

    <a
      href="{{ route('profile.show', ['user' => $activity->user, 'from' => $profileFrom]) }}"
      class="inline-block text-[13px] font-extrabold tracking-tight text-orange underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange"
      aria-label="Bekijk het profiel van {{ $activity->user->username }}">{{ $activity->user->username }}</a>
    <h3 class="mt-1 text-[15px] font-extrabold tracking-tight">{{ $activity->title }}</h3>
    <p class="settings-description mt-1 whitespace-pre-line text-[13px] leading-relaxed text-[#5c616a]">{{ $activity->description }}</p>
    <time datetime="{{ $activity->created_at->toIso8601String() }}" class="settings-description mt-2 block text-[11px] font-semibold text-[#656c77]">
      {{ $activity->created_at->locale('nl')->translatedFormat('j F Y') }}
    </time>

    {{-- Reacties (open/dicht via resources/js/community.js) --}}
    <div id="{{ $commentsId }}" class="mt-3" @if (! $commentsOpen) hidden @endif>
      <div class="settings-divider mb-3 h-px bg-[#eceef2]" aria-hidden="true"></div>

      @if ($activity->comments->isEmpty())
        <p class="settings-description mb-3 text-[12px] text-[#656c77]">Nog geen reacties. Wees de eerste!</p>
      @else
        <ul class="mb-3 space-y-2">
          @foreach ($activity->comments as $comment)
            <li class="flex items-start gap-2 text-[12px] leading-snug">
              <p class="min-w-0 flex-1 break-words">
                <span class="font-extrabold">{{ $comment->user->username }}</span>
                <span class="settings-description whitespace-pre-line text-[#5c616a]">{{ $comment->body }}</span>
              </p>
              @if ($comment->user_id === auth()->id())
                {{-- Alleen de schrijver kan zijn eigen reactie verwijderen --}}
                <form method="post" action="{{ route('comments.destroy', $comment) }}" class="shrink-0">
                  @csrf
                  @method('DELETE')
                  <button type="submit"
                    class="settings-description -mr-1 -mt-0.5 grid h-6 w-6 cursor-pointer place-items-center rounded-full text-[#656c77] transition hover:bg-red-50 hover:text-red-600 active:scale-95"
                    aria-label="Reactie verwijderen" title="Reactie verwijderen">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                  </button>
                </form>
              @endif
            </li>
          @endforeach
        </ul>
      @endif

      @if ($commentsOpen && $commentError !== '')
        <p class="mb-2 rounded-xl bg-red-50 px-3 py-2 text-[12px] font-medium text-red-600" role="alert">{{ $commentError }}</p>
      @endif

      <form method="post" action="{{ route('activities.comment', $activity) }}" class="flex items-center gap-2">
        @csrf
        <input
          type="text"
          name="body"
          value="{{ $commentsOpen ? old('body') : '' }}"
          maxlength="1000"
          required
          placeholder="Schrijf een reactie…"
          aria-label="Reactie op de activiteit van {{ $activity->user->username }}"
          class="account-input min-w-0 flex-1 rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition placeholder:font-medium placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
        <button type="submit" class="shrink-0 cursor-pointer rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95">Plaats</button>
      </form>
    </div>
  </div>
</article>
