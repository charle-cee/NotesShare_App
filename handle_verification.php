<?php
include('config.php');

$response = ['status' => '', 'message' => '', 'showPasswordForm' => false];
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle OTP verification
    if (!empty($_POST["submit_otp"])) {
        $otp = trim($_POST["otp"]);
        $email = $_POST["email"];

        $query = "SELECT otp, otp_expiry FROM registered_users WHERE email = :email";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            if (strtotime($data["otp_expiry"]) < time()) {
                $response['status'] = 'error';
                $response['message'] = 'OTP expired. Please resend.';
            } elseif ((string)$data['otp'] === (string)$otp) {
                // Mark user as verified
                $updateQuery = "UPDATE registered_users SET verified = 'yes' WHERE email = :email";
                $stmt = $dbh->prepare($updateQuery);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                $response['status'] = 'success';
                $response['message'] = 'OTP verified. Enter your new password.';
                $response['showPasswordForm'] = true;
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Invalid OTP!';
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'No OTP found for this email.';
        }
    }
    // Handle password update via JS
    elseif (isset($_POST["password"])) {
        $email = $_POST["email"];
        $password = password_hash(trim($_POST["password"]), PASSWORD_BCRYPT);

        $update = "UPDATE registered_users SET password = :password WHERE email = :email";
        $stmt = $dbh->prepare($update);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Password has been set successfully!',
            'redirect' => 'login.php'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        h2 {
            color: #003366;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        button {
            background: #003366;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #FFD700;
            color: #003366;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="container">
   

    <form id="otp-form" method="POST">
    <h2>Verify OTP</h2>
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit" name="submit_otp">Verify OTP</button>
    </form>

    <form id="password-form" method="POST" class="hidden">
    <h2>Set New Password</h2>
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="password" id="password" name="password" placeholder="Enter New Password" required>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit" id="set-password-btn">Set New Password</button>
    </form>
</div>

<script>
    const passwordForm = document.getElementById('password-form');
    const otpForm = document.getElementById('otp-form');

    <?php if (!empty($response['status'])): ?>
    Swal.fire({
        icon: '<?php echo $response["status"]; ?>',
        title: '<?php echo ucfirst($response["status"]); ?>',
        text: '<?php echo $response["message"]; ?>',
        confirmButtonColor: '<?php echo $response["status"] === "success" ? "#003366" : "#d33"; ?>'
    });

    <?php if ($response["showPasswordForm"]): ?>
        otpForm.classList.add("hidden");
        passwordForm.classList.remove("hidden");
    <?php endif; ?>
    <?php endif; ?>

    passwordForm.addEventListener("submit", async function(e) {
        e.preventDefault();

        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirm_password').value.trim();

        if (password !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: 'Passwords do not match!',
                confirmButtonColor: '#d33'
            });
            return;
        }

        const formData = new FormData(passwordForm);

        const response = await fetch("", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        Swal.fire({
            icon: result.status,
            title: result.status === "success" ? "Success!" : "Error!",
            text: result.message,
            confirmButtonColor: "#003366"
        }).then(() => {
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        });
    });
</script>
</body>
</html>
