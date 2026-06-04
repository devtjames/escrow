<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$faculties = db_all('SELECT * FROM faculties ORDER BY name');
$departments = db_all('SELECT d.*, f.name faculty_name FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY f.name, d.name');
$active = active_session();
$sessions = $active ? [$active] : [];
if (post_request()) {
    verify_csrf();
    try {
        if (field('action') === 'delete') {
            db_exec('DELETE FROM fees WHERE id = ?', [(int) field('id')]);
        } else {
            $sessionId = (int) field('session_id');
            if (!$active || $sessionId !== (int) $active['id']) {
                throw new RuntimeException('Fees can only be saved for the active open session.');
            }
            $programme = nullable_field('programme_type');
            $mode = nullable_field('study_mode');
            $feeLevel = nullable_field('fee_level');
            $category = nullable_field('student_category');
            $facultyId = nullable_field('faculty_id');
            $departmentId = nullable_field('department_id');
            if ($programme && $mode && !validate_programme($programme, $mode)) {
                throw new RuntimeException('Invalid programme combination. HND PT does not exist.');
            }
            if ($feeLevel && $programme && !validate_entry_level($programme, $feeLevel)) {
                throw new RuntimeException('Fee level must match the selected programme type.');
            }
            if ($facultyId && $departmentId) {
                $departmentBelongsToFaculty = db_one('SELECT id FROM departments WHERE id = ? AND faculty_id = ? LIMIT 1', [$departmentId, $facultyId]);
                if (!$departmentBelongsToFaculty) {
                    throw new RuntimeException('Selected department does not belong to the selected faculty.');
                }
            }
            if (field('fee_type') === 'school_fee') {
                if (!$facultyId || !in_array((string) $category, ['Fresher', 'Staylite'], true)) {
                    throw new RuntimeException('School fee must be assigned to a faculty and student category.');
                }
                $departmentId = null;
                $programme = null;
                $mode = null;
                $feeLevel = null;
            } else {
                $category = null;
            }
            db_exec(
                'INSERT INTO fees (title, fee_type, amount, faculty_id, department_id, session_id, programme_type, study_mode, fee_level, student_category, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [field('title'), field('fee_type'), (int) field('amount'), $facultyId, $departmentId, $sessionId, $programme, $mode, $feeLevel, $category, field('status')]
            );
            prepare_session_for_payments($sessionId, false);
        }
        log_activity((int) $admin['id'], null, 'save_fee');
        flash_set('success', 'Fee record saved.');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/fees');
}
$rows = db_all('SELECT fees.*, s.name session_name, f.name faculty_name, d.name department_name FROM fees JOIN sessions s ON s.id = fees.session_id LEFT JOIN faculties f ON f.id = fees.faculty_id LEFT JOIN departments d ON d.id = fees.department_id ORDER BY fees.created_at DESC');
$pagination = pagination_state(count($rows), 12);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);
page_head('Manage Fees | FEDPOFFA Admin', 'Manage FEDPOFFA fees.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100"><?php admin_header('fees'); ?><main class="mx-auto max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8"><section class="view grid gap-5 py-4"><div class="card rounded-lg p-5"><div class="page-kicker"><span data-icon="wallet"></span> Fee management</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Manage Fees</h1></div><div class="grid gap-5 xl:grid-cols-[minmax(30rem,1fr)_minmax(0,1.05fr)]"><form class="card rounded-lg p-5" method="post" data-admin-form><?= csrf_field() ?><h2 class="text-xl font-black text-slate-950 dark:text-white">Create Fee</h2><div class="mt-5 grid gap-4"><label class="form-label">Fee Title<input class="input" name="title" placeholder="Faculty Fresher School Fee" required></label><label class="form-label">Fee Type<select class="input" name="fee_type" data-fee-type-select><option value="school_fee">School fee</option><option value="departmental_fee">Departmental fee</option><option value="acceptance_fee">Acceptance fee</option><option value="institutional_charge">Institutional charge</option><option value="other">Other</option></select></label><label class="form-label">Session<select class="input" name="session_id"><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>"><?= e($session['name']) ?></option><?php endforeach; ?></select><span class="form-help">Only the currently active open session can receive new fees.</span></label><label class="form-label">Amount<input class="input" name="amount" type="number" min="0" placeholder="185000" required></label><label class="form-label">Faculty Scope<select class="input" name="faculty_id" data-faculty-select><option value="">All faculties</option><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>"><?= e($faculty['name']) ?></option><?php endforeach; ?></select><span class="form-help">School fees should be faculty based.</span></label><label class="form-label" data-fee-extra-scope>Department Scope<select class="input" name="department_id" data-department-select><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>"><?= e($department['faculty_name'] . ' - ' . $department['name']) ?></option><?php endforeach; ?></select><span class="form-help">Use this for departmental or level-specific fees.</span></label><label class="form-label">Programme Type<select class="input" name="programme_type" data-programme-select><option value="">All programmes</option><option>ND</option><option>HND</option></select></label><label class="form-label">Study Mode<select class="input" name="study_mode"><option value="">All modes</option><option>FT</option><option>PT</option></select></label><label class="form-label" data-fee-extra-scope>Fee Level<select class="input" name="fee_level" data-level-select><option value="">All levels</option><option>ND1</option><option>ND2</option><option>HND1</option><option>HND2</option></select><span class="form-help">Optional. Useful for other fees that target a specific level.</span></label><label class="form-label" data-fee-school-scope>Student Category<select class="input" name="student_category"><option value="">All categories</option><option>Fresher</option><option>Staylite</option></select><span class="form-help">School fees require Fresher or Staylite.</span></label><label class="form-label">Status<select class="input" name="status"><option value="active">Enabled</option><option value="draft">Draft</option><option value="disabled">Disabled</option></select></label><button class="btn-primary" type="submit"><span data-icon="save"></span> Save Fee</button></div></form><section class="card rounded-lg p-5"><h2 class="text-xl font-black text-slate-950 dark:text-white">Configured Fees</h2><div class="table-wrap mt-5"><table class="data-table"><thead><tr><th>Fee</th><th>Scope</th><th>Session</th><th>Amount</th><th>Status</th><th class="text-right">Actions</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td data-label="Fee" class="font-black text-slate-950 dark:text-white"><?= e($row['title']) ?></td><td data-label="Scope"><?= e(($row['faculty_name'] ?: 'All faculties') . ' / ' . ($row['department_name'] ?: 'All departments') . ' / ' . ($row['student_category'] ?: 'All categories') . ' / ' . ($row['fee_level'] ?: 'All levels')) ?></td><td data-label="Session"><?= e($row['session_name']) ?></td><td data-label="Amount"><?= e(money_naira((int) $row['amount'])) ?></td><td data-label="Status"><?= e($row['status']) ?></td><td data-label="Actions" class="text-right"><div class="inline-flex gap-2"><a class="icon-btn" href="./fee_edit?id=<?= (int) $row['id'] ?>" title="Edit fee"><span data-icon="edit"></span></a><form method="post" onsubmit="return confirm('Delete fee?')" class="inline-flex"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="icon-btn" type="submit"><span data-icon="trash"></span></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php render_pagination($pagination); ?></section></div></section></main><?php admin_mobile_nav('fees'); ?></div><?php modal_and_toasts(); ?></body></html>
