<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (current_admin()) {
    redirect_to('/admin/dashboard');
}
if (post_request()) {
    verify_csrf();
    if (admin_login(field('identifier'), field('password'))) {
        redirect_to('/admin/dashboard');
    }
    flash_set('error', 'Invalid admin credentials or inactive account.');
}
page_head('Admin Login | FEDPOFFA', 'FEDPOFFA admin login.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_public_header('login'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8"><section class="view grid gap-6 lg:grid-cols-[0.85fr_1.15fr]"><aside class="card rounded-lg p-6"><div class="page-kicker"><span data-icon="building"></span> Admin access</div><h1 class="mt-5 text-3xl font-black text-slate-950 dark:text-white">Finance office management console</h1><p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">This admin area is intentionally separate from all student navigation.</p></aside><form class="card rounded-lg p-5 sm:p-7" method="post" data-admin-login><?= csrf_field() ?><h2 class="text-2xl font-black text-slate-950 dark:text-white">Admin Login</h2><div class="mt-5 grid gap-4"><label class="form-label">Staff ID or Email<input class="input" name="identifier" autocomplete="username" placeholder="FPO/FIN/001" required></label><label class="form-label password-field" data-password-field>Password<input class="input" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label><button class="btn-primary w-full" type="submit"><span data-icon="login"></span> Login</button><a class="btn-secondary w-full" href="<?= e(url_for('/admin/register')) ?>"><span data-icon="userPlus"></span> Register Admin</a></div></form></section></main>
</div><?php modal_and_toasts(); ?></body></html>
