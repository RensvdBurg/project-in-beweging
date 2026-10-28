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

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Je moet ingelogd zijn om instellingen te bekijken.']);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../database/database.php';
require_once __DIR__ . '/../../database/user-settings.php';

try {
    $db = getDatabaseConnection();
    $userId = (int) $_SESSION['user']['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(getUserSettings($db, $userId), JSON_THROW_ON_ERROR);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        http_response_code(405);
        echo json_encode(['error' => 'Deze aanvraagmethode wordt niet ondersteund.']);
        exit;
    }

    $request = json_decode(file_get_contents('php://input'), true);
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (
        !is_array($request) ||
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.']);
        exit;
    }

    if (!isset($request['settings']) || !is_array($request['settings'])) {
        http_response_code(400);
        echo json_encode(['error' => 'De instellingen zijn ongeldig.']);
        exit;
    }

    $settings = saveUserSettings($db, $userId, $request['settings']);
    echo json_encode(['settings' => $settings], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('Instellingen/databasefout: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Je instellingen konden niet in de database worden opgeslagen. Probeer het later opnieuw.']);
}
