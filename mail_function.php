<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start the session if not already started
}

// Manually include the PHPMailer files
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Base URL for generating links
$base_url = "https://notesshare.wuaze.com";

function sendOTP($name, $email, $otp) {
    global $dbh, $base_url; // Use the global PDO object and base URL
    
    try {
        // Generate the verification link
        $verificationLink = $base_url . "/relay_verify.php?email=" . urlencode($email) . "&otp=" . urlencode($otp);

        // Styled email body
        $message_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .email-container { background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px #ddd; }
                h2 { color: #003366; }
                p { color: #333; font-size: 16px; }
                .otp-code { font-size: 24px; font-weight: bold; color: #ff6600; }
                .btn { display: inline-block; background: #003366; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 18px; }
                .footer { font-size: 12px; color: #888; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <h2>Verify Your Email</h2>
                <p>Dear $name,</p>
                <p>Your One Time Password (OTP) for verification is:</p>
                <p class='otp-code'>$otp</p>
                <p>Please enter this OTP to verify your account. Alternatively, you can click the button below:</p>
                <p><a href='$verificationLink' class='btn' target='_blank'>Verify Email</a></p>
                <p><strong>Note:</strong> This OTP is valid for <strong>15 minutes</strong>. Do not share it with anyone.</p>
                <p class='footer'>This is an automated email from Charle Cee Graphix. Please do not reply.</p>
            </div>
        </body>
        </html>
        ";

        // Create a new PHPMailer instance
        $mail = new PHPMailer();

        // Set mailer to use SMTP
        $mail->isSMTP();  
        $mail->SMTPDebug = 0;  // Disable verbose debugging
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL encryption
        $mail->Port = 465; // Use the SMTP port for SSL (465)
        
        // SMTP server configuration
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->Username = 'charleceegraphix@gmail.com'; // Your email address (SMTP username)
        $mail->Password = 'veiz tbpo kwta rqvr'; // Your app password or email password
        $mail->SetFrom('charleceegraphix@gmail.com', 'NotesShare'); // Sender's email and name
        $mail->AddAddress($email); // Recipient's email address
        $mail->addReplyTo('charleceegraphix@gmail.com', 'No Reply'); // Disable email replies

        // Set email subject and body
        $mail->Subject = "Verify Your Email Address";
        $mail->MsgHTML($message_body);
        $mail->isHTML(true);

        // Send email
        if ($mail->send()) {
            // Set OTP sent and expiry timestamps (15 minutes from now)
            $otp_sent = date('Y-m-d H:i:s'); // Current time
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Expiry time

            // Update the database with the OTP, sent time, and expiry time
            $sql = "UPDATE registered_users SET otp = :otp, otp_sent = :otp_sent, otp_expiry = :otp_expiry WHERE email = :email";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':otp', $otp);
            $stmt->bindParam(':otp_sent', $otp_sent);
            $stmt->bindParam(':otp_expiry', $otp_expiry);
            $stmt->bindParam(':email', $email);

            if ($stmt->execute()) {
                $_SESSION['mail_status'] = 1;  // Store mail status in session
            } else {
                $_SESSION['mail_status'] = 0;  // Store mail status in session
            }
        } else {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        // Handle any errors that occur
        echo "Error sending OTP: " . $e->getMessage();
    }
}
?>