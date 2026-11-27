<?php $__env->startSection('title', 'Account | UpMove'); ?>

<?php $__env->startSection('body'); ?>
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-account flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <!-- Navigatie en profielkaart vormen de kop van de accountinstellingen. -->
            <header class="relative mb-6 flex min-h-11 items-center justify-center">
                <a
                    href="<?php echo e(route('settings')); ?>"
                    class="settings-card absolute left-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition hover:bg-[#f4f6f8] active:scale-95 focus-visible:outline-2 focus-visible:outline-orange"
                    aria-label="Terug naar instellingen">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <h1 class="text-[17px] font-extrabold tracking-tight">Account</h1>
            </header>

            <?php if($statusMessage !== ''): ?>
                <p
                    class="mb-4 rounded-xl px-4 py-3 text-[12px] font-medium <?php echo e($statusType === 'error' ? 'bg-red-50 text-red-600' : 'bg-teal-soft text-[#18796f]'); ?>"
                    role="<?php echo e($statusType === 'error' ? 'alert' : 'status'); ?>"
                    aria-live="polite"><?php echo e($statusMessage); ?></p>
            <?php endif; ?>

                <!-- Profielnaam en e-mail blijven zichtbaar boven de bewerkvelden. -->
                <section class="account-surface mb-6 flex items-center gap-3 rounded-[18px] bg-white p-4 shadow-[0_4px_14px_rgba(20,28,38,0.05)]" aria-label="Huidig profiel">
                    <span class="account-avatar grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange-soft text-[17px] font-extrabold text-orange" aria-hidden="true"><?php echo e($accountUser->initials()); ?></span>
                    <span class="min-w-0">
                        <span class="block truncate text-[15px] font-extrabold"><?php echo e($accountUser->full_name); ?></span>
                        <span class="settings-description mt-0.5 block truncate text-[12px] text-[#656c77]"><?php echo e($accountUser->email); ?></span>
                    </span>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Over mij</h2>
                    <form method="post" action="<?php echo e(route('account.about')); ?>" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <fieldset class="contents">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <label class="block p-4">
                                <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Vertel iets over jezelf</span>
                                <textarea
                                    name="about_me"
                                    rows="5"
                                    maxlength="500"
                                    aria-describedby="about-me-visibility"
                                    class="account-input w-full resize-y rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30"><?php echo e(old('about_me', $accountUser->about_me)); ?></textarea>
                                <span id="about-me-visibility" class="settings-description mt-2 block text-[11px] text-[#656c77]">Dit stukje is zichtbaar voor andere ingelogde gebruikers die je profiel via de ranglijst bekijken. Maximaal 500 tekens.</span>
                            </label>
                            <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                            <div class="flex justify-end p-4">
                                <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Over mij opslaan</button>
                            </div>
                        </fieldset>
                    </form>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Profiel</h2>
                    <!-- Profielgegevens worden bijgewerkt in de bestaande users-tabel. -->
                    <form method="post" action="<?php echo e(route('account.profile')); ?>" class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <fieldset class="contents">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <label class="block p-4">
                            <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Naam</span>
                            <input
                                type="text"
                                name="name"
                                value="<?php echo e(old('name', $accountUser->full_name)); ?>"
                                maxlength="150"
                                autocomplete="name"
                                required
                                class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                        </label>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <label class="block p-4">
                            <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Gebruikersnaam</span>
                            <input
                                type="text"
                                name="username"
                                value="<?php echo e(old('username', $accountUser->username)); ?>"
                                minlength="3"
                                maxlength="30"
                                pattern="[A-Za-z0-9_.\-]{3,30}"
                                title="3 tot 30 tekens: letters, cijfers, punt, streepje of underscore"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                required
                                class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                            <span class="settings-description mt-1.5 block text-[11px] text-[#656c77]">3 tot 30 tekens: letters, cijfers, punt, streepje of underscore. Hiermee kun je ook inloggen.</span>
                        </label>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <label class="block p-4">
                            <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">E-mailadres</span>
                            <input
                                type="email"
                                name="email"
                                value="<?php echo e(old('email', $accountUser->email)); ?>"
                                maxlength="254"
                                autocomplete="email"
                                required
                                class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                        </label>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <div class="flex justify-end p-4">
                            <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Profiel opslaan</button>
                        </div>
                        </fieldset>
                    </form>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Beveiliging</h2>
                    <!-- Inklapbaar wachtwoordformulier houdt de mobiele pagina compact. -->
                    <details class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]" <?php if($passwordFormOpen): ?> open <?php endif; ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 transition hover:bg-[#fafbfc] focus-visible:outline-2 focus-visible:outline-orange">
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-bold">Wachtwoord wijzigen</span>
                                <span class="settings-description mt-0.5 block text-[11px] text-[#656c77]">Werk je wachtwoord bij</span>
                            </span>
                            <svg class="account-chevron shrink-0 text-[#656c77]" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </summary>
                        <form method="post" action="<?php echo e(route('account.password')); ?>" class="border-t border-[#eceef2] p-4">
                            <fieldset class="contents">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <div class="space-y-3">
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Huidig wachtwoord</span>
                                    <input type="password" name="current_password" autocomplete="current-password" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Nieuw wachtwoord</span>
                                    <input type="password" name="new_password" autocomplete="new-password" minlength="8" maxlength="72" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                                <label class="block">
                                    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">Nieuw wachtwoord bevestigen</span>
                                    <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" maxlength="72" required class="account-input w-full rounded-xl border border-[#e5e8ec] bg-white px-3 py-2.5 text-[13px] outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
                                </label>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="rounded-xl bg-orange px-4 py-2.5 text-[12px] font-bold text-white transition hover:brightness-95 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">Wachtwoord opslaan</button>
                            </div>
                            </fieldset>
                        </form>
                    </details>
                </section>

                <section class="mb-6">
                    <h2 class="settings-label mb-2 px-0.5 text-[11px] font-extrabold uppercase tracking-wide text-[#5f6672]">Account</h2>
                    <!-- Uitloggen en verwijderen zijn gescheiden, expliciete accountacties. -->
                    <div class="account-surface overflow-hidden rounded-[18px] bg-white shadow-[0_4px_14px_rgba(20,28,38,0.05)]">
                        <form method="post" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="flex w-full items-center gap-3 p-4 text-left transition hover:bg-[#fafbfc] active:bg-[#f4f6f8] focus-visible:outline-2 focus-visible:outline-orange">
                                <span class="min-w-0 flex-1 text-[13px] font-bold">Uitloggen</span>
                                <svg class="shrink-0 text-[#656c77]" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                        <div class="settings-divider mx-4 h-px bg-[#eceef2]"></div>
                        <button
                            type="button"
                            id="account-delete-open"
                            class="flex w-full items-center gap-3 p-4 text-left text-red-600 transition hover:bg-red-50 active:bg-red-100 focus-visible:outline-2 focus-visible:outline-red-600 disabled:cursor-not-allowed disabled:opacity-50">
                            <span class="min-w-0 flex-1 text-[13px] font-bold">Account verwijderen</span>
                            <svg class="shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </section>

                <!-- De browserdialoog vraagt om bevestiging voordat het account wordt verwijderd. -->
                <dialog id="account-delete-dialog" class="account-dialog w-[calc(100%-2rem)] max-w-sm rounded-[20px] bg-white p-0 text-[#20242a] shadow-2xl backdrop:bg-black/45">
                    <form method="post" action="<?php echo e(route('account.destroy')); ?>" id="account-delete-form" class="p-5">
                        <fieldset class="contents">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <h2 class="text-[16px] font-extrabold">Account verwijderen?</h2>
                        <p class="settings-description mt-2 text-[12px] leading-relaxed text-[#656c77]">Dit verwijdert je account en alle bijbehorende gegevens permanent.</p>
                        <label class="mt-4 flex cursor-pointer items-start gap-2 text-[12px] leading-relaxed">
                            <input type="checkbox" name="confirm_delete" value="1" required class="mt-0.5 accent-red-600">
                            <span>Ik begrijp dat mijn account permanent wordt verwijderd.</span>
                        </label>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" id="account-delete-cancel" class="account-dialog-cancel rounded-xl px-3 py-2 text-[12px] font-semibold text-[#66707d] transition hover:bg-[#f4f6f8] focus-visible:outline-2 focus-visible:outline-orange">Annuleren</button>
                            <button type="submit" class="rounded-xl bg-red-600 px-3 py-2 text-[12px] font-bold text-white transition hover:bg-red-700 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Definitief verwijderen</button>
                        </div>
                        </fieldset>
                    </form>
                </dialog>
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

<?php echo $__env->make('layouts.app', ['bodyClass' => 'min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased', 'scripts' => ['resources/js/account-page.js', 'resources/js/footer-nav.js']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/settings/account.blade.php ENDPATH**/ ?>