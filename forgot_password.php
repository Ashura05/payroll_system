<?php
session_start();

// Include PHPMailer files
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate OTP
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

// Database connection
$host = 'localhost';
$db = 'CoffeeShop';
$user = 'root';
$pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $sql = "SELECT * FROM admin_accounts WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin) {
            $otp = generateOTP();
            $_SESSION['otp'] = $otp;
            $_SESSION['email'] = $email;
            $_SESSION['reset_admin_id'] = $admin['id'];
            $_SESSION['otp_expiry'] = time() + (10 * 60);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'vsg030@gmail.com';
                $mail->Password = 'fots xmad zdmc ewga';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('your_email@gmail.com', 'Your Application');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP';
                $mail->Body = "Your OTP is: $otp";

                $mail->send();
                echo "OTP sent. Please check your email.<br>";
            } catch (Exception $e) {
                echo "Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Email not found.";
        }
    }

    if (isset($_POST['otp'])) {
        $userOtp = trim($_POST['otp']);
        $current_time = time();

        if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
            if ($userOtp == $_SESSION['otp'] && $current_time <= $_SESSION['otp_expiry']) {
                header("Location: reset_password.php");
                exit();
            } else {
                echo "Invalid OTP or OTP has expired.";
            }
        } else {
            echo "OTP has expired.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/forgot_pass.css">
    <title>Forgot Password</title>
</head>
<body>

<div class="left-side">
        <img src="img/lourds_cafe.jpg" alt="Company Logo" class="logo-left">
    </div>

    <div class="container">
        <h2>Forgot Password</h2>

        <!-- Form to request OTP -->
        <div class="email-form">
            <form method="POST" action="">
                <label for="email">Enter Your Email:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Send OTP</button>
            </form>
            <div class="form-message">Enter your registered email to receive an OTP for password reset.</div>
        </div>

        <!-- Form to verify OTP -->
        <div class="otp-form">
            <form method="POST" action="">
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required>
                <button type="submit">Verify OTP</button>
            </form>
            <div class="form-message">Enter the OTP sent to your email address.</div>
        </div>
    </div>

    <script>
        // Optionally add some JavaScript to toggle forms between email and OTP inputs
        // For instance, when the OTP is sent, you can show the OTP verification form.
        const emailForm = document.querySelector('.email-form');
        const otpForm = document.querySelector('.otp-form');

        // Simulate OTP form showing after email form submission (for demo purposes)
        emailForm.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form submission for demo
            emailForm.style.display = 'none';
            otpForm.style.display = 'block';
        });
    </script>
</body>
</html>