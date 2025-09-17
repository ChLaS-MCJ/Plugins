<?php
/**
 * Gestionnaire des formulaires de contact
 * Fichier: includes/contact-handler.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class HTIC_Contact_Handler {
    
    private $allowed_file_types = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx');
    private $max_file_size = 10485760; // 10 Mo
    
    public function __construct() {
        // Initialisation
    }
    
    /**
     * Traiter le formulaire de contact
     */
    public function process_contact_form($post_data) {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($post_data['nonce'], 'htic_contact_nonce')) {
                throw new Exception('Nonce invalide');
            }
            
            // Validation et nettoyage des données
            $data = $this->validate_and_sanitize_data($post_data);
            
            // Traitement du fichier uploadé si présent
            $file_info = null;
            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $file_info = $this->handle_file_upload($_FILES);
                if (!$file_info['success']) {
                    throw new Exception($file_info['message']);
                }
                $file_info = $file_info['data'];
            }
            
            // Anti-spam basique
            if (!$this->check_anti_spam($data, $_SERVER)) {
                throw new Exception('Détection de spam');
            }
            
            // Envoyer l'email
            $email_sent = $this->send_contact_email($data, $file_info);
            
            if (!$email_sent) {
                throw new Exception('Erreur lors de l\'envoi de l\'email');
            }
            
            // Log de la demande (optionnel)
            $this->log_contact_submission($data, $file_info);
            
            return array(
                'success' => true,
                'data' => array(
                    'message' => 'Votre demande a été envoyée avec succès',
                    'id' => uniqid('contact_')
                )
            );
            
        } catch (Exception $e) {
            error_log('Erreur formulaire contact: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Valider et nettoyer les données
     */
    private function validate_and_sanitize_data($post_data) {
        $data = array();
        
        // Champs obligatoires
        $required_fields = array('civilite', 'nom', 'prenom', 'email', 'telephone', 'adresse', 'code_postal', 'ville', 'type_demande');
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                throw new Exception("Le champ {$field} est obligatoire");
            }
            $data[$field] = sanitize_text_field($post_data[$field]);
        }
        
        // Validation du code postal français
        if (!preg_match('/^[0-9]{5}$/', $data['code_postal'])) {
            throw new Exception('Le code postal doit contenir 5 chiffres');
        }
        
        // Validation email
        if (!is_email($data['email'])) {
            throw new Exception('Format d\'email invalide');
        }
        
        // Validation téléphone
        if (!preg_match('/^[\d\s\-\+\(\)\.]{10,}$/', $data['telephone'])) {
            throw new Exception('Format de téléphone invalide');
        }
        
        // Champs optionnels
        $data['complement_adresse'] = isset($post_data['complement_adresse']) ? sanitize_text_field($post_data['complement_adresse']) : '';
        $data['message'] = isset($post_data['message']) ? sanitize_textarea_field($post_data['message']) : '';
        
        // Vérifications RGPD et captcha
        if (empty($post_data['rgpd_consent'])) {
            throw new Exception('Vous devez accepter la politique de confidentialité');
        }
        
        if (empty($post_data['captcha'])) {
            throw new Exception('Veuillez confirmer que vous n\'êtes pas un robot');
        }
        
        // Ajouter des métadonnées
        $data['ip_address'] = $this->get_client_ip();
        $data['user_agent'] = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $data['date_soumission'] = current_time('mysql');
        
        return $data;
    }
    
    /**
     * Gérer l'upload de fichier
     */
    public function handle_file_upload($files) {
        try {
            if (!isset($files['fichier']) || $files['fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erreur lors de l\'upload du fichier');
            }
            
            $file = $files['fichier'];
            
            // Vérifier la taille
            if ($file['size'] > $this->max_file_size) {
                throw new Exception('Le fichier est trop volumineux (max. 10 Mo)');
            }
            
            // Vérifier le type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $this->allowed_file_types)) {
                throw new Exception('Type de fichier non autorisé');
            }
            
            // Générer un nom unique
            $upload_dir = wp_upload_dir();
            $filename = uniqid('contact_') . '_' . sanitize_file_name($file['name']);
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            // Déplacer le fichier
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception('Erreur lors de la sauvegarde du fichier');
            }
            
            return array(
                'success' => true,
                'data' => array(
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'file_path' => $file_path,
                    'file_url' => $upload_dir['url'] . '/' . $filename,
                    'file_size' => $file['size'],
                    'file_type' => $file['type']
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Vérifications anti-spam
     */
    private function check_anti_spam($data, $server_data) {
        // Vérifier si l'email contient des mots suspects
        $spam_words = array('viagra', 'casino', 'poker', 'loan', 'mortgage');
        $email_content = strtolower($data['email'] . ' ' . $data['message']);
        
        foreach ($spam_words as $word) {
            if (strpos($email_content, $word) !== false) {
                return false;
            }
        }
        
        // Vérifier la fréquence de soumission par IP
        $ip = $this->get_client_ip();
        $recent_submissions = get_transient('contact_submissions_' . md5($ip));
        
        if ($recent_submissions && $recent_submissions >= 3) {
            return false; // Trop de soumissions récentes
        }
        
        // Incrementer le compteur
        $count = $recent_submissions ? $recent_submissions + 1 : 1;
        set_transient('contact_submissions_' . md5($ip), $count, HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Envoyer l'email de contact
     */
    private function send_contact_email($data, $file_info = null) {
        // Destinataires
        $admin_email = get_option('admin_email');
        $contact_email = get_option('htic_contact_email', $admin_email);
        
        // Sujet
        $subject = sprintf('[%s] Nouvelle demande de contact - %s', 
            get_bloginfo('name'), 
            $this->get_type_demande_label($data['type_demande'])
        );
        
        // Corps de l'email
        $message = $this->build_email_message($data, $file_info);
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>',
            'Reply-To: ' . $data['email']
        );
        
        // Pièce jointe
        $attachments = array();
        if ($file_info && isset($file_info['file_path']) && file_exists($file_info['file_path'])) {
            $attachments[] = $file_info['file_path'];
        }
        
        // Envoyer l'email
        $sent = wp_mail($contact_email, $subject, $message, $headers, $attachments);
        
        // Envoyer une copie de confirmation au client
        if ($sent) {
            $this->send_confirmation_email($data);
        }
        
        return $sent;
    }
    
    /**
     * Construire le message email
     */
    private function build_email_message($data, $file_info = null) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                .header { background: #e39411; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 20px; margin-top: 20px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #2c3e50; }
                .value { margin-left: 10px; }
                .file-info { background: #f0f9ff; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>📞 Nouvelle demande de contact</h2>
                    <p>Reçue le <?php echo date('d/m/Y à H:i'); ?></p>
                </div>
                
                <div class="content">
                    <h3>Informations du contact</h3>
                    
                    <div class="field">
                        <span class="label">Civilité:</span>
                        <span class="value"><?php echo esc_html($data['civilite']); ?></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Nom:</span>
                        <span class="value"><?php echo esc_html($data['nom']); ?></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Prénom:</span>
                        <span class="value"><?php echo esc_html($data['prenom']); ?></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Email:</span>
                        <span class="value"><a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Téléphone:</span>
                        <span class="value"><a href="tel:<?php echo esc_attr($data['telephone']); ?>"><?php echo esc_html($data['telephone']); ?></a></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Adresse:</span>
                        <span class="value"><?php echo esc_html($data['adresse']); ?></span>
                    </div>
                    
                    <?php if (!empty($data['complement_adresse'])): ?>
                    <div class="field">
                        <span class="label">Complément d'adresse:</span>
                        <span class="value"><?php echo esc_html($data['complement_adresse']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="field">
                        <span class="label">Code postal:</span>
                        <span class="value"><?php echo esc_html($data['code_postal']); ?></span>
                    </div>
                    
                    <div class="field">
                        <span class="label">Ville:</span>
                        <span class="value"><?php echo esc_html($data['ville']); ?></span>
                    </div>
                    
                    <h3>Détails de la demande</h3>
                    
                    <div class="field">
                        <span class="label">Type de demande:</span>
                        <span class="value"><?php echo esc_html($this->get_type_demande_label($data['type_demande'])); ?></span>
                    </div>
                    
                    <?php if (!empty($data['message'])): ?>
                    <div class="field">
                        <span class="label">Message:</span>
                        <div class="value" style="margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                            <?php echo nl2br(esc_html($data['message'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($file_info): ?>
                    <div class="file-info">
                        <h4>📎 Fichier joint</h4>
                        <p><strong>Nom:</strong> <?php echo esc_html($file_info['original_name']); ?></p>
                        <p><strong>Taille:</strong> <?php echo $this->format_file_size($file_info['file_size']); ?></p>
                        <p><strong>Type:</strong> <?php echo esc_html($file_info['file_type']); ?></p>
                        <p><em>Le fichier est joint à cet email.</em></p>
                    </div>
                    <?php endif; ?>
                    
                    <h3>Informations techniques</h3>
                    <div class="field">
                        <span class="label">IP:</span>
                        <span class="value"><?php echo esc_html($data['ip_address']); ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Navigateur:</span>
                        <span class="value"><?php echo esc_html($data['user_agent']); ?></span>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Email automatique envoyé depuis <?php echo get_bloginfo('name'); ?></p>
                    <p><a href="<?php echo home_url(); ?>"><?php echo home_url(); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Envoyer un email de confirmation au client
     */
    private function send_confirmation_email($data) {
        $subject = sprintf('[%s] Confirmation de réception de votre demande', get_bloginfo('name'));
        
        $message = $this->build_confirmation_message($data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($data['email'], $subject, $message, $headers);
    }
    
    /**
     * Construire le message de confirmation
     */
    private function build_confirmation_message($data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 20px; margin-top: 20px; }
                .highlight { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>✅ Demande reçue avec succès</h2>
                </div>
                
                <div class="content">
                    <p>Bonjour <?php echo esc_html($data['prenom']); ?>,</p>
                    
                    <div class="highlight">
                        <p><strong>Votre demande a bien été reçue !</strong></p>
                        <p>Type de demande : <strong><?php echo esc_html($this->get_type_demande_label($data['type_demande'])); ?></strong></p>
                        <p>Date de réception : <strong><?php echo date('d/m/Y à H:i'); ?></strong></p>
                    </div>
                    
                    <p>Nous avons bien reçu votre demande concernant <strong><?php echo esc_html($this->get_type_demande_label($data['type_demande'])); ?></strong>.</p>
                    
                    <p>Notre équipe va examiner votre demande et vous recontacter dans les plus brefs délais :</p>
                    <ul>
                        <li><strong>Demandes urgentes :</strong> sous 2 heures en jours ouvrés</li>
                        <li><strong>Demandes standard :</strong> sous 24 heures</li>
                        <li><strong>Demandes administratives :</strong> sous 48 heures</li>
                    </ul>
                    
                    <p>Si votre demande est urgente, vous pouvez également nous contacter directement :</p>
                    <ul>
                        <li><strong>Téléphone :</strong> [VOTRE NUMÉRO]</li>
                        <li><strong>Email :</strong> [VOTRE EMAIL]</li>
                    </ul>
                    
                    <p>Nous vous remercions de votre confiance.</p>
                    
                    <p>Cordialement,<br>
                    L'équipe Gascogne Énergies Services</p>
                </div>
                
                <div class="footer">
                    <p>Gascogne Énergies Services</p>
                    <p>ZAC de Peyres - BP143 - 62 rue de Sarron - 40800 AIRE SUR L'ADOUR</p>
                    <p><a href="<?php echo home_url(); ?>"><?php echo home_url(); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Logger la soumission (optionnel)
     */
    private function log_contact_submission($data, $file_info = null) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $data['type_demande'],
            'email' => $data['email'],
            'ip' => $data['ip_address'],
            'file' => $file_info ? $file_info['original_name'] : null
        );
        
        // Optionnel : sauvegarder dans les logs WordPress
        error_log('Contact form submission: ' . json_encode($log_entry));
        
        // Optionnel : sauvegarder dans une option WordPress pour statistiques
        $submissions = get_option('htic_contact_submissions', array());
        $submissions[] = $log_entry;
        
        // Garder seulement les 1000 dernières soumissions
        if (count($submissions) > 1000) {
            $submissions = array_slice($submissions, -1000);
        }
        
        update_option('htic_contact_submissions', $submissions);
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
    
    /**
     * Obtenir l'IP du client
     */
    private function get_client_ip() {
        $ip_fields = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_fields as $field) {
            if (array_key_exists($field, $_SERVER) && !empty($_SERVER[$field])) {
                $ip = trim($_SERVER[$field]);
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return trim($ip);
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Formater la taille de fichier
     */
    private function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}