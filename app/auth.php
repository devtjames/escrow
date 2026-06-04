<?php
declare(strict_types=1);

function seed_super_admin(): void
{
    try {
        $count = db_one('SELECT COUNT(*) AS total FROM admins')['total'] ?? 0;
    } catch (Throwable) {
        return;
    }

    if ((int) $count > 0) {
        return;
    }

    $password = (string) env_value('SUPER_ADMIN_PASSWORD', 'ChangeMe123!');
    db_exec(
        'INSERT INTO admins (full_name, staff_id, email, password_hash, role, status)
         VALUES (?, ?, ?, ?, ?, ?)',
        [
            env_value('SUPER_ADMIN_NAME', 'System Administrator'),
            env_value('SUPER_ADMIN_STAFF_ID', 'FPO/FIN/001'),
            env_value('SUPER_ADMIN_EMAIL', 'admin@example.com'),
            password_hash($password, PASSWORD_BCRYPT),
            'super_admin',
            'active',
        ]
    );
}

function current_student(): ?array
{
    if (empty($_SESSION['student_id'])) {
        return null;
    }
    return db_one(
        'SELECT s.*, f.name AS faculty_name, d.name AS department_name, ss.name AS admission_session_name
         FROM students s
         LEFT JOIN faculties f ON f.id = s.faculty_id
         LEFT JOIN departments d ON d.id = s.department_id
         LEFT JOIN sessions ss ON ss.id = s.admission_session_id
         WHERE s.id = ? AND s.status = "active"',
        [(int) $_SESSION['student_id']]
    );
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    return db_one('SELECT * FROM admins WHERE id = ? AND status = "active"', [(int) $_SESSION['admin_id']]);
}

function require_student(): array
{
    $student = current_student();
    if (!$student) {
        redirect_to('/student/login');
    }
    return $student;
}

function require_admin(array $roles = []): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect_to('/admin/login');
    }
    if ($roles && !in_array($admin['role'], $roles, true)) {
        render_error_page(
            403,
            'Access Not Allowed',
            'Your admin account does not have permission to open this page.',
            [
                ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
            ]
        );
    }
    return $admin;
}

function too_many_login_attempts(string $scope, string $identifier): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS total FROM login_attempts
         WHERE scope = ? AND identifier = ? AND ip_address = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
        [$scope, mb_strtolower($identifier), client_ip()]
    );
    return (int) ($row['total'] ?? 0) >= 5;
}

function record_login_attempt(string $scope, string $identifier, bool $successful): void
{
    db_exec(
        'INSERT INTO login_attempts (scope, identifier, ip_address, successful) VALUES (?, ?, ?, ?)',
        [$scope, mb_strtolower($identifier), client_ip(), $successful ? 1 : 0]
    );
}

function clear_login_attempts(string $scope, string $identifier): void
{
    db_exec(
        'DELETE FROM login_attempts WHERE scope = ? AND identifier = ? AND ip_address = ?',
        [$scope, mb_strtolower($identifier), client_ip()]
    );
}

function student_login(string $identifier, string $password): bool
{
    if (too_many_login_attempts('student', $identifier)) {
        return false;
    }

    $student = db_one(
        'SELECT * FROM students
         WHERE email = ? OR application_number = ? OR matric_number = ?
         LIMIT 1',
        [$identifier, $identifier, $identifier]
    );

    $valid = $student && password_verify($password, $student['password_hash']) && $student['status'] === 'active';
    record_login_attempt('student', $identifier, (bool) $valid);

    if (!$valid) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['student_id'] = (int) $student['id'];
    unset($_SESSION['admin_id']);
    clear_login_attempts('student', $identifier);
    ensure_student_wallet((int) $student['id']);
    log_activity(null, (int) $student['id'], 'student_login');
    return true;
}

function admin_login(string $identifier, string $password): bool
{
    if (too_many_login_attempts('admin', $identifier)) {
        return false;
    }

    $admin = db_one('SELECT * FROM admins WHERE email = ? OR staff_id = ? LIMIT 1', [$identifier, $identifier]);
    $valid = $admin && password_verify($password, $admin['password_hash']) && $admin['status'] === 'active';
    record_login_attempt('admin', $identifier, (bool) $valid);

    if (!$valid) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    unset($_SESSION['student_id']);
    clear_login_attempts('admin', $identifier);
    log_activity((int) $admin['id'], null, 'admin_login');
    return true;
}

function logout_all(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
