<?php $__env->startSection('title', ($activeAuthMode === 'register' ? 'Registreren' : 'Aanmelden').' | UpMove'); ?>

<?php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maart', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Augustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December',
    ];
    $currentYear = (int) now()->format('Y');

    // Gedeelde opmaak, zodat alle velden er hetzelfde uitzien.
    $labelClass = 'mb-1.5 block text-[12px] font-semibold text-[#3d4a47]';
    $inputClass = 'auth-input h-12 w-full rounded-[14px] border border-[#dfe6e3] bg-white px-4 text-[14px] font-medium text-[#1f2a28] outline-none transition placeholder:font-normal placeholder:text-[#8a9592] hover:border-[#c9d4d0] focus:border-[#17685b] focus-visible:ring-4 focus-visible:ring-[#17685b]/12';
    $selectClass = 'auth-input h-12 w-full min-w-0 appearance-none rounded-[14px] border border-[#dfe6e3] bg-white bg-[length:16px] bg-[right_0.75rem_center] bg-no-repeat pl-3.5 pr-8 text-[14px] font-medium text-[#1f2a28] outline-none transition hover:border-[#c9d4d0] focus:border-[#17685b] focus-visible:ring-4 focus-visible:ring-[#17685b]/12';
    $buttonClass = 'flex h-12 w-full cursor-pointer items-center justify-center rounded-[14px] bg-primary text-[14px] font-bold text-white shadow-[0_8px_20px_rgba(255,107,74,0.28)] transition hover:brightness-95 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
    $switchClass = 'cursor-pointer rounded font-bold text-[#17685b] underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#17685b]';
?>

<?php $__env->startSection('body'); ?>
<main class="auth-page relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-[#f4f7f6] px-4 pb-[max(2rem,env(safe-area-inset-bottom))] pt-[max(2rem,env(safe-area-inset-top))] text-[#1f2a28] sm:py-12">
    
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <span class="absolute -right-32 -top-36 h-[26rem] w-[26rem] rounded-full bg-[#dcefe9] opacity-70 blur-3xl"></span>
        <span class="absolute -bottom-40 -left-36 h-[25rem] w-[25rem] rounded-full bg-[#f9e9df] opacity-60 blur-3xl"></span>
    </div>

    <div class="relative z-10 w-full max-w-[400px]">
        
        <div class="mb-6 flex items-center justify-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#17685b] shadow-[0_8px_20px_rgba(23,104,91,0.22)]" aria-hidden="true">
                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
            </span>
            <span class="text-2xl font-extrabold tracking-tight">UpMove</span>
        </div>

        
        <section id="login" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['auth-panel rounded-[24px] border border-[#e6ece9] bg-white/95 px-6 pb-7 pt-8 shadow-[0_24px_70px_rgba(16,54,46,0.12)] backdrop-blur sm:px-8', 'hidden' => $activeAuthMode === 'register']); ?>" aria-labelledby="loginTitle">
            <header class="mb-6">
                <h1 id="loginTitle" class="text-[22px] font-extrabold tracking-tight">Welkom terug</h1>
                <p class="mt-1 text-[13px] text-[#66736f]">Meld je aan om verder te gaan met bewegen.</p>
            </header>

            <form id="loginForm" method="post" action="<?php echo e(route('login.store')); ?>" class="flex flex-col gap-4" novalidate>
                <?php echo csrf_field(); ?>

                <?php if($authMessage !== '' && $activeAuthMode === 'login'): ?>
                    <?php echo $__env->make('auth.partials.message', [
                        'id' => 'loginStatus',
                        'type' => $authMessageType,
                        'title' => $authMessageType === 'success' ? 'Gelukt' : 'Aanmelden niet gelukt',
                        'message' => $authMessage,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="loginEmail">E-mailadres of gebruikersnaam</label>
                    <input id="loginEmail" required name="email" value="<?php echo e($activeAuthMode === 'login' ? old('email') : ''); ?>" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="naam@voorbeeld.nl" class="<?php echo e($inputClass); ?>" <?php if($activeAuthMode === 'login'): ?> autofocus <?php endif; ?>>
                </div>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="loginPassword">Wachtwoord</label>
                    <?php echo $__env->make('auth.partials.password', ['id' => 'loginPassword', 'name' => 'password', 'autocomplete' => 'current-password', 'inputClass' => $inputClass], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <button type="submit" class="<?php echo e($buttonClass); ?> mt-2">Aanmelden</button>
            </form>

            <p class="mt-6 border-t border-[#edf1ef] pt-5 text-center text-[13px] text-[#66736f]">
                Nog geen account?
                <button type="button" data-mode="register" class="<?php echo e($switchClass); ?>">Account aanmaken</button>
            </p>
        </section>

        
        <section id="register" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['auth-panel rounded-[24px] border border-[#e6ece9] bg-white/95 px-6 pb-7 pt-8 shadow-[0_24px_70px_rgba(16,54,46,0.12)] backdrop-blur sm:px-8', 'hidden' => $activeAuthMode !== 'register']); ?>" aria-labelledby="registerTitle">
            <header class="mb-6">
                <h1 id="registerTitle" class="text-[22px] font-extrabold tracking-tight">Registreren</h1>
                <p class="mt-1 text-[13px] text-[#66736f]">Maak een account aan en verdien XP met korte beweegmomenten.</p>
            </header>

            <form id="registerForm" method="post" action="<?php echo e(route('register')); ?>" class="flex flex-col gap-4" novalidate>
                <?php echo csrf_field(); ?>

                <?php if($authMessage !== '' && $activeAuthMode === 'register'): ?>
                    <?php echo $__env->make('auth.partials.message', [
                        'id' => 'registerStatus',
                        'type' => $authMessageType,
                        'title' => $authMessageType === 'success' ? 'Gelukt' : 'Registreren niet gelukt',
                        'message' => $authMessage,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="fullName">Volledige naam</label>
                    <input id="fullName" required name="fullName" value="<?php echo e(old('fullName')); ?>" maxlength="150" autocomplete="name" placeholder="Sanne de Vries" class="<?php echo e($inputClass); ?>" <?php if($activeAuthMode === 'register'): ?> autofocus <?php endif; ?>>
                </div>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="username">Gebruikersnaam</label>
                    <input id="username" required name="username" value="<?php echo e(old('username')); ?>" minlength="3" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="sanne_01" aria-describedby="usernameHint" class="<?php echo e($inputClass); ?>">
                    <p id="usernameHint" class="mt-1.5 text-[11px] text-[#5f6b68]">3 tot 30 tekens: letters, cijfers, punt, streepje of underscore.</p>
                </div>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="registerEmail">E-mailadres</label>
                    <input id="registerEmail" required type="email" name="email" value="<?php echo e($activeAuthMode === 'register' ? old('email') : ''); ?>" maxlength="254" autocomplete="email" autocapitalize="none" spellcheck="false" placeholder="naam@voorbeeld.nl" aria-describedby="emailHint" class="<?php echo e($inputClass); ?>">
                    <p id="emailHint" class="mt-1.5 text-[11px] text-[#5f6b68]">Je ontvangt mogelijk meldingen van ons.</p>
                </div>

                <fieldset>
                    <legend class="<?php echo e($labelClass); ?>">Geboortedatum</legend>
                    <div class="grid grid-cols-[1fr_1.6fr_1.2fr] gap-2">
                        <div>
                            <label class="sr-only" for="birthDay">Dag</label>
                            <select id="birthDay" required name="day" class="<?php echo e($selectClass); ?> auth-select">
                                <option value="" disabled <?php if(old('day') === null): echo 'selected'; endif; ?>>Dag</option>
                                <?php for($day = 1; $day <= 31; $day++): ?>
                                    <option value="<?php echo e($day); ?>" <?php if((int) old('day') === $day): echo 'selected'; endif; ?>><?php echo e($day); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="birthMonth">Maand</label>
                            <select id="birthMonth" required name="month" class="<?php echo e($selectClass); ?> auth-select">
                                <option value="" disabled <?php if(old('month') === null): echo 'selected'; endif; ?>>Maand</option>
                                <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthNumber => $monthName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($monthNumber); ?>" <?php if((int) old('month') === $monthNumber): echo 'selected'; endif; ?>><?php echo e($monthName); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="birthYear">Jaar</label>
                            <select id="birthYear" required name="year" class="<?php echo e($selectClass); ?> auth-select">
                                <option value="" disabled <?php if(old('year') === null): echo 'selected'; endif; ?>>Jaar</option>
                                <?php for($year = $currentYear; $year >= $currentYear - 110; $year--): ?>
                                    <option value="<?php echo e($year); ?>" <?php if((int) old('year') === $year): echo 'selected'; endif; ?>><?php echo e($year); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="registerPassword">Wachtwoord</label>
                    <?php echo $__env->make('auth.partials.password', ['id' => 'registerPassword', 'name' => 'password', 'autocomplete' => 'new-password', 'inputClass' => $inputClass, 'describedBy' => 'passwordHint'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <p id="passwordHint" class="mt-1.5 text-[11px] text-[#5f6b68]">Minimaal 8 tekens.</p>
                </div>

                <div>
                    <label class="<?php echo e($labelClass); ?>" for="confirmPassword">Wachtwoord herhalen</label>
                    <?php echo $__env->make('auth.partials.password', ['id' => 'confirmPassword', 'name' => 'confirmPassword', 'autocomplete' => 'new-password', 'inputClass' => $inputClass], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <button type="submit" class="<?php echo e($buttonClass); ?> mt-2">Account aanmaken</button>
            </form>

            <p class="mt-6 border-t border-[#edf1ef] pt-5 text-center text-[13px] text-[#66736f]">
                Heb je al een account?
                <button type="button" data-mode="login" class="<?php echo e($switchClass); ?>">Aanmelden</button>
            </p>
        </section>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['scripts' => ['resources/js/login.js']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/auth/login.blade.php ENDPATH**/ ?>