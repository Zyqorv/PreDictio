<?php

error_log("unflagUser.php accessed");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit();
}

require_once __DIR__ . '/../../src/admin/unflagUser.php';