<?php
#$type = $argv[1] ?? "echo";
#$message = $argv[2] ?? "message";
#sendInformation($type,$message);

function sendAdminMessage ($type, $message)
{

$request = [
    "type" => $type,
    "message" => $message,  
    "sent_at" => date(DATE_ATOM),
];

try {
    require_once __DIR__ . "/../RabbitMQLib.inc";

    $client = new rabbitMQClient(__DIR__ . "/../../config/adminRabbitMQ.ini");
    $response = $client->send_request($request);

    error_log($response);

    return $response;
  #  print_r($response);
} catch (Throwable $error) {
    error_log("RabbitMQ client sample failed: " . $error->getMessage());
    fwrite(STDERR, "Client error: " . $error->getMessage() . PHP_EOL);
    exit(1);
}
}