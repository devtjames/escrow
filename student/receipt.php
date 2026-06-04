<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();
$session = active_session();
$wallet = ensure_student_wallet((int) $student['id']);
$reference = field('ref');
$params = [(int) $student['id']];
$sql = 'SELECT * FROM transactions WHERE student_id = ?';
if ($reference !== '') {
    $sql .= ' AND reference = ?';
    $params[] = $reference;
}
$sql .= ' ORDER BY created_at DESC LIMIT 1';
$transaction = db_one($sql, $params);
page_head('Printable Receipt | FEDPOFFA', 'Printable FEDPOFFA student receipt.');
?>
  <main class="min-h-screen bg-slate-100 px-4 py-6 dark:bg-slate-950 sm:py-10">
    <div class="no-print mx-auto mb-5 flex max-w-3xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <a class="btn-secondary" href="./dashboard"><span data-icon="x"></span> Back</a>
      <button class="btn-primary" type="button" onclick="window.print()"><span data-icon="printer"></span> Print Receipt</button>
    </div>
    <section>
      <div id="receiptPrintable" class="receipt-paper mx-auto rounded-lg border border-slate-200 p-6 shadow-sm sm:p-8">
        <div class="flex items-start justify-between gap-5 border-b border-slate-200 pb-5">
          <div><p class="text-xs font-black uppercase tracking-wide text-fedpoffa-blue">Federal Polytechnic Offa</p><h1 class="mt-1 text-2xl font-black">School Fee Payment Receipt</h1><p class="mt-1 text-sm font-semibold text-slate-500">PMB 420, Offa, Kwara State</p></div>
          <img class="h-14 w-auto max-w-[11rem] object-contain" src="<?= e(asset_path('assets/img/logo2.png')) ?>" alt="Federal Polytechnic Offa">
        </div>
        <?php if (!$transaction): ?>
          <p class="mt-6 text-center text-sm font-bold text-slate-500">No successful payment receipt is available yet.</p>
        <?php else: ?>
          <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
            <?php $rows = [
                'Student' => $student['full_name'],
                'Application Number' => $student['application_number'],
                'Matric Number' => $student['matric_number'] ?: 'Pending',
                'Fee Type' => 'School Fees / Wallet Deposit',
                'Session' => $session['name'] ?? '',
                'Reference' => $transaction['reference'],
                'Date' => date('d M Y', strtotime($transaction['created_at'])),
                'Gateway' => $transaction['gateway'],
                'Amount' => money_naira((int) $transaction['amount']),
                'Status' => strtoupper($transaction['status']),
            ]; ?>
            <?php foreach ($rows as $label => $value): ?><div class="rounded-md border border-slate-200 p-3"><span class="block text-xs font-black uppercase text-slate-500"><?= e($label) ?></span><span class="mt-1 block font-black text-slate-950"><?= e($value) ?></span></div><?php endforeach; ?>
          </div>
          <div class="mt-8 grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-2">
            <div><p class="text-xs font-black uppercase text-slate-500">Student balance due</p><p class="mt-1 text-xl font-black"><?= e(money_naira(wallet_remaining($wallet))) ?></p></div>
            <div class="text-left sm:text-right"><p class="text-xs font-black uppercase text-slate-500">Generated</p><p class="mt-1 text-sm font-bold"><?= e(date('d M Y')) ?></p></div>
          </div>
          <p class="mt-8 text-center text-xs font-semibold text-slate-500">This receipt is valid only after Paystack verification against the stored transaction reference.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
<?php modal_and_toasts(); ?>
</body></html>
