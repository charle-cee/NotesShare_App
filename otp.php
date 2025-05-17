<?php
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 0); // Display errors on the screen
session_start();
include('config.php');
require_once("mail_function.php"); // Ensure this file exists and works

// Initialize variables for success and error messages
$success = "";
$error_message = "";

// Handle email submission for OTP generation
if (isset($_GET['email']) && isset($_GET['resend'])) {
    // Get and sanitize the email
    $email = htmlspecialchars($_GET['email']);
    $resend = htmlspecialchars($_GET['resend']); // Check if resend flag is explicitly 'yes' or 'no'

    try {
      // Check if email and name exist in the registered_users table
$query = "SELECT otp, name FROM registered_users WHERE email = :email";
$stmt = $dbh->prepare($query);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

// Fetch the row as an associative array
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stored_otp = $row['otp'] ?? null;
$name = $row['name'] ?? null;
        if ($stmt->rowCount() > 0) {
            if ($resend === "yes") {
                // Resend the existing OTP
                if ($stored_otp) {
                    $mail_status = sendOTP($name, $email, $stored_otp); // Function to send the OTP via email
                    $mail_status = $_SESSION['mail_status'];
                    if ($mail_status === 1) {
                        // Mask email for security
                        $masked_email = substr($email, 0, 3) . str_repeat('*', strlen($email) - 6) . substr($email, -3);
                        $success = "OTP has been resent to your email: " . $masked_email;
                    } else {
                        $error_message = "Failed to resend OTP.";
                    }
                } else {
                    $error_message = "No OTP found to resend.";
                }
            } elseif ($resend === "no") {
                // Generate a new OTP for first-time send
                $otp = rand(100000, 999999); // Generate a random 6-digit OTP

                // Save the OTP to the database
                $updateQuery = "UPDATE registered_users SET otp = :otp WHERE email = :email";
                $updateStmt = $dbh->prepare($updateQuery);
                $updateStmt->bindParam(':otp', $otp, PDO::PARAM_STR);
                $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $updateStmt->execute();

                // Send the generated OTP
                $mail_status = sendOTP($name, $email, $otp);
                $mail_status = $_SESSION['mail_status'];
                if ($mail_status === 1) {
                    // Mask email for security
                    $masked_email = substr($email, 0, 3) . str_repeat('*', strlen($email) - 6) . substr($email, -3);
                    $success = "OTP has been sent to your email: " . $masked_email;
                } else {
                    $error_message = "Failed to send OTP.";
                }
            } else {
                $error_message = "Invalid resend parameter value.";
            }
        } else {
            $error_message = "Email does not exist in the system.";
        }
    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
} else {
    $error_message = "Email parameter is missing.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <title>User OTP Login</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #003366, #001f4d);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 10px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            text-align: center;
        }

        .form-container h2 {
            font-size: 26px;
            margin-bottom: 15px;
            color: #003366;
        }

        .form-container p {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .input-field {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #ffd700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #003366;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #e6c300;
        }

        .resend-btn {
            width: 100%;
            padding: 12px;
            background: #f0ad4e;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: white;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .resend-btn:hover {
            background: #ec971f;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .form-container {
                width: 95%;
                padding: 20px;
            }

            .btn, .resend-btn {
                font-size: 14px;
            }
        }
    </style>
   <script>
    function triggerResend() {
        const email = "<?php echo $email; ?>"; // Inject PHP variable into JavaScript
        if (email) {
            // Redirect to the same page with email and resend=yes
            window.location.href = `?email=${encodeURIComponent(email)}&resend=yes`;
        } else {
            alert("Email not found!"); // Handle case where email is not available
        }
    }
</script>
</head>
<body>

    <div class="container">
        <form name="frmOTP" method="post" action="handle_verification.php" class="form-container">
    <h2>Enter OTP</h2>

    <?php if (!empty($success)) { ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php } ?>

    <?php if (!empty($error_message)) { ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php } ?>

    <?php if ($mail_status === 1) { ?>
        <p>Please check your email inbox for the OTP. If not in the inbox, try to check in the spam folder.</p>
        <input type="text" name="otp" placeholder="Enter OTP" class="input-field" required>
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

    <?php } else { ?>
        <p>Failed to send OTP. Please try again later.</p>
    <?php } ?>

    <input type="submit" name="submit_otp" value="Verify OTP" class="btn">

    <?php if ($mail_status === 0) { ?>
        <!-- Resend OTP button will only be visible when mail_status is 0 -->
        <input type="button" value="Resend OTP" class="resend-btn" onclick="triggerResend()">
    <?php } ?>

</form>
    </div>

</body>
</html>
