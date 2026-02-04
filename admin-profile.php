<?php
session_start();
require_once './db/db_connection.php';

// Check if session variables for role and admin_id are set
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['admin_id'])) {
    // If the session variables are not set, redirect to the login page (index.php)
    header("Location: index.php");
    exit();
}

// Retrieve session variables
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

$feedback = '';
$username = $newUsername = $confirmNewUsername = $currentPassword = $newPassword = $confirmPassword = "";

// Handle form submission for username and password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form values
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newUsername = $_POST['newUsername'] ?? '';
    $confirmNewUsername = $_POST['confirmNewUsername'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validate the username and password inputs
    if (empty($currentPassword)) {
        $feedback = "Current password is required.";
    } elseif (empty($newUsername) || strlen($newUsername) > 8 || strpos($newUsername, ' ') !== false) {
        $feedback = "New username must be 8 characters or less, and no spaces allowed.";
    } elseif ($newUsername === $adminUsername) {
        $feedback = "New username cannot be the same as the current username.";
    } elseif ($newUsername !== $confirmNewUsername) {
        $feedback = "New username and confirmation do not match.";
    } elseif (empty($newPassword) || strlen($newPassword) > 8 || strpos($newPassword, ' ') !== false) {
        $feedback = "New password must be 8 characters or less, and no spaces allowed.";
    } elseif ($newPassword !== $confirmPassword) {
        $feedback = "New password and confirmation do not match.";
    } elseif ($currentPassword === $newPassword) {
        // Add this check to ensure current password is not the same as the new password
        $feedback = "Current password cannot be the same as the new password.";
    } else {
        // Verify current password
        $query = "SELECT password_hash FROM admin_accounts WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $adminUsername);
        $stmt->execute();
        $stmt->bind_result($storedPasswordHash);
        $stmt->fetch();
        $stmt->close();

        if (!$storedPasswordHash || !password_verify($currentPassword, $storedPasswordHash)) {
            $feedback = "Current password is incorrect.";
        } else {
            // Start a transaction to ensure both updates (username and password) happen together
            $conn->begin_transaction();
            try {
                // Update the admin's username if it's changed
                if ($newUsername !== $adminUsername) {
                    $updateUsernameQuery = "UPDATE admin_accounts SET username = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateUsernameQuery);
                    $stmt->bind_param("si", $newUsername, $adminId);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update username.");
                    }
                    // Update the session with the new username
                    $_SESSION['admin_username'] = $newUsername;
                }

                // Update to the new password
                if (!empty($newPassword)) {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updatePasswordQuery = "UPDATE admin_accounts SET password_hash = ? WHERE id = ?";
                    $stmt = $conn->prepare($updatePasswordQuery);
                    $stmt->bind_param("si", $newPasswordHash, $adminId);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update password.");
                    }
                }

                // Commit the transaction if both queries are successful
                $conn->commit();
                $feedback = "Username and/or password updated successfully!";
            } catch (Exception $e) {
                // Rollback transaction if there was any error
                $conn->rollback();
                $feedback = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Admin Profile</title>
    <link rel="stylesheet" href="style/style-sidebar.css">
    <link rel="stylesheet" href="style/style-admin-profile.css">
</head>

<body>
    <div class="sidebar">
        <a href=""><img alt="Company Logo" height="80" src="img/letterLogo.png" width="80" /></a>

        <ul>
            <p>Reports</p>
            <li style="background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                <a href="" style=" color: black; transition: color 0.3s ease;"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            </li>

            <p>Employee Management</p>
            <ul>
                <li><a href="EmManagement/Attendance.php"><i class="fa fa-laptop" aria-hidden="true"></i>Attendance</a></li>
                <li><a href="EmManagement/employee-list.php"><i class="fa fa-users" aria-hidden="true"></i>Employee List</a></li>
                <li><a href="EmManagement/inactive-employees.php"><i class="fa fa-user-times" aria-hidden="true"></i>Inactive Employees</a></li>
                <li><a href="EmManagement/positions.php"><i class="fa fa-briefcase" aria-hidden="true"></i>Positions</a></li>
            </ul>

            <p>Time Management</p>
            <ul>
                <li><a href="EmManagement/schedule_management.php"><i class="fas fa-calendar-check"></i>Schedules</a></li>
                <li><a href="EmManagement/employee-schedule.php"><i class="fa fa-address-book" aria-hidden="true"></i>Employee Schedule</a></li>
            </ul>

            <p>Payroll Management</p>
            <li><a href="PayManagement/payroll.php"><i class="fa fa-envelope" aria-hidden="true"></i>Payroll</a></li>
            <li><a href="PayManagement/cash-advance.php"><i class="fas fa-money-check-alt"></i>Cash Advance</a></li>
            <li><a href="PayManagement/taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>

            <li><a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>

    </div>

    <div class="content">
        <div class="contentBody">
            <div class="top-bar">
                <h1>Admin Profile</h1>
            </div>

            <h2>Edit Admin Profile</h2>
            <?php if (!empty($feedback)): ?>
                <p style="color: <?= strpos($feedback, "successfully") !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($feedback) ?></p>
            <?php endif; ?>

            <form id="adminProfileForm" method="POST" enctype="multipart/form-data" class="employee-form">
                <div class="form-container">
                    <div class="form-column">
                        <label for="username">Current Username:</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($adminUsername) ?>" disabled><br><br>

                        <label for="newUsername">New Username:</label>
                        <input type="text" id="newUsername" name="newUsername" value="<?= htmlspecialchars($newUsername) ?>" placeholder="Enter New Username" required><br><br>

                        <label for="confirmNewUsername">Confirm New Username:</label>
                        <input type="text" id="confirmNewUsername" name="confirmNewUsername" value="<?= htmlspecialchars($confirmNewUsername) ?>" placeholder="Confirm New Username" required><br><br>
                    </div>

                    <div class="form-column">
                        <label for="currentPassword">Current Password:</label>
                        <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter Current Password" required><br><br>

                        <label for="password">New Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter New Password" required><br><br>

                        <label for="confirmPassword">Confirm New Password:</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm New Password" required><br><br>
                    </div>
                </div>

                <div class="submit-btn-container">
                    <input type="submit" name="update_profile" value="Update Profile">
                    <button type="button" class="cancel-btn" onclick="redirectToPreviousPage()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function redirectToPreviousPage() {
            const previousPage = document.referrer;
            if (previousPage) {
                window.location.href = previousPage;
            } else {
                window.location.href = "dashboard.php";
            }
        }
    </script>
</body>

</html>