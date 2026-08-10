<?php
session_start();

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["word_edit"] !== 1) {
    header("Location: /admin/");
    exit();
}

require __DIR__ . '/../../src/admin/getWords.php';