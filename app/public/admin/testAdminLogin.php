<?php
session_start();

// Use only for testing pages that require admin login when RMQ and DB VMs are unavailable

$_SESSION['admin_email'] = 'test_admin@test.com';
$_SESSION['email'] = 'test_admin@test.com';

$_SESSION['user_edit'] = 1;
$_SESSION['word_edit'] = 1;
$_SESSION['db_query'] = 1;
$_SESSION['edit_log'] = 1;
$_SESSION['api_log'] = 1;

header('Location: /admin/');