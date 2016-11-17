-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2016 at 10:13 PM
-- Server version: 5.6.21
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `annotator`
--

-- --------------------------------------------------------

--
-- Table structure for table `annotations`
--

DROP TABLE IF EXISTS `annotations`;
CREATE TABLE IF NOT EXISTS `annotations` (
  `tagsetid` int(11) NOT NULL,
  `annotationtypeid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `annotationtypes`
--

DROP TABLE IF EXISTS `annotationtypes`;
CREATE TABLE IF NOT EXISTS `annotationtypes` (
`annotationtypeid` int(11) NOT NULL,
  `type` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `patterns`
--

DROP TABLE IF EXISTS `patterns`;
CREATE TABLE IF NOT EXISTS `patterns` (
`patternid` int(11) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sentences`
--

DROP TABLE IF EXISTS `sentences`;
CREATE TABLE IF NOT EXISTS `sentences` (
`sentenceid` int(11) NOT NULL,
  `pmid` int(11) NOT NULL,
  `pmcid` int(11) NOT NULL,
  `text` text NOT NULL,
  `filename` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
`tagid` int(11) NOT NULL,
  `type` enum('cancer','gene','mutation','') NOT NULL,
  `startpos` int(11) NOT NULL,
  `endpos` int(11) NOT NULL,
  `text` text NOT NULL,
  `sourceid` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tagsetinfos`
--

DROP TABLE IF EXISTS `tagsetinfos`;
CREATE TABLE IF NOT EXISTS `tagsetinfos` (
  `tagsetid` int(11) NOT NULL,
  `sentenceid` int(11) NOT NULL,
  `description` text NOT NULL,
  `patternid` int(11) NOT NULL,
  `a2output` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tagsets`
--

DROP TABLE IF EXISTS `tagsets`;
CREATE TABLE IF NOT EXISTS `tagsets` (
  `tagsetid` int(11) NOT NULL,
  `tagid` int(11) NOT NULL,
  `patternindex` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annotations`
--
ALTER TABLE `annotations`
 ADD PRIMARY KEY (`tagsetid`,`annotationtypeid`);

--
-- Indexes for table `annotationtypes`
--
ALTER TABLE `annotationtypes`
 ADD PRIMARY KEY (`annotationtypeid`);

--
-- Indexes for table `patterns`
--
ALTER TABLE `patterns`
 ADD PRIMARY KEY (`patternid`);

--
-- Indexes for table `sentences`
--
ALTER TABLE `sentences`
 ADD PRIMARY KEY (`sentenceid`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
 ADD PRIMARY KEY (`tagid`);

--
-- Indexes for table `tagsets`
--
ALTER TABLE `tagsets`
 ADD PRIMARY KEY (`tagsetid`,`tagid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annotationtypes`
--
ALTER TABLE `annotationtypes`
MODIFY `annotationtypeid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `patterns`
--
ALTER TABLE `patterns`
MODIFY `patternid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sentences`
--
ALTER TABLE `sentences`
MODIFY `sentenceid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
MODIFY `tagid` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
