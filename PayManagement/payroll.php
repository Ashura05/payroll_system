<?php
session_start();
// 1. Database Connection
$servername = "localhost"; $db_username = "root"; $db_password = ""; $dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 2. Period Selection
$selected_period = isset($_POST['payrollPeriod']) ? $_POST['payrollPeriod'] : date('Y-m-01') . ' to ' . date('Y-m-15');
list($start_date, $end_date) = explode(' to ', $selected_period);
$start_month = date('m', strtotime($start_date));
$start_year = date('Y', strtotime($start_date));

// 3. Calculation Loop
$sql = "SELECT e.employee_id, e.firstname, e.lastname, p.position_title, p.rate_per_hour 
        FROM employees e LEFT JOIN positions p ON e.position = p.position_id";
$result = $conn->query($sql);
$employee_data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $eid = $row['employee_id'];
        
        // Fetch Regular Rendered Hours
        $stmt = $conn->prepare("SELECT SUM(TIMESTAMPDIFF(HOUR, time_in, time_out)) as hrs FROM employee_time_log WHERE employee_id=? AND date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $eid, $start_date, $end_date);
        $stmt->execute();
        $total_hours = $stmt->get_result()->fetch_assoc()['hrs'] ?: 0;

        // Fetch Overtime Hours and Pay
        $stmt = $conn->prepare("SELECT SUM(hours_worked + (minutes_worked/60)) as ot_hrs, SUM(total_overtime_pay) as ot_pay FROM overtime_records WHERE employee_id=? AND date_issued BETWEEN ? AND ?");
        $stmt->bind_param("iss", $eid, $start_date, $end_date);
        $stmt->execute();
        $ot_res = $stmt->get_result()->fetch_assoc();
        $ot_hours = $ot_res['ot_hrs'] ?: 0;
        $ot_pay = $ot_res['ot_pay'] ?: 0;

        // Fetch Cash Advance
        $stmt = $conn->prepare("SELECT SUM(advance_amount) as adv FROM cash_advances WHERE employee_id=? AND MONTH(date_issued)=? AND YEAR(date_issued)=?");
        $stmt->bind_param("iii", $eid, $start_month, $start_year);
        $stmt->execute();
        $advance = $stmt->get_result()->fetch_assoc()['adv'] ?: 0;

        // Calculations
        $gross = ($row['rate_per_hour'] * $total_hours) + $ot_pay;
        $sss = round($gross * 0.045, 2);
        $ph = round($gross * 0.02, 2);
        $pi = 50;
        $total_deductions = $sss + $ph + $pi + $advance;
        $net = $gross - $total_deductions;

        $employee_data[] = [
            'employee_id' => $eid,
            'name' => $row['firstname'] . ' ' . $row['lastname'],
            'total_hours' => $total_hours,
            'ot_hours' => $ot_hours,
            'gross_pay' => $gross,
            'deduction' => $total_deductions,
            'net_pay' => $net,
            'sss' => $sss, 'philhealth' => $ph, 'pagibig' => $pi, 'cash_advance' => $advance
        ];
    }
}

// 4. Save Logic (For Dashboard sync)
if (isset($_POST['save_payroll_btn'])) {
    foreach ($employee_data as $emp) {
        $stmt = $conn->prepare("INSERT INTO payroll (employee_id, gross, deduction, payroll_date) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE gross = VALUES(gross), deduction = VALUES(deduction)");
        $stmt->bind_param("idds", $emp['employee_id'], $emp['gross_pay'], $emp['deduction'], $end_date);
        $stmt->execute();
    }
    echo "<script>alert('Payroll Registered Successfully!'); window.location.href='payroll.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Payroll Management</title>
    <link rel="stylesheet" href="../style/style-payroll.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">
    <style>
        .action-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .save-btn { background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .export-btn { background-color: #17a2b8; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="../dashboard.php"><img alt="Logo" height="80" src="../img/letterLogo.png" width="80" /></a>
        <ul>
            <p>Reports</p>
            <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <p>Employee Management</p>
            <ul>
                <li><a href="../EmManagement/attendance.php"><i class="fa fa-laptop"></i>Attendance</a></li>
                <li><a href="../EmManagement/employee-list.php"><i class="fa fa-users"></i>Employee List</a></li>
                <li><a href="../EmManagement/inactive-employees.php"><i class="fa fa-user-times"></i>Inactive Employees</a></li>
                <li><a href="../EmManagement/positions.php"><i class="fa fa-briefcase"></i>Positions</a></li>
            </ul>
            <p>Time Management</p>
            <ul>
                <li><a href="../EmManagement/schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="../EmManagement/employee-schedule.php"><i class="fa fa-address-book"></i>Employee Schedule</a></li>
            </ul>
            <p>Payroll Management</p>
            <li style="background-color: #f3feff; border-radius: 7px 0px 0px 7px;">
                <a href="payroll.php" style="color: black;"><i class="fa fa-envelope"></i>Payroll</a>
            </li>
            <li><a href="cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li><a href="overtime.php"><i class="fas fa-clock"></i>Overtime</a></li>
            <li><a href="taxDeduction.php"><i class="fa fa-minus-square"></i>Deductions</a></li><br>
            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="contentBody">
            <div class="top-bar"><h1>Payroll Management</h1></div>

            <div class="calendar-bar">
                <form method="POST">
                    <select id="payrollPeriod" name="payrollPeriod">
                        <?php
                        $current_year = date('Y');
                        for ($m = 1; $m <= 12; $m++) {
                            $month = str_pad($m, 2, "0", STR_PAD_LEFT);
                            $end_day = date('t', strtotime("$current_year-$month-01"));
                            $p1 = "$current_year-$month-01 to $current_year-$month-15";
                            $p2 = "$current_year-$month-16 to $current_year-$month-$end_day";
                            echo "<option value='$p1' ".($selected_period==$p1?'selected':'').">$p1</option>";
                            echo "<option value='$p2' ".($selected_period==$p2?'selected':'').">$p2</option>";
                        }
                        ?>
                    </select>
                    <button type="submit">Show Payroll</button>
                </form>
            </div>

            <div class="payroll-data">
                <h1 style="margin-top: 50px;">Period: <?= $selected_period; ?></h1>
                
                <?php if (!empty($employee_data)): ?>
                <div class="action-container">
                    <form method="POST" action="export_payroll.php">
                        <input type="hidden" name="payrollPeriod" value="<?= $selected_period; ?>">
                        <button type="submit" class="export-btn"><i class="fas fa-file-excel"></i> Export to Excel</button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="payrollPeriod" value="<?= $selected_period; ?>">
                        <button type="submit" name="save_payroll_btn" class="save-btn"><i class="fas fa-save"></i> Save & Register Payroll</button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Reg. Hours</th>
                            <th>OT Hours</th>
                            <th>Gross Pay</th>
                            <th>Total Ded.</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employee_data as $emp): ?>
                            <tr>
                                <td><?= $emp['employee_id'] ?></td>
                                <td><a href="payslip.php?employee_id=<?= $emp['employee_id'] ?>"><?= $emp['name'] ?></a></td>
                                <td><?= number_format($emp['total_hours'], 1) ?> hrs</td>
                                <td><?= number_format($emp['ot_hours'], 1) ?> hrs</td>
                                <td>₱<?= number_format($emp['gross_pay'], 2) ?></td>
                                <td>₱<?= number_format($emp['deduction'], 2) ?></td>
                                <td><strong>₱<?= number_format($emp['net_pay'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No records found for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>