<?php
header('Content-Type: application/json');
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_id = $_POST['payment_id'];
    $company_sno = $_POST['company_sno'];
    $amount = $_POST['amount'];
    $plan_type = $_POST['plan_type'];
    $num_employees = $_POST['num_employees'];

    $company_name = $_POST['company_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];

    // Start Transaction
    $conn->begin_transaction();

    try {
        // 1. Insert into payments table
        $stmt = $conn->prepare("INSERT INTO payments (company_sno, company_name, mobile, email, payment_id, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'Success')");
        $stmt->bind_param("issssd", $company_sno, $company_name, $mobile, $email, $payment_id, $amount);
        $stmt->execute();
        $stmt->close();

        // 2. Update companies table
        // Add 1 Year to current date for next subscription
        $next_date = date('Y-m-d', strtotime('+1 year'));
        $last_date = date('Y-m-d');

        $stmt = $conn->prepare("UPDATE companies SET 
            num_employees = ?, 
            plan_type = ?, 
            total_amount = ?, 
            last_payment_date = ?, 
            next_subscription_date = ?, 
            status = 'Active' 
            WHERE sno = ?");

        $stmt->bind_param("isdssi", $num_employees, $plan_type, $amount, $last_date, $next_date, $company_sno);
        $stmt->execute();
        $stmt->close();

        // Commit Transaction
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Payment processed successfully"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Transaction failed: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}

$conn->close();
?>