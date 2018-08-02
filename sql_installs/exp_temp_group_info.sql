-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2018 at 10:31 PM
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
-- Table structure for table `exp_temp_group_info`
--

CREATE TABLE `exp_temp_group_info` (
  `GROUP_NAME` varchar(255) DEFAULT NULL,
  `GROUP_ID` int(11) NOT NULL,
  `VENDOR_GROUP_ID` int(11) DEFAULT NULL,
  `GROUP_TYPE_ID` int(11) DEFAULT NULL,
  `GROUP_STATUS` varchar(32) DEFAULT NULL,
  `CREATION_DATE` varchar(64) DEFAULT NULL,
  `PARENT_GROUP_ID` int(11) DEFAULT NULL,
  `PARENT_ID_LIST` varchar(255) DEFAULT NULL,
  `GROUP_CODE` varchar(32) DEFAULT NULL,
  `GROUP_DESCRIPTION` varchar(255) DEFAULT NULL,
  `ROLE_ID` int(11) DEFAULT NULL,
  `ROLE_NAME` varchar(64) DEFAULT NULL,
  `insert_time_gmt` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exp_temp_group_info`
--
ALTER TABLE `exp_temp_group_info`
  ADD PRIMARY KEY (`GROUP_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
