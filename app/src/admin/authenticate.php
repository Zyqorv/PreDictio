<?php
session_start();

function redirectWithStatus(string $target, string $message, string $type = 'success'): void
{
    $redirectUrl = $target . '?status_message=' . urlencode($message) . '&status_type=' . urlencode($type);
    header('Location: ' . $redirectUrl);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithStatus('/admin/login', 'Invalid request method', 'error');
}

if (empty($_POST['admin_email']) || empty($_POST['password'])) {
    redirectWithStatus('/admin/login', 'Missing credentials', 'error');
}

$adminEmail = trim($_POST['admin_email']);
$password = $_POST['password'];

require_once __DIR__ . '/adminMessage.php';

try {

    $message = [
        'admin_email' => $adminEmail,
    ];

    $response = sendAdminMessage('admin_login', $message);

    if (!is_array($response) || !isset($response['status'])) {
        redirectWithStatus('/admin/login', 'Invalid response from server', 'error');
    }

    if ($response['status'] === 'error') {
        $message = $response['message'] ?? 'Server error during authentication';
        redirectWithStatus('/admin/login', $message, 'error');
    }

    if ($response['status'] === 'invalid') {
        redirectWithStatus('/admin/login', 'User not found', 'error');
    }

    if (!isset($response['password_hash'])) {
        redirectWithStatus('/admin/login', 'Malformed response from server', 'error');
    }

    $storedHash = $response['password_hash'];

    if (!password_verify($password, $storedHash)) {
        redirectWithStatus('/admin/login', 'Invalid password', 'error');
    }

    $response = sendAdminMessage('get_perms', $message);

    if (!is_array($response) || !isset($response['status'])) {
        redirectWithStatus('/admin/login', 'Invalid response from server', 'error');
    }

    if ($response['status'] === 'error') {
        $message = $response['message'] ?? 'Server error during authentication';
        redirectWithStatus('/admin/login', $message, 'error');
    }

    if ($response['status'] === 'invalid') {
        redirectWithStatus('/admin/login', 'User not found', 'error');
    }

    if (!isset($response['user_edit']) || !isset($response['word_edit']) || !isset($response['db_query']) || !isset($response['admin_log']) 
                || !isset($response['api_log'])) {
        redirectWithStatus('/admin/login', 'Malformed response from server', 'error');
    }

    $_SESSION['admin_email'] = $adminEmail;
    $_SESSION['email'] = $adminEmail;

    $_SESSION['user_edit'] = $response['user_edit'];
    $_SESSION['word_edit'] = $response['word_edit'];
    $_SESSION['db_query'] = $response['db_query'];
    $_SESSION['admin_log'] = $response['admin_log'];
    $_SESSION['api_log'] = $response['api_log'];

    $redirectTo = $_POST['redirect_to'] ?? '/admin/';
    if (!is_string($redirectTo) || !preg_match('#^/[-A-Za-z0-9_./]*$#', $redirectTo)) {
        $redirectTo = '/admin/';
    }

    redirectWithStatus($redirectTo, 'Login successful.');
} catch (Throwable $e) {
    error_log('Authentication error: ' . $e->getMessage());
    redirectWithStatus('/admin/login', 'Server error during login', 'error');
}