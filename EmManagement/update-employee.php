<?php
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../db/db_connection.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Employee ID is required.');
}

$employee_id = $_GET['id'];

$sql = "SELECT e.employee_id, CONCAT(e.firstname, ' ', e.lastname) AS name, s.shift_start, s.shift_end
        FROM employees e
        LEFT JOIN employee_schedule s ON e.employee_id = s.employee_id
        WHERE e.employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('Employee not found.');
}

$employee = $result->fetch_assoc();

$shift_start = $employee['shift_start'] ? $employee['shift_start'] : '';
$shift_end = $employee['shift_end'] ? $employee['shift_end'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shift_start = $_POST['shift_start'];
    $shift_end = $_POST['shift_end'];

    $sql_check = "SELECT * FROM employee_schedule WHERE employee_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $employee_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $insert_sql = "INSERT INTO employee_schedule (employee_id, shift_start, shift_end)
                       VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iss", $employee_id, $shift_start, $shift_end);
        $insert_stmt->execute();
        $_SESSION['message'] = "Schedule added successfully.";
    } else {
        $sql_update = "UPDATE employee_schedule
                       SET shift_start = ?, shift_end = ?
                       WHERE employee_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $shift_start, $shift_end, $employee_id);
        $stmt_update->execute();
        $_SESSION['message'] = "Schedule updated successfully.";
    }

    header("Location: update-employee.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Employee Schedule</title>
</head>

<body>
    <?php
    if (isset($employee)) {
        echo "<h1>Update Schedule for " . htmlspecialchars($employee['name']) . "</h1>";
    }
    ?>

    <?php
    if (isset($_SESSION['message'])) {
        echo '<p style="color: green;">' . $_SESSION['message'] . '</p>';
        unset($_SESSION['message']); 
    }
    ?>

    <form action="update-employee.php?id=<?php echo $employee_id; ?>" method="POST">
        <label for="shift_start">Shift Start: </label>
        <input type="time" name="shift_start" value="<?php echo $shift_start; ?>" required><br><br>

        <label for="shift_end">Shift End: </label>
        <input type="time" name="shift_end" value="<?php echo $shift_end; ?>" required><br><br>

        <input type="submit" value="Update Schedule">
    </form>

    <br>

    <a href="./dashboard.php">Back to Admin Dashboard</a>

</body>

</html>