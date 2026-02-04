<?php
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "CoffeeShop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT e.employee_id, CONCAT(e.firstname, ' ', e.lastname) AS name, log.date, log.time_in, log.time_out, s.shift_start, s.shift_end
        FROM employee_time_log log
        JOIN employees e ON log.employee_id = e.employee_id
        JOIN employee_schedule s ON e.employee_id = s.employee_id
        WHERE log.date = CURDATE()
        ORDER BY log.date DESC, e.firstname ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Time Management</title>
    <link rel="stylesheet" href="../style/style-employee-list.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">
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
                <li><a href=""><i class="fas fa-calendar-check"></i>Employee List</a></li>
                <li><a href="admin_management.php"><i class="fas fa-calendar-check"></i>Add Sub Admin</a></li>
                <li><a href="positions.php"><i class="fas fa-calendar-check"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href=""><i class="fas fa-calendar-check"></i>Schedules</a></li> <!--EmManagement/schedule_management.php-->
                <li><a href="employee-schedule.php"><i class="fas fa-calendar-check"></i>Employee Schedule</a></li>
                <li><a href=""><i class="fas fa-calendar-check"></i>Time Management</a></li> <!--EmManagement/time-management.php-->
            </ul>

                <p>Payroll Management</p>
                <li><a href="../PayManagement/payroll.php"><i class="fas fa-money-check-alt"></i>Payroll</a></li>
                <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
                <li><a href="../PayManagement/taxDeduction.php"><i class="fas fa-money-check-alt"></i>tax Deductions</a></li>
                <li><a href=""><i class="fas fa-money-check-alt"></i>Salary Forcasting</a></li> <!--PayManagement/SalPForcast.php-->
        </ul>
    </div>


    <div class="content">
        <div class="contentBody">
            <div class="top-bar">
                <h1>Employee Time Management</h1>
            </div>

        <h2>Current Employees Time Shifts</h2>
        <table border="1">
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    if ($row['time_in']) {
                        $time_in = strtotime($row['time_in']);
                        $shift_start = strtotime($row['shift_start']);

                        if ($time_in < $shift_start) {
                            $status = 'Early';
                        } elseif ($time_in == $shift_start) {
                            $status = 'On Time';
                        } else {
                            $status = 'Late';
                        }
                    } else {
                        $status = 'Not Logged';
                    }
                ?>
                    <tr>
                        <td><?php echo $row['employee_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'Not Logged'; ?></td>
                        <td><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'Not Logged'; ?></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No time logs available for today.</td>
                </tr>
            <?php endif; ?>
        </table>
        <?php $conn->close(); ?>
        </div>
    </div>
</body>

</html>