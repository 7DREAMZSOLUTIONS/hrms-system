<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "hrms_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    echo "Table 'payments' checked/created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>