
<?php
    $isSuccess = $type === 'success';
?>
<div
    id="<?php echo e($id); ?>"
    role="<?php echo e($isSuccess ? 'status' : 'alert'); ?>"
    aria-live="<?php echo e($isSuccess ? 'polite' : 'assertive'); ?>"
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'auth-message flex items-start gap-3 rounded-[14px] border px-3.5 py-3 text-left',
        'border-[#b8ddd4] bg-[#edf7f4] text-[#17685b]' => $isSuccess,
        'border-[#f2c9c7] bg-[#fff3f2] text-[#b4231a]' => ! $isSuccess,
    ]); ?>">
    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-extrabold text-white',
        'bg-[#17685b]' => $isSuccess,
        'bg-[#d6453b]' => ! $isSuccess,
    ]); ?>" aria-hidden="true"><?php echo e($isSuccess ? '✓' : '!'); ?></span>
    <div class="min-w-0">
        <p class="text-[13px] font-bold"><?php echo e($title); ?></p>
        <p class="mt-0.5 text-[12px] leading-relaxed"><?php echo e($message); ?></p>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/auth/partials/message.blade.php ENDPATH**/ ?>