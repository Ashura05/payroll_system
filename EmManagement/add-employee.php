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

// Fetch positions for the dropdown
$sql_positions = "SELECT * FROM positions";
$result_positions = $conn->query($sql_positions);
$positions = [];
if ($result_positions && $result_positions->num_rows > 0) {
    while ($row = $result_positions->fetch_assoc()) {
        $positions[] = $row;
    }
}

// Add Employee Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_employee'])) {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $position = $_POST['position'] ?? '';
    $member_since = $_POST['member_since'] ?? null;

    $photo_path = null;
    $message = '';
    $message_type = 'success';

    // File validation checks
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_name = basename($_FILES['photo']['name']);
        $sanitized_photo_name = preg_replace("/[^a-zA-Z0-9._-]/", "", $photo_name);
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($sanitized_photo_name, PATHINFO_EXTENSION));
        $max_file_size = 2 * 1024 * 1024; // 2MB
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        // Check if the file extension is valid
        if (!in_array($file_extension, $allowed_extensions)) {
            $message = "Invalid file extension. Only JPG, JPEG, and PNG files are allowed.";
        } elseif ($_FILES['photo']['size'] > $max_file_size) {
            $message = "File size exceeds 2MB limit.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            $allowed_mime_types = ['image/jpeg', 'image/png'];
            if (!in_array($mime_type, $allowed_mime_types)) {
                $message = "Invalid file format. Only .jpg, .jpeg, and .png files are allowed.";
            } else {
                $image_info = getimagesize($_FILES['photo']['tmp_name']);
                if ($image_info === false) {
                    $message = "Uploaded file is not a valid image.";
                }
            }
        }

        if (!empty($message)) {
            $_SESSION['error_message'] = $message;
            header("Location: add-employee.php");
            exit();
        }

        // Generate unique file name and move the file
        if (empty($message)) {
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_file_name = uniqid('photo_', true) . '.' . $file_extension;
            $photo_path = $target_dir . $new_file_name;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                $message = "Error uploading file.";
                $_SESSION['error_message'] = $message;
                header("Location: add-employee.php");
                exit();
            }
        }
    }

    // Validate fields
    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($address) || empty($position)) {
        $message = "Please fill in all required fields.";
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $message = "Phone number must start with 09 and be exactly 11 digits.";
    } else {
        // Check for duplicate email or phone
        $sql_check = "SELECT * FROM employees WHERE email = ? OR phone = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $email, $phone);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Employee with this email or phone number already exists.";
        } else {
            // Insert into database
            $sql = "INSERT INTO employees (firstname, lastname, email, phone, address, position, photo, member_since)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $firstname, $lastname, $email, $phone, $address, $position, $photo_path, $member_since);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Employee added successfully!";
                header("Location: employee-list.php");
                exit();
            } else {
                $message = "Error adding employee.";
            }
        }
        $stmt_check->close();
    }

    if (!empty($message)) {
        $_SESSION['error_message'] = $message;
        header("Location: add-employee.php");
        exit();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Add Employee</title>
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-add-employee.css">
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
        <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
        <li><a href="../PayManagement/taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>

        <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="content">
    <div class="contentBody">
        <div class="top-bar">
            <h1>Add New Employee</h1>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div id="success-message" class="floating-message">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div id="error-message" class="floating-message error">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>



        <form action="add-employee.php" method="POST" enctype="multipart/form-data" class="employee-form">
            <div class="form-container">
                <div class="form-column">
                    <label for="photo">Photo:</label>
                    <input type="file" id="photo" name="photo" accept=".jpg, .jpeg, .png" title="Upload only '.jpeg', '.jpg', or '.png' file types." required>

                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" required><br><br>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required><br><br>

                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required><br><br>
                </div>

                <div class="form-column">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" required><br><br>

                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" maxlength="11" pattern="09\d{9}"
                        title="Phone number must start with '09' and be exactly 11 digits long."
                        required>

                    <label for="position">Position:</label>
                    <div class="select-wrapper">
                        <select id="position" name="position">
                            <option value="" disabled selected>Select a position</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?= htmlspecialchars($position['position_id']) ?>">
                                    <?= htmlspecialchars($position['position_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label for="member_since">Member Since:</label>
                    <input type="date" id="member_since" name="member_since" required><br><br>
                </div>
            </div>
            <div class="submit-btn-container">
                <input type="submit" name="submit_employee" value="Add Employee">

                <button type="button" onclick="window.location.href='employee-list.php'" class="cancel-btn">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.onload = function() {
        var successMessage = document.getElementById('success-message');
        var errorMessage = document.getElementById('error-message');

        if (successMessage || errorMessage) {
            if (successMessage) {
                successMessage.style.display = 'block';
            }
            if (errorMessage) {
                errorMessage.style.display = 'block';
            }

            setTimeout(function() {
                if (successMessage) {
                    successMessage.style.display = 'none';
                }
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            }, 5000); // Hide after 5 seconds
        }
    };
</script>

</body>

</html> 