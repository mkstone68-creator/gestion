<?php
/**
 * Gestion sécurisée des sessions
 */

if (session_status() === PHP_SESSION_NONE) {
  // Configuration sécurisée des cookies de session
  ini_set('session.cookie_httponly', 1);
  ini_set('session.cookie_samesite', 'Strict');
  // ini_set('session.cookie_secure', 1); // À activer en HTTPS
  ini_set('session.use_strict_mode', 1);
  ini_set('session.gc_maxlifetime', 1800); // 30 minutes

  session_start();
}

// Vérifier que l'utilisateur est authentifié
if (!isset($_SESSION['userId'])) {
  header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'Admin') !== false ? '../index.php' : '../../index.php'));
  exit;
}

// Vérifier le timeout de session (30 minutes)
$timeout = 1800;
if (isset($_SESSION['LAST']) && (time() - $_SESSION['LAST'] > $timeout)) {
  // Session expirée
  $_SESSION = array();
  session_destroy();

  $redirect = (strpos($_SERVER['REQUEST_URI'], 'Admin') !== false ? '../index.php' : '../../index.php');
  header('Location: ' . $redirect . '?session_expired=1');
  exit;
}

// Mettre à jour le timestamp d'activité
$_SESSION['LAST'] = time();

// Vérifier les droits d'accès si nécessaire
// (À implémenter selon les rôles)
?>
