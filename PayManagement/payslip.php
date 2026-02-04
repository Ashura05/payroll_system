<?php
session_start();

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get `employee_id` and `payroll_period` from the request
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : null;
$payroll_period = isset($_GET['payroll_period']) ? $_GET['payroll_period'] : date('Y-m-01') . ' to ' . date('Y-m-15');

if (!$employee_id) {
    die("Employee ID is required.");
}

// Split payroll period into start and end dates
list($start_date, $end_date) = explode(' to ', $payroll_period);

// Fetch employee details
$sql = "
    SELECT 
        e.employee_id, 
        e.firstname, 
        e.lastname, 
        p.position_title, 
        p.rate_per_hour
    FROM employees e
    LEFT JOIN positions p ON e.position = p.position_id
    WHERE e.employee_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    die("Employee not found.");
}

// Calculate total hours worked
$attendance_sql = "
    SELECT 
        SUM(TIMESTAMPDIFF(HOUR, time_in, time_out)) AS total_hours
    FROM employee_time_log
    WHERE employee_id = ? 
    AND date BETWEEN ? AND ?
";
$stmt_attendance = $conn->prepare($attendance_sql);
$stmt_attendance->bind_param("iss", $employee_id, $start_date, $end_date);
$stmt_attendance->execute();
$attendance_result = $stmt_attendance->get_result();
$attendance_data = $attendance_result->fetch_assoc();
$total_hours = $attendance_data['total_hours'] ?: 0;

// Fetch overtime pay
$overtime_sql = "
    SELECT SUM(total_overtime_pay) AS total_overtime_pay
    FROM overtime_records
    WHERE employee_id = ? 
    AND date_issued BETWEEN ? AND ?
";
$stmt_overtime = $conn->prepare($overtime_sql);
$stmt_overtime->bind_param("iss", $employee_id, $start_date, $end_date);
$stmt_overtime->execute();
$overtime_result = $stmt_overtime->get_result();
$overtime_data = $overtime_result->fetch_assoc();
$total_overtime_pay = $overtime_data['total_overtime_pay'] ?: 0;

// Calculate gross pay
$gross_pay = ($employee['rate_per_hour'] * $total_hours) + $total_overtime_pay;

// Fetch cash advance
$cash_advance_sql = "
    SELECT SUM(advance_amount) AS cash_advance 
    FROM cash_advances 
    WHERE employee_id = ? 
    AND MONTH(date_issued) = MONTH(?) 
    AND YEAR(date_issued) = YEAR(?)
";
$stmt_advance = $conn->prepare($cash_advance_sql);
$stmt_advance->bind_param("iss", $employee_id, $start_date, $start_date);
$stmt_advance->execute();
$advance_result = $stmt_advance->get_result();
$advance_row = $advance_result->fetch_assoc();
$cash_advance = $advance_row['cash_advance'] ?: 0;

// Calculate deductions
$sss_employee = round($gross_pay * 0.045, 2);
$philhealth_employee = round($gross_pay * 0.02, 2);
$pagibig_employee = 50;
$total_deductions = $sss_employee + $philhealth_employee + $pagibig_employee + $cash_advance;

// Calculate net pay
$net_pay = $gross_pay - $total_deductions;

$conn->close();

// Define selected period for display
$selected_period = $payroll_period ? $payroll_period : 'No period selected';



?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Payslip</title>
    <link rel="stylesheet" href="../style/style-payslip.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">

    <script>
        function printPayslip() {
            // Create a new window or use the existing one
            var printWindow = window.open('', '', 'height=800,width=800');

            // Get the HTML content for the payslip
            var payslipContent = document.querySelector('.content').innerHTML;

            // Write the content to the print window
            printWindow.document.write('<html><head><title>Payslip</title>');

            // Include CSS for printing
            printWindow.document.write('<style>' + document.querySelector('style').innerHTML + '</style>');
            
            printWindow.document.write('</head><body>');
            
            // Write the content into the body
            printWindow.document.write('<div class="content">' + payslipContent + '</div>');
            
            // Close the document and print
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
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
                <h1>Payslip for <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></h1>
                <h4>Position: <?php echo htmlspecialchars($employee['position_title']); ?></h4> 
            </div>

            <div class="calendar-bar">
                <form method="GET" action="payslip.php">
                    <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                    <select id="payrollPeriod" name="payroll_period">
                        <?php
                        $current_year = date('Y');
                        $current_month = date('m');
                        for ($i = 0; $i < 12; $i++) {
                            $month = date('m', strtotime("+$i month", strtotime("$current_year-$current_month-01")));
                            $year = date('Y', strtotime("+$i month", strtotime("$current_year-$current_month-01")));
                            $end_day = date('t', strtotime("$year-$month-01"));
                            $mid_period = "$year-$month-01 to $year-$month-15";
                            $end_period = "$year-$month-16 to $year-$month-$end_day";

                            $selected = ($payroll_period === $mid_period || $payroll_period === $end_period) ? 'selected' : '';
                            echo "<option value='$mid_period' $selected>$mid_period</option>";
                            echo "<option value='$end_period' $selected>$end_period</option>";
                        }
                        ?>
                    </select>
                    <button type="submit">Update Payslip</button>
                </form>
            </div>

            <a href="payroll.php">
                <button type="button" class="backpayroll">Back to Payroll</button>
            </a>

            <div class="payroll-data">

                <table>
                    <thead>
                        <tr>
                            <th>Week:</th>
                            <th><?php echo $selected_period; ?></th>
                        </tr>
                    </thead>
                    </table>

                <table>
                    <thead>
                        <tr>
                            <th>Total Hours</th>
                            <th>Gross Pay</th>
                            <th>Overtime</th>
                            <th>Cash Advance</th>
                            <th>SSS Deduction</th>
                            <th>PhilHealth Deduction</th>
                            <th>Pag-IBIG Deduction</th>
                            <th>Total Salary Deduction</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo number_format($total_hours, 2); ?></td>
                            <td><?php echo number_format($gross_pay, 2); ?></td>
                            <td><?php echo number_format($total_overtime_pay, 2); ?></td>
                            <td><?php echo number_format($cash_advance, 2); ?></td>
                            <td><?php echo number_format($sss_employee, 2); ?></td>
                            <td><?php echo number_format($philhealth_employee, 2); ?></td>
                            <td><?php echo number_format($pagibig_employee, 2); ?></td>
                            <td><?php echo number_format($total_deductions, 2); ?></td>
                            <td><?php echo number_format($net_pay, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="print-button">
                    <form action="payslip_printable.php" method="GET" target="_blank">
                        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                        <input type="hidden" name="payroll_period" value="<?php echo htmlspecialchars($payroll_period); ?>">
                        <button type="submit">Print Payslip</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
