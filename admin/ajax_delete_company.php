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
    $companyId = $data['eCid'];

    // Check if company exists first
    $query = new MongoDB\Driver\Query(['companyId' => $companyId]);
    $cursor = $mongoManager->executeQuery("$mongodb_name.companies", $query);
    $company = current($cursor->toArray());

    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    // 1. Delete from companies collection
    $bulkCompanies = new MongoDB\Driver\BulkWrite;
    $bulkCompanies->delete(['companyId' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.companies", $bulkCompanies);

    // 2. Delete from admins collection
    $bulkAdmins = new MongoDB\Driver\BulkWrite;
    $bulkAdmins->delete(['companyId' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkAdmins);

    // 3. Delete from employees collection
    $bulkEmployees = new MongoDB\Driver\BulkWrite;
    $bulkEmployees->delete(['companyId' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.employees", $bulkEmployees);

    // 4. Delete from devices collection
    $bulkDevices = new MongoDB\Driver\BulkWrite;
    $bulkDevices->delete(['companyId' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkDevices);

    // 5. Delete from subscription collection
    // Note: Some collections use company_id instead of companyId
    $bulkSub = new MongoDB\Driver\BulkWrite;
    $bulkSub->delete(['company_id' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

    // 6. Delete from transaction_history collection
    // transaction_history uses companyId
    $bulkTrans = new MongoDB\Driver\BulkWrite;
    $bulkTrans->delete(['companyId' => $companyId]);
    $mongoManager->executeBulkWrite("$mongodb_name.transaction_history", $bulkTrans);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>