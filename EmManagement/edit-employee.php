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

// Fetch employee details
if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];
    $sql = "SELECT * FROM employees WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
} else {
    header("Location: employee-list.php");
    exit();
}

// Fetch positions from the database
$positions = [];
$position_sql = "SELECT position_id, position_title FROM positions";
$position_result = $conn->query($position_sql);
if ($position_result->num_rows > 0) {
    while ($row = $position_result->fetch_assoc()) {
        $positions[] = $row;
    }
}

// Update employee details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $emp_id = $_POST['employee_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $position = isset($_POST['position']) ? $_POST['position'] : null;
    $photo = $_FILES['photo']['name'];
    $member_since = $_POST['member_since'];

    // Validate position
    if (empty($position)) {
        $_SESSION['error_message'] = "Please select a valid position.";
        header("Location: edit-employee.php?employee_id=" . $emp_id);
        exit();
    }

    // Validate phone number
    if (!preg_match('/^09\d{9}$/', $phone)) {
        $_SESSION['error_message'] = "Invalid phone number. Phone number must start with 09 and be exactly 11 digits long.";
        header("Location: edit-employee.php?employee_id=" . $emp_id);
        exit();
    }

    // Check for duplicate email or phone
    $sql_check = "SELECT * FROM employees WHERE (email = ? OR phone = ?) AND employee_id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssi", $email, $phone, $emp_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['error_message'] = "Email or phone number already exists for another employee.";
        header("Location: edit-employee.php?employee_id=" . $emp_id);
        exit();
    }

    // Prepare photo upload directory
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Check file extension
    if (!empty($photo)) {
        $file_extension = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file format. Only .jpg, .jpeg, and .png files are allowed.";
            header("Location: edit-employee.php?employee_id=" . $emp_id);
            exit();
        }

        $target_file = $target_dir . basename($photo);
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $_SESSION['error_message'] = "Error uploading file.";
            header("Location: edit-employee.php?employee_id=" . $emp_id);
            exit();
        }
    }

    // Build the update query
    $update_sql = "UPDATE employees SET firstname = ?, lastname = ?, email = ?, phone = ?, address = ?, position = ?, member_since = ?";

    // Add photo to the SQL query if uploaded
    if (!empty($photo)) {
        $update_sql .= ", photo = ?";
    }
    $update_sql .= " WHERE employee_id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        $_SESSION['error_message'] = "Error preparing the query: " . $conn->error;
        header("Location: edit-employee.php?employee_id=" . $emp_id);
        exit();
    }

    // Bind parameters based on photo existence
    if (!empty($photo)) {
        $stmt->bind_param("ssssssssi", $firstname, $lastname, $email, $phone, $address, $position, $member_since, $target_file, $emp_id);
    } else {
        $stmt->bind_param("sssssssi", $firstname, $lastname, $email, $phone, $address, $position, $member_since, $emp_id);
    }

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Employee updated successfully!";
        header("Location: employee-list.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating employee details: " . $stmt->error;
        header("Location: edit-employee.php?employee_id=" . $emp_id);
        exit();
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Modify Employee Profile</title>
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-edit-employee.css">
    <style>
        .error-banner {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
    </style>
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
                <li style="background-color: #f3feff; border-radius: 7px 0px 0px 7px; text-decoration: none; height: 27px; font-size: 18px; transition: color 0.3s ease;">
                    <a href="employee-list.php" style="color: black; transition: color 0.3s ease;"><i class="fa fa-users" aria-hidden="true"></i>Employee List</a></li>
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
            <li><a href="../PayManagement/taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>

            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="contentBody">

            <div class="top-bar">
                <h1> Modify Employee Profile</h1>
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


            <form action="edit-employee.php?employee_id=<?php echo $employee['employee_id']; ?>" method="POST" enctype="multipart/form-data" class="employee-form">
                <div class="form-container">
                    <div class="form-column">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                        <input type="file" id="photo" name="photo" accept=".jpg, .jpeg, .png"><br><br>
                        <label for="firstname">First Name:</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($employee['firstname'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee['email'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($employee['address'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
                       
                        <label for="phone">Phone:</label>
                        <input type="text" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($employee['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                            maxlength="11" pattern="09\d{9}"
                            title="Phone number must start with '09' and be exactly 11 digits long."
                            required>
                    </div>

                    <div class="form-column">
                        <label for="lastname">Last Name:</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($employee['lastname'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
                        <label for="position">Position:</label>
                        <div class="select-wrapper">
                            <select id="position" name="position" required>
                                <option value="" disabled>Select a position</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo htmlspecialchars($pos['position_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($employee['position'] == $pos['position_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pos['position_title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <label for="member_since">Member Since:</label>
                        <input type="date" id="member_since" name="member_since" value="<?php echo $employee['member_since'] ? date('Y-m-d', strtotime($employee['member_since'])) : ''; ?>"><br><br>
                    </div>
                </div>
                <div class="submit-btn-container">
                    <input type="submit" name="update_employee" value="Update Employee">

                    <button type="button" onclick="window.location.href='employee-list.php'" class="cancel-btn">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    window.onload = function() {
    // Check if success or error message is present
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    if (successMessage || errorMessage) {
        // Show the message
        if (successMessage) {
            successMessage.style.display = 'block';
        } else if (errorMessage) {
            errorMessage.style.display = 'block';
        }

        // After 5 seconds (when the animation is complete), hide the message
        setTimeout(function() {
            if (successMessage) {
                successMessage.style.display = 'none';
            } else if (errorMessage) {
                errorMessage.style.display = 'none';
            }
        }, 5000); // The duration of the animation
    }
};
</script>


</body>

</html>