<?php
// validate_employee_mongo.php
header('Content-Type: application/json');
require 'db_connect_mongo.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mobile = $_POST['mobile_number'] ?? '';

    if (empty($mobile)) {
        echo json_encode(["success" => false, "message" => "Mobile number is required"]);
        exit;
    }

    try {
        // defined in db_connect_mongo.php: $mongodb_name, $mongoManager

        // 1. SEARCH IN 'admins' collection
        $namespaceAdmin = "$mongodb_name.admins";
        $filterAdmin = ['phone' => $mobile];
        $queryAdmin = new MongoDB\Driver\Query($filterAdmin);
        $cursorAdmin = $mongoManager->executeQuery($namespaceAdmin, $queryAdmin);
        $resultAdmin = $cursorAdmin->toArray();

        if (count($resultAdmin) > 0) {
            $adminDoc = $resultAdmin[0];

            // Determine company ID string
            $companyIdStr = isset($adminDoc->companyId) ? $adminDoc->companyId : (string) $adminDoc->_id;

            // 2. FETCH FROM 'subscription' collection using company_id
            $namespaceSub = "$mongodb_name.subscription";
            $filterSub = ['company_id' => $companyIdStr];
            $querySub = new MongoDB\Driver\Query($filterSub);
            $cursorSub = $mongoManager->executeQuery($namespaceSub, $querySub);
            $resultSub = $cursorSub->toArray();

            $subDoc = count($resultSub) > 0 ? $resultSub[0] : null;

            // Helper to safe-get property
            $val = function ($obj, $prop, $default = '') {
                return (is_object($obj) && isset($obj->$prop)) ? $obj->$prop : $default;
            };

            $response_data = [
                'sno' => (string) $adminDoc->_id, // Need this for reference
                'companyId' => $companyIdStr, // Authoritative ID for transactions
                'company_name' => $val($adminDoc, 'companyName', 'Unknown Company'),
                'mobile_number' => $val($adminDoc, 'phone', $mobile),
                'email' => $val($adminDoc, 'email', 'N/A'),

                // Subscription Details from Validated Subscription Table
                'num_employees' => $val($subDoc, 'num_users', 10),
                'plan_type' => $val($subDoc, 'plan_type', 'Starter'),
                'subscription_amount' => $val($subDoc, 'subscription_amount', 3000),
                'next_subscription_date' => $val($subDoc, 'next_subscription_date', 'N/A'),
                'last_payment_date' => $val($subDoc, 'last_payment_date', 'N/A'),
                'status' => $val($subDoc, 'status', 'Active')
            ];

            echo json_encode(["success" => true, "data" => $response_data]);

        } else {
            echo json_encode(["success" => false, "message" => "Mobile number not found in Admins"]);
        }

    } catch (MongoDB\Driver\Exception\Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error connecting to MongoDB: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>