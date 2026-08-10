<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; Admin Edit Logs</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="game-card">

        <div class="tiles" id="tiles" aria-label="PreDictio"></div>

        <p class="tagline">Admin Edit Logs</p>

        <?php if (!empty($queryError)): ?>

            <div class="field">
                <p class="error-message">
                    <?= htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

        <?php elseif (empty($editLogs)): ?>

            <div class="field">
                <p>No edit logs have been recorded.</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="api-log-table">
                    <thead>
                        <tr>
                            <th>Admin Email</th>
                            <th>Query</th>
                            <th>Rows Affected</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($editLogs as $log): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $log['admin_user_id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $log['query'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $log['rows_affected'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $log['timestamp'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
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