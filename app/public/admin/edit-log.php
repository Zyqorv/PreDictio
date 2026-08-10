<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["edit_log"] !== true) {
    header("Location: /admin/");
    exit();
}

require __DIR__ . '/../../views/admin/edit-log.php';