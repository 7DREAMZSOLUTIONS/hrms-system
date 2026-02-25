<?php
// db_connect_mongo.php

// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MongoDB Config
define('MONGO_URI', 'mongodb://127.0.0.1:27017');
$mongodb_name = 'hrms';

try {
    // Create MongoDB Manager
    $mongoManager = new MongoDB\Driver\Manager(MONGO_URI);

    // Ping MongoDB to confirm connection
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoManager->executeCommand('admin', $command);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'MongoDB Connection Failed',
        'message' => $e->getMessage()
    ]);
    exit;
}
