<?php
session_start();

// If the user is already logged in as an admin, redirect them to the admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'];

    $conn = new mysqli("localhost", "root", "", "CoffeeShop");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (!empty($username)) {
        // Admin login flow
        $sql = "SELECT id, password_hash, is_main_admin FROM admin_accounts WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($admin_id, $password_hash, $is_main_admin);
        $stmt->fetch();

        if ($password_hash && password_verify($password, $password_hash)) {
            // Set session variables
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_id'] = $admin_id;  // Store the admin's ID
            $_SESSION['is_main_admin'] = $is_main_admin;

            // Redirect to the admin dashboard
            $stmt->close();
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Invalid admin credentials.";
            $stmt->close();
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Login</title>
</head>

<body>

    <div class="left-side">
        <img src="img/lourds_cafe.jpg" alt="Company Logo" class="logo-left">
    </div>

    <div class="loginForm">
        <h2>Login Admin Account</h2>
        <div class="contentBody">
            <form action="index.php" method="POST">
                <label for="username">Username (Admin):</label>
                <input type="text" id="username" name="username" placeholder="Admin Username" required /><br><br>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
                <input type="submit" value="Login" />
                <p class="forgot-password"><a href="forgot_password.php">Forgot Password?</a></p>
            </form>
        </div>
    </div>

</body>

</html>