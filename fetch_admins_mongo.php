<?php
// fetch_admins_mongo.php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

try {
    // Query: select only _id, fullName, phone, companyName
    $filter = [];
    $options = [
        'projection' => [
            '_id' => 1,
            'fullName' => 1,
            'phone' => 1,
            'companyName' => 1
        ]
    ];

    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $mongoManager->executeQuery("$mongodb_name.admins", $query);

    $admins = [];
    foreach ($cursor as $document) {
        // Convert BSON ObjectId to string
        if (isset($document->_id) && $document->_id instanceof MongoDB\BSON\ObjectId) {
            $document->_id = (string) $document->_id;
        }
        $admins[] = $document;
    }

    echo json_encode([
        'success' => true,
        'data' => $admins
    ]);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>