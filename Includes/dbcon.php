<?php

/**
 * Connexion sécurisée à la base de données
 * NOTE: Pour la production, utiliser des variables d'environnement .env
 */

// À placer dans un fichier .env en production :
// DB_HOST=localhost
// DB_USER=root
// DB_PASS=
// DB_NAME=attendancemsystem

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "formation";

// Créer la connexion avec mysqli
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Vérifier la connexion
if ($conn->connect_error) {
	// Ne JAMAIS afficher les détails de l'erreur en production
	error_log("Database connection failed: " . $conn->connect_error);
	die("Database connection error. Please contact administrator.");
}

// Définir le charset UTF-8 pour éviter les problèmes d'encodage
$conn->set_charset("utf8mb4");

// Mode strict pour les requêtes SQL
$conn->query("SET sql_mode='STRICT_TRANS_TABLES'");
