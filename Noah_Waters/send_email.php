<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

function sendResetEmail($to, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };

        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noah.waters.station@gmail.com';         
        $mail->Password   = 'axaelkykxomaamrd';         
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //email content kung ano yung makikita sa email na sinend sa yo
        $mail->setFrom('noah.waters.station@gmail.com', 'Noah Waters');
        $mail->addAddress($to);
        $mail->Subject = 'Password Reset Link';
        $mail->Body    = "Click the link to reset your password: $resetLink";

        $mail->send();
        error_log("Password reset email sent successfully to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send password reset email to $to. Error: " . $e->getMessage());
        error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
        return false;
    }
}
?>
