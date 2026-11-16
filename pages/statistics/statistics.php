<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Alleen ingelogde gebruikers mogen hun persoonlijke instellingen openen.
if (empty($_SESSION['user'])) {
    header('Location: /project-in-beweging/index.php', true, 303);
    exit;
}

$userId = $_SESSION['user']['id']
        ?? $_SESSION['user']['email']
        ?? $_SESSION['user']['full_name']
        ?? 'user';

?>


<!DOCTYPE html>
<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta
        name="theme-color"
        content="#f7f8fa"
        id="theme-color">

    <title>Statistieken | UpMove</title>


    <!-- Tailwind CSS -->
    <link
        rel="stylesheet"
        href="/project-in-beweging/css/output.css">
</head>
<body class="min-h-dvh bg-[#f7f8fa] text-[#20242a] antialiased">
    <div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">
        <header class="relative mb-6 mx-6 flex min-h-16 items-center justify-center">
            <h1 class="text-[18px] font-extrabold tracking-tight">
                Activiteitsstatistieken
            </h1>
            <button type="button"
                    class="settings-card absolute right-0 grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm transition active:scale-95"
                    aria-label="Meldingen">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke="currentColor"
                          stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
            </button>
        </header>
        <main class="px-6">
            <section class="py-3 flex gap-4 items-center justify-center">
                <!-- gemiddelde & reeks -->
                <div class="rounded-2xl bg-white p-4 grow">
                    <p class="text-[12px] text-[#9299a3]">Dagelijks gemiddelde</p>
                    <p class="text-[24px] font-bold">16.4 mins</p>
                    <p class="text-[12px]">12% t.o.v. vorige week</p>
                    <!-- Gemiddelde -->
                </div>
                <div class="rounded-2xl bg-white p-4 grow">
                    <p class="text-[12px] text-[#9299a3]">Langste Reeks</p>
                    <p class="text-[24px] font-bold">12 Dagen</p>
                    <p class="text-[12px]">Doel: 15 dagen</p>
                    <!-- Reeks -->
                </div>
            </section>
            <!-- Graph -->
            <section class="py-3">
                <div class="rounded-2xl bg-white p-5">
                    <p class="text-[16px] font-bold">Wekelijkse bewegingsminuten</p>
                    <div class="pt-5">
                        <!-- Graph-->
                    </div>
                </div>
            </section>
            <!-- Badges -->
            <section class="py-3">

                <p class="text-[16px] font-bold">Behaalde badges</p>

                <div class="flex-col py-3">
                    <div class="flex items-center p-3 bg-white rounded-2xl gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-[#fff0ec] flex items-center justify-center">
                            <svg color="#ff6b4a" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke="currentColor"
                                      stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="grow">
                            <p class="text-[14px] font-bold">Eerste Stap</p>
                            <p class="text-[12px] text-[#9299a3]">Eerste opsta-pauze</p>
                        </div>
                        <p class="text-[16px] text-[#2ec4b6] font-bold w-fit">Behaald</p>
                    </div>
                </div>

                <p class="text-[16px] font-bold">Vergrendelde badges</p>

                <div class="flex-col py-3">
                    <div class="flex items-center p-3 bg-white rounded-2xl gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-[#f9fafc] flex items-center justify-center">
                            <svg color="#9ca3af" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke="currentColor"
                                      stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="grow">
                            <p class="text-[14px] text-muted font-bold">Zitverslagen</p>
                            <p class="text-[12px] text-[#9299a3]">100 keer opgestaan</p>
                        </div>
                        <p class="text-[16px] text-muted font-bold w-fit">Vergendeld</p>
                    </div>
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
