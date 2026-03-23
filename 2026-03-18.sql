-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2026 at 11:58 AM
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
-- Database: `alhayiki_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `staff_profile_id` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `staff_profile_id`, `updated_by`, `field_changed`, `old_value`, `new_value`, `updated_at`, `created_at`) VALUES
(1, 3, 4, 'qid_expiry', NULL, '2026-04-18', '2026-03-14 08:58:58', '2026-03-14 09:10:16'),
(2, 3, 4, 'roles', 'SuperAdmin, Branch Manager, PRO', '', '2026-03-14 08:58:58', '2026-03-14 09:10:16'),
(3, 3, 4, 'roles', '', 'Super Admin', '2026-03-14 09:02:13', '2026-03-14 09:10:16'),
(4, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Active', 'Inactive', '2026-03-16 09:48:24', '2026-03-16 09:48:24'),
(5, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Inactive', 'Active', '2026-03-16 09:48:28', '2026-03-16 09:48:28'),
(6, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Active', 'Inactive', '2026-03-16 09:55:58', '2026-03-16 09:55:58'),
(7, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Inactive', 'Active', '2026-03-16 09:56:01', '2026-03-16 09:56:01'),
(8, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Active', 'Inactive', '2026-03-16 09:56:06', '2026-03-16 09:56:06'),
(9, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION)', 'Inactive', 'Active', '2026-03-16 09:56:09', '2026-03-16 09:56:09'),
(10, NULL, 4, 'Branch Updated', 'ALHAYIKI TRANSLATION', 'ALHAYIKI TRANSLATION - Hilal Branch', '2026-03-16 10:03:08', '2026-03-16 10:03:08'),
(11, NULL, 4, 'Branch Updated', 'ALHAYIKI TRANSLATION', 'ALHAYIKI TRANSLATION', '2026-03-16 10:03:54', '2026-03-16 10:03:54'),
(12, NULL, 4, 'Branch Deleted', 'ALHAYIKI TRANSLATION', 'Permanently Removed', '2026-03-16 10:04:00', '2026-03-16 10:04:00'),
(13, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION - Hilal Branch)', 'Active', 'Inactive', '2026-03-16 10:04:49', '2026-03-16 10:04:49'),
(14, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION - Hilal Branch)', 'Inactive', 'Active', '2026-03-16 10:04:52', '2026-03-16 10:04:52'),
(15, NULL, 4, 'Branch Updated', 'ALHAYIKI TRANSLATION - Hilal Branch', 'ALHAYIKI TRANSLATION - MSHEIREB BRANCH', '2026-03-16 10:07:14', '2026-03-16 10:07:14'),
(16, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION - Hilal Branch)', 'Active', 'Inactive', '2026-03-17 18:21:22', '2026-03-17 18:21:22'),
(17, NULL, 4, 'Branch Status (ALHAYIKI TRANSLATION - Hilal Branch)', 'Inactive', 'Active', '2026-03-17 18:21:26', '2026-03-17 18:21:26');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `branch_code` varchar(20) DEFAULT NULL,
  `is_main_branch` tinyint(1) DEFAULT 0,
  `parent_company` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `landline` varchar(20) DEFAULT NULL,
  `mobile_numbers` text DEFAULT NULL,
  `emails` text DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `street_name` varchar(100) DEFAULT NULL,
  `building_number` varchar(20) DEFAULT NULL,
  `zone_number` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'State of Qatar',
  `po_box` varchar(20) DEFAULT NULL,
  `kahramaa_number` varchar(50) DEFAULT NULL,
  `water_number` varchar(50) DEFAULT NULL,
  `google_maps_link` text DEFAULT NULL,
  `legal_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`legal_documents`)),
  `manager_id` int(11) DEFAULT NULL,
  `cr_number` varchar(50) DEFAULT NULL,
  `cr_issue_date` date DEFAULT NULL,
  `cr_expiry_date` date DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `fire_safety_number` varchar(50) DEFAULT NULL,
  `fire_safety_expiry` date DEFAULT NULL,
  `computer_card_number` varchar(50) DEFAULT NULL,
  `computer_card_expiry` date DEFAULT NULL,
  `chamber_number` varchar(50) DEFAULT NULL,
  `chamber_expiry` date DEFAULT NULL,
  `lease_number` varchar(50) DEFAULT NULL,
  `lease_expiry` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `branch_code`, `is_main_branch`, `parent_company`, `status`, `landline`, `mobile_numbers`, `emails`, `website`, `area`, `street_name`, `building_number`, `zone_number`, `city`, `country`, `po_box`, `kahramaa_number`, `water_number`, `google_maps_link`, `legal_documents`, `manager_id`, `cr_number`, `cr_issue_date`, `cr_expiry_date`, `license_number`, `license_expiry_date`, `fire_safety_number`, `fire_safety_expiry`, `computer_card_number`, `computer_card_expiry`, `chamber_number`, `chamber_expiry`, `lease_number`, `lease_expiry`, `created_at`) VALUES
(1, 'ALHAYIKI TRANSLATION - MSHEIREB BRANCH', '11471', 1, '', 'Active', '44367755', '[\"33411150\",\"33411153\",\"33112214\"]', '[\"hayiki4u@gmail.com\",\"info@alhayikitranslation.com\"]', 'https://www.alhayikitranslation.com/', '4', '950', '4', '4', 'Doha', 'State of Qatar', '15702', '0', '0', 'https://maps.app.goo.gl/z3LBxRWz4pxD4qrJ8', '[{\"name\":\"Commercial Registration (CR)\",\"number\":\"11471\",\"issue_date\":\"1988-10-13\",\"expiry_date\":\"2026-10-10\"},{\"name\":\"Commercial License\",\"number\":\"7930\",\"issue_date\":\"\",\"expiry_date\":\"2026-12-26\"},{\"name\":\"Civil Defense (Fire Safety)\",\"number\":\"\",\"issue_date\":\"\",\"expiry_date\":\"\"},{\"name\":\"Computer Card\",\"number\":\"1057910\",\"issue_date\":\"\",\"expiry_date\":\"2026-12-26\"}]', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 19:48:02'),
(3, 'ALHAYIKI TRANSLATION - Hilal Branch', '11471/26', 0, 'ALHAYIKI', 'Active', '44181990', '[\"33411148\",\"33411149\"]', '[\"alhayikitranslation@gmail.com\"]', 'https://www.alhayikitranslation.com/', '', '', '', '', 'Doha', 'State of Qatar', '', '', '', '', '[{\"name\":\"Commercial Registration (CR)\",\"number\":\"\",\"issue_date\":\"\",\"expiry_date\":\"\"},{\"name\":\"Commercial License\",\"number\":\"\",\"issue_date\":\"\",\"expiry_date\":\"\"},{\"name\":\"Civil Defense (Fire Safety)\",\"number\":\"\",\"issue_date\":\"\",\"expiry_date\":\"\"},{\"name\":\"Computer Card\",\"number\":\"\",\"issue_date\":\"\",\"expiry_date\":\"\"}]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 09:14:20');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `client_type` enum('Individual','Company') NOT NULL,
  `name` varchar(150) NOT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `qid_passport` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `cr_number` varchar(50) DEFAULT NULL,
  `cr_expiry` date DEFAULT NULL,
  `vat_number` varchar(50) DEFAULT NULL,
  `mobile_primary` varchar(20) NOT NULL,
  `mobile_secondary` varchar(20) DEFAULT NULL,
  `landline` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_job_date` datetime DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Qatar',
  `requires_lpo` tinyint(1) DEFAULT 0,
  `has_contract` tinyint(1) DEFAULT 0,
  `contract_start` date DEFAULT NULL,
  `contract_expiry` date DEFAULT NULL,
  `contract_file_path` varchar(255) DEFAULT NULL,
  `delivery_prefs` set('Digital','Office_Collection','Client_Location','On_Request') DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `is_vip` tinyint(1) DEFAULT 0,
  `status` enum('Active','On Hold','Inactive') DEFAULT 'Active',
  `is_archived` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `activity_level` enum('One-Time','Occasional','Regular','Frequent') DEFAULT 'One-Time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_type`, `name`, `nationality`, `qid_passport`, `gender`, `company_name`, `position`, `contact_person`, `cr_number`, `cr_expiry`, `vat_number`, `mobile_primary`, `mobile_secondary`, `landline`, `email`, `last_job_date`, `website`, `address`, `city`, `country`, `requires_lpo`, `has_contract`, `contract_start`, `contract_expiry`, `contract_file_path`, `delivery_prefs`, `internal_notes`, `tags`, `is_vip`, `status`, `is_archived`, `created_by`, `branch_id`, `is_active`, `activity_level`) VALUES
(1, 'Individual', 'JAKER HOSSAN', '', '', '', NULL, NULL, '', '', NULL, '', '+97466985896', NULL, NULL, 'jhossan1991@gmail.com', NULL, '', '950', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, NULL, 'urgent', '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(2, 'Individual', 'NASEEF', NULL, NULL, NULL, NULL, NULL, 'naseef', NULL, NULL, NULL, '+97455181524', '+97433112214', '44367755', 'admin@office.com', NULL, NULL, 'Doha', NULL, 'Qatar', 0, 0, NULL, NULL, NULL, 'Digital,Client_Location', 'very good', NULL, 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(3, 'Individual', 'Mujib', '', '', '', NULL, NULL, 'JAKER HOSSAN', '', NULL, '', '+97431518998', NULL, NULL, NULL, NULL, '', '950', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, NULL, NULL, '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(4, 'Individual', 'OMAR FARUK', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, '+97450479741', NULL, NULL, NULL, NULL, NULL, '', NULL, 'Qatar', 0, 1, NULL, '2026-12-31', NULL, 'Digital,Client_Location', 'GOOD CLIENT', NULL, 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(5, 'Individual', 'KASHIF', '', '', '', NULL, NULL, NULL, '', NULL, '', '+97444367755', NULL, NULL, NULL, NULL, '', '', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, NULL, '', '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(6, 'Individual', 'NOUSHAD', '', '', '', NULL, NULL, NULL, '', NULL, '', '+97433411138', NULL, NULL, NULL, NULL, '', '', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, NULL, '', '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(7, 'Company', 'ARTIC', '', '', '', 'ARTIC', NULL, NULL, '', NULL, '', '+97433626464', NULL, '44223888', 'neyrouz.elhady@artic.com.qa', NULL, '', 'P.O. Box: 22465 Doha – Qatar', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, 'Digital', '', '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(8, 'Individual', 'Amjad', '', '', 'Male', NULL, NULL, NULL, '', NULL, '', '+97466681765', NULL, NULL, NULL, NULL, '', '', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, 'Digital,Client_Location', '', '', 0, 'Active', 0, NULL, 1, 1, 'One-Time'),
(9, 'Individual', 'Khalid Ahmed Saleh Al-Shajrah', 'Qatari', '27063400600', 'Male', NULL, NULL, 'Khalid Ahmed Saleh Al-Shajrah', '', NULL, '', '+97455552063', NULL, NULL, 'alshajra@yahoo.com', NULL, '', 'Doha', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, 'Digital', '', '', 1, 'Active', 0, NULL, 1, 1, 'One-Time'),
(10, 'Company', 'Sovereign PPG Corporate Services', '', '', '', 'Sovereign PPG Corporate Services', NULL, 'Serina Alexander ', '80773', '2026-02-26', '5000228180', '+97474788267', NULL, '44788765', 'salexander@sovereigngroup.com', NULL, 'https://www.sovereigngroup.com/', '14th Floor, Office 1403, Building 5,\r\nMarina 50, Lusail\r\nP.O. Box 17062', 'Doha', 'Qatar', 0, 0, NULL, NULL, NULL, 'Digital,Client_Location', '', '', 1, 'Active', 0, NULL, 1, 1, 'One-Time');

-- --------------------------------------------------------

--
-- Table structure for table `client_phones`
--

CREATE TABLE `client_phones` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_type` enum('Mobile','WhatsApp','Landline') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_phones`
--

INSERT INTO `client_phones` (`id`, `client_id`, `contact_name`, `department`, `position`, `phone_number`, `email`, `phone_type`) VALUES
(1, 10, 'Serina Alexander ', 'Finance', 'Finance & Administration Assistant', '74788267', 'salexander@sovereigngroup.com', '');

-- --------------------------------------------------------

--
-- Table structure for table `client_rates`
--

CREATE TABLE `client_rates` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `service_category` enum('Translation','PRO/Other') NOT NULL,
  `source_lang` varchar(50) DEFAULT NULL,
  `target_lang` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_rates`
--

INSERT INTO `client_rates` (`id`, `client_id`, `service_category`, `source_lang`, `target_lang`, `description`, `unit`, `rate`, `created_at`) VALUES
(6, 2, 'Translation', 'English', 'Arabic', '', 'page', 25.00, '2026-02-05 14:33:51'),
(10, 3, 'PRO/Other', '', '', 'Qatar Chamber Attestation', 'page', 25.00, '2026-02-07 17:17:09'),
(11, 4, 'Translation', 'English', 'Arabic', '', 'page', 25.00, '2026-02-14 17:41:06'),
(12, 1, 'Translation', 'Arabic', 'English', '', 'Page', 25.00, '2026-03-09 19:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `job_no` varchar(20) NOT NULL,
  `client_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `receiving_method` varchar(50) DEFAULT 'Walk-in',
  `whatsapp_number_id` int(11) DEFAULT NULL,
  `status` enum('Draft','In Progress','Ready','Completed') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `delivery_info` varchar(255) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `amount_due` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `translator_id` int(11) DEFAULT NULL,
  `project_notes` text DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `assigned_translator` varchar(255) DEFAULT NULL,
  `translator_deadline` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `client_ref` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `job_no`, `client_id`, `branch_id`, `receiving_method`, `whatsapp_number_id`, `status`, `created_by`, `grand_total`, `payment_status`, `delivery_info`, `additional_notes`, `sub_total`, `discount`, `internal_notes`, `created_at`, `amount_paid`, `amount_due`, `payment_method`, `payment_ref`, `assigned_to`, `translator_id`, `project_notes`, `deadline`, `assigned_translator`, `translator_deadline`, `updated_at`, `client_ref`) VALUES
(52, 'HM-2026-0001', 1, 1, 'Walk-In', NULL, 'In Progress', 4, 95.00, 'Partially Paid', '', '', 0.00, 0.00, NULL, '2026-02-15 12:02:56', 25.00, 70.00, 'Cash', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 'HM-2026-0002', 1, 1, 'WhatsApp', 1, 'In Progress', 4, 190.00, 'Partially Paid', 'Urgent', '', 195.00, 5.00, NULL, '2026-02-15 12:05:24', 40.00, 150.00, 'Cash', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15 12:22:03', NULL),
(54, 'HM-2026-0003', 1, 1, 'Walk-In', NULL, 'In Progress', 4, 0.00, 'Unpaid', '', '', 0.00, 0.00, NULL, '2026-02-15 13:39:29', 0.00, 0.00, 'Cash', '', NULL, NULL, NULL, '2026-02-02 17:30:00', NULL, NULL, NULL, NULL),
(55, 'HM-2026-0004', 2, 1, 'Walk-In', NULL, 'In Progress', 4, 1700.00, 'Partially Paid', 'urgent', 'very urgent', 0.00, 50.00, NULL, '2026-02-15 16:58:47', 750.00, 950.00, 'Bank Transfer', 'Saleem Sir Fawran ', NULL, NULL, NULL, '2026-02-15 20:00:00', NULL, NULL, NULL, NULL),
(56, 'HM-2026-0005', 4, 1, 'Walk-In', NULL, 'In Progress', 4, 2000.00, 'Partially Paid', '', 'additional', 0.00, 500.00, NULL, '2026-02-15 17:06:01', 1500.00, 500.00, 'Cash', 'none', NULL, NULL, NULL, '2026-02-16 17:00:00', NULL, NULL, NULL, NULL),
(57, 'HM-2026-0006', 1, 1, 'WhatsApp', 1, 'In Progress', 4, 260.00, 'Partially Paid', 'Ref/NOte after Deadline up', '', 295.00, 35.00, NULL, '2026-02-16 07:12:58', 150.00, 110.00, 'Bank Transfer', '', NULL, NULL, NULL, '2026-02-16 13:00:00', NULL, NULL, '2026-02-16 09:46:20', 'Client Ref update'),
(58, 'HM-2026-0007', 3, 1, 'Walk-In', NULL, 'In Progress', 4, 0.00, 'Unpaid', '', '', 0.00, 0.00, NULL, '2026-02-16 10:10:21', 0.00, 0.00, 'Cash', '', NULL, NULL, NULL, '2026-02-16 17:30:00', NULL, NULL, '2026-03-10 10:46:35', '');

-- --------------------------------------------------------

--
-- Table structure for table `job_assignments`
--

CREATE TABLE `job_assignments` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `task_title` varchar(255) DEFAULT NULL,
  `source_lang` varchar(50) DEFAULT NULL,
  `target_lang` varchar(50) DEFAULT NULL,
  `pages` int(11) DEFAULT 0,
  `actual_words` int(11) DEFAULT 0,
  `words` int(11) DEFAULT 0,
  `translator_id` int(11) NOT NULL,
  `translator_type` enum('Internal','Branch','External') NOT NULL,
  `translator_name` varchar(255) DEFAULT NULL,
  `translator_deadline` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `assignment_status` enum('Assigned','On Hold','Completed','Cancelled') DEFAULT 'Assigned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_items`
--

CREATE TABLE `job_items` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `actual_qty` decimal(10,2) DEFAULT 1.00,
  `source_lang` varchar(50) DEFAULT NULL,
  `target_lang` varchar(50) DEFAULT NULL,
  `pages_s` int(11) DEFAULT 0,
  `pages_t` int(11) DEFAULT 0,
  `rate_per_page` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT 1.00,
  `unit` varchar(50) DEFAULT 'Page',
  `rate` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_items`
--

INSERT INTO `job_items` (`id`, `job_id`, `service_type`, `description`, `actual_qty`, `source_lang`, `target_lang`, `pages_s`, `pages_t`, `rate_per_page`, `amount`, `qty`, `unit`, `rate`, `total`) VALUES
(24, 52, 'Translation', 'English to Arabic', 1.00, NULL, NULL, 1, 0, NULL, NULL, 1.00, 'Page', 25.00, 25.00),
(25, 52, 'Attestation', 'Qatar Chamber Attestation', 1.00, NULL, NULL, 1, 0, NULL, NULL, 1.00, 'Page', 70.00, 70.00),
(32, 53, 'Translation', 'English to Arabic', 5.00, NULL, NULL, 1, 0, NULL, NULL, 5.00, 'Page', 25.00, 125.00),
(33, 53, 'Attestation', 'Qatar Chamber Attestation', 1.00, NULL, NULL, 1, 0, NULL, NULL, 1.00, 'Page', 70.00, 70.00),
(34, 54, 'Translation', ' to ', 1.00, NULL, NULL, 1, 0, NULL, NULL, 1.00, 'Page', 0.00, 0.00),
(35, 55, 'Translation', 'English to Arabic', 70.00, NULL, NULL, 50, 0, NULL, NULL, 70.00, 'Page', 25.00, 1750.00),
(36, 56, 'Translation', 'English to Arabic', 100.00, NULL, NULL, 70, 0, NULL, NULL, 100.00, 'Page', 25.00, 2500.00),
(49, 57, 'Translation', 'English up to Arabic up', 7.00, NULL, NULL, 7, 0, NULL, NULL, 7.00, 'Page', 25.00, 175.00),
(50, 57, 'Attestation', 'Qatar Chamber Attestation up', 2.00, NULL, NULL, 2, 0, NULL, NULL, 2.00, 'Page', 60.00, 120.00);

-- --------------------------------------------------------

--
-- Table structure for table `language_pairs`
--

CREATE TABLE `language_pairs` (
  `id` int(11) NOT NULL,
  `source_language` varchar(100) NOT NULL,
  `target_language` varchar(100) NOT NULL,
  `pair_code` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `language_pairs`
--

INSERT INTO `language_pairs` (`id`, `source_language`, `target_language`, `pair_code`, `status`, `created_at`) VALUES
(2, 'Arabic', 'English', 'AR-EN', 'Active', '2026-03-17 10:51:31'),
(3, 'English', 'Arabic', 'EN-AR', 'Active', '2026-03-17 18:36:09'),
(5, 'English', 'French', 'EN-FR', 'Active', '2026-03-17 18:47:21'),
(6, 'French', 'English', 'FR-EN', 'Active', '2026-03-17 18:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_slug` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `permission_slug`, `category`) VALUES
(1, 'Submit Review Feedback', 'reviewer.submit_feedback', 'Translation');

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rules`
--

CREATE TABLE `pricing_rules` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `language_id` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_type` enum('Page','Word','Hour','Document') DEFAULT 'Page',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pricing_rules`
--

INSERT INTO `pricing_rules` (`id`, `service_id`, `language_id`, `unit_price`, `unit_type`, `status`, `created_at`) VALUES
(1, 4, 3, 30.00, 'Page', 'Active', '2026-03-17 18:57:19'),
(2, 4, 2, 30.00, 'Page', 'Active', '2026-03-17 19:09:36'),
(3, 2, NULL, 10.00, 'Page', 'Active', '2026-03-17 19:10:49'),
(4, 5, NULL, 70.00, 'Document', 'Active', '2026-03-17 19:11:19'),
(6, 4, 5, 60.00, 'Page', 'Active', '2026-03-17 19:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quote_no` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT 1,
  `receiving_method` varchar(50) DEFAULT 'Walk-in',
  `whatsapp_number_id` int(11) DEFAULT NULL,
  `client_ref` varchar(100) DEFAULT NULL,
  `quotation_for` text DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `sub_total` decimal(15,2) DEFAULT 0.00,
  `discount` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `deadline` datetime DEFAULT NULL,
  `delivery_info` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `status` enum('Draft','In Progress','Approved','Rejected','Converted','Cancelled') DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_no`, `client_id`, `branch_id`, `receiving_method`, `whatsapp_number_id`, `client_ref`, `quotation_for`, `valid_until`, `sub_total`, `discount`, `grand_total`, `deadline`, `delivery_info`, `additional_notes`, `payment_terms`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '', 1, 1, 'WhatsApp', 1, 'Client Ref update', '', '2026-02-18', 600.00, 50.00, 550.00, '2026-02-02 17:30:00', 'Delivery Deadline: 02 Feb 2026 17:30', 'remarks update', NULL, 'Draft', 4, '2026-02-17 17:22:37', '2026-02-17 19:16:31'),
(2, '', 1, 1, 'WhatsApp', 2, 'Client Ref update', '', '2026-02-24', 25.00, 0.00, 25.00, NULL, NULL, '', NULL, 'Draft', 4, '2026-02-17 19:22:31', '2026-02-17 19:22:58'),
(3, '', 1, 1, 'WhatsApp', 1, '', '', '2026-03-17', 0.00, 0.00, 0.00, NULL, NULL, '', NULL, 'Draft', 4, '2026-03-10 21:22:16', NULL),
(4, 'QT-2026-0001', 5, 1, 'Walk-In', NULL, '', '', '2026-03-17', 75.00, 5.00, 70.00, '2026-03-11 18:00:00', NULL, 'remakrs', '1. Payment: Due upon receipt of invoice unless otherwise agreed.\r\n2. Advance Payment: May be required for large projects.\r\n3. Extra Charges: Third-party fees are charged separately.\r\n4. Cancellation: Charges apply for work completed.', 'Draft', 4, '2026-03-10 21:58:20', NULL),
(12, 'QT-2026-0002', 5, 1, 'Walk-In', NULL, 'Client Ref update', '', '2026-03-17', 75.00, 0.00, 75.00, '2026-03-11 22:00:00', 'Delivery Deadline: 11/03/2026, 22:00:00', 'dfd', '1. Payment: Due upon receipt of invoice unless otherwise agreed.\r\n2. Advance Payment: May be required for large projects.\r\n3. Extra Charges: Third-party fees are charged separately.\r\n4. Cancellation: Charges apply for work completed.', 'Draft', 4, '2026-03-10 22:07:27', '2026-03-10 22:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pages_s` decimal(10,2) DEFAULT 0.00,
  `qty` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'Page',
  `rate` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quote_id`, `service_type`, `description`, `pages_s`, `qty`, `unit`, `rate`, `total`) VALUES
(32, 2, 'Translation', 'English to Arabic', 1.00, 1.00, 'Page', 25.00, 25.00),
(33, 1, 'Translation', 'English to Arabic', 25.00, 30.00, 'Page', 20.00, 600.00),
(34, 3, 'Translation', '', 1.00, 1.00, 'Page', 0.00, 0.00),
(35, 4, 'Translation', 'English to Arabic', 1.00, 3.00, 'Page', 25.00, 75.00),
(47, 12, 'Attestation', 'Qatar Chamber Attestation', 1.00, 1.00, 'Doc', 75.00, 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_versions`
--

CREATE TABLE `quotation_versions` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `version_number` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `grand_total_at_time` decimal(10,2) DEFAULT NULL,
  `data_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_versions`
--

INSERT INTO `quotation_versions` (`id`, `quote_id`, `version_number`, `changed_by`, `change_reason`, `grand_total_at_time`, `data_json`, `created_at`) VALUES
(1, 12, 1, 4, NULL, 70.00, '{\"client_id\":\"5\",\"quotation_for\":\"\",\"client_ref\":\"\",\"valid_until\":\"2026-03-17\",\"receiving_method\":\"Walk-In\",\"whatsapp_number_id\":\"\",\"quote_no\":\"QT-2026-0002\",\"type\":[\"Attestation\"],\"src_lang\":[\"\"],\"target_lang\":[\"\"],\"pro_desc\":[\"Qatar Chamber Attestation\"],\"actual_qty\":[\"1\"],\"qty\":[\"1\"],\"unit\":[\"Doc\"],\"rate\":[\"70\"],\"deadline\":\"2026-03-11T22:00\",\"delivery_info\":\"Delivery Deadline: 11\\/03\\/2026, 22:00:00\",\"payment_terms\":\"1. Payment: Due upon receipt of invoice unless otherwise agreed.\\r\\n2. Advance Payment: May be required for large projects.\\r\\n3. Extra Charges: Third-party fees are charged separately.\\r\\n4. Cancellation: Charges apply for work completed.\",\"additional_notes\":\"dfd\",\"sub_total\":\"70.00\",\"discount\":\"0\",\"grand_total\":\"70.00\"}', '2026-03-10 19:07:27');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Super Admin'),
(2, 'Branch Manager'),
(3, 'Front Desk'),
(4, 'Translator'),
(5, 'Reviewer'),
(6, 'Accountant'),
(7, 'PRO'),
(8, 'Support Staff');

-- --------------------------------------------------------

--
-- Table structure for table `service_types`
--

CREATE TABLE `service_types` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_types`
--

INSERT INTO `service_types` (`id`, `service_name`, `description`, `status`, `created_at`) VALUES
(2, 'Typing', 'All Kind of Typing', 'Active', '2026-03-17 10:49:19'),
(4, 'Translation', 'All kind of Translation', 'Active', '2026-03-17 18:48:04'),
(5, 'Attestation', 'Qatar Chamber', 'Active', '2026-03-17 18:56:16');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL,
  `staff_id_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sponsor_company` varchar(150) DEFAULT NULL,
  `working_under_company` varchar(150) DEFAULT NULL,
  `date_joined` date DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `in_vacation` enum('Yes','No') DEFAULT 'No',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `qid_number` varchar(50) DEFAULT NULL,
  `qid_expiry` date DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`id`, `staff_id_code`, `full_name`, `mobile`, `email`, `sponsor_company`, `working_under_company`, `date_joined`, `branch_id`, `in_vacation`, `status`, `qid_number`, `qid_expiry`, `passport_number`, `passport_expiry`, `created_at`, `created_by`) VALUES
(3, 'STF-002', 'JAKER HOSSAN', '66985896', 'jhossan1991@gmail.com', 'ALSAQAR INFORMATION TECHNOLOGY', 'ALHAYIKI TRANSLATION AND SERVICE ', '2026-03-12', 1, 'No', 'Active', '29105020194', '2026-04-18', '', NULL, '2026-03-12 07:35:37', 4),
(10, 'STF-003', 'MOHAMD MANIR ALAM', '50212869', 'manirlove@gmail.com', 'AL HAYIKI', 'ALHAYIKI TRANSLATION AND SERVICE ', '2026-03-17', 3, 'No', 'Active', '', NULL, '', NULL, '2026-03-17 09:49:19', 4);

-- --------------------------------------------------------

--
-- Table structure for table `translator_ledger`
--

CREATE TABLE `translator_ledger` (
  `id` int(11) NOT NULL,
  `translator_name` varchar(255) DEFAULT NULL,
  `category` enum('Internal','Branch','External') DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `task_title` varchar(255) DEFAULT NULL,
  `source_lang` varchar(50) DEFAULT NULL,
  `target_lang` varchar(50) DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  `words` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `staff_profile_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `account_status` enum('Active','Locked') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `staff_profile_id`, `username`, `password`, `full_name`, `role`, `branch_id`, `permissions`, `account_status`, `last_login`) VALUES
(4, NULL, 'admin', '$2y$10$lrKW09ad0X.Zccxd0jibNuV6hXS/f56.t7UZ4WpdnVscuaE3T7wh2', 'System Admin', 'SuperAdmin', 1, NULL, 'Active', NULL),
(7, 3, 'jhossan1991@gmail.com', '$2y$10$.4UbbUrNsxqhjNP9ioXKJO/.ncXK7fi.i5z4X23KQ91d25ZH53.Ni', 'JAKER HOSSAN', 'Super Admin', 1, '[\"View Quotations\",\"Create Quotations\",\"Edit Quotations\",\"View Jobs\",\"Create Jobs\",\"Assign Jobs\",\"View Invoices\",\"Create Invoices\",\"Manage Vendors\",\"Manage Clients\",\"Access Settings\",\"HR Management\"]', 'Active', NULL),
(8, 10, 'manirlove@gmail.com', '$2y$10$LdVycNMtOxwwjVtD0zobk.4ftvLvJ7oPtlOp4rcOroQWmuUHl6vvu', 'MOHAMD MANIR ALAM', 'Branch Manager', 3, '[]', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `is_allowed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `service_type` varchar(100) DEFAULT 'Translation',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_numbers`
--

CREATE TABLE `whatsapp_numbers` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `branch_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whatsapp_numbers`
--

INSERT INTO `whatsapp_numbers` (`id`, `phone_number`, `branch_id`, `is_active`) VALUES
(1, '+974 0000 0000', 1, 1),
(2, '+974 1111 2222', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_primary` (`mobile_primary`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `idx_client_search` (`name`,`mobile_primary`,`cr_number`);

--
-- Indexes for table `client_phones`
--
ALTER TABLE `client_phones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `client_rates`
--
ALTER TABLE `client_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_client_rate_pair` (`client_id`,`service_category`,`source_lang`,`target_lang`,`description`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_assignments`
--
ALTER TABLE `job_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_items`
--
ALTER TABLE `job_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `language_pairs`
--
ALTER TABLE `language_pairs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pair` (`source_language`,`target_language`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_slug` (`permission_slug`);

--
-- Indexes for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quote_items` (`quote_id`);

--
-- Indexes for table `quotation_versions`
--
ALTER TABLE `quotation_versions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_types`
--
ALTER TABLE `service_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id_code` (`staff_id_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `translator_ledger`
--
ALTER TABLE `translator_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `fk_user_staff` (`staff_profile_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_numbers`
--
ALTER TABLE `whatsapp_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_whatsapp_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_phones`
--
ALTER TABLE `client_phones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_rates`
--
ALTER TABLE `client_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `job_assignments`
--
ALTER TABLE `job_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_items`
--
ALTER TABLE `job_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `language_pairs`
--
ALTER TABLE `language_pairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `quotation_versions`
--
ALTER TABLE `quotation_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_types`
--
ALTER TABLE `service_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `translator_ledger`
--
ALTER TABLE `translator_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_numbers`
--
ALTER TABLE `whatsapp_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `client_phones`
--
ALTER TABLE `client_phones`
  ADD CONSTRAINT `client_phones_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_rates`
--
ALTER TABLE `client_rates`
  ADD CONSTRAINT `client_rates_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_items`
--
ALTER TABLE `job_items`
  ADD CONSTRAINT `job_items_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD CONSTRAINT `pricing_rules_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `service_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pricing_rules_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `language_pairs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `fk_quote_items` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_staff` FOREIGN KEY (`staff_profile_id`) REFERENCES `staff_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `whatsapp_numbers`
--
ALTER TABLE `whatsapp_numbers`
  ADD CONSTRAINT `fk_whatsapp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
