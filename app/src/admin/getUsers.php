<?php

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ((int) ($_SESSION["user_edit"] ?? 0) !== 1) {
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


    if (isset($response["status"]) && $response["status"] === "success") {
        if (isset($response["message"]) && is_array($response["message"])) {
            $users = $response["message"];
        }
        else {
            $queryError = "Invalid response from query service.";
        }
    } 
    else if (isset($response["status"]) && $response["status"] === "error") {
        $queryError = $response["message"] ?? "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("User query error: " . $e->getMessage());

    $queryError = "Failed to load user data.";
}

require __DIR__ . "/../../views/admin/users.php";