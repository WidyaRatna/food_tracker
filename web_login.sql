-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 09:21 PM
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
-- Database: `web_login`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `admin_phone` varchar(20) DEFAULT NULL,
  `admin_password` mediumtext DEFAULT NULL,
  `admin_status` varchar(15) NOT NULL DEFAULT 'active',
  `admin_main` tinyint(1) NOT NULL DEFAULT 0,
  `admin_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`, `admin_email`, `admin_phone`, `admin_password`, `admin_status`, `admin_main`, `admin_created_at`, `admin_updated_at`, `admin_deleted_at`) VALUES
(1, 'Widya', 'widya@gmail.com', '08123456789', '$2y$10$7AMP9Y2DzEAuoPz0bLppleXkVIpUUchn6Vb/DQ5qernGA9OxoMmSq', 'active', 1, '2025-05-23 23:38:56', '2025-05-24 02:34:23', NULL),
(0, 'arya', 'arya@gmail.com', '081230843434', '$2y$10$zeDLt3e./gwH11p2/XZeuOsSQy48l7XBVWUZSpqslut46.7yt5/Hq', 'active', 0, '2025-05-25 22:55:25', '2025-05-25 22:55:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_reports`
--

CREATE TABLE `daily_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `total_calories` double NOT NULL,
  `total_protein` double NOT NULL,
  `total_carbs` double NOT NULL,
  `bmr` double NOT NULL,
  `tdee` double NOT NULL,
  `calorie_limit` double NOT NULL,
  `protein_limit` double NOT NULL,
  `carb_limit` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_reports`
--

INSERT INTO `daily_reports` (`id`, `user_id`, `report_date`, `total_calories`, `total_protein`, `total_carbs`, `bmr`, `tdee`, `calorie_limit`, `protein_limit`, `carb_limit`) VALUES
(10, 29, '2025-05-26', 975, 94, 54, 1587.5, 2182.8125, 2182.8125, 49.6, 218);

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `calories` float NOT NULL,
  `protein` float NOT NULL,
  `carbs` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `name`, `calories`, `protein`, `carbs`) VALUES
(1, 'Nasi Putih', 131, 2.7, 28),
(2, 'Ayam Goreng', 250, 22, 4),
(3, 'Tempe', 195, 20, 8),
(4, 'Tahu', 145, 11, 4),
(5, 'Telur Rebus', 70, 6, 0.5),
(6, 'Kentang Rebus', 85, 2, 20),
(7, 'Roti Tawar', 265, 9, 50),
(8, 'Susu Sapi', 60, 3.2, 4.8),
(9, 'Pisang', 90, 1.1, 23),
(10, 'Apel', 52, 0.3, 14),
(11, 'Dada Ayam', 165, 31, 0),
(12, 'Nasi Merah', 110, 2.5, 23),
(13, 'Nasi Uduk', 180, 3, 30),
(14, 'Nasi Kuning', 170, 2.8, 31),
(15, 'Nasi Liwet', 160, 2.5, 30),
(16, 'Bubur Ayam', 90, 3.5, 14),
(17, 'Lontong', 110, 2, 24),
(18, 'Ketupat', 120, 2.2, 25),
(19, 'Nasi Kebuli', 195, 4.5, 32),
(20, 'Nasi Goreng', 200, 5, 28),
(21, 'Nasi Tim', 120, 2.5, 26),
(22, 'Rendang', 280, 24, 8),
(23, 'Sate Ayam', 230, 22, 5),
(24, 'Ayam Bakar', 190, 25, 1),
(25, 'Ayam Penyet', 210, 23, 2),
(26, 'Sate Kambing', 250, 24, 3),
(27, 'Semur Daging', 205, 18, 8),
(28, 'Gulai Kambing', 230, 20, 6),
(29, 'Empal Gentong', 215, 19, 5),
(30, 'Iga Bakar', 270, 26, 3),
(31, 'Bebek Goreng', 300, 24, 2),
(32, 'Ikan Bakar', 180, 24, 1),
(33, 'Ikan Asam Pedas', 170, 22, 2),
(34, 'Pindang Ikan', 165, 25, 1),
(35, 'Pepes Ikan', 170, 24, 1.5),
(36, 'Udang Goreng Tepung', 220, 18, 12),
(37, 'Cumi Goreng Tepung', 235, 17, 14),
(38, 'Kerang Saus Padang', 190, 19, 8),
(39, 'Ikan Asin', 200, 25, 0),
(40, 'Otak-Otak', 170, 14, 10),
(41, 'Terong Balado', 95, 2.5, 18),
(42, 'Sayur Asem', 70, 2.5, 13),
(43, 'Sayur Lodeh', 110, 3.5, 13),
(44, 'Sayur Bening', 60, 2, 10),
(45, 'Sayur Sop', 65, 2.5, 10),
(46, 'Cap Cay', 85, 4, 12),
(47, 'Tumis Kangkung', 55, 3, 7),
(48, 'Pecel', 125, 6, 15),
(49, 'Gado-Gado', 150, 7, 16),
(50, 'Urap', 120, 5.5, 14),
(51, 'Lalapan', 45, 2.5, 8),
(52, 'Sambal Terasi', 80, 2, 7),
(53, 'Sambal Matah', 65, 1.5, 6),
(54, 'Sambal Bajak', 75, 1.8, 7),
(55, 'Sambal Ijo', 70, 1.5, 6.5),
(56, 'Sambal Bawang', 80, 1.9, 7),
(57, 'Kremes Ayam', 180, 6, 16),
(58, 'Kerupuk Udang', 420, 8, 65),
(59, 'Kerupuk Ikan', 410, 9, 64),
(60, 'Emping', 400, 7, 70),
(61, 'Rempeyek', 450, 9, 65),
(62, 'Soto Ayam', 120, 12, 8),
(63, 'Soto Betawi', 180, 9, 10),
(64, 'Soto Madura', 130, 11, 9),
(65, 'Soto Kudus', 120, 10, 8),
(66, 'Bakso', 170, 14, 12),
(67, 'Rawon', 180, 15, 8),
(68, 'Coto Makassar', 190, 16, 8),
(69, 'Konro', 205, 18, 6),
(70, 'Sup Buntut', 200, 17, 6),
(71, 'Tekwan', 130, 10, 12),
(72, 'Mie Goreng', 310, 8, 50),
(73, 'Mie Rebus', 260, 7.5, 48),
(74, 'Mie Aceh', 300, 9, 50),
(75, 'Bakmi Jawa', 290, 8, 48),
(76, 'Bakmi Godog', 280, 7.5, 47),
(77, 'Kwetiau Goreng', 300, 7, 53),
(78, 'Bihun Goreng', 280, 6, 54),
(79, 'Siomay', 150, 10, 15),
(80, 'Batagor', 170, 12, 17),
(81, 'Pempek', 180, 11, 22),
(82, 'Kue Lupis', 160, 2, 32),
(83, 'Klepon', 150, 1.8, 33),
(84, 'Onde-Onde', 170, 2.5, 35),
(85, 'Kue Cucur', 180, 2, 36),
(86, 'Lemper', 160, 3.5, 30),
(87, 'Nagasari', 145, 1.5, 31),
(88, 'Serabi', 150, 1.8, 30),
(89, 'Pukis', 190, 3, 35),
(90, 'Apem', 180, 2.8, 34),
(91, 'Kue Putu', 140, 1.5, 29),
(92, 'Rambutan', 85, 0.9, 20),
(93, 'Durian', 145, 1.5, 26),
(94, 'Mangga', 65, 0.7, 15),
(95, 'Salak', 70, 0.8, 16),
(96, 'Manggis', 60, 0.6, 14),
(97, 'Jeruk Bali', 50, 0.5, 12),
(98, 'Sirsak', 65, 0.7, 15),
(99, 'Nangka', 95, 1.2, 22),
(100, 'Markisa', 55, 0.8, 13),
(101, 'Pisang Raja', 95, 1.1, 22),
(112, 'Tahu Gejrot', 100, 4, 12),
(113, 'Tahu Tek', 120, 5, 15),
(114, 'Tahu Sumedang', 150, 6, 10),
(115, 'Soto Semarang', 125, 10, 8),
(116, 'Soto Tegal', 130, 11, 9),
(117, 'Soto Banjar', 140, 12, 8),
(118, 'Sop Konro', 200, 17, 6),
(119, 'Sop Saudara', 190, 16, 7),
(120, 'Sop Kaki Kambing', 180, 15, 5),
(121, 'Sop Iga', 195, 16, 6),
(122, 'Sop Ayam Kampung', 110, 10, 7),
(123, 'Nasi Gandul', 185, 6, 30),
(124, 'Nasi Bebek', 220, 18, 5),
(125, 'Nasi Brongkos', 170, 7, 25),
(126, 'Nasi Tiwul', 140, 3, 30),
(127, 'Nasi Jagung', 130, 2.5, 28),
(128, 'Nasi Glintir', 135, 2.8, 29),
(129, 'Nasi Kucing', 100, 3, 18),
(130, 'Nasi Bakmoy', 160, 8, 22),
(131, 'Tahu Petis', 110, 5, 13),
(132, 'Tahu Gimbal', 140, 6, 16),
(133, 'Tahu Pong', 145, 6, 10),
(134, 'Tahu Campur', 130, 7, 14),
(135, 'Tahu Telur', 150, 8, 12),
(136, 'Tempe Bacem', 200, 15, 12),
(137, 'Tempe Mendoan', 210, 14, 14),
(138, 'Tempe Penyet', 195, 15, 10),
(139, 'Sayur Kolplay', 80, 3, 12),
(140, 'Sayur Bayam', 60, 2.5, 10),
(141, 'Sayur Kunci', 70, 2, 13),
(142, 'Sayur Tewel', 90, 3, 15),
(143, 'Sayur Rebung', 75, 2.5, 12),
(144, 'Sayur Daun Singkong', 80, 3, 13),
(145, 'Sayur Jantungan', 85, 3, 14),
(146, 'Sayur Pecel Lele', 120, 6, 15),
(147, 'Sayur Brongkos', 110, 4, 14),
(148, 'Sayur Krecek', 130, 5, 16),
(149, 'Sayur Tumpang', 125, 5, 15),
(150, 'Sayur Gori', 100, 3, 16),
(151, 'Sambal Goreng Ati', 180, 14, 8),
(152, 'Sambal Goreng Krecek', 140, 6, 14),
(153, 'Sambal Goreng Tempe', 200, 12, 12),
(154, 'Sambal Goreng Tahu', 160, 8, 10),
(155, 'Sambal Goreng Udang', 190, 15, 8),
(156, 'Sambal Goreng Ikan', 180, 16, 6),
(157, 'Sambal Goreng Telur', 150, 8, 10),
(158, 'Sambal Goreng Kentang', 140, 3, 20),
(159, 'Sambal Tumpang', 130, 5, 15),
(160, 'Sambal Petis', 90, 3, 12),
(161, 'Krupuk Kulit', 400, 25, 10),
(162, 'Krupuk Melinjo', 410, 7, 70),
(163, 'Krupuk Bawang', 420, 6, 65),
(164, 'Krupuk Kampung', 400, 5, 68),
(165, 'Krupuk Palembang', 415, 6, 66),
(166, 'Rengginang', 390, 5, 70),
(167, 'Intip', 400, 4, 72),
(168, 'Kue Bika Ambon', 200, 3, 35),
(169, 'Kue Lapis Legit', 300, 5, 40),
(170, 'Kue Pepe', 180, 2, 36),
(171, 'Kue Apem Jawa', 170, 2.5, 34),
(172, 'Kue Getuk', 160, 2, 33),
(173, 'Kue Cenil', 150, 1.5, 32),
(174, 'Kue Wajik', 170, 2, 35),
(175, 'Kue Jadah', 160, 2, 34),
(176, 'Kue Koci', 180, 2.5, 36),
(177, 'Kue Talam', 170, 2, 35),
(178, 'Kue Putu Mayang', 160, 2, 33),
(179, 'Kue Rangi', 180, 2.5, 36),
(180, 'Kue Pancong', 190, 3, 35),
(181, 'Kue Cubit', 200, 3, 38),
(182, 'Kue Pukis', 195, 3, 36),
(183, 'Kue Bikang', 180, 2.5, 35),
(184, 'Kue Carabikang', 170, 2, 34),
(185, 'Kue Kembang Goyang', 200, 2, 38),
(186, 'Kue Ali Agrem', 190, 2.5, 36),
(187, 'Kue Clorot', 160, 2, 33),
(188, 'Kue Gemblong', 180, 2, 35),
(189, 'Kue Jongkong', 170, 2, 34),
(190, 'Kue Mendut', 180, 2.5, 36),
(191, 'Wedang Ronde', 150, 2, 30),
(192, 'Wedang Secang', 80, 0, 20),
(193, 'Wedang Uwuh', 85, 0, 21),
(194, 'Wedang Jahe', 90, 0, 22),
(195, 'Wedang Bajigur', 120, 2, 25),
(196, 'Wedang Angsle', 140, 2.5, 28),
(197, 'Es Dawet', 130, 1.5, 30),
(198, 'Es Cendol', 140, 1.5, 32),
(199, 'Es Campur', 150, 2, 33),
(200, 'Es Teler', 160, 2, 35),
(201, 'Es Cincau', 100, 1, 22),
(202, 'Es Kelapa Muda', 80, 0.5, 18),
(203, 'Es Pisang Ijo', 150, 2, 32),
(204, 'Es Doger', 140, 2, 30),
(205, 'Es Goyobod', 130, 1.5, 28),
(206, 'Es Oyen', 145, 2, 31),
(207, 'Es Blewah', 90, 0.5, 20),
(208, 'Es Selendang Mayang', 120, 1, 26),
(209, 'Es Pallu Butung', 140, 2, 30),
(210, 'Es Kolplay', 100, 1, 22),
(211, 'Kacang Tolo', 350, 25, 60),
(212, 'Kacang Mede', 550, 18, 30),
(213, 'Kacang Tanah Goreng', 570, 25, 20),
(214, 'Kacang Hijau', 340, 22, 60),
(215, 'Kacang Merah', 330, 22, 60),
(216, 'Kacang Kedelai', 400, 35, 30),
(217, 'Jengkol', 140, 6, 25),
(218, 'Petai', 90, 5, 15),
(219, 'Daun Pepaya', 60, 3, 10),
(220, 'Daun Melinjo', 70, 4, 12),
(221, 'Daun Kemangi', 25, 1, 4),
(222, 'Daun Kelor', 60, 5, 8),
(223, 'Kolplay', 80, 2, 15),
(224, 'Trancam', 70, 2, 12),
(225, 'Rujak Soto', 150, 8, 18),
(226, 'Rujak Manis', 100, 1, 22),
(227, 'Rujak Bebek', 90, 1, 20),
(228, 'Rujak Serut', 95, 1, 21),
(229, 'Rujak Kuah Pindang', 110, 3, 20),
(230, 'Rujak Es Krim', 140, 2, 30),
(231, 'Asinan Bogor', 100, 1, 22),
(232, 'Asinan Betawi', 90, 1, 20),
(233, 'Karedok', 120, 5, 15),
(234, 'Lotek', 130, 6, 16),
(235, 'Pecel Madiun', 125, 6, 15),
(236, 'Pecel Blitar', 120, 5.5, 14),
(237, 'Pecel Kawi', 130, 6, 15),
(238, 'Gudangan', 110, 5, 14),
(239, 'Urap Sayur', 120, 5, 14),
(240, 'Sayur Bobor', 100, 3, 15),
(241, 'Sayur Oyong', 70, 2, 12),
(242, 'Sayur Labu Siam', 80, 2, 14),
(243, 'Sayur Daun Ubi', 75, 3, 12),
(244, 'Sayur Genjer', 60, 2, 10),
(245, 'Sayur Kol Goreng', 80, 2, 13),
(246, 'Sayur Pakis', 65, 2.5, 11),
(247, 'Sayur Jipang', 70, 2, 12),
(248, 'Sayur Kembang Kol', 85, 3, 14),
(249, 'Sayur Buncis', 75, 2, 13),
(250, 'Sayur Bayam Goreng', 80, 3, 12),
(251, 'Tumis Kolplay', 85, 2, 14),
(252, 'Tumis Genjer', 70, 2, 12),
(253, 'Tumis Tauge', 65, 2, 11),
(254, 'Tumis Daun Pepaya', 75, 3, 12),
(255, 'Tumis Jantungan', 80, 3, 13),
(256, 'Tumis Rebung', 80, 2.5, 13),
(257, 'Tumis Kembang Kol', 85, 3, 14),
(258, 'Tumis Buncis', 75, 2, 13),
(259, 'Tumis Labu Siam', 80, 2, 14),
(260, 'Tumis Daun Ubi', 75, 3, 12),
(261, 'Sate Usus', 180, 15, 5),
(262, 'Sate Ati', 190, 16, 6),
(263, 'Sate Telur Puyuh', 150, 10, 5),
(264, 'Sate Kerang', 170, 14, 6),
(265, 'Sate Cumi', 180, 15, 5),
(266, 'Sate Udang', 190, 16, 5),
(267, 'Sate Tempe', 200, 12, 10),
(268, 'Sate Tahu', 160, 8, 10),
(269, 'Sate Jamur', 100, 4, 15),
(270, 'Sate Kulit Ayam', 220, 14, 8),
(271, 'Gorengan Pisang', 200, 1, 35),
(272, 'Gorengan Tahu', 150, 6, 10),
(273, 'Gorengan Tempe', 210, 14, 12),
(274, 'Gorengan Ubi', 180, 2, 33),
(275, 'Gorengan Singkong', 170, 2, 32),
(276, 'Gorengan Cireng', 200, 3, 35),
(277, 'Gorengan Combro', 190, 3, 34),
(278, 'Gorengan Misro', 180, 2, 33),
(279, 'Gorengan Bakwan', 190, 4, 30),
(280, 'Gorengan Perkedel', 170, 4, 28),
(281, 'Mie Koclok', 280, 7, 48),
(282, 'Mie Kopyok', 270, 7, 47),
(283, 'Mie Ongklok', 290, 8, 49),
(284, 'Mie Lethek', 280, 7, 48),
(285, 'Mie Pentil', 270, 7, 47),
(286, 'Bakmi Nyemek', 280, 7.5, 48),
(287, 'Bakmi Keriting', 290, 8, 49),
(288, 'Bakmi Bangka', 300, 8, 50),
(289, 'Bakmi Karet', 280, 7, 48),
(290, 'Kwetiau Rebus', 260, 7, 47),
(291, 'Bihun Rebus', 250, 6, 46),
(292, 'Soun Goreng', 270, 6, 48),
(293, 'Soun Rebus', 250, 5, 46),
(294, 'Laksa Bogor', 150, 5, 20),
(295, 'Laksa Betawi', 160, 5, 22),
(296, 'Laksa Tangerang', 155, 5, 21),
(297, 'Laksa Cibinong', 150, 5, 20),
(298, 'Lontong Cap Go Meh', 130, 4, 22),
(299, 'Lontong Kikil', 140, 6, 20),
(300, 'Lontong Sayur', 120, 4, 20),
(301, 'Ketoprak', 150, 6, 20),
(302, 'Kerak Telor', 200, 8, 25),
(303, 'Nasi Ulam', 170, 5, 28),
(304, 'Nasi Timbel', 160, 4, 30),
(305, 'Nasi Tutug Oncom', 180, 6, 28),
(306, 'Nasi Liwet Solo', 165, 3, 31),
(307, 'Nasi Bogana', 170, 5, 29),
(308, 'Nasi Langgi', 180, 6, 30),
(309, 'Nasi Jaha', 160, 3, 32),
(310, 'Nasi Kapau', 175, 5, 30),
(311, 'Sego Sambel', 180, 6, 28),
(312, 'Sego Tempong', 170, 5, 29),
(313, 'Sego Cawuk', 160, 5, 28),
(314, 'Sego Berkat', 165, 4, 30),
(315, 'Sego Koyor', 170, 6, 28),
(316, 'Sego Wiwit', 160, 4, 29),
(317, 'Sego Gurih', 170, 3, 31),
(318, 'Sego Megono', 150, 4, 27),
(319, 'Sego Godog', 160, 5, 28),
(320, 'Sego Kuning', 170, 3, 31),
(321, 'Sego Bakar', 180, 4, 32),
(322, 'Sego Bungkus', 160, 4, 30),
(323, 'Sego Krawu', 170, 6, 28),
(324, 'Sego Tahu', 150, 6, 25),
(325, 'Sego Tempe', 160, 7, 25),
(326, 'Sego Telur', 140, 7, 20),
(327, 'Sego Ayam', 180, 8, 25),
(328, 'Sego Bebek', 190, 9, 25),
(329, 'Sego Ikan', 170, 8, 25),
(330, 'Sego Sayur', 130, 4, 22),
(331, 'Sego Pecel', 150, 5, 25),
(332, 'Sego Lotek', 140, 5, 24),
(333, 'Sego Karedok', 145, 5, 25),
(334, 'Sego Urap', 150, 5, 26),
(335, 'Sego Brongkos', 160, 6, 27),
(336, 'Sego Tumpang', 155, 6, 26),
(337, 'Sego Gudeg', 170, 5, 28),
(338, 'Sego Krecek', 150, 5, 25),
(339, 'Sego Babat', 180, 8, 25),
(340, 'Sego Iso', 170, 8, 25),
(341, 'Sego Paru', 180, 8, 25),
(342, 'Sego Usus', 170, 8, 25),
(343, 'Sego Ati', 175, 8, 25),
(344, 'Sego Kikil', 170, 7, 25),
(345, 'Sego Tahu Telur', 160, 7, 24),
(346, 'Sego Tahu Gimbal', 170, 7, 25),
(347, 'Sego Tahu Gejrot', 150, 6, 24),
(348, 'Sego Tahu Tek', 160, 6, 25),
(349, 'Sego Tahu Campur', 165, 7, 25),
(350, 'Sego Tempe Bacem', 170, 8, 25),
(351, 'Sego Tempe Mendoan', 180, 8, 26),
(352, 'Sego Tempe Penyet', 170, 8, 25),
(353, 'Sego Sayur Kolplay', 140, 4, 24),
(354, 'Sego Sayur Bayam', 130, 4, 23),
(355, 'Sego Sayur Kunci', 135, 4, 24),
(356, 'Sego Sayur Tewel', 140, 4, 25),
(357, 'Sego Sayur Rebung', 135, 4, 24),
(358, 'Sego Sayur Daun Singkong', 140, 4, 25),
(359, 'Sego Sayur Jantungan', 140, 4, 25),
(360, 'Sego Sayur Pecel Lele', 150, 6, 25),
(361, 'Sego Sayur Brongkos', 145, 5, 25),
(362, 'Sego Sayur Tumpang', 145, 5, 25),
(363, 'Sego Sayur Gori', 140, 4, 25),
(364, 'Sego Sambal Goreng Ati', 170, 8, 25),
(365, 'Sego Sambal Goreng Krecek', 160, 6, 25),
(366, 'Sego Sambal Goreng Tempe', 170, 7, 25),
(367, 'Sego Sambal Goreng Tahu', 160, 6, 25),
(368, 'Sego Sambal Goreng Udang', 170, 8, 25),
(369, 'Sego Sambal Goreng Ikan', 170, 8, 25),
(370, 'Sego Sambal Goreng Telur', 160, 7, 25),
(371, 'Sego Sambal Goreng Kentang', 160, 4, 27),
(372, 'Sego Sambal Tumpang', 150, 5, 25),
(373, 'Sego Sambal Petis', 150, 5, 25),
(374, 'Sego Krupuk Kulit', 180, 8, 25),
(375, 'Sego Krupuk Melinjo', 180, 4, 28),
(376, 'Sego Krupuk Bawang', 180, 4, 28),
(377, 'Sego Krupuk Kampung', 180, 4, 28),
(378, 'Sego Krupuk Palembang', 180, 4, 28),
(379, 'Sego Rengginang', 180, 4, 28),
(380, 'Sego Intip', 180, 4, 28),
(381, 'Sego Bika Ambon', 190, 4, 30),
(382, 'Sego Lapis Legit', 200, 5, 32),
(383, 'Sego Pepe', 180, 4, 30),
(384, 'Sego Apem Jawa', 180, 4, 30),
(385, 'Sego Getuk', 180, 4, 30),
(386, 'Sego Cenil', 170, 3, 30),
(387, 'Sego Wajik', 180, 4, 30),
(388, 'Sego Jadah', 180, 4, 30),
(389, 'Sego Koci', 180, 4, 30),
(390, 'Sego Talam', 180, 4, 30),
(391, 'Sego Putu Mayang', 180, 4, 30),
(392, 'Sego Rangi', 180, 4, 30),
(393, 'Sego Pancong', 190, 4, 31),
(394, 'Sego Cubit', 190, 4, 31),
(395, 'Sego Bikang', 180, 4, 30),
(396, 'Sego Carabikang', 180, 4, 30),
(397, 'Sego Kembang Goyang', 190, 4, 31),
(398, 'Sego Ali Agrem', 190, 4, 31),
(399, 'Sego Clorot', 180, 4, 30),
(400, 'Sego Gemblong', 180, 4, 30),
(401, 'Sego Jongkong', 180, 4, 30),
(402, 'martabak', 300, 10, 40),
(404, 'kurma', 277, 1.8, 75);

-- --------------------------------------------------------

--
-- Table structure for table `food_logs`
--

CREATE TABLE `food_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `grams` float NOT NULL,
  `log_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_logs`
--

INSERT INTO `food_logs` (`id`, `user_id`, `food_id`, `grams`, `log_date`) VALUES
(118, 29, 11, 100, '2025-05-26'),
(119, 29, 3, 100, '2025-05-26'),
(120, 29, 4, 100, '2025-05-26'),
(121, 29, 33, 100, '2025-05-26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `height_cm` float NOT NULL,
  `weight_cm` float DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `age` int(11) NOT NULL,
  `activity_level` enum('sedentary','lightly_active','moderately_active','very_active','extremely_active') DEFAULT NULL,
  `goal` enum('weight loss','maintenance','bulking') NOT NULL,
  `daily_calorie_limit` float NOT NULL DEFAULT 0,
  `daily_protein_limit` float NOT NULL DEFAULT 0,
  `daily_carb_limit` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `height_cm`, `weight_cm`, `gender`, `age`, `activity_level`, `goal`, `daily_calorie_limit`, `daily_protein_limit`, `daily_carb_limit`) VALUES
(29, 'arya', '$2y$10$ES4.tVvIjBRhq.8VYEchSe7KOK87fX1eGhyWaBEFexC.wp1HFDNxy', 'arya@gmail.com', 170, 62, 'male', 20, 'lightly_active', 'maintenance', 0, 0, 0),
(31, 'arta', '$2y$10$HREK/BMmjChlXN7fbBfJAOjVNBps5.Qvn7UzCqqCjnb41djV5sqJi', 'arta@gmail.com', 170, 55, 'male', 32, 'lightly_active', 'bulking', 0, 0, 0),
(32, 'arga', '$2y$10$Kj5urczgTwz1JxP/mu8hnO0NrwN5p8MDEl1.CQYxHQdMJCMynnFK6', 'arga@gmail.com', 170, 60, 'male', 20, 'lightly_active', 'bulking', 0, 0, 0),
(33, 'agra', '$2y$10$LF5vTKDdCxFvSCwkZP0O4OynD3YoBcAmadgvPyC4kJVhHXf0hmJaq', 'agra@gmail.com', 170, 70, 'male', 20, 'moderately_active', 'bulking', 0, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`report_date`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `food_logs`
--
ALTER TABLE `food_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_reports`
--
ALTER TABLE `daily_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `food_logs`
--
ALTER TABLE `food_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD CONSTRAINT `daily_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `food_logs`
--
ALTER TABLE `food_logs`
  ADD CONSTRAINT `food_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `food_logs_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
