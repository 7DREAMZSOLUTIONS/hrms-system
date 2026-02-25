<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../db_connect_mongo.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data payload']);
    exit;
}

try {
    $action = $data['action'] ?? '';
    $companyId = $data['companyId'] ?? '';
    $now = new MongoDB\BSON\UTCDateTime();

    if (empty($companyId)) {
        throw new Exception("Company ID is required.");
    }

    if ($action === 'update') {
        $planType = $data['planType'] ?? '';
        $expiryDate = $data['expiryDate'] ?? '';

        if (empty($planType) || empty($expiryDate)) {
            throw new Exception("Plan Type and Expiry Date are required for update.");
        }

        // 1. Update Subscription Collection
        $subFilter = ['company_id' => $companyId];
        $subUpdate = [
            '$set' => [
                'plan_type' => $planType,
                'next_subscription_date' => $expiryDate,
                'updated_at' => $now
            ]
        ];
        $bulkSub = new MongoDB\Driver\BulkWrite;
        $bulkSub->update($subFilter, $subUpdate, ['upsert' => false]);
        $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

        // 2. Update Companies Collection
        $compFilter = ['companyId' => $companyId];
        $compUpdate = [
            '$set' => [
                'planType' => $planType,
                'updatedAt' => $now
            ]
        ];
        $bulkComp = new MongoDB\Driver\BulkWrite;
        $bulkComp->update($compFilter, $compUpdate, ['upsert' => false]);
        $mongoManager->executeBulkWrite("$mongodb_name.companies", $bulkComp);

        // 3. Update Devices Collection
        $devFilter = ['companyId' => $companyId];
        $devUpdate = [
            '$set' => [
                'subscriptionExpiry' => $expiryDate,
                'updatedAt' => $now
            ]
        ];
        $bulkDev = new MongoDB\Driver\BulkWrite;
        $bulkDev->update($devFilter, $devUpdate, ['upsert' => false, 'multi' => true]);
        $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkDev);

        echo json_encode(['success' => true]);

    } elseif ($action === 'cancel') {

        // 1. Update Subscription Collection (Set Status to Cancelled)
        $subFilter = ['company_id' => $companyId];
        $subUpdate = [
            '$set' => [
                'status' => 'Cancelled',
                'updated_at' => $now
            ]
        ];
        $bulkSub = new MongoDB\Driver\BulkWrite;
        $bulkSub->update($subFilter, $subUpdate, ['upsert' => false]);
        $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

        // Optional: Could deactivate company or devices here if needed, 
        // but typically cancellation just stops auto-renew/turns sub status inactive.

        echo json_encode(['success' => true]);

    } elseif ($action === 'activate') {

        // 1. Update Subscription Collection (Set Status to Active)
        $subFilter = ['company_id' => $companyId];
        $subUpdate = [
            '$set' => [
                'status' => 'Active',
                'updated_at' => $now
            ]
        ];
        $bulkSub = new MongoDB\Driver\BulkWrite;
        $bulkSub->update($subFilter, $subUpdate, ['upsert' => false]);
        $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

        echo json_encode(['success' => true]);

    } else {
        throw new Exception("Invalid action specified.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>