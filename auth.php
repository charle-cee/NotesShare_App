<?php
// Start session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('config.php');

$response = ['status' => '', 'message' => '', 'redirect' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Always set content type header for JSON response
    header('Content-Type: application/json');

    if ($action === "register") {
        $fullname = $_POST["fullname"];

        if (empty($fullname)) {
            $response['status'] = 'error';
            $response['message'] = "Full name is required!";
            echo json_encode($response);
            exit;
        }

        try {
            // Check if email exists
            $stmt = $dbh->prepare("SELECT verified FROM registered_users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                if ($result['verified'] == 'yes') {
                    $response['status'] = 'success';
                    $response['message'] = "Email is already registered and verified!";
                } else {
                    $response = [
                        'status' => 'warning',
                        'message' => 'Email exists but not verified. Redirecting to OTP verification. Please check your email for verification code. If its not in your inbox, try to check in spam folder',
                        'redirect' => 'otp.php?email=' . urlencode($email) . '&resend=no'
                    ];
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $dbh->prepare("INSERT INTO registered_users (email, name, password) VALUES (:email, :name, :password)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':name', $fullname);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Congrats! You are now registered with NotesShare. Please check your email for verification code. If its not in your inbox, try to check in spam folder',
                        'redirect' => 'otp.php?email=' . urlencode($email) . '&resend=no'
                    ];
                } else {
                    $response['status'] = 'error';
                    $response['message'] = "Registration failed. Try again.";
                }
            }
        } catch (PDOException $e) {
            $response['status'] = 'error';
            $response['message'] = "Database error: " . $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }

    if ($action === "login") {
        try {
            $stmt = $dbh->prepare("SELECT id, password, verified FROM registered_users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['verified'] === 'yes') {
                    session_regenerate_id(true);
                    $_SESSION['ocasuid'] = $user['id'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['last_login'] = time();

                    $response = [
                        'status' => 'success',
                        'message' => 'Login successful! Redirecting...',
                        'redirect' => 'dashboard.php'
                    ];
                } else {
                    $response = [
                        'status' => 'warning',
                        'message' => 'You are registered but not verified. Please check your email. If its not in your inbox, try to check in spam folder',
                        'redirect' => 'otp.php?email=' . urlencode($email) . '&resend=no'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid email or password.'
                ];
            }
        } catch (PDOException $e) {
            error_log("Login error for email: $email - " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => 'A system error occurred. Please try again later.'
            ];
        }

        echo json_encode($response);
        exit;
    }

    echo json_encode($response);
    exit;
}
?>
