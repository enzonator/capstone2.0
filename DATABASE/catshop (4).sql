-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 04:36 PM
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
-- Database: `catshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `adoption_applications`
--

CREATE TABLE `adoption_applications` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `applicant_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `has_other_pets` tinyint(1) DEFAULT 0,
  `other_pets_details` text DEFAULT NULL,
  `home_type` enum('House','Apartment','Condo','Other') DEFAULT NULL,
  `has_yard` tinyint(1) DEFAULT 0,
  `experience_with_cats` text DEFAULT NULL,
  `reason_for_adoption` text DEFAULT NULL,
  `veterinarian_info` text DEFAULT NULL,
  `references` text DEFAULT NULL,
  `living_with` text DEFAULT NULL,
  `household_allergic` varchar(10) DEFAULT NULL,
  `responsible_person` varchar(255) DEFAULT NULL,
  `financially_responsible` varchar(255) DEFAULT NULL,
  `vacation_care` varchar(255) DEFAULT NULL,
  `hours_alone` varchar(100) DEFAULT NULL,
  `introduction_steps` text DEFAULT NULL,
  `family_support` varchar(10) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `admin_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adoption_applications`
--

INSERT INTO `adoption_applications` (`id`, `cat_id`, `applicant_name`, `email`, `phone`, `address`, `has_other_pets`, `other_pets_details`, `home_type`, `has_yard`, `experience_with_cats`, `reason_for_adoption`, `veterinarian_info`, `references`, `living_with`, `household_allergic`, `responsible_person`, `financially_responsible`, `vacation_care`, `hours_alone`, `introduction_steps`, `family_support`, `status`, `admin_notes`, `submitted_at`, `updated_at`) VALUES
(1, 3, 'violet evergarden', 'Violet17@gmail.com', '01239123', 'Bago Davao City', 0, '', 'House', 1, 'I love cats since I\'m a kid', 'looks cute', '98934324', 'yesy', 'Living alone', 'No', 'me', 'me', 'my mother', '0', 'take it slowly', 'Yes', 'Pending', NULL, '2025-11-03 21:31:31', '2025-11-03 21:31:31'),
(2, 3, 'violet evergarden', 'monk@yahoo.com', '01239123', 'Dumoy Davao City', 0, '', 'House', 1, 'I love cats since I was a kid', 'she cute', 'yesdy', 'yes', 'Spouse', 'No', 'me', 'me', 'my mother', '0', 'take it easy', 'Yes', 'Pending', NULL, '2025-11-03 21:53:10', '2025-11-03 21:53:10'),
(3, 3, 'asdq', 'qweqwe@gsdf.asd', '123123', 'qaweqweqwe', 1, 'qweqwe', 'House', 0, 'qweqwe', 'qweqwe', 'qweqwe', 'qweqwe', 'Living alone, Children over 18, Roommate(s)', 'Yes', 'qweqwe', 'qweqwe', 'qweqwe', 'qweqw', 'eqweqweqwe', 'Yes', 'Pending', NULL, '2025-11-09 13:44:31', '2025-11-09 13:44:31'),
(4, 3, 'qwqeqwe', '3213asd@asdaas.asd', '123213', 'qweqwe', 1, 'qweqw', 'House', 0, 'eqwe', 'qweqwe', 'qweqwe', 'qweqwe', 'Living alone, Children over 18', 'Yes', 'qweqwe', 'qweqwe', 'qweqwe', 'qwee', 'qweqwe', 'Yes', 'Pending', NULL, '2025-11-09 14:01:23', '2025-11-09 14:01:23'),
(5, 3, '123123', '12asd2@GASD.FG', '123123', '1QAWEQWE', 1, 'QWEQWE', 'Apartment', 0, 'QWEQWE', 'QWEQWE', 'QWEQWE', 'QWEQWE', 'Living alone', 'Yes', 'QWEQW', 'QWEQWE', 'QWEQWE', 'QWEQWE', 'QWEQWEQWE', 'Yes', 'Pending', NULL, '2025-11-09 14:03:00', '2025-11-09 14:03:00'),
(6, 3, '123123', '12asd2@GASD.FG', '123123', '1QAWEQWE', 1, 'QWEQWE', 'Apartment', 0, 'QWEQWE', 'QWEQWE', 'QWEQWE', 'QWEQWE', 'Living alone', 'Yes', 'QWEQW', 'QWEQWE', 'QWEQWE', 'QWEQWE', 'QWEQWEQWE', 'Yes', 'Pending', NULL, '2025-11-09 14:03:03', '2025-11-09 14:03:03'),
(7, 3, 'qwe', 'king@gmail.com', '123213', '123qwe', 1, 'qwe', 'House', 0, 'qwe', 'qwe', 'qwe', 'qwe', 'Living alone', 'Yes', 'qweqw', 'qweqwe', 'qweqwe', 'qweqwe', 'qweqwe', 'Yes', 'Approved', 'okay sieer', '2025-11-09 14:05:43', '2025-11-09 21:05:20'),
(8, 6, 'asdq', '12asd2@GASD.FG', '123213', '12312', 1, 'qweq', 'House', 1, 'qweqwee', 'qweqwe', 'qweqwe', 'qweqwe', 'Living alone, Children over 18, Roommate(s)', 'Yes', 'qweqwe', 'qweqwe', 'qweqwe', '322', 'qweqwe', 'Yes', 'Rejected', 'sorry sir', '2025-11-11 00:16:07', '2025-11-11 00:39:28');

-- --------------------------------------------------------

--
-- Table structure for table `adoption_appointments`
--

CREATE TABLE `adoption_appointments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `appointment_type` enum('Meet and Greet','Home Visit','Final Adoption') DEFAULT 'Meet and Greet',
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adoption_cats`
--

CREATE TABLE `adoption_cats` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `health_status` text DEFAULT NULL,
  `vaccinated` tinyint(1) DEFAULT 0,
  `neutered` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `adoption_fee` decimal(10,2) DEFAULT 0.00,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `additional_address` text DEFAULT NULL,
  `status` enum('Available','Pending','Adopted') DEFAULT 'Available',
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adoption_cats`
--

INSERT INTO `adoption_cats` (`id`, `name`, `age`, `gender`, `breed`, `description`, `health_status`, `vaccinated`, `neutered`, `image_url`, `adoption_fee`, `latitude`, `longitude`, `address`, `additional_address`, `status`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Katy', 3, 'Male', 'American Wirehair', 'Cutie cat with beautiful hair', 'Healthy', 1, 1, '1762005148_0_0ef510fc560eb848087b711366368562 - Copy.jpg', 500.00, '7.0255941538318565', '125.51628828048707', 'Could not get address', NULL, 'Adopted', NULL, '2025-11-01 13:52:28', '2025-11-17 17:00:29'),
(3, 'mimi', 3, 'Male', 'Sphynx', 'Cute friendly cat', 'Healthy', 1, 1, '1762109528_0_58851fb848a7361f52d22e3eba20eb10.jpg', 200.00, '', '', 'Could not get address', NULL, 'Adopted', 18, '2025-11-02 18:52:08', '2025-11-17 17:00:39'),
(4, 'japaneser', 2, 'Female', 'Japanese Bobtail', 'cutie', 'no sickness', 1, 1, '1762771675_0_asd.jpg', 200.00, '7.073210817063724', '125.61883449554445', 'Santa Ana National High School, D. Suazo Street, Barangay 28-C, Poblacion District, Davao City, Davao Region, 8000, Philippines', NULL, 'Adopted', 2, '2025-11-10 10:47:55', '2025-11-17 17:00:50'),
(5, 'wave', 1, 'Female', 'American Bobtail', 'Cutie', 'So healthy', 1, 1, '1762818487_0_asd.jpg', 200.00, '', '', 'Could not get address', NULL, 'Adopted', 20, '2025-11-10 23:48:07', '2025-11-17 17:01:01'),
(6, ' Pham Hanni', 1, 'Female', 'American Bobtail', 'qwe', 'qwe', 1, 1, '1762820130_0_asd.jpg', 100.00, '7.0746112', '125.5440384', 'Caflor Village, Catalunan Grande, Talomo District, Davao City, Davao Region, 8000, Philippines', NULL, 'Adopted', 21, '2025-11-11 00:15:30', '2025-11-17 17:01:13'),
(7, 'jake', 3, 'Male', 'Sphynx', 'behave cat', 'health', 1, 1, '1763399808_0_jake.jpg', 250.00, '7.1917178', '125.4782225', 'Davao City, Philippines', '', 'Available', 19, '2025-11-17 17:16:48', '2025-11-17 17:16:48'),
(8, 'grobby', 3, 'Male', 'Cornish Rex', 'playful cat', 'in good health', 1, 1, '1763399932_0_grobby1.jpg', 500.00, '7.017501390769704', '125.5102586746216', 'Location: 7.017501, 125.510259', '', 'Available', 19, '2025-11-17 17:18:52', '2025-11-17 17:18:52'),
(9, 'grent', 1, 'Male', 'American Wirehair', 'furry playful cat ', 'in good condition', 1, 1, '1763400237_0_grent1.jpg', 400.00, '7.105066095723701', '125.56304454803468', 'Location: 7.105066, 125.563045', '', 'Available', 17, '2025-11-17 17:23:57', '2025-11-17 17:23:57'),
(10, 'violet', 2, 'Female', 'American Bobtail', 'looks like an angel', 'good health', 1, 1, '1763400559_0_evergarden1.jpg', 500.00, '7.052980721810788', '125.55649995803834', 'Location: 7.052981, 125.556500', 'Blk.60 lot 67', 'Available', 17, '2025-11-17 17:29:19', '2025-11-17 17:29:19'),
(11, 'georgia', 1, 'Female', 'Cornish Rex', 'loves fish', 'heallthy', 1, 1, '1763400648_0_georgia.jpg', 250.00, '7.046549495755373', '125.50384283065797', 'Location: 7.046549, 125.503843', 'blk.15 lot 24', 'Available', 17, '2025-11-17 17:30:48', '2025-11-17 17:30:48'),
(12, 'jammean', 1, 'Female', 'Devon Rex', 'playful', 'healthy', 1, 1, '1763400803_0_jammean1.jpg', 250.00, '7.20666452575607', '125.50429344177248', 'Location: 7.206665, 125.504293', 'kalamansi street', 'Available', 17, '2025-11-17 17:33:23', '2025-11-17 17:33:23'),
(13, 'coby', 1, 'Male', 'Khao Manee', 'night cat', 'in good condition', 1, 1, '1763400913_0_cobby.jpg', 250.00, '7.126273333185034', '125.43374061584474', 'Location: 7.126273, 125.433741', 'rizal street', 'Available', 17, '2025-11-17 17:35:13', '2025-11-17 17:35:13'),
(14, 'thomas', 3, 'Female', 'Khao Manee', 'rescue cat', 'no sickness', 1, 1, '1763401075_0_thomas1.jpg', 250.00, '7.020674622538112', '125.4993259906769', 'Location: 7.020675, 125.499326', 'antonio street blk.6 lot7', 'Available', 17, '2025-11-17 17:37:55', '2025-11-17 17:37:55'),
(15, 'phampham', 3, 'Female', 'Siamese', 'triple color cat', 'healthy', 1, 1, '1763401247_0_phampham1.jpg', 600.00, '7.021845944232477', '125.50682544708253', 'Location: 7.021846, 125.506825', 'katipunan highway blk. 6 lot.7', 'Available', 17, '2025-11-17 17:40:47', '2025-11-17 17:40:47'),
(16, 'orange', 3, 'Male', 'Turkish Angora', 'playful', 'single eye cat', 1, 1, '1763401440_0_orange1.jpg', 450.00, '7.1311866', '125.6118938', 'The Harmony, Davao City, Philippines', 'maligaya street', 'Available', 17, '2025-11-17 17:44:00', '2025-11-17 17:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `adoption_cat_images`
--

CREATE TABLE `adoption_cat_images` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adoption_cat_images`
--

INSERT INTO `adoption_cat_images` (`id`, `cat_id`, `filename`, `created_at`) VALUES
(1, 1, '1762005148_0_0ef510fc560eb848087b711366368562 - Copy.jpg', '2025-11-01 13:52:28'),
(2, 1, '1762005148_1_6ce107dbe303c5d1cce1bf5bdc092fb7.jpg', '2025-11-01 13:52:28'),
(5, 3, '1762109528_0_58851fb848a7361f52d22e3eba20eb10.jpg', '2025-11-02 18:52:08'),
(6, 3, '1762109528_1_ff559ba0c9eac235a8c6e60f6511a02e.jpg', '2025-11-02 18:52:08'),
(7, 4, '1762771675_0_asd.jpg', '2025-11-10 10:47:55'),
(8, 4, '1762771675_1_ebb3f2a4774a823b2403c10d935aba33.jpg', '2025-11-10 10:47:55'),
(9, 5, '1762818487_0_asd.jpg', '2025-11-10 23:48:07'),
(10, 5, '1762818487_1_ebb3f2a4774a823b2403c10d935aba33.jpg', '2025-11-10 23:48:07'),
(11, 6, '1762820130_0_asd.jpg', '2025-11-11 00:15:30'),
(12, 6, '1762820130_1_ebb3f2a4774a823b2403c10d935aba33.jpg', '2025-11-11 00:15:30'),
(13, 7, '1763399808_0_jake.jpg', '2025-11-17 17:16:48'),
(14, 7, '1763399808_1_jake2.jpg', '2025-11-17 17:16:48'),
(15, 8, '1763399932_0_grobby1.jpg', '2025-11-17 17:18:52'),
(16, 8, '1763399932_1_grobby2.jpg', '2025-11-17 17:18:52'),
(17, 9, '1763400237_0_grent1.jpg', '2025-11-17 17:23:57'),
(18, 9, '1763400237_1_grent2.jpg', '2025-11-17 17:23:57'),
(19, 10, '1763400559_0_evergarden1.jpg', '2025-11-17 17:29:19'),
(20, 10, '1763400559_1_evergarden2.jpg', '2025-11-17 17:29:19'),
(21, 10, '1763400559_2_evergarden3.jpg', '2025-11-17 17:29:19'),
(22, 11, '1763400648_0_georgia.jpg', '2025-11-17 17:30:48'),
(23, 11, '1763400648_1_georgia2.jpg', '2025-11-17 17:30:48'),
(24, 12, '1763400803_0_jammean1.jpg', '2025-11-17 17:33:23'),
(25, 12, '1763400803_1_jammean2.jpg', '2025-11-17 17:33:23'),
(26, 13, '1763400913_0_cobby.jpg', '2025-11-17 17:35:13'),
(27, 13, '1763400913_1_coby.jpg', '2025-11-17 17:35:13'),
(28, 14, '1763401075_0_thomas1.jpg', '2025-11-17 17:37:55'),
(29, 14, '1763401075_1_thomas2.jpg', '2025-11-17 17:37:55'),
(30, 15, '1763401247_0_phampham1.jpg', '2025-11-17 17:40:47'),
(31, 15, '1763401247_1_phampham2.jpg', '2025-11-17 17:40:47'),
(32, 16, '1763401440_0_orange1.jpg', '2025-11-17 17:44:00'),
(33, 16, '1763401440_1_orange2.jpg', '2025-11-17 17:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `pet_id`, `created_at`) VALUES
(10, 2, 16, '2025-11-09 12:37:15'),
(14, 1, 19, '2025-11-13 16:52:06'),
(15, 9, 29, '2025-11-16 17:12:34');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('pending','reviewed','archived') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `rating`, `message`, `created_at`, `status`, `admin_notes`) VALUES
(1, 21, 5, 'very niceu', '2025-11-17 20:34:25', 'archived', NULL),
(2, 9, 3, 'not bad', '2025-11-17 20:46:28', 'reviewed', NULL),
(3, 19, 5, 'nice website dawg', '2025-11-17 20:51:14', 'reviewed', NULL),
(4, 20, 5, 'cute website', '2025-11-17 20:55:42', 'reviewed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `pet_id`, `sender_id`, `receiver_id`, `message`, `created_at`, `is_read`) VALUES
(1, 15, 5, 2, 'hello', '2025-09-22 05:34:34', 1),
(2, 15, 5, 2, 'heyheyhey', '2025-09-28 08:54:51', 1),
(3, 15, 2, 5, 'damn', '2025-09-28 08:58:35', 1),
(4, 15, 5, 2, 'what the helly', '2025-09-28 09:01:31', 1),
(5, 15, 5, 2, 'pyramids', '2025-09-28 09:03:44', 1),
(6, 15, 5, 2, 'boeng', '2025-09-28 09:16:59', 1),
(7, 15, 5, 2, 'takemybreath', '2025-09-28 09:18:10', 1),
(8, 15, 2, 5, 'WAHAHAHAHA', '2025-09-28 09:19:33', 1),
(9, 15, 2, 5, 'BOeng', '2025-09-28 09:19:45', 1),
(10, 15, 5, 2, 'are you okay?', '2025-09-28 09:26:24', 1),
(11, 15, 5, 2, 'out of time', '2025-09-28 09:29:36', 1),
(12, 15, 5, 2, 'grashshahasdh', '2025-09-28 10:15:42', 1),
(13, 15, 9, 2, 'hello', '2025-09-29 06:35:31', 1),
(14, 15, 2, 9, 'hello siers', '2025-09-29 06:36:27', 1),
(15, 15, 5, 2, 'aaa', '2025-10-01 07:45:55', 1),
(16, 16, 5, 18, 'Is she still available?', '2025-11-03 15:42:40', 1),
(17, 16, 5, 18, 'Is she still available?', '2025-11-03 15:45:05', 1),
(18, 16, 17, 18, 'Is it a boy or girl?', '2025-11-03 15:48:09', 1),
(19, 17, 20, 2, 'hello', '2025-11-11 00:25:02', 1),
(20, 17, 2, 20, 'what?', '2025-11-11 00:31:53', 1),
(21, 17, 20, 2, 'HAHAHA', '2025-11-11 00:32:21', 1),
(22, 10, 21, 2, 'hello', '2025-11-13 15:40:38', 1),
(23, 10, 21, 2, 'what', '2025-11-13 15:41:13', 1),
(24, 29, 19, 21, 'up?', '2025-11-17 10:22:03', 1),
(25, 29, 21, 19, 'nash', '2025-11-17 10:22:12', 1),
(26, 29, 21, 19, 'okay', '2025-11-17 10:22:27', 1),
(27, 29, 19, 21, 'yes', '2025-11-17 10:22:38', 1),
(28, 29, 19, 21, 'yo', '2025-11-17 10:30:33', 1),
(29, 29, 21, 19, 'what', '2025-11-17 10:30:53', 1),
(30, 29, 19, 21, 'asd', '2025-11-17 10:31:29', 1),
(31, 31, 17, 20, 'still available bang?', '2025-11-17 23:49:08', 1),
(32, 31, 20, 17, 'yes bang', '2025-11-17 23:49:20', 1),
(33, 32, 17, 20, 'up?', '2025-11-18 00:04:53', 1),
(34, 32, 20, 17, 'yes sir', '2025-11-18 00:05:04', 1),
(35, 32, 17, 20, 'I buy', '2025-11-18 00:05:13', 1),
(36, 32, 20, 17, 'okay', '2025-11-18 00:05:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `order_id` int(11) DEFAULT NULL,
  `cat_id` int(11) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `order_id`, `cat_id`, `pet_id`, `is_read`, `created_at`) VALUES
(6, 2, 'New order received for Posang cute from monk. Order #11', 'new_order', 11, NULL, NULL, 1, '2025-11-01 19:48:52'),
(7, 5, 'Your order #11 has been confirmed by the seller.', 'order_update', 11, NULL, NULL, 1, '2025-11-01 19:49:37'),
(8, 5, 'Your order #11 for Posang cute has been confirmed by the seller.', 'order_update', 11, NULL, NULL, 1, '2025-11-01 19:52:48'),
(9, 5, 'Your order #11 for Posang cute has been confirmed by the seller.', 'order_update', 11, NULL, NULL, 1, '2025-11-01 19:55:37'),
(10, 5, 'Great news! Your order #11 for Posang cute has been shipped!', 'order_update', 11, NULL, NULL, 1, '2025-11-01 19:55:41'),
(11, 5, 'Your order #11 for Posang cute has been marked as delivered.', 'order_update', 11, NULL, NULL, 1, '2025-11-01 19:55:58'),
(12, 5, 'Your order #11 for Posang cute has been confirmed by the seller.', 'order_confirmed', 11, NULL, NULL, 1, '2025-11-01 20:03:41'),
(13, 5, 'Great news! Your order #11 for Posang cute has been shipped!', 'order_shipped', 11, NULL, NULL, 1, '2025-11-01 20:03:57'),
(14, 5, 'Your order #11 for Posang cute has been marked as delivered.', 'order_delivered', 11, NULL, NULL, 1, '2025-11-01 20:04:10'),
(15, 5, 'Your order #11 for Posang cute has been delivered successfully! Enjoy your new pet! 🎉', 'order_delivered', 11, NULL, NULL, 1, '2025-11-01 20:10:15'),
(16, 18, 'monk sent you a message about kiki', 'new_message', NULL, NULL, 16, 1, '2025-11-03 23:42:40'),
(17, 18, 'monk sent you a message about kiki', 'new_message', NULL, NULL, 16, 1, '2025-11-03 23:45:05'),
(18, 18, 'Violet17 sent you a message about kiki', 'new_message', NULL, NULL, 16, 1, '2025-11-03 23:48:09'),
(19, 2, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-09 21:07:00'),
(20, 19, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-09 21:29:49'),
(21, 18, 'New adoption application received for mimi from qwe', 'adoption_application', NULL, 3, NULL, 1, '2025-11-09 22:05:43'),
(22, 19, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-10 04:50:50'),
(23, 19, 'Your adoption application for mimi has been approved', 'adoption_approved', NULL, 3, NULL, 1, '2025-11-10 05:05:20'),
(24, 19, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-10 05:13:39'),
(25, 18, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-10 19:09:51'),
(26, 2, 'New order received for mamamiming from Vangeance. Order #12', 'new_order', 12, NULL, NULL, 1, '2025-11-10 19:13:10'),
(27, 18, 'Your order #12 for mamamiming has been confirmed by the seller.', 'order_confirmed', 12, NULL, NULL, 1, '2025-11-10 19:14:06'),
(28, 18, 'Great news! Your order #12 for mamamiming has been shipped!', 'order_shipped', 12, NULL, NULL, 1, '2025-11-10 19:14:20'),
(29, 18, 'Your order #12 for mamamiming has been delivered successfully! Enjoy your new pet! 🎉', 'order_delivered', 12, NULL, NULL, 1, '2025-11-10 19:14:39'),
(30, 21, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-11 08:03:41'),
(31, 20, '❌ Your verification request has been declined. Please ensure all requirements are met and try again.', 'verification_rejected', NULL, NULL, NULL, 1, '2025-11-11 08:07:10'),
(32, 20, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-11 08:10:43'),
(33, 21, 'New adoption application received for  Pham Hanni from asdq', 'adoption_application', NULL, 6, NULL, 1, '2025-11-11 08:16:07'),
(34, 2, 'frankocean01 sent you a message about Phampham', 'new_message', NULL, NULL, 17, 1, '2025-11-11 08:25:02'),
(35, 20, 'test sent you a message about Phampham', 'new_message', NULL, NULL, 17, 1, '2025-11-11 08:31:53'),
(36, 2, 'frankocean01 sent you a message about Phampham', 'new_message', NULL, NULL, 17, 1, '2025-11-11 08:32:21'),
(37, 2, '❌ Your verification request has been declined. Please ensure all requirements are met and try again.', 'verification_rejected', NULL, NULL, NULL, 1, '2025-11-13 08:04:10'),
(38, 2, 'jojo sent you a message about Caroline', 'new_message', NULL, NULL, 10, 1, '2025-11-13 23:40:38'),
(39, 2, 'jojo sent you a message about Caroline', 'new_message', NULL, NULL, 10, 1, '2025-11-13 23:41:13'),
(40, 2, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-13 23:42:38'),
(41, 23, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-14 19:01:45'),
(42, 21, 'kingkong sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:22:03'),
(43, 19, 'jojo sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:22:12'),
(44, 19, 'jojo sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:22:27'),
(45, 21, 'kingkong sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:22:38'),
(46, 21, 'kingkong sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:30:33'),
(47, 19, 'jojo sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:30:53'),
(48, 21, 'kingkong sent you a message about Riyakitty', 'new_message', NULL, NULL, 29, 1, '2025-11-17 18:31:29'),
(49, 19, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-17 18:35:55'),
(50, 17, '🎉 Congratulations! Your verification request has been approved. You are now a verified user!', 'verification_approved', NULL, NULL, NULL, 1, '2025-11-18 01:22:00'),
(51, 20, 'Violet17 sent you a message about brobro', 'new_message', NULL, NULL, 31, 1, '2025-11-18 07:49:08'),
(52, 17, 'frankocean01 sent you a message about brobro', 'new_message', NULL, NULL, 31, 1, '2025-11-18 07:49:20'),
(53, 20, 'New order received for brobro from Violet17. Order #13', 'new_order', 13, NULL, NULL, 1, '2025-11-18 07:50:33'),
(54, 17, 'Your order #13 for brobro has been confirmed by the seller.', 'order_confirmed', 13, NULL, NULL, 1, '2025-11-18 07:51:54'),
(55, 17, 'Great news! Your order #13 for brobro has been shipped!', 'order_shipped', 13, NULL, NULL, 1, '2025-11-18 07:52:10'),
(56, 17, 'Your order #13 for brobro has been delivered successfully! Enjoy your new pet! 🎉', 'order_delivered', 13, NULL, NULL, 1, '2025-11-18 07:52:50'),
(57, 20, 'Violet17 sent you a message about kiddy', 'new_message', NULL, NULL, 32, 1, '2025-11-18 08:04:53'),
(58, 17, 'frankocean01 sent you a message about kiddy', 'new_message', NULL, NULL, 32, 1, '2025-11-18 08:05:04'),
(59, 20, 'Violet17 sent you a message about kiddy', 'new_message', NULL, NULL, 32, 1, '2025-11-18 08:05:13'),
(60, 17, 'frankocean01 sent you a message about kiddy', 'new_message', NULL, NULL, 32, 1, '2025-11-18 08:05:22'),
(61, 20, 'New order received for kiddy from Violet17. Order #14', 'new_order', 14, NULL, NULL, 1, '2025-11-18 08:11:30'),
(62, 17, 'Your order #14 for kiddy has been confirmed by the seller.', 'order_confirmed', 14, NULL, NULL, 1, '2025-11-18 08:11:54'),
(63, 17, 'Great news! Your order #14 for kiddy has been shipped!', 'order_shipped', 14, NULL, NULL, 1, '2025-11-18 08:12:05'),
(64, 17, 'Your order #14 for kiddy has been delivered successfully! Payment completed. Enjoy your new pet! 🎉', 'order_delivered', 14, NULL, NULL, 1, '2025-11-18 08:12:22'),
(65, 20, 'New order received for You and I from Violet17. Order #15', 'new_order', 15, NULL, NULL, 1, '2025-11-18 08:19:20'),
(66, 17, 'Your order #15 for You and I has been confirmed by the seller.', 'order_confirmed', 15, NULL, NULL, 1, '2025-11-18 08:19:29'),
(67, 17, 'Great news! Your order #15 for You and I has been shipped!', 'order_shipped', 15, NULL, NULL, 1, '2025-11-18 08:19:38'),
(68, 17, 'Your order #15 for You and I has been delivered successfully! Payment completed. Enjoy your new pet! 🎉', 'order_delivered', 15, NULL, NULL, 1, '2025-11-18 08:19:46'),
(69, 20, 'New order received for tiramisu from Violet17. Order #16', 'new_order', 16, NULL, NULL, 1, '2025-11-18 08:33:23'),
(70, 17, 'Your order #16 for tiramisu has been cancelled by the seller. Payment has been cancelled.', 'order_cancelled', 16, NULL, NULL, 1, '2025-11-18 08:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('Pending','Paid','Shipped','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `seller_id`, `pet_id`, `total_amount`, `full_name`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_method`, `payment_status`, `notes`, `order_date`, `status`, `created_at`, `updated_at`) VALUES
(11, 5, 2, 13, 1975.00, 'monk', 'monk@yahoo.com', '01239123', 'Sicogon Street, Villa Josefina, Dumoy, Talomo District, Davao City, Davao Region, 8000, Philippines', 'Davao City', '1231', 'cod', 'pending', 'Thanks in advance', '2025-11-01 19:48:52', '', '2025-11-01 11:48:52', '2025-11-01 20:10:15'),
(12, 18, 2, 14, 1234.00, 'Vangeance', 'Vangeance@gmail.com', '123123123', 'qwe', 'qwe', '123123', 'cod', 'pending', 'yes', '2025-11-10 19:13:10', '', '2025-11-10 11:13:10', '2025-11-10 19:14:39'),
(13, 17, 20, 31, 5600.00, 'Violet17', 'Violet17@gmail.com', '09678546274', 'Santa Ana National High School, D. Suazo Street, Barangay 28-C, Poblacion District, Davao City, Davao Region, 8000, Philippines', 'davao city', '2500', 'cod', 'pending', 'pa selopin lang boss', '2025-11-18 07:50:33', '', '2025-11-17 23:50:33', '2025-11-18 07:52:50'),
(14, 17, 20, 32, 11000.00, 'Violet17', 'Violet17@gmail.com', '09123456789', 'Yllana Bay Street, Gulf View Executive Homes, Davao City, Philippines', 'davao city', '2500', 'cod', 'completed', 'ayaw lang i selopin boss salamat', '2025-11-18 08:11:30', '', '2025-11-18 00:11:30', '2025-11-18 08:12:22'),
(15, 17, 20, 33, 123.00, 'Violet17', 'Violet17@gmail.com', '09678546274', 'Santa Ana National High School, D. Suazo Street, Barangay 28-C, Poblacion District, Davao City, Davao Region, 8000, Philippines', '124', '2500', 'cod', 'completed', '', '2025-11-18 08:19:20', '', '2025-11-18 00:19:20', '2025-11-18 08:19:46'),
(16, 17, 20, 34, 124124.00, 'Violet17', 'Violet17@gmail.com', '09678546274', 'Santa Ana National High School, D. Suazo Street, Barangay 28-C, Poblacion District, Davao City, Davao Region, 8000, Philippines', 'davao city', '1234', 'cod', 'cancelled', '', '2025-11-18 08:33:23', 'Cancelled', '2025-11-18 00:33:23', '2025-11-18 08:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `changed_by`, `changed_at`) VALUES
(1, 11, 'confirmed', 2, '2025-11-01 20:03:41'),
(2, 11, 'shipped', 2, '2025-11-01 20:03:57'),
(3, 11, 'delivered', 2, '2025-11-01 20:04:10'),
(4, 11, 'delivered', 2, '2025-11-01 20:10:15'),
(5, 12, 'confirmed', 2, '2025-11-10 19:14:06'),
(6, 12, 'shipped', 2, '2025-11-10 19:14:20'),
(7, 12, 'delivered', 2, '2025-11-10 19:14:39'),
(8, 13, 'confirmed', 20, '2025-11-18 07:51:54'),
(9, 13, 'shipped', 20, '2025-11-18 07:52:10'),
(10, 13, 'delivered', 20, '2025-11-18 07:52:50'),
(11, 14, 'confirmed', 20, '2025-11-18 08:11:54'),
(12, 14, 'shipped', 20, '2025-11-18 08:12:05'),
(13, 14, 'delivered', 20, '2025-11-18 08:12:22'),
(14, 15, 'confirmed', 20, '2025-11-18 08:19:29'),
(15, 15, 'shipped', 20, '2025-11-18 08:19:38'),
(16, 15, 'delivered', 20, '2025-11-18 08:19:46'),
(17, 16, 'cancelled', 20, '2025-11-18 08:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `gender` varchar(10) NOT NULL DEFAULT 'Male',
  `age` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `vaccinated` tinyint(1) DEFAULT 0,
  `neutered` tinyint(1) DEFAULT 0,
  `health_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `user_id`, `name`, `type`, `breed`, `gender`, `age`, `description`, `price`, `status`, `image`, `created_at`, `latitude`, `longitude`, `address`, `vaccinated`, `neutered`, `health_status`) VALUES
(1, 2, ' Pham Hanni', 'cat', 'Persian Otter', 'Male', '21', 'I love her so much', 17.00, 'sold', '1755753546_0d671b9ff913bda362b57c63b3bae486.jpg', '2025-08-21 05:19:06', NULL, NULL, NULL, 0, 0, NULL),
(2, 2, 'kitty', 'cat', 'idk', 'Male', '4', 'sad', 4.00, 'sold', '1755754090_93612b1be5083b820924bb851dbeaaf5.jpg', '2025-08-21 05:28:10', NULL, NULL, NULL, 0, 0, NULL),
(3, 2, 'sakurajima', 'cat', 'bunny', 'Male', '17', 'a cute bunny girl', 100.00, 'sold', '1755775398_f797d4d42a1fffdc48c3427ba0176c2e.jpg', '2025-08-21 11:23:18', NULL, NULL, NULL, 0, 0, NULL),
(4, 2, 'yorushika', 'cat', 'japanese', 'Male', '20', 'I love this artist', 3.00, 'sold', NULL, '2025-08-27 04:58:34', NULL, NULL, NULL, 0, 0, NULL),
(7, 2, 'Ngoc Han', 'cat', 'Ino', 'Male', '99', 'crush ko tong eabab na to', 169.00, 'sold', NULL, '2025-08-27 05:05:25', NULL, NULL, NULL, 0, 0, NULL),
(8, 2, 'summer', 'cat', 'Persian Otter', 'Male', '21', 'shiny', 888.00, 'sold', NULL, '2025-09-07 06:39:30', NULL, NULL, NULL, 0, 0, NULL),
(10, 2, 'Caroline', 'cat', 'American Shorthair', 'Male', '5', 'music lover', 4000.00, 'sold', NULL, '2025-09-20 01:19:24', NULL, NULL, NULL, 0, 0, NULL),
(11, 2, 'Shibal', '', 'Maine Coon', 'Male', '17', 'Part of the band', 2000.00, 'sold', NULL, '2025-09-20 01:36:16', '', '0', '', 0, 0, NULL),
(12, 2, 'becky', '', 'Bombay', 'Male', '2', 'bading', 9999.00, 'sold', NULL, '2025-09-20 01:58:59', '7.0703101153421635', '125.61263322830202', 'Christ for All Nations Cathedral, 202, M. Roxas Avenue, Barangay 34-D, Poblacion District, Davao City, Davao Region, 8000, Philippines', 0, 0, NULL),
(13, 2, 'Posang cute', '', 'Cornish Rex', 'Male', '4', 'Human too', 1975.00, 'sold', NULL, '2025-09-20 02:02:36', '', '0', '', 0, 0, NULL),
(14, 2, 'mamamiming', '', 'American Curl', 'Male', '4', 'mother', 1234.00, 'sold', NULL, '2025-09-20 02:24:30', '7.0351976383323604', '125.51097214221956', 'Capili Subdivision, Dumoy, Talomo District, Davao City, Davao Region, 8000, Philippines', 0, 0, NULL),
(15, 2, 'You and I', '', 'Burmese', 'Male', '5', 'to the sky', 123213.00, 'sold', NULL, '2025-09-20 02:29:45', '7.044855327992125', '125.53578794002534', 'Caribbean Sea Street, Gulf View Executive Homes, Bago Aplaya, Talomo District, Davao City, Davao Region, 8000, Philippines', 0, 0, NULL),
(16, 18, 'kiki', '', 'American Curl', 'Male', '3', 'Good healthy cat', 2003.00, 'sold', NULL, '2025-11-02 18:54:38', '7.027037659022466', '125.5145287513733', 'Cuyo Street, Villa Josefina, Dumoy, Talomo District, Davao City, Davao Region, 8000, Philippines', 0, 0, NULL),
(17, 2, 'Phampham', '', 'American Curl', 'Female', '2', 'cuteness overload', 1500.00, 'sold', NULL, '2025-11-10 15:12:46', '7.084354658450104', '125.61985128104303', 'Alaska Street, Dacudao, Paciano Bangoy, Agdao District, Davao City, Davao Region, 8000, Philippines', 1, 1, 'yes'),
(18, 2, 'sushi', '', 'Japanese Bobtail', 'Female', '2', 'literal sushi', 1200.00, 'sold', NULL, '2025-11-13 15:47:22', '', '0', 'Could not get address', 1, 1, 'healthy'),
(19, 2, 'sushi', '', 'Japanese Bobtail', 'Female', '2', 'literal sushi', 1200.00, 'sold', NULL, '2025-11-13 15:54:57', '', '0', 'Could not get address', 1, 1, 'healthy'),
(20, 23, 'oreo', '', 'Exotic Shorthair', 'Male', '2', 'black and white fur', 3500.00, 'available', NULL, '2025-11-14 12:01:10', '7.0428677', '125.5357794', 'Yllana Bay Street, Gulf View Executive Homes, Davao City, Philippines', 1, 1, 'no illness'),
(21, 21, 'cece', '', 'Turkish Angora', 'Female', '2', 'furry', 5600.00, 'available', NULL, '2025-11-15 12:10:07', '7.0647808', '125.5440384', 'Location: 7.064781, 125.544038', 1, 1, ' healthy'),
(22, 21, 'macay', '', 'American Bobtail', 'Male', '4', 'playful', 1200.00, 'available', NULL, '2025-11-15 12:14:53', '7.121963030889703', '125.64252376556398', 'Location: 7.121963, 125.642524', 1, 1, 'healthy'),
(23, 21, 'choco', '', 'Turkish Angora', 'Male', '1', 'playful', 1600.00, 'available', NULL, '2025-11-15 12:25:27', '7.0620318', '125.53925', 'BRC Village, Davao City, Philippines', 1, 1, 'no health problems'),
(24, 21, 'kiwi', '', 'Persian', 'Female', '1', 'always sleepy', 6700.00, 'available', NULL, '2025-11-15 12:28:20', '7.0632599', '125.5449182', 'Talomo-Puan Bypass Road, San Vicente Village, Davao City, Philippines', 1, 1, 'healthy'),
(25, 21, 'sam', '', 'Persian', 'Female', '3', 'playful', 6700.00, 'available', NULL, '2025-11-15 12:34:15', '7.0632599', '125.5449182', 'Talomo-Puan Bypass Road, San Vicente Village, Davao City, Philippines', 1, 1, 'no health issues'),
(26, 21, 'Shiloh', '', 'Persian', 'Female', '1', 'fluffy', 8700.00, 'available', NULL, '2025-11-15 12:38:28', '7.0658596', '125.5501738', 'Seminary Road, Hope Avenue, Davao City, Philippines', 1, 1, 'good health'),
(27, 21, 'Liam', '', 'British Longhair', 'Male', '2', 'fluffy tail', 10999.00, 'available', NULL, '2025-11-15 12:41:08', '7.0647808', '125.5440384', 'Location: 7.064781, 125.544038', 1, 1, 'good condition'),
(28, 21, 'tracy', '', 'Birman', 'Female', '1', 'fluffy', 12000.00, 'available', NULL, '2025-11-15 12:43:55', '7.0632599', '125.5449182', 'Talomo-Puan Bypass Road, San Vicente Village, Davao City, Philippines', 1, 1, 'healthy'),
(29, 21, 'Riyakitty', '', 'Birman', 'Female', '4', 'cute mommy cat', 13000.00, 'available', NULL, '2025-11-15 12:46:44', '7.0632599', '125.5449182', 'Talomo-Puan Bypass Road, San Vicente Village, Davao City, Philippines', 1, 1, 'good health'),
(31, 20, 'brobro', '', 'American Shorthair', 'Male', '2', 'cutie patootie', 5600.00, 'sold', NULL, '2025-11-17 23:48:27', '7.0582272', '125.5931904', 'Location: 7.058227, 125.593190', 1, 1, 'good haealthy cat so furry and so yes yes AKNeiuda,mshekljwkasdkjjkahwjem  asdakwhekla.s,dlas,d a.,sdnkamwewae '),
(32, 20, 'kiddy', '', 'American Curl', 'Male', '1', 'furry', 11000.00, 'sold', NULL, '2025-11-18 00:04:39', '7.0582272', '125.5931904', 'Location: 7.058227, 125.593190', 1, 1, 'healthy'),
(33, 20, 'You and I', '', 'American Bobtail', 'Male', '4', '213', 123.00, 'sold', NULL, '2025-11-18 00:19:06', '7.143743016140417', '125.5342483520508', 'Location: 7.143743, 125.534248', 1, 1, 'd'),
(34, 20, 'tiramisu', '', 'American Curl', 'Male', '1', 'qwe', 124124.00, 'available', NULL, '2025-11-18 00:32:47', '7.0581224', '125.5926045', '30, Carlos Avenue, 74-A Matina Crossing, Davao City, Philippines', 1, 0, 'qwe'),
(35, 20, 'lopers', '', 'American Shorthair', 'Male', '17', 'yes', 250.00, 'available', NULL, '2025-11-18 00:40:48', '7.0582272', '125.5931904', 'Location: 7.058227, 125.593190', 1, 1, 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `pet_comments`
--

CREATE TABLE `pet_comments` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_comments`
--

INSERT INTO `pet_comments` (`id`, `pet_id`, `user_id`, `comment_text`, `parent_id`, `created_at`) VALUES
(1, 19, 2, 'yes', NULL, '2025-11-14 00:58:41'),
(2, 19, 20, 'no', 1, '2025-11-14 01:01:39'),
(3, 19, 20, 'ofcourse', 1, '2025-11-14 01:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `pet_images`
--

CREATE TABLE `pet_images` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pet_images`
--

INSERT INTO `pet_images` (`id`, `pet_id`, `filename`) VALUES
(1, 4, '1756270714_07fc6a08e3ccc9105a466e25c9925aae.jpg'),
(2, 4, '1756270714_3335761927dee97d71592a21b1458bf1.jpg'),
(3, 4, '1756270714_d9fd297eeb4063f3cf107f8f006f5039.jpg'),
(4, 7, '1756271125_0d671b9ff913bda362b57c63b3bae486.jpg'),
(5, 7, '1756271125_48619f5227928df9c637559a46f1f24d - Copy.jpg'),
(6, 7, '1756271125_48619f5227928df9c637559a46f1f24d.jpg'),
(7, 7, '1756271125_b66a1d48091246cca3e2992bb78b4524.jpg'),
(8, 8, '1757227170_07fc6a08e3ccc9105a466e25c9925aae.jpg'),
(9, 8, '1757227170_3335761927dee97d71592a21b1458bf1 - Copy.jpg'),
(10, 8, '1757227170_d9fd297eeb4063f3cf107f8f006f5039.jpg'),
(13, 10, '1758331164_402471d047d9c21bae814cc3fa7e91a9.jpg'),
(14, 10, '1758331164_c14232705bbcc5c339b7632fd42dcc4c.jpg'),
(15, 11, '1758332176_539d164fbf5e27f589aeeab811619d97.jpg'),
(16, 11, '1758332176_402471d047d9c21bae814cc3fa7e91a9.jpg'),
(17, 12, '1758333539_402471d047d9c21bae814cc3fa7e91a9.jpg'),
(18, 12, '1758333539_cd2672ffd09bcb2fc57ed95f7343f2b3.jpg'),
(19, 13, '1758333756_402471d047d9c21bae814cc3fa7e91a9.jpg'),
(20, 13, '1758333756_c14232705bbcc5c339b7632fd42dcc4c.jpg'),
(21, 13, '1758333756_cd2672ffd09bcb2fc57ed95f7343f2b3.jpg'),
(22, 14, '1758335070_a04b098425d2bfff62af0ac91ec3f3df.jpg'),
(23, 14, '1758335070_c14232705bbcc5c339b7632fd42dcc4c.jpg'),
(24, 14, '1758335070_cd2672ffd09bcb2fc57ed95f7343f2b3.jpg'),
(25, 14, '1758335070_f71f6fdca6fd8823cb34e345c3cbec2a.jpg'),
(26, 15, '1758335385_cd2672ffd09bcb2fc57ed95f7343f2b3.jpg'),
(27, 15, '1758335385_f71f6fdca6fd8823cb34e345c3cbec2a.jpg'),
(28, 16, '1762109678_58851fb848a7361f52d22e3eba20eb10.jpg'),
(29, 16, '1762109678_ff559ba0c9eac235a8c6e60f6511a02e.jpg'),
(30, 17, '1762787566_asd.jpg'),
(31, 17, '1762787566_ebb3f2a4774a823b2403c10d935aba33.jpg'),
(32, 18, '1763048842_279bcc848f2739549d40e67eb2bad27f.jpg'),
(33, 18, '1763048842_674d1da341390c06f24b38685a26d958.jpg'),
(34, 19, '1763049297_279bcc848f2739549d40e67eb2bad27f.jpg'),
(35, 19, '1763049297_674d1da341390c06f24b38685a26d958.jpg'),
(36, 20, '1763121670_oreo.jpg'),
(37, 20, '1763121670_oreo1.webp'),
(38, 21, '1763208607_cece1.jpg'),
(39, 21, '1763208607_cece2.jpg'),
(40, 22, '1763208893_macay2.jpg'),
(41, 22, '1763208893_macay3.jpg'),
(42, 23, '1763209527_choco.jpg'),
(43, 23, '1763209527_choco2.jpg'),
(44, 24, '1763209700_kiwi1.jpg'),
(45, 24, '1763209700_kiwi2.webp'),
(46, 25, '1763210055_sam1.jpg'),
(47, 25, '1763210055_sam2.jpg'),
(48, 26, '1763210308_Shiloh1.jpg'),
(49, 26, '1763210308_Shiloh2.jpg'),
(50, 27, '1763210468_Liam1.jpg'),
(51, 27, '1763210468_Liam2.jpg'),
(52, 28, '1763210635_tracy1.jpg'),
(53, 28, '1763210635_tracy2.jpg'),
(54, 29, '1763210804_Riyakitty.jpg'),
(55, 29, '1763210804_Riyakitty2.jpg'),
(56, 29, '1763210804_Riyakitty3.jpg'),
(59, 31, '1763423307_279bcc848f2739549d40e67eb2bad27f.jpg'),
(60, 31, '1763423307_674d1da341390c06f24b38685a26d958.jpg'),
(61, 32, '1763424279_asd.jpg'),
(62, 32, '1763424279_ebb3f2a4774a823b2403c10d935aba33.jpg'),
(63, 33, '1763425146_asd.jpg'),
(64, 33, '1763425146_db66e1c85f95abcc5ea2373244763458.jpg'),
(65, 34, '1763425967_asd.jpg'),
(66, 34, '1763425967_db66e1c85f95abcc5ea2373244763458.jpg'),
(67, 35, '1763426448_db66e1c85f95abcc5ea2373244763458.jpg'),
(68, 35, '1763426448_oreo1.webp');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('cat','accessory') DEFAULT 'cat',
  `breed` varchar(120) DEFAULT NULL,
  `age_months` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `breed`, `age_months`, `stock`, `image`, `created_at`) VALUES
(1, 'Mochi', 'Playful Scottish Fold kitten', 12000.00, 'cat', 'Scottish Fold', 3, 1, 'mochi.jpg', '2025-08-19 06:13:50'),
(2, 'Luna', 'Calm Ragdoll, vaccinated', 15000.00, 'cat', 'Ragdoll', 7, 1, 'luna.jpg', '2025-08-19 06:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `id_image` varchar(255) DEFAULT NULL,
  `verification_status` enum('not verified','pending','verified') DEFAULT 'not verified',
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `role`, `created_at`, `verified`, `id_image`, `verification_status`, `profile_pic`) VALUES
(1, '', '', 'admin', 'admin@catshop.local', '$2y$10$zNjCOsEoUGomTXinPC01AObTdD6Ikhm2GnkbwsJDi36mcuhGa1IlS', 'admin', '2025-08-19 06:20:03', 0, NULL, 'pending', NULL),
(2, '', '', 'test', 'test@gmail.com', '$2y$10$MBuWxPGVcV8V5qoebUYq/uJmEouf77HB8FGjB8AhfIA/WEVSYGTy2', 'customer', '2025-08-19 06:20:34', 0, NULL, 'verified', 'profile_pics/1761993833_6ce107dbe303c5d1cce1bf5bdc092fb7.jpg'),
(5, '', '', 'monk', 'monk@yahoo.com', '$2y$10$fMkQsIzak7CivpNkKMqG9OYhUosnXgiNKokLQOI2EYVBk7.09ckmC', 'customer', '2025-08-21 11:48:53', 0, NULL, 'pending', NULL),
(9, 'newest', 'tester', 'new west', 'newest@gmail.com', '$2y$10$DVf7LvZUmLntssxUA3oqne4FVVQ7aPXuzTBwAdR5bBZRc.PsuQ4x.', 'customer', '2025-09-29 06:32:33', 0, NULL, 'pending', NULL),
(16, 'Hanni', 'Pham', 'hanni', 'hanni@gmail.com', '$2y$10$uCYGdrDxh7OcO/h3lfOmj.NVhZysxET26W16yvkN3Kqmnqvyp7mXO', 'customer', '2025-10-22 06:24:23', 1, NULL, 'pending', NULL),
(17, 'Violet', 'Evergarden', 'Violet17', 'Violet17@gmail.com', '$2y$10$CfVZ6agc3lrdza9h9ettC.m/2jWR5wkq9rVlIDMYVG2p6qY.M45p6', 'customer', '2025-11-02 18:13:19', 0, NULL, 'verified', NULL),
(18, 'Willam', 'Vangeance', 'Vangeance', 'Vangeance@gmail.com', '$2y$10$MooigHf4.HLnnT1e7J.1HOAlP4RLQoU/F1JJNtxINfv0015e9YgQi', 'customer', '2025-11-02 18:30:56', 0, NULL, 'verified', NULL),
(19, 'king', 'kong', 'kingkong', 'king@gmail.com', '$2y$10$rUD7nHvk/tiqmN5DqgM4pedRt.TueyLxLjq6fdj/nTz3RhutAqeU.', 'customer', '2025-11-09 13:24:15', 0, NULL, 'verified', NULL),
(20, 'Frank', 'Ocean', 'frankocean01', 'frankocean@gmail.com', '$2y$10$hFevtY1O2q3JHtO7EkFQLOlP.wgt45rxIDm9fdgoW0c.5LtRf/Zsq', 'customer', '2025-11-10 23:30:55', 0, NULL, 'verified', NULL),
(21, 'joseph', 'joestar', 'jojo', 'jojo@gmail.com', '$2y$10$3yEahY/I35ugQo0Oa58nZenq/pLZ0M4neaDgHILvElFY/oMVc93WG', 'customer', '2025-11-10 23:32:13', 0, NULL, 'verified', NULL),
(22, 'reaver', 'vandal', 'reaver', 'vandal@gmail.com', '$2y$10$OF9UQycoSS2sGTtpWQ9vCeWeANuKAF9aXi8G/QJE5XVTZIbXm1Qhe', 'customer', '2025-11-13 03:49:52', 0, NULL, 'not verified', NULL),
(23, 'john', 'miller', 'johnmiller', 'johnmiller@gmail.com', '$2y$10$4NwRUnLVn0agoyseMZTttegVXJX4ZRUZvPDlxzC5bp1zaRagNeowu', 'customer', '2025-11-14 11:01:05', 0, NULL, 'verified', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `verifications`
--

CREATE TABLE `verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_image` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verifications`
--

INSERT INTO `verifications` (`id`, `user_id`, `id_image`, `status`, `created_at`) VALUES
(1, 2, 'valid_id_2_1761112791.jpg', 'Rejected', '2025-10-22 05:59:51'),
(8, 16, 'valid_id_16_1761114274.jpg', 'Approved', '2025-10-22 06:24:34'),
(9, 2, 'valid_id_2_1762693603.jpg', 'Approved', '2025-11-09 13:06:43'),
(10, 19, 'valid_id_19_1762694838.jpg', 'Rejected', '2025-11-09 13:27:18'),
(11, 19, 'valid_id_19_1762694955.jpg', 'Approved', '2025-11-09 13:29:15'),
(12, 19, 'valid_id_19_1762721427.jpg', 'Approved', '2025-11-09 20:50:27'),
(13, 19, 'valid_id_19_1762722727.jpg', 'Approved', '2025-11-09 21:12:07'),
(14, 18, 'valid_id_18_1762772977.jpg', 'Approved', '2025-11-10 11:09:37'),
(15, 21, 'valid_id_21_1762818335.jpg', 'Approved', '2025-11-10 23:45:35'),
(16, 20, 'valid_id_20_1762819218.jpg', 'Rejected', '2025-11-11 00:00:18'),
(17, 20, 'valid_id_20_1762819526.jpg', 'Rejected', '2025-11-11 00:05:26'),
(18, 20, 'valid_id_20_1762819834.jpg', 'Approved', '2025-11-11 00:10:34'),
(19, 2, 'valid_id_2_1763048552.jpg', 'Approved', '2025-11-13 15:42:32'),
(20, 23, 'valid_id_23_1763118082.jpg', 'Approved', '2025-11-14 11:01:22'),
(21, 19, 'valid_id_19_1763375742.jpg', 'Approved', '2025-11-17 10:35:42'),
(22, 17, 'valid_id_17_1763400105.jpg', 'Approved', '2025-11-17 17:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `pet_id`, `created_at`) VALUES
(17, 2, 4, '2025-11-12 23:57:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adoption_applications`
--
ALTER TABLE `adoption_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `adoption_appointments`
--
ALTER TABLE `adoption_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `adoption_cats`
--
ALTER TABLE `adoption_cats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adoption_cat_images`
--
ALTER TABLE `adoption_cat_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`pet_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_feedback` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_order` (`order_id`),
  ADD KEY `fk_items_product` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_at` (`changed_at`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pet_comments`
--
ALTER TABLE `pet_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `pet_images`
--
ALTER TABLE `pet_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `verifications`
--
ALTER TABLE `verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`pet_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adoption_applications`
--
ALTER TABLE `adoption_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `adoption_appointments`
--
ALTER TABLE `adoption_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adoption_cats`
--
ALTER TABLE `adoption_cats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `adoption_cat_images`
--
ALTER TABLE `adoption_cat_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `pet_comments`
--
ALTER TABLE `pet_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pet_images`
--
ALTER TABLE `pet_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `verifications`
--
ALTER TABLE `verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adoption_applications`
--
ALTER TABLE `adoption_applications`
  ADD CONSTRAINT `adoption_applications_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `adoption_cats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `adoption_appointments`
--
ALTER TABLE `adoption_appointments`
  ADD CONSTRAINT `adoption_appointments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `adoption_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `adoption_cat_images`
--
ALTER TABLE `adoption_cat_images`
  ADD CONSTRAINT `adoption_cat_images_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `adoption_cats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pet_comments`
--
ALTER TABLE `pet_comments`
  ADD CONSTRAINT `pet_comments_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pet_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pet_comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `pet_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pet_images`
--
ALTER TABLE `pet_images`
  ADD CONSTRAINT `pet_images_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verifications`
--
ALTER TABLE `verifications`
  ADD CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
