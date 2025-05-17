<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp']) {
        echo "Phone number verified successfully!";
        // Proceed with registration (e.g., save user data to database)
    } else {
        echo "Invalid OTP. Please try again.";
    }
}
?>

<form method="post">
    <label>Enter OTP sent to <?php echo htmlspecialchars($_SESSION['phone']); ?>:</label>
    <input type="text" name="otp" required>
    <button type="submit">Verify</button>
</form>
