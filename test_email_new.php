<?php
// test_email_new.php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "Attempting to send test email with NEW config (mail@ssexports.asia)...\n";

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 2; // 2 = client and server messages
    $mail->Debugoutput = 'echo';

    $mail->isSMTP();
    $mail->Host = 'smtp.stackmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mail@ssexports.asia';
    $mail->Password = 'mail@ssexports.asia';

    // Stackmail (StackCP) usually supports TLS on 587 or SSL on 465.
    // Let's try TLS on 587 first as it's common. If that fails, we can try SSL/465.
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('mail@ssexports.asia', 'SS Exports Test');
    $mail->addAddress('sahubarsadhik051@gmail.com', 'Initial Tester');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email (StackMail Config)';
    $mail->Body = 'This is a test email verify the StackMail SMTP configuration.';
    $mail->AltBody = 'This is a test email verify the StackMail SMTP configuration.';

    $mail->send();
    echo "\nMessage has been sent successfully!\n";

} catch (Exception $e) {
    echo "\nMessage could not be sent. Mailer Error: {$mail->ErrorInfo}\n";

    // Fallback attempt with SSL/465 if TLS fails
    echo "\nRetrying with SSL on port 465...\n";
    try {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->send();
        echo "\nMessage sent successfully on SSL/465!\n";
    } catch (Exception $e2) {
        echo "\nFailed with SSL/465 too: {$mail->ErrorInfo}\n";
    }
}
?>