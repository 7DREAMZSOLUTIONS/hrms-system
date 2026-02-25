<?php
// db_connect_mongo.php

// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MongoDB Config
define('MONGO_URI', 'mongodb+srv://7dreamzofficials_db_user:KB4aTOzZb88kKy22@hrms-website.djl5xdh.mongodb.net/
');
$mongodb_name = 'hrms';

try {
    // Create MongoDB Manager
    $mongoManager = new MongoDB\Driver\Manager(MONGO_URI);

    // Ping MongoDB to confirm connection
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoManager->executeCommand('admin', $command);

} catch (MongoDB\Driver\Exception\Exception $e) {
    // http_response_code(500); // Allow valid JSON error response
    echo json_encode([
        'data' => [],
        'error' => 'MongoDB Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}
