<?php
include __DIR__ . '/../Includes/dbcon.php';

echo "Setting up tables in database: " . $db_name . "\n";

// 1. Table tblabsencerequests
$sql1 = "CREATE TABLE IF NOT EXISTS `tblabsencerequests` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `studentAdmissionNo` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `status` enum('En attente', 'Accepté', 'Refusé') NOT NULL DEFAULT 'En attente',
  `justificationFile` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  INDEX `idx_admission` (`studentAdmissionNo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql1)) {
    echo "✓ Table tblabsencerequests created successfully.\n";
} else {
    echo "✗ Error creating tblabsencerequests: " . $conn->error . "\n";
}

// 2. Table tblattestation
$sql2 = "CREATE TABLE IF NOT EXISTS `tblattestation` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `studentId` int NOT NULL,
  `formationId` int NOT NULL,
  `classId` int NOT NULL,
  `classArmId` int NOT NULL,
  `teacherId` int NOT NULL,
  `sessionId` int NOT NULL,
  `mention` varchar(50) NOT NULL,
  `dateGenerated` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  INDEX `idx_student` (`studentId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql2)) {
    echo "✓ Table tblattestation created successfully.\n";
} else {
    echo "✗ Error creating tblattestation: " . $conn->error . "\n";
}

echo "Database setup completed.\n";
?>
