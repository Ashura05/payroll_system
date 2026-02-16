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
    $employee_id = intval($_GET['inactive']); // Sanitize input

    // Delete related records first (in order of dependencies)
    $conn->query("DELETE FROM overtime_records WHERE employee_id = $employee_id");
    $conn->query("DELETE FROM attendance WHERE employee_id = $employee_id");
    $conn->query("DELETE FROM employee_schedule WHERE employee_id = $employee_id");
    $conn->query("DELETE FROM payroll WHERE employee_id = $employee_id");
    $conn->query("DELETE FROM cash_advance WHERE employee_id = $employee_id");

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
        e.employee_id = $employee_id";
    
    if (!$conn->query($move_employee_sql)) {
        die("Error moving employee: " . $conn->error);
    }

    // Delete the employee from the employees table
    $delete_employee_sql = "DELETE FROM employees WHERE employee_id = $employee_id";
    if (!$conn->query($delete_employee_sql)) {
        die("Error deleting employee: " . $conn->error);
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-box">
                <span class="logo-text">LOURD'S Café</span>
            </div>
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="sidebar-menu">
            <ul>
                <p class="section-title">Reports</p>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <p class="section-title">Employee Management</p>
                <ul>
                    <li><a href="attendance.php"><i class="fa fa-laptop"></i><span>Attendance</span></a></li>
                    <li style="background-color: #f3feff; border-radius: 7px 0px 0px 7px;">
                        <a href="employee-list.php" style="color: black;"><i class="fa fa-users"></i><span>Employee List</span></a>
                    </li>
                    <li><a href="inactive-employees.php"><i class="fa fa-user-times"></i><span>Inactive Employees</span></a></li>
                    <li><a href="positions.php"><i class="fa fa-briefcase"></i><span>Positions</span></a></li>
                </ul>
                <p class="section-title">Time Management</p>
                <ul>
                    <li><a href="schedule_management.php"><i class="fas fa-calendar-check"></i><span>Schedules</span></a></li>
                    <li><a href="employee-schedule.php"><i class="fa fa-address-book"></i><span>Employee Schedule</span></a></li>
                </ul>
                <p class="section-title">Payroll Management</p>
                <li><a href="../PayManagement/payroll.php"><i class="fa fa-envelope"></i><span>Payroll</span></a></li>
                <li><a href="../PayManagement/cash-advance.php"><i class="fas fa-money-check-alt"></i><span>Cash Advance</span></a></li>
                <li><a href="../PayManagement/overtime.php"><i class="fas fa-clock"></i><span>Overtime</span></a></li>
                <li><a href="../PayManagement/taxDeduction.php"><i class="fa fa-minus-square"></i><span>Deductions</span></a></li>
            </ul>
        </div>
        <div class="sidebar-logout">
            <ul>
                <li>
                    <a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');">
                        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>
    <div class="body">
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
    </div>
</body>

</html>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.content').classList.toggle('collapsed');
}
</script>

<style>
.content {
    margin-left: 260px;
    transition: margin-left .3s ease;
}
.content.collapsed {
    margin-left: 64px;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    background: #2fae9c;
    color: #fff;
    transition: width .3s ease;
    z-index: 1000;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.sidebar.collapsed {
    width: 64px;
}
.logo-text {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
    white-space: nowrap;
}
.sidebar.collapsed .logo-text {
    opacity: 0;
    pointer-events: none;
}
.toggle-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 22px;
    cursor: pointer;
    margin-left: 8px;
}
.section-title {
    font-size: 14px;
    font-weight: bold;
    margin: 16px 0 8px 16px;
    color: #fff;
}
.sidebar.collapsed .section-title {
    opacity: 0;
    pointer-events: none;
}
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar li a {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: #fff;
    text-decoration: none;
    font-size: 16px;
    transition: background .2s, color .2s;
}
.sidebar li a i {
    margin-right: 12px;
    font-size: 18px;
}
.sidebar.collapsed li a span {
    opacity: 0;
    pointer-events: none;
}
.sidebar li a:hover {
    background: #249e8c;
    color: #fff;
}

.contentBody {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
    box-sizing: border-box;
}

table {
    width: 100%;
    max-width: 100%;
    table-layout: auto;
    word-break: break-word;
    font-size: 15px; /* Resize table text */
}

th, td {
    padding: 8px 12px; /* Add spacing for cells */
    text-align: left;
    font-size: 15px;   /* Resize cell text */
}

.actions {
    display: flex;
    gap: 6px;
}

.edit-button, .inactive-button {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;      /* Smaller padding */
    font-size: 13px;       /* Smaller button text */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    min-width: 100px;       /* Minimum width for consistency */
}

.edit-button {
    background: #4caf50;
    color: #fff;
}

.inactive-button {
    background: #f44336;
    color: #fff;
    min-width: 130px;  
}

.sidebar-menu {
    flex: 1 1 auto;
    overflow-y: auto;
}

.sidebar-logout {
    flex-shrink: 0;
    margin-bottom: 16px;
}

.sidebar-logout ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-logout li a {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: #fff;
    text-decoration: none;
    font-size: 16px;
    transition: background .2s, color .2s;
}

.sidebar-logout li a i {
    margin-right: 12px;
    font-size: 18px;
}

.sidebar-logout li a:hover {
    background: #249e8c;
    color: #fff;
}
</style>



