<?php

$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!is_array($currentUser) || empty($currentUser['id'])) {
    header('Location: /project-in-beweging/', true, 303);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../database/database.php';
require_once __DIR__ . '/../../database/activity-posts.php';

$userId = (int) $currentUser['id'];
$pageTitle = 'Activiteiten | In beweging';
$activityError = '';
$title = '';
$description = '';
$activities = [];
$activityPageSize = 5;
$requestedActivityPage = filter_input(
    INPUT_GET,
    'activity_page',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$activityPage = is_int($requestedActivityPage) ? $requestedActivityPage : 1;
$activityPageCount = 1;
$activityLoadError = false;
$activityNotice = is_string($_SESSION['activity_notice'] ?? null)
    ? $_SESSION['activity_notice']
    : '';
unset($_SESSION['activity_notice']);
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $activityError = 'Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.';
    } else {
        $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
        $description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');

        if ($title === '' || !preg_match('/\A.{1,120}\z/us', $title)) {
            $activityError = 'Vul een titel in van maximaal 120 tekens.';
        } elseif ($description === '' || !preg_match('/\A.{1,2000}\z/us', $description)) {
            $activityError = 'Vul een beschrijving in van maximaal 2000 tekens.';
        } elseif (
            !isset($_FILES['photo'])
            || !is_array($_FILES['photo'])
            || !isset($_FILES['photo']['error'])
            || !is_int($_FILES['photo']['error'])
        ) {
            $activityError = 'Kies een afbeelding om te uploaden.';
        } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $activityError = match ($_FILES['photo']['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'De afbeelding is te groot. De maximale bestandsgrootte is 5 MB.',
                UPLOAD_ERR_NO_FILE => 'Kies een afbeelding om te uploaden.',
                default => 'De afbeelding kon niet worden geüpload. Probeer het opnieuw.',
            };
        } elseif (
            !isset($_FILES['photo']['tmp_name'], $_FILES['photo']['size'])
            || !is_string($_FILES['photo']['tmp_name'])
            || !is_int($_FILES['photo']['size'])
            || $_FILES['photo']['size'] < 1
            || $_FILES['photo']['size'] > 5 * 1024 * 1024
            || !is_uploaded_file($_FILES['photo']['tmp_name'])
        ) {
            $activityError = 'Kies een geldige afbeelding van maximaal 5 MB.';
        } else {
            $imageInfo = @getimagesize($_FILES['photo']['tmp_name']);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['photo']['tmp_name']);
            $allowedImages = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (
                $imageInfo === false
                || !is_string($mimeType)
                || !isset($allowedImages[$mimeType])
                || ($imageInfo['mime'] ?? '') !== $mimeType
            ) {
                $activityError = 'Gebruik een geldige JPG-, PNG- of WebP-afbeelding.';
            } else {
                $uploadDirectory = getenv('ACTIVITY_UPLOAD_DIR') ?: __DIR__ . '/../../uploads/activiteiten';
                $savedFilename = bin2hex(random_bytes(16)) . '.' . $allowedImages[$mimeType];
                $savedImagePath = $uploadDirectory . '/' . $savedFilename;
                $imageMoved = false;

                try {
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                        throw new RuntimeException('Uploadmap kon niet worden aangemaakt.');
                    }

                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $savedImagePath)) {
                        throw new RuntimeException('Afbeelding kon niet naar de uploadmap worden verplaatst.');
                    }
                    $imageMoved = true;

                    $db = getDatabaseConnection();
                    ensureActivityPostsTable($db);

                    $statement = $db->prepare(
                        'INSERT INTO activity_posts (user_id, title, description, image_filename)
                         VALUES (:user_id, :title, :description, :image_filename)'
                    );
                    $statement->execute([
                        'user_id' => $userId,
                        'title' => $title,
                        'description' => $description,
                        'image_filename' => $savedFilename,
                    ]);

                    $_SESSION['activity_notice'] = 'Je activiteit is toegevoegd.';
                    header('Location: /project-in-beweging/pages/activiteit/activiteit.php', true, 303);
                    exit;
                } catch (Throwable $exception) {
                    if ($imageMoved && is_file($savedImagePath) && !unlink($savedImagePath)) {
                        error_log('Activiteiten/uploadfout: tijdelijke afbeelding kon niet worden verwijderd.');
                    }
                    error_log('Activiteiten/opslaan mislukt: ' . $exception->getMessage());
                    $activityError = 'Je activiteit kon niet worden opgeslagen. Probeer het later opnieuw.';
                }
            }
        }
    }
}

try {
    $db = getDatabaseConnection();
    ensureActivityPostsTable($db);
    $countStatement = $db->prepare(
        'SELECT COUNT(*) FROM activity_posts WHERE user_id = :user_id'
    );
    $countStatement->execute(['user_id' => $userId]);
    $activityCount = (int) $countStatement->fetchColumn();
    $activityPageCount = max(1, (int) ceil($activityCount / $activityPageSize));
    $activityPage = min($activityPage, $activityPageCount);
    $activityOffset = ($activityPage - 1) * $activityPageSize;

    $statement = $db->prepare(
        'SELECT title, description, image_filename, created_at
         FROM activity_posts
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT :limit OFFSET :offset'
    );
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $activityPageSize, PDO::PARAM_INT);
    $statement->bindValue(':offset', $activityOffset, PDO::PARAM_INT);
    $statement->execute();
    $activities = $statement->fetchAll();
} catch (Throwable $exception) {
    error_log('Activiteiten/databasefout: ' . $exception->getMessage());
    $activityLoadError = true;
}

?>
<!doctype html>
<html lang="nl">
<head>
    <?php require __DIR__ . '/../../components/header.php'; ?>
</head>
<body>
    <main class="mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6">
        <header class="mb-5 pt-2">
            <p class="mb-1 text-sm font-bold text-orange">Jouw voortgang</p>
            <h1 class="text-2xl font-extrabold tracking-tight">Activiteiten</h1>
            <p class="mt-1 text-sm text-muted">Deel wat je hebt gedaan, van een hardlooprondje tot een nieuwe sportieve mijlpaal.</p>
        </header>

        <?php if ($activityNotice !== ''): ?>
            <p class="mb-4 rounded-[18px] bg-teal-soft p-4 text-sm font-semibold text-ink" role="status">
                <?= $escape($activityNotice) ?>
            </p>
        <?php endif; ?>

        <?php if ($activityError !== ''): ?>
            <p class="mb-4 rounded-[18px] bg-alert-bg p-4 text-sm font-semibold text-ink" role="alert">
                <?= $escape($activityError) ?>
            </p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="mb-6 space-y-4 rounded-[22px] bg-surface p-4 shadow-card sm:p-5">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">

            <div>
                <label for="activity-title" class="mb-1.5 block text-sm font-bold">Titel</label>
                <input
                    id="activity-title"
                    name="title"
                    type="text"
                    maxlength="120"
                    required
                    value="<?= $escape($title) ?>"
                    placeholder="Bijvoorbeeld: 5 km hardgelopen"
                    class="w-full rounded-[14px] border border-[#eceef2] bg-surface px-4 py-3 text-sm outline-none transition placeholder:text-muted focus-visible:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
            </div>

            <div>
                <label for="activity-description" class="mb-1.5 block text-sm font-bold">Beschrijving</label>
                <textarea
                    id="activity-description"
                    name="description"
                    rows="4"
                    maxlength="2000"
                    required
                    placeholder="Vertel wat je hebt gedaan en hoe het ging..."
                    class="w-full resize-y rounded-[14px] border border-[#eceef2] bg-surface px-4 py-3 text-sm outline-none transition placeholder:text-muted focus-visible:border-orange focus-visible:ring-2 focus-visible:ring-orange/30"><?= $escape($description) ?></textarea>
            </div>

            <div>
                <label for="activity-photo" class="mb-1.5 block text-sm font-bold">Afbeelding</label>
                <input
                    id="activity-photo"
                    name="photo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    class="block w-full cursor-pointer rounded-[14px] border border-[#eceef2] bg-surface text-sm text-muted file:mr-3 file:cursor-pointer file:rounded-l-[13px] file:border-0 file:bg-orange-soft file:px-4 file:py-3 file:font-bold file:text-orange hover:file:brightness-95">
                <p class="mt-1.5 text-xs text-muted">JPG, PNG of WebP · maximaal 5 MB</p>
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-orange px-5 py-3 text-sm font-extrabold text-white shadow-[0_6px_16px_rgba(255,107,74,0.28)] transition hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange active:scale-[0.99]">
                Activiteit delen
            </button>
        </form>

        <section aria-labelledby="activity-list-title">
            <h2 id="activity-list-title" class="mb-3 text-lg font-extrabold tracking-tight">Jouw activiteiten</h2>

            <?php if ($activityLoadError): ?>
                <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
                    Je activiteiten konden niet worden geladen. Probeer het later opnieuw.
                </p>
            <?php elseif ($activities === []): ?>
                <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
                    Je hebt nog geen activiteiten gedeeld. Voeg hierboven je eerste activiteit toe.
                </p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($activities as $activity): ?>
                        <li>
                            <article class="overflow-hidden rounded-[20px] bg-surface shadow-card">
                                <img
                                    src="/project-in-beweging/uploads/activiteiten/<?= rawurlencode($activity['image_filename']) ?>"
                                    alt="<?= $escape($activity['title']) ?>"
                                    class="max-h-80 w-full object-cover"
                                    loading="lazy">
                                <div class="p-4">
                                    <h3 class="text-base font-extrabold tracking-tight"><?= $escape($activity['title']) ?></h3>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted"><?= $escape($activity['description']) ?></p>
                                    <time class="mt-3 block text-xs font-medium text-muted" datetime="<?= $escape(date(DATE_ATOM, strtotime($activity['created_at']))) ?>">
                                        <?= $escape(date('d-m-Y H:i', strtotime($activity['created_at']))) ?>
                                    </time>
                                </div>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($activityPageCount > 1): ?>
                    <nav class="mt-5 flex flex-wrap justify-center gap-2" aria-label="Pagina's met jouw activiteiten">
                        <?php for ($page = 1; $page <= $activityPageCount; $page++): ?>
                            <a
                                href="/project-in-beweging/pages/activiteit/activiteit.php?activity_page=<?= $page ?>#activity-list-title"
                                class="activity-feed-page-link grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange <?= $page === $activityPage ? 'bg-orange text-white' : 'bg-surface text-ink shadow-card hover:bg-orange-soft' ?>"
                                <?= $page === $activityPage ? 'aria-current="page"' : '' ?>>
                                <?= $page ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php require __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>
