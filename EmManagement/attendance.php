<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Ensure the database connection file defines the $conn variable


// Check if the user is logged in as an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get the selected date or default to today's date
$selected_date = isset($_GET['date']) && strtotime($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$current_time = time();
$is_day_end = $current_time >= strtotime("$selected_date 23:00:00");  // Check if current time is after 11:00 PM

// Initialize an empty array for attendance history
$attendance_history = [];

// Modify the SQL query to select all employees regardless of time_in/time_out for the selected date
$query = "
    SELECT 
        e.employee_id AS emp_id,
        e.firstname,
        e.lastname,
        e.status,  -- This will show 'inactive' for inactive employees
        s.shift_start,
        s.shift_end,
        t.date,
        t.time_in,
        t.time_out
    FROM 
        employees e
    LEFT JOIN 
        employee_schedule s ON e.employee_id = s.employee_id
    LEFT JOIN 
        employee_time_log t ON t.employee_id = e.employee_id 
        AND t.date = ?
    ORDER BY 
        t.time_in IS NULL ASC,  -- Ensures employees without attendance appear last
        t.time_in ASC; 
";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param('s', $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $shiftStart = strtotime($row['shift_start']);
        $shiftEnd = strtotime($row['shift_end']);
        $timeIn = !empty($row['time_in']) ? strtotime($row['time_in']) : null;
        $gracePeriod = 15 * 60; // 15 minutes in seconds
        $shiftStartWithGrace = $shiftStart + $gracePeriod;

        // Default status is "absent"
        $status = 'absent';

        // If the employee is not inactive, check the attendance status
        if ($row['status'] !== 'inactive') {
            if (!empty($timeIn)) {
                // Check if time_in is early, on time, or late based on shift start time
                if ($timeIn < $shiftStart) {
                    $status = 'early';
                } elseif ($timeIn <= $shiftStartWithGrace) {
                    $status = 'on_time';
                } elseif ($timeIn > $shiftStartWithGrace && $timeIn <= $shiftEnd) {
                    $status = 'late';
                } else {
                    $status = 'absent';  // Mark as absent if time_in is after shift end
                }
            }
        }

        // Set employee ID and name to "N/A" if the employee is inactive, else show their info
        $employeeID = $row['status'] === 'inactive' ? 'N/A' : $row['emp_id'];
        $employeeName = $row['status'] === 'inactive' ? 'N/A' : trim($row['firstname'] . ' ' . $row['lastname']);

        // Add the attendance record for display
        if ($is_day_end || !empty($timeIn)) {
            // If it's after 11:00 PM or if the employee has time_in, show the record
            $attendance_history[] = [
                'employee_id' => $employeeID,
                'name' => $employeeName,
                'date' => $selected_date,
                'time_in' => !empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : 'N/A',
                'time_out' => !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : 'N/A',
                'status' => $status,
                'shift_start' => !empty($row['shift_start']) ? date('h:i A', strtotime($row['shift_start'])) : 'N/A',
                'shift_end' => !empty($row['shift_end']) ? date('h:i A', strtotime($row['shift_end'])) : 'N/A',
            ];
        }
    }

    $stmt->close();
}

// If it's after 11:00 PM, update absent status for those who haven't clocked in
if ($is_day_end) {
    foreach ($attendance_history as $key => $record) {
        // If no time_in and not inactive, mark as absent
        if ($record['status'] === 'absent' && $record['time_in'] === 'N/A') {
            $attendance_history[$key]['status'] = 'absent';
            $attendance_history[$key]['time_in'] = 'N/A';
            $attendance_history[$key]['time_out'] = 'N/A';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="stylesheet" href="../style/style-attendance.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> <!-- jQuery UI Styles -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> <!-- jQuery UI -->
</head>
<body>
<!-- ================= SIDEBAR ================= -->
<aside class="sidebar" id="sidebar">

    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="../dashboard.php" class="logo-box">
            <img src="../img/letterLogo.png" alt="Company Logo" class="logo-img">
            <span class="logo-text" src="./dashboard.php">LOURD'S Café</span>
        </a>

        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <ul class="sidebar-menu">

        <!-- Reports -->
        <li class="section-title">Reports</li>
        <li>
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span class="link-text">Dashboard</span>
            </a>
        </li>

        <!-- Employee Management -->
        <li class="section-title">Employee Management</li>
        <li>
            <a href="./EmManagement/attendance.php">
                <i class="fa fa-laptop"></i>
                <span class="link-text">Attendance</span>
            </a>
        </li>
        <li class="active">
            <a href="employee-list.php">
                <i class="fa fa-users"></i>
                <span class="link-text">Employee List</span>
            </a>
        </li>
        <li>
            <a href="inactive-employees.php">
                <i class="fa fa-user-times"></i>
                <span class="link-text">Inactive Employees</span>
            </a>
        </li>
        <li>
            <a href="positions.php">
                <i class="fa fa-briefcase"></i>
                <span class="link-text">Positions</span>
            </a>
        </li>

        <!-- Time Management -->
        <li class="section-title">Time Management</li>
        <li>
            <a href="schedule_management.php">
                <i class="fas fa-calendar-check"></i>
                <span class="link-text">Schedules</span>
            </a>
        </li>
        <li>
            <a href="employee-schedule.php">
                <i class="fa fa-address-book"></i>
                <span class="link-text">Employee Schedule</span>
            </a>
        </li>

        <!-- Payroll Management -->
        <li class="section-title">Payroll Management</li>
        <li>
            <a href="../PayManagement/payroll.php">
                <i class="fa fa-envelope"></i>
                <span class="link-text">Payroll</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/cash-advance.php">
                <i class="fas fa-money-check-alt"></i>
                <span class="link-text">Cash Advance</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/overtime.php">
                <i class="fas fa-clock"></i>
                <span class="link-text">Overtime</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/taxDeduction.php">
                <i class="fa fa-minus-square"></i>
                <span class="link-text">Deductions</span>
            </a>
        </li>

        <!-- Logout -->
        <li class="logout">
            <a href="../logout.php" onclick="return confirm('Are you sure you want to log out?');">
                <i class="fas fa-sign-out-alt"></i>
                <span class="link-text">Logout</span>
            </a>
        </li>

    </ul>
</aside>

<div class="content">
    <div class="contentBody">
        <div class="top-bar">
            <h1>Attendance Management</h1>
            <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80"></a>
        </div>
         <h2>Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>

        <form method="get">
            <input type="text" id="date" name="date" value="<?php echo $selected_date; ?>" placeholder="Select date">
            <button class="button" type="submit">Display</button>
        </form>

        <table class="attendance-table">
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
                <?php if (empty($attendance_history)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No attendance records were found for this day.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendance_history as $record): ?>
                        <tr>
                            <td><?php echo date('F d, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo $record['employee_id']; ?></td>
                            <td><?php echo $record['name']; ?></td>
                            <td><?php echo $record['shift_start'] . ' - ' . $record['shift_end']; ?></td>
                            <td><?php echo $record['time_in']; ?></td>
                            <td><?php echo $record['time_out']; ?></td>
                            <td><?php echo $record['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(function() {
        // jQuery UI Datepicker for the date input field
        $("#date").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        });
    });
</script>

<script src="../sidebar_function/collapse_sidebar.js"></script>
</body>
</html>
