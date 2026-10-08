<?php

$cssFile = __DIR__ . '/../css/output.css';

$cssVersion = file_exists($cssFile)
    ? filemtime($cssFile)
    : time();

?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover">

<meta
    name="theme-color"
    content="#f7f8fa"
    id="theme-color">

<title>In beweging</title>


<!-- ========================================= -->
<!-- GLOBALE INSTELLINGEN -->
<!-- Dark mode + werk/school instellingen -->
<!-- ========================================= -->

<script src="/project-in-beweging/assets/js/app-settings.js"></script>


<!-- ========================================= -->
<!-- ICONEN -->
<!-- ========================================= -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">


<!-- ========================================= -->
<!-- TAILWIND -->
<!-- Cache wordt automatisch vernieuwd -->
<!-- ========================================= -->

<link
    rel="stylesheet"
    href="/project-in-beweging/css/output.css?v=<?= $cssVersion ?>">


<!-- ========================================= -->
<!-- FONT -->
<!-- ========================================= -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap"
    rel="stylesheet">