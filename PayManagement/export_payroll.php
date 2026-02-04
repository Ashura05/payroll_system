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

// Fetch payroll data
$selected_period = isset($_POST['payrollPeriod']) ? $_POST['payrollPeriod'] : date('Y-m-01') . ' to ' . date('Y-m-15');
list($start_date, $end_date) = explode(' to ', $selected_period);

$sql = "
    SELECT 
        e.employee_id, 
        CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
        p.position_title,
        p.rate_per_hour,
        SUM(TIMESTAMPDIFF(HOUR, etl.time_in, etl.time_out)) AS total_hours,
        SUM(`or`.total_overtime_pay) AS total_overtime_pay
    FROM employees e
    LEFT JOIN positions p ON e.position = p.position_id
    LEFT JOIN employee_time_log etl ON e.employee_id = etl.employee_id AND etl.date BETWEEN '$start_date' AND '$end_date'
    LEFT JOIN overtime_records `or` ON e.employee_id = `or`.employee_id AND `or`.date_issued BETWEEN '$start_date' AND '$end_date'
    GROUP BY e.employee_id
";


$result = $conn->query($sql);

// Create the Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=payroll_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output the headers
echo "Employee ID\tEmployee Name\tPosition\tRate Per Hour\tTotal Hours\tOvertime Pay\tGross Pay\n";

// Output the data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $gross_pay = ($row['rate_per_hour'] * $row['total_hours']) + $row['total_overtime_pay'];
        echo "{$row['employee_id']}\t{$row['employee_name']}\t{$row['position_title']}\t{$row['rate_per_hour']}\t{$row['total_hours']}\t{$row['total_overtime_pay']}\t{$gross_pay}\n";
    }
} else {
    echo "No data available.";
}

$conn->close();
?>
