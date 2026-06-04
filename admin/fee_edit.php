<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$id = (int) field('id');
$fee = db_one('SELECT * FROM fees WHERE id = ?', [$id]);
if (!$fee) {
    flash_set('error', 'Fee not found.');
    redirect_to('/admin/fees');
}
$faculties = db_all('SELECT * FROM faculties ORDER BY name');
$departments = db_all('SELECT d.*, f.name faculty_name FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY f.name, d.name');
$active = active_session();
$sessions = $active ? [$active] : [];

if (post_request()) {
    verify_csrf();
    try {
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
            'UPDATE fees
             SET title = ?, fee_type = ?, amount = ?, faculty_id = ?, department_id = ?, session_id = ?,
                 programme_type = ?, study_mode = ?, fee_level = ?, student_category = ?, status = ?
             WHERE id = ?',
            [field('title'), field('fee_type'), (int) field('amount'), $facultyId, $departmentId, $sessionId, $programme, $mode, $feeLevel, $category, field('status'), $id]
        );
        prepare_session_for_payments($sessionId, false);
        log_activity((int) $admin['id'], null, 'update_fee', ['id' => $id]);
        flash_set('success', 'Fee updated and wallets recalculated for the session.');
        redirect_to('/admin/fee_edit?id=' . $id);
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        $fee = array_merge($fee, $_POST);
    }
}

page_head('Edit Fee | FEDPOFFA Admin', 'Edit fee record.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('fees'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><div class="page-kicker"><span data-icon="edit"></span> Fee management</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Edit Fee</h1></div>
        <a class="btn-secondary" href="./fees"><span data-icon="x"></span> Back</a>
      </div>
      <div class="form-grid mt-6">
        <label class="form-label">Fee Title<input class="input" name="title" value="<?= e($fee['title']) ?>" required></label>
        <label class="form-label">Fee Type<select class="input" name="fee_type" data-fee-type-select><?php foreach (['school_fee'=>'School fee','departmental_fee'=>'Departmental fee','acceptance_fee'=>'Acceptance fee','institutional_charge'=>'Institutional charge','other'=>'Other'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $fee['fee_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Session<select class="input" name="session_id"><?php foreach ($sessions as $session): ?><option value="<?= (int) $session['id'] ?>" <?= (int) $fee['session_id'] === (int) $session['id'] ? 'selected' : '' ?>><?= e($session['name']) ?></option><?php endforeach; ?></select><span class="form-help">Only the currently active open session can receive fee changes.</span></label>
        <label class="form-label">Amount<input class="input" name="amount" type="number" min="0" value="<?= e($fee['amount']) ?>" required></label>
        <label class="form-label">Faculty Scope<select class="input" name="faculty_id" data-faculty-select><option value="">All faculties</option><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>" <?= (int) $fee['faculty_id'] === (int) $faculty['id'] ? 'selected' : '' ?>><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label" data-fee-extra-scope>Department Scope<select class="input" name="department_id" data-department-select><option value="">All departments</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $fee['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['faculty_name'] . ' - ' . $department['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Programme Type<select class="input" name="programme_type" data-programme-select><option value="">All programmes</option><?php foreach (['ND','HND'] as $programme): ?><option <?= $fee['programme_type'] === $programme ? 'selected' : '' ?>><?= e($programme) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Study Mode<select class="input" name="study_mode"><option value="">All modes</option><?php foreach (['FT','PT'] as $mode): ?><option <?= $fee['study_mode'] === $mode ? 'selected' : '' ?>><?= e($mode) ?></option><?php endforeach; ?></select></label>
        <label class="form-label" data-fee-extra-scope>Fee Level<select class="input" name="fee_level" data-level-select><option value="">All levels</option><?php foreach (['ND1','ND2','HND1','HND2'] as $level): ?><option <?= ($fee['fee_level'] ?? '') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select><span class="form-help">Optional. Useful for other fees that target a specific level.</span></label>
        <label class="form-label" data-fee-school-scope>Student Category<select class="input" name="student_category"><option value="">All categories</option><?php foreach (['Fresher','Staylite'] as $category): ?><option <?= $fee['student_category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Status<select class="input" name="status"><?php foreach (['active'=>'Enabled','draft'=>'Draft','disabled'=>'Disabled'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $fee['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Update Fee</button>
    </form>
  </main>
  <?php admin_mobile_nav('fees'); ?>
</div><?php modal_and_toasts(); ?></body></html>
