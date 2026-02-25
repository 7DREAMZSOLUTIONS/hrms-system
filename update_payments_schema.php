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

// Array of columns to add
$columns = [
    "company_name VARCHAR(255)",
    "mobile VARCHAR(15)",
    "email VARCHAR(255)"
];

foreach ($columns as $col) {
    $colName = explode(" ", $col)[0];
    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM payments LIKE '$colName'";
    $result = $conn->query($checkSql);

    if ($result->num_rows == 0) {
        // Add column
        $sql = "ALTER TABLE payments ADD $col AFTER company_sno";
        if ($conn->query($sql) === TRUE) {
            echo "Column '$colName' added successfully.<br>";
        } else {
            echo "Error adding column '$colName': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$colName' already exists.<br>";
    }
}

$conn->close();
?>