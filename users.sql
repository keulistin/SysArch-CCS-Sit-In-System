-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 05:03 AM
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
-- Database: `users`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(50) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `created_at`) VALUES
(1, 'ADMIN001', 'Updated PC 5 in Lab 517 to status: Used', '2025-04-25 04:22:25'),
(2, 'ADMIN001', 'Updated PC 20 in Lab 517 to status: Used', '2025-04-25 04:36:01'),
(3, 'ADMIN001', 'Updated PC 1 in Lab 517 to status: Available', '2025-04-27 20:36:35'),
(4, 'ADMIN001', 'Updated PC 194 in Lab 530 to status: Used', '2025-04-27 20:37:27'),
(5, 'ADMIN001', 'Updated PC 1 in Lab 517 to status: Used', '2025-04-27 20:37:37'),
(6, 'ADMIN001', 'Updated PC 3 in Lab 517 to status: Used', '2025-04-27 20:37:43'),
(7, 'ADMIN001', 'Updated PC 4 in Lab 517 to status: Used', '2025-04-27 20:37:48'),
(8, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-04-28 04:37:18'),
(9, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-04-28 04:37:36'),
(10, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-04-28 04:37:45'),
(11, 'ADMIN001', 'Updated PC 50 in Lab 524 to status: Used', '2025-04-28 07:32:32'),
(12, 'ADMIN001', 'Updated ALL PCs in Lab 528 to status: Used', '2025-04-28 07:32:50'),
(13, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-04-28 07:39:33'),
(14, 'ADMIN001', 'Updated ALL PCs in Lab 524 to status: Used', '2025-04-28 07:39:40'),
(15, 'ADMIN001', 'Updated ALL PCs in Lab 524 to status: Used', '2025-04-28 07:42:24'),
(16, 'ADMIN001', 'Updated ALL PCs in Lab 524 to status: Used', '2025-04-28 07:50:08'),
(17, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-04-29 02:37:14'),
(18, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-04-29 02:37:21'),
(19, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-04-29 03:15:05'),
(20, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-04-29 06:58:30'),
(21, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-04-29 07:04:02'),
(22, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-04-30 19:43:41'),
(23, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-05-09 02:05:28'),
(24, 'ADMIN001', 'Updated PC 6 in Lab 517 to status: Maintenance', '2025-05-11 07:39:02'),
(25, 'ADMIN001', 'Updated PC 6 in Lab 517 to status: Maintenance', '2025-05-11 07:39:38'),
(26, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Used', '2025-05-13 07:31:00'),
(27, 'ADMIN001', 'Updated ALL PCs in Lab 517 to status: Available', '2025-05-13 07:31:03'),
(28, 'ADMIN001', 'Updated ALL PCs in Lab 544 to status: Maintenance', '2025-05-13 07:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`) VALUES
(2, 'hi', 'hello', '2025-03-23 21:47:09'),
(3, 'Server is down', 'Due to the power outage, the system is currently down. No available labs for today.', '2025-05-09 01:48:51'),
(5, 'Free Snacks', 'For top students', '2025-05-09 05:25:01');

-- --------------------------------------------------------

--
-- Table structure for table `lab_pcs`
--

CREATE TABLE `lab_pcs` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` enum('Available','Used','Maintenance') DEFAULT 'Available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_pcs`
--

INSERT INTO `lab_pcs` (`id`, `lab_name`, `pc_number`, `status`, `last_updated`) VALUES
(1, 'Lab 517', 1, 'Available', '2025-05-13 09:05:31'),
(2, 'Lab 517', 2, 'Available', '2025-05-13 07:31:03'),
(3, 'Lab 517', 3, 'Used', '2025-05-13 09:06:09'),
(4, 'Lab 517', 4, 'Available', '2025-05-13 07:31:03'),
(5, 'Lab 517', 5, 'Available', '2025-05-13 07:31:03'),
(6, 'Lab 517', 6, 'Available', '2025-05-13 07:31:03'),
(7, 'Lab 517', 7, 'Used', '2025-05-13 09:26:59'),
(8, 'Lab 517', 8, 'Available', '2025-05-13 07:31:03'),
(9, 'Lab 517', 9, 'Available', '2025-05-13 07:31:03'),
(10, 'Lab 517', 10, 'Available', '2025-05-13 07:31:03'),
(11, 'Lab 517', 11, 'Available', '2025-05-13 07:31:03'),
(12, 'Lab 517', 12, 'Available', '2025-05-13 07:31:03'),
(13, 'Lab 517', 13, 'Available', '2025-05-13 07:31:03'),
(14, 'Lab 517', 14, 'Available', '2025-05-13 07:31:03'),
(15, 'Lab 517', 15, 'Available', '2025-05-13 07:31:03'),
(16, 'Lab 517', 16, 'Available', '2025-05-13 07:31:03'),
(17, 'Lab 517', 17, 'Available', '2025-05-13 07:31:03'),
(18, 'Lab 517', 18, 'Available', '2025-05-13 07:31:03'),
(19, 'Lab 517', 19, 'Available', '2025-05-13 07:31:03'),
(20, 'Lab 517', 20, 'Available', '2025-05-13 07:31:03'),
(21, 'Lab 517', 21, 'Available', '2025-05-13 07:31:03'),
(22, 'Lab 517', 22, 'Available', '2025-05-13 07:31:03'),
(23, 'Lab 517', 23, 'Available', '2025-05-13 07:31:03'),
(24, 'Lab 517', 24, 'Available', '2025-05-13 07:31:03'),
(25, 'Lab 517', 25, 'Available', '2025-05-13 07:31:03'),
(26, 'Lab 517', 26, 'Available', '2025-05-13 07:31:03'),
(27, 'Lab 517', 27, 'Available', '2025-05-13 07:31:03'),
(28, 'Lab 517', 28, 'Available', '2025-05-13 07:31:03'),
(29, 'Lab 517', 29, 'Available', '2025-05-13 07:31:03'),
(30, 'Lab 517', 30, 'Available', '2025-05-13 07:31:03'),
(31, 'Lab 517', 31, 'Available', '2025-05-13 07:31:03'),
(32, 'Lab 517', 32, 'Available', '2025-05-13 07:31:03'),
(33, 'Lab 517', 33, 'Available', '2025-05-13 07:31:03'),
(34, 'Lab 517', 34, 'Available', '2025-05-13 07:31:03'),
(35, 'Lab 517', 35, 'Available', '2025-05-13 07:31:03'),
(36, 'Lab 517', 36, 'Available', '2025-05-13 07:31:03'),
(37, 'Lab 517', 37, 'Available', '2025-05-13 07:31:03'),
(38, 'Lab 517', 38, 'Available', '2025-05-13 07:31:03'),
(39, 'Lab 517', 39, 'Available', '2025-05-13 07:31:03'),
(40, 'Lab 517', 40, 'Available', '2025-05-13 07:31:03'),
(41, 'Lab 517', 41, 'Available', '2025-05-13 07:31:03'),
(42, 'Lab 517', 42, 'Available', '2025-05-13 07:31:03'),
(43, 'Lab 517', 43, 'Available', '2025-05-13 07:31:03'),
(44, 'Lab 517', 44, 'Available', '2025-05-13 07:31:03'),
(45, 'Lab 517', 45, 'Available', '2025-05-13 07:31:03'),
(46, 'Lab 517', 46, 'Available', '2025-05-13 07:31:03'),
(47, 'Lab 517', 47, 'Available', '2025-05-13 07:31:03'),
(48, 'Lab 517', 48, 'Available', '2025-05-13 07:31:03'),
(49, 'Lab 524', 1, 'Used', '2025-04-26 02:38:58'),
(50, 'Lab 524', 2, 'Used', '2025-04-28 07:32:32'),
(51, 'Lab 524', 3, 'Used', '2025-04-28 07:42:24'),
(52, 'Lab 524', 4, 'Used', '2025-04-28 07:39:40'),
(53, 'Lab 524', 5, 'Used', '2025-04-28 07:39:40'),
(54, 'Lab 524', 6, 'Used', '2025-04-28 07:39:40'),
(55, 'Lab 524', 7, 'Used', '2025-04-28 07:39:40'),
(56, 'Lab 524', 8, 'Used', '2025-04-26 02:55:26'),
(57, 'Lab 524', 9, 'Used', '2025-04-28 07:39:40'),
(58, 'Lab 524', 10, 'Used', '2025-04-28 07:39:40'),
(59, 'Lab 524', 11, 'Used', '2025-04-28 07:39:40'),
(60, 'Lab 524', 12, 'Used', '2025-04-28 07:39:40'),
(61, 'Lab 524', 13, 'Used', '2025-04-28 07:39:40'),
(62, 'Lab 524', 14, 'Used', '2025-04-28 07:39:40'),
(63, 'Lab 524', 15, 'Used', '2025-04-27 13:02:00'),
(64, 'Lab 524', 16, 'Used', '2025-04-28 07:39:40'),
(65, 'Lab 524', 17, 'Used', '2025-04-28 07:39:40'),
(66, 'Lab 524', 18, 'Used', '2025-04-28 07:39:40'),
(67, 'Lab 524', 19, 'Used', '2025-04-28 07:39:40'),
(68, 'Lab 524', 20, 'Used', '2025-04-28 07:39:40'),
(69, 'Lab 524', 21, 'Used', '2025-04-28 07:39:40'),
(70, 'Lab 524', 22, 'Used', '2025-04-28 07:39:40'),
(71, 'Lab 524', 23, 'Used', '2025-04-28 07:39:40'),
(72, 'Lab 524', 24, 'Used', '2025-04-28 07:39:40'),
(73, 'Lab 524', 25, 'Used', '2025-04-28 07:39:40'),
(74, 'Lab 524', 26, 'Used', '2025-04-28 07:39:40'),
(75, 'Lab 524', 27, 'Used', '2025-04-28 07:39:40'),
(76, 'Lab 524', 28, 'Used', '2025-04-28 07:39:40'),
(77, 'Lab 524', 29, 'Used', '2025-04-28 07:39:40'),
(78, 'Lab 524', 30, 'Used', '2025-04-28 07:39:40'),
(79, 'Lab 524', 31, 'Used', '2025-04-28 07:39:40'),
(80, 'Lab 524', 32, 'Used', '2025-04-28 07:39:40'),
(81, 'Lab 524', 33, 'Used', '2025-04-28 07:39:40'),
(82, 'Lab 524', 34, 'Used', '2025-04-28 07:39:40'),
(83, 'Lab 524', 35, 'Used', '2025-04-28 07:39:40'),
(84, 'Lab 524', 36, 'Used', '2025-04-28 07:39:40'),
(85, 'Lab 524', 37, 'Used', '2025-04-28 07:39:40'),
(86, 'Lab 524', 38, 'Used', '2025-04-28 07:39:40'),
(87, 'Lab 524', 39, 'Used', '2025-04-28 07:39:40'),
(88, 'Lab 524', 40, 'Used', '2025-04-28 07:39:40'),
(89, 'Lab 524', 41, 'Used', '2025-04-28 07:39:40'),
(90, 'Lab 524', 42, 'Used', '2025-04-28 07:39:40'),
(91, 'Lab 524', 43, 'Used', '2025-04-28 07:39:40'),
(92, 'Lab 524', 44, 'Used', '2025-04-28 07:39:40'),
(93, 'Lab 524', 45, 'Used', '2025-04-28 07:39:40'),
(94, 'Lab 524', 46, 'Used', '2025-04-28 07:39:40'),
(95, 'Lab 524', 47, 'Used', '2025-04-28 07:39:40'),
(96, 'Lab 524', 48, 'Used', '2025-04-25 05:05:18'),
(97, 'Lab 526', 1, 'Used', '2025-04-27 06:16:40'),
(98, 'Lab 526', 2, 'Available', '2025-05-02 20:31:59'),
(99, 'Lab 526', 3, 'Used', '2025-04-26 02:48:34'),
(100, 'Lab 526', 4, 'Available', '2025-04-25 04:22:09'),
(101, 'Lab 526', 5, 'Available', '2025-05-02 20:22:30'),
(102, 'Lab 526', 6, 'Used', '2025-04-26 03:55:38'),
(103, 'Lab 526', 7, 'Used', '2025-04-26 02:57:19'),
(104, 'Lab 526', 8, 'Available', '2025-04-30 19:59:16'),
(105, 'Lab 526', 9, 'Available', '2025-04-25 04:22:09'),
(106, 'Lab 526', 10, 'Available', '2025-04-25 04:22:09'),
(107, 'Lab 526', 11, 'Available', '2025-04-25 04:22:09'),
(108, 'Lab 526', 12, 'Available', '2025-04-25 04:22:09'),
(109, 'Lab 526', 13, 'Available', '2025-04-25 04:22:09'),
(110, 'Lab 526', 14, 'Available', '2025-04-25 04:22:09'),
(111, 'Lab 526', 15, 'Available', '2025-04-25 04:22:09'),
(112, 'Lab 526', 16, 'Available', '2025-04-25 04:22:09'),
(113, 'Lab 526', 17, 'Available', '2025-04-25 04:22:09'),
(114, 'Lab 526', 18, 'Available', '2025-04-25 04:22:09'),
(115, 'Lab 526', 19, 'Available', '2025-04-25 04:22:09'),
(116, 'Lab 526', 20, 'Available', '2025-04-25 04:22:09'),
(117, 'Lab 526', 21, 'Available', '2025-04-25 04:22:09'),
(118, 'Lab 526', 22, 'Available', '2025-04-25 04:22:09'),
(119, 'Lab 526', 23, 'Available', '2025-04-25 04:22:09'),
(120, 'Lab 526', 24, 'Available', '2025-04-25 04:22:09'),
(121, 'Lab 526', 25, 'Available', '2025-04-25 04:22:09'),
(122, 'Lab 526', 26, 'Available', '2025-04-25 04:22:09'),
(123, 'Lab 526', 27, 'Available', '2025-04-25 04:22:09'),
(124, 'Lab 526', 28, 'Available', '2025-04-25 04:22:09'),
(125, 'Lab 526', 29, 'Available', '2025-04-25 04:22:09'),
(126, 'Lab 526', 30, 'Available', '2025-04-25 04:22:09'),
(127, 'Lab 526', 31, 'Available', '2025-04-25 04:22:09'),
(128, 'Lab 526', 32, 'Available', '2025-04-25 04:22:09'),
(129, 'Lab 526', 33, 'Available', '2025-04-25 04:22:09'),
(130, 'Lab 526', 34, 'Available', '2025-04-25 04:22:09'),
(131, 'Lab 526', 35, 'Available', '2025-04-25 04:22:09'),
(132, 'Lab 526', 36, 'Available', '2025-04-25 04:22:09'),
(133, 'Lab 526', 37, 'Available', '2025-04-25 04:22:09'),
(134, 'Lab 526', 38, 'Available', '2025-04-25 04:22:09'),
(135, 'Lab 526', 39, 'Available', '2025-04-25 04:22:09'),
(136, 'Lab 526', 40, 'Available', '2025-04-25 04:22:09'),
(137, 'Lab 526', 41, 'Available', '2025-04-25 04:22:09'),
(138, 'Lab 526', 42, 'Available', '2025-04-25 04:22:09'),
(139, 'Lab 526', 43, 'Available', '2025-04-25 04:22:09'),
(140, 'Lab 526', 44, 'Available', '2025-04-25 04:22:09'),
(141, 'Lab 526', 45, 'Available', '2025-04-25 04:22:09'),
(142, 'Lab 526', 46, 'Available', '2025-04-25 04:22:09'),
(143, 'Lab 526', 47, 'Available', '2025-04-25 04:22:09'),
(144, 'Lab 526', 48, 'Available', '2025-04-25 04:22:09'),
(145, 'Lab 528', 1, 'Used', '2025-04-28 07:32:50'),
(146, 'Lab 528', 2, 'Used', '2025-04-28 07:32:50'),
(147, 'Lab 528', 3, 'Used', '2025-04-28 07:32:50'),
(148, 'Lab 528', 4, 'Used', '2025-04-28 07:32:50'),
(149, 'Lab 528', 5, 'Used', '2025-04-28 07:32:50'),
(150, 'Lab 528', 6, 'Used', '2025-04-28 07:32:50'),
(151, 'Lab 528', 7, 'Used', '2025-04-28 07:32:50'),
(152, 'Lab 528', 8, 'Used', '2025-04-28 07:32:50'),
(153, 'Lab 528', 9, 'Used', '2025-04-28 07:32:50'),
(154, 'Lab 528', 10, 'Used', '2025-04-28 07:32:50'),
(155, 'Lab 528', 11, 'Used', '2025-04-28 07:32:50'),
(156, 'Lab 528', 12, 'Used', '2025-04-28 07:32:50'),
(157, 'Lab 528', 13, 'Used', '2025-04-28 07:32:50'),
(158, 'Lab 528', 14, 'Used', '2025-04-28 07:32:50'),
(159, 'Lab 528', 15, 'Used', '2025-04-28 07:32:50'),
(160, 'Lab 528', 16, 'Used', '2025-04-28 07:32:50'),
(161, 'Lab 528', 17, 'Used', '2025-04-28 07:32:50'),
(162, 'Lab 528', 18, 'Used', '2025-04-28 07:32:50'),
(163, 'Lab 528', 19, 'Used', '2025-04-28 07:32:50'),
(164, 'Lab 528', 20, 'Used', '2025-04-28 07:32:50'),
(165, 'Lab 528', 21, 'Used', '2025-04-28 07:32:50'),
(166, 'Lab 528', 22, 'Used', '2025-04-28 07:32:50'),
(167, 'Lab 528', 23, 'Used', '2025-04-28 07:32:50'),
(168, 'Lab 528', 24, 'Used', '2025-04-28 07:32:50'),
(169, 'Lab 528', 25, 'Used', '2025-04-28 07:32:50'),
(170, 'Lab 528', 26, 'Used', '2025-04-28 07:32:50'),
(171, 'Lab 528', 27, 'Used', '2025-04-28 07:32:50'),
(172, 'Lab 528', 28, 'Used', '2025-04-28 07:32:50'),
(173, 'Lab 528', 29, 'Used', '2025-04-28 07:32:50'),
(174, 'Lab 528', 30, 'Used', '2025-04-28 07:32:50'),
(175, 'Lab 528', 31, 'Used', '2025-04-28 07:32:50'),
(176, 'Lab 528', 32, 'Used', '2025-04-28 07:32:50'),
(177, 'Lab 528', 33, 'Used', '2025-04-28 07:32:50'),
(178, 'Lab 528', 34, 'Used', '2025-04-28 07:32:50'),
(179, 'Lab 528', 35, 'Used', '2025-04-28 07:32:50'),
(180, 'Lab 528', 36, 'Used', '2025-04-28 07:32:50'),
(181, 'Lab 528', 37, 'Used', '2025-04-28 07:32:50'),
(182, 'Lab 528', 38, 'Used', '2025-04-28 07:32:50'),
(183, 'Lab 528', 39, 'Used', '2025-04-28 07:32:50'),
(184, 'Lab 528', 40, 'Used', '2025-04-28 07:32:50'),
(185, 'Lab 528', 41, 'Used', '2025-04-28 07:32:50'),
(186, 'Lab 528', 42, 'Used', '2025-04-28 07:32:50'),
(187, 'Lab 528', 43, 'Used', '2025-04-28 07:32:50'),
(188, 'Lab 528', 44, 'Used', '2025-04-28 07:32:50'),
(189, 'Lab 528', 45, 'Used', '2025-04-28 07:32:50'),
(190, 'Lab 528', 46, 'Used', '2025-04-28 07:32:50'),
(191, 'Lab 528', 47, 'Used', '2025-04-28 07:32:50'),
(192, 'Lab 528', 48, 'Used', '2025-04-28 07:32:50'),
(193, 'Lab 530', 1, 'Used', '2025-04-27 12:53:03'),
(194, 'Lab 530', 2, 'Used', '2025-04-27 20:37:27'),
(195, 'Lab 530', 3, 'Available', '2025-05-11 06:59:43'),
(196, 'Lab 530', 4, 'Available', '2025-04-25 04:22:09'),
(197, 'Lab 530', 5, 'Available', '2025-04-25 04:22:09'),
(198, 'Lab 530', 6, 'Available', '2025-05-02 20:35:53'),
(199, 'Lab 530', 7, 'Available', '2025-05-02 20:33:12'),
(200, 'Lab 530', 8, 'Available', '2025-04-25 04:22:09'),
(201, 'Lab 530', 9, 'Available', '2025-04-25 04:22:09'),
(202, 'Lab 530', 10, 'Available', '2025-04-25 04:22:09'),
(203, 'Lab 530', 11, 'Available', '2025-04-25 04:22:09'),
(204, 'Lab 530', 12, 'Available', '2025-04-25 04:22:09'),
(205, 'Lab 530', 13, 'Available', '2025-04-25 04:22:09'),
(206, 'Lab 530', 14, 'Available', '2025-04-25 04:22:09'),
(207, 'Lab 530', 15, 'Available', '2025-04-25 04:22:10'),
(208, 'Lab 530', 16, 'Available', '2025-04-25 04:22:10'),
(209, 'Lab 530', 17, 'Available', '2025-04-25 04:22:10'),
(210, 'Lab 530', 18, 'Available', '2025-04-25 04:22:10'),
(211, 'Lab 530', 19, 'Available', '2025-04-25 04:22:10'),
(212, 'Lab 530', 20, 'Available', '2025-04-25 04:22:10'),
(213, 'Lab 530', 21, 'Available', '2025-04-25 04:22:10'),
(214, 'Lab 530', 22, 'Available', '2025-04-25 04:22:10'),
(215, 'Lab 530', 23, 'Available', '2025-04-25 04:22:10'),
(216, 'Lab 530', 24, 'Available', '2025-04-25 04:22:10'),
(217, 'Lab 530', 25, 'Available', '2025-04-25 04:22:10'),
(218, 'Lab 530', 26, 'Available', '2025-04-25 04:22:10'),
(219, 'Lab 530', 27, 'Available', '2025-04-25 04:22:10'),
(220, 'Lab 530', 28, 'Available', '2025-04-25 04:22:10'),
(221, 'Lab 530', 29, 'Available', '2025-04-25 04:22:10'),
(222, 'Lab 530', 30, 'Available', '2025-04-25 04:22:10'),
(223, 'Lab 530', 31, 'Available', '2025-04-25 04:22:10'),
(224, 'Lab 530', 32, 'Available', '2025-04-25 04:22:10'),
(225, 'Lab 530', 33, 'Available', '2025-04-25 04:22:10'),
(226, 'Lab 530', 34, 'Available', '2025-04-25 04:22:10'),
(227, 'Lab 530', 35, 'Available', '2025-04-25 04:22:10'),
(228, 'Lab 530', 36, 'Available', '2025-04-25 04:22:10'),
(229, 'Lab 530', 37, 'Available', '2025-04-25 04:22:10'),
(230, 'Lab 530', 38, 'Available', '2025-04-25 04:22:10'),
(231, 'Lab 530', 39, 'Available', '2025-04-25 04:22:10'),
(232, 'Lab 530', 40, 'Available', '2025-04-25 04:22:10'),
(233, 'Lab 530', 41, 'Available', '2025-04-25 04:22:10'),
(234, 'Lab 530', 42, 'Available', '2025-04-25 04:22:10'),
(235, 'Lab 530', 43, 'Available', '2025-04-25 04:22:10'),
(236, 'Lab 530', 44, 'Available', '2025-04-25 04:22:10'),
(237, 'Lab 530', 45, 'Available', '2025-04-25 04:22:10'),
(238, 'Lab 530', 46, 'Available', '2025-04-25 04:22:10'),
(239, 'Lab 530', 47, 'Available', '2025-04-25 04:22:10'),
(240, 'Lab 530', 48, 'Available', '2025-04-25 04:22:10'),
(241, 'Lab 542', 1, 'Available', '2025-05-02 20:31:12'),
(242, 'Lab 542', 2, 'Available', '2025-04-25 04:22:10'),
(243, 'Lab 542', 3, 'Available', '2025-04-25 04:22:10'),
(244, 'Lab 542', 4, 'Available', '2025-04-25 04:22:10'),
(245, 'Lab 542', 5, 'Available', '2025-04-25 04:22:10'),
(246, 'Lab 542', 6, 'Available', '2025-04-25 04:22:10'),
(247, 'Lab 542', 7, 'Available', '2025-05-13 08:38:26'),
(248, 'Lab 542', 8, 'Available', '2025-05-13 09:05:32'),
(249, 'Lab 542', 9, 'Available', '2025-04-25 04:22:10'),
(250, 'Lab 542', 10, 'Available', '2025-04-25 04:22:10'),
(251, 'Lab 542', 11, 'Available', '2025-04-25 04:22:10'),
(252, 'Lab 542', 12, 'Available', '2025-04-25 04:22:10'),
(253, 'Lab 542', 13, 'Available', '2025-04-25 04:22:10'),
(254, 'Lab 542', 14, 'Available', '2025-04-25 04:22:10'),
(255, 'Lab 542', 15, 'Available', '2025-04-25 04:22:10'),
(256, 'Lab 542', 16, 'Available', '2025-04-25 04:22:10'),
(257, 'Lab 542', 17, 'Available', '2025-04-25 04:22:10'),
(258, 'Lab 542', 18, 'Available', '2025-04-25 04:22:10'),
(259, 'Lab 542', 19, 'Available', '2025-04-25 04:22:10'),
(260, 'Lab 542', 20, 'Available', '2025-04-25 04:22:10'),
(261, 'Lab 542', 21, 'Available', '2025-04-25 04:22:10'),
(262, 'Lab 542', 22, 'Available', '2025-04-25 04:22:10'),
(263, 'Lab 542', 23, 'Available', '2025-04-25 04:22:10'),
(264, 'Lab 542', 24, 'Available', '2025-04-25 04:22:10'),
(265, 'Lab 542', 25, 'Available', '2025-04-25 04:22:10'),
(266, 'Lab 542', 26, 'Available', '2025-04-25 04:22:10'),
(267, 'Lab 542', 27, 'Available', '2025-04-25 04:22:10'),
(268, 'Lab 542', 28, 'Available', '2025-04-25 04:22:10'),
(269, 'Lab 542', 29, 'Available', '2025-04-25 04:22:10'),
(270, 'Lab 542', 30, 'Available', '2025-04-25 04:22:10'),
(271, 'Lab 542', 31, 'Available', '2025-04-25 04:22:10'),
(272, 'Lab 542', 32, 'Available', '2025-04-25 04:22:10'),
(273, 'Lab 542', 33, 'Available', '2025-04-25 04:22:10'),
(274, 'Lab 542', 34, 'Available', '2025-04-25 04:22:10'),
(275, 'Lab 542', 35, 'Available', '2025-04-25 04:22:10'),
(276, 'Lab 542', 36, 'Available', '2025-04-25 04:22:10'),
(277, 'Lab 542', 37, 'Available', '2025-04-25 04:22:10'),
(278, 'Lab 542', 38, 'Available', '2025-04-25 04:22:10'),
(279, 'Lab 542', 39, 'Available', '2025-04-25 04:22:10'),
(280, 'Lab 542', 40, 'Available', '2025-04-25 04:22:10'),
(281, 'Lab 542', 41, 'Available', '2025-04-25 04:22:10'),
(282, 'Lab 542', 42, 'Available', '2025-04-25 04:22:10'),
(283, 'Lab 542', 43, 'Available', '2025-04-25 04:22:10'),
(284, 'Lab 542', 44, 'Available', '2025-04-25 04:22:10'),
(285, 'Lab 542', 45, 'Available', '2025-04-25 04:22:10'),
(286, 'Lab 542', 46, 'Available', '2025-04-25 04:22:10'),
(287, 'Lab 542', 47, 'Available', '2025-04-25 04:22:10'),
(288, 'Lab 542', 48, 'Available', '2025-04-25 04:22:10'),
(289, 'Lab 544', 1, 'Maintenance', '2025-05-13 07:32:03'),
(290, 'Lab 544', 2, 'Maintenance', '2025-05-13 07:32:03'),
(291, 'Lab 544', 3, 'Maintenance', '2025-05-13 07:32:03'),
(292, 'Lab 544', 4, 'Maintenance', '2025-05-13 07:32:03'),
(293, 'Lab 544', 5, 'Maintenance', '2025-05-13 07:32:03'),
(294, 'Lab 544', 6, 'Maintenance', '2025-05-13 07:32:03'),
(295, 'Lab 544', 7, 'Maintenance', '2025-05-13 07:32:03'),
(296, 'Lab 544', 8, 'Maintenance', '2025-05-13 07:32:03'),
(297, 'Lab 544', 9, 'Maintenance', '2025-05-13 07:32:03'),
(298, 'Lab 544', 10, 'Maintenance', '2025-05-13 07:32:03'),
(299, 'Lab 544', 11, 'Maintenance', '2025-05-13 07:32:03'),
(300, 'Lab 544', 12, 'Maintenance', '2025-05-13 07:32:03'),
(301, 'Lab 544', 13, 'Maintenance', '2025-05-13 07:32:03'),
(302, 'Lab 544', 14, 'Maintenance', '2025-05-13 07:32:03'),
(303, 'Lab 544', 15, 'Maintenance', '2025-05-13 07:32:03'),
(304, 'Lab 544', 16, 'Maintenance', '2025-05-13 07:32:03'),
(305, 'Lab 544', 17, 'Maintenance', '2025-05-13 07:32:03'),
(306, 'Lab 544', 18, 'Maintenance', '2025-05-13 07:32:03'),
(307, 'Lab 544', 19, 'Maintenance', '2025-05-13 07:32:03'),
(308, 'Lab 544', 20, 'Maintenance', '2025-05-13 07:32:03'),
(309, 'Lab 544', 21, 'Maintenance', '2025-05-13 07:32:03'),
(310, 'Lab 544', 22, 'Maintenance', '2025-05-13 07:32:03'),
(311, 'Lab 544', 23, 'Maintenance', '2025-05-13 07:32:03'),
(312, 'Lab 544', 24, 'Maintenance', '2025-05-13 07:32:03'),
(313, 'Lab 544', 25, 'Maintenance', '2025-05-13 07:32:03'),
(314, 'Lab 544', 26, 'Maintenance', '2025-05-13 07:32:03'),
(315, 'Lab 544', 27, 'Maintenance', '2025-05-13 07:32:03'),
(316, 'Lab 544', 28, 'Maintenance', '2025-05-13 07:32:03'),
(317, 'Lab 544', 29, 'Maintenance', '2025-05-13 07:32:03'),
(318, 'Lab 544', 30, 'Maintenance', '2025-05-13 07:32:03'),
(319, 'Lab 544', 31, 'Maintenance', '2025-05-13 07:32:03'),
(320, 'Lab 544', 32, 'Maintenance', '2025-05-13 07:32:03'),
(321, 'Lab 544', 33, 'Maintenance', '2025-05-13 07:32:03'),
(322, 'Lab 544', 34, 'Maintenance', '2025-05-13 07:32:03'),
(323, 'Lab 544', 35, 'Maintenance', '2025-05-13 07:32:03'),
(324, 'Lab 544', 36, 'Maintenance', '2025-05-13 07:32:03'),
(325, 'Lab 544', 37, 'Maintenance', '2025-05-13 07:32:03'),
(326, 'Lab 544', 38, 'Maintenance', '2025-05-13 07:32:03'),
(327, 'Lab 544', 39, 'Maintenance', '2025-05-13 07:32:03'),
(328, 'Lab 544', 40, 'Maintenance', '2025-05-13 07:32:03'),
(329, 'Lab 544', 41, 'Maintenance', '2025-05-13 07:32:03'),
(330, 'Lab 544', 42, 'Maintenance', '2025-05-13 07:32:03'),
(331, 'Lab 544', 43, 'Maintenance', '2025-05-13 07:32:03'),
(332, 'Lab 544', 44, 'Maintenance', '2025-05-13 07:32:03'),
(333, 'Lab 544', 45, 'Maintenance', '2025-05-13 07:32:03'),
(334, 'Lab 544', 46, 'Maintenance', '2025-05-13 07:32:03'),
(335, 'Lab 544', 47, 'Maintenance', '2025-05-13 07:32:03'),
(336, 'Lab 544', 48, 'Maintenance', '2025-05-13 07:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(132, '111', 'You logged out without earning points this session. Remaining sessions: 29', 0, '2025-05-09 02:33:24'),
(133, '3333', 'You logged out without earning points this session. Remaining sessions: 29', 0, '2025-05-11 06:42:39'),
(134, '111', 'You logged out without earning points this session. Remaining sessions: 28', 0, '2025-05-11 06:59:10'),
(135, '111', 'You logged out without earning points this session. Remaining sessions: 27', 0, '2025-05-11 06:59:43'),
(136, '111', 'You logged out without earning points this session. Remaining sessions: 26', 0, '2025-05-11 07:19:59'),
(137, '3333', 'You gained 1 point for your sit-in session (Total: 1 point)', 0, '2025-05-13 08:28:12'),
(138, '111', 'You gained 1 point for your sit-in session (Total: 1 point)', 0, '2025-05-13 08:28:34'),
(139, '3333', 'You logged out without earning points this session. Remaining sessions: 27', 0, '2025-05-13 08:38:26'),
(140, '111', 'You logged out without earning points this session. Remaining sessions: 24', 0, '2025-05-13 09:05:31'),
(141, '3333', 'You logged out without earning points this session. Remaining sessions: 26', 0, '2025-05-13 09:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab_room` varchar(50) NOT NULL,
  `pc_number` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `available_to` enum('all','students','admins') NOT NULL DEFAULT 'all',
  `uploaded_by` varchar(50) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `description`, `file_name`, `file_path`, `file_size`, `file_type`, `available_to`, `uploaded_by`, `upload_date`) VALUES
(3, 'text', 'file', 'pass.txt', 'resources/67f9f6419fcca_pass.txt', 15, 'txt', 'all', 'ADMIN001', '2025-04-12 05:12:33'),
(13, '1', '1', 'bbe5a673-f7d4-4961-977f-cc20c1c85246.jpg', 'resources/681d932b70676_bbe5a673-f7d4-4961-977f-cc20c1c85246.jpg', 10693, 'jpg', 'all', 'ADMIN001', '2025-05-09 05:31:23');

-- --------------------------------------------------------

--
-- Table structure for table `rewards_log`
--

CREATE TABLE `rewards_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'References users.id',
  `points_earned` int(11) NOT NULL,
  `action` enum('sit_in_completion','admin_add','admin_remove') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards_log`
--

INSERT INTO `rewards_log` (`id`, `user_id`, `points_earned`, `action`, `created_at`) VALUES
(46, 14, 1, 'sit_in_completion', '2025-05-13 08:28:12'),
(47, 13, 1, 'sit_in_completion', '2025-05-13 08:28:34');

-- --------------------------------------------------------

--
-- Table structure for table `satisfaction_surveys`
--

CREATE TABLE `satisfaction_surveys` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `satisfaction` tinyint(4) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_records`
--

CREATE TABLE `sit_in_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `pc_number` int(11) DEFAULT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_in_records`
--

INSERT INTO `sit_in_records` (`id`, `student_id`, `purpose`, `lab`, `pc_number`, `start_time`, `end_time`, `feedback`) VALUES
(115, 14, 'Php Programming', 'Lab 517', 7, '2025-05-11 14:42:34', '2025-05-11 14:42:39', 'fuck'),
(116, 13, 'Web Design', 'Lab 517', 4, '2025-05-11 14:59:08', '2025-05-11 14:59:10', 'wtf'),
(117, 13, 'Php Programming', 'Lab 530', 3, '2025-05-11 14:59:41', '2025-05-11 14:59:43', 'wow nice'),
(119, 13, 'Php Programming', 'Lab 517', 3, '2025-05-13 14:55:03', '2025-05-13 16:28:34', NULL),
(120, 14, 'Mobile Appilication', 'Lab 517', 3, '2025-05-13 16:28:03', '2025-05-13 16:28:12', NULL),
(121, 14, 'Web Design', 'Lab 542', 7, '2025-05-13 16:37:39', '2025-05-13 16:38:26', NULL),
(122, 14, 'Mobile Appilication', 'Lab 542', 8, '2025-05-13 16:41:11', '2025-05-13 17:05:32', NULL),
(123, 13, 'Other', 'Lab 517', 1, '2025-05-13 17:05:11', '2025-05-13 17:05:31', NULL),
(124, 13, 'Python Programming', 'Lab 517', 3, '2025-05-13 17:06:09', NULL, NULL),
(125, 14, 'Php Programming', 'Lab 517', 7, '2025-05-13 17:26:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `static_lab_schedules`
--

CREATE TABLE `static_lab_schedules` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `day_group` varchar(10) NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `status` enum('open','close') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `static_lab_schedules`
--

INSERT INTO `static_lab_schedules` (`id`, `lab_name`, `day_group`, `time_slot`, `status`) VALUES
(1, 'Lab 517', 'MW', '7:30 AM - 9:00 AM', ''),
(2, 'Lab 524', 'MW', '7:30 AM - 9:00 AM', ''),
(3, 'Lab 526', 'MW', '7:30 AM - 9:00 AM', ''),
(4, 'Lab 528', 'MW', '7:30 AM - 9:00 AM', ''),
(5, 'Lab 530', 'MW', '7:30 AM - 9:00 AM', ''),
(6, 'Lab 542', 'MW', '7:30 AM - 9:00 AM', ''),
(7, 'Lab 544', 'MW', '7:30 AM - 9:00 AM', ''),
(8, 'Lab 517', 'MW', '9:00 AM - 10:30 AM', ''),
(9, 'Lab 524', 'MW', '9:00 AM - 10:30 AM', ''),
(10, 'Lab 526', 'MW', '9:00 AM - 10:30 AM', ''),
(11, 'Lab 528', 'MW', '9:00 AM - 10:30 AM', ''),
(12, 'Lab 530', 'MW', '9:00 AM - 10:30 AM', ''),
(13, 'Lab 542', 'MW', '9:00 AM - 10:30 AM', ''),
(14, 'Lab 544', 'MW', '9:00 AM - 10:30 AM', ''),
(15, 'Lab 517', 'MW', '10:30 AM - 12:00 PM', 'close'),
(16, 'Lab 524', 'MW', '10:30 AM - 12:00 PM', ''),
(17, 'Lab 526', 'MW', '10:30 AM - 12:00 PM', ''),
(18, 'Lab 528', 'MW', '10:30 AM - 12:00 PM', ''),
(19, 'Lab 530', 'MW', '10:30 AM - 12:00 PM', ''),
(20, 'Lab 542', 'MW', '10:30 AM - 12:00 PM', ''),
(21, 'Lab 544', 'MW', '10:30 AM - 12:00 PM', ''),
(22, 'Lab 517', 'MW', '12:00 PM - 1:30 PM', ''),
(23, 'Lab 524', 'MW', '12:00 PM - 1:30 PM', ''),
(24, 'Lab 526', 'MW', '12:00 PM - 1:30 PM', ''),
(25, 'Lab 528', 'MW', '12:00 PM - 1:30 PM', ''),
(26, 'Lab 530', 'MW', '12:00 PM - 1:30 PM', ''),
(27, 'Lab 542', 'MW', '12:00 PM - 1:30 PM', ''),
(28, 'Lab 544', 'MW', '12:00 PM - 1:30 PM', ''),
(29, 'Lab 517', 'MW', '1:30 PM - 3:00 PM', ''),
(30, 'Lab 524', 'MW', '1:30 PM - 3:00 PM', ''),
(31, 'Lab 526', 'MW', '1:30 PM - 3:00 PM', ''),
(32, 'Lab 528', 'MW', '1:30 PM - 3:00 PM', ''),
(33, 'Lab 530', 'MW', '1:30 PM - 3:00 PM', ''),
(34, 'Lab 542', 'MW', '1:30 PM - 3:00 PM', ''),
(35, 'Lab 544', 'MW', '1:30 PM - 3:00 PM', ''),
(36, 'Lab 517', 'MW', '3:00 PM - 4:30 PM', ''),
(37, 'Lab 524', 'MW', '3:00 PM - 4:30 PM', ''),
(38, 'Lab 526', 'MW', '3:00 PM - 4:30 PM', ''),
(39, 'Lab 528', 'MW', '3:00 PM - 4:30 PM', ''),
(40, 'Lab 530', 'MW', '3:00 PM - 4:30 PM', ''),
(41, 'Lab 542', 'MW', '3:00 PM - 4:30 PM', ''),
(42, 'Lab 544', 'MW', '3:00 PM - 4:30 PM', ''),
(43, 'Lab 517', 'MW', '4:30 PM - 6:00 PM', ''),
(44, 'Lab 524', 'MW', '4:30 PM - 6:00 PM', ''),
(45, 'Lab 526', 'MW', '4:30 PM - 6:00 PM', ''),
(46, 'Lab 528', 'MW', '4:30 PM - 6:00 PM', ''),
(47, 'Lab 530', 'MW', '4:30 PM - 6:00 PM', ''),
(48, 'Lab 542', 'MW', '4:30 PM - 6:00 PM', ''),
(49, 'Lab 544', 'MW', '4:30 PM - 6:00 PM', ''),
(50, 'Lab 517', 'MW', '6:00 PM - 7:30 PM', ''),
(51, 'Lab 524', 'MW', '6:00 PM - 7:30 PM', ''),
(52, 'Lab 526', 'MW', '6:00 PM - 7:30 PM', ''),
(53, 'Lab 528', 'MW', '6:00 PM - 7:30 PM', ''),
(54, 'Lab 530', 'MW', '6:00 PM - 7:30 PM', ''),
(55, 'Lab 542', 'MW', '6:00 PM - 7:30 PM', ''),
(56, 'Lab 544', 'MW', '6:00 PM - 7:30 PM', ''),
(57, 'Lab 517', 'MW', '7:30 PM - 9:00 PM', ''),
(58, 'Lab 524', 'MW', '7:30 PM - 9:00 PM', ''),
(59, 'Lab 526', 'MW', '7:30 PM - 9:00 PM', ''),
(60, 'Lab 528', 'MW', '7:30 PM - 9:00 PM', ''),
(61, 'Lab 530', 'MW', '7:30 PM - 9:00 PM', ''),
(62, 'Lab 542', 'MW', '7:30 PM - 9:00 PM', ''),
(63, 'Lab 544', 'MW', '7:30 PM - 9:00 PM', ''),
(64, 'Lab 517', 'TTh', '7:30 AM - 9:00 AM', ''),
(65, 'Lab 524', 'TTh', '7:30 AM - 9:00 AM', ''),
(66, 'Lab 526', 'TTh', '7:30 AM - 9:00 AM', ''),
(67, 'Lab 528', 'TTh', '7:30 AM - 9:00 AM', ''),
(68, 'Lab 530', 'TTh', '7:30 AM - 9:00 AM', ''),
(69, 'Lab 542', 'TTh', '7:30 AM - 9:00 AM', ''),
(70, 'Lab 544', 'TTh', '7:30 AM - 9:00 AM', ''),
(71, 'Lab 517', 'TTh', '9:00 AM - 10:30 AM', ''),
(72, 'Lab 524', 'TTh', '9:00 AM - 10:30 AM', ''),
(73, 'Lab 526', 'TTh', '9:00 AM - 10:30 AM', ''),
(74, 'Lab 528', 'TTh', '9:00 AM - 10:30 AM', ''),
(75, 'Lab 530', 'TTh', '9:00 AM - 10:30 AM', ''),
(76, 'Lab 542', 'TTh', '9:00 AM - 10:30 AM', ''),
(77, 'Lab 544', 'TTh', '9:00 AM - 10:30 AM', ''),
(78, 'Lab 517', 'TTh', '10:30 AM - 12:00 PM', ''),
(79, 'Lab 524', 'TTh', '10:30 AM - 12:00 PM', ''),
(80, 'Lab 526', 'TTh', '10:30 AM - 12:00 PM', ''),
(81, 'Lab 528', 'TTh', '10:30 AM - 12:00 PM', ''),
(82, 'Lab 530', 'TTh', '10:30 AM - 12:00 PM', ''),
(83, 'Lab 542', 'TTh', '10:30 AM - 12:00 PM', ''),
(84, 'Lab 544', 'TTh', '10:30 AM - 12:00 PM', ''),
(85, 'Lab 517', 'TTh', '12:00 PM - 1:30 PM', ''),
(86, 'Lab 524', 'TTh', '12:00 PM - 1:30 PM', ''),
(87, 'Lab 526', 'TTh', '12:00 PM - 1:30 PM', ''),
(88, 'Lab 528', 'TTh', '12:00 PM - 1:30 PM', ''),
(89, 'Lab 530', 'TTh', '12:00 PM - 1:30 PM', ''),
(90, 'Lab 542', 'TTh', '12:00 PM - 1:30 PM', ''),
(91, 'Lab 544', 'TTh', '12:00 PM - 1:30 PM', ''),
(92, 'Lab 517', 'TTh', '1:30 PM - 3:00 PM', ''),
(93, 'Lab 524', 'TTh', '1:30 PM - 3:00 PM', ''),
(94, 'Lab 526', 'TTh', '1:30 PM - 3:00 PM', ''),
(95, 'Lab 528', 'TTh', '1:30 PM - 3:00 PM', ''),
(96, 'Lab 530', 'TTh', '1:30 PM - 3:00 PM', ''),
(97, 'Lab 542', 'TTh', '1:30 PM - 3:00 PM', ''),
(98, 'Lab 544', 'TTh', '1:30 PM - 3:00 PM', ''),
(99, 'Lab 517', 'TTh', '3:00 PM - 4:30 PM', ''),
(100, 'Lab 524', 'TTh', '3:00 PM - 4:30 PM', ''),
(101, 'Lab 526', 'TTh', '3:00 PM - 4:30 PM', ''),
(102, 'Lab 528', 'TTh', '3:00 PM - 4:30 PM', ''),
(103, 'Lab 530', 'TTh', '3:00 PM - 4:30 PM', ''),
(104, 'Lab 542', 'TTh', '3:00 PM - 4:30 PM', ''),
(105, 'Lab 544', 'TTh', '3:00 PM - 4:30 PM', ''),
(106, 'Lab 517', 'TTh', '4:30 PM - 6:00 PM', ''),
(107, 'Lab 524', 'TTh', '4:30 PM - 6:00 PM', ''),
(108, 'Lab 526', 'TTh', '4:30 PM - 6:00 PM', ''),
(109, 'Lab 528', 'TTh', '4:30 PM - 6:00 PM', ''),
(110, 'Lab 530', 'TTh', '4:30 PM - 6:00 PM', ''),
(111, 'Lab 542', 'TTh', '4:30 PM - 6:00 PM', ''),
(112, 'Lab 544', 'TTh', '4:30 PM - 6:00 PM', ''),
(113, 'Lab 517', 'TTh', '6:00 PM - 7:30 PM', ''),
(114, 'Lab 524', 'TTh', '6:00 PM - 7:30 PM', ''),
(115, 'Lab 526', 'TTh', '6:00 PM - 7:30 PM', ''),
(116, 'Lab 528', 'TTh', '6:00 PM - 7:30 PM', ''),
(117, 'Lab 530', 'TTh', '6:00 PM - 7:30 PM', ''),
(118, 'Lab 542', 'TTh', '6:00 PM - 7:30 PM', ''),
(119, 'Lab 544', 'TTh', '6:00 PM - 7:30 PM', ''),
(120, 'Lab 517', 'TTh', '7:30 PM - 9:00 PM', ''),
(121, 'Lab 524', 'TTh', '7:30 PM - 9:00 PM', ''),
(122, 'Lab 526', 'TTh', '7:30 PM - 9:00 PM', ''),
(123, 'Lab 528', 'TTh', '7:30 PM - 9:00 PM', ''),
(124, 'Lab 530', 'TTh', '7:30 PM - 9:00 PM', ''),
(125, 'Lab 542', 'TTh', '7:30 PM - 9:00 PM', ''),
(126, 'Lab 544', 'TTh', '7:30 PM - 9:00 PM', ''),
(127, 'Lab 517', 'Fri', '7:30 AM - 9:00 AM', ''),
(128, 'Lab 524', 'Fri', '7:30 AM - 9:00 AM', ''),
(129, 'Lab 526', 'Fri', '7:30 AM - 9:00 AM', ''),
(130, 'Lab 528', 'Fri', '7:30 AM - 9:00 AM', ''),
(131, 'Lab 530', 'Fri', '7:30 AM - 9:00 AM', ''),
(132, 'Lab 542', 'Fri', '7:30 AM - 9:00 AM', ''),
(133, 'Lab 544', 'Fri', '7:30 AM - 9:00 AM', ''),
(134, 'Lab 517', 'Fri', '9:00 AM - 10:30 AM', ''),
(135, 'Lab 524', 'Fri', '9:00 AM - 10:30 AM', ''),
(136, 'Lab 526', 'Fri', '9:00 AM - 10:30 AM', ''),
(137, 'Lab 528', 'Fri', '9:00 AM - 10:30 AM', ''),
(138, 'Lab 530', 'Fri', '9:00 AM - 10:30 AM', ''),
(139, 'Lab 542', 'Fri', '9:00 AM - 10:30 AM', ''),
(140, 'Lab 544', 'Fri', '9:00 AM - 10:30 AM', ''),
(141, 'Lab 517', 'Fri', '10:30 AM - 12:00 PM', ''),
(142, 'Lab 524', 'Fri', '10:30 AM - 12:00 PM', ''),
(143, 'Lab 526', 'Fri', '10:30 AM - 12:00 PM', ''),
(144, 'Lab 528', 'Fri', '10:30 AM - 12:00 PM', ''),
(145, 'Lab 530', 'Fri', '10:30 AM - 12:00 PM', ''),
(146, 'Lab 542', 'Fri', '10:30 AM - 12:00 PM', ''),
(147, 'Lab 544', 'Fri', '10:30 AM - 12:00 PM', ''),
(148, 'Lab 517', 'Fri', '12:00 PM - 1:30 PM', ''),
(149, 'Lab 524', 'Fri', '12:00 PM - 1:30 PM', ''),
(150, 'Lab 526', 'Fri', '12:00 PM - 1:30 PM', ''),
(151, 'Lab 528', 'Fri', '12:00 PM - 1:30 PM', ''),
(152, 'Lab 530', 'Fri', '12:00 PM - 1:30 PM', ''),
(153, 'Lab 542', 'Fri', '12:00 PM - 1:30 PM', ''),
(154, 'Lab 544', 'Fri', '12:00 PM - 1:30 PM', ''),
(155, 'Lab 517', 'Fri', '1:30 PM - 3:00 PM', ''),
(156, 'Lab 524', 'Fri', '1:30 PM - 3:00 PM', ''),
(157, 'Lab 526', 'Fri', '1:30 PM - 3:00 PM', ''),
(158, 'Lab 528', 'Fri', '1:30 PM - 3:00 PM', ''),
(159, 'Lab 530', 'Fri', '1:30 PM - 3:00 PM', ''),
(160, 'Lab 542', 'Fri', '1:30 PM - 3:00 PM', ''),
(161, 'Lab 544', 'Fri', '1:30 PM - 3:00 PM', ''),
(162, 'Lab 517', 'Fri', '3:00 PM - 4:30 PM', ''),
(163, 'Lab 524', 'Fri', '3:00 PM - 4:30 PM', ''),
(164, 'Lab 526', 'Fri', '3:00 PM - 4:30 PM', ''),
(165, 'Lab 528', 'Fri', '3:00 PM - 4:30 PM', ''),
(166, 'Lab 530', 'Fri', '3:00 PM - 4:30 PM', ''),
(167, 'Lab 542', 'Fri', '3:00 PM - 4:30 PM', ''),
(168, 'Lab 544', 'Fri', '3:00 PM - 4:30 PM', ''),
(169, 'Lab 517', 'Fri', '4:30 PM - 6:00 PM', ''),
(170, 'Lab 524', 'Fri', '4:30 PM - 6:00 PM', ''),
(171, 'Lab 526', 'Fri', '4:30 PM - 6:00 PM', ''),
(172, 'Lab 528', 'Fri', '4:30 PM - 6:00 PM', ''),
(173, 'Lab 530', 'Fri', '4:30 PM - 6:00 PM', ''),
(174, 'Lab 542', 'Fri', '4:30 PM - 6:00 PM', ''),
(175, 'Lab 544', 'Fri', '4:30 PM - 6:00 PM', ''),
(176, 'Lab 517', 'Fri', '6:00 PM - 7:30 PM', ''),
(177, 'Lab 524', 'Fri', '6:00 PM - 7:30 PM', ''),
(178, 'Lab 526', 'Fri', '6:00 PM - 7:30 PM', ''),
(179, 'Lab 528', 'Fri', '6:00 PM - 7:30 PM', ''),
(180, 'Lab 530', 'Fri', '6:00 PM - 7:30 PM', ''),
(181, 'Lab 542', 'Fri', '6:00 PM - 7:30 PM', ''),
(182, 'Lab 544', 'Fri', '6:00 PM - 7:30 PM', ''),
(183, 'Lab 517', 'Fri', '7:30 PM - 9:00 PM', ''),
(184, 'Lab 524', 'Fri', '7:30 PM - 9:00 PM', ''),
(185, 'Lab 526', 'Fri', '7:30 PM - 9:00 PM', ''),
(186, 'Lab 528', 'Fri', '7:30 PM - 9:00 PM', ''),
(187, 'Lab 530', 'Fri', '7:30 PM - 9:00 PM', ''),
(188, 'Lab 542', 'Fri', '7:30 PM - 9:00 PM', ''),
(189, 'Lab 544', 'Fri', '7:30 PM - 9:00 PM', ''),
(190, 'Lab 517', 'Sat', '7:30 AM - 9:00 AM', ''),
(191, 'Lab 524', 'Sat', '7:30 AM - 9:00 AM', ''),
(192, 'Lab 526', 'Sat', '7:30 AM - 9:00 AM', ''),
(193, 'Lab 528', 'Sat', '7:30 AM - 9:00 AM', ''),
(194, 'Lab 530', 'Sat', '7:30 AM - 9:00 AM', ''),
(195, 'Lab 542', 'Sat', '7:30 AM - 9:00 AM', ''),
(196, 'Lab 544', 'Sat', '7:30 AM - 9:00 AM', ''),
(197, 'Lab 517', 'Sat', '9:00 AM - 10:30 AM', ''),
(198, 'Lab 524', 'Sat', '9:00 AM - 10:30 AM', ''),
(199, 'Lab 526', 'Sat', '9:00 AM - 10:30 AM', ''),
(200, 'Lab 528', 'Sat', '9:00 AM - 10:30 AM', ''),
(201, 'Lab 530', 'Sat', '9:00 AM - 10:30 AM', ''),
(202, 'Lab 542', 'Sat', '9:00 AM - 10:30 AM', ''),
(203, 'Lab 544', 'Sat', '9:00 AM - 10:30 AM', ''),
(204, 'Lab 517', 'Sat', '10:30 AM - 12:00 PM', ''),
(205, 'Lab 524', 'Sat', '10:30 AM - 12:00 PM', ''),
(206, 'Lab 526', 'Sat', '10:30 AM - 12:00 PM', ''),
(207, 'Lab 528', 'Sat', '10:30 AM - 12:00 PM', ''),
(208, 'Lab 530', 'Sat', '10:30 AM - 12:00 PM', ''),
(209, 'Lab 542', 'Sat', '10:30 AM - 12:00 PM', ''),
(210, 'Lab 544', 'Sat', '10:30 AM - 12:00 PM', ''),
(211, 'Lab 517', 'Sat', '12:00 PM - 1:30 PM', ''),
(212, 'Lab 524', 'Sat', '12:00 PM - 1:30 PM', ''),
(213, 'Lab 526', 'Sat', '12:00 PM - 1:30 PM', ''),
(214, 'Lab 528', 'Sat', '12:00 PM - 1:30 PM', ''),
(215, 'Lab 530', 'Sat', '12:00 PM - 1:30 PM', ''),
(216, 'Lab 542', 'Sat', '12:00 PM - 1:30 PM', ''),
(217, 'Lab 544', 'Sat', '12:00 PM - 1:30 PM', ''),
(218, 'Lab 517', 'Sat', '1:30 PM - 3:00 PM', ''),
(219, 'Lab 524', 'Sat', '1:30 PM - 3:00 PM', ''),
(220, 'Lab 526', 'Sat', '1:30 PM - 3:00 PM', ''),
(221, 'Lab 528', 'Sat', '1:30 PM - 3:00 PM', ''),
(222, 'Lab 530', 'Sat', '1:30 PM - 3:00 PM', ''),
(223, 'Lab 542', 'Sat', '1:30 PM - 3:00 PM', ''),
(224, 'Lab 544', 'Sat', '1:30 PM - 3:00 PM', ''),
(225, 'Lab 517', 'Sat', '3:00 PM - 4:30 PM', ''),
(226, 'Lab 524', 'Sat', '3:00 PM - 4:30 PM', ''),
(227, 'Lab 526', 'Sat', '3:00 PM - 4:30 PM', ''),
(228, 'Lab 528', 'Sat', '3:00 PM - 4:30 PM', ''),
(229, 'Lab 530', 'Sat', '3:00 PM - 4:30 PM', ''),
(230, 'Lab 542', 'Sat', '3:00 PM - 4:30 PM', ''),
(231, 'Lab 544', 'Sat', '3:00 PM - 4:30 PM', ''),
(232, 'Lab 517', 'Sat', '4:30 PM - 6:00 PM', ''),
(233, 'Lab 524', 'Sat', '4:30 PM - 6:00 PM', ''),
(234, 'Lab 526', 'Sat', '4:30 PM - 6:00 PM', ''),
(235, 'Lab 528', 'Sat', '4:30 PM - 6:00 PM', ''),
(236, 'Lab 530', 'Sat', '4:30 PM - 6:00 PM', ''),
(237, 'Lab 542', 'Sat', '4:30 PM - 6:00 PM', ''),
(238, 'Lab 544', 'Sat', '4:30 PM - 6:00 PM', ''),
(239, 'Lab 517', 'Sat', '6:00 PM - 7:30 PM', ''),
(240, 'Lab 524', 'Sat', '6:00 PM - 7:30 PM', ''),
(241, 'Lab 526', 'Sat', '6:00 PM - 7:30 PM', ''),
(242, 'Lab 528', 'Sat', '6:00 PM - 7:30 PM', ''),
(243, 'Lab 530', 'Sat', '6:00 PM - 7:30 PM', ''),
(244, 'Lab 542', 'Sat', '6:00 PM - 7:30 PM', ''),
(245, 'Lab 544', 'Sat', '6:00 PM - 7:30 PM', ''),
(246, 'Lab 517', 'Sat', '7:30 PM - 9:00 PM', ''),
(247, 'Lab 524', 'Sat', '7:30 PM - 9:00 PM', ''),
(248, 'Lab 526', 'Sat', '7:30 PM - 9:00 PM', ''),
(249, 'Lab 528', 'Sat', '7:30 PM - 9:00 PM', ''),
(250, 'Lab 530', 'Sat', '7:30 PM - 9:00 PM', ''),
(251, 'Lab 542', 'Sat', '7:30 PM - 9:00 PM', ''),
(252, 'Lab 544', 'Sat', '7:30 PM - 9:00 PM', ''),
(253, 'Lab 517', 'MW', '09:00 AM - 10:30 AM', 'close'),
(254, 'Lab 524', 'MW', '12:00 PM - 01:30 PM', ''),
(255, 'Lab 524', 'MW', '01:30 PM - 03:00 PM', ''),
(256, 'Lab 526', 'MW', '07:30 AM - 09:00 AM', ''),
(257, 'Lab 526', 'MW', '09:00 AM - 10:30 AM', ''),
(258, 'Lab 517', 'MW', '06:00 PM - 07:30 PM', 'close'),
(259, 'Lab 517', 'MW', '04:30 PM - 06:00 PM', ''),
(260, 'Lab 528', 'MW', '12:00 PM - 01:30 PM', ''),
(261, 'Lab 526', 'MW', '06:00 PM - 07:30 PM', ''),
(262, 'Lab 526', 'MW', '07:30 PM - 09:00 PM', ''),
(263, 'Lab 528', 'MW', '04:30 PM - 06:00 PM', ''),
(264, 'Lab 530', 'MW', '09:00 AM - 10:30 AM', ''),
(265, 'Lab 530', 'MW', '12:00 PM - 01:30 PM', ''),
(266, 'Lab 542', 'MW', '07:30 AM - 09:00 AM', ''),
(267, 'Lab 542', 'MW', '01:30 PM - 03:00 PM', ''),
(268, 'Lab 542', 'MW', '03:00 PM - 04:30 PM', ''),
(269, 'Lab 542', 'MW', '07:30 PM - 09:00 PM', ''),
(270, 'Lab 544', 'MW', '12:00 PM - 01:30 PM', ''),
(271, 'Lab 544', 'MW', '04:30 PM - 06:00 PM', ''),
(272, 'Lab 517', 'TTh', '07:30 AM - 09:00 AM', ''),
(273, 'Lab 524', 'TTh', '09:00 AM - 10:30 AM', ''),
(274, 'Lab 517', 'TTh', '03:00 PM - 04:30 PM', ''),
(275, 'Lab 524', 'TTh', '04:30 PM - 06:00 PM', ''),
(276, 'Lab 526', 'TTh', '07:30 PM - 09:00 PM', ''),
(277, 'Lab 526', 'TTh', '01:30 PM - 03:00 PM', ''),
(278, 'Lab 526', 'TTh', '06:00 PM - 07:30 PM', ''),
(279, 'Lab 528', 'TTh', '07:30 AM - 09:00 AM', ''),
(280, 'Lab 528', 'TTh', '09:00 AM - 10:30 AM', ''),
(281, 'Lab 528', 'TTh', '03:00 PM - 04:30 PM', ''),
(282, 'Lab 530', 'TTh', '12:00 PM - 01:30 PM', ''),
(283, 'Lab 530', 'TTh', '04:30 PM - 06:00 PM', ''),
(284, 'Lab 542', 'TTh', '09:00 AM - 10:30 AM', ''),
(285, 'Lab 517', 'Fri', '07:30 AM - 09:00 AM', ''),
(286, 'Lab 517', 'Fri', '09:00 AM - 10:30 AM', ''),
(287, 'Lab 517', 'Fri', '01:30 PM - 03:00 PM', ''),
(288, 'Lab 517', 'Fri', '04:30 PM - 06:00 PM', ''),
(289, 'Lab 524', 'Fri', '07:30 AM - 09:00 AM', ''),
(290, 'Lab 524', 'Fri', '09:00 AM - 10:30 AM', ''),
(291, 'Lab 524', 'Fri', '03:00 PM - 04:30 PM', ''),
(292, 'Lab 524', 'Fri', '06:00 PM - 07:30 PM', ''),
(293, 'Lab 526', 'Fri', '12:00 PM - 01:30 PM', ''),
(294, 'Lab 526', 'Fri', '07:30 PM - 09:00 PM', ''),
(295, 'Lab 528', 'Fri', '09:00 AM - 10:30 AM', ''),
(296, 'Lab 528', 'Fri', '03:00 PM - 04:30 PM', ''),
(297, 'Lab 528', 'Fri', '04:30 PM - 06:00 PM', ''),
(298, 'Lab 530', 'Fri', '04:30 PM - 06:00 PM', ''),
(299, 'Lab 530', 'Fri', '06:00 PM - 07:30 PM', ''),
(300, 'Lab 530', 'Fri', '12:00 PM - 01:30 PM', ''),
(301, 'Lab 542', 'Fri', '07:30 AM - 09:00 AM', ''),
(302, 'Lab 542', 'Fri', '01:30 PM - 03:00 PM', ''),
(303, 'Lab 542', 'Fri', '07:30 PM - 09:00 PM', ''),
(304, 'Lab 544', 'Fri', '07:30 AM - 09:00 AM', ''),
(305, 'Lab 544', 'Fri', '06:00 PM - 07:30 PM', ''),
(306, 'Lab 517', 'Sat', '09:00 AM - 10:30 AM', ''),
(307, 'Lab 524', 'Sat', '12:00 PM - 01:30 PM', ''),
(308, 'Lab 524', 'Sat', '03:00 PM - 04:30 PM', ''),
(309, 'Lab 526', 'Sat', '04:30 PM - 06:00 PM', ''),
(310, 'Lab 526', 'Sat', '06:00 PM - 07:30 PM', ''),
(311, 'Lab 528', 'Sat', '12:00 PM - 01:30 PM', ''),
(312, 'Lab 530', 'Sat', '07:30 AM - 09:00 AM', ''),
(313, 'Lab 530', 'Sat', '09:00 AM - 10:30 AM', ''),
(314, 'Lab 530', 'Sat', '07:30 PM - 09:00 PM', ''),
(315, 'Lab 542', 'Sat', '01:30 PM - 03:00 PM', ''),
(316, 'Lab 544', 'Sat', '06:00 PM - 07:30 PM', ''),
(317, 'Lab 544', 'Sat', '09:00 AM - 10:30 AM', ''),
(318, 'Lab 526', 'Sat', '07:30 AM - 09:00 AM', ''),
(319, 'Lab 530', 'MW', '04:30 PM - 06:00 PM', ''),
(320, 'Lab 517', 'MW', '07:30 AM - 09:00 AM', 'close'),
(321, 'Lab 528', 'MW', '07:30 AM - 09:00 AM', ''),
(322, 'Lab 524', 'MW', '07:30 AM - 09:00 AM', 'close'),
(323, 'Lab 517', 'MW', '07:30 PM - 09:00 PM', 'close'),
(324, 'Lab 524', 'TTh', '07:30 AM - 09:00 AM', 'open'),
(325, 'Lab 524', 'MW', '09:00 AM - 10:30 AM', 'close'),
(326, 'Lab 544', 'MW', '07:30 AM - 09:00 AM', ''),
(327, 'Lab 526', 'TTh', '07:30 AM - 09:00 AM', ''),
(328, 'Lab 530', 'MW', '07:30 AM - 09:00 AM', ''),
(329, 'Lab 542', 'MW', '09:00 AM - 10:30 AM', ''),
(330, 'Lab 517', 'MW', '12:00 PM - 01:30 PM', 'close'),
(331, 'Lab 517', 'MW', '01:30 PM - 03:00 PM', 'close'),
(332, 'Lab 517', 'MW', '03:00 PM - 04:30 PM', 'close'),
(333, 'Lab 517', 'TTh', '09:00 AM - 10:30 AM', ''),
(334, 'Lab 517', 'TTh', '12:00 PM - 01:30 PM', ''),
(335, 'Lab 517', 'TTh', '01:30 PM - 03:00 PM', ''),
(336, 'Lab 517', 'TTh', '04:30 PM - 06:00 PM', ''),
(337, 'Lab 517', 'TTh', '06:00 PM - 07:30 PM', ''),
(338, 'Lab 517', 'TTh', '07:30 PM - 09:00 PM', ''),
(339, 'Lab 526', 'Fri', '07:30 AM - 09:00 AM', ''),
(340, 'Lab 524', 'Sat', '07:30 AM - 09:00 AM', ''),
(341, 'Lab 530', 'Fri', '07:30 AM - 09:00 AM', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `course` varchar(50) NOT NULL,
  `yearlevel` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `remaining_sessions` int(11) DEFAULT 30,
  `points` int(11) NOT NULL DEFAULT 0,
  `cover_photo` varchar(255) DEFAULT NULL,
  `survey_completed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `course`, `yearlevel`, `email`, `firstname`, `lastname`, `middlename`, `username`, `password`, `profile_picture`, `role`, `remaining_sessions`, `points`, `cover_photo`, `survey_completed`) VALUES
(1, 'ADMIN001', '', '', 'admin@example.com', 'admin', '', NULL, 'admin', '$2y$10$wJu01nHFOeffP6vcOdF/r.R0iJ0TeEyK3.wkoY8BakPQDmIUBf/nq', 'Cha Hae-In _ Solo Leveling _ Season 2.jpg', 'admin', 30, 0, NULL, 0),
(12, '321', 'Bachelor of Science in Information Technology', '3', 'christineannealesna@gmail.com', 'Christine ', 'Alesna', 'aguhar', 'christine', '$2y$10$oecuTOx5tyidez5Zl7oBSuhZunrsoa3uRhg6x0j9L9B4bkTvFprtW', NULL, 'student', 30, 0, NULL, 0),
(13, '111', 'Bachelor of Science in Computer Science', '4', 'bryl@gmail.com', 'bryl', 'gorgonio', 'm', 'bryl', '$2y$10$LTzOJQygak1DEv1E.dfN9.bRdBtd5hdB95P9BXkegsAQSrWaZ4QOS', 'Igris _ Solo Leveling.jpg', 'student', 24, 1, NULL, 0),
(14, '3333', 'Bachelor of Science in Computer Science', '1', 'amaro@gmail.com', 'kobe', 'amaro', 'a', 'kobe', '$2y$10$f9quRFyQ730z8B5i3NMUwuBPJfTYRcp141rWR.zXpJybbE8Eq4DiO', '3333.png', 'student', 26, 1, NULL, 0),
(15, '098', 'Bachelor of Science in Computer Engineering', '3', 'keith@gmail.com', 'keith', 'alesna', 'a', 'keith', '$2y$10$Z0ZvewtddP0dBQjLtFzLMuLA73mjXKD4kJlM59lOxxeU4U8KfiEZK', NULL, 'student', 30, 0, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_name` (`lab_name`,`pc_number`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `rewards_log`
--
ALTER TABLE `rewards_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `static_lab_schedules`
--
ALTER TABLE `static_lab_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_name` (`lab_name`,`day_group`,`time_slot`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idno` (`idno`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `rewards_log`
--
ALTER TABLE `rewards_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `static_lab_schedules`
--
ALTER TABLE `static_lab_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=342;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`idno`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`idno`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rewards_log`
--
ALTER TABLE `rewards_log`
  ADD CONSTRAINT `rewards_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  ADD CONSTRAINT `satisfaction_surveys_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD CONSTRAINT `sit_in_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
