<?php


if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}


if (!isset($_SESSION["word_edit"]) || (int) ($_SESSION["word_edit"] ?? 0) !== 1) {
    header("Location: /admin/");
    exit();
}


if (!isset($id) || !isset($enabled)) {
    header("Location: /admin/words");
    exit();
}


require_once __DIR__ . "/adminQuery.php";

if ($enabled !== 0 && $enabled !== 1) {
    header("Location: /admin/words");
    exit();
}

try {

    $sql = "
        UPDATE words
        SET
            enabled = $enabled
        WHERE word_id = $id
    ";


    executeAdminQuery(
        $_SESSION["admin_email"],
        $sql,
        [
            ":enabled" => $enabled,
            ":id" => $wordId
        ]
    );


} catch (Throwable $e) {


    error_log("Word enable/disable error: " . $e->getMessage());


}


header("Location: /admin/words");
exit();
