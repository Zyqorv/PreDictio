<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION["admin_query_result"] = "Error: Invalid request method.";
    header("Location: /admin/database");
    exit();
}

if (!isset($_POST["query"]) || trim($_POST["query"]) === "") {
    $_SESSION["admin_query_result"] = "Error: No query provided.";
    header("Location: /admin/database");
    exit();
}

$query = trim($_POST["query"]);

require_once __DIR__ . "/adminMessage.php";

try {

    $message = [
        'admin_email' => $_SESSION["admin_email"],
        'sql' => $query,
    ];

    $response = sendAdminMessage('query', $message);

    if (!is_array($response)) {
        $_SESSION["admin_query_result"] = "Error: Invalid response from query service.";
        header("Location: /admin/database");
        exit();
    }

    if (isset($response["status"]) && $response["status"] === "error") {
        $message = isset($response["message"]) ? $response["message"] : "Unknown query error.";
        $_SESSION["admin_query_result"] = "Error: " . $message;
        header("Location: /admin/database");
        exit();
    }

    $_SESSION["admin_query_result"] = print_r($response, true);

    header("Location: /admin/database");
    exit();

} catch (Throwable $e) {
    error_log("Admin query error: " . $e->getMessage());
    $_SESSION["admin_query_result"] = "Error: Failed to process query.";
    header("Location: /admin/database");
    exit();
}
