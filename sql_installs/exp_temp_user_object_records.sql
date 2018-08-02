-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2018 at 02:27 PM
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
-- Table structure for table `exp_temp_user_object_records`
--

CREATE TABLE `exp_temp_user_object_records` (
  `SCORE` float DEFAULT NULL,
  `COMPLETED_TS` varchar(255) DEFAULT NULL,
  `USER_ID` int(11) NOT NULL,
  `BATCH_ID` int(11) DEFAULT NULL,
  `STATUS` varchar(255) DEFAULT NULL,
  `OBJECT_ID` int(11) NOT NULL,
  `ENTRY_NO` int(11) DEFAULT NULL,
  `PASS_YN` tinyint(1) DEFAULT NULL,
  `GRANDFATHER_ID` int(11) DEFAULT NULL,
  `PARENT_RECORD_ID` int(11) DEFAULT NULL,
  `RECORD_ID` int(11) NOT NULL,
  `VM_ATTENDEE_ID` int(11) DEFAULT NULL,
  `TOTAL_SECS_TRACKED` int(11) DEFAULT NULL,
  `START_TS` varchar(255) DEFAULT NULL,
  `insert_time_gmt` varchar(255) NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `exp_temp_user_object_records`
--
ALTER TABLE `exp_temp_user_object_records`
  ADD PRIMARY KEY (`RECORD_ID`),
  ADD UNIQUE KEY `RECORD_ID` (`RECORD_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
