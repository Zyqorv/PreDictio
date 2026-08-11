<?php


if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}


if (!isset($_SESSION["user_edit"]) || (int) ($_SESSION["user_edit"] ?? 0) !== 1) {
    header("Location: /admin/");
    exit();
}


if (!isset($_POST["id"])) {
    header("Location: /admin/users");
    exit();
}


require_once __DIR__ . "/adminQuery.php";


$userId = $_POST["id"];


try {


    $sql = "
        UPDATE users
        SET
            is_flagged = 0,
            flagged_at = NULL,
            flag_reason = NULL,
            flagged_by_admin_id = NULL
        WHERE id = :id
    ";


    executeAdminQuery(
        $_SESSION["admin_email"],
        $sql,
        [
            ":id" => $userId
        ]
    );


} catch (Throwable $e) {


    error_log("User unflag error: " . $e->getMessage());

}


header("Location: /admin/users");
exit();