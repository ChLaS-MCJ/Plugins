<?php
/**
 * Plugin Name: HTIC Simulateur Consommation Énergie
 * Description: Plugin pour gérer et afficher le simulateur de consommation énergétique avec interface d'administration
 * Version: 1.0.0
 * Author: HTIC
 * Text Domain: htic-simulateur
 * 
 * Ce plugin fournit des simulateurs de consommation énergétique pour :
 * - Électricité résidentielle et professionnelle
 * - Gaz résidentiel et professionnel
 * - Système de contact intégré
 * - Envoi d'emails automatisés via Brevo
 * - Interface d'administration complète
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('HTIC_SIMULATEUR_URL', plugin_dir_url(__FILE__));
define('HTIC_SIMULATEUR_PATH', plugin_dir_path(__FILE__));
define('HTIC_SIMULATEUR_VERSION', '1.0.0');

/**
 * Classe principale du plugin HTIC Simulateur Énergie
 * Gère l'administration, les shortcodes, les calculs et la sauvegarde des données
 */
class HticSimulateurEnergieAdmin {
    
    public function __construct() {
        $this->init_hooks();
        $this->init_shortcodes();
        $this->init_ajax_handlers();
    }
    
    // ================================
    // INITIALISATION
    // ================================
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_simulateur_data', array($this, 'save_simulateur_data'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function init_shortcodes() {
        add_shortcode('htic_simulateur_elec_residentiel', array($this, 'shortcode_elec_residentiel'));
        add_shortcode('htic_simulateur_gaz_residentiel', array($this, 'shortcode_gaz_residentiel'));
        add_shortcode('htic_simulateur_elec_professionnel', array($this, 'shortcode_elec_professionnel'));
        add_shortcode('htic_simulateur_gaz_professionnel', array($this, 'shortcode_gaz_professionnel'));
        add_shortcode('htic_simulateur_energie', array($this, 'shortcode_simulateur_unifie'));
        add_shortcode('htic_contact_form', array($this, 'shortcode_contact_form'));
    }
    
    private function init_ajax_handlers() {
        $ajax_actions = array(
            'htic_load_formulaire' => 'ajax_load_formulaire',
            'htic_calculate_estimation' => 'ajax_calculate_estimation',
            'htic_get_communes_gaz' => 'ajax_get_communes_gaz',
            'htic_recalculate_with_power' => 'ajax_recalculate_with_power',
            'process_electricity_form' => 'ajax_electricity_form',
            'htic_process_contact' => 'ajax_process_contact',
            'process_gaz_form' => 'ajax_gaz_form',
            'get_simulation_details' => 'ajax_get_simulation_details'
        );
        
        foreach ($ajax_actions as $action => $method) {
            add_action('wp_ajax_' . $action, array($this, $method));
            add_action('wp_ajax_nopriv_' . $action, array($this, $method));
        }
    }
        
    public function activate() {
        $this->create_default_options();
        $this->create_tables();
        $this->create_formulaires_structure();
    }
    
    private function create_formulaires_structure() {
        $base_path = HTIC_SIMULATEUR_PATH;
        
        $directories = array(
            'admin', 'templates', 'formulaires', 'includes',
            'formulaires/elec-residentiel', 'formulaires/gaz-residentiel', 
            'formulaires/elec-professionnel', 'formulaires/gaz-professionnel',
            'formulaires/contact'
        );
        
        foreach ($directories as $dir) {
            $full_path = $base_path . $dir;
            if (!file_exists($full_path)) {
                wp_mkdir_p($full_path);
            }
        }
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('htic_simulateur_update_tariffs');
    }
    
    // ================================
    // INTERFACE ADMINISTRATEUR
    // ================================
    
    public function add_admin_menu() {
        add_menu_page(
            'HTIC Simulateur Énergie',
            'Simulateur Énergie',
            'manage_options',
            'htic-simulateur-energie',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'htic-simulateur-energie',
            'Configuration Emails (Brevo)',
            'Emails Brevo',
            'manage_options',
            'htic-brevo-config',
            array($this, 'brevo_config_page')
        );
        
        add_submenu_page(
            'htic-simulateur-energie',
            'Simulations reçues',
            'Simulations',
            'manage_options',
            'htic-simulations-list',
            array($this, 'simulations_list_page')
        );
    }
    
    public function register_settings() {
        $settings_groups = array(
            'htic_simulateur_elec_residentiel' => 'htic_simulateur_elec_residentiel_data',
            'htic_simulateur_gaz_residentiel' => 'htic_simulateur_gaz_residentiel_data',
            'htic_simulateur_elec_professionnel' => 'htic_simulateur_elec_professionnel_data',
            'htic_simulateur_gaz_professionnel' => 'htic_simulateur_gaz_professionnel_data',
            'htic_contact_settings' => 'htic_contact_email',
            'htic_brevo_settings' => array('brevo_api_key', 'ges_notification_email')
        );
        
        foreach ($settings_groups as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $setting) {
                    register_setting($group, $setting);
                }
            } else {
                register_setting($group, $settings);
            }
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_htic-simulateur-energie') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('htic-simulateur-admin-js', HTIC_SIMULATEUR_URL . 'js/admin.js', array('jquery'), HTIC_SIMULATEUR_VERSION, true);
        wp_enqueue_style('htic-simulateur-admin-css', HTIC_SIMULATEUR_URL . 'css/admin.css', array(), HTIC_SIMULATEUR_VERSION);
        
        wp_localize_script('htic-simulateur-admin-js', 'htic_simulateur_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htic_simulateur_nonce')
        ));
    }
    
    // ================================
    // SHORTCODES POUR LES FORMULAIRES
    // ================================
    
    public function shortcode_elec_residentiel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('elec-residentiel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/elec-residentiel/elec-residentiel.php';
        return ob_get_clean();
    }
    
    public function shortcode_gaz_residentiel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('gaz-residentiel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/gaz-residentiel/gaz-residentiel.php';
        return ob_get_clean();
    }
    
    public function shortcode_elec_professionnel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('elec-professionnel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/elec-professionnel/elec-professionnel.php';
        return ob_get_clean();
    }
    
    public function shortcode_gaz_professionnel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('gaz-professionnel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/gaz-professionnel/gaz-professionnel.php';
        return ob_get_clean();
    }
    
    public function shortcode_simulateur_unifie($atts) {
        $atts = shortcode_atts(array('type' => '', 'theme' => 'default'), $atts);
        $this->enqueue_simulateur_unifie_assets();
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'templates/simulateur-unifie.php';
        return ob_get_clean();
    }
    
    public function shortcode_contact_form($atts) {
        $atts = shortcode_atts(array('theme' => 'modern', 'type' => 'general'), $atts);
        $this->enqueue_contact_assets();
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/contact/contact-form.php';
        return ob_get_clean();
    }
    
    // ================================
    // GESTION DES ASSETS
    // ================================
    
    private function enqueue_formulaire_assets($type) {
        $formulaire_path = HTIC_SIMULATEUR_PATH . 'formulaires/' . $type . '/';
        $formulaire_url = HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/';
        $js_file = $formulaire_path . $type . '.js';

        if (file_exists($js_file)) {
            wp_enqueue_script(
                'htic-simulateur-' . $type . '-js', 
                $formulaire_url . $type . '.js', 
                array('jquery'), 
                HTIC_SIMULATEUR_VERSION, 
                true
            );
    
            wp_localize_script(
                'htic-simulateur-' . $type . '-js', 
                'hticSimulateur', 
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('htic_simulateur_calculate'),
                    'type' => $type,
                    'restUrl' => rest_url('htic-simulateur/v1/')
                )
            );
        }
    }
    
    private function enqueue_simulateur_unifie_assets() {
        wp_enqueue_style(
            'htic-simulateur-unifie-css',
            HTIC_SIMULATEUR_URL . 'templates/simulateur-unifie.css',
            array(),
            HTIC_SIMULATEUR_VERSION
        );
        
        wp_enqueue_script(
            'htic-simulateur-unifie-js',
            HTIC_SIMULATEUR_URL . 'templates/simulateur-unifie.js',
            array('jquery'),
            HTIC_SIMULATEUR_VERSION,
            true
        );
        
        wp_localize_script(
            'htic-simulateur-unifie-js', 
            'hticSimulateur',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('htic_simulateur_calculate'),
                'type' => 'unifie',
                'pluginUrl' => HTIC_SIMULATEUR_URL
            )
        );
    }
    
    private function enqueue_contact_assets() {
        wp_enqueue_style(
            'htic-contact-form-css',
            HTIC_SIMULATEUR_URL . 'formulaires/contact/contact-form.css',
            array(),
            HTIC_SIMULATEUR_VERSION
        );
        
        wp_enqueue_script(
            'htic-contact-form-js',
            HTIC_SIMULATEUR_URL . 'formulaires/contact/contact-form.js',
            array('jquery'),
            HTIC_SIMULATEUR_VERSION,
            true
        );
        
        wp_localize_script(
            'htic-contact-form-js',
            'hticContactConfig',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('htic_simulateur_calculate'),
                'maxFileSize' => wp_max_upload_size(),
                'allowedTypes' => array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'),
                'messages' => array(
                    'uploading' => 'Upload en cours...',
                    'uploadSuccess' => 'Fichier uploadé avec succès',
                    'uploadError' => 'Erreur lors de l\'upload',
                    'required' => 'Ce champ est obligatoire',
                    'invalidEmail' => 'Format d\'email invalide',
                    'invalidPhone' => 'Format de téléphone invalide'
                )
            )
        );
    }
    
    // ================================
    // HANDLERS AJAX
    // ================================
    
    public function ajax_load_formulaire() {
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $type = sanitize_text_field($_POST['type']);
        $types_valides = array('elec-residentiel', 'gaz-residentiel', 'elec-professionnel', 'gaz-professionnel');
        
        if (!in_array($type, $types_valides)) {
            wp_send_json_error('Type de formulaire invalide');
            return;
        }
        
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/' . $type . '/' . $type . '.php';
        
        if (!file_exists($template_path)) {
            wp_send_json_error('Template de formulaire non trouvé');
            return;
        }
        
        ob_start();
        $atts = array('theme' => 'default');
        include $template_path;
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'type' => $type,
            'assets' => array(
                'css' => HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/' . $type . '.css',
                'js' => HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/' . $type . '.js'
            )
        ));
    }
    
    public function ajax_calculate_estimation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        ob_start();
        
        $type = sanitize_text_field($_POST['type']);
        $userData = $_POST['user_data'];
        $configData = $_POST['config_data'];
        
        $types_valides = array('elec-residentiel', 'gaz-residentiel', 'elec-professionnel', 'gaz-professionnel');
        
        if (!in_array($type, $types_valides)) {
            wp_send_json_error('Type de calculateur invalide: ' . $type);
            return;
        }
        
        $calculatorFile = HTIC_SIMULATEUR_PATH . 'includes/calculateur-' . str_replace('-', '-', $type) . '.php';
        
        if (!file_exists($calculatorFile)) {
            wp_send_json_error('Calculateur non trouvé pour le type: ' . $type);
            return;
        }
        
        require_once $calculatorFile;
        
        try {
            $results = null;
            
            switch ($type) {
                case 'elec-residentiel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_elec_residentiel_data', array());
                    }
                    $results = htic_calculateur_elec_residentiel($userData, $configData);
                    break;
                    
                case 'gaz-residentiel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_gaz_residentiel_data', array());
                    }
                    $results = htic_calculateur_gaz_residentiel($userData, $configData);
                    break;
                    
                case 'elec-professionnel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_elec_professionnel_data', array());
                    }
                    $results = htic_calculateur_elec_professionnel($userData, $configData);
                    break;
                    
                case 'gaz-professionnel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_gaz_professionnel_data', array());
                    }
                    $results = htic_calculateur_gaz_professionnel($userData, $configData);
                    break;
            }

            ob_get_clean();
            
            if ($results && $results['success']) {
                $this->saveCalculationLog($type, $userData, $results['data']);
                wp_send_json_success($results['data']);
            } else {
                $error = $results['error'] ?? 'Erreur inconnue lors du calcul';
                wp_send_json_error($error);
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            wp_send_json_error('Erreur technique: ' . $e->getMessage());
        }
    }

    public function ajax_recalculate_with_power() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        ob_start();

        $type = sanitize_text_field($_POST['type']);
        $userData = $_POST['user_data'];
        $configData = $_POST['config_data'];
        $nouvellePuissance = intval($_POST['nouvelle_puissance']);
        $tarifChoisi = sanitize_text_field($_POST['tarif_choisi']);
        
        $types_valides = array('elec-residentiel', 'gaz-residentiel', 'elec-professionnel', 'gaz-professionnel');
        
        if (!in_array($type, $types_valides)) {
            wp_send_json_error('Type de calculateur invalide: ' . $type);
            return;
        }

        if ($nouvellePuissance < 3 || $nouvellePuissance > 36) {
            wp_send_json_error('Puissance invalide. Doit être entre 3 et 36 kVA.');
            return;
        }

        $tarifs_valides = array('base', 'hc', 'tempo');
        if (!in_array($tarifChoisi, $tarifs_valides)) {
            wp_send_json_error('Tarif invalide.');
            return;
        }

        $userData['puissance_forcee'] = $nouvellePuissance;
        $userData['tarif_force'] = $tarifChoisi;
        
        try {
            $calculatorFile = HTIC_SIMULATEUR_PATH . 'includes/calculateur-' . str_replace('-', '-', $type) . '.php';
            
            if (!file_exists($calculatorFile)) {
                wp_send_json_error('Calculateur non trouvé pour le type: ' . $type);
                return;
            }
            
            require_once $calculatorFile;
            
            $calculatorFunction = 'htic_calculateur_' . str_replace('-', '_', $type);
            
            if (!function_exists($calculatorFunction)) {
                wp_send_json_error('Fonction de calcul non trouvée: ' . $calculatorFunction);
                return;
            }
            
            $results = call_user_func($calculatorFunction, $userData, $configData);
            
            if (ob_get_length()) {
                ob_end_clean();
            }
            
            if ($results && isset($results['success']) && $results['success']) {
                wp_send_json_success($results['data']);
            } else {
                $error = isset($results['error']) ? $results['error'] : 'Erreur inconnue lors du recalcul';
                wp_send_json_error($error);
            }
            
        } catch (Exception $e) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            wp_send_json_error('Erreur technique lors du recalcul: ' . $e->getMessage());
        }
    }

    public function ajax_get_communes_gaz() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Sécurité échouée');
            return;
        }
        
        $gaz_data = get_option('htic_simulateur_gaz_residentiel_data', array());
        $communes = array();
        
        if (isset($gaz_data['communes']) && is_array($gaz_data['communes'])) {
            $communes = $gaz_data['communes'];
        } else {
            $communes = $this->get_default_communes_excel();
        }
        
        wp_send_json_success(array('communes' => $communes));
    }

    public function ajax_electricity_form() {
        $call_id = 'ELEC-' . time() . '-' . rand(1000, 9999);
        
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }
        
        $form_data = isset($_POST['form_data']) ? json_decode(stripslashes($_POST['form_data']), true) : array();
        
        if (empty($form_data)) {
            wp_send_json_error('Aucune donnée reçue');
            return;
        }
        
        $simulationType = $form_data['simulationType'] ?? 'elec-residentiel';
        
        if ($simulationType === 'elec-professionnel') {
            $uploaded_files = $this->process_uploaded_files_professional();
        } else {
            $uploaded_files = $this->process_uploaded_files();
        }
        
        try {
            require_once HTIC_SIMULATEUR_PATH . 'includes/SendEmail/EmailHandler.php';
            
            $form_data['uploaded_files'] = $uploaded_files;
            $emailHandler = new EmailHandler();
            
            if ($simulationType === 'elec-professionnel') {
                $result = $emailHandler->processBusinessFormData(json_encode($form_data));
            } else {
                $result = $emailHandler->processFormData(json_encode($form_data));
            }
            
            $this->cleanup_uploaded_files($uploaded_files);
            
            if ($result['success']) {
                if ($simulationType === 'elec-professionnel') {
                    $this->save_business_simulation_to_db($form_data);
                } else {
                    $this->save_simulation_to_db($form_data);
                }
                
                wp_send_json_success([
                    'message' => $result['message'],
                    'referenceNumber' => $result['referenceNumber'] ?? 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
                ]);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    public function ajax_process_contact() {
        $call_id = 'CONTACT-' . time() . '-' . rand(1000, 9999);
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }
        
        $form_data_json = isset($_POST['form_data']) ? stripslashes($_POST['form_data']) : '';
        $form_data = json_decode($form_data_json, true);
        
        if (!$form_data) {
            wp_send_json_error('Données invalides');
            return;
        }
        
        // Vérifier les appels dupliqués
        $cache_key = 'contact_processing_' . md5($form_data['email'] . ($form_data['message'] ?? ''));
        $last_process = get_transient($cache_key);
        
        if ($last_process && (time() - $last_process) < 30) {
            wp_send_json_error('Demande en cours de traitement, veuillez patienter...');
            return;
        }
        
        set_transient($cache_key, time(), 60);
        
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = $this->process_uploaded_contact_file($_FILES['fichier']);
            if ($uploaded_file) {
                $form_data['uploaded_files']['fichier'] = $uploaded_file;
            }
        }
        
        try {
            require_once HTIC_SIMULATEUR_PATH . 'includes/SendEmail/EmailHandler.php';
            
            $emailHandler = new EmailHandler();
            $result = $emailHandler->processContactForm($form_data);
            
            if (isset($form_data['uploaded_files']['fichier']['path'])) {
                $temp_file = $form_data['uploaded_files']['fichier']['path'];
                if (file_exists($temp_file)) {
                    @unlink($temp_file);
                }
            }
            
            delete_transient($cache_key);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'reference' => $call_id
                ]);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            delete_transient($cache_key);
            wp_send_json_error('Erreur technique: ' . $e->getMessage());
        }
    }

    public function ajax_gaz_form() {
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }
        
        $form_data = isset($_POST['form_data']) ? json_decode(stripslashes($_POST['form_data']), true) : array();
        
        if (empty($form_data)) {
            wp_send_json_error('Aucune donnée reçue');
            return;
        }
        
        $simulationType = $form_data['simulationType'] ?? 'gaz-residentiel';
        
        if ($simulationType === 'gaz-residentiel') {
            if (isset($_FILES['kbis_file']) || isset($_FILES['rib_entreprise']) || isset($_FILES['mandat_signature'])) {
                $simulationType = 'gaz-professionnel';
                $form_data['simulationType'] = 'gaz-professionnel';
            }
        }
        
        try {
            require_once HTIC_SIMULATEUR_PATH . 'includes/SendEmail/EmailHandler.php';
            
            $emailHandler = new EmailHandler();
            
            if ($simulationType === 'gaz-professionnel') {
                $result = $emailHandler->processGazProfessionnelFormData(json_encode($form_data));
            } else {
                $result = $emailHandler->processGazFormData(json_encode($form_data));
            }
            
            if ($result['success']) {
                if ($simulationType === 'gaz-professionnel') {
                    $this->save_gas_professional_simulation_to_db($form_data);
                } else {
                    $this->save_gaz_simulation_to_db($form_data);
                }
                
                wp_send_json_success([
                    'message' => $result['message'],
                    'referenceNumber' => $result['referenceNumber'] ?? 'GAZ-PRO-' . date('Ymd') . '-' . rand(1000, 9999)
                ]);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    public function ajax_get_simulation_details() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'get_simulation_details')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        global $wpdb;
        $type = sanitize_text_field($_POST['type']);
        $id = intval($_POST['id']);
        
        // Déterminer la table selon le type
        $tables = [
            'elec_res' => $wpdb->prefix . 'simulations_electricite',
            'elec_pro' => $wpdb->prefix . 'simulations_electricite_pro',
            'gaz_res' => $wpdb->prefix . 'simulations_gaz',
            'gaz_pro' => $wpdb->prefix . 'simulations_gaz_pro'
        ];
        
        if (!isset($tables[$type])) {
            wp_send_json_error('Type invalide');
            return;
        }
        
        $simulation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables[$type]} WHERE id = %d",
            $id
        ));
        
        if (!$simulation) {
            wp_send_json_error('Simulation non trouvée');
            return;
        }
        
        // Décoder les données JSON
        $data = json_decode($simulation->data_json, true);
        
        // Construire le HTML selon le type
        $html = '<div class="detail-sections">';
        
        // Section informations client/entreprise
        $html .= '<div class="detail-section">';
        if (in_array($type, ['elec_res', 'gaz_res'])) {
            $html .= '<h3 class="detail-section-title">👤 Informations Client</h3>';
            $html .= '<div class="detail-grid">';
            $html .= '<div class="detail-item">
                <span class="detail-label">Nom complet</span>
                <span class="detail-value">' . esc_html($simulation->first_name . ' ' . $simulation->last_name) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value"><a href="mailto:' . esc_attr($simulation->email) . '">' . esc_html($simulation->email) . '</a></span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Téléphone</span>
                <span class="detail-value"><a href="tel:' . esc_attr($simulation->phone) . '">' . esc_html($simulation->phone) . '</a></span>
            </div>';
            if (!empty($data['client_data']['date_naissance'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Date de naissance</span>
                    <span class="detail-value">' . date('d/m/Y', strtotime($data['client_data']['date_naissance'])) . '</span>
                </div>';
            }
            if (!empty($data['client_data']['lieu_naissance'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Lieu de naissance</span>
                    <span class="detail-value">' . esc_html($data['client_data']['lieu_naissance']) . '</span>
                </div>';
            }
        } else {
            $html .= '<h3 class="detail-section-title">🏢 Informations Entreprise</h3>';
            $html .= '<div class="detail-grid">';
            $html .= '<div class="detail-item">
                <span class="detail-label">Raison sociale</span>
                <span class="detail-value">' . esc_html($simulation->company_name) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Forme juridique</span>
                <span class="detail-value">' . esc_html($simulation->legal_form) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">SIRET</span>
                <span class="detail-value">' . esc_html($simulation->siret) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Code NAF</span>
                <span class="detail-value">' . esc_html($simulation->naf_code ?? 'Non renseigné') . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Contact</span>
                <span class="detail-value">' . esc_html($simulation->contact_first_name . ' ' . $simulation->contact_last_name) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value"><a href="mailto:' . esc_attr($simulation->contact_email) . '">' . esc_html($simulation->contact_email) . '</a></span>
            </div>';
        }
        $html .= '</div></div>';
        
        // Section adresse
        $html .= '<div class="detail-section">';
        $html .= '<h3 class="detail-section-title">📍 Adresse</h3>';
        $html .= '<div class="detail-grid">';
        
        if ($type === 'gaz_res' && isset($data['client_data'])) {
            $html .= '<div class="detail-item">
                <span class="detail-label">Adresse</span>
                <span class="detail-value">' . esc_html($data['client_data']['adresse']) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Code postal / Ville</span>
                <span class="detail-value">' . esc_html($data['client_data']['code_postal'] . ' ' . $data['client_data']['ville']) . '</span>
            </div>';
            if (!empty($data['client_data']['complement'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Complément</span>
                    <span class="detail-value">' . esc_html($data['client_data']['complement']) . '</span>
                </div>';
            }
            $html .= '<div class="detail-item">
                <span class="detail-label">Commune desservie</span>
                <span class="detail-value">' . esc_html($simulation->commune) . '</span>
            </div>';
        } elseif (in_array($type, ['elec_pro', 'gaz_pro'])) {
            $html .= '<div class="detail-item">
                <span class="detail-label">Adresse</span>
                <span class="detail-value">' . esc_html($simulation->company_address) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Code postal / Ville</span>
                <span class="detail-value">' . esc_html($simulation->company_postal_code . ' ' . $simulation->company_city) . '</span>
            </div>';
        } elseif ($type === 'elec_res') {
            $html .= '<div class="detail-item">
                <span class="detail-label">Code postal</span>
                <span class="detail-value">' . esc_html($simulation->postal_code) . '</span>
            </div>';
        }
        
        $html .= '</div></div>';
        
        // Section détails techniques
        $html .= '<div class="detail-section">';
        $html .= '<h3 class="detail-section-title">⚙️ Détails Techniques</h3>';
        $html .= '<div class="detail-grid">';
        
        if ($type === 'elec_res') {
            $html .= '<div class="detail-item">
                <span class="detail-label">Type de logement</span>
                <span class="detail-value">' . esc_html($simulation->housing_type) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Surface</span>
                <span class="detail-value">' . esc_html($simulation->surface) . ' m²</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Nombre de résidents</span>
                <span class="detail-value">' . esc_html($simulation->residents) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Puissance souscrite</span>
                <span class="detail-value">' . esc_html($simulation->power_chosen) . ' kVA</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Tarif choisi</span>
                <span class="detail-value">' . esc_html(strtoupper($simulation->tarif_chosen)) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Consommation annuelle</span>
                <span class="detail-value">' . number_format($simulation->annual_consumption, 0, ',', ' ') . ' kWh</span>
            </div>';
        } elseif ($type === 'gaz_res') {
            $html .= '<div class="detail-item">
                <span class="detail-label">Type de logement</span>
                <span class="detail-value">' . esc_html(ucfirst($data['form_data']['type_logement'] ?? 'Non renseigné')) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Surface</span>
                <span class="detail-value">' . esc_html($data['form_data']['superficie'] ?? 0) . ' m²</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Nombre de personnes</span>
                <span class="detail-value">' . esc_html($data['form_data']['nb_personnes'] ?? 0) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Type de gaz</span>
                <span class="detail-value">' . esc_html($simulation->type_gaz) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Chauffage au gaz</span>
                <span class="detail-value">' . ($data['form_data']['chauffage_gaz'] === 'oui' ? '✅ Oui' : '❌ Non') . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Isolation</span>
                <span class="detail-value">' . esc_html(ucfirst($data['form_data']['isolation'] ?? 'Non renseigné')) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Eau chaude</span>
                <span class="detail-value">' . esc_html(ucfirst($data['form_data']['eau_chaude'] ?? 'Non renseigné')) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Cuisson</span>
                <span class="detail-value">' . esc_html(ucfirst($data['form_data']['cuisson'] ?? 'Non renseigné')) . '</span>
            </div>';
            $html .= '<div class="detail-item">
                <span class="detail-label">Consommation annuelle</span>
                <span class="detail-value">' . number_format($simulation->consommation_annuelle, 0, ',', ' ') . ' kWh</span>
            </div>';
        }
        
        $html .= '</div></div>';
        
        // Section compteur/PDL
        if (isset($data['client_data']['numero_compteur']) || isset($data['client_data']['pdl_adresse'])) {
            $html .= '<div class="detail-section">';
            $html .= '<h3 class="detail-section-title">📊 Informations Compteur</h3>';
            $html .= '<div class="detail-grid">';
            
            if (!empty($data['client_data']['numero_compteur'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Numéro de compteur</span>
                    <span class="detail-value">' . esc_html($data['client_data']['numero_compteur']) . '</span>
                </div>';
            }
            if (!empty($data['client_data']['pdl_adresse'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">PDL / Adresse</span>
                    <span class="detail-value">' . esc_html($data['client_data']['pdl_adresse']) . '</span>
                </div>';
            }
            if (!empty($data['client_data']['ancien_numero_compteur'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Ancien n° compteur</span>
                    <span class="detail-value">' . esc_html($data['client_data']['ancien_numero_compteur']) . '</span>
                </div>';
            }
            
            $html .= '</div></div>';
        }
        
        // Section documents uploadés
        if (!empty($data['uploaded_files'])) {
            $html .= '<div class="detail-section">';
            $html .= '<h3 class="detail-section-title">📎 Documents fournis</h3>';
            $html .= '<div class="detail-grid">';
            
            foreach ($data['uploaded_files'] as $key => $file) {
                $labels = [
                    'rib_file' => 'RIB',
                    'carte_identite_recto' => 'Carte identité (recto)',
                    'carte_identite_verso' => 'Carte identité (verso)',
                    'kbis_file' => 'Extrait KBIS',
                    'rib_entreprise' => 'RIB Entreprise',
                    'mandat_signature' => 'Mandat de signature'
                ];
                
                $html .= '<div class="detail-item">
                    <span class="detail-label">' . ($labels[$key] ?? $key) . '</span>
                    <span class="detail-value">✅ ' . esc_html($file['name']) . ' (' . esc_html($file['size']) . ')</span>
                </div>';
            }
            
            $html .= '</div></div>';
        }
        
        // Section consentements
        if (isset($data['client_data']['accept_conditions']) || isset($data['client_data']['accept_prelevement'])) {
            $html .= '<div class="detail-section">';
            $html .= '<h3 class="detail-section-title">✅ Consentements</h3>';
            $html .= '<div class="detail-grid">';
            
            if (isset($data['client_data']['accept_conditions'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Conditions générales</span>
                    <span class="detail-value">' . ($data['client_data']['accept_conditions'] ? '✅ Acceptées' : '❌ Non acceptées') . '</span>
                </div>';
            }
            if (isset($data['client_data']['accept_prelevement'])) {
                $html .= '<div class="detail-item">
                    <span class="detail-label">Prélèvement automatique</span>
                    <span class="detail-value">' . ($data['client_data']['accept_prelevement'] ? '✅ Accepté' : '❌ Non accepté') . '</span>
                </div>';
            }
            
            $html .= '</div></div>';
        }
        
        // Section estimation financière avec highlight
        $html .= '<div class="highlight-box">';
        $html .= '<h3 style="margin-top: 0; color: #1e40af;">💰 Estimation Tarifaire</h3>';
        
        if (in_array($type, ['elec_res'])) {
            $html .= '<div class="highlight-value">' . number_format($simulation->monthly_estimate, 2, ',', ' ') . ' €/mois</div>';
            $html .= '<div class="highlight-label">Soit ' . number_format($simulation->monthly_estimate * 12, 2, ',', ' ') . ' €/an</div>';
        } elseif ($type === 'gaz_res') {
            $html .= '<div class="highlight-value">' . number_format($simulation->cout_annuel, 2, ',', ' ') . ' €/an</div>';
            $html .= '<div class="highlight-label">Soit ' . number_format($simulation->cout_annuel / 12, 2, ',', ' ') . ' €/mois</div>';
            
            if (isset($data['results_data']['cout_abonnement'])) {
                $html .= '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dbeafe;">';
                $html .= '<div style="display: flex; justify-content: space-between; margin: 5px 0;">';
                $html .= '<span>Abonnement annuel:</span>';
                $html .= '<strong>' . number_format($data['results_data']['cout_abonnement'], 2, ',', ' ') . ' €</strong>';
                $html .= '</div>';
                $html .= '<div style="display: flex; justify-content: space-between; margin: 5px 0;">';
                $html .= '<span>Consommation:</span>';
                $html .= '<strong>' . number_format($data['results_data']['cout_consommation'], 2, ',', ' ') . ' €</strong>';
                $html .= '</div>';
                $html .= '</div>';
            }
        } elseif (in_array($type, ['elec_pro'])) {
            $html .= '<div class="highlight-value">' . number_format($simulation->annual_estimate, 2, ',', ' ') . ' €/an</div>';
            $html .= '<div class="highlight-label">Soit ' . number_format($simulation->annual_estimate / 12, 2, ',', ' ') . ' €/mois</div>';
        } elseif ($type === 'gaz_pro') {
            if ($simulation->is_high_consumption) {
                $html .= '<div class="highlight-value">Sur devis</div>';
                $html .= '<div class="highlight-label">Consommation > 35 000 kWh/an - Tarification personnalisée requise</div>';
            } else {
                $html .= '<div class="highlight-value">' . number_format($simulation->estimated_annual_cost, 2, ',', ' ') . ' €/an</div>';
                $html .= '<div class="highlight-label">Soit ' . number_format($simulation->estimated_annual_cost / 12, 2, ',', ' ') . ' €/mois</div>';
            }
        }
        
        $html .= '</div>';
        
        // Métadonnées
        $html .= '<div class="detail-section" style="margin-top: 20px; background: #f9fafb; padding: 15px; border-radius: 8px;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: center;">';
        $html .= '<div>';
        $html .= '<strong>Date de simulation:</strong> ' . date('d/m/Y à H:i', strtotime($simulation->created_at));
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<strong>Référence:</strong> #' . str_pad($simulation->id, 6, '0', STR_PAD_LEFT);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        wp_send_json_success(['html' => $html]);
    }
    
    // ================================
    // GESTION DES FICHIERS
    // ================================
    
    private function process_uploaded_files() {
        $files = array();
        $expected_files = array('rib_file', 'carte_identite_recto', 'carte_identite_verso');
        
        foreach ($expected_files as $file_key) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = wp_upload_dir();
                $temp_dir = $upload_dir['basedir'] . '/temp-documents';
                
                if (!file_exists($temp_dir)) {
                    wp_mkdir_p($temp_dir);
                }
                
                $file_extension = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $safe_filename = $file_key . '_' . time() . '.' . $file_extension;
                $file_path = $temp_dir . '/' . $safe_filename;
                
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $file_path)) {
                    $files[$file_key] = array(
                        'path' => $file_path,
                        'name' => $_FILES[$file_key]['name'],
                        'type' => $_FILES[$file_key]['type'],
                        'size' => $_FILES[$file_key]['size']
                    );
                }
            }
        }
        
        return $files;
    }

    private function process_uploaded_files_professional() {
        $files = array();
        $expected_files = array('kbis_file', 'rib_entreprise', 'mandat_signature');
        
        foreach ($expected_files as $file_key) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = wp_upload_dir();
                $temp_dir = $upload_dir['basedir'] . '/temp-documents-pro';
                
                if (!file_exists($temp_dir)) {
                    wp_mkdir_p($temp_dir);
                }
                
                $file_extension = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $safe_filename = $file_key . '_' . time() . '.' . $file_extension;
                $file_path = $temp_dir . '/' . $safe_filename;
                
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $file_path)) {
                    $files[$file_key] = array(
                        'path' => $file_path,
                        'name' => $_FILES[$file_key]['name'],
                        'type' => $_FILES[$file_key]['type'],
                        'size' => $_FILES[$file_key]['size']
                    );
                }
            }
        }
        
        return $files;
    }

    private function process_uploaded_contact_file($file) {
        try {
            $max_size = 5 * 1024 * 1024; // 5 Mo
            if ($file['size'] > $max_size) {
                return false;
            }
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                return false;
            }
            
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/temp-contact';
            
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            $safe_filename = 'contact_' . time() . '_' . sanitize_file_name($file['name']);
            $file_path = $temp_dir . '/' . $safe_filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                return [
                    'name' => $file['name'],
                    'path' => $file_path,
                    'type' => $file['type'],
                    'size' => $file['size']
                ];
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }

    private function cleanup_uploaded_files($files) {
        foreach ($files as $file_info) {
            if (isset($file_info['path']) && file_exists($file_info['path'])) {
                unlink($file_info['path']);
            }
        }
    }
    
    // ================================
    // SAUVEGARDE EN BASE DE DONNÉES
    // ================================
    
    private function save_simulation_to_db($data) {
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
                'housing_type' => $data['housingType'],
                'surface' => $data['surface'],
                'residents' => $data['residents'],
                'annual_consumption' => $data['annualConsumption'],
                'monthly_estimate' => $data['monthlyEstimate'],
                'tarif_chosen' => $data['pricingType'],
                'power_chosen' => $data['contractPower'],
                'data_json' => json_encode($data),
                'created_at' => current_time('mysql')
            ]
        );
    }

    private function save_business_simulation_to_db($data) {
        global $wpdb;
        
        $this->create_business_simulations_table();
        $table_name = $wpdb->prefix . 'simulations_electricite_pro';
        
        $wpdb->insert(
            $table_name,
            [
                'company_name' => $data['companyName'] ?? '',
                'legal_form' => $data['legalForm'] ?? '',
                'siret' => $data['siret'] ?? '',
                'naf_code' => $data['nafCode'] ?? '',
                'contact_first_name' => $data['firstName'] ?? '',
                'contact_last_name' => $data['lastName'] ?? '',
                'contact_email' => $data['email'] ?? '',
                'contact_phone' => $data['phone'] ?? '',
                'company_address' => $data['companyAddress'] ?? '',
                'company_postal_code' => $data['companyPostalCode'] ?? '',
                'company_city' => $data['companyCity'] ?? '',
                'category' => $data['category'] ?? '',
                'contract_power' => $data['contractPower'] ?? 0,
                'annual_consumption' => $data['annualConsumption'] ?? 0,
                'monthly_estimate' => $data['monthlyEstimate'] ?? 0,
                'annual_estimate' => $data['annualEstimate'] ?? 0,
                'pricing_type' => $data['pricingType'] ?? '',
                'contract_type' => $data['contractType'] ?? 'principal',
                'selected_offer' => json_encode($data['selectedOffer'] ?? []),
                'data_json' => json_encode($data),
                'created_at' => current_time('mysql'),
                'status' => 'non_traite'
            ]
        );
    }

    private function save_gaz_simulation_to_db($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simulations_gaz';
        
        $this->create_gaz_simulations_table();
        
        $wpdb->insert(
            $table_name,
            [
                'first_name' => $data['client_data']['prenom'] ?? '',
                'last_name' => $data['client_data']['nom'] ?? '',
                'email' => $data['client_data']['email'] ?? '',
                'phone' => $data['client_data']['telephone'] ?? '',
                'commune' => $data['form_data']['commune'] ?? '',
                'type_gaz' => $this->determine_type_gaz($data),
                'consommation_annuelle' => $data['results_data']['consommation_annuelle'] ?? 0,
                'cout_annuel' => $data['results_data']['cout_annuel_ttc'] ?? 0,
                'data_json' => json_encode($data),
                'created_at' => current_time('mysql'),
                'status' => 'non_traite'
            ]
        );
    }

    private function save_gas_professional_simulation_to_db($data) {
        global $wpdb;
        
        $this->create_gas_professional_simulations_table();
        $table_name = $wpdb->prefix . 'simulations_gaz_pro';
        
        $consumption = intval($data['consommation_previsionnelle'] ?? 0);
        $is_high_consumption = $consumption > 35000;
        
        $wpdb->insert(
            $table_name,
            [
                'company_name' => $data['raison_sociale'] ?? '',
                'legal_form' => $data['forme_juridique'] ?? '',
                'siret' => $data['siret'] ?? '',
                'naf_code' => $data['code_naf'] ?? '',
                'contact_first_name' => $data['responsable_prenom'] ?? '',
                'contact_last_name' => $data['responsable_nom'] ?? '',
                'contact_email' => $data['responsable_email'] ?? '',
                'contact_phone' => $data['responsable_telephone'] ?? '',
                'contact_function' => $data['responsable_fonction'] ?? '',
                'company_address' => $data['entreprise_adresse'] ?? '',
                'company_postal_code' => $data['entreprise_code_postal'] ?? '',
                'company_city' => $data['entreprise_ville'] ?? '',
                'commune' => $data['commune'] ?? '',
                'annual_consumption' => $consumption,
                'gas_type' => $this->determine_gas_type_from_data($data),
                'contract_type' => $data['type_contrat'] ?? 'principal',
                'selected_tariff' => $data['tarif_choisi'] ?? '',
                'is_high_consumption' => $is_high_consumption ? 1 : 0,
                'estimated_annual_cost' => $is_high_consumption ? 0 : ($data['cout_annuel'] ?? 0),
                'accept_conditions' => ($data['accept_conditions_pro'] ?? false) ? 1 : 0,
                'accept_direct_debit' => ($data['accept_prelevement_pro'] ?? false) ? 1 : 0,
                'certify_authority' => ($data['certifie_pouvoir'] ?? false) ? 1 : 0,
                'data_json' => json_encode($data),
                'created_at' => current_time('mysql'),
                'status' => 'non_traite'
            ]
        );
    }
    
    // ================================
    // MÉTHODES UTILITAIRES
    // ================================
    
    private function saveCalculationLog($type, $userData, $results) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htic_simulateur_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }
        
        $logData = array(
            'user_data' => $userData,
            'results' => $results,
            'timestamp' => current_time('mysql'),
            'user_ip' => $this->getUserIP()
        );
        
        $wpdb->insert(
            $table_name,
            array(
                'type' => $type,
                'user_ip' => $this->getUserIP(),
                'data' => json_encode($logData),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    private function get_default_communes_excel() {
        return array(
            array('nom' => 'AIRE SUR L\'ADOUR', 'type' => 'naturel'),
            array('nom' => 'BARCELONNE DU GERS', 'type' => 'naturel'),
            array('nom' => 'GAAS', 'type' => 'naturel'),
            array('nom' => 'LABATUT', 'type' => 'naturel'),
            array('nom' => 'LALUQUE', 'type' => 'naturel'),
            array('nom' => 'MISSON', 'type' => 'naturel'),
            array('nom' => 'POUILLON', 'type' => 'naturel'),
            array('nom' => 'BASCONS', 'type' => 'propane'),
            array('nom' => 'BENESSE LES DAX', 'type' => 'propane'),
            array('nom' => 'CAMPAGNE', 'type' => 'propane'),
            array('nom' => 'CARCARES SAINTE CROIX', 'type' => 'propane'),
            array('nom' => 'GEAUNE', 'type' => 'propane'),
            array('nom' => 'MAZEROLLES', 'type' => 'propane'),
            array('nom' => 'MEILHAN', 'type' => 'propane'),
            array('nom' => 'PONTONX SUR L\'ADOUR', 'type' => 'propane'),
            array('nom' => 'SAINT MAURICE', 'type' => 'propane'),
            array('nom' => 'SOUPROSSE', 'type' => 'propane'),
            array('nom' => 'TETHIEU', 'type' => 'propane'),
            array('nom' => 'YGOS SAINT SATURNIN', 'type' => 'propane'),
        );
    }

    private function determine_type_gaz($data) {
        if (isset($data['form_data']['type_gaz_autre'])) {
            return $data['form_data']['type_gaz_autre'];
        }
        
        if (isset($data['form_data']['commune'])) {
            $commune = $data['form_data']['commune'];
            
            $communes_naturel = [
                'AIRE SUR L\'ADOUR', 'BARCELONNE DU GERS', 'GAAS',
                'LABATUT', 'LALUQUE', 'MISSON', 'POUILLON'
            ];
            
            if (in_array(strtoupper($commune), $communes_naturel)) {
                return 'naturel';
            }
            
            if ($commune !== 'autre') {
                return 'propane';
            }
        }
        
        return 'non_defini';
    }

    private function determine_gas_type_from_data($data) {
        if (isset($data['type_gaz_autre'])) {
            return $data['type_gaz_autre'] === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
        }
        
        $commune = $data['commune'] ?? '';
        
        if ($commune === 'autre') {
            return 'Non défini';
        }
        
        $communes_naturel = [
            'AIRE SUR L\'ADOUR', 'BARCELONNE DU GERS', 'GAAS',
            'LABATUT', 'LALUQUE', 'MISSON', 'POUILLON'
        ];
        
        if (in_array(strtoupper($commune), $communes_naturel)) {
            return 'Gaz naturel';
        }
        
        return 'Gaz propane';
    }
    
    // ================================
    // ADMINISTRATION - PAGES
    // ================================
    
    public function admin_page() {
        $admin_path = HTIC_SIMULATEUR_PATH . 'admin/';
        if (!file_exists($admin_path)) {
            wp_mkdir_p($admin_path);
        }
        
        ?>
        <div class="wrap">
            <h1>Configuration du Simulateur Énergie</h1>
            
            <div class="notice notice-info">
                <h3>📝 Shortcodes pour pages :</h3>
                <p><strong>Simulateur Unifié :</strong> <code>[htic_simulateur_energie]</code></p>
            </div>
            
            <div class="htic-simulateur-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#tab-elec-residentiel" class="nav-tab nav-tab-active">
                        <span class="tab-icon"></span>Électricité Résidentiel
                    </a>
                    <a href="#tab-gaz-residentiel" class="nav-tab">
                        <span class="tab-icon"></span>Gaz Résidentiel
                    </a>
                    <a href="#tab-elec-professionnel" class="nav-tab">
                        <span class="tab-icon"></span>Électricité Professionnel
                    </a>
                    <a href="#tab-gaz-professionnel" class="nav-tab">
                        <span class="tab-icon"></span>Gaz Professionnel
                    </a>
                </nav>
                
                <div class="tab-content">
                    <div id="tab-elec-residentiel" class="tab-pane active">
                        <?php include $admin_path . 'admin-elec-residentiel.php'; ?>
                    </div>
                    <div id="tab-gaz-residentiel" class="tab-pane">
                        <?php include $admin_path . 'admin-gaz-residentiel.php'; ?>
                    </div>
                    <div id="tab-elec-professionnel" class="tab-pane">
                        <?php include $admin_path . 'admin-elec-professionnel.php'; ?>
                    </div>
                    <div id="tab-gaz-professionnel" class="tab-pane">
                        <?php include $admin_path . 'admin-gaz-professionnel.php'; ?>
                    </div>
                </div>
            </div>
            
            <div class="htic-simulateur-actions">
                <button type="button" class="button button-primary" id="reset-defaults">Réinitialiser aux valeurs par défaut</button>
            </div>
        </div>
        <?php
    }

    public function brevo_config_page() {
        if (isset($_POST['submit'])) {
            update_option('brevo_api_key', sanitize_text_field($_POST['brevo_api_key']));
            update_option('ges_notification_email', sanitize_email($_POST['ges_notification_email']));
            echo '<div class="notice notice-success"><p>Configuration Brevo sauvegardée!</p></div>';
        }
        
        $brevo_api_key = get_option('brevo_api_key', '');
        $ges_email = get_option('ges_notification_email', 'commercial@ges-solutions.fr');
        ?>
        <div class="wrap">
            <h1>⚡ Configuration Emails - Brevo</h1>
            
            <div class="card">
                <h2>Configuration API Brevo</h2>
                <p>Configurez ici vos paramètres pour l'envoi d'emails via Brevo (ex-SendinBlue)</p>
                
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="brevo_api_key">Clé API Brevo</label>
                            </th>
                            <td>
                                <input type="text" id="brevo_api_key" name="brevo_api_key" 
                                    value="<?php echo esc_attr($brevo_api_key); ?>" 
                                    class="regular-text" placeholder="xkeysib-XXXXX" />
                                <p class="description">
                                    Trouvable dans votre compte Brevo > SMTP & API > API Keys
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ges_notification_email">Email notification GES</label>
                            </th>
                            <td>
                                <input type="email" id="ges_notification_email" name="ges_notification_email" 
                                    value="<?php echo esc_attr($ges_email); ?>" 
                                    class="regular-text" />
                                <p class="description">
                                    Email qui recevra les notifications de nouvelles simulations
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Sauvegarder la configuration'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function simulations_list_page() {
        $this->create_simulations_table();
        $this->create_business_simulations_table();
        $this->create_gaz_simulations_table();
        $this->create_gas_professional_simulations_table();
        
        // Inclure le dashboard
        include HTIC_SIMULATEUR_PATH . 'admin/simulations-dashboard.php';
    }

    // Méthodes utilitaires pour les simulations
    private function get_simulation_type($sim) {
        $data = json_decode($sim->data_json, true);
        
        if (isset($data['type'])) {
            return $data['type'];
        }
        
        if (isset($data['housingType'])) {
            return 'elec-residentiel';
        }
        
        return 'unknown';
    }

    private function get_simulation_type_label($sim) {
        $type = $this->get_simulation_type($sim);
        
        $labels = array(
            'elec-residentiel' => 'Élec. Résidentiel',
            'elec-professionnel' => 'Élec. Professionnel', 
            'gaz-residentiel' => 'Gaz Résidentiel',
            'gaz-professionnel' => 'Gaz Professionnel',
            'unknown' => 'Non défini'
        );
        
        return $labels[$type] ?? 'Inconnu';
    }

    private function handle_simulation_action($post_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simulations_electricite';
        
        switch($post_data['action']) {
            case 'update_status':
                $wpdb->update(
                    $table_name,
                    array('status' => sanitize_text_field($post_data['status'])),
                    array('id' => intval($post_data['sim_id']))
                );
                echo '<div class="notice notice-success"><p>Statut mis à jour!</p></div>';
                break;
                
            case 'delete':
                $wpdb->delete($table_name, array('id' => intval($post_data['sim_id'])));
                echo '<div class="notice notice-success"><p>Simulation supprimée!</p></div>';
                break;
        }
    }
    
    // ================================
    // CRÉATION DES TABLES
    // ================================
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'htic_simulateur_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            type varchar(50) NOT NULL,
            user_ip varchar(45),
            data longtext,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function create_simulations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simulations_electricite';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            postal_code VARCHAR(10),
            housing_type VARCHAR(50),
            surface INT(11),
            residents INT(11),
            annual_consumption INT(11),
            monthly_estimate DECIMAL(10,2),
            tarif_chosen VARCHAR(50),
            power_chosen INT(11),
            data_json TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function create_business_simulations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simulations_electricite_pro';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_name VARCHAR(200),
            legal_form VARCHAR(100),
            siret VARCHAR(14),
            naf_code VARCHAR(10),
            contact_first_name VARCHAR(100),
            contact_last_name VARCHAR(100),
            contact_email VARCHAR(100),
            contact_phone VARCHAR(20),
            company_address TEXT,
            company_postal_code VARCHAR(10),
            company_city VARCHAR(100),
            category VARCHAR(50),
            contract_power INT(11),
            annual_consumption INT(11),
            monthly_estimate DECIMAL(10,2),
            annual_estimate DECIMAL(10,2),
            pricing_type VARCHAR(50),
            contract_type VARCHAR(50),
            selected_offer TEXT,
            data_json LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'non_traite',
            treated_at DATETIME NULL,
            treated_by BIGINT(20) NULL,
            notes TEXT,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function create_gaz_simulations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simulations_gaz';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            commune VARCHAR(100),
            type_gaz VARCHAR(50),
            type_logement VARCHAR(50),
            surface INT(11),
            nb_personnes INT(11),
            chauffage_gaz VARCHAR(10),
            eau_chaude VARCHAR(50),
            cuisson VARCHAR(50),
            isolation VARCHAR(50),
            offre VARCHAR(50),
            consommation_annuelle INT(11),
            cout_annuel DECIMAL(10,2),
            cout_mensuel DECIMAL(10,2),
            data_json LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'non_traite',
            treated_at DATETIME NULL,
            treated_by BIGINT(20) NULL,
            notes TEXT,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_created (created_at),
            INDEX idx_email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function create_gas_professional_simulations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simulations_gaz_pro';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_name VARCHAR(200),
            legal_form VARCHAR(100),
            siret VARCHAR(14),
            naf_code VARCHAR(10),
            contact_first_name VARCHAR(100),
            contact_last_name VARCHAR(100),
            contact_email VARCHAR(100),
            contact_phone VARCHAR(20),
            contact_function VARCHAR(100),
            company_address TEXT,
            company_postal_code VARCHAR(10),
            company_city VARCHAR(100),
            commune VARCHAR(100),
            annual_consumption INT(11),
            gas_type VARCHAR(50),
            contract_type VARCHAR(50),
            selected_tariff VARCHAR(50),
            is_high_consumption TINYINT(1) DEFAULT 0,
            estimated_annual_cost DECIMAL(10,2),
            accept_conditions TINYINT(1) DEFAULT 0,
            accept_direct_debit TINYINT(1) DEFAULT 0,
            certify_authority TINYINT(1) DEFAULT 0,
            data_json LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'non_traite',
            treated_at DATETIME NULL,
            treated_by BIGINT(20) NULL,
            notes TEXT,
            PRIMARY KEY (id),
            INDEX idx_consumption (annual_consumption),
            INDEX idx_status (status),
            INDEX idx_high_consumption (is_high_consumption),
            INDEX idx_created (created_at),
            INDEX idx_company (company_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function upgrade_simulations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simulations_electricite';
        
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'status'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN status VARCHAR(50) DEFAULT 'non_traite' AFTER data_json");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN notes TEXT AFTER status");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN treated_at DATETIME NULL AFTER notes");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN treated_by BIGINT(20) NULL AFTER treated_at");
        }
    }
    
    // ================================
    // DONNÉES PAR DÉFAUT
    // ================================
    
    public function save_simulateur_data() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        check_ajax_referer('htic_simulateur_nonce', 'nonce');
        
        $tab = sanitize_text_field($_POST['tab']);
        $data = $_POST['data'];
        
        switch($tab) {
            case 'elec_residentiel':
                update_option('htic_simulateur_elec_residentiel_data', $data);
                break;
            case 'gaz_residentiel':
                update_option('htic_simulateur_gaz_residentiel_data', $data);
                break;
            case 'elec_professionnel':
                update_option('htic_simulateur_elec_professionnel_data', $data);
                break;
            case 'gaz_professionnel':
                update_option('htic_simulateur_gaz_professionnel_data', $data);
                break;
        }
        
        wp_send_json_success('Données sauvegardées avec succès');
    }
    
    private function create_default_options() {
        if (!get_option('htic_simulateur_elec_residentiel_data')) {
            add_option('htic_simulateur_elec_residentiel_data', $this->get_default_elec_residentiel());
        }
        if (!get_option('htic_simulateur_gaz_residentiel_data')) {
            add_option('htic_simulateur_gaz_residentiel_data', $this->get_default_gaz_residentiel());
        }
        if (!get_option('htic_simulateur_elec_professionnel_data')) {
            add_option('htic_simulateur_elec_professionnel_data', $this->get_default_elec_professionnel());
        }
        if (!get_option('htic_simulateur_gaz_professionnel_data')) {
            add_option('htic_simulateur_gaz_professionnel_data', $this->get_default_gaz_professionnel());
        }
    }
    
    public function get_default_elec_residentiel() {
        return array(
            'puissance_defaut' => 15,
            
            // Tarifs BASE
            'base_abo_3' => 9.69, 'base_kwh_3' => 0.2516,
            'base_abo_6' => 12.67, 'base_kwh_6' => 0.2516,
            'base_abo_9' => 15.89, 'base_kwh_9' => 0.2516,
            'base_abo_12' => 19.16, 'base_kwh_12' => 0.2516,
            'base_abo_15' => 22.21, 'base_kwh_15' => 0.2516,
            'base_abo_18' => 25.24, 'base_kwh_18' => 0.2516,
            'base_abo_24' => 31.96, 'base_kwh_24' => 0.2516,
            'base_abo_30' => 37.68, 'base_kwh_30' => 0.2516,
            'base_abo_36' => 44.43, 'base_kwh_36' => 0.2516,
            
            // Tarifs HC
            'hc_abo_3' => 0, 'hc_hp_3' => 0, 'hc_hc_3' => 0,
            'hc_abo_6' => 13.28, 'hc_hp_6' => 0.27, 'hc_hc_6' => 0.2068,
            'hc_abo_9' => 16.82, 'hc_hp_9' => 0.27, 'hc_hc_9' => 0.2068,
            'hc_abo_12' => 20.28, 'hc_hp_12' => 0.27, 'hc_hc_12' => 0.2068,
            'hc_abo_15' => 23.57, 'hc_hp_15' => 0.27, 'hc_hc_15' => 0.2068,
            'hc_abo_18' => 26.84, 'hc_hp_18' => 0.27, 'hc_hc_18' => 0.2068,
            'hc_abo_24' => 33.7, 'hc_hp_24' => 0.27, 'hc_hc_24' => 0.2068,
            'hc_abo_30' => 39.94, 'hc_hp_30' => 0.27, 'hc_hc_30' => 0.2068,
            'hc_abo_36' => 46.24, 'hc_hp_36' => 0.27, 'hc_hc_36' => 0.2068,
            
            // Équipements et consommations
            'chauffe_eau' => 900, 'chauffe_eau_puissance' => 2400, 'chauffe_eau_simultaneite' => 30,
            'lave_linge' => 100, 'lave_linge_puissance' => 2000, 'lave_linge_simultaneite' => 50,
            'four' => 125, 'four_puissance' => 2000, 'four_simultaneite' => 50,
            'seche_linge' => 175, 'seche_linge_puissance' => 2500, 'seche_linge_simultaneite' => 50,
            'lave_vaisselle' => 100, 'lave_vaisselle_puissance' => 1800, 'lave_vaisselle_simultaneite' => 50,
            'cave_a_vin' => 150, 'cave_a_vin_puissance' => 1000, 'cave_a_vin_simultaneite' => 50,
            'refrigerateur' => 125, 'refrigerateur_puissance' => 150, 'refrigerateur_simultaneite' => 80,
            'congelateur' => 125, 'congelateur_puissance' => 200, 'congelateur_simultaneite' => 80,
            'plaque_induction' => 180, 'plaque_induction_puissance' => 3500, 'plaque_induction_simultaneite' => 30,
            'plaque_vitroceramique' => 250, 'plaque_vitroceramique_puissance' => 3000, 'plaque_vitroceramique_simultaneite' => 30,
            'tv_pc_box' => 300, 'tv_pc_box_puissance' => 500, 'tv_pc_box_simultaneite' => 80,
            'piscine' => 1400, 'piscine_puissance' => 2500, 'piscine_simultaneite' => 80,
            'piscine_chauffee' => 4000,
            'spa_jacuzzi' => 2000, 'spa_jacuzzi_puissance' => 2000, 'spa_jacuzzi_simultaneite' => 50,
            'aquarium' => 240, 'aquarium_puissance' => 100, 'aquarium_simultaneite' => 80,
            'voiture_electrique' => 1500, 'voiture_electrique_puissance' => 7000, 'voiture_electrique_simultaneite' => 30,
            'climatiseur_mobile' => 150, 'climatiseur_mobile_puissance' => 3000, 'climatiseur_mobile_simultaneite' => 50,
            'chauffage_m2_puissance' => 50, 'chauffage_m2_simultaneite' => 80,
            'eclairage' => 750, 'eclairage_puissance' => 500, 'eclairage_simultaneite' => 80,
            'forfait_petits_electromenagers' => 150,
            
            // Répartitions
            'repartition_hp' => 60, 'repartition_hc' => 40,
            
            // Coefficients par nombre de personnes
            'coeff_chauffe_eau_1' => 1, 'coeff_chauffe_eau_2' => 2, 'coeff_chauffe_eau_3' => 2.8, 'coeff_chauffe_eau_4' => 3.7, 'coeff_chauffe_eau_5' => 3.9, 'coeff_chauffe_eau_6' => 5.5,
            'coeff_lave_linge_1' => 1, 'coeff_lave_linge_2' => 1.4, 'coeff_lave_linge_3' => 1.8, 'coeff_lave_linge_4' => 2.2, 'coeff_lave_linge_5' => 2.6, 'coeff_lave_linge_6' => 3,
            'coeff_four_1' => 1, 'coeff_four_2' => 1.4, 'coeff_four_3' => 1.8, 'coeff_four_4' => 2.2, 'coeff_four_5' => 2.6, 'coeff_four_6' => 3,
            'coeff_seche_linge_1' => 1, 'coeff_seche_linge_2' => 1.6, 'coeff_seche_linge_3' => 2.2, 'coeff_seche_linge_4' => 2.8, 'coeff_seche_linge_5' => 3.4, 'coeff_seche_linge_6' => 4,
            'coeff_lave_vaisselle_1' => 1, 'coeff_lave_vaisselle_2' => 1.4, 'coeff_lave_vaisselle_3' => 1.8, 'coeff_lave_vaisselle_4' => 2.2, 'coeff_lave_vaisselle_5' => 2.6, 'coeff_lave_vaisselle_6' => 3,
            'coeff_cave_a_vin_1' => 1, 'coeff_cave_a_vin_2' => 1, 'coeff_cave_a_vin_3' => 1, 'coeff_cave_a_vin_4' => 1, 'coeff_cave_a_vin_5' => 1, 'coeff_cave_a_vin_6' => 1,
            'coeff_refrigerateur_1' => 1, 'coeff_refrigerateur_2' => 1.4, 'coeff_refrigerateur_3' => 1.8, 'coeff_refrigerateur_4' => 2.2, 'coeff_refrigerateur_5' => 2.6, 'coeff_refrigerateur_6' => 3,
            'coeff_congelateur_1' => 1, 'coeff_congelateur_2' => 1.4, 'coeff_congelateur_3' => 1.8, 'coeff_congelateur_4' => 2.2, 'coeff_congelateur_5' => 2.6, 'coeff_congelateur_6' => 3,
            'coeff_plaque_induction_1' => 1, 'coeff_plaque_induction_2' => 1.4, 'coeff_plaque_induction_3' => 1.8, 'coeff_plaque_induction_4' => 2, 'coeff_plaque_induction_5' => 2.2, 'coeff_plaque_induction_6' => 2.4,
            'coeff_plaque_vitroceramique_1' => 1, 'coeff_plaque_vitroceramique_2' => 1.4, 'coeff_plaque_vitroceramique_3' => 1.8, 'coeff_plaque_vitroceramique_4' => 2, 'coeff_plaque_vitroceramique_5' => 2.2, 'coeff_plaque_vitroceramique_6' => 2.4,
            'coeff_tv_pc_box_1' => 1, 'coeff_tv_pc_box_2' => 1, 'coeff_tv_pc_box_3' => 1, 'coeff_tv_pc_box_4' => 1, 'coeff_tv_pc_box_5' => 1, 'coeff_tv_pc_box_6' => 1,
            'coeff_piscine_1' => 1, 'coeff_piscine_2' => 1, 'coeff_piscine_3' => 1, 'coeff_piscine_4' => 1, 'coeff_piscine_5' => 1, 'coeff_piscine_6' => 1,
            'coeff_spa_jacuzzi_1' => 1, 'coeff_spa_jacuzzi_2' => 1, 'coeff_spa_jacuzzi_3' => 1, 'coeff_spa_jacuzzi_4' => 1, 'coeff_spa_jacuzzi_5' => 1, 'coeff_spa_jacuzzi_6' => 1,
            'coeff_aquarium_1' => 1, 'coeff_aquarium_2' => 1, 'coeff_aquarium_3' => 1, 'coeff_aquarium_4' => 1, 'coeff_aquarium_5' => 1, 'coeff_aquarium_6' => 1,
            'coeff_voiture_electrique_1' => 1, 'coeff_voiture_electrique_2' => 1, 'coeff_voiture_electrique_3' => 1, 'coeff_voiture_electrique_4' => 1, 'coeff_voiture_electrique_5' => 1, 'coeff_voiture_electrique_6' => 1,
            'coeff_climatiseur_mobile_1' => 1, 'coeff_climatiseur_mobile_2' => 1, 'coeff_climatiseur_mobile_3' => 1, 'coeff_climatiseur_mobile_4' => 1, 'coeff_climatiseur_mobile_5' => 1, 'coeff_climatiseur_mobile_6' => 1,
            
            // Chauffage par m² - Maison
            'maison_convecteurs_mauvaise' => 215, 'maison_convecteurs_moyenne' => 150, 'maison_convecteurs_bonne' => 75, 'maison_convecteurs_tres_bonne' => 37.5,
            'maison_inertie_mauvaise' => 185, 'maison_inertie_moyenne' => 125, 'maison_inertie_bonne' => 65, 'maison_inertie_tres_bonne' => 30,
            'maison_clim_reversible_mauvaise' => 100, 'maison_clim_reversible_moyenne' => 70, 'maison_clim_reversible_bonne' => 45, 'maison_clim_reversible_tres_bonne' => 17.5,
            'maison_pac_mauvaise' => 80, 'maison_pac_moyenne' => 52.5, 'maison_pac_bonne' => 35, 'maison_pac_tres_bonne' => 12.5,
            
            // Chauffage par m² - Appartement
            'appartement_convecteurs_mauvaise' => 204.25, 'appartement_convecteurs_moyenne' => 142.5, 'appartement_convecteurs_bonne' => 71.25, 'appartement_convecteurs_tres_bonne' => 35.63,
            'appartement_inertie_mauvaise' => 175.75, 'appartement_inertie_moyenne' => 118.75, 'appartement_inertie_bonne' => 61.75, 'appartement_inertie_tres_bonne' => 28.5,
            'appartement_clim_reversible_mauvaise' => 95, 'appartement_clim_reversible_moyenne' => 66.5, 'appartement_clim_reversible_bonne' => 42.75, 'appartement_clim_reversible_tres_bonne' => 16.63,
            'appartement_pac_mauvaise' => 76, 'appartement_pac_moyenne' => 49.88, 'appartement_pac_bonne' => 33.25, 'appartement_pac_tres_bonne' => 11.88,
            
            // Éclairage par m²
            'eclairage_led_m2' => 5, 'eclairage_incandescent_m2' => 15,
            'coefficient_maison' => 1, 'coefficient_appartement' => 0.95,
            'tempo_jours_bleus' => 300, 'tempo_jours_blancs' => 43, 'tempo_jours_rouges' => 22
        );
    }
    
    public function get_default_gaz_residentiel() {
        return array(
            'communes_gaz' => array(
                'AIRE SUR L\'ADOUR', 'BARCELONNE DU GERS', 'BASCONS', 'BENESSE LES DAX',
                'CAMPAGNE', 'CARCARES SAINTE CROIX', 'GAAS', 'GEAUNE', 'LABATUT',
                'LALUQUE', 'MAZEROLLES', 'MEILHAN', 'MISSON', 'PONTONX SUR L\'ADOUR',
                'POUILLON', 'SAINT MAURICE', 'SOUPROSSE', 'TETHIEU', 'YGOS SAINT SATURNIN'
            ),
            'communes_types' => array(
                'AIRE SUR L\'ADOUR' => 'naturel', 'BARCELONNE DU GERS' => 'naturel', 'GAAS' => 'naturel',
                'LABATUT' => 'naturel', 'LALUQUE' => 'naturel', 'MISSON' => 'naturel', 'POUILLON' => 'naturel',
                'BASCONS' => 'propane', 'BENESSE LES DAX' => 'propane', 'CAMPAGNE' => 'propane',
                'CARCARES SAINTE CROIX' => 'propane', 'GEAUNE' => 'propane', 'MAZEROLLES' => 'propane',
                'MEILHAN' => 'propane', 'PONTONX SUR L\'ADOUR' => 'propane', 'SAINT MAURICE' => 'propane',
                'SOUPROSSE' => 'propane', 'TETHIEU' => 'propane', 'YGOS SAINT SATURNIN' => 'propane'
            ),
            'seuil_gom_naturel' => 4000,
            'gaz_naturel_gom0_abo' => 8.92, 'gaz_naturel_gom0_kwh' => 0.1265,
            'gaz_naturel_gom1_abo' => 22.42, 'gaz_naturel_gom1_kwh' => 0.0978,
            'gaz_propane_p0_abo' => 4.64, 'gaz_propane_p0_kwh' => 0.12479,
            'gaz_propane_p1_abo' => 5.26, 'gaz_propane_p1_kwh' => 0.11852,
            'gaz_propane_p2_abo' => 16.06, 'gaz_propane_p2_kwh' => 0.11305,
            'gaz_propane_p3_abo' => 34.56, 'gaz_propane_p3_kwh' => 0.10273,
            'gaz_propane_p4_abo' => 311.01, 'gaz_propane_p4_kwh' => 0.10064,
            'gaz_cuisson_par_personne' => 50,
            'gaz_eau_chaude_par_personne' => 400,
            'gaz_chauffage_niveau_1' => 160, 'gaz_chauffage_niveau_2' => 70,
            'gaz_chauffage_niveau_3' => 110, 'gaz_chauffage_niveau_4' => 20,
            'coefficient_maison' => 1.0, 'coefficient_appartement' => 0.8,
            'surface_min_chauffage' => 15, 'nb_personnes_min' => 1
        );
    }
        
    public function get_default_elec_professionnel() {
        return array(
            'pro_nom_offre_francaise' => 'Offre 100% française',
            'pro_nom_autre_offre' => 'Autre offre',
            'pro_trv_max_kva' => 36, 'pro_tempo_jours_rouges' => 22,
            'pro_tempo_jours_blancs' => 43, 'pro_tempo_jours_bleus' => 300,
            'pro_tempo_min_kva' => 9, 'pro_offre_fr_majoration' => 5,
            
            // Tarifs TRV BASE
            'pro_trv_base_abo_3' => 9.69, 'pro_trv_base_kwh_3' => 0.2516,
            'pro_trv_base_abo_6' => 12.67, 'pro_trv_base_kwh_6' => 0.2516,
            'pro_trv_base_abo_9' => 15.89, 'pro_trv_base_kwh_9' => 0.2516,
            'pro_trv_base_abo_12' => 19.16, 'pro_trv_base_kwh_12' => 0.2516,
            'pro_trv_base_abo_15' => 22.21, 'pro_trv_base_kwh_15' => 0.2516,
            'pro_trv_base_abo_18' => 25.24, 'pro_trv_base_kwh_18' => 0.2516,
            'pro_trv_base_abo_24' => 31.96, 'pro_trv_base_kwh_24' => 0.2516,
            'pro_trv_base_abo_30' => 37.68, 'pro_trv_base_kwh_30' => 0.2516,
            'pro_trv_base_abo_36' => 44.43, 'pro_trv_base_kwh_36' => 0.2516,
            
            // Tarifs TRV HEURES CREUSES
            'pro_trv_hc_abo_3' => 0, 'pro_trv_hc_hp_3' => 0, 'pro_trv_hc_hc_3' => 0,
            'pro_trv_hc_abo_6' => 13.28, 'pro_trv_hc_hp_6' => 0.27, 'pro_trv_hc_hc_6' => 0.2068,
            'pro_trv_hc_abo_9' => 16.82, 'pro_trv_hc_hp_9' => 0.27, 'pro_trv_hc_hc_9' => 0.2068,
            'pro_trv_hc_abo_12' => 20.28, 'pro_trv_hc_hp_12' => 0.27, 'pro_trv_hc_hc_12' => 0.2068,
            'pro_trv_hc_abo_15' => 23.57, 'pro_trv_hc_hp_15' => 0.27, 'pro_trv_hc_hc_15' => 0.2068,
            'pro_trv_hc_abo_18' => 26.84, 'pro_trv_hc_hp_18' => 0.27, 'pro_trv_hc_hc_18' => 0.2068,
            'pro_trv_hc_abo_24' => 33.70, 'pro_trv_hc_hp_24' => 0.27, 'pro_trv_hc_hc_24' => 0.2068,
            'pro_trv_hc_abo_30' => 39.94, 'pro_trv_hc_hp_30' => 0.27, 'pro_trv_hc_hc_30' => 0.2068,
            'pro_trv_hc_abo_36' => 46.24, 'pro_trv_hc_hp_36' => 0.27, 'pro_trv_hc_hc_36' => 0.2068,
            
            // Autres tarifs (TEMPO, offres)...
            'pro_seuil_salaries' => 10, 'pro_seuil_ca' => 3000000, 'pro_ratio_hp_defaut' => 60,
            'pro_cspe' => 22.5, 'pro_tcfe' => 9.5, 'pro_cta' => 2.71, 'pro_tva' => 20
        );
    }
    
    public function get_default_gaz_professionnel() {
        return array(
            'pro_gaz_abo' => 156.12, 'pro_gaz_kwh' => 0.0798,
            'pro_gaz_bureau' => 80, 'pro_gaz_commerce' => 120,
            'pro_gaz_restaurant' => 200, 'pro_gaz_artisanat' => 180, 'pro_gaz_industrie' => 300
        );
    }
}

// Initialisation du plugin
new HticSimulateurEnergieAdmin();

// Fonction utilitaire pour récupérer les données de configuration
function htic_get_simulateur_data($type) {
    return get_option('htic_simulateur_' . $type . '_data', array());
}

?>