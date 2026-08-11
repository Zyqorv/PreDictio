<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit();
}

error_log("unflagUser.php accessed");


require_once __DIR__ . '/../../src/admin/unflagUser.php';