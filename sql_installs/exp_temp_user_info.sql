-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2018 at 08:11 PM
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
-- Table structure for table `exp_temp_user_info`
--

CREATE TABLE `exp_temp_user_info` (
  `CURRENCY` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VENDOR_USER_ID` int(11) DEFAULT NULL,
  `PHONE` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `JOB_TITLE` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `USER_ID` int(11) NOT NULL,
  `USER_AVATAR` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AUX_FIELD_NAMES` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TAX_PERCENT` decimal(2,2) DEFAULT NULL,
  `EMAIL` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LAST_NAME` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FIRST_NAME` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `STATUS` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ADDED_ON` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `GMT_OFFSET` decimal(2,2) DEFAULT NULL,
  `ML Trainee Schedule Option` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `USERNAME` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `MIDDLE_NAME` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ADDED_BY` int(11) DEFAULT NULL,
  `LANGUAGE_ID` int(11) DEFAULT NULL,
  `AUX_FIELD_VALUES` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EXPIRES_ON` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `primary_yn` int(1) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `insert_time_gmt` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `exp_temp_user_info`
--

INSERT INTO `exp_temp_user_info` (`CURRENCY`, `VENDOR_USER_ID`, `PHONE`, `JOB_TITLE`, `USER_ID`, `USER_AVATAR`, `AUX_FIELD_NAMES`, `TAX_PERCENT`, `EMAIL`, `LAST_NAME`, `FIRST_NAME`, `STATUS`, `ADDED_ON`, `GMT_OFFSET`, `ML Trainee Schedule Option`, `USERNAME`, `MIDDLE_NAME`, `ADDED_BY`, `LANGUAGE_ID`, `AUX_FIELD_VALUES`, `EXPIRES_ON`, `group_id`, `primary_yn`, `role_id`, `insert_time_gmt`) VALUES
('', 0, '', '', 11016, '', 'ML Trainee Schedule Option', '0.00', '', 'test', 'Irena', 'Active', '2016-10-07 14:25:08.993', '-0.99', 'Permanent Full Time', 'irena_retail', '', 1, 1, 'Permanent Full Time', '', 0, 0, 0, 'Mar, 12 2018 19:00:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exp_temp_user_info`
--
ALTER TABLE `exp_temp_user_info`
  ADD PRIMARY KEY (`USER_ID`),
  ADD UNIQUE KEY `USER_ID` (`USER_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
