<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$id = (int) field('id');
$session = db_one('SELECT * FROM sessions WHERE id = ?', [$id]);
if (!$session) {
    flash_set('error', 'Session not found.');
    redirect_to('/admin/sessions');
}

if (post_request()) {
    verify_csrf();
    try {
        $makeActive = field('is_active') === '1';
        db_exec(
            'UPDATE sessions SET name = ?, status = ?, start_date = ?, end_date = ? WHERE id = ?',
            [field('name'), $makeActive ? 'open' : field('status'), nullable_field('start_date'), nullable_field('end_date'), $id]
        );
        if ($makeActive) {
            activate_session($id, field('copy_fees', '1') === '1');
        } else {
            close_inactive_open_sessions();
        }
        log_activity((int) $admin['id'], null, 'update_session', ['id' => $id]);
        flash_set('success', 'Session updated.');
        redirect_to('/admin/session_edit?id=' . $id);
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        $session = array_merge($session, $_POST);
    }
}

page_head('Edit Session | FEDPOFFA Admin', 'Edit academic session.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('sessions'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><div class="page-kicker"><span data-icon="edit"></span> Academic sessions</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Edit Session</h1></div>
        <a class="btn-secondary" href="./sessions"><span data-icon="x"></span> Back</a>
      </div>
      <div class="form-grid mt-6">
        <label class="form-label">Session Name<input class="input" name="name" value="<?= e($session['name']) ?>" pattern="\d{4}/\d{4}" required></label>
        <label class="form-label">Status<select class="input" name="status"><option value="closed" <?= $session['status'] === 'closed' ? 'selected' : '' ?>>Closed</option><option value="archived" <?= $session['status'] === 'archived' ? 'selected' : '' ?>>Archived</option><option value="open" <?= $session['status'] === 'open' ? 'selected' : '' ?>>Open</option></select><span class="form-help">Only the active session should remain open.</span></label>
        <label class="form-label">Start Date<input class="input" name="start_date" type="date" value="<?= e($session['start_date']) ?>"></label>
        <label class="form-label">End Date<input class="input" name="end_date" type="date" value="<?= e($session['end_date']) ?>"></label>
        <label class="form-label">Make Active<select class="input" name="is_active"><option value="0" <?= !$session['is_active'] ? 'selected' : '' ?>>No</option><option value="1" <?= $session['is_active'] ? 'selected' : '' ?>>Yes</option></select></label>
        <label class="form-label">Copy Previous Fees If Empty<select class="input" name="copy_fees"><option value="1">Yes</option><option value="0">No</option></select></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Update Session</button>
    </form>
  </main>
  <?php admin_mobile_nav(''); ?>
</div><?php modal_and_toasts(); ?></body></html>
