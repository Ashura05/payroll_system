<?php
session_start();

// Ensure employee is logged in
if ($_SESSION['role'] != 'employee') {
    header("Location: ../index.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$action = $_GET['action'] ?? '';

// Connect to the database
$conn = new mysqli("localhost", "root", "", "CoffeeShop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch employee's schedule
$sql = "SELECT shift_start, shift_end FROM employee_schedule WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($shift_start, $shift_end);
$stmt->fetch();
$stmt->close();

$current_time = date('H:i:s');
$current_date = date('Y-m-d');

// Convert shift times to timestamps
$shift_start_time = strtotime($shift_start);
$shift_end_time = strtotime($shift_end);
$current_time_stamp = strtotime($current_time);

// Calculate grace period for time-in
$grace_period = 15 * 60; // 15 minutes in seconds
$shift_start_with_grace = $shift_start_time + $grace_period;

// Check if employee has already timed in today
$sql_check_time_in = "SELECT time_in FROM employee_time_log WHERE employee_id = ? AND date = ?";
$stmt_check_time_in = $conn->prepare($sql_check_time_in);
$stmt_check_time_in->bind_param("is", $employee_id, $current_date);
$stmt_check_time_in->execute();
$stmt_check_time_in->bind_result($time_in_recorded);
$stmt_check_time_in->fetch();
$stmt_check_time_in->close();

// Mark as absent if past shift start time and no time-in record exists
if (!$time_in_recorded && $current_time_stamp > $shift_start_time) {
    $attendance_status = 'absent';
    $sql_absent = "INSERT INTO employee_time_log (employee_id, date, status) VALUES (?, ?, ?)";
    $stmt_absent = $conn->prepare($sql_absent);
    $stmt_absent->bind_param("iss", $employee_id, $current_date, $attendance_status);
    $stmt_absent->execute();
    $stmt_absent->close();
}

// Handle time-in action
if ($action == 'time_in' && !$time_in_recorded) {
    // Determine if early, on-time, or late for time-in
    $attendance_status = 'on_time';
    if ($current_time_stamp < $shift_start_time) {
        $attendance_status = 'early';
    } elseif ($current_time_stamp > $shift_start_with_grace) {
        $attendance_status = 'late';
    }

    // Log the time-in with status
    $sql_time_in = "INSERT INTO employee_time_log (employee_id, time_in, date, status) VALUES (?, ?, ?, ?)";
    $stmt_time_in = $conn->prepare($sql_time_in);
    $stmt_time_in->bind_param("isss", $employee_id, $current_time, $current_date, $attendance_status);
    $stmt_time_in->execute();
    $stmt_time_in->close();

    header("Location: time-management.php?employee_id=$employee_id&date=" . $current_date);
    exit();
} elseif ($action == 'time_out') {
    // Determine if early, on-time, or late for time-out
    $attendance_status = 'on_time';
    if ($current_time_stamp < $shift_end_time) {
        $attendance_status = 'early';
    } elseif ($current_time_stamp > $shift_end_time) {
        $attendance_status = 'late';
    }

    // Update the time-out and status
    $sql_time_out = "UPDATE employee_time_log SET time_out = ?, status = ? WHERE employee_id = ? AND date = ? AND time_out IS NULL";
    $stmt_time_out = $conn->prepare($sql_time_out);
    $stmt_time_out->bind_param("ssis", $current_time, $attendance_status, $employee_id, $current_date);

    if ($stmt_time_out->execute()) {
        echo "Time-out successfully logged!";
    } else {
        echo "Error logging time-out: " . $stmt_time_out->error;
    }
    $stmt_time_out->close();
    header("Location: time-management.php?employee_id=$employee_id&date=" . $current_date);
    exit();
}

$conn->close();
