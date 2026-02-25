<?php
header('Content-Type: application/json');
include 'db_connect.php';

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get raw POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate inputs
    if (empty($data['full_name']) || empty($data['email']) || empty($data['phone']) || empty($data['company_size'])) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    $full_name = $conn->real_escape_string($data['full_name']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $company_size = $conn->real_escape_string($data['company_size']);
    $message = isset($data['message']) ? $conn->real_escape_string($data['message']) : "";

    $sql = "INSERT INTO appointments (full_name, email, phone, company_size, message) VALUES ('$full_name', '$email', '$phone', '$company_size', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Appointment booked successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>