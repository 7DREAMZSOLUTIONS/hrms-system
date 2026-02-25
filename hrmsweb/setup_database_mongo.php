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
    ]
];

foreach ($collections as $colName => $config) {
    try {
        // Create Collection if not exists
        $listCollections = new MongoDB\Driver\Command(['listCollections' => 1, 'filter' => ['name' => $colName]]);
        $cursor = $mongoManager->executeCommand($mongodb_name, $listCollections);
        if (empty($cursor->toArray())) {
            $createCmd = new MongoDB\Driver\Command(['create' => $colName]);
            $mongoManager->executeCommand($mongodb_name, $createCmd);
            $results[] = "[$colName] Collection created.";
        } else {
            $results[] = "[$colName] Collection already exists.";
        }

        // Create Indexes
        if (!empty($config['indexes'])) {
            // We use 'createIndexes' command. MongoDB handles existing indexes gracefully usually, 
            // but might error if options match but name differs or vice versa. We catch errors.
            $indexesCmd = new MongoDB\Driver\Command([
                'createIndexes' => $colName,
                'indexes' => $config['indexes']
            ]);
            $mongoManager->executeCommand($mongodb_name, $indexesCmd);
            $results[] = "[$colName] Indexes verified/created.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $results[] = "[$colName] Structure Error: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------- //
// 2. Data Backfill: Fill missing fields with defaults
// ---------------------------------------------------------------- //

try {
    $bulk = new MongoDB\Driver\BulkWrite;
    $ops = 0;

    // 2.1 Admins: Subscription Defaults
    // Set default plan if missing
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

    // 2.2 Admins: Date Defaults
    // Set default dates if missing
    $bulk->update(
        ['last_payment_date' => ['$exists' => false]],
        [
            '$set' => [
                'last_payment_date' => 'N/A',
                'next_subscription_date' => 'N/A',
                'status' => 'Active'
            ]
        ],
        ['multi' => true]
    );

    // Write Admins Backfill
    $res = $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulk);
    if ($res->getModifiedCount() > 0) {
        $results[] = "[admins] Backfilled default data for " . $res->getModifiedCount() . " documents.";
    }

    // 2.3 Devices: Subscription Details
    $bulkDev = new MongoDB\Driver\BulkWrite;
    $bulkDev->update(
        ['subscriptionExpiry' => ['$exists' => false]],
        ['$set' => ['subscriptionExpiry' => 'N/A']],
        ['multi' => true]
    );

    $resDev = $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkDev);
    if ($resDev->getModifiedCount() > 0) {
        $results[] = "[devices] Backfilled default data for " . $resDev->getModifiedCount() . " documents.";
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    $results[] = "Data Backfill Error: " . $e->getMessage();
}

// ---------------------------------------------------------------- //
// 3. Dynamic Updates: companyId generation
// ---------------------------------------------------------------- //

try {
    // Ideally we use aggregation pipeline update (MongoDB 4.2+), assuming support.
    // Update companyId = (string)_id WHERE companyId is missing
    /*
     db.admins.updateMany(
        { companyId: { $exists: false } },
        [ { $set: { companyId: { $toString: "$_id" } } } ]
     )
    */
    // Since PHP driver 'update' method doesn't easily support pipeline as the second arg in all versions/wrappers,
    // we will do a manual iterate-and-update for safety and compatibility.

    $query = new MongoDB\Driver\Query(['companyId' => ['$exists' => false]]);
    $cursor = $mongoManager->executeQuery("$mongodb_name.admins", $query);

    $bulkDynamic = new MongoDB\Driver\BulkWrite;
    $countDynamic = 0;

    foreach ($cursor as $doc) {
        $strId = (string) $doc->_id;
        $bulkDynamic->update(
            ['_id' => $doc->_id],
            ['$set' => ['companyId' => $strId]]
        );
        $countDynamic++;
    }

    if ($countDynamic > 0) {
        $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkDynamic);
        $results[] = "[admins] Generated missing 'companyId' for $countDynamic documents.";
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    $results[] = "Dynamic Update Error: " . $e->getMessage();
}


echo json_encode([
    'success' => true,
    'message' => 'Database setup and repair completed.',
    'details' => $results
], JSON_PRETTY_PRINT);
?>