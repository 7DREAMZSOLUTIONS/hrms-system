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
    $response = callApi('/companies/' . $companyId, 'GET');

    if ($response) {
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reach Node API']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>