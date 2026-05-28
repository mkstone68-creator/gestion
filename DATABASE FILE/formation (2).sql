-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 28 mai 2026 à 13:09
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `formation`
--

-- --------------------------------------------------------

--
-- Structure de la table `tblabsencerequests`
--

DROP TABLE IF EXISTS `tblabsencerequests`;
CREATE TABLE IF NOT EXISTS `tblabsencerequests` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `studentAdmissionNo` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `justificationFile` varchar(255) DEFAULT NULL,
  `status` enum('En attente','Approuvée','Refusée') NOT NULL DEFAULT 'En attente',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `studentAdmissionNo` (`studentAdmissionNo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbladmin`
--

DROP TABLE IF EXISTS `tbladmin`;
CREATE TABLE IF NOT EXISTS `tbladmin` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `emailAddress` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tbladmin`
--

INSERT INTO `tbladmin` (`Id`, `firstName`, `lastName`, `emailAddress`, `password`) VALUES
(1, 'Admin', 'System', 'admin@gmail.com', '$2y$10$9o5HBcnZcfMPMH924tBX9eCp72vFqTvI3/lAAUmwv9gvcau4hnjgq');

-- --------------------------------------------------------

--
-- Structure de la table `tblalerts`
--

DROP TABLE IF EXISTS `tblalerts`;
CREATE TABLE IF NOT EXISTS `tblalerts` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL,
  `userRole` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `type` enum('information','warning','success','danger') DEFAULT 'information',
  `isRead` tinyint(1) DEFAULT '0',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblattendance`
--

DROP TABLE IF EXISTS `tblattendance`;
CREATE TABLE IF NOT EXISTS `tblattendance` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `admissionNo` varchar(50) NOT NULL,
  `classId` int NOT NULL,
  `classArmId` int DEFAULT NULL,
  `sessionTermId` int NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `dateTimeTaken` datetime NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `admissionNo` (`admissionNo`),
  KEY `classId` (`classId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblbranch`
--

DROP TABLE IF EXISTS `tblbranch`;
CREATE TABLE IF NOT EXISTS `tblbranch` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `branchName` varchar(255) NOT NULL,
  `salleId` int NOT NULL,
  `annee` int NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblbranch`
--

INSERT INTO `tblbranch` (`Id`, `branchName`, `salleId`, `annee`) VALUES
(1, 'Front-end', 1, 1),
(2, 'Back-end', 1, 2),
(3, 'Full Stack', 1, 3),
(4, 'Data Analyst', 2, 1),
(5, 'Data Scientist', 2, 2),
(6, 'AI Engineer', 2, 3),
(7, 'Réseaux', 3, 1),
(8, 'Sécurité Système', 3, 2),
(9, 'Cyber Expert', 3, 3),
(10, 'Mobile Dev Base', 4, 1),
(11, 'Android/iOS', 4, 2),
(12, 'Cross-platform Expert', 4, 3),
(13, 'Cloud Basics', 5, 1),
(14, 'Cloud Architect', 5, 2),
(15, 'DevOps Expert', 5, 3),
(16, 'Game Design', 6, 1),
(17, 'Unity Developer', 6, 2),
(18, 'Game Director', 6, 3);

-- --------------------------------------------------------

--
-- Structure de la table `tblclass`
--

DROP TABLE IF EXISTS `tblclass`;
CREATE TABLE IF NOT EXISTS `tblclass` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `className` varchar(255) DEFAULT NULL,
  `salleId` int NOT NULL,
  `annee` int NOT NULL,
  `specialisation` varchar(100) NOT NULL,
  `capacity` int DEFAULT '30',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblclass`
--

INSERT INTO `tblclass` (`Id`, `className`, `salleId`, `annee`, `specialisation`, `capacity`, `dateCreated`) VALUES
(1, 'Language', 1, 1, 'Front-end', 30, '2026-05-27 17:47:42'),
(2, 'Language', 1, 2, 'Back-end', 30, '2026-05-27 17:47:42'),
(3, 'Language', 1, 3, 'Full Stack', 30, '2026-05-27 17:47:42'),
(4, 'DataLab', 2, 1, 'Data Analyst', 30, '2026-05-27 17:47:42'),
(5, 'DataLab', 2, 2, 'Data Scientist', 30, '2026-05-27 17:47:42'),
(6, 'DataLab', 2, 3, 'AI Engineer', 30, '2026-05-27 17:47:42'),
(7, 'CyberTech', 3, 1, 'Réseaux', 30, '2026-05-27 17:47:42'),
(8, 'CyberTech', 3, 2, 'Sécurité Système', 30, '2026-05-27 17:47:42'),
(9, 'CyberTech', 3, 3, 'Cyber Expert', 30, '2026-05-27 17:47:42'),
(10, 'MobileLab', 4, 1, 'Mobile Dev Base', 30, '2026-05-27 17:47:42'),
(11, 'MobileLab', 4, 2, 'Android/iOS', 30, '2026-05-27 17:47:42'),
(12, 'MobileLab', 4, 3, 'Cross-platform Expert', 30, '2026-05-27 17:47:42'),
(13, 'CloudNet', 5, 1, 'Cloud Basics', 30, '2026-05-27 17:47:42'),
(14, 'CloudNet', 5, 2, 'Cloud Architect', 30, '2026-05-27 17:47:42'),
(15, 'CloudNet', 5, 3, 'DevOps Expert', 30, '2026-05-27 17:47:42'),
(16, 'GameStudio', 6, 1, 'Game Design', 30, '2026-05-27 17:47:42'),
(17, 'GameStudio', 6, 2, 'Unity Developer', 30, '2026-05-27 17:47:42'),
(18, 'GameStudio', 6, 3, 'Game Director', 30, '2026-05-27 17:47:42');

-- --------------------------------------------------------

--
-- Structure de la table `tblclassarms`
--

DROP TABLE IF EXISTS `tblclassarms`;
CREATE TABLE IF NOT EXISTS `tblclassarms` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `classId` int NOT NULL,
  `classArmName` varchar(255) NOT NULL,
  `isAssigned` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblformation`
--

DROP TABLE IF EXISTS `tblformation`;
CREATE TABLE IF NOT EXISTS `tblformation` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `classArmId` int DEFAULT NULL,
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblinformation`
--

DROP TABLE IF EXISTS `tblinformation`;
CREATE TABLE IF NOT EXISTS `tblinformation` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `audience` enum('all','teachers','students') DEFAULT 'all',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblsalle`
--

DROP TABLE IF EXISTS `tblsalle`;
CREATE TABLE IF NOT EXISTS `tblsalle` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `salleName` varchar(255) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblsalle`
--

INSERT INTO `tblsalle` (`Id`, `salleName`) VALUES
(1, 'Language'),
(2, 'DataLab'),
(3, 'CyberTech'),
(4, 'MobileLab'),
(5, 'CloudNet'),
(6, 'GameStudio');

-- --------------------------------------------------------

--
-- Structure de la table `tblsessionterm`
--

DROP TABLE IF EXISTS `tblsessionterm`;
CREATE TABLE IF NOT EXISTS `tblsessionterm` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `sessionName` varchar(50) NOT NULL,
  `termId` int NOT NULL,
  `isActive` tinyint(1) DEFAULT '0',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblstudents`
--

DROP TABLE IF EXISTS `tblstudents`;
CREATE TABLE IF NOT EXISTS `tblstudents` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `otherName` varchar(100) DEFAULT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `emailAddress` varchar(150) DEFAULT NULL,
  `admissionNumber` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(20) DEFAULT NULL,
  `address` text,
  `classId` int NOT NULL,
  `status` enum('active','suspended','graduated') DEFAULT 'active',
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_admission` (`admissionNumber`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `tblteacherclass`
--

DROP TABLE IF EXISTS `tblteacherclass`;
CREATE TABLE IF NOT EXISTS `tblteacherclass` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `teacherId` int NOT NULL,
  `classId` int NOT NULL,
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblteacherclass`
--

INSERT INTO `tblteacherclass` (`Id`, `teacherId`, `classId`, `dateCreated`) VALUES
(1, 1, 13, '2026-05-27 17:33:12'),
(2, 1, 8, '2026-05-27 17:33:12'),
(3, 1, 4, '2026-05-27 17:33:12');

-- --------------------------------------------------------

--
-- Structure de la table `tblteacherpayment`
--

DROP TABLE IF EXISTS `tblteacherpayment`;
CREATE TABLE IF NOT EXISTS `tblteacherpayment` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `teacherId` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paymentDate` date NOT NULL,
  `month` varchar(20) NOT NULL,
  `status` enum('paid','pending') DEFAULT 'pending',
  `note` text,
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `teacherId` (`teacherId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tblteachers`
--

DROP TABLE IF EXISTS `tblteachers`;
CREATE TABLE IF NOT EXISTS `tblteachers` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `emailAddress` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(20) NOT NULL,
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `emailAddress` (`emailAddress`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblteachers`
--

INSERT INTO `tblteachers` (`Id`, `firstName`, `lastName`, `emailAddress`, `password`, `phoneNo`, `dateCreated`) VALUES
(1, 'diffo', 'Max', 'diffomax@gmail.com', '$2y$10$6cJWAqbVFfheyIoUKHVl8OCulpfcUxnwjhB1D/cWy7kVi45PfVjae', '+237679910238', '2026-05-27 17:33:12');

-- --------------------------------------------------------

--
-- Structure de la table `tblterm`
--

DROP TABLE IF EXISTS `tblterm`;
CREATE TABLE IF NOT EXISTS `tblterm` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `termName` varchar(20) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblterm`
--

INSERT INTO `tblterm` (`Id`, `termName`) VALUES
(1, '1er Trimestre'),
(2, '2ème Trimestre'),
(3, '3ème Trimestre');

-- --------------------------------------------------------

--
-- Structure de la table `tblusers`
--

DROP TABLE IF EXISTS `tblusers`;
CREATE TABLE IF NOT EXISTS `tblusers` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `emailAddress` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','ClassTeacher','Student') NOT NULL,
  `phoneNo` varchar(20) DEFAULT NULL,
  `dateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `emailAddress` (`emailAddress`),
  KEY `idx_email` (`emailAddress`),
  KEY `idx_role` (`role`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tblusers`
--

INSERT INTO `tblusers` (`Id`, `firstName`, `lastName`, `emailAddress`, `password`, `role`, `phoneNo`, `dateCreated`) VALUES
(1, 'Admin', 'System', 'admin@gmail.com', '$2y$10$9o5HBcnZcfMPMH924tBX9eCp72vFqTvI3/lAAUmwv9gvcau4hnjgq', 'Administrator', '699861968', '2026-05-27 17:30:32');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
