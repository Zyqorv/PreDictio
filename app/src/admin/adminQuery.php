<?php

require_once __DIR__ . '/adminMessage.php';

function executeAdminQuery(string $adminEmail, string $sql): array
{
    $message = [
        'admin_email' => $adminEmail,
        'sql' => $sql,
    ];

    $response = sendAdminMessage('query', $message);

    if (!is_array($response)) {
        throw new RuntimeException(
            'Invalid response from query service.'
        );
    }

    if (
        isset($response['status']) &&
        $response['status'] === 'error'
    ) {
        $message = $response['message'] ?? 'Unknown query error.';

        throw new RuntimeException($message);
    }

    return $response;
}