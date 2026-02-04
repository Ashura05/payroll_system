<?php
session_start();
$conn = new mysqli("localhost", "root", "", "CoffeeShop");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is redirected after OTP verification
if (!isset($_SESSION['otp']) || !isset($_SESSION['reset_admin_id'])) {
    header("Location: forgot_password.php"); // Redirect if not authorized
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

     if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update the password in the database
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $admin_id = $_SESSION['reset_admin_id'];

        $sql = "UPDATE admin_accounts SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $admin_id);

        if ($stmt->execute()) {
            unset($_SESSION['otp'], $_SESSION['reset_admin_id']); // Clear session data
            $success = "Password updated successfully! <a href='index.php'>Login</a>";
        } else {
            $error = "Failed to update the password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php else: ?>
        <form action="reset_password.php" method="POST">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required><br><br>
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</body>
</html>
