<?php
/**
 * EmailHandler.php - Adapté pour WordPress avec Brevo
 * includes/SendEmail/EmailHandler.php
 */

require_once __DIR__ . '/init.php';

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
     * Traite les données du formulaire et envoie les emails via Brevo
     */
    public function processFormData($jsonData) {
        try {
            error_log('EmailHandler: Début processFormData');
            
            // Décoder les données JSON
            $this->data = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('EmailHandler: Erreur JSON - ' . json_last_error_msg());
                throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            error_log('EmailHandler: Données décodées OK');
            
            // Vérifier que les données essentielles sont présentes
            if (empty($this->data['email'])) {
                error_log('EmailHandler: Email manquant dans les données');
                throw new Exception('Email client manquant');
            }
            
            error_log('EmailHandler: Email client trouvé - ' . $this->data['email']);
            
            // Générer le PDF
            error_log('EmailHandler: Génération du PDF...');
            $this->generatePDF();
            error_log('EmailHandler: PDF généré - ' . $this->pdfPath);
            
            // Vérifier la clé API Brevo
            if (empty($this->brevoApiKey) || $this->brevoApiKey === 'xkeysib-VOTRE-CLE-API-BREVO') {
                error_log('EmailHandler: Clé API Brevo manquante ou invalide');
                throw new Exception('Clé API Brevo non configurée');
            }
            
            error_log('EmailHandler: Clé API Brevo OK');
            
            // Envoyer email au client via Brevo
            error_log('EmailHandler: Envoi email client...');
            $clientSent = $this->sendClientEmailBrevo();
            error_log('EmailHandler: Email client - ' . ($clientSent ? 'SUCCÈS' : 'ÉCHEC'));
            
            // Envoyer email à GES via Brevo
            error_log('EmailHandler: Envoi email GES...');
            $gesSent = $this->sendGESEmailBrevo();
            error_log('EmailHandler: Email GES - ' . ($gesSent ? 'SUCCÈS' : 'ÉCHEC'));
            
            // Nettoyer le PDF temporaire
            if (file_exists($this->pdfPath)) {
                unlink($this->pdfPath);
                error_log('EmailHandler: PDF temporaire supprimé');
            }
            
            if ($clientSent && $gesSent) {
                error_log('EmailHandler: Processus terminé avec SUCCÈS');
                return [
                    'success' => true,
                    'message' => 'Emails envoyés avec succès'
                ];
            } else {
                error_log('EmailHandler: Processus terminé avec ÉCHEC - Client: ' . ($clientSent ? 'OK' : 'KO') . ', GES: ' . ($gesSent ? 'OK' : 'KO'));
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
    
private function generatePDF() {
    $temp_dir = wp_upload_dir()['basedir'] . '/temp-simulations';
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    $this->pdfPath = $temp_dir . '/simulation_' . time() . '.pdf';
    
    try {
        $fpdf_file = __DIR__ . '/../libs/fpdf/fpdf.php';
        if (!file_exists($fpdf_file)) {
            throw new Exception('Fichier FPDF non trouvé: ' . $fpdf_file);
        }
        
        require_once $fpdf_file;
        
        // Créer le PDF standard
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 10);
        
        // Construire le PDF moderne
        $this->buildModernHeader($pdf);
        $this->buildMainSection($pdf);
        $this->buildDetailsSection($pdf);
        $this->buildEquipmentsSection($pdf);
        $this->buildModernFooter($pdf);
        
        // Sauvegarder
        $pdf->Output('F', $this->pdfPath);
        
        error_log('PDF généré avec succès: ' . $this->pdfPath . ' (' . filesize($this->pdfPath) . ' bytes)');
        
    } catch (Exception $e) {
        error_log('Erreur génération PDF: ' . $e->getMessage());
        $this->generateSimplePDF();
    }
}

private function buildModernHeader($pdf) {
    
    $pdf->SetFillColor(34, 47, 70);
    $pdf->Rect(0, 0, 210, 50, 'F');
    
    // Logo
    $logo_path = plugin_dir_path(__FILE__) . '../../logoS.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 20, 12, 25);
    }
    
    // Titre principal
    $pdf->SetFont('Arial', 'B', 26);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(52, 15);
    $pdf->Cell(0, 10, 'GES SOLUTIONS', 0, 1);
    
    // Sous-titre
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(240, 240, 240);
    $pdf->SetXY(52, 28);
    $pdf->Cell(0, 5, 'SIMULATION ELECTRICITE', 0, 1);
    
    // Référence et date à droite
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(140, 18);
    $pdf->Cell(50, 5, 'REF: SIM' . date('Ymd') . substr(md5($this->data['email']), 0, 4), 0, 1, 'R');
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(240, 240, 240);
    $pdf->SetXY(140, 25);
    $pdf->Cell(50, 5, date('d/m/Y H:i'), 0, 1, 'R');
    
    $pdf->SetY(55);
}

private function buildMainSection($pdf) {
    $y = $pdf->GetY();
    
    // Grande carte blanche principale
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(10, $y, 190, 85, 'F');
    
    // Bordure élégante
    $pdf->SetDrawColor(230, 230, 230);
    $pdf->SetLineWidth(0.8);
    $pdf->Rect(10, $y, 190, 85, 'D');
    
    // Bande colorée à gauche (accent) - BLEU FONCÉ
    $pdf->SetFillColor(34, 47, 70); // #222F46
    $pdf->Rect(10, $y, 5, 85, 'F');
    
    // Section CLIENT
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(140, 140, 140);
    $pdf->SetXY(25, $y + 10);
    $pdf->Cell(0, 5, 'CLIENT', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->SetX(25);
    $clientName = ($this->data['firstName'] ?? '') . ' ' . strtoupper($this->data['lastName'] ?? '');
    $pdf->Cell(0, 8, $clientName, 0, 1);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetX(25);
    $pdf->Cell(0, 5, $this->data['email'] ?? '', 0, 1);
    if (!empty($this->data['phone'])) {
        $pdf->SetX(25);
        $pdf->Cell(0, 5, $this->data['phone'], 0, 1);
    }
    
    // Ligne de séparation horizontale
    $pdf->SetDrawColor(240, 240, 240);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(25, $y + 38, 190, $y + 38);
    
    // MONTANT PRINCIPAL
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(140, 140, 140);
    $pdf->SetXY(25, $y + 44);
    $pdf->Cell(0, 5, 'ESTIMATION MENSUELLE TTC', 0, 1);
    
    // Prix en très grand - BLEU FONCÉ
    $estimation = number_format($this->data['monthlyEstimate'] ?? 0, 0, ',', ' ');
    $pdf->SetFont('Arial', 'B', 40);
    $pdf->SetTextColor(34, 47, 70); // #222F46
    $pdf->SetXY(25, $y + 52);
    $pdf->Cell(80, 18, $estimation, 0, 0);
    
    // EUR à côté du montant
    $pdf->SetFont('Arial', '', 20);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY(25 + strlen($estimation) * 10, $y + 58);
    $pdf->Cell(30, 10, 'EUR', 0, 0);
    
    // "par mois" en dessous
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(140, 140, 140);
    $pdf->SetXY(25, $y + 72);
    $pdf->Cell(0, 5, 'par mois', 0, 1);
    
    // Indicateurs à droite
    $this->buildIndicators($pdf, 130, $y + 10);
    
    $pdf->SetY($y + 90);
}

private function buildIndicators($pdf, $x, $y) {
    $indicators = [
        ['label' => 'Consommation', 'value' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' '), 'unit' => 'kWh/an', 'color' => [130, 199, 32]], // #82C720 Vert
        ['label' => 'Puissance', 'value' => $this->data['contractPower'] ?? '12', 'unit' => 'kVA', 'color' => [227, 148, 17]], // #E39411 Orange
        ['label' => 'Surface', 'value' => $this->data['surface'] ?? '100', 'unit' => 'm2', 'color' => [155, 89, 182]], // Violet
        ['label' => 'Option', 'value' => strtoupper($this->data['pricingType'] ?? 'HC'), 'unit' => '', 'color' => [34, 47, 70]] // #222F46 Bleu foncé
    ];
    
    foreach ($indicators as $i => $indicator) {
        $yPos = $y + ($i * 18);
        
        // Fond coloré pour chaque indicateur
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Rect($x, $yPos, 65, 15, 'F');
        
        // Petite barre colorée à gauche
        $pdf->SetFillColor($indicator['color'][0], $indicator['color'][1], $indicator['color'][2]);
        $pdf->Rect($x, $yPos, 3, 15, 'F');
        
        // Label
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY($x + 6, $yPos + 2);
        $pdf->Cell(55, 4, $indicator['label'], 0, 0);
        
        // Valeur en gras
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY($x + 6, $yPos + 7);
        $pdf->Cell(35, 6, $indicator['value'], 0, 0);
        
        // Unité
        if (!empty($indicator['unit'])) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(20, 6, $indicator['unit'], 0, 0);
        }
    }
}

private function buildDetailsSection($pdf) {
    $y = $pdf->GetY();
    
    // Titre de section avec ligne
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->SetXY(10, $y + 5);
    $pdf->Cell(0, 8, 'DETAILS DE LA SIMULATION', 0, 1);
    
    // Ligne sous le titre - BLEU FONCÉ
    $pdf->SetDrawColor(34, 47, 70); // #222F46
    $pdf->SetLineWidth(2);
    $pdf->Line(10, $pdf->GetY(), 70, $pdf->GetY());
    
    $y = $pdf->GetY() + 3;
    
    // Carte Logement - BLEU FONCÉ
    $this->buildDetailBox($pdf, 10, $y, 92, 'LOGEMENT', [
        'Type' => ucfirst($this->data['housingType'] ?? 'Maison'),
        'Surface' => ($this->data['surface'] ?? '100') . ' m2',
        'Occupants' => ($this->data['residents'] ?? '4') . ' personnes',
        'Isolation' => ucfirst($this->data['isolation'] ?? 'Standard'),
        'Chauffage' => ucfirst($this->data['heatingType'] ?? 'Electrique')
    ], [34, 47, 70]); // #222F46
    
    // Carte Consommation - VERT
    $this->buildDetailBox($pdf, 108, $y, 92, 'CONSOMMATION', [
        'Annuelle' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . ' kWh',
        'Mensuelle' => number_format(($this->data['annualConsumption'] ?? 0) / 12, 0, ' ', ' ') . ' kWh',
        'Option' => strtoupper($this->data['pricingType'] ?? 'HC'),
        'Puissance' => ($this->data['contractPower'] ?? '12') . ' kVA',
        'Budget' => number_format($this->data['monthlyEstimate'] ?? 0, 2, ',', ' ') . ' EUR/mois'
    ], [130, 199, 32]); // #82C720
    
    $pdf->SetY($y + 75);
}

private function buildDetailBox($pdf, $x, $y, $width, $title, $items, $color) {
    // Carte avec fond blanc
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($x, $y, $width, 70, 'F');
    
    // Bordure de la carte
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($x, $y, $width, 70, 'D');
    
    // Header coloré
    $pdf->SetFillColor($color[0], $color[1], $color[2]);
    $pdf->Rect($x, $y, $width, 12, 'F');
    
    // Titre en blanc
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($x, $y + 3);
    $pdf->Cell($width, 6, $title, 0, 1, 'C');
    
    // Contenu
    $yPos = $y + 17;
    foreach ($items as $label => $value) {
        // Label
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY($x + 5, $yPos);
        $pdf->Cell(30, 5, $label . ':', 0, 0);
        
        // Valeur
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell($width - 35, 5, $value, 0, 1);
        
        $yPos += 10;
    }
}

private function buildEquipmentsSection($pdf) {
    $y = $pdf->GetY() + 5;
    
    $appliances_labels = [
        'lave_linge' => ['name' => 'Lave-linge', 'color' => [34, 47, 70]], // #222F46 Bleu foncé
        'seche_linge' => ['name' => 'Seche-linge', 'color' => [227, 148, 17]], // #E39411 Orange
        'refrigerateur' => ['name' => 'Refrigerateur', 'color' => [130, 199, 32]], // #82C720 Vert
        'lave_vaisselle' => ['name' => 'Lave-vaisselle', 'color' => [34, 47, 70]], // #222F46 Bleu foncé
        'four' => ['name' => 'Four', 'color' => [227, 148, 17]], // #E39411 Orange
        'congelateur' => ['name' => 'Congelateur', 'color' => [130, 199, 32]] // #82C720 Vert
    ];
    
    $appliances = [];
    if (isset($this->data['appliances']) && !empty($this->data['appliances'])) {
        foreach ($this->data['appliances'] as $category => $items) {
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($appliances_labels[$item])) {
                        $appliances[] = $appliances_labels[$item];
                    }
                }
            }
        }
    }
    
    if (!empty($appliances)) {
        // Titre avec style
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(10, $y);
        $pdf->Cell(0, 8, 'EQUIPEMENTS DECLARES', 0, 1);
        
        // Ligne sous le titre - BLEU FONCÉ
        $pdf->SetDrawColor(34, 47, 70); // #222F46
        $pdf->SetLineWidth(2);
        $pdf->Line(10, $pdf->GetY(), 65, $pdf->GetY());
        
        $y = $pdf->GetY() + 3;
        $x = 10;
        
        foreach ($appliances as $appliance) {
            $width = $pdf->GetStringWidth($appliance['name']) + 16;
            
            // Nouvelle ligne si nécessaire
            if ($x + $width > 195) {
                $x = 10;
                $y += 13;
            }
            
            // Badge avec couleur
            $pdf->SetFillColor($appliance['color'][0], $appliance['color'][1], $appliance['color'][2]);
            $pdf->Rect($x, $y, $width, 10, 'F');
            
            // Texte en blanc
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($x, $y + 3);
            $pdf->Cell($width, 4, $appliance['name'], 0, 0, 'C');
            
            $x += $width + 5;
        }
        
        $pdf->SetY($y + 18);
    }
}

private function buildModernFooter($pdf) {
    $y = 265;
    
    // Background du footer
    $pdf->SetFillColor(245, 247, 250);
    $pdf->Rect(0, $y, 210, 35, 'F');
    
    // Ligne de séparation colorée - BLEU FONCÉ
    $pdf->SetFillColor(34, 47, 70); // #222F46
    $pdf->Rect(0, $y, 210, 1, 'F');
    
    // Message principal en gras
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->SetXY(0, $y + 6);
    $pdf->Cell(210, 6, 'UN CONSEILLER VOUS CONTACTERA SOUS 24H', 0, 1, 'C');
    
    // Informations de contact
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetY($y + 16);
    
    // Trois colonnes d'infos
    $pdf->Cell(70, 5, 'contact@ges-solutions.fr', 0, 0, 'C');
    $pdf->Cell(70, 5, '01 23 45 67 89', 0, 0, 'C');
    $pdf->Cell(70, 5, 'www.ges-solutions.fr', 0, 0, 'C');
    
    // Copyright et mentions légales
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetXY(0, $y + 26);
    $pdf->Cell(210, 4, 'Copyright ' . date('Y') . ' GES Solutions - Simulation indicative valable 30 jours - Document non contractuel', 0, 0, 'C');
}

private function generateSimplePDF() {
    // PDF de secours ultra simple
    require_once __DIR__ . '/../libs/fpdf/fpdf.php';
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Titre
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(0, 15, 'SIMULATION ELECTRICITE - GES SOLUTIONS', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informations principales
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(50, 50, 50);
    
    $content = "Client: " . ($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? '') . "\n";
    $content .= "Email: " . ($this->data['email'] ?? '') . "\n";
    $content .= "Telephone: " . ($this->data['phone'] ?? '') . "\n";
    $content .= "Code postal: " . ($this->data['postalCode'] ?? '') . "\n\n";
    $content .= "RESULTATS DE LA SIMULATION\n";
    $content .= "================================\n";
    $content .= "Estimation mensuelle: " . number_format($this->data['monthlyEstimate'] ?? 0, 2, ',', ' ') . " EUR/mois\n";
    $content .= "Consommation annuelle: " . number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . " kWh/an\n";
    $content .= "Puissance souscrite: " . ($this->data['contractPower'] ?? '12') . " kVA\n";
    $content .= "Option tarifaire: " . strtoupper($this->data['pricingType'] ?? 'BASE') . "\n\n";
    $content .= "DETAILS DU LOGEMENT\n";
    $content .= "================================\n";
    $content .= "Type: " . ucfirst($this->data['housingType'] ?? 'Maison') . "\n";
    $content .= "Surface: " . ($this->data['surface'] ?? '100') . " m2\n";
    $content .= "Nombre d'occupants: " . ($this->data['residents'] ?? '4') . " personnes\n";
    $content .= "Isolation: " . ucfirst($this->data['isolation'] ?? 'Standard') . "\n";
    $content .= "Type de chauffage: " . ucfirst($this->data['heatingType'] ?? 'Electrique') . "\n";
    
    $pdf->MultiCell(0, 6, $content);
    
    // Footer
    $pdf->SetY(260);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 10, 'Document genere le ' . date('d/m/Y') . ' - GES Solutions', 0, 1, 'C');
    
    $pdf->Output('F', $this->pdfPath);
}

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
            'htmlContent' => $this->buildClientEmailTemplate(),
            'attachment' => [
                [
                    'name' => 'simulation_electricite.pdf', // Changé en .pdf
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
        $attachments[] = array(
            'name' => 'simulation_client.pdf', // Changé en .pdf
            'content' => base64_encode(file_get_contents($this->pdfPath))
        );
        
        // Ajouter les documents uploadés
        if (isset($this->data['uploaded_files'])) {
            foreach ($this->data['uploaded_files'] as $file_key => $file_info) {
                if (isset($file_info['path']) && file_exists($file_info['path'])) {
                    $attachments[] = array(
                        'name' => $file_info['name'],
                        'content' => base64_encode(file_get_contents($file_info['path']))
                    );
                    
                    error_log("Pièce jointe ajoutée pour GES: " . $file_info['name']);
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
            'htmlContent' => $this->buildGESEmailTemplate(),
            'attachment' => $attachments // Utiliser le tableau préparé
        ];
        
        return $this->callBrevoAPI($params);
    }

    /**
     * Template pour l'email GES
     */
    private function buildGESEmailTemplate() {
        $data = $this->data;
        
        // Définir la priorité selon le montant
        $priorite = 'NORMALE';
        $prioriteColor = '#82C720';
        if (($data['monthlyEstimate'] ?? 0) > 200) {
            $priorite = 'HAUTE';
            $prioriteColor = '#E39411';
        }
        if (($data['monthlyEstimate'] ?? 0) > 300) {
            $priorite = 'URGENTE';
            $prioriteColor = '#e74c3c';
        }
        
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #222F46; color: white; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .priority-badge { display: inline-block; background: ' . $prioriteColor . '; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-top: 10px; }
                .content { padding: 30px; }
                .section { background: #f9f9f9; border-left: 4px solid #222F46; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .info-grid { display: table; width: 100%; }
                .info-row { display: table-row; }
                .info-label { display: table-cell; padding: 8px; font-weight: bold; color: #666; width: 40%; }
                .info-value { display: table-cell; padding: 8px; color: #333; }
                .highlight-box { background: #e8f5e8; border: 2px solid #82C720; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .amount { font-size: 32px; color: #222F46; font-weight: bold; }
                .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .action-required { background: #fff3cd; border-left: 4px solid #E39411; padding: 15px; margin: 20px 0; }
                .documents { background: #e3f2fd; padding: 10px; border-radius: 4px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>NOUVELLE SIMULATION</h2>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">Reçue le ' . date('d/m/Y à H:i') . '</p>
                </div>
                
                <div class="content">
                    <!-- Montant principal en évidence -->
                    <div class="highlight-box" style="text-align: center;">
                        <div style="color: #666; margin-bottom: 10px;">Estimation mensuelle client</div>
                        <div class="amount">' . number_format($data['monthlyEstimate'] ?? 0, 0, ',', ' ') . ' € TTC</div>
                        <div style="color: #666; margin-top: 5px;">soit ' . number_format(($data['monthlyEstimate'] ?? 0) * 12, 0, ',', ' ') . ' €/an</div>
                    </div>
                    
                    <!-- Informations client -->
                    <div class="section">
                        <h3 style="margin-top: 0; color: #222F46;">👤 Informations Client</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Nom complet :</div>
                                <div class="info-value"><strong>' . ($data['firstName'] ?? '') . ' ' . strtoupper($data['lastName'] ?? '') . '</strong></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email :</div>
                                <div class="info-value"><a href="mailto:' . ($data['email'] ?? '') . '">' . ($data['email'] ?? '') . '</a></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Téléphone :</div>
                                <div class="info-value"><strong style="color: #E39411;">' . ($data['phone'] ?? '') . '</strong></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Code postal :</div>
                                <div class="info-value">' . ($data['postalCode'] ?? '') . '</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Détails simulation -->
                    <div class="section">
                        <h3 style="margin-top: 0; color: #222F46;">📊 Détails de la Simulation</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Type logement :</div>
                                <div class="info-value">' . ucfirst($data['housingType'] ?? 'Non spécifié') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Surface :</div>
                                <div class="info-value">' . ($data['surface'] ?? 'N/A') . ' m²</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Nb occupants :</div>
                                <div class="info-value">' . ($data['residents'] ?? 'N/A') . ' personnes</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Consommation :</div>
                                <div class="info-value"><strong>' . number_format($data['annualConsumption'] ?? 0, 0, ' ', ' ') . ' kWh/an</strong></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Puissance :</div>
                                <div class="info-value">' . ($data['contractPower'] ?? 'N/A') . ' kVA</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Option tarifaire :</div>
                                <div class="info-value"><span style="background: #222F46; color: white; padding: 2px 8px; border-radius: 3px;">' . strtoupper($data['pricingType'] ?? 'BASE') . '</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documents joints -->
                    ' . ($this->hasUploadedFiles($data) ? '
                    <div class="documents">
                        <strong>📎 Documents fournis par le client :</strong>
                        <ul style="margin: 5px 0;">
                            ' . $this->listUploadedFiles($data) . '
                        </ul>
                    </div>' : '') . '
                    
                    <!-- Action requise -->
                    <div class="action-required">
                        <h3 style="margin-top: 0; color: #E39411;">⚠️ ACTION REQUISE</h3>
                        <ul style="margin: 10px 0;">
                            <li>Contacter le client <strong>sous 72h maximum</strong></li>
                            <li>Valider les informations de la simulation</li>
                            <li>Proposer les offres adaptées</li>
                            <li>Planifier la mise en service si accord</li>
                        </ul>
                    </div>
                    
                    <p style="text-align: center; color: #666; margin-top: 30px;">
                        📄 <strong>PDF complet en pièce jointe</strong> avec tous les détails de la simulation
                    </p>
                </div>
                
                <div class="footer">
                    <p>Email automatique - Système de simulation GES Solutions<br>
                </div>
            </div>
        </body>
        </html>';
    }

    // Fonction helper pour vérifier si des fichiers ont été uploadés
    private function hasUploadedFiles($data) {
        return isset($data['uploaded_files']) && !empty($data['uploaded_files']);
    }

    // Fonction helper pour lister les fichiers uploadés
    private function listUploadedFiles($data) {
        $list = '';
        if (isset($data['uploaded_files'])) {
            foreach ($data['uploaded_files'] as $file) {
                $list .= '<li>' . ($file['name'] ?? 'Document') . '</li>';
            }
        }
        return $list;
    }

    /**
     * Template HTML personnalisé utilisant le système de templates existant
     */
    private function buildClientEmailTemplate() {
        // Préparer les données dans le format attendu par les templates
        $templateData = [
            'type' => 'elec-residentiel',
            'client' => [
                'nom' => $this->data['lastName'] ?? '',
                'prenom' => $this->data['firstName'] ?? '',
                'email' => $this->data['email'] ?? ''
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
                'tarifs' => $this->data['tarifs'] ?? [],
                'estimation_mensuelle' => $this->data['monthlyEstimate'] ?? 0
            ]
        ];
        
        // Utiliser le template elec-residentiel.php
        return $this->loadTemplate('elec-residentiel', $templateData);
    }

    /**
     * Charger un template avec les données
     */
    private function loadTemplate($templateName, $data) {
        $templatePath = __DIR__ . '/templates/' . $templateName . '.php';
        
        if (!file_exists($templatePath)) {
            error_log('Template non trouvé: ' . $templatePath);
            return $this->buildFallbackTemplate();
        }
        
        // Extraire les variables pour le template
        extract($data);
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Template de fallback simple
     */
    private function buildFallbackTemplate() {
        $data = $this->data;
        return '
        <html>
        <body style="font-family: Arial, sans-serif;">
            <h2>Votre simulation électricité - GES Solutions</h2>
            <p>Bonjour ' . $data['firstName'] . ' ' . $data['lastName'] . ',</p>
            <p>Merci pour votre simulation électricité.</p>
            <ul>
                <li><strong>Consommation annuelle :</strong> ' . number_format($data['annualConsumption'] ?? 0) . ' kWh</li>
                <li><strong>Estimation mensuelle :</strong> ' . number_format($data['monthlyEstimate'] ?? 0, 2) . ' €/mois</li>
            </ul>
            <p>Notre équipe vous contactera prochainement.</p>
            <p>Cordialement,<br>GES Solutions</p>
        </body>
        </html>';
    }
    
    /**
     * Prépare les paramètres pour les templates Brevo
     */
    private function prepareTemplateParams() {
        return [
            'FIRSTNAME' => $this->data['firstName'] ?? '',
            'LASTNAME' => $this->data['lastName'] ?? '',
            'EMAIL' => $this->data['email'] ?? '',
            'PHONE' => $this->data['phone'] ?? '',
            'POSTAL_CODE' => $this->data['postalCode'] ?? '',
            'HOUSING_TYPE' => $this->data['housingType'] ?? '',
            'SURFACE' => $this->data['surface'] ?? '',
            'RESIDENTS' => $this->data['residents'] ?? '',
            'ANNUAL_CONSUMPTION' => number_format($this->data['annualConsumption'] ?? 0, 0, ',', ' '),
            'MONTHLY_ESTIMATE' => number_format($this->data['monthlyEstimate'] ?? 0, 2, ',', ' '),
            'TARIF_CHOSEN' => $this->data['pricingType'] ?? '',
            'POWER_CHOSEN' => $this->data['contractPower'] ?? '',
            'DATE' => date('d/m/Y'),
            'REFERENCE' => 'SIM-' . date('Ymd') . '-' . rand(1000, 9999)
        ];
    }
    
    /**
     * Appel API Brevo
     */
    private function callBrevoAPI($params) {
        error_log('Brevo API: Début appel');
        error_log('Brevo API: URL = https://api.brevo.com/v3/smtp/email');
        error_log('Brevo API: API Key = ' . substr($this->brevoApiKey, 0, 20) . '...');
        
        $url = 'https://api.brevo.com/v3/smtp/email';
        
        // Si on est dans WordPress, utiliser wp_remote_post
        if (function_exists('wp_remote_post')) {
            error_log('Brevo API: Utilisation wp_remote_post');
            
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
            
            error_log('Brevo API: Status Code = ' . $statusCode);
            error_log('Brevo API: Response Body = ' . $body);
            
            if ($statusCode === 201) {
                $result = json_decode($body, true);
                error_log('Brevo API: SUCCÈS - Message ID = ' . ($result['messageId'] ?? 'N/A'));
                return true;
            } else {
                error_log('Brevo API: ÉCHEC - Code ' . $statusCode . ' - ' . $body);
                return false;
            }
                
            } else {
                // Fallback avec cURL si pas dans WordPress
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