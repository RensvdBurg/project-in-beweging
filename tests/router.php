<?php
// Test-only router: mirror the XAMPP mount without serving sibling projects.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = preg_replace('#^/project-in-beweging(?=/|$)#', '', $path);
$root = realpath(__DIR__ . '/..');
$file = realpath($root . ($path === '' || $path === '/' ? '/index.php' : $path));
if (!$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)
    || preg_match('#^/(?:tests|database|scripts|work|node_modules|\.git)/#', $path)) {
    http_response_code(404); exit;
}
if (str_ends_with($file, '.php')) {
    $_SERVER['SCRIPT_FILENAME'] = $file;
    require $file;
} else {
    $type = ['css' => 'text/css', 'js' => 'text/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg'];
    header('Content-Type: ' . ($type[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($file);
}
