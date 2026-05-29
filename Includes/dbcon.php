<?php

/**
 * Connexion sécurisée à la base de données
 * Compatible : développement local + Railway (variables MYSQL*)
 */

// Railway injecte automatiquement ces variables quand on lie un service MySQL :
//   MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT
// En local, on retombe sur les valeurs par défaut.

$db_host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$db_user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$db_pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$db_name = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'formation');
$db_port = (int) (getenv('MYSQLPORT') ?: 3306);

// Créer la connexion avec mysqli
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

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
