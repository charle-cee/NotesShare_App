<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('config.php');

$email = $_GET['email'] ?? '';
$otp = $_GET['otp'] ?? '';
$status = false;
$showPasswordForm = false;
$message = '';
$redirectUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    // Handle password update
    $email = $_POST['email'];
    $password = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($password !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $update = "UPDATE registered_users SET password = :password WHERE email = :email";
    $stmt = $dbh->prepare($update);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!', 'redirect' => 'login.php']);
    exit;
}

if (!empty($email) && !empty($otp)) {
    try {
        $otp = trim($otp);
        $query = "SELECT otp, otp_expiry, verified FROM registered_users WHERE email = :email";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $dbOtp = trim($result['otp']);
            $otpExpiry = trim($result['otp_expiry']);
            $verified = $result['verified'];

            if ($verified === 'yes') {
                $message = "Email already verified.";
                $status = true;
                $showPasswordForm = true;
            } elseif ($dbOtp === $otp && strtotime($otpExpiry) > time()) {
                $updateQuery = "UPDATE registered_users SET verified = 'yes' WHERE email = :email";
                $updateStmt = $dbh->prepare($updateQuery);
                $updateStmt->bindParam(':email', $email);
                if ($updateStmt->execute()) {
                    $message = "Verification successful. Please set your new password.";
                    $status = true;
                    $showPasswordForm = true;
                } else {
                    $message = "Verification update failed.";
                }
            } else {
                $message = "Invalid or expired OTP. Please request new by trying to login, or register, or forgot password";
            }
        } else {
            $message = "No user found.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
} else {
    $message = "Missing email or OTP.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: black;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h2 {
            color: #003366;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 12px;
            width: 100%;
            border: none;
            background-color: #003366;
            color: gold;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0055a4;
        }
        .message {
            margin-top: 15px;
            color: #003366;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Email Verification</h2>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form id="password-form" method="POST" class="<?php echo $showPasswordForm ? '' : 'hidden'; ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="password" name="new_password" placeholder="Enter New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Set New Password</button>
    </form>
</div>

<script>
    const passwordForm = document.getElementById("password-form");

    passwordForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(passwordForm);

        const res = await fetch("", {
            method: "POST",
            body: formData
        });
        const data = await res.json();

        Swal.fire({
            icon: data.status,
            title: data.status === 'success' ? 'Success' : 'Error',
            text: data.message,
            confirmButtonColor: '#003366'
        }).then(() => {
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        });
    });
</script>
</body>
</html>
