<?php
/**
 * Configuration pour l'envoi d'emails avec Brevo
 * includes/SendEmail/init.php
 */

// Configuration Brevo - Clé API seulement (plus de template IDs)
if (function_exists('get_option')) {
    // Si on est dans WordPress, utiliser les options
    define('BREVO_API_KEY', get_option('brevo_api_key', 'xkeysib-VOTRE-CLE-API-BREVO'));
    define('GES_NOTIFICATION_EMAIL', get_option('ges_notification_email', 'commercial@ges-solutions.fr'));
} else {
    // Configuration par défaut si pas dans WordPress
    define('BREVO_API_KEY', 'xkeysib-VOTRE-CLE-API-BREVO');
    define('GES_NOTIFICATION_EMAIL', 'commercial@ges-solutions.fr');
}

// Configuration des emails
define('GES_FROM_EMAIL', 'noreply@ges-solutions.fr');
define('GES_FROM_NAME', 'GES Solutions');
define('GES_REPLY_TO', 'contact@ges-solutions.fr');

// Fonction helper pour nettoyer les données
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour valider l'email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour logger les erreurs
function logError($message) {
    if (function_exists('error_log')) {
        // Si on est dans WordPress
        error_log('[Simulateur Electricité] ' . $message);
    } else {
        // Sinon, écrire dans un fichier de log
        $logFile = __DIR__ . '/../../logs/email_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Fonction pour formater le téléphone
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        return substr($phone, 0, 2) . ' ' . substr($phone, 2, 2) . ' ' . 
               substr($phone, 4, 2) . ' ' . substr($phone, 6, 2) . ' ' . 
               substr($phone, 8, 2);
    }
    return $phone;
}

// Fonction pour formater les montants
function formatMontant($montant, $decimales = 2) {
    return number_format($montant, $decimales, ',', ' ');
}