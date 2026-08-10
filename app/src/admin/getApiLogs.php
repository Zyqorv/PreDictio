<?php

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["api_log"] !== 1) {
    header("Location: /admin/");
    exit();
}

require_once __DIR__ . "/adminQuery.php";

$apiLogs = [];
$queryError = null;

try {

    $sql = "
        SELECT
            error_id,
            source_api,
            error_type,
            error_message,
            request_time,
            resolved,
            resolved_at
        FROM api_error_logs
        ORDER BY request_time DESC
    ";

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );


    if (isset($response["data"]) && is_array($response["data"])) {
        $apiLogs = $response["data"];
    } elseif (isset($response["rows"]) && is_array($response["rows"])) {
        $apiLogs = $response["rows"];
    } else {
        $queryError = "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("API log query error: " . $e->getMessage());

    $queryError = "Failed to load API error logs.";
}

require __DIR__ . "/../../views/admin/api-log.php";