<?php
session_start();


if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}


if (!isset($_SESSION["word_edit"]) || $_SESSION["word_edit"] !== 1) {
    header("Location: /admin/");
    exit();
}


if (
    !isset(
        $_POST["id"],
        $_POST["enabled"]
    )
) {
    header("Location: /admin/words");
    exit();
}

$id = (int) $_POST["id"];
$enabled = (int) $_POST["enabled"];


require __DIR__ . '/../../src/admin/wordEnable.php';
