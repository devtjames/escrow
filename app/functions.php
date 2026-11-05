<?php
declare(strict_types=1);

function app_name(): string
{
    return (string) env_value('APP_NAME', 'FEDPOFFA Student Fee Portal');
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) env_value('APP_URL', ''), '/');
    return $base . '/' . ltrim(clean_url_path($path), '/');
}

function app_base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/student/', '/admin/', '/api/', '/webhooks/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            return rtrim(substr($script, 0, $pos), '/');
        }
    }
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $dir === '/' ? '' : $dir;
}

function clean_url_path(string $path): string
{
    [$main, $query] = array_pad(explode('?', $path, 2), 2, '');
    $main = preg_replace('/\.php$/i', '', $main) ?? $main;
    return $main . ($query !== '' ? '?' . $query : '');
}

function url_for(string $path): string
{
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    $clean = clean_url_path($path);
    if (str_starts_with($clean, '/')) {
        return app_base_path() . $clean;
    }
    return $clean;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money_naira(int|float|string $amount): string
{
    return 'NGN ' . number_format((float) $amount, 0);
}

function minimum_deposit_amount(): int
{
    return (int) env_value('MINIMUM_DEPOSIT_AMOUNT', 5000);
}

function amount_to_kobo(int|string $amount): int
{
    $normalized = preg_replace('/[^\d]/', '', (string) $amount);
    return ((int) $normalized) * 100;
}

function kobo_to_amount(int|string $kobo): int
{
    return intdiv((int) $kobo, 100);
}

function redirect_to(string $path): never
{
    header('Location: ' . url_for($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function request_expects_json(): bool
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
    return str_contains($uri, '/api/')
        || str_contains($accept, 'application/json')
        || str_contains($contentType, 'application/json')
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if (request_expects_json()) {
            json_response(['ok' => false, 'message' => 'Invalid session token. Refresh the page and try again.'], 419);
        }
        render_error_page(
            419,
            'Invalid Session Token',
            'Your form session has expired or could not be verified. Refresh the page and try again.',
            [
                ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
            ]
        );
    }
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_all(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function field(string $key, mixed $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $_GET[$key] ?? $default));
}

function nullable_field(string $key): ?string
{
    $value = field($key);
    return $value === '' ? null : $value;
}

function normalize_full_name(string $name): string
{
    $name = preg_replace('/\s+/', ' ', trim($name)) ?? trim($name);
    return function_exists('mb_strtoupper') ? mb_strtoupper($name, 'UTF-8') : strtoupper($name);
}

function full_name_has_number(string $name): bool
{
    return preg_match('/\d/', $name) === 1;
}

function normalized_contact_email(string $email): string
{
    $email = trim($email);
    return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
}

function normalized_phone_number(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    // Treat the common Nigerian local and international forms as the same number.
    if (str_starts_with($digits, '0') && strlen($digits) === 11) {
        $digits = '234' . substr($digits, 1);
    }
    return $digits;
}

function validate_next_of_kin_details(string $fullName, string $email, string $phone, string $relationship, string $studentEmail, string $studentPhone): ?string
{
    if ($fullName === '' || full_name_has_number($fullName)) {
        return 'Next of kin full name cannot be empty or contain numbers.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid next of kin email address.';
    }
    $normalizedPhone = normalized_phone_number($phone);
    if (strlen($normalizedPhone) < 7) {
        return 'Enter a valid next of kin phone number.';
    }
    if (trim($relationship) === '') {
        return 'Select or enter the next of kin relationship.';
    }
    if (normalized_contact_email($email) === normalized_contact_email($studentEmail)) {
        return 'Next of kin email address must be different from the student email address.';
    }
    if ($normalizedPhone === normalized_phone_number($studentPhone)) {
        return 'Next of kin phone number must be different from the student phone number.';
    }
    return null;
}

function password_is_strong(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password);
}

function client_ip(): string
{
    return substr((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 64);
}

function user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
}

function make_reference(string $prefix = 'FPO'): string
{
    return $prefix . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function post_request(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function setting_get(string $key, mixed $default = null): mixed
{
    try {
        $row = db_one('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return $row['setting_value'] ?? $default;
    } catch (Throwable) {
        return $default;
    }
}

function setting_set(string $key, string $value): void
{
    db_exec(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

function log_activity(?int $adminId, ?int $studentId, string $action, array $context = []): void
{
    db_exec(
        'INSERT INTO activity_logs (admin_id, student_id, action, context_json, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$adminId, $studentId, $action, json_encode($context), client_ip(), user_agent()]
    );
}
