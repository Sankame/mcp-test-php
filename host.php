<?php
require __DIR__ . '/vendor/autoload.php';
// host.php - MCP サーバーエントリポイント

require __DIR__ . '/src/MCP/Host.php';
require __DIR__ . '/src/MCP/Client.php';
require __DIR__ . '/src/MCP/StdIOConnection.php';
require __DIR__ . '/src/MCP/Logger.php';

use MCP\Host;

$host = null;
try {
    $host = new Host(__DIR__ . '/tools/dice-server.php');
    $host->connectToServer();
    $host->chatLoop();
} catch (Exception $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
} finally {
    if ($host !== null) {
        $host->close();
    }
} 