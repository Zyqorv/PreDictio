<?php

session_start();

if (!isset($_SESSION['admin_email'])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION['db_query'] !== 1) {
    header("Location: /admin/");
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

require_once __DIR__ . "/adminQuery.php";

try {

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $query
    );

    $_SESSION["admin_query_result"] = print_r($response, true);

} catch (Throwable $e) {

    error_log("Admin query error: " . $e->getMessage());

    $_SESSION["admin_query_result"] =
        "Error: " . $e->getMessage();
}

header("Location: /admin/database");
exit();