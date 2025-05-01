-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 12:52 PM
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
-- Database: `sysarch_sitin`
--
CREATE DATABASE IF NOT EXISTS `sysarch_sitin` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sysarch_sitin`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `user_id` int(11) NOT NULL,
  `admin_idno` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`user_id`, `admin_idno`) VALUES
(18, '120403');

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `announcement_id` int(11) NOT NULL,
  `ann_title` varchar(100) NOT NULL,
  `ann_description` varchar(500) NOT NULL,
  `ann_timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`announcement_id`, `ann_title`, `ann_description`, `ann_timestamp`) VALUES
(8, 'Lab 123 will be close tomorrow!!!', 'We will have a server maintenance in lab 123, starting tomorrow it will be close. Please wait for further announcement. We will have a server maintenance in lab 123, starting tomorrow it will be close. Please wait for further announcement.', '2025-03-20 17:54:39'),
(9, 'Lost Wallet', 'Lost wallet in lab 321', '2025-03-20 17:57:38'),
(10, 'Trial', 'trial', '2025-03-20 23:29:00'),
(11, '2', '2', '2025-03-21 03:54:13'),
(12, '11', '1', '2025-03-21 04:14:31'),
(13, '11', '1', '2025-03-21 04:14:31'),
(14, '1', '1', '2025-03-21 04:14:40'),
(15, '1', '1', '2025-03-21 04:14:40'),
(16, '122', '122', '2025-03-21 04:15:07'),
(17, 'Hii', 'hii', '2025-03-21 11:41:06'),
(18, 'wwww', 'wwww', '2025-03-21 11:51:07'),
(19, 'hELLO WORLD', 'HELLO', '2025-03-21 12:14:00');

-- --------------------------------------------------------

--
-- Table structure for table `current_sitin`
--

CREATE TABLE `current_sitin` (
  `sitin_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `student_idno` varchar(10) NOT NULL,
  `admin_idno` int(11) NOT NULL,
  `lab_room` varchar(100) NOT NULL,
  `sitin_purpose` varchar(100) NOT NULL,
  `start_time` time NOT NULL DEFAULT current_timestamp(),
  `sitin_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `current_sitin`
--

INSERT INTO `current_sitin` (`sitin_id`, `full_name`, `student_idno`, `admin_idno`, `lab_room`, `sitin_purpose`, `start_time`, `sitin_date`) VALUES
(3905, 'Bryl Darel Mejeca Gorgonio', '20949194', 29, '526', 'C Programming', '18:37:00', '2025-05-01');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reservation`
--

CREATE TABLE `lab_reservation` (
  `reservation_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_idno` varchar(50) NOT NULL,
  `lab_room` int(11) NOT NULL,
  `seat_no` int(11) NOT NULL,
  `sitin_purpose` varchar(50) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `reservation_status` set('Pending','Approved','Disapproved') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_reservation`
--

INSERT INTO `lab_reservation` (`reservation_no`, `user_id`, `student_idno`, `lab_room`, `seat_no`, `sitin_purpose`, `reservation_date`, `reservation_time`, `reservation_status`) VALUES
('133822', 29, '20949194', 524, 1, 'C# Programming', '2025-05-01', '17:37:00', 'Approved'),
('476133', 29, '20949194', 542, 1, 'C# Programming', '2025-05-01', '17:58:00', 'Approved'),
('957320', 29, '20949194', 528, 3, 'C# Programming', '2025-05-01', '18:00:00', 'Approved'),
('657551', 29, '20949194', 526, 3, 'C Programming', '2025-05-01', '18:05:00', 'Approved'),
('938677', 29, '20949194', 530, 4, 'C Programming', '2025-05-01', '18:12:00', 'Approved'),
('425793', 29, '20949194', 528, 2, 'Python Programming', '2025-05-01', '18:21:00', 'Approved'),
('432361', 29, '20949194', 528, 2, 'C Programming', '2025-05-01', '18:32:00', 'Approved'),
('732344', 29, '20949194', 528, 4, 'Python Programming', '2025-05-01', '18:34:00', 'Approved'),
('521588', 29, '20949194', 526, 2, 'C Programming', '2025-05-01', '18:37:00', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `lab_schedules`
--

CREATE TABLE `lab_schedules` (
  `lab_room` int(11) NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `status` set('open','close') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_schedules`
--

INSERT INTO `lab_schedules` (`lab_room`, `open_time`, `close_time`, `status`) VALUES
(524, '08:00:00', '17:00:00', 'open'),
(526, '08:00:00', '17:00:00', 'open'),
(528, '08:00:00', '17:00:00', 'open'),
(530, '08:00:00', '17:00:00', 'open'),
(542, '08:00:00', '17:00:00', 'open'),
(544, '08:00:00', '17:00:00', 'open'),
(517, '08:00:00', '17:00:00', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_history`
--

CREATE TABLE `sitin_history` (
  `history_id` int(11) NOT NULL,
  `sitin_id` int(11) NOT NULL,
  `student_idno` varchar(10) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `admin_idno` int(11) NOT NULL,
  `lab_room` varchar(100) NOT NULL,
  `sitin_purpose` varchar(100) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_time` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `duration` int(11) GENERATED ALWAYS AS (if(`end_time` is not null,timestampdiff(MINUTE,`start_time`,`end_time`),NULL)) STORED,
  `feedback_desc` varchar(500) NOT NULL,
  `sitin_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_history`
--

INSERT INTO `sitin_history` (`history_id`, `sitin_id`, `student_idno`, `full_name`, `admin_idno`, `lab_room`, `sitin_purpose`, `start_time`, `end_time`, `feedback_desc`, `sitin_date`) VALUES
(115, 138, 'abc', 'bryl m gorgonio', 0, '512', 'C# Programming', '2025-04-15 00:29:47', '2025-04-15 00:29:47', 'atay', '2025-04-15'),
(116, 139, 'abc', 'bryl m gorgonio', 0, '512', 'ASP.Net Programming', '2025-04-15 00:25:11', '2025-04-15 00:25:11', 'Murag wtfuck ang internet', '2025-04-15'),
(117, 140, '001', 'Kobe Alkzar Amaro', 0, '602', 'Java Programming', '2025-04-15 00:24:31', '2025-04-15 00:24:32', '', '2025-04-15'),
(118, 141, 'abc', 'bryl m gorgonio', 0, '533', 'Java Programming', '2025-04-15 00:24:43', '2025-04-15 00:24:45', '', '2025-04-15'),
(119, 142, 'abc', 'bryl m gorgonio', 0, '512', 'Java Programming', '2025-04-15 00:29:28', '2025-04-15 00:29:30', '', '2025-04-15'),
(120, 143, 'abc', 'bryl m gorgonio', 0, '602', 'C# Programming', '2025-04-15 05:36:44', '2025-04-15 05:36:44', 'Wow nice', '2025-04-15'),
(121, 144, 'abc', 'bryl m gorgonio', 0, '512', 'Java Programming', '2025-04-15 07:05:43', '2025-04-15 07:05:43', 'Nice ang gabantay', '2025-04-15'),
(122, 145, '001', 'Kobe Alkzar Amaro', 0, '512', 'ASP.Net Programming', '2025-04-15 06:12:03', '2025-04-15 06:12:03', 'Atay guba ang mouse', '2025-04-15'),
(123, 146, '001', 'Kobe Alkzar Amaro', 0, '533', 'C# Programming', '2025-04-15 06:10:54', '2025-04-15 06:10:55', '', '2025-04-15'),
(124, 147, 'abc', 'bryl m gorgonio', 0, '512', 'C# Programming', '2025-04-15 07:05:58', '2025-04-15 07:05:58', 'atay', '2025-04-15'),
(125, 148, '001', 'Kobe Alkzar Amaro', 0, '602', 'ASP.Net Programming', '2025-04-15 06:11:05', '2025-04-15 06:11:07', '', '2025-04-15'),
(126, 149, '001', 'Kobe Alkzar Amaro', 0, '602', 'ASP.Net Programming', '2025-04-15 06:11:54', '2025-04-15 06:11:54', 'Buotan ang gabantay', '2025-04-15'),
(127, 150, 'abc', 'bryl m gorgonio', 0, '512', 'C# Programming', '2025-04-27 20:00:11', '2025-04-27 20:00:14', '', '2025-04-27'),
(128, 151, 'abc', 'bryl m gorgonio', 0, '512', 'C# Programming', '2025-04-27 20:09:40', '2025-04-27 20:09:44', '', '2025-04-27'),
(129, 152, 'abc', 'bryl m gorgonio', 0, '512', 'C# Programming', '2025-04-27 20:12:46', '2025-04-27 20:12:50', '', '2025-04-27'),
(130, 153, 'abc', 'bryl m gorgonio', 0, '602', 'C# Programming', '2025-04-27 20:32:36', '2025-04-27 20:32:39', '', '2025-04-27'),
(131, 154, 'abc', 'bryl m gorgonio', 0, '530', 'C# Programming', '2025-04-27 21:01:33', '2025-04-27 21:01:36', '', '2025-04-27'),
(132, 155, 'abc', 'bryl m gorgonio', 0, '530', 'C# Programming', '2025-04-27 21:01:49', '2025-04-27 21:01:52', '', '2025-04-27'),
(133, 156, 'abc', 'bryl m gorgonio', 0, '530', 'C# Programming', '2025-04-27 21:02:38', '2025-04-27 21:02:45', '', '2025-04-27'),
(134, 157, 'abc', 'bryl m gorgonio', 0, '530', 'C# Programming', '2025-04-27 21:04:55', '2025-04-27 21:05:10', '', '2025-04-27'),
(135, 158, 'abc', 'bryl m gorgonio', 0, '530', 'ASP.Net Programming', '2025-04-27 21:07:46', '2025-04-27 21:07:51', '', '2025-04-27'),
(136, 159, 'abc', 'bryl m gorgonio', 0, '530', 'ASP.Net Programming', '2025-04-27 21:08:10', '2025-04-27 21:08:18', '', '2025-04-27'),
(137, 160, 'abc', 'bryl m gorgonio', 0, '530', 'Java Programming', '2025-04-27 21:09:55', '2025-04-27 21:09:58', '', '2025-04-27'),
(138, 161, 'abc', 'bryl m gorgonio', 0, '530', 'Php Programming', '2025-04-27 21:11:40', '2025-04-27 21:11:47', '', '2025-04-27'),
(139, 162, 'abc', 'bryl m gorgonio', 0, '530', 'Php Programming', '2025-04-27 21:14:25', '2025-04-27 21:14:29', '', '2025-04-27'),
(140, 163, 'abc', 'bryl m gorgonio', 0, '530', 'Php Programming', '2025-04-27 21:14:49', '2025-04-27 21:15:03', '', '2025-04-27'),
(141, 164, '20949194', 'Bryl Darel Mejeca Gorgonio', 0, '524', 'C# Programming', '2025-05-01 09:56:47', '2025-05-01 09:58:58', '', '2025-05-01'),
(142, 3903, '20949194', 'Bryl Darel Mejeca Gorgonio', 0, '528', 'C Programming', '2025-05-01 10:32:00', '2025-05-01 10:32:31', '', '2025-05-01'),
(143, 3904, '20949194', 'Bryl Darel Mejeca Gorgonio', 0, '528', 'Python Programming', '0000-00-00 00:00:00', '2025-05-01 10:35:42', '', '2025-05-01');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `user_id` int(11) NOT NULL,
  `student_idno` varchar(10) NOT NULL,
  `year_level` int(1) NOT NULL,
  `course` varchar(30) NOT NULL,
  `remaining_sitin` int(11) NOT NULL DEFAULT 30,
  `points` int(11) NOT NULL,
  `total_points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`user_id`, `student_idno`, `year_level`, `course`, `remaining_sitin`, `points`, `total_points`) VALUES
(21, '001', 3, 'BSIT', 30, 0, 0),
(22, '002', 3, 'BSIT', 30, 0, 0),
(23, '003', 4, 'BSED', 30, 0, 0),
(28, '0099', 3, 'BSIT', 30, 0, 0),
(27, '123', 1, 'BSED', 30, 0, 0),
(29, '20949194', 3, 'BSIT', 28, 0, 3),
(19, 'abc', 3, 'BSIT', 19, 0, 9),
(25, 'qwerty', 1, 'BSIT', 30, 0, 0),
(26, 'zxcv', 3, 'BSED', 30, 0, 0);

--
-- Triggers `student`
--
DELIMITER $$
CREATE TRIGGER `trg_update_sessions_on_points` BEFORE UPDATE ON `student` FOR EACH ROW BEGIN
    -- Check if the points are greater than or equal to 3
    IF NEW.points >= 3 THEN
        -- Check if sessions is less than 30 and not NULL
        IF NEW.remaining_sitin IS NOT NULL AND NEW.remaining_sitin < 30 THEN
            -- Increment sessions by 1
            SET NEW.remaining_sitin = NEW.remaining_sitin + 1;
        END IF;
        -- Reset points to 0
        SET NEW.points = 0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `middle_name` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` varchar(10) NOT NULL DEFAULT 'student',
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `middle_name`, `email`, `username`, `password`, `user_role`, `reg_date`) VALUES
(18, 'admin', 'admin', 'admin', 'admin@gmail.com', 'admin', '$2y$10$7yupwqu9fCivf9fQryS2wOOh3eSgLI/WDoLKAX3./YbH27B0A7kUe', 'admin', '2025-03-19 09:41:50'),
(19, 'bryl', 'gorgonio', 'm', 'bryl@gmail.com', 'bryl', '$2y$10$1aYZwzAdfL.EDGkvW/VFmutrQgaWn7nfpZP3to1xCByV3RrJHewgG', 'student', '2025-03-20 15:42:06'),
(21, 'Kobe', 'Amaro', 'Alkzar', 'kobe@gmail.com', 'kobe', '$2y$10$vBlzK5Vbmy7kvaP1agWYm.EPSPlNF8L3Zgq.ONAmSqB09rVE7lnYm', 'student', '2025-03-21 01:47:44'),
(22, 'Christine', 'Alesna', 'Aguhar', 'christineannealesna@gmail.com', 'christine', '$2y$10$cjmzxmbkxcgvt9CCVSCnB.6KVGvSXpGs6qbAGSBjwWOt1o7dWeMJ.', 'student', '2025-03-21 01:49:35'),
(23, 'Rosalinda', 'Aguhar', 'Rosales', 'rosalinda@gmail.com', 'rosalinda', '$2y$10$GXxTBXJlyi1/II.3F03Q.eyuRV6hpz.TEUc1MPn9IeMg0X0ehyJlu', 'student', '2025-03-21 01:51:23'),
(24, '32', '32', '32', '32@gmail.com', '32', '$2y$10$a.9QXEzyyFJxBLDIMEMIeO6Peovp/5ODmAS0XcZmqIUJOLmLag0mq', 'student', '2025-03-21 02:40:47'),
(25, 'Angel', 'Locsin', 'Laura', 'angel@gmail.com', 'angel', '$2y$10$dESkjafPEMq7T.zlge15Ke9kKUPYa3Q4spX2ASzE3CoN9bkQDt4gK', 'student', '2025-03-25 03:55:04'),
(26, 'Juan', 'Cruz', 'Jose', 'juan@gmail.com', 'juan', '$2y$10$EjDLA4flr3FiEI0VmiVX2OwRrXhDKTIlMSEo8Z0/WddglSH7zzhy6', 'student', '2025-03-25 03:56:27'),
(27, 'Maomao', 'Apo', 'Xiao', 'mao@gmail.com', 'maomao', '$2y$10$YRu8Z2YUpiee12fBXV/2fuaWgVu67DQRIPhlzCVoNQKRMaXRH35na', 'student', '2025-03-25 04:00:58'),
(28, 'Maria', 'Piattos', 'San', 'maria@gmail.com', 'maria', '$2y$10$L7SRtVTsspTIn8DCksVTKukPwgNkStYYiosY96GeYJ97wBky2U3E.', 'student', '2025-03-25 04:05:38'),
(29, 'Bryl Darel', 'Gorgonio', 'Mejeca', 'brylgorgonio@gmail.com', 'bryldarel', '$2y$10$xVKEIBdVUAZ3pgIXXlPGpO5aHGVrs1yi0vF4IR9xxW8WjjCVHuxxC', 'student', '2025-05-01 05:17:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_idno`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `current_sitin`
--
ALTER TABLE `current_sitin`
  ADD PRIMARY KEY (`sitin_id`),
  ADD UNIQUE KEY `student_idno` (`student_idno`);

--
-- Indexes for table `sitin_history`
--
ALTER TABLE `sitin_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_idno`) USING BTREE,
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `current_sitin`
--
ALTER TABLE `current_sitin`
  MODIFY `sitin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3906;

--
-- AUTO_INCREMENT for table `sitin_history`
--
ALTER TABLE `sitin_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
