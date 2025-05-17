<?php
session_start();
include('config.php');
$message = ""; // Message to display to the user

// Handle OTP resending
if (isset($_GET['email'])) {
    // Get the email from session
    $email = $_GET['email']; // Or fetch from input field if available
    // Send OTP
    require_once("mail_function.php");
    // Retrieve the stored OTP from the database
    $sql = "SELECT otp, name FROM registered_users WHERE email = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $email);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $otp = $row['otp']; // Get the existing OTP
    $name = $row['name']; // Get the existing name
    
    if ($otp) {
        // Resend OTP to the email
        $mail_status = sendOTP($name, $email, $otp); // Resend the same OTP
        if ($mail_status) {
            $success = "OTP has been resent to your email: " . substr($email, 0, 3) . str_repeat('*', strlen($email) - 6) . substr($email, -3);
        } else {
            $error_message = "Failed to resend OTP.";
        }
    } else {
        $error_message = "Error: OTP not found in the database.";
    }
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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 10px;
        }

        .form-container {
            background-color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }

        .form-container h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }

        .form-container p {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }

        .input-field {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .resend-btn {
            width: 100%;
            padding: 12px;
            background-color: #f0ad4e;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .resend-btn:hover {
            background-color: #ec971f;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .form-container {
                width: 100%;
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
        window.location.href = `resend_otp.php?email=${email}`;
    }
</script>


</head>
<body>

    <!-- OTP Form -->
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
                <p>Please check your email for the OTP.</p>
                <input type="text" name="otp" placeholder="Enter OTP" class="input-field" required>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" class="input-field" required>

                <?php } else { ?>
                <p>Failed to resend OTP. Please try again later.</p>
            <?php } ?>

            <!-- Both buttons in the same form -->
            <input type="submit" name="submit_otp" value="Submit OTP" class="btn">
           
        </form>
    </div>

</body>

</html>