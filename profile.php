<html>

<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <title>Manage Employees</title>
    <link rel="stylesheet" href="style/style-profile.css">
</head>

<body>
    <div class="sidebar">
        <a href="profile.php">
            <img alt="Company Logo" height="80" src="fb63b6b5-9d67-45d4-a428-51f2692f4cdb.jpeg" width="80" />
        </a>
        <h2>Lourds Cafe</h2>
        <ul>
            <li class="active">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            <li>
                Employee Management
            </li>
            <li>
                <a href="attendance.php">
                    <i class="fas fa-calendar-check"></i>Attendance
                </a>
            </li>
            <li>
                <i class="fas fa-user"></i>Employee

                <ul>
                    <li>
                        <a href="employee-list.php">
                            Employee List
                        </a>
                    </li>
                    <li>
                        <a href="employee-schedule.php">
                            Employee Schedule
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="payroll.php">
                    <i class="fas fa-money-check-alt"></i>Payroll
                </a>
            </li>
        </ul>
    </div>
    <div class="content">
        <h1>New Employee</h1>
        <div class="form-container">
            <img alt="Profile Picture" height="100" src="fb63b6b5-9d67-45d4-a428-51f2692f4cdb.jpeg" width="100" />
            <button>Upload Photo</button>
            <form>
                <div class="form-group">
                    <label for="first-name">First Name *</label>
                    <input id="first-name" name="first-name" required="" type="text" />
                </div>
                <div class="form-group">
                    <label for="middle-name">
                        Middle Name *
                    </label>
                    <input id="middle-name" name="middle-name" required="" type="text" />
                </div>
                <div class="form-group">
                    <label for="last-name">
                        Last Name *
                    </label>
                    <input id="last-name" name="last-name" required="" type="text" />
                </div>
                <div class="form-group">
                    <label for="employee-id">
                        Employee ID *
                    </label>
                    <input id="employee-id" name="employee-id" required="" type="text" />
                </div>
                <div class="form-group">
                    <label for="email">
                        Email *
                    </label>
                    <input id="email" name="email" required="" type="email" />
                </div>
                <div class="form-group">
                    <label for="employee-type">
                        Employee Type *
                    </label>
                    <select id="employee-type" name="employee-type" required="">
                        <option value="">
                            -Select-
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="employee-status">
                        Employee Status *
                    </label>
                    <select id="employee-status" name="employee-status" required="">
                        <option value="">
                            -Select-
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="employee-end-date">
                        Employee End Date *
                    </label>
                    <input id="employee-end-date" name="employee-end-date" required="" type="date" />
                </div>
                <div class="form-group">
                    <label for="date-of-hire">
                        Date of Hire *
                    </label>
                    <input id="date-of-hire" name="date-of-hire" required="" type="date" />
                </div>
                <div class="form-group" style="width: 100%;">
                    <input type="submit" value="add employee" />
                </div>
            </form>
        </div>
    </div>
</body>

</html>