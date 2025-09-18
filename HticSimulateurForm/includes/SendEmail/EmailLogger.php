<?php
/**
 * Structure Email Loggs :
 * 
 */

class HticEmailLogger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'htic_email_logs';
        $this->create_table_if_needed();
    }
    
    /**
     * Logger un succès d'envoi
     */
    public function log_success($data, $recipient) {
        $this->log_email($data, $recipient, 'success', null);
    }
    
    /**
     * Logger une erreur d'envoi
     */
    public function log_error($data, $error) {
        $recipient = $data['client']['email'] ?? 'unknown';
        $this->log_email($data, $recipient, 'error', $error);
    }
    
    /**
     * Enregistrer en base
     */
    private function log_email($data, $recipient, $status, $error = null) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'email_to' => $recipient,
                'form_type' => $data['type'],
                'status' => $status,
                'error_message' => $error,
                'client_data' => json_encode($data['client']),
                'ip_address' => $data['metadata']['ip'] ?? 'unknown',
                'sent_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Log WordPress
        if ($status === 'success') {
            error_log("HTIC Email SUCCESS: {$data['type']} sent to {$recipient}");
        } else {
            error_log("HTIC Email ERROR: {$data['type']} failed for {$recipient} - {$error}");
        }
    }
    
    /**
     * Créer la table de logs
     */
    private function create_table_if_needed() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email_to varchar(255) NOT NULL,
            form_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            client_data longtext,
            ip_address varchar(45),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_type (form_type),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}