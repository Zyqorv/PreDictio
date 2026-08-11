<?php


if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}


if (!isset($_SESSION["user_edit"]) || (int) ($_SESSION["user_edit"] ?? 0) !== 1) {
    header("Location: /admin/");
    exit();
}


if (
    !isset($_POST["id"]) ||
    !isset($_POST["flag_reason"]) ||
    trim($_POST["flag_reason"]) === ''
) {
    header("Location: /admin/users");
    exit();
}


require_once __DIR__ . "/adminQuery.php";


$userId = $_POST["id"];
$flagReason = trim($_POST["flag_reason"]);


try {


    $sql = "
        UPDATE users
        SET
            is_flagged = 1,
            flagged_at = CURRENT_TIMESTAMP,
            flag_reason = :flag_reason,
            flagged_by_admin_id = (
                SELECT id
                FROM users
                WHERE email = :admin_email
            )
        WHERE id = :id
    ";


    executeAdminQuery(
        $_SESSION["admin_email"],
        $sql,
        [
            ":flag_reason" => $flagReason,
            ":admin_email" => $_SESSION["admin_email"],
            ":id" => $userId
        ]
    );


} catch (Throwable $e) {


    error_log("User flag error: " . $e->getMessage());


}


header("Location: /admin/users");
exit();