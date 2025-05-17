<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login & Signup - NotesShare</title>
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #003366, #1a1a2e); /* Dark Blue & Dark Color Gradient */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .wrapper {
            width: 90%;
            max-width: 400px;
            background: #ffffff; /* White background for a cleaner look */
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
            padding: 30px;
            text-align: center;
        }

        /* Center logo with padding and smooth animation */
        .logo {
            width: 120px;
            margin-bottom: 20px;
            animation: logoFadeIn 1s ease-in-out;
        }

        /* Logo animation */
        @keyframes logoFadeIn {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: #003366; /* Dark Blue */
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: bold;
        }

        .input-box {
            position: relative;
            margin-bottom: 20px;
        }

        .input-box input {
            width: 100%;
            padding: 10px;
            border: none;
            border-bottom: 2px solid #ffd700; /* Gold color for input border */
            background: transparent;
            color: #003366; /* Dark Blue for text */
            font-size: 16px;
            outline: none;
        }

        .input-box label {
            position: absolute;
            top: 10px;
            left: 0;
            font-size: 16px;
            color: #003366;
            transition: 0.5s;
        }

        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top: -10px;
            font-size: 12px;
            color: #ffd700; /* Gold color when input is focused */
        }

        .input-box i {
            position: absolute;
            right: 0;
            bottom: 10px;
            font-size: 20px;
            color: #003366;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: #ffd700; /* Gold background */
            border: none;
            border-radius: 5px;
            color: #003366;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #c0a040;
        }

        .linkTxt {
            margin-top: 10px;
            color: #003366;
        }

        .linkTxt a {
            color: #ffd700;
            text-decoration: none;
            font-weight: bold;
        }

        .linkTxt a:hover {
            text-decoration: underline;
        }

        /* Message Box */
        .message {
            display: none;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }

        .error {
            background: #ff4d4d;
            color: white;
        }

        .success {
            background: #28a745;
            color: white;
        }

        /* Hide the forms by default, only showing the active one */
        .form-box {
            display: none;
        }

        .form-box.active {
            display: block;
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <!-- Logo -->
        <img src="includes/logo.png" alt="NotesShare Logo" class="logo">

        <!-- Login Form -->
        <div class="form-box login active">
            <h2>Login</h2>
            <form id="loginForm">
                <div class="input-box">
                    <input type="email" id="loginEmail" required autocomplete="off" />
                    <label>Email</label>
                    <i class="bx bxs-envelope"></i>
                </div>

                <div class="input-box">
                    <input type="password" id="loginPassword" required autocomplete="off" />
                    <label>Password</label>
                    <i class="bx bxs-lock-alt"></i>
                </div>

                <button type="button" class="btn" onclick="handleAuth('login')">Login</button>

                <div class="linkTxt">
                    <p>Don't have an account? <a href="#" class="register-link">Sign Up</a></p>
                    <p>Forgot password? <a href="forgot-password.php" class="forgot-link">Forgot Password</a></p>
                    
                </div>
<div style="text-align: center;">
  <a href="download.php" 
     style="display: inline-block; padding: 10px 20px; border: 2px solid #003366; color: #003366; background-color: transparent; text-decoration: none; font-weight: bold; border-radius: 12px;">
     Access Notes
  </a>
</div>

                <div class="message" id="loginMessage"></div>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="form-box register">
            <h2>Sign Up</h2>
            <form id="registerForm">
                <div class="input-box">
                    <input type="text" id="fullname" required autocomplete="off" />
                    <label>Full Name</label>
                    <i class="bx bxs-user"></i>
                </div>

                <div class="input-box">
                    <input type="email" id="registerEmail" required autocomplete="off" />
                    <label>Email</label>
                    <i class="bx bxs-envelope"></i>
                </div>

<div class="input-box" style="display: none;">
    <input type="password" id="registerPassword" required autocomplete="off" />
    <label>Password</label>
    <i class="bx bxs-lock-alt"></i>
</div>




                <button type="button" class="btn" onclick="handleAuth('register')">Sign Up</button>

                <div class="linkTxt">
                    <p>Already have an account? <a href="#" class="login-link">Login</a></p>
                    <div style="text-align: center;">
  <a href="download.php" 
     style="display: inline-block; padding: 10px 20px; border: 2px solid #003366; color: #003366; background-color: transparent; text-decoration: none; font-weight: bold; border-radius: 12px;">
     Access Notes
  </a>
</div>

                </div>

                <div class="message" id="registerMessage"></div>
            </form>
        </div>
    </div>
<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const loginForm = document.querySelector('.form-box.login');
const registerForm = document.querySelector('.form-box.register');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');

// Switch between login and register forms
registerLink.addEventListener('click', () => {
    loginForm.classList.remove('active');
    registerForm.classList.add('active');
});

loginLink.addEventListener('click', () => {
    registerForm.classList.remove('active');
    loginForm.classList.add('active');
});

async function handleAuth(action) {
    const email = action === 'login' ? document.getElementById('loginEmail').value : document.getElementById('registerEmail').value;
    const password = action === 'login' ? document.getElementById('loginPassword').value : document.getElementById('registerPassword').value;
    const fullname = action === 'register' ? document.getElementById('fullname').value : '';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('email', email);
    formData.append('password', password);
    if (fullname) formData.append('fullname', fullname);

    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        // Use SweetAlert to show the message
        Swal.fire({
            icon: result.status,
            title: result.status === 'success' ? 'Success!' : result.status === 'warning' ? 'Warning!' : 'Oops!',
            text: result.message,
            confirmButtonColor: '#003366',
        }).then((swalResult) => {
            // Handle redirection if needed
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: 'Something went wrong! Please try again later.',
            confirmButtonColor: '#003366',
        });
    }
}

</script>
<script>
    // Function to generate a random password
    function generateRandomPassword(length) {
        const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=<>?";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    // Generate a random password and set it to the password field
    const randomPassword = generateRandomPassword(12); // You can adjust the length as needed
    document.getElementById("registerPassword").value = randomPassword;
</script>
</body>

</html>
