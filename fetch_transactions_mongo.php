<?php
// fetch_transactions_mongo.php
header('Content-Type: application/json');
require_once 'db_connect_mongo.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $company_id = $_GET['company_id'] ?? '';

    if (empty($company_id)) {
        echo json_encode(["success" => false, "message" => "Company ID is required"]);
        exit;
    }

    try {
        // Query transaction_history for this company_id
        // Sort by created_at descending (newest first)
        $filter = ['companyId' => $company_id]; // Now stored as companyId string
        $options = [
            'sort' => ['created_at' => -1],
            'limit' => 10 // Limit to last 10 transactions for now
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $mongoManager->executeQuery("$mongodb_name.transaction_history", $query);

        $transactions = [];
        foreach ($cursor as $document) {
            // Format date
            // Format date
            $date = 'N/A';
            if (isset($document->payment_date)) {
                // If stored as string Y-m-d, reformat it
                $timestamp = strtotime($document->payment_date);
                if ($timestamp) {
                    $date = date('d M Y', $timestamp);
                } else {
                    $date = $document->payment_date;
                }
            } elseif (isset($document->created_at) && $document->created_at instanceof MongoDB\BSON\UTCDateTime) {
                $date = $document->created_at->toDateTime()->format('d M Y');
            }

            $transactions[] = [
                'payment_id' => $document->payment_id ?? 'N/A',
                'amount' => $document->amount ?? 0,
                'plan_type' => $document->plan_type ?? 'N/A',
                'num_employees' => $document->num_employees ?? 0,
                'status' => $document->status ?? 'Unknown',
                'date' => $date
            ];
        }

        echo json_encode(["success" => true, "data" => $transactions]);

    } catch (MongoDB\Driver\Exception\Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error fetching transactions: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>