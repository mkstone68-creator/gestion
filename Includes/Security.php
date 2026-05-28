<?php

/**
 * Classe de sécurité centralisée pour l'application
 * Gère : CSRF, passwords, input sanitization, logging
 */

class Security
{

  /**
   * Génère un token CSRF et le stocke en session
   */
  public static function generateCSRFToken()
  {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }

  /**
   * Valide un token CSRF
   */
  public static function validateCSRFToken($token)
  {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
  }

  /**
   * Hash sécurisé d'un mot de passe avec bcrypt
   */
  public static function hashPassword($password)
  {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
  }

  /**
   * Vérifie un mot de passe contre son hash
   */
  public static function verifyPassword($password, $hash)
  {
    return password_verify($password, $hash);
  }

  /**
   * Échappe les données pour l'affichage HTML
   */
  public static function escapeHTML($data)
  {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Valide et nettoie une adresse email
   */
  public static function validateEmail($email)
  {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return false;
    }
    if (strlen($email) > 255) {
      return false;
    }
    return $email;
  }

  /**
   * Valide une chaîne générique
   */
  public static function validateString($input, $minLength = 1, $maxLength = 255)
  {
    $input = trim($input);
    $length = strlen($input);
    return ($length >= $minLength && $length <= $maxLength) ? $input : false;
  }

  /**
   * Log les événements de sécurité
   */
  public static function logSecurityEvent($type, $details, $severity = 'INFO')
  {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);

    // Créer le répertoire s'il n'existe pas
    if (!is_dir($logDir)) {
      mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $message = "[$timestamp] [$severity] [$type] IP: $ip | Details: $details\n";

    error_log($message, 3, $logFile);
  }

  /**
   * Définit une limite de taux (rate limiting)
   * Utilise SESSION pour compter les tentatives
   */
  public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 900)
  {
    $key = "ratelimit_" . $action;

    if (!isset($_SESSION[$key])) {
      $_SESSION[$key] = [
        'attempts' => 0,
        'first_attempt' => time()
      ];
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];

    // Réinitialiser si dépassé la fenêtre de temps
    if ($elapsed > $timeWindow) {
      $_SESSION[$key] = [
        'attempts' => 1,
        'first_attempt' => time()
      ];
      return true;
    }

    $_SESSION[$key]['attempts']++;

    return $_SESSION[$key]['attempts'] <= $maxAttempts;
  }

  /**
   * Régénère l'ID de session (à faire après authentification)
   */
  public static function regenerateSession()
  {
    session_regenerate_id(true);
  }

  /**
   * Détruit la session de façon sécurisée
   */
  public static function destroySession()
  {
    $_SESSION = array();
    session_destroy();
  }

  /**
   * Ajoute les headers de sécurité HTTP
   */
  public static function setSecurityHeaders()
  {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }
}

// Initialiser les sessions avec sécurité
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.cookie_httponly', 1);
  // ini_set('session.cookie_secure', 1); // À décommenter en HTTPS
  ini_set('session.cookie_samesite', 'Strict');
  session_start();
}
