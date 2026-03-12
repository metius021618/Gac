-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 11, 2026 at 02:04 PM
-- Server version: 5.7.33-0ubuntu0.16.04.1
-- PHP Version: 5.6.40-1+ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gps`
--

-- --------------------------------------------------------

--
-- Table structure for table `recorrido`
--

CREATE TABLE `recorrido` (
  `id_reco` int(11) NOT NULL,
  `f_fereg` date NOT NULL,
  `co_coordx` decimal(10,6) NOT NULL,
  `co_coordy` decimal(10,6) NOT NULL,
  `id_vehi` int(11) NOT NULL,
  `id_chofer` bigint(30) UNSIGNED NOT NULL,
  `re_tiempo` double NOT NULL,
  `ho_hora` text NOT NULL,
  `re_velocidad` double NOT NULL,
  `re_distancia` double NOT NULL,
  `fe_actualiza_coord` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `di_grados` text NOT NULL,
  `le_combustible_01` text NOT NULL,
  `le_combustible_02` text NOT NULL,
  `an3` int(11) NOT NULL DEFAULT '0',
  `an4` int(11) NOT NULL DEFAULT '0',
  `f_gps_actual` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `din1` int(11) NOT NULL DEFAULT '0',
  `din2` int(11) NOT NULL DEFAULT '0',
  `din3` int(11) NOT NULL DEFAULT '0',
  `din4` int(11) NOT NULL DEFAULT '0',
  `out1` int(11) NOT NULL DEFAULT '0',
  `out2` int(11) NOT NULL DEFAULT '0',
  `stop` bigint(20) NOT NULL DEFAULT '0',
  `re_distancia_mts` double NOT NULL DEFAULT '0',
  `rpm` int(11) NOT NULL DEFAULT '0',
  `temp` int(11) NOT NULL DEFAULT '0',
  `freno` int(11) NOT NULL DEFAULT '0',
  `embrague` int(11) NOT NULL DEFAULT '0',
  `speed` int(11) NOT NULL DEFAULT '0',
  `acelerador` int(11) NOT NULL DEFAULT '0',
  `carga_motor` int(11) NOT NULL DEFAULT '0',
  `pto` int(11) NOT NULL DEFAULT '0',
  `combust_utilizado` int(11) NOT NULL DEFAULT '0',
  `ho_motor` int(11) NOT NULL DEFAULT '0',
  `distance` int(11) NOT NULL DEFAULT '0',
  `tasa_combust` int(11) NOT NULL DEFAULT '0',
  `eco_combust` int(11) NOT NULL DEFAULT '0',
  `ext_voltaje` decimal(10,2) NOT NULL DEFAULT '0.00',
  `var1` int(11) NOT NULL DEFAULT '0',
  `var2` int(11) NOT NULL DEFAULT '0',
  `flag_enviado` int(11) NOT NULL DEFAULT '0',
  `state` varchar(100) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `recorrido`
--
ALTER TABLE `recorrido`
  ADD PRIMARY KEY (`id_reco`),
  ADD KEY `id_vehi` (`id_vehi`),
  ADD KEY `id_chofer` (`id_chofer`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `recorrido`
--
ALTER TABLE `recorrido`
  MODIFY `id_reco` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
