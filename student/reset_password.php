<?php
require_once __DIR__ . '/../app/bootstrap.php';
$token = field('token');
$reset = $token ? db_one(
    'SELECT pr.*, s.email, s.full_name FROM password_resets pr
     JOIN students s ON s.id = pr.student_id
     WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at >= NOW()
     ORDER BY pr.id DESC LIMIT 1',
    [hash('sha256', $token)]
) : null;

if (post_request()) {
    verify_csrf();
    $token = field('token');
    $reset = db_one(
        'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1',
        [hash('sha256', $token)]
    );
    if (!$reset) {
        flash_set('error', 'The reset link is invalid or expired.');
    } elseif (field('password') !== field('password_confirm') || !password_is_strong(field('password'))) {
        flash_set('error', 'Passwords must match and include upper/lowercase letters and a number.');
    } else {
        db_exec('UPDATE students SET password_hash = ? WHERE id = ?', [password_hash(field('password'), PASSWORD_BCRYPT), (int) $reset['student_id']]);
        db_exec('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [(int) $reset['id']]);
        flash_set('success', 'Password updated. You can now login.');
        redirect_to('/student/login');
    }
}

page_head('Reset Password | FEDPOFFA', 'Set a new student password.', ['assets/js/student.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_public_header('login'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
    <form class="view card rounded-lg p-5 sm:p-7" method="post">
      <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="page-kicker"><span data-icon="save"></span> Password reset</div>
      <h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Set New Password</h1>
      <?php if (!$reset): ?><p class="mt-4 text-sm font-bold text-red-800 dark:text-red-200">This reset link is invalid or expired.</p><?php endif; ?>
      <div class="form-grid mt-6">
        <label class="form-label">Password<input class="input" name="password" type="password" required <?= !$reset ? 'disabled' : '' ?>></label>
        <label class="form-label">Confirm Password<input class="input" name="password_confirm" type="password" required <?= !$reset ? 'disabled' : '' ?>></label>
      </div>
      <button class="btn-primary mt-6 w-full sm:w-auto" type="submit" <?= !$reset ? 'disabled' : '' ?>><span data-icon="save"></span> Update Password</button>
      <a class="btn-secondary mt-6 w-full sm:w-auto" href="<?= e(url_for('/student/login')) ?>">Back to Login</a>
    </form>
  </main>
  <?php student_public_mobile_nav('login'); ?>
</div>
<?php modal_and_toasts(); ?>
</body></html>
