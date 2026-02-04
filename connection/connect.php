<?php
session_start(); // Start session to manage user login

// Database connection tanungin natin kung ano pwedeng gawin or may ipapagawa na ba sa atin
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your actual DB password
$dbname = "CoffeeShop";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the input values and trim any extra spaces
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Query to check if the user exists
    $sql = "SELECT password_hash FROM admin_accounts WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($passwordHash);
    $stmt->fetch();

    // Check if password is valid
    if ($passwordHash) {
        // Use password_verify to check if the plain text password matches the hash
        if (password_verify($password, $passwordHash)) {
            // Password is correct
            $_SESSION['username'] = $username;
            header("Location: dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            // Invalid password
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        // Username not found
        echo "<script>alert('Username not found.');</script>";
    }

    $stmt->close(); // Close statement
}

$conn->close(); // Close connection
