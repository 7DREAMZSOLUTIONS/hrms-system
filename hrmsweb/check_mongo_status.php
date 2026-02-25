<?php
// check_mongo_status.php
header('Content-Type: text/plain');

echo "Checking MongoDB Extension...\n";

if (extension_loaded("mongodb")) {
    echo "SUCCESS: MongoDB extension is LOADED.\n";

    try {
        $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
        echo "Manager created successfully.\n";

        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $cursor = $manager->executeCommand('admin', $command);
        $response = $cursor->toArray()[0];

        echo "Connection Ping: " . ($response->ok ? "OK" : "FAILED") . "\n";

    } catch (Exception $e) {
        echo "Connection Error: " . $e->getMessage() . "\n";
    }

} else {
    echo "ERROR: MongoDB extension is NOT loaded.\n";
    echo "Please install it via PECL or enable it in php.ini.\n";
    echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
}
?>