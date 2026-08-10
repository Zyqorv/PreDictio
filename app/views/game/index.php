<?php
/*
Expected JSON Response:


{
    "wordId": 42,
    "definition": "The quality of being honest and having strong moral principles.",
    "hints": [
        {
            "type": "Length",
            "value": "9 letters"
        },
        {
            "type": "Part of Speech",
            "value": "Noun"
        },
        {
            "type": "Synonym",
            "value": "Honesty"
        },
        {
            "type": "Syllable Count",
            "value": "4 syllables"
        }
    ]
    "total_guesses": 9
    "total_wins": 2
    "total_losses": 1 
}


*/
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreDictio &mdash; Play</title>
    <link rel="stylesheet" href="/css/style.css">
</head>


<body>


<main class="page-stack">


    <div class="tiles" id="tiles" aria-label="PreDictio"></div>


    <p class="tagline">Guess the word from its definition.</p>


    <div class="game-card">


        <div class="definition-label">Definition</div>

        <div class="definition-box" id="definition">
            Loading definition...
        </div>


        <div class="guess-counter">
            Your Guesses: <span id="guess-count">-</span>
        </div>

        <div class="points">
            Possible Points: <span id="points">-</span>
        </div>


        <div class="stats">
            <div>Global Guesses: <span id="total-guesses">-</span></div>
            <div>Global Wins: <span id="total-wins">-</span></div>
            <div>Global Losses: <span id="total-losses">-</span></div>
        </div>


        <div id="hints-container"></div>

        <div id="hint-message"></div>


        <div class="input-group">


            <input
                type="text"
                id="guess-input"
                placeholder="Type your guess here..."
            >


            <button
                class="btn btn-primary"
                onclick="checkGuess()">
                Submit
            </button>


        </div>

        <div id="message"></div>

        <button
            class="btn btn-secondary"
            onclick="giveUp()">
            Give Up
        </button>

        <button onclick="window.location.href='/account/'" class="btn btn-secondary">Account</button>

    </div>


</main>


<script src="/js/tiles.js"></script>


<script>


let currentItem = null;
let hintsRevealed = 0;
let guessCount = null;
let points = null;

function updateStats() {

    document.getElementById("total-guesses").innerText =
        currentItem.total_guesses ?? "-";

    document.getElementById("total-wins").innerText =
        currentItem.total_wins ?? "-";

    document.getElementById("total-losses").innerText =
        currentItem.total_losses ?? "-";
}

async function loadGame() {


    const message = document.getElementById("message");
    const definition = document.getElementById("definition");


    message.innerText = "";
    message.className = "";


    try {


        const response = await fetch("/game/getWord", {
            method: "POST"
        });

        if (!response.ok) {

            const errorData = await response.json();

            throw new Error(
                errorData.details ||
                errorData.error ||
                "Unknown server error"
            );
        }

        currentItem = await response.json();

        updateStats();

        if (!currentItem || !currentItem.definition || !currentItem.hints) {
            throw new Error("Invalid game data received.");
        }


        definition.innerText = currentItem.definition;


        document.getElementById("hints-container").innerHTML = "";
        document.getElementById("hint-message").innerText = "";
        document.getElementById("guess-input").value = "";

        hintsRevealed = 0;
        guessCount = currentItem.user_guesses ?? 0;

        points = 10 - (guessCount);

        document.getElementById("guess-count").innerText = guessCount;
        document.getElementById("points").innerText = points;

        while (hintsRevealed < guessCount && hintsRevealed < currentItem.hints.length) {
            revealNextHint();
        }


    } catch (error) {


        console.error(error);


        definition.innerText = "";


        message.innerText =
            "Error: Unable to load a word. Please try refreshing the page.";


        message.className = "incorrect";
    }
}




function revealNextHint() {
   
    if (!currentItem) {
        return;
    }


    if (hintsRevealed >= currentItem.hints.length) {


        document.getElementById("hint-message").innerText =
            "No more hints available!";


        return;
    }


    const hint = currentItem.hints[hintsRevealed];


    const div = document.createElement("div");


    div.className = "hint-section";


    div.innerHTML = `
        <div class="hint-title">${hint.type}</div>
        <div>${hint.value}</div>
    `;


    document
        .getElementById("hints-container")
        .appendChild(div);


    hintsRevealed++;
}

async function giveUp() {

    if (!currentItem) {
        return;
    }

    await checkGuess(true);

}

async function checkGuess(giveUp = false) {


    if (!currentItem) {
        return;
    }


    const message = document.getElementById("message");


    try {


        const guess = giveUp
            ? "give_up"
            : document
                .getElementById("guess-input")
                .value
                .trim();

        if (!giveUp) {
            guessCount++;
            document.getElementById("guess-count").innerText = guessCount;
        }


        const response = await fetch("/game/checkGuess", {


            method: "POST",


            headers: {
                "Content-Type": "application/json"
            },


            body: JSON.stringify({


                wordId: currentItem.wordId,


                guess: guess


            })


        });


        if (!response.ok) {
            throw new Error("Server returned an error.");
        }


        const result = await response.json();


        if (giveUp) {

            if (points <= 0) {
                message.innerText =
                    `Out of points! The correct word was "${result.message.result}". Loading next word...`;
            } else {
                message.innerText =
                    `The correct word was "${result.message.result}". Loading next word...`;
            }

            message.className = "incorrect";

            setTimeout(loadGame, 2000);

        } else if (result.message.result) {

            message.innerText = "Correct! Loading next word...";
            message.className = "correct";

            setTimeout(loadGame, 1500);

        } else {

            points--;

            if (points <= 0)
            {
                giveUp();
            }

            revealNextHint();

            message.innerText = "Incorrect, try again!";
            message.className = "incorrect";

        }


    } catch (error) {


        console.error(error);


        message.innerText =
            "Error: Unable to check guess. Please try again.";


        message.className = "incorrect";


    }


}


window.onload = loadGame;


</script>


</body>
</html>
