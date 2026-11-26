<!doctype html>
@php
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
@endphp
<html lang="nl"
    @auth data-user-id="{{ auth()->id() }}" @endauth
    {{-- learn-page-transition-back: terug van een challengebeschrijving schuift de pagina van links in --}}
    @class([
        'upmove-dark' => $layoutSettings['darkMode'] ?? false,
        'learn-page-transition-back' => $learnTransitionBack ?? false,
    ])
    @if ($layoutSettings !== null)
        data-dark-mode="{{ $layoutSettings['darkMode'] ? 'true' : 'false' }}"
        data-work-hours='@json($layoutWorkHours)'
    @endif>
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta
        name="theme-color"
        content="{{ ($layoutSettings['darkMode'] ?? false) ? '#151920' : '#f7f8fa' }}"
        id="theme-color">

    <title>@yield('title', 'UpMove')</title>

    <!-- ========================================= -->
    <!-- GLOBALE INSTELLINGEN -->
    <!-- Dark mode + werk/school instellingen. -->
    <!-- Bewust geen module: moet vóór het tekenen draaien. -->
    <!-- ========================================= -->

    <script src="{{ asset('js/app-settings.js') }}"></script>


    <!-- ========================================= -->
    <!-- ICONEN -->
    <!-- ========================================= -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">


    <!-- ========================================= -->
    <!-- FONT (Montserrat, lokaal gebundeld door Vite) -->
    <!-- ========================================= -->

    @fonts


    <!-- ========================================= -->
    <!-- TAILWIND + PAGINASCRIPTS -->
    <!-- ========================================= -->

    @vite(array_merge(['resources/css/app.css'], $scripts ?? []))
</head>
<body @isset($bodyClass) class="{{ $bodyClass }}" @endisset>
    @yield('body')
</body>
</html>
