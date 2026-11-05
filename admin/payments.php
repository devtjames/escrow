<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_admin();
$active = active_session();

if (post_request()) {
    verify_csrf();
    try {
        if (field('action') === 'resend_receipt') {
            $transactionId = (int) field('transaction_id');
            $ok = sendTransactionReceiptEmailOnce($transactionId);
            log_activity((int) $admin['id'], null, 'resend_payment_receipt', ['transaction_id' => $transactionId]);
            flash_set($ok ? 'success' : 'error', $ok ? 'Receipt email sent or was already sent.' : 'Receipt email failed. Check Email Logs for details.');
        } elseif (field('action') === 'bulk_receipts') {
            $ids = array_values(array_unique(array_map('intval', (array) ($_POST['selected_ids'] ?? []))));
            if (!$ids) {
                throw new RuntimeException('Select at least one transaction.');
            }
            $sent = 0;
            foreach ($ids as $id) {
                if (sendTransactionReceiptEmailOnce($id)) {
                    $sent++;
                }
            }
            log_activity((int) $admin['id'], null, 'bulk_resend_receipts', ['count' => count($ids)]);
            flash_set($sent > 0 ? 'success' : 'error', $sent . ' receipt email(s) sent or already delivered.');
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect_to('/admin/payments');
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => (string) ($_GET['status'] ?? ''),
    'gateway' => (string) ($_GET['gateway'] ?? ''),
    'date_from' => (string) ($_GET['date_from'] ?? ''),
    'date_to' => (string) ($_GET['date_to'] ?? ''),
];
$where = [];
$params = [];
if ($filters['q'] !== '') {
    $where[] = '(t.reference LIKE ? OR s.full_name LIKE ? OR s.application_number LIKE ? OR s.matric_number LIKE ?)';
    $term = '%' . $filters['q'] . '%';
    array_push($params, $term, $term, $term, $term);
}
if ($filters['status'] !== '') {
    $where[] = 't.status = ?';
    $params[] = $filters['status'];
}
if ($filters['gateway'] !== '') {
    $where[] = 't.gateway = ?';
    $params[] = $filters['gateway'];
}
if ($filters['date_from'] !== '') {
    $where[] = 'DATE(t.created_at) >= ?';
    $params[] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $where[] = 'DATE(t.created_at) <= ?';
    $params[] = $filters['date_to'];
}
$sql = 'SELECT t.*, s.full_name, s.matric_number, s.application_number, s.next_of_kin_email, ss.name session_name
        FROM transactions t
        JOIN students s ON s.id = t.student_id
        JOIN sessions ss ON ss.id = t.session_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY t.created_at DESC';
$rows = db_all($sql, $params);
$gateways = db_all('SELECT DISTINCT gateway FROM transactions ORDER BY gateway');
$totalAmount = array_sum(array_map(static fn ($row) => (int) $row['amount'], array_filter($rows, static fn ($row) => $row['status'] === 'successful')));
$pagination = pagination_state(count($rows), 15);
$rows = array_slice($rows, (int) $pagination['offset'], (int) $pagination['per_page']);

page_head('Payment Records | FEDPOFFA Admin', 'Manage FEDPOFFA payment records.', ['assets/js/admin.js']);
?>
<div class="portal-shell dark:text-slate-100">
  <?php admin_header('payments'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <?php admin_breadcrumbs('payments'); ?>
      <div class="card rounded-lg p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="page-kicker"><span data-icon="receipt"></span> Payment records</div>
            <h2 class="mt-4 text-3xl font-black text-slate-950 dark:text-white">Student Payment History</h2>
            <p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><?= number_format(count($rows)) ?> record(s), <?= e(money_naira($totalAmount)) ?> successful in this view.</p>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
            <a class="btn-secondary" href="./emails"><span data-icon="mail"></span> Email Logs</a>
            <button class="btn-secondary" type="button" onclick="window.print()"><span data-icon="printer"></span> Print Preview</button>
          </div>
        </div>
      </div>

      <section class="card rounded-lg p-5">
        <form class="admin-filter-bar" method="get">
          <label class="form-label">Search<input class="input" name="q" value="<?= e($filters['q']) ?>" placeholder="Reference, student, matric"></label>
          <label class="form-label">Status<select class="input" name="status"><option value="">All statuses</option><?php foreach (['pending','successful','failed'] as $status): ?><option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
          <label class="form-label">Gateway<select class="input" name="gateway"><option value="">All gateways</option><?php foreach ($gateways as $gateway): ?><option value="<?= e($gateway['gateway']) ?>" <?= $filters['gateway'] === $gateway['gateway'] ? 'selected' : '' ?>><?= e($gateway['gateway']) ?></option><?php endforeach; ?></select></label>
          <label class="form-label">From<input class="input" name="date_from" type="date" value="<?= e($filters['date_from']) ?>"></label>
          <label class="form-label">To<input class="input" name="date_to" type="date" value="<?= e($filters['date_to']) ?>"></label>
          <button class="btn-primary" type="submit"><span data-icon="search"></span> Filter</button>
        </form>

        <form method="post" class="mt-5">
          <?= csrf_field() ?>
          <div class="admin-bulk-bar">
            <span>Bulk resend receipt emails for selected payment records.</span>
            <button class="btn-secondary" type="submit" name="action" value="bulk_receipts" data-confirm="Resend receipt emails for selected transactions?"><span data-icon="mail"></span> Send Receipts</button>
          </div>

          <?php if ($rows): ?>
            <div class="table-wrap mt-5">
              <table class="data-table">
                <thead><tr><th><input type="checkbox" data-check-all></th><th>Reference</th><th>Student</th><th>Method</th><th>Amount</th><th>Status</th><th>Email</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td data-label="Select"><input type="checkbox" name="selected_ids[]" value="<?= (int) $row['id'] ?>" data-check-row></td>
                      <td data-label="Reference" class="font-black text-slate-950 dark:text-white"><?= e($row['reference']) ?><div class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= e(date('d M Y h:i A', strtotime($row['created_at']))) ?></div></td>
                      <td data-label="Student"><?= e($row['full_name']) ?><div class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= e($row['matric_number'] ?: $row['application_number']) ?> · <?= e($row['session_name']) ?></div></td>
                      <td data-label="Method"><?= e($row['gateway']) ?></td>
                      <td data-label="Amount" class="font-black"><?= e(money_naira((int) $row['amount'])) ?></td>
                      <td data-label="Status"><span class="status-pill <?= $row['status'] === 'successful' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : ($row['status'] === 'failed' ? 'bg-red-50 text-red-800 dark:bg-red-500/15 dark:text-red-200' : 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200') ?>"><?= e(strtoupper($row['status'])) ?></span></td>
                      <td data-label="Email"><?php
                        $studentReceiptSent = !empty($row['receipt_email_sent_at']);
                        $nextOfKinRequired = !empty($row['next_of_kin_email']);
                        $nextOfKinReceiptSent = !$nextOfKinRequired || !empty($row['next_of_kin_receipt_email_sent_at']);
                        if ($studentReceiptSent && $nextOfKinReceiptSent) {
                            echo '<span class="status-pill bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300">' . ($nextOfKinRequired ? 'BOTH SENT' : 'SENT') . '</span>';
                        } elseif ($studentReceiptSent || $nextOfKinReceiptSent) {
                            echo '<span class="status-pill bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">PARTIAL</span>';
                        } else {
                            echo '<span class="status-pill bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">PENDING</span>';
                        }
                      ?></td>
                      <td data-label="Actions" class="text-right">
                        <div class="inline-flex gap-2">
                          <a class="icon-btn" href="../student/receipt?ref=<?= urlencode((string) $row['reference']) ?>" target="_blank" title="View receipt"><span data-icon="receipt"></span></a>
                          <button class="icon-btn" type="submit" name="action" value="resend_receipt" formaction="./payments?transaction_id=<?= (int) $row['id'] ?>" title="Send receipt email"><span data-icon="mail"></span></button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="admin-empty-state mt-5"><span data-icon="receipt"></span><b>No payment records found</b><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Clear filters or wait for student deposits.</p></div>
          <?php endif; ?>
          <?php render_pagination($pagination); ?>
        </form>
      </section>
    </section>
  </main>
</div><?php modal_and_toasts(); ?></body></html>
