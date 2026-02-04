<?php
session_start();
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

// Handle inactivate employee action
if (isset($_GET['inactive'])) {
    $employee_id = $_GET['inactive'];

    // Move the employee to the inactive_employees table
    // Move the employee to the inactive_employees table
    $move_employee_sql = "
    INSERT INTO inactive_employees 
        (employee_id, photo, firstname, lastname, position, email, phone, address, schedule_id, shift_start, shift_end, member_since)
    SELECT 
        e.employee_id, e.photo, e.firstname, e.lastname, e.position, e.email, e.phone, e.address, 
        es.schedule_id, es.shift_start, es.shift_end, e.member_since
    FROM 
        employees e
    LEFT JOIN 
        employee_schedule es ON e.employee_id = es.employee_id
    WHERE 
        e.employee_id = ?";
    $stmt_move = $conn->prepare($move_employee_sql);
    $stmt_move->bind_param("i", $employee_id);
    $stmt_move->execute();


    // Delete the employee from the employees table
    $delete_employee_sql = "DELETE FROM employees WHERE employee_id = ?";
    $stmt_delete = $conn->prepare($delete_employee_sql);
    $stmt_delete->bind_param("i", $employee_id);
    $stmt_delete->execute();


    $success_message = "Employee moved to inactive successfully.";
    header("Location: employee-list.php");
    exit();
}

// Fetch active employees
$sql = "
    SELECT 
        e.employee_id,
        e.firstname,
        e.lastname,
        e.email,
        e.phone,
        e.address,
        p.position_title AS position,
        IFNULL(CONCAT(DATE_FORMAT(s.time_in, '%h:%i %p'), ' - ', DATE_FORMAT(s.time_out, '%h:%i %p')), 'No Schedule') AS schedule,
        e.photo,
        e.member_since
    FROM 
        employees e
    LEFT JOIN 
        positions p ON e.position = p.position_id
    LEFT JOIN 
        employee_schedule es ON e.employee_id = es.employee_id
    LEFT JOIN 
        schedules s ON es.schedule_id = s.schedule_id
";




// Execute query and check for errors
$result = $conn->query($sql);
if (!$result) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Employee List</title>
    <link rel="stylesheet" href="../style/style-employee-list.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
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
                <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                    <a href="employee-list.php" style=" color: black; transition: color 0.3s ease;"><i class="fa fa-users" aria-hidden="true"></i></i>Employee List</a>
                </li>
                <li><a href="inactive-employees.php"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
                <li><a href="positions.php"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href="schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="employee-schedule.php"><i class="fa fa-address-book" aria-hidden="true"></i>Employee Schedule</a></li>
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
                <h1>Employee List</h1>
                <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
            </div>


            <?php if (isset($success_message)) {
                echo "<div class='success-message'>$success_message</div>";
            } ?>

            <h2>Current Employees</h2>
            <div class="addTopbar">
                <button id="add-employee-btn" onclick="location.href='add-employee.php'">Add Employee</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Schedule</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $schedule = !empty($row['schedule']) ? $row['schedule'] : 'No Schedule';
                            echo "<tr>";
                            echo "<td>{$row['employee_id']}</td>";
                            echo "<td><img src='{$row['photo']}' alt='{$row['firstname']}' width='50'></td>";
                            echo "<td>{$row['firstname']} {$row['lastname']}</td>";
                            echo "<td>{$row['position']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>{$row['address']}</td>";
                            echo "<td>{$schedule}</td>";
                            echo "<td>{$row['member_since']}</td>";
                            echo "<td>
                                <div class='actions'>
                                    <a href='edit-employee.php?employee_id={$row['employee_id']}'><button class='edit-button'><i class='fas fa-edit'></i> Edit</button></a>
                                    <a href='employee-list.php?inactive={$row['employee_id']}' onclick='return confirm(\"Are you sure you want to mark this employee as inactive?\");'><button class='inactive-button'><i class='fas fa-user-slash'></i> Inactivate</button></a>
                                </div>
                              </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10'>No active employees found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>