
<div class="relative">
    <input
        id="<?php echo e($id); ?>"
        required
        type="password"
        name="<?php echo e($name); ?>"
        autocomplete="<?php echo e($autocomplete); ?>"
        <?php if(isset($describedBy)): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>
        class="<?php echo e($inputClass); ?> pr-12">
    <button
        type="button"
        class="absolute right-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 cursor-pointer place-items-center rounded-[10px] text-[#5f6b68] transition hover:bg-[#f1f5f4] hover:text-[#17685b] focus-visible:outline-2 focus-visible:outline-[#17685b]"
        data-password-toggle="<?php echo e($id); ?>"
        aria-controls="<?php echo e($id); ?>"
        aria-pressed="false"
        aria-label="Wachtwoord tonen">
        
        <svg data-icon="show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        
        <svg data-icon="hide" class="hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.7 8.4 2 12 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6" />
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18" />
        </svg>
    </button>
</div>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/auth/partials/password.blade.php ENDPATH**/ ?>