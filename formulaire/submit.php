<?php
$servername = "localhost";
$username = "root"; // Remplacez par votre nom d'utilisateur
$password = ""; // Laissez vide si c'est 'root'
$dbname = "formation"; // Nom de ma base de données

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Échec de la connexion: " . $conn->connect_error]));
}

// Valider les données POST
if (!isset($_POST['firstName'], $_POST['lastName'], $_POST['emailAddress'], $_POST['password'], $_POST['confirm'], $_POST['role'])) {
    die(json_encode(["status" => "error", "message" => "Données manquantes"]));
}

$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$emailAddress = trim($_POST['emailAddress']);
$password = trim($_POST['password']);
$confirm = trim($_POST['confirm']);
$role = trim($_POST['role']);

// Valider que les mots de passe correspondent
if ($password !== $confirm) {
    die(json_encode(["status" => "error", "message" => "Les mots de passe ne correspondent pas"]));
}

// Valider le format email
if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(["status" => "error", "message" => "Format email invalide"]));
}

// Valider le rôle
if (!in_array($role, ['Administrator', 'ClassTeacher', 'Student'])) {
    die(json_encode(["status" => "error", "message" => "Rôle invalide"]));
}

// Vérifier si l'email existe déjà
$emailCheck = $conn->prepare("SELECT Id FROM tblusers WHERE emailAddress = ?");
if (!$emailCheck) {
    die(json_encode(["status" => "error", "message" => "Erreur base de données: " . $conn->error]));
}

$emailCheck->bind_param("s", $emailAddress);
$emailCheck->execute();
$result = $emailCheck->get_result();

if ($result->num_rows > 0) {
    die(json_encode(["status" => "error", "message" => "Cet email est déjà utilisé"]));
}

// Insérer le nouvel utilisateur
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO tblusers (firstName, lastName, emailAddress, password, role) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Erreur: " . $conn->error]));
}

$stmt->bind_param("sssss", $firstName, $lastName, $emailAddress, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Inscription réussie ! Redirection vers la connexion..."]);
    header("Refresh: 2; url=../index.php");
} else {
    echo json_encode(["status" => "error", "message" => "Erreur: " . $stmt->error]);
}

$stmt->close();
$emailCheck->close();
$conn->close();
