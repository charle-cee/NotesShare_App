<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('config.php'); // Include database connection

// Get and sanitize email from the request
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
if (empty($email)) {
    die("Invalid request. No email provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <title>Complete Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .progress span {
            width: 30%;
            height: 8px;
            background-color: #ccc;
            border-radius: 4px;
            position: relative;
        }
        .progress span.active {
            background-color: #003366;
        }
        .progress span::after {
            content: attr(data-label);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            color: #003366;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .form-group select {
            background-color: #fff;
        }
        .form-group input:disabled {
            background-color: #f9f9f9;
        }
        .badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 12px;
            margin-left: 10px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #003366;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0055a4;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .hidden {
            display: none;
        }
    </style>
   <script>
    document.addEventListener("DOMContentLoaded", function () {
        const nextButton = document.getElementById('next-button');
        const completeButton = document.getElementById('complete-button');
        const personalInfoForm = document.getElementById('personal-info-form');
        const passwordForm = document.getElementById('password-form');
        const progressSteps = document.querySelectorAll('.progress span');

        // Validate personal info fields
        function validatePersonalInfo() {
            const firstName = document.getElementById('first-name').value.trim();
            const surname = document.getElementById('surname').value.trim();
            const gender = document.getElementById('gender').value;

            return firstName !== '' && surname !== '' && gender !== '';
        }

        // Enable or disable the "Next" button based on validation
        personalInfoForm.addEventListener('input', function () {
            nextButton.disabled = !validatePersonalInfo();
        });

        // Handle "Next" button click
        nextButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (validatePersonalInfo()) {
                personalInfoForm.classList.add('hidden');
                passwordForm.classList.remove('hidden');
                progressSteps[1].classList.add('active');
            } else {
                alert("Please fill in all personal information fields!");
            }
        });

        // Validate passwords match
        function validatePasswords() {
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm-password').value.trim();

            // Ensure both passwords are at least 6 characters and match
            return password.length >= 6 && password === confirmPassword;
        }

        // Enable or disable the "Complete Registration" button based on validation
        passwordForm.addEventListener('input', function () {
            const isValid = validatePasswords();
            completeButton.disabled = !isValid;

            // Show alert dynamically for mismatched passwords
            if (!isValid && document.getElementById('confirm-password').value.trim() !== '') {
                document.getElementById('password').setCustomValidity(
                    "Passwords must be at least 6 characters and match."
                );
            } else {
                document.getElementById('password').setCustomValidity("");
            }
        });

        // Handle "Complete Registration" button
        completeButton.addEventListener('click', function (e) {
            if (!validatePasswords()) {
                e.preventDefault();
                alert("Passwords must match and be at least 6 characters long!");
            }
        });
    });
</script>

</head>
<body>
    

        <!-- Personal Information Form -->
        <form id="personal-info-form" method="POST" action="save_personal_info.php">
            <h1> User Registration</h1>
            <br>
        <div class="container">
        <div class="progress">
            <span class="active" data-label="Email Verified"></span>
            <span data-label="Personal Info"></span>
            <span data-label="Password"></span>
        </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <div class="email-container">
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    <span class="badge">Verified</span>
                </div>
            </div>
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first_name" placeholder="Enter your first name" required>
            </div>
            <div class="form-group">
                <label for="surname">Surname</label>
                <input type="text" id="surname" name="surname" placeholder="Enter your surname" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <button id="next-button" disabled>Next</button>
        </form>

       <!-- Password Form -->
        <form id="password-form" method="POST" action="complete_registration.php" class="hidden">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="form-group">
                <label for="password">Enter Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            <button type="submit" id="complete-button" disabled>Complete Registration</button>
        </form>

    </div>
</body>
</html>
