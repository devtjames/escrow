<?php
require_once __DIR__ . '/app/bootstrap.php';

$status = (int) ($_SERVER['REDIRECT_STATUS'] ?? 404);
if ($status < 400) {
    $status = 404;
}

$titles = [
    400 => 'Invalid Request',
    403 => 'Access Not Allowed',
    404 => 'Page Not Found',
    405 => 'Method Not Allowed',
    419 => 'Invalid Session Token',
    500 => 'Server Error',
];

$messages = [
    400 => 'The request could not be understood. Please check the link and try again.',
    403 => 'You do not have permission to access this page.',
    404 => 'The page you requested does not exist or may have been moved.',
    405 => 'This page does not accept the request method used.',
    419 => 'Your form session has expired or could not be verified. Refresh the page and try again.',
    500 => 'Something went wrong while processing this request. Please try again later.',
];

render_error_page(
    $status,
    $titles[$status] ?? 'Portal Error',
    $messages[$status] ?? 'An unexpected portal error occurred.',
    [
        ['label' => 'Homepage', 'href' => url_for('/'), 'class' => 'btn-primary', 'icon' => 'table'],
    ]
);
