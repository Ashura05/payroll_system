<<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Establish connection BEFORE using it
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "CoffeeShop";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Now you can safely run queries
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Total Payroll Query
$total_payroll = 0;
$result = $conn->query("SELECT SUM(gross - deduction) AS total FROM payroll WHERE YEAR(payroll_date) = $selected_year");
// ... rest of your code
if ($payroll_result) {
    $total_payroll = $payroll_result->fetch_assoc()['total'] ?? 0;
}

// 2. Total Rendered Hours (Filtered by Selected Year)
$total_hours = 0;
$hours_sql = "SELECT SUM(TIMESTAMPDIFF(HOUR, time_in, time_out)) AS total_hrs 
              FROM employee_time_log 
              WHERE YEAR(date) = $selected_year AND time_out IS NOT NULL";
$hours_result = $conn->query($hours_sql);
if ($hours_result) {
    $total_hours = $hours_result->fetch_assoc()['total_hrs'] ?? 0;
}

// --- TOP CARDS (Current Day / Today) ---

// Active Employees
$employee_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM employees WHERE status='active'")) ? $r->fetch_assoc()['cnt'] : 0;

// Early Today (Logic synced with attendance.php - uses specific employee shifts)
$early_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM employee_time_log t JOIN employee_schedule s ON t.employee_id = s.employee_id WHERE t.date = CURDATE() AND t.time_in < s.shift_start")) ? $r->fetch_assoc()['cnt'] : 0;

// On Time Today (Fixed 08:00 AM threshold as per your code)
$on_time_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM attendance WHERE TIME(time_in) <= '08:00:00' AND DATE(date)=CURDATE()")) ? $r->fetch_assoc()['cnt'] : 0;

// Late Today (Logic synced with attendance.php)
$late_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM employee_time_log t JOIN employee_schedule s ON t.employee_id = s.employee_id WHERE t.date = CURDATE() AND t.time_in > s.shift_start")) ? $r->fetch_assoc()['cnt'] : 0;

// Absent Today
$absent_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM employees WHERE status='active' AND employee_id NOT IN (SELECT employee_id FROM attendance WHERE DATE(date)=CURDATE())")) ? $r->fetch_assoc()['cnt'] : 0;


// --- SUMMARY CARDS (Yearly Totals) ---

// FIXED: Total Payroll for the Selected Year using 'payroll_date'
// --- FIXED PAYROLL SECTION ---
$total_payroll = 0;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// 1. Assign the query result to the variable $payroll_result
$payroll_result = $conn->query("SELECT SUM(gross - deduction) AS total FROM payroll WHERE YEAR(payroll_date) = $selected_year");

// 2. Now you can safely check if it is defined and has data
if ($payroll_result) {
    $row = $payroll_result->fetch_assoc();
    $total_payroll = $row['total'] ?? 0;
} else {
    // Optional: Log error if the query fails
    error_log("Payroll Query Failed: " . $conn->error);
}

$inactive_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM inactive_employees")) ? $r->fetch_assoc()['cnt'] : 0;
$positions_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM positions")) ? $r->fetch_assoc()['cnt'] : 0;
$schedules_count = ($r = $conn->query("SELECT COUNT(*) AS cnt FROM schedules")) ? $r->fetch_assoc()['cnt'] : 0;


// --- CHART DATA (Filtered by $selected_year) ---
$attendance_data = []; 
$early_data = [];
$late_data = [];

for ($m = 1; $m <= 12; $m++) {
    // Early
    $e = $conn->query("SELECT COUNT(*) AS cnt FROM employee_time_log t JOIN employee_schedule s ON t.employee_id = s.employee_id WHERE MONTH(t.date)=$m AND YEAR(t.date)=$selected_year AND t.time_in < s.shift_start");
    $early_data[] = $e->fetch_assoc()['cnt'];

    // On Time
    $o = $conn->query("SELECT COUNT(*) AS cnt FROM attendance WHERE MONTH(date)=$m AND YEAR(date)=$selected_year AND TIME(time_in) <= '08:00:00'");
    $attendance_data[] = $o->fetch_assoc()['cnt'];

    // Late
    $l = $conn->query("SELECT COUNT(*) AS cnt FROM attendance WHERE MONTH(date)=$m AND YEAR(date)=$selected_year AND TIME(time_in) > '08:00:00'");
    $late_data[] = $l->fetch_assoc()['cnt'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
/* ================= GLOBAL ================= */
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

/* ================= SIDEBAR ================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    background: #2fae9c;
    color: #fff;
    transition: width .3s ease;
    overflow: hidden;
    z-index: 1000;
}

/* collapsed width */
.sidebar.collapsed {
    width: 64px;
}


/* ===== SIDEBAR HEADER ===== */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
    height: 70px;
}

/* logo container (NO RESIZE OF IMAGE) */
.logo-box {
    display: flex;
    align-items: center;
    gap: 10px;
    overflow: hidden;
    text-decoration: none;
}

.logo-box img {
    display: none;
}

/* logo text */
.logo-text {
    font-size: 24px;
    font-weight: bold;
    white-space: nowrap;
    transition: opacity .2s ease;
    color: #fff;
}

/* hide logo text only */
.sidebar.collapsed .logo-text {
    opacity: 0;
}

/* toggle button */
.toggle-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
}

/* ===== SIDEBAR MENU ===== */
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar .section-title {
    font-size: 12px;
    opacity: .7;
    padding: 10px 20px;
    white-space: nowrap;
}

/* hide section titles only */
.sidebar.collapsed .section-title {
    display: none;
}

.sidebar li a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 20px;
    color: #fff;
    text-decoration: none;
    transition: background .2s;
    white-space: nowrap;
}

/* ICONS NEVER DISAPPEAR */
.sidebar li a i {
    font-size: 18px;
    min-width: 24px;
    text-align: center;
}

/* hide labels only */
.sidebar.collapsed li a span {
    display: none;
}

.sidebar li a:hover {
    background: rgba(255,255,255,0.15);
}

/* ================= MAIN CONTENT ================= */
.main {
    margin-left: 260px;
    padding: 25px;
    transition: margin-left .3s ease, width .3s ease;
    width: calc(100% - 260px);
}

.main.collapsed {
    margin-left: 64px;
    width: calc(100% - 64px);
}

/* ================= TOP BAR ================= */
.top-bar {
    background: #f1fcfb;
    padding: 15px 25px;
    border-radius: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* ================= DASH CARDS ================= */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
    gap: 20px;
    margin: 25px 0;
}

.card {
    background: #fff;
    border-radius: 18px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.card i {
    font-size: 36px;
    color: #aaa;
}

/* ================= SUMMARY ================= */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px,1fr));
    gap: 20px;
    margin: 25px 0;
}

.summary-card {
    background: #fff;
    border-radius: 18px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.icon-box {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.green { background: #4caf50; }
.red { background: #f44336; }
.blue { background: #3f51b5; }
.teal { background: #009688; }

/* ================= YEAR FILTER ================= */
.year-filter {
    display: flex;
    justify-content: flex-end;
    margin: 20px 0;
}

.year-box {
    background: #fff;
    padding: 10px 15px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

/* ================= CHART ================= */
.chart-box {
    background: #fff;
    padding: 20px;
    border-radius: 18px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}
</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar" id="sidebar">

    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="../dashboard.php" class="logo-box">
            <img src="img/letterLogo.png" alt="Company Logo" class="logo-img">
            <span class="logo-text" src="./dashboard.php">LOURD'S Café</span>
        </a>

        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <ul class="sidebar-menu">

        <!-- Reports -->
        <li class="section-title">Reports</li>
        <li>
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span class="link-text">Dashboard</span>
            </a>
        </li>

        <!-- Employee Management -->
        <li class="section-title">Employee Management</li>
        <li>
            <a href="./EmManagement/attendance.php">
                <i class="fa fa-laptop"></i>
                <span class="link-text">Attendance</span>
            </a>
        </li>
        <li class="active">
            <a href="./EmManagement/employee-list.php">
                <i class="fa fa-users"></i>
                <span class="link-text">Employee List</span>
            </a>
        </li>
        <li>
            <a href="./EmManagement/inactive-employees.php">
                <i class="fa fa-user-times"></i>
                <span class="link-text">Inactive Employees</span>
            </a>
        </li>
        <li>
            <a href="./EmManagement/positions.php">
                <i class="fa fa-briefcase"></i>
                <span class="link-text">Positions</span>
            </a>
        </li>

        <!-- Time Management -->
        <li class="section-title">Time Management</li>
        <li>
            <a href="./EmManagement/schedule_management.php">
                <i class="fas fa-calendar-check"></i>
                <span class="link-text">Schedules</span>
            </a>
        </li>
        <li>
            <a href="./EmManagement/employee-schedule.php">
                <i class="fa fa-address-book"></i>
                <span class="link-text">Employee Schedule</span>
            </a>
        </li>

        <!-- Payroll Management -->
        <li class="section-title">Payroll Management</li>
        <li>
            <a href="./PayManagement/payroll.php">
                <i class="fa fa-envelope"></i>
                <span class="link-text">Payroll</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/cash-advance.php">
                <i class="fas fa-money-check-alt"></i>
                <span class="link-text">Cash Advance</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/overtime.php">
                <i class="fas fa-clock"></i>
                <span class="link-text">Overtime</span>
            </a>
        </li>
        <li>
            <a href="../PayManagement/taxDeduction.php">
                <i class="fa fa-minus-square"></i>
                <span class="link-text">Deductions</span>
            </a>
        </li>

        <!-- Logout -->
        <li class="logout">
            <a href="../logout.php" onclick="return confirm('Are you sure you want to log out?');">
                <i class="fas fa-sign-out-alt"></i>
                <span class="link-text">Logout</span>
            </a>
        </li>

    </ul>
</aside>


<!-- ================= MAIN ================= -->
<div class="main" id="main">

    <div class="top-bar">
        <h1>Dashboard</h1>
        <img src="img/lourds_cafe.jpg" width="50" style="border-radius:50%">
    </div>

    <div class="cards">
        <div class="card"><i class="fas fa-users"></i><h2><?= $employee_count ?></h2><p>Employees</p></div>
        <div class="card"><i class="fas fa-arrow-up"></i><h2><?= $early_count ?></h2><p>Early</p></div>
        <div class="card"><i class="fas fa-clock"></i><h2><?= $on_time_count ?></h2><p>On Time</p></div>
        <div class="card"><i class="fas fa-hourglass-half"></i><h2><?= $late_count ?></h2><p>Late</p></div>
        <div class="card"><i class="fas fa-minus-circle"></i><h2><?= $absent_count ?></h2><p>Absent</p></div>
    </div>

    <h2>Payroll Overview</h2>

    <div class="summary-cards">
        <div class="summary-card">
            <div class="icon-box green"><i class="fas fa-wallet"></i></div>
            <div><p>Total Payroll</p><h3>₱<?= number_format($total_payroll, 2) ?></h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box red"><i class="fas fa-user-slash"></i></div>
            <div><p>Inactive Employees</p><h3><?= $inactive_count ?></h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box blue"><i class="fas fa-briefcase"></i></div>
            <div><p>Positions</p><h3><?= $positions_count ?></h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box teal"><i class="fas fa-calendar-alt"></i></div>
            <div><p>Schedules</p><h3><?= $schedules_count ?></h3></div>
        </div>
    </div>

<div class="year-filter">
    <div class="year-box">
        <i class="fas fa-calendar"></i>
     <select id="yearSelect" onchange="window.location.href='dashboard.php?year='+this.value">
    <?php 
    for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
        <option value="<?= $y ?>" <?= ($y == $selected_year) ? 'selected' : '' ?>><?= $y ?></option>
    <?php endfor; ?>
</select>
    </div>
</div>

    <div class="chart-box">
        <canvas id="attendanceChart"></canvas>
    </div>

</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('main').classList.toggle('collapsed');
}

const attendanceData = <?= json_encode($attendance_data) ?>;
const earlyData = <?= json_encode($early_data) ?>;
const lateData = <?= json_encode($late_data) ?>;

new Chart(document.getElementById('attendanceChart'),{
    type:'bar',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets:[
            {label:'Early', data:earlyData, backgroundColor:'#3f51b5'}, // Blue for Early
            {label:'On Time', data:attendanceData, backgroundColor:'#4caf50'}, // Green for On Time
            {label:'Late', data:lateData, backgroundColor:'#f44336'} // Red for Late
        ]
    }
});

function changeYear(year) {
    // Reloads the page with the year as a query parameter
    window.location.href = 'dashboard.php?year=' + year;
}
</script>

</body>
</html>

