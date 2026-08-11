<?php

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["edit_log"] !== 1) {
    header("Location: /admin/");
    exit();
}

require_once __DIR__ . "/adminQuery.php";

$editLogs = [];
$queryError = null;

try {

    $sql = "
        SELECT
            log_id,
            admin_user_id,
            query,
            rows_affected,
            created_at
        FROM admin_logs
        ORDER BY created_at DESC
    ";

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );


    if (isset($response["status"]) && $response["status"] === "success") {
        if (isset($response["message"]) && is_array($response["message"])) {
            $editLogs = $response["message"];
        }
        else {
            $queryError = "Invalid response from query service.";
        }
    } 
    else if (isset($response["status"]) && $response["status"] === "error") {
        $queryError = $response["message"] ?? "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("Admin edit log query error: " . $e->getMessage());

    $queryError = "Failed to load admin edit logs.";
}

require __DIR__ . "/../../views/admin/edit-log.php";