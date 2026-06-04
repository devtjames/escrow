<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$faculties = db_all('SELECT * FROM faculties WHERE status = "active" ORDER BY name');

if (post_request()) {
    verify_csrf();
    try {
        if (field('action') === 'delete') {
            db_exec('DELETE FROM departments WHERE id = ?', [(int) field('id')]);
        } else {
            db_exec(
                'INSERT INTO departments (faculty_id, name, hod_name, award_type, status)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE hod_name = VALUES(hod_name), award_type = VALUES(award_type), status = VALUES(status)',
                [(int) field('faculty_id'), field('name'), nullable_field('hod_name'), field('award_type'), field('status')]
            );
        }
        log_activity((int) $admin['id'], null, 'save_department');
        flash_set('success', 'Department record saved.');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/departments');
}

$rows = db_all('SELECT d.*, f.name faculty_name FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY f.name, d.name');
$pagination = pagination_state(count($rows), 12);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);
page_head('Manage Departments | FEDPOFFA Admin', 'Manage FEDPOFFA departments.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('departments'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('departments'); ?>
      <div class="card rounded-lg p-5">
        <div class="page-kicker"><span data-icon="book"></span> Department records</div>
        <h2 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Manage Departments</h2>
      </div>

      <div class="grid gap-5 xl:grid-cols-[minmax(28rem,0.95fr)_minmax(0,1.05fr)]">
        <form class="card rounded-lg p-5" method="post" data-admin-form>
          <?= csrf_field() ?>
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Create Department</h2>
          <div class="mt-5 grid gap-4">
            <label class="form-label">Department Name<input class="input" name="name" required></label>
            <label class="form-label">HOD<input class="input" name="hod_name" placeholder="Head of Department"></label>
            <label class="form-label">Faculty<select class="input" name="faculty_id"><?php foreach ($faculties as $faculty): ?><option value="<?= (int) $faculty['id'] ?>"><?= e($faculty['name']) ?></option><?php endforeach; ?></select></label>
            <label class="form-label">Award Type<select class="input" name="award_type"><option>HND/ND</option><option>HND</option><option>ND</option></select></label>
            <label class="form-label">Status<select class="input" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <button class="btn-primary" type="submit"><span data-icon="save"></span> Save Department</button>
          </div>
        </form>

        <section class="card rounded-lg p-5">
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Departments</h2>
          <?php if ($rows): ?>
            <div class="table-wrap mt-5">
              <table class="data-table">
                <thead><tr><th>Department</th><th>Faculty</th><th>HOD</th><th>Award</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td data-label="Department" class="font-black text-slate-950 dark:text-white"><?= e($row['name']) ?></td>
                      <td data-label="Faculty"><?= e($row['faculty_name']) ?></td>
                      <td data-label="HOD"><?= e($row['hod_name'] ?: '-') ?></td>
                      <td data-label="Award"><?= e($row['award_type']) ?></td>
                      <td data-label="Status"><?= e($row['status']) ?></td>
                      <td data-label="Actions" class="text-right">
                        <div class="inline-flex gap-2">
                          <a class="icon-btn" href="./department_edit?id=<?= (int) $row['id'] ?>" title="Edit department"><span data-icon="edit"></span></a>
                          <form method="post" class="inline-flex">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-btn" type="submit" data-confirm="Delete department?" title="Delete"><span data-icon="trash"></span></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php render_pagination($pagination); ?>
          <?php else: ?>
            <div class="admin-empty-state mt-5"><span data-icon="book"></span><b>No departments yet</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Create the first department from the form.</p></div>
          <?php endif; ?>
        </section>
      </div>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
