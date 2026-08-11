<?php

if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}

if ($_SESSION["word_edit"] !== 1) {
    header("Location: /admin/");
    exit();
}

require_once __DIR__ . "/adminQuery.php";

$user = [];
$queryError = null;

try {

    $sql = "
        SELECT
            w.word_id,
            w.word,
            w.part_of_speech,
            w.syllable_count,
            w.source,
            w.cached_at,
            w.enabled,
            d.definition,
            s.synonym
        FROM words AS w
        LEFT JOIN definitions AS d
            ON w.word_id = d.word_id
        LEFT JOIN synonyms AS s
            ON w.word_id = s.word_id;
        ORDER BY w.cached_at DESC
    ";

    $response = executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );

    if (isset($response["status"]) && $response["status"] === "success") {
        if (isset($response["message"]) && is_array($response["message"])) {
            $words = $response["message"];
        }
        else {
            $queryError = "Invalid response from query service.";
        }
    } 
    else if (isset($response["status"]) && $response["status"] === "error") {
        $queryError = $response["message"] ?? "Invalid response from query service.";
    }

} catch (Throwable $e) {

    error_log("Word query error: " . $e->getMessage());

    $queryError = "Failed to load word data.";
}

require __DIR__ . "/../../views/admin/words.php";