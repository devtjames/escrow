<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$active = active_session();
$activeSessionId = (int) ($active['id'] ?? 0);
$totalStudents = (int) (db_one('SELECT COUNT(*) total FROM students')['total'] ?? 0);
$sessionStudents = $activeSessionId > 0
    ? (int) (db_one('SELECT COUNT(*) total FROM students WHERE admission_session_id = ?', [$activeSessionId])['total'] ?? 0)
    : 0;
$walletDeposits = $activeSessionId > 0
    ? (int) (db_one('SELECT COALESCE(SUM(amount),0) total FROM transactions WHERE status = "successful" AND session_id = ?', [$activeSessionId])['total'] ?? 0)
    : 0;
$pendingBalance = $activeSessionId > 0
    ? (int) (db_one('SELECT COALESCE(SUM(GREATEST(target_amount - current_balance, 0)),0) total FROM wallets WHERE session_id = ?', [$activeSessionId])['total'] ?? 0)
    : 0;
$receiptsSent = (int) (db_one('SELECT COUNT(*) total FROM transactions WHERE receipt_email_sent_at IS NOT NULL')['total'] ?? 0);
$configuredWallets = $activeSessionId > 0
    ? (int) (db_one('SELECT COUNT(*) total FROM wallets WHERE session_id = ? AND target_amount > 0', [$activeSessionId])['total'] ?? 0)
    : 0;
$activeStudents = (int) (db_one('SELECT COUNT(*) total FROM students WHERE status = "active"')['total'] ?? 0);
$targetPercent = $activeStudents > 0 ? min(100, (int) round(($configuredWallets / $activeStudents) * 100)) : 0;
$recentPayments = db_all(
    'SELECT t.reference, t.gateway, t.amount, t.status, t.created_at, s.full_name, s.programme_type, d.name department_name
     FROM transactions t
     JOIN students s ON s.id = t.student_id
     JOIN departments d ON d.id = s.department_id
     ORDER BY t.created_at DESC
     LIMIT 8'
);
$activityRows = db_all(
    'SELECT al.action, al.created_at, a.full_name admin_name, s.full_name student_name
     FROM activity_logs al
     LEFT JOIN admins a ON a.id = al.admin_id
     LEFT JOIN students s ON s.id = al.student_id
     ORDER BY al.created_at DESC
     LIMIT 5'
);

function dashboard_compact_money(int $amount): string
{
    $abs = abs($amount);
    if ($abs >= 1000000000) {
        return 'NGN ' . rtrim(rtrim(number_format($amount / 1000000000, 1), '0'), '.') . 'B';
    }
    if ($abs >= 1000000) {
        return 'NGN ' . rtrim(rtrim(number_format($amount / 1000000, 1), '0'), '.') . 'M';
    }
    if ($abs >= 100000) {
        return 'NGN ' . rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.') . 'K';
    }
    return money_naira($amount);
}

function dashboard_metric_value(string $type, int $value): string
{
    if ($type === 'money') {
        return dashboard_compact_money($value);
    }
    return number_format($value);
}

page_head('Admin Dashboard | FEDPOFFA', 'FEDPOFFA admin dashboard.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('dashboard'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('dashboard'); ?>

      <section class="grid gap-5 lg:grid-cols-[minmax(0,1.8fr)_minmax(18rem,0.75fr)]">
        <div class="card rounded-lg p-5 sm:p-6">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div class="page-kicker"><span data-icon="table"></span> Admin control room</div>
              <h2 class="mt-4 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">School Fees Collection Control Room</h2>
              <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">Monitor active wallets, payment confirmations, fee targets, receipt delivery, and administrative actions from one connected workspace.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
              <a class="btn-secondary" href="./payments"><span data-icon="receipt"></span> View Payments</a>
              <a class="btn-primary" href="./fees"><span data-icon="wallet"></span> Create Fee</a>
            </div>
          </div>
        </div>

        <aside class="card rounded-lg p-5">
          <h2 class="text-sm font-black uppercase text-slate-500 dark:text-slate-400">Active Session</h2>
          <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-500/25 dark:bg-green-500/10">
            <b class="text-2xl font-black text-slate-950 dark:text-white"><?= e($active['name'] ?? 'None') ?></b>
            <span class="status-pill bg-green-100 text-fedpoffa-blue dark:bg-green-500/15 dark:text-green-300"><?= $active ? 'OPEN' : 'CLOSED' ?></span>
          </div>
          <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full rounded-full bg-fedpoffa-gold" style="width: <?= $targetPercent ?>%"></div></div>
          <p class="mt-3 text-xs font-bold text-slate-500 dark:text-slate-400"><?= $targetPercent ?>% of expected wallet targets configured.</p>
        </aside>
      </section>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ([
            ['Total Students', 'number', $totalStudents, '+' . number_format($sessionStudents) . ' this session'],
            ['Wallet Deposits', 'money', $walletDeposits, 'Successful payments'],
            ['Pending Balance', 'money', $pendingBalance, 'Across active wallets'],
            ['Receipts Sent', 'number', $receiptsSent, smtp_configured() ? 'Email delivery active' : 'SMTP not configured'],
        ] as $metric): ?>
          <article class="card rounded-lg p-5">
            <p class="text-xs font-black uppercase text-slate-500 dark:text-slate-400"><?= e($metric[0]) ?></p>
            <h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white"><?= e(dashboard_metric_value($metric[1], (int) $metric[2])) ?></h2>
            <p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><?= e($metric[3]) ?></p>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="grid gap-5">
        <div class="card min-w-0 rounded-lg p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-xl font-black text-slate-950 dark:text-white">Recent Payment Activity</h2><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Latest 8 successful or pending transaction records.</p></div>
            <a class="btn-secondary" href="./payments"><span data-icon="receipt"></span> Open Payments</a>
          </div>
          <?php if ($recentPayments): ?>
            <div class="table-wrap mt-5">
              <table class="data-table">
                <thead><tr><th>Student</th><th>Programme</th><th>Reference</th><th>Status</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                  <?php foreach ($recentPayments as $row): ?>
                    <tr>
                      <td data-label="Student" class="font-black text-slate-950 dark:text-white"><?= e($row['full_name']) ?></td>
                      <td data-label="Programme"><?= e($row['programme_type'] . ' ' . $row['department_name']) ?></td>
                      <td data-label="Reference"><?= e($row['reference']) ?></td>
                      <td data-label="Status"><span class="status-pill <?= $row['status'] === 'successful' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' ?>"><?= e(strtoupper($row['status'])) ?></span></td>
                      <td data-label="Amount" class="text-right font-black"><?= e(money_naira((int) $row['amount'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="admin-empty-state mt-5"><span data-icon="receipt"></span><b>No payment activity yet</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Successful student deposits will appear here.</p></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <section class="card rounded-lg p-5">
          <h2 class="text-xl font-black text-slate-950 dark:text-white">Quick Operations</h2>
          <div class="mt-5 grid gap-3">
            <a class="btn-secondary quick-op-btn justify-start" href="./fees"><span data-icon="wallet"></span> Configure faculty school fee</a>
            <a class="btn-secondary quick-op-btn justify-start" href="./sessions"><span data-icon="calendar"></span> Activate next session</a>
            <a class="btn-secondary quick-op-btn justify-start" href="./emails"><span data-icon="mail"></span> Send payment reminders</a>
            <a class="btn-secondary quick-op-btn justify-start" href="./students"><span data-icon="users"></span> Manage student records</a>
          </div>
        </section>

        <section class="card rounded-lg p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-xl font-black text-slate-950 dark:text-white">Recent Admin Activity</h2><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Latest 5 administrative events.</p></div><a class="btn-secondary" href="./students"><span data-icon="users"></span> Manage Records</a></div>
          <div class="admin-audit-list is-compact mt-4">
            <?php foreach ($activityRows as $row): ?>
              <div class="admin-audit-item"><span class="admin-audit-dot"></span><div><b class="text-sm text-slate-950 dark:text-white"><?= e(ucwords(str_replace('_', ' ', $row['action']))) ?></b><p class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= e($row['admin_name'] ?: $row['student_name'] ?: 'System') ?> · <?= e(date('d M Y h:i A', strtotime($row['created_at']))) ?></p></div></div>
            <?php endforeach; ?>
            <?php if (!$activityRows): ?><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No recent activity yet.</p><?php endif; ?>
          </div>
        </section>
      </section>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
