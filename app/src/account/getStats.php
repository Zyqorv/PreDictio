<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../game/gameMessage.php';

try {

    $result = sendGameMessage(
        "get_stats",
        [
            "email" => $_SESSION["email"] ?? null
        ]
    );


    if (!$result["success"]) {

        http_response_code(500);

        echo json_encode([
            "error" => "RabbitMQ request failed",
            "details" => $result["error"],
            "location" =>
                $result["file"] . ":" . $result["line"]
        ]);

        exit;
    }


    $response = $result["data"];


    if (
        !$response ||
        !isset($response["total_guesses"]) ||
        !isset($response["total_wins"]) ||
        !isset($response["total_losses"]) ||
        !isset($response["current_streak"]) ||
        !isset($response["total_score"])
    ) {

        http_response_code(500);

        echo json_encode([
            "error" => "Invalid response from game server",
            "response_received" => $response
        ]);

        exit;
    }


    echo json_encode($response);


} catch (Throwable $e) {


    http_response_code(500);

    echo json_encode([
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);

}