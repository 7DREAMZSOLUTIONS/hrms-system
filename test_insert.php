<?php
session_start();
$_SESSION['admin_logged_in'] = true;

require_once 'db_connect_mongo.php';

$ch = curl_init('http://localhost:8080/admin/ajax_add_company.php');
$payload = json_encode([
    'name' => 'Test Company',
    'gst' => 'GST123',
    'addr' => '123 Test St',
    'did' => 'DEV123',
    'sExp' => '2027-12-31',
    'dExp' => '2027-12-31',
    'eName' => 'John Doe',
    'eCode' => 'EMP123',
    'ePhone' => '1234567890',
    'eEmail' => 'john@test.com',
    'eCid' => 'CID123',
    'eCnum' => '9876543210'
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

echo $result;
?>
