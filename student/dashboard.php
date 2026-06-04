<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();
$session = active_session();
$wallet = ensure_student_wallet((int) $student['id']);
$transactions = db_all('SELECT * FROM transactions WHERE student_id = ? ORDER BY created_at DESC LIMIT 20', [(int) $student['id']]);
$feeItems = student_fee_items($student, $session);
$data = [
    'student' => [
        'id' => (int) $student['id'],
        'name' => $student['full_name'],
        'application' => $student['application_number'],
        'matric' => $student['matric_number'] ?: 'Pending matric',
        'program' => $student['programme_type'] . ' ' . $student['department_name'],
        'level' => student_level($student, $session),
        'category' => student_category($student, $session),
        'session' => $session['name'] ?? 'No active session',
        'dueDate' => $session['end_date'] ?? date('Y-m-d'),
    ],
    'wallet' => $wallet,
    'transactions' => $transactions,
    'feeItems' => $feeItems,
];
page_head('Student Dashboard | FEDPOFFA', 'FEDPOFFA student dashboard.', ['assets/js/student.js']);
echo '<script>window.StudentData=' . json_encode($data, JSON_UNESCAPED_SLASHES) . ';</script>';
?>
<div class="portal-shell dark:text-slate-100">
  <?php student_header('dashboard'); ?>
  <main class="mx-auto max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <section class="view grid gap-5 py-4">
      <div class="card rounded-lg p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><h1 class="text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">Student Dashboard</h1><p class="mt-1 truncate text-sm font-semibold text-slate-500 dark:text-slate-400"><span data-text="studentName"></span> | <span data-text="matric"></span></p></div><span data-status-pill class="status-pill">ACTIVE</span></div></div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Total fee</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white" data-text="totalFee">...</h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Session target</p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Paid amount</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white" data-text="paidAmount">...</h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><span data-text="progress"></span> complete</p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Remaining</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white" data-text="remainingAmount">...</h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Balance due</p></article>
        <article class="card rounded-lg p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Due date</p><h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white" data-text="dueDate">...</h2><p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400" data-text="session">...</p></article>
      </div>
      <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="card rounded-lg p-5"><div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="text-xl font-black text-slate-950 dark:text-white">Fee Overview</h2><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400"><span data-text="program"></span> | <span data-text="level"></span> | <span data-text="category"></span></p></div><span data-status-pill class="status-pill">ACTIVE</span></div><div class="mt-7"><div class="mb-2 flex justify-between text-sm font-bold text-slate-600 dark:text-slate-300"><span>Payment progress</span><span data-text="progress">0%</span></div><div class="h-4 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full rounded-full bg-gradient-to-r from-fedpoffa-blue to-green-500 transition-[width] duration-700" style="width:0%" data-progress-bar></div></div></div><div class="mt-7 flex flex-col gap-3 sm:flex-row"><button class="btn-primary w-full sm:w-auto" data-open-deposit data-deposit-button="Deposit" type="button"></button><a class="btn-secondary w-full sm:w-auto" href="./payment"><span data-icon="wallet"></span> View Checkout</a><a class="btn-secondary w-full sm:w-auto" href="./biodata"><span data-icon="userPlus"></span> Biodata</a><button class="btn-secondary w-full sm:w-auto" data-open-receipt type="button"><span data-icon="receipt"></span> Receipt Preview</button></div></section>
        <aside class="card rounded-lg p-5"><h2 class="text-xl font-black text-slate-950 dark:text-white">Wallet Activity</h2><div class="mt-4 grid gap-3" data-recent-transactions></div></aside>
      </div>
    </section>
  </main>
  <button class="btn-primary fixed bottom-20 right-4 z-40 shadow-2xl md:bottom-6" data-open-deposit data-deposit-button="Deposit" type="button"></button>
  <?php student_mobile_nav('dashboard'); ?>
</div>
<?php modal_and_toasts(); ?>
</body></html>
