<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$id = (int) field('id');
$department = db_one('SELECT * FROM departments WHERE id = ?', [$id]);
if (!$department) {
    flash_set('error', 'Department not found.');
    redirect_to('/admin/departments');
}
$faculties = db_all('SELECT * FROM faculties ORDER BY name');

if (post_request()) {
    verify_csrf();
    try {
        db_exec(
            'UPDATE departments SET faculty_id = ?, name = ?, hod_name = ?, award_type = ?, status = ? WHERE id = ?',
            [(int) field('faculty_id'), field('name'), nullable_field('hod_name'), field('award_type'), field('status'), $id]
        );
        log_activity((int) $admin['id'], null, 'update_department', ['id' => $id]);
        flash_set('success', 'Department updated.');
        redirect_to('/admin/department_edit?id=' . $id);
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        $department = array_merge($department, $_POST);
    }
}

page_head('Edit Department | FEDPOFFA Admin', 'Edit department record.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('departments'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><div class="page-kicker"><span data-icon="edit"></span> Department records</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Edit Department</h1></div>
        <a class="btn-secondary" href="./departments"><span data-icon="x"></span> Back</a>
      </div>
      <div class="form-grid mt-6">
        <label class="form-label">Department Name<input class="input" name="name" value="<?= e($department['name']) ?>" required></label>
        <label class="form-label">HOD<input class="input" name="hod_name" value="<?= e($department['hod_name'] ?? '') ?>"></label>
        <label class="form-label">Faculty<select class="input" name="faculty_id"><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>" <?= (int) $department['faculty_id'] === (int) $faculty['id'] ? 'selected' : '' ?>><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Award Type<select class="input" name="award_type"><?php foreach (['HND/ND','HND','ND'] as $award): ?><option <?= $department['award_type'] === $award ? 'selected' : '' ?>><?= e($award) ?></option><?php endforeach; ?></select></label>
        <label class="form-label">Status<select class="input" name="status"><option value="active" <?= $department['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $department['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Update Department</button>
    </form>
  </main>
  <?php admin_mobile_nav(''); ?>
</div><?php modal_and_toasts(); ?></body></html>
