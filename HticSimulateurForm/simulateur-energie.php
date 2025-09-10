<?php
/**
 * Plugin Name: HTIC Simulateur Consommation Énergie
 * Description: Plugin unifié pour simuler la consommation énergétique avec sélecteur à onglets
 * Version: 2.0.0
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
define('HTIC_SIMULATEUR_VERSION', '2.0.0');

class HticSimulateurEnergieAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_simulateur_data', array($this, 'save_simulateur_data'));
        
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Shortcode principal unifié (NOUVEAU)
        add_shortcode('htic_simulateur_energie', array($this, 'shortcode_simulateur_unifie'));
        
        // Shortcodes individuels (conservés pour compatibilité)
        add_shortcode('htic_simulateur_elec_residentiel', array($this, 'shortcode_elec_residentiel'));
        add_shortcode('htic_simulateur_gaz_residentiel', array($this, 'shortcode_gaz_residentiel'));
        add_shortcode('htic_simulateur_elec_professionnel', array($this, 'shortcode_elec_professionnel'));
        add_shortcode('htic_simulateur_gaz_professionnel', array($this, 'shortcode_gaz_professionnel'));
        
        // AJAX handlers
        add_action('wp_ajax_htic_load_formulaire', array($this, 'ajax_load_formulaire'));
        add_action('wp_ajax_nopriv_htic_load_formulaire', array($this, 'ajax_load_formulaire'));
        add_action('wp_ajax_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));
        add_action('wp_ajax_nopriv_htic_calculate_estimation', array($this, 'ajax_calculate_estimation'));
    }
    
    public function activate() {
        $this->create_default_options();
        $this->create_tables();
        $this->create_formulaires_structure();
    }
    
    private function create_formulaires_structure() {
        $base_path = HTIC_SIMULATEUR_PATH;
        
        // Créer les dossiers nécessaires
        $directories = array(
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
    // SHORTCODE PRINCIPAL UNIFIÉ (NOUVEAU)
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
        
        // CSS commun
        if (file_exists(HTIC_SIMULATEUR_PATH . 'includes/common.css')) {
            wp_enqueue_style(
                'htic-simulateur-common', 
                HTIC_SIMULATEUR_URL . 'includes/common.css', 
                array(), 
                HTIC_SIMULATEUR_VERSION
            );
        }
        
        // JS du simulateur unifié
        wp_enqueue_script(
            'htic-simulateur-unifie-js',
            HTIC_SIMULATEUR_URL . 'templates/simulateur-unifie.js',
            array('jquery'),
            HTIC_SIMULATEUR_VERSION,
            true
        );
        
        // JS commun
        if (file_exists(HTIC_SIMULATEUR_PATH . 'includes/common.js')) {
            wp_enqueue_script(
                'htic-simulateur-common-js', 
                HTIC_SIMULATEUR_URL . 'includes/common.js', 
                array('jquery'), 
                HTIC_SIMULATEUR_VERSION, 
                true
            );
        }
    }
    
    // ================================
    // SHORTCODES INDIVIDUELS (CONSERVÉS)
    // ================================
    
    public function shortcode_elec_residentiel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('elec-residentiel');
        
        ob_start();
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/elec-residentiel/elec-residentiel.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>⚠️ Template non trouvé: ' . $template_path . '</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_gaz_residentiel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('gaz-residentiel');
        
        ob_start();
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/gaz-residentiel/gaz-residentiel.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>⚠️ Template en cours de développement</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_elec_professionnel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('elec-professionnel');
        
        ob_start();
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/elec-professionnel/elec-professionnel.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>⚠️ Template en cours de développement</p>';
        }
        return ob_get_clean();
    }
    
    public function shortcode_gaz_professionnel($atts) {
        $atts = shortcode_atts(array('theme' => 'default'), $atts);
        $this->enqueue_formulaire_assets('gaz-professionnel');
        
        ob_start();
        $template_path = HTIC_SIMULATEUR_PATH . 'formulaires/gaz-professionnel/gaz-professionnel.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>⚠️ Template en cours de développement</p>';
        }
        return ob_get_clean();
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
            wp_send_json_error('Template de formulaire non trouvé: ' . $template_path);
            return;
        }
        
        // Capturer le contenu du template
        ob_start();
        
        // Simuler les attributs pour le template
        $atts = array('theme' => 'default');
        
        include $template_path;
        $html = ob_get_clean();
        
        // Retourner le HTML et les informations sur les assets
        wp_send_json_success(array(
            'html' => $html,
            'type' => $type,
            'assets' => array(
                'css' => HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/' . $type . '.css',
                'js' => HTIC_SIMULATEUR_URL . 'formulaires/' . $type . '/' . $type . '.js'
            )
        ));
    }
    
    // ================================
    // AJAX HANDLER POUR CALCULS
    // ================================
    
    public function ajax_calculate_estimation() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $type = sanitize_text_field($_POST['type']);
        $user_data = $_POST['user_data'];
        $config_data = $_POST['config_data'];
        
        // Charger le calculateur si disponible
        $calculateur_path = HTIC_SIMULATEUR_PATH . 'includes/calculateur.php';
        if (file_exists($calculateur_path)) {
            require_once $calculateur_path;
            
            if (class_exists('HticCalculateur')) {
                $calculateur = new HticCalculateur();
                $result = $calculateur->calculate($type, $user_data, $config_data);
                wp_send_json_success($result);
                return;
            }
        }
        
        // Fallback: calculateur simplifié
        $result = $this->calculate_estimation_fallback($type, $user_data);
        wp_send_json_success($result);
    }
    
    private function calculate_estimation_fallback($type, $user_data) {
        // Calculateur de base pour les tests
        $surface = intval($user_data['surface'] ?? 100);
        $nb_personnes = intval($user_data['nb_personnes'] ?? 3);
        
        switch ($type) {
            case 'elec-residentiel':
                $consommation_base = $surface * 45; // kWh/m²/an
                $consommation_personnes = $nb_personnes * 500; // kWh/personne/an
                $consommation_totale = $consommation_base + $consommation_personnes;
                
                return array(
                    'consommation_annuelle' => $consommation_totale,
                    'puissance_recommandee' => $surface > 120 ? '15' : '12',
                    'tarifs' => array(
                        'base' => array(
                            'total_annuel' => round($consommation_totale * 0.25),
                            'total_mensuel' => round($consommation_totale * 0.25 / 12)
                        ),
                        'hc' => array(
                            'total_annuel' => round($consommation_totale * 0.23),
                            'total_mensuel' => round($consommation_totale * 0.23 / 12)
                        )
                    ),
                    'repartition' => array(
                        'chauffage' => round($consommation_totale * 0.6),
                        'eau_chaude' => round($consommation_totale * 0.2),
                        'electromenagers' => round($consommation_totale * 0.15),
                        'eclairage' => round($consommation_totale * 0.05)
                    ),
                    'recap' => $user_data
                );
                
            case 'gaz-residentiel':
                $consommation_gaz = $surface * 80; // kWh/m²/an
                
                return array(
                    'consommation_annuelle' => $consommation_gaz,
                    'cout_annuel' => round($consommation_gaz * 0.088),
                    'cout_mensuel' => round($consommation_gaz * 0.088 / 12),
                    'repartition' => array(
                        'chauffage' => round($consommation_gaz * 0.8),
                        'eau_chaude' => round($consommation_gaz * 0.15),
                        'cuisson' => round($consommation_gaz * 0.05)
                    ),
                    'recap' => $user_data
                );
                
            default:
                return array(
                    'consommation_annuelle' => 3000,
                    'cout_annuel' => 750,
                    'recap' => $user_data
                );
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
            
            // Localisation pour AJAX
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
    
    // ================================
    // ADMIN INTERFACE (CONSERVÉE)
    // ================================
    
    public function admin_page() {
        $elec_residentiel = get_option('htic_simulateur_elec_residentiel_data', $this->get_default_elec_residentiel());
        $gaz_residentiel = get_option('htic_simulateur_gaz_residentiel_data', $this->get_default_gaz_residentiel());
        $elec_professionnel = get_option('htic_simulateur_elec_professionnel_data', $this->get_default_elec_professionnel());
        $gaz_professionnel = get_option('htic_simulateur_gaz_professionnel_data', $this->get_default_gaz_professionnel());
        
        ?>
        <div class="wrap">
            <h1>Configuration du Simulateur Énergie HTIC</h1>
            
            <!-- Guide d'utilisation mis à jour -->
            <div class="notice notice-info">
                <h3>📝 Shortcode principal (NOUVEAU) :</h3>
                <p><strong>Simulateur Unifié avec menu à onglets :</strong> <code>[htic_simulateur_energie]</code></p>
                <p><em>💡 Ce shortcode affiche un menu permettant de choisir entre Électricité/Gaz et Particulier/Professionnel !</em></p>
                <hr style="margin: 1rem 0;">
                <h4>Shortcodes individuels (conservés pour compatibilité) :</h4>
                <p><strong>Électricité Résidentiel :</strong> <code>[htic_simulateur_elec_residentiel]</code></p>
                <p><strong>Gaz Résidentiel :</strong> <code>[htic_simulateur_gaz_residentiel]</code></p>
                <p><strong>Électricité Professionnel :</strong> <code>[htic_simulateur_elec_professionnel]</code></p>
                <p><strong>Gaz Professionnel :</strong> <code>[htic_simulateur_gaz_professionnel]</code></p>
            </div>
            
            <div class="htic-simulateur-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#tab-elec-residentiel" class="nav-tab nav-tab-active">
                        <span class="tab-icon"></span>
                        Électricité Résidentiel
                    </a>
                    <a href="#tab-gaz-residentiel" class="nav-tab">
                        <span class="tab-icon"></span>
                        Gaz Résidentiel
                    </a>
                    <a href="#tab-elec-professionnel" class="nav-tab">
                        <span class="tab-icon"></span>
                        Électricité Professionnel
                    </a>
                    <a href="#tab-gaz-professionnel" class="nav-tab">
                        <span class="tab-icon"></span>
                        Gaz Professionnel
                    </a>
                </nav>
                
                <div class="tab-content">
                    <!-- ONGLET 1: Électricité Résidentiel -->
                    <div id="tab-elec-residentiel" class="tab-pane active">
                        <form method="post" action="options.php" class="htic-simulateur-form">
                            <?php settings_fields('htic_simulateur_elec_residentiel'); ?>
                            
                            <h2>⚡ Tarifs Électricité Résidentiel (TTC)</h2>
                            <p class="description">Configuration pour le simulateur électricité particuliers</p>
                            
                            <div class="htic-simulateur-section">
                                <h3>Configuration Générale</h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Puissance recommandée par défaut</th>
                                        <td>
                                            <input type="number" name="htic_simulateur_elec_residentiel_data[puissance_defaut]" 
                                                   value="<?php echo esc_attr($elec_residentiel['puissance_defaut'] ?? 15); ?>" /> KVA
                                        </td>
                                    </tr>
                                </table>
                                
                                <h4>💡 Tarifs BASE</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Puissance (KVA)</th>
                                            <th>Abonnement (€/mois)</th>
                                            <th>Prix kWh (€)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $puissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];
                                        foreach($puissances as $p): 
                                        ?>
                                        <tr>
                                            <td><?php echo $p; ?></td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       name="htic_simulateur_elec_residentiel_data[base_abo_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_residentiel['base_abo_'.$p] ?? ''); ?>" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" 
                                                       name="htic_simulateur_elec_residentiel_data[base_kwh_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_residentiel['base_kwh_'.$p] ?? ''); ?>" />
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <h4>🔄 Tarifs Heures Creuses</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Puissance (KVA)</th>
                                            <th>Abonnement (€/mois)</th>
                                            <th>Prix kWh HP (€)</th>
                                            <th>Prix kWh HC (€)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $puissances_hc = [6, 9, 12, 15, 18, 24, 30, 36];
                                        foreach($puissances_hc as $p): 
                                        ?>
                                        <tr>
                                            <td><?php echo $p; ?></td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       name="htic_simulateur_elec_residentiel_data[hc_abo_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_residentiel['hc_abo_'.$p] ?? ''); ?>" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" 
                                                       name="htic_simulateur_elec_residentiel_data[hc_hp_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_residentiel['hc_hp_'.$p] ?? ''); ?>" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" 
                                                       name="htic_simulateur_elec_residentiel_data[hc_hc_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_residentiel['hc_hc_'.$p] ?? ''); ?>" />
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <h4>🏠 Consommations par usage (kWh/an)</h4>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Chauffage électrique convecteurs (par m²)</th>
                                        <td>
                                            <label>Avant 1980 : 
                                                <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_avant_1980]" 
                                                       value="<?php echo esc_attr($elec_residentiel['chauffage_avant_1980'] ?? 215); ?>" /> kWh/m²
                                            </label><br>
                                            <label>1980-2000 : 
                                                <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_1980_2000]" 
                                                       value="<?php echo esc_attr($elec_residentiel['chauffage_1980_2000'] ?? 150); ?>" /> kWh/m²
                                            </label><br>
                                            <label>Après 2000 : 
                                                <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_apres_2000]" 
                                                       value="<?php echo esc_attr($elec_residentiel['chauffage_apres_2000'] ?? 75); ?>" /> kWh/m²
                                            </label><br>
                                            <label>Rénovation récente : 
                                                <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_renovation]" 
                                                       value="<?php echo esc_attr($elec_residentiel['chauffage_renovation'] ?? 37.5); ?>" /> kWh/m²
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <?php submit_button('💾 Sauvegarder les tarifs électricité résidentiel'); ?>
                        </form>
                    </div>
                    
                    <!-- ONGLET 2: Gaz Résidentiel -->
                    <div id="tab-gaz-residentiel" class="tab-pane">
                        <form method="post" action="options.php" class="htic-simulateur-form">
                            <?php settings_fields('htic_simulateur_gaz_residentiel'); ?>
                            
                            <h2>🔥 Tarifs Gaz Résidentiel (TTC)</h2>
                            <p class="description">Configuration pour le simulateur gaz particuliers</p>
                            
                            <div class="htic-simulateur-section">
                                <h3>Tarifs Réglementés Gaz</h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Abonnement Base (€/an)</th>
                                        <td>
                                            <input type="number" step="0.01" name="htic_simulateur_gaz_residentiel_data[gaz_abo_base]" 
                                                   value="<?php echo esc_attr($gaz_residentiel['gaz_abo_base'] ?? 102.12); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Prix kWh Gaz Base (€/kWh)</th>
                                        <td>
                                            <input type="number" step="0.0001" name="htic_simulateur_gaz_residentiel_data[gaz_kwh_base]" 
                                                   value="<?php echo esc_attr($gaz_residentiel['gaz_kwh_base'] ?? 0.0878); ?>" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <?php submit_button('💾 Sauvegarder les tarifs gaz résidentiel'); ?>
                        </form>
                    </div>
                    
                    <!-- ONGLET 3: Électricité Professionnel -->
                    <div id="tab-elec-professionnel" class="tab-pane">
                        <form method="post" action="options.php" class="htic-simulateur-form">
                            <?php settings_fields('htic_simulateur_elec_professionnel'); ?>
                            
                            <h2>🏢 Tarifs Électricité Professionnel (TTC)</h2>
                            <p class="description">Configuration pour le simulateur électricité professionnels</p>
                            
                            <div class="htic-simulateur-section">
                                <h3>Tarifs Professionnels</h3>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Puissance (KVA)</th>
                                            <th>Abonnement (€/mois)</th>
                                            <th>Prix kWh (€)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $puissances_pro = [6, 9, 12, 15, 18, 24, 36];
                                        foreach($puissances_pro as $p): 
                                        ?>
                                        <tr>
                                            <td><?php echo $p; ?></td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       name="htic_simulateur_elec_professionnel_data[pro_elec_abo_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_professionnel['pro_elec_abo_'.$p] ?? ''); ?>" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" 
                                                       name="htic_simulateur_elec_professionnel_data[pro_elec_kwh_<?php echo $p; ?>]" 
                                                       value="<?php echo esc_attr($elec_professionnel['pro_elec_kwh_'.$p] ?? ''); ?>" />
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php submit_button('💾 Sauvegarder les tarifs électricité professionnel'); ?>
                        </form>
                    </div>
                    
                    <!-- ONGLET 4: Gaz Professionnel -->
                    <div id="tab-gaz-professionnel" class="tab-pane">
                        <form method="post" action="options.php" class="htic-simulateur-form">
                            <?php settings_fields('htic_simulateur_gaz_professionnel'); ?>
                            
                            <h2>🏭 Tarifs Gaz Professionnel (TTC)</h2>
                            <p class="description">Configuration pour le simulateur gaz professionnels</p>
                            
                            <div class="htic-simulateur-section">
                                <h3>Tarifs Professionnels Gaz</h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Abonnement Professionnel (€/an)</th>
                                        <td>
                                            <input type="number" step="0.01" name="htic_simulateur_gaz_professionnel_data[pro_gaz_abo]" 
                                                   value="<?php echo esc_attr($gaz_professionnel['pro_gaz_abo'] ?? 156.12); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Prix kWh Gaz Pro (€/kWh)</th>
                                        <td>
                                            <input type="number" step="0.0001" name="htic_simulateur_gaz_professionnel_data[pro_gaz_kwh]" 
                                                   value="<?php echo esc_attr($gaz_professionnel['pro_gaz_kwh'] ?? 0.0798); ?>" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <?php submit_button('💾 Sauvegarder les tarifs gaz professionnel'); ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="htic-simulateur-actions">
                <button type="button" class="button button-primary" id="reset-defaults">
                    🔄 Réinitialiser aux valeurs par défaut
                </button>
            </div>
        </div>
        <?php
    }
    
    // ================================
    // SAUVEGARDE AJAX
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
    
    // ================================
    // MÉTHODES DE DONNÉES PAR DÉFAUT
    // ================================
    
    private function get_default_elec_residentiel() {
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
            'hc_abo_6' => 13.28, 'hc_hp_6' => 0.27, 'hc_hc_6' => 0.2068,
            'hc_abo_9' => 16.82, 'hc_hp_9' => 0.27, 'hc_hc_9' => 0.2068,
            'hc_abo_12' => 20.28, 'hc_hp_12' => 0.27, 'hc_hc_12' => 0.2068,
            'hc_abo_15' => 23.57, 'hc_hp_15' => 0.27, 'hc_hc_15' => 0.2068,
            'hc_abo_18' => 26.84, 'hc_hp_18' => 0.27, 'hc_hc_18' => 0.2068,
            'hc_abo_24' => 33.7, 'hc_hp_24' => 0.27, 'hc_hc_24' => 0.2068,
            'hc_abo_30' => 39.94, 'hc_hp_30' => 0.27, 'hc_hc_30' => 0.2068,
            'hc_abo_36' => 46.24, 'hc_hp_36' => 0.27, 'hc_hc_36' => 0.2068,
            // Chauffage
            'chauffage_avant_1980' => 215,
            'chauffage_1980_2000' => 150,
            'chauffage_apres_2000' => 75,
            'chauffage_renovation' => 37.5,
            // Autres consommations
            'eau_chaude' => 1800,
            'electromenagers' => 1497,
            'eclairage' => 750,
            'equipements_supp' => 1500,
            'multimedia' => 300,
            // Coefficients
            'coeff_maison' => 1.0,
            'coeff_appartement' => 0.95,
            'coeff_1_pers' => 0.33,
            'coeff_2_pers' => 0.67,
            'coeff_3_pers' => 0.93,
            'coeff_4_pers' => 1.23,
            'coeff_5_pers' => 1.30,
            'coeff_6_pers' => 1.83
        );
    }
    
    private function get_default_gaz_residentiel() {
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
    
    private function get_default_elec_professionnel() {
        return array(
            'pro_elec_abo_6' => 15.67, 'pro_elec_kwh_6' => 0.2716,
            'pro_elec_abo_9' => 18.89, 'pro_elec_kwh_9' => 0.2716,
            'pro_elec_abo_12' => 22.28, 'pro_elec_kwh_12' => 0.2716,
            'pro_elec_abo_15' => 25.57, 'pro_elec_kwh_15' => 0.2716,
            'pro_elec_abo_18' => 28.84, 'pro_elec_kwh_18' => 0.2716,
            'pro_elec_abo_24' => 35.96, 'pro_elec_kwh_24' => 0.2716,
            'pro_elec_abo_36' => 48.43, 'pro_elec_kwh_36' => 0.2716,
            'pro_bureau' => 120,
            'pro_commerce' => 180,
            'pro_restaurant' => 300,
            'pro_artisanat' => 250,
            'pro_industrie_legere' => 400
        );
    }
    
    private function get_default_gaz_professionnel() {
        return array(
            'pro_gaz_abo' => 156.12,
            'pro_gaz_kwh' => 0.0798,
            'pro_gaz_bureau' => 80,
            'pro_gaz_commerce' => 120,
            'pro_gaz_restaurant' => 200,
            'pro_gaz_artisanat' => 180,
            'pro_gaz_industrie' => 300
        );
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