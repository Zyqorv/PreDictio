<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["db_query"] !== true) {
    header("Location: /admin/");
    exit();
}

$queryResult = $_SESSION["admin_query_result"] ?? "Query results will appear here.";
unset($_SESSION["admin_query_result"]);

require __DIR__ . '/../../views/admin/database.php';