<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$faculties = db_all('SELECT * FROM faculties WHERE status = "active" ORDER BY name');
$departments = db_all('SELECT d.*, f.name faculty_name FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY f.name, d.name');
$sessions = sessions_all();
$active = active_session();
$activeSessionId = (int) ($active['id'] ?? 0);

if (post_request()) {
    verify_csrf();
    try {
        $action = field('action');
        if ($action === 'delete') {
            db_exec('DELETE FROM students WHERE id = ?', [(int) field('id')]);
            flash_set('success', 'Student deleted.');
            log_activity((int) $admin['id'], null, 'delete_student', ['id' => field('id')]);
        } elseif ($action === 'bulk') {
            $ids = array_values(array_unique(array_map('intval', (array) ($_POST['selected_ids'] ?? []))));
            $bulkAction = field('bulk_action');
            if (!$ids) {
                throw new RuntimeException('Select at least one student.');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($bulkAction === 'activate') {
                db_exec('UPDATE students SET status = "active" WHERE id IN (' . $placeholders . ')', $ids);
                flash_set('success', count($ids) . ' student account(s) activated.');
            } elseif ($bulkAction === 'suspend') {
                db_exec('UPDATE students SET status = "suspended" WHERE id IN (' . $placeholders . ')', $ids);
                flash_set('success', count($ids) . ' student account(s) suspended.');
            } elseif ($bulkAction === 'delete') {
                db_exec('DELETE FROM students WHERE id IN (' . $placeholders . ')', $ids);
                flash_set('success', count($ids) . ' student record(s) deleted.');
            } elseif ($bulkAction === 'remind') {
                $students = db_all(
                    'SELECT s.*, COALESCE(w.target_amount,0) target_amount, COALESCE(w.current_balance,0) current_balance
                     FROM students s
                     LEFT JOIN wallets w ON w.student_id = s.id AND w.session_id = ?
                     WHERE s.id IN (' . $placeholders . ')',
                    array_merge([$activeSessionId], $ids)
                );
                $sent = 0;
                foreach ($students as $student) {
                    $balance = max(0, (int) $student['target_amount'] - (int) $student['current_balance']);
                    $content = '<p style="margin:0 0 14px 0">Dear ' . e($student['full_name']) . ',</p>'
                        . '<p style="margin:0 0 14px 0">This is a reminder to review your fee wallet for the current session.</p>'
                        . email_rows([
                            'Current Paid' => money_naira((int) $student['current_balance']),
                            'Outstanding Balance' => money_naira($balance),
                            'Session' => $active['name'] ?? 'Current session',
                        ])
                        . email_button(app_url('/student/payment'), 'Open Student Payment Page');
                    if (sendMail((string) $student['email'], 'FEDPOFFA Payment Reminder', $content)) {
                        $sent++;
                    }
                }
                flash_set($sent > 0 ? 'success' : 'error', $sent . ' reminder email(s) sent.');
            } else {
                throw new RuntimeException('Choose a bulk action.');
            }
            log_activity((int) $admin['id'], null, 'bulk_student_action', ['action' => $bulkAction, 'count' => count($ids)]);
        } else {
            $programme = strtoupper(field('programme_type'));
            $mode = strtoupper(field('study_mode'));
            $entryLevel = strtoupper(field('entry_level'));
            $fullName = normalize_full_name(field('full_name'));
            $nextOfKinFullName = normalize_full_name(field('next_of_kin_full_name'));
            $nextOfKinEmail = field('next_of_kin_email');
            $nextOfKinPhone = field('next_of_kin_phone');
            $nextOfKinRelationship = field('next_of_kin_relationship');
            if ($fullName === '' || full_name_has_number($fullName)) {
                throw new RuntimeException('Full name cannot contain numbers.');
            }
            if ($nextOfKinError = validate_next_of_kin_details($nextOfKinFullName, $nextOfKinEmail, $nextOfKinPhone, $nextOfKinRelationship, field('email'), field('phone'))) {
                throw new RuntimeException($nextOfKinError);
            }
            if (db_one('SELECT id FROM students WHERE email = ? LIMIT 1', [field('email')])) {
                throw new RuntimeException('Email address already exists.');
            }
            if (db_one('SELECT id FROM students WHERE application_number = ? LIMIT 1', [field('application_number')])) {
                throw new RuntimeException('Application number already exists.');
            }
            $matric = nullable_field('matric_number');
            if ($matric && db_one('SELECT id FROM students WHERE matric_number = ? LIMIT 1', [$matric])) {
                throw new RuntimeException('Matric number already exists.');
            }
            if (!validate_programme($programme, $mode)) {
                throw new RuntimeException('Invalid programme combination. HND PT does not exist.');
            }
            if (!validate_entry_level($programme, $entryLevel)) {
                throw new RuntimeException('Selected level must match the programme type.');
            }
            if (!department_supports_programme((int) field('department_id'), (int) field('faculty_id'), $programme)) {
                throw new RuntimeException('Selected department is not available for the chosen programme type.');
            }
            $password = field('password') ?: 'Student123';
            if (!password_is_strong($password)) {
                throw new RuntimeException('Password must include upper/lowercase letters and a number.');
            }
            db_exec(
                'INSERT INTO students (full_name, email, password_hash, phone, next_of_kin_full_name, next_of_kin_email, next_of_kin_phone, next_of_kin_relationship, address, gender, faculty_id, department_id, programme_type, entry_level, study_mode, application_number, matric_number, matric_locked, admission_session_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$fullName, field('email'), password_hash($password, PASSWORD_BCRYPT), field('phone'), $nextOfKinFullName, $nextOfKinEmail, $nextOfKinPhone, $nextOfKinRelationship, field('address'), field('gender'), (int) field('faculty_id'), (int) field('department_id'), $programme, $entryLevel, $mode, field('application_number'), $matric, $matric ? 1 : 0, (int) field('admission_session_id')]
            );
            ensure_student_wallet((int) db()->lastInsertId());
            flash_set('success', 'Student saved.');
            log_activity((int) $admin['id'], null, 'save_student');
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/students');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'faculty_id' => (string) ($_GET['faculty_id'] ?? ''),
    'department_id' => (string) ($_GET['department_id'] ?? ''),
    'session_id' => (string) ($_GET['session_id'] ?? ''),
    'status' => (string) ($_GET['status'] ?? ''),
    'wallet_status' => (string) ($_GET['wallet_status'] ?? ''),
];
$where = [];
$params = [$activeSessionId];
if ($filters['q'] !== '') {
    $where[] = '(s.full_name LIKE ? OR s.email LIKE ? OR s.application_number LIKE ? OR s.matric_number LIKE ?)';
    $term = '%' . $filters['q'] . '%';
    array_push($params, $term, $term, $term, $term);
}
foreach (['faculty_id', 'department_id'] as $key) {
    if ($filters[$key] !== '') {
        $where[] = 's.' . $key . ' = ?';
        $params[] = (int) $filters[$key];
    }
}
if ($filters['session_id'] !== '') {
    $where[] = 's.admission_session_id = ?';
    $params[] = (int) $filters['session_id'];
}
if ($filters['status'] !== '') {
    $where[] = 's.status = ?';
    $params[] = $filters['status'];
}
if ($filters['wallet_status'] !== '') {
    $where[] = 'COALESCE(w.status,"active") = ?';
    $params[] = $filters['wallet_status'];
}
$sql = 'SELECT s.*, f.name faculty_name, d.name department_name, ss.name admission_session_name,
               COALESCE(w.current_balance,0) paid, COALESCE(w.target_amount,0) target_amount, COALESCE(w.status,"active") wallet_status
        FROM students s
        JOIN faculties f ON f.id = s.faculty_id
        JOIN departments d ON d.id = s.department_id
        JOIN sessions ss ON ss.id = s.admission_session_id
        LEFT JOIN wallets w ON w.student_id = s.id AND w.session_id = ?';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.created_at DESC';
$rows = db_all($sql, $params);
$totalFiltered = count($rows);
$pagination = pagination_state($totalFiltered, 15);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);

page_head('Manage Students | FEDPOFFA Admin', 'Manage FEDPOFFA students.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('students'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('students'); ?>
      <div class="card rounded-lg p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="page-kicker"><span data-icon="users"></span> Student records</div>
            <h2 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Manage Students</h2>
            <p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><?= number_format($totalFiltered) ?> record(s) in this view.</p>
          </div>
          <a class="btn-secondary" href="./emails"><span data-icon="mail"></span> Email Center</a>
        </div>
      </div>

      <div class="grid gap-5 xl:grid-cols-[minmax(28rem,0.95fr)_minmax(0,1.05fr)]">
        <form class="card rounded-lg p-5" method="post" data-admin-form>
          <?= csrf_field() ?>
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Add Student</h2>
          <div class="mt-5 grid gap-4">
            <label class="form-label">Full Name<input class="input uppercase" name="full_name" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label>
            <label class="form-label">Application Number<input class="input" name="application_number" required></label>
            <label class="form-label">Matric Number<input class="input" name="matric_number"><span class="form-help">Locks immediately if supplied.</span></label>
            <label class="form-label">Email<input class="input" name="email" type="email" required></label>
            <label class="form-label">Phone<input class="input" name="phone" required></label>
            <label class="form-label">Next of Kin / Guardian Full Name<input class="input uppercase" name="next_of_kin_full_name" pattern="[^0-9]*" title="Full name cannot contain numbers" data-name-input required></label>
            <label class="form-label">Next of Kin Relationship<input class="input" name="next_of_kin_relationship" placeholder="Parent, guardian, sibling" required></label>
            <label class="form-label">Next of Kin Email<input class="input" name="next_of_kin_email" type="email" required></label>
            <label class="form-label">Next of Kin Phone<input class="input" name="next_of_kin_phone" required><span class="form-help">Must differ from the student's email address and phone number.</span></label>
            <label class="form-label">Gender<select class="input" name="gender"><option>Female</option><option>Male</option><option>Other</option></select></label>
            <label class="form-label">Faculty<select class="input" name="faculty_id" data-faculty-select required><option value="">Select faculty</option><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>"><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-label">Department<select class="input" name="department_id" data-department-select required><option value="">Select faculty first</option></select></label>
            <label class="form-label">Programme Type<select class="input" name="programme_type" data-programme-select><option>ND</option><option>HND</option></select></label>
            <label class="form-label">Level<select class="input" name="entry_level" data-level-select><option>ND1</option><option>ND2</option><option>HND1</option><option>HND2</option></select><span class="form-help">ND1/HND1 are freshers. ND2/HND2 are staylites.</span></label>
            <label class="form-label">Study Mode<select class="input" name="study_mode"><option>FT</option><option>PT</option></select></label>
            <label class="form-label">Admission Session<select class="input" name="admission_session_id"><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>" <?= $active && (int) $active['id'] === (int) $session['id'] ? 'selected' : '' ?>><?= e($session['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-label">Password<input class="input" name="password" placeholder="Student123"></label>
            <label class="form-label">Address<textarea class="input min-h-24" name="address"></textarea></label>
            <button class="btn-primary" type="submit"><span data-icon="save"></span> Save Student</button>
          </div>
        </form>

        <section class="card rounded-lg p-5">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Student List</h2>
            <a class="btn-secondary" href="./students"><span data-icon="x"></span> Clear Filters</a>
          </div>

          <form class="admin-filter-bar mt-5" method="get">
            <label class="form-label">Search<input class="input" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, email, application, matric"></label>
            <label class="form-label">Faculty<select class="input" name="faculty_id"><option value="">All faculties</option><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>" <?= $filters['faculty_id'] === (string) $faculty['id'] ? 'selected' : '' ?>><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-label">Session<select class="input" name="session_id"><option value="">All sessions</option><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>" <?= $filters['session_id'] === (string) $session['id'] ? 'selected' : '' ?>><?= e($session['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-label">Status<select class="input" name="status"><option value="">All accounts</option><option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option></select></label>
            <button class="btn-primary" type="submit"><span data-icon="search"></span> Filter</button>
          </form>

          <form method="post" class="mt-5">
            <?= csrf_field() ?>
            <div class="admin-bulk-bar">
              <span>Select students below, then apply a bulk operation.</span>
              <div class="flex flex-col gap-2 sm:flex-row">
                <select class="input min-w-44" name="bulk_action" required><option value="">Bulk action</option><option value="remind">Send reminder</option><option value="activate">Activate</option><option value="suspend">Suspend</option><option value="delete">Delete</option></select>
                <button class="btn-secondary" type="submit" name="action" value="bulk" data-confirm="Apply this bulk action to selected students?"><span data-icon="save"></span> Apply</button>
              </div>
            </div>

            <?php if ($rows): ?>
              <div class="table-wrap mt-5">
                <table class="data-table">
                  <thead><tr><th><input type="checkbox" data-check-all></th><th>Identifier</th><th>Name</th><th>Program</th><th>Level</th><th>Paid</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($rows as $row): ?>
                      <tr>
                        <td data-label="Select"><input type="checkbox" name="selected_ids[]" value="<?= (int) $row['id'] ?>" data-check-row></td>
                        <td data-label="Identifier" class="font-black text-slate-950 dark:text-white"><?= e($row['matric_number'] ?: $row['application_number']) ?></td>
                        <td data-label="Name"><?= e($row['full_name']) ?><div class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= e($row['email']) ?></div></td>
                        <td data-label="Program"><?= e($row['programme_type'] . ' ' . $row['department_name']) ?></td>
                        <td data-label="Level"><?= e(student_level($row, $active) . ' / ' . student_category($row, $active)) ?></td>
                        <td data-label="Paid"><?= e(money_naira((int) $row['paid'])) ?><div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Target <?= e(money_naira((int) $row['target_amount'])) ?></div></td>
                        <td data-label="Status"><span class="status-pill <?= $row['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-500/15 dark:text-red-200' ?>"><?= e(strtoupper($row['status'])) ?></span><div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400"><?= e($row['wallet_status']) ?> wallet</div></td>
                        <td data-label="Actions" class="text-right">
                          <div class="inline-flex gap-2">
                            <a class="icon-btn" href="./student_edit?id=<?= (int) $row['id'] ?>" title="Edit student"><span data-icon="edit"></span></a>
                            <button class="icon-btn" type="submit" name="action" value="delete" formaction="./students?id=<?= (int) $row['id'] ?>" formnovalidate data-confirm="Delete this student?" title="Delete student"><span data-icon="trash"></span></button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="admin-empty-state mt-5"><span data-icon="users"></span><b>No students found</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Adjust the filters or add a new student.</p></div>
            <?php endif; ?>
            <?php render_pagination($pagination); ?>
          </form>
        </section>
      </div>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
