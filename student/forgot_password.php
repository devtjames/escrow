<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (post_request()) {
    verify_csrf();
    $student = db_one('SELECT * FROM students WHERE email = ? LIMIT 1', [field('email')]);
    if ($student) {
        $token = bin2hex(random_bytes(32));
        db_exec(
            'INSERT INTO password_resets (student_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            [(int) $student['id'], hash('sha256', $token)]
        );
        sendResetEmail($student, $token);
    }
    flash_set('success', 'If the email exists, a reset link has been sent.');
}
page_head('Forgot Password | FEDPOFFA', 'Reset student password.', ['assets/js/student.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_public_header('login'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
    <section class="view grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
      <aside class="card rounded-lg p-6"><div class="page-kicker"><span data-icon="login"></span> Password reset</div><h1 class="mt-5 text-3xl font-black text-slate-950 dark:text-white">Recover your student account</h1><p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">A secure reset link expires one hour after it is generated.</p></aside>
      <form class="card rounded-lg p-5 sm:p-7" method="post">
        <?= csrf_field() ?>
        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Forgot Password</h2>
        <div class="mt-5 grid gap-4">
          <label class="form-label">Email<input class="input" name="email" type="email" autocomplete="email" required></label>
          <button class="btn-primary w-full" type="submit"><span data-icon="save"></span> Send Reset Link</button>
          <a class="btn-secondary w-full" href="<?= e(url_for('/student/login')) ?>"><span data-icon="login"></span> Back to Login</a>
        </div>
      </form>
    </section>
  </main>
  <?php student_public_mobile_nav('login'); ?>
</div>
<?php modal_and_toasts(); ?>
</body></html>
