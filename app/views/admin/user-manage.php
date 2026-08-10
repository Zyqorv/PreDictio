<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; User Management</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<main class="auth-card">


    <div class="tiles" id="tiles" aria-label="PreDictio"></div>


    <p class="tagline">User Management</p>


    <div class="auth-form">


        <div class="field">

            <p><strong>ID:</strong>
                <?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p><strong>Email:</strong>
                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p><strong>Role:</strong>
                <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p><strong>Is Flagged:</strong>
                <?php if ((int) $isFlagged === 1): ?>
                    <span class="status-resolved">Yes</span>
                <?php else: ?>
                    <span class="status-unresolved">No</span>
                <?php endif; ?>
            </p>

            <p><strong>Flagged At:</strong>
                <?= htmlspecialchars($flaggedAt, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p><strong>Flag Reason:</strong>
                <?= htmlspecialchars($flagReason, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p><strong>Flagged by Admin:</strong>
                <?= htmlspecialchars($flaggedByAdminId, ENT_QUOTES, 'UTF-8') ?>
            </p>

        </div>


        <form action="/admin/flagUser.php" method="POST">

            <input
                type="hidden"
                name="id"
                value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="field">

                <label for="flag_reason">Flag Description</label>

                <textarea
                    id="flag_reason"
                    name="flag_reason"
                    rows="4"
                    required
                    placeholder="Enter the reason for flagging this user..."
                ></textarea>

            </div>

            <button type="submit" class="btn btn-secondary">
                Flag User
            </button>

        </form>


        <form action="/admin/unflagUser.php" method="POST">

            <input
                type="hidden"
                name="id"
                value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit" class="btn btn-secondary">
                Remove Flag
            </button>

        </form>


        <div class="divider">
            <span>or</span>
        </div>


        <button
            onclick="window.location.href='/admin/users'"
            class="btn btn-secondary"
        >
            Back to Users
        </button>


        <button
            onclick="window.location.href='/admin'"
            class="btn btn-secondary"
        >
            Back to Admin Portal
        </button>


        <form action="/logout?redirect_to=/admin/login" method="POST">

            <button type="submit" class="btn btn-secondary">
                Logout
            </button>

        </form>


    </div>


</main>


<script src="/js/tiles.js"></script>