<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; API Error Logs</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="game-card">

        <div class="tiles" id="tiles" aria-label="PreDictio"></div>

        <p class="tagline">API Error Logs</p>

        <?php if (!empty($queryError)): ?>

            <div class="field">
                <p class="error-message">
                    <?= htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

        <?php elseif (empty($apiLogs)): ?>

            <div class="field">
                <p>No API errors have been logged.</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="api-log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Source API</th>
                            <th>Error Type</th>
                            <th>Error Message</th>
                            <th>Request Time</th>
                            <th>Resolved</th>
                            <th>Resolved At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($apiLogs as $log): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $log['error_id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $log['source_api'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $log['error_type'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td class="error-message-cell">
                                    <?= htmlspecialchars(
                                        $log['error_message'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $log['request_time'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ((int) $log['resolved'] === 1): ?>
                                        <span class="status-resolved">Yes</span>
                                    <?php else: ?>
                                        <span class="status-unresolved">No</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $log['resolved_at']
                                        ? htmlspecialchars(
                                            $log['resolved_at'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : '—'
                                    ?>
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