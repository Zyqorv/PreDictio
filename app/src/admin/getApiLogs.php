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


    if (isset($response["type"]) && $response["type"] === "success") {
        if (isset($response["message"]) && is_array($response["message"])) {
            $apiLogs = $response["message"];
        }
        else {
            $queryError = "Invalid response from query service.";
        }
    } 
    else if (isset($response["type"]) && $response["type"] === "error") {
        $queryError = $response["message"] ?? "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("API log query error: " . $e->getMessage());

    $queryError = "Failed to load API error logs.";
}

require __DIR__ . "/../../views/admin/api-log.php";