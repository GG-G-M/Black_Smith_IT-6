-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2025 at 06:23 AM
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
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `created_at`, `last_login`, `is_active`) VALUES
(1, '1', '$2y$10$GU4XID0nn7H6BZG3h.VN1.CcATqbt2E3jbjzRAGfDZJqfiozP5VOG', 'Gilgre Gene', 'Mantilla', '2025-03-12 05:08:45', '2025-03-12 05:09:24', 1),
(2, '2', '$2y$10$rrdKGVVa4whPV7s.FwcQR.EXr9DtUtdl02iyIifM0.c4dGX/LNRT6', 'Jan Angel', 'Ayala', '2025-03-12 05:08:54', '2025-03-12 05:08:54', 1),
(3, '3', '$2y$10$DiVXUS22RBhIWw/zCU4rzuKaCM461rje8LVJckyfyCnqUiS3drQZO', 'Josh Andrei', 'Magcalas', '2025-03-12 05:09:03', '2025-03-12 05:09:03', 1),
(4, '4', '$2y$10$55IjM6V5sFbOt8cj4KHlzeDTaNkfN2ELK.caqQ4mowC1BpQ6PW/.W', 'Jhon Jan Raven', 'Canedo', '2025-03-12 05:09:10', '2025-03-12 05:09:10', 1),
(5, '5', '$2y$10$.5UrWmZiYaoMaGst29W6yOBEukUsjHNrZvo5YmSRI2dE7W0Aff.lW', 'Earl', 'Fructose', '2025-03-12 05:09:21', '2025-03-12 05:09:21', 1);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
