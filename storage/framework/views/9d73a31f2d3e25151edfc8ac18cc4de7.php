
<main
  class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'screen mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6',
    'hidden' => $screen !== 'leren',
  ]); ?>"
  id="screen-leren">
  <header class="mb-5 pt-2">
    <p class="mb-1 text-sm font-bold text-orange">Leren &amp; Groeien</p>
    <h1 class="text-2xl font-extrabold tracking-tight">Challenges</h1>
    <p class="mt-1 text-sm text-muted">Kies een challenge en ontdek wat je kunt doen.</p>
    <?php if($challenges->isNotEmpty()): ?>
      <label class="sr-only" for="challenge-search">Zoek een challenge</label>
      <div class="relative mt-4">
        <input id="challenge-search" type="search" autocomplete="off"
          placeholder="Zoek een challenge..."
          class="w-full rounded-[18px] border border-[#eceef2] bg-surface px-4 py-3 text-left text-sm font-medium shadow-card outline-none transition placeholder:text-muted focus-visible:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
      </div>
    <?php endif; ?>
  </header>

  <?php if($challenges->isEmpty()): ?>
    <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
      Er zijn nog geen challenges beschikbaar.
    </p>
  <?php else: ?>
    <ul class="space-y-4" id="challenge-list">
      <?php $__currentLoopData = $challenges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $challenge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li data-search="<?php echo e(implode(' ', [$challenge->title, $challenge->description, $challenge->difficulty])); ?>">
          <a href="<?php echo e(route('challenges.show', $challenge)); ?>"
            class="flex items-center gap-4 rounded-[20px] bg-surface p-3 shadow-card transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange"
            data-learn-transition>
            <?php if(! empty($challenge->img)): ?>
              <img src="<?php echo e($challenge->imageUrl(320)); ?>"
                alt=""
                class="h-20 w-20 shrink-0 rounded-[14px] object-cover"
                width="80"
                height="80"
                loading="<?php echo e($index < 3 ? 'eager' : 'lazy'); ?>"
                decoding="async"
                <?php if($index === 0): ?> fetchpriority="high" <?php endif; ?>>
            <?php else: ?>
              <span
                class="grid h-20 w-20 shrink-0 place-items-center rounded-[14px] bg-orange-soft text-orange"
                aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                  <path d="M10 8.5v7l6-3.5-6-3.5z" fill="currentColor" />
                </svg>
              </span>
            <?php endif; ?>

            <span class="min-w-0 flex-1">
              <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-orange">
                <?php echo e($challenge->difficulty); ?>

              </span>
              <span class="block truncate text-base font-extrabold tracking-tight">
                <?php echo e($challenge->title); ?>

              </span>
              <span class="mt-1 block text-xs font-medium text-muted">
                <?php echo e((int) $challenge->kcal_burned); ?> kcal
                <span aria-hidden="true">•</span>
                <?php echo e((int) $challenge->xp_reward); ?> XP
              </span>
            </span>

            <svg class="shrink-0 text-muted" width="20" height="20" viewBox="0 0 24 24" fill="none"
              aria-hidden="true">
              <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
          </a>
        </li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <p class="mt-4 rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" id="challenge-search-empty" hidden>
      Geen challenges gevonden. Probeer een andere zoekterm.
    </p>
  <?php endif; ?>
</main>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/challenges/screen.blade.php ENDPATH**/ ?>