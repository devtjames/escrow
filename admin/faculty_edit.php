<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$id = (int) field('id');
$faculty = db_one('SELECT * FROM faculties WHERE id = ?', [$id]);
if (!$faculty) {
    flash_set('error', 'Faculty not found.');
    redirect_to('/admin/faculties');
}

if (post_request()) {
    verify_csrf();
    try {
        db_exec(
            'UPDATE faculties SET name = ?, dean_name = ?, status = ? WHERE id = ?',
            [field('name'), field('dean_name'), field('status'), $id]
        );
        log_activity((int) $admin['id'], null, 'update_faculty', ['id' => $id]);
        flash_set('success', 'Faculty updated.');
        redirect_to('/admin/faculty_edit?id=' . $id);
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        $faculty = array_merge($faculty, $_POST);
    }
}

page_head('Edit Faculty | FEDPOFFA Admin', 'Edit faculty record.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('faculties'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><div class="page-kicker"><span data-icon="edit"></span> Faculty records</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Edit Faculty</h1></div>
        <a class="btn-secondary" href="./faculties"><span data-icon="x"></span> Back</a>
      </div>
      <div class="form-grid mt-6">
        <label class="form-label">Faculty Name<input class="input" name="name" value="<?= e($faculty['name']) ?>" required></label>
        <label class="form-label">Dean<input class="input" name="dean_name" value="<?= e($faculty['dean_name']) ?>"></label>
        <label class="form-label">Status<select class="input" name="status"><option value="active" <?= $faculty['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $faculty['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Update Faculty</button>
    </form>
  </main>
  <?php admin_mobile_nav(''); ?>
</div><?php modal_and_toasts(); ?></body></html>
