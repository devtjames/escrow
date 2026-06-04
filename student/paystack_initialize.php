<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();
if (!post_request()) {
    json_response(['ok' => false, 'message' => 'Invalid request.'], 405);
}
verify_csrf();

try {
    $wallet = ensure_student_wallet((int) $student['id']);
    if ($wallet['status'] !== 'active') {
        json_response(['ok' => false, 'message' => 'Deposit locked.'], 422);
    }
    $remaining = wallet_remaining($wallet);
    $amount = min((int) field('amount'), $remaining);
    $minimum = min(minimum_deposit_amount(), $remaining);
    if ($amount < $minimum) {
        json_response(['ok' => false, 'message' => 'Minimum deposit is ' . money_naira($minimum) . '.'], 422);
    }
    $data = paystack_initialize($student, $wallet, $amount);
    db_exec(
        'INSERT INTO pending_payments (student_id, wallet_id, reference, amount, gateway) VALUES (?, ?, ?, ?, "Paystack")',
        [(int) $student['id'], (int) $wallet['id'], $data['reference'], $amount]
    );
    json_response(['ok' => true, 'authorization_url' => $data['authorization_url'], 'reference' => $data['reference']]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => $e->getMessage()], 500);
}
