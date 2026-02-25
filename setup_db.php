<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS hrms_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error;
}

// Select database
$conn->select_db("hrms_db");

// Create table
$sql = "CREATE TABLE IF NOT EXISTS companies (
    sno INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    email VARCHAR(255),
    num_employees INT(10),
    plan_type VARCHAR(50),
    total_amount DECIMAL(10,2),
    next_subscription_date DATE,
    last_payment_date DATE,
    status VARCHAR(50) DEFAULT 'Active'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table companies created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error;
}

// Create Payments Table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_sno INT(6) UNSIGNED,
    payment_id VARCHAR(255),
    amount DECIMAL(10,2),
    status VARCHAR(50),
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_sno) REFERENCES companies(sno)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table payments created successfully<br>";
} else {
    echo "Error creating payments table: " . $conn->error;
}

// Insert dummy data
$sql = "INSERT INTO companies (company_name, mobile_number, email, num_employees, plan_type, total_amount, next_subscription_date, last_payment_date, status)
VALUES ('7 Dreamz Solutions', '9876543210', 'info@7dreamz.com', 25, 'Enterprise', 35000.00, '2026-12-31', '2025-12-31', 'Active')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>