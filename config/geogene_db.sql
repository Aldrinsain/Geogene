-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2026 at 09:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `geogene_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(20) DEFAULT NULL,
  `activity` varchar(255) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `role`, `activity`, `module`, `description`, `created_at`) VALUES
(1, 8, 'admin', 'Logged in', 'Auth', NULL, '2026-09-09 15:52:02'),
(2, 8, 'admin', 'Added Staff Account', 'Staff Management', 'Trisha (staff2@geogene.gov.ph)', '2026-09-10 17:06:40'),
(3, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-09-15 11:04:52'),
(4, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-09-15 11:05:00'),
(5, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-09-28 16:18:05'),
(6, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-09-28 16:18:20'),
(7, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-14 10:14:59'),
(8, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-14 10:15:10'),
(9, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-14 10:15:19'),
(10, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-14 10:15:25'),
(11, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-14 10:18:46'),
(12, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-14 10:18:51'),
(13, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-14 10:19:10'),
(14, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-14 10:20:18'),
(15, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-14 10:24:53'),
(16, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-14 10:28:39'),
(17, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 16:55:50'),
(18, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-16 16:56:36'),
(19, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 16:58:09'),
(20, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-16 16:58:23'),
(21, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 16:58:31'),
(22, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-16 16:59:48'),
(23, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 17:06:07'),
(24, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-16 17:06:12'),
(25, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 17:09:52'),
(26, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'Trisha', '2026-10-16 17:09:55'),
(27, 8, 'admin', 'Managed Staff (Suspended)', 'Staff Management', 'Trisha', '2026-10-16 17:10:32'),
(28, 8, 'admin', 'Managed Staff (Disable)', 'Staff Management', 'yanyan', '2026-10-16 17:15:14'),
(29, 8, 'admin', 'Managed Staff (Active)', 'Staff Management', 'yanyan', '2026-10-16 17:15:21'),
(30, 8, 'admin', 'Managed Staff (Disable)', 'Staff Management', 'yanyan', '2026-10-16 17:15:25'),
(31, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-16 21:30:57'),
(32, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'Trisha', '2026-10-16 21:31:04'),
(33, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-16 21:31:42'),
(34, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'Trisha', '2026-10-16 21:31:47'),
(35, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-16 21:31:52'),
(36, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'yanyan', '2026-10-16 21:31:56'),
(37, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'Trisha', '2026-10-18 15:30:16'),
(38, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-18 15:31:01'),
(39, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'Trisha', '2026-10-18 15:34:20'),
(40, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-18 16:07:04'),
(41, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'Trisha', '2026-10-18 16:07:09'),
(42, 8, 'admin', 'Managed Staff (Disabled)', 'Staff Management', 'yanyan', '2026-10-18 16:07:38'),
(43, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'Trisha', '2026-10-18 16:08:10'),
(44, 8, 'admin', 'Managed Staff (Enabled)', 'Staff Management', 'yanyan', '2026-10-18 16:08:12');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `name`, `latitude`, `longitude`) VALUES
(1, 'Alae', 8.4227150, 124.8141440),
(2, 'Agusan Canyon', 8.3191080, 124.8090650),
(3, 'Santiago', 8.4340230, 124.9948630),
(4, 'Sto. Niño', 8.4315300, 124.8673690),
(5, 'Dicklum', 8.3762580, 124.8481190),
(6, 'Damilag', 8.3666900, 124.8196900),
(7, 'Dahilayan', 8.2214500, 124.8552690),
(8, 'Dalirig', 8.3733470, 124.9050470),
(9, 'Guilang-guilang', 8.4507200, 125.0184890),
(10, 'Kalugmanan', 8.2633400, 124.8657680),
(11, 'Lindaban', 8.2824800, 124.8476810),
(12, 'Lingion', 8.3990620, 124.8889330),
(13, 'Lunocan', 8.4268150, 124.8404550),
(14, 'Maluko', 8.3773140, 124.9537620),
(15, 'Mambatangan', 8.4735090, 124.7982350),
(16, 'Mampayag', 8.2602540, 124.8359350),
(17, 'Mantibugao', 8.4590870, 124.8244190),
(18, 'Minsuro', 8.5033020, 124.8302870),
(19, 'San Miguel', 8.3803140, 124.8348960),
(20, 'Sankanan', 8.3274530, 124.8523920),
(21, 'Tankulan (Pob.)', 8.3581510, 124.8676720),
(22, 'Ticala', 8.3417240, 124.8902940);

-- --------------------------------------------------------

--
-- Table structure for table `civil_registry_records`
--

CREATE TABLE `civil_registry_records` (
  `record_id` int(11) NOT NULL,
  `registry_type` enum('birth','marriage','death') NOT NULL,
  `registry_number` varchar(50) DEFAULT NULL,
  `book_no` varchar(20) DEFAULT NULL,
  `page_no` varchar(20) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `date_prepared` date DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `sex` enum('male','female') DEFAULT NULL,
  `civil_status` enum('single','married','widowed','divorced') DEFAULT NULL,
  `date_of_event` date DEFAULT NULL,
  `place_of_event` varchar(255) DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `mother_name` varchar(200) DEFAULT NULL,
  `spouse_name` varchar(200) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `scan_image_path` varchar(255) DEFAULT NULL,
  `scan_image_path_2` varchar(255) DEFAULT NULL,
  `scan_image_path_back` varchar(255) DEFAULT NULL,
  `scan_image_path_back_2` varchar(255) DEFAULT NULL,
  `certificate_data` longtext DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_of_birth` date DEFAULT NULL,
  `wife_father_name` varchar(255) DEFAULT NULL,
  `wife_mother_name` varchar(255) DEFAULT NULL,
  `age_at_death` varchar(20) DEFAULT NULL,
  `cause_of_death` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `civil_registry_records`
--

INSERT INTO `civil_registry_records` (`record_id`, `registry_type`, `registry_number`, `book_no`, `page_no`, `city_municipality`, `date_prepared`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `civil_status`, `date_of_event`, `place_of_event`, `father_name`, `mother_name`, `spouse_name`, `barangay`, `zone`, `remarks`, `scan_image_path`, `scan_image_path_2`, `scan_image_path_back`, `scan_image_path_back_2`, `certificate_data`, `verified_by`, `verified_at`, `created_at`, `date_of_birth`, `wife_father_name`, `wife_mother_name`, `age_at_death`, `cause_of_death`) VALUES
(3236, 'birth', '2020-2003', NULL, NULL, 'MANOLO FORTICH', '2020-09-21', 'Sain, Angela Piloton', 'Angela', 'Piloton', 'Sain', NULL, NULL, NULL, '2003-04-03', NULL, 'Sain, Omar Lambana', 'Sain, Maylade Piloton', NULL, 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 15:29:29', NULL, NULL, NULL, NULL, NULL),
(3237, 'birth', '2020-1980', NULL, NULL, 'MANOLO FORTICH', '2020-09-24', 'Sain, Maylade Piloton', 'Maylade', 'Piloton', 'Sain', NULL, NULL, NULL, '1980-08-24', NULL, 'Piloton, Reto Qelang', 'Piloton, Raymaie Balonga', NULL, 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 15:29:29', NULL, NULL, NULL, NULL, NULL),
(3238, 'birth', '2020-1986', NULL, NULL, 'MANOLO FORTICH', '2020-09-23', 'Sain, Omar Lambana', 'Omar', 'Lambana', 'Sain', NULL, NULL, NULL, '1980-05-09', NULL, 'Sain, Oddie Lumuna', 'Sain, Sarya Lambana', NULL, 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 15:29:29', NULL, NULL, NULL, NULL, NULL),
(3239, 'birth', '2020-2006', NULL, NULL, 'MANOLO FORTICH', '2020-09-24', 'Sain, Aleah Piloton', 'Aleah', 'Piloton', 'Sain', NULL, NULL, NULL, '2006-03-24', NULL, 'Sain, Omar Lambana', 'Sain, Maylade Piloton', NULL, 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 15:29:29', NULL, NULL, NULL, NULL, NULL),
(3241, 'birth', '1967-0001', '20', '59', 'MANOLO FORTICH', '1967-09-08', 'Alfeche, Estrelleta Caballero', 'Estrelleta', 'Caballero', 'Alfeche', NULL, NULL, NULL, '1967-08-18', NULL, 'Alfeche, Teodoro', 'Caballero, Rosario', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3242, 'birth', '1970-0002', '53', '15', 'MANOLO FORTICH', '1970-07-20', 'Alfeche, Nievajane Caballero', 'Nievajane', 'Caballero', 'Alfeche', NULL, NULL, NULL, '1970-07-01', NULL, 'Alfeche, Teodoro', 'Caballero, Rosario', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3243, 'birth', '1971-0003', '16', '81', 'MANOLO FORTICH', '1971-04-07', 'Alfeche, Nestor Caballero', 'Nestor', 'Caballero', 'Alfeche', NULL, NULL, NULL, '1971-03-24', NULL, 'Alfeche, Teodoro', 'Caballero, Rosario', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3244, 'birth', '1971-0004', '33', '21', 'MANOLO FORTICH', '1971-06-05', 'Alfeche, Cristina Caballero', 'Cristina', 'Caballero', 'Alfeche', NULL, NULL, NULL, '1971-05-21', NULL, 'Alfeche, Teodoro', 'Caballero, Rosario', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3245, 'birth', '1982-0005', '24', '23', 'MANOLO FORTICH', '1982-02-24', 'Tuling, Samuel Alfeche', 'Samuel', 'Alfeche', 'Tuling', NULL, NULL, NULL, '1982-02-15', NULL, 'Tuling, Bonifacio', 'Alfeche, Estrelleta Caballero', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3246, 'birth', '1983-0006', '44', '60', 'MANOLO FORTICH', '1983-07-10', 'Rosello, Johnnas Alfeche', 'Johnnas', 'Alfeche', 'Rosello', NULL, NULL, NULL, '1983-06-11', NULL, 'Rosello, Marcelo', 'Alfeche, Cristina Caballero', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3247, 'birth', '1983-0007', '34', '89', 'MANOLO FORTICH', '1983-11-04', 'Rosello, Jehiel Alfeche', 'Jehiel', 'Alfeche', 'Rosello', NULL, NULL, NULL, '1983-10-23', NULL, 'Rosello, Marcelo', 'Alfeche, Cristina Caballero', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3248, 'birth', '1986-0008', '25', '61', 'MANOLO FORTICH', '1986-06-24', 'Candillada, Jehiel Alfeche', 'Jehiel', 'Alfeche', 'Candillada', NULL, NULL, NULL, '1986-06-20', NULL, 'Candillada, Pedro', 'Alfeche, Nievajane Caballero', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3249, 'birth', '1987-0009', '59', '67', 'MANOLO FORTICH', '1987-08-31', 'Alfeche, Grace Tabada', 'Grace', 'Tabada', 'Alfeche', NULL, NULL, NULL, '1987-07-19', NULL, 'Alfeche, Nestor Caballero', 'Tabada, Perpetua', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3250, 'birth', '1987-0010', '18', '60', 'MANOLO FORTICH', '1987-08-14', 'Alfeche, Samuel Tabada', 'Samuel', 'Tabada', 'Alfeche', NULL, NULL, NULL, '1987-08-04', NULL, 'Alfeche, Nestor Caballero', 'Tabada, Perpetua', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3251, 'birth', '1991-0011', '27', '43', 'MANOLO FORTICH', '1991-12-04', 'Rosello, Mario Alfeche', 'Mario', 'Alfeche', 'Rosello', NULL, NULL, NULL, '1991-11-13', NULL, 'Rosello, Marcelo', 'Alfeche, Cristina Caballero', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3252, 'birth', '1991-0012', '40', '22', 'MANOLO FORTICH', '1991-12-18', 'Candillada, Marilou Alfeche', 'Marilou', 'Alfeche', 'Candillada', NULL, NULL, NULL, '1991-12-02', NULL, 'Candillada, Pedro', 'Alfeche, Nievajane Caballero', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3253, 'birth', '1993-0013', '20', '61', 'MANOLO FORTICH', '1993-06-05', 'Alfeche, Cinde Tabada', 'Cinde', 'Tabada', 'Alfeche', NULL, NULL, NULL, '1993-04-28', NULL, 'Alfeche, Nestor Caballero', 'Tabada, Perpetua', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3254, 'birth', '1993-0014', '15', '31', 'MANOLO FORTICH', '1993-09-05', 'Candillada, Romeo Alfeche', 'Romeo', 'Alfeche', 'Candillada', NULL, NULL, NULL, '1993-07-24', NULL, 'Candillada, Pedro', 'Alfeche, Nievajane Caballero', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3255, 'birth', '1994-0015', '35', '32', 'MANOLO FORTICH', '1994-12-26', 'Tuling, Wilma Alfeche', 'Wilma', 'Alfeche', 'Tuling', NULL, NULL, NULL, '1994-11-29', NULL, 'Tuling, Bonifacio', 'Alfeche, Estrelleta Caballero', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3256, 'birth', '2002-0016', '14', '83', 'MANOLO FORTICH', '2002-06-30', 'Candillada, Kezia Oclarit', 'Kezia', 'Oclarit', 'Candillada', NULL, NULL, NULL, '2002-06-04', NULL, 'Candillada, Romeo Alfeche', 'Oclarit, Aiza', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3257, 'birth', '2002-0017', '32', '40', 'MANOLO FORTICH', '2003-01-14', 'Tuling, Zayd Dagno', 'Zayd', 'Dagno', 'Tuling', NULL, NULL, NULL, '2002-11-30', NULL, 'Tuling, Samuel Alfeche', 'Dagno, Grace', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3258, 'birth', '2002-0018', '30', '13', 'MANOLO FORTICH', '2002-12-18', 'Rosello, Vince Montanez', 'Vince', 'Montanez', 'Rosello', NULL, NULL, NULL, '2002-12-01', NULL, 'Rosello, Johnnas Alfeche', 'Montanez, Cristina', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3259, 'birth', '2003-0019', '54', '18', 'MANOLO FORTICH', '2003-08-08', 'Rosello, Kezia Montanez', 'Kezia', 'Montanez', 'Rosello', NULL, NULL, NULL, '2003-07-18', NULL, 'Rosello, Johnnas Alfeche', 'Montanez, Cristina', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3260, 'birth', '2003-0020', '51', '62', 'MANOLO FORTICH', '2003-08-09', 'Rosello, Enzo Sarzosa', 'Enzo', 'Sarzosa', 'Rosello', NULL, NULL, NULL, '2003-07-21', NULL, 'Rosello, Mario Alfeche', 'Sarzosa, Cinde', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3261, 'birth', '2003-0021', '48', '20', 'MANOLO FORTICH', '2003-09-07', 'Saculingan, Mark Angelo Candillada', 'Mark', 'Angelo Candillada', 'Saculingan', NULL, NULL, NULL, '2003-08-23', NULL, 'Saculingan, Emmanuel', 'Candillada, Marilou Alfeche', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3262, 'birth', '2004-0022', '56', '57', 'MANOLO FORTICH', '2004-02-22', 'Dayog, Enzo Alfeche', 'Enzo', 'Alfeche', 'Dayog', NULL, NULL, NULL, '2004-01-11', NULL, 'Dayog, Danilo', 'Alfeche, Cinde Tabada', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3263, 'birth', '2004-0023', '28', '13', 'MANOLO FORTICH', '2004-07-28', 'Dayog, Cathaleen Alfeche', 'Cathaleen', 'Alfeche', 'Dayog', NULL, NULL, NULL, '2004-07-16', NULL, 'Dayog, Danilo', 'Alfeche, Cinde Tabada', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3264, 'birth', '2005-0024', '40', '57', 'MANOLO FORTICH', '2005-08-18', 'Candillada, Cathaleen Oclarit', 'Cathaleen', 'Oclarit', 'Candillada', NULL, NULL, NULL, '2005-07-18', NULL, 'Candillada, Romeo Alfeche', 'Oclarit, Aiza', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3265, 'birth', '2005-0025', '48', '49', 'MANOLO FORTICH', '2005-11-22', 'Dayog, Miguel Alfeche', 'Miguel', 'Alfeche', 'Dayog', NULL, NULL, NULL, '2005-10-11', NULL, 'Dayog, Danilo', 'Alfeche, Cinde Tabada', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3266, 'birth', '2006-0026', '21', '28', 'MANOLO FORTICH', '2006-02-17', 'Alfeche, Kezia Tuling', 'Kezia', 'Tuling', 'Alfeche', NULL, NULL, NULL, '2006-01-22', NULL, 'Alfeche, Samuel Tabada', 'Tuling, Grace', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3267, 'birth', '2007-0027', '25', '57', 'MANOLO FORTICH', '2007-09-12', 'Horca, Zsanine Tuling', 'Zsanine', 'Tuling', 'Horca', NULL, NULL, NULL, '2007-08-29', NULL, 'Horca, Mario', 'Tuling, Wilma Alfeche', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3268, 'birth', '2007-0028', '33', '37', 'MANOLO FORTICH', '2007-11-25', 'Horca, Kyla Tuling', 'Kyla', 'Tuling', 'Horca', NULL, NULL, NULL, '2007-11-12', NULL, 'Horca, Mario', 'Tuling, Wilma Alfeche', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3269, 'birth', '2007-0029', '22', '48', 'MANOLO FORTICH', '2008-01-19', 'Villasorda, Yeshu Alfeche', 'Yeshu', 'Alfeche', 'Villasorda', NULL, NULL, NULL, '2007-12-28', NULL, 'Villasorda, Herson Jablan', 'Alfeche, Grace Tabada', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3270, 'birth', '2008-0030', '46', '84', 'MANOLO FORTICH', '2008-07-10', 'Dayog, Trisha Candillada', 'Trisha', 'Candillada', 'Dayog', NULL, NULL, NULL, '2008-07-05', NULL, 'Dayog, Herson', 'Candillada, Jehiel Alfeche', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3271, 'birth', '2008-0031', '14', '40', 'MANOLO FORTICH', '2008-09-13', 'Candillada, Kezia Oclarit', 'Kezia', 'Oclarit', 'Candillada', NULL, NULL, NULL, '2008-08-19', NULL, 'Candillada, Romeo Alfeche', 'Oclarit, Aiza', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3272, 'birth', '2008-0032', '13', '58', 'MANOLO FORTICH', '2008-11-18', 'Dayog, Marc Candillada', 'Marc', 'Candillada', 'Dayog', NULL, NULL, NULL, '2008-10-14', NULL, 'Dayog, Herson', 'Candillada, Jehiel Alfeche', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3273, 'birth', '2008-0033', '26', '28', 'MANOLO FORTICH', '2008-12-03', 'Rosello, Kezia Montanez', 'Kezia', 'Montanez', 'Rosello', NULL, NULL, NULL, '2008-11-25', NULL, 'Rosello, Johnnas Alfeche', 'Montanez, Cristina', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3274, 'birth', '2009-0034', '49', '37', 'MANOLO FORTICH', '2009-08-20', 'Villasorda, Angel Alfeche', 'Angel', 'Alfeche', 'Villasorda', NULL, NULL, NULL, '2009-08-02', NULL, 'Villasorda, Herson Jablan', 'Alfeche, Grace Tabada', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3275, 'birth', '2009-0035', '56', '85', 'MANOLO FORTICH', '2009-08-31', 'Tuling, Xander Dagno', 'Xander', 'Dagno', 'Tuling', NULL, NULL, NULL, '2009-08-10', NULL, 'Tuling, Samuel Alfeche', 'Dagno, Grace', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3276, 'birth', '2009-0036', '14', '43', 'MANOLO FORTICH', '2009-08-18', 'Tuling, Kyla Dagno', 'Kyla', 'Dagno', 'Tuling', NULL, NULL, NULL, '2009-08-12', NULL, 'Tuling, Samuel Alfeche', 'Dagno, Grace', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3277, 'birth', '2009-0037', '51', '86', 'MANOLO FORTICH', '2009-12-24', 'Alfeche, Enzo Tuling', 'Enzo', 'Tuling', 'Alfeche', NULL, NULL, NULL, '2009-11-18', NULL, 'Alfeche, Samuel Tabada', 'Tuling, Grace', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3278, 'birth', '2009-0038', '51', '43', 'MANOLO FORTICH', '2010-01-04', 'Alfeche, Jerry May Tuling', 'Jerry', 'May Tuling', 'Alfeche', NULL, NULL, NULL, '2009-12-13', NULL, 'Alfeche, Samuel Tabada', 'Tuling, Grace', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3279, 'birth', '2010-0039', '36', '21', 'MANOLO FORTICH', '2010-10-28', 'Rosello, Nyla Sarzosa', 'Nyla', 'Sarzosa', 'Rosello', NULL, NULL, NULL, '2010-09-23', NULL, 'Rosello, Mario Alfeche', 'Sarzosa, Cinde', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3280, 'birth', '2012-0040', '15', '29', 'MANOLO FORTICH', '2012-03-09', 'Binanlao, Trisha Rosello', 'Trisha', 'Rosello', 'Binanlao', NULL, NULL, NULL, '2012-02-26', NULL, 'Binanlao, Rogelio', 'Rosello, Jehiel Alfeche', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3281, 'birth', '2012-0041', '35', '65', 'MANOLO FORTICH', '2012-07-07', 'Binanlao, Kezia Rosello', 'Kezia', 'Rosello', 'Binanlao', NULL, NULL, NULL, '2012-06-08', NULL, 'Binanlao, Rogelio', 'Rosello, Jehiel Alfeche', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3282, 'birth', '2012-0042', '28', '11', 'MANOLO FORTICH', '2012-07-29', 'Rosello, Jojale Sarzosa', 'Jojale', 'Sarzosa', 'Rosello', NULL, NULL, NULL, '2012-06-23', NULL, 'Rosello, Mario Alfeche', 'Sarzosa, Cinde', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3283, 'birth', '2012-0043', '39', '73', 'MANOLO FORTICH', '2012-10-14', 'Saculingan, Rafael Candillada', 'Rafael', 'Candillada', 'Saculingan', NULL, NULL, NULL, '2012-09-01', NULL, 'Saculingan, Emmanuel', 'Candillada, Marilou Alfeche', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3284, 'birth', '2014-0044', '17', '74', 'MANOLO FORTICH', '2014-03-09', 'Ceniza, Kezia Binanlao', 'Kezia', 'Binanlao', 'Ceniza', NULL, NULL, NULL, '2014-01-23', NULL, 'Ceniza, Marc', 'Binanlao, Kezia Rosello', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3285, 'birth', '2014-0045', '54', '45', 'MANOLO FORTICH', '2014-02-21', 'Mejias, Alonso Dayog', 'Alonso', 'Dayog', 'Mejias', NULL, NULL, NULL, '2014-02-03', NULL, 'Mejias, Rafael', 'Dayog, Cathaleen Alfeche', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3286, 'birth', '2014-0046', '38', '49', 'MANOLO FORTICH', '2014-06-21', 'Guerero, Flong Villasorda', 'Flong', 'Villasorda', 'Guerero', NULL, NULL, NULL, '2014-06-11', NULL, 'Guerero, Mark Angelo', 'Villasorda, Yeshu Alfeche', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3287, 'birth', '2014-0047', '48', '58', 'MANOLO FORTICH', '2014-10-04', 'Saculingan, Justin Ramirez', 'Justin', 'Ramirez', 'Saculingan', NULL, NULL, NULL, '2014-09-08', NULL, 'Saculingan, Rafael Candillada', 'Ramirez, Kezia', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3288, 'birth', '2014-0048', '31', '29', 'MANOLO FORTICH', '2014-11-25', 'Alfeche, Kyla Horca', 'Kyla', 'Horca', 'Alfeche', NULL, NULL, NULL, '2014-10-17', NULL, 'Alfeche, Jerry May Tuling', 'Horca, Kyla Tuling', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3289, 'birth', '2014-0049', '10', '42', 'MANOLO FORTICH', '2014-11-21', 'Mejias, Trisha Dayog', 'Trisha', 'Dayog', 'Mejias', NULL, NULL, NULL, '2014-11-06', NULL, 'Mejias, Rafael', 'Dayog, Cathaleen Alfeche', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3290, 'birth', '2015-0050', '23', '22', 'MANOLO FORTICH', '2015-05-22', 'Alfeche, Kezia Libanta', 'Kezia', 'Libanta', 'Alfeche', NULL, NULL, NULL, '2015-04-21', NULL, 'Alfeche, Enzo Tuling', 'Libanta, Zsanine', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3291, 'birth', '2016-0051', '56', '13', 'MANOLO FORTICH', '2016-02-12', 'Ceniza, Reign Binanlao', 'Reign', 'Binanlao', 'Ceniza', NULL, NULL, NULL, '2016-01-08', NULL, 'Ceniza, Marc', 'Binanlao, Kezia Rosello', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3292, 'birth', '2016-0052', '59', '34', 'MANOLO FORTICH', '2016-06-04', 'Abao, Skye Rosello', 'Skye', 'Rosello', 'Abao', NULL, NULL, NULL, '2016-05-20', NULL, 'Abao, Zayd', 'Rosello, Jojale', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3293, 'birth', '2016-0053', '35', '55', 'MANOLO FORTICH', '2016-12-26', 'Ramirez, Kyla Alfeche', 'Kyla', 'Alfeche', 'Ramirez', NULL, NULL, NULL, '2016-11-26', NULL, 'Ramirez, Kyle', 'Alfeche, Kezia Tuling', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3294, 'birth', '2017-0054', '56', '80', 'MANOLO FORTICH', '2017-02-06', 'Rosello, Nyla Amoyo', 'Nyla', 'Amoyo', 'Rosello', NULL, NULL, NULL, '2017-01-28', NULL, 'Rosello, Enzo Sarzosa', 'Amoyo, Mikaela', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3295, 'birth', '2017-0055', '46', '59', 'MANOLO FORTICH', '2017-02-23', 'Alfeche, Justin Ramirez', 'Justin', 'Ramirez', 'Alfeche', NULL, NULL, NULL, '2017-01-30', NULL, 'Alfeche, Jerry May Tuling', 'Ramirez, Yeshu', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3296, 'birth', '2017-0056', '31', '46', 'MANOLO FORTICH', '2017-03-23', 'Villasorda, Alonso Rosello', 'Alonso', 'Rosello', 'Villasorda', NULL, NULL, NULL, '2017-02-07', NULL, 'Villasorda, Marc', 'Rosello, Kezia Montanez', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3297, 'birth', '2017-0057', '28', '34', 'MANOLO FORTICH', '2017-02-24', 'Cabiles, Angel Horca', 'Angel', 'Horca', 'Cabiles', NULL, NULL, NULL, '2017-02-20', NULL, 'Cabiles, Kian', 'Horca, Zsanine Tuling', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3298, 'birth', '2017-0058', '29', '54', 'MANOLO FORTICH', '2017-07-21', 'Guijon, Yeshu Rosello', 'Yeshu', 'Rosello', 'Guijon', NULL, NULL, NULL, '2017-06-08', NULL, 'Guijon, Kian', 'Rosello, Nyla Sarzosa', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3299, 'birth', '2018-0059', '31', '81', 'MANOLO FORTICH', '2018-01-25', 'Alfeche, Rafael Ramirez', 'Rafael', 'Ramirez', 'Alfeche', NULL, NULL, NULL, '2018-01-17', NULL, 'Alfeche, Jerry May Tuling', 'Ramirez, Yeshu', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3300, 'birth', '2018-0060', '43', '39', 'MANOLO FORTICH', '2018-05-23', 'Tuling, Kian Dayog', 'Kian', 'Dayog', 'Tuling', NULL, NULL, NULL, '2018-05-13', NULL, 'Tuling, Zayd Dagno', 'Dayog, Jojale', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3301, 'birth', '2018-0061', '20', '16', 'MANOLO FORTICH', '2018-10-01', 'Sarzosa, Gabriel Villasorda', 'Gabriel', 'Villasorda', 'Sarzosa', NULL, NULL, NULL, '2018-09-27', NULL, 'Sarzosa, Mark Angelo', 'Villasorda, Angel Alfeche', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3302, 'birth', '2018-0062', '49', '51', 'MANOLO FORTICH', '2018-12-15', 'Dignos, Skye Dayog', 'Skye', 'Dayog', 'Dignos', NULL, NULL, NULL, '2018-11-23', NULL, 'Dignos, Rafael', 'Dayog, Trisha Candillada', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3303, 'birth', '2019-0063', '60', '43', 'MANOLO FORTICH', '2019-01-26', 'Tuling, Gabriel Libanta', 'Gabriel', 'Libanta', 'Tuling', NULL, NULL, NULL, '2019-01-04', NULL, 'Tuling, Xander Dagno', 'Libanta, Zsanine', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3304, 'birth', '2019-0064', '37', '49', 'MANOLO FORTICH', '2019-09-21', 'Conlayan, Zayd Candillada', 'Zayd', 'Candillada', 'Conlayan', NULL, NULL, NULL, '2019-08-30', NULL, 'Conlayan, Marc', 'Candillada, Kezia Oclarit', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3305, 'birth', '2019-0065', '48', '7', 'MANOLO FORTICH', '2019-11-03', 'Guerero, Rafael Villasorda', 'Rafael', 'Villasorda', 'Guerero', NULL, NULL, NULL, '2019-09-27', NULL, 'Guerero, Mark Angelo', 'Villasorda, Yeshu Alfeche', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3306, 'birth', '2020-0066', '36', '84', 'MANOLO FORTICH', '2020-03-03', 'Amoyo, Mikaela Rosello', 'Mikaela', 'Rosello', 'Amoyo', NULL, NULL, NULL, '2020-02-05', NULL, 'Amoyo, Vince', 'Rosello, Kezia Montanez', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3307, 'birth', '2020-0067', '31', '56', 'MANOLO FORTICH', '2020-08-15', 'Dayog, Marc Montanez', 'Marc', 'Montanez', 'Dayog', NULL, NULL, NULL, '2020-07-10', NULL, 'Dayog, Miguel Alfeche', 'Montanez, Kyla', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3308, 'birth', '2020-0068', '13', '82', 'MANOLO FORTICH', '2020-08-15', 'Tuling, Skye Dayog', 'Skye', 'Dayog', 'Tuling', NULL, NULL, NULL, '2020-08-05', NULL, 'Tuling, Zayd Dagno', 'Dayog, Jojale', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3309, 'birth', '2020-0069', '41', '27', 'MANOLO FORTICH', '2020-08-21', 'Lusterio, Cathaleen Binanlao', 'Cathaleen', 'Binanlao', 'Lusterio', NULL, NULL, NULL, '2020-08-16', NULL, 'Lusterio, Kyle', 'Binanlao, Trisha Rosello', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3310, 'birth', '2020-0070', '16', '23', 'MANOLO FORTICH', '2020-09-23', 'Rosello, Mark Angelo Bacalso', 'Mark', 'Angelo Bacalso', 'Rosello', NULL, NULL, NULL, '2020-08-22', NULL, 'Rosello, Vince Montanez', 'Bacalso, Nyla', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3311, 'birth', '2021-0071', '27', '34', 'MANOLO FORTICH', '2021-08-29', 'Dayog, Belle Oclarit', 'Belle', 'Oclarit', 'Dayog', NULL, NULL, NULL, '2021-07-17', NULL, 'Dayog, Marc Candillada', 'Oclarit, Kezia', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3312, 'birth', '2022-0072', '52', '72', 'MANOLO FORTICH', '2022-02-06', 'Alfeche, Trisha Horca', 'Trisha', 'Horca', 'Alfeche', NULL, NULL, NULL, '2022-01-15', NULL, 'Alfeche, Jerry May Tuling', 'Horca, Kyla Tuling', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3313, 'birth', '2022-0073', '35', '3', 'MANOLO FORTICH', '2022-02-05', 'Dayog, Reign Conlayan', 'Reign', 'Conlayan', 'Dayog', NULL, NULL, NULL, '2022-01-21', NULL, 'Dayog, Enzo Alfeche', 'Conlayan, Nyla', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3314, 'birth', '2022-0074', '57', '15', 'MANOLO FORTICH', '2022-02-21', 'Rosello, Rafael Bacalso', 'Rafael', 'Bacalso', 'Rosello', NULL, NULL, NULL, '2022-01-29', NULL, 'Rosello, Vince Montanez', 'Bacalso, Nyla', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3315, 'birth', '2022-0075', '33', '60', 'MANOLO FORTICH', '2022-02-15', 'Saculingan, Angel Dayog', 'Angel', 'Dayog', 'Saculingan', NULL, NULL, NULL, '2022-01-31', NULL, 'Saculingan, Mark Angelo', 'Dayog, Andrea', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3316, 'birth', '2022-0076', '17', '52', 'MANOLO FORTICH', '2022-06-04', 'Amoyo, Mikaela Rosello', 'Mikaela', 'Rosello', 'Amoyo', NULL, NULL, NULL, '2022-05-11', NULL, 'Amoyo, Vince', 'Rosello, Kezia Montanez', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3317, 'birth', '2022-0077', '20', '9', 'MANOLO FORTICH', '2022-09-18', 'Dayog, Kian Montanez', 'Kian', 'Montanez', 'Dayog', NULL, NULL, NULL, '2022-08-09', NULL, 'Dayog, Miguel Alfeche', 'Montanez, Kyla', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3318, 'birth', '2022-0078', '24', '83', 'MANOLO FORTICH', '2022-09-15', 'Sarzosa, Kezia Candillada', 'Kezia', 'Candillada', 'Sarzosa', NULL, NULL, NULL, '2022-09-02', NULL, 'Sarzosa, Mark Angelo', 'Candillada, Cathaleen Oclarit', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3319, 'birth', '2023-0079', '40', '17', 'MANOLO FORTICH', '2023-03-13', 'Bacalso, Kian Candillada', 'Kian', 'Candillada', 'Bacalso', NULL, NULL, NULL, '2023-03-09', NULL, 'Bacalso, Alonso', 'Candillada, Kezia Oclarit', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3320, 'birth', '2024-0080', '53', '43', 'MANOLO FORTICH', '2024-08-11', 'Montanez, Cathaleen Tuling', 'Cathaleen', 'Tuling', 'Montanez', NULL, NULL, NULL, '2024-07-23', NULL, 'Montanez, Rafael', 'Tuling, Kyla Dagno', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3321, 'birth', '2025-0081', '29', '88', 'MANOLO FORTICH', '2025-07-18', 'Dignos, Alonso Dayog', 'Alonso', 'Dayog', 'Dignos', NULL, NULL, NULL, '2025-06-22', NULL, 'Dignos, Rafael', 'Dayog, Trisha Candillada', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3322, 'birth', '2025-0082', '48', '52', 'MANOLO FORTICH', '2025-08-26', 'Cabiles, Rafael Horca', 'Rafael', 'Horca', 'Cabiles', NULL, NULL, NULL, '2025-08-08', NULL, 'Cabiles, Kian', 'Horca, Zsanine Tuling', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3323, 'birth', '2026-0083', '26', '89', 'MANOLO FORTICH', '2026-06-08', 'Saculingan, Vince Ramirez', 'Vince', 'Ramirez', 'Saculingan', NULL, NULL, NULL, '2026-05-14', NULL, 'Saculingan, Rafael Candillada', 'Ramirez, Kezia', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3324, 'birth', '1961-0084', '43', '21', 'MANOLO FORTICH', '1961-06-16', 'Candillada, Pedro', 'Pedro', NULL, 'Candillada', NULL, NULL, NULL, '1961-05-14', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3325, 'birth', '1961-0085', '26', '27', 'MANOLO FORTICH', '1961-12-08', 'Tabada, Perpetua', 'Perpetua', NULL, 'Tabada', NULL, NULL, NULL, '1961-11-02', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3326, 'birth', '1964-0086', '46', '32', 'MANOLO FORTICH', '1964-03-30', 'Tuling, Bonifacio', 'Bonifacio', NULL, 'Tuling', NULL, NULL, NULL, '1964-03-02', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3327, 'birth', '1965-0087', '10', '59', 'MANOLO FORTICH', '1966-01-07', 'Rosello, Marcelo', 'Marcelo', NULL, 'Rosello', NULL, NULL, NULL, '1965-12-15', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3328, 'birth', '1984-0088', '55', '21', 'MANOLO FORTICH', '1984-12-13', 'Dayog, Herson', 'Herson', NULL, 'Dayog', NULL, NULL, NULL, '1984-12-09', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3329, 'birth', '1986-0089', '58', '7', 'MANOLO FORTICH', '1986-04-29', 'Oclarit, Aiza', 'Aiza', NULL, 'Oclarit', NULL, NULL, NULL, '1986-03-17', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3330, 'birth', '1987-0090', '34', '41', 'MANOLO FORTICH', '1988-01-05', 'Montanez, Cristina', 'Cristina', NULL, 'Montanez', NULL, NULL, NULL, '1987-12-12', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3331, 'birth', '1988-0091', '45', '19', 'MANOLO FORTICH', '1988-03-07', 'Dagno, Grace', 'Grace', NULL, 'Dagno', NULL, NULL, NULL, '1988-02-18', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3332, 'birth', '1988-0092', '40', '45', 'MANOLO FORTICH', '1988-10-12', 'Dayog, Danilo', 'Danilo', NULL, 'Dayog', NULL, NULL, NULL, '1988-09-11', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3333, 'birth', '1989-0093', '52', '70', 'MANOLO FORTICH', '1989-02-24', 'Saculingan, Emmanuel', 'Emmanuel', NULL, 'Saculingan', NULL, NULL, NULL, '1989-01-20', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3334, 'birth', '1991-0094', '51', '20', 'MANOLO FORTICH', '1991-11-28', 'Binanlao, Rogelio', 'Rogelio', NULL, 'Binanlao', NULL, NULL, NULL, '1991-11-16', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3335, 'birth', '1992-0095', '42', '76', 'MANOLO FORTICH', '1992-08-15', 'Sarzosa, Cinde', 'Cinde', NULL, 'Sarzosa', NULL, NULL, NULL, '1992-08-10', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3336, 'birth', '1993-0096', '60', '65', 'MANOLO FORTICH', '1993-06-24', 'Tuling, Grace', 'Grace', NULL, 'Tuling', NULL, NULL, NULL, '1993-05-26', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3337, 'birth', '1994-0097', '24', '53', 'MANOLO FORTICH', '1994-03-28', 'Villasorda, Herson', 'Herson', NULL, 'Villasorda', NULL, NULL, NULL, '1994-03-10', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3338, 'birth', '1994-0098', '51', '20', 'MANOLO FORTICH', '1994-06-23', 'Horca, Mario', 'Mario', NULL, 'Horca', NULL, NULL, NULL, '1994-05-22', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3339, 'birth', '2002-0099', '46', '13', 'MANOLO FORTICH', '2002-03-24', 'Dayog, Jojale', 'Jojale', NULL, 'Dayog', NULL, NULL, NULL, '2002-02-08', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3340, 'birth', '2002-0100', '42', '90', 'MANOLO FORTICH', '2002-03-11', 'Cabiles, Kian', 'Kian', NULL, 'Cabiles', NULL, NULL, NULL, '2002-02-18', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3341, 'birth', '2002-0101', '14', '88', 'MANOLO FORTICH', '2002-07-14', 'Ramirez, Yeshu', 'Yeshu', NULL, 'Ramirez', NULL, NULL, NULL, '2002-06-24', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3342, 'birth', '2002-0102', '57', '50', 'MANOLO FORTICH', '2002-12-11', 'Sarzosa, Mark Angelo', 'Mark', 'Angelo', 'Sarzosa', NULL, NULL, NULL, '2002-10-29', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3343, 'birth', '2002-0103', '53', '70', 'MANOLO FORTICH', '2002-12-21', 'Ramirez, Kyle', 'Kyle', NULL, 'Ramirez', NULL, NULL, NULL, '2002-11-12', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3344, 'birth', '2003-0104', '18', '12', 'MANOLO FORTICH', '2003-01-30', 'Guerero, Mark Angelo', 'Mark', 'Angelo', 'Guerero', NULL, NULL, NULL, '2003-01-10', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3345, 'birth', '2003-0105', '43', '5', 'MANOLO FORTICH', '2003-09-10', 'Conlayan, Marc', 'Marc', NULL, 'Conlayan', NULL, NULL, NULL, '2003-07-28', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3346, 'birth', '2003-0106', '40', '10', 'MANOLO FORTICH', '2003-09-19', 'Lusterio, Kyle', 'Kyle', NULL, 'Lusterio', NULL, NULL, NULL, '2003-08-21', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3347, 'birth', '2005-0107', '26', '71', 'MANOLO FORTICH', '2005-07-17', 'Ramirez, Kezia', 'Kezia', NULL, 'Ramirez', NULL, NULL, NULL, '2005-07-05', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3348, 'birth', '2005-0108', '51', '77', 'MANOLO FORTICH', '2005-10-16', 'Bacalso, Nyla', 'Nyla', NULL, 'Bacalso', NULL, NULL, NULL, '2005-09-22', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3349, 'birth', '2005-0109', '39', '73', 'MANOLO FORTICH', '2006-01-17', 'Montanez, Rafael', 'Rafael', NULL, 'Montanez', NULL, NULL, NULL, '2005-12-22', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3350, 'birth', '2006-0110', '58', '41', 'MANOLO FORTICH', '2006-01-22', 'Dayog, Andrea', 'Andrea', NULL, 'Dayog', NULL, NULL, NULL, '2006-01-13', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3351, 'birth', '2007-0111', '11', '1', 'MANOLO FORTICH', '2007-03-16', 'Oclarit, Kezia', 'Kezia', NULL, 'Oclarit', NULL, NULL, NULL, '2007-02-11', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3352, 'birth', '2008-0112', '23', '76', 'MANOLO FORTICH', '2008-02-26', 'Libanta, Zsanine', 'Zsanine', NULL, 'Libanta', NULL, NULL, NULL, '2008-02-03', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3353, 'birth', '2008-0113', '39', '22', 'MANOLO FORTICH', '2008-04-08', 'Bacalso, Alonso', 'Alonso', NULL, 'Bacalso', NULL, NULL, NULL, '2008-03-05', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3354, 'birth', '2008-0114', '15', '80', 'MANOLO FORTICH', '2008-04-29', 'Conlayan, Nyla', 'Nyla', NULL, 'Conlayan', NULL, NULL, NULL, '2008-03-15', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3355, 'birth', '2008-0115', '32', '71', 'MANOLO FORTICH', '2008-04-04', 'Guijon, Kian', 'Kian', NULL, 'Guijon', NULL, NULL, NULL, '2008-03-31', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3356, 'birth', '2008-0116', '28', '89', 'MANOLO FORTICH', '2008-11-03', 'Villasorda, Marc', 'Marc', NULL, 'Villasorda', NULL, NULL, NULL, '2008-10-06', NULL, NULL, NULL, NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3357, 'birth', '2008-0117', '60', '58', 'MANOLO FORTICH', '2008-10-18', 'Montanez, Kyla', 'Kyla', NULL, 'Montanez', NULL, NULL, NULL, '2008-10-14', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3358, 'birth', '2009-0118', '14', '20', 'MANOLO FORTICH', '2009-07-11', 'Dignos, Rafael', 'Rafael', NULL, 'Dignos', NULL, NULL, NULL, '2009-06-30', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3359, 'birth', '2009-0119', '34', '71', 'MANOLO FORTICH', '2009-09-30', 'Libanta, Zsanine', 'Zsanine', NULL, 'Libanta', NULL, NULL, NULL, '2009-09-02', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3360, 'birth', '2010-0120', '34', '73', 'MANOLO FORTICH', '2010-02-21', 'Sarzosa, Mark Angelo', 'Mark', 'Angelo', 'Sarzosa', NULL, NULL, NULL, '2010-01-28', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3361, 'birth', '2010-0121', '16', '18', 'MANOLO FORTICH', '2010-02-15', 'Ceniza, Marc', 'Marc', NULL, 'Ceniza', NULL, NULL, NULL, '2010-01-31', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3362, 'birth', '2010-0122', '16', '56', 'MANOLO FORTICH', '2010-05-13', 'Mejias, Rafael', 'Rafael', NULL, 'Mejias', NULL, NULL, NULL, '2010-04-18', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3363, 'birth', '2010-0123', '17', '73', 'MANOLO FORTICH', '2010-11-05', 'Amoyo, Vince', 'Vince', NULL, 'Amoyo', NULL, NULL, NULL, '2010-10-08', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3364, 'birth', '2010-0124', '34', '11', 'MANOLO FORTICH', '2010-11-09', 'Amoyo, Mikaela', 'Mikaela', NULL, 'Amoyo', NULL, NULL, NULL, '2010-10-19', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3365, 'birth', '2012-0125', '22', '80', 'MANOLO FORTICH', '2012-07-28', 'Alfeche, Jerry May', 'Jerry', 'May', 'Alfeche', NULL, NULL, NULL, '2012-07-16', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3366, 'birth', '2012-0126', '18', '35', 'MANOLO FORTICH', '2013-01-16', 'Abao, Zayd', 'Zayd', NULL, 'Abao', NULL, NULL, NULL, '2012-12-20', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3367, 'birth', '1938-0127', '19', '28', 'MANOLO FORTICH', '1938-03-19', 'Bustillo, Gregorio', 'Gregorio', NULL, 'Bustillo', NULL, NULL, NULL, '1938-02-23', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3368, 'birth', '1940-0128', '12', '59', 'MANOLO FORTICH', '1940-02-24', 'Alfeche, Purificacion', 'Purificacion', NULL, 'Alfeche', NULL, NULL, NULL, '1940-01-30', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3369, 'birth', '1960-0129', '25', '8', 'MANOLO FORTICH', '1960-07-04', 'Bustillo, Maria Alfeche', 'Maria', 'Alfeche', 'Bustillo', NULL, NULL, NULL, '1960-06-08', NULL, 'Bustillo, Gregorio', 'Alfeche, Purificacion', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3370, 'birth', '1961-0130', '58', '69', 'MANOLO FORTICH', '1961-11-10', 'Bustillo, Juan Alfeche', 'Juan', 'Alfeche', 'Bustillo', NULL, NULL, NULL, '1961-10-07', NULL, 'Bustillo, Gregorio', 'Alfeche, Purificacion', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3371, 'birth', '1970-0131', '43', '78', 'MANOLO FORTICH', '1970-09-11', 'Bustillo, Rogelio Alfeche', 'Rogelio', 'Alfeche', 'Bustillo', NULL, NULL, NULL, '1970-08-29', NULL, 'Bustillo, Gregorio', 'Alfeche, Purificacion', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3372, 'birth', '1982-0132', '36', '69', 'MANOLO FORTICH', '1982-03-12', 'Jablan, Aiza Bustillo', 'Aiza', 'Bustillo', 'Jablan', NULL, NULL, NULL, '1982-02-18', NULL, 'Jablan, Godofredo', 'Bustillo, Maria Alfeche', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3373, 'birth', '1982-0133', '18', '61', 'MANOLO FORTICH', '1982-06-28', 'Bustillo, Danilo Saculingan', 'Danilo', 'Saculingan', 'Bustillo', NULL, NULL, NULL, '1982-06-16', NULL, 'Bustillo, Rogelio Alfeche', 'Saculingan, Asuncion', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3374, 'birth', '1983-0134', '58', '4', 'MANOLO FORTICH', '1983-08-30', 'Bustillo, Nestor Saculingan', 'Nestor', 'Saculingan', 'Bustillo', NULL, NULL, NULL, '1983-07-17', NULL, 'Bustillo, Rogelio Alfeche', 'Saculingan, Asuncion', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3375, 'birth', '1983-0135', '13', '45', 'MANOLO FORTICH', '1983-11-06', 'Bustillo, Sarami Saculingan', 'Sarami', 'Saculingan', 'Bustillo', NULL, NULL, NULL, '1983-11-02', NULL, 'Bustillo, Rogelio Alfeche', 'Saculingan, Asuncion', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3376, 'birth', '1986-0136', '58', '49', 'MANOLO FORTICH', '1986-10-23', 'Bustillo, Sarami Rosello', 'Sarami', 'Rosello', 'Bustillo', NULL, NULL, NULL, '1986-09-30', NULL, 'Bustillo, Juan Alfeche', 'Rosello, Josephine', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3377, 'birth', '1986-0137', '11', '50', 'MANOLO FORTICH', '1987-01-02', 'Jablan, Querudel Bustillo', 'Querudel', 'Bustillo', 'Jablan', NULL, NULL, NULL, '1986-12-03', NULL, 'Jablan, Godofredo', 'Bustillo, Maria Alfeche', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3378, 'birth', '1991-0138', '58', '74', 'MANOLO FORTICH', '1991-07-14', 'Jablan, Johnnas Bustillo', 'Johnnas', 'Bustillo', 'Jablan', NULL, NULL, NULL, '1991-06-04', NULL, 'Jablan, Godofredo', 'Bustillo, Maria Alfeche', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3379, 'birth', '1991-0139', '13', '34', 'MANOLO FORTICH', '1991-09-14', 'Bustillo, Bernard Rosello', 'Bernard', 'Rosello', 'Bustillo', NULL, NULL, NULL, '1991-08-13', NULL, 'Bustillo, Juan Alfeche', 'Rosello, Josephine', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3380, 'birth', '1992-0140', '11', '51', 'MANOLO FORTICH', '1992-07-06', 'Bustillo, Luzviminda Rosello', 'Luzviminda', 'Rosello', 'Bustillo', NULL, NULL, NULL, '1992-06-25', NULL, 'Bustillo, Juan Alfeche', 'Rosello, Josephine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3381, 'birth', '2002-0141', '28', '46', 'MANOLO FORTICH', '2002-07-13', 'Bustillo, Reign Binanlao', 'Reign', 'Binanlao', 'Bustillo', NULL, NULL, NULL, '2002-05-30', NULL, 'Bustillo, Bernard Rosello', 'Binanlao, Charlene', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3382, 'birth', '2002-0142', '26', '3', 'MANOLO FORTICH', '2002-07-25', 'Jablan, Zsanine Abao', 'Zsanine', 'Abao', 'Jablan', NULL, NULL, NULL, '2002-06-28', NULL, 'Jablan, Querudel Bustillo', 'Abao, Wilma', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3383, 'birth', '2002-0143', '44', '38', 'MANOLO FORTICH', '2002-12-31', 'Rosello, Xander Bustillo', 'Xander', 'Bustillo', 'Rosello', NULL, NULL, NULL, '2002-11-20', NULL, 'Rosello, Herson', 'Bustillo, Sarami Saculingan', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3384, 'birth', '2002-0144', '29', '58', 'MANOLO FORTICH', '2003-01-08', 'Rosello, Keith Bustillo', 'Keith', 'Bustillo', 'Rosello', NULL, NULL, NULL, '2002-11-24', NULL, 'Rosello, Herson', 'Bustillo, Sarami Saculingan', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3385, 'birth', '2003-0145', '33', '50', 'MANOLO FORTICH', '2003-06-20', 'Edem, Jojale Bustillo', 'Jojale', 'Bustillo', 'Edem', NULL, NULL, NULL, '2003-06-02', NULL, 'Edem, Alfredo Libanta', 'Bustillo, Sarami Saculingan', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3386, 'birth', '2004-0146', '30', '40', 'MANOLO FORTICH', '2004-04-20', 'Achas, Flong Bustillo', 'Flong', 'Bustillo', 'Achas', NULL, NULL, NULL, '2004-04-05', NULL, 'Achas, Romeo', 'Bustillo, Luzviminda Rosello', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3387, 'birth', '2005-0147', '37', '52', 'MANOLO FORTICH', '2005-10-01', 'Bustillo, Mark Angelo Binanlao', 'Mark', 'Angelo Binanlao', 'Bustillo', NULL, NULL, NULL, '2005-09-21', NULL, 'Bustillo, Bernard Rosello', 'Binanlao, Charlene', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `civil_registry_records` (`record_id`, `registry_type`, `registry_number`, `book_no`, `page_no`, `city_municipality`, `date_prepared`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `civil_status`, `date_of_event`, `place_of_event`, `father_name`, `mother_name`, `spouse_name`, `barangay`, `zone`, `remarks`, `scan_image_path`, `scan_image_path_2`, `scan_image_path_back`, `scan_image_path_back_2`, `certificate_data`, `verified_by`, `verified_at`, `created_at`, `date_of_birth`, `wife_father_name`, `wife_mother_name`, `age_at_death`, `cause_of_death`) VALUES
(3388, 'birth', '2005-0148', '33', '67', 'MANOLO FORTICH', '2005-11-23', 'Bustillo, Cathaleen Binanlao', 'Cathaleen', 'Binanlao', 'Bustillo', NULL, NULL, NULL, '2005-10-20', NULL, 'Bustillo, Bernard Rosello', 'Binanlao, Charlene', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3389, 'birth', '2005-0149', '59', '75', 'MANOLO FORTICH', '2005-12-28', 'Jablan, Yeshu Abao', 'Yeshu', 'Abao', 'Jablan', NULL, NULL, NULL, '2005-11-13', NULL, 'Jablan, Querudel Bustillo', 'Abao, Wilma', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3390, 'birth', '2005-0150', '59', '88', 'MANOLO FORTICH', '2006-01-07', 'Achas, Kian Bustillo', 'Kian', 'Bustillo', 'Achas', NULL, NULL, NULL, '2005-12-14', NULL, 'Achas, Romeo', 'Bustillo, Luzviminda Rosello', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3391, 'birth', '2005-0151', '21', '86', 'MANOLO FORTICH', '2006-01-24', 'Rosello, Justin Jablan', 'Justin', 'Jablan', 'Rosello', NULL, NULL, NULL, '2005-12-16', NULL, 'Rosello, Bernard', 'Jablan, Aiza Bustillo', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3392, 'birth', '2006-0152', '25', '15', 'MANOLO FORTICH', '2006-03-07', 'Bustillo, Xander Horca', 'Xander', 'Horca', 'Bustillo', NULL, NULL, NULL, '2006-02-12', NULL, 'Bustillo, Nestor Saculingan', 'Horca, Evangeline', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3393, 'birth', '2007-0153', '58', '10', 'MANOLO FORTICH', '2007-02-12', 'Bustillo, Vince Horca', 'Vince', 'Horca', 'Bustillo', NULL, NULL, NULL, '2007-01-17', NULL, 'Bustillo, Nestor Saculingan', 'Horca, Evangeline', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3394, 'birth', '2007-0154', '19', '78', 'MANOLO FORTICH', '2007-10-16', 'Edem, Reign Bustillo', 'Reign', 'Bustillo', 'Edem', NULL, NULL, NULL, '2007-10-03', NULL, 'Edem, Alfredo Libanta', 'Bustillo, Sarami Saculingan', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3395, 'birth', '2007-0155', '28', '55', 'MANOLO FORTICH', '2007-10-30', 'Rosello, Zayd Jablan', 'Zayd', 'Jablan', 'Rosello', NULL, NULL, NULL, '2007-10-12', NULL, 'Rosello, Bernard', 'Jablan, Aiza Bustillo', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3396, 'birth', '2007-0156', '26', '34', 'MANOLO FORTICH', '2007-11-29', 'Bustillo, Xander Libanta', 'Xander', 'Libanta', 'Bustillo', NULL, NULL, NULL, '2007-10-22', NULL, 'Bustillo, Danilo Saculingan', 'Libanta, Estrelleta', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3397, 'birth', '2008-0157', '17', '18', 'MANOLO FORTICH', '2008-12-15', 'Bustillo, Belle Libanta', 'Belle', 'Libanta', 'Bustillo', NULL, NULL, NULL, '2008-11-11', NULL, 'Bustillo, Danilo Saculingan', 'Libanta, Estrelleta', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3398, 'birth', '2009-0158', '55', '59', 'MANOLO FORTICH', '2009-02-11', 'Achas, Cathaleen Bustillo', 'Cathaleen', 'Bustillo', 'Achas', NULL, NULL, NULL, '2009-01-31', NULL, 'Achas, Romeo', 'Bustillo, Luzviminda Rosello', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3399, 'birth', '2009-0159', '14', '15', 'MANOLO FORTICH', '2009-04-13', 'Jablan, Rafael Tuling', 'Rafael', 'Tuling', 'Jablan', NULL, NULL, NULL, '2009-03-02', NULL, 'Jablan, Johnnas Bustillo', 'Tuling, Cristina', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3400, 'birth', '2009-0160', '12', '46', 'MANOLO FORTICH', '2009-10-21', 'Rosello, Skye Bustillo', 'Skye', 'Bustillo', 'Rosello', NULL, NULL, NULL, '2009-09-26', NULL, 'Rosello, Herson', 'Bustillo, Sarami Saculingan', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3401, 'birth', '2010-0161', '17', '7', 'MANOLO FORTICH', '2010-10-03', 'Jablan, Belle Tuling', 'Belle', 'Tuling', 'Jablan', NULL, NULL, NULL, '2010-09-15', NULL, 'Jablan, Johnnas Bustillo', 'Tuling, Cristina', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3402, 'birth', '2012-0162', '44', '65', 'MANOLO FORTICH', '2012-12-13', 'Rosello, Vince Jablan', 'Vince', 'Jablan', 'Rosello', NULL, NULL, NULL, '2012-12-06', NULL, 'Rosello, Bernard', 'Jablan, Aiza Bustillo', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3403, 'birth', '2012-0163', '52', '82', 'MANOLO FORTICH', '2013-01-03', 'Edem, Reign Bustillo', 'Reign', 'Bustillo', 'Edem', NULL, NULL, NULL, '2012-12-28', NULL, 'Edem, Alfredo Libanta', 'Bustillo, Sarami Rosello', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3404, 'birth', '2014-0164', '49', '87', 'MANOLO FORTICH', '2014-10-23', 'Conlayan, Vince Rosello', 'Vince', 'Rosello', 'Conlayan', NULL, NULL, NULL, '2014-09-22', NULL, 'Conlayan, Xander', 'Rosello, Skye Edem', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3405, 'birth', '2015-0165', '59', '58', 'MANOLO FORTICH', '2015-02-28', 'Achas, Miguel Bacalso', 'Miguel', 'Bacalso', 'Achas', NULL, NULL, NULL, '2015-01-16', NULL, 'Achas, Flong Bustillo', 'Bacalso, Cathaleen', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3406, 'birth', '2015-0166', '36', '76', 'MANOLO FORTICH', '2015-05-09', 'Nacino, Enzo Jablan', 'Enzo', 'Jablan', 'Nacino', NULL, NULL, NULL, '2015-04-26', NULL, 'Nacino, Rafael', 'Jablan, Belle Tuling', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3407, 'birth', '2015-0167', '36', '70', 'MANOLO FORTICH', '2015-08-17', 'Bustillo, Rafael Miso', 'Rafael', 'Miso', 'Bustillo', NULL, NULL, NULL, '2015-07-13', NULL, 'Bustillo, Xander Horca', 'Miso, Mikaela', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3408, 'birth', '2016-0168', '13', '86', 'MANOLO FORTICH', '2016-06-18', 'Rosello, Flong Binanlao', 'Flong', 'Binanlao', 'Rosello', NULL, NULL, NULL, '2016-05-22', NULL, 'Rosello, Xander Bustillo', 'Binanlao, Keith', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3409, 'birth', '2016-0169', '12', '9', 'MANOLO FORTICH', '2016-08-12', 'Jablan, Andrea Dayog', 'Andrea', 'Dayog', 'Jablan', NULL, NULL, NULL, '2016-07-14', NULL, 'Jablan, Rafael Tuling', 'Dayog, Zsanine', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3410, 'birth', '2016-0170', '39', '25', 'MANOLO FORTICH', '2016-10-14', 'Saculingan, Miguel Achas', 'Miguel', 'Achas', 'Saculingan', NULL, NULL, NULL, '2016-09-16', NULL, 'Saculingan, Flong', 'Achas, Cathaleen Bustillo', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3411, 'birth', '2017-0171', '20', '10', 'MANOLO FORTICH', '2017-08-11', 'Rosello, Kian Pahamotang', 'Kian', 'Pahamotang', 'Rosello', NULL, NULL, NULL, '2017-07-09', NULL, 'Rosello, Justin Jablan', 'Pahamotang, Angel', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3412, 'birth', '2017-0172', '13', '89', 'MANOLO FORTICH', '2017-08-30', 'Saculingan, Mark Angelo Rosello', 'Mark', 'Angelo Rosello', 'Saculingan', NULL, NULL, NULL, '2017-08-11', NULL, 'Saculingan, Mark Angelo', 'Rosello, Keith Bustillo', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3413, 'birth', '2017-0173', '16', '33', 'MANOLO FORTICH', '2017-09-17', 'Saculingan, Marc Achas', 'Marc', 'Achas', 'Saculingan', NULL, NULL, NULL, '2017-09-06', NULL, 'Saculingan, Flong', 'Achas, Cathaleen Bustillo', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3414, 'birth', '2017-0174', '56', '17', 'MANOLO FORTICH', '2018-01-31', 'Montanez, Angel Bustillo', 'Angel', 'Bustillo', 'Montanez', NULL, NULL, NULL, '2017-12-22', NULL, 'Montanez, Xander', 'Bustillo, Reign Binanlao', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3415, 'birth', '2017-0175', '55', '15', 'MANOLO FORTICH', '2018-02-02', 'Dayog, Kian Bustillo', 'Kian', 'Bustillo', 'Dayog', NULL, NULL, NULL, '2017-12-27', NULL, 'Dayog, Mark Angelo', 'Bustillo, Cathaleen Binanlao', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3416, 'birth', '2018-0176', '53', '45', 'MANOLO FORTICH', '2018-12-02', 'Dayog, Yeshu Bustillo', 'Yeshu', 'Bustillo', 'Dayog', NULL, NULL, NULL, '2018-11-16', NULL, 'Dayog, Mark Angelo', 'Bustillo, Cathaleen Binanlao', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3417, 'birth', '2018-0177', '31', '47', 'MANOLO FORTICH', '2019-01-03', 'Bustillo, Miguel Achas', 'Miguel', 'Achas', 'Bustillo', NULL, NULL, NULL, '2018-12-18', NULL, 'Bustillo, Xander Horca', 'Achas, Reign', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3418, 'birth', '2019-0178', '46', '58', 'MANOLO FORTICH', '2019-05-12', 'Rosello, Jojale Ramirez', 'Jojale', 'Ramirez', 'Rosello', NULL, NULL, NULL, '2019-04-10', NULL, 'Rosello, Vince Montanez', 'Ramirez, Kezia', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3419, 'birth', '2019-0179', '60', '15', 'MANOLO FORTICH', '2019-07-14', 'Achas, Kezia Bustillo', 'Kezia', 'Bustillo', 'Achas', NULL, NULL, NULL, '2019-06-21', NULL, 'Achas, Rafael', 'Bustillo, Belle', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3420, 'birth', '2019-0180', '19', '72', 'MANOLO FORTICH', '2019-08-24', 'Rosello, Marc Guerero', 'Marc', 'Guerero', 'Rosello', NULL, NULL, NULL, '2019-07-30', NULL, 'Rosello, Zayd Jablan', 'Guerero, Jojale', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3421, 'birth', '2020-0181', '14', '41', 'MANOLO FORTICH', '2020-11-11', 'Bustillo, Marc Bustillo', 'Marc', 'Bustillo', 'Bustillo', NULL, NULL, NULL, '2020-10-03', NULL, 'Bustillo, Vince Horca', 'Bustillo, Belle', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3422, 'birth', '2021-0182', '16', '52', 'MANOLO FORTICH', '2021-03-08', 'Tabada, Jojale Jablan', 'Jojale', 'Jablan', 'Tabada', NULL, NULL, NULL, '2021-02-02', NULL, 'Tabada, Kian', 'Jablan, Zsanine Abao', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3423, 'birth', '2021-0183', '48', '89', 'MANOLO FORTICH', '2021-04-17', 'Montanez, Kian Bustillo', 'Kian', 'Bustillo', 'Montanez', NULL, NULL, NULL, '2021-03-21', NULL, 'Montanez, Xander', 'Bustillo, Reign Binanlao', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3424, 'birth', '2021-0184', '24', '71', 'MANOLO FORTICH', '2021-06-09', 'Rosello, Belle Ramirez', 'Belle', 'Ramirez', 'Rosello', NULL, NULL, NULL, '2021-06-03', NULL, 'Rosello, Vince Montanez', 'Ramirez, Kezia', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3425, 'birth', '2021-0185', '45', '67', 'MANOLO FORTICH', '2021-10-10', 'Achas, Vince Compra', 'Vince', 'Compra', 'Achas', NULL, NULL, NULL, '2021-08-28', NULL, 'Achas, Kian Bustillo', 'Compra, Skye', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3426, 'birth', '2021-0186', '18', '53', 'MANOLO FORTICH', '2021-10-10', 'Bustillo, Belle Cabiles', 'Belle', 'Cabiles', 'Bustillo', NULL, NULL, NULL, '2021-09-09', NULL, 'Bustillo, Mark Angelo Binanlao', 'Cabiles, Yeshu', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3427, 'birth', '2022-0187', '16', '85', 'MANOLO FORTICH', '2022-03-27', 'Achas, Angel Compra', 'Angel', 'Compra', 'Achas', NULL, NULL, NULL, '2022-02-24', NULL, 'Achas, Kian Bustillo', 'Compra, Skye', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3428, 'birth', '2022-0188', '53', '17', 'MANOLO FORTICH', '2022-08-02', 'Jablan, Reign Dayog', 'Reign', 'Dayog', 'Jablan', NULL, NULL, NULL, '2022-07-08', NULL, 'Jablan, Rafael Tuling', 'Dayog, Zsanine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3429, 'birth', '2022-0189', '10', '33', 'MANOLO FORTICH', '2022-10-03', 'Abarre, Xander Edem', 'Xander', 'Edem', 'Abarre', NULL, NULL, NULL, '2022-09-10', NULL, 'Abarre, Mark Angelo', 'Edem, Reign Bustillo', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3430, 'birth', '2023-0190', '18', '21', 'MANOLO FORTICH', '2023-09-19', 'Bustillo, Belle Edem', 'Belle', 'Edem', 'Bustillo', NULL, NULL, NULL, '2023-08-12', NULL, 'Bustillo, Marc', 'Edem, Jojale Bustillo', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3431, 'birth', '2023-0191', '58', '76', 'MANOLO FORTICH', '2023-12-26', 'Abarre, Enzo Edem', 'Enzo', 'Edem', 'Abarre', NULL, NULL, NULL, '2023-12-09', NULL, 'Abarre, Mark Angelo', 'Edem, Reign Bustillo', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3432, 'birth', '2024-0192', '21', '66', 'MANOLO FORTICH', '2024-02-01', 'Amoyo, Kyle Edem', 'Kyle', 'Edem', 'Amoyo', NULL, NULL, NULL, '2024-01-25', NULL, 'Amoyo, Justin', 'Edem, Reign Bustillo', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3433, 'birth', '2024-0193', '24', '59', 'MANOLO FORTICH', '2024-04-21', 'Caballero, Vince Jablan', 'Vince', 'Jablan', 'Caballero', NULL, NULL, NULL, '2024-03-26', NULL, 'Caballero, Vince', 'Jablan, Yeshu Abao', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3434, 'birth', '2024-0194', '19', '42', 'MANOLO FORTICH', '2024-06-29', 'Bustillo, Gabriel Bustillo', 'Gabriel', 'Bustillo', 'Bustillo', NULL, NULL, NULL, '2024-06-17', NULL, 'Bustillo, Vince Horca', 'Bustillo, Belle', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3435, 'birth', '2025-0195', '43', '7', 'MANOLO FORTICH', '2025-05-27', 'Achas, Cathaleen Bacalso', 'Cathaleen', 'Bacalso', 'Achas', NULL, NULL, NULL, '2025-04-23', NULL, 'Achas, Flong Bustillo', 'Bacalso, Cathaleen', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3436, 'birth', '2025-0196', '52', '44', 'MANOLO FORTICH', '2025-11-17', 'Rosello, Reign Guerero', 'Reign', 'Guerero', 'Rosello', NULL, NULL, NULL, '2025-10-09', NULL, 'Rosello, Zayd Jablan', 'Guerero, Jojale', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3437, 'birth', '2025-0197', '17', '86', 'MANOLO FORTICH', '2026-01-01', 'Bustillo, Skye Achas', 'Skye', 'Achas', 'Bustillo', NULL, NULL, NULL, '2025-11-24', NULL, 'Bustillo, Xander Horca', 'Achas, Reign', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3438, 'birth', '2026-0198', '43', '48', 'MANOLO FORTICH', '2026-03-05', 'Achas, Angel Bustillo', 'Angel', 'Bustillo', 'Achas', NULL, NULL, NULL, '2026-02-12', NULL, 'Achas, Rafael', 'Bustillo, Belle', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3439, 'birth', '2026-0199', '42', '72', 'MANOLO FORTICH', '2026-05-04', 'Caballero, Flong Jablan', 'Flong', 'Jablan', 'Caballero', NULL, NULL, NULL, '2026-04-26', NULL, 'Caballero, Vince', 'Jablan, Yeshu Abao', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3440, 'birth', '2026-0200', '60', '90', 'MANOLO FORTICH', '2026-08-21', 'Conlayan, Xander Rosello', 'Xander', 'Rosello', 'Conlayan', NULL, NULL, NULL, '2026-08-03', NULL, 'Conlayan, Xander', 'Rosello, Skye Edem', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3441, 'birth', '2026-0201', '28', '55', 'MANOLO FORTICH', '2026-11-17', 'Rosello, Zsanine Binanlao', 'Zsanine', 'Binanlao', 'Rosello', NULL, NULL, NULL, '2026-10-19', NULL, 'Rosello, Xander Bustillo', 'Binanlao, Keith', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3442, 'birth', '1961-0202', '14', '32', 'MANOLO FORTICH', '1961-09-03', 'Jablan, Godofredo', 'Godofredo', NULL, 'Jablan', NULL, NULL, NULL, '1961-08-23', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3443, 'birth', '1962-0203', '27', '37', 'MANOLO FORTICH', '1962-06-05', 'Saculingan, Asuncion', 'Asuncion', NULL, 'Saculingan', NULL, NULL, NULL, '1962-04-28', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3444, 'birth', '1969-0204', '10', '86', 'MANOLO FORTICH', '1969-12-12', 'Rosello, Josephine', 'Josephine', NULL, 'Rosello', NULL, NULL, NULL, '1969-10-29', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3445, 'birth', '1984-0205', '60', '8', 'MANOLO FORTICH', '1984-04-08', 'Rosello, Bernard', 'Bernard', NULL, 'Rosello', NULL, NULL, NULL, '1984-04-03', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3446, 'birth', '1985-0206', '47', '47', 'MANOLO FORTICH', '1985-02-25', 'Libanta, Estrelleta', 'Estrelleta', NULL, 'Libanta', NULL, NULL, NULL, '1985-01-25', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3447, 'birth', '1987-0207', '14', '57', 'MANOLO FORTICH', '1987-10-30', 'Binanlao, Charlene', 'Charlene', NULL, 'Binanlao', NULL, NULL, NULL, '1987-09-23', NULL, NULL, NULL, NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3448, 'birth', '1990-0208', '24', '60', 'MANOLO FORTICH', '1990-09-09', 'Achas, Romeo', 'Romeo', NULL, 'Achas', NULL, NULL, NULL, '1990-07-31', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3449, 'birth', '1991-0209', '16', '31', 'MANOLO FORTICH', '1991-08-04', 'Rosello, Herson', 'Herson', NULL, 'Rosello', NULL, NULL, NULL, '1991-07-08', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3450, 'birth', '1991-0210', '20', '23', 'MANOLO FORTICH', '1991-10-08', 'Abao, Wilma', 'Wilma', NULL, 'Abao', NULL, NULL, NULL, '1991-08-30', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3451, 'birth', '1993-0211', '50', '25', 'MANOLO FORTICH', '1993-03-18', 'Horca, Evangeline', 'Evangeline', NULL, 'Horca', NULL, NULL, NULL, '1993-02-21', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3452, 'birth', '1994-0212', '24', '12', 'MANOLO FORTICH', '1994-04-03', 'Tuling, Cristina', 'Cristina', NULL, 'Tuling', NULL, NULL, NULL, '1994-02-19', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3453, 'birth', '1994-0213', '27', '2', 'MANOLO FORTICH', '1995-01-06', 'Edem, Alfredo', 'Alfredo', NULL, 'Edem', NULL, NULL, NULL, '1994-11-22', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3454, 'birth', '2002-0214', '16', '32', 'MANOLO FORTICH', '2002-07-13', 'Pahamotang, Angel', 'Angel', NULL, 'Pahamotang', NULL, NULL, NULL, '2002-05-31', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3455, 'birth', '2002-0215', '53', '22', 'MANOLO FORTICH', '2002-10-27', 'Saculingan, Flong', 'Flong', NULL, 'Saculingan', NULL, NULL, NULL, '2002-10-03', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3456, 'birth', '2003-0216', '49', '76', 'MANOLO FORTICH', '2003-04-27', 'Binanlao, Keith', 'Keith', NULL, 'Binanlao', NULL, NULL, NULL, '2003-04-13', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3457, 'birth', '2003-0217', '42', '50', 'MANOLO FORTICH', '2003-05-24', 'Saculingan, Mark Angelo', 'Mark', 'Angelo', 'Saculingan', NULL, NULL, NULL, '2003-04-28', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3458, 'birth', '2003-0218', '49', '7', 'MANOLO FORTICH', '2003-12-08', 'Abarre, Mark Angelo', 'Mark', 'Angelo', 'Abarre', NULL, NULL, NULL, '2003-11-15', NULL, NULL, NULL, NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3459, 'birth', '2003-0219', '24', '52', 'MANOLO FORTICH', '2004-01-15', 'Bacalso, Cathaleen', 'Cathaleen', NULL, 'Bacalso', NULL, NULL, NULL, '2003-12-16', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3460, 'birth', '2004-0220', '38', '38', 'MANOLO FORTICH', '2004-02-08', 'Nacino, Rafael', 'Rafael', NULL, 'Nacino', NULL, NULL, NULL, '2004-01-07', NULL, NULL, NULL, NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3461, 'birth', '2004-0221', '45', '66', 'MANOLO FORTICH', '2004-08-17', 'Bustillo, Belle', 'Belle', NULL, 'Bustillo', NULL, NULL, NULL, '2004-07-09', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3462, 'birth', '2005-0222', '44', '28', 'MANOLO FORTICH', '2005-01-07', 'Miso, Mikaela', 'Mikaela', NULL, 'Miso', NULL, NULL, NULL, '2005-01-02', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3463, 'birth', '2005-0223', '40', '13', 'MANOLO FORTICH', '2005-08-03', 'Compra, Skye', 'Skye', NULL, 'Compra', NULL, NULL, NULL, '2005-07-01', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3464, 'birth', '2006-0224', '44', '65', 'MANOLO FORTICH', '2006-11-02', 'Guerero, Jojale', 'Jojale', NULL, 'Guerero', NULL, NULL, NULL, '2006-09-30', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3465, 'birth', '2007-0225', '48', '50', 'MANOLO FORTICH', '2007-04-02', 'Dayog, Mark Angelo', 'Mark', 'Angelo', 'Dayog', NULL, NULL, NULL, '2007-02-18', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3466, 'birth', '2007-0226', '36', '88', 'MANOLO FORTICH', '2007-07-13', 'Amoyo, Justin', 'Justin', NULL, 'Amoyo', NULL, NULL, NULL, '2007-06-08', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3467, 'birth', '2007-0227', '36', '11', 'MANOLO FORTICH', '2007-08-21', 'Caballero, Vince', 'Vince', NULL, 'Caballero', NULL, NULL, NULL, '2007-08-12', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3468, 'birth', '2007-0228', '33', '51', 'MANOLO FORTICH', '2007-10-10', 'Tabada, Kian', 'Kian', NULL, 'Tabada', NULL, NULL, NULL, '2007-09-23', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3469, 'birth', '2008-0229', '54', '83', 'MANOLO FORTICH', '2008-07-25', 'Conlayan, Xander', 'Xander', NULL, 'Conlayan', NULL, NULL, NULL, '2008-06-12', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3470, 'birth', '2009-0230', '38', '76', 'MANOLO FORTICH', '2009-04-19', 'Montanez, Xander', 'Xander', NULL, 'Montanez', NULL, NULL, NULL, '2009-03-08', NULL, NULL, NULL, NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3471, 'birth', '2009-0231', '16', '2', 'MANOLO FORTICH', '2009-04-28', 'Ramirez, Kezia', 'Kezia', NULL, 'Ramirez', NULL, NULL, NULL, '2009-04-04', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3472, 'birth', '2009-0232', '44', '24', 'MANOLO FORTICH', '2010-01-04', 'Cabiles, Yeshu', 'Yeshu', NULL, 'Cabiles', NULL, NULL, NULL, '2009-11-26', NULL, NULL, NULL, NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3473, 'birth', '2011-0233', '26', '45', 'MANOLO FORTICH', '2011-04-28', 'Achas, Reign', 'Reign', NULL, 'Achas', NULL, NULL, NULL, '2011-04-23', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3474, 'birth', '2011-0234', '60', '29', 'MANOLO FORTICH', '2011-07-12', 'Bustillo, Marc', 'Marc', NULL, 'Bustillo', NULL, NULL, NULL, '2011-06-03', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3475, 'birth', '2011-0235', '25', '33', 'MANOLO FORTICH', '2011-08-17', 'Dayog, Zsanine', 'Zsanine', NULL, 'Dayog', NULL, NULL, NULL, '2011-07-05', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3476, 'birth', '2011-0236', '55', '79', 'MANOLO FORTICH', '2011-10-25', 'Achas, Rafael', 'Rafael', NULL, 'Achas', NULL, NULL, NULL, '2011-09-15', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3477, 'birth', '1939-0237', '55', '9', 'MANOLO FORTICH', '1939-11-03', 'Edem, Leoncio', 'Leoncio', NULL, 'Edem', NULL, NULL, NULL, '1939-10-08', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3478, 'birth', '1944-0238', '39', '82', 'MANOLO FORTICH', '1944-07-25', 'Horca, Maria', 'Maria', NULL, 'Horca', NULL, NULL, NULL, '1944-06-28', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3479, 'birth', '1961-0239', '14', '66', 'MANOLO FORTICH', '1961-07-11', 'Edem, Cirilo Horca', 'Cirilo', 'Horca', 'Edem', NULL, NULL, NULL, '1961-07-06', NULL, 'Edem, Leoncio', 'Horca, Maria', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3480, 'birth', '1964-0240', '52', '84', 'MANOLO FORTICH', '1964-07-27', 'Edem, Teodoro Horca', 'Teodoro', 'Horca', 'Edem', NULL, NULL, NULL, '1964-07-05', NULL, 'Edem, Leoncio', 'Horca, Maria', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3481, 'birth', '1971-0241', '24', '9', 'MANOLO FORTICH', '1971-08-20', 'Edem, Sarami Horca', 'Sarami', 'Horca', 'Edem', NULL, NULL, NULL, '1971-08-10', NULL, 'Edem, Leoncio', 'Horca, Maria', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3482, 'birth', '1972-0242', '10', '10', 'MANOLO FORTICH', '1972-07-13', 'Edem, Wilma Horca', 'Wilma', 'Horca', 'Edem', NULL, NULL, NULL, '1972-06-03', NULL, 'Edem, Leoncio', 'Horca, Maria', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3483, 'birth', '1982-0243', '41', '28', 'MANOLO FORTICH', '1982-09-16', 'Edem, Sarami Libanta', 'Sarami', 'Libanta', 'Edem', NULL, NULL, NULL, '1982-08-24', NULL, 'Edem, Cirilo Horca', 'Libanta, Felicisima', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3484, 'birth', '1983-0244', '16', '90', 'MANOLO FORTICH', '1983-10-28', 'Bacalso, Cristina Edem', 'Cristina', 'Edem', 'Bacalso', NULL, NULL, NULL, '1983-09-15', NULL, 'Bacalso, Renato', 'Edem, Sarami Horca', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3485, 'birth', '1984-0245', '32', '69', 'MANOLO FORTICH', '1984-10-27', 'Bacalso, Evangeline Edem', 'Evangeline', 'Edem', 'Bacalso', NULL, NULL, NULL, '1984-09-18', NULL, 'Bacalso, Renato', 'Edem, Sarami Horca', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3486, 'birth', '1987-0246', '45', '31', 'MANOLO FORTICH', '1987-06-30', 'Edem, Alfredo Libanta', 'Alfredo', 'Libanta', 'Edem', NULL, NULL, NULL, '1987-06-01', NULL, 'Edem, Cirilo Horca', 'Libanta, Felicisima', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3487, 'birth', '1987-0247', '38', '32', 'MANOLO FORTICH', '1987-07-13', 'Sarzosa, Grace Edem', 'Grace', 'Edem', 'Sarzosa', NULL, NULL, NULL, '1987-07-05', NULL, 'Sarzosa, Juan', 'Edem, Wilma Horca', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3488, 'birth', '1987-0248', '48', '78', 'MANOLO FORTICH', '1987-10-27', 'Edem, Nestor Chavez', 'Nestor', 'Chavez', 'Edem', NULL, NULL, NULL, '1987-10-01', NULL, 'Edem, Teodoro Horca', 'Chavez, Marilou', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3489, 'birth', '1988-0249', '29', '74', 'MANOLO FORTICH', '1989-02-01', 'Edem, Mario Chavez', 'Mario', 'Chavez', 'Edem', NULL, NULL, NULL, '1988-12-23', NULL, 'Edem, Teodoro Horca', 'Chavez, Marilou', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3490, 'birth', '1989-0250', '45', '2', 'MANOLO FORTICH', '1989-09-30', 'Edem, Leizl Libanta', 'Leizl', 'Libanta', 'Edem', NULL, NULL, NULL, '1989-08-20', NULL, 'Edem, Cirilo Horca', 'Libanta, Felicisima', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3491, 'birth', '1990-0251', '37', '23', 'MANOLO FORTICH', '1990-03-19', 'Sarzosa, Romeo Edem', 'Romeo', 'Edem', 'Sarzosa', NULL, NULL, NULL, '1990-02-15', NULL, 'Sarzosa, Juan', 'Edem, Wilma Horca', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3492, 'birth', '1990-0252', '10', '54', 'MANOLO FORTICH', '1990-05-29', 'Bacalso, Estrelleta Edem', 'Estrelleta', 'Edem', 'Bacalso', NULL, NULL, NULL, '1990-04-24', NULL, 'Bacalso, Renato', 'Edem, Sarami Horca', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3493, 'birth', '1994-0253', '37', '76', 'MANOLO FORTICH', '1994-02-28', 'Sarzosa, Evangeline Edem', 'Evangeline', 'Edem', 'Sarzosa', NULL, NULL, NULL, '1994-01-26', NULL, 'Sarzosa, Juan', 'Edem, Wilma Horca', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3494, 'birth', '2002-0254', '12', '83', 'MANOLO FORTICH', '2002-06-22', 'Oclarit, Mikaela Bacalso', 'Mikaela', 'Bacalso', 'Oclarit', NULL, NULL, NULL, '2002-06-18', NULL, 'Oclarit, Samuel', 'Bacalso, Estrelleta Edem', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3495, 'birth', '2002-0255', '33', '71', 'MANOLO FORTICH', '2002-07-11', 'Abarre, Nyla Edem', 'Nyla', 'Edem', 'Abarre', NULL, NULL, NULL, '2002-07-05', NULL, 'Abarre, Jerry', 'Edem, Leizl Libanta', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3496, 'birth', '2002-0256', '38', '54', 'MANOLO FORTICH', '2002-11-01', 'Sarzosa, Angel Libanta', 'Angel', 'Libanta', 'Sarzosa', NULL, NULL, NULL, '2002-10-26', NULL, 'Sarzosa, Romeo Edem', 'Libanta, Jehiel', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3497, 'birth', '2003-0257', '45', '14', 'MANOLO FORTICH', '2003-07-16', 'Pahamotang, Reign Sarzosa', 'Reign', 'Sarzosa', 'Pahamotang', NULL, NULL, NULL, '2003-06-20', NULL, 'Pahamotang, Emmanuel', 'Sarzosa, Evangeline', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3498, 'birth', '2004-0258', '25', '64', 'MANOLO FORTICH', '2004-07-22', 'Edem, Jojale Bustillo', 'Jojale', 'Bustillo', 'Edem', NULL, NULL, NULL, '2004-07-07', NULL, 'Edem, Mario Chavez', 'Bustillo, Grace', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3499, 'birth', '2005-0259', '42', '83', 'MANOLO FORTICH', '2005-07-01', 'Oclarit, Zayd Bacalso', 'Zayd', 'Bacalso', 'Oclarit', NULL, NULL, NULL, '2005-05-25', NULL, 'Oclarit, Samuel', 'Bacalso, Estrelleta Edem', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3500, 'birth', '2005-0260', '55', '41', 'MANOLO FORTICH', '2005-11-06', 'Pahamotang, Kyla Sarzosa', 'Kyla', 'Sarzosa', 'Pahamotang', NULL, NULL, NULL, '2005-10-25', NULL, 'Pahamotang, Emmanuel', 'Sarzosa, Evangeline', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3501, 'birth', '2006-0261', '49', '88', 'MANOLO FORTICH', '2006-06-02', 'Abarre, Trisha Edem', 'Trisha', 'Edem', 'Abarre', NULL, NULL, NULL, '2006-05-02', NULL, 'Abarre, Jerry', 'Edem, Leizl Libanta', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3502, 'birth', '2006-0262', '36', '53', 'MANOLO FORTICH', '2006-07-22', 'Horca, Gabriel Bacalso', 'Gabriel', 'Bacalso', 'Horca', NULL, NULL, NULL, '2006-06-19', NULL, 'Horca, Nestor', 'Bacalso, Cristina Edem', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3503, 'birth', '2006-0263', '41', '81', 'MANOLO FORTICH', '2006-10-16', 'Sarzosa, Zayd Libanta', 'Zayd', 'Libanta', 'Sarzosa', NULL, NULL, NULL, '2006-09-03', NULL, 'Sarzosa, Romeo Edem', 'Libanta, Jehiel', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3504, 'birth', '2007-0264', '25', '19', 'MANOLO FORTICH', '2007-04-15', 'Pahamotang, Jojale Sarzosa', 'Jojale', 'Sarzosa', 'Pahamotang', NULL, NULL, NULL, '2007-03-26', NULL, 'Pahamotang, Emmanuel', 'Sarzosa, Evangeline', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3505, 'birth', '2007-0265', '42', '59', 'MANOLO FORTICH', '2007-08-08', 'Cabiles, Kezia Edem', 'Kezia', 'Edem', 'Cabiles', NULL, NULL, NULL, '2007-07-31', NULL, 'Cabiles, Johnnas', 'Edem, Sarami Libanta', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3506, 'birth', '2007-0266', '20', '42', 'MANOLO FORTICH', '2007-11-15', 'Chavez, Kyle Bacalso', 'Kyle', 'Bacalso', 'Chavez', NULL, NULL, NULL, '2007-10-24', NULL, 'Chavez, Emmanuel', 'Bacalso, Evangeline Edem', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3507, 'birth', '2008-0267', '18', '34', 'MANOLO FORTICH', '2008-01-17', 'Cabiles, Kezia Edem', 'Kezia', 'Edem', 'Cabiles', NULL, NULL, NULL, '2008-01-08', NULL, 'Cabiles, Johnnas', 'Edem, Sarami Libanta', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3508, 'birth', '2008-0268', '20', '90', 'MANOLO FORTICH', '2008-03-23', 'Edem, Trisha Guijon', 'Trisha', 'Guijon', 'Edem', NULL, NULL, NULL, '2008-03-02', NULL, 'Edem, Nestor Chavez', 'Guijon, Josephine', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3509, 'birth', '2008-0269', '45', '17', 'MANOLO FORTICH', '2008-04-14', 'Edem, Zayd Bustillo', 'Zayd', 'Bustillo', 'Edem', NULL, NULL, NULL, '2008-03-19', NULL, 'Edem, Mario Chavez', 'Bustillo, Grace', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3510, 'birth', '2008-0270', '28', '64', 'MANOLO FORTICH', '2008-07-23', 'Cabiles, Vince Edem', 'Vince', 'Edem', 'Cabiles', NULL, NULL, NULL, '2008-07-17', NULL, 'Cabiles, Johnnas', 'Edem, Sarami Libanta', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3511, 'birth', '2008-0271', '12', '6', 'MANOLO FORTICH', '2008-10-31', 'Edem, Kian Sarzosa', 'Kian', 'Sarzosa', 'Edem', NULL, NULL, NULL, '2008-10-03', NULL, 'Edem, Alfredo Libanta', 'Sarzosa, Evangeline', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3512, 'birth', '2009-0272', '37', '65', 'MANOLO FORTICH', '2009-03-19', 'Chavez, Zayd Bacalso', 'Zayd', 'Bacalso', 'Chavez', NULL, NULL, NULL, '2009-03-05', NULL, 'Chavez, Emmanuel', 'Bacalso, Evangeline Edem', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3513, 'birth', '2010-0273', '39', '20', 'MANOLO FORTICH', '2010-02-03', 'Edem, Vince Sarzosa', 'Vince', 'Sarzosa', 'Edem', NULL, NULL, NULL, '2010-01-01', NULL, 'Edem, Alfredo Libanta', 'Sarzosa, Evangeline', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3514, 'birth', '2010-0274', '41', '66', 'MANOLO FORTICH', '2010-02-16', 'Miso, Keith Sarzosa', 'Keith', 'Sarzosa', 'Miso', NULL, NULL, NULL, '2010-02-01', NULL, 'Miso, Renato', 'Sarzosa, Grace Edem', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3515, 'birth', '2012-0275', '53', '41', 'MANOLO FORTICH', '2012-02-29', 'Edem, Angel Guijon', 'Angel', 'Guijon', 'Edem', NULL, NULL, NULL, '2012-01-29', NULL, 'Edem, Nestor Chavez', 'Guijon, Josephine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3516, 'birth', '2012-0276', '35', '79', 'MANOLO FORTICH', '2012-07-04', 'Edem, Zayd Sarzosa', 'Zayd', 'Sarzosa', 'Edem', NULL, NULL, NULL, '2012-06-11', NULL, 'Edem, Alfredo Libanta', 'Sarzosa, Evangeline', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3517, 'birth', '2012-0277', '52', '80', 'MANOLO FORTICH', '2012-08-04', 'Oclarit, Zayd Bacalso', 'Zayd', 'Bacalso', 'Oclarit', NULL, NULL, NULL, '2012-07-23', NULL, 'Oclarit, Samuel', 'Bacalso, Estrelleta Edem', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3518, 'birth', '2012-0278', '25', '21', 'MANOLO FORTICH', '2012-09-19', 'Miso, Xander Sarzosa', 'Xander', 'Sarzosa', 'Miso', NULL, NULL, NULL, '2012-08-08', NULL, 'Miso, Renato', 'Sarzosa, Grace Edem', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3519, 'birth', '2012-0279', '26', '26', 'MANOLO FORTICH', '2012-09-06', 'Chavez, Kian Bacalso', 'Kian', 'Bacalso', 'Chavez', NULL, NULL, NULL, '2012-08-16', NULL, 'Chavez, Emmanuel', 'Bacalso, Evangeline Edem', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3520, 'birth', '2012-0280', '53', '52', 'MANOLO FORTICH', '2012-10-26', 'Sarzosa, Belle Libanta', 'Belle', 'Libanta', 'Sarzosa', NULL, NULL, NULL, '2012-09-23', NULL, 'Sarzosa, Romeo Edem', 'Libanta, Jehiel', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3521, 'birth', '2012-0281', '38', '60', 'MANOLO FORTICH', '2013-01-09', 'Horca, Vince Bacalso', 'Vince', 'Bacalso', 'Horca', NULL, NULL, NULL, '2012-11-26', NULL, 'Horca, Nestor', 'Bacalso, Cristina Edem', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3522, 'birth', '2012-0282', '60', '44', 'MANOLO FORTICH', '2013-01-04', 'Abarre, Jerry May Edem', 'Jerry', 'May Edem', 'Abarre', NULL, NULL, NULL, '2012-12-07', NULL, 'Abarre, Jerry', 'Edem, Leizl Libanta', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3523, 'birth', '2014-0283', '60', '47', 'MANOLO FORTICH', '2015-01-02', 'Candillada, Zsanine Pahamotang', 'Zsanine', 'Pahamotang', 'Candillada', NULL, NULL, NULL, '2014-12-17', NULL, 'Candillada, Mark Angelo', 'Pahamotang, Jojale Sarzosa', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3524, 'birth', '2014-0284', '24', '62', 'MANOLO FORTICH', '2015-01-27', 'Edem, Angel Amoyo', 'Angel', 'Amoyo', 'Edem', NULL, NULL, NULL, '2014-12-22', NULL, 'Edem, Zayd Amoyo', 'Amoyo, Keith', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3525, 'birth', '2015-0285', '56', '75', 'MANOLO FORTICH', '2015-07-15', 'Chavez, Mark Angelo Rosello', 'Mark', 'Angelo Rosello', 'Chavez', NULL, NULL, NULL, '2015-07-06', NULL, 'Chavez, Kian Bacalso', 'Rosello, Andrea', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3526, 'birth', '2015-0286', '23', '20', 'MANOLO FORTICH', '2015-11-07', 'Jablan, Kian Cabiles', 'Kian', 'Cabiles', 'Jablan', NULL, NULL, NULL, '2015-09-24', NULL, 'Jablan, Mark Angelo', 'Cabiles, Kezia Edem', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3527, 'birth', '2015-0287', '24', '67', 'MANOLO FORTICH', '2015-10-19', 'Tabada, Trisha Miso', 'Trisha', 'Miso', 'Tabada', NULL, NULL, NULL, '2015-09-30', NULL, 'Tabada, Flong', 'Miso, Keith Sarzosa', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3528, 'birth', '2016-0288', '60', '69', 'MANOLO FORTICH', '2016-04-01', 'Edem, Kyle Ramirez', 'Kyle', 'Ramirez', 'Edem', NULL, NULL, NULL, '2016-02-28', NULL, 'Edem, Vince Sarzosa', 'Ramirez, Andrea', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3529, 'birth', '2016-0289', '42', '88', 'MANOLO FORTICH', '2016-06-23', 'Edem, Kezia Jablan', 'Kezia', 'Jablan', 'Edem', NULL, NULL, NULL, '2016-06-08', NULL, 'Edem, Kian Sarzosa', 'Jablan, Skye', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3530, 'birth', '2016-0290', '58', '71', 'MANOLO FORTICH', '2016-09-10', 'Chavez, Cathaleen Binanlao', 'Cathaleen', 'Binanlao', 'Chavez', NULL, NULL, NULL, '2016-07-27', NULL, 'Chavez, Kyle Bacalso', 'Binanlao, Reign', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3531, 'birth', '2016-0291', '58', '75', 'MANOLO FORTICH', '2016-08-30', 'Edem, Trisha Guijon', 'Trisha', 'Guijon', 'Edem', NULL, NULL, NULL, '2016-08-27', NULL, 'Edem, Zayd Amoyo', 'Guijon, Belle', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3532, 'birth', '2016-0292', '18', '81', 'MANOLO FORTICH', '2016-12-23', 'Tuling, Zayd Cabiles', 'Zayd', 'Cabiles', 'Tuling', NULL, NULL, NULL, '2016-11-13', NULL, 'Tuling, Alonso', 'Cabiles, Kezia Edem', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3533, 'birth', '2017-0293', '39', '58', 'MANOLO FORTICH', '2017-01-29', 'Dignos, Zsanine Oclarit', 'Zsanine', 'Oclarit', 'Dignos', NULL, NULL, NULL, '2017-01-05', NULL, 'Dignos, Flong', 'Oclarit, Mikaela Bacalso', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3534, 'birth', '2017-0294', '49', '85', 'MANOLO FORTICH', '2017-04-24', 'Abarre, Reign Caballero', 'Reign', 'Caballero', 'Abarre', NULL, NULL, NULL, '2017-03-15', NULL, 'Abarre, Jerry May Edem', 'Caballero, Reign Sarzosa', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3535, 'birth', '2017-0295', '59', '76', 'MANOLO FORTICH', '2017-05-30', 'Guijon, Keith Sarzosa', 'Keith', 'Sarzosa', 'Guijon', NULL, NULL, NULL, '2017-05-02', NULL, 'Guijon, Miguel', 'Sarzosa, Angel Libanta', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3536, 'birth', '2017-0296', '12', '12', 'MANOLO FORTICH', '2017-07-15', 'Abarre, Flong Edem', 'Flong', 'Edem', 'Abarre', NULL, NULL, NULL, '2017-06-13', NULL, 'Abarre, Kyle', 'Edem, Jojale Bustillo', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3537, 'birth', '2017-0297', '38', '21', 'MANOLO FORTICH', '2017-10-25', 'Lusterio, Rafael Pahamotang', 'Rafael', 'Pahamotang', 'Lusterio', NULL, NULL, NULL, '2017-09-20', NULL, 'Lusterio, Vince', 'Pahamotang, Kyla Sarzosa', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3538, 'birth', '2017-0298', '35', '77', 'MANOLO FORTICH', '2017-10-08', 'Candillada, Mark Angelo Abarre', 'Mark', 'Angelo Abarre', 'Candillada', NULL, NULL, NULL, '2017-09-24', NULL, 'Candillada, Zayd', 'Abarre, Nyla Edem', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `civil_registry_records` (`record_id`, `registry_type`, `registry_number`, `book_no`, `page_no`, `city_municipality`, `date_prepared`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `civil_status`, `date_of_event`, `place_of_event`, `father_name`, `mother_name`, `spouse_name`, `barangay`, `zone`, `remarks`, `scan_image_path`, `scan_image_path_2`, `scan_image_path_back`, `scan_image_path_back_2`, `certificate_data`, `verified_by`, `verified_at`, `created_at`, `date_of_birth`, `wife_father_name`, `wife_mother_name`, `age_at_death`, `cause_of_death`) VALUES
(3539, 'birth', '2017-0299', '10', '81', 'MANOLO FORTICH', '2017-11-09', 'Compra, Rafael Edem', 'Rafael', 'Edem', 'Compra', NULL, NULL, NULL, '2017-10-03', NULL, 'Compra, Rafael', 'Edem, Angel Guijon', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3540, 'birth', '2017-0300', '46', '30', 'MANOLO FORTICH', '2018-01-17', 'Abarre, Rafael Edem', 'Rafael', 'Edem', 'Abarre', NULL, NULL, NULL, '2017-12-29', NULL, 'Abarre, Kyle', 'Edem, Jojale Bustillo', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3541, 'birth', '2018-0301', '23', '69', 'MANOLO FORTICH', '2018-11-12', 'Ramirez, Enzo Edem', 'Enzo', 'Edem', 'Ramirez', NULL, NULL, NULL, '2018-10-13', NULL, 'Ramirez, Mark Angelo', 'Edem, Trisha Guijon', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3542, 'birth', '2018-0302', '40', '22', 'MANOLO FORTICH', '2018-12-21', 'Sudaria, Skye Abarre', 'Skye', 'Abarre', 'Sudaria', NULL, NULL, NULL, '2018-11-10', NULL, 'Sudaria, Marc', 'Abarre, Trisha Edem', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3543, 'birth', '2020-0303', '30', '66', 'MANOLO FORTICH', '2020-10-08', 'Compra, Andrea Edem', 'Andrea', 'Edem', 'Compra', NULL, NULL, NULL, '2020-08-25', NULL, 'Compra, Rafael', 'Edem, Angel Guijon', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3544, 'birth', '2021-0304', '24', '49', 'MANOLO FORTICH', '2021-10-10', 'Edem, Miguel Jablan', 'Miguel', 'Jablan', 'Edem', NULL, NULL, NULL, '2021-09-08', NULL, 'Edem, Kian Sarzosa', 'Jablan, Skye', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3545, 'birth', '2021-0305', '42', '18', 'MANOLO FORTICH', '2021-10-30', 'Tabada, Skye Miso', 'Skye', 'Miso', 'Tabada', NULL, NULL, NULL, '2021-09-27', NULL, 'Tabada, Flong', 'Miso, Keith Sarzosa', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3546, 'birth', '2022-0306', '16', '37', 'MANOLO FORTICH', '2022-02-18', 'Chavez, Cathaleen Rosello', 'Cathaleen', 'Rosello', 'Chavez', NULL, NULL, NULL, '2022-01-04', NULL, 'Chavez, Kian Bacalso', 'Rosello, Andrea', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3547, 'birth', '2022-0307', '24', '9', 'MANOLO FORTICH', '2022-02-04', 'Ceniza, Flong Pahamotang', 'Flong', 'Pahamotang', 'Ceniza', NULL, NULL, NULL, '2022-01-18', NULL, 'Ceniza, Gabriel', 'Pahamotang, Reign Sarzosa', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3548, 'birth', '2022-0308', '36', '43', 'MANOLO FORTICH', '2022-02-25', 'Jablan, Zayd Cabiles', 'Zayd', 'Cabiles', 'Jablan', NULL, NULL, NULL, '2022-02-15', NULL, 'Jablan, Mark Angelo', 'Cabiles, Kezia Edem', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3549, 'birth', '2022-0309', '50', '19', 'MANOLO FORTICH', '2022-11-15', 'Horca, Rafael Tuling', 'Rafael', 'Tuling', 'Horca', NULL, NULL, NULL, '2022-10-09', NULL, 'Horca, Vince Ceniza', 'Tuling, Mikaela', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3550, 'birth', '2022-0310', '29', '27', 'MANOLO FORTICH', '2022-12-11', 'Chavez, Kian Nacino', 'Kian', 'Nacino', 'Chavez', NULL, NULL, NULL, '2022-11-03', NULL, 'Chavez, Zayd Bacalso', 'Nacino, Keith', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3551, 'birth', '2022-0311', '40', '21', 'MANOLO FORTICH', '2022-12-26', 'Horca, Mikaela Tuling', 'Mikaela', 'Tuling', 'Horca', NULL, NULL, NULL, '2022-11-11', NULL, 'Horca, Vince Ceniza', 'Tuling, Mikaela', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3552, 'birth', '2023-0312', '25', '29', 'MANOLO FORTICH', '2023-08-13', 'Oclarit, Cathaleen Alfeche', 'Cathaleen', 'Alfeche', 'Oclarit', NULL, NULL, NULL, '2023-08-10', NULL, 'Oclarit, Zayd', 'Alfeche, Angel', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3553, 'birth', '2024-0313', '30', '3', 'MANOLO FORTICH', '2024-03-12', 'Edem, Zsanine Guijon', 'Zsanine', 'Guijon', 'Edem', NULL, NULL, NULL, '2024-02-13', NULL, 'Edem, Zayd Amoyo', 'Guijon, Belle', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3554, 'birth', '2024-0314', '51', '42', 'MANOLO FORTICH', '2024-03-18', 'Candillada, Andrea Pahamotang', 'Andrea', 'Pahamotang', 'Candillada', NULL, NULL, NULL, '2024-03-13', NULL, 'Candillada, Mark Angelo', 'Pahamotang, Jojale Sarzosa', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3555, 'birth', '2024-0315', '35', '47', 'MANOLO FORTICH', '2024-07-08', 'Guijon, Trisha Sarzosa', 'Trisha', 'Sarzosa', 'Guijon', NULL, NULL, NULL, '2024-06-03', NULL, 'Guijon, Miguel', 'Sarzosa, Angel Libanta', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3556, 'birth', '2024-0316', '51', '53', 'MANOLO FORTICH', '2024-10-13', 'Cabiles, Skye Rosello', 'Skye', 'Rosello', 'Cabiles', NULL, NULL, NULL, '2024-09-27', NULL, 'Cabiles, Vince Edem', 'Rosello, Jojale', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3557, 'birth', '2024-0317', '10', '87', 'MANOLO FORTICH', '2024-11-05', 'Edem, Alonso Ramirez', 'Alonso', 'Ramirez', 'Edem', NULL, NULL, NULL, '2024-10-25', NULL, 'Edem, Vince Sarzosa', 'Ramirez, Andrea', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3558, 'birth', '2024-0318', '54', '75', 'MANOLO FORTICH', '2024-12-18', 'Oclarit, Marc Candillada', 'Marc', 'Candillada', 'Oclarit', NULL, NULL, NULL, '2024-11-24', NULL, 'Oclarit, Zayd', 'Candillada, Cathaleen Oclarit', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3559, 'birth', '2024-0319', '51', '78', 'MANOLO FORTICH', '2024-12-12', 'Dignos, Trisha Oclarit', 'Trisha', 'Oclarit', 'Dignos', NULL, NULL, NULL, '2024-12-03', NULL, 'Dignos, Flong', 'Oclarit, Mikaela Bacalso', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3560, 'birth', '2025-0320', '48', '12', 'MANOLO FORTICH', '2025-05-03', 'Horca, Jerry May Dayog', 'Jerry', 'May Dayog', 'Horca', NULL, NULL, NULL, '2025-04-12', NULL, 'Horca, Gabriel Bacalso', 'Dayog, Skye', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3561, 'birth', '2025-0321', '24', '79', 'MANOLO FORTICH', '2025-07-13', 'Chavez, Kyle Binanlao', 'Kyle', 'Binanlao', 'Chavez', NULL, NULL, NULL, '2025-06-30', NULL, 'Chavez, Kyle Bacalso', 'Binanlao, Reign', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3562, 'birth', '2026-0322', '56', '10', 'MANOLO FORTICH', '2026-02-18', 'Sarzosa, Yeshu Alfeche', 'Yeshu', 'Alfeche', 'Sarzosa', NULL, NULL, NULL, '2026-01-30', NULL, 'Sarzosa, Zayd', 'Alfeche, Angel', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3563, 'birth', '2026-0323', '17', '6', 'MANOLO FORTICH', '2026-07-09', 'Chavez, Mikaela Nacino', 'Mikaela', 'Nacino', 'Chavez', NULL, NULL, NULL, '2026-06-01', NULL, 'Chavez, Zayd Bacalso', 'Nacino, Keith', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3564, 'birth', '2026-0324', '26', '62', 'MANOLO FORTICH', '2026-12-15', 'Edem, Jojale Amoyo', 'Jojale', 'Amoyo', 'Edem', NULL, NULL, NULL, '2026-12-03', NULL, 'Edem, Zayd Amoyo', 'Amoyo, Keith', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3565, 'birth', '2026-0325', '34', '84', 'MANOLO FORTICH', '2026-12-15', 'Miso, Trisha Villasorda', 'Trisha', 'Villasorda', 'Miso', NULL, NULL, NULL, '2026-12-24', NULL, 'Miso, Xander Sarzosa', 'Villasorda, Keith', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3566, 'birth', '2026-0326', '12', '71', 'MANOLO FORTICH', '2026-12-15', 'Abao, Xander Sarzosa', 'Xander', 'Sarzosa', 'Abao', NULL, NULL, NULL, '2026-12-26', NULL, 'Abao, Jerry May', 'Sarzosa, Belle Libanta', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:03', NULL, NULL, NULL, NULL, NULL),
(3567, 'birth', '1960-0327', '47', '71', 'MANOLO FORTICH', '1960-03-25', 'Sarzosa, Juan', 'Juan', NULL, 'Sarzosa', NULL, NULL, NULL, '1960-03-20', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3568, 'birth', '1960-0328', '52', '14', 'MANOLO FORTICH', '1960-12-24', 'Libanta, Felicisima', 'Felicisima', NULL, 'Libanta', NULL, NULL, NULL, '1960-12-08', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3569, 'birth', '1966-0329', '20', '57', 'MANOLO FORTICH', '1966-04-28', 'Bacalso, Renato', 'Renato', NULL, 'Bacalso', NULL, NULL, NULL, '1966-03-16', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3570, 'birth', '1967-0330', '13', '12', 'MANOLO FORTICH', '1967-10-13', 'Chavez, Marilou', 'Marilou', NULL, 'Chavez', NULL, NULL, NULL, '1967-09-09', NULL, NULL, NULL, NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3571, 'birth', '1983-0331', '58', '71', 'MANOLO FORTICH', '1983-03-07', 'Abarre, Jerry', 'Jerry', NULL, 'Abarre', NULL, NULL, NULL, '1983-02-19', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3572, 'birth', '1983-0332', '15', '51', 'MANOLO FORTICH', '1983-10-17', 'Horca, Nestor', 'Nestor', NULL, 'Horca', NULL, NULL, NULL, '1983-09-09', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3573, 'birth', '1984-0333', '16', '87', 'MANOLO FORTICH', '1984-11-05', 'Chavez, Emmanuel', 'Emmanuel', NULL, 'Chavez', NULL, NULL, NULL, '1984-10-09', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3574, 'birth', '1987-0334', '19', '17', 'MANOLO FORTICH', '1987-03-27', 'Sarzosa, Evangeline', 'Evangeline', NULL, 'Sarzosa', NULL, NULL, NULL, '1987-03-24', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3575, 'birth', '1988-0335', '60', '70', 'MANOLO FORTICH', '1988-08-29', 'Guijon, Josephine', 'Josephine', NULL, 'Guijon', NULL, NULL, NULL, '1988-08-14', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3576, 'birth', '1990-0336', '15', '85', 'MANOLO FORTICH', '1990-01-27', 'Cabiles, Johnnas', 'Johnnas', NULL, 'Cabiles', NULL, NULL, NULL, '1990-01-17', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3577, 'birth', '1990-0337', '36', '84', 'MANOLO FORTICH', '1990-10-29', 'Pahamotang, Emmanuel', 'Emmanuel', NULL, 'Pahamotang', NULL, NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3578, 'birth', '1991-0338', '25', '47', 'MANOLO FORTICH', '1991-03-29', 'Miso, Renato', 'Renato', NULL, 'Miso', NULL, NULL, NULL, '1991-02-26', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3579, 'birth', '1991-0339', '17', '13', 'MANOLO FORTICH', '1991-03-21', 'Libanta, Jehiel', 'Jehiel', NULL, 'Libanta', NULL, NULL, NULL, '1991-02-27', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3580, 'birth', '1993-0340', '57', '44', 'MANOLO FORTICH', '1993-05-06', 'Bustillo, Grace', 'Grace', NULL, 'Bustillo', NULL, NULL, NULL, '1993-04-03', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3581, 'birth', '1994-0341', '50', '60', 'MANOLO FORTICH', '1994-09-18', 'Oclarit, Samuel', 'Samuel', NULL, 'Oclarit', NULL, NULL, NULL, '1994-08-05', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3582, 'birth', '2002-0342', '20', '48', 'MANOLO FORTICH', '2002-03-24', 'Sudaria, Marc', 'Marc', NULL, 'Sudaria', NULL, NULL, NULL, '2002-02-16', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3583, 'birth', '2002-0343', '45', '84', 'MANOLO FORTICH', '2002-07-22', 'Amoyo, Keith', 'Keith', NULL, 'Amoyo', NULL, NULL, NULL, '2002-07-08', NULL, NULL, NULL, NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3584, 'birth', '2002-0344', '46', '22', 'MANOLO FORTICH', '2002-11-18', 'Jablan, Skye', 'Skye', NULL, 'Jablan', NULL, NULL, NULL, '2002-11-12', NULL, NULL, NULL, NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3585, 'birth', '2003-0345', '38', '59', 'MANOLO FORTICH', '2003-02-22', 'Binanlao, Reign', 'Reign', NULL, 'Binanlao', NULL, NULL, NULL, '2003-02-12', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3586, 'birth', '2003-0346', '19', '69', 'MANOLO FORTICH', '2003-10-23', 'Rosello, Jojale', 'Jojale', NULL, 'Rosello', NULL, NULL, NULL, '2003-09-18', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3587, 'birth', '2005-0347', '59', '12', 'MANOLO FORTICH', '2005-03-25', 'Dayog, Skye', 'Skye', NULL, 'Dayog', NULL, NULL, NULL, '2005-02-13', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3588, 'birth', '2005-0348', '18', '53', 'MANOLO FORTICH', '2005-09-22', 'Ramirez, Mark Angelo', 'Mark', 'Angelo', 'Ramirez', NULL, NULL, NULL, '2005-08-18', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3589, 'birth', '2006-0349', '29', '18', 'MANOLO FORTICH', '2006-05-12', 'Alfeche, Angel', 'Angel', NULL, 'Alfeche', NULL, NULL, NULL, '2006-04-19', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3590, 'birth', '2006-0350', '15', '82', 'MANOLO FORTICH', '2006-07-19', 'Jablan, Mark Angelo', 'Mark', 'Angelo', 'Jablan', NULL, NULL, NULL, '2006-06-19', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3591, 'birth', '2007-0351', '17', '2', 'MANOLO FORTICH', '2007-03-18', 'Candillada, Zayd', 'Zayd', NULL, 'Candillada', NULL, NULL, NULL, '2007-02-01', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3592, 'birth', '2007-0352', '23', '6', 'MANOLO FORTICH', '2007-04-17', 'Tabada, Flong', 'Flong', NULL, 'Tabada', NULL, NULL, NULL, '2007-04-14', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3593, 'birth', '2007-0353', '33', '70', 'MANOLO FORTICH', '2007-12-31', 'Villasorda, Keith', 'Keith', NULL, 'Villasorda', NULL, NULL, NULL, '2007-12-04', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3594, 'birth', '2008-0354', '50', '12', 'MANOLO FORTICH', '2008-07-24', 'Guijon, Belle', 'Belle', NULL, 'Guijon', NULL, NULL, NULL, '2008-06-23', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3595, 'birth', '2008-0355', '23', '67', 'MANOLO FORTICH', '2008-10-01', 'Ceniza, Gabriel', 'Gabriel', NULL, 'Ceniza', NULL, NULL, NULL, '2008-09-27', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3596, 'birth', '2008-0356', '44', '36', 'MANOLO FORTICH', '2008-12-04', 'Compra, Rafael', 'Rafael', NULL, 'Compra', NULL, NULL, NULL, '2008-11-28', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3597, 'birth', '2009-0357', '13', '32', 'MANOLO FORTICH', '2009-03-13', 'Tuling, Alonso', 'Alonso', NULL, 'Tuling', NULL, NULL, NULL, '2009-02-11', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3598, 'birth', '2009-0358', '14', '17', 'MANOLO FORTICH', '2009-05-24', 'Alfeche, Angel', 'Angel', NULL, 'Alfeche', NULL, NULL, NULL, '2009-05-08', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3599, 'birth', '2009-0359', '37', '28', 'MANOLO FORTICH', '2009-07-11', 'Tuling, Mikaela', 'Mikaela', NULL, 'Tuling', NULL, NULL, NULL, '2009-06-13', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3600, 'birth', '2009-0360', '56', '90', 'MANOLO FORTICH', '2009-07-27', 'Candillada, Cathaleen', 'Cathaleen', NULL, 'Candillada', NULL, NULL, NULL, '2009-06-14', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3601, 'birth', '2009-0361', '38', '45', 'MANOLO FORTICH', '2009-08-08', 'Abao, Jerry May', 'Jerry', 'May', 'Abao', NULL, NULL, NULL, '2009-08-05', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3602, 'birth', '2009-0362', '54', '17', 'MANOLO FORTICH', '2009-08-27', 'Dignos, Flong', 'Flong', NULL, 'Dignos', NULL, NULL, NULL, '2009-08-17', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3603, 'birth', '2009-0363', '51', '47', 'MANOLO FORTICH', '2009-10-11', 'Rosello, Andrea', 'Andrea', NULL, 'Rosello', NULL, NULL, NULL, '2009-09-21', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3604, 'birth', '2010-0364', '27', '83', 'MANOLO FORTICH', '2010-03-21', 'Lusterio, Vince', 'Vince', NULL, 'Lusterio', NULL, NULL, NULL, '2010-03-10', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3605, 'birth', '2010-0365', '17', '31', 'MANOLO FORTICH', '2010-06-25', 'Candillada, Mark Angelo', 'Mark', 'Angelo', 'Candillada', NULL, NULL, NULL, '2010-05-22', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3606, 'birth', '2010-0366', '15', '13', 'MANOLO FORTICH', '2010-06-23', 'Guijon, Miguel', 'Miguel', NULL, 'Guijon', NULL, NULL, NULL, '2010-05-23', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3607, 'birth', '2010-0367', '10', '6', 'MANOLO FORTICH', '2010-09-20', 'Ramirez, Andrea', 'Andrea', NULL, 'Ramirez', NULL, NULL, NULL, '2010-09-03', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3608, 'birth', '2011-0368', '60', '44', 'MANOLO FORTICH', '2011-04-27', 'Nacino, Keith', 'Keith', NULL, 'Nacino', NULL, NULL, NULL, '2011-04-16', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3609, 'birth', '2011-0369', '25', '87', 'MANOLO FORTICH', '2011-10-04', 'Abarre, Kyle', 'Kyle', NULL, 'Abarre', NULL, NULL, NULL, '2011-09-22', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3610, 'birth', '2011-0370', '43', '9', 'MANOLO FORTICH', '2012-01-09', 'Caballero, Reign', 'Reign', NULL, 'Caballero', NULL, NULL, NULL, '2011-12-10', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3611, 'birth', '1943-0371', '53', '42', 'MANOLO FORTICH', '1943-07-19', 'Binanlao, Consolacion', 'Consolacion', NULL, 'Binanlao', NULL, NULL, NULL, '1943-06-29', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3612, 'birth', '1946-0372', '48', '82', 'MANOLO FORTICH', '1946-06-04', 'Compra, Ambrosio', 'Ambrosio', NULL, 'Compra', NULL, NULL, NULL, '1946-04-21', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3613, 'birth', '1969-0373', '53', '84', 'MANOLO FORTICH', '1969-08-02', 'Compra, Cinde Binanlao', 'Cinde', 'Binanlao', 'Compra', NULL, NULL, NULL, '1969-07-01', NULL, 'Compra, Ambrosio', 'Binanlao, Consolacion', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3614, 'birth', '1970-0374', '16', '13', 'MANOLO FORTICH', '1970-09-08', 'Compra, Evangeline Binanlao', 'Evangeline', 'Binanlao', 'Compra', NULL, NULL, NULL, '1970-08-10', NULL, 'Compra, Ambrosio', 'Binanlao, Consolacion', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3615, 'birth', '1970-0375', '56', '74', 'MANOLO FORTICH', '1970-12-27', 'Compra, Wilma Binanlao', 'Wilma', 'Binanlao', 'Compra', NULL, NULL, NULL, '1970-11-20', NULL, 'Compra, Ambrosio', 'Binanlao, Consolacion', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3616, 'birth', '1982-0376', '35', '89', 'MANOLO FORTICH', '1982-06-12', 'Sarzosa, Estrelleta Compra', 'Estrelleta', 'Compra', 'Sarzosa', NULL, NULL, NULL, '1982-05-22', NULL, 'Sarzosa, Alfredo', 'Compra, Wilma Binanlao', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3617, 'birth', '1988-0377', '16', '2', 'MANOLO FORTICH', '1988-08-01', 'Rosello, Alfredo Compra', 'Alfredo', 'Compra', 'Rosello', NULL, NULL, NULL, '1988-07-21', NULL, 'Rosello, Ambrosio', 'Compra, Cinde Binanlao', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3618, 'birth', '1988-0378', '27', '6', 'MANOLO FORTICH', '1988-11-18', 'Rosello, Charlene Compra', 'Charlene', 'Compra', 'Rosello', NULL, NULL, NULL, '1988-10-12', NULL, 'Rosello, Ambrosio', 'Compra, Cinde Binanlao', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3619, 'birth', '1990-0379', '32', '55', 'MANOLO FORTICH', '1990-05-20', 'Sarzosa, Herson Compra', 'Herson', 'Compra', 'Sarzosa', NULL, NULL, NULL, '1990-04-12', NULL, 'Sarzosa, Alfredo', 'Compra, Wilma Binanlao', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3620, 'birth', '1992-0380', '24', '16', 'MANOLO FORTICH', '1992-03-28', 'Sarzosa, Leizl Compra', 'Leizl', 'Compra', 'Sarzosa', NULL, NULL, NULL, '1992-03-06', NULL, 'Sarzosa, Alfredo', 'Compra, Wilma Binanlao', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3621, 'birth', '1993-0381', '22', '38', 'MANOLO FORTICH', '1993-10-28', 'Achas, Cristina Compra', 'Cristina', 'Compra', 'Achas', NULL, NULL, NULL, '1993-10-07', NULL, 'Achas, Ricardo', 'Compra, Evangeline Binanlao', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3622, 'birth', '1994-0382', '23', '62', 'MANOLO FORTICH', '1994-01-21', 'Achas, Evangeline Compra', 'Evangeline', 'Compra', 'Achas', NULL, NULL, NULL, '1994-01-07', NULL, 'Achas, Ricardo', 'Compra, Evangeline Binanlao', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3623, 'birth', '2002-0383', '19', '53', 'MANOLO FORTICH', '2002-04-11', 'Sarzosa, Marc Abarre', 'Marc', 'Abarre', 'Sarzosa', NULL, NULL, NULL, '2002-03-19', NULL, 'Sarzosa, Herson Compra', 'Abarre, Charlene', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3624, 'birth', '2005-0384', '29', '37', 'MANOLO FORTICH', '2005-03-10', 'Candillada, Kian Achas', 'Kian', 'Achas', 'Candillada', NULL, NULL, NULL, '2005-02-16', NULL, 'Candillada, Renato', 'Achas, Evangeline Compra', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3625, 'birth', '2005-0385', '48', '61', 'MANOLO FORTICH', '2005-07-27', 'Caballero, Reign Sarzosa', 'Reign', 'Sarzosa', 'Caballero', NULL, NULL, NULL, '2005-06-14', NULL, 'Caballero, Romeo', 'Sarzosa, Estrelleta Compra', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3626, 'birth', '2005-0386', '34', '41', 'MANOLO FORTICH', '2005-09-26', 'Alfeche, Alonso Sarzosa', 'Alonso', 'Sarzosa', 'Alfeche', NULL, NULL, NULL, '2005-08-24', NULL, 'Alfeche, Danilo', 'Sarzosa, Leizl Compra', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3627, 'birth', '2005-0387', '49', '62', 'MANOLO FORTICH', '2005-11-28', 'Candillada, Trisha Achas', 'Trisha', 'Achas', 'Candillada', NULL, NULL, NULL, '2005-11-03', NULL, 'Candillada, Renato', 'Achas, Evangeline Compra', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3628, 'birth', '2006-0388', '53', '18', 'MANOLO FORTICH', '2006-09-22', 'Rosello, Vince Guerero', 'Vince', 'Guerero', 'Rosello', NULL, NULL, NULL, '2006-08-20', NULL, 'Rosello, Alfredo Compra', 'Guerero, Cinde', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3629, 'birth', '2007-0389', '28', '16', 'MANOLO FORTICH', '2007-06-27', 'Sarzosa, Keith Achas', 'Keith', 'Achas', 'Sarzosa', NULL, NULL, NULL, '2007-05-20', NULL, 'Sarzosa, Danilo', 'Achas, Cristina Compra', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3630, 'birth', '2008-0390', '50', '80', 'MANOLO FORTICH', '2008-07-31', 'Sarzosa, Marc Abarre', 'Marc', 'Abarre', 'Sarzosa', NULL, NULL, NULL, '2008-07-18', NULL, 'Sarzosa, Herson Compra', 'Abarre, Charlene', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3632, 'birth', '2009-0392', '50', '30', 'MANOLO FORTICH', '2009-08-11', 'Rosello, Miguel Guerero', 'Miguel', 'Guerero', 'Rosello', NULL, NULL, NULL, '2009-07-06', NULL, 'Rosello, Alfredo Compra', 'Guerero, Cinde', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3634, 'birth', '2010-0394', '55', '18', 'MANOLO FORTICH', '2010-10-13', 'Alfeche, Kyla Sarzosa', 'Kyla', 'Sarzosa', 'Alfeche', NULL, NULL, NULL, '2010-09-10', NULL, 'Alfeche, Danilo', 'Sarzosa, Leizl Compra', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3635, 'birth', '2011-0395', '53', '15', 'MANOLO FORTICH', '2011-03-22', 'Caballero, Kezia Sarzosa', 'Kezia', 'Sarzosa', 'Caballero', NULL, NULL, NULL, '2011-02-24', NULL, 'Caballero, Romeo', 'Sarzosa, Estrelleta Compra', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3636, 'birth', '2011-0396', '12', '37', 'MANOLO FORTICH', '2011-03-19', 'Sarzosa, Keith Achas', 'Keith', 'Achas', 'Sarzosa', NULL, NULL, NULL, '2011-03-07', NULL, 'Sarzosa, Danilo', 'Achas, Cristina Compra', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3637, 'birth', '2011-0397', '46', '90', 'MANOLO FORTICH', '2011-04-07', 'Sarzosa, Zsanine Abarre', 'Zsanine', 'Abarre', 'Sarzosa', NULL, NULL, NULL, '2011-03-20', NULL, 'Sarzosa, Herson Compra', 'Abarre, Charlene', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3638, 'birth', '2011-0398', '55', '73', 'MANOLO FORTICH', '2011-05-23', 'Caballero, Trisha Sarzosa', 'Trisha', 'Sarzosa', 'Caballero', NULL, NULL, NULL, '2011-04-16', NULL, 'Caballero, Romeo', 'Sarzosa, Estrelleta Compra', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3639, 'birth', '2014-0399', '59', '52', 'MANOLO FORTICH', '2014-01-11', 'Guerero, Kyle Candillada', 'Kyle', 'Candillada', 'Guerero', NULL, NULL, NULL, '2014-01-03', NULL, 'Guerero, Vince', 'Candillada, Trisha Achas', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3640, 'birth', '2014-0400', '40', '77', 'MANOLO FORTICH', '2014-02-21', 'Sarzosa, Rafael Candillada', 'Rafael', 'Candillada', 'Sarzosa', NULL, NULL, NULL, '2014-02-13', NULL, 'Sarzosa, Marc Abarre', 'Candillada, Skye', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3641, 'birth', '2014-0401', '14', '49', 'MANOLO FORTICH', '2014-08-23', 'Montanez, Skye Abao', 'Skye', 'Abao', 'Montanez', NULL, NULL, NULL, '2014-08-13', NULL, 'Montanez, Flong', 'Abao, Belle Rosello', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3642, 'birth', '2016-0402', '45', '23', 'MANOLO FORTICH', '2016-02-25', 'Rosello, Xander Tuling', 'Xander', 'Tuling', 'Rosello', NULL, NULL, NULL, '2016-02-01', NULL, 'Rosello, Miguel Guerero', 'Tuling, Andrea', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3643, 'birth', '2017-0403', '35', '44', 'MANOLO FORTICH', '2017-09-03', 'Dignos, Jerry May Sarzosa', 'Jerry', 'May Sarzosa', 'Dignos', NULL, NULL, NULL, '2017-08-18', NULL, 'Dignos, Enzo', 'Sarzosa, Zsanine Abarre', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3644, 'birth', '2018-0404', '28', '25', 'MANOLO FORTICH', '2018-08-25', 'Alfeche, Mikaela Lusterio', 'Mikaela', 'Lusterio', 'Alfeche', NULL, NULL, NULL, '2018-07-11', NULL, 'Alfeche, Alonso Sarzosa', 'Lusterio, Kezia', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3645, 'birth', '2018-0405', '33', '14', 'MANOLO FORTICH', '2018-09-02', 'Montanez, Gabriel Caballero', 'Gabriel', 'Caballero', 'Montanez', NULL, NULL, NULL, '2018-08-10', NULL, 'Montanez, Flong', 'Caballero, Kezia Sarzosa', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3646, 'birth', '2019-0406', '23', '30', 'MANOLO FORTICH', '2019-04-26', 'Rosello, Angel Libanta', 'Angel', 'Libanta', 'Rosello', NULL, NULL, NULL, '2019-04-08', NULL, 'Rosello, Vince Montanez', 'Libanta, Jojale', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3647, 'birth', '2019-0407', '46', '47', 'MANOLO FORTICH', '2019-10-16', 'Sulinay, Belle Caballero', 'Belle', 'Caballero', 'Sulinay', NULL, NULL, NULL, '2019-09-09', NULL, 'Sulinay, Vince', 'Caballero, Reign Sarzosa', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3648, 'birth', '2019-0408', '48', '5', 'MANOLO FORTICH', '2019-12-22', 'Mejias, Justin Alfeche', 'Justin', 'Alfeche', 'Mejias', NULL, NULL, NULL, '2019-11-26', NULL, 'Mejias, Marc', 'Alfeche, Kyla Sarzosa', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3649, 'birth', '2020-0409', '32', '36', 'MANOLO FORTICH', '2020-06-26', 'Candillada, Gabriel Guerero', 'Gabriel', 'Guerero', 'Candillada', NULL, NULL, NULL, '2020-05-19', NULL, 'Candillada, Kian Achas', 'Guerero, Cathaleen', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3650, 'birth', '2020-0410', '26', '69', 'MANOLO FORTICH', '2020-08-17', 'Mejias, Vince Sarzosa', 'Vince', 'Sarzosa', 'Mejias', NULL, NULL, NULL, '2020-08-07', NULL, 'Mejias, Kyle Libanta', 'Sarzosa, Keith Achas', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3651, 'birth', '2020-0411', '57', '18', 'MANOLO FORTICH', '2020-12-12', 'Sarzosa, Kyla Sulinay', 'Kyla', 'Sulinay', 'Sarzosa', NULL, NULL, NULL, '2020-11-17', NULL, 'Sarzosa, Marc Abarre', 'Sulinay, Reign', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3652, 'birth', '2021-0412', '21', '88', 'MANOLO FORTICH', '2022-01-14', 'Rosello, Yeshu Tuling', 'Yeshu', 'Tuling', 'Rosello', NULL, NULL, NULL, '2021-12-06', NULL, 'Rosello, Miguel Guerero', 'Tuling, Andrea', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3653, 'birth', '2022-0413', '43', '2', 'MANOLO FORTICH', '2022-12-22', 'Guerero, Nyla Candillada', 'Nyla', 'Candillada', 'Guerero', NULL, NULL, NULL, '2022-11-12', NULL, 'Guerero, Vince', 'Candillada, Trisha Achas', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3654, 'birth', '2023-0414', '21', '53', 'MANOLO FORTICH', '2023-01-26', 'Saculingan, Zayd Sarzosa', 'Zayd', 'Sarzosa', 'Saculingan', NULL, NULL, NULL, '2023-01-23', NULL, 'Saculingan, Mark Angelo', 'Sarzosa, Keith Achas', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3655, 'birth', '2023-0415', '10', '90', 'MANOLO FORTICH', '2023-05-08', 'Horca, Vince Abao', 'Vince', 'Abao', 'Horca', NULL, NULL, NULL, '2023-04-02', NULL, 'Horca, Enzo', 'Abao, Belle Rosello', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3656, 'birth', '2023-0416', '21', '48', 'MANOLO FORTICH', '2023-05-17', 'Sarzosa, Trisha Sulinay', 'Trisha', 'Sulinay', 'Sarzosa', NULL, NULL, NULL, '2023-04-09', NULL, 'Sarzosa, Marc Abarre', 'Sulinay, Reign', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3657, 'birth', '2025-0417', '53', '5', 'MANOLO FORTICH', '2025-07-01', 'Alfeche, Kian Lusterio', 'Kian', 'Lusterio', 'Alfeche', NULL, NULL, NULL, '2025-06-14', NULL, 'Alfeche, Alonso Sarzosa', 'Lusterio, Kezia', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3658, 'birth', '2025-0418', '59', '23', 'MANOLO FORTICH', '2025-10-19', 'Mejias, Belle Alfeche', 'Belle', 'Alfeche', 'Mejias', NULL, NULL, NULL, '2025-09-08', NULL, 'Mejias, Marc', 'Alfeche, Kyla Sarzosa', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3659, 'birth', '2026-0419', '10', '1', 'MANOLO FORTICH', '2026-04-01', 'Villasorda, Xander Caballero', 'Xander', 'Caballero', 'Villasorda', NULL, NULL, NULL, '2026-03-13', NULL, 'Villasorda, Miguel', 'Caballero, Trisha Sarzosa', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3660, 'birth', '2026-0420', '59', '59', 'MANOLO FORTICH', '2026-09-06', 'Montanez, Skye Abao', 'Skye', 'Abao', 'Montanez', NULL, NULL, NULL, '2026-08-19', NULL, 'Montanez, Flong', 'Abao, Belle Rosello', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3661, 'birth', '1964-0421', '52', '25', 'MANOLO FORTICH', '1964-07-09', 'Rosello, Ambrosio', 'Ambrosio', NULL, 'Rosello', NULL, NULL, NULL, '1964-06-22', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3662, 'birth', '1969-0422', '36', '4', 'MANOLO FORTICH', '1969-02-24', 'Sarzosa, Alfredo', 'Alfredo', NULL, 'Sarzosa', NULL, NULL, NULL, '1969-02-11', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3663, 'birth', '1969-0423', '34', '5', 'MANOLO FORTICH', '1969-04-08', 'Achas, Ricardo', 'Ricardo', NULL, 'Achas', NULL, NULL, NULL, '1969-03-26', NULL, NULL, NULL, NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3664, 'birth', '1983-0424', '49', '82', 'MANOLO FORTICH', '1983-11-04', 'Sarzosa, Danilo', 'Danilo', NULL, 'Sarzosa', NULL, NULL, NULL, '1983-10-24', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3665, 'birth', '1985-0425', '57', '9', 'MANOLO FORTICH', '1986-01-03', 'Abarre, Charlene', 'Charlene', NULL, 'Abarre', NULL, NULL, NULL, '1985-11-28', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3666, 'birth', '1985-0426', '60', '31', 'MANOLO FORTICH', '1985-12-03', 'Caballero, Romeo', 'Romeo', NULL, 'Caballero', NULL, NULL, NULL, '1985-11-30', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3667, 'birth', '1989-0427', '48', '56', 'MANOLO FORTICH', '1989-05-19', 'Abao, Danilo', 'Danilo', NULL, 'Abao', NULL, NULL, NULL, '1989-04-17', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3668, 'birth', '1990-0428', '49', '6', 'MANOLO FORTICH', '1990-03-20', 'Alfeche, Danilo', 'Danilo', NULL, 'Alfeche', NULL, NULL, NULL, '1990-02-13', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3669, 'birth', '1992-0429', '53', '14', 'MANOLO FORTICH', '1992-05-08', 'Candillada, Renato', 'Renato', NULL, 'Candillada', NULL, NULL, NULL, '1992-04-10', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3670, 'birth', '1994-0430', '39', '52', 'MANOLO FORTICH', '1994-06-12', 'Guerero, Cinde', 'Cinde', NULL, 'Guerero', NULL, NULL, NULL, '1994-05-14', NULL, NULL, NULL, NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3671, 'birth', '2002-0431', '33', '88', 'MANOLO FORTICH', '2002-03-05', 'Horca, Enzo', 'Enzo', NULL, 'Horca', NULL, NULL, NULL, '2002-02-18', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3672, 'birth', '2003-0432', '17', '69', 'MANOLO FORTICH', '2003-10-22', 'Guerero, Cathaleen', 'Cathaleen', NULL, 'Guerero', NULL, NULL, NULL, '2003-09-29', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3673, 'birth', '2004-0433', '59', '40', 'MANOLO FORTICH', '2004-08-04', 'Montanez, Flong', 'Flong', NULL, 'Montanez', NULL, NULL, NULL, '2004-07-03', NULL, NULL, NULL, NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3674, 'birth', '2004-0434', '12', '30', 'MANOLO FORTICH', '2004-09-22', 'Candillada, Skye', 'Skye', NULL, 'Candillada', NULL, NULL, NULL, '2004-09-02', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3675, 'birth', '2004-0435', '17', '65', 'MANOLO FORTICH', '2004-10-20', 'Villasorda, Miguel', 'Miguel', NULL, 'Villasorda', NULL, NULL, NULL, '2004-10-13', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3676, 'birth', '2005-0436', '55', '90', 'MANOLO FORTICH', '2005-06-29', 'Saculingan, Mark Angelo', 'Mark', 'Angelo', 'Saculingan', NULL, NULL, NULL, '2005-06-16', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3677, 'birth', '2005-0437', '57', '40', 'MANOLO FORTICH', '2005-11-22', 'Dignos, Enzo', 'Enzo', NULL, 'Dignos', NULL, NULL, NULL, '2005-11-19', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3678, 'birth', '2007-0438', '57', '80', 'MANOLO FORTICH', '2007-03-25', 'Sulinay, Vince', 'Vince', NULL, 'Sulinay', NULL, NULL, NULL, '2007-02-14', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3679, 'birth', '2008-0439', '51', '57', 'MANOLO FORTICH', '2008-08-04', 'Tuling, Andrea', 'Andrea', NULL, 'Tuling', NULL, NULL, NULL, '2008-07-28', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3680, 'birth', '2008-0440', '10', '53', 'MANOLO FORTICH', '2008-09-07', 'Sulinay, Reign', 'Reign', NULL, 'Sulinay', NULL, NULL, NULL, '2008-08-20', NULL, NULL, NULL, NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3681, 'birth', '2008-0441', '26', '41', 'MANOLO FORTICH', '2008-09-08', 'Lusterio, Kezia', 'Kezia', NULL, 'Lusterio', NULL, NULL, NULL, '2008-08-21', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3682, 'birth', '2009-0442', '18', '82', 'MANOLO FORTICH', '2009-11-19', 'Mejias, Marc', 'Marc', NULL, 'Mejias', NULL, NULL, NULL, '2009-11-10', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3683, 'birth', '2011-0443', '16', '60', 'MANOLO FORTICH', '2011-04-21', 'Montanez, Flong', 'Flong', NULL, 'Montanez', NULL, NULL, NULL, '2011-03-14', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3684, 'birth', '2011-0444', '22', '18', 'MANOLO FORTICH', '2011-05-18', 'Guerero, Vince', 'Vince', NULL, 'Guerero', NULL, NULL, NULL, '2011-05-01', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3685, 'birth', '2011-0445', '42', '7', 'MANOLO FORTICH', '2011-06-11', 'Mejias, Kyle', 'Kyle', NULL, 'Mejias', NULL, NULL, NULL, '2011-05-17', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3686, 'birth', '2012-0446', '46', '56', 'MANOLO FORTICH', '2012-03-02', 'Libanta, Jojale', 'Jojale', NULL, 'Libanta', NULL, NULL, NULL, '2012-02-25', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3687, 'birth', '1938-0447', '23', '73', 'MANOLO FORTICH', '1938-10-10', 'Villasorda, Gregorio', 'Gregorio', NULL, 'Villasorda', NULL, NULL, NULL, '1938-09-20', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3688, 'birth', '1949-0448', '25', '72', 'MANOLO FORTICH', '1949-04-01', 'Binanlao, Purificacion', 'Purificacion', NULL, 'Binanlao', NULL, NULL, NULL, '1949-03-13', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3689, 'birth', '1962-0449', '10', '12', 'MANOLO FORTICH', '1962-04-26', 'Villasorda, Teodomera Binanlao', 'Teodomera', 'Binanlao', 'Villasorda', NULL, NULL, NULL, '1962-03-13', NULL, 'Villasorda, Gregorio', 'Binanlao, Purificacion', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3690, 'birth', '1967-0450', '22', '25', 'MANOLO FORTICH', '1967-08-20', 'Villasorda, Nestor Binanlao', 'Nestor', 'Binanlao', 'Villasorda', NULL, NULL, NULL, '1967-08-11', NULL, 'Villasorda, Gregorio', 'Binanlao, Purificacion', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3691, 'birth', '1969-0451', '25', '10', 'MANOLO FORTICH', '1969-07-08', 'Villasorda, Jehiel Binanlao', 'Jehiel', 'Binanlao', 'Villasorda', NULL, NULL, NULL, '1969-06-18', NULL, 'Villasorda, Gregorio', 'Binanlao, Purificacion', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3692, 'birth', '1984-0452', '26', '85', 'MANOLO FORTICH', '1984-04-08', 'Ceniza, Nestor Villasorda', 'Nestor', 'Villasorda', 'Ceniza', NULL, NULL, NULL, '1984-03-23', NULL, 'Ceniza, Juan', 'Villasorda, Jehiel Binanlao', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3693, 'birth', '1985-0453', '10', '53', 'MANOLO FORTICH', '1985-12-01', 'Libanta, Rogelio Villasorda', 'Rogelio', 'Villasorda', 'Libanta', NULL, NULL, NULL, '1985-11-24', NULL, 'Libanta, Querudel', 'Villasorda, Teodomera Binanlao', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3694, 'birth', '1987-0454', '48', '77', 'MANOLO FORTICH', '1987-09-03', 'Villasorda, Estrelleta Jablan', 'Estrelleta', 'Jablan', 'Villasorda', NULL, NULL, NULL, '1987-07-27', NULL, 'Villasorda, Nestor Binanlao', 'Jablan, Felicisima', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3695, 'birth', '1990-0455', '19', '20', 'MANOLO FORTICH', '1991-01-03', 'Villasorda, Herson Jablan', 'Herson', 'Jablan', 'Villasorda', NULL, NULL, NULL, '1990-12-27', NULL, 'Villasorda, Nestor Binanlao', 'Jablan, Felicisima', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `civil_registry_records` (`record_id`, `registry_type`, `registry_number`, `book_no`, `page_no`, `city_municipality`, `date_prepared`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `civil_status`, `date_of_event`, `place_of_event`, `father_name`, `mother_name`, `spouse_name`, `barangay`, `zone`, `remarks`, `scan_image_path`, `scan_image_path_2`, `scan_image_path_back`, `scan_image_path_back_2`, `certificate_data`, `verified_by`, `verified_at`, `created_at`, `date_of_birth`, `wife_father_name`, `wife_mother_name`, `age_at_death`, `cause_of_death`) VALUES
(3696, 'birth', '1991-0456', '26', '62', 'MANOLO FORTICH', '1991-12-02', 'Villasorda, Aiza Jablan', 'Aiza', 'Jablan', 'Villasorda', NULL, NULL, NULL, '1991-11-18', NULL, 'Villasorda, Nestor Binanlao', 'Jablan, Felicisima', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3697, 'birth', '1991-0457', '29', '73', 'MANOLO FORTICH', '1992-01-15', 'Ceniza, Leizl Villasorda', 'Leizl', 'Villasorda', 'Ceniza', NULL, NULL, NULL, '1991-12-06', NULL, 'Ceniza, Juan', 'Villasorda, Jehiel Binanlao', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3698, 'birth', '1992-0458', '28', '37', 'MANOLO FORTICH', '1992-07-21', 'Libanta, Leizl Villasorda', 'Leizl', 'Villasorda', 'Libanta', NULL, NULL, NULL, '1992-07-16', NULL, 'Libanta, Querudel', 'Villasorda, Teodomera Binanlao', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3699, 'birth', '2002-0459', '29', '21', 'MANOLO FORTICH', '2002-09-22', 'Ceniza, Zsanine Oclarit', 'Zsanine', 'Oclarit', 'Ceniza', NULL, NULL, NULL, '2002-09-12', NULL, 'Ceniza, Nestor Villasorda', 'Oclarit, Evangeline', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3700, 'birth', '2004-0460', '20', '59', 'MANOLO FORTICH', '2004-03-10', 'Lusterio, Reign Villasorda', 'Reign', 'Villasorda', 'Lusterio', NULL, NULL, NULL, '2004-02-29', NULL, 'Lusterio, Emmanuel', 'Villasorda, Estrelleta Jablan', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3701, 'birth', '2005-0461', '51', '74', 'MANOLO FORTICH', '2005-03-21', 'Chavez, Zsanine Villasorda', 'Zsanine', 'Villasorda', 'Chavez', NULL, NULL, NULL, '2005-03-15', NULL, 'Chavez, Romeo', 'Villasorda, Aiza Jablan', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3702, 'birth', '2006-0462', '35', '64', 'MANOLO FORTICH', '2006-09-22', 'Villasorda, Miguel Miso', 'Miguel', 'Miso', 'Villasorda', NULL, NULL, NULL, '2006-09-08', NULL, 'Villasorda, Herson Jablan', 'Miso, Josephine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3703, 'birth', '2007-0463', '41', '18', 'MANOLO FORTICH', '2007-03-14', 'Lusterio, Zsanine Villasorda', 'Zsanine', 'Villasorda', 'Lusterio', NULL, NULL, NULL, '2007-02-05', NULL, 'Lusterio, Emmanuel', 'Villasorda, Estrelleta Jablan', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3704, 'birth', '2007-0464', '26', '50', 'MANOLO FORTICH', '2007-05-28', 'Horca, Vince Ceniza', 'Vince', 'Ceniza', 'Horca', NULL, NULL, NULL, '2007-05-10', NULL, 'Horca, Herson', 'Ceniza, Leizl Villasorda', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3705, 'birth', '2007-0465', '36', '90', 'MANOLO FORTICH', '2007-06-14', 'Libanta, Kyle Rosello', 'Kyle', 'Rosello', 'Libanta', NULL, NULL, NULL, '2007-05-13', NULL, 'Libanta, Rogelio Villasorda', 'Rosello, Josephine', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3706, 'birth', '2007-0466', '52', '13', 'MANOLO FORTICH', '2007-10-20', 'Horca, Keith Ceniza', 'Keith', 'Ceniza', 'Horca', NULL, NULL, NULL, '2007-10-12', NULL, 'Horca, Herson', 'Ceniza, Leizl Villasorda', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3707, 'birth', '2008-0467', '43', '57', 'MANOLO FORTICH', '2008-01-21', 'Mejias, Kyle Libanta', 'Kyle', 'Libanta', 'Mejias', NULL, NULL, NULL, '2008-01-08', NULL, 'Mejias, Renato', 'Libanta, Leizl Villasorda', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3708, 'birth', '2009-0468', '49', '29', 'MANOLO FORTICH', '2009-03-22', 'Libanta, Andrea Rosello', 'Andrea', 'Rosello', 'Libanta', NULL, NULL, NULL, '2009-03-01', NULL, 'Libanta, Rogelio Villasorda', 'Rosello, Josephine', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3709, 'birth', '2009-0469', '51', '26', 'MANOLO FORTICH', '2009-05-31', 'Chavez, Miguel Villasorda', 'Miguel', 'Villasorda', 'Chavez', NULL, NULL, NULL, '2009-04-28', NULL, 'Chavez, Romeo', 'Villasorda, Aiza Jablan', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3710, 'birth', '2009-0470', '13', '71', 'MANOLO FORTICH', '2009-11-29', 'Mejias, Keith Libanta', 'Keith', 'Libanta', 'Mejias', NULL, NULL, NULL, '2009-11-22', NULL, 'Mejias, Renato', 'Libanta, Leizl Villasorda', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3711, 'birth', '2010-0471', '21', '79', 'MANOLO FORTICH', '2010-04-27', 'Horca, Vince Ceniza', 'Vince', 'Ceniza', 'Horca', NULL, NULL, NULL, '2010-04-06', NULL, 'Horca, Herson', 'Ceniza, Leizl Villasorda', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3712, 'birth', '2010-0472', '53', '77', 'MANOLO FORTICH', '2011-01-14', 'Libanta, Zsanine Rosello', 'Zsanine', 'Rosello', 'Libanta', NULL, NULL, NULL, '2010-12-29', NULL, 'Libanta, Rogelio Villasorda', 'Rosello, Josephine', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3713, 'birth', '2011-0473', '46', '78', 'MANOLO FORTICH', '2011-11-04', 'Villasorda, Flong Miso', 'Flong', 'Miso', 'Villasorda', NULL, NULL, NULL, '2011-11-01', NULL, 'Villasorda, Herson Jablan', 'Miso, Josephine', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3714, 'birth', '2012-0474', '26', '84', 'MANOLO FORTICH', '2012-10-04', 'Ceniza, Keith Oclarit', 'Keith', 'Oclarit', 'Ceniza', NULL, NULL, NULL, '2012-09-12', NULL, 'Ceniza, Nestor Villasorda', 'Oclarit, Evangeline', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3715, 'birth', '2014-0475', '18', '81', 'MANOLO FORTICH', '2014-04-19', 'Horca, Kyle Amoyo', 'Kyle', 'Amoyo', 'Horca', NULL, NULL, NULL, '2014-03-21', NULL, 'Horca, Vince Ceniza', 'Amoyo, Kyla', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3716, 'birth', '2014-0476', '57', '90', 'MANOLO FORTICH', '2014-07-27', 'Libanta, Kyle Oclarit', 'Kyle', 'Oclarit', 'Libanta', NULL, NULL, NULL, '2014-07-09', NULL, 'Libanta, Kyle Rosello', 'Oclarit, Angel', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3717, 'birth', '2014-0477', '49', '41', 'MANOLO FORTICH', '2015-01-15', 'Oclarit, Enzo Chavez', 'Enzo', 'Chavez', 'Oclarit', NULL, NULL, NULL, '2014-12-13', NULL, 'Oclarit, Zayd', 'Chavez, Zsanine Villasorda', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3718, 'birth', '2017-0478', '39', '41', 'MANOLO FORTICH', '2017-12-12', 'Villasorda, Skye Sudaria', 'Skye', 'Sudaria', 'Villasorda', NULL, NULL, NULL, '2017-12-02', NULL, 'Villasorda, Flong', 'Sudaria, Reign', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3719, 'birth', '2018-0479', '30', '47', 'MANOLO FORTICH', '2018-11-02', 'Villasorda, Xander Sudaria', 'Xander', 'Sudaria', 'Villasorda', NULL, NULL, NULL, '2018-09-23', NULL, 'Villasorda, Flong', 'Sudaria, Reign', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3720, 'birth', '2018-0480', '50', '13', 'MANOLO FORTICH', '2018-11-20', 'Sudaria, Enzo Horca', 'Enzo', 'Horca', 'Sudaria', NULL, NULL, NULL, '2018-11-01', NULL, 'Sudaria, Gabriel', 'Horca, Keith Ceniza', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3721, 'birth', '2019-0481', '35', '13', 'MANOLO FORTICH', '2019-09-26', 'Sudaria, Trisha Horca', 'Trisha', 'Horca', 'Sudaria', NULL, NULL, NULL, '2019-08-18', NULL, 'Sudaria, Gabriel', 'Horca, Keith Ceniza', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3722, 'birth', '2019-0482', '49', '44', 'MANOLO FORTICH', '2019-12-14', 'Villasorda, Justin Lusterio', 'Justin', 'Lusterio', 'Villasorda', NULL, NULL, NULL, '2019-11-24', NULL, 'Villasorda, Flong', 'Lusterio, Zsanine', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3723, 'birth', '2020-0483', '34', '26', 'MANOLO FORTICH', '2020-02-08', 'Bustillo, Rafael Lusterio', 'Rafael', 'Lusterio', 'Bustillo', NULL, NULL, NULL, '2020-01-21', NULL, 'Bustillo, Justin', 'Lusterio, Reign Villasorda', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3724, 'birth', '2020-0484', '27', '51', 'MANOLO FORTICH', '2020-03-14', 'Oclarit, Skye Chavez', 'Skye', 'Chavez', 'Oclarit', NULL, NULL, NULL, '2020-02-29', NULL, 'Oclarit, Zayd', 'Chavez, Zsanine Villasorda', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3725, 'birth', '2020-0485', '20', '82', 'MANOLO FORTICH', '2020-07-18', 'Sarzosa, Skye Ceniza', 'Skye', 'Ceniza', 'Sarzosa', NULL, NULL, NULL, '2020-06-05', NULL, 'Sarzosa, Zayd', 'Ceniza, Zsanine Oclarit', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3726, 'birth', '2021-0486', '48', '83', 'MANOLO FORTICH', '2021-03-01', 'Villasorda, Alonso Libanta', 'Alonso', 'Libanta', 'Villasorda', NULL, NULL, NULL, '2021-02-08', NULL, 'Villasorda, Mark Angelo', 'Libanta, Andrea Rosello', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3727, 'birth', '2021-0487', '59', '58', 'MANOLO FORTICH', '2021-05-22', 'Oclarit, Zsanine Ceniza', 'Zsanine', 'Ceniza', 'Oclarit', NULL, NULL, NULL, '2021-04-17', NULL, 'Oclarit, Miguel', 'Ceniza, Keith Oclarit', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3728, 'birth', '2021-0488', '46', '74', 'MANOLO FORTICH', '2021-12-27', 'Horca, Mikaela Libanta', 'Mikaela', 'Libanta', 'Horca', NULL, NULL, NULL, '2021-11-16', NULL, 'Horca, Vince Ceniza', 'Libanta, Skye', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3729, 'birth', '2021-0489', '15', '87', 'MANOLO FORTICH', '2022-01-04', 'Chavez, Kyle Lusterio', 'Kyle', 'Lusterio', 'Chavez', NULL, NULL, NULL, '2021-12-26', NULL, 'Chavez, Miguel Villasorda', 'Lusterio, Zsanine', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3730, 'birth', '2022-0490', '32', '3', 'MANOLO FORTICH', '2022-03-15', 'Horca, Keith Libanta', 'Keith', 'Libanta', 'Horca', NULL, NULL, NULL, '2022-02-05', NULL, 'Horca, Vince Ceniza', 'Libanta, Skye', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3731, 'birth', '2022-0491', '38', '53', 'MANOLO FORTICH', '2022-04-04', 'Libanta, Angel Oclarit', 'Angel', 'Oclarit', 'Libanta', NULL, NULL, NULL, '2022-02-23', NULL, 'Libanta, Kyle Rosello', 'Oclarit, Angel', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3732, 'birth', '2022-0492', '57', '37', 'MANOLO FORTICH', '2022-10-16', 'Mejias, Mark Angelo Bacalso', 'Mark', 'Angelo Bacalso', 'Mejias', NULL, NULL, NULL, '2022-09-29', NULL, 'Mejias, Kyle Libanta', 'Bacalso, Mikaela', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3733, 'birth', '2022-0493', '40', '71', 'MANOLO FORTICH', '2022-12-06', 'Ramirez, Jerry May Libanta', 'Jerry', 'May Libanta', 'Ramirez', NULL, NULL, NULL, '2022-11-27', NULL, 'Ramirez, Gabriel', 'Libanta, Zsanine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3734, 'birth', '2024-0494', '46', '88', 'MANOLO FORTICH', '2024-03-20', 'Bustillo, Cathaleen Lusterio', 'Cathaleen', 'Lusterio', 'Bustillo', NULL, NULL, NULL, '2024-02-10', NULL, 'Bustillo, Justin', 'Lusterio, Reign Villasorda', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3735, 'birth', '2025-0495', '54', '48', 'MANOLO FORTICH', '2025-03-25', 'Villasorda, Nyla Lusterio', 'Nyla', 'Lusterio', 'Villasorda', NULL, NULL, NULL, '2025-03-09', NULL, 'Villasorda, Flong', 'Lusterio, Zsanine', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3736, 'birth', '2025-0496', '26', '80', 'MANOLO FORTICH', '2025-10-10', 'Dayog, Rafael Mejias', 'Rafael', 'Mejias', 'Dayog', NULL, NULL, NULL, '2025-10-02', NULL, 'Dayog, Jerry May', 'Mejias, Keith Libanta', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3737, 'birth', '2025-0497', '44', '5', 'MANOLO FORTICH', '2025-11-15', 'Villasorda, Rafael Amoyo', 'Rafael', 'Amoyo', 'Villasorda', NULL, NULL, NULL, '2025-10-16', NULL, 'Villasorda, Miguel', 'Amoyo, Belle', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3738, 'birth', '2026-0498', '20', '44', 'MANOLO FORTICH', '2026-11-03', 'Villasorda, Nyla Libanta', 'Nyla', 'Libanta', 'Villasorda', NULL, NULL, NULL, '2026-10-27', NULL, 'Villasorda, Mark Angelo', 'Libanta, Andrea Rosello', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3739, 'birth', '1961-0499', '11', '15', 'MANOLO FORTICH', '1961-08-28', 'Jablan, Felicisima', 'Felicisima', NULL, 'Jablan', NULL, NULL, NULL, '1961-07-22', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3740, 'birth', '1965-0500', '37', '78', 'MANOLO FORTICH', '1966-01-08', 'Ceniza, Juan', 'Juan', NULL, 'Ceniza', NULL, NULL, NULL, '1965-12-14', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3741, 'birth', '1972-0501', '44', '88', 'MANOLO FORTICH', '1972-12-09', 'Libanta, Querudel', 'Querudel', NULL, 'Libanta', NULL, NULL, NULL, '1972-12-06', NULL, NULL, NULL, NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3742, 'birth', '1984-0502', '32', '14', 'MANOLO FORTICH', '1984-10-19', 'Lusterio, Emmanuel', 'Emmanuel', NULL, 'Lusterio', NULL, NULL, NULL, '1984-09-28', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3743, 'birth', '1985-0503', '36', '59', 'MANOLO FORTICH', '1985-03-24', 'Mejias, Renato', 'Renato', NULL, 'Mejias', NULL, NULL, NULL, '1985-03-08', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3744, 'birth', '1985-0504', '17', '36', 'MANOLO FORTICH', '1985-07-16', 'Horca, Herson', 'Herson', NULL, 'Horca', NULL, NULL, NULL, '1985-06-21', NULL, NULL, NULL, NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3745, 'birth', '1986-0505', '21', '19', 'MANOLO FORTICH', '1986-02-11', 'Rosello, Josephine', 'Josephine', NULL, 'Rosello', NULL, NULL, NULL, '1986-01-17', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3746, 'birth', '1991-0506', '14', '12', 'MANOLO FORTICH', '1991-08-10', 'Chavez, Romeo', 'Romeo', NULL, 'Chavez', NULL, NULL, NULL, '1991-07-29', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3747, 'birth', '1991-0507', '48', '85', 'MANOLO FORTICH', '1991-09-30', 'Oclarit, Evangeline', 'Evangeline', NULL, 'Oclarit', NULL, NULL, NULL, '1991-08-17', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3748, 'birth', '1991-0508', '47', '72', 'MANOLO FORTICH', '1991-10-16', 'Miso, Josephine', 'Josephine', NULL, 'Miso', NULL, NULL, NULL, '1991-09-08', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3749, 'birth', '2002-0509', '55', '23', 'MANOLO FORTICH', '2002-01-14', 'Oclarit, Miguel', 'Miguel', NULL, 'Oclarit', NULL, NULL, NULL, '2002-01-03', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3750, 'birth', '2002-0510', '32', '73', 'MANOLO FORTICH', '2002-04-27', 'Sudaria, Reign', 'Reign', NULL, 'Sudaria', NULL, NULL, NULL, '2002-03-21', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3751, 'birth', '2002-0511', '14', '48', 'MANOLO FORTICH', '2002-05-06', 'Sarzosa, Zayd', 'Zayd', NULL, 'Sarzosa', NULL, NULL, NULL, '2002-03-31', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3752, 'birth', '2002-0512', '21', '36', 'MANOLO FORTICH', '2002-05-26', 'Lusterio, Zsanine', 'Zsanine', NULL, 'Lusterio', NULL, NULL, NULL, '2002-05-01', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3753, 'birth', '2002-0513', '51', '68', 'MANOLO FORTICH', '2002-10-06', 'Amoyo, Belle', 'Belle', NULL, 'Amoyo', NULL, NULL, NULL, '2002-09-07', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3754, 'birth', '2003-0514', '60', '82', 'MANOLO FORTICH', '2003-03-03', 'Villasorda, Mark Angelo', 'Mark', 'Angelo', 'Villasorda', NULL, NULL, NULL, '2003-02-18', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3755, 'birth', '2003-0515', '41', '53', 'MANOLO FORTICH', '2003-09-19', 'Oclarit, Zayd', 'Zayd', NULL, 'Oclarit', NULL, NULL, NULL, '2003-09-01', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3756, 'birth', '2003-0516', '21', '78', 'MANOLO FORTICH', '2004-02-06', 'Ramirez, Gabriel', 'Gabriel', NULL, 'Ramirez', NULL, NULL, NULL, '2003-12-29', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3757, 'birth', '2005-0517', '59', '36', 'MANOLO FORTICH', '2005-01-29', 'Villasorda, Flong', 'Flong', NULL, 'Villasorda', NULL, NULL, NULL, '2005-01-12', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3758, 'birth', '2005-0518', '48', '29', 'MANOLO FORTICH', '2005-06-25', 'Libanta, Skye', 'Skye', NULL, 'Libanta', NULL, NULL, NULL, '2005-06-19', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3759, 'birth', '2006-0519', '27', '69', 'MANOLO FORTICH', '2006-01-22', 'Bustillo, Justin', 'Justin', NULL, 'Bustillo', NULL, NULL, NULL, '2006-01-04', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3760, 'birth', '2008-0520', '46', '25', 'MANOLO FORTICH', '2008-09-28', 'Sudaria, Gabriel', 'Gabriel', NULL, 'Sudaria', NULL, NULL, NULL, '2008-08-20', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3761, 'birth', '2009-0521', '25', '45', 'MANOLO FORTICH', '2009-03-28', 'Dayog, Jerry May', 'Jerry', 'May', 'Dayog', NULL, NULL, NULL, '2009-03-04', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3762, 'birth', '2009-0522', '34', '40', 'MANOLO FORTICH', '2009-12-23', 'Oclarit, Angel', 'Angel', NULL, 'Oclarit', NULL, NULL, NULL, '2009-12-06', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3763, 'birth', '2011-0523', '23', '32', 'MANOLO FORTICH', '2011-12-09', 'Bacalso, Mikaela', 'Mikaela', NULL, 'Bacalso', NULL, NULL, NULL, '2011-12-06', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3764, 'birth', '2012-0524', '25', '76', 'MANOLO FORTICH', '2012-09-01', 'Amoyo, Kyla', 'Kyla', NULL, 'Amoyo', NULL, NULL, NULL, '2012-08-18', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3765, 'birth', '1939-0525', '60', '88', 'MANOLO FORTICH', '1939-03-05', 'Saculingan, Corazon', 'Corazon', NULL, 'Saculingan', NULL, NULL, NULL, '1939-01-21', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3766, 'birth', '1947-0526', '18', '66', 'MANOLO FORTICH', '1947-09-06', 'Tabada, Cirilo', 'Cirilo', NULL, 'Tabada', NULL, NULL, NULL, '1947-08-11', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3767, 'birth', '1964-0527', '47', '70', 'MANOLO FORTICH', '1964-04-20', 'Tabada, Asuncion Saculingan', 'Asuncion', 'Saculingan', 'Tabada', NULL, NULL, NULL, '1964-04-04', NULL, 'Tabada, Cirilo', 'Saculingan, Corazon', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3768, 'birth', '1968-0528', '37', '90', 'MANOLO FORTICH', '1968-11-04', 'Tabada, Leizl Saculingan', 'Leizl', 'Saculingan', 'Tabada', NULL, NULL, NULL, '1968-10-03', NULL, 'Tabada, Cirilo', 'Saculingan, Corazon', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3769, 'birth', '1970-0529', '13', '22', 'MANOLO FORTICH', '1970-12-08', 'Tabada, Herson Saculingan', 'Herson', 'Saculingan', 'Tabada', NULL, NULL, NULL, '1970-11-22', NULL, 'Tabada, Cirilo', 'Saculingan, Corazon', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3770, 'birth', '1985-0530', '14', '8', 'MANOLO FORTICH', '1985-05-25', 'Tabada, Samuel Bacalso', 'Samuel', 'Bacalso', 'Tabada', NULL, NULL, NULL, '1985-05-05', NULL, 'Tabada, Herson Saculingan', 'Bacalso, Marilou', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3771, 'birth', '1987-0531', '37', '15', 'MANOLO FORTICH', '1987-04-16', 'Tabada, Samuel Bacalso', 'Samuel', 'Bacalso', 'Tabada', NULL, NULL, NULL, '1987-03-28', NULL, 'Tabada, Herson Saculingan', 'Bacalso, Marilou', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3772, 'birth', '1988-0532', '40', '67', 'MANOLO FORTICH', '1988-01-22', 'Edem, Danilo Tabada', 'Danilo', 'Tabada', 'Edem', NULL, NULL, NULL, '1988-01-17', NULL, 'Edem, Johnnas', 'Tabada, Leizl Saculingan', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3773, 'birth', '1989-0533', '31', '53', 'MANOLO FORTICH', '1989-03-24', 'Nacino, Marilou Tabada', 'Marilou', 'Tabada', 'Nacino', NULL, NULL, NULL, '1989-03-15', NULL, 'Nacino, Johnnas', 'Tabada, Asuncion Saculingan', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3774, 'birth', '1989-0534', '15', '30', 'MANOLO FORTICH', '1989-04-13', 'Edem, Danilo Tabada', 'Danilo', 'Tabada', 'Edem', NULL, NULL, NULL, '1989-03-25', NULL, 'Edem, Johnnas', 'Tabada, Leizl Saculingan', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3775, 'birth', '1990-0535', '39', '12', 'MANOLO FORTICH', '1991-02-01', 'Nacino, Grace Tabada', 'Grace', 'Tabada', 'Nacino', NULL, NULL, NULL, '1990-12-25', NULL, 'Nacino, Johnnas', 'Tabada, Asuncion Saculingan', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3776, 'birth', '1991-0536', '40', '45', 'MANOLO FORTICH', '1991-11-14', 'Nacino, Marilou Tabada', 'Marilou', 'Tabada', 'Nacino', NULL, NULL, NULL, '1991-10-11', NULL, 'Nacino, Johnnas', 'Tabada, Asuncion Saculingan', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3777, 'birth', '1993-0537', '15', '77', 'MANOLO FORTICH', '1993-04-26', 'Edem, Nievajane Tabada', 'Nievajane', 'Tabada', 'Edem', NULL, NULL, NULL, '1993-03-23', NULL, 'Edem, Johnnas', 'Tabada, Leizl Saculingan', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3778, 'birth', '1994-0538', '17', '60', 'MANOLO FORTICH', '1994-10-28', 'Tabada, Samuel Bacalso', 'Samuel', 'Bacalso', 'Tabada', NULL, NULL, NULL, '1994-10-07', NULL, 'Tabada, Herson Saculingan', 'Bacalso, Marilou', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3779, 'birth', '2004-0539', '10', '28', 'MANOLO FORTICH', '2004-05-29', 'Lusterio, Enzo Nacino', 'Enzo', 'Nacino', 'Lusterio', NULL, NULL, NULL, '2004-05-05', NULL, 'Lusterio, Jerry', 'Nacino, Marilou Tabada', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3780, 'birth', '2004-0540', '27', '25', 'MANOLO FORTICH', '2004-11-04', 'Rosello, Skye Edem', 'Skye', 'Edem', 'Rosello', NULL, NULL, NULL, '2004-10-10', NULL, 'Rosello, Jerry', 'Edem, Nievajane Tabada', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3781, 'birth', '2004-0541', '47', '10', 'MANOLO FORTICH', '2004-11-11', 'Tabada, Enzo Lusterio', 'Enzo', 'Lusterio', 'Tabada', NULL, NULL, NULL, '2004-11-08', NULL, 'Tabada, Samuel Bacalso', 'Lusterio, Sarami', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3782, 'birth', '2005-0542', '59', '83', 'MANOLO FORTICH', '2005-04-04', 'Tabada, Nyla Lusterio', 'Nyla', 'Lusterio', 'Tabada', NULL, NULL, NULL, '2005-03-21', NULL, 'Tabada, Samuel Bacalso', 'Lusterio, Sarami', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3783, 'birth', '2005-0543', '13', '72', 'MANOLO FORTICH', '2005-05-28', 'Montanez, Enzo Nacino', 'Enzo', 'Nacino', 'Montanez', NULL, NULL, NULL, '2005-05-16', NULL, 'Montanez, Mario', 'Nacino, Marilou Tabada', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3784, 'birth', '2005-0544', '25', '39', 'MANOLO FORTICH', '2005-09-09', 'Edem, Zayd Amoyo', 'Zayd', 'Amoyo', 'Edem', NULL, NULL, NULL, '2005-07-30', NULL, 'Edem, Danilo Tabada', 'Amoyo, Jehiel', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3785, 'birth', '2005-0545', '13', '9', 'MANOLO FORTICH', '2005-11-19', 'Tabada, Gabriel Lusterio', 'Gabriel', 'Lusterio', 'Tabada', NULL, NULL, NULL, '2005-11-12', NULL, 'Tabada, Samuel Bacalso', 'Lusterio, Sarami', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3786, 'birth', '2006-0546', '14', '25', 'MANOLO FORTICH', '2006-05-05', 'Lusterio, Alonso Nacino', 'Alonso', 'Nacino', 'Lusterio', NULL, NULL, NULL, '2006-03-25', NULL, 'Lusterio, Jerry', 'Nacino, Marilou Tabada', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3787, 'birth', '2006-0547', '29', '34', 'MANOLO FORTICH', '2006-05-16', 'Bustillo, Skye Nacino', 'Skye', 'Nacino', 'Bustillo', NULL, NULL, NULL, '2006-04-09', NULL, 'Bustillo, Emmanuel', 'Nacino, Grace Tabada', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3788, 'birth', '2006-0548', '21', '69', 'MANOLO FORTICH', '2006-08-22', 'Edem, Cathaleen Caballero', 'Cathaleen', 'Caballero', 'Edem', NULL, NULL, NULL, '2006-07-31', NULL, 'Edem, Danilo Tabada', 'Caballero, Cinde', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:04', NULL, NULL, NULL, NULL, NULL),
(3789, 'birth', '2006-0549', '45', '60', 'MANOLO FORTICH', '2006-10-07', 'Tabada, Marc Ramirez', 'Marc', 'Ramirez', 'Tabada', NULL, NULL, NULL, '2006-08-29', NULL, 'Tabada, Samuel Bacalso', 'Ramirez, Evangeline', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3790, 'birth', '2007-0550', '15', '88', 'MANOLO FORTICH', '2007-02-08', 'Tabada, Mikaela Edem', 'Mikaela', 'Edem', 'Tabada', NULL, NULL, NULL, '2007-01-12', NULL, 'Tabada, Samuel Bacalso', 'Edem, Estrelleta', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3791, 'birth', '2007-0551', '13', '59', 'MANOLO FORTICH', '2007-07-31', 'Tabada, Jerry May Ramirez', 'Jerry', 'May Ramirez', 'Tabada', NULL, NULL, NULL, '2007-06-26', NULL, 'Tabada, Samuel Bacalso', 'Ramirez, Evangeline', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3792, 'birth', '2007-0552', '60', '68', 'MANOLO FORTICH', '2007-08-18', 'Edem, Kezia Caballero', 'Kezia', 'Caballero', 'Edem', NULL, NULL, NULL, '2007-07-24', NULL, 'Edem, Danilo Tabada', 'Caballero, Cinde', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3793, 'birth', '2007-0553', '27', '8', 'MANOLO FORTICH', '2007-12-05', 'Edem, Justin Amoyo', 'Justin', 'Amoyo', 'Edem', NULL, NULL, NULL, '2007-11-06', NULL, 'Edem, Danilo Tabada', 'Amoyo, Jehiel', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3794, 'birth', '2008-0554', '42', '38', 'MANOLO FORTICH', '2008-01-26', 'Edem, Yeshu Amoyo', 'Yeshu', 'Amoyo', 'Edem', NULL, NULL, NULL, '2008-01-20', NULL, 'Edem, Danilo Tabada', 'Amoyo, Jehiel', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3795, 'birth', '2008-0555', '25', '33', 'MANOLO FORTICH', '2008-04-12', 'Montanez, Trisha Nacino', 'Trisha', 'Nacino', 'Montanez', NULL, NULL, NULL, '2008-04-01', NULL, 'Montanez, Mario', 'Nacino, Marilou Tabada', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3796, 'birth', '2008-0556', '25', '6', 'MANOLO FORTICH', '2008-09-29', 'Rosello, Flong Edem', 'Flong', 'Edem', 'Rosello', NULL, NULL, NULL, '2008-08-29', NULL, 'Rosello, Jerry', 'Edem, Nievajane Tabada', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3797, 'birth', '2009-0557', '15', '69', 'MANOLO FORTICH', '2009-05-03', 'Montanez, Jerry May Nacino', 'Jerry', 'May Nacino', 'Montanez', NULL, NULL, NULL, '2009-04-02', NULL, 'Montanez, Mario', 'Nacino, Marilou Tabada', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3798, 'birth', '2011-0558', '25', '27', 'MANOLO FORTICH', '2011-02-27', 'Rosello, Alonso Edem', 'Alonso', 'Edem', 'Rosello', NULL, NULL, NULL, '2011-01-29', NULL, 'Rosello, Jerry', 'Edem, Nievajane Tabada', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3799, 'birth', '2011-0559', '39', '87', 'MANOLO FORTICH', '2011-05-17', 'Tabada, Flong Edem', 'Flong', 'Edem', 'Tabada', NULL, NULL, NULL, '2011-05-13', NULL, 'Tabada, Samuel Bacalso', 'Edem, Estrelleta', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3800, 'birth', '2011-0560', '44', '51', 'MANOLO FORTICH', '2011-09-04', 'Tabada, Vince Edem', 'Vince', 'Edem', 'Tabada', NULL, NULL, NULL, '2011-08-25', NULL, 'Tabada, Samuel Bacalso', 'Edem, Estrelleta', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3801, 'birth', '2012-0561', '35', '31', 'MANOLO FORTICH', '2012-05-14', 'Bustillo, Justin Nacino', 'Justin', 'Nacino', 'Bustillo', NULL, NULL, NULL, '2012-04-03', NULL, 'Bustillo, Emmanuel', 'Nacino, Grace Tabada', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3802, 'birth', '2014-0562', '54', '77', 'MANOLO FORTICH', '2014-04-24', 'Edem, Kezia Bustillo', 'Kezia', 'Bustillo', 'Edem', NULL, NULL, NULL, '2014-03-15', NULL, 'Edem, Justin Amoyo', 'Bustillo, Cathaleen Binanlao', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3803, 'birth', '2014-0563', '36', '40', 'MANOLO FORTICH', '2014-05-17', 'Tabada, Marc Dagno', 'Marc', 'Dagno', 'Tabada', NULL, NULL, NULL, '2014-05-01', NULL, 'Tabada, Flong', 'Dagno, Kyla', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3804, 'birth', '2014-0564', '22', '13', 'MANOLO FORTICH', '2014-09-23', 'Lusterio, Justin Guerero', 'Justin', 'Guerero', 'Lusterio', NULL, NULL, NULL, '2014-09-14', NULL, 'Lusterio, Enzo Nacino', 'Guerero, Belle', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3805, 'birth', '2014-0565', '13', '35', 'MANOLO FORTICH', '2014-09-25', 'Montanez, Keith Rosello', 'Keith', 'Rosello', 'Montanez', NULL, NULL, NULL, '2014-09-17', NULL, 'Montanez, Mark Angelo', 'Rosello, Skye Edem', NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3806, 'birth', '2014-0566', '17', '33', 'MANOLO FORTICH', '2015-01-19', 'Amoyo, Angel Edem', 'Angel', 'Edem', 'Amoyo', NULL, NULL, NULL, '2014-12-12', NULL, 'Amoyo, Enzo', 'Edem, Yeshu Amoyo', NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3807, 'birth', '2017-0567', '55', '57', 'MANOLO FORTICH', '2017-01-13', 'Montanez, Belle Tabada', 'Belle', 'Tabada', 'Montanez', NULL, NULL, NULL, '2017-01-08', NULL, 'Montanez, Enzo Nacino', 'Tabada, Kyla', NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3808, 'birth', '2017-0568', '43', '18', 'MANOLO FORTICH', '2017-07-02', 'Tabada, Belle Libanta', 'Belle', 'Libanta', 'Tabada', NULL, NULL, NULL, '2017-06-29', NULL, 'Tabada, Enzo Lusterio', 'Libanta, Reign', NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3809, 'birth', '2018-0569', '39', '82', 'MANOLO FORTICH', '2018-10-10', 'Abao, Alonso Bustillo', 'Alonso', 'Bustillo', 'Abao', NULL, NULL, NULL, '2018-09-06', NULL, 'Abao, Kyle', 'Bustillo, Skye Nacino', NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3810, 'birth', '2019-0570', '33', '25', 'MANOLO FORTICH', '2019-03-13', 'Tabada, Reign Tuling', 'Reign', 'Tuling', 'Tabada', NULL, NULL, NULL, '2019-03-10', NULL, 'Tabada, Gabriel Lusterio', 'Tuling, Cathaleen', NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3811, 'birth', '2019-0571', '38', '25', 'MANOLO FORTICH', '2019-04-29', 'Binanlao, Vince Edem', 'Vince', 'Edem', 'Binanlao', NULL, NULL, NULL, '2019-03-22', NULL, 'Binanlao, Kian', 'Edem, Cathaleen Caballero', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3812, 'birth', '2019-0572', '53', '80', 'MANOLO FORTICH', '2019-09-03', 'Ceniza, Cathaleen Edem', 'Cathaleen', 'Edem', 'Ceniza', NULL, NULL, NULL, '2019-07-31', NULL, 'Ceniza, Kyle', 'Edem, Kezia Caballero', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3813, 'birth', '2019-0573', '34', '29', 'MANOLO FORTICH', '2019-09-23', 'Bustillo, Cathaleen Tabada', 'Cathaleen', 'Tabada', 'Bustillo', NULL, NULL, NULL, '2019-08-10', NULL, 'Bustillo, Alonso', 'Tabada, Nyla Lusterio', NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3814, 'birth', '2020-0574', '56', '2', 'MANOLO FORTICH', '2020-05-11', 'Tabada, Mark Angelo Dayog', 'Mark', 'Angelo Dayog', 'Tabada', NULL, NULL, NULL, '2020-04-30', NULL, 'Tabada, Vince Edem', 'Dayog, Mikaela', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3815, 'birth', '2020-0575', '49', '58', 'MANOLO FORTICH', '2020-08-12', 'Pahamotang, Jerry May Tabada', 'Jerry', 'May Tabada', 'Pahamotang', NULL, NULL, NULL, '2020-06-30', NULL, 'Pahamotang, Gabriel', 'Tabada, Mikaela Edem', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3816, 'birth', '2020-0576', '52', '28', 'MANOLO FORTICH', '2020-10-09', 'Amoyo, Yeshu Edem', 'Yeshu', 'Edem', 'Amoyo', NULL, NULL, NULL, '2020-09-13', NULL, 'Amoyo, Enzo', 'Edem, Yeshu Amoyo', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3817, 'birth', '2021-0577', '44', '3', 'MANOLO FORTICH', '2021-03-23', 'Tabada, Kezia Dayog', 'Kezia', 'Dayog', 'Tabada', NULL, NULL, NULL, '2021-03-11', NULL, 'Tabada, Vince Edem', 'Dayog, Mikaela', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3818, 'birth', '2021-0578', '52', '9', 'MANOLO FORTICH', '2021-04-01', 'Lusterio, Jojale Bacalso', 'Jojale', 'Bacalso', 'Lusterio', NULL, NULL, NULL, '2021-03-23', NULL, 'Lusterio, Alonso Nacino', 'Bacalso, Kezia', NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3819, 'birth', '2021-0579', '60', '14', 'MANOLO FORTICH', '2022-01-03', 'Binanlao, Alonso Edem', 'Alonso', 'Edem', 'Binanlao', NULL, NULL, NULL, '2021-12-22', NULL, 'Binanlao, Kian', 'Edem, Cathaleen Caballero', NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3820, 'birth', '2022-0580', '30', '9', 'MANOLO FORTICH', '2022-04-13', 'Montanez, Rafael Montanez', 'Rafael', 'Montanez', 'Montanez', NULL, NULL, NULL, '2022-03-02', NULL, 'Montanez, Jerry May Nacino', 'Montanez, Zsanine', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3821, 'birth', '2022-0581', '34', '24', 'MANOLO FORTICH', '2022-03-30', 'Montanez, Skye Montanez', 'Skye', 'Montanez', 'Montanez', NULL, NULL, NULL, '2022-03-27', NULL, 'Montanez, Jerry May Nacino', 'Montanez, Zsanine', NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3822, 'birth', '2022-0582', '57', '5', 'MANOLO FORTICH', '2022-09-19', 'Rosello, Rafael Conlayan', 'Rafael', 'Conlayan', 'Rosello', NULL, NULL, NULL, '2022-08-19', NULL, 'Rosello, Alonso Edem', 'Conlayan, Keith', NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3823, 'birth', '2023-0583', '58', '79', 'MANOLO FORTICH', '2023-07-09', 'Bustillo, Zsanine Tabada', 'Zsanine', 'Tabada', 'Bustillo', NULL, NULL, NULL, '2023-06-21', NULL, 'Bustillo, Alonso', 'Tabada, Nyla Lusterio', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3824, 'birth', '2023-0584', '60', '36', 'MANOLO FORTICH', '2023-09-30', 'Edem, Vince Libanta', 'Vince', 'Libanta', 'Edem', NULL, NULL, NULL, '2023-09-27', NULL, 'Edem, Zayd Amoyo', 'Libanta, Nyla', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3825, 'birth', '2024-0585', '43', '82', 'MANOLO FORTICH', '2024-05-11', 'Tabada, Alonso Pahamotang', 'Alonso', 'Pahamotang', 'Tabada', NULL, NULL, NULL, '2024-03-29', NULL, 'Tabada, Marc Ramirez', 'Pahamotang, Keith', NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3826, 'birth', '2024-0586', '45', '57', 'MANOLO FORTICH', '2024-10-12', 'Tabada, Skye Dignos', 'Skye', 'Dignos', 'Tabada', NULL, NULL, NULL, '2024-09-05', NULL, 'Tabada, Jerry May Ramirez', 'Dignos, Angel', NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3827, 'birth', '2024-0587', '13', '47', 'MANOLO FORTICH', '2025-02-07', 'Bustillo, Trisha Dayog', 'Trisha', 'Dayog', 'Bustillo', NULL, NULL, NULL, '2024-12-27', NULL, 'Bustillo, Justin', 'Dayog, Angel', NULL, 'Ticala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3828, 'birth', '2025-0588', '49', '37', 'MANOLO FORTICH', '2025-04-06', 'Lusterio, Rafael Bacalso', 'Rafael', 'Bacalso', 'Lusterio', NULL, NULL, NULL, '2025-02-27', NULL, 'Lusterio, Alonso Nacino', 'Bacalso, Kezia', NULL, 'Tankulan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3829, 'birth', '2025-0589', '34', '88', 'MANOLO FORTICH', '2025-09-25', 'Rosello, Zsanine Sarzosa', 'Zsanine', 'Sarzosa', 'Rosello', NULL, NULL, NULL, '2025-09-09', NULL, 'Rosello, Flong Edem', 'Sarzosa, Kezia', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3830, 'birth', '2025-0590', '12', '85', 'MANOLO FORTICH', '2025-11-07', 'Tabada, Enzo Dignos', 'Enzo', 'Dignos', 'Tabada', NULL, NULL, NULL, '2025-10-25', NULL, 'Tabada, Jerry May Ramirez', 'Dignos, Angel', NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3831, 'birth', '2025-0591', '22', '9', 'MANOLO FORTICH', '2025-12-26', 'Pahamotang, Reign Tabada', 'Reign', 'Tabada', 'Pahamotang', NULL, NULL, NULL, '2025-12-06', NULL, 'Pahamotang, Gabriel', 'Tabada, Mikaela Edem', NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3832, 'birth', '2026-0592', '29', '2', 'MANOLO FORTICH', '2026-02-04', 'Tabada, Kezia Tuling', 'Kezia', 'Tuling', 'Tabada', NULL, NULL, NULL, '2026-01-01', NULL, 'Tabada, Gabriel Lusterio', 'Tuling, Cathaleen', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3833, 'birth', '2026-0593', '29', '47', 'MANOLO FORTICH', '2026-02-15', 'Tabada, Yeshu Libanta', 'Yeshu', 'Libanta', 'Tabada', NULL, NULL, NULL, '2026-02-07', NULL, 'Tabada, Enzo Lusterio', 'Libanta, Reign', NULL, 'Mantibugao', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3834, 'birth', '2026-0594', '26', '49', 'MANOLO FORTICH', '2026-03-22', 'Montanez, Skye Rosello', 'Skye', 'Rosello', 'Montanez', NULL, NULL, NULL, '2026-03-08', NULL, 'Montanez, Mark Angelo', 'Rosello, Skye Edem', NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3835, 'birth', '2026-0595', '19', '58', 'MANOLO FORTICH', '2026-06-16', 'Amoyo, Cathaleen Montanez', 'Cathaleen', 'Montanez', 'Amoyo', NULL, NULL, NULL, '2026-06-09', NULL, 'Amoyo, Mark Angelo', 'Montanez, Trisha Nacino', NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3836, 'birth', '2026-0596', '30', '60', 'MANOLO FORTICH', '2026-10-24', 'Amoyo, Cathaleen Montanez', 'Cathaleen', 'Montanez', 'Amoyo', NULL, NULL, NULL, '2026-09-17', NULL, 'Amoyo, Mark Angelo', 'Montanez, Trisha Nacino', NULL, 'Guilang-guilang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3837, 'birth', '1969-0597', '36', '86', 'MANOLO FORTICH', '1969-03-10', 'Edem, Johnnas', 'Johnnas', NULL, 'Edem', NULL, NULL, NULL, '1969-02-27', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3838, 'birth', '1969-0598', '56', '39', 'MANOLO FORTICH', '1969-09-26', 'Bacalso, Marilou', 'Marilou', NULL, 'Bacalso', NULL, NULL, NULL, '1969-09-03', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3839, 'birth', '1972-0599', '23', '56', 'MANOLO FORTICH', '1972-10-28', 'Nacino, Johnnas', 'Johnnas', NULL, 'Nacino', NULL, NULL, NULL, '1972-10-06', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3840, 'birth', '1983-0600', '28', '42', 'MANOLO FORTICH', '1983-04-05', 'Rosello, Jerry', 'Jerry', NULL, 'Rosello', NULL, NULL, NULL, '1983-03-25', NULL, NULL, NULL, NULL, 'Agusan Canyon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3841, 'birth', '1983-0601', '36', '35', 'MANOLO FORTICH', '1983-12-11', 'Lusterio, Sarami', 'Sarami', NULL, 'Lusterio', NULL, NULL, NULL, '1983-10-31', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3842, 'birth', '1988-0602', '19', '21', 'MANOLO FORTICH', '1989-01-04', 'Ramirez, Evangeline', 'Evangeline', NULL, 'Ramirez', NULL, NULL, NULL, '1988-12-27', NULL, NULL, NULL, NULL, 'Lunocan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3843, 'birth', '1989-0603', '43', '66', 'MANOLO FORTICH', '1989-09-17', 'Bustillo, Emmanuel', 'Emmanuel', NULL, 'Bustillo', NULL, NULL, NULL, '1989-08-13', NULL, NULL, NULL, NULL, 'Kalugmanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3844, 'birth', '1989-0604', '52', '40', 'MANOLO FORTICH', '1989-11-01', 'Edem, Estrelleta', 'Estrelleta', NULL, 'Edem', NULL, NULL, NULL, '1989-09-28', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3845, 'birth', '1990-0605', '46', '55', 'MANOLO FORTICH', '1990-12-04', 'Caballero, Cinde', 'Cinde', NULL, 'Caballero', NULL, NULL, NULL, '1990-11-27', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3846, 'birth', '1991-0606', '49', '59', 'MANOLO FORTICH', '1991-06-07', 'Montanez, Mario', 'Mario', NULL, 'Montanez', NULL, NULL, NULL, '1991-05-15', NULL, NULL, NULL, NULL, 'Lingion', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `civil_registry_records` (`record_id`, `registry_type`, `registry_number`, `book_no`, `page_no`, `city_municipality`, `date_prepared`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `civil_status`, `date_of_event`, `place_of_event`, `father_name`, `mother_name`, `spouse_name`, `barangay`, `zone`, `remarks`, `scan_image_path`, `scan_image_path_2`, `scan_image_path_back`, `scan_image_path_back_2`, `certificate_data`, `verified_by`, `verified_at`, `created_at`, `date_of_birth`, `wife_father_name`, `wife_mother_name`, `age_at_death`, `cause_of_death`) VALUES
(3847, 'birth', '1991-0607', '45', '86', 'MANOLO FORTICH', '1991-09-15', 'Amoyo, Jehiel', 'Jehiel', NULL, 'Amoyo', NULL, NULL, NULL, '1991-08-29', NULL, NULL, NULL, NULL, 'Diclum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3848, 'birth', '1991-0608', '15', '51', 'MANOLO FORTICH', '1991-11-15', 'Lusterio, Jerry', 'Jerry', NULL, 'Lusterio', NULL, NULL, NULL, '1991-10-18', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3849, 'birth', '2002-0609', '13', '13', 'MANOLO FORTICH', '2002-06-06', 'Bustillo, Alonso', 'Alonso', NULL, 'Bustillo', NULL, NULL, NULL, '2002-05-07', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3850, 'birth', '2003-0610', '60', '37', 'MANOLO FORTICH', '2003-09-09', 'Tabada, Kyla', 'Kyla', NULL, 'Tabada', NULL, NULL, NULL, '2003-08-31', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3851, 'birth', '2004-0611', '42', '21', 'MANOLO FORTICH', '2004-06-10', 'Dayog, Angel', 'Angel', NULL, 'Dayog', NULL, NULL, NULL, '2004-05-15', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3852, 'birth', '2004-0612', '48', '83', 'MANOLO FORTICH', '2004-08-11', 'Guerero, Belle', 'Belle', NULL, 'Guerero', NULL, NULL, NULL, '2004-07-22', NULL, NULL, NULL, NULL, 'Damilag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3853, 'birth', '2004-0613', '56', '54', 'MANOLO FORTICH', '2004-12-14', 'Dignos, Angel', 'Angel', NULL, 'Dignos', NULL, NULL, NULL, '2004-11-17', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3854, 'birth', '2005-0614', '44', '53', 'MANOLO FORTICH', '2005-01-13', 'Sarzosa, Kezia', 'Kezia', NULL, 'Sarzosa', NULL, NULL, NULL, '2005-01-05', NULL, NULL, NULL, NULL, 'Sankanan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3855, 'birth', '2005-0615', '41', '90', 'MANOLO FORTICH', '2005-01-27', 'Bacalso, Kezia', 'Kezia', NULL, 'Bacalso', NULL, NULL, NULL, '2005-01-05', NULL, NULL, NULL, NULL, 'Maluko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3856, 'birth', '2005-0616', '46', '87', 'MANOLO FORTICH', '2005-06-17', 'Libanta, Nyla', 'Nyla', NULL, 'Libanta', NULL, NULL, NULL, '2005-05-09', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3857, 'birth', '2005-0617', '60', '54', 'MANOLO FORTICH', '2005-10-09', 'Binanlao, Kian', 'Kian', NULL, 'Binanlao', NULL, NULL, NULL, '2005-08-28', NULL, NULL, NULL, NULL, 'Lindaban', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3858, 'birth', '2005-0618', '12', '23', 'MANOLO FORTICH', '2005-12-18', 'Amoyo, Mark Angelo', 'Mark', 'Angelo', 'Amoyo', NULL, NULL, NULL, '2005-11-09', NULL, NULL, NULL, NULL, 'Mampayag', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3859, 'birth', '2006-0619', '38', '60', 'MANOLO FORTICH', '2006-03-05', 'Conlayan, Keith', 'Keith', NULL, 'Conlayan', NULL, NULL, NULL, '2006-02-28', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3860, 'birth', '2006-0620', '29', '31', 'MANOLO FORTICH', '2006-09-18', 'Pahamotang, Gabriel', 'Gabriel', NULL, 'Pahamotang', NULL, NULL, NULL, '2006-08-04', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3861, 'birth', '2007-0621', '26', '14', 'MANOLO FORTICH', '2007-01-27', 'Libanta, Reign', 'Reign', NULL, 'Libanta', NULL, NULL, NULL, '2007-01-23', NULL, NULL, NULL, NULL, 'Dahilayan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3862, 'birth', '2007-0622', '15', '83', 'MANOLO FORTICH', '2007-05-28', 'Montanez, Mark Angelo', 'Mark', 'Angelo', 'Montanez', NULL, NULL, NULL, '2007-04-24', NULL, NULL, NULL, NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3863, 'birth', '2007-0623', '22', '26', 'MANOLO FORTICH', '2007-09-11', 'Abao, Kyle', 'Kyle', NULL, 'Abao', NULL, NULL, NULL, '2007-09-07', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3864, 'birth', '2008-0624', '53', '61', 'MANOLO FORTICH', '2008-04-15', 'Amoyo, Enzo', 'Enzo', NULL, 'Amoyo', NULL, NULL, NULL, '2008-03-17', NULL, NULL, NULL, NULL, 'Alae', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3865, 'birth', '2008-0625', '10', '55', 'MANOLO FORTICH', '2008-06-05', 'Dayog, Mikaela', 'Mikaela', NULL, 'Dayog', NULL, NULL, NULL, '2008-05-30', NULL, NULL, NULL, NULL, 'San Miguel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3866, 'birth', '2008-0626', '54', '31', 'MANOLO FORTICH', '2009-01-04', 'Montanez, Zsanine', 'Zsanine', NULL, 'Montanez', NULL, NULL, NULL, '2008-12-07', NULL, NULL, NULL, NULL, 'Santiago', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3867, 'birth', '2009-0627', '23', '22', 'MANOLO FORTICH', '2009-06-13', 'Dagno, Kyla', 'Kyla', NULL, 'Dagno', NULL, NULL, NULL, '2009-06-04', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3868, 'birth', '2011-0628', '15', '50', 'MANOLO FORTICH', '2011-04-16', 'Ceniza, Kyle', 'Kyle', NULL, 'Ceniza', NULL, NULL, NULL, '2011-03-24', NULL, NULL, NULL, NULL, 'Sto. Niño', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3869, 'birth', '2011-0629', '60', '74', 'MANOLO FORTICH', '2011-11-30', 'Pahamotang, Keith', 'Keith', NULL, 'Pahamotang', NULL, NULL, NULL, '2011-11-21', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3870, 'birth', '2011-0630', '48', '79', 'MANOLO FORTICH', '2011-12-24', 'Tuling, Cathaleen', 'Cathaleen', NULL, 'Tuling', NULL, NULL, NULL, '2011-12-16', NULL, NULL, NULL, NULL, 'Mambatangan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3871, 'birth', '2012-0631', '12', '55', 'MANOLO FORTICH', '2012-09-08', 'Bustillo, Cathaleen', 'Cathaleen', NULL, 'Bustillo', NULL, NULL, NULL, '2012-08-24', NULL, NULL, NULL, NULL, 'Minsuro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 18:43:05', NULL, NULL, NULL, NULL, NULL),
(3872, 'birth', '2026-287', '43', NULL, 'Manolo Fortich', '1980-03-08', 'Sain, Omar', 'Omar', NULL, 'Sain', 'NA', 'male', 'married', NULL, NULL, 'Sain, odie Lumuna', 'Sain, Saria Lambana', 'Sain, Maylade Piloton', 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-07 20:33:44', NULL, NULL, NULL, NULL, NULL),
(3874, 'birth', NULL, NULL, NULL, 'Manolo Fortich', NULL, 'Sain, Aleah Piloton', 'Aleah', 'Piloton', 'Sain', NULL, NULL, NULL, NULL, NULL, 'Sain, Omar', 'Sain, Maylade Piloton', NULL, 'Dicklum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-07 20:33:44', NULL, NULL, NULL, NULL, NULL),
(3877, 'birth', '2020-01', '51', '51', 'MANOLO FORTICH', '2020-09-21', 'Christian Rey Bocon Sanico', 'Christian', 'Rey Bocon Sanico', '', NULL, NULL, NULL, '2004-05-11', NULL, 'Rene Calipan Sanico', 'Emma Bocon Sanico', NULL, 'Dalirig', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-08 11:02:32', NULL, NULL, NULL, NULL, NULL),
(3878, 'birth', '2020-02', '56', '56', 'MANOLO FORTICH', '2020-09-24', 'Marie Bocon Sanico', 'Marie', 'Bocon Sanico', '', NULL, NULL, NULL, '1995-08-24', NULL, 'Rene Calipan Sanico', 'Emma Bocon Sanico', NULL, 'linge on', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-08 11:02:32', NULL, NULL, NULL, NULL, NULL),
(3880, 'birth', 'jbkkb7685', NULL, NULL, 'hjh', '0000-00-00', 'mb, khb hbk', 'khb', 'hbk', 'mb', NULL, 'male', NULL, NULL, NULL, 'hjbb', 'kbkj, bk kbk', NULL, 'nvj', NULL, NULL, 'uploads/civil-registry-scans/scan_8_front_1788948996_287.jpg', NULL, 'uploads/civil-registry-scans/scan_8_back_1788948997_850.jpg', NULL, '{\"registry_number\":\"jbkkb7685\",\"province\":\"yfcjg\",\"city_municipality\":\"hjh\",\"barangay\":\"nvj\",\"date_prepared\":\"khbkh986597\",\"child_last\":\"mb\",\"child_first\":\"khb\",\"child_middle\":\"hbk\",\"child_sex\":\"hv\",\"date_of_birth\":\"\",\"type_of_birth\":\"\",\"birth_order\":\"mb m\",\"place_of_birth_hospital\":\"kjbk\",\"place_of_birth_city\":\"jbjk\",\"place_of_birth_province\":\"n\",\"mother_last\":\"kbkj\",\"mother_first\":\"bk\",\"mother_middle\":\"kbk\",\"mother_citizenship\":\"m\",\"mother_religion\":\"\",\"mother_occupation\":\"\",\"mother_age\":\"\",\"mother_residence\":\"\",\"father_last\":\"\",\"father_first\":\"\",\"father_middle\":\"hjbb\",\"father_citizenship\":\"\",\"father_religion\":\"\",\"father_occupation\":\"\",\"father_age\":\"\",\"father_residence\":\"\",\"parents_marriage_date\":\"\",\"parents_marriage_place\":\"\",\"attendant_type\":\"\",\"attendant_name\":\"\",\"informant_name\":\"\",\"informant_relationship\":\"\",\"informant_address\":\"\",\"prepared_by\":\"\",\"received_by\":\"\",\"registered_by\":\"\"}', 8, '2026-09-09 18:17:37', '2026-09-09 18:17:37', NULL, NULL, NULL, NULL, NULL),
(3885, 'birth', '2005-3476', NULL, '102', NULL, '2003-05-02', 'Aldrin Piloton Sain', 'Aldrin', 'Piloton', 'Sain', NULL, NULL, NULL, '2003-04-03', NULL, 'Omar Lambana Sain', 'Maylade Piloton Sain', NULL, 'Dicklum', NULL, NULL, 'uploads/civil-registry-scans/scan_2_front1_1792369911_762.jpg', NULL, NULL, NULL, '{\"registry_number\":\"2005-3476\",\"province\":\"inall ICH dE ry\",\"city_municipality\":\"MANOLO FORT TE) EET\",\"barangay\":\"DICKLUM 3 MANOLO FORTICH BUKIDNON PHILIPPINES\",\"child_first\":\"7 OMAR LAMBANA SAIN\",\"child_sex\":\"FEMALE\",\"date_of_birth\":\"FEBRUARY 15, 2012\",\"mother_religion\":\"/RELIGIOUS SECT\",\"mother_occupation\":\"[12: CEE\",\"mother_residence\":\"DICKLUM 3 MANOLO FORTICH BUKIDNON PHILIPPINES\",\"father_first\":\"7 OMAR LAMBANA SAIN\",\"father_religion\":\"/RELIGIOUS SECT 17.0CCUPATION 18. AGE at the time of this\",\"father_age\":\"the time of this\",\"father_residence\":\"Rl SRR DICKITUM MANOLO FORTICH BUKIDNON PHILIPPINES\",\"attendant_time_born\":\"04:00 PM\",\"attendant_title\":\"NURSE TT RY ee ee CR Std\"}', 2, '2026-10-20 07:49:29', '2026-10-20 07:49:29', NULL, NULL, NULL, NULL, NULL),
(3886, 'birth', '7980-87008', NULL, '102', NULL, '2026-10-14', 'Alan Piloton Sain', 'Alan', 'Piloton', 'Sain', NULL, NULL, NULL, '2026-10-09', NULL, 'Omar Lambana Sain', 'Mayalade Piloton Sain', NULL, 'Barangay', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-20 07:52:55', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `identity_verifications`
--

CREATE TABLE `identity_verifications` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matched_record_id` int(11) DEFAULT NULL,
  `search_full_name` varchar(200) DEFAULT NULL,
  `id_image_path` varchar(255) DEFAULT NULL,
  `verification_method` enum('name_search','id_scan') NOT NULL,
  `status` enum('pending','verified','failed') DEFAULT 'pending',
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `identity_verifications`
--

INSERT INTO `identity_verifications` (`verification_id`, `user_id`, `matched_record_id`, `search_full_name`, `id_image_path`, `verification_method`, `status`, `verified_at`, `created_at`) VALUES
(1, 5, NULL, NULL, NULL, 'id_scan', 'verified', '2026-08-30 22:47:14', '2026-08-30 22:47:14'),
(2, 6, NULL, NULL, NULL, 'id_scan', 'verified', '2026-08-31 12:33:19', '2026-08-31 12:33:19'),
(3, 7, NULL, NULL, NULL, 'id_scan', 'verified', '2026-09-07 20:40:36', '2026-09-07 20:40:36');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `login_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`log_id`, `user_id`, `ip_address`, `user_agent`, `login_at`) VALUES
(1, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 09:45:14'),
(2, 3, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-08-29 09:46:32'),
(3, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 07:47:45'),
(4, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 19:47:26'),
(5, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 19:58:11'),
(6, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:08:09'),
(7, 5, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-30 22:48:16'),
(8, 5, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-30 22:49:19'),
(9, 5, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-30 22:53:22'),
(10, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 11:50:41'),
(11, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 12:32:04'),
(12, 6, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-31 12:34:27'),
(13, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 15:40:18'),
(14, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 21:22:18'),
(15, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 15:31:38'),
(16, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 15:58:55'),
(17, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 11:33:24'),
(18, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 20:16:17'),
(19, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 07:09:58'),
(20, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 07:14:14'),
(21, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 05:52:11'),
(22, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 13:23:10'),
(23, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 13:23:54'),
(24, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 18:09:57'),
(25, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 18:10:54'),
(26, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 19:23:13'),
(27, 7, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Mobile Safari/537.36', '2026-09-07 20:51:19'),
(28, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 10:43:42'),
(29, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 10:47:17'),
(30, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 10:52:22'),
(31, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 11:02:14'),
(32, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 14:06:38'),
(33, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 14:16:59'),
(34, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 15:39:17'),
(35, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 15:52:02'),
(36, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:03:55'),
(37, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:08:48'),
(38, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:09:23'),
(39, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:21:52'),
(40, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:22:20'),
(41, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:22:45'),
(42, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:23:35'),
(43, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:24:45'),
(44, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:25:14'),
(45, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:26:35'),
(46, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:31:25'),
(47, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:37:09'),
(48, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:37:19'),
(49, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:37:33'),
(50, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:38:00'),
(51, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:53:13'),
(52, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 17:55:52'),
(53, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 22:01:43'),
(54, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:18:13'),
(55, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 06:22:43'),
(56, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:29:43'),
(57, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 07:32:21'),
(58, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:26:13'),
(59, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:26:25'),
(60, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:27:15'),
(61, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:35:21'),
(62, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:37:21'),
(63, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:46:42'),
(64, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 14:59:41'),
(65, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:06:53'),
(66, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:11:26'),
(67, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:12:09'),
(68, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 15:12:49'),
(69, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 16:39:38'),
(70, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 16:46:36'),
(71, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 17:17:37'),
(72, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 18:50:48'),
(73, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 19:27:49'),
(74, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 19:28:09'),
(75, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 19:29:33'),
(76, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 19:31:27'),
(77, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 06:57:26'),
(78, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 07:11:03'),
(79, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 07:12:37'),
(80, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 07:13:38'),
(81, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 07:37:34'),
(82, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 19:19:47'),
(83, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 20:02:39'),
(84, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 08:37:35'),
(85, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-15 06:20:43'),
(86, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-15 06:34:01'),
(87, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-15 11:08:39'),
(88, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 11:28:12'),
(89, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 07:47:41'),
(90, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 14:04:09'),
(91, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 14:06:40'),
(92, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 14:15:03'),
(93, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 17:37:00'),
(94, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-27 14:55:32'),
(95, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-28 16:17:18'),
(96, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-28 16:42:19'),
(97, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 08:46:48'),
(98, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 09:19:25'),
(99, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 10:11:24'),
(100, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 10:19:42'),
(101, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 10:20:03'),
(102, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 10:31:03'),
(103, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-14 13:34:38'),
(104, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 11:20:15'),
(105, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 11:20:30'),
(106, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 11:47:56'),
(107, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 11:50:14'),
(108, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 11:57:41'),
(109, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 16:55:30'),
(110, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 07:41:22'),
(111, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 15:29:43'),
(112, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 15:29:59'),
(113, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 15:30:30'),
(114, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 15:30:47'),
(115, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 16:07:18'),
(116, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 16:08:16'),
(117, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 08:04:00'),
(118, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 14:21:50'),
(119, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 14:23:23');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `otp_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('email_verification','password_reset','login_2fa') DEFAULT 'email_verification',
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`otp_id`, `user_id`, `otp_code`, `purpose`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 5, '777293', 'email_verification', '2026-08-30 22:57:14', 1, '2026-08-30 22:47:14'),
(2, 6, '912566', 'email_verification', '2026-08-31 12:43:19', 1, '2026-08-31 12:33:19'),
(3, 7, '450679', 'email_verification', '2026-09-07 20:50:36', 0, '2026-09-07 20:40:36'),
(4, 7, '333173', 'email_verification', '2026-09-07 21:00:57', 1, '2026-09-07 20:50:57');

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `person_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `sex` enum('male','female') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `death_date` date DEFAULT NULL,
  `status` enum('living','deceased') DEFAULT 'living',
  `barangay` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `relationships`
--

CREATE TABLE `relationships` (
  `relationship_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `related_person_id` int(11) NOT NULL,
  `relationship_type` enum('parent','child','spouse','sibling') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_reports`
--

CREATE TABLE `staff_reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_reports`
--

INSERT INTO `staff_reports` (`report_id`, `report_type`, `description`, `generated_by`, `generated_at`) VALUES
(1, 'Civil Registry Summary', 'Summary of all registry records', 2, '2026-08-29 09:43:41'),
(2, 'Verified Records Report', 'List of all verified records', 2, '2026-08-29 09:43:41');

-- --------------------------------------------------------

--
-- Table structure for table `system_ratings`
--

CREATE TABLE `system_ratings` (
  `rating_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stars` tinyint(4) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('user','office_head','admin') DEFAULT 'user',
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `identity_verified` tinyint(1) DEFAULT 0,
  `matched_record_id` int(11) DEFAULT NULL,
  `gender` enum('male','female','prefer_not_to_say') DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `civil_status` enum('single','married','widowed','divorced') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone_number`, `password_hash`, `google_id`, `role`, `status`, `email_verified`, `identity_verified`, `matched_record_id`, `gender`, `barangay`, `address`, `civil_status`, `date_of_birth`, `profile_photo`, `last_seen`, `created_at`, `updated_at`) VALUES
(1, 'Aldrin Piloton Sain', 'aldrin123@gmail.com', '09123456789', '$2b$10$K3P8MD/4p7D0Yzm32HzVBuU7KyYo7im5g9gpgoXSXgyS97ERcHqPi', NULL, 'user', 'active', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-29 09:43:41', '2026-08-30 08:05:41'),
(2, 'yanyan', 'staff@geogene.gov.ph', '', '$2b$10$cnmAZ6AH0p/CLFV87daeYOC2cQeTzGCTG2mvFeTI6ogHEAuG7sP5a', NULL, 'office_head', 'active', 1, 1, NULL, 'male', NULL, 'Zone 1, Dicklum, Manolo Fortich, Bukidnon', 'single', NULL, 'uploads/staff-photos/staff_2_1789126318.png', '2026-10-20 13:34:01', '2026-08-29 09:43:41', '2026-10-20 13:34:01'),
(3, 'Sain, Aldrin Piloton', 'aldrin123@example.com', '09123456789', '$2b$10$K3P8MD/4p7D0Yzm32HzVBuU7KyYo7im5g9gpgoXSXgyS97ERcHqPi', NULL, 'user', 'active', 1, 1, NULL, 'male', 'Dicklum', NULL, 'single', NULL, 'uploads/profile-photos/user_3_1789215657.png', '2026-10-20 14:21:50', '2026-08-29 09:43:41', '2026-10-20 14:21:50'),
(4, 'Aldrin Sain', 'aldrinpilotonsain2003@gmail.com', NULL, NULL, '117386655392944592226', 'user', 'active', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-30 19:47:26', '2026-09-05 20:03:41'),
(5, 'Aldrin Piloton Sain', '20231499@nbsc.edu.ph', '09126217661', '$2y$10$tqA5Fk/I/ASNVTPO2cAf1OdEMp7EOwqgUj2CbQDlntvVBTldqVxm6', NULL, 'user', 'active', 1, 1, NULL, 'male', NULL, NULL, 'single', '2003-04-03', 'uploads/profile-photos/user_5_1788101639.jpg', '2026-08-31 12:29:54', '2026-08-30 22:47:14', '2026-08-31 12:29:54'),
(6, 'Christian Rey Bocon Sanico', '20231667@nbsc.edu.ph', NULL, '$2y$10$ABYR.2Vhy5PoCLP6IXbBs.RiQ/TqqowEyEH4B1G/kk/19.f/jDML6', NULL, 'user', 'active', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 12:34:31', '2026-08-31 12:33:19', '2026-08-31 12:34:31'),
(7, 'Omar Lambana Sain', 'aleahsain190@gmail.com', '09532888089', '$2y$10$embnIm55XNWVMEovN8aaPOWLmr0mUQhUggwMbMKk7p5on3jSvzAwK', NULL, 'user', 'active', 1, 1, 3238, 'male', NULL, NULL, 'married', '1980-05-09', 'uploads/profile-photos/user_7_1788785730.jpg', '2026-09-07 20:58:41', '2026-09-07 20:40:36', '2026-10-14 09:25:04'),
(8, 'Adminn niiiii uwwaww', 'admin@geogene.gov.ph', '09123456789', '$2b$10$R4nIbq6S6uhYfVGal66UP.ZCEh3kgug0yeVfXJpCNDkVbmVJWlsH6', NULL, 'admin', 'active', 1, 1, NULL, 'male', NULL, 'Zone 1, Dicklum, Manolo Fortich, Bukidnon', 'single', NULL, 'uploads/staff-photos/staff_8_1789184013.png', '2026-10-20 14:30:31', '2026-09-09 15:51:27', '2026-10-20 14:30:31'),
(10, 'Trisha', 'staff2@geogene.gov.ph', '09126217651', '$2y$10$/HOhVaTfYiSR50Btkom7OeVI4NdJ.vqOf9qlfQxWbVYPw7WmdcmSC', NULL, 'office_head', 'active', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/staff-photos/staff_10_1789124003.png', '2026-10-15 11:53:02', '2026-09-10 17:06:40', '2026-10-18 16:08:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `dark_mode` tinyint(1) DEFAULT 0,
  `email_notifications` tinyint(1) DEFAULT 1,
  `profile_visibility` enum('public','private') DEFAULT 'private'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`setting_id`, `user_id`, `dark_mode`, `email_notifications`, `profile_visibility`) VALUES
(1, 3, 1, 1, 'private'),
(2, 2, 0, 1, 'private'),
(3, 5, 1, 1, 'private'),
(22, 6, 0, 1, 'private'),
(49, 7, 1, 1, 'private'),
(54, 10, 1, 1, 'private'),
(55, 8, 0, 1, 'private');

-- --------------------------------------------------------

--
-- Table structure for table `verified_id_numbers`
--

CREATE TABLE `verified_id_numbers` (
  `id` int(11) NOT NULL,
  `id_hash` char(64) NOT NULL,
  `id_type` varchar(30) NOT NULL,
  `matched_record_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verified_id_numbers`
--

INSERT INTO `verified_id_numbers` (`id`, `id_hash`, `id_type`, `matched_record_id`, `created_at`) VALUES
(1, 'b3f207c95293869c0b39508973627165acadfa85e747713b3a20712d0df86481', 'philhealth', 3885, '2026-10-20 08:59:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `civil_registry_records`
--
ALTER TABLE `civil_registry_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `registry_number` (`registry_number`);

--
-- Indexes for table `identity_verifications`
--
ALTER TABLE `identity_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `matched_record_id` (`matched_record_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`person_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `relationships`
--
ALTER TABLE `relationships`
  ADD PRIMARY KEY (`relationship_id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `related_person_id` (`related_person_id`);

--
-- Indexes for table `staff_reports`
--
ALTER TABLE `staff_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `system_ratings`
--
ALTER TABLE `system_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `fk_users_matched_record` (`matched_record_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `verified_id_numbers`
--
ALTER TABLE `verified_id_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_hash` (`id_hash`),
  ADD KEY `matched_record_id` (`matched_record_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `civil_registry_records`
--
ALTER TABLE `civil_registry_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3887;

--
-- AUTO_INCREMENT for table `identity_verifications`
--
ALTER TABLE `identity_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `person_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `relationships`
--
ALTER TABLE `relationships`
  MODIFY `relationship_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_reports`
--
ALTER TABLE `staff_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_ratings`
--
ALTER TABLE `system_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=292;

--
-- AUTO_INCREMENT for table `verified_id_numbers`
--
ALTER TABLE `verified_id_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `identity_verifications`
--
ALTER TABLE `identity_verifications`
  ADD CONSTRAINT `identity_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `identity_verifications_ibfk_2` FOREIGN KEY (`matched_record_id`) REFERENCES `civil_registry_records` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `persons`
--
ALTER TABLE `persons`
  ADD CONSTRAINT `persons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `persons_ibfk_2` FOREIGN KEY (`record_id`) REFERENCES `civil_registry_records` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `relationships`
--
ALTER TABLE `relationships`
  ADD CONSTRAINT `relationships_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relationships_ibfk_2` FOREIGN KEY (`related_person_id`) REFERENCES `persons` (`person_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_reports`
--
ALTER TABLE `staff_reports`
  ADD CONSTRAINT `staff_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `system_ratings`
--
ALTER TABLE `system_ratings`
  ADD CONSTRAINT `system_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_matched_record` FOREIGN KEY (`matched_record_id`) REFERENCES `civil_registry_records` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `verified_id_numbers`
--
ALTER TABLE `verified_id_numbers`
  ADD CONSTRAINT `verified_id_numbers_ibfk_1` FOREIGN KEY (`matched_record_id`) REFERENCES `civil_registry_records` (`record_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
