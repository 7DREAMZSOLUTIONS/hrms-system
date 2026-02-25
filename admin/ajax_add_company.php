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
    $response = callApi('/companies', 'POST', $data);

    if ($response) {
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reach Node API']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>