<?php

$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secureCookie,
    'samesite' => 'Lax',
]);
session_start();

$authMessage = $_SESSION['auth_message'] ?? '';
$authMessageType = $_SESSION['auth_message_type'] ?? 'error';
$activeAuthMode = $_SESSION['auth_mode'] ?? 'login';
$currentUser = $_SESSION['user'] ?? null;
unset($_SESSION['auth_message'], $_SESSION['auth_message_type'], $_SESSION['auth_mode']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$mode = $_POST['action'] ?? '';
if (!in_array($mode, ['login', 'register', 'logout'], true)) {
    http_response_code(400);
    exit('Ongeldige aanvraag.');
}

$redirectWithMessage = static function (string $message, string $type = 'error') use (&$mode): never {
    $_SESSION['auth_message'] = $message;
    $_SESSION['auth_message_type'] = $type;
    $_SESSION['auth_mode'] = $mode;
    header('Location: index.php', true, 303);
    exit;
};

$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
    $redirectWithMessage('Je sessie is verlopen. Probeer het opnieuw.');
}

if ($mode === 'logout') {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $mode = 'login';
    $redirectWithMessage('Je bent uitgelogd.', 'success');
}

require_once __DIR__ . '/../database/database.php';

$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
};

try {
    $db = getDatabaseConnection();

    if ($mode === 'register') {
        $email = trim($postString('email'));
        $username = trim($postString('username'));
        $fullName = trim($postString('fullName'));
        $password = $postString('password');
        $confirmPassword = $postString('confirmPassword');
        $day = filter_var($_POST['day'] ?? null, FILTER_VALIDATE_INT);
        $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $redirectWithMessage('Vul een geldig e-mailadres in.');
        }
        if (!preg_match('/\A[A-Za-z0-9_.-]{3,30}\z/', $username)) {
            $redirectWithMessage('Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).');
        }
        if ($fullName === '' || !preg_match('/\A.{1,150}\z/us', $fullName)) {
            $redirectWithMessage('Vul een naam van maximaal 150 tekens in.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $redirectWithMessage('Kies een wachtwoord van minimaal 8 en maximaal 72 bytes.');
        }
        if ($password !== $confirmPassword) {
            $redirectWithMessage('Wachtwoorden komen niet overeen.');
        }
        if (
            $day === false || $month === false || $year === false ||
            $year < (int) date('Y') - 110 ||
            !checkdate($month, $day, $year) ||
            sprintf('%04d-%02d-%02d', $year, $month, $day) > date('Y-m-d')
        ) {
            $redirectWithMessage('Kies een geldige geboortedatum.');
        }

        $birthDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $db->prepare(
            'INSERT INTO users (email, username, password_hash, full_name, birth_date)
             VALUES (:email, :username, :password_hash, :full_name, :birth_date)'
        );
        $statement->execute([
            'email' => $email,
            'username' => $username,
            'password_hash' => $passwordHash,
            'full_name' => $fullName,
            'birth_date' => $birthDate,
        ]);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $db->lastInsertId(),
            'username' => $username,
            'full_name' => $fullName,
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $mode = 'login';
        $redirectWithMessage('Je account is aangemaakt. Je bent nu ingelogd.', 'success');
    }

    $identifier = trim($postString('email'));
    $password = $postString('password');
    $statement = $db->prepare(
        'SELECT id, username, full_name, password_hash
         FROM users
         WHERE email = :email OR username = :username
         LIMIT 1'
    );
    $statement->execute([
        'email' => $identifier,
        'username' => $identifier,
    ]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $redirectWithMessage('E-mailadres/gebruikersnaam of wachtwoord is onjuist.');
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $redirectWithMessage('Je bent ingelogd als ' . $user['username'] . '.', 'success');
} catch (PDOException $exception) {
    if ($mode === 'register' && (int) ($exception->errorInfo[1] ?? 0) === 1062) {
        $redirectWithMessage('Dit e-mailadres of deze gebruikersnaam is al in gebruik.');
    }

    error_log('Authenticatie/databasefout: ' . $exception->getMessage());
    $redirectWithMessage('Er ging iets mis met de database. Controleer of de database is ingesteld en probeer het opnieuw.');
}
