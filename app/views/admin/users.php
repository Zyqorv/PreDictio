<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; User Management</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="game-card">

        <div class="tiles" id="tiles" aria-label="PreDictio"></div>

        <p class="tagline">User Management</p>

        <?php if (!empty($queryError)): ?>

            <div class="field">
                <p class="error-message">
                    <?= htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

        <?php elseif (empty($users)): ?>

            <div class="field">
                <p>No users found.</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="api-log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Is Flagged</th>
                            <th>Flagged At</th>
                            <th>Flag Reason</th>
                            <th>Flagged by Admin ID</th>
                            <th>Manage</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($users as $user): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $user['id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['role'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ((int) $user['is_flagged'] === 1): ?>
                                        <span class="status-resolved">Yes</span>
                                    <?php else: ?>
                                        <span class="status-unresolved">No</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['flagged_at'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['flag_reason'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                            $user['flagged_by_admin_id'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                    ) ?>
                                </td>

                                <td> 
                                    <form action="/admin/user-manage" method="POST">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="role" value="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="is_flagged" value="<?= (int) $user['is_flagged'] ?>">
                                        <input type="hidden" name="flagged_at" value="<?= htmlspecialchars($user['flagged_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="flag_reason" value="<?= htmlspecialchars($user['flag_reason'] ?? '-', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="flagged_by_admin_id" value="<?= htmlspecialchars($user['flagged_by_admin_id'] ?? '-', ENT_QUOTES, 'UTF-8') ?>">

                                        <button type="submit" class="btn btn-secondary">...</button>
                                    </form>                                
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <div class="auth-form" style="margin-top: 16px;">

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

</body>
</html>