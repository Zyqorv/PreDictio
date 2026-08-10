<?php

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["user_edit"] !== 1) {
    header("Location: /admin/");
    exit();
}

require_once __DIR__ . "/adminQuery.php";

$user = [];
$queryError = null;

try {

    $sql = "
        SELECT
            id,
            email,
            role,
            is_flagged,
            flagged_at,
            flag_reason,
            flagged_by_admin_id
        FROM users
        ORDER BY id ASC
    ";

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );


    if (isset($response["data"]) && is_array($response["data"])) {
        $users = $response["data"];
    } elseif (isset($response["rows"]) && is_array($response["rows"])) {
        $users = $response["rows"];
    } else {
        $queryError = "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("User query error: " . $e->getMessage());

    $queryError = "Failed to load user data.";
}

require __DIR__ . "/../../views/admin/users.php";