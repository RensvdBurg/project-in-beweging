<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Alleen ingelogde gebruikers mogen hun persoonlijke instellingen openen.
if (empty($_SESSION['user'])) {
    header('Location: /project-in-beweging/index.php', true, 303);
    exit;
}

$pageTitle = 'Statistieken | UpMove';
$enableSettingsPageTransitions = true;

?>


<!DOCTYPE html>
<html lang="nl">

<head>
    <?php require __DIR__ . '/../../components/header.php'; ?>
</head>
<body class="min-h-dvh bg-app-bg font-sans text-ink antialiased">
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <main class="upmove-statistics flex-1 px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">
            <header class="mb-5 pt-2">
                <h1 class="text-xl font-extrabold tracking-tight">
                    Activiteitsstatistieken
                </h1>
                <p class="mt-1 text-sm text-muted">Bekijk je voortgang en behaalde badges.</p>
            </header>

            <!-- Tracker-hooks voor statistieken, grafiek en badges. -->
            <section class="flex gap-3 py-3" aria-label="Samenvatting">
                <div class="settings-card min-w-0 flex-1 rounded-2xl bg-surface p-4 shadow-card">
                    <p class="text-xs text-muted">Dagelijks gemiddelde</p>
                    <p class="mt-1 text-xl font-bold"><output data-stat="daily-average-minutes">—</output> min</p>
                    <p class="mt-1 text-xs text-muted" data-stat="weekly-change">Nog geen gegevens</p>
                </div>
                <div class="settings-card min-w-0 flex-1 rounded-2xl bg-surface p-4 shadow-card">
                    <p class="text-xs text-muted">Langste reeks</p>
                    <p class="mt-1 text-xl font-bold"><output data-stat="longest-streak-days">—</output> dagen</p>
                    <p class="mt-1 text-xs text-muted">
                        Doel: <output data-stat="streak-goal-days">—</output> dagen
                    </p>
                </div>
            </section>

            <section class="py-3" aria-labelledby="weekly-statistics-title">
                <div class="settings-card rounded-2xl bg-surface p-5 shadow-card">
                    <h2 id="weekly-statistics-title" class="text-base font-bold">Wekelijkse bewegingsminuten</h2>
                    <div class="mt-3 rounded-xl bg-app-bg p-4" data-chart="weekly-minutes">
                        <p class="text-sm text-muted" role="status">
                            Je weekoverzicht verschijnt hier zodra er beweeggegevens beschikbaar zijn.
                        </p>
                    </div>
                </div>
            </section>

            <section class="py-3" aria-labelledby="earned-badges-title">
                <h2 id="earned-badges-title" class="text-base font-bold">Behaalde badges</h2>

                <div class="mt-3 space-y-3" data-badge-list="earned">
                    <p class="rounded-xl bg-app-bg p-4 text-sm text-muted" role="status">
                        Je behaalde badges verschijnen hier zodra er trackergegevens zijn.
                    </p>
                </div>

                <h2 class="mt-6 text-base font-bold">Vergrendelde badges</h2>
                <div class="mt-3 space-y-3" data-badge-list="locked">
                    <p class="rounded-xl bg-app-bg p-4 text-sm text-muted" role="status">
                        Vergrendelde badges verschijnen hier zodra de tracker actief is.
                    </p>
                </div>
            </section>
        </main>
        <!-- ========================================= -->
        <!-- FOOTER -->
        <!-- ========================================= -->

        <?php

        require_once __DIR__ . '/../../components/footer.php';

        ?>
    </div>
</body>
</html>
