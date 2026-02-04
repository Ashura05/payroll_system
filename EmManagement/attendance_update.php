<?php
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "CoffeeShop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$current_date = date('Y-m-d');
$employee_id = $_POST['employee_id'] ?? ''; // Use session or specific input for employee_id

$sql = "SELECT time_in, time_out FROM employee_time_log WHERE employee_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $employee_id, $current_date);
$stmt->execute();
$stmt->bind_result($time_in, $time_out);
$stmt->fetch();
$stmt->close();

echo json_encode([
    "time_in" => $time_in,
    "time_out" => $time_out,
]);

$conn->close();
