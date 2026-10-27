-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2026 at 01:37 PM
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
-- Database: `rent_tracking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `balance_record`
--

CREATE TABLE `balance_record` (
  `balance_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `total_rent_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outstanding_balance` decimal(10,2) GENERATED ALWAYS AS (`total_rent_due` - `total_paid`) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('Pending','Confirmed') NOT NULL DEFAULT 'Pending',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmation_timestamp` timestamp NULL DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL
) ;

--
-- Triggers `payment`
--
DELIMITER $$
CREATE TRIGGER `trg_payment_protect_delete` BEFORE DELETE ON `payment` FOR EACH ROW BEGIN
  IF OLD.`status` = 'Confirmed' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Confirmed payments cannot be deleted';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payment_protect_update` BEFORE UPDATE ON `payment` FOR EACH ROW BEGIN
  IF OLD.`status` = 'Confirmed' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Confirmed payments cannot be changed';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `rent_charge`
--

CREATE TABLE `rent_charge` (
  `charge_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `billing_month` date NOT NULL COMMENT 'always the 1st of the month',
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tenant`
--

CREATE TABLE `tenant` (
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `house_number` varchar(20) NOT NULL,
  `expected_monthly_rent` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Triggers `tenant`
--
DELIMITER $$
CREATE TRIGGER `trg_tenant_create_balance` AFTER INSERT ON `tenant` FOR EACH ROW BEGIN
  INSERT INTO `balance_record` (`tenant_id`) VALUES (NEW.`tenant_id`);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `role` enum('Landlord','Tenant') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `phone_no`, `role`, `created_at`) VALUES
(2, 'landlord1', '$2y$10$hqPKOP5TPXv.xc1YGSv3b.66xJp0SmDbL1tfmIaVkBMoxFGpTQ0nW', 'Mwenda Neema', 'simplynk@gmail.com', '0712345678', 'Landlord', '2026-10-27 12:06:56');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_paid_arrears_report`
-- (See below for the actual view)
--
CREATE TABLE `v_paid_arrears_report` (
`tenant_id` int(11)
,`full_name` varchar(100)
,`house_number` varchar(20)
,`expected_monthly_rent` decimal(10,2)
,`total_rent_due` decimal(10,2)
,`total_paid` decimal(10,2)
,`outstanding_balance` decimal(10,2)
,`payment_status` varchar(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_payment_history`
-- (See below for the actual view)
--
CREATE TABLE `v_payment_history` (
`payment_id` int(11)
,`user_id` int(11)
,`tenant_id` int(11)
,`amount_paid` decimal(10,2)
,`payment_date` date
,`status` enum('Pending','Confirmed')
,`recorded_at` timestamp
,`confirmation_timestamp` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_paid_arrears_report`
--
DROP TABLE IF EXISTS `v_paid_arrears_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_paid_arrears_report`  AS SELECT `t`.`tenant_id` AS `tenant_id`, `u`.`full_name` AS `full_name`, `t`.`house_number` AS `house_number`, `t`.`expected_monthly_rent` AS `expected_monthly_rent`, `b`.`total_rent_due` AS `total_rent_due`, `b`.`total_paid` AS `total_paid`, `b`.`outstanding_balance` AS `outstanding_balance`, CASE WHEN `b`.`outstanding_balance` <= 0 THEN 'Paid' ELSE 'Arrears' END AS `payment_status` FROM ((`tenant` `t` join `users` `u` on(`u`.`user_id` = `t`.`user_id`)) join `balance_record` `b` on(`b`.`tenant_id` = `t`.`tenant_id`)) WHERE `t`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `v_payment_history`
--
DROP TABLE IF EXISTS `v_payment_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_payment_history`  AS SELECT `p`.`payment_id` AS `payment_id`, `t`.`user_id` AS `user_id`, `p`.`tenant_id` AS `tenant_id`, `p`.`amount_paid` AS `amount_paid`, `p`.`payment_date` AS `payment_date`, `p`.`status` AS `status`, `p`.`recorded_at` AS `recorded_at`, `p`.`confirmation_timestamp` AS `confirmation_timestamp` FROM (`payment` `p` join `tenant` `t` on(`t`.`tenant_id` = `p`.`tenant_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `balance_record`
--
ALTER TABLE `balance_record`
  ADD PRIMARY KEY (`balance_id`),
  ADD UNIQUE KEY `uq_balance_tenant` (`tenant_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_payment_tenant` (`tenant_id`),
  ADD KEY `fk_payment_confirmed_by` (`confirmed_by`);

--
-- Indexes for table `rent_charge`
--
ALTER TABLE `rent_charge`
  ADD PRIMARY KEY (`charge_id`),
  ADD UNIQUE KEY `uq_charge_tenant_month` (`tenant_id`,`billing_month`);

--
-- Indexes for table `tenant`
--
ALTER TABLE `tenant`
  ADD PRIMARY KEY (`tenant_id`),
  ADD UNIQUE KEY `uq_tenant_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `balance_record`
--
ALTER TABLE `balance_record`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rent_charge`
--
ALTER TABLE `rent_charge`
  MODIFY `charge_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenant`
--
ALTER TABLE `tenant`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `balance_record`
--
ALTER TABLE `balance_record`
  ADD CONSTRAINT `fk_balance_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenant` (`tenant_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_payment_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenant` (`tenant_id`);

--
-- Constraints for table `rent_charge`
--
ALTER TABLE `rent_charge`
  ADD CONSTRAINT `fk_charge_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenant` (`tenant_id`);

--
-- Constraints for table `tenant`
--
ALTER TABLE `tenant`
  ADD CONSTRAINT `fk_tenant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
