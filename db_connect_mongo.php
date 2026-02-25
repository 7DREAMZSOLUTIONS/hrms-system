<?php
// db_connect_mongo.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ================= CONFIG ================= //
define(
    'MONGO_URI',
    'mongodb+srv://7dreamzofficials_db_user:KB4aTOzZb88kKy22@hrms-website.djl5xdh.mongodb.net/hrms'
);

$mongodb_name = "hrms";

// TLS OPTIONS (macOS fix)
$options = [
    "tls" => true,
    "tlsCAFile" => "/opt/homebrew/etc/ca-certificates/cert.pem"
];

// ================ CONNECT ================= //
try {
    // IMPORTANT: use same variable name everywhere
    $mongoManager = new MongoDB\Driver\Manager(MONGO_URI, $options);

    // Ping test
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoManager->executeCommand('admin', $command);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "MongoDB connection failed: " . $e->getMessage()
    ]);
    exit;
}
