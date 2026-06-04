<?php
declare(strict_types=1);

function paystack_secret(): string
{
    return (string) env_value('PAYSTACK_SECRET_KEY', '');
}

function paystack_public_key(): string
{
    return (string) env_value('PAYSTACK_PUBLIC_KEY', '');
}

function paystack_request(string $method, string $endpoint, array $payload = []): array
{
    $ch = curl_init('https://api.paystack.co' . $endpoint);
    $headers = [
        'Authorization: Bearer ' . paystack_secret(),
        'Content-Type: application/json',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException($error ?: 'Paystack request failed.');
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid Paystack response.');
    }
    return $data;
}

function paystack_initialize(array $student, array $wallet, int $amount): array
{
    $reference = make_reference('FPO');
    $payload = [
        'email' => $student['email'],
        'amount' => amount_to_kobo($amount),
        'reference' => $reference,
        'callback_url' => app_url('/student/paystack_callback'),
        'metadata' => [
            'student_id' => (int) $student['id'],
            'wallet_id' => (int) $wallet['id'],
            'amount_naira' => $amount,
        ],
    ];

    $response = paystack_request('POST', '/transaction/initialize', $payload);
    if (empty($response['status'])) {
        throw new RuntimeException($response['message'] ?? 'Unable to initialize payment.');
    }
    return $response['data'] + ['reference' => $reference];
}

function paystack_verify(string $reference): array
{
    $response = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
    if (empty($response['status'])) {
        throw new RuntimeException($response['message'] ?? 'Unable to verify payment.');
    }
    return $response['data'];
}

function paystack_signature_valid(string $payload, string $signature): bool
{
    return hash_equals(hash_hmac('sha512', $payload, paystack_secret()), $signature);
}

function complete_paystack_transaction(array $data): array
{
    if (($data['status'] ?? '') !== 'success') {
        throw new RuntimeException('Payment was not successful.');
    }

    $reference = (string) $data['reference'];
    $amount = kobo_to_amount((int) $data['amount']);
    $metadata = $data['metadata'] ?? [];
    $walletId = (int) ($metadata['wallet_id'] ?? 0);

    if (!$walletId) {
        $pending = db_one('SELECT wallet_id FROM pending_payments WHERE reference = ? LIMIT 1', [$reference]);
        $walletId = (int) ($pending['wallet_id'] ?? 0);
    }
    if (!$walletId) {
        throw new RuntimeException('Wallet metadata missing.');
    }

    $wallet = apply_wallet_deposit($walletId, $amount, $reference, 'Paystack', $data);
    $transaction = db_one('SELECT * FROM transactions WHERE reference = ?', [$reference]);
    if ($transaction) {
        try {
            sendTransactionReceiptEmailOnce((int) $transaction['id']);
        } catch (Throwable $e) {
            try {
                db_exec(
                    'UPDATE transactions SET receipt_email_error = ? WHERE id = ?',
                    ['Receipt email failed: ' . $e->getMessage(), (int) $transaction['id']]
                );
            } catch (Throwable) {
            }
        }
    }
    return $wallet;
}
