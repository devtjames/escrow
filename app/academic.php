<?php
declare(strict_types=1);

function active_session(): ?array
{
    return db_one('SELECT * FROM sessions WHERE is_active = 1 AND status = "open" LIMIT 1');
}

function sessions_all(): array
{
    close_expired_sessions();
    close_inactive_open_sessions();
    return db_all('SELECT * FROM sessions ORDER BY name DESC');
}

function session_start_year(string $name): int
{
    if (preg_match('/^(\d{4})\/\d{4}$/', $name, $match)) {
        return (int) $match[1];
    }
    return (int) date('Y');
}

function student_session_year(array $student, ?array $session = null): int
{
    $session ??= active_session();
    if (!$session || empty($student['admission_session_name'])) {
        return 1;
    }
    return max(1, session_start_year($session['name']) - session_start_year((string) $student['admission_session_name']) + 1);
}

function ensure_academic_schema_updates(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $column = db_one("SHOW COLUMNS FROM students LIKE 'entry_level'");
        if (!$column) {
            db()->exec("ALTER TABLE students ADD entry_level ENUM('ND1','ND2','HND1','HND2') NOT NULL DEFAULT 'ND1' AFTER programme_type");
            db()->exec("UPDATE students SET entry_level = CONCAT(programme_type, '1')");
        }
        $studentColumns = [
            'next_of_kin_full_name' => 'VARCHAR(160) NULL AFTER phone',
            'next_of_kin_email' => 'VARCHAR(190) NULL AFTER next_of_kin_full_name',
            'next_of_kin_phone' => 'VARCHAR(40) NULL AFTER next_of_kin_email',
            'next_of_kin_relationship' => 'VARCHAR(100) NULL AFTER next_of_kin_phone',
        ];
        foreach ($studentColumns as $name => $definition) {
            $column = db_one("SHOW COLUMNS FROM students LIKE '" . $name . "'");
            if (!$column) {
                db()->exec('ALTER TABLE students ADD ' . $name . ' ' . $definition);
            }
        }
        db()->exec(
            "UPDATE students
             SET entry_level = CONCAT(programme_type, RIGHT(entry_level, 1))
             WHERE (programme_type = 'ND' AND entry_level NOT LIKE 'ND%')
                OR (programme_type = 'HND' AND entry_level NOT LIKE 'HND%')"
        );
        $column = db_one("SHOW COLUMNS FROM fees LIKE 'fee_level'");
        if (!$column) {
            db()->exec("ALTER TABLE fees ADD fee_level ENUM('ND1','ND2','HND1','HND2') NULL AFTER study_mode");
        }
        $column = db_one("SHOW COLUMNS FROM departments LIKE 'hod_name'");
        if (!$column) {
            db()->exec("ALTER TABLE departments ADD hod_name VARCHAR(160) NULL AFTER name");
        }
    } catch (Throwable) {
    }
}

function level_programme_type(string $level): string
{
    return str_starts_with(strtoupper($level), 'HND') ? 'HND' : 'ND';
}

function level_year(string $level): int
{
    return (int) substr(strtoupper($level), -1);
}

function validate_entry_level(string $programmeType, string $entryLevel): bool
{
    $programmeType = strtoupper($programmeType);
    $entryLevel = strtoupper($entryLevel);
    return in_array($entryLevel, ['ND1', 'ND2', 'HND1', 'HND2'], true)
        && level_programme_type($entryLevel) === $programmeType;
}

function department_supports_programme(int $departmentId, int $facultyId, string $programmeType): bool
{
    $programmeType = strtoupper($programmeType);
    if (!in_array($programmeType, ['ND', 'HND'], true)) {
        return false;
    }
    $department = db_one(
        'SELECT award_type FROM departments WHERE id = ? AND faculty_id = ? AND status = "active" LIMIT 1',
        [$departmentId, $facultyId]
    );
    if (!$department) {
        return false;
    }
    return (string) $department['award_type'] === 'HND/ND' || (string) $department['award_type'] === $programmeType;
}

function student_current_level_code(array $student, ?array $session = null): string
{
    $entryLevel = strtoupper((string) ($student['entry_level'] ?? (($student['programme_type'] ?? 'ND') . '1')));
    $year = level_year($entryLevel) + student_session_year($student, $session) - 1;
    if ($year > 2) {
        return 'Alumni';
    }
    return level_programme_type($entryLevel) . $year;
}

function student_category(array $student, ?array $session = null): string
{
    $level = student_current_level_code($student, $session);
    if ($level === 'Alumni') {
        return 'Alumni';
    }
    return str_ends_with($level, '1') ? 'Fresher' : 'Staylite';
}

function student_level(array $student, ?array $session = null): string
{
    $level = student_current_level_code($student, $session);
    return $level === 'Alumni' ? 'Alumni' : $level . ' ' . $student['study_mode'];
}

function validate_programme(string $programmeType, string $studyMode): bool
{
    $programmeType = strtoupper($programmeType);
    $studyMode = strtoupper($studyMode);
    return in_array($programmeType, ['ND', 'HND'], true)
        && in_array($studyMode, ['FT', 'PT'], true)
        && !($programmeType === 'HND' && $studyMode === 'PT');
}

function close_expired_sessions(): void
{
    try {
        db_exec(
            'UPDATE sessions
             SET is_active = 0, status = "closed"
             WHERE status = "open" AND end_date IS NOT NULL AND end_date < CURDATE()'
        );
    } catch (Throwable) {
    }
}

function close_inactive_open_sessions(): void
{
    try {
        db_exec('UPDATE sessions SET status = "closed" WHERE is_active = 0 AND status = "open"');
    } catch (Throwable) {
    }
}

function prepare_session_for_payments(int $sessionId, bool $copyFeesFromPrevious = true): void
{
    if ($copyFeesFromPrevious && !session_has_fees($sessionId)) {
        $sourceSessionId = previous_fee_session_id($sessionId);
        if ($sourceSessionId) {
            clone_session_fees($sourceSessionId, $sessionId);
        }
    }
    prepare_wallets_for_session($sessionId);
}

function session_has_fees(int $sessionId): bool
{
    $row = db_one('SELECT COUNT(*) AS total FROM fees WHERE session_id = ?', [$sessionId]);
    return (int) ($row['total'] ?? 0) > 0;
}

function previous_fee_session_id(int $targetSessionId): ?int
{
    $target = db_one('SELECT name FROM sessions WHERE id = ?', [$targetSessionId]);
    if (!$target) {
        return null;
    }
    $targetYear = session_start_year((string) $target['name']);
    $rows = db_all(
        'SELECT DISTINCT s.id, s.name
         FROM sessions s
         JOIN fees f ON f.session_id = s.id
         WHERE s.id <> ?
         ORDER BY s.name DESC',
        [$targetSessionId]
    );
    foreach ($rows as $row) {
        if (session_start_year((string) $row['name']) < $targetYear) {
            return (int) $row['id'];
        }
    }
    return $rows ? (int) $rows[0]['id'] : null;
}

function clone_session_fees(int $fromSessionId, int $toSessionId): int
{
    if ($fromSessionId === $toSessionId || session_has_fees($toSessionId)) {
        return 0;
    }
    return db_exec(
        'INSERT INTO fees (title, fee_type, amount, faculty_id, department_id, session_id, programme_type, study_mode, fee_level, student_category, status)
         SELECT title, fee_type, amount, faculty_id, department_id, ?, programme_type, study_mode, fee_level, student_category, status
         FROM fees
         WHERE session_id = ? AND status <> "disabled"',
        [$toSessionId, $fromSessionId]
    );
}

function prepare_wallets_for_session(int $sessionId): void
{
    $session = db_one('SELECT * FROM sessions WHERE id = ?', [$sessionId]);
    if (!$session) {
        return;
    }

    $students = db_all(
        'SELECT s.*, ss.name AS admission_session_name
         FROM students s
         LEFT JOIN sessions ss ON ss.id = s.admission_session_id
         WHERE s.status = "active"'
    );

    foreach ($students as $student) {
        $target = calculate_student_target($student, $session);
        $wallet = db_one('SELECT * FROM wallets WHERE student_id = ? AND session_id = ?', [(int) $student['id'], $sessionId]);
        if (!$wallet) {
            db_exec(
                'INSERT INTO wallets (student_id, session_id, target_amount, current_balance, status)
                 VALUES (?, ?, ?, 0, ?)',
                [(int) $student['id'], $sessionId, $target, $target > 0 ? 'active' : 'completed']
            );
            continue;
        }

        $status = 'active';
        if ($target <= 0 || (int) $wallet['current_balance'] >= $target) {
            $status = 'completed';
        }
        if ($wallet['status'] === 'paid') {
            $status = 'paid';
        }
        db_exec('UPDATE wallets SET target_amount = ?, status = ? WHERE id = ?', [$target, $status, (int) $wallet['id']]);
    }
}

function activate_session(int $sessionId, bool $copyFeesFromPrevious = true): void
{
    db()->beginTransaction();
    try {
        $session = db_one('SELECT * FROM sessions WHERE id = ?', [$sessionId]);
        if (!$session) {
            throw new RuntimeException('Session not found.');
        }
        if (!empty($session['end_date']) && strtotime((string) $session['end_date']) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('Cannot activate an expired session. Update the end date first.');
        }

        db_exec('UPDATE sessions SET is_active = 0, status = "closed" WHERE id <> ? AND status <> "archived"', [$sessionId]);

        db_exec('UPDATE sessions SET is_active = 1, status = "open" WHERE id = ?', [$sessionId]);
        db()->commit();
        prepare_session_for_payments($sessionId, $copyFeesFromPrevious);
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function active_school_fee(array $student, ?array $session = null): ?array
{
    $session ??= active_session();
    if (!$session) {
        return null;
    }
    if (student_category($student, $session) === 'Alumni') {
        return null;
    }

    return db_one(
        'SELECT * FROM fees
         WHERE fee_type = "school_fee"
           AND status = "active"
           AND session_id = ?
           AND (faculty_id IS NULL OR faculty_id = ?)
           AND department_id IS NULL
           AND (programme_type IS NULL OR programme_type = ?)
           AND (study_mode IS NULL OR study_mode = ?)
           AND fee_level IS NULL
           AND student_category = ?
         ORDER BY faculty_id DESC
         LIMIT 1',
        [
            (int) $session['id'],
            (int) $student['faculty_id'],
            $student['programme_type'],
            $student['study_mode'],
            student_category($student, $session),
        ]
    );
}

function student_other_fees(array $student, ?array $session = null): array
{
    $session ??= active_session();
    if (!$session) {
        return [];
    }
    if (student_category($student, $session) === 'Alumni') {
        return [];
    }
    $level = student_current_level_code($student, $session);
    return db_all(
        'SELECT * FROM fees
         WHERE fee_type <> "school_fee"
           AND status = "active"
           AND session_id = ?
           AND (faculty_id IS NULL OR faculty_id = ?)
           AND (department_id IS NULL OR department_id = ?)
           AND (programme_type IS NULL OR programme_type = ?)
           AND (study_mode IS NULL OR study_mode = ?)
           AND (fee_level IS NULL OR fee_level = ?)
         ORDER BY title',
        [
            (int) $session['id'],
            (int) $student['faculty_id'],
            (int) $student['department_id'],
            $student['programme_type'],
            $student['study_mode'],
            $level,
        ]
    );
}

function student_fee_items(array $student, ?array $session = null): array
{
    $items = [];
    $schoolFee = active_school_fee($student, $session);
    if ($schoolFee) {
        $items[] = $schoolFee;
    }
    return array_merge($items, student_other_fees($student, $session));
}

function calculate_student_target(array $student, ?array $session = null): int
{
    $total = 0;
    foreach (student_fee_items($student, $session) as $fee) {
        $total += (int) $fee['amount'];
    }
    return $total;
}

function ensure_student_wallet(int $studentId): array
{
    $student = db_one(
        'SELECT s.*, ss.name AS admission_session_name
         FROM students s LEFT JOIN sessions ss ON ss.id = s.admission_session_id WHERE s.id = ?',
        [$studentId]
    );
    if (!$student) {
        throw new RuntimeException('Student not found.');
    }
    $session = active_session();
    if (!$session) {
        throw new RuntimeException('No active session configured.');
    }
    $target = calculate_student_target($student, $session);
    $wallet = db_one('SELECT * FROM wallets WHERE student_id = ? AND session_id = ?', [$studentId, (int) $session['id']]);

    if (!$wallet) {
        db_exec(
            'INSERT INTO wallets (student_id, session_id, target_amount, current_balance, status)
             VALUES (?, ?, ?, 0, ?)',
            [$studentId, (int) $session['id'], $target, $target > 0 ? 'active' : 'completed']
        );
    } else {
        $status = $wallet['status'];
        if ((int) $wallet['current_balance'] >= $target && $target > 0) {
            $status = 'completed';
        } elseif ($status !== 'paid') {
            $status = 'active';
        }
        db_exec(
            'UPDATE wallets SET target_amount = ?, status = ? WHERE id = ?',
            [$target, $status, (int) $wallet['id']]
        );
    }

    return db_one('SELECT * FROM wallets WHERE student_id = ? AND session_id = ?', [$studentId, (int) $session['id']]);
}

function wallet_remaining(array $wallet): int
{
    return max(0, (int) $wallet['target_amount'] - (int) $wallet['current_balance']);
}

function apply_wallet_deposit(int $walletId, int $amount, string $reference, string $gateway, array $payload = []): array
{
    db()->beginTransaction();
    try {
        $wallet = db_one('SELECT * FROM wallets WHERE id = ? FOR UPDATE', [$walletId]);
        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }
        if ($wallet['status'] !== 'active') {
            throw new RuntimeException('Deposit locked.');
        }

        $existing = db_one('SELECT id FROM transactions WHERE reference = ? LIMIT 1', [$reference]);
        if ($existing) {
            db()->commit();
            return $wallet;
        }

        $remaining = wallet_remaining($wallet);
        $depositAmount = min($amount, $remaining);
        if ($depositAmount <= 0) {
            throw new RuntimeException('Invalid deposit amount.');
        }

        $newBalance = (int) $wallet['current_balance'] + $depositAmount;
        $status = $newBalance >= (int) $wallet['target_amount'] ? 'completed' : 'active';

        db_exec(
            'UPDATE wallets SET current_balance = ?, status = ? WHERE id = ?',
            [$newBalance, $status, $walletId]
        );
        db_exec(
            'INSERT INTO transactions (student_id, wallet_id, session_id, reference, gateway, amount, status, payload_json)
             VALUES (?, ?, ?, ?, ?, ?, "successful", ?)',
            [
                (int) $wallet['student_id'],
                $walletId,
                (int) $wallet['session_id'],
                $reference,
                $gateway,
                $depositAmount,
                json_encode($payload),
            ]
        );
        db_exec(
            'INSERT INTO payments (student_id, wallet_id, session_id, transaction_reference, fee_type, amount, status)
             VALUES (?, ?, ?, ?, "wallet_deposit", ?, "successful")',
            [(int) $wallet['student_id'], $walletId, (int) $wallet['session_id'], $reference, $depositAmount]
        );

        db()->commit();
        return db_one('SELECT * FROM wallets WHERE id = ?', [$walletId]);
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}
