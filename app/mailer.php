<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function smtp_configured(): bool
{
    return trim((string) setting_get('smtp_host', env_value('SMTP_HOST', ''))) !== ''
        && trim((string) setting_get('smtp_from', env_value('SMTP_FROM', ''))) !== '';
}

function ensure_receipt_email_columns(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $column = db_one("SHOW COLUMNS FROM transactions LIKE 'receipt_email_sent_at'");
        if (!$column) {
            db()->exec('ALTER TABLE transactions ADD receipt_email_sent_at DATETIME NULL AFTER status');
        }
        $column = db_one("SHOW COLUMNS FROM transactions LIKE 'receipt_email_error'");
        if (!$column) {
            db()->exec('ALTER TABLE transactions ADD receipt_email_error TEXT NULL AFTER receipt_email_sent_at');
        }
    } catch (Throwable) {
    }
}

function email_is_full_document(string $html): bool
{
    return stripos($html, '<html') !== false || stripos($html, '<!doctype') !== false;
}

function email_button(string $url, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 4px 0">'
        . '<tr><td style="border-radius:8px;background:#0B5D3B">'
        . '<a href="' . e($url) . '" style="display:inline-block;padding:13px 20px;font-family:Arial,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px">' . e($label) . '</a>'
        . '</td></tr></table>';
}

function email_rows(array $rows): string
{
    $html = '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-top:18px;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden">';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E5E7EB;background:#F8F9FA;font-family:Arial,sans-serif;font-size:12px;font-weight:700;text-transform:uppercase;color:#6B7280;width:38%">' . e((string) $label) . '</td>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E5E7EB;font-family:Arial,sans-serif;font-size:14px;font-weight:700;color:#1F1F1F">' . e((string) $value) . '</td>'
            . '</tr>';
    }
    return $html . '</table>';
}

function email_shell(string $title, string $preheader, string $contentHtml): string
{
    $brand = app_name();
    $year = date('Y');
    $logoUrl = rtrim((string) env_value('APP_URL', ''), '/') !== ''
        ? app_url('/assets/img/logo2.png')
        : 'https://portal.fedpoffaonline.edu.ng/img/logo2.png';
    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light">'
        . '<title>' . e($title) . '</title>'
        . '<style>'
        . '@media only screen and (max-width:620px){.email-container{width:100%!important}.email-card{border-radius:0!important}.email-pad{padding:22px!important}.email-title{font-size:24px!important;line-height:30px!important}.email-stat{display:block!important;width:100%!important;margin-bottom:10px!important}.email-logo-wrap{padding:8px 10px!important}.email-logo-wrap img{width:136px!important;max-width:136px!important}.email-brand-name{font-size:11px!important;line-height:16px!important}.email-brand-app{font-size:14px!important;line-height:20px!important}}'
        . '</style></head>'
        . '<body style="margin:0;padding:0;background:#EAF7F0;color:#1F1F1F">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">' . e($preheader) . '</div>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EAF7F0;border-collapse:collapse">'
        . '<tr><td align="center" style="padding:28px 12px">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="600" class="email-container" style="width:600px;max-width:600px;border-collapse:collapse">'
        . '<tr><td class="email-card" style="background:#ffffff;border:1px solid #BFE6CE;border-radius:14px;overflow:hidden;box-shadow:0 18px 45px rgba(31,31,31,0.10)">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">'
        . '<tr><td class="email-pad" align="center" style="padding:28px 30px;background:#0B5D3B;background-image:linear-gradient(135deg,#0B5D3B,#1F8A5B);text-align:center">'
        . '<div class="email-logo-wrap" style="display:inline-block;padding:9px 12px;border-radius:10px;background:#ffffff"><img src="' . e($logoUrl) . '" width="158" alt="FEDPOFFA" style="display:block;width:158px;max-width:158px;height:auto;border:0"></div>'
        . '<div class="email-brand-name" style="margin-top:16px;font-family:Arial,sans-serif;font-size:12px;line-height:18px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#D4A017">Federal Polytechnic Offa</div>'
        . '<div class="email-brand-app" style="margin-top:4px;font-family:Arial,sans-serif;font-size:15px;line-height:22px;font-weight:700;color:#ffffff">' . e($brand) . '</div>'
        . '</td></tr>'
        . '<tr><td class="email-pad" style="padding:30px">'
        . '<h1 class="email-title" style="margin:0 0 14px 0;font-family:Arial,sans-serif;font-size:28px;line-height:36px;font-weight:900;color:#1F1F1F">' . e($title) . '</h1>'
        . '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:24px;color:#1F1F1F">' . $contentHtml . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 30px;background:#F8F9FA;border-top:1px solid #E5E7EB">'
        . '<p style="margin:0;font-family:Arial,sans-serif;font-size:12px;line-height:18px;color:#6B7280">This message was sent by the FEDPOFFA payment system. If you did not initiate this activity, please contact the finance office.</p>'
        . '</td></tr></table></td></tr>'
        . '<tr><td align="center" style="padding:16px 8px;font-family:Arial,sans-serif;font-size:12px;color:#6B7280">Copyright ' . e($year) . ' Federal Polytechnic Offa</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function sendMail(string $to, string $subject, string $html, string $text = ''): bool
{
    $ok = false;
    $error = null;
    $bodyHtml = email_is_full_document($html)
        ? $html
        : email_shell($subject, strip_tags($html), $html);

    try {
        if (class_exists(PHPMailer::class) && smtp_configured()) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Timeout = 8;
            $mail->SMTPKeepAlive = false;
            $mail->Host = (string) setting_get('smtp_host', env_value('SMTP_HOST', ''));
            $mail->Port = (int) setting_get('smtp_port', env_value('SMTP_PORT', 587));
            $mail->SMTPAuth = true;
            $mail->Username = (string) setting_get('smtp_user', env_value('SMTP_USER', ''));
            $mail->Password = (string) setting_get('smtp_pass', env_value('SMTP_PASS', ''));
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->setFrom(
                (string) setting_get('smtp_from', env_value('SMTP_FROM', 'no-reply@example.com')),
                (string) setting_get('smtp_name', env_value('SMTP_NAME', 'FEDPOFFA Finance'))
            );
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $text ?: trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml)));
            $ok = $mail->send();
        } else {
            $from = (string) setting_get('smtp_from', env_value('SMTP_FROM', 'no-reply@example.com'));
            $name = (string) setting_get('smtp_name', env_value('SMTP_NAME', 'FEDPOFFA Finance'));
            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
            $headers .= 'From: ' . $name . ' <' . $from . ">\r\n";
            $ok = mail($to, $subject, $bodyHtml, $headers);
            if (!$ok && !smtp_configured()) {
                $error = 'SMTP is not configured and PHP mail() failed. Configure SMTP in Admin > Settings.';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    try {
        db_exec(
            'INSERT INTO email_logs (recipient, subject, body, status, error_message) VALUES (?, ?, ?, ?, ?)',
            [$to, $subject, $bodyHtml, $ok ? 'sent' : 'failed', $error]
        );
    } catch (Throwable) {
    }

    return $ok;
}

function sendReceiptEmail(array $student, array $transaction): bool
{
    $receiptUrl = app_url('/student/receipt?ref=' . urlencode((string) $transaction['reference']));
    $content = '<p style="margin:0 0 14px 0">Dear ' . e($student['full_name']) . ',</p>'
        . '<p style="margin:0 0 18px 0">Your payment/deposit has been received and verified successfully.</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0;border-collapse:collapse"><tr>'
        . '<td class="email-stat" style="padding:18px;border-radius:10px;background:#EAF7F0;border:1px solid #BFE6CE"><div style="font-size:12px;font-weight:800;text-transform:uppercase;color:#0B5D3B">Amount Paid</div><div style="margin-top:6px;font-size:26px;line-height:32px;font-weight:900;color:#1F1F1F">' . e(money_naira((int) $transaction['amount'])) . '</div></td>'
        . '</tr></table>'
        . email_rows([
            'Reference' => $transaction['reference'],
            'Gateway' => $transaction['gateway'],
            'Status' => strtoupper((string) $transaction['status']),
            'Date' => date('d M Y h:i A', strtotime((string) $transaction['created_at'])),
        ])
        . email_button($receiptUrl, 'View / Print Receipt')
        . '<p style="margin:18px 0 0 0;color:#6B7280;font-size:13px;line-height:20px">Keep this reference for your records. Your dashboard has also been updated.</p>';
    $html = email_shell('Payment Receipt', 'Your payment of ' . money_naira((int) $transaction['amount']) . ' was verified.', $content);
    return sendMail($student['email'], 'Payment Receipt - ' . $transaction['reference'], $html);
}

function sendTransactionReceiptEmailOnce(int $transactionId): bool
{
    ensure_receipt_email_columns();
    $transaction = db_one('SELECT * FROM transactions WHERE id = ?', [$transactionId]);
    if (!$transaction) {
        return false;
    }
    if (!empty($transaction['receipt_email_sent_at'])) {
        return true;
    }

    $student = db_one('SELECT * FROM students WHERE id = ?', [(int) $transaction['student_id']]);
    if (!$student) {
        return false;
    }

    $ok = sendReceiptEmail($student, $transaction);
    $latestLog = db_one(
        'SELECT error_message FROM email_logs WHERE recipient = ? AND subject = ? ORDER BY id DESC LIMIT 1',
        [$student['email'], 'Payment Receipt - ' . $transaction['reference']]
    );
    try {
        db_exec(
            'UPDATE transactions SET receipt_email_sent_at = ?, receipt_email_error = ? WHERE id = ?',
            [$ok ? date('Y-m-d H:i:s') : null, $ok ? null : ($latestLog['error_message'] ?? 'Email delivery failed.'), $transactionId]
        );
    } catch (Throwable) {
    }
    return $ok;
}

function sendResetEmail(array $student, string $token): bool
{
    $link = app_url('/student/reset_password?token=' . urlencode($token));
    $content = '<p style="margin:0 0 14px 0">Dear ' . e($student['full_name']) . ',</p>'
        . '<p style="margin:0 0 14px 0">We received a request to reset your student portal password. Use the secure button below to continue.</p>'
        . email_button($link, 'Reset Password')
        . '<p style="margin:18px 0 0 0;color:#6B7280;font-size:13px;line-height:20px">This link expires in 1 hour. If you did not request this reset, you can safely ignore this email.</p>';
    $html = email_shell('Password Reset', 'Reset your student portal password. This link expires in 1 hour.', $content);
    return sendMail($student['email'], 'Password Reset', $html);
}
