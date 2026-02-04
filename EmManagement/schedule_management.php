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

if (isset($_POST['add'])) {
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    // Check if the schedule already exists in the database
    $checkSql = "SELECT * FROM schedules WHERE time_in = ? AND time_out = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ss", $time_in, $time_out);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the schedule already exists, display a message and don't insert it
    if ($result->num_rows > 0) {
        echo "<script>alert('Schedule already exists. Please choose a different time.');</script>";
    } else {
        // If the schedule doesn't exist, insert it
        $addSql = "INSERT INTO schedules (time_in, time_out) VALUES (?, ?)";
        $stmt = $conn->prepare($addSql);
        $stmt->bind_param("ss", $time_in, $time_out);
        $stmt->execute();

        header("Location: schedule_management.php");
        exit();
    }
}

if (isset($_POST['update'])) {
    $schedule_id = $_POST['schedule_id'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    // Check if the new schedule already exists for a different schedule_id
    $checkSql = "SELECT * FROM schedules WHERE time_in = ? AND time_out = ? AND schedule_id != ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ssi", $time_in, $time_out, $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the schedule exists, block the update
    if ($result->num_rows > 0) {
        echo "<script>alert('The schedule already exists for another record. Please choose a different time.');</script>";
    } else {
        // Proceed with the update if no duplicates are found
        $updateSql = "UPDATE schedules SET time_in = ?, time_out = ? WHERE schedule_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssi", $time_in, $time_out, $schedule_id);
        $stmt->execute();

        header("Location: schedule_management.php");
        exit();
    }
}


if (isset($_GET['delete'])) {
    $schedule_id = $_GET['delete'];

    // Try to delete the schedule and catch any foreign key constraint errors
    try {
        $deleteSql = "DELETE FROM schedules WHERE schedule_id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        header("Location: schedule_management.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Cannot delete this schedule because it is assigned to employees. Please remove the assignments first.');</script>";
    }
}


$sql = "SELECT * FROM schedules";
$result = $conn->query($sql);
?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Schedules</title>
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-schedules.css">
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
                <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                    <a href="schedule_management.php" style=" color: black; transition: color 0.3s ease;"><i class="fas fa-calendar-check"></i>Schedules</a></li>
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
                <h1>Schedules</h1>
                <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
            </div>

            <h2>Employee Schedules</h2>
            <div class="posButton">
                <button id="add-schedule-btn" onclick="openForm()">Add New Schedule</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Tools</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $time_in_12hr = date("g:i A", strtotime($row['time_in']));
                            $time_out_12hr = date("g:i A", strtotime($row['time_out']));
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($time_in_12hr) . "</td>";
                            echo "<td>" . htmlspecialchars($time_out_12hr) . "</td>";
                            echo "<td>
                                <button onclick=\"editSchedule({$row['schedule_id']}, '{$row['time_in']}', '{$row['time_out']}')\" class='edit-button'>
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                <a href='schedule_management.php?delete={$row['schedule_id']}' onclick=\"return confirm('Are you sure you want to delete this schedule?');\">
                                    <button class='delete-button'>
                                    <i class='fas fa-trash-alt'></i> Delete
                                    </button>
                                </a>
                              </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No schedules found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="overlay" id="overlay"></div>
            <div id="schedule-form">
                <span class="close" onclick="closeForm()">&times;</span>
                <h2>Edit Schedule</h2>
                <form method="POST" action="schedule_management.php">
                    <input type="hidden" name="schedule_id" id="schedule_id">

                    <label for="time_in">Time In:</label>
                    <input type="time" name="time_in" id="time_in" required>

                    <label for="time_out">Time Out:</label>
                    <input type="time" name="time_out" id="time_out" required>

                    <button type="submit" name="add" id="add-button">Add Schedule</button>
                    <button type="submit" name="update" id="update-button" style="display: none;">Update Schedule</button>
                    <button type="button" onclick="closeForm()">Cancel</button>
                </form>
            </div>


    <script>
        function openForm() {
            document.getElementById('schedule_id').value = '';
            document.getElementById('time_in').value = '';
            document.getElementById('time_out').value = '';
            document.getElementById('add-button').style.display = 'inline-block';
            document.getElementById('update-button').style.display = 'none';
            document.getElementById('schedule-form').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function formatTime24to12(time) {
            const [hours, minutes] = time.split(':');
            const period = hours >= 12 ? 'PM' : 'AM';
            const hours12 = hours % 12 || 12;
            return `${hours12}:${minutes} ${period}`;
        }

        function editSchedule(id, timeIn, timeOut) {
            document.getElementById('schedule_id').value = id;
            document.getElementById('time_in').value = timeIn;
            document.getElementById('time_out').value = timeOut;

            const timeInFormatted = formatTime24to12(timeIn);
            const timeOutFormatted = formatTime24to12(timeOut);

            alert(`Editing Schedule:\nTime In: ${timeInFormatted}\nTime Out: ${timeOutFormatted}`);

            document.getElementById('add-button').style.display = 'none';
            document.getElementById('update-button').style.display = 'inline-block';
            document.getElementById('schedule-form').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeForm() {
            document.getElementById('schedule-form').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        document.getElementById('overlay').onclick = function(event) {
            if (event.target === document.getElementById('overlay')) {
                closeForm();
            }
        };
    </script>
</body>

</html>

<?php
$conn->close();
?>