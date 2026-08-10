<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; Admin Portal</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>


    <main class="auth-card">


        <div class="tiles" id="tiles" aria-label="PreDictio"></div>


        <p class="tagline">Currently logged in as: <?php echo htmlspecialchars($adminEmail); ?></p>


        <div class="auth-form">


            <button onclick="window.location.href='/game'" class="btn btn-primary">To Game</button>


            <button onclick="window.location.href='/admin/users'" class="btn btn-secondary" <?php echo !$userEdit ? 'disabled' : ''; ?> > User Edit </button> 
            <button onclick="window.location.href='/admin/words'" class="btn btn-secondary" <?php echo !$wordEdit ? 'disabled' : ''; ?> > Word Edit </button>
            <button onclick="window.location.href='/admin/database'" class="btn btn-secondary" <?php echo !$dbQuery ? 'disabled' : ''; ?> > Database Query </button> 
            <button onclick="window.location.href='/admin/edit-log'" class="btn btn-secondary" <?php echo !$adminLog ? 'disabled' : ''; ?> > Edit Log </button>
            <button onclick="window.location.href='/admin/api-log'" class="btn btn-secondary" <?php echo !$apiLog ? 'disabled' : ''; ?> > API Log </button>

            <div class="divider">
                <span>or</span>
            </div>


            <form action="/logout?redirect_to=/admin/login" method="POST">
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>


        </div>


    </main>


    <script src="/js/tiles.js"></script>


</body>
</html>
