<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

function sendResetEmail($to, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noah.waters.station@gmail.com';         // Your Gmail address
        $mail->Password   = 'axaelkykxomaamrd';           // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email content
        $mail->setFrom('noah.waters.station@gmail.com', 'Noah Waters');
        $mail->addAddress($to);
        $mail->Subject = 'Password Reset Link';
        $mail->Body    = "Click the link to reset your password: $resetLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
