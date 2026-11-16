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

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

$respond = static function (int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
};

if (empty($_SESSION['user']['id'])) {
    $respond(401, ['error' => 'Log in om te reageren op activiteiten.']);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../database/database.php';
require_once __DIR__ . '/../../database/activity-posts.php';

try {
    $db = getDatabaseConnection();
    ensureActivityPostsTable($db);
    $userId = (int) $_SESSION['user']['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
        if (!is_int($postId) || $postId < 1) {
            $respond(400, ['error' => 'Deze activiteit is ongeldig.']);
        }

        $postStatement = $db->prepare('SELECT 1 FROM activity_posts WHERE id = :id');
        $postStatement->execute(['id' => $postId]);
        if ($postStatement->fetchColumn() === false) {
            $respond(404, ['error' => 'Deze activiteit bestaat niet meer.']);
        }

        $commentsStatement = $db->prepare(
            'SELECT comments.body, comments.created_at,
                    COALESCE(users.full_name, users.username, "UpMove-gebruiker") AS author_name
             FROM (
                 SELECT id, user_id, body, created_at
                 FROM activity_post_comments
                 WHERE post_id = :post_id
                 ORDER BY created_at DESC, id DESC
                 LIMIT 100
             ) AS comments
             LEFT JOIN users ON users.id = comments.user_id
             ORDER BY comments.created_at ASC, comments.id ASC'
        );
        $commentsStatement->execute(['post_id' => $postId]);
        $comments = array_map(
            static fn(array $comment): array => [
                'author' => (string) $comment['author_name'],
                'body' => (string) $comment['body'],
                'createdAt' => date(DATE_ATOM, strtotime($comment['created_at'])),
            ],
            $commentsStatement->fetchAll()
        );
        $respond(200, ['comments' => $comments]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        $respond(405, ['error' => 'Deze aanvraagmethode wordt niet ondersteund.']);
    }

    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $respond(400, ['error' => 'Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.']);
    }

    try {
        $request = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $respond(400, ['error' => 'De aanvraag bevat ongeldige gegevens.']);
    }

    if (!is_array($request) || !is_int($request['postId'] ?? null) || $request['postId'] < 1) {
        $respond(400, ['error' => 'Deze activiteit is ongeldig.']);
    }

    $postId = $request['postId'];
    $postStatement = $db->prepare('SELECT 1 FROM activity_posts WHERE id = :id');
    $postStatement->execute(['id' => $postId]);
    if ($postStatement->fetchColumn() === false) {
        $respond(404, ['error' => 'Deze activiteit bestaat niet meer.']);
    }

    if (($request['action'] ?? '') === 'like') {
        $db->beginTransaction();
        $lockPost = $db->prepare('SELECT id FROM activity_posts WHERE id = :id FOR UPDATE');
        $lockPost->execute(['id' => $postId]);
        if ($lockPost->fetchColumn() === false) {
            $db->rollBack();
            $respond(404, ['error' => 'Deze activiteit bestaat niet meer.']);
        }

        $existingLike = $db->prepare(
            'SELECT 1 FROM activity_post_likes WHERE post_id = :post_id AND user_id = :user_id'
        );
        $existingLike->execute(['post_id' => $postId, 'user_id' => $userId]);
        $liked = $existingLike->fetchColumn() === false;

        if ($liked) {
            $changeLike = $db->prepare(
                'INSERT INTO activity_post_likes (post_id, user_id) VALUES (:post_id, :user_id)'
            );
        } else {
            $changeLike = $db->prepare(
                'DELETE FROM activity_post_likes WHERE post_id = :post_id AND user_id = :user_id'
            );
        }
        $changeLike->execute(['post_id' => $postId, 'user_id' => $userId]);

        $countLikes = $db->prepare('SELECT COUNT(*) FROM activity_post_likes WHERE post_id = :post_id');
        $countLikes->execute(['post_id' => $postId]);
        $likeCount = (int) $countLikes->fetchColumn();
        $db->commit();
        $respond(200, ['liked' => $liked, 'likeCount' => $likeCount]);
    }

    if (($request['action'] ?? '') === 'comment') {
        $body = $request['body'] ?? null;
        if (!is_string($body)) {
            $respond(400, ['error' => 'Schrijf eerst een reactie.']);
        }

        $body = trim($body);
        if (!preg_match('/\A.{1,1000}\z/us', $body)) {
            $respond(422, ['error' => 'Een reactie moet 1 tot 1000 tekens bevatten.']);
        }

        $insertComment = $db->prepare(
            'INSERT INTO activity_post_comments (post_id, user_id, body)
             VALUES (:post_id, :user_id, :body)'
        );
        $insertComment->execute([
            'post_id' => $postId,
            'user_id' => $userId,
            'body' => $body,
        ]);
        $countComments = $db->prepare(
            'SELECT COUNT(*) FROM activity_post_comments WHERE post_id = :post_id'
        );
        $countComments->execute(['post_id' => $postId]);
        $respond(201, ['commentCount' => (int) $countComments->fetchColumn()]);
    }

    $respond(400, ['error' => 'Deze activiteitactie wordt niet ondersteund.']);
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Activiteiten/interactiefout: ' . $exception->getMessage());
    $respond(500, ['error' => 'De interactie kon niet worden opgeslagen. Probeer het later opnieuw.']);
}
