<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle employee reactivation
if (isset($_GET['reactivate'])) {
    $employee_id = $_GET['reactivate'];

    // Move the employee back to the employees table
    $move_employee_sql = "
        INSERT INTO employees 
            (employee_id, photo, firstname, lastname, position, email, phone, address, member_since)
        SELECT 
            employee_id, photo, firstname, lastname, position, email, phone, address, member_since
        FROM 
            inactive_employees
        WHERE 
            employee_id = ?";
    $stmt_move = $conn->prepare($move_employee_sql);
    $stmt_move->bind_param("i", $employee_id);
    $stmt_move->execute();

    // Delete the employee from the inactive_employees table
    $delete_inactive_sql = "DELETE FROM inactive_employees WHERE employee_id = ?";
    $stmt_delete = $conn->prepare($delete_inactive_sql);
    $stmt_delete->bind_param("i", $employee_id);
    $stmt_delete->execute();

    header("Location: inactive-employees.php");
    exit();
}

if (isset($_GET['delete'])) {
    $employee_id = $_GET['delete'];

    // Delete the employee permanently from the inactive_employees table
    $delete_employee_sql = "DELETE FROM inactive_employees WHERE employee_id = ?";
    $stmt_delete = $conn->prepare($delete_employee_sql);
    $stmt_delete->bind_param("i", $employee_id);

    // Check if the deletion was successful
    if (!$stmt_delete->execute()) {
        echo "Error deleting employee: " . $stmt_delete->error;
        exit();
    } elseif ($stmt_delete->affected_rows > 0) {
        echo "Employee deleted successfully.";
    } else {
        echo "No rows affected. Employee ID may not exist.";
    }

    header("Location: inactive-employees.php");
    exit();
}
// Fetch inactive employees with position and schedule information
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
        inactive_employees e
    LEFT JOIN 
        positions p ON e.position = p.position_id
    LEFT JOIN 
        schedules s ON e.schedule_id = s.schedule_id
";

$result = $conn->query($sql);
?>


<html>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Inactive Employees</title>
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
                <li><a href="employee-list.php"><i class="fa fa-users" aria-hidden="true"></i></i>Employee List</a></li>
                <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                    <a href="inactive-employees.php" style=" color: black; transition: color 0.3s ease;"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
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
                <h1>Inactive Employees</h1>
                <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
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
                    if ($result->num_rows > 0) {
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
                                    <a href='inactive-employees.php?reactivate={$row['employee_id']}' onclick='return confirm(\"Are you sure you want to reactivate this employee?\");'>
                                        <button class='reactivate-button'><i class='fas fa-user-check'></i> Reactivate</button>
                                    </a>
                                    <a href='inactive-employees.php?delete={$row['employee_id']}' onclick='return confirm(\"Are you sure you want to delete this employee permanently? This action cannot be undone.\");'>
                                        <button class='delete-button'><i class='fas fa-trash-alt'></i> Delete</button>
                                    </a>
                                </div>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No inactive employees found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>