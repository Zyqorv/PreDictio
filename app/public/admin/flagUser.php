<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit();
}

$id = $_POST["id"];
$flagReason = $_POST["flag_reason"];

require_once __DIR__ . '/../../src/admin/flagUser.php';