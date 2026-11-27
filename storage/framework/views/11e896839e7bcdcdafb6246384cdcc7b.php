<?php $__env->startSection('title', $challenge->title.' | UpMove'); ?>

<?php $__env->startSection('body'); ?>
    <main class="mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6">
        <a href="<?php echo e(route('home', ['screen' => 'leren', 'transition' => 'back'])); ?>#challenge-list"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange"
            aria-label="Terug naar alle challenges"
            data-learn-transition>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Alle challenges
        </a>

        <article class="overflow-hidden rounded-[22px] bg-surface shadow-card">
            <?php if(! empty($challenge->img)): ?>
                <img src="<?php echo e($challenge->imageUrl(1200)); ?>"
                    alt="<?php echo e($challenge->title); ?>"
                    class="h-56 w-full object-cover"
                    width="672"
                    height="224"
                    fetchpriority="high"
                    decoding="async">
            <?php endif; ?>
            <div class="p-5 sm:p-7">
                <p class="mb-2 text-sm font-bold text-orange">Challenge</p>
                <h1 class="mb-3 text-2xl font-extrabold tracking-tight">
                    <?php echo e($challenge->title); ?>

                </h1>

                <div class="mb-5 flex flex-wrap gap-2 text-sm font-semibold text-muted">
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        <?php echo e($challenge->difficulty); ?>

                    </span>
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        <?php echo e((int) $challenge->kcal_burned); ?> kcal
                    </span>
                    <span class="rounded-full bg-orange-soft px-3 py-1">
                        <?php echo e((int) $challenge->xp_reward); ?> XP
                    </span>
                </div>

                <h2 class="mb-2 text-lg font-extrabold">Over deze challenge</h2>
                
                <p class="whitespace-pre-line text-sm leading-relaxed text-muted"><?php echo e($challenge->description_extra ?: $challenge->description); ?></p>
            </div>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginal28b48899fa39549fd9c35f39f71f830c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal28b48899fa39549fd9c35f39f71f830c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.footer-nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('footer-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal28b48899fa39549fd9c35f39f71f830c)): ?>
<?php $attributes = $__attributesOriginal28b48899fa39549fd9c35f39f71f830c; ?>
<?php unset($__attributesOriginal28b48899fa39549fd9c35f39f71f830c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28b48899fa39549fd9c35f39f71f830c)): ?>
<?php $component = $__componentOriginal28b48899fa39549fd9c35f39f71f830c; ?>
<?php unset($__componentOriginal28b48899fa39549fd9c35f39f71f830c); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['scripts' => ['resources/css/learn-page-transition.css', 'resources/js/learn.js', 'resources/js/footer-nav.js']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/challenges/show.blade.php ENDPATH**/ ?>