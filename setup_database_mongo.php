<?php
// setup_database_mongo.php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

$results = [];

// ---------------------------------------------------------------- //
// 1. Structure Setup: Collections & Indexes
// ---------------------------------------------------------------- //

$collections = [
    'admins' => [
        'indexes' => [
            ['key' => ['phone' => 1], 'name' => 'phone_idx', 'unique' => true],
            ['key' => ['email' => 1], 'name' => 'email_idx', 'unique' => true],
            ['key' => ['companyId' => 1], 'name' => 'companyId_idx', 'unique' => true, 'sparse' => true]
        ]
    ],
    'transaction_history' => [
        'indexes' => [
            ['key' => ['companyId' => 1], 'name' => 'companyId_idx'],
            ['key' => ['payment_id' => 1], 'name' => 'payment_id_idx', 'unique' => true],
            ['key' => ['created_at' => -1], 'name' => 'created_at_idx']
        ]
    ],
    'devices' => [
        'indexes' => [
            ['key' => ['companyId' => 1], 'name' => 'companyId_idx']
        ]
    ],
    'subscription' => [
        'indexes' => [
            ['key' => ['company_id' => 1], 'name' => 'company_id_idx', 'unique' => true]
        ]
    ],
    'employees' => [
        'indexes' => [
            ['key' => ['companyId' => 1], 'name' => 'companyId_idx'],
            ['key' => ['email' => 1], 'name' => 'email_idx', 'unique' => true, 'sparse' => true],
            ['key' => ['phone' => 1], 'name' => 'phone_idx']
        ]
    ]
];

foreach ($collections as $colName => $config) {
    try {
        // Check collection
        $listCollections = new MongoDB\Driver\Command([
            'listCollections' => 1,
            'filter' => ['name' => $colName]
        ]);
        $cursor = $mongoManager->executeCommand($mongodb_name, $listCollections);

        if (empty($cursor->toArray())) {
            $createCmd = new MongoDB\Driver\Command(['create' => $colName]);
            $mongoManager->executeCommand($mongodb_name, $createCmd);
            $results[] = "[$colName] Collection created.";
        } else {
            $results[] = "[$colName] Collection exists.";
        }

        // Create indexes
        $indexesCmd = new MongoDB\Driver\Command([
            'createIndexes' => $colName,
            'indexes' => $config['indexes']
        ]);
        $mongoManager->executeCommand($mongodb_name, $indexesCmd);
        $results[] = "[$colName] Indexes created/verified.";

    } catch (MongoDB\Driver\Exception\Exception $e) {
        $results[] = "[$colName] Warning: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------- //
// 2. Data Migration & Cleanup
// ---------------------------------------------------------------- //

try {
    $queryAdmins = new MongoDB\Driver\Query([]);
    $cursorAdmins = $mongoManager->executeQuery("$mongodb_name.admins", $queryAdmins);

    $bulkSub = new MongoDB\Driver\BulkWrite;
    $bulkAdminsCleanup = new MongoDB\Driver\BulkWrite;

    $count = 0;

    foreach ($cursorAdmins as $admin) {
        $compId = isset($admin->companyId) ? $admin->companyId : (string) $admin->_id;

        // Subscription upsert
        $bulkSub->update(
            ['company_id' => $compId],
            [
                '$setOnInsert' => [
                    'company_id' => $compId,
                    'company_name' => $admin->companyName ?? 'Unknown',
                    'plan_type' => $admin->plan_type ?? 'Starter',
                    'num_users' => $admin->num_users ?? 10,
                    'subscription_amount' => $admin->subscription_amount ?? 3000,
                    'status' => $admin->status ?? 'Active',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ],
            ['upsert' => true]
        );

        // Cleanup admins
        $bulkAdminsCleanup->update(
            ['_id' => $admin->_id],
            [
                '$unset' => [
                    'num_users' => "",
                    'plan_type' => "",
                    'subscription_amount' => "",
                    'status' => ""
                ]
            ]
        );

        $count++;
    }

    if ($count > 0) {
        $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);
        $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkAdminsCleanup);
        $results[] = "[migration] Synced $count admin → subscription records.";
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    $results[] = "Migration error: " . $e->getMessage();
}

// ---------------------------------------------------------------- //
// FINAL RESPONSE
// ---------------------------------------------------------------- //

echo json_encode([
    "success" => true,
    "message" => "MongoDB HRMS setup completed successfully",
    "details" => $results
], JSON_PRETTY_PRINT);

?>