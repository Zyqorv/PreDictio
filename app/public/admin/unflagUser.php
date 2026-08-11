<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit();
}

$id = $_POST["id"];

require_once __DIR__ . '/../../src/admin/unflagUser.php';