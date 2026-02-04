<?php
session_start();
date_default_timezone_set('Asia/Manila');

$current_date = date('Y-m-d');
$success_message = '';
$error_message = '';
$employee_details = null;
$existing_time_in = null;
$existing_time_out = null;
$status = null;

$conn = new mysqli("localhost", "root", "", "CoffeeShop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $current_time = date('H:i:s');

    if ($employee_id) {
        // Fetch Employee Information and Schedule
        $sql_employee = "
            SELECT 
                e.firstname, e.lastname, 
                s.shift_start, s.shift_end 
            FROM employees e
            LEFT JOIN employee_schedule s 
            ON e.employee_id = s.employee_id
            WHERE e.employee_id = ?";
        $stmt_employee = $conn->prepare($sql_employee);
        $stmt_employee->bind_param("i", $employee_id);
        $stmt_employee->execute();
        $stmt_employee->bind_result($firstname, $lastname, $shift_start, $shift_end);
        if ($stmt_employee->fetch()) {
            $employee_details = [
                'name' => $firstname . ' ' . $lastname,
                'shift_start' => date('h:i A', strtotime($shift_start)),
                'shift_end' => date('h:i A', strtotime($shift_end)),
            ];
        } else {
            $error_message = "Employee not found.";
        }
        $stmt_employee->close();

        if ($employee_details) {
            if (is_null($shift_start) || is_null($shift_end)) {
                // No schedule assigned
                $error_message = "You have no schedule yet.";
            } else {
                // Fetch Attendance Data
                $sql_check_attendance = "
                    SELECT time_in, time_out, status 
                    FROM employee_time_log 
                    WHERE employee_id = ? AND date = ?";
                $stmt_check = $conn->prepare($sql_check_attendance);
                $stmt_check->bind_param("is", $employee_id, $current_date);
                $stmt_check->execute();
                $stmt_check->bind_result($existing_time_in, $existing_time_out, $status);
                $stmt_check->fetch();

                if ($existing_time_in) {
                    $existing_time_in = date('h:i A', strtotime($existing_time_in));
                }
                if ($existing_time_out) {
                    $existing_time_out = date('h:i A', strtotime($existing_time_out));
                }

                $stmt_check->close();

                // Time In Logic
                if ($action == 'time_in') {
                    if ($existing_time_in) {
                        $error_message = "You have already time-in for today.";
                    } else {
                        $shift_start_time = strtotime($shift_start);
                        $grace_period = 15 * 60; // 15 minutes
                        $shift_start_with_grace = $shift_start_time + $grace_period;
                        $current_time_unix = strtotime($current_time);

                        $status = ($current_time_unix < $shift_start_time) ? 'early' : (($current_time_unix <= $shift_start_with_grace) ? 'on_time' : 'late');

                        $sql_time_in = "INSERT INTO employee_time_log (employee_id, time_in, date, status) VALUES (?, ?, ?, ?)";
                        $stmt_time_in = $conn->prepare($sql_time_in);
                        $stmt_time_in->bind_param("isss", $employee_id, $current_time, $current_date, $status);

                        if ($stmt_time_in->execute()) {
                            $success_message = "Time-in logged successfully.";
                            $existing_time_in = $current_time; // Update time-in locally
                        } else {
                            $error_message = "Error logging time-in: " . $stmt_time_in->error;
                        }
                        $stmt_time_in->close();
                    }
                }

                // Time Out Logic
                if ($action == 'time_out') {
                    if (!$existing_time_in) {
                        $error_message = "You have not time-in yet. Please time-in first.";
                    } elseif ($existing_time_out) {
                        $error_message = "You have already time-out for today.";
                    } else {
                        $sql_time_out = "UPDATE employee_time_log SET time_out = ? WHERE employee_id = ? AND date = ?";
                        $stmt_time_out = $conn->prepare($sql_time_out);
                        $stmt_time_out->bind_param("sis", $current_time, $employee_id, $current_date);

                        if ($stmt_time_out->execute()) {
                            $success_message = "Time-out logged successfully.";
                            $existing_time_out = $current_time; // Update time-out locally
                        } else {
                            $error_message = "Error logging time-out: " . $stmt_time_out->error;
                        }
                        $stmt_time_out->close();
                    }
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style-time-management.css">
    <title>Employee Dashboard</title>
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('current-date-time').innerText = now.toLocaleString('en-US', options);
        }
        setInterval(updateClock, 1000);

        function confirmTimeOut() {
            return confirm("Are you sure you want to time-out?");
        }
    </script>
</head>

<body>
    <div class="dashboard-container">
        <h1>Lourd's Cafe Employee's Time Management</h1>
        <div class="current-time">
            <p id="current-date-time"><?php echo date('F d, Y h:i:s A'); ?></p>
        </div>

        <form id="time-form" action="employee_dashboard.php" method="POST">
            <div class="employee-info">
                <label for="employee_id">Enter Employee ID:</label>
                <input type="text" name="employee_id" required>
            </div>
            <?php if ($success_message): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
            <?php elseif ($error_message): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <div class="action-buttons">
                <br>
                <button type="submit" name="action" value="time_in" class="btn time-in">Time In</button>
                <button type="submit" name="action" value="time_out" class="btn time-out" onclick="return confirmTimeOut()">Time Out</button>
            </div>

        </form>


        <?php if ($employee_details): ?>
            <div class="employee-schedule">
                <h4>Time Management Information</h4>

                    <h3>Employee: <?php echo $employee_details['name']; ?></h3>
                    
                    <div class="schedM">
                        <p>Shift Start: <?php echo $employee_details['shift_start'] == '00:00:00' ? '12:00:00' : $employee_details['shift_start']; ?></p>
                        <p>Shift End: <?php echo $employee_details['shift_end'] == '00:00:00' ? '12:00:00' : $employee_details['shift_end']; ?></p>
                    </div>
        
                    <div class="timeM">
                    <br><br>
                        <p>Time In: <?php echo $existing_time_in ?: 'N/A'; ?></p>
                        <p>Time Out: <?php echo $existing_time_out ?: 'N/A'; ?></p>
                    <br><br>
                        <p>Status: <?php echo $status ?: 'N/A'; ?></p>
                    </div>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>