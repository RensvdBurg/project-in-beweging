
<?php
  // Plaats de tweede gebruiker links, de eerste in het midden en de derde rechts.
  $podiumLayout = match ($rankings->count()) {
    1 => 'leaderboard-podium--single',
    2 => 'leaderboard-podium--two',
    default => '',
  };
?>
<main
  class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'screen mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6',
    'hidden' => $screen !== 'ranglijst',
  ]); ?>"
  id="screen-ranglijst">
  <header class="mb-5 pt-2">
    <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
    <h1 class="text-2xl font-extrabold tracking-tight">Ranglijst</h1>
    <p class="mt-1 text-sm text-muted">Bekijk wie de meeste XP heeft verdiend.</p>
  </header>

  <?php if($rankings->isEmpty()): ?>
    <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
      Er staan nog geen deelnemers op de ranglijst.
    </p>
  <?php else: ?>
    <section class="leaderboard-podium <?php echo e($podiumLayout); ?>" aria-label="Top drie">
      <?php $__currentLoopData = [1 => 2, 0 => 1, 2 => 3]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $podiumIndex => $rank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(isset($rankings[$podiumIndex])): ?>
          <?php
            $podiumUser = $rankings[$podiumIndex];
          ?>
          <a
            href="<?php echo e(route('profile.show', $podiumUser)); ?>"
            class="leaderboard-podium-card leaderboard-podium-card--<?php echo e($rank); ?>"
            data-reveal-rank="<?php echo e($rank); ?>"
            aria-label="Bekijk het profiel van <?php echo e($podiumUser->full_name); ?>">
            <span class="leaderboard-podium-rank" aria-label="Positie <?php echo e($rank); ?>">#<?php echo e($rank); ?></span>
            <span class="leaderboard-podium-avatar" aria-hidden="true">
              <?php echo e($podiumUser->initials()); ?>

            </span>
            <span class="leaderboard-podium-name">
              <?php echo e($podiumUser->full_name); ?>

            </span>
            <span class="leaderboard-podium-username">
              <?php echo e('@'.$podiumUser->username); ?>

            </span>
            <span class="leaderboard-xp">
              <?php echo e((int) $podiumUser->xp); ?> XP
            </span>
          </a>
        <?php endif; ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>

    
    <section class="rounded-[22px] bg-surface p-4 shadow-card sm:p-5" aria-labelledby="rankingTitle">
      <h2 id="rankingTitle" class="mb-4 text-lg font-extrabold tracking-tight">Top 10</h2>
      <?php if($rankings->count() > 3): ?>
        <ol class="space-y-2">
          <?php $__currentLoopData = $rankings->slice(3, 7)->values(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $rankedUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $rank = $index + 4;
              $isCurrentUser = $rankedUser->id === $user->id;
            ?>
            <li data-reveal-rank="<?php echo e($rank); ?>">
              <a
                href="<?php echo e(route('profile.show', $rankedUser)); ?>"
                class="leaderboard-user-link flex items-center gap-3 rounded-2xl px-3 py-3 <?php echo e($isCurrentUser ? 'bg-orange-soft' : 'bg-app-bg/60'); ?>"
                aria-label="Bekijk het profiel van <?php echo e($rankedUser->full_name); ?>">
                <span class="leaderboard-rank" role="img" aria-label="Positie <?php echo e($rank); ?>">
                  <?php echo e($rank); ?>

                </span>
                <span class="leaderboard-avatar" aria-hidden="true">
                  <?php echo e($rankedUser->initials()); ?>

                </span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-bold">
                    <?php echo e($rankedUser->full_name); ?>

                    <?php if($isCurrentUser): ?>
                      <span class="text-orange">(jij)</span>
                    <?php endif; ?>
                  </span>
                  <span class="block truncate text-xs text-muted">
                    <?php echo e('@'.$rankedUser->username); ?>

                  </span>
                </span>
                <span class="leaderboard-xp shrink-0">
                  <?php echo e((int) $rankedUser->xp); ?> XP
                </span>
              </a>
            </li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ol>
      <?php else: ?>
        <p class="text-sm text-muted">De overige plekken vullen zich zodra er meer deelnemers zijn.</p>
      <?php endif; ?>
    </section>

    
    <?php if($currentUserRank > $rankings->count()): ?>
      <section class="leaderboard-your-rank" aria-label="Jouw positie" data-reveal-rank="0">
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold uppercase tracking-wide text-orange">Jouw positie</p>
          <p class="truncate text-base font-extrabold">
            <?php echo e($user->full_name); ?>

            <span class="text-sm font-semibold text-muted">
              <?php echo e('@'.$user->username); ?>

            </span>
          </p>
          <p class="mt-0.5 text-xs text-muted">Je staat buiten de top 10</p>
        </div>
        <span class="leaderboard-your-rank-number">#<?php echo e($currentUserRank); ?></span>
        <span class="leaderboard-xp"><?php echo e((int) $user->xp); ?> XP</span>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/leaderboard/screen.blade.php ENDPATH**/ ?>