<?php
/**
 * Structure Email Handler :
 * 
 */


class HticEmailHandler {
    
    private $validator;
    private $logger;
    private $templates_path;
    
    public function __construct() {
        $this->templates_path = plugin_dir_path(__FILE__) . 'templates/';
        
        // Charger les dépendances
        require_once plugin_dir_path(__FILE__) . 'EmailValidator.php';
        require_once plugin_dir_path(__FILE__) . 'EmailLogger.php';
        
        $this->validator = new HticEmailValidator();
        $this->logger = new HticEmailLogger();
        
        // Enregistrer les actions AJAX
        add_action('wp_ajax_htic_send_simulation_email', array($this, 'handle_simulation_email'));
        add_action('wp_ajax_nopriv_htic_send_simulation_email', array($this, 'handle_simulation_email'));
        
        add_action('wp_ajax_htic_send_contact_email', array($this, 'handle_contact_email'));
        add_action('wp_ajax_nopriv_htic_send_contact_email', array($this, 'handle_contact_email'));
    }
    
    /**
     * Handler pour les emails de simulation
     */
    public function handle_simulation_email() {
        // Validation de sécurité
        if (!$this->validator->verify_request()) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        // Nettoyer et structurer les données
        $data = $this->validator->sanitize_simulation_data();
        if (!$data) {
            wp_send_json_error('Données invalides');
            return;
        }
        
        // Validation métier
        $validation = $this->validator->validate_simulation_data($data);
        if (!$validation['valid']) {
            wp_send_json_error('Validation échouée: ' . implode(', ', $validation['errors']));
            return;
        }
        
        // Envoyer l'email
        $result = $this->send_simulation_email($data);
        
        if ($result['success']) {
            $this->logger->log_success($data, $result['recipient']);
            wp_send_json_success('Email envoyé avec succès');
        } else {
            $this->logger->log_error($data, $result['error']);
            wp_send_json_error('Erreur lors de l\'envoi: ' . $result['error']);
        }
    }
    
    /**
     * Envoi de l'email de simulation
     */
    private function send_simulation_email($data) {
        $type = $data['type'];
        $client = $data['client'];
        
        // Charger le template approprié
        $template_file = $this->templates_path . $type . '.php';
        if (!file_exists($template_file)) {
            return array('success' => false, 'error' => 'Template non trouvé: ' . $type);
        }
        
        // Générer le contenu
        $email_content = $this->load_template($template_file, $data);
        if (!$email_content) {
            return array('success' => false, 'error' => 'Erreur génération template');
        }
        
        // Configuration email
        $to = $client['email'];
        $subject = $this->get_email_subject($type);
        $headers = $this->get_email_headers();
        
        // Envoi
        $sent = wp_mail($to, $subject, $email_content, $headers);
        
        return array(
            'success' => $sent,
            'recipient' => $to,
            'error' => $sent ? null : 'Échec wp_mail'
        );
    }
    
    /**
     * Charger un template avec les données
     */
    private function load_template($template_file, $data) {
        if (!file_exists($template_file)) {
            return false;
        }
        
        // Extraire les variables pour le template
        extract($data);
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    /**
     * Sujets d'email par type
     */
    private function get_email_subject($type) {
        $subjects = array(
            'elec-residentiel' => 'Votre simulation électricité résidentielle',
            'gaz-residentiel' => 'Votre simulation gaz résidentielle',
            'elec-professionnel' => 'Votre simulation électricité professionnelle',
            'gaz-professionnel' => 'Votre simulation gaz professionnelle',
            'contact' => 'Confirmation de votre demande'
        );
        
        return $subjects[$type] ?? 'Votre simulation énergétique';
    }
    
    /**
     * En-têtes d'email
     */
    private function get_email_headers() {
        $admin_email = get_option('admin_email');
        
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>',
            'Reply-To: ' . $admin_email,
            'Bcc: ' . $admin_email
        );
    }
    
    /**
     * Handler pour les emails de contact
     */
    public function handle_contact_email() {
        // Logique similaire mais pour contact
        // avec gestion des fichiers uploadés
    }
}