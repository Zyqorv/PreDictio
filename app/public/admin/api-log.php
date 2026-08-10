<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["api_log"] !== 1) {
    header("Location: /admin/");
    exit();
}

require __DIR__ . '/../../views/admin/api-log.php';