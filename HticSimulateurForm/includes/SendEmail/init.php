
<?php
/**
 * Base Init appeler depuis simulateur-energie :
 * 

 */

// Fichier d'initialisation
if (!defined('ABSPATH')) {
    exit;
}

// Charger le gestionnaire d'emails
require_once plugin_dir_path(__FILE__) . 'EmailHandler.php';

// Initialiser
if (class_exists('HticEmailHandler')) {
    new HticEmailHandler();
}
