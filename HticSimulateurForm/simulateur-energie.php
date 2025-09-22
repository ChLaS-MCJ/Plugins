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

define('HTIC_SIMULATEUR_URL', plugin_dir_url(__FILE__));
define('HTIC_SIMULATEUR_PATH', plugin_dir_path(__FILE__));
define('HTIC_SIMULATEUR_VERSION', '1.0.0');

class HticSimulateurEnergieAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_simulateur_data', array($this, 'save_simulateur_data'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_shortcode('htic_simulateur_elec_residentiel', array($this, 'shortcode_elec_residentiel'));
        add_shortcode('htic_simulateur_gaz_residentiel', array($this, 'shortcode_gaz_residentiel'));
        add_shortcode('htic_simulateur_elec_professionnel', array($this, 'shortcode_elec_professionnel'));
        add_shortcode('htic_simulateur_gaz_professionnel', array($this, 'shortcode_gaz_professionnel'));
        add_shortcode('htic_simulateur_energie', array($this, 'shortcode_simulateur_unifie'));
        
        add_action('wp_ajax_htic_load_formulaire', array($this, 'ajax_load_formulaire'));
        add_action('wp_ajax_nopriv_htic_load_formulaire', array($this, 'ajax_load_formulaire'));

        add_action('wp_ajax_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));
        add_action('wp_ajax_nopriv_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));

        add_action('wp_ajax_htic_get_communes_gaz', array($this, 'ajax_get_communes_gaz'));
        add_action('wp_ajax_nopriv_htic_get_communes_gaz', array($this, 'ajax_get_communes_gaz'));

        add_shortcode('htic_contact_form', array($this, 'shortcode_contact_form'));
        add_action('wp_ajax_htic_contact_submit', array($this, 'ajax_contact_submit'));
        add_action('wp_ajax_nopriv_htic_contact_submit', array($this, 'ajax_contact_submit'));
        
        add_action('wp_ajax_htic_recalculate_with_power', array($this, 'ajax_recalculate_with_power'));
        add_action('wp_ajax_nopriv_htic_recalculate_with_power', array($this, 'ajax_recalculate_with_power'));

        add_action('wp_ajax_process_electricity_form', array($this, 'process_electricity_form'));
        add_action('wp_ajax_nopriv_process_electricity_form', array($this, 'process_electricity_form'));
        
        // Initialiser le système d'emails
        $this->init_email_system();

        // Test email Brevo
        add_action('wp_ajax_test_brevo_email', array($this, 'test_brevo_email'));
    }
        
    public function activate() {
        $this->create_default_options();
        $this->create_tables();
        $this->create_formulaires_structure();
    }

    public function init_email_system() {
     require_once plugin_dir_path(__FILE__) . 'includes/SendEmail/init.php';
    }
    
    private function create_formulaires_structure() {
        $base_path = HTIC_SIMULATEUR_PATH;
        
        $directories = array(
            'admin',
            'templates',
            'formulaires',
            'formulaires/elec-residentiel',
            'formulaires/gaz-residentiel', 
            'formulaires/elec-professionnel',
            'formulaires/gaz-professionnel',
            'formulaires/contact',
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
        register_setting('htic_simulateur_elec_residentiel', 'htic_simulateur_elec_residentiel_data');
        register_setting('htic_simulateur_gaz_residentiel', 'htic_simulateur_gaz_residentiel_data');
        register_setting('htic_simulateur_elec_professionnel', 'htic_simulateur_elec_professionnel_data');
        register_setting('htic_simulateur_gaz_professionnel', 'htic_simulateur_gaz_professionnel_data');

        register_setting('htic_contact_settings', 'htic_contact_email');

        register_setting('htic_brevo_settings', 'brevo_api_key');
        register_setting('htic_brevo_settings', 'ges_notification_email');
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
        
        $this->enqueue_formulaire_assets('elec-residentiel');
        
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
            'type' => '',
            'theme' => 'default'
        ), $atts);
        
        $this->enqueue_simulateur_unifie_assets();
        
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
    
    public function ajax_load_formulaire() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $type = sanitize_text_field($_POST['type']);
        
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

            $unwanted_output = ob_get_clean();
            
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

    public function process_electricity_form() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }
        
        // Récupérer les données JSON
        $form_data = isset($_POST['form_data']) ? json_decode(stripslashes($_POST['form_data']), true) : array();
        
        if (empty($form_data)) {
            wp_send_json_error('Aucune donnée reçue');
            return;
        }
        
        // Traiter les fichiers uploadés
        $uploaded_files = $this->process_uploaded_files();
        
        try {
            // Inclure EmailHandler
            require_once HTIC_SIMULATEUR_PATH . 'includes/SendEmail/EmailHandler.php';
            
            // Ajouter les fichiers aux données
            $form_data['uploaded_files'] = $uploaded_files;
            
            // Traiter avec EmailHandler
            $emailHandler = new EmailHandler();
            $result = $emailHandler->processFormData(json_encode($form_data));
            
            // Nettoyer les fichiers temporaires après envoi
            $this->cleanup_uploaded_files($uploaded_files);
            
            if ($result['success']) {
                // Sauvegarder en base
                $this->save_simulation_to_db($form_data);
                
                wp_send_json_success([
                    'message' => 'Simulation envoyée avec succès',
                    'referenceNumber' => 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
                ]);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Traiter les fichiers uploadés
     */
    private function process_uploaded_files() {
        $files = array();
        
        // Liste des fichiers attendus
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
                    
                    error_log("Fichier uploadé: $file_key -> $file_path");
                }
            }
        }
        
        return $files;
    }

    /**
     * Nettoyer les fichiers temporaires
     */
    private function cleanup_uploaded_files($files) {
        foreach ($files as $file_info) {
            if (isset($file_info['path']) && file_exists($file_info['path'])) {
                unlink($file_info['path']);
            }
        }
    }


    // ================================
    // GESTION DES RESSOURCES
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
    
        // CETTE PARTIE EST CRUCIALE
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
            
        } else {
            error_log("ERROR: File not found for: " . $type . " at path: " . $js_file);
        }
    }
   
    // ================================
    // ADMIN INTERFACE
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

    public function brevo_config_page() {
        // Sauvegarder si formulaire soumis
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
            
            <div class="card">
                <h2>📧 Comment configurer Brevo</h2>
                <ol>
                    <li>Créez un compte sur <a href="https://www.brevo.com" target="_blank">Brevo.com</a></li>
                    <li>Allez dans <strong>SMTP & API</strong> > <strong>API Keys</strong></li>
                    <li>Créez une nouvelle clé API et copiez-la ici</li>
                    <li>Les emails utiliseront automatiquement votre template HTML personnalisé</li>
                </ol>
                
                <h3>Template utilisé :</h3>
                <p>Le système utilise le template <code>base-template.php</code> qui génère :</p>
                <ul>
                    <li>✅ Un email personnalisé pour le client</li>
                    <li>✅ Un email détaillé pour GES</li>
                    <li>✅ Un PDF récapitulatif en pièce jointe</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>🧪 Test d'envoi</h2>
                <p>Testez la configuration en envoyant un email de test :</p>
                <button type="button" class="button button-primary" id="test-brevo-email">
                    Envoyer un email de test
                </button>
                <div id="test-result"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-brevo-email').on('click', function() {
                var $btn = $(this);
                var $result = $('#test-result');
                
                $btn.prop('disabled', true).text('Envoi en cours...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_brevo_email',
                        nonce: '<?php echo wp_create_nonce('test_brevo'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Envoyer un email de test');
                    }
                });
            });
        });
        </script>
        <?php
        }

        public function simulations_list_page() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'simulations_electricite';
            
            // Mettre à jour la structure de la table si nécessaire
            $this->upgrade_simulations_table();
            
            // Traitement des actions
            if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'], 'simulation_action')) {
                $this->handle_simulation_action($_POST);
            }
            
            // Vérifier si la table existe
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if (!$table_exists) {
                $this->create_simulations_table();
            }
            
            // Récupérer les simulations
            $simulations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50");
            ?>
            
            <div class="wrap">
                <h1>Simulations Électricité Reçues</h1>
                
                <?php if (empty($simulations)) : ?>
                    <div class="notice notice-info">
                        <p>Aucune simulation reçue pour le moment.</p>
                    </div>
                <?php else : ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="12%">Date</th>
                            <th width="15%">Type</th>
                            <th width="18%">Nom</th>
                            <th width="20%">Email</th>
                            <th width="12%">Téléphone</th>
                            <th width="8%">CP</th>
                            <th width="12%">Estimation</th>
                            <th width="10%">Statut</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($simulations as $sim) : ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($sim->created_at)); ?></td>
                            <td>
                                <span class="simulation-type type-<?php echo $this->get_simulation_type($sim); ?>">
                                    <?php echo $this->get_simulation_type_label($sim); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($sim->first_name . ' ' . $sim->last_name); ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($sim->email); ?>">
                                    <?php echo esc_html($sim->email); ?>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?php echo esc_attr($sim->phone); ?>">
                                    <?php echo esc_html($sim->phone); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($sim->postal_code); ?></td>
                            <td>
                                <strong><?php echo number_format($sim->monthly_estimate, 2, ',', ' '); ?> €/mois</strong>
                            </td>
                            <td>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('simulation_action', 'nonce'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="sim_id" value="<?php echo $sim->id; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select status-<?php echo $sim->status; ?>">
                                        <option value="non_traite" <?php selected($sim->status, 'non_traite'); ?>>❌ Non traité</option>
                                        <option value="en_cours" <?php selected($sim->status, 'en_cours'); ?>>⏳ En cours</option>
                                        <option value="traite" <?php selected($sim->status, 'traite'); ?>>✅ Traité</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <button class="button button-small view-details" 
                                        data-id="<?php echo $sim->id; ?>">
                                    👁️ Détails
                                </button>
                                
                                <form method="post" style="display: inline-block; margin-left: 5px;" 
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette simulation ?');">
                                    <?php wp_nonce_field('simulation_action', 'nonce'); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="sim_id" value="<?php echo $sim->id; ?>">
                                    <button type="submit" class="button button-small button-link-delete">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php endif; ?>
            </div>
            
            <!-- Modal pour les détails -->
            <div id="simulation-modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Détails de la simulation</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body" id="simulation-details"></div>
                </div>
            </div>
            
            <style>
            .simulation-type {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .type-elec-residentiel {
                background: #e0f2fe;
                color: #0277bd;
            }
            
            .type-elec-professionnel {
                background: #f3e5f5;
                color: #7b1fa2;
            }
            
            .type-gaz-residentiel {
                background: #fff3e0;
                color: #f57c00;
            }
            
            .type-gaz-professionnel {
                background: #e8f5e8;
                color: #388e3c;
            }
            
            .status-select {
                border: none;
                padding: 4px 6px;
                border-radius: 4px;
                font-size: 12px;
                cursor: pointer;
            }
            
            .status-select.status-non_traite {
                background: #ffebee;
                color: #c62828;
            }
            
            .status-select.status-en_cours {
                background: #fff8e1;
                color: #f57c00;
            }
            
            .status-select.status-traite {
                background: #e8f5e8;
                color: #2e7d32;
            }
            
            #simulation-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .modal-content {
                background: white;
                max-width: 800px;
                max-height: 90vh;
                overflow-y: auto;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            }
            
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid #e0e0e0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f5f5f5;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .detail-section {
                margin-bottom: 25px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 8px;
            }
            
            .detail-section h4 {
                margin: 0 0 15px 0;
                color: #333;
                border-bottom: 2px solid #e0e0e0;
                padding-bottom: 8px;
            }
            
            .detail-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .detail-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e8e8e8;
            }
            
            .detail-label {
                font-weight: 600;
                color: #555;
            }
            
            .detail-value {
                color: #333;
                text-align: right;
            }
            
            .highlight-value {
                background: #e3f2fd;
                padding: 2px 6px;
                border-radius: 4px;
                font-weight: 600;
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('.view-details').on('click', function() {
                    var simId = $(this).data('id');
                    
                    // Ici on va chercher les détails via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_simulation_details',
                            sim_id: simId,
                            nonce: '<?php echo wp_create_nonce('get_details'); ?>'
                        },
                        success: function(response) {
                            $('#simulation-details').html(response);
                            $('#simulation-modal').show();
                        }
                    });
                });
                
                $('.modal-close').on('click', function() {
                    $('#simulation-modal').hide();
                });
                
                $(document).on('click', function(e) {
                    if (e.target.id === 'simulation-modal') {
                        $('#simulation-modal').hide();
                    }
                });
            });
            </script>
            <?php
        }

        private function get_simulation_type($sim) {
    // Analyser les données JSON pour déterminer le type
    $data = json_decode($sim->data_json, true);
    
    if (isset($data['type'])) {
        return $data['type'];
    }
    
    // Si pas de type, deviner basé sur les données disponibles
    if (isset($data['housingType'])) {
        return 'elec-residentiel'; // Par défaut pour l'électricité résidentielle
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

        private function handle_simulation_actions() {
            // Enregistrer les actions AJAX
            add_action('wp_ajax_update_simulation_status', array($this, 'ajax_update_simulation_status'));
            add_action('wp_ajax_delete_simulation', array($this, 'ajax_delete_simulation'));
            add_action('wp_ajax_get_simulation_details', array($this, 'ajax_get_simulation_details'));
        }

        public function ajax_update_simulation_status() {
            if (!wp_verify_nonce($_POST['nonce'], 'simulation_action') || !current_user_can('manage_options')) {
                wp_send_json_error('Non autorisé');
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'simulations_electricite';
            
            $id = intval($_POST['id']);
            $status = sanitize_text_field($_POST['status']);
            
            $valid_statuses = array('non_traite', 'en_cours', 'traite');
            if (!in_array($status, $valid_statuses)) {
                wp_send_json_error('Statut invalide');
            }
            
            $update_data = array('status' => $status);
            if ($status === 'traite') {
                $update_data['treated_at'] = current_time('mysql');
                $update_data['treated_by'] = get_current_user_id();
            }
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $id)
            );
            
            if ($result !== false) {
                wp_send_json_success('Statut mis à jour');
            } else {
                wp_send_json_error('Erreur lors de la mise à jour');
            }
        }

        public function ajax_delete_simulation() {
            if (!wp_verify_nonce($_POST['nonce'], 'simulation_action') || !current_user_can('manage_options')) {
                wp_send_json_error('Non autorisé');
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'simulations_electricite';
            
            $id = intval($_POST['id']);
            
            $result = $wpdb->delete($table_name, array('id' => $id));
            
            if ($result !== false) {
                wp_send_json_success('Simulation supprimée');
            } else {
                wp_send_json_error('Erreur lors de la suppression');
            }
        }

        public function ajax_get_simulation_details() {
            if (!wp_verify_nonce($_POST['nonce'], 'simulation_action') || !current_user_can('manage_options')) {
                wp_send_json_error('Non autorisé');
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'simulations_electricite';
            
            $id = intval($_POST['id']);
            $sim = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
            
            if (!$sim) {
                wp_send_json_error('Simulation non trouvée');
            }
            
            $data = json_decode($sim->data_json, true);
            
            ob_start();
            ?>
            <div class="simulation-detail-view">
                <div class="detail-section">
                    <h4>Informations client</h4>
                    <p><strong>Nom:</strong> <?php echo esc_html($sim->first_name . ' ' . $sim->last_name); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($sim->email); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo esc_html($sim->phone); ?></p>
                </div>
                
                <div class="detail-section">
                    <h4>Détails logement</h4>
                    <p><strong>Surface:</strong> <?php echo esc_html($sim->surface); ?> m²</p>
                    <p><strong>Residents:</strong> <?php echo esc_html($sim->residents); ?></p>
                    <p><strong>Type:</strong> <?php echo esc_html($sim->housing_type); ?></p>
                </div>
                
                <div class="detail-section">
                    <h4>Estimation</h4>
                    <p><strong>Consommation annuelle:</strong> <?php echo number_format($sim->annual_consumption); ?> kWh</p>
                    <p><strong>Tarif choisi:</strong> <?php echo esc_html($sim->tarif_chosen); ?></p>
                    <p><strong>Puissance:</strong> <?php echo esc_html($sim->power_chosen); ?> kVA</p>
                    <p><strong>Estimation mensuelle:</strong> <?php echo number_format($sim->monthly_estimate, 2); ?> €</p>
                </div>
                
                <div class="detail-section">
                    <h4>Données brutes</h4>
                    <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
        <?php echo json_encode($data, JSON_PRETTY_PRINT); ?>
                    </pre>
                </div>
            </div>
            <?php
            
            wp_send_json_success(ob_get_clean());
        }

        private function get_status_label($status) {
            $labels = array(
                'non_traite' => 'Non traitée',
                'en_cours' => 'En cours',
                'traite' => 'Traitée'
            );
            return $labels[$status] ?? $status;
        }


    // Sauvegarder la simulation en base
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


    public function test_brevo_email() {
        if (!wp_verify_nonce($_POST['nonce'], 'test_brevo')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }
        
        $api_key = get_option('brevo_api_key');
        if (empty($api_key)) {
            wp_send_json_error('Clé API Brevo non configurée');
            return;
        }
        
        // Créer des données de test
        $test_data = [
            'firstName' => 'Test',
            'lastName' => 'Simulation',
            'email' => get_option('admin_email'),
            'phone' => '01 23 45 67 89',
            'postalCode' => '75001',
            'housingType' => 'appartement',
            'surface' => '80',
            'residents' => '2',
            'monthlyEstimate' => 125.50,
            'annualConsumption' => 3500
        ];
        
        // Tester l'envoi
        require_once HTIC_SIMULATEUR_PATH . 'includes/SendEmail/EmailHandler.php';
        $emailHandler = new EmailHandler();
        $result = $emailHandler->processFormData(json_encode($test_data));
        
        if ($result['success']) {
            wp_send_json_success('Email de test envoyé avec succès à ' . $test_data['email']);
        } else {
            wp_send_json_error('Erreur: ' . $result['message']);
        }
    }

    // Méthode pour créer la table des simulations
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

    private function upgrade_simulations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simulations_electricite';
        
        // Vérifier si la colonne status existe
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'status'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN status VARCHAR(50) DEFAULT 'non_traite' AFTER data_json");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN notes TEXT AFTER status");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN treated_at DATETIME NULL AFTER notes");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN treated_by BIGINT(20) NULL AFTER treated_at");
        }
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
            
            // Équipements et consommations
            'chauffe_eau' => 900,
            'chauffe_eau_puissance' => 2400,
            'chauffe_eau_simultaneite' => 30,
            
            'lave_linge' => 100,
            'lave_linge_puissance' => 2000,
            'lave_linge_simultaneite' => 50,
            
            'four' => 125,
            'four_puissance' => 2000,
            'four_simultaneite' => 50,
            
            'seche_linge' => 175,
            'seche_linge_puissance' => 2500,
            'seche_linge_simultaneite' => 50,
            
            'lave_vaisselle' => 100,
            'lave_vaisselle_puissance' => 1800,
            'lave_vaisselle_simultaneite' => 50,
            
            'cave_a_vin' => 150,
            'cave_a_vin_puissance' => 1000,
            'cave_a_vin_simultaneite' => 50,
            
            'refrigerateur' => 125,
            'refrigerateur_puissance' => 150,
            'refrigerateur_simultaneite' => 80,
            
            'congelateur' => 125,
            'congelateur_puissance' => 200,
            'congelateur_simultaneite' => 80,
            
            'plaque_induction' => 180,
            'plaque_induction_puissance' => 3500,
            'plaque_induction_simultaneite' => 30,

            'plaque_vitroceramique' => 250,
            'plaque_vitroceramique_puissance' => 3000,
            'plaque_vitroceramique_simultaneite' => 30,
            
            'tv_pc_box' => 300,
            'tv_pc_box_puissance' => 500,
            'tv_pc_box_simultaneite' => 80,
            
            'piscine' => 1400,
            'piscine_puissance' => 2500,
            'piscine_simultaneite' => 80,
            
            'piscine_chauffee' => 4000,
            
            'spa_jacuzzi' => 2000,
            'spa_jacuzzi_puissance' => 2000,
            'spa_jacuzzi_simultaneite' => 50,
            
            'aquarium' => 240,
            'aquarium_puissance' => 100,
            'aquarium_simultaneite' => 80,
            
            'voiture_electrique' => 1500,
            'voiture_electrique_puissance' => 7000,
            'voiture_electrique_simultaneite' => 30,
            
            'climatiseur_mobile' => 150,
            'climatiseur_mobile_puissance' => 3000,
            'climatiseur_mobile_simultaneite' => 50,
            
            'chauffage_m2_puissance' => 50,
            'chauffage_m2_simultaneite' => 80,
            
            'eclairage' => 750,
            'eclairage_puissance' => 500,
            'eclairage_simultaneite' => 80,
            
            'forfait_petits_electromenagers' => 150,
            
            // Répartitions
            'repartition_hp' => 60,
            'repartition_hc' => 40,
            
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
            'maison_convecteurs_mauvaise' => 215,
            'maison_convecteurs_moyenne' => 150,
            'maison_convecteurs_bonne' => 75,
            'maison_convecteurs_tres_bonne' => 37.5,
            
            'maison_inertie_mauvaise' => 185,
            'maison_inertie_moyenne' => 125,
            'maison_inertie_bonne' => 65,
            'maison_inertie_tres_bonne' => 30,
            
            'maison_clim_reversible_mauvaise' => 100,
            'maison_clim_reversible_moyenne' => 70,
            'maison_clim_reversible_bonne' => 45,
            'maison_clim_reversible_tres_bonne' => 17.5,
            
            'maison_pac_mauvaise' => 80,
            'maison_pac_moyenne' => 52.5,
            'maison_pac_bonne' => 35,
            'maison_pac_tres_bonne' => 12.5,
            
            // Chauffage par m² - Appartement
            'appartement_convecteurs_mauvaise' => 204.25,
            'appartement_convecteurs_moyenne' => 142.5,
            'appartement_convecteurs_bonne' => 71.25,
            'appartement_convecteurs_tres_bonne' => 35.63,
            
            'appartement_inertie_mauvaise' => 175.75,
            'appartement_inertie_moyenne' => 118.75,
            'appartement_inertie_bonne' => 61.75,
            'appartement_inertie_tres_bonne' => 28.5,
            
            'appartement_clim_reversible_mauvaise' => 95,
            'appartement_clim_reversible_moyenne' => 66.5,
            'appartement_clim_reversible_bonne' => 42.75,
            'appartement_clim_reversible_tres_bonne' => 16.63,
            
            'appartement_pac_mauvaise' => 76,
            'appartement_pac_moyenne' => 49.88,
            'appartement_pac_bonne' => 33.25,
            'appartement_pac_tres_bonne' => 11.88,
            
            // Éclairage par m²
            'eclairage_led_m2' => 5,
            'eclairage_incandescent_m2' => 15,

            'coefficient_maison' => 1,
            'coefficient_appartement' => 0.95,

            'tempo_jours_bleus' => 300,
            'tempo_jours_blancs' => 43,
            'tempo_jours_rouges' => 22
        );
    }
    
    public function get_default_gaz_residentiel() {
        return array(
            // COMMUNES (19 total) - Excel M20:N38
            'communes_gaz' => array(
                'AIRE SUR L\'ADOUR',
                'BARCELONNE DU GERS', 
                'BASCONS',
                'BENESSE LES DAX',
                'CAMPAGNE',
                'CARCARES SAINTE CROIX',
                'GAAS',
                'GEAUNE',
                'LABATUT',
                'LALUQUE',
                'MAZEROLLES',
                'MEILHAN',
                'MISSON',
                'PONTONX SUR L\'ADOUR',
                'POUILLON',
                'SAINT MAURICE',
                'SOUPROSSE',
                'TETHIEU',
                'YGOS SAINT SATURNIN'
            ),
            
            // TYPES GAZ PAR COMMUNE - Excel exact
            'communes_types' => array(
                'AIRE SUR L\'ADOUR' => 'naturel',
                'BARCELONNE DU GERS' => 'naturel', 
                'BASCONS' => 'propane',
                'BENESSE LES DAX' => 'propane',
                'CAMPAGNE' => 'propane',
                'CARCARES SAINTE CROIX' => 'propane',
                'GAAS' => 'naturel',
                'GEAUNE' => 'propane',
                'LABATUT' => 'naturel',
                'LALUQUE' => 'naturel',
                'MAZEROLLES' => 'propane',
                'MEILHAN' => 'propane',
                'MISSON' => 'naturel',
                'PONTONX SUR L\'ADOUR' => 'propane',
                'POUILLON' => 'naturel',
                'SAINT MAURICE' => 'propane',
                'SOUPROSSE' => 'propane',
                'TETHIEU' => 'propane',
                'YGOS SAINT SATURNIN' => 'propane'
            ),
            
            // GAZ NATUREL (2 tranches GOM0/GOM1) - Excel P9:Q10
            'seuil_gom_naturel' => 4000, // P6
            'gaz_naturel_gom0_abo' => 8.92, // P9 = 107.04/12
            'gaz_naturel_gom0_kwh' => 0.1265, // P10
            'gaz_naturel_gom1_abo' => 22.42, // Q9 = 269.01/12  
            'gaz_naturel_gom1_kwh' => 0.0978, // Q10
            
            // GAZ PROPANE (5 tranches P0-P4) - Excel P14:T15
            'gaz_propane_p0_abo' => 4.64, // P14 = 55.7/12
            'gaz_propane_p0_kwh' => 0.12479, // P15
            'gaz_propane_p1_abo' => 5.26, // Q14 = 63.09/12
            'gaz_propane_p1_kwh' => 0.11852, // Q15
            'gaz_propane_p2_abo' => 16.06, // R14 calculé
            'gaz_propane_p2_kwh' => 0.11305, // R15
            'gaz_propane_p3_abo' => 34.56, // S14 calculé
            'gaz_propane_p3_kwh' => 0.10273, // S15
            'gaz_propane_p4_abo' => 311.01, // T14 calculé
            'gaz_propane_p4_kwh' => 0.10064, // T15
            
            // CONSOMMATIONS PAR USAGE - Excel K28, K29
            'gaz_cuisson_par_personne' => 50, // K28 - PAS de base
            'gaz_eau_chaude_par_personne' => 400, // K29 - PAS de base
            
            // ISOLATION (4 niveaux) - Excel G28:H31 
            'gaz_chauffage_niveau_1' => 160, // H28
            'gaz_chauffage_niveau_2' => 70, // H29
            'gaz_chauffage_niveau_3' => 110, // H30
            'gaz_chauffage_niveau_4' => 20, // H31
            
            // COEFFICIENTS LOGEMENT
            'coefficient_maison' => 1.0,
            'coefficient_appartement' => 0.8,
            
            // PARAMÈTRES DIVERS
            'surface_min_chauffage' => 15,
            'nb_personnes_min' => 1
        );
    }
        
    public function get_default_elec_professionnel() {
        return array(
            // Noms des offres (administrables)
            'pro_nom_offre_francaise' => 'Offre 100% française',
            'pro_nom_autre_offre' => 'Autre offre',
            
            // Paramètres des descriptions (administrables)
            'pro_trv_max_kva' => 36,
            'pro_tempo_jours_rouges' => 22,
            'pro_tempo_jours_blancs' => 43,
            'pro_tempo_jours_bleus' => 300,
            'pro_tempo_min_kva' => 9,
            'pro_offre_fr_majoration' => 5,
            
            // TABLEAU 1: Tarifs TRV BASE (€ HT)
            'pro_trv_base_abo_3' => 9.69,
            'pro_trv_base_kwh_3' => 0.2516,
            'pro_trv_base_abo_6' => 12.67,
            'pro_trv_base_kwh_6' => 0.2516,
            'pro_trv_base_abo_9' => 15.89,
            'pro_trv_base_kwh_9' => 0.2516,
            'pro_trv_base_abo_12' => 19.16,
            'pro_trv_base_kwh_12' => 0.2516,
            'pro_trv_base_abo_15' => 22.21,
            'pro_trv_base_kwh_15' => 0.2516,
            'pro_trv_base_abo_18' => 25.24,
            'pro_trv_base_kwh_18' => 0.2516,
            'pro_trv_base_abo_24' => 31.96,
            'pro_trv_base_kwh_24' => 0.2516,
            'pro_trv_base_abo_30' => 37.68,
            'pro_trv_base_kwh_30' => 0.2516,
            'pro_trv_base_abo_36' => 44.43,
            'pro_trv_base_kwh_36' => 0.2516,
            
            // TABLEAU 1: Tarifs TRV HEURES CREUSES (€ HT)
            'pro_trv_hc_abo_3' => 0,
            'pro_trv_hc_hp_3' => 0,
            'pro_trv_hc_hc_3' => 0,
            'pro_trv_hc_abo_6' => 13.28,
            'pro_trv_hc_hp_6' => 0.27,
            'pro_trv_hc_hc_6' => 0.2068,
            'pro_trv_hc_abo_9' => 16.82,
            'pro_trv_hc_hp_9' => 0.27,
            'pro_trv_hc_hc_9' => 0.2068,
            'pro_trv_hc_abo_12' => 20.28,
            'pro_trv_hc_hp_12' => 0.27,
            'pro_trv_hc_hc_12' => 0.2068,
            'pro_trv_hc_abo_15' => 23.57,
            'pro_trv_hc_hp_15' => 0.27,
            'pro_trv_hc_hc_15' => 0.2068,
            'pro_trv_hc_abo_18' => 26.84,
            'pro_trv_hc_hp_18' => 0.27,
            'pro_trv_hc_hc_18' => 0.2068,
            'pro_trv_hc_abo_24' => 33.70,
            'pro_trv_hc_hp_24' => 0.27,
            'pro_trv_hc_hc_24' => 0.2068,
            'pro_trv_hc_abo_30' => 39.94,
            'pro_trv_hc_hp_30' => 0.27,
            'pro_trv_hc_hc_30' => 0.2068,
            'pro_trv_hc_abo_36' => 46.24,
            'pro_trv_hc_hp_36' => 0.27,
            'pro_trv_hc_hc_36' => 0.2068,
            
            // TABLEAU 2: Tarifs TEMPO PRO (€ HT)
            'pro_tempo_abo_9' => 13.23,
            'pro_tempo_rouge_hp_9' => 0.7562,
            'pro_tempo_rouge_hc_9' => 0.1568,
            'pro_tempo_blanc_hp_9' => 0.1894,
            'pro_tempo_blanc_hc_9' => 0.1486,
            'pro_tempo_bleu_hp_9' => 0.1609,
            'pro_tempo_bleu_hc_9' => 0.1296,
            
            'pro_tempo_abo_12' => 16.55,
            'pro_tempo_rouge_hp_12' => 0.7562,
            'pro_tempo_rouge_hc_12' => 0.1568,
            'pro_tempo_blanc_hp_12' => 0.1894,
            'pro_tempo_blanc_hc_12' => 0.1486,
            'pro_tempo_bleu_hp_12' => 0.1609,
            'pro_tempo_bleu_hc_12' => 0.1296,
            
            'pro_tempo_abo_15' => 23.08,
            'pro_tempo_rouge_hp_15' => 0.7562,
            'pro_tempo_rouge_hc_15' => 0.1568,
            'pro_tempo_blanc_hp_15' => 0.1894,
            'pro_tempo_blanc_hc_15' => 0.1486,
            'pro_tempo_bleu_hp_15' => 0.1609,
            'pro_tempo_bleu_hc_15' => 0.1296,
            
            'pro_tempo_abo_18' => 26.18,
            'pro_tempo_rouge_hp_18' => 0.7562,
            'pro_tempo_rouge_hc_18' => 0.1568,
            'pro_tempo_blanc_hp_18' => 0.1894,
            'pro_tempo_blanc_hc_18' => 0.1486,
            'pro_tempo_bleu_hp_18' => 0.1609,
            'pro_tempo_bleu_hc_18' => 0.1296,
            
            'pro_tempo_abo_24' => 38.22,
            'pro_tempo_rouge_hp_24' => 0.7562,
            'pro_tempo_rouge_hc_24' => 0.1568,
            'pro_tempo_blanc_hp_24' => 0.1894,
            'pro_tempo_blanc_hc_24' => 0.1486,
            'pro_tempo_bleu_hp_24' => 0.1609,
            'pro_tempo_bleu_hc_24' => 0.1296,
            
            'pro_tempo_abo_30' => 39.50,
            'pro_tempo_rouge_hp_30' => 0.7562,
            'pro_tempo_rouge_hc_30' => 0.1568,
            'pro_tempo_blanc_hp_30' => 0.1894,
            'pro_tempo_blanc_hc_30' => 0.1486,
            'pro_tempo_bleu_hp_30' => 0.1609,
            'pro_tempo_bleu_hc_30' => 0.1296,
            
            'pro_tempo_abo_36' => 45.87,
            'pro_tempo_rouge_hp_36' => 0.7562,
            'pro_tempo_rouge_hc_36' => 0.1568,
            'pro_tempo_blanc_hp_36' => 0.1894,
            'pro_tempo_blanc_hc_36' => 0.1486,
            'pro_tempo_bleu_hp_36' => 0.1609,
            'pro_tempo_bleu_hc_36' => 0.1296,
            
            // TABLEAU 3: Offre 100% française (TRV + 5%)
            'pro_offre_fr_base_abo_3' => 9.69,
            'pro_offre_fr_base_kwh_3' => 0.2642,
            'pro_offre_fr_base_abo_6' => 12.67,
            'pro_offre_fr_base_kwh_6' => 0.2642,
            'pro_offre_fr_base_abo_9' => 15.89,
            'pro_offre_fr_base_kwh_9' => 0.2642,
            'pro_offre_fr_base_abo_12' => 19.16,
            'pro_offre_fr_base_kwh_12' => 0.2642,
            'pro_offre_fr_base_abo_15' => 22.21,
            'pro_offre_fr_base_kwh_15' => 0.2642,
            'pro_offre_fr_base_abo_18' => 25.24,
            'pro_offre_fr_base_kwh_18' => 0.2642,
            'pro_offre_fr_base_abo_24' => 31.96,
            'pro_offre_fr_base_kwh_24' => 0.2642,
            'pro_offre_fr_base_abo_30' => 37.68,
            'pro_offre_fr_base_kwh_30' => 0.2642,
            'pro_offre_fr_base_abo_36' => 44.43,
            'pro_offre_fr_base_kwh_36' => 0.2642,
            
            'pro_offre_fr_hc_abo_6' => 13.28,
            'pro_offre_fr_hc_hp_6' => 0.2835,
            'pro_offre_fr_hc_hc_6' => 0.2171,
            'pro_offre_fr_hc_abo_9' => 16.82,
            'pro_offre_fr_hc_hp_9' => 0.2835,
            'pro_offre_fr_hc_hc_9' => 0.2171,
            'pro_offre_fr_hc_abo_12' => 20.28,
            'pro_offre_fr_hc_hp_12' => 0.2835,
            'pro_offre_fr_hc_hc_12' => 0.2171,
            'pro_offre_fr_hc_abo_15' => 23.57,
            'pro_offre_fr_hc_hp_15' => 0.2835,
            'pro_offre_fr_hc_hc_15' => 0.2171,
            'pro_offre_fr_hc_abo_18' => 26.84,
            'pro_offre_fr_hc_hp_18' => 0.2835,
            'pro_offre_fr_hc_hc_18' => 0.2171,
            'pro_offre_fr_hc_abo_24' => 33.70,
            'pro_offre_fr_hc_hp_24' => 0.2835,
            'pro_offre_fr_hc_hc_24' => 0.2171,
            'pro_offre_fr_hc_abo_30' => 39.94,
            'pro_offre_fr_hc_hp_30' => 0.2835,
            'pro_offre_fr_hc_hc_30' => 0.2171,
            'pro_offre_fr_hc_abo_36' => 46.24,
            'pro_offre_fr_hc_hp_36' => 0.2835,
            'pro_offre_fr_hc_hc_36' => 0.2171,
            
            // TABLEAU 4: Autre offre (TRV + 10% par défaut)
            'pro_autre_offre_base_abo_3' => 9.69,
            'pro_autre_offre_base_kwh_3' => 0.2768,
            'pro_autre_offre_base_abo_6' => 12.67,
            'pro_autre_offre_base_kwh_6' => 0.2768,
            'pro_autre_offre_base_abo_9' => 15.89,
            'pro_autre_offre_base_kwh_9' => 0.2768,
            'pro_autre_offre_base_abo_12' => 19.16,
            'pro_autre_offre_base_kwh_12' => 0.2768,
            'pro_autre_offre_base_abo_15' => 22.21,
            'pro_autre_offre_base_kwh_15' => 0.2768,
            'pro_autre_offre_base_abo_18' => 25.24,
            'pro_autre_offre_base_kwh_18' => 0.2768,
            'pro_autre_offre_base_abo_24' => 31.96,
            'pro_autre_offre_base_kwh_24' => 0.2768,
            'pro_autre_offre_base_abo_30' => 37.68,
            'pro_autre_offre_base_kwh_30' => 0.2768,
            'pro_autre_offre_base_abo_36' => 44.43,
            'pro_autre_offre_base_kwh_36' => 0.2768,
            
            'pro_autre_offre_hc_abo_6' => 13.28,
            'pro_autre_offre_hc_hp_6' => 0.297,
            'pro_autre_offre_hc_hc_6' => 0.2275,
            'pro_autre_offre_hc_abo_9' => 16.82,
            'pro_autre_offre_hc_hp_9' => 0.297,
            'pro_autre_offre_hc_hc_9' => 0.2275,
            'pro_autre_offre_hc_abo_12' => 20.28,
            'pro_autre_offre_hc_hp_12' => 0.297,
            'pro_autre_offre_hc_hc_12' => 0.2275,
            'pro_autre_offre_hc_abo_15' => 23.57,
            'pro_autre_offre_hc_hp_15' => 0.297,
            'pro_autre_offre_hc_hc_15' => 0.2275,
            'pro_autre_offre_hc_abo_18' => 26.84,
            'pro_autre_offre_hc_hp_18' => 0.297,
            'pro_autre_offre_hc_hc_18' => 0.2275,
            'pro_autre_offre_hc_abo_24' => 33.70,
            'pro_autre_offre_hc_hp_24' => 0.297,
            'pro_autre_offre_hc_hc_24' => 0.2275,
            'pro_autre_offre_hc_abo_30' => 39.94,
            'pro_autre_offre_hc_hp_30' => 0.297,
            'pro_autre_offre_hc_hc_30' => 0.2275,
            'pro_autre_offre_hc_abo_36' => 46.24,
            'pro_autre_offre_hc_hp_36' => 0.297,
            'pro_autre_offre_hc_hc_36' => 0.2275,
            
            // Paramètres
            'pro_seuil_salaries' => 10,
            'pro_seuil_ca' => 3000000,
            'pro_ratio_hp_defaut' => 60,
            
            // Taxes
            'pro_cspe' => 22.5,
            'pro_tcfe' => 9.5,
            'pro_cta' => 2.71,
            'pro_tva' => 20
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

        /**
     * Endpoint AJAX pour récupérer les communes configurées
     */
    public function ajax_get_communes_gaz() {
        // Vérification nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Sécurité échouée');
            return;
        }
        
        // Récupérer les communes depuis les données de configuration
        $gaz_data = get_option('htic_simulateur_gaz_residentiel_data', array());
        
        // Chercher les communes dans la configuration
        $communes = array();
        
        // Si vous avez une structure de communes dans vos données
        if (isset($gaz_data['communes']) && is_array($gaz_data['communes'])) {
            $communes = $gaz_data['communes'];
        } else {
            // Sinon, utiliser les communes par défaut Excel
            $communes = $this->get_default_communes_excel();
        }
        
        wp_send_json_success(array('communes' => $communes));
    }

    /**
     * Obtenir les communes par défaut depuis Excel
     */
    private function get_default_communes_excel() {
        return array(
            // Communes Gaz Naturel (données Excel exactes)
            array('nom' => 'AIRE SUR L\'ADOUR', 'type' => 'naturel'),
            array('nom' => 'BARCELONNE DU GERS', 'type' => 'naturel'),
            array('nom' => 'GAAS', 'type' => 'naturel'),
            array('nom' => 'LABATUT', 'type' => 'naturel'),
            array('nom' => 'LALUQUE', 'type' => 'naturel'),
            array('nom' => 'MISSON', 'type' => 'naturel'),
            array('nom' => 'POUILLON', 'type' => 'naturel'),
            
            // Communes Gaz Propane (données Excel exactes)
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

    /**
     * Initialiser le système de contact
     */
    public function init_contact_system() {
        require_once HTIC_SIMULATEUR_PATH . 'includes/contact-handler.php';
    }

    /**
     * Shortcode pour le formulaire de contact
     */
    public function shortcode_contact_form($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'modern',
            'type' => 'general'
        ), $atts);
        
        $this->enqueue_contact_assets();
        
        ob_start();
        include HTIC_SIMULATEUR_PATH . 'formulaires/contact/contact-form.php';
        return ob_get_clean();
    }

    /**
     * Charger les assets du formulaire de contact
     */
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
                'nonce' => wp_create_nonce('htic_contact_nonce'),
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

    /**
     * Traitement AJAX du formulaire de contact
     */
    public function ajax_contact_submit() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'htic_contact_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $contact_handler = new HTIC_Contact_Handler();
        $result = $contact_handler->process_contact_form($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Page d'administration du formulaire de contact
     */
    public function contact_admin_page() {
        ?>
        <div class="wrap">
            <h1>📞 Formulaire de Contact</h1>
            
            <div class="card">
                <h2>Utilisation du shortcode</h2>
                <p>Pour afficher le formulaire de contact sur votre site, utilisez le shortcode suivant :</p>
                <code>[htic_contact_form]</code>
                
                <h3>Paramètres disponibles :</h3>
                <ul>
                    <li><code>theme="modern"</code> - Style du formulaire</li>
                    <li><code>type="general"</code> - Type de formulaire</li>
                </ul>
                
                <h3>Exemple d'utilisation :</h3>
                <code>[htic_contact_form theme="modern"]</code>
            </div>
            
            <div class="card">
                <h2>Configuration Email</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('htic_contact_settings');
                    do_settings_sections('htic_contact_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Email de réception</th>
                            <td>
                                <input type="email" name="htic_contact_email" 
                                    value="<?php echo esc_attr(get_option('htic_contact_email', get_option('admin_email'))); ?>" 
                                    class="regular-text" />
                                <p class="description">Email où recevoir les demandes de contact</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <div class="card">
                <h2>Statistiques</h2>
                <?php $this->display_contact_stats(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher les statistiques des contacts
     */
    private function display_contact_stats() {
        $submissions = get_option('htic_contact_submissions', array());
        $total = count($submissions);
        
        if ($total === 0) {
            echo '<p>Aucune soumission de formulaire pour le moment.</p>';
            return;
        }
        
        // Statistiques par type
        $types_count = array();
        $recent_submissions = array();
        
        foreach ($submissions as $submission) {
            // Compter par type
            $type = $submission['type'] ?? 'inconnu';
            $types_count[$type] = ($types_count[$type] ?? 0) + 1;
            
            // Garder les 10 dernières
            if (count($recent_submissions) < 10) {
                $recent_submissions[] = $submission;
            }
        }
        
        echo '<h3>Total des soumissions : ' . $total . '</h3>';
        
        echo '<h4>Répartition par type :</h4>';
        echo '<ul>';
        foreach ($types_count as $type => $count) {
            $label = $this->get_type_demande_label($type);
            echo '<li>' . esc_html($label) . ' : ' . $count . '</li>';
        }
        echo '</ul>';
        
        echo '<h4>Dernières soumissions :</h4>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Date</th><th>Type</th><th>Email</th></tr></thead>';
        echo '<tbody>';
        foreach (array_reverse($recent_submissions) as $submission) {
            echo '<tr>';
            echo '<td>' . esc_html($submission['timestamp']) . '</td>';
            echo '<td>' . esc_html($this->get_type_demande_label($submission['type'])) . '</td>';
            echo '<td>' . esc_html($submission['email']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    /**
     * Obtenir le libellé du type de demande
     */
    private function get_type_demande_label($type) {
        $types = array(
            'releve_index' => 'Relevé d\'index',
            'changement_rib' => 'Changement de RIB',
            'resiliation_contrat' => 'Résiliation de contrat',
            'modification_contrat' => 'Modification de contrat',
            'depannage_urgent' => 'Dépannage urgent',
            'mise_aux_normes' => 'Mise aux normes',
            'renovation_electrique' => 'Rénovation électrique',
            'maintenance_preventive' => 'Maintenance préventive',
            'raccordement' => 'Raccordement',
            'autre' => 'Autre'
        );
        
        return isset($types[$type]) ? $types[$type] : 'Demande inconnue';
    }

    public function ajax_recalculate_with_power() {
        // Vérification de sécurité
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

        // Validation des paramètres spécifiques
        if ($nouvellePuissance < 3 || $nouvellePuissance > 36) {
            wp_send_json_error('Puissance invalide. Doit être entre 3 et 36 kVA.');
            return;
        }

        $tarifs_valides = array('base', 'hc', 'tempo');
        if (!in_array($tarifChoisi, $tarifs_valides)) {
            wp_send_json_error('Tarif invalide.');
            return;
        }

        // Forcer la nouvelle puissance dans les données utilisateur
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
            
            // Appel du calculateur avec les nouvelles données
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
}

new HticSimulateurEnergieAdmin();

function htic_get_simulateur_data($type) {
    return get_option('htic_simulateur_' . $type . '_data', array());
}

?>