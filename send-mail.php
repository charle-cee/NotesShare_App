<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sendMail') {
    $base_url = "https://notesshare.wuaze.com";
    $verified = $_POST['verified'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email address is required.']);
        exit;
    }

    try {
        $mail = new PHPMailer(true)

        if ($verified === 'yes') {
            $mail->Subject = "Welcome to NotesShare – Account Verified";
            $mail->Body = <<<HTML
            <html>
            <body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 30px;">
                <div style="max-width: 600px; margin: auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #003366;">Hello {$name},</h2>
                    <p>Congratulations! Your <strong>NotesShare</strong> account has been successfully <strong>verified</strong>.</p>
                    <p>You can now:</p>
                    <ul style="line-height: 1.6;">
                        <li>Upload and organize your notes</li>
                        <li>Download educational resources</li>
                        <li>Engage in discussions with peers</li>
                    </ul>
                    <p style="margin-top: 30px;">
                        <a href="{$base_url}/login.php" style="background-color: #003366; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Login to Your Account</a>
                    </p>
                    <p style="font-size: 12px; color: #888; margin-top: 30px;">This is an automated message from NotesShare. Please do not reply.</p>
                </div>
            </body>
            </html>
            HTML;
        } else {
            $verificationLink = "{$base_url}/otp.php?email=" . urlencode($email) . "&resend=no";
            $mail->Subject = "Complete Your NotesShare Registration";
            $mail->Body = <<<HTML
            <html>
            <body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 30px;">
                <div style="max-width: 600px; margin: auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #003366;">Hi {$name},</h2>
                    <p>Thank you for registering on <strong>NotesShare</strong>.</p>
                    <p>Please verify your email to activate your account:</p>
                    <p style="margin-top: 20px;">
                        <a href="{$verificationLink}" style="background-color: #003366; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Verify Email</a>
                    </p>
                    <p>If you did not register for NotesShare, please ignore this email.</p>
                    <p style="font-size: 12px; color: #888; margin-top: 30px;">This is an automated message. Do not reply.</p>
                </div>
            </body>
            </html>
            HTML;
        }

        $mail->AltBody = "Please use a mail client that supports HTML.";
        $mail->send();

        echo json_encode(['success' => true, 'message' => 'Email sent successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;
?>
