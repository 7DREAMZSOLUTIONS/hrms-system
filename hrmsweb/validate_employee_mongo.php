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
        $namespace = "hrms.employees"; // Using 'employees' collection as requested

        // Create a query filter
        $filter = ['mobile' => $mobile]; // Assuming field name is 'mobile' or 'mobile_number'

        // Try 'mobile' first, if not found, we might need to adjust based on actual DB structure.
        // For now we will check both 'mobile' and 'mobile_number' just in case.
        $query = new MongoDB\Driver\Query(['$or' => [['mobile' => $mobile], ['mobile_number' => $mobile]]]);

        $cursor = $mongoManager->executeQuery($namespace, $query);
        $result = $cursor->toArray();

        if (count($result) > 0) {
            $doc = $result[0];

            // Map MongoDB document fields to the Expected JSON structure for recharge.html
            // Expected: sno, company_name, mobile_number, email, num_employees, plan_type, next_subscription_date, last_payment_date, status

            // Helper to get field safely
            $get = function ($obj, $prop, $default = '') {
                return isset($obj->$prop) ? $obj->$prop : $default;
            };

            $response_data = [
                'sno' => (string) $get($doc, '_id'), // Using ID as Sno
                'company_name' => $get($doc, 'company_name', $get($doc, 'name', 'Unknown Company')),
                'mobile_number' => $get($doc, 'mobile', $get($doc, 'mobile_number', $mobile)),
                'email' => $get($doc, 'email', 'N/A'),
                'num_employees' => $get($doc, 'num_employees', 0),
                'plan_type' => $get($doc, 'plan_type', 'Starter'),
                'next_subscription_date' => $get($doc, 'next_subscription_date', 'N/A'),
                'last_payment_date' => $get($doc, 'last_payment_date', 'N/A'),
                'status' => $get($doc, 'status', 'Active')
            ];

            // Re-calculate plan logic if needed, but for now trusting DB or Defaults
            // The frontend also calculates logic based on num_employees, so we just need to pass the count.

            echo json_encode(["success" => true, "data" => $response_data]);

        } else {
            echo json_encode(["success" => false, "message" => "Mobile number not found in MongoDB"]);
        }

    } catch (MongoDB\Driver\Exception\Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error connecting to MongoDB: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>