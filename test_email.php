<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Show full debug output
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '23-1-00791@vsu.edu.ph'; 
    $mail->Password   = 'mrboorcjfuabhozd'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('23-1-00791@vsu.edu.ph', 'SLSU Facility Booking');
    $mail->addAddress('23-1-00791@vsu.edu.ph'); // Send to yourself

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'If you see this, SMTP works!';

    $mail->send();
    echo "<br><h2>✅ Message has been sent</h2>";
} catch (Exception $e) {
    echo "<br><h2>❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}</h2>";
}
?>