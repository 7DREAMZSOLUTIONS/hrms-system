<?php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

try {
    $namespace = $mongodb_name . '.employees';
    $query  = new MongoDB\Driver\Query([]);
    $cursor = $mongoManager->executeQuery($namespace, $query);

    $data = [];

    foreach ($cursor as $doc) {

        // Convert BSON to array
        $doc = json_decode(json_encode($doc), true);

        // Flatten _id
        if (isset($doc['_id']['$oid'])) {
            $doc['_id'] = $doc['_id']['$oid'];
        }

        // Flatten employeeId
        if (isset($doc['employeeId']['$oid'])) {
            $doc['employeeId'] = $doc['employeeId']['$oid'];
        }

        // ---- CREATED AT ----
        if (isset($doc['createdAt']['$date'])) {
            $dateVal = $doc['createdAt']['$date'];

            if (is_array($dateVal) && isset($dateVal['$numberLong'])) {
                $timestamp = (int) round(((int)$dateVal['$numberLong']) / 1000);
            } else {
                $timestamp = (int) strtotime($dateVal);
            }

            $doc['createdAt'] = date('Y-m-d H:i:s', $timestamp);
        }

        // ---- UPDATED AT ----
        if (isset($doc['updatedAt']['$date'])) {
            $dateVal = $doc['updatedAt']['$date'];

            if (is_array($dateVal) && isset($dateVal['$numberLong'])) {
                $timestamp = (int) round(((int)$dateVal['$numberLong']) / 1000);
            } else {
                $timestamp = (int) strtotime($dateVal);
            }

            $doc['updatedAt'] = date('Y-m-d H:i:s', $timestamp);
        }

        $data[] = $doc;
    }

    echo json_encode(['data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
