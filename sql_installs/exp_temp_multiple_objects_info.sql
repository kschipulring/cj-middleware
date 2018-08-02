-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2018 at 06:01 PM
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
-- Table structure for table `exp_temp_multiple_objects_info`
--

CREATE TABLE `exp_temp_multiple_objects_info` (
  `OBJECT_EXP_DATE` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `LINK_PATH` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `OBJECT_REL_DATE` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `CREATOR_USER_ID` int(11) NOT NULL,
  `CREATION_TS` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AVG_RATING` int(11) DEFAULT NULL,
  `OBJECT_TYPE_ID` int(11) DEFAULT NULL,
  `VIDEO_STEPS` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `OBJECT_TYPE_NAME` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `OBJECT_DESCR` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `OBJECT_STATUS` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
  `CHILD_ID_LIST` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `DURATION_SECS` int(11) DEFAULT NULL,
  `OBJECT_IMAGE` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `OBJECT_ID` int(11) NOT NULL,
  `OBJECT_NAME` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `FACULTY_ID` int(11) DEFAULT NULL,
  `OWNER_GROUP_ID` int(11) NOT NULL,
  `PASSING_PERCENT` float DEFAULT NULL,
  `DURATION` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `STEP_NO` int(11) DEFAULT NULL,
  `TAGS` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `parent_object_id` int(11) DEFAULT NULL,
  `map_zh_Hant_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_zh_Hant_description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_zh_Hans_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_zh_Hans_description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_ko_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_ko_description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_ja_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_ja_description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_fr_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `map_fr_description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `insert_time_gmt` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exp_temp_multiple_objects_info`
--
ALTER TABLE `exp_temp_multiple_objects_info`
  ADD UNIQUE KEY `OBJECT_ID` (`OBJECT_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
