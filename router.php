<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($uri));

if (preg_match('/\.php$/i', $uri)) {
    $clean = preg_replace('/\.php$/i', '', $uri);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $clean . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

if (is_file($path)) {
    return false;
}

if (is_dir($path)) {
    $indexPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    if (is_file($indexPath)) {
        require $indexPath;
        return true;
    }
    return false;
}

$phpPath = rtrim($path, DIRECTORY_SEPARATOR) . '.php';
if (is_file($phpPath)) {
    require $phpPath;
    return true;
}

$htmlPath = rtrim($path, DIRECTORY_SEPARATOR) . '.html';
if (is_file($htmlPath)) {
    return false;
}

if (pathinfo($uri, PATHINFO_EXTENSION) !== '') {
    return false;
}

require __DIR__ . '/app/bootstrap.php';
render_error_page(
    404,
    'Page Not Found',
    'The page you requested does not exist or may have been moved.',
    [
        ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
    ]
);
