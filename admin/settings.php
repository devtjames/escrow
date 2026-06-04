<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin(['super_admin']);

if (post_request()) {
    verify_csrf();
    if (field('action') === 'test_email') {
        $recipient = field('test_email');
        if ($recipient === '') {
            flash_set('error', 'Enter an email address for the test.');
        } else {
            $content = '<p style="margin:0 0 14px 0">Hello,</p>'
                . '<p style="margin:0 0 18px 0">This confirms that the FEDPOFFA payment system can send email through the configured SMTP account.</p>';
            $ok = sendMail($recipient, 'FEDPOFFA SMTP Test', $content);
            flash_set($ok ? 'success' : 'error', $ok ? 'Test email sent.' : 'Test email failed. Check Email Logs for the exact error.');
        }
    } else {
        foreach (['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_name'] as $key) {
            setting_set($key, field($key));
        }
        log_activity((int) $admin['id'], null, 'save_settings');
        flash_set('success', 'SMTP settings saved.');
    }
    redirect_to('/admin/settings');
}

page_head('Settings | FEDPOFFA Admin', 'Manage SMTP settings.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('settings'); ?>
  <main class="mx-auto max-w-6xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('settings'); ?>
      <div class="card rounded-lg p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="page-kicker"><span data-icon="settings"></span> System settings</div>
            <h2 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">SMTP Settings</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Email delivery credentials are stored in settings and can override .env values.</p>
          </div>
          <span class="status-pill <?= smtp_configured() ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' ?>"><?= smtp_configured() ? 'SMTP ACTIVE' : 'SMTP INCOMPLETE' ?></span>
        </div>
      </div>

      <section class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <form class="card rounded-lg p-5 sm:p-7" method="post">
          <?= csrf_field() ?>
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Mail Server Credentials</h2>
          <div class="form-grid mt-6">
            <?php foreach (['smtp_host'=>'SMTP Host','smtp_port'=>'SMTP Port','smtp_user'=>'SMTP User','smtp_pass'=>'SMTP Password','smtp_from'=>'From Email','smtp_name'=>'From Name'] as $key => $label): ?>
              <label class="form-label"><?= e($label) ?><input class="input" name="<?= e($key) ?>" value="<?= e((string) setting_get($key, env_value(strtoupper($key), ''))) ?>" <?= $key === 'smtp_pass' ? 'type="password"' : '' ?>></label>
            <?php endforeach; ?>
          </div>
          <div class="admin-sticky-actions">
            <span>Settings affect receipts, reminders, password resets, and SMTP tests.</span>
            <button class="btn-primary" type="submit"><span data-icon="save"></span> Save Settings</button>
          </div>
        </form>

        <aside class="grid gap-5">
          <form class="card rounded-lg p-5" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_email">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Send Test Email</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Use this after changing SMTP credentials or hosting environment.</p>
            <div class="mt-5 grid gap-4">
              <label class="form-label">Recipient Email<input class="input" name="test_email" type="email" required></label>
              <button class="btn-secondary" type="submit"><span data-icon="mail"></span> Send Test</button>
            </div>
          </form>

          <section class="card rounded-lg p-5">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">Email Tools</h2>
            <div class="mt-5 grid gap-3">
              <a class="btn-secondary justify-start" href="./emails"><span data-icon="mail"></span> Email Center</a>
              <a class="btn-secondary justify-start" href="./payments"><span data-icon="receipt"></span> Payment Receipts</a>
            </div>
          </section>
        </aside>
      </section>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
