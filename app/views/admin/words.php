<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; Word Management</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="game-card">

        <div class="tiles" id="tiles" aria-label="PreDictio"></div>

        <p class="tagline">Word Management</p>

        <?php if (!empty($queryError)): ?>

            <div class="field">
                <p class="error-message">
                    <?= htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

        <?php elseif (empty($words)): ?>

            <div class="field">
                <p>No words found.</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="api-log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Word</th>
                            <th>Part of Speech</th>
                            <th>Syllables</th>
                            <th>Source</th>
                            <th>Cached At</th>
                            <th>Enabled</th>
                            <th>Definition</th>
                            <th>Synonym</th>
                            <th>Manage</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($words as $word): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $word['word_id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['word'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['part_of_speech'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['syllable_count'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['source'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['cached_at'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ((int) $word['enabled'] === 1): ?>
                                        <span class="status-resolved">Yes</span>
                                    <?php else: ?>
                                        <span class="status-unresolved">No</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['definition'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $word['synonym'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td> 
                                    <form action="/admin/wordEnable" method="POST"> 
                                        <input type="hidden" name="id" value="<?= htmlspecialchars( (string) $word['word_id'], ENT_QUOTES, 'UTF-8' ) ?>" >

                                        <input
                                            type="hidden"
                                            name="enabled"
                                            value="<?= ((int) $word['enabled'] === 1) ? '0' : '1' ?>"
                                        >

                                        <button type="submit" class="btn btn-secondary">
                                            <?= ((int) $word['enabled'] === 1) ? 'Disable' : 'Enable' ?>
                                        </button>
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