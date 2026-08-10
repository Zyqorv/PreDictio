<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; Account</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>


    <main class="auth-card">


        <div class="tiles" id="tiles" aria-label="PreDictio"></div>


        <p class="tagline">Currently logged in as: <?php echo htmlspecialchars($email); ?>!</p>

        <div class="stat-container">
            <div>Total Guesses: <span id="total-guesses">Loading...</span></div>
            <div>Total Wins: <span id="total-wins">Loading...</span></div>
            <div>Total Losses: <span id="total-losses">Loading...</span></div>
            <div>Lifetime Score: <span id="total-score">Loading...</span></div>
            <div>Current Streak: <span id="current-streak">Loading...</span></div>
        </div>

        <div id="stats-message"></div>

        <div class="auth-form">

            <button onclick="window.location.href='/game'" class="btn btn-primary">To Game</button>


            <button onclick="window.location.href='/account/settings'" class="btn btn-secondary">Settings</button>


            <div class="divider">
                <span>or</span>
            </div>


            <form action="/logout" method="POST">
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>


        </div>


    </main>


    <script src="/js/tiles.js"></script>

    <script>
        async function loadStats() {

            const message = document.getElementById("stats-message");

            const timeout = new Promise((_, reject) =>
                setTimeout(() => reject(new Error("Request timed out")), 5000)
            );

            try {

                const response = await Promise.race([
                    fetch('/account/getStats', {
                        method: 'POST'
                    }),
                    timeout
                ]);


                const stats = await response.json();


                if (!response.ok) {
                    throw new Error(
                        stats.details ||
                        stats.error ||
                        "Unknown server error"
                    );
                }


                document.getElementById('total-guesses').textContent = stats.total_guesses;
                document.getElementById('total-wins').textContent = stats.total_wins;
                document.getElementById('total-losses').textContent = stats.total_losses;
                document.getElementById('current-streak').textContent = stats.current_streak;
                document.getElementById('total-score').textContent = stats.total_score;


            } catch (error) {

                console.error(error);


                document.getElementById('total-guesses').textContent = "-";
                document.getElementById('total-wins').textContent = "-";
                document.getElementById('total-losses').textContent = "-";
                document.getElementById('current-streak').textContent = "-";
                document.getElementById('total-score').textContent = "-";

                message.innerText = "Error: Unable to load account statistics.";
                message.className = "error";


                message.className = "incorrect";

            }
        }

        loadStats();
    </script>
</body>
</html>
