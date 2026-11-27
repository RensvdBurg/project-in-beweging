<!doctype html>
<?php
    // Instellingen uit de database (per gebruiker) voor public/js/app-settings.js.
    // Nog nooit opgeslagen? Dan niets meegeven, zodat localStorage blijft gelden.
    $layoutSettings = auth()->user()?->settings !== null ? auth()->user()->preferences() : null;
    $layoutWorkHours = $layoutSettings === null ? null : [
        'enabled' => $layoutSettings['workOnly'],
        'start' => $layoutSettings['workStart'],
        'end' => $layoutSettings['workEnd'],
        'days' => $layoutSettings['workDays'],
        'schedule' => $layoutSettings['workSchedule'],
    ];
?>
<html lang="nl"
    <?php if(auth()->guard()->check()): ?> data-user-id="<?php echo e(auth()->id()); ?>" <?php endif; ?>
    
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'upmove-dark' => $layoutSettings['darkMode'] ?? false,
        'learn-page-transition-back' => $learnTransitionBack ?? false,
    ]); ?>"
    <?php if($layoutSettings !== null): ?>
        data-dark-mode="<?php echo e($layoutSettings['darkMode'] ? 'true' : 'false'); ?>"
        data-work-hours='<?php echo json_encode($layoutWorkHours, 15, 512) ?>'
    <?php endif; ?>>
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta
        name="theme-color"
        content="<?php echo e(($layoutSettings['darkMode'] ?? false) ? '#151920' : '#f7f8fa'); ?>"
        id="theme-color">

    <title><?php echo $__env->yieldContent('title', 'UpMove'); ?></title>

    <!-- ========================================= -->
    <!-- GLOBALE INSTELLINGEN -->
    <!-- Dark mode + werk/school instellingen. -->
    <!-- Bewust geen module: moet vóór het tekenen draaien. -->
    <!-- ========================================= -->

    <script src="<?php echo e(asset('js/app-settings.js')); ?>"></script>


    <!-- ========================================= -->
    <!-- ICONEN -->
    <!-- ========================================= -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">


    <!-- ========================================= -->
    <!-- FONT (Montserrat, lokaal gebundeld door Vite) -->
    <!-- ========================================= -->

    <?php echo app('Illuminate\Foundation\Vite')->fonts(); ?>


    <!-- ========================================= -->
    <!-- TAILWIND + PAGINASCRIPTS -->
    <!-- ========================================= -->

    <?php echo app('Illuminate\Foundation\Vite')(array_merge(['resources/css/app.css'], $scripts ?? [])); ?>
</head>
<body <?php if(isset($bodyClass)): ?> class="<?php echo e($bodyClass); ?>" <?php endif; ?>>
    <?php echo $__env->yieldContent('body'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/layouts/app.blade.php ENDPATH**/ ?>