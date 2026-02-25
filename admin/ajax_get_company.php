<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../db_connect_mongo.php';

$companyId = $_GET['id'] ?? '';

if (empty($companyId)) {
    echo json_encode(['success' => false, 'message' => 'Company ID is required']);
    exit;
}

try {
    // 1. Fetch Company Details
    $filter = ['companyId' => $companyId];
    $query = new MongoDB\Driver\Query($filter);
    $companyCursor = $mongoManager->executeQuery("$mongodb_name.companies", $query);
    $companyArray = $companyCursor->toArray();

    if (empty($companyArray)) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    $companyDoc = $companyArray[0];

    // 2. Fetch Device Details
    $deviceQuery = new MongoDB\Driver\Query(['companyId' => $companyId]);
    $deviceCursor = $mongoManager->executeQuery("$mongodb_name.devices", $deviceQuery);
    $deviceArray = $deviceCursor->toArray();
    $deviceDoc = !empty($deviceArray) ? $deviceArray[0] : null;

    // 3. Fetch Admin Details
    $adminQuery = new MongoDB\Driver\Query(['companyId' => $companyId]);
    $adminCursor = $mongoManager->executeQuery("$mongodb_name.admins", $adminQuery);
    $adminArray = $adminCursor->toArray();
    $adminDoc = !empty($adminArray) ? $adminArray[0] : null;

    // 4. Fetch Employee Details to get empCode (assuming first admin employee for this company)
    $empQuery = new MongoDB\Driver\Query(['companyId' => $companyId, 'staffType' => 'admin']);
    $empCursor = $mongoManager->executeQuery("$mongodb_name.employees", $empQuery);
    $empArray = $empCursor->toArray();
    $empDoc = !empty($empArray) ? $empArray[0] : null;

    // Format data for response
    $responseData = [
        'companyId' => $companyDoc->companyId ?? '',
        'companyName' => $companyDoc->companyName ?? '',
        'address' => $companyDoc->address ?? 'N/A',
        'gstNumber' => $companyDoc->gstNumber ?? 'N/A',
        'status' => (isset($companyDoc->isActive) && $companyDoc->isActive) ? 'Active' : 'Inactive',
        'deviceId' => $deviceDoc->deviceId ?? 'N/A',
        'subscriptionExpiry' => $deviceDoc->subscriptionExpiry ?? 'N/A',
        'adminName' => $adminDoc->fullName ?? 'N/A',
        'adminEmail' => $adminDoc->email ?? 'N/A',
        'adminPhone' => $adminDoc->phone ?? 'N/A',
        'empCode' => $empDoc->empCode ?? 'N/A'
    ];

    echo json_encode(['success' => true, 'data' => $responseData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>