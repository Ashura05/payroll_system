<?php
session_start();

// Ensure only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Database Connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch valid employee IDs
$valid_employee_ids = [];
$employee_result = $conn->query("SELECT employee_id FROM employees");
if ($employee_result) {
    while ($row = $employee_result->fetch_assoc()) {
        $valid_employee_ids[] = $row['employee_id'];
    }
}

// Add Overtime
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $employee_id = $_POST['employee_id'];
    $hours_worked = isset($_POST['overtime_hours']) ? floatval($_POST['overtime_hours']) : 0;
    $minutes_worked = isset($_POST['overtime_minutes']) ? intval($_POST['overtime_minutes']) : 0;

    // Validate inputs
    if (!in_array($employee_id, $valid_employee_ids)) {
        $_SESSION['error_message'] = "Invalid Employee ID.";
    } elseif ($hours_worked < 0 || $minutes_worked < 0 || $minutes_worked >= 60) {
        $_SESSION['error_message'] = "Invalid hours or minutes input.";
    } else {
        // Fetch hourly rate based on position
        $stmt = $conn->prepare("
            SELECT p.rate_per_hour 
            FROM employees e 
            JOIN positions p ON e.position = p.position_id 
            WHERE e.employee_id = ?
        ");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $rate_result = $stmt->get_result();

        if ($rate_result && $rate_result->num_rows > 0) {
            $rate_row = $rate_result->fetch_assoc();
            $hourly_rate = floatval($rate_row['rate_per_hour']);
            $total_overtime_pay = ($hours_worked + ($minutes_worked / 60)) * $hourly_rate;

            // Insert overtime record
            $insert_stmt = $conn->prepare("
                INSERT INTO overtime_records 
                (employee_id, date_issued, hours_worked, minutes_worked, hourly_rate, total_overtime_pay)
                VALUES (?, NOW(), ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iiddd", $employee_id, $hours_worked, $minutes_worked, $hourly_rate, $total_overtime_pay);
            $insert_stmt->execute();
            $insert_stmt->close();

            $_SESSION['success_message'] = "Overtime record added successfully.";
        } else {
            $_SESSION['error_message'] = "Unable to fetch hourly rate for the employee.";
        }
        $stmt->close();
    }
    header("Location: overtime.php");
    exit();
}

// Update overtime entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $hours_worked = isset($_POST['hours_worked']) ? floatval($_POST['hours_worked']) : 0;
    $minutes_worked = isset($_POST['minutes_worked']) ? intval($_POST['minutes_worked']) : 0;

    if ($hours_worked >= 0 && $minutes_worked >= 0 && $minutes_worked < 60) {
        // Fetch hourly rate from the database
        $stmt = $conn->prepare("SELECT hourly_rate FROM overtime_records WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $rate_result = $stmt->get_result();

        if ($rate_result && $rate_result->num_rows > 0) {
            $rate_row = $rate_result->fetch_assoc();
            $hourly_rate = $rate_row['hourly_rate'];

            // Calculate total overtime pay
            $total_overtime_pay = ($hours_worked + ($minutes_worked / 60)) * $hourly_rate;

            // Update overtime record
            $update_stmt = $conn->prepare("UPDATE overtime_records SET hours_worked = ?, minutes_worked = ?, total_overtime_pay = ? WHERE id = ?");
            $update_stmt->bind_param("iidi", $hours_worked, $minutes_worked, $total_overtime_pay, $id);
            $update_stmt->execute();
            $update_stmt->close();

            $_SESSION['success_message'] = "Overtime updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to retrieve hourly rate for the overtime record.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid overtime details!";
    }
    header("Location: overtime.php");
    exit();
}

// Delete Overtime Entry
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM overtime_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['success_message'] = "Overtime record deleted successfully.";
    header("Location: overtime.php");
    exit();
}

// Fetch Overtime Records
$sql = "
    SELECT 
        o.id, o.employee_id, 
        CONCAT(e.firstname, ' ', e.lastname) AS employee_name, 
        o.date_issued, o.hours_worked, o.minutes_worked, 
        o.hourly_rate, o.total_overtime_pay
    FROM overtime_records o
    LEFT JOIN employees e ON o.employee_id = e.employee_id
";
$result = $conn->query($sql);
$overtimeRecords = $result->fetch_all(MYSQLI_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style-overtime.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Overtime</title>

    <script>
function openModal(id, employeeId, hours, minutes) {
    document.getElementById("editModal").style.display = "flex";
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_employee_id").value = employeeId;
    document.getElementById("edit_hours_worked").value = hours;
    document.getElementById("edit_minutes_worked").value = minutes;
}

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
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
                <li><a href="../EmManagement/employee-list.php"><i class="fa fa-users" aria-hidden="true"></i></i>Employee List</a></li>
                <li><a href="../EmManagement/inactive-employees.php"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
                <li><a href="../EmManagement/positions.php"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href="../EmManagement/schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="../EmManagement/employee-schedule.php"><i class="fa fa-address-book" aria-hidden="true"></i>Employee Schedule</a></li>
            </ul>

            <p>Payroll Management</p>
            <li><a href="payroll.php"><i class="fa fa-envelope" aria-hidden="true"></i>Payroll</a></li>
            <li><a href="cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                <a href="overtime.php" style=" color: black; transition: color 0.3s ease;"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
                    
           
            <li><a href="taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>


            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

        </ul>
    </div>

    <div class="content">
    <div class="contentBody">
        <div class="top-bar">
            <h1>Overtime</h1>
            <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div id="success-message" class="floating-message">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div id="error-message" class="floating-message">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="overtime.php">
            <label for="employee_id">Employee ID:</label>
            <input type="number" id="employee_id" name="employee_id" required>

            <label for="overtime_hours">No. of Hours:</label>
            <input type="number" id="overtime_hours" name="overtime_hours" required>

            <label for="overtime_minutes">No. of Minutes:</label>
            <input type="number" id="overtime_minutes" name="overtime_minutes" required>

            <button type="submit" name="add">Add Overtime</button>
        </form>


        <table>
            <tr>
                <th>Date</th>
                <th>Employee ID</th>
                <th>Employee Name</th>
                <th>No. of Hours</th>
                <th>No. of Minutes</th>
                <th>Rate</th>
                <th>Total Pay</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($overtimeRecords as $record): ?>
            <tr>
                <td><?= htmlspecialchars($record['date_issued']); ?></td>
                <td><?= htmlspecialchars($record['employee_id']); ?></td>
                <td><?= htmlspecialchars($record['employee_name']); ?></td>
                <td><?= htmlspecialchars($record['hours_worked']); ?></td>
                <td><?= htmlspecialchars($record['minutes_worked']); ?></td>
                <td><?= htmlspecialchars(number_format($record['hourly_rate'], 2)); ?></td>
                <td><?= htmlspecialchars(number_format($record['total_overtime_pay'], 2)); ?></td>
                <td>
                    <!-- Edit button -->
                    <button onclick="openModal('<?= $record['id']; ?>', '<?= htmlspecialchars($record['employee_id']); ?>', '<?= $record['hours_worked']; ?>', '<?= $record['minutes_worked']; ?>')" class="edit-button">
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    
                    <!-- Delete button -->
                    <a href="overtime.php?delete_id=<?= $record['id']; ?>" onclick="return confirm('Are you sure you want to delete this overtime entry?')">
                        <button class="delete-button">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Modal for editing overtime -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Overtime</h2>
            <form method="POST" action="overtime.php">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="edit_employee_id" name="employee_id">
                
                <label for="edit_hours_worked">No. of Hours:</label>
                <input type="number" id="edit_hours_worked" name="hours_worked" required>

                <label for="edit_minutes_worked">No. of Minutes:</label>
                <input type="number" id="edit_minutes_worked" name="minutes_worked" required>

                <button type="submit" name="update">Update</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Function to open the modal with existing data
    function openModal(id, employeeId, hours, minutes, rate) {
        document.getElementById("editModal").style.display = "flex";
        document.getElementById("edit_id").value = id;
        document.getElementById("edit_employee_id").value = employeeId;
        document.getElementById("edit_hours_worked").value = hours;
        document.getElementById("edit_minutes_worked").value = minutes;
        document.getElementById("edit_hourly_rate").value = rate;
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById("editModal").style.display = "none";
    }
</script>

</body>

</html>