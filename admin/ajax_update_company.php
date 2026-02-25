<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../db_connect_mongo.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['eCid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data payload or missing company ID']);
    exit;
}

try {
    $now = new MongoDB\BSON\UTCDateTime();
    $companyId = $data['eCid'];
    $companyName = !empty($data['name']) ? $data['name'] : 'Unknown';
    $address = $data['addr'] ?? '';
    $gst = $data['gst'] ?? '';

    // 1. Update Companies Collection
    $bulkCompanies = new MongoDB\Driver\BulkWrite;
    $bulkCompanies->update(
        ['companyId' => $companyId],
        [
            '$set' => [
                'companyName' => $companyName,
                'address' => $address,
                'gstNumber' => $gst,
                'updatedAt' => $now
            ]
        ],
        ['multi' => false, 'upsert' => false]
    );
    $mongoManager->executeBulkWrite("$mongodb_name.companies", $bulkCompanies);

    // 2. Update Devices Collection
    if (!empty($data['did'])) {
        $bulkDevices = new MongoDB\Driver\BulkWrite;
        $deviceUpdate = [
            'deviceId' => $data['did'],
            'subscriptionExpiry' => !empty($data['sExp']) ? $data['sExp'] : null,
            'updatedAt' => $now
        ];

        $bulkDevices->update(
            ['companyId' => $companyId],
            ['$set' => $deviceUpdate],
            ['multi' => false, 'upsert' => true] // Upsert if device didn't exist
        );
        $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkDevices);
    }

    // 3. Update Subscription Collection (Dashboard dependency)
    $bulkSub = new MongoDB\Driver\BulkWrite;
    $subUpdate = [
        'company_name' => $companyName,
        'next_subscription_date' => !empty($data['sExp']) ? $data['sExp'] : null
    ];
    $bulkSub->update(
        ['company_id' => $companyId],
        ['$set' => $subUpdate],
        ['multi' => false, 'upsert' => false]
    );
    $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

    // 4. Update Employees / Admins Collection (Only if eCode / Email matches or are provided)
    // For admins:
    if (!empty($data['eEmail']) || !empty($data['eName'])) {
        $bulkAdmins = new MongoDB\Driver\BulkWrite;
        $adminUpdate = [
            'fullName' => !empty($data['eName']) ? $data['eName'] : 'Admin',
            'email' => !empty($data['eEmail']) ? $data['eEmail'] : 'N/A',
            'phone' => $data['ePhone'] ?? '',
            'companyName' => $companyName,
            'updatedAt' => $now
        ];
        $bulkAdmins->update(
            ['companyId' => $companyId],
            ['$set' => $adminUpdate],
            ['multi' => true, 'upsert' => false]
        );
        $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkAdmins);
    }

    // 5. Update employees collection to sync name, phone, email, and empCode
    if (!empty($data['eCode']) || !empty($data['eName'])) {
        $bulkEmployees = new MongoDB\Driver\BulkWrite;
        $empUpdate = [
            'name' => !empty($data['eName']) ? $data['eName'] : 'Admin',
            'empCode' => !empty($data['eCode']) ? $data['eCode'] : 'ADM-' . time(),
            'phone' => $data['ePhone'] ?? '',
            'companyName' => $companyName,
            'updatedAt' => $now
        ];
        if (!empty($data['eEmail']))
            $empUpdate['email'] = $data['eEmail'];

        $bulkEmployees->update(
            ['companyId' => $companyId, 'staffType' => 'admin'],
            ['$set' => $empUpdate],
            ['multi' => true, 'upsert' => false]
        );
        $mongoManager->executeBulkWrite("$mongodb_name.employees", $bulkEmployees);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>