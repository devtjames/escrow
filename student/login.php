<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (current_student()) {
    redirect_to('/student/dashboard');
}
if (post_request()) {
    verify_csrf();
    if (student_login(field('identifier'), field('password'))) {
        redirect_to('/student/dashboard');
    }
    flash_set('error', 'Invalid login details or too many attempts. Try again shortly.');
}
page_head('Student Login | FEDPOFFA', 'FEDPOFFA student login.', ['assets/js/student.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_public_header('login'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
    <section class="view grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
      <aside class="card rounded-lg p-6"><div class="page-kicker"><span data-icon="login"></span> Student access</div><h1 class="mt-5 text-3xl font-black text-slate-950 dark:text-white">Login to continue your fee payment</h1><p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Use your email, application number, or matric number to open the student dashboard, add funds, view receipts, and track fee clearance.</p></aside>
      <form class="card rounded-lg p-5 sm:p-7" method="post" data-student-login>
        <?= csrf_field() ?>
        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Student Login</h2>
        <div class="mt-5 grid gap-4">
          <label class="form-label">Email, Application Number, or Matric Number<input class="input" name="identifier" autocomplete="username" placeholder="FPO/HND/24/0412" required></label>
          <label class="form-label password-field" data-password-field>Password<input class="input" name="password" type="password" autocomplete="current-password" placeholder="Enter password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label>
          <button class="btn-primary w-full" type="submit"><span data-icon="login"></span> Login</button>
          <a class="btn-secondary w-full" href="<?= e(url_for('/student/register')) ?>"><span data-icon="userPlus"></span> Create Student Account</a>
          <a class="text-center text-sm font-bold text-fedpoffa-blue dark:text-green-300" href="<?= e(url_for('/student/forgot_password')) ?>">Forgot password?</a>
        </div>
      </form>
    </section>
  </main>
  <?php student_public_mobile_nav('login'); ?>
</div>
<?php modal_and_toasts(); ?>
</body></html>
