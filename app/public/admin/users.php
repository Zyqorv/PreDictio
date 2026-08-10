<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["user_edit"] !== true) {
    header("Location: /admin/");
    exit();
}

require __DIR__ . '/../../views/admin/users.php';