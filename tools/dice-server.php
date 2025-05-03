#!/usr/bin/env php
<?php
// tools/dice-server.php

require __DIR__ . '/../vendor/autoload.php';

use MCP\Server;

$server = new Server();
$server->registerTool(
    'dice',
    'Roll a dice',
    [
        'type' => 'object',
        'properties' => [
            'sides' => [
                'type' => 'integer',
                'description' => 'The number of sides on the dice'
            ]
        ],
        'required' => ['sides']
    ],
    function (array $args) {
        return rand(1, (int)$args['sides']);
    }
);

$server->run(); 