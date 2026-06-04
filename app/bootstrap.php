<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
} else {
    $envFile = BASE_PATH . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}

date_default_timezone_set('Africa/Lagos');

function env_value(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

$appDebug = filter_var(env_value('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
error_reporting(E_ALL);

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (PHP_SAPI !== 'cli' && ob_get_level() === 0) {
    ob_start();
}

require_once BASE_PATH . '/app/functions.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/mailer.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/academic.php';
require_once BASE_PATH . '/app/paystack.php';
require_once BASE_PATH . '/app/views.php';

set_exception_handler(static function (Throwable $exception): void {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Application error: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (function_exists('request_expects_json') && request_expects_json()) {
        json_response(['ok' => false, 'message' => 'A server error occurred. Please try again later.'], 500);
    }

    render_error_page(
        500,
        'Server Error',
        'Something went wrong while processing this request. Please try again later.',
        [
            ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
        ]
    );
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Fatal application error: ' . $error['message'] . PHP_EOL);
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (function_exists('request_expects_json') && request_expects_json()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'A server error occurred. Please try again later.']);
        return;
    }

    if (function_exists('render_error_page')) {
        render_error_page(
            500,
            'Server Error',
            'Something went wrong while processing this request. Please try again later.',
            [
                ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
            ]
        );
    }
});

ensure_academic_schema_updates();
seed_super_admin();
close_expired_sessions();
close_inactive_open_sessions();
