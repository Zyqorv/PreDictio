<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if (!isset($_SESSION["user_edit"]) || $_SESSION["user_edit"] !== 1) {
    header("Location: /admin/");
    exit();
}

if (
    !isset(
        $_POST["id"],
        $_POST["email"],
        $_POST["role"],
        $_POST["is_flagged"],
        $_POST["flagged_at"],
        $_POST["flag_reason"],
        $_POST["flagged_by_admin_id"]
    )
) {
    header("Location: /admin/users");
    exit();
}

require __DIR__ . '/../../views/admin/user-manage.php';