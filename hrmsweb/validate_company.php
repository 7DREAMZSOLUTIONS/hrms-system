<?php
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mobile = $_POST['mobile_number'];

    // Prevent SQL Injection
    $stmt = $conn->prepare("SELECT sno, company_name, mobile_number, email, num_employees, plan_type, total_amount, next_subscription_date, last_payment_date, status FROM companies WHERE mobile_number = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Calculate Amount Logic
        $emp = $row['num_employees'];
        $calculated_amount = 0;
        $plan_name = $row['plan_type']; // Default to DB value

        if ($emp < 25) {
            // Plan 1: 10 to 24 (Base 3000 for 10)
            $base_price = 3000;
            $base_emp = 10;
            $extra_cost = 20;
            $plan_name = "Starter";

            if ($emp <= $base_emp) {
                $calculated_amount = $base_price;
            } else {
                $calculated_amount = $base_price + (($emp - $base_emp) * $extra_cost);
            }
        } elseif ($emp < 50) {
            // Plan 2: 25 to 49 (Base 7000 for 25)
            $base_price = 7000;
            $base_emp = 25;
            $extra_cost = 20;
            $plan_name = "Growth";

            $calculated_amount = $base_price + (($emp - $base_emp) * $extra_cost);
        } elseif ($emp < 100) {
            // Plan 3: 50 to 99 (Base 12000 for 50)
            $base_price = 12000;
            $base_emp = 50;
            $extra_cost = 20;
            $plan_name = "Business";

            $calculated_amount = $base_price + (($emp - $base_emp) * $extra_cost);
        } else {
            // Plan 4: Unlimited (100+)
            $calculated_amount = 35000;
            $plan_name = "Enterprise";
        }

        $row['total_amount'] = $calculated_amount;
        $row['plan_type'] = $plan_name; // Update plan name based on count

        echo json_encode(["success" => true, "data" => $row]);
    } else {
        echo json_encode(["success" => false, "message" => "Mobile number not found"]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

$conn->close();
?>