#!/usr/bin/env php
<?php
// client.php - HTTPクライアント for MCP

if ($argc < 2) {
    fwrite(STDERR, "Usage: php client.php 'Your question'\n");
    exit(1);
}

$query = $argv[1];
$url   = 'http://localhost:8080/ask';

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode(['query' => $query]),
        'timeout' => 30,
    ],
];

$context  = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    fwrite(STDERR, "Error: Unable to reach host server at {$url}\n");
    exit(1);
}

$data = json_decode($response, true);
if (!isset($data['result'])) {
    fwrite(STDERR, "Invalid response from server: {$response}\n");
    exit(1);
}

echo $data['result'] . "\n"; 