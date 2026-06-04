<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin(['super_admin']);

if (post_request()) {
    verify_csrf();
    try {
        $targetId = (int) field('id');
        $target = db_one('SELECT * FROM admins WHERE id = ?', [$targetId]);
        if (!$target) {
            throw new RuntimeException('Admin account not found.');
        }

        if (field('action') === 'set_status') {
            $status = field('status');
            if (!in_array($status, ['pending', 'active', 'suspended'], true)) {
                throw new RuntimeException('Invalid admin status.');
            }
            if ($targetId === (int) $admin['id'] && $status !== 'active') {
                throw new RuntimeException('You cannot deactivate your own super admin account.');
            }
            db_exec('UPDATE admins SET status = ? WHERE id = ?', [$status, $targetId]);
            flash_set('success', 'Admin account status updated.');
            log_activity((int) $admin['id'], null, 'update_admin_status', ['admin_id' => $targetId, 'status' => $status]);
        } elseif (field('action') === 'set_role') {
            $role = field('role');
            if (!in_array($role, ['admin', 'super_admin'], true)) {
                throw new RuntimeException('Invalid admin role.');
            }
            if ($targetId === (int) $admin['id'] && $role !== 'super_admin') {
                throw new RuntimeException('You cannot remove your own super admin role.');
            }
            db_exec('UPDATE admins SET role = ? WHERE id = ?', [$role, $targetId]);
            flash_set('success', 'Admin account role updated.');
            log_activity((int) $admin['id'], null, 'update_admin_role', ['admin_id' => $targetId, 'role' => $role]);
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/admins');
}

$rows = db_all('SELECT * FROM admins ORDER BY FIELD(status, "pending", "active", "suspended"), created_at DESC');
$pendingCount = (int) (db_one('SELECT COUNT(*) total FROM admins WHERE status = "pending"')['total'] ?? 0);
$activeCount = (int) (db_one('SELECT COUNT(*) total FROM admins WHERE status = "active"')['total'] ?? 0);
$pagination = pagination_state(count($rows), 15);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);

page_head('Admin Accounts | FEDPOFFA Admin', 'Manage admin account approvals.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('admins'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('admins'); ?>

      <div class="grid gap-4 sm:grid-cols-2">
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Pending Requests</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white"><?= number_format($pendingCount) ?></h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Need super admin approval</p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Active Admins</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white"><?= number_format($activeCount) ?></h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Can access admin dashboard</p></article>
      </div>

      <section class="card rounded-lg p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="page-kicker"><span data-icon="users"></span> Super admin only</div>
            <h2 class="mt-4 text-2xl font-black text-slate-950 dark:text-white">Admin Account Requests</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">New admin registrations remain pending until a super admin activates them.</p>
          </div>
          <a class="btn-secondary" href="./dashboard"><span data-icon="table"></span> Dashboard</a>
        </div>

        <?php if ($rows): ?>
          <div class="table-wrap mt-5">
            <table class="data-table">
              <thead><tr><th>Name</th><th>Staff ID</th><th>Email</th><th>Role</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <tr>
                    <td data-label="Name" class="font-black text-slate-950 dark:text-white"><?= e($row['full_name']) ?></td>
                    <td data-label="Staff ID"><?= e($row['staff_id']) ?></td>
                    <td data-label="Email"><?= e($row['email']) ?></td>
                    <td data-label="Role">
                      <form method="post" class="inline-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_role">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <select class="input min-w-36" name="role" onchange="this.form.submit()" <?= (int) $row['id'] === (int) $admin['id'] ? 'disabled' : '' ?>>
                          <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                          <option value="super_admin" <?= $row['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                      </form>
                    </td>
                    <td data-label="Status"><span class="status-pill <?= $row['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : ($row['status'] === 'pending' ? 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-red-50 text-red-800 dark:bg-red-500/15 dark:text-red-200') ?>"><?= e(strtoupper($row['status'])) ?></span></td>
                    <td data-label="Actions" class="text-right">
                      <div class="inline-flex flex-wrap justify-end gap-2">
                        <?php foreach ([['active','Activate'], ['suspended','Suspend']] as $action): ?>
                          <?php if ($row['status'] !== $action[0] && !((int) $row['id'] === (int) $admin['id'] && $action[0] === 'suspended')): ?>
                            <form method="post" class="inline-flex">
                              <?= csrf_field() ?>
                              <input type="hidden" name="action" value="set_status">
                              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                              <input type="hidden" name="status" value="<?= e($action[0]) ?>">
                              <button class="btn-secondary" type="submit" data-confirm="<?= e($action[1]) ?> this admin account?"><?= e($action[1]) ?></button>
                            </form>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php render_pagination($pagination); ?>
        <?php else: ?>
          <div class="admin-empty-state mt-5"><span data-icon="users"></span><b>No admin accounts yet</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Admin requests will appear here.</p></div>
        <?php endif; ?>
      </section>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
