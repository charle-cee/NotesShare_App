<?php
// Start the session
session_start();

// Function to generate OTP
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1); // Generate random 6-digit OTP
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // Ensure it's in WhatsApp international format (+265999123456)
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Validate phone number format
    if (!preg_match('/^\+\d{10,15}$/', $phone)) {
        die("Invalid phone number format. Use international format (e.g., +265999123456).");
    }

    // Generate OTP
    $otp = generateOTP();

    // Save OTP and phone to session for verification
    $_SESSION['otp'] = $otp;
    $_SESSION['phone'] = $phone;

    // WhatsApp API Credentials
    $token = 'EAANdla72R4kBOZB4bqBQ0mEcn1fbsHlRwUObIskyfj3MJqTFsuUOjUy2N8emFHZCAu7bJ4TIvLdneU1YvWDGj9UccrbY9ENGpO82JsSEqOuaTWtZBjA2YeScH1k7ZBZCJcJcaLIxugMtgkmO1EMToM19utZCpqlj00ZBR9uJfoZBDwmZCTuIZALZBwl5lXJNUx9xC3gF2ZAV4B7ZAV48H5fI2ZAOtUZAVpuXfbMxL9ZClJZCHGYy6T54B'; // Replace with your WhatsApp API access token
    $phone_number_id = '552467487946665'; // Replace with your Phone Number ID

    // API Endpoint
    $url = "https://graph.facebook.com/v16.0/$phone_number_id/messages";

    // Message Template
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $phone,
        "type" => "template",
        "template" => [
            "name" => "otp_message", // Replace with your pre-approved template name
            "language" => ["code" => "en_US"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $otp] // Pass the OTP here
                    ]
                ]
            ]
        ]
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute and check response
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch); // Get cURL errors if any
    curl_close($ch);

    // Handle API response
    if ($http_code === 200) {
        echo "OTP sent successfully to $phone via WhatsApp. Please verify.";
        header('Location: verify_otp.php'); // Redirect to OTP verification page
        exit();
    } else {
        // Decode the response for detailed error
        $response_data = json_decode($response, true);
        $error_message = $response_data['error']['message'] ?? 'Unknown error occurred.';
        $error_code = $response_data['error']['code'] ?? 'No code provided.';

        // Display a meaningful error message
        echo "Failed to send OTP. HTTP Code: $http_code. Error: $error_message (Code: $error_code).";
        if ($curl_error) {
            echo " cURL Error: $curl_error";
        }
    }
}
?>
