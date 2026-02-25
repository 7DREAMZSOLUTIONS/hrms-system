<?php
$_SESSION['admin_logged_in'] = true;
$data = ['eCid' => 'CID2', 'name' => 'Name', 'addr' => 'addr', 'gst' => 'gst'];
$raw = json_encode($data);
require_once 'db_connect_mongo.php';
// wait, the script reads from php://input, so better to curl it directly.
