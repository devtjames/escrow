<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$active = active_session();
$activeSessionId = (int) ($active['id'] ?? 0);

if (post_request()) {
    verify_csrf();
    try {
        if (field('action') !== 'send_reminders') {
            throw new RuntimeException('Invalid email action.');
        }
        $group = field('recipient_group');
        $subject = field('subject') ?: 'FEDPOFFA Payment Reminder';
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '') {
            throw new RuntimeException('Enter the reminder message.');
        }
        if (!$active) {
            throw new RuntimeException('No active session is available for reminder targeting.');
        }

        $baseSql = 'SELECT s.*, COALESCE(w.target_amount,0) target_amount, COALESCE(w.current_balance,0) current_balance, COALESCE(w.status,"active") wallet_status
                    FROM students s
                    LEFT JOIN wallets w ON w.student_id = s.id AND w.session_id = ?
                    WHERE s.status = "active" AND s.email <> ""';
        $params = [$activeSessionId];
        if ($group === 'unpaid') {
            $baseSql .= ' AND COALESCE(w.target_amount,0) > COALESCE(w.current_balance,0)';
        } elseif ($group === 'completed') {
            $baseSql .= ' AND COALESCE(w.target_amount,0) > 0 AND COALESCE(w.current_balance,0) >= COALESCE(w.target_amount,0)';
        } elseif ($group !== 'all_active') {
            throw new RuntimeException('Choose a valid recipient group.');
        }
        $students = db_all($baseSql . ' ORDER BY s.full_name', $params);
        if (!$students) {
            throw new RuntimeException('No students matched the selected recipient group.');
        }

        $sent = 0;
        $failed = 0;
        foreach ($students as $student) {
            $balance = max(0, (int) $student['target_amount'] - (int) $student['current_balance']);
            $body = '<p style="margin:0 0 14px 0">Dear ' . e($student['full_name']) . ',</p>'
                . '<p style="margin:0 0 14px 0">' . nl2br(e($message)) . '</p>'
                . email_rows([
                    'Session' => $active['name'],
                    'Amount Paid' => money_naira((int) $student['current_balance']),
                    'Outstanding Balance' => money_naira($balance),
                ])
                . email_button(app_url('/student/payment'), 'Open Student Payment Page');
            if (sendMail((string) $student['email'], $subject, $body)) {
                $sent++;
            } else {
                $failed++;
            }
        }
        log_activity((int) $admin['id'], null, 'send_email_reminders', ['group' => $group, 'sent' => $sent, 'failed' => $failed]);
        flash_set($sent > 0 ? 'success' : 'error', $sent . ' reminder(s) sent. ' . $failed . ' failed.');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/emails');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => (string) ($_GET['status'] ?? ''),
];
$where = [];
$params = [];
if ($filters['q'] !== '') {
    $where[] = '(recipient LIKE ? OR subject LIKE ?)';
    $term = '%' . $filters['q'] . '%';
    array_push($params, $term, $term);
}
if ($filters['status'] !== '') {
    $where[] = 'status = ?';
    $params[] = $filters['status'];
}
$logSql = 'SELECT * FROM email_logs';
if ($where) {
    $logSql .= ' WHERE ' . implode(' AND ', $where);
}
$logSql .= ' ORDER BY created_at DESC LIMIT 150';
$rows = db_all($logSql, $params);
$pagination = pagination_state(count($rows), 15);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);
$unpaidCount = $activeSessionId > 0
    ? (int) (db_one('SELECT COUNT(*) total FROM students s JOIN wallets w ON w.student_id = s.id AND w.session_id = ? WHERE s.status = "active" AND w.target_amount > w.current_balance', [$activeSessionId])['total'] ?? 0)
    : 0;
$sentCount = (int) (db_one('SELECT COUNT(*) total FROM email_logs WHERE status = "sent"')['total'] ?? 0);
$failedCount = (int) (db_one('SELECT COUNT(*) total FROM email_logs WHERE status = "failed"')['total'] ?? 0);

page_head('Email Center | FEDPOFFA Admin', 'View system email logs and send reminders.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('emails'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('emails'); ?>
      <div class="grid gap-4 sm:grid-cols-3">
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">SMTP Status</p><h2 class="mt-3 text-2xl font-black <?= smtp_configured() ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' ?>"><?= smtp_configured() ? 'Active' : 'Needs Setup' ?></h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Managed in Settings</p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Unpaid Wallets</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white"><?= number_format($unpaidCount) ?></h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><?= e($active['name'] ?? 'No active session') ?></p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Email Delivery</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white"><?= number_format($sentCount) ?></h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><?= number_format($failedCount) ?> failed attempts</p></article>
      </div>

      <section class="grid gap-5 xl:grid-cols-[minmax(28rem,0.95fr)_minmax(0,1.05fr)]">
        <form class="card rounded-lg p-5" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_reminders">
          <div class="page-kicker"><span data-icon="mail"></span> Email Center</div>
          <h2 class="mt-4 text-xl font-black text-slate-950 dark:text-white">Send Reminder</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Target active students by wallet status and send a branded responsive HTML email.</p>
          <div class="mt-5 grid gap-4">
            <label class="form-label">Recipient Group<select class="input" name="recipient_group" required><option value="unpaid">Unpaid students</option><option value="all_active">All active students</option><option value="completed">Completed students</option></select></label>
            <label class="form-label">Subject<input class="input" name="subject" value="FEDPOFFA Payment Reminder" required></label>
            <label class="form-label">Message<textarea class="input min-h-36" name="message" required>Kindly review your outstanding school fee balance and complete your payment before the session deadline.</textarea></label>
          </div>
          <div class="admin-sticky-actions">
            <span><?= e($active['name'] ?? 'No active session') ?></span>
            <button class="btn-primary" type="submit" data-confirm="Send this email reminder to the selected student group?"><span data-icon="mail"></span> Send Email</button>
          </div>
        </form>

        <section class="card rounded-lg p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-xl font-black text-slate-950 dark:text-white">Recent Email Logs</h2>
              <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Payment receipts, SMTP tests, password resets, and reminders.</p>
            </div>
            <a class="btn-secondary" href="./settings"><span data-icon="settings"></span> SMTP Settings</a>
          </div>

          <form class="admin-filter-bar mt-5" method="get">
            <label class="form-label">Search<input class="input" name="q" value="<?= e($filters['q']) ?>" placeholder="Recipient or subject"></label>
            <label class="form-label">Status<select class="input" name="status"><option value="">All statuses</option><option value="sent" <?= $filters['status'] === 'sent' ? 'selected' : '' ?>>Sent</option><option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Failed</option></select></label>
            <span></span><span></span>
            <button class="btn-primary" type="submit"><span data-icon="search"></span> Filter</button>
          </form>

          <?php if ($rows): ?>
            <div class="table-wrap mt-5">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Error</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td data-label="Date" class="font-black text-slate-950 dark:text-white"><?= e(date('d M Y h:i A', strtotime($row['created_at']))) ?></td>
                      <td data-label="Recipient"><?= e($row['recipient']) ?></td>
                      <td data-label="Subject"><?= e($row['subject']) ?></td>
                      <td data-label="Status"><span class="status-pill <?= $row['status'] === 'sent' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-500/15 dark:text-red-200' ?>"><?= e(strtoupper($row['status'])) ?></span></td>
                      <td data-label="Error"><?= e($row['error_message'] ?: '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="admin-empty-state mt-5"><span data-icon="mail"></span><b>No email attempts found</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Email delivery records will appear here.</p></div>
          <?php endif; ?>
          <?php render_pagination($pagination); ?>
        </section>
      </section>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
