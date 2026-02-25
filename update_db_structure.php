<?php
// update_db_structure.php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

try {
    // We want to ensure all admin documents have the payment-related structure
    // This script backfills missing fields with default values (null or N/A)
    // to ensure the "DB Structure" corresponds to what is expected.

    $bulk = new MongoDB\Driver\BulkWrite;

    // Upadte 1: Set defaults for payment dates if they don't exist
    $bulk->update(
        ['last_payment_date' => ['$exists' => false]],
        [
            '$set' => [
                'last_payment_date' => 'N/A',
                'next_subscription_date' => 'N/A',
                'status' => 'Active' // Default status
            ]
        ],
        ['multi' => true]
    );

    // Update 2: Ensure num_users, plan_type, subscription_amount exist (reinforcing previous step)
    $bulk->update(
        ['num_users' => ['$exists' => false]],
        [
            '$set' => [
                'num_users' => 10,
                'plan_type' => 'Starter',
                'subscription_amount' => 3000
            ]
        ],
        ['multi' => true]
    );

    $result = $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulk);

    echo json_encode([
        'success' => true,
        'message' => "DB Structure Updated. Modified count: " . $result->getModifiedCount()
    ]);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>