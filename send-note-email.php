<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable for production

require_once "config.php";
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify admin session
if (empty($_SESSION['ocasuid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get required POST data with validation
$requiredFields = ['action', 'noteid', 'email', 'name', 'title', 'subject', 'filename'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$action = $_POST['action']; // 'approve' or 'reject'
$noteId = (int)$_POST['noteid'];
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$name = htmlspecialchars($_POST['name']);
$title = htmlspecialchars($_POST['title']);
$subject = htmlspecialchars($_POST['subject']);
$filename = htmlspecialchars($_POST['filename']);

// Validate email
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Get current date for the email
$currentDate = date("F j, Y");
$statusText = $action === 'approve' ? 'approved' : 'rejected';
$statusColor = $action === 'approve' ? '#4CAF50' : '#F44336'; // Green for approved, Red for rejected

try {
    $mail = new PHPMailer(true);

    // SMTP Configuration (move these to config.php in production)
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->Username = 'charleceegraphix@gmail.com'; // Replace with your email
    $mail->Password = 'veiz tbpo kwta rqvr';      // Replace with your app password

    // Sender and recipient
    $mail->setFrom('charleceegraphix@gmail.com', 'NotesShare Admin');
    $mail->addAddress($email, $name);
    $mail->addReplyTo('no-reply@notesshare.com', 'No Reply');

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "Your Note Has Been " . ucfirst($statusText);
    
    $mail->Body = <<<HTML
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f7fb; padding: 20px; margin: 0; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .header { text-align: center; background-color: #003366; color: #FFD700; padding: 20px 0; border-radius: 8px 8px 0 0; }
            .status { font-weight: bold; color: $statusColor; font-size: 18px; }
            .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #eee; padding-top: 10px; }
            .btn { padding: 12px 20px; background: #003366; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; text-align: center; display: inline-block; }
            .btn:hover { background-color: #FFD700; color: #003366; }
            .note-details { margin-top: 20px; }
            .note-details ul { list-style-type: none; padding-left: 0; }
            .note-details li { margin-bottom: 10px; }
            .note-details li strong { color: #003366; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>NotesShare Update</h2>
            </div>
            <p>Hello $name,</p>
            <p>We wanted to inform you that your note titled "<strong>$title</strong>" has been <span class="status">$statusText</span> by the NotesShare admin team.</p>
            
            <div class="note-details">
                <h4>Note Details:</h4>
                <ul>
                    <li><strong>Title:</strong> $title</li>
                    <li><strong>Subject:</strong> $subject</li>
                    <li><strong>File:</strong> $filename</li>
                    <li><strong>Date Processed:</strong> $currentDate</li>
                </ul>
            </div>
            
            <p>If you have any questions, feel free to reach out to us.</p>
            
            <p>Thank you for your contribution!</p>

            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; <?php echo date('Y'); ?> NotesShare. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    HTML; ?>
<?php
    $mail->AltBody = "Hello $name,\n\n" .
        "We wanted to inform you that your note titled \"$title\" has been $statusText by the NotesShare admin team.\n\n" .
        "Note Details:\n" .
        "- Title: $title\n" .
        "- Subject: $subject\n" .
        "- File: $filename\n" .
        "- Date Processed: $currentDate\n\n" .
        "If you have any questions, feel free to reach out to us.\n\n" .
        "Thank you for your contribution!\n\n" .
        "This is an automated message. Please do not reply.";

    if ($mail->send()) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    } else {
        throw new Exception($mail->ErrorInfo);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send notification',
        'error' => $e->getMessage() // Only include in development
    ]);
}
?>
