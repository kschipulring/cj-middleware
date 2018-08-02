-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2018 at 10:30 PM
-- Server version: 10.1.30-MariaDB
-- PHP Version: 5.6.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `adgt`
--

-- --------------------------------------------------------

--
-- Table structure for table `exp_temp_tags`
--

CREATE TABLE `exp_temp_tags` (
  `OWNER_GROUP_ID` int(11) DEFAULT NULL,
  `TAG_ID` int(11) NOT NULL,
  `TAG_TYPE` varchar(32) DEFAULT NULL,
  `TAG_NAME` varchar(64) NOT NULL,
  `insert_time_gmt` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exp_temp_tags`
--
ALTER TABLE `exp_temp_tags`
  ADD PRIMARY KEY (`TAG_ID`),
  ADD UNIQUE KEY `TAG_ID` (`TAG_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
