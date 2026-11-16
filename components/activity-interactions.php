<?php
$interactionPostId = (int) $activityPost['id'];
$interactionLikeCount = (int) ($activityPost['like_count'] ?? 0);
$interactionCommentCount = (int) ($activityPost['comment_count'] ?? 0);
$interactionIsLiked = (bool) ($activityPost['liked_by_user'] ?? false);
?>
<div class="activity-interactions border-t border-[#eceef2] px-4 py-3" data-activity-post-id="<?= $interactionPostId ?>" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="flex items-center gap-5">
        <button
            type="button"
            class="activity-like-button inline-flex min-h-10 items-center gap-2 rounded-full px-2 text-sm font-bold text-ink transition hover:text-orange focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange"
            data-liked="<?= $interactionIsLiked ? 'true' : 'false' ?>"
            aria-pressed="<?= $interactionIsLiked ? 'true' : 'false' ?>"
            aria-label="<?= $interactionIsLiked ? 'Vind ik niet meer leuk' : 'Vind ik leuk' ?>">
            <svg class="activity-interaction-icon h-5 w-5" viewBox="0 0 24 24" fill="<?= $interactionIsLiked ? 'currentColor' : 'none' ?>" aria-hidden="true">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="activity-interaction-count" data-like-count><?= $interactionLikeCount ?></span>
        </button>
        <button
            type="button"
            class="activity-comments-toggle inline-flex min-h-10 items-center gap-2 rounded-full px-2 text-sm font-bold text-ink transition hover:text-orange focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange"
            aria-expanded="false"
            aria-label="Bekijk reacties">
            <svg class="activity-interaction-icon h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-9.5A7.5 7.5 0 0 1 10.5 4H12a8 8 0 0 1 8 7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M8 11h8m-8 3h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
            <span class="activity-interaction-count" data-comment-count><?= $interactionCommentCount ?></span>
        </button>
    </div>
    <p class="activity-interaction-status mt-2 hidden text-sm font-semibold text-flag-red" role="status"></p>
    <div class="activity-comments-panel mt-3 hidden border-t border-[#eceef2] pt-3">
        <ul class="activity-comments-list space-y-3" aria-live="polite"></ul>
        <form class="activity-comment-form mt-3 flex items-start gap-2">
            <label class="block min-w-0 flex-1">
                <span class="sr-only">Schrijf een reactie</span>
                <textarea name="comment" rows="2" maxlength="1000" required class="activity-comment-input w-full resize-y rounded-xl border border-[#eceef2] bg-surface px-3 py-2 text-sm outline-none focus-visible:border-orange focus-visible:ring-2 focus-visible:ring-orange/30" placeholder="Schrijf een reactie..."></textarea>
            </label>
            <button type="submit" class="shrink-0 rounded-full bg-orange px-4 py-2 text-xs font-extrabold text-white transition hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">
                Reageren
            </button>
        </form>
    </div>
</div>
