<?php
/**
 * EmailHandler.php - Gestionnaire d'emails pour GES Solutions
 * includes/SendEmail/EmailHandler.php
 * 
 * Gère l'envoi d'emails via Brevo pour tous les types de simulations :
 * - Résidentiel électricité
 * - Professionnel électricité  
 * - Résidentiel gaz
 * - Professionnel gaz
 * - Formulaire de contact
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
        if (function_exists('get_option')) {
            $this->brevoApiKey = get_option('brevo_api_key', BREVO_API_KEY);
            $this->templateIdClient = get_option('brevo_template_client', 1);
            $this->templateIdGES = get_option('brevo_template_ges', 2);
        } else {
            $this->brevoApiKey = BREVO_API_KEY;
            $this->templateIdClient = 1;
            $this->templateIdGES = 2;
        }
    }
    
    /*******************************************
     * PROCESSUS PRINCIPAUX PAR TYPE
     *******************************************/
    
    /**
     * Traite les données du formulaire résidentiel électricité
     */
    public function processFormData($jsonData) {
        try {
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }

            if (empty($this->data['email'])) {
                throw new Exception('Email client manquant');
            }
            
            $this->generateResidentialPDF();
            $this->validateBrevoConfig();

            $clientSent = $this->sendClientEmailBrevo();
            $gesSent = $this->sendGESEmailBrevo();
            
            $this->cleanupTempFile();
            
            return [
                'success' => ($clientSent && $gesSent),
                'message' => ($clientSent && $gesSent) ? 'Emails envoyés avec succès' : 'Erreur lors de l\'envoi des emails'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Traite le formulaire professionnel électricité
     */
    public function processBusinessFormData($jsonData) {
        try {
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            if (empty($this->data['email'])) {
                throw new Exception('Email responsable manquant');
            }
            
            if (empty($this->data['companyName'])) {
                throw new Exception('Nom entreprise manquant');
            }
            
            $this->generateBusinessPDF();
            $this->validateBrevoConfig();

            $clientSent = $this->sendBusinessClientEmailBrevo();
            $gesSent = $this->sendBusinessGESEmailBrevo();
            
            $this->cleanupTempFile();
            
            return [
                'success' => ($clientSent && $gesSent),
                'message' => ($clientSent && $gesSent) ? 'Devis professionnel envoyé avec succès' : 'Erreur lors de l\'envoi du devis professionnel',
                'referenceNumber' => ($clientSent && $gesSent) ? 'PRO-' . date('Ymd') . '-' . rand(1000, 9999) : null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite les données du formulaire gaz résidentiel
     */
    public function processGazFormData($jsonData) {
        try {
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            $this->processUploadedFiles();
            $this->normalizeGazData();
            
            if (empty($this->data['email'])) {
                throw new Exception('Email client manquant');
            }
            
            $this->generateGazPDF();
            $this->validateBrevoConfig();

            $clientSent = $this->sendGazClientEmailBrevo();
            $gesSent = $this->sendGazGESEmailBrevo();
            
            $this->cleanupTempFile();
            
            return [
                'success' => ($clientSent && $gesSent),
                'message' => ($clientSent && $gesSent) ? 'Emails envoyés avec succès' : 'Erreur lors de l\'envoi des emails',
                'referenceNumber' => ($clientSent && $gesSent) ? 'GAZ-' . date('Ymd') . '-' . rand(1000, 9999) : null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite les données du formulaire gaz professionnel
     */
    public function processGazProfessionnelFormData($jsonData) {
        try {
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            $this->processUploadedFiles();
            $this->normalizeGazProfessionnelData();
            
            if (empty($this->data['email'])) {
                throw new Exception('Email responsable manquant');
            }
            
            if (empty($this->data['companyName'])) {
                throw new Exception('Nom entreprise manquant');
            }
            
            $this->generateGazProfessionnelPDF();
            $this->validateBrevoConfig();

            $clientSent = $this->sendGazProfessionnelClientEmailBrevo();
            $gesSent = $this->sendGazProfessionnelGESEmailBrevo();
            
            $this->cleanupTempFile();
            
            return [
                'success' => ($clientSent && $gesSent),
                'message' => ($clientSent && $gesSent) ? 'Devis gaz professionnel envoyé avec succès' : 'Erreur lors de l\'envoi du devis gaz professionnel',
                'referenceNumber' => ($clientSent && $gesSent) ? 'GAZ-PRO-' . date('Ymd') . '-' . rand(1000, 9999) : null
            ];
            
        } catch (Exception $e) {
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
            $this->data = $form_data;
            
            if (empty($this->data['email'])) {
                return array('success' => false, 'message' => 'Email client manquant');
            }
            
            $this->validateBrevoConfig();
            
            $clientSent = $this->sendContactConfirmationToClient();
            $gesSent = $this->sendContactEmailToGES();
            
            return array(
                'success' => ($clientSent && $gesSent),
                'message' => ($clientSent && $gesSent) ? 'Emails de contact envoyés avec succès' : 'Erreur lors de l\'envoi des emails de contact'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erreur contact: ' . $e->getMessage()
            );
        }
    }

    /*******************************************
     * GÉNÉRATION PDF
     *******************************************/

    private function generateResidentialPDF() {
        $this->prepareTempDirectory();
        $this->pdfPath = $this->getTempPath('simulation_' . time() . '.pdf');
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateResidentialPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF résidentiel');
        }
    }

    private function generateBusinessPDF() {
        $this->prepareTempDirectory();
        $this->pdfPath = $this->getTempPath('devis_professionnel_' . time() . '.pdf');
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateBusinessPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF professionnel');
        }
    }

    private function generateGazPDF() {
        $this->prepareTempDirectory();
        $this->pdfPath = $this->getTempPath('simulation_gaz_' . time() . '.pdf');
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateGazPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF gaz');
        }
    }

    private function generateGazProfessionnelPDF() {
        $this->prepareTempDirectory();
        $this->pdfPath = $this->getTempPath('devis_gaz_professionnel_' . time() . '.pdf');
        
        $pdfGenerator = new PDFGenerator();
        $success = $pdfGenerator->generateGazProfessionnelPDF($this->data, $this->pdfPath);
        
        if (!$success) {
            throw new Exception('Erreur lors de la génération du PDF gaz professionnel');
        }
    }

    /*******************************************
     * ENVOI EMAILS ÉLECTRICITÉ RÉSIDENTIEL
     *******************************************/

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

    private function sendGESEmailBrevo() {
        $gesEmail = $this->getGESEmail();
        $attachments = $this->prepareAttachments('simulation_client.pdf');
        
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
        
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS ÉLECTRICITÉ PROFESSIONNEL
     *******************************************/

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

    private function sendBusinessGESEmailBrevo() {
        $gesEmail = $this->getGESEmail();
        $attachments = $this->prepareAttachments('devis_professionnel.pdf');
        
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
        
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS GAZ RÉSIDENTIEL
     *******************************************/

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

    private function sendGazGESEmailBrevo() {
        $gesEmail = $this->getGESEmail();
        $attachments = $this->prepareGazAttachments('simulation_gaz_client.pdf');
        
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
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS GAZ PROFESSIONNEL
     *******************************************/

    private function sendGazProfessionnelClientEmailBrevo() {
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
            'subject' => $this->data['isHighConsumption'] 
                ? 'Votre demande de devis gaz professionnel - GES Solutions'
                : 'Votre devis gaz professionnel - GES Solutions',
            'htmlContent' => $this->loadTemplate('gaz-professionnel-client'),
            'attachment' => [
                [
                    'name' => 'devis_gaz_professionnel.pdf',
                    'content' => base64_encode(file_get_contents($this->pdfPath)),
                    'type' => 'application/pdf'
                ]
            ]
        ];
        
        return $this->callBrevoAPI($params);
    }

    private function sendGazProfessionnelGESEmailBrevo() {
        $gesEmail = $this->getGESEmail();
        $attachments = $this->prepareGazProfessionnelAttachments();
        
        $subject = $this->data['isHighConsumption'] 
            ? 'Nouvelle demande de devis gaz professionnel (grosse consommation)'
            : 'Nouveau devis gaz professionnel reçu';
        
        $params = [
            'to' => [
                [
                    'email' => $gesEmail,
                    'name' => 'GES Commercial Gaz Pro'
                ]
            ],
            'sender' => [
                'email' => 'contact@applitwo.com',
                'name' => 'GES Solutions'
            ],
            'subject' => $subject,
            'htmlContent' => $this->loadTemplate('gaz-professionnel-ges')
        ];
        
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

    /*******************************************
     * ENVOI EMAILS CONTACT
     *******************************************/

    private function sendContactEmailToGES() {
        $gesEmail = function_exists('get_option') ? 
                    get_option('ges_notification_email', 'contact@ges-solutions.fr') : 
                    'contact@ges-solutions.fr';
        
        $attachments = $this->prepareContactAttachments();
        
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
        
        if (!empty($attachments)) {
            $params['attachment'] = $attachments;
        }
        
        return $this->callBrevoAPI($params);
    }

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

    /*******************************************
     * GESTION DES FICHIERS UPLOADÉS
     *******************************************/

    private function processUploadedFiles() {
        if (!isset($this->data['uploaded_files'])) {
            $this->data['uploaded_files'] = array();
        }
        
        $simulationType = $this->detectSimulationType();
        
        if ($simulationType === 'gaz-professionnel') {
            $this->processBusinessFiles(['kbis_file', 'rib_entreprise', 'mandat_signature']);
        } else {
            $this->processResidentialFiles();
        }
    }

    private function detectSimulationType() {
        $simulationType = $this->data['simulationType'] ?? 'gaz-residentiel';
        
        if ($simulationType === 'gaz-residentiel') {
            if (isset($_FILES['kbis_file']) || isset($_FILES['rib_entreprise']) || isset($_FILES['mandat_signature'])) {
                $simulationType = 'gaz-professionnel';
                $this->data['simulationType'] = 'gaz-professionnel';
            }
        }
        
        return $simulationType;
    }

    private function processBusinessFiles($fileTypes) {
        foreach ($fileTypes as $file_type) {
            if (isset($_FILES[$file_type]) && $_FILES[$file_type]['error'] === UPLOAD_ERR_OK) {
                $this->data['uploaded_files'][$file_type] = array(
                    'name' => $_FILES[$file_type]['name'],
                    'tmp_name' => $_FILES[$file_type]['tmp_name'],
                    'size' => $_FILES[$file_type]['size'],
                    'type' => $_FILES[$file_type]['type'],
                    'error' => $_FILES[$file_type]['error']
                );
            }
        }
    }

    private function processResidentialFiles() {
        foreach ($_FILES as $field_name => $file_info) {
            if (strpos($field_name, 'file_') === 0 && $file_info['error'] === UPLOAD_ERR_OK) {
                $file_type = substr($field_name, 5);
                
                $this->data['uploaded_files'][$file_type] = array(
                    'name' => $file_info['name'],
                    'tmp_name' => $file_info['tmp_name'],
                    'size' => $file_info['size'],
                    'type' => $file_info['type'],
                    'error' => $file_info['error']
                );
            }
        }
    }

    /*******************************************
     * NORMALISATION DES DONNÉES
     *******************************************/

    private function normalizeGazData() {
        if (isset($this->data['client_data'])) {
            $clientData = $this->data['client_data'];
            $formData = $this->data['form_data'] ?? [];
            $resultsData = $this->data['results_data'] ?? [];
            
            $this->data['firstName'] = $clientData['prenom'] ?? '';
            $this->data['lastName'] = $clientData['nom'] ?? '';
            $this->data['email'] = $clientData['email'] ?? '';
            $this->data['phone'] = $clientData['telephone'] ?? '';
            
            $this->data['commune'] = $formData['commune'] ?? '';
            $this->data['surface'] = $formData['superficie'] ?? 0;
            $this->data['residents'] = $formData['nb_personnes'] ?? 0;
            $this->data['housingType'] = $formData['type_logement'] ?? '';
            $this->data['chauffageGaz'] = $formData['chauffage_gaz'] ?? 'non';
            $this->data['eauChaude'] = $formData['eau_chaude'] ?? '';
            $this->data['cuisson'] = $formData['cuisson'] ?? '';
            
            $this->data['annualConsumption'] = $resultsData['consommation_annuelle'] ?? 0;
            $this->data['annualCost'] = $resultsData['cout_annuel_ttc'] ?? 0;
            $this->data['monthlyCost'] = $resultsData['total_mensuel'] ?? 0;
        }
    }

    private function normalizeGazProfessionnelData() {
        if (!empty($this->data['email']) && !empty($this->data['companyName'])) {
            return;
        }
        
        if (!empty($this->data['responsable_email']) && empty($this->data['email'])) {
            $this->data['email'] = $this->data['responsable_email'];
        }
        
        if (!empty($this->data['raison_sociale']) && empty($this->data['companyName'])) {
            $this->data['companyName'] = $this->data['raison_sociale'];
        }
        
        $mappings = [
            'companyName' => 'raison_sociale',
            'legalForm' => 'forme_juridique',
            'siret' => 'siret',
            'nafCode' => 'code_naf',
            'firstName' => 'responsable_prenom',
            'lastName' => 'responsable_nom',
            'email' => 'responsable_email',
            'phone' => 'responsable_telephone',
            'fonction' => 'responsable_fonction',
            'companyAddress' => 'entreprise_adresse',
            'companyPostalCode' => 'entreprise_code_postal',
            'companyCity' => 'entreprise_ville'
        ];
        
        foreach ($mappings as $normalized => $original) {
            if (!empty($this->data[$original]) && empty($this->data[$normalized])) {
                $this->data[$normalized] = $this->data[$original];
            }
        }
        
        $this->data['commune'] = $this->data['commune'] ?? '';
        $this->data['annualConsumption'] = intval($this->data['consommation_previsionnelle'] ?? 0);
        $this->data['gasType'] = $this->determineGazType();
        $this->data['contractType'] = $this->data['type_contrat'] ?? 'principal';
        $this->data['selectedTariff'] = $this->data['tarif_choisi'] ?? '';
        
        $this->data['isHighConsumption'] = $this->data['annualConsumption'] > 35000;
        
        if (!$this->data['isHighConsumption']) {
            $this->data['annualCost'] = floatval($this->data['cout_annuel'] ?? 0);
            $this->data['monthlyCost'] = round($this->data['annualCost'] / 10, 2);
        } else {
            $this->data['annualCost'] = 0;
            $this->data['monthlyCost'] = 0;
        }
        
        $this->data['acceptConditions'] = $this->toBool($this->data['accept_conditions_pro'] ?? false);
        $this->data['acceptPrelevement'] = $this->toBool($this->data['accept_prelevement_pro'] ?? false);
        $this->data['certifiePouvoir'] = $this->toBool($this->data['certifie_pouvoir'] ?? false);
    }

    /*******************************************
     * PRÉPARATION DES PIÈCES JOINTES
     *******************************************/

    private function prepareAttachments($pdfName) {
        $attachments = [];
        
        if (file_exists($this->pdfPath)) {
            $attachments[] = [
                'name' => $pdfName,
                'content' => base64_encode(file_get_contents($this->pdfPath))
            ];
        }
        
        if (isset($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                $attachment = $this->processFileAttachment($file_info);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }
        
        return $attachments;
    }

    private function prepareGazAttachments($pdfName) {
        $attachments = [];
        
        if (file_exists($this->pdfPath)) {
            $attachments[] = [
                'name' => $pdfName,
                'content' => base64_encode(file_get_contents($this->pdfPath))
            ];
        }
        
        if (isset($this->data['uploaded_files']) && is_array($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                $attachment = $this->processFileAttachment($file_info);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }
        
        return $attachments;
    }

    private function prepareGazProfessionnelAttachments() {
        $attachments = [];
        
        if (file_exists($this->pdfPath)) {
            $attachments[] = [
                'name' => 'devis_gaz_professionnel.pdf',
                'content' => base64_encode(file_get_contents($this->pdfPath))
            ];
        }
        
        if (isset($this->data['uploaded_files']) && is_array($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                $attachment = $this->processFileAttachment($file_info);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }
        
        if (empty($attachments) || count($attachments) <= 1) {
            $expected_files = ['kbis_file', 'rib_entreprise', 'mandat_signature'];
            
            foreach ($expected_files as $file_key) {
                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                    if (file_exists($_FILES[$file_key]['tmp_name'])) {
                        $file_content = file_get_contents($_FILES[$file_key]['tmp_name']);
                        if ($file_content) {
                            $attachments[] = [
                                'name' => $_FILES[$file_key]['name'],
                                'content' => base64_encode($file_content)
                            ];
                        }
                    }
                }
            }
        }
        
        return $attachments;
    }

    private function prepareContactAttachments() {
        $attachments = [];
        
        if (!empty($this->data['uploaded_files'])) {
            if (isset($this->data['uploaded_files']['fichier'])) {
                $file = $this->data['uploaded_files']['fichier'];
                if (!empty($file['path']) && file_exists($file['path']) && !empty($file['name'])) {
                    $attachments[] = [
                        'name' => $file['name'],
                        'content' => base64_encode(file_get_contents($file['path']))
                    ];
                }
            }
            
            foreach ($this->data['uploaded_files'] as $key => $file) {
                if (is_array($file) && !empty($file['path']) && file_exists($file['path']) && !empty($file['name'])) {
                    $attachments[] = [
                        'name' => $file['name'],
                        'content' => base64_encode(file_get_contents($file['path']))
                    ];
                    break;
                }
            }
        }
        
        return $attachments;
    }

    private function processFileAttachment($file_info) {
        if (isset($file_info['path']) && file_exists($file_info['path'])) {
            return [
                'name' => $file_info['name'] ?? basename($file_info['path']),
                'content' => base64_encode(file_get_contents($file_info['path']))
            ];
        } elseif (isset($file_info['tmp_name']) && file_exists($file_info['tmp_name'])) {
            return [
                'name' => $file_info['name'] ?? 'document.pdf',
                'content' => base64_encode(file_get_contents($file_info['tmp_name']))
            ];
        } elseif (isset($file_info['file_path']) && file_exists($file_info['file_path'])) {
            return [
                'name' => $file_info['name'] ?? basename($file_info['file_path']),
                'content' => base64_encode(file_get_contents($file_info['file_path']))
            ];
        } elseif (isset($file_info['content']) && isset($file_info['name'])) {
            return [
                'name' => $file_info['name'],
                'content' => $file_info['content']
            ];
        }
        
        return null;
    }

    /*******************************************
     * UTILITAIRES ET HELPERS
     *******************************************/

    private function validateBrevoConfig() {
        if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
            throw new Exception('Clé API Brevo non configurée');
        }
    }

    private function prepareTempDirectory() {
        $temp_dir = wp_upload_dir()['basedir'] . '/temp-simulations';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
    }

    private function getTempPath($filename) {
        return wp_upload_dir()['basedir'] . '/temp-simulations/' . $filename;
    }

    private function cleanupTempFile() {
        if (file_exists($this->pdfPath)) {
            unlink($this->pdfPath);
        }
    }

    private function getGESEmail() {
        return function_exists('get_option') ? 
               get_option('ges_notification_email', 'commercial@ges-solutions.fr') : 
               'commercial@ges-solutions.fr';
    }

    private function determineGazType() {
        if (isset($this->data['type_gaz_autre'])) {
            return $this->data['type_gaz_autre'] === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
        }
        
        $commune = $this->data['commune'] ?? '';
        $communes_naturel = [
            'AIRE SUR L\'ADOUR', 'BARCELONNE DU GERS', 'GAAS', 
            'LABATUT', 'LALUQUE', 'MISSON', 'POUILLON'
        ];
        
        if (in_array(strtoupper($commune), $communes_naturel)) {
            return 'Gaz naturel';
        }
        
        return 'Gaz propane';
    }

    private function toBool($value) {
        if (is_bool($value)) return $value;
        if (is_string($value)) return in_array(strtolower($value), ['true', '1', 'yes', 'oui', 'on']);
        if (is_numeric($value)) return $value != 0;
        return false;
    }

    /*******************************************
     * GESTION DES TEMPLATES
     *******************************************/

    private function loadTemplate($templateName) {
        $templatePath = __DIR__ . '/templates/' . $templateName . '.php';
        
        if (!file_exists($templatePath)) {
            throw new Exception('Template non trouvé: ' . $templateName);
        }
        
        $templateData = $this->prepareTemplateData();
        extract($templateData);
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function prepareTemplateData() {
        return [
            'data' => $this->data,
            
            'client' => [
                'nom' => $this->data['lastName'] ?? '',
                'prenom' => $this->data['firstName'] ?? '',
                'email' => $this->data['email'] ?? '',
                'phone' => $this->data['phone'] ?? '',
                'telephone' => $this->data['phone'] ?? '',
                'postalCode' => $this->data['postalCode'] ?? '',
                'firstName' => $this->data['firstName'] ?? '',
                'lastName' => $this->data['lastName'] ?? ''
            ],
            
            'simulation' => [
                'type_logement' => $this->data['housingType'] ?? '',
                'surface' => $this->data['surface'] ?? '',
                'nb_personnes' => $this->data['residents'] ?? '',
                'isolation' => $this->data['isolation'] ?? '',
                'type_chauffage' => $this->data['heatingType'] ?? '',
                'electromenagers' => $this->data['appliances']['electromenagers'] ?? []
            ],
            
            'results' => [
                'consommation_annuelle' => $this->data['annualConsumption'] ?? 0,
                'puissance_recommandee' => $this->data['contractPower'] ?? '',
                'estimation_mensuelle' => $this->data['monthlyEstimate'] ?? 0,
                'estimation_annuelle' => $this->data['annualEstimate'] ?? 0
            ],
            
            'business' => [
                'companyName' => $this->data['companyName'] ?? '',
                'siret' => $this->data['siret'] ?? '',
                'legalForm' => $this->data['legalForm'] ?? '',
                'category' => $this->data['category'] ?? '',
                'contractType' => $this->data['contractType'] ?? '',
                'selectedOffer' => $this->data['selectedOffer'] ?? null
            ],
            
            'contact' => [
                'objet' => $this->data['objet'] ?? 'Contact général',
                'message' => $this->data['message'] ?? '',
                'company' => $this->data['company'] ?? ''
            ],
            
            'uploaded_files' => $this->data['uploaded_files'] ?? [],
            
            'date' => date('d/m/Y'),
            'timestamp' => time(),
            'datetime' => date('d/m/Y H:i'),
            'reference' => 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
        ];
    }

    /*******************************************
     * API BREVO
     *******************************************/

    private function callBrevoAPI($params) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        
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
                return false;
            }
            
            $statusCode = wp_remote_retrieve_response_code($response);
            return $statusCode === 201;
                
        } else {
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
            
            return $httpCode === 201;
        }
    }
}
?>