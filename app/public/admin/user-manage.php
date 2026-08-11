<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if (!isset($_SESSION["user_edit"]) || (int) ($_SESSION["user_edit"] ?? 0) !== 1) {
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

$id = $_POST["id"];
$email = $_POST["email"];
$role = $_POST["role"];
$isFlagged = $_POST["is_flagged"];
$flaggedAt = $_POST["flagged_at"];
$flagReason = $_POST["flag_reason"];
$flaggedByAdminId = $_POST["flagged_by_admin_id"];

require __DIR__ . '/../../views/admin/user-manage.php';