<?php
/**
 * Plugin Name: HTIC Simulateur Consommation Énergie
 * Description: Plugin pour gérer et afficher le simulateur de consommation énergétique avec interface d'administration
 * Version: 1.0.0
 * Author: HTIC
 * Text Domain: htic-simulateur
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('HTIC_SIMULATEUR_URL', plugin_dir_url(__FILE__));
define('HTIC_SIMULATEUR_PATH', plugin_dir_path(__FILE__));
define('HTIC_SIMULATEUR_VERSION', '1.0.0');

class HticSimulateurEnergieAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_simulateur_data', array($this, 'save_simulateur_data'));
        

        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Shortcodes existants...
        add_shortcode('htic_simulateur_elec_residentiel', array($this, 'shortcode_elec_residentiel'));
        add_shortcode('htic_simulateur_gaz_residentiel', array($this, 'shortcode_gaz_residentiel'));
        add_shortcode('htic_simulateur_elec_professionnel', array($this, 'shortcode_elec_professionnel'));
        add_shortcode('htic_simulateur_gaz_professionnel', array($this, 'shortcode_gaz_professionnel'));
        add_shortcode('htic_simulateur_energie', array($this, 'shortcode_simulateur_unifie'));
        
        // AJAX handlers existants
        add_action('wp_ajax_htic_load_formulaire', array($this, 'ajax_load_formulaire'));
        add_action('wp_ajax_nopriv_htic_load_formulaire', array($this, 'ajax_load_formulaire'));

        add_action('wp_ajax_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));
        add_action('wp_ajax_nopriv_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));
    }
        
    public function activate() {
        $this->create_default_options();
        $this->create_tables();
        
        // Créer la structure des dossiers si elle n'existe pas
        $this->create_formulaires_structure();
    }
    
    private function create_formulaires_structure() {
        $base_path = HTIC_SIMULATEUR_PATH;
        
        // Créer les dossiers nécessaires
        $directories = array(
            'admin',
            'templates',
            'formulaires',
            'formulaires/elec-residentiel',
            'formulaires/gaz-residentiel', 
            'formulaires/elec-professionnel',
            'formulaires/gaz-professionnel',
            'includes'
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
    }
    
    public function register_settings() {
        register_setting('htic_simulateur_elec_residentiel', 'htic_simulateur_elec_residentiel_data');
        register_setting('htic_simulateur_gaz_residentiel', 'htic_simulateur_gaz_residentiel_data');
        register_setting('htic_simulateur_elec_professionnel', 'htic_simulateur_elec_professionnel_data');
        register_setting('htic_simulateur_gaz_professionnel', 'htic_simulateur_gaz_professionnel_data');
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
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        // Enqueue les ressources spécifiques
        $this->enqueue_formulaire_assets('elec-residentiel');
        
        // Capturer le contenu du template
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/elec-residentiel/elec-residentiel.php';
        return ob_get_clean();
    }
    
    public function shortcode_gaz_residentiel($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        $this->enqueue_formulaire_assets('gaz-residentiel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/gaz-residentiel/gaz-residentiel.php';
        return ob_get_clean();
    }
    
    public function shortcode_elec_professionnel($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        $this->enqueue_formulaire_assets('elec-professionnel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/elec-professionnel/elec-professionnel.php';
        return ob_get_clean();
    }
    
    public function shortcode_gaz_professionnel($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        $this->enqueue_formulaire_assets('gaz-professionnel');
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/gaz-professionnel/gaz-professionnel.php';
        return ob_get_clean();
    }
    
    // ================================
    // SHORTCODE PRINCIPAL UNIFIÉ
    // ================================
    
    public function shortcode_simulateur_unifie($atts) {
        $atts = shortcode_atts(array(
            'type' => '', // Type par défaut (optionnel)
            'theme' => 'default'
        ), $atts);
        
        // Enqueue les ressources du simulateur unifié
        $this->enqueue_simulateur_unifie_assets();
        
        // Capturer le contenu du template
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'templates/simulateur-unifie.php';
        return ob_get_clean();
    }
    
    private function enqueue_simulateur_unifie_assets() {
        // CSS du simulateur unifié
        wp_enqueue_style(
            'htic-simulateur-unifie-css',
            HTIC_SIMULATEUR_URL . 'templates/simulateur-unifie.css',
            array(),
            HTIC_SIMULATEUR_VERSION
        );
        
        // JS du simulateur unifié
        wp_enqueue_script(
            'htic-simulateur-unifie-js',
            HTIC_SIMULATEUR_URL . 'templates/simulateur-unifie.js',
            array('jquery'),
            HTIC_SIMULATEUR_VERSION,
            true
        );
        
        // AJOUTER CETTE LOCALISATION MANQUANTE :
        wp_localize_script(
            'htic-simulateur-unifie-js', 
            'hticSimulateurUnifix', 
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('htic_simulateur_nonce'),
                'calculateNonce' => wp_create_nonce('htic_simulateur_calculate'),
                'pluginUrl' => HTIC_SIMULATEUR_URL
            )
        );
    }
    
    // ================================
    // AJAX HANDLER POUR CHARGEMENT FORMULAIRES
    // ================================
    
    public function ajax_load_formulaire() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $type = sanitize_text_field($_POST['type']);
        
        // Vérifier que le type est valide
        $types_valides = array(
            'elec-residentiel',
            'gaz-residentiel', 
            'elec-professionnel',
            'gaz-professionnel'
        );
        
        if (!in_array($type, $types_valides)) {
            wp_send_json_error('Type de formulaire invalide');
            return;
        }
        
        // Charger le template du formulaire
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/' . $type . '/' . $type . '.php';
        
        if (!file_exists($template_path)) {
            wp_send_json_error('Template de formulaire non trouvé');
            return;
        }
        
        // Capturer le contenu du template
        ob_start();
        
        // Simuler les attributs pour le template
        $atts = array('theme' => 'default');
        
        include $template_path;
        $html = ob_get_clean();
        
        // Retourner le HTML
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
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        ob_start();
    
        $type = sanitize_text_field($_POST['type']);
        $userData = $_POST['user_data'];
        $configData = $_POST['config_data'];
        
        // Log pour debug (dans les logs serveur uniquement)
        error_log('🔍 HTIC AJAX CALCUL - Type: ' . $type);
        error_log('🔍 Nombre de champs utilisateur: ' . count($userData));
        
        // Vérifier que le type est valide
        $types_valides = array(
            'elec-residentiel',
            'gaz-residentiel', 
            'elec-professionnel',
            'gaz-professionnel'
        );
        
        if (!in_array($type, $types_valides)) {
            wp_send_json_error('Type de calculateur invalide: ' . $type);
            return;
        }
        
        // Charger le calculateur approprié
        $calculatorFile = HTIC_SIMULATEUR_PATH . 'includes/calculateur-' . str_replace('-', '-', $type) . '.php';
        
        if (!file_exists($calculatorFile)) {
            wp_send_json_error('Calculateur non trouvé pour le type: ' . $type);
            return;
        }
        
        // Inclure le fichier calculateur
        require_once $calculatorFile;
        
        try {
            // Appeler le calculateur approprié
            $results = null;
            
             switch ($type) {
                case 'elec-residentiel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_elec_residentiel_data', array());
                    }
                    // PASSER debugMode = false pour éviter les sorties HTML
                    $results = htic_calculateur_elec_residentiel($userData, $configData);
                    break;
                    
                case 'gaz-residentiel':
                    if (empty($configData)) {
                        $configData = get_option('htic_simulateur_gaz_residentiel_data', array());
                    }
                    // Créer la fonction équivalente pour le gaz
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

            $unwanted_output = ob_get_clean();
            if (!empty($unwanted_output)) {
                error_log('🧹 HTIC - Sortie nettoyée: ' . substr($unwanted_output, 0, 200) . '...');
            }
            
            if ($results && $results['success']) {
                error_log('✅ HTIC CALCUL RÉUSSI - Consommation: ' . $results['data']['consommation_annuelle'] . ' kWh/an');
                $this->saveCalculationLog($type, $userData, $results['data']);
                wp_send_json_success($results['data']);
            } else {
                $error = $results['error'] ?? 'Erreur inconnue lors du calcul';
                error_log('❌ HTIC CALCUL ÉCHOUÉ: ' . $error);
                wp_send_json_error($error);
            }
            
            
        } catch (Exception $e) {
            ob_end_clean();
            error_log('💥 HTIC EXCEPTION: ' . $e->getMessage());
            wp_send_json_error('Erreur technique: ' . $e->getMessage());
        }
}

private function saveCalculationLog($type, $userData, $results) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'htic_simulateur_logs';
    
    // Vérifier que la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return; // Table n'existe pas, ignorer
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
            
    // ================================
    // GESTION DES RESSOURCES
    // ================================
    
    private function enqueue_formulaire_assets($type) {
    $formulaire_path = HTIC_SIMULATEUR_PATH . 'formulaires/' . $type . '/';
    $formulaire_url = HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/';
    
    // Enqueue CSS commun
    if (file_exists(HTIC_SIMULATEUR_PATH . 'includes/common.css')) {
        wp_enqueue_style(
            'htic-simulateur-common', 
            HTIC_SIMULATEUR_URL . 'includes/common.css', 
            array(), 
            HTIC_SIMULATEUR_VERSION
        );
    }
    
    // Enqueue CSS spécifique
    if (file_exists($formulaire_path . $type . '.css')) {
        wp_enqueue_style(
            'htic-simulateur-' . $type, 
            $formulaire_url . $type . '.css', 
            array('htic-simulateur-common'), 
            HTIC_SIMULATEUR_VERSION
        );
    }
    
    // Enqueue JS commun
    if (file_exists(HTIC_SIMULATEUR_PATH . 'includes/common.js')) {
        wp_enqueue_script(
            'htic-simulateur-common-js', 
            HTIC_SIMULATEUR_URL . 'includes/common.js', 
            array('jquery'), 
            HTIC_SIMULATEUR_VERSION, 
            true
        );
    }
    
    // Enqueue JS spécifique
    if (file_exists($formulaire_path . $type . '.js')) {
        wp_enqueue_script(
            'htic-simulateur-' . $type . '-js', 
            $formulaire_url . $type . '.js', 
            array('jquery', 'htic-simulateur-common-js'), 
            HTIC_SIMULATEUR_VERSION, 
            true
        );
        
        // IMPORTANT: Localisation avec le bon nonce pour le calcul
        wp_localize_script(
            'htic-simulateur-' . $type . '-js', 
            'hticSimulateur', 
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('htic_simulateur_calculate'), // Bon nonce
                'type' => $type,
                'restUrl' => rest_url('htic-simulateur/v1/')
            )
        );
    }
}
    
    // ================================
    // ADMIN INTERFACE
    // ================================
    
    public function admin_page() {
        // Créer le dossier admin s'il n'existe pas
        $admin_path = HTIC_SIMULATEUR_PATH . 'admin/';
        if (!file_exists($admin_path)) {
            wp_mkdir_p($admin_path);
        }
        
        ?>
        <div class="wrap">
            <h1>Configuration du Simulateur Énergie HTIC</h1>
            
            <!-- Guide d'utilisation des shortcodes -->
            <div class="notice notice-info">
                <h3>📝 Shortcodes pour vos pages :</h3>
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
                    <!-- Onglet 1: Électricité Résidentiel -->
                    <div id="tab-elec-residentiel" class="tab-pane active">
                        <?php include $admin_path . 'admin-elec-residentiel.php'; ?>
                    </div>
                    
                    <!-- Onglet 2: Gaz Résidentiel -->
                    <div id="tab-gaz-residentiel" class="tab-pane">
                        <?php include $admin_path . 'admin-gaz-residentiel.php'; ?>
                    </div>
                    
                    <!-- Onglet 3: Électricité Professionnel -->
                    <div id="tab-elec-professionnel" class="tab-pane">
                        <?php include $admin_path . 'admin-elec-professionnel.php'; ?>
                    </div>
                    
                    <!-- Onglet 4: Gaz Professionnel -->
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
    
    // ================================
    // MÉTHODES PAR DÉFAUT AVEC DONNÉES COMPLÈTES
    // ================================
    
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
            
            // Autres données...
            'chauffe_eau' => 900,
            'lave_linge' => 100,
            'four' => 125,
            'seche_linge' => 175,
            'lave_vaisselle' => 100,
            'cave_vin' => 150,
            'refrigerateur' => 125,
            'congelateur' => 125,
            'plaque_cuisson' => 215,
            'tv_pc_box' => 300,
            'piscine' => 1400,
            'piscine_chauffee' => 4000,
            'spa_jacuzzi' => 2000,
            'aquarium' => 240,
            'voiture_electrique' => 1500,
            'climatiseur_mobile' => 150,
            'eclairage' => 750,
            'forfait_petits_electromenagers' => 150,
            
            // Répartitions
            'repartition_hp' => 60,
            'repartition_hc' => 40
        );
    }
    
    public function get_default_gaz_residentiel() {
        return array(
            'gaz_abo_base' => 102.12, 
            'gaz_kwh_base' => 0.0878,
            'gaz_chauffage_avant_1980' => 180, 
            'gaz_chauffage_1980_2000' => 120,
            'gaz_chauffage_apres_2000' => 80, 
            'gaz_chauffage_renovation' => 60,
            'gaz_eau_chaude' => 1200, 
            'gaz_cuisson' => 365
        );
    }
    
    public function get_default_elec_professionnel() {
        return array(
            'pro_elec_abo_6' => 15.67, 'pro_elec_kwh_6' => 0.2716,
            'pro_elec_abo_9' => 18.89, 'pro_elec_kwh_9' => 0.2716,
            'pro_elec_abo_12' => 22.28, 'pro_elec_kwh_12' => 0.2716,
            'pro_elec_abo_15' => 25.57, 'pro_elec_kwh_15' => 0.2716,
            'pro_elec_abo_18' => 28.84, 'pro_elec_kwh_18' => 0.2716,
            'pro_elec_abo_24' => 35.96, 'pro_elec_kwh_24' => 0.2716,
            'pro_elec_abo_36' => 48.43, 'pro_elec_kwh_36' => 0.2716,
            'pro_bureau' => 120, 'pro_commerce' => 180,
            'pro_restaurant' => 300, 'pro_artisanat' => 250, 'pro_industrie_legere' => 400
        );
    }
    
    public function get_default_gaz_professionnel() {
        return array(
            'pro_gaz_abo' => 156.12, 'pro_gaz_kwh' => 0.0798,
            'pro_gaz_bureau' => 80, 'pro_gaz_commerce' => 120,
            'pro_gaz_restaurant' => 200, 'pro_gaz_artisanat' => 180, 'pro_gaz_industrie' => 300
        );
    }
    
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
}

// Initialiser le plugin
new HticSimulateurEnergieAdmin();

// Fonction utilitaire pour récupérer les données du simulateur
function htic_get_simulateur_data($type) {
    return get_option('htic_simulateur_' . $type . '_data', array());
}

?>