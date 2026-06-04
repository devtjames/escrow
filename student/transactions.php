<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();
$session = active_session();
$wallet = ensure_student_wallet((int) $student['id']);
$transactions = db_all('SELECT * FROM transactions WHERE student_id = ? ORDER BY created_at DESC', [(int) $student['id']]);
$feeItems = student_fee_items($student, $session);
$data = ['student' => ['id' => (int) $student['id'], 'name' => $student['full_name'], 'application' => $student['application_number'], 'matric' => $student['matric_number'] ?: 'Pending matric', 'program' => $student['programme_type'] . ' ' . $student['department_name'], 'level' => student_level($student, $session), 'category' => student_category($student, $session), 'session' => $session['name'] ?? 'No active session', 'dueDate' => $session['end_date'] ?? date('Y-m-d')], 'wallet' => $wallet, 'transactions' => $transactions, 'feeItems' => $feeItems];
page_head('Student Transactions | FEDPOFFA', 'FEDPOFFA student transaction history.', ['assets/js/student.js']);
echo '<script>window.StudentData=' . json_encode($data, JSON_UNESCAPED_SLASHES) . ';</script>';
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_header('transactions'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <div class="card rounded-lg p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">Transaction History</h1><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Deposits, confirmations, and receipt references</p></div><span data-status-pill class="status-pill">ACTIVE</span></div></div>
      <section class="card rounded-lg p-5"><div class="grid gap-3 md:grid-cols-[1fr_12rem]"><label class="form-label"><span class="flex items-center gap-2"><span data-icon="search"></span> Search</span><input class="input" data-search-transactions placeholder="Search reference, method, status"></label><label class="form-label">Sort<select class="input" data-sort-transactions><option value="desc">Newest first</option><option value="asc">Oldest first</option></select></label></div><div class="table-wrap mt-5"><table class="data-table"><thead><tr><th>Reference</th><th>Date</th><th>Method</th><th>Status</th><th class="text-right">Amount</th><th class="text-right">Receipt</th></tr></thead><tbody data-transaction-table></tbody></table></div></section>
    </section>
  </main>
  <?php student_mobile_nav('transactions'); ?>
</div><?php modal_and_toasts(); ?></body></html>
