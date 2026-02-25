<?php
// update_admins_mongo.php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

try {
    // Bulk write is more efficient, but we will iterate and update for simplicity in this context
    // We want to add these fields to ALL admins as a baseline if they don't exist
    // num_users: 10
    // plan_type: "Starter"
    // subscription_amount: 3000

    $bulk = new MongoDB\Driver\BulkWrite;

    // Update all documents in the 'admins' collection
    $bulk->update(
        [], // Filter: empty array means match ALL documents
        [
            '$set' => [
                'num_users' => 10,
                'plan_type' => 'Starter',
                'subscription_amount' => 3000
            ]
        ],
        ['multi' => true] // Apply to multiple documents
    );

    $result = $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulk);

    echo json_encode([
        'success' => true,
        'message' => "Updated " . $result->getModifiedCount() . " admin records with default plan details."
    ]);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>