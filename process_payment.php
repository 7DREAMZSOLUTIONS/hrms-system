<?php
// process_payment.php
header('Content-Type: application/json');

// Use the MongoDB connection file
require_once 'db_connect_mongo.php';
// Include email sender
require_once 'send_invoice_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect POST data
    $payment_id = $_POST['payment_id'] ?? '';
    // $company_sno from frontend is the admin _id or companyId string
    $company_sno = $_POST['company_sno'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $plan_type = $_POST['plan_type'] ?? '';
    // map num_employees to num_users for the admins collection
    $num_employees = $_POST['num_employees'] ?? 0;

    $company_name = $_POST['company_name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';

    // Validate minimal required data
    if (empty($mobile) || empty($payment_id)) {
        echo json_encode(["success" => false, "message" => "Missing required payment or mobile information."]);
        exit;
    }

    // Step 0: Validate Admin by Phone to get authoritative companyId
    // This ensures we have the correct ID format for all subsequent updates
    try {
        $filter = ['phone' => $mobile];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $mongoManager->executeQuery("$mongodb_name.admins", $query);
        $adminDoc = current($cursor->toArray());

        if (!$adminDoc) {
            echo json_encode(["success" => false, "message" => "Admin not found for this mobile number: $mobile"]);
            exit;
        }

        // Authoritative ID for Admin Document Update (ObjectId)
        $adminObjId = $adminDoc->_id;

        // Authoritative Company ID String (e.g., COMP001)
        // If not present in DB, fall back to stringified ObjectId, but prefer the explicit field "companyId"
        $companyIdStr = isset($adminDoc->companyId) ? $adminDoc->companyId : (string) $adminDoc->_id;

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error validating admin: " . $e->getMessage()]);
        exit;
    }

    // Dates logic
    $current_date_str = date('Y-m-d');
    $next_year_date_str = date('Y-m-d', strtotime('+1 year'));

    try {
        // GST Calculation (18%)
        // Total = Base * 1.18  => Base = Total / 1.18
        $total_amount = (float) $amount;
        $base_amount = round($total_amount / 1.18, 2);
        $gst_amount = round($total_amount - $base_amount, 2);

        // Generate Invoice Number: INV-YYYYMMDD-UnknownSEQUENCENUMBER (Using random for now to avoid concurrency lock)
        // A better approach in Mongo is findAndModify a counter, but specific req: "give invoice number"
        $invoice_number = 'INV-' . date('Ymd') . '-' . mt_rand(1000, 9999);

        // 1. Prepare Transaction History Document
        $transactionDoc = [
            'payment_id' => $payment_id,
            'invoice_number' => $invoice_number,
            'companyId' => $companyIdStr,
            'company_name' => $company_name,
            'mobile' => $mobile,
            'amount' => $total_amount, // Total Paid
            'base_amount' => $base_amount,
            'gst_amount' => $gst_amount,
            'plan_type' => $plan_type,
            'num_employees' => (int) $num_employees,
            'payment_date' => $current_date_str,
            'currency' => 'INR',
            'status' => 'Success',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];

        // 2. Prepare Subscription Collection Update (New Table)

        $subFilter = ['company_id' => $companyIdStr];
        $subUpdate = [
            '$set' => [
                'company_name' => $company_name,
                'plan_type' => $plan_type,
                'num_users' => (int) $num_employees,
                'subscription_amount' => (float) $amount,
                'next_subscription_date' => $next_year_date_str,
                'last_payment_date' => $current_date_str,
                'status' => 'Active',
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ],
            '$setOnInsert' => [
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ];

        // Operation A: Transaction History
        $bulkA = new MongoDB\Driver\BulkWrite;
        $bulkA->insert($transactionDoc);
        $mongoManager->executeBulkWrite("$mongodb_name.transaction_history", $bulkA);

        // Operation B: Update/Upsert Subscription Collection
        $bulkB = new MongoDB\Driver\BulkWrite;
        $bulkB->update($subFilter, $subUpdate, ['upsert' => true]);
        $result = $mongoManager->executeBulkWrite("$mongodb_name.subscription", $bulkB);

        // Update Admins Email Only (if provided)
        if (!empty($email)) {
            try {
                $bulkAdmin = new MongoDB\Driver\BulkWrite;
                $bulkAdmin->update(['_id' => $adminObjId], ['$set' => ['email' => $email]]);
                $mongoManager->executeBulkWrite("$mongodb_name.admins", $bulkAdmin);
            } catch (Exception $e) { /* Ignore non-critical email update error */
            }
        }

        // Operation C: Update Devices Collection
        $devicesFilter = [
            'companyId' => [
                '$in' => [
                    $companyIdStr
                ]
            ]
        ];
        $devicesUpdate = [
            '$set' => [
                'companyId' => $companyIdStr,
                'subscriptionExpiry' => $next_year_date_str
            ]
        ];

        $bulkC = new MongoDB\Driver\BulkWrite;
        $bulkC->update($devicesFilter, $devicesUpdate, ['multi' => true]);
        $mongoManager->executeBulkWrite("$mongodb_name.devices", $bulkC);

        // ---------------------------------------------------------------- //
        // 3. Send Email to ALL Admins (Iterate Employees Collection)
        // ---------------------------------------------------------------- //
        try {
            // Find all employees with staffType = 'admin' AND companyId = current companyId
            // Also ensure they have a valid email (not null)
            $empFilter = [
                'companyId' => $companyIdStr,
                'staffType' => 'admin',
                'email' => ['$ne' => null]
            ];

            $empQuery = new MongoDB\Driver\Query($empFilter);
            $empCursor = $mongoManager->executeQuery("$mongodb_name.employees", $empQuery);

            $adminEmails = [];

            foreach ($empCursor as $emp) {
                if (!empty($emp->email)) {
                    $adminEmails[$emp->email] = isset($emp->name) ? $emp->name : 'Admin';
                }
            }

            // Also ensure the primary email from the POST request is included if valid
            if (!empty($email)) {
                if (!isset($adminEmails[$email])) {
                    $adminEmails[$email] = !empty($company_name) ? $company_name : "Valued Customer";
                }
            }

            // Send Emails
            foreach ($adminEmails as $recipientEmail => $recipientName) {
                $invoiceDetails = [
                    'invoice_number' => $invoice_number,
                    'payment_id' => $payment_id,
                    'company_id' => $companyIdStr,
                    'company_name' => $company_name,
                    'plan_type' => $plan_type,
                    'num_users' => $num_employees,
                    'amount' => $total_amount,
                    'base_amount' => $base_amount,
                    'gst_amount' => $gst_amount,
                    'date' => $current_date_str,
                    'next_date' => $next_year_date_str
                ];
                // Function handles its own error logging
                sendInvoiceEmail($recipientEmail, $recipientName, $invoiceDetails);
            }

        } catch (Exception $e) {
            // Log error but don't fail the payment response
            error_log("Email sending batch failed: " . $e->getMessage());
        }

        // Return Success Response
        if ($result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0 || $result->getMatchedCount() > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Payment successful. Subscription updated & Activation emails sent to known admins.",
                "next_subscription_date" => $next_year_date_str
            ]);
        } else {
            // Fallback if no document matched (maybe ID was wrong)
            echo json_encode(["success" => true, "message" => "Payment recorded, but subscription update found no matching record."]);
        }

    } catch (MongoDB\Driver\Exception\Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "General Error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>