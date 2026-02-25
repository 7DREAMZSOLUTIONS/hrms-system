<?php
require_once '../db_connect_mongo.php';
// fake session
session_start();
$_SESSION['admin_logged_in'] = true;

// 1. fetch a subscription record or company
$query = new MongoDB\Driver\Query([], ['limit' => 1, 'sort' => ['created_at' => -1]]);
$cursor = $mongoManager->executeQuery("$mongodb_name.subscription", $query);
$arr = $cursor->toArray();

if(!empty($arr)) {
    $id = $arr[0]->company_id;
    echo "Found ID: " . $id . "\n";
    
    // Now simulate getting from API
    $_GET['id'] = $id;
    ob_start();
    include 'ajax_get_company.php';
    $out = ob_get_clean();
    echo "API output:\n" . $out;
} else {
    echo "No subscription records\n";
}
