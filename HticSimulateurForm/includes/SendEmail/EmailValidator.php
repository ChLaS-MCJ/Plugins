<?php
/**
 * Structure Email Validator :
 * 
 */

class HticEmailValidator {
    
    /**
     * Vérification de sécurité de base
     */
    public function verify_request() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        if (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
            if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_nonce')) {
                error_log('HTIC Email - Nonce invalide');
                return false;
            }
        }
        
        if (!isset($_POST['type']) || !isset($_POST['client'])) {
            error_log('HTIC Email - Données manquantes');
            return false;
        }
        
        return true;
    }
    
    /**
     * Nettoyer les données de simulation
     */
    public function sanitize_simulation_data() {
        $type = sanitize_text_field($_POST['type']);
        
        $client = array();
        if (isset($_POST['client']) && is_array($_POST['client'])) {
            $client = array(
                'nom' => sanitize_text_field($_POST['client']['nom'] ?? ''),
                'prenom' => sanitize_text_field($_POST['client']['prenom'] ?? ''),
                'email' => sanitize_email($_POST['client']['email'] ?? ''),
                'telephone' => sanitize_text_field($_POST['client']['telephone'] ?? ''),
                'adresse' => sanitize_textarea_field($_POST['client']['adresse'] ?? ''),
                'code_postal' => sanitize_text_field($_POST['client']['code_postal'] ?? ''),
                'ville' => sanitize_text_field($_POST['client']['ville'] ?? ''),
            );
        }
        
        $simulation = array();
        if (isset($_POST['simulation']) && is_array($_POST['simulation'])) {
            $simulation = $this->sanitize_array($_POST['simulation']);
        }
        
        $results = array();
        if (isset($_POST['results']) && is_array($_POST['results'])) {
            $results = $this->sanitize_array($_POST['results']);
        }
        
        return array(
            'type' => $type,
            'client' => $client,
            'simulation' => $simulation,
            'results' => $results,
            'metadata' => array(
                'timestamp' => current_time('mysql'),
                'ip' => $this->get_client_ip()
            )
        );
    }
    
    /**
     * Validation métier des données
     */
    public function validate_simulation_data($data) {
        $errors = array();
        
        // Email obligatoire et valide
        if (empty($data['client']['email']) || !is_email($data['client']['email'])) {
            $errors[] = 'Email invalide';
        }
        
        // Nom et prénom obligatoires
        if (empty($data['client']['nom'])) {
            $errors[] = 'Nom requis';
        }
        if (empty($data['client']['prenom'])) {
            $errors[] = 'Prénom requis';
        }
        
        // Validations spécifiques par type
        switch ($data['type']) {
            case 'elec-residentiel':
            case 'gaz-residentiel':
                if (empty($data['simulation']['surface']) || $data['simulation']['surface'] < 10) {
                    $errors[] = 'Surface invalide';
                }
                break;
                
            case 'elec-professionnel':
            case 'gaz-professionnel':
                if (empty($data['simulation']['raison_sociale'])) {
                    $errors[] = 'Raison sociale requise';
                }
                break;
        }
        
        // Anti-spam basique
        $suspicious = array('script', 'javascript', 'eval');
        $content = json_encode($data);
        foreach ($suspicious as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $errors[] = 'Contenu suspect';
                break;
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    private function sanitize_array($array) {
        $clean = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $clean[sanitize_key($key)] = $this->sanitize_array($value);
            } elseif (is_numeric($value)) {
                $clean[sanitize_key($key)] = floatval($value);
            } else {
                $clean[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        return $clean;
    }
    
    private function get_client_ip() {
        $fields = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($fields as $field) {
            if (!empty($_SERVER[$field]) && filter_var($_SERVER[$field], FILTER_VALIDATE_IP)) {
                return $_SERVER[$field];
            }
        }
        return 'unknown';
    }
}
