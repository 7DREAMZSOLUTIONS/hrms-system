<?php
// test_mongo.php
include 'db_connect_mongo.php';

try {
    // Attempt to insert a test document
    $bulk = new MongoDB\Driver\BulkWrite;
    $document = ['_id' => new MongoDB\BSON\ObjectId, 'name' => 'Test User', 'status' => 'Active', 'timestamp' => new MongoDB\BSON\UTCDateTime()];
    $bulk->insert($document);

    // Use the configured database name
    $namespace = $mongodb_name . '.test_collection';
    $result = $mongoManager->executeBulkWrite($namespace, $bulk);

    echo "<h1>MongoDB Connection Successful!</h1>";
    echo "<p>Connected to database: <strong>$mongodb_name</strong></p>";
    echo "<p>Inserted " . $result->getInsertedCount() . " document(s) into '$namespace'.</p>";

} catch (MongoDB\Driver\Exception\Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>