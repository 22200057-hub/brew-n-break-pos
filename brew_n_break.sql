-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 06:32 PM
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
-- Database: `brew_n_break`
--

-- --------------------------------------------------------

--
-- Table structure for table `billiard_sessions`
--

CREATE TABLE `billiard_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_code` varchar(30) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `table_name` varchar(60) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hours_used` decimal(5,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billiard_sessions`
--

INSERT INTO `billiard_sessions` (`id`, `session_code`, `customer_name`, `table_name`, `start_time`, `end_time`, `hours_used`, `amount`, `status`, `created_at`) VALUES
(36, 'BT001', 'justine mislang', 'Outdoor 1', '22:32:00', '23:32:00', 0.00, 100.00, 'Done', '2026-05-22 14:32:41'),
(37, 'BT002', 'Jrf', 'Outdoor 2', '22:35:00', '23:35:00', 0.00, 100.00, 'Done', '2026-05-22 14:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `billiard_tables`
--

CREATE TABLE `billiard_tables` (
  `id` int(10) UNSIGNED NOT NULL,
  `table_name` varchar(60) NOT NULL,
  `status` enum('Available','Reserved','Occupied') DEFAULT 'Available',
  `hours_left` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billiard_tables`
--

INSERT INTO `billiard_tables` (`id`, `table_name`, `status`, `hours_left`) VALUES
(1, 'Outdoor 1', 'Reserved', NULL),
(2, 'Outdoor 2', 'Available', NULL),
(3, 'Outdoor 3', 'Reserved', NULL),
(4, 'Indoor 1', 'Available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_code` varchar(30) NOT NULL,
  `guest_name` varchar(120) NOT NULL,
  `room` varchar(60) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `status` varchar(30) DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `guest_name`, `room`, `check_in`, `check_out`, `status`, `created_at`) VALUES
(12, 'BK001', 'hehe', 'Airbnb', '2026-05-22', '2026-05-24', 'Confirmed', '2026-05-22 14:36:45'),
(13, 'BK002', 'asdasddsaads', 'Airbnb', '2026-05-26', '2026-05-28', 'Confirmed', '2026-05-22 15:42:57'),
(14, 'BK003', 'dfgdfgdfg', 'Airbnb', '2026-05-30', '2026-05-31', 'Confirmed', '2026-05-22 15:48:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(30) NOT NULL,
  `type` varchar(30) DEFAULT 'Coffee',
  `status` varchar(30) DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `type`, `status`, `total_amount`, `created_at`) VALUES
(16, 'ORDR001', 'Coffee', 'Done', 160.00, '2026-05-22 14:18:24'),
(17, 'ORDR002', 'Coffee', 'Done', 180.00, '2026-05-22 14:28:24'),
(18, 'ORDR003', 'Coffee', 'Done', 110.00, '2026-05-22 14:28:41'),
(19, 'ORDR004', 'Coffee', 'Done', 140.00, '2026-05-22 14:28:45'),
(20, 'ORDR005', 'Coffee', 'Done', 160.00, '2026-05-22 14:28:50'),
(21, 'ORDR006', 'Coffee', 'Done', 150.00, '2026-05-22 14:28:54'),
(23, 'ORDR007', 'Foods', 'Done', 220.00, '2026-05-22 15:36:12'),
(24, 'ORDR008', 'Foods', 'Done', 190.00, '2026-05-22 15:38:24'),
(25, 'ORDR009', 'Coffee', 'Done', 140.00, '2026-05-22 15:43:41'),
(26, 'ORDR010', 'Foods', 'Done', 220.00, '2026-05-22 16:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(21, 16, 18, 1, 160.00),
(22, 17, 23, 1, 180.00),
(23, 18, 7, 1, 110.00),
(24, 19, 8, 1, 140.00),
(25, 20, 19, 1, 160.00),
(26, 21, 1, 1, 150.00),
(28, 23, 32, 1, 220.00),
(29, 24, 37, 1, 190.00),
(30, 25, 15, 1, 140.00),
(31, 26, 30, 1, 220.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(60) DEFAULT 'Coffee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` varchar(255) DEFAULT '',
  `available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `category`, `created_at`, `description`, `available`) VALUES
(1, 'Matcha Latte', 150.00, 'Coffee', '2026-04-30 18:58:48', '', 1),
(4, 'Solos Fries', 95.00, 'Foods', '2026-04-30 18:58:48', '', 1),
(5, 'Nachos', 120.00, 'Foods', '2026-04-30 18:58:48', '', 1),
(7, 'Americano', 110.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(8, 'Cappuccino', 140.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(9, 'Matcha Latte', 145.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(10, 'Café Mocha', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(11, 'White Mocha', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(12, 'Spanish Latte', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(13, 'Caramel Macchiato', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(14, 'Iced Americano', 110.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(15, 'Iced Latte', 140.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(16, 'Iced White Mocha', 150.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(17, 'Iced Mocha', 150.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(18, 'Almond Latte', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(19, 'Iced Biscoff Latte', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(20, 'Hazelnut Latte', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(22, 'Spanish Caramel Latte', 160.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(23, 'Oat Honey Latte', 180.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(24, 'Blueberry Milk', 140.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(25, 'Strawberry Milk', 140.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(26, 'Chocolate Milk', 150.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(27, 'Strawberry Matcha Latte', 170.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(28, 'Blueberry Matcha Latte', 170.00, 'Coffee', '2026-05-12 07:37:03', '', 1),
(29, 'Mojos', 180.00, 'Foods', '2026-05-13 13:03:19', '', 1),
(30, 'Cornsilog', 220.00, 'Foods', '2026-05-13 13:04:44', '', 1),
(31, 'Tosilog', 220.00, 'Foods', '2026-05-13 13:04:44', '', 1),
(32, 'Tapsilog', 220.00, 'Foods', '2026-05-13 13:04:44', '', 1),
(33, 'Longsilog', 220.00, 'Foods', '2026-05-13 13:04:44', '', 1),
(34, 'Hungsilog', 220.00, 'Foods', '2026-05-13 13:04:44', '', 1),
(35, 'Chicken Poppers', 220.00, 'Foods', '2026-05-13 13:05:43', '', 1),
(36, 'Pork & Shrimp Siomai', 150.00, 'Foods', '2026-05-13 13:06:05', '', 1),
(37, 'Toasted Ham & Egg Sandwich w/ Fries', 190.00, 'Foods', '2026-05-13 13:06:25', '', 1),
(38, 'Classic Cheeseburger w/ Fries', 190.00, 'Foods', '2026-05-13 13:06:44', '', 1),
(39, 'Barkada Fries', 150.00, 'Foods', '2026-05-13 13:07:18', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `report_code` varchar(30) NOT NULL,
  `type` varchar(60) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_code`, `type`, `date_from`, `date_to`, `created_at`) VALUES
(9, 'RID0001', 'Daily Report', '2026-04-22', '2026-05-22', '2026-05-22 14:38:11'),
(12, 'RID0002', 'Daily Report', '2026-04-22', '2026-05-22', '2026-05-22 15:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(120) DEFAULT '',
  `first_name` varchar(60) DEFAULT '',
  `last_name` varchar(60) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `photo` varchar(255) DEFAULT '',
  `status` enum('Active','Deactivated') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `name`, `first_name`, `last_name`, `phone`, `photo`, `status`) VALUES
(3, 'admin', '$2y$10$dgsjvG6Ffumo2oautl1XtOWMPsSjBg5uQoUeVhv9RTbz7GEDf4/dG', 'admin', '2026-04-30 19:02:58', 'Admin User', 'Admin', 'User', '+63 9451831321', '/uploads/profiles/user_3.jpg', 'Active'),
(8, 'staff', '$2y$10$KLW2CnJEjyS5JgWgtUX1LORA1cFAPIPUufeLEiqv/cCDG7fk9Uxgm', 'staff', '2026-05-13 08:05:57', 'Sana Minatozaki', 'Sana', 'Minatozaki', '+63 ', '/uploads/profiles/user_8.jpg', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billiard_sessions`
--
ALTER TABLE `billiard_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billiard_tables`
--
ALTER TABLE `billiard_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billiard_sessions`
--
ALTER TABLE `billiard_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `billiard_tables`
--
ALTER TABLE `billiard_tables`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
