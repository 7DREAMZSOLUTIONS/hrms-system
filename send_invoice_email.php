<?php
// send_invoice_email.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require 'vendor/autoload.php';

function sendInvoiceEmail($recipientEmail, $recipientName, $invoiceDetails)
{
  $mail = new PHPMailer(true);

  // Helper to convert number to words (Simplified)
  function numberToWords($num)
  {
    // Basic implementation or placeholder if library not desired
    // For brevity, using formatter or simple logic
    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    return ucwords($f->format($num));
  }

  try {
    //Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.stackmail.com';
    $mail->SMTPAuth = true;
    // User provided credentials
    $mail->Username = 'mail@ssexports.asia';
    $mail->Password = 'mail@ssexports.asia';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
    $mail->Port = 587;

    //Recipients
    $mail->setFrom('mail@ssexports.asia', '7DREAMZ HRMS PRO');
    $mail->addAddress($recipientEmail, $recipientName);

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Subscription Invoice - 7DREAMZ HRMS PRO';

    // Construct HTML Body
    $body = "
        <html>
        <head>
          <style>
            body { font-family: sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
            .header { background: #e63946; color: #fff; padding: 10px; text-align: center; }
            .details { margin: 20px 0; }
            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
            .footer { margin-top: 30px; font-size: 0.8em; color: #777; text-align: center; }
          </style>
        </head>
        <body>
          <div class='container'>
            <div class='header'>
              <h2>Payment Successful</h2>
            </div>
            <p>Dear {$recipientName},</p>
            <p>Thank you for subscribing to 7DREAMZ HRMS PRO. Your payment has been successfully processed.</p>
            <p>Please find the attached invoice for your records.</p>
            
            <div class='details'>
              <h3>Invoice/Transaction Details</h3>
              <table class='table'>
                <tr><th>Invoice Number</th><td>{$invoiceDetails['invoice_number']}</td></tr>
                <tr><th>Transaction ID</th><td>{$invoiceDetails['payment_id']}</td></tr>
                <tr><th>Company ID</th><td>{$invoiceDetails['company_id']}</td></tr>
                <tr><th>Company Name</th><td>{$invoiceDetails['company_name']}</td></tr>
                <tr><th>Plan Type</th><td>{$invoiceDetails['plan_type']}</td></tr>
                <tr><th>Users</th><td>{$invoiceDetails['num_users']}</td></tr>
                <tr><th>Base Amount</th><td>₹{$invoiceDetails['base_amount']}</td></tr>
                <tr><th>GST (18%)</th><td>₹{$invoiceDetails['gst_amount']}</td></tr>
                <tr><th>Total Amount</th><td>₹{$invoiceDetails['amount']}</td></tr>
                <tr><th>Date</th><td>" . date('j M Y', strtotime($invoiceDetails['date'])) . "</td></tr>
                <tr><th>Next Renewal</th><td>" . date('j M Y', strtotime($invoiceDetails['next_date'])) . "</td></tr>
              </table>
            </div>

            <p>Your subscription is now active until {$invoiceDetails['next_date']}.</p>
            
            <div class='footer'>
              <p>This is an automated email. Please do not reply directly to this message.</p>
              <p>&copy; " . date('Y') . " 7DREAMZ. All rights reserved.</p>
            </div>
          </div>
        </body>
        </html>
        ";

    $mail->Body = $body;
    $mail->AltBody = "Payment Successful. Invoice No: {$invoiceDetails['invoice_number']}. Please find attached invoice.";

    // --- ATTACH INVOICE ---
    try {
      // Read Template
      $templatePath = __DIR__ . '/invoice/invoice.html';
      if (file_exists($templatePath)) {
        $invoiceHtml = file_get_contents($templatePath);

        // Read and Embed Logo if exists
        $logoPath = __DIR__ . '/invoice/img/logo_white.png';
        $logoData = '';
        if (file_exists($logoPath)) {
          $logoData = base64_encode(file_get_contents($logoPath));
          $invoiceHtml = str_replace('img/logo_white.png', 'data:image/png;base64,' . $logoData, $invoiceHtml);
        }

        // Replace Placeholders
        $invoiceHtml = str_replace('{{invoice_number}}', $invoiceDetails['invoice_number'], $invoiceHtml);
        $invoiceHtml = str_replace('{{date}}', date('F j, Y', strtotime($invoiceDetails['date'])), $invoiceHtml);
        $invoiceHtml = str_replace('{{company_name}}', $invoiceDetails['company_name'], $invoiceHtml);
        $invoiceHtml = str_replace('{{company_gst}}', 'Not Provided', $invoiceHtml); // Placeholder
        $invoiceHtml = str_replace('{{company_address}}', '', $invoiceHtml); // Placeholder

        $invoiceHtml = str_replace('{{plan_type}}', $invoiceDetails['plan_type'] . ' (' . $invoiceDetails['num_users'] . ' Users)', $invoiceHtml);

        $invoiceHtml = str_replace('{{base_amount}}', number_format($invoiceDetails['base_amount'], 2), $invoiceHtml);
        $invoiceHtml = str_replace('{{gst_amount}}', number_format($invoiceDetails['gst_amount'], 2), $invoiceHtml);

        // Calculate CGST/SGST (9% each of base)
        $half_gst = $invoiceDetails['gst_amount'] / 2;
        $invoiceHtml = str_replace('{{cgst_amount}}', number_format($half_gst, 2), $invoiceHtml);
        $invoiceHtml = str_replace('{{sgst_amount}}', number_format($half_gst, 2), $invoiceHtml);

        $invoiceHtml = str_replace('{{total_amount}}', number_format($invoiceDetails['amount'], 2), $invoiceHtml);

        // New Fields
        $invoiceHtml = str_replace('{{service_from}}', date('d-m-Y', strtotime($invoiceDetails['date'])), $invoiceHtml);
        $invoiceHtml = str_replace('{{service_to}}', date('d-m-Y', strtotime($invoiceDetails['next_date'])), $invoiceHtml);
        $invoiceHtml = str_replace('{{payment_mode}}', 'Razorpay', $invoiceHtml); // Or passed from frontend
        $invoiceHtml = str_replace('{{transaction_id}}', $invoiceDetails['payment_id'], $invoiceHtml);
        $invoiceHtml = str_replace('{{due_date}}', date('d-m-Y', strtotime($invoiceDetails['date'])), $invoiceHtml); // Typically same as payment date for prepaid

        // Amount in Words
        // Check if NumberFormatter exists, else use placeholder
        if (class_exists('NumberFormatter')) {
          $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
          $amtWords = ucwords($f->format($invoiceDetails['amount'])) . " Rupees Only";
        } else {
          $amtWords = "INR " . number_format($invoiceDetails['amount'], 2) . " Only";
        }
        $invoiceHtml = str_replace('{{amount_in_words}}', $amtWords, $invoiceHtml);

        // Customer State (Placeholder as we don't capture this yet, or default to TN)
        $invoiceHtml = str_replace('{{customer_state}}', 'Tamil Nadu (33)', $invoiceHtml);

        // Attach as PDF file
        // Configure Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Allow remote images if needed, though we use base64
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($invoiceHtml);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Get the PDF content string
        $pdfOutput = $dompdf->output();

        $mail->addStringAttachment($pdfOutput, "Invoice_{$invoiceDetails['invoice_number']}.pdf");
      } else {
        error_log("Invoice template not found at $templatePath");
      }
    } catch (Exception $e) {
      error_log("Error attaching invoice: " . $e->getMessage());
    }
    // ----------------------

    $mail->send();
    // Log success
    error_log("Invoice email sent to $recipientEmail");
    return true;
  } catch (Exception $e) {
    // Log error inside the function or return false
    error_log("Message could not be sent to $recipientEmail. Mailer Error: {$mail->ErrorInfo}");
    return false;
  }
}
?>