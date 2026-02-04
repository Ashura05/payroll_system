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



$deductions = 0;
$selected_period = isset($_POST['payrollPeriod']) ? $_POST['payrollPeriod'] : date('Y-m-01') . ' to ' . date('Y-m-15');

// Split selected period into start and end dates
list($start_date, $end_date) = explode(' to ', $selected_period);

$start_month = date('m', strtotime($start_date));
$start_year = date('Y', strtotime($start_date));

$month_name = date('F', strtotime($start_date));

$sql = "
    SELECT 
        e.employee_id, 
        e.firstname, 
        e.lastname, 
        p.position_title, 
        p.rate_per_hour
    FROM employees e
    LEFT JOIN positions p ON e.position = p.position_id
";
$result = $conn->query($sql);
$employee_data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employee_id = $row['employee_id'];
        $rate_per_hour = $row['rate_per_hour'];

        // Calculate total hours worked for the pay period
        $attendance_sql = "
            SELECT 
                SUM(TIMESTAMPDIFF(HOUR, time_in, time_out)) AS total_hours
            FROM employee_time_log
            WHERE employee_id = ? 
            AND date BETWEEN ? AND ?
        ";
        $stmt = $conn->prepare($attendance_sql);
        $stmt->bind_param("iss", $employee_id, $start_date, $end_date);
        $stmt->execute();
        $attendance_result = $stmt->get_result();
        $attendance_data = $attendance_result->fetch_assoc();
        $total_hours = $attendance_data['total_hours'] ?: 0;

        // Fetch overtime pay
        $overtime_sql = "
            SELECT SUM(total_overtime_pay) AS total_overtime_pay
            FROM overtime_records
            WHERE employee_id = ? 
            AND date_issued BETWEEN ? AND ?
        ";
        $stmt = $conn->prepare($overtime_sql);
        $stmt->bind_param("iss", $employee_id, $start_date, $end_date);
        $stmt->execute();
        $overtime_result = $stmt->get_result();
        $overtime_data = $overtime_result->fetch_assoc();
        $total_overtime_pay = $overtime_data['total_overtime_pay'] ?: 0;

        // Calculate gross pay including overtime
        $gross_pay = ($rate_per_hour * $total_hours) + $total_overtime_pay;

        // Fetch cash advance for the selected month
            $cash_advance_sql = "
            SELECT SUM(advance_amount) AS cash_advance 
            FROM cash_advances 
            WHERE employee_id = ? 
            AND MONTH(date_issued) = ? 
            AND YEAR(date_issued) = ?
            ";
            $stmt = $conn->prepare($cash_advance_sql);
            $stmt->bind_param("iii", $employee_id, $start_month, $start_year); // Use the start month and year
            $stmt->execute();
            $advance_result = $stmt->get_result();
            $advance_row = $advance_result->fetch_assoc();
            $cash_advance = $advance_row['cash_advance'] ?: 0; // Default to 0 if no cash advance is found


        // Calculate the mandatory government deductions based on gross pay
       // SSS (employee contribution 4.5%)
            $sss_employee = round($gross_pay * 0.045, 2); // Rounded to two decimal places

            // PhilHealth (employee contribution 2%)
            $philhealth_employee = round($gross_pay * 0.02, 2); // Rounded to two decimal places

            // Pag-IBIG (fixed at PHP 50)
            $pagibig_employee = 50; // Fixed amount, no percentage needed


        // Total employee deductions
        $total_employee_deductions = $sss_employee + $philhealth_employee + $pagibig_employee + $cash_advance + $deductions;

        // Calculate net pay after deductions
        $net_pay = $gross_pay - $total_employee_deductions;

        // Store employee data
        $employee_data[] = [
            'employee_id' => $employee_id,
            'name' => $row['firstname'] . ' ' . $row['lastname'],
            'position' => $row['position_title'],
            'overtime' => $total_overtime_pay,
            'gross_pay' => $gross_pay,
            'cash_advance' => $cash_advance,
            'sss' => $sss_employee,
            'philhealth' => $philhealth_employee,
            'pagibig' => $pagibig_employee,
            'other_deductions' => $deductions,
            'net_pay' => $net_pay
        ];
    }
}

$conn->close();
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Payroll</title>
    <link rel="stylesheet" href="../style/style-payroll.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">
</head>
<body>
    <div class="sidebar">
        <a href="../dashboard.php"><img alt="Company Logo" height="80" src="../img/letterLogo.png" width="80" /></a>
        <ul>
            <p>Reports</p>
            <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <p>Employee Management</p>
            <ul>
                <li><a href="../EmManagement/attendance.php"><i class="fa fa-laptop" aria-hidden="true"></i>Attendance</a></li>
                <li><a href="../EmManagement/employee-list.php"><i class="fa fa-users" aria-hidden="true"></i>Employee List</a></li>
                <li><a href="../EmManagement/inactive-employees.php"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
                <li><a href="../EmManagement/positions.php"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
            </ul>
            <p>Time Management</p>
            <ul>
                <li><a href="../EmManagement/schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="../EmManagement/employee-schedule.php"><i class="fa fa-address-book" aria-hidden="true"></i>Employee Schedule</a></li>
            </ul>
            <p>Payroll Management</p>
            <li style="background-color: #f3feff; border-radius: 7px 0px 0px 7px;">
                <a href="payroll.php" style=" color: black;"><i class="fa fa-envelope" aria-hidden="true"></i>Payroll</a>
            </li>
            <li><a href="cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
            <li><a href="taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>
            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>


<div class="content">
    <div class="contentBody">
        <div class="top-bar">
            <h1>Payroll</h1>
        </div>

        <div class="calendar-bar">
            <form method="POST" action="payroll.php">
                <select id="payrollPeriod" name="payrollPeriod">
                    <?php
                    $current_year = date('Y');
                    $current_month = date('m');
                    for ($i = 0; $i < 12; $i++) {
                        $month = date('m', strtotime("+$i month", strtotime("$current_year-$current_month-01")));
                        $year = date('Y', strtotime("+$i month", strtotime("$current_year-$current_month-01")));
                        $end_day = date('t', strtotime("$year-$month-01"));
                        $mid_period = "$year-$month-01 to $year-$month-15";
                        $end_period = "$year-$month-16 to $year-$month-$end_day";
                        echo "<option value='$mid_period'>$mid_period</option>";
                        echo "<option value='$end_period'>$end_period</option>";
                    }
                    ?>
                </select>
                <button type="submit">Show Payroll</button>
            </form>
        </div>

        <div class="payroll-data">
            <h1>Payroll for the Period: <?php echo $selected_period; ?></h1>
            <?php if (count($employee_data) > 0): ?>

                <div class="print-button">
                    <form method="POST" action="export_payroll.php" class="export">
                        <input type="hidden" name="payrollPeriod" value="<?php echo $selected_period; ?>">
                        <button type="submit">Export to Excel</button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Overtime</th>
                            <th>Gross Pay</th>
                            <th>Cash Advance</th>
                            <th>SSS Deduction</th>
                            <th>PhilHealth Deduction</th>
                            <th>Pag-IBIG Deduction</th>
                            <th>Total Salary Deduction</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employee_data as $employee): ?>
                            <tr>
                                <td><?php echo $employee['employee_id']; ?></td>
                                <td>
                                    <a href="payslip.php?employee_id=<?php echo $employee['employee_id']; ?>">
                                        <?php echo $employee['name']; ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($employee['overtime'], 2); ?></td>
                                <td><?php echo number_format($employee['gross_pay'], 2); ?></td>
                                <td><?php echo number_format($employee['cash_advance'], 2); ?></td>
                                <td><?php echo number_format($employee['sss'], 2); ?></td>
                                <td><?php echo number_format($employee['philhealth'], 2); ?></td>
                                <td><?php echo number_format($employee['pagibig'], 2); ?></td>
                                <td><?php echo number_format($employee['sss'] + $employee['philhealth'] + $employee['pagibig'] + $employee['other_deductions'], 2); ?></td>
                                <td><?php echo number_format($employee['net_pay'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            <?php else: ?>
                <p>No payroll data available for the selected period.</p>
            <?php endif; ?>

           

        </div>
    </div>
</div>
</body>
</html>
