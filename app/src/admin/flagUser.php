<?php
session_start();


if (!isset($_SESSION["admin_email"])) {
    header("Location: /admin/login");
    exit();
}


if (!isset($_SESSION["user_edit"]) || (int) ($_SESSION["user_edit"] ?? 0) !== 1) {
    header("Location: /admin/");
    exit();
}


if (!isset($id) || !isset($flagReason) || trim($flagReason) === '') {
    header("Location: /admin/users");
    exit();
}


require_once __DIR__ . "/adminQuery.php";


try {


    $id = (int) $id;

    $flagReasonSql = addslashes(trim($flagReason));
    $adminEmailSql = addslashes($_SESSION["admin_email"]);

    $sql = "
        UPDATE users
        SET
            is_flagged = 1,
            flagged_at = CURRENT_TIMESTAMP,
            flag_reason = '$flagReasonSql',
            flagged_by_admin_id = (
                SELECT id
                FROM users
                WHERE email = '$adminEmailSql'
            )
        WHERE id = $id
    ";


    executeAdminQuery(
        $_SESSION["admin_email"],
        $sql
    );


} catch (Throwable $e) {


    error_log("User flag error: " . $e->getMessage());


}


header("Location: /admin/users");
exit();