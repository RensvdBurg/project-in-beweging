<?php $__env->startSection('title', 'Activiteiten | UpMove'); ?>

<?php $__env->startSection('body'); ?>
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-activities flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <header class="relative mb-6 flex min-h-11 items-center justify-center">
                <a
                    href="<?php echo e(route('home')); ?>"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar home">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h1 class="text-[17px] font-extrabold tracking-tight">Activiteiten</h1>
            </header>

            
            <div class="mb-5 px-0.5">
                <h2 class="text-[20px] font-extrabold tracking-tight">Activiteiten</h2>
                <p class="settings-description mt-1 text-[13px] leading-relaxed text-[#656c77]">Deel wat je hebt gedaan, van een hardlooprondje tot een nieuwe sportieve mijlpaal.</p>
            </div>

            <?php if($statusMessage !== ''): ?>
                <p
                    class="mb-4 rounded-xl px-4 py-3 text-[12px] font-medium <?php echo e($statusType === 'error' ? 'bg-red-50 text-red-600' : 'bg-teal-soft text-[#18796f]'); ?>"
                    role="<?php echo e($statusType === 'error' ? 'alert' : 'status'); ?>"
                    aria-live="polite"><?php echo e($statusMessage); ?></p>
            <?php endif; ?>

            
            <section class="mb-6">
                <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Activiteit toevoegen</h2>
                <form method="post" action="<?php echo e(route('activities.store')); ?>" enctype="multipart/form-data" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                    <?php echo csrf_field(); ?>
                    <label class="block p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Titel</span>
                        <input
                            type="text"
                            name="title"
                            value="<?php echo e(old('title')); ?>"
                            maxlength="150"
                            required
                            placeholder="Bijvoorbeeld: Rondje fietsen"
                            class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition placeholder:font-medium placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                    </label>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <label class="block p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Beschrijving</span>
                        <textarea
                            name="description"
                            rows="4"
                            maxlength="2000"
                            required
                            placeholder="Wat heb je gedaan?"
                            class="account-input block w-full resize-y rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-medium leading-relaxed outline-none transition placeholder:text-[#8f96a0] focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30"><?php echo e(old('description')); ?></textarea>
                    </label>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <div class="p-4">
                        <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Afbeelding <span class="font-medium">(optioneel)</span></span>

                        <?php if($isNative): ?>
                            
                            <input type="hidden" name="native_image_path" id="activity-native-image" value="">
                            <button
                                type="button"
                                id="activity-pick-image"
                                class="settings-button flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-[#d5d9df] bg-[#f4f6f8] px-3 py-3 text-[12px] font-bold text-[#5c616a] transition active:scale-[0.99]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7" />
                                    <circle cx="9" cy="10" r="1.6" stroke="currentColor" stroke-width="1.5" />
                                    <path d="m4 17 5-4.5 4 3.5 3-2.5 4 3.5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                </svg>
                                <span id="activity-pick-label">Kies een afbeelding</span>
                            </button>
                        <?php else: ?>
                            <input
                                type="file"
                                name="image"
                                id="activity-image"
                                accept="image/jpeg,image/png,image/webp,image/gif,image/heic"
                                class="account-input block w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2 text-[12px] font-medium text-[#5c616a] file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-orange-soft file:px-3 file:py-1.5 file:text-[12px] file:font-bold file:text-orange">
                        <?php endif; ?>

                        <img id="activity-image-preview" alt="" class="mt-3 hidden h-40 w-full rounded-xl object-cover">
                    </div>
                    <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                    <div class="flex justify-end p-4">
                        <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Activiteit opslaan</button>
                    </div>
                </form>
            </section>

            
            <section class="mb-6">
                <div class="mb-2 flex items-baseline justify-between px-0.5">
                    <h2 class="settings-label text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Mijn activiteiten</h2>
                    <span class="settings-description text-[11px] font-semibold text-[#656c77]"><?php echo e($activityCount); ?> totaal</span>
                </div>

                <?php if($activities->isEmpty()): ?>
                    <div class="account-surface rounded-[18px] bg-white p-5 text-center shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <p class="text-[13px] font-bold">Nog geen activiteiten</p>
                        <p class="settings-description mt-1 text-[11px] text-[#656c77]">Voeg hierboven je eerste activiteit toe.</p>
                    </div>
                <?php else: ?>
                    <ul class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(! $loop->first): ?>
                                <li class="settings-divider mx-4 h-px bg-[#eceef2]" aria-hidden="true"></li>
                            <?php endif; ?>
                            <li class="flex items-start gap-3 p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-orange-soft text-orange account-avatar" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-bold"><?php echo e($activity->title); ?></span>
                                    <span class="settings-description mt-0.5 line-clamp-2 text-[11px] leading-snug text-[#656c77]"><?php echo e($activity->description); ?></span>
                                </span>
                                <time datetime="<?php echo e($activity->created_at->toIso8601String()); ?>" class="settings-description shrink-0 text-[10px] font-semibold text-[#656c77]">
                                    <?php echo e($activity->created_at->locale('nl')->translatedFormat('j M')); ?>

                                </time>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <?php if($activityCount > \App\Http\Controllers\ActivityController::PER_PAGE): ?>
                        <a
                            href="<?php echo e(route('activities.all')); ?>"
                            class="settings-card group mt-3 flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)] transition hover:bg-[#fafbfc] active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-orange">
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-bold">Alle activiteiten bekijken</span>
                                <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">Met afbeeldingen en volledige beschrijving</span>
                            </span>
                            <svg class="shrink-0 text-[#656c77] transition group-hover:translate-x-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
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
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/activities.js', 'resources/js/footer-nav.js']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/activities/index.blade.php ENDPATH**/ ?>