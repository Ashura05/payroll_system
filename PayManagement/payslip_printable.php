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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printing Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .payslip-container {
            width: 1000px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            background: #f9f9f9;
            margin-top: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 200px;
        }

        .header h2 {
            margin: 5px 0;
        }

        .details {
            margin-left: 40px;
            margin-right: -200px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .details .section {
            width: 48%;
        }

        .table-container {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f4f4f4;
        }

        .acknowledgment {
            margin-top: 30px;
            font-size: 14px;
            border-top: 2px solid #ccc;
            padding-top: 15px;
            text-align: center;
        }

        .acknowledgment p {
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .acknowledgment .signature-line {
            margin-top: 50px;
        }

        .acknowledgment .signature {
            width: 50%;
            border-top: 1px solid #000;
            margin: 0 auto;
            padding-top: 10px;
            text-align: center;
            font-size: 14px;
            font-style: italic;
            color: #555;
        }

        .print-button {
            text-align: center;
            margin-top: 20px;
        }

        .print-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .print-button button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <div class="header">
            <img src="../img/letterLogo.png" alt="Company Logo">
            <p>Payslip for Payroll Period: <?php echo htmlspecialchars($payroll_period); ?></p>
        </div>
        <div class="details">
            <div class="section">
                <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position_title']); ?></p>
            </div>
            <div class="section">
                <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <p><strong>Date Issued:</strong> <?php echo date('Y-m-d'); ?></p>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Earnings</th>
                        <th>Amount</th>
                        <th>Deductions</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gross Pay</td>
                        <td><?php echo number_format($gross_pay, 2); ?></td>
                        <td>SSS Contribution</td>
                        <td><?php echo number_format($sss_employee, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Overtime</td>
                        <td><?php echo number_format($total_overtime_pay, 2); ?></td>
                        <td>PhilHealth Contribution</td>
                        <td><?php echo number_format($philhealth_employee, 2); ?></td>
                    </tr>
                    <tr>
                        <td>---</td>
                        <td>---</td>
                        <td>Pag-IBIG Contribution</td>
                        <td><?php echo number_format($pagibig_employee, 2); ?></td>
                    </tr>
                    <tr>
                        <td>---</td>
                        <td>---</td>
                        <td>Cash Advance</td>
                        <td><?php echo number_format($cash_advance, 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Net Pay</th>
                        <th><?php echo number_format($net_pay, 2); ?></th>
                        <th>Total Deductions</th>
                        <th><?php echo number_format($total_deductions, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="acknowledgment">
            <p>
                I hereby acknowledge receipt of my payslip for the payroll period <strong><?php echo htmlspecialchars($payroll_period); ?></strong>. 
                I confirm that the details provided are accurate to the best of my knowledge.
            </p>
            <div class="signature-line">
                <div class="signature">
                    Signature over Printed Name
                </div>
            </div>
        </div>
    </div>
    <div class="print-button">
            <button onclick="window.print()">Print Payslip</button>
        </div>
</body>
</html>
