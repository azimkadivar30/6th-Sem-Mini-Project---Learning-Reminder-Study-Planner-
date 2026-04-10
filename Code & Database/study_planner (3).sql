-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 01:30 PM
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
-- Database: `study_planner`
--

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `reminder_time` datetime DEFAULT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `user_id`, `title`, `task_id`, `reminder_time`, `status`, `created_at`) VALUES
(5, 1, 'sdgdfg', NULL, '2026-02-28 14:09:00', 'done', '2026-02-28 08:39:00'),
(6, 1, 'rtyry', NULL, '2026-02-28 14:11:00', 'done', '2026-02-28 08:41:11'),
(7, 1, 'cffhdfhfdh', NULL, '2026-03-01 14:36:00', 'pending', '2026-02-28 09:06:41'),
(10, 1, 'dfghjm,', NULL, '2026-04-06 19:47:00', 'pending', '2026-04-07 14:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `study_sessions`
--

CREATE TABLE `study_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_sessions`
--

INSERT INTO `study_sessions` (`id`, `user_id`, `task_id`, `start_time`, `end_time`, `duration_seconds`, `created_at`) VALUES
(1, 1, 3, '2026-02-28 14:00:03', '2026-02-28 14:01:35', 0, '2026-02-28 08:30:03'),
(2, 1, 3, '2026-02-28 14:09:15', '2026-02-28 14:18:30', 0, '2026-02-28 08:39:15'),
(3, 1, 7, '2026-02-28 14:19:05', '2026-02-28 15:34:42', 0, '2026-02-28 08:49:05'),
(4, 1, 11, '2026-03-04 15:23:16', '2026-03-04 15:23:19', 0, '2026-03-04 09:53:16'),
(5, 1, 12, '2026-04-06 18:29:47', '2026-04-06 18:29:48', 0, '2026-04-06 12:59:47'),
(6, 1, 15, '2026-04-07 18:10:59', '2026-04-07 18:11:06', 7, '2026-04-07 12:40:59'),
(7, 1, 16, '2026-04-07 18:11:25', '2026-04-07 18:11:27', 2, '2026-04-07 12:41:25'),
(8, 1, 17, '2026-04-07 18:12:56', '2026-04-07 18:13:00', 4, '2026-04-07 12:42:56'),
(9, 1, 18, '2026-04-07 18:15:06', '2026-04-07 18:15:21', 15, '2026-04-07 12:45:06'),
(10, 1, 18, '2026-04-07 18:15:22', '2026-04-07 18:15:23', 1, '2026-04-07 12:45:22'),
(11, 1, 18, '2026-04-07 18:15:27', '2026-04-07 18:15:28', 1, '2026-04-07 12:45:27'),
(12, 1, 18, '2026-04-07 18:16:26', '2026-04-07 18:16:27', 1, '2026-04-07 12:46:26'),
(13, 1, 23, '2026-04-07 19:59:31', '2026-04-07 19:59:36', 5, '2026-04-07 14:29:31');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('high','medium','low') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `user_id`, `name`, `created_at`, `priority`) VALUES
(1, 1, 'OS', '2026-02-27 12:08:13', 'high'),
(6, 1, 'JS', '2026-02-28 09:03:38', 'low'),
(8, 1, 'CS', '2026-02-28 09:05:18', 'high'),
(10, 5, 'OS', '2026-04-05 09:43:11', 'high'),
(11, 5, 'OS', '2026-04-05 09:43:17', 'medium'),
(12, 5, 'CS', '2026-04-05 09:43:24', 'low'),
(13, 1, 'OST', '2026-04-07 12:37:20', 'medium');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `subject_id`, `task_name`, `deadline`, `status`, `created_at`, `completed_at`) VALUES
(18, 1, 13, 'Types of os', '2026-04-17', 'completed', '2026-04-07 12:44:21', '2026-04-07 18:30:17'),
(19, 1, 13, 'Types of os', '2026-04-07', 'completed', '2026-04-07 13:00:27', '2026-04-07 18:30:40'),
(20, 1, 13, 'Types of os', '2000-02-02', 'completed', '2026-04-07 13:00:37', '2026-04-07 18:30:38'),
(21, 1, 13, 'Explain Different types of OS', '2000-10-10', 'completed', '2026-04-07 13:07:59', '2026-04-07 18:38:02'),
(22, 1, 13, 'Explain Different types of OS', '2026-04-07', 'pending', '2026-04-07 14:17:00', NULL),
(23, 1, 6, 'explain what is js', '2026-04-07', 'completed', '2026-04-07 14:29:29', '2026-04-07 19:59:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `badge` varchar(50) DEFAULT 'Beginner'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `phone`, `profile_pic`, `points`, `badge`) VALUES
(1, 'Safik Sherasiya', 'safiksherasiya786@gmail.com', '$2y$10$xzTmLhWvrjfN43bR8JiOJuyu.jStTjcoyD/Ktt4HN8IVyVUNFRRNe', '2026-02-27 12:07:45', '91 9510218598', '1775563034_IMG_3511__Small_.jpeg', 110, 'Bronze'),
(2, 'farid', 'parasarafarid@gmail.com', '$2y$10$EzGYQkh3Do1Mk3dDM2bqO.VRhvtaihoFD6dJSBV4JxYb02bO3p3SO', '2026-02-27 13:11:46', NULL, NULL, 0, 'Beginner'),
(3, 'farid parasara', 'abc@gmail.com', '$2y$10$O15xbuqJ3XnfuDPI0nIJS.xYQr01u/bRPLugpEBT3C5FUw95KT5U6', '2026-02-27 13:52:46', NULL, NULL, 0, 'Beginner'),
(5, 'sk', 'sk@gmail.com', '$2y$10$KEKdhUL0kKt1IF5eggVqdOZUVi5KS8yyUMIVikgRTMnDip/QLeCp2', '2026-04-05 09:20:26', '9510218598', '', 0, 'Beginner');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `study_sessions`
--
ALTER TABLE `study_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
