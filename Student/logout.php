<?php
include '../Includes/session.php';
include '../Includes/Security.php';

// Détruire la session
Security::destroySession();

// Rediriger vers la page de login
header('Location: ../index.php');
exit;
