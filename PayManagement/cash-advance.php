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

// Fetch valid employee IDs
$valid_employee_ids = [];
$employee_result = $conn->query("SELECT employee_id FROM employees");
if ($employee_result && $employee_result->num_rows > 0) {
    while ($row = $employee_result->fetch_assoc()) {
        $valid_employee_ids[] = $row['employee_id'];
    }
}

// Add cash advance
if (isset($_POST['add'])) {
    $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $advance_amount = isset($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : null;

    if (!empty($employee_id) && $advance_amount > 0) {
        if (!in_array($employee_id, $valid_employee_ids)) {
            $_SESSION['error_message'] = "Invalid Employee ID! Please use a registered Employee ID.";
        } else {
            // Get the current month and year
            $current_month = date('m');
            $current_year = date('Y');

            // Check if the employee already has a cash advance this month
            $stmt = $conn->prepare("
                SELECT SUM(advance_amount) AS total_advance 
                FROM cash_advances 
                WHERE employee_id = ? 
                  AND MONTH(date_issued) = ? 
                  AND YEAR(date_issued) = ?
            ");
            $stmt->bind_param("sii", $employee_id, $current_month, $current_year);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $current_total = $row['total_advance'] ? $row['total_advance'] : 0;

            if ($current_total + $advance_amount > 5000) {
                $_SESSION['error_message'] = "Cannot grant cash advance. Total for this month exceeds 5,000 pesos.";
            } elseif ($current_total > 0) {
                $_SESSION['error_message'] = "This employee has already been granted a cash advance this month.";
            } else {
                // Add the cash advance
                $stmt = $conn->prepare("INSERT INTO cash_advances (employee_id, advance_amount, date_issued) VALUES (?, ?, NOW())");
                $stmt->bind_param("sd", $employee_id, $advance_amount);
                $stmt->execute();
                $_SESSION['success_message'] = "Cash advance added successfully!";
            }

            $stmt->close();
        }
    } else {
        $_SESSION['error_message'] = "Error: All fields are required, and the amount must be positive!";
    }
    header("Location: cash-advance.php");
    exit();
}

// Update cash advance
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $advance_amount = floatval($_POST['advance_amount']);

    if ($advance_amount > 0) {
        // Fetch the employee ID associated with the cash advance
        $stmt = $conn->prepare("SELECT employee_id FROM cash_advances WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cash_advance = $result->fetch_assoc();
        $employee_id = $cash_advance['employee_id'];
        $stmt->close();

        // Get the current month and year
        $current_month = date('m');
        $current_year = date('Y');

        // Calculate the total cash advance for this employee (excluding the current record)
        $stmt = $conn->prepare("
            SELECT SUM(advance_amount) AS total_advance
            FROM cash_advances
            WHERE employee_id = ?
              AND MONTH(date_issued) = ?
              AND YEAR(date_issued) = ?
              AND id != ?
        ");
        $stmt->bind_param("siii", $employee_id, $current_month, $current_year, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_total = $row['total_advance'] ? $row['total_advance'] : 0;
        $stmt->close();

        // Check if the updated amount exceeds the 5,000-peso limit
        if ($current_total + $advance_amount > 5000) {
            $_SESSION['error_message'] = "Cannot update cash advance. Total for this month exceeds 5,000 pesos.";
        } else {
            // Update the cash advance
            $stmt = $conn->prepare("UPDATE cash_advances SET advance_amount = ? WHERE id = ?");
            $stmt->bind_param("di", $advance_amount, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = "Cash advance updated successfully!";
        }
    } else {
        $_SESSION['error_message'] = "Advance amount must be positive!";
    }

    header("Location: cash-advance.php");
    exit();
}



// Delete cash advance
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM cash_advances WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success_message'] = "Cash advance deleted successfully!";
    header("Location: cash-advance.php");
    exit();
}

// Fetch cash advances
$sql = "
    SELECT 
        ca.id,
        ca.employee_id,
        CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
        ca.advance_amount,
        ca.date_issued
    FROM cash_advances ca
    LEFT JOIN employees e ON ca.employee_id = e.employee_id
";
$result = $conn->query($sql);
$cashAdvances = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style-cash-advance.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
    <link rel="stylesheet" href="../style/style-logout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <title>Cash Advance</title>

    <script>
        function openModal(id, employeeId, amount) {
            document.getElementById("editModal").style.display = "flex";
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_employee_id").value = employeeId;
            document.getElementById("edit_advance_amount").value = amount;
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
            <li style="    background-color: #f3feff;
                            border-radius: 7px 0px 0px 7px; 
                            text-decoration: none;
                            height: 27px;
                            font-size: 18px;
                            transition: color 0.3s ease; ">
                <a href="cash-advance.php" style=" color: black; transition: color 0.3s ease;"><i class="fas fa-money-check-alt"></i>Cash Advance</a>
            </li>
            <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
            <li><a href="taxDeduction.php"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>


            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

        </ul>
    </div>

    <div class="content">
        <div class="contentBody">
            <div class="top-bar">
                <h1>Cash Advance</h1>
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

            <form method="POST" action="cash-advance.php">
                <label for="employee_id">Employee ID:</label>
                <input type="number" id="employee_id" name="employee_id" required>

                <label for="advance_amount">Advance Amount:</label>
                <input type="number" id="advance_amount" name="advance_amount" step="0.01" required>

                <button type="submit" name="add">Add Cash Advance</button>
            </form>

            <table>
                <tr>
                    <th>Date Issued</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($cashAdvances as $advance): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($advance['date_issued']); ?></td>
                        <td><?php echo htmlspecialchars($advance['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($advance['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($advance['advance_amount'], 2)); ?></td>
                        <td>
                            <button onclick="openModal('<?php echo $advance['id']; ?>', '<?php echo htmlspecialchars($advance['employee_id']); ?>', <?php echo $advance['advance_amount']; ?>)" class="edit-button">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="cash-advance.php?delete_id=<?php echo $advance['id']; ?>" onclick="return confirm('Are you sure you want to delete this cash advance?')">
                                <button class="delete-button">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Edit Cash Advance</h2>
                <form method="POST" action="cash-advance.php">
                    <input type="hidden" id="edit_id" name="id">
                    <label for="edit_advance_amount">Advance Amount:</label>
                    <input type="number" id="edit_advance_amount" name="advance_amount" step="0.01" required>

                    <button type="submit" name="update">Update</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const successMessage = document.getElementById("success-message");
            const errorMessage = document.getElementById("error-message");
            if (successMessage) {
                successMessage.style.display = "block";
                setTimeout(() => successMessage.style.display = "none", 5000); // Hide after 5 seconds
            }
            if (errorMessage) {
                errorMessage.style.display = "block";
                setTimeout(() => errorMessage.style.display = "none", 5000); // Hide after 5 seconds
            }
        });
    </script>
</body>

</html>