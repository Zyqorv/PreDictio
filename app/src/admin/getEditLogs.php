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
            admin_user_id,
            query,
            rows_affected,
            timestamp
        FROM admin_logs
        ORDER BY timestamp DESC
    ";

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );


    if (isset($response["data"]) && is_array($response["data"])) {
        $editLogs = $response["data"];
    } elseif (isset($response["rows"]) && is_array($response["rows"])) {
        $editLogs = $response["rows"];
    } else {
        $queryError = "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("Admin edit log query error: " . $e->getMessage());

    $queryError = "Failed to load admin edit logs.";
}

require __DIR__ . "/../../views/admin/edit-log.php";