<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $schedule_id = $_POST['schedule_id'];

    $scheduleSql = "SELECT time_in, time_out FROM schedules WHERE schedule_id = ?";
    $stmt_schedule = $conn->prepare($scheduleSql);
    $stmt_schedule->bind_param("i", $schedule_id);
    $stmt_schedule->execute();
    $stmt_schedule->bind_result($shift_start, $shift_end);
    $stmt_schedule->fetch();
    $stmt_schedule->close();

    $checkSql = "SELECT * FROM employee_schedule WHERE employee_id = ? AND schedule_id = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $employee_id, $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['success_message'] = "This schedule is already assigned to the employee.";
    } else {
        $checkExistingSql = "SELECT * FROM employee_schedule WHERE employee_id = ?";
        $stmt = $conn->prepare($checkExistingSql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $existingResult = $stmt->get_result();

        if ($existingResult->num_rows > 0) {
            $updateSql = "UPDATE employee_schedule 
                          SET schedule_id = ?, shift_start = ?, shift_end = ? 
                          WHERE employee_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("issi", $schedule_id, $shift_start, $shift_end, $employee_id);
            if ($updateStmt->execute()) {
                $_SESSION['success_message'] = "Schedule updated successfully!";
            } else {
                $_SESSION['success_message'] = "Error updating schedule.";
            }
        } else {
            $insertSql = "INSERT INTO employee_schedule (employee_id, schedule_id, shift_start, shift_end) 
                          VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iiss", $employee_id, $schedule_id, $shift_start, $shift_end);
            if ($insertStmt->execute()) {
                $_SESSION['success_message'] = "Schedule assigned successfully!";
            } else {
                $_SESSION['success_message'] = "Error assigning schedule.";
            }
        }
    }
}

$sql = "
    SELECT 
        e.employee_id, 
        CONCAT(e.firstname, ' ', e.lastname) AS name, 
        es.shift_start, 
        es.shift_end, 
        es.schedule_id 
    FROM 
        employees e
    LEFT JOIN 
        employee_schedule es ON e.employee_id = es.employee_id
";
$result = $conn->query($sql);

$scheduleSql = "SELECT schedule_id, time_in, time_out FROM schedules";
$scheduleResult = $conn->query($scheduleSql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Employee Schedule</title>
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-employee-schedule.css">
    <link rel="stylesheet" href="../style/style-logout.css">
</head>

<body>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
        <title>Employee Schedule</title>

        <link rel="stylesheet" href="../style/style-sidebar.css">
        <link rel="stylesheet" href="../style/style-employee-schedule.css">
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
            <li><a href="attendance.php"><i class="fa fa-laptop" aria-hidden="true"></i>Attendance</a></li>
                <li><a href="employee-list.php"><i class="fa fa-users" aria-hidden="true"></i></i>Employee List</a></li>
                <li><a href="inactive-employees.php"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
                <li><a href="positions.php"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href="schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                    <a href="employee-schedule.php" style=" color: black; transition: color 0.3s ease;"><i class="fa fa-address-book" aria-hidden="true"></i>Employee Schedule</a></li>
            </ul>

            <p>Payroll Management</p>
            <li><a href="../PayManagement/payroll.php"><i class="fa fa-envelope" aria-hidden="true"></i>Payroll</a></li>
            <li><a href="../PayManagement/cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li><a href="../PayManagement/overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
            <li><a href="../PayManagement/taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>


            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

        </ul>
    </div>

        <div class="content">
            <div class="contentBody">
                <div class="top-bar">
                    <h1>Employee Schedule</h1>
                    <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
                </div>
                <h2>Current Employee Schedules</h2>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div id="success-message" class="floating-message">
                        <?= htmlspecialchars($_SESSION['success_message']); ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Schedule</th>
                            <th>Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $schedule = isset($row['shift_start']) && isset($row['shift_end']) ?
                                    date('h:i A', strtotime($row['shift_start'])) . " - " . date('h:i A', strtotime($row['shift_end'])) :
                                    'No Schedule';
                                echo "<tr>";
                                echo "<td>" . $row['employee_id'] . "</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $schedule . "</td>";
                                echo "<td class='actions'>
                                <button onclick=\"editSchedule({$row['employee_id']}, {$row['schedule_id']})\" class='edit-button'>Update</button>
                              </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No employees found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="overlay" id="overlay"></div>
        <div id="schedule-form">
            <form method="POST" action="employee-schedule.php">
                <input type="hidden" name="employee_id" id="employee_id">
                <label for="schedule_id">Select Schedule:</label>
                <select name="schedule_id" id="schedule_id" required>
                    <option value="">--Select a Schedule--</option>
                    <?php
                    if ($scheduleResult->num_rows > 0) {
                        while ($scheduleRow = $scheduleResult->fetch_assoc()) {
                            $timeRange = date('h:i A', strtotime($scheduleRow['time_in'])) . " - " . date('h:i A', strtotime($scheduleRow['time_out']));
                            echo "<option value='" . $scheduleRow['schedule_id'] . "'>" . $timeRange . "</option>";
                        }
                    }
                    ?>
                </select>
                <button type="submit" name="add" id="add-button">Assign Schedule</button>
                <button type="submit" name="update" id="update-button" style="display: none;">Update Schedule</button>
                <button type="button" onclick="closeForm()">Cancel</button>
            </form>
        </div>

        <script>
            function editSchedule(id, scheduleId) {
                // Set the employee_id and schedule_id for updating
                document.getElementById('employee_id').value = id;
                document.getElementById('schedule_id').value = scheduleId;

                // Make the 'Update' button visible
                document.getElementById('add-button').style.display = 'none';
                document.getElementById('update-button').style.display = 'inline-block';

                // Display the form for editing
                document.getElementById('schedule-form').style.display = 'block';
                document.getElementById('overlay').style.display = 'block';
            }

            function closeForm() {
                document.getElementById('schedule-form').style.display = 'none';
                document.getElementById('overlay').style.display = 'none';
            }
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const successMessage = document.getElementById("success-message");
                if (successMessage) {
                    successMessage.style.display = "block"; // Show the message
                    setTimeout(() => {
                        successMessage.style.display = "none"; // Hide after 5 seconds
                    }, 5000);
                }
            });
        </script>
    </body>

    </html>

    <?php
    $conn->close();
    ?>