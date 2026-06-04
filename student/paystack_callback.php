<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_student();
$reference = field('reference');
if ($reference === '') {
    flash_set('error', 'Missing payment reference.');
    redirect_to('/student/payment');
}

try {
    $data = paystack_verify($reference);
    complete_paystack_transaction($data);
    db_exec('UPDATE pending_payments SET status = "completed" WHERE reference = ?', [$reference]);
    $transaction = db_one('SELECT * FROM transactions WHERE reference = ?', [$reference]);
    if (!empty($transaction['receipt_email_sent_at'])) {
        flash_set('success', 'Payment verified successfully. A system receipt email has been sent.');
    } else {
        flash_set('warning', 'Payment verified successfully, but the system receipt email was not delivered. Please contact admin to check email settings.');
    }
    redirect_to('/student/receipt?ref=' . urlencode($reference));
} catch (Throwable $e) {
    $verifiedTransaction = db_one('SELECT id FROM transactions WHERE reference = ?', [$reference]);
    if ($verifiedTransaction) {
        db_exec('UPDATE pending_payments SET status = "completed" WHERE reference = ?', [$reference]);
        flash_set('warning', 'Payment verified, but receipt email delivery failed. Admin can resend it after SMTP is fixed.');
        redirect_to('/student/receipt?ref=' . urlencode($reference));
    }
    db_exec('UPDATE pending_payments SET status = "failed" WHERE reference = ?', [$reference]);
    flash_set('error', 'Payment verification failed: ' . $e->getMessage());
    redirect_to('/student/payment');
}
