-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 01:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `taxxpert`
--
CREATE DATABASE IF NOT EXISTS `taxxpert` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `taxxpert`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 04, 2025 at 10:07 AM
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `admin`:
--

--
-- Truncate table before insert `admin`
--

TRUNCATE TABLE `admin`;
--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`, `name`, `created_at`) VALUES
(1, 'akashprabu@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'naan than admin', '2025-09-28 08:26:33');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 10:22 AM
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `gstin` varchar(15) NOT NULL,
  `pan` varchar(10) NOT NULL,
  `place_of_supply` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `gstin` (`gstin`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `companies`:
--

--
-- Truncate table before insert `companies`
--

TRUNCATE TABLE `companies`;
--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `gstin`, `pan`, `place_of_supply`, `email`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(2, 'Demo Company', '07AABCU9603R1ZM', 'AABCU9603R', 'Delhi', 'demo@company.com', '$2y$10$CSuJoxox15B3syy/BouoGuqNsXJZYkh6ddPZApYUs5EQba9bd8SmO', NULL, '2025-09-28 08:51:46', '2025-09-28 08:51:46'),
(3, 'Xemzo', '33QWERT1234A1Z5', 'QWERT1234A', 'Tamil Nadu', 'akashprabu2775@gmail.com', '$2y$10$je8iJhurf21IdFugmrHB2eg70PGQjMVVoLiaw80q9Wv4Cxgh/6U3e', 'company_3_1759486585.jpg', '2025-09-28 08:56:51', '2025-10-03 10:22:28'),
(4, 'mindtree', '123theirio', 'QWE234RJL', 'Tamil Nadu', 'MINDTREE1@gmail.com', '$2y$10$M.xYxltiZPkTDhHhB8zc8eS96mqrEfQO2jSXwrL9gu.ciYQQfSItO', NULL, '2025-09-28 11:34:00', '2025-09-28 11:34:00'),
(5, 'Accenture', '33QWERT1234A1Z7', 'QWGTT1234A', 'Tamil Nadu', 'mr.ajay4702@gmail.com', '$2y$10$TNmAqSn009Xs2PhFGNUZjuEvtOHWKnPTZlE4HBnlA1zCJSV4Grdby', NULL, '2025-09-29 08:20:09', '2025-09-29 08:20:09');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 07:55 AM
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `place_of_supply` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `customers`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `customers`
--

TRUNCATE TABLE `customers`;
--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `name`, `gstin`, `place_of_supply`, `created_at`) VALUES
(3, 3, 'kevin', '', 'Tamil Nadu', '2025-10-03 07:51:29'),
(4, 3, 'Ajay', '', 'Tamil Nadu', '2025-10-03 07:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 07:52 AM
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expenses_company_date` (`company_id`,`expense_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `expenses`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `expenses`
--

TRUNCATE TABLE `expenses`;
--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `company_id`, `expense_date`, `category`, `description`, `amount`, `created_at`) VALUES
(1, 4, '2025-09-28', 'Travel', 'travel', 50000.00, '2025-09-28 11:36:18'),
(2, 3, '2025-10-03', 'Rent', '', 10000.00, '2025-10-03 07:52:01'),
(3, 3, '2025-10-03', 'Salary', '', 2500.00, '2025-10-03 07:52:17');

-- --------------------------------------------------------

--
-- Table structure for table `gst_summary`
--
-- Creation: Sep 28, 2025 at 08:26 AM
--

DROP TABLE IF EXISTS `gst_summary`;
CREATE TABLE IF NOT EXISTS `gst_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `period_month` int(11) NOT NULL,
  `period_year` int(11) NOT NULL,
  `total_input_igst` decimal(15,2) DEFAULT 0.00,
  `total_input_cgst` decimal(15,2) DEFAULT 0.00,
  `total_input_sgst` decimal(15,2) DEFAULT 0.00,
  `total_output_igst` decimal(15,2) DEFAULT 0.00,
  `total_output_cgst` decimal(15,2) DEFAULT 0.00,
  `total_output_sgst` decimal(15,2) DEFAULT 0.00,
  `net_gst_payable` decimal(15,2) DEFAULT 0.00,
  `itc_carried_forward` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period` (`company_id`,`period_month`,`period_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `gst_summary`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `gst_summary`
--

TRUNCATE TABLE `gst_summary`;
-- --------------------------------------------------------

--
-- Table structure for table `income_tax_summary`
--
-- Creation: Sep 28, 2025 at 08:26 AM
--

DROP TABLE IF EXISTS `income_tax_summary`;
CREATE TABLE IF NOT EXISTS `income_tax_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `financial_year` varchar(9) NOT NULL,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `total_expenses` decimal(15,2) DEFAULT 0.00,
  `profit` decimal(15,2) DEFAULT 0.00,
  `income_tax_payable` decimal(15,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 25.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_financial_year` (`company_id`,`financial_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `income_tax_summary`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `income_tax_summary`
--

TRUNCATE TABLE `income_tax_summary`;
-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 04, 2025 at 10:52 AM
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `type` enum('gst_reminder','payment_due','tax_filing','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_company` (`company_id`,`is_read`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `notifications`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `notifications`
--

TRUNCATE TABLE `notifications`;
--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `company_id`, `type`, `title`, `message`, `is_read`, `due_date`, `created_at`) VALUES
(13, 4, 'gst_reminder', 'GST Filing Reminder', 'Last date for GST filing for this month is 20th. Please complete your invoice entries.', 0, '2025-10-03', '2025-09-28 11:34:34'),
(14, 4, 'payment_due', 'GST Payment Due', 'GST payment for the previous month is due on 25th.', 0, '2025-10-08', '2025-09-28 11:34:34'),
(39, 2, 'gst_reminder', 'GST Filing Reminder', 'Last date for GST filing for this month is 20th. Please complete your invoice entries.', 1, '2025-10-08', '2025-10-03 18:21:35'),
(40, 2, 'payment_due', 'GST Payment Due', 'GST payment for the previous month is due on 25th.', 1, '2025-10-13', '2025-10-03 18:21:35');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 07:48 AM
--

DROP TABLE IF EXISTS `purchase_invoices`;
CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `taxable_value` decimal(15,2) NOT NULL,
  `igst` decimal(15,2) DEFAULT 0.00,
  `cgst` decimal(15,2) DEFAULT 0.00,
  `sgst` decimal(15,2) DEFAULT 0.00,
  `total_gst` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `itc_eligible` tinyint(1) DEFAULT 1,
  `reverse_charge` tinyint(1) DEFAULT 0,
  `place_of_supply` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `idx_purchase_company_date` (`company_id`,`invoice_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `purchase_invoices`:
--   `company_id`
--       `companies` -> `id`
--   `supplier_id`
--       `suppliers` -> `id`
--

--
-- Truncate table before insert `purchase_invoices`
--

TRUNCATE TABLE `purchase_invoices`;
--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `company_id`, `supplier_id`, `invoice_number`, `invoice_date`, `taxable_value`, `igst`, `cgst`, `sgst`, `total_gst`, `total_amount`, `itc_eligible`, `reverse_charge`, `place_of_supply`, `created_at`) VALUES
(1, 3, 3, 'INV/2025/1001', '2025-10-03', 20000.00, 2400.00, 0.00, 0.00, 2400.00, 22400.00, 1, 1, 'Tiruvinamalai', '2025-10-03 07:47:04'),
(2, 3, 4, 'INV/2025/1002', '2025-10-03', 50000.00, 0.00, 0.00, 0.00, 0.00, 50000.00, 1, 0, 'Chennai', '2025-10-03 07:48:06');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 07:55 AM
--

DROP TABLE IF EXISTS `sales_invoices`;
CREATE TABLE IF NOT EXISTS `sales_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `taxable_value` decimal(15,2) NOT NULL,
  `igst` decimal(15,2) DEFAULT 0.00,
  `cgst` decimal(15,2) DEFAULT 0.00,
  `sgst` decimal(15,2) DEFAULT 0.00,
  `total_gst` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `place_of_supply` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_sales_company_date` (`company_id`,`invoice_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `sales_invoices`:
--   `company_id`
--       `companies` -> `id`
--   `customer_id`
--       `customers` -> `id`
--

--
-- Truncate table before insert `sales_invoices`
--

TRUNCATE TABLE `sales_invoices`;
--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `company_id`, `customer_id`, `invoice_number`, `invoice_date`, `taxable_value`, `igst`, `cgst`, `sgst`, `total_gst`, `total_amount`, `place_of_supply`, `created_at`) VALUES
(1, 3, 3, 'INV/2025/2001', '2025-10-03', 200.00, 0.00, 18.00, 18.00, 36.00, 236.00, 'Tamil Nadu', '2025-10-03 07:51:29'),
(2, 3, 4, 'INV/2025/2002', '2025-10-03', 3000.00, 0.00, 270.00, 270.00, 540.00, 3540.00, 'Tamil Nadu', '2025-10-03 07:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--
-- Creation: Sep 28, 2025 at 08:26 AM
-- Last update: Oct 03, 2025 at 07:48 AM
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `place_of_supply` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `suppliers`:
--   `company_id`
--       `companies` -> `id`
--

--
-- Truncate table before insert `suppliers`
--

TRUNCATE TABLE `suppliers`;
--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `name`, `gstin`, `place_of_supply`, `created_at`) VALUES
(3, 3, 'Colgate', '33ZXCVB3456Q1Z3', 'Tiruvinamalai', '2025-10-03 07:47:04'),
(4, 3, 'Himalayan', '33GHJKL5432U1Z5', 'Chennai', '2025-10-03 07:48:06');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gst_summary`
--
ALTER TABLE `gst_summary`
  ADD CONSTRAINT `gst_summary_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `income_tax_summary`
--
ALTER TABLE `income_tax_summary`
  ADD CONSTRAINT `income_tax_summary_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `purchase_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_invoices_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `sales_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
