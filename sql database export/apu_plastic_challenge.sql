-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 10, 2026 at 03:34 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `apu_plastic_challenge`
--

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
CREATE TABLE IF NOT EXISTS `certificates` (
  `certificate_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `design_style` enum('professional','modern','classic') DEFAULT 'professional',
  `criteria_type` enum('manual','auto') DEFAULT 'manual',
  `criteria_value` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`certificate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`certificate_id`, `name`, `description`, `design_style`, `criteria_type`, `criteria_value`, `created_at`) VALUES
(4, 'King of Plastic', 'Very good contribution', 'classic', 'auto', 10, '2026-01-09 11:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `environmental_factors`
--

DROP TABLE IF EXISTS `environmental_factors`;
CREATE TABLE IF NOT EXISTS `environmental_factors` (
  `factor_id` int NOT NULL AUTO_INCREMENT,
  `item_type` enum('bottle','bag','container') NOT NULL,
  `co2_saved_grams` decimal(8,2) NOT NULL,
  `water_saved_liters` decimal(8,2) NOT NULL,
  `data_source` varchar(255) NOT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`factor_id`),
  UNIQUE KEY `item_type` (`item_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `environmental_factors`
--

INSERT INTO `environmental_factors` (`factor_id`, `item_type`, `co2_saved_grams`, `water_saved_liters`, `data_source`, `last_updated`, `is_active`) VALUES
(1, 'bottle', 500.00, 5.00, 'EPA: Plastic Manufacturing Lifecycle Analysis 2023', '2026-01-09 10:46:34', 1),
(2, 'bag', 200.00, 2.00, 'Journal of Cleaner Production, Vol 45, 2022', '2026-01-09 10:46:34', 1),
(3, 'container', 300.00, 3.00, 'Environmental Research Letters, Issue 18, 2023', '2026-01-09 10:46:34', 1);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `factor_id` int NOT NULL,
  `quantity` int NOT NULL,
  `log_date` date NOT NULL,
  `co2_saved` decimal(10,2) NOT NULL,
  `water_saved` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `factor_id` (`factor_id`),
  KEY `idx_user_date` (`user_id`,`log_date`),
  KEY `idx_log_date` (`log_date`)
) ;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `factor_id`, `quantity`, `log_date`, `co2_saved`, `water_saved`, `created_at`) VALUES
(2, 2, 2, 10, '2026-01-09', 2000.00, 20.00, '2026-01-09 11:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('guest','participant','admin') DEFAULT 'guest',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role`, `status`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@apu.edu.my', '$2y$10$e7gmBgONb59XQBFyh2QhcevXZYcp2sCAx2v39K0rMIlBdspQZpUre', 'admin', 'active', '2026-01-09 10:44:25', '2026-01-09 11:58:54'),
(2, 'JohnDoe', 'john123@gmail.com', '$2y$10$VqMkKU3zElpyMdzPt/DiteQ1J/XhYo4reTw9aK8Yg8Ao/HVMDZr9q', 'participant', 'active', '2026-01-09 11:43:23', '2026-01-09 11:43:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_certificates`
--

DROP TABLE IF EXISTS `user_certificates`;
CREATE TABLE IF NOT EXISTS `user_certificates` (
  `user_certificate_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `certificate_id` int NOT NULL,
  `awarded_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `awarded_by` int NOT NULL,
  `personal_message` text,
  PRIMARY KEY (`user_certificate_id`),
  UNIQUE KEY `unique_user_cert` (`user_id`,`certificate_id`),
  KEY `certificate_id` (`certificate_id`),
  KEY `awarded_by` (`awarded_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_certificates`
--

INSERT INTO `user_certificates` (`user_certificate_id`, `user_id`, `certificate_id`, `awarded_date`, `awarded_by`, `personal_message`) VALUES
(5, 2, 4, '2026-01-09 11:46:45', 2, NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`factor_id`) REFERENCES `environmental_factors` (`factor_id`);

--
-- Constraints for table `user_certificates`
--
ALTER TABLE `user_certificates`
  ADD CONSTRAINT `user_certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_certificates_ibfk_2` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`certificate_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_certificates_ibfk_3` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
