<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
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
}

.logo-box img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

/* logo text */
.logo-text {
    font-size: 18px;
    font-weight: bold;
    white-space: nowrap;
    transition: opacity .2s ease;
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
    transition: margin-left .3s ease;
}

.main.collapsed {
    margin-left: 72px;
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

    <div class="sidebar-header">
        <div class="logo-box">
            <img src="img/letterLogo.png" alt="Logo">
            <div class="logo-text">LOURD'S Café</div>
        </div>
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <ul>
        <div class="section-title">Reports</div>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>

        <div class="section-title">Employee Management</div>
        <li><a href="#"><i class="fas fa-laptop"></i><span>Attendance</span></a></li>
        <li><a href="#"><i class="fas fa-users"></i><span>Employee List</span></a></li>
        <li><a href="#"><i class="fas fa-user-times"></i><span>Inactive Employees</span></a></li>
        <li><a href="#"><i class="fas fa-briefcase"></i><span>Positions</span></a></li>

        <div class="section-title">Time Management</div>
        <li><a href="#"><i class="fas fa-calendar-check"></i><span>Schedules</span></a></li>
        <li><a href="#"><i class="fas fa-address-book"></i><span>Employee Schedule</span></a></li>

        <div class="section-title">Payroll Management</div>
        <li><a href="#"><i class="fas fa-envelope"></i><span>Payroll</span></a></li>
        <li><a href="#"><i class="fas fa-money-check-alt"></i><span>Cash Advance</span></a></li>
    </ul>
</aside>

<!-- ================= MAIN ================= -->
<div class="main" id="main">

    <div class="top-bar">
        <h1>Dashboard</h1>
        <img src="img/lourds_cafe.jpg" width="50" style="border-radius:50%">
    </div>

    <div class="cards">
        <div class="card"><i class="fas fa-users"></i><h2>7</h2><p>Employees</p></div>
        <div class="card"><i class="fas fa-clock"></i><h2>6</h2><p>On Time</p></div>
        <div class="card"><i class="fas fa-hourglass-half"></i><h2>1</h2><p>Late</p></div>
        <div class="card"><i class="fas fa-minus-circle"></i><h2>0</h2><p>Absent</p></div>
    </div>

    <h2>Payroll Overview</h2>

    <div class="summary-cards">
        <div class="summary-card">
            <div class="icon-box green"><i class="fas fa-wallet"></i></div>
            <div><p>Total Payroll</p><h3>₱125,430</h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box red"><i class="fas fa-user-slash"></i></div>
            <div><p>Inactive Employees</p><h3>4</h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box blue"><i class="fas fa-briefcase"></i></div>
            <div><p>Positions</p><h3>8</h3></div>
        </div>
        <div class="summary-card">
            <div class="icon-box teal"><i class="fas fa-calendar-alt"></i></div>
            <div><p>Schedules</p><h3>6</h3></div>
        </div>
    </div>

    <div class="year-filter">
        <div class="year-box">
            <i class="fas fa-calendar"></i>
            <select>
                <option>2024</option>
                <option selected>2025</option>
                <option>2026</option>
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

new Chart(document.getElementById('attendanceChart'),{
    type:'bar',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets:[
            {label:'On Time',data:[4,5,6,7,6,5,6,7,6,5,6,7],backgroundColor:'green'},
            {label:'Late',data:[1,1,0,1,0,1,0,1,0,1,0,1],backgroundColor:'gray'}
        ]
    }
});
</script>

</body>
</html>
