<?php
/**
 * Point d'entrée pour le traitement du formulaire via AJAX WordPress
 * process-form.php
 */

// Si on est dans WordPress
if (defined('ABSPATH')) {
    // Hook WordPress AJAX
    add_action('wp_ajax_process_electricity_form', 'process_electricity_form');
    add_action('wp_ajax_nopriv_process_electricity_form', 'process_electricity_form');
}

function process_electricity_form() {
    // Vérifier le nonce WordPress
    if (!wp_verify_nonce($_POST['nonce'], 'electricity_form_nonce')) {
        wp_send_json_error('Erreur de sécurité');
        return;
    }
    
    // Récupérer les données JSON
    $rawData = file_get_contents('php://input');
    
    if (empty($rawData)) {
        wp_send_json_error('Aucune donnée reçue');
        return;
    }
    
    // Décoder le JSON
    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Format JSON invalide');
        return;
    }
    
    // Validation des champs requis
    $requiredFields = ['firstName', 'lastName', 'email', 'phone', 'postalCode'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            wp_send_json_error("Le champ '$field' est requis");
            return;
        }
    }
    
    // Valider l'email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        wp_send_json_error('Adresse email invalide');
        return;
    }
    
    // Valider le téléphone
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    if (strlen($phone) !== 10) {
        wp_send_json_error('Numéro de téléphone invalide');
        return;
    }
    
    // Valider le code postal
    if (!preg_match('/^[0-9]{5}$/', $data['postalCode'])) {
        wp_send_json_error('Code postal invalide');
        return;
    }
    
    try {
        // Inclure EmailHandler
        require_once __DIR__ . '/includes/SendEmail/EmailHandler.php';
        
        // Traiter avec EmailHandler
        $emailHandler = new EmailHandler();
        $result = $emailHandler->processFormData($rawData);
        
        if ($result['success']) {
            // Sauvegarder en base de données WordPress
            saveToWordPressDB($data);
            
            // Réponse de succès
            wp_send_json_success([
                'message' => 'Votre simulation a été envoyée avec succès',
                'referenceNumber' => 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
        
    } catch (Exception $e) {
        error_log('Erreur process-form.php: ' . $e->getMessage());
        wp_send_json_error('Une erreur est survenue : ' . $e->getMessage());
    }
}

/**
 * Sauvegarde en base de données WordPress
 */
function saveToWordPressDB($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'simulations_electricite';
    
    $wpdb->insert(
        $table_name,
        [
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'postal_code' => $data['postalCode'],
            'housing_type' => $data['housingType'] ?? null,
            'surface' => $data['surface'] ?? null,
            'residents' => $data['residents'] ?? null,
            'annual_consumption' => $data['annualConsumption'] ?? null,
            'monthly_estimate' => $data['monthlyEstimate'] ?? null,
            'tarif_chosen' => $data['pricingType'] ?? null,
            'power_chosen' => $data['contractPower'] ?? null,
            'data_json' => json_encode($data),
            'created_at' => current_time('mysql')
        ],
        [
            '%s', '%s', '%s', '%s', '%s',
            '%s', '%d', '%d', '%d', '%f',
            '%s', '%d', '%s', '%s'
        ]
    );
    
    return $wpdb->insert_id;
}