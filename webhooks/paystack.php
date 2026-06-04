<?php
require_once __DIR__ . '/../app/bootstrap.php';
$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!paystack_signature_valid($payload, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
if (!is_array($event)) {
    http_response_code(400);
    exit('Invalid payload');
}

$eventName = (string) ($event['event'] ?? '');
$data = $event['data'] ?? [];
$reference = $data['reference'] ?? null;

try {
    $inserted = db_exec(
        'INSERT IGNORE INTO paystack_webhooks (event_name, reference, payload_json) VALUES (?, ?, ?)',
        [$eventName, $reference, $payload]
    );
    if ($inserted === 0) {
        http_response_code(200);
        echo 'OK';
        exit;
    }
    if ($eventName === 'charge.success' && $reference) {
        complete_paystack_transaction($data);
        db_exec('UPDATE pending_payments SET status = "completed" WHERE reference = ?', [$reference]);
        db_exec('UPDATE paystack_webhooks SET processed = 1 WHERE event_name = ? AND reference = ?', [$eventName, $reference]);
    }
    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Webhook failed';
}
