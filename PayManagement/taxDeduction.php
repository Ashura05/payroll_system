<?php
session_start();

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all deductions
$result = $conn->query("SELECT * FROM tax_deductions");
$taxDeductions = $result->fetch_all(MYSQLI_ASSOC);

// Handle the delete action
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM tax_deductions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: taxDeduction.php"); // Redirect to prevent form resubmission
    exit();
}

// Handle the update action
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $tax_name = $_POST['tax_name'];
    $deduction_amount = $_POST['deduction_amount'];

    if ($deduction_amount <= 0) {
        echo "<script>alert('Deduction amount must be a positive number!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE tax_deductions SET tax_name = ?, deduction_amount = ? WHERE id = ?");
        $stmt->bind_param("sdi", $tax_name, $deduction_amount, $id);
        $stmt->execute();
        header("Location: taxDeduction.php"); // Redirect to refresh the page
        exit();
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Deductions</title>
    <link rel="stylesheet" href="../style/style-taxDeduct.css">
    <link rel="stylesheet" href="../style/style-sidebar.css">
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
                <li><a href="../EmManagement/attendance.php"><i class="fa fa-laptop" aria-hidden="true"></i>Attendance</a></li>
                <li><a href="../EmManagement/employee-list.php"><i class="fa fa-users" aria-hidden="true"></i>Employee List</a></li>
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
            <li><a href="overtime.php"><i class="fas fa-clock" aria-hidden="true"></i>Overtime</a></li>
            <li style="background-color: #f3feff;
                        border-radius: 7px 0px 0px 7px;">
                <a href="taxDeduction.php" style=" color: black;"><i class="fa fa-minus-square" aria-hidden="true"></i>Deductions</a></li><br>
            <li><a href="../logout.php" class="logout-link" onclick="return confirm('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

<div class="content">
    <div class="contentBody">
        <div class="top-bar">
            <h1>Deductions</h1>
        </div>

        <div class="deductions-data">
            <h2>Deductions Overview</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tax Name</th>
                        <th>Deduction Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($taxDeductions as $deduction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($deduction['tax_name']); ?></td>
                            <td>
                                <?php 
                                    if ($deduction['tax_name'] == 'SSS' || $deduction['tax_name'] == 'Philhealth') {
                                        echo number_format($deduction['deduction_amount'], 2) . '%'; 
                                    } else {
                                        echo number_format($deduction['deduction_amount'], 2); 
                                    }
                                ?>
                            </td>
                            <td>
                                <button onclick="openModal(<?php echo $deduction['id']; ?>, '<?php echo htmlspecialchars($deduction['tax_name']); ?>', <?php echo $deduction['deduction_amount']; ?>)" class="edit-button">
                                    Edit
                                </button>
                                <a href="taxDeduction.php?delete_id=<?php echo $deduction['id']; ?>" onclick="return confirm('Are you sure you want to delete this deduction?')">
                                    <button class="delete-button">
                                        Delete
                                    </button>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Deduction</h2>
        <form method="POST" action="taxDeduction.php">
            <input type="hidden" id="edit_id" name="id">
            <label for="edit_tax_name">Tax Name:</label>
            <input type="text" id="edit_tax_name" name="tax_name" required>
            <label for="edit_deduction_amount">Deduction Amount:</label>
            <input type="number" id="edit_deduction_amount" name="deduction_amount" step="0.01" required>
            <button type="submit" name="update">Update</button>
        </form>
    </div>
</div>

<script>
    function openModal(id, name, amount) {
        const modal = document.getElementById("editModal");
        modal.style.display = "flex";
        document.getElementById("edit_id").value = id;
        document.getElementById("edit_tax_name").value = name;
        document.getElementById("edit_deduction_amount").value = amount;
    }

    function closeModal() {
        const modal = document.getElementById("editModal");
        modal.style.display = "none";
    }

    // Close modal when clicking outside the content
    window.onclick = function (event) {
        const modal = document.getElementById("editModal");
        if (event.target === modal) {
            closeModal();
        }
    };
</script>

</body>
</html>
