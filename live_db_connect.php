<?php
// test_mongo.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use your real password (URL-encoded if needed)
define(
    'MONGO_URI',
    'mongodb+srv://7dreamzofficials_db_user:KB4aTOzZb88kKy22@hrms-website.djl5xdh.mongodb.net/hrms'
);

$options = [
    "tls" => true,
    "tlsCAFile" => "/opt/homebrew/etc/ca-certificates/cert.pem"
];

try {
    $manager = new MongoDB\Driver\Manager(MONGO_URI, $options);

    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $manager->executeCommand('admin', $command);

    echo "MongoDB Atlas connected successfully";

} catch (MongoDB\Driver\Exception\Exception $e) {
    echo "MongoDB connection failed: " . $e->getMessage();
}
