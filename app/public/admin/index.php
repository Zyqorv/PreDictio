<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

$adminEmail = $_SESSION["admin_email"] ?? 'Admin';

$userEdit = $_SESSION["user_edit"];
$wordEdit = $_SESSION["word_edit"];
$dbQuery = $_SESSION["db_query"];
$adminLog = $_SESSION["admin_log"];
$apiLog = $_SESSION["api_log"];

require __DIR__ . '/../../views/admin/index.php';