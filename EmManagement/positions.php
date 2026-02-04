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
    $position_title = $_POST['position_title'];
    $rate_per_hour = $_POST['rate_per_hour'];

    // Check if the position title already exists in the database
    $checkSql = "SELECT * FROM positions WHERE position_title = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $position_title);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if rate_per_hour is positive
    if ($rate_per_hour <= 0) {
        echo "<script>alert('Rate per hour must be a positive number!');</script>";
    } elseif ($result->num_rows > 0) {
        echo "<script>alert('Position title already exists!');</script>";
    } else {
        // If no duplicate and rate is valid, proceed with insertion
        $addSql = "INSERT INTO positions (position_title, rate_per_hour) VALUES (?, ?)";
        $stmt = $conn->prepare($addSql);
        $stmt->bind_param("sd", $position_title, $rate_per_hour);
        $stmt->execute();

        header("Location: positions.php");
        exit();
    }
}

if (isset($_POST['update'])) {
    $position_id = $_POST['position_id'];
    $position_title = $_POST['position_title'];
    $rate_per_hour = $_POST['rate_per_hour'];

    // Check if rate_per_hour is positive
    if ($rate_per_hour <= 0) {
        echo "<script>alert('Rate per hour must be a positive number!');</script>";
    } else {
        $updateSql = "UPDATE positions SET position_title = ?, rate_per_hour = ? WHERE position_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("sdi", $position_title, $rate_per_hour, $position_id);
        $stmt->execute();

        header("Location: positions.php");
        exit();
    }
}


if (isset($_GET['delete'])) {
    $position_id = $_GET['delete'];

    $deleteSql = "DELETE FROM positions WHERE position_id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $position_id);
    $stmt->execute();

    header("Location: positions.php");
    exit();
}


$sql = "SELECT * FROM positions";
$result = $conn->query($sql);
?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Positions</title>

    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-positions.css">
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
                <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                    <a href="positions.php" style=" color: black; transition: color 0.3s ease;"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
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
                <h1>Positions</h1>
                <a href="../admin-profile.php"><img alt="Company Logo" height="80" src="../img/lourds_cafe.jpg" width="80" /></a>
            </div>
            
            <h2>Current Positions</h2>
            <div class="posButton">
                <button id="add-position-btn" onclick="openForm()">Add Position Title</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Position Title</th>
                        <th>Rate per Hour</th>
                        <th>Tools</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['position_title']) . "</td>";
                            echo "<td>₱" . htmlspecialchars(number_format($row['rate_per_hour'], 2)) . "</td>";
                            echo "<td>
                                <button onclick=\"editPosition({$row['position_id']}, '" . addslashes($row['position_title']) . "', {$row['rate_per_hour']})\" class='edit-button'>
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                <a href='positions.php?delete={$row['position_id']}' onclick=\"return confirm('Are you sure you want to delete this position?');\">
                                    <button class='delete-button'>
                                    <i class='fas fa-trash-alt'></i> Delete
                                    </button>
                                </a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No positions found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>


        <div class="overlay" id="overlay"></div>
            <div id="position-form">
            <span class="close" onclick="closeForm()">&times;</span>
            <h2>Edit Overtime</h2>
                <form method="POST" action="positions.php">
                    <input type="hidden" name="position_id" id="position_id">
                    
                    <label for="position_title">Position Title:</label>
                    <input type="text" name="position_title" id="position_title" placeholder="Enter Position Title" required>
                    
                    <label for="rate_per_hour">Rate per Hour (₱):</label>
                    <input type="number" step="0.01" name="rate_per_hour" id="rate_per_hour" placeholder="Enter Rate per Hour" required>
                    
                    <button type="submit" name="add" id="add-button">Add Position</button>
                    <button type="submit" name="update" id="update-button" style="display: none;">Update Position</button>
                    <button type="button" onclick="closeForm()">Cancel</button>
                </form>
            </div>

        <script>
            function openForm() {
                document.getElementById('position_id').value = '';
                document.getElementById('position_title').value = '';
                document.getElementById('rate_per_hour').value = '';
                document.getElementById('add-button').style.display = 'inline-block';
                document.getElementById('update-button').style.display = 'none';
                document.getElementById('position-form').style.display = 'block';
                document.getElementById('overlay').style.display = 'block';
            }

            function editPosition(id, title, rate) {
                document.getElementById('position_id').value = id;
                document.getElementById('position_title').value = title;
                document.getElementById('rate_per_hour').value = rate;
                document.getElementById('add-button').style.display = 'none';
                document.getElementById('update-button').style.display = 'inline-block';
                document.getElementById('position-form').style.display = 'block';
                document.getElementById('overlay').style.display = 'block';
            }

            function closeForm() {
                document.getElementById('position-form').style.display = 'none';
                document.getElementById('overlay').style.display = 'none';
            }

            // Close modal when clicking on the overlay
            document.getElementById('overlay').onclick = function (event) {
                closeForm();
            };

            document.querySelector('form').addEventListener('submit', function(event) {
                const ratePerHour = parseFloat(document.getElementById('rate_per_hour').value);

                if (ratePerHour <= 0) {
                    alert("Rate per hour must be a positive number!");
                    event.preventDefault(); // Prevent form submission if validation fails
                }
            });

        </script>

</body>

</html>

<?php
$conn->close();
?>