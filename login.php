<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    $password = $_POST['password'];

    if (empty($username) && empty($employee_id)) {
        echo "Please provide either username (for admin) or employee ID.";
        exit();
    }

    // Admin login
    if (!empty($username)) {
        $sql = "SELECT id, password_hash, is_main_admin FROM admin_accounts WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($admin_id, $password_hash, $is_main_admin);
        $stmt->fetch();

        if ($password_hash && password_verify($password, $password_hash)) {
            // After successful login (example)
$_SESSION['role'] = 'admin';
$_SESSION['admin_id'] = $adminId;  // Ensure this is set
$_SESSION['admin_username'] = $username;  // Ensure this is set

            $_SESSION['is_main_admin'] = $is_main_admin;
        
            header("Location: dashboard.php");
            exit();
        }
         {
            echo "Invalid admin credentials.";
        }
    }

    if (!empty($username)) {
        $sql = "SELECT id, password_hash, is_main_admin FROM admin_accounts WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($admin_id, $password_hash, $is_main_admin);
        $stmt->fetch();
    
        if ($password_hash && password_verify($password, $password_hash)) {
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_id'] = $admin_id; // Assign admin ID to session
            $_SESSION['admin_username'] = $username; // Assign username to session
            $_SESSION['is_main_admin'] = $is_main_admin; // If applicable
    
            // Do not forcibly redirect to dashboard here
            header("Location: dashboard.php"); // Or wherever you'd like to redirect
            exit();
        } else {
            echo "Invalid admin credentials.";
        }
    
        $stmt->close();
    }
    
    
    
}

$conn->close();