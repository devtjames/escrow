<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();

if (post_request()) {
    verify_csrf();
    try {
        if (field('action') === 'delete') {
            db_exec('DELETE FROM faculties WHERE id = ?', [(int) field('id')]);
            log_activity((int) $admin['id'], null, 'delete_faculty', ['id' => field('id')]);
        } else {
            db_exec(
                'INSERT INTO faculties (name, dean_name, status)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE dean_name = VALUES(dean_name), status = VALUES(status)',
                [field('name'), field('dean_name'), field('status')]
            );
            log_activity((int) $admin['id'], null, 'save_faculty', ['name' => field('name')]);
        }
        flash_set('success', 'Faculty record saved.');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/faculties');
}

$rows = db_all('SELECT f.*, COUNT(d.id) department_count FROM faculties f LEFT JOIN departments d ON d.faculty_id = f.id GROUP BY f.id ORDER BY f.name');
$pagination = pagination_state(count($rows), 12);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);
page_head('Manage Faculties | FEDPOFFA Admin', 'Manage FEDPOFFA faculties.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('faculties'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('faculties'); ?>
      <div class="card rounded-lg p-5">
        <div class="page-kicker"><span data-icon="building"></span> Faculty records</div>
        <h2 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Manage Faculties</h2>
      </div>

      <div class="grid gap-5 xl:grid-cols-[minmax(28rem,0.95fr)_minmax(0,1.05fr)]">
        <form class="card rounded-lg p-5" method="post" data-admin-form>
          <?= csrf_field() ?>
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Create Faculty</h2>
          <div class="mt-5 grid gap-4">
            <label class="form-label">Faculty Name<input class="input" name="name" required></label>
            <label class="form-label">Dean<input class="input" name="dean_name"></label>
            <label class="form-label">Status<select class="input" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <button class="btn-primary" type="submit"><span data-icon="save"></span> Save Faculty</button>
          </div>
        </form>

        <section class="card rounded-lg p-5">
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Faculties</h2>
          <?php if ($rows): ?>
            <div class="table-wrap mt-5">
              <table class="data-table">
                <thead><tr><th>Faculty</th><th>Departments</th><th>Dean</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td data-label="Faculty" class="font-black text-slate-950 dark:text-white"><?= e($row['name']) ?></td>
                      <td data-label="Departments"><?= (int) $row['department_count'] ?> departments</td>
                      <td data-label="Dean"><?= e($row['dean_name'] ?: '-') ?></td>
                      <td data-label="Status"><?= e($row['status']) ?></td>
                      <td data-label="Actions" class="text-right">
                        <div class="inline-flex gap-2">
                          <a class="icon-btn" href="./faculty_edit?id=<?= (int) $row['id'] ?>" title="Edit faculty"><span data-icon="edit"></span></a>
                          <form method="post" class="inline-flex">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-btn" type="submit" data-confirm="Delete faculty?" title="Delete"><span data-icon="trash"></span></button>
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
            <div class="admin-empty-state mt-5"><span data-icon="building"></span><b>No faculties yet</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Create the first faculty from the form.</p></div>
          <?php endif; ?>
        </section>
      </div>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
