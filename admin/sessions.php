<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();

if (post_request()) {
    verify_csrf();
    try {
        if (field('action') === 'activate') {
            activate_session((int) field('id'), field('copy_fees', '1') === '1');
            flash_set('success', 'Active session updated. Previous open sessions were closed and wallets were prepared.');
        } elseif (field('action') === 'prepare') {
            prepare_session_for_payments((int) field('id'), field('copy_fees', '1') === '1');
            flash_set('success', 'Session wallets prepared. Students can deposit if a fee target exists.');
        } elseif (field('action') === 'delete') {
            db_exec('DELETE FROM sessions WHERE id = ? AND is_active = 0', [(int) field('id')]);
            flash_set('success', 'Inactive session deleted.');
        } else {
            $isActive = field('is_active') === '1';
            db_exec(
                'INSERT INTO sessions (name, status, start_date, end_date, is_active)
                 VALUES (?, ?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), start_date = VALUES(start_date), end_date = VALUES(end_date)',
                [field('name'), $isActive ? 'open' : field('status'), nullable_field('start_date'), nullable_field('end_date')]
            );

            $sessionId = (int) db()->lastInsertId();
            if (!$sessionId) {
                $sessionId = (int) (db_one('SELECT id FROM sessions WHERE name = ?', [field('name')])['id'] ?? 0);
            }
            if ($isActive) {
                activate_session($sessionId, field('copy_fees', '1') === '1');
            }
            flash_set('success', 'Session saved.');
        }
        log_activity((int) $admin['id'], null, 'save_session');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/sessions');
}

$rows = sessions_all();
$pagination = pagination_state(count($rows), 12);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);
page_head('Manage Sessions | FEDPOFFA Admin', 'Manage FEDPOFFA sessions.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('sessions'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <div class="card rounded-lg p-5">
        <div class="page-kicker"><span data-icon="calendar"></span> Academic sessions</div>
        <h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Manage Sessions</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Only one session can be active. Activating a new session closes the previous one and prepares fresh wallets for the new session.</p>
      </div>

      <div class="grid gap-5 xl:grid-cols-[minmax(28rem,0.95fr)_minmax(0,1.05fr)]">
        <form class="card rounded-lg p-5" method="post" data-admin-form>
          <?= csrf_field() ?>
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Create Session</h2>
          <div class="mt-5 grid gap-4">
            <label class="form-label">Session Name<input class="input" name="name" placeholder="2026/2027" pattern="\d{4}/\d{4}" required></label>
            <label class="form-label">Status<select class="input" name="status"><option value="closed">Closed</option><option value="archived">Archived</option></select><span class="form-help">A session is open only when it is active.</span></label>
            <label class="form-label">Start Date<input class="input" name="start_date" type="date"></label>
            <label class="form-label">End Date<input class="input" name="end_date" type="date"></label>
            <label class="form-label">Make Active<select class="input" name="is_active"><option value="0">No</option><option value="1">Yes</option></select></label>
            <label class="form-label">Copy Previous Fees If Empty<select class="input" name="copy_fees"><option value="1">Yes</option><option value="0">No</option></select><span class="form-help">Useful when starting a new session with the same fee structure.</span></label>
            <button class="btn-primary" type="submit"><span data-icon="save"></span> Save Session</button>
          </div>
        </form>

        <section class="card rounded-lg p-5">
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Sessions</h2>
          <div class="table-wrap mt-5">
            <table class="data-table">
              <thead><tr><th>Session</th><th>Type</th><th>Due Date</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <tr>
                    <td data-label="Session" class="font-black text-slate-950 dark:text-white"><?= e($row['name']) ?></td>
                    <td data-label="Type"><?= $row['is_active'] ? 'Current' : 'Closed' ?></td>
                    <td data-label="Due Date"><?= e($row['end_date'] ?: '-') ?></td>
                    <td data-label="Status"><?= e($row['status']) ?></td>
                    <td data-label="Actions" class="text-right">
                      <div class="inline-flex gap-2">
                        <a class="icon-btn" href="./session_edit?id=<?= (int) $row['id'] ?>" title="Edit session"><span data-icon="edit"></span></a>
                        <?php if (!$row['is_active']): ?>
                          <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="copy_fees" value="1">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-btn" type="submit" title="Activate and prepare wallets"><span data-icon="save"></span></button>
                          </form>
                        <?php else: ?>
                          <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="prepare">
                            <input type="hidden" name="copy_fees" value="1">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-btn" type="submit" title="Prepare wallets for this session"><span data-icon="wallet"></span></button>
                          </form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Delete inactive session?')">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                          <button class="icon-btn" type="submit" title="Delete"><span data-icon="trash"></span></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php render_pagination($pagination); ?>
        </section>
      </div>
    </section>
  </main>
  <?php admin_mobile_nav(''); ?>
</div><?php modal_and_toasts(); ?></body></html>
