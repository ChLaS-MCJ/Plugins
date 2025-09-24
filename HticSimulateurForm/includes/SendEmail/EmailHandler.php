<?php
/**
 * EmailHandler.php - Adapté pour WordPress avec Brevo
 * includes/SendEmail/EmailHandler.php
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/PDFGenerator.php';

class EmailHandler {
    private $data;
    private $pdfPath;
    private $brevoApiKey;
    private $templateIdClient;
    private $templateIdGES;
    
    public function __construct() {
        // Récupérer la configuration depuis WordPress
        if (function_exists('get_option')) {
            $this->brevoApiKey = get_option('brevo_api_key', BREVO_API_KEY);
            $this->templateIdClient = get_option('brevo_template_client', 1);
            $this->templateIdGES = get_option('brevo_template_ges', 2);
        } else {
            // Fallback si pas dans WordPress
            $this->brevoApiKey = BREVO_API_KEY;
            $this->templateIdClient = 1;
            $this->templateIdGES = 2;
        }
    }
    
    /**
     * Traite les données du formulaire résidentiel et envoie les emails via Brevo
     */
    public function processFormData($jsonData) {
        try {
            // Décoder les données JSON
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }

            // Vérifier que les données essentielles sont présentes
            if (empty($this->data['email'])) {
                throw new Exception('Email client manquant');
            }
            
            // Générer le PDF résidentiel
            $this->generateResidentialPDF();
            
            // Vérifier la clé API Brevo
            if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
                throw new Exception('Clé API Brevo non configurée');
            }

            $clientSent = $this->sendClientEmailBrevo();
            $gesSent = $this->sendGESEmailBrevo();
            
            // Nettoyer le PDF temporaire
            if (file_exists($this->pdfPath)) {
                unlink($this->pdfPath);
            }
            
            if ($clientSent && $gesSent) {
                return [
                    'success' => true,
                    'message' => 'Emails envoyés avec succès'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi des emails'
                ];
            }
            
        } catch (Exception $e) {
            error_log('EmailHandler: Exception - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Traite le formulaire professionnel
     */
    public function processBusinessFormData($jsonData) {
        try {
            // Décoder les données JSON
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            // Vérifier que les données essentielles sont présentes
            if (empty($this->data['email'])) {
                throw new Exception('Email responsable manquant');
            }
            
            if (empty($this->data['companyName'])) {
                throw new Exception('Nom entreprise manquant');
            }
            
            // Générer le PDF professionnel
            $this->generateBusinessPDF();
            
            // Vérifier la clé API Brevo
            if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
                throw new Exception('Clé API Brevo non configurée');
            }

            $clientSent = $this->sendBusinessClientEmailBrevo();
            $gesSent = $this->sendBusinessGESEmailBrevo();
            
            // Nettoyer le PDF temporaire
            if (file_exists($this->pdfPath)) {
                unlink($this->pdfPath);
            }
            
            if ($clientSent && $gesSent) {
                return [
                    'success' => true,
                    'message' => 'Devis professionnel envoyé avec succès',
                    'referenceNumber' => 'PRO-' . date('Ymd') . '-' . rand(1000, 9999)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du devis professionnel'
                ];
            }
            
        } catch (Exception $e) {
            error_log('EmailHandler Business: Exception - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite le formulaire de contact
     */
    public function processContactForm($form_data) {
        try {
            // Stocker les données pour les templates
            $this->data = $form_data;
            
            // Vérifier email obligatoire
            if (empty($this->data['email'])) {
                return array('success' => false, 'message' => 'Email client manquant');
            }
            
            // Vérifier la clé API Brevo
            if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
                return array('success' => false, 'message' => 'Clé API Brevo non configurée');
            }
            
            $clientSent = $this->sendContactConfirmationToClient();
            $gesSent = $this->sendContactEmailToGES();
            
            // Résultat final
            if ($clientSent && $gesSent) {
                return array(
                    'success' => true,
                    'message' => 'Emails de contact envoyés avec succès'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi des emails de contact'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erreur contact: ' . $e->getMessage()
            );
        }
    }

    /*******************************************
     * GENERATION PDF (via PDFGenerator)
     *******************************************/

    /**
     * Génère le PDF résidentiel via PDFGenerator
     */
    private function generateResidentialPDF() {
        $temp_dir = wp_upload_dir()['basedir'] . '/temp-simulations';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $this->pdfPath = $temp_dir . '/simulation_' . time() . '.pdf';
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateResidentialPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF résidentiel');
        }
    }

    /**
     * Génère le PDF professionnel via PDFGenerator
     */
    private function generateBusinessPDF() {
        $temp_dir = wp_upload_dir()['basedir'] . '/temp-simulations';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $this->pdfPath = $temp_dir . '/devis_professionnel_' . time() . '.pdf';
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateBusinessPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF professionnel');
        }
    }

    /*******************************************
     * ENVOI EMAILS RESIDENTIEL
     *******************************************/

    /**
     * Envoie l'email au client via Brevo API
     */
    private function sendClientEmailBrevo() {
        $params = [
            'to' => [
                [
                    'email' => $this->data['email'],
                    'name' => $this->data['firstName'] . ' ' . $this->data['lastName']
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Votre simulation électricité - GES Solutions',
            'htmlContent' => $this->loadTemplate('elec-residentiel-client'),
            'attachment' => [
                [
                    'name' => 'simulation_electricite.pdf',
                    'content' => base64_encode(file_get_contents($this->pdfPath)),
                    'type' => 'application/pdf'
                ]
            ]
        ];
        
        return $this->callBrevoAPI($params);
    }

    /**
     * Envoie l'email à GES via Brevo API
     */
    private function sendGESEmailBrevo() {
        $gesEmail = function_exists('get_option') ? 
                    get_option('ges_notification_email', 'commercial@ges-solutions.fr') : 
                    'commercial@ges-solutions.fr';
        
        // Préparer les pièces jointes
        $attachments = array();
        
        // Ajouter le PDF de simulation
        if (file_exists($this->pdfPath)) {
            $attachments[] = array(
                'name' => 'simulation_client.pdf',
                'content' => base64_encode(file_get_contents($this->pdfPath))
            );
        }
        
        // Ajouter les documents uploadés
        if (isset($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                if (isset($file_info['path']) && file_exists($file_info['path'])) {
                    $attachments[] = array(
                        'name' => $file_info['name'],
                        'content' => base64_encode(file_get_contents($file_info['path']))
                    );
                }
            }
        }
        
        $params = [
            'to' => [
                [
                    'email' => $gesEmail,
                    'name' => 'GES Commercial'
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Nouvelle simulation électricité reçue',
            'htmlContent' => $this->loadTemplate('elec-residentiel-ges')
        ];
        
        // Ajouter les attachments seulement s'il y en a
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS PROFESSIONNEL
     *******************************************/

    /**
     * Envoie l'email au client professionnel via Brevo
     */
    private function sendBusinessClientEmailBrevo() {
        $params = [
            'to' => [
                [
                    'email' => $this->data['email'],
                    'name' => $this->data['firstName'] . ' ' . $this->data['lastName']
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Votre devis électricité professionnel - GES Solutions',
            'htmlContent' => $this->loadTemplate('elec-professionnel-client'),
            'attachment' => [
                [
                    'name' => 'devis_professionnel.pdf',
                    'content' => base64_encode(file_get_contents($this->pdfPath)),
                    'type' => 'application/pdf'
                ]
            ]
        ];
        
        return $this->callBrevoAPI($params);
    }

    /**
     * Envoie l'email à GES pour les professionnels via Brevo API
     */
    private function sendBusinessGESEmailBrevo() {
        $gesEmail = function_exists('get_option') ? 
                    get_option('ges_notification_email', 'commercial@ges-solutions.fr') : 
                    'commercial@ges-solutions.fr';
        
        // Préparer les pièces jointes
        $attachments = array();
        
        // Ajouter le PDF de devis
        if (file_exists($this->pdfPath)) {
            $attachments[] = array(
                'name' => 'devis_professionnel.pdf',
                'content' => base64_encode(file_get_contents($this->pdfPath))
            );
        }
        
        // Ajouter les documents uploadés professionnels
        if (isset($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                if (isset($file_info['path']) && file_exists($file_info['path'])) {
                    $attachments[] = array(
                        'name' => $file_info['name'],
                        'content' => base64_encode(file_get_contents($file_info['path']))
                    );
                }
            }
        }
        
        $params = [
            'to' => [
                [
                    'email' => $gesEmail,
                    'name' => 'GES Commercial Pro'
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Nouveau devis électricité professionnel reçu',
            'htmlContent' => $this->loadTemplate('elec-professionnel-ges')
        ];
        
        // Ajouter les attachments seulement s'il y en a
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS CONTACT
     *******************************************/

    /**
     * Envoie l'email de contact à GES via Brevo
     */
    private function sendContactEmailToGES() {
        $gesEmail = function_exists('get_option') ? 
                    get_option('ges_notification_email', 'contact@ges-solutions.fr') : 
                    'contact@ges-solutions.fr';
        
        // Préparer les pièces jointes - vérification robuste
        $attachments = array();
        
        // Vérifier plusieurs formats possibles de fichiers uploadés
        if (!empty($this->data['uploaded_files'])) {
            // Format 1: uploaded_files['fichier']
            if (isset($this->data['uploaded_files']['fichier'])) {
                $file = $this->data['uploaded_files']['fichier'];
                if (!empty($file['path']) && file_exists($file['path']) && !empty($file['name'])) {
                    $attachments[] = array(
                        'name' => $file['name'],
                        'content' => base64_encode(file_get_contents($file['path']))
                    );
                }
            }
            
            // Format 2: uploaded_files comme tableau direct
            foreach ($this->data['uploaded_files'] as $key => $file) {
                if (is_array($file) && !empty($file['path']) && file_exists($file['path']) && !empty($file['name'])) {
                    $attachments[] = array(
                        'name' => $file['name'],
                        'content' => base64_encode(file_get_contents($file['path']))
                    );
                    break; // Un seul fichier pour le contact
                }
            }
        }
        
        $params = [
            'to' => [
                [
                    'email' => $gesEmail,
                    'name' => 'GES Solutions'
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'Site Web GES'
            ],
            'subject' => '[CONTACT] ' . ($this->data['objet'] ?? 'Nouvelle demande'),
            'htmlContent' => $this->loadTemplate('contact-ges')
        ];
        
        // Ajouter les attachments seulement s'il y en a vraiment
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
            error_log('Contact GES - Fichier attaché: ' . $attachments[0]['name']);
        } else {
            error_log('Contact GES - Aucun fichier attaché');
        }
        
        return $this->callBrevoAPI($params);
    }

    /**
     * Envoie l'email de confirmation au client
     */
    private function sendContactConfirmationToClient() {
        $params = [
            'to' => [
                [
                    'email' => $this->data['email'],
                    'name' => $this->data['firstName'] . ' ' . $this->data['lastName']
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Confirmation de votre demande - GES Solutions',
            'htmlContent' => $this->loadTemplate('contact-client')
        ];
        
        return $this->callBrevoAPI($params);
    }

    // Dans EmailHandler.php, ajouter cette nouvelle méthode :

/**
 * Traite les données du formulaire gaz résidentiel et envoie les emails
 */
public function processGazFormData($jsonData) {
    try {
        // Décoder les données JSON
        $this->data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
        }
        
        // CORRECTION - Traiter les fichiers uploadés depuis $_FILES
        $this->processUploadedFiles();
        
        // Restructurer les données pour être compatibles avec le format attendu
        $this->normalizeGazData();
        
        // Vérifier que les données essentielles sont présentes
        if (empty($this->data['email'])) {
            throw new Exception('Email client manquant');
        }
        
        // Générer le PDF pour le gaz
        $this->generateGazPDF();
        
        // Vérifier la clé API Brevo
        if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
            throw new Exception('Clé API Brevo non configurée');
        }

        $clientSent = $this->sendGazClientEmailBrevo();
        $gesSent = $this->sendGazGESEmailBrevo();
        
        // Nettoyer le PDF temporaire
        if (file_exists($this->pdfPath)) {
            unlink($this->pdfPath);
        }
        
        if ($clientSent && $gesSent) {
            return [
                'success' => true,
                'message' => 'Emails envoyés avec succès',
                'referenceNumber' => 'GAZ-' . date('Ymd') . '-' . rand(1000, 9999)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des emails'
            ];
        }
        
    } catch (Exception $e) {
        error_log('EmailHandler Gaz: Exception - ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ];
    }
}

private function processUploadedFiles() {
    error_log('EmailHandler GAZ: Traitement des fichiers $_FILES');
    error_log('EmailHandler GAZ: $_FILES contenu: ' . print_r($_FILES, true));
    
    // Initialiser uploaded_files si pas présent
    if (!isset($this->data['uploaded_files'])) {
        $this->data['uploaded_files'] = array();
    }
    
    // Traiter tous les fichiers qui commencent par 'file_'
    foreach ($_FILES as $field_name => $file_info) {
        // Les fichiers du gaz résidentiel arrivent comme 'file_rib_file', 'file_carte_identite_recto', etc.
        if (strpos($field_name, 'file_') === 0 && $file_info['error'] === UPLOAD_ERR_OK) {
            
            // Extraire le nom du type de fichier (enlever le préfixe 'file_')
            $file_type = substr($field_name, 5); // Enlever 'file_'
            
            error_log("EmailHandler GAZ: Traitement fichier $file_type depuis $_FILES[$field_name]");
            
            // Ajouter le fichier avec le format attendu par sendGazGESEmailBrevo()
            $this->data['uploaded_files'][$file_type] = array(
                'name' => $file_info['name'],
                'tmp_name' => $file_info['tmp_name'],  // ← Important pour sendGazGESEmailBrevo()
                'size' => $file_info['size'],
                'type' => $file_info['type'],
                'error' => $file_info['error']
            );
            
            error_log("EmailHandler GAZ: Fichier $file_type ajouté - " . $file_info['name'] . " (" . $file_info['size'] . " bytes)");
        }
    }
    
    error_log('EmailHandler GAZ: Fichiers traités - ' . count($this->data['uploaded_files']) . ' fichier(s)');
    error_log('EmailHandler GAZ: uploaded_files final: ' . print_r($this->data['uploaded_files'], true));
}

/**
 * Normalise les données gaz pour être compatibles avec le format standard
 */
private function normalizeGazData() {
    // Si les données sont dans le format du récapitulatif gaz
    if (isset($this->data['client_data'])) {
        $clientData = $this->data['client_data'];
        $formData = $this->data['form_data'] ?? [];
        $resultsData = $this->data['results_data'] ?? [];
        
        // Restructurer pour le format standard
        $this->data['firstName'] = $clientData['prenom'] ?? '';
        $this->data['lastName'] = $clientData['nom'] ?? '';
        $this->data['email'] = $clientData['email'] ?? '';
        $this->data['phone'] = $clientData['telephone'] ?? '';
        
        // Données du logement
        $this->data['commune'] = $formData['commune'] ?? '';
        $this->data['surface'] = $formData['superficie'] ?? 0;
        $this->data['residents'] = $formData['nb_personnes'] ?? 0;
        $this->data['housingType'] = $formData['type_logement'] ?? '';
        $this->data['chauffageGaz'] = $formData['chauffage_gaz'] ?? 'non';
        $this->data['eauChaude'] = $formData['eau_chaude'] ?? '';
        $this->data['cuisson'] = $formData['cuisson'] ?? '';
        
        // Résultats
        $this->data['annualConsumption'] = $resultsData['consommation_annuelle'] ?? 0;
        $this->data['annualCost'] = $resultsData['cout_annuel_ttc'] ?? 0;
        $this->data['monthlyCost'] = $resultsData['total_mensuel'] ?? 0;
    }
}

/**
 * Génère le PDF pour la simulation gaz
 */
private function generateGazPDF() {
    $temp_dir = wp_upload_dir()['basedir'] . '/temp-simulations';
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    $this->pdfPath = $temp_dir . '/simulation_gaz_' . time() . '.pdf';
    
    $pdfGenerator = new PDFGenerator();
    $success = $pdfGenerator->generateGazPDF($this->data, $this->pdfPath);
    
    if (!$success) {
        throw new Exception('Erreur lors de la génération du PDF gaz');
    }
}

/**
 * Envoie l'email au client pour le gaz
 */
private function sendGazClientEmailBrevo() {
    $params = [
        'to' => [
            [
                'email' => $this->data['email'],
                'name' => $this->data['firstName'] . ' ' . $this->data['lastName']
            ]
        ],
        'sender' => [
            'email' => 'contact@applitwo.com',
            'name' => 'GES Solutions'
        ],
        'subject' => 'Votre simulation gaz - GES Solutions',
        'htmlContent' => $this->loadTemplate('gaz-residentiel-client'),
        'attachment' => [
            [
                'name' => 'simulation_gaz.pdf',
                'content' => base64_encode(file_get_contents($this->pdfPath)),
                'type' => 'application/pdf'
            ]
        ]
    ];
    
    return $this->callBrevoAPI($params);
}

    /**
     * Envoie l'email à GES pour le gaz
     */
    private function sendGazGESEmailBrevo() {
        $gesEmail = function_exists('get_option') ? 
                    get_option('ges_notification_email', 'commercial@ges-solutions.fr') : 
                    'commercial@ges-solutions.fr';
        
        $attachments = [];
        
        // PDF de simulation
        if (file_exists($this->pdfPath)) {
            $attachments[] = [
                'name' => 'simulation_gaz_client.pdf',
                'content' => base64_encode(file_get_contents($this->pdfPath))
            ];
        }
        
        // CORRECTION - Documents uploadés avec gestion flexible
        if (isset($this->data['uploaded_files']) && is_array($this->data['uploaded_files'])) {
            error_log('EmailHandler GAZ: Fichiers reçus - ' . print_r(array_keys($this->data['uploaded_files']), true));
            
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                error_log("EmailHandler GAZ: Traitement fichier $file_key - " . print_r($file_info, true));
                
                // FORMAT 1: Fichier avec 'path' (méthode standard)
                if (isset($file_info['path']) && file_exists($file_info['path'])) {
                    error_log("EmailHandler GAZ: Fichier trouvé via path - " . $file_info['path']);
                    $attachments[] = [
                        'name' => $file_info['name'] ?? basename($file_info['path']),
                        'content' => base64_encode(file_get_contents($file_info['path']))
                    ];
                }
                // FORMAT 2: Fichier avec 'tmp_name' (directement depuis $_FILES)
                elseif (isset($file_info['tmp_name']) && file_exists($file_info['tmp_name'])) {
                    error_log("EmailHandler GAZ: Fichier trouvé via tmp_name - " . $file_info['tmp_name']);
                    $attachments[] = [
                        'name' => $file_info['name'] ?? $file_info['original_name'] ?? 'document.pdf',
                        'content' => base64_encode(file_get_contents($file_info['tmp_name']))
                    ];
                }
                // FORMAT 3: Fichier avec 'file_path' (alternative)
                elseif (isset($file_info['file_path']) && file_exists($file_info['file_path'])) {
                    error_log("EmailHandler GAZ: Fichier trouvé via file_path - " . $file_info['file_path']);
                    $attachments[] = [
                        'name' => $file_info['name'] ?? basename($file_info['file_path']),
                        'content' => base64_encode(file_get_contents($file_info['file_path']))
                    ];
                }
                // FORMAT 4: Fichier avec 'content' en base64 (déjà traité)
                elseif (isset($file_info['content']) && isset($file_info['name'])) {
                    error_log("EmailHandler GAZ: Fichier trouvé en base64 - " . $file_info['name']);
                    $attachments[] = [
                        'name' => $file_info['name'],
                        'content' => $file_info['content']  // Déjà en base64
                    ];
                }
                else {
                    error_log("EmailHandler GAZ: Format de fichier non reconnu pour $file_key - " . print_r($file_info, true));
                }
            }
        } else {
            error_log('EmailHandler GAZ: Aucun fichier uploadé ou format incorrect');
        }
        
        error_log('EmailHandler GAZ: ' . count($attachments) . ' fichier(s) attaché(s)');
        
        $params = [
            'to' => [
                [
                    'email' => $gesEmail,
                    'name' => 'GES Commercial'
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => 'Nouvelle simulation gaz reçue',
            'htmlContent' => $this->loadTemplate('gaz-residentiel-ges')
        ];
        
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
            error_log('EmailHandler GAZ: Email avec ' . count($attachments) . ' pièce(s) jointe(s)');
        } else {
            error_log('EmailHandler GAZ: Email sans pièce jointe');
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * UTILITAIRES
     *******************************************/

    /**
     * Charge un template avec les données
     */
    private function loadTemplate($templateName) {
        $templatePath = __DIR__ . '/templates/' . $templateName . '.php';
        
        if (!file_exists($templatePath)) {
            error_log('Template non trouvé: ' . $templatePath);
            throw new Exception('Template non trouvé: ' . $templateName);
        }
        
        // Préparer les données pour le template
        $templateData = $this->prepareTemplateData();
        
        // Extraire les variables pour le template
        extract($templateData);
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Prépare les données dans le format attendu par les templates
     */
    private function prepareTemplateData() {
        return [
            // Données brutes pour compatibilité
            'data' => $this->data,
            
            // Client avec tous les champs possibles
            'client' => [
                'nom' => $this->data['lastName'] ?? '',
                'prenom' => $this->data['firstName'] ?? '',
                'email' => $this->data['email'] ?? '',
                'phone' => $this->data['phone'] ?? '',
                'telephone' => $this->data['phone'] ?? '', // Alias pour compatibilité templates
                'postalCode' => $this->data['postalCode'] ?? '',
                'firstName' => $this->data['firstName'] ?? '',
                'lastName' => $this->data['lastName'] ?? ''
            ],
            
            // Simulation résidentielle
            'simulation' => [
                'type_logement' => $this->data['housingType'] ?? '',
                'surface' => $this->data['surface'] ?? '',
                'nb_personnes' => $this->data['residents'] ?? '',
                'isolation' => $this->data['isolation'] ?? '',
                'type_chauffage' => $this->data['heatingType'] ?? '',
                'electromenagers' => $this->data['appliances']['electromenagers'] ?? []
            ],
            
            // Résultats
            'results' => [
                'consommation_annuelle' => $this->data['annualConsumption'] ?? 0,
                'puissance_recommandee' => $this->data['contractPower'] ?? '',
                'estimation_mensuelle' => $this->data['monthlyEstimate'] ?? 0,
                'estimation_annuelle' => $this->data['annualEstimate'] ?? 0
            ],
            
            // Entreprise/professionnel
            'business' => [
                'companyName' => $this->data['companyName'] ?? '',
                'siret' => $this->data['siret'] ?? '',
                'legalForm' => $this->data['legalForm'] ?? '',
                'category' => $this->data['category'] ?? '',
                'contractType' => $this->data['contractType'] ?? '',
                'selectedOffer' => $this->data['selectedOffer'] ?? null
            ],
            
            // Contact
            'contact' => [
                'objet' => $this->data['objet'] ?? 'Contact général',
                'message' => $this->data['message'] ?? '',
                'company' => $this->data['company'] ?? ''
            ],
            
            // Fichiers uploadés
            'uploaded_files' => $this->data['uploaded_files'] ?? [],
            
            // Métadonnées
            'date' => date('d/m/Y'),
            'timestamp' => time(),
            'datetime' => date('d/m/Y H:i'),
            'reference' => 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
        ];
    }

    /**
     * Appel API Brevo
     */
    private function callBrevoAPI($params) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        
        // Log pour debug
        error_log('Brevo API Call - Subject: ' . ($params['subject'] ?? 'No subject'));
        error_log('Brevo API Call - Has attachment: ' . (isset($params['attachment']) ? 'YES (' . count($params['attachment']) . ')' : 'NO'));
        
        // Si on est dans WordPress, utiliser wp_remote_post
        if (function_exists('wp_remote_post')) {
            $response = wp_remote_post($url, [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'api-key' => $this->brevoApiKey
                ],
                'body' => json_encode($params),
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                error_log('Brevo API: Erreur WP - ' . $response->get_error_message());
                return false;
            }
            
            $statusCode = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($statusCode === 201) {
                error_log('Brevo API: SUCCÈS - Email envoyé');
                return true;
            } else {
                error_log('Brevo API: ÉCHEC - Code ' . $statusCode . ' - ' . $body);
                return false;
            }
                
        } else {
            // Fallback avec cURL si pas dans WordPress
            $headers = [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->brevoApiKey
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 201) {
                return true;
            } else {
                error_log('Erreur Brevo: ' . $response);
                return false;
            }
        }
    }
}