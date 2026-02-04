<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../db/db_connection.php'; // Ensure this file defines the $conn variable

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get the selected date or default to today's date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Initialize attendance history
$attendance_history = [];

// Fetch Attendance History
$query = "
    SELECT 
        t.id,
        t.employee_id AS log_employee_id,
        t.date,
        t.time_in,
        t.time_out,
        t.status,
        t.permanent_employee_id,
        e.employee_id AS emp_id,
        e.firstname,
        e.lastname,
        s.shift_start,
        s.shift_end
    FROM 
        employee_time_log_history t
    LEFT JOIN 
        employees e ON t.permanent_employee_id = e.employee_id
    LEFT JOIN 
        employee_schedule s ON e.employee_id = s.employee_id
    WHERE 
        t.date = ?
    ORDER BY 
        t.id;
";


if ($stmt = $conn->prepare($query)) {
    // Bind the date parameter
    $stmt->bind_param('s', $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Use the original status if it exists
        $status = $row['status'];

        if (empty($status)) {
            // Calculate the status only if both time_in and shift_start are available
            if (!empty($row['time_in']) && !empty($row['shift_start'])) {
                $timeIn = strtotime($row['time_in']);
                $shiftStart = strtotime($row['shift_start']);

                if ($timeIn <= $shiftStart) {
                    $status = 'on_time';
                } else {
                    $status = 'late';
                }
            } else {
                // If required fields are missing, set status to 'N/A'
                $status = 'N/A';
            }
        }

        // Add the record to the attendance history array
        $attendance_history[] = [
            'id' => $row['id'],
            'employee_id' => $row['emp_id'] ?: 'N/A', // Fetch correct employee ID
            'name' => trim($row['firstname'] . ' ' . $row['lastname']) ?: 'N/A', // Combine first and last name
            'date' => $row['date'] ?: 'N/A',
            'time_in' => $row['time_in'] ?: 'N/A',
            'time_out' => !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : 'N/A',
            'status' => $status,
            'shift_start' => !empty($row['shift_start']) ? date('h:i A', strtotime($row['shift_start'])) : 'N/A',
            'shift_end' => !empty($row['shift_end']) ? date('h:i A', strtotime($row['shift_end'])) : 'N/A',
        ];
    }


    // Close the statement
    $stmt->close();
} else {
    // Log any query errors
    error_log("Query Error: " . $conn->error);
}

// Close the database connection
$conn->close();



?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style-attendance.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Attendance History</title>
</head>

<body>
    <div class="sidebar">
        <a href="../dashboard.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
        <h2>Lourds Cafe</h2>

        <ul>
            <p>Reports</p>
            <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>

            <p>Employee Management</p>
            <ul>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i>Attendance</a></li>
                <li><a href="attendance-history.php"><i class="fas fa-calendar-check"></i>Attendance History</a></li>
                <li><a href="employee-list.php"><i class="fas fa-calendar-check"></i>Employee List</a></li>
                <li><a href="positions.php"><i class="fas fa-calendar-check"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href="schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="employee-schedule.php"><i class="fas fa-calendar-check"></i>Employee Schedule</a></li>
            </ul>

            <p>Payroll Management</p>
            <li><a href="../PayManagement/payroll.php"><i class="fas fa-money-check-alt"></i>Payroll</a></li>
            <li><a href="../PayManagement/cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
            <li><a href="../PayManagement/taxDeduction.php"><i class="fas fa-money-check-alt"></i>Deductions</a></li>
            <li><a href="../PayManagement/SalPForcast.php"><i class="fas fa-money-check-alt"></i>Salary Forecasting</a></li>

            <p>Settings</p>
            <li><a href="logout.php" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-money-check-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="contentBody">
            <div class="top-bar">
                <h1>Attendance History</h1>
                <div class="date-banner">
                    <p>Attendance records were captured on: <?php echo date('F d, Y', strtotime($selected_date)); ?></p>
                </div>
            </div>

            <!-- Date Picker or Navigation for Previous/Next Day -->
            <div class="date-navigation">
                <a href="attendance-history.php?date=<?php echo date('Y-m-d', strtotime('-1 day', strtotime($selected_date))); ?>">Previous Day</a>
                <a href="attendance-history.php?date=<?php echo date('Y-m-d', strtotime('+1 day', strtotime($selected_date))); ?>">Next Day</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Schedule</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_history as $record): ?>
                        <tr>
                            <td><?php echo date('F d, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo $record['employee_id'] !== 'N/A' ? $record['employee_id'] : 'Not Found'; ?></td>
                            <td><?php echo !empty($record['name']) ? $record['name'] : 'Not Found'; ?></td>
                            <td><?php echo $record['shift_start'] . ' - ' . $record['shift_end']; ?></td>
                            <td><?php echo $record['time_in']; ?></td>
                            <td><?php echo $record['time_out']; ?></td>
                            <td><?php echo ucfirst($record['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>
</body>

</html>