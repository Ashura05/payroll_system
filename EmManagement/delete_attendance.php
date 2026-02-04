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

if (isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];
    $current_date = date('Y-m-d'); 

    $sql = "DELETE FROM employee_time_log WHERE employee_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $employee_id, $current_date);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Attendance record deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting the attendance record.";
    }

    $stmt->close();
}

header("Location: attendance.php");
exit();

$conn->close();
?>
