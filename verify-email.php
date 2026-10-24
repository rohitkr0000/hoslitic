<?php
// AJAX: sends and checks the email verification code (register + checkout forms).
require __DIR__ . '/includes/init.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'message' => 'Method not allowed.']));
}
csrf_check();

$email = strtolower(post('email'));
if (post('action') === 'send') {
    if (post('context') === 'register' && val('SELECT id FROM users WHERE email = ?', [$email])) {
        $error = 'An account with this email already exists. Please log in.';
    } elseif (email_verified($email)) {
        exit(json_encode(['ok' => true, 'verified' => true, 'message' => 'Email verified.']));
    } else {
        $error = otp_send($email);
    }
    exit(json_encode($error ? ['ok' => false, 'message' => $error] : ['ok' => true, 'message' => 'We have emailed a 6-digit code to ' . $email . '. Check your inbox (and spam folder).']));
}

if (post('action') === 'verify') {
    $error = otp_verify($email, post('code'));
    exit(json_encode($error ? ['ok' => false, 'message' => $error] : ['ok' => true, 'verified' => true, 'message' => 'Email verified.']));
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
