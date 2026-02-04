-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2024 at 11:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coffeeshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_accounts`
--

CREATE TABLE `admin_accounts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_main_admin` tinyint(1) DEFAULT 0,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `email` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_accounts`
--

INSERT INTO `admin_accounts` (`id`, `username`, `password_hash`, `is_main_admin`, `firstname`, `lastname`, `position`, `email`) VALUES
(2, 'adminaq', '$2y$10$cXn01BOTL83q7JnobsG6FO7kIf5mQ26SAIL19MWKo3FuZhVPDkMH6', 1, '', '', '', ''),
(8, 'vincy', '$2y$10$9MM/ljdraJD9JjV8b/QdGO0wUBl.bnfPveQLVxx.CuUBY57g0a7ii', 0, 'Vincent', 'Pogi', 'Manager', 'vs@gmail.com'),
(10, 'francymapagmahal', '$2y$10$BTSB.chhu00TnUkrwkmNYuhjCnz2sxkz0CvlzB2Om9crXTPKEo2ZS', 0, 'Francy', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_advances`
--

CREATE TABLE `cash_advances` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `advance_amount` decimal(10,2) NOT NULL,
  `date_issued` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_advances`
--

INSERT INTO `cash_advances` (`id`, `employee_id`, `advance_amount`, `date_issued`) VALUES
(21, '98945', 5000.00, '2024-11-20 04:07:36'),
(22, '98947', 1000.00, '2024-11-27 15:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT '12345',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `email`, `password`, `phone`, `address`, `photo`, `firstname`, `lastname`, `birthdate`, `schedule`, `gender`, `position`, `member_since`, `status`) VALUES
(98940, 'vincyz05@gmail.com', '12345', '09123456789', 'QC', 'uploads/02220003970.jpg', 'Vincent', 'Uchiha', NULL, NULL, NULL, '14', '2024-11-20', 'active'),
(98941, 'vgg12345678@gmail.com', '12345', '09124342352', 'QC', 'uploads/Screenshot 2024-01-09 223338.png', 'Sasuke', 'Uchiha', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98942, 'yabai@gmail.com', '12345', '09235434753', 'Bind City', 'uploads/Screenshot 2024-02-19 225625.png', 'Huey', 'Cypher', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98943, 'mapagmahal@gmail.com', '12345', '12345678910', 'Icebox', 'uploads/Screenshot 2024-02-01 231448.png', 'Stephen', 'Xerofang', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98944, 'oreocheesecake@gmail.com', '12345', '09564587664', 'Caloocan City', 'uploads/Screenshot 2024-04-01 014828.png', 'Oreoffin', 'oreocat', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98945, 'sasuke05@gmail.com', '12345', '09878788787', 'Konoha Village', 'uploads/Sasuke-Uchiha-720x405.jpg', 'Sasuke', 'Uchiha', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98946, 'milk@gmail.com', '12345', '09323423452', 'QC', 'uploads/ice cream_milk tea.png', 'Milk', 'Tea', NULL, NULL, NULL, '13', '2024-11-20', 'active'),
(98947, 'lourds@gmail.com', '12345', '09045678900', 'yyyyyyy', 'uploads/ITPM CRITERIA (1).docx', 'christopher go', 'luna', NULL, NULL, NULL, '14', '2024-11-27', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `employee_schedule`
--

CREATE TABLE `employee_schedule` (
  `schedule_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_schedule`
--

INSERT INTO `employee_schedule` (`schedule_id`, `employee_id`, `shift_start`, `shift_end`) VALUES
(15, 98929, '05:00:00', '22:48:00'),
(16, 98930, '10:00:00', '20:00:00'),
(16, 98939, '10:00:00', '20:00:00'),
(16, 98940, '10:00:00', '20:00:00'),
(16, 98941, '10:00:00', '20:00:00'),
(15, 98942, '05:00:00', '22:48:00'),
(16, 98944, '10:00:00', '20:00:00'),
(16, 98945, '10:00:00', '20:00:00'),
(12, 98946, '13:51:00', '14:51:00'),
(16, 98947, '10:00:00', '20:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `employee_time_log`
--

CREATE TABLE `employee_time_log` (
  `time_log_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_time_log`
--

INSERT INTO `employee_time_log` (`time_log_id`, `employee_id`, `date`, `time_in`, `time_out`, `status`, `shift_start`, `shift_end`) VALUES
(151, 98940, '2024-11-20', '02:28:42', '04:37:45', 'on_time', NULL, NULL),
(152, 98942, '2024-11-20', '02:47:17', NULL, 'on_time', NULL, NULL),
(153, 98941, '2024-11-20', '02:51:26', NULL, 'on_time', NULL, NULL),
(155, 98944, '2024-11-20', '03:02:53', NULL, 'on_time', NULL, NULL),
(156, 98945, '2024-11-20', '03:06:16', '04:31:49', 'early', NULL, NULL),
(157, 98946, '2024-11-20', '03:09:06', '04:32:22', 'early', NULL, NULL),
(158, 98940, '2024-11-27', '13:51:00', '14:55:58', 'late', NULL, NULL),
(159, 98941, '2024-11-27', '14:54:33', NULL, 'late', NULL, NULL),
(160, 98942, '2024-11-27', '15:54:43', '15:57:38', 'late', NULL, NULL),
(161, 98947, '2024-11-27', '15:56:56', NULL, 'late', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_time_log_history`
--

CREATE TABLE `employee_time_log_history` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `permanent_employee_id` int(11) NOT NULL,
  `permanent_employee_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_time_log_history`
--

INSERT INTO `employee_time_log_history` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `status`, `shift_start`, `shift_end`, `permanent_employee_id`, `permanent_employee_name`) VALUES
(29, NULL, '2024-11-19', '23:51:35', '23:51:38', 'on_time', NULL, NULL, 0, ''),
(30, NULL, '2024-11-19', '20:49:30', '23:43:29', 'on_time', NULL, NULL, 0, ''),
(31, 98942, '2024-11-20', '02:47:17', NULL, 'on_time', NULL, NULL, 0, ''),
(32, 98941, '2024-11-20', '02:51:26', NULL, 'on_time', NULL, NULL, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `inactive_employees`
--

CREATE TABLE `inactive_employees` (
  `employee_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime_records`
--

CREATE TABLE `overtime_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date_issued` date NOT NULL,
  `hours_worked` float NOT NULL,
  `minutes_worked` int(11) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `total_overtime_pay` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_records`
--

INSERT INTO `overtime_records` (`id`, `employee_id`, `date_issued`, `hours_worked`, `minutes_worked`, `hourly_rate`, `total_overtime_pay`) VALUES
(2, 98941, '2024-11-27', 2, 30, 100.00, 250.00),
(4, 98947, '2024-11-27', 16, 1, 120.00, 1922.00);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `gross` decimal(10,2) NOT NULL,
  `deduction` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `rate_per_hour` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_title`, `rate_per_hour`) VALUES
(13, 'Manager', 100.00),
(14, 'Barista', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `time_in`, `time_out`) VALUES
(12, '13:51:00', '14:51:00'),
(15, '05:00:00', '22:48:00'),
(16, '10:00:00', '20:00:00'),
(18, '04:00:00', '16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tax_deductions`
--

CREATE TABLE `tax_deductions` (
  `id` int(11) NOT NULL,
  `tax_name` varchar(255) NOT NULL,
  `deduction_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_deductions`
--

INSERT INTO `tax_deductions` (`id`, `tax_name`, `deduction_amount`) VALUES
(12, 'Philhealth', 500.00),
(13, 'SSS', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('admin','employee') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_schedule`
--
ALTER TABLE `employee_schedule`
  ADD PRIMARY KEY (`employee_id`,`schedule_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `employee_time_log`
--
ALTER TABLE `employee_time_log`
  ADD PRIMARY KEY (`time_log_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_time_log_history`
--
ALTER TABLE `employee_time_log_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_employee_time_log_employee` (`employee_id`);

--
-- Indexes for table `inactive_employees`
--
ALTER TABLE `inactive_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `overtime_records`
--
ALTER TABLE `overtime_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `tax_deductions`
--
ALTER TABLE `tax_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_advances`
--
ALTER TABLE `cash_advances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98948;

--
-- AUTO_INCREMENT for table `employee_time_log`
--
ALTER TABLE `employee_time_log`
  MODIFY `time_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `employee_time_log_history`
--
ALTER TABLE `employee_time_log_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `overtime_records`
--
ALTER TABLE `overtime_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tax_deductions`
--
ALTER TABLE `tax_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `employee_schedule`
--
ALTER TABLE `employee_schedule`
  ADD CONSTRAINT `employee_schedule_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_time_log`
--
ALTER TABLE `employee_time_log`
  ADD CONSTRAINT `employee_time_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_time_log_history`
--
ALTER TABLE `employee_time_log_history`
  ADD CONSTRAINT `fk_employee_time_log_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `inactive_employees`
--
ALTER TABLE `inactive_employees`
  ADD CONSTRAINT `inactive_employees_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`),
  ADD CONSTRAINT `inactive_employees_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`);

--
-- Constraints for table `overtime_records`
--
ALTER TABLE `overtime_records`
  ADD CONSTRAINT `overtime_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
