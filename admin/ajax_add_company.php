<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../db_connect_mongo.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data payload']);
    exit;
}

try {
    $now = new MongoDB\BSON\UTCDateTime();
    $companyId = !empty($data['eCid']) ? $data['eCid'] : 'COMP' . time();
    $companyName = !empty($data['name']) ? $data['name'] : 'Unknown';
    $address = $data['addr'] ?? '';
    $gst = $data['gst'] ?? '';
    $planType = !empty($data['planType']) ? $data['planType'] : 'Starter';

    // 1. Insert into companies collection
    $bulkCompanies = new MongoDB\Driver\BulkWrite;
    $companyDoc = [
        '_id' => new MongoDB\BSON\ObjectId(),
        'companyId' => $companyId,
        'companyName' => $companyName,
        'isActive' => true,
        'createdAt' => $now,
        'updatedAt' => $now,
        '__v' => 0,
        'address' => $address,
        'gstNumber' => $gst,
        'officeTimings' => [
            'startTime' => '10:00',
            'endTime' => '18:00'
        ],
        'weekends' => [
            'sundayOff' => true,
            'saturday' => ['offWeeks' => [2, 4]]
        ],
        'planType' => $planType
    ];
    $bulkCompanies->insert($companyDoc);
    $mongoManager->executeBulkWrite("$mongodb_name.companies", $bulkCompanies);

    // 2. Insert into devices collection
    if (!empty($data['did'])) {
        $bulkDevices = new MongoDB\Driver\BulkWrite;
        $deviceDoc = [
            '_id' => new MongoDB\BSON\ObjectId(),
            'deviceId' => $data['did'],
            'subscriptionExpiry' => !empty($data['sExp']) ? $data['sExp'] : null,
            'companyId' => $companyId,
            'createdAt' => $now,
            'updatedAt' => $now,
            '__v' => 0,
            'planType' => $planType
        ];
        $bulkDevices->insert($deviceDoc);
        $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkDevices);
    }

    // Also insert into subscription collection (since dashboard reads this table)
    $bulkSub = new MongoDB\Driver\BulkWrite;
    $subDoc = [
        '_id' => new MongoDB\BSON\ObjectId(),
        'company_id' => $companyId,
        'company_name' => $companyName,
        'plan_type' => $planType,
        'num_users' => 0,
        'subscription_amount' => 0,
        'status' => 'Active',
        'next_subscription_date' => !empty($data['sExp']) ? $data['sExp'] : null,
        'created_at' => $now
    ];
    $bulkSub->insert($subDoc);
    $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkSub);

    // 3. Insert into employees collection
    $employeeObjId = new MongoDB\BSON\ObjectId();
    $defaultPassword = password_hash('123456', PASSWORD_BCRYPT); // Or custom flow

    $bulkEmp = new MongoDB\Driver\BulkWrite;
    $empDoc = [
        '_id' => $employeeObjId,
        'name' => !empty($data['eName']) ? $data['eName'] : 'Admin',
        'empCode' => $data['eCode'] ?? ('ADM-' . time()),
        'phone' => $data['ePhone'] ?? '',
        'email' => !empty($data['eEmail']) ? $data['eEmail'] : 'N/A',
        'password' => $defaultPassword,
        'companyId' => $companyId,
        'companyName' => $companyName,
        'staffType' => 'admin',
        'salary' => 0,
        'isDeleted' => false,
        'createdAt' => $now,
        'updatedAt' => $now,
        'planType' => $planType
    ];
    $bulkEmp->insert($empDoc);
    $mongoManager->executeBulkWrite("$mongodb_name.employees", $bulkEmp);

    // 4. Insert into admins collection
    $bulkAdmins = new MongoDB\Driver\BulkWrite;
    $adminDoc = [
        '_id' => new MongoDB\BSON\ObjectId(),
        'fullName' => !empty($data['eName']) ? $data['eName'] : 'Admin',
        'phone' => $data['ePhone'] ?? '',
        'password' => $defaultPassword,
        'role' => 'hr_admin', // Based on user requirements
        'employeeId' => $employeeObjId,
        'companyId' => $companyId,
        'companyName' => $companyName,
        'email' => !empty($data['eEmail']) ? $data['eEmail'] : 'N/A',
        'createdAt' => $now,
        'updatedAt' => $now,
        'planType' => $planType
    ];
    $bulkAdmins->insert($adminDoc);
    $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkAdmins);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>