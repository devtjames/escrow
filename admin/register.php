<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (post_request()) {
    verify_csrf();
    if (field('password') !== field('password_confirm') || !password_is_strong(field('password'))) {
        flash_set('error', 'Passwords must match and include upper/lowercase letters and a number.');
    } elseif (db_one('SELECT id FROM admins WHERE staff_id = ? LIMIT 1', [field('staff_id')])) {
        flash_set('error', 'An admin account request already exists with this staff ID.');
    } elseif (db_one('SELECT id FROM admins WHERE email = ? LIMIT 1', [field('email')])) {
        flash_set('error', 'An admin account request already exists with this email address.');
    } else {
        try {
            db_exec(
                'INSERT INTO admins (full_name, staff_id, email, password_hash, role, status) VALUES (?, ?, ?, ?, "admin", "pending")',
                [field('full_name'), field('staff_id'), field('email'), password_hash(field('password'), PASSWORD_BCRYPT)]
            );
            flash_set('success', 'Admin request submitted. A super admin must activate it.');
            redirect_to('/admin/login');
        } catch (Throwable $e) {
            flash_set('error', 'Request failed: ' . $e->getMessage());
        }
    }
}
page_head('Admin Registration | FEDPOFFA', 'FEDPOFFA admin registration.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_public_header('register'); ?>
  <main class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8"><form class="view card rounded-lg p-5 sm:p-7" method="post" data-admin-register><?= csrf_field() ?><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><div class="page-kicker"><span data-icon="userPlus"></span> Admin registration</div><h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Request Admin Account</h1><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">For finance, bursary, ICT, and authorized school officers.</p></div><a class="btn-secondary" href="<?= e(url_for('/admin/login')) ?>">Login</a></div><div class="form-grid mt-6"><label class="form-label">Full Name<input class="input" name="full_name" required></label><label class="form-label">Staff ID<input class="input" name="staff_id" placeholder="FPO/FIN/001" required></label><label class="form-label">Official Email<input class="input" name="email" type="email" required></label><label class="form-label">Role<select class="input" disabled><option>Admin request</option></select></label><label class="form-label password-field" data-password-field>Password<input class="input" name="password" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label><label class="form-label password-field" data-password-field>Confirm Password<input class="input" name="password_confirm" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><span data-icon="eye"></span></button></label></div><button class="btn-primary mt-6 w-full sm:w-auto" type="submit"><span data-icon="save"></span> Submit Request</button></form></main>
</div><?php modal_and_toasts(); ?></body></html>
