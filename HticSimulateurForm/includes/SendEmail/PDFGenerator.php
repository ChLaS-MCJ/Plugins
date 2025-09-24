<?php
/**
 * PDFGenerator.php - Générateur de PDF pour GES Solutions
 * includes/SendEmail/PDFGenerator.php
 * 
 * Génère des PDF professionnels pour les simulations et devis
 * Types supportés : Résidentiel électricité, Professionnel électricité, 
 * Résidentiel gaz, Professionnel gaz
 */

class PDFGenerator {
    private $data;
    
    /**
     * Génère un PDF de simulation résidentielle électricité
     */
    public function generateResidentialPDF($data, $outputPath) {
        $this->data = $data;
        
        try {
            $this->loadFPDF();
            
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 10);
            
            $this->buildResidentialHeader($pdf);
            $this->buildResidentialMainSection($pdf);
            $this->buildResidentialDetailsSection($pdf);
            $this->buildResidentialEquipmentsSection($pdf);
            $this->buildResidentialFooter($pdf);
            
            $pdf->Output('F', $outputPath);
            return true;
            
        } catch (Exception $e) {
            return $this->generateSimpleResidentialPDF($outputPath);
        }
    }
    
    /**
     * Génère un PDF de devis professionnel électricité
     */
    public function generateBusinessPDF($data, $outputPath) {
        $this->data = $data;
        
        try {
            $this->loadFPDF();
            
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 20);
            
            $currentY = $this->buildBusinessHeader($pdf);
            $currentY = $this->buildBusinessMainSection($pdf, $currentY);
            $currentY = $this->buildBusinessDetailsSection($pdf, $currentY);
            $this->buildBusinessFooter($pdf);
            
            $pdf->Output('F', $outputPath);
            return true;
            
        } catch (Exception $e) {
            return $this->generateSimpleBusinessPDF($outputPath);
        }
    }

    /**
     * Génère un PDF de simulation gaz résidentielle
     */
    public function generateGazPDF($data, $outputPath) {
        $this->data = $data;
        
        try {
            $this->loadFPDF();
            
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 10);
            
            $this->buildGazHeader($pdf);
            $this->buildGazMainSection($pdf);
            $this->buildGazDetailsSection($pdf);
            $this->buildGazRepartitionSection($pdf);
            $this->buildGazFooter($pdf);
            
            $pdf->Output('F', $outputPath);
            return true;
            
        } catch (Exception $e) {
            return $this->generateSimpleGazPDF($outputPath);
        }
    }

    /**
     * Génère un PDF de devis gaz professionnel
     */
    public function generateGazProfessionnelPDF($data, $outputPath) {
        $this->data = $data;
        
        try {
            $this->loadFPDF();
            
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            
            $this->validateGazProfessionnelData();
            
            $currentY = $this->buildGazProfessionnelHeader($pdf);
            $currentY = $this->buildGazProfessionnelMainSection($pdf, $currentY);
            $currentY = $this->buildGazProfessionnelDetailsSection($pdf, $currentY);
            $this->buildGazProfessionnelFooter($pdf);
            
            $pdf->Output('F', $outputPath);
            return true;
            
        } catch (Exception $e) {
            return $this->generateSimpleGazProfessionnelPDF($outputPath);
        }
    }

    /*******************************************
     * UTILITAIRES GÉNÉRAUX
     *******************************************/

    private function loadFPDF() {
        $fpdf_file = __DIR__ . '/../libs/fpdf/fpdf.php';
        if (!file_exists($fpdf_file)) {
            throw new Exception('Fichier FPDF non trouvé: ' . $fpdf_file);
        }
        require_once $fpdf_file;
    }

    private function utf8_decode_text($text) {
        return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
    }

    /*******************************************
     * PDF RÉSIDENTIEL ÉLECTRICITÉ
     *******************************************/

    private function buildResidentialHeader($pdf) {
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, 0, 210, 50, 'F');
        
        $logo_path = plugin_dir_path(__FILE__) . '../../logoS.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 20, 12, 25);
        }
        
        $pdf->SetFont('Arial', 'B', 26);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(52, 15);
        $pdf->Cell(0, 10, 'GES SOLUTIONS', 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(52, 28);
        $pdf->Cell(0, 5, 'SIMULATION ELECTRICITE', 0, 1);
        
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

    private function buildResidentialMainSection($pdf) {
        $y = $pdf->GetY();
        
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $y, 190, 85, 'F');
        
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, $y, 190, 85, 'D');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(10, $y, 5, 85, 'F');
        
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
        
        $pdf->SetDrawColor(240, 240, 240);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25, $y + 38, 190, $y + 38);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->SetXY(25, $y + 44);
        $pdf->Cell(0, 5, 'ESTIMATION ANNUELLE TTC', 0, 1);
        
        $montantAnnuel = $this->data['annualEstimate'] ?? ($this->data['monthlyEstimate'] ?? 0) * 10;
        $estimation = number_format($montantAnnuel, 0, ',', ' ');
        
        $pdf->SetFont('Arial', 'B', 40);
        $pdf->SetTextColor(34, 47, 70);
        $pdf->SetXY(25, $y + 52);
        $pdf->Cell(80, 18, $estimation, 0, 0);
        
        $pdf->SetFont('Arial', '', 20);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetXY(25 + strlen($estimation) * 10, $y + 58);
        $pdf->Cell(30, 10, 'EUR', 0, 0);
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->SetXY(25, $y + 72);
        $montantMensuel = round($montantAnnuel / 10);
        $pdf->Cell(0, 5, 'par an - Soit ' . number_format($montantMensuel, 0, ',', ' ') . ' EUR/mois', 0, 1);
        
        $this->buildResidentialIndicators($pdf, 130, $y + 10);
        
        $pdf->SetY($y + 90);
    }

    private function buildResidentialIndicators($pdf, $x, $y) {
        $indicators = [
            ['label' => 'Consommation', 'value' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' '), 'unit' => 'kWh/an', 'color' => [130, 199, 32]],
            ['label' => 'Puissance', 'value' => $this->data['contractPower'] ?? '12', 'unit' => 'kVA', 'color' => [227, 148, 17]],
            ['label' => 'Surface', 'value' => $this->data['surface'] ?? '100', 'unit' => 'm2', 'color' => [155, 89, 182]],
            ['label' => 'Option', 'value' => strtoupper($this->data['pricingType'] ?? 'HC'), 'unit' => '', 'color' => [34, 47, 70]]
        ];
        
        foreach ($indicators as $i => $indicator) {
            $yPos = $y + ($i * 18);
            
            $pdf->SetFillColor(250, 250, 250);
            $pdf->Rect($x, $yPos, 65, 15, 'F');
            
            $pdf->SetFillColor($indicator['color'][0], $indicator['color'][1], $indicator['color'][2]);
            $pdf->Rect($x, $yPos, 3, 15, 'F');
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($x + 6, $yPos + 2);
            $pdf->Cell(55, 4, $indicator['label'], 0, 0);
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 6, $yPos + 7);
            $pdf->Cell(35, 6, $indicator['value'], 0, 0);
            
            if (!empty($indicator['unit'])) {
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(20, 6, $indicator['unit'], 0, 0);
            }
        }
    }

    private function buildResidentialDetailsSection($pdf) {
        $y = $pdf->GetY();
        
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(10, $y + 5);
        $pdf->Cell(0, 8, 'DETAILS DE LA SIMULATION', 0, 1);
        
        $pdf->SetDrawColor(34, 47, 70);
        $pdf->SetLineWidth(2);
        $pdf->Line(10, $pdf->GetY(), 70, $pdf->GetY());
        
        $y = $pdf->GetY() + 3;
        
        $this->buildDetailBox($pdf, 10, $y, 92, 'LOGEMENT', [
            'Type' => ucfirst($this->data['housingType'] ?? 'Maison'),
            'Surface' => ($this->data['surface'] ?? '100') . ' m2',
            'Occupants' => ($this->data['residents'] ?? '4') . ' personnes',
            'Isolation' => ucfirst($this->data['isolation'] ?? 'Standard'),
            'Chauffage' => ucfirst($this->data['heatingType'] ?? 'Electrique')
        ], [34, 47, 70]);
        
        $this->buildDetailBox($pdf, 108, $y, 92, 'CONSOMMATION', [
            'Annuelle' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . ' kWh',
            'Mensuelle' => number_format(($this->data['annualConsumption'] ?? 0) / 12, 0, ' ', ' ') . ' kWh',
            'Option' => strtoupper($this->data['pricingType'] ?? 'HC'),
            'Puissance' => ($this->data['contractPower'] ?? '12') . ' kVA',
            'Budget' => number_format($this->data['monthlyEstimate'] ?? 0, 2, ',', ' ') . ' EUR/mois'
        ], [130, 199, 32]);
        
        $pdf->SetY($y + 75);
    }

    private function buildDetailBox($pdf, $x, $y, $width, $title, $items, $color) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, 70, 'F');
        
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $width, 70, 'D');
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y, $width, 12, 'F');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($width, 6, $title, 0, 1, 'C');
        
        $yPos = $y + 17;
        foreach ($items as $label => $value) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($x + 5, $yPos);
            $pdf->Cell(30, 5, $label . ':', 0, 0);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->Cell($width - 35, 5, $value, 0, 1);
            
            $yPos += 10;
        }
    }

    private function buildResidentialEquipmentsSection($pdf) {
        $appliances_labels = [
            'lave_linge' => ['name' => 'Lave-linge', 'color' => [34, 47, 70]],
            'seche_linge' => ['name' => 'Seche-linge', 'color' => [227, 148, 17]],
            'refrigerateur' => ['name' => 'Refrigerateur', 'color' => [130, 199, 32]],
            'lave_vaisselle' => ['name' => 'Lave-vaisselle', 'color' => [34, 47, 70]],
            'four' => ['name' => 'Four', 'color' => [227, 148, 17]],
            'congelateur' => ['name' => 'Congelateur', 'color' => [130, 199, 32]]
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
            $y = $pdf->GetY() + 5;
            
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY(10, $y);
            $pdf->Cell(0, 8, 'EQUIPEMENTS DECLARES', 0, 1);
            
            $pdf->SetDrawColor(34, 47, 70);
            $pdf->SetLineWidth(2);
            $pdf->Line(10, $pdf->GetY(), 65, $pdf->GetY());
            
            $y = $pdf->GetY() + 3;
            $x = 10;
            
            foreach ($appliances as $appliance) {
                $width = $pdf->GetStringWidth($appliance['name']) + 16;
                
                if ($x + $width > 195) {
                    $x = 10;
                    $y += 13;
                }
                
                $pdf->SetFillColor($appliance['color'][0], $appliance['color'][1], $appliance['color'][2]);
                $pdf->Rect($x, $y, $width, 10, 'F');
                
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetXY($x, $y + 3);
                $pdf->Cell($width, 4, $appliance['name'], 0, 0, 'C');
                
                $x += $width + 5;
            }
            
            $pdf->SetY($y + 18);
        }
    }

    private function buildResidentialFooter($pdf) {
        $y = 265;
        
        $pdf->SetFillColor(245, 247, 250);
        $pdf->Rect(0, $y, 210, 35, 'F');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, $y, 210, 1, 'F');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(0, $y + 6);
        $pdf->Cell(210, 6, 'UN CONSEILLER VOUS CONTACTERA SOUS 24H', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetY($y + 16);
        
        $pdf->Cell(70, 5, 'contact@ges-solutions.fr', 0, 0, 'C');
        $pdf->Cell(70, 5, '01 23 45 67 89', 0, 0, 'C');
        $pdf->Cell(70, 5, 'www.ges-solutions.fr', 0, 0, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY(0, $y + 26);
        $pdf->Cell(210, 4, 'Copyright ' . date('Y') . ' GES Solutions - Simulation indicative valable 30 jours - Document non contractuel', 0, 0, 'C');
    }

    private function generateSimpleResidentialPDF($outputPath) {
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(0, 15, 'SIMULATION ELECTRICITE - GES SOLUTIONS', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        
        $montantAnnuel = $this->data['annualEstimate'] ?? (($this->data['monthlyEstimate'] ?? 0) * 10);
        $montantMensuel = round($montantAnnuel / 10);
        
        $content = "Client: " . ($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? '') . "\n";
        $content .= "Email: " . ($this->data['email'] ?? '') . "\n";
        $content .= "Telephone: " . ($this->data['phone'] ?? '') . "\n";
        $content .= "Code postal: " . ($this->data['postalCode'] ?? '') . "\n\n";
        $content .= "RESULTATS DE LA SIMULATION\n";
        $content .= "================================\n";
        $content .= "Estimation annuelle: " . number_format($montantAnnuel, 2, ',', ' ') . " EUR/an\n";
        $content .= "Estimation mensuelle: " . number_format($montantMensuel, 2, ',', ' ') . " EUR/mois (sur 10 mois)\n";
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
        
        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 10, 'Document genere le ' . date('d/m/Y') . ' - GES Solutions', 0, 1, 'C');
        
        $pdf->Output('F', $outputPath);
        return true;
    }

    /*******************************************
     * PDF PROFESSIONNEL ÉLECTRICITÉ
     *******************************************/

    private function buildBusinessHeader($pdf) {
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, 0, 210, 55, 'F');
        
        $logo_path = plugin_dir_path(__FILE__) . '../../logoS.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 20, 12, 25);
        }
        
        $pdf->SetFont('Arial', 'B', 26);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(52, 15);
        $pdf->Cell(0, 10, 'GES SOLUTIONS', 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(52, 28);
        $pdf->Cell(0, 5, $this->utf8_decode_text('DEVIS ÉLECTRICITÉ PROFESSIONNEL'), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(140, 18);
        $pdf->Cell(50, 5, $this->utf8_decode_text('RÉF: PRO' . date('Ymd') . substr(md5($this->data['email']), 0, 4)), 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(140, 25);
        $pdf->Cell(50, 5, date('d/m/Y H:i'), 0, 1, 'R');
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(140, 35);
        $pdf->Cell(50, 5, $this->utf8_decode_text('CLIENT PROFESSIONNEL'), 0, 1, 'R');
        
        return 60;
    }

    private function buildBusinessMainSection($pdf, $startY) {
        $y = $startY;
        
        if ($y > 180) {
            $pdf->AddPage();
            $y = 20;
        }
        
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $y, 190, 95, 'F');
        
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, $y, 190, 95, 'D');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(10, $y, 5, 95, 'F');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(25, $y + 10);
        $pdf->Cell(0, 5, $this->utf8_decode_text('ENTREPRISE'), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(25);
        $companyName = $this->utf8_decode_text(strtoupper($this->data['companyName'] ?? 'ENTREPRISE'));
        $pdf->Cell(0, 8, $companyName, 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(25);
        $siretText = $this->utf8_decode_text(($this->data['legalForm'] ?? '') . ' - SIRET: ' . ($this->data['siret'] ?? ''));
        $pdf->Cell(0, 5, $siretText, 0, 1);
        
        $pdf->SetX(25);
        $contactName = $this->utf8_decode_text(($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? ''));
        $pdf->Cell(0, 5, $contactName, 0, 1);
        $pdf->SetX(25);
        $pdf->Cell(0, 5, $this->data['email'] ?? '', 0, 1);
        
        if (!empty($this->data['phone'])) {
            $pdf->SetX(25);
            $pdf->Cell(0, 5, $this->data['phone'], 0, 1);
        }
        
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25, $y + 45, 190, $y + 45);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(25, $y + 50);
        $pdf->Cell(0, 5, $this->utf8_decode_text('ESTIMATION ANNUELLE HTVA'), 0, 1);
        
        $estimation = number_format($this->data['annualEstimate'] ?? 0, 0, ' ', ' ');
        $pdf->SetFont('Arial', 'B', 32);
        $pdf->SetTextColor(34, 47, 70);
        $pdf->SetXY(25, $y + 58);
        $pdf->Cell(80, 15, $estimation, 0, 0);
        
        $pdf->SetFont('Arial', '', 16);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY(25 + strlen($estimation) * 8, $y + 65);
        $pdf->Cell(20, 8, $this->utf8_decode_text('EUR HTVA'), 0, 0);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY(25, $y + 80);
        $tvaNote = $this->utf8_decode_text('+ TVA 20% = ' . number_format(($this->data['annualEstimate'] ?? 0) * 1.2, 0, ' ', ' ') . ' EUR TTC');
        $pdf->Cell(0, 5, $tvaNote, 0, 1);
        
        $this->buildBusinessIndicators($pdf, 130, $y + 10);
        
        return $y + 100;
    }

    private function buildBusinessIndicators($pdf, $x, $y) {
        $indicators = [
            [
                'label' => $this->utf8_decode_text('Consommation'), 
                'value' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' '), 
                'unit' => 'kWh/an', 
                'color' => [130, 199, 32]
            ],
            [
                'label' => 'Puissance', 
                'value' => $this->data['contractPower'] ?? '36', 
                'unit' => 'kVA', 
                'color' => [227, 148, 17]
            ],
            [
                'label' => $this->utf8_decode_text('Catégorie'), 
                'value' => $this->utf8_decode_text($this->data['category'] ?? 'BT'), 
                'unit' => '', 
                'color' => [34, 47, 70]
            ],
            [
                'label' => 'Type contrat', 
                'value' => $this->utf8_decode_text($this->data['contractType'] === 'principal' ? 'Principal' : 'Secondaire'), 
                'unit' => '', 
                'color' => [227, 148, 17]
            ]
        ];
        
        foreach ($indicators as $i => $indicator) {
            $yPos = $y + ($i * 18);
            
            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect($x, $yPos, 65, 15, 'F');
            
            $pdf->SetFillColor($indicator['color'][0], $indicator['color'][1], $indicator['color'][2]);
            $pdf->Rect($x, $yPos, 3, 15, 'F');
            
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($x + 6, $yPos + 2);
            $pdf->Cell(55, 4, $indicator['label'], 0, 0);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 6, $yPos + 7);
            $pdf->Cell(35, 5, $indicator['value'], 0, 0);
            
            if (!empty($indicator['unit'])) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(20, 5, $indicator['unit'], 0, 0);
            }
        }
    }

    private function buildBusinessDetailsSection($pdf, $startY) {
        $y = $startY + 5;
        
        if ($y > 190) {
            $pdf->AddPage();
            $y = 20;
        }
        
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(10, $y);
        $pdf->Cell(0, 8, $this->utf8_decode_text('DÉTAILS DU DEVIS PROFESSIONNEL'), 0, 1);
        
        $pdf->SetDrawColor(34, 47, 70);
        $pdf->SetLineWidth(2);
        $pdf->Line(10, $pdf->GetY(), 75, $pdf->GetY());
        
        $y = $pdf->GetY() + 5;
        
        $this->buildBusinessDetailBox($pdf, 10, $y, 92, $this->utf8_decode_text('CONFIGURATION TECHNIQUE'), [
            $this->utf8_decode_text('Catégorie') => $this->utf8_decode_text($this->data['category'] ?? '--'),
            $this->utf8_decode_text('Puissance souscrite') => $this->utf8_decode_text(($this->data['contractPower'] ?? '36') . ' kVA'),
            $this->utf8_decode_text('Formule tarifaire') => $this->utf8_decode_text($this->data['tarifFormula'] ?? 'Base'),
            $this->utf8_decode_text('Éligible TRV') => $this->utf8_decode_text($this->data['eligibleTRV'] ? 'Oui' : 'Non'),
            $this->utf8_decode_text('Consommation prévisionnelle') => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . ' kWh/an'
        ], [34, 47, 70]);
        
        $selectedOffer = $this->data['selectedOffer'] ?? null;
        $this->buildBusinessDetailBox($pdf, 108, $y, 92, $this->utf8_decode_text('OFFRE SÉLECTIONNÉE'), [
            $this->utf8_decode_text('Nom de l\'offre') => $this->utf8_decode_text($selectedOffer['name'] ?? '--'),
            $this->utf8_decode_text('Coût annuel HTVA') => number_format($selectedOffer['totalHTVA'] ?? 0, 0, ' ', ' ') . ' EUR',
            $this->utf8_decode_text('Coût annuel TTC') => number_format($selectedOffer['totalTTC'] ?? 0, 0, ' ', ' ') . ' EUR',
            $this->utf8_decode_text('Type de contrat') => $this->utf8_decode_text($this->data['contractType'] === 'principal' ? 'Contrat principal' : 'Site secondaire'),
            $this->utf8_decode_text('Mise en service') => $this->utf8_decode_text('Sous 15 jours ouvrés')
        ], [130, 199, 32]);
        
        return $y + 80;
    }

    private function buildBusinessDetailBox($pdf, $x, $y, $width, $title, $items, $color) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, 70, 'F');
        
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $width, 70, 'D');
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y, $width, 12, 'F');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($width, 6, $title, 0, 1, 'C');
        
        $yPos = $y + 16;
        foreach ($items as $label => $value) {
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($x + 5, $yPos);
            $pdf->Cell(35, 4, $label . ' :', 0, 0);
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 5, $yPos + 5);
            
            $maxWidth = $width - 10;
            if ($pdf->GetStringWidth($value) > $maxWidth) {
                $value = substr($value, 0, 25) . '...';
            }
            
            $pdf->Cell($maxWidth, 4, $value, 0, 1);
            
            $yPos += 10;
        }
    }

    private function buildBusinessFooter($pdf) {
        $pdf->SetY(250);
        $y = 250;
        
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, $y, 210, 47, 'F');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, $y, 210, 2, 'F');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(34, 47, 70);
        $pdf->SetXY(0, $y + 8);
        $pdf->Cell(210, 8, $this->utf8_decode_text('VOTRE CONSEILLER PROFESSIONNEL VOUS CONTACTERA SOUS 48H'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetY($y + 20);
        
        $pdf->Cell(70, 6, 'commercial@ges-solutions.fr', 0, 0, 'C');
        $pdf->Cell(70, 6, '01 23 45 67 89', 0, 0, 'C');
        $pdf->Cell(70, 6, 'www.ges-solutions.fr', 0, 0, 'C');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetY($y + 30);
        $advantages = $this->utf8_decode_text('Accompagnement dédié • Facturation adaptée • Service client professionnel');
        $pdf->Cell(210, 4, $advantages, 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetY($y + 38);
        $copyright = $this->utf8_decode_text('Copyright ' . date('Y') . ' GES Solutions - Devis professionnel valable 30 jours - Tarifs HTVA');
        $pdf->Cell(210, 4, $copyright, 0, 0, 'C');
    }

    private function generateSimpleBusinessPDF($outputPath) {
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(0, 15, 'DEVIS ELECTRICITE PROFESSIONNEL - GES SOLUTIONS', 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        
        $content = "ENTREPRISE: " . ($this->data['companyName'] ?? '') . "\n";
        $content .= "Contact: " . ($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? '') . "\n";
        $content .= "Email: " . ($this->data['email'] ?? '') . "\n";
        $content .= "Telephone: " . ($this->data['phone'] ?? '') . "\n";
        $content .= "SIRET: " . ($this->data['siret'] ?? '') . "\n\n";
        $content .= "RESULTATS DU DEVIS\n";
        $content .= "================================\n";
        $content .= "Estimation annuelle HTVA: " . number_format($this->data['annualEstimate'] ?? 0, 2, ',', ' ') . " EUR\n";
        $content .= "Consommation annuelle: " . number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . " kWh/an\n";
        $content .= "Puissance souscrite: " . ($this->data['contractPower'] ?? '36') . " kVA\n";
        $content .= "Categorie: " . ($this->data['category'] ?? 'BT') . "\n";
        $content .= "Type de contrat: " . ($this->data['contractType'] === 'principal' ? 'Principal' : 'Site secondaire') . "\n\n";
        
        $pdf->MultiCell(0, 6, $content);
        
        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 10, 'Devis genere le ' . date('d/m/Y') . ' - GES Solutions Pro', 0, 1, 'C');
        
        $pdf->Output('F', $outputPath);
        return true;
    }

    /*******************************************
     * PDF GAZ RÉSIDENTIEL
     *******************************************/

    private function buildGazHeader($pdf) {
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, 0, 210, 50, 'F');
        
        $logo_path = plugin_dir_path(__FILE__) . '../../logoS.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 20, 12, 25);
        }
        
        $pdf->SetFont('Arial', 'B', 26);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(52, 15);
        $pdf->Cell(0, 10, 'GES SOLUTIONS', 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(52, 28);
        $pdf->Cell(0, 5, 'SIMULATION GAZ', 0, 1);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(140, 18);
        $pdf->Cell(50, 5, 'REF: GAZ' . date('Ymd') . substr(md5($this->data['email']), 0, 4), 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(140, 25);
        $pdf->Cell(50, 5, date('d/m/Y H:i'), 0, 1, 'R');
        
        $typeGaz = $this->determineTypeGaz();
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(140, 32);
        $pdf->Cell(50, 5, strtoupper($typeGaz), 0, 1, 'R');
        
        $pdf->SetY(55);
    }

    private function buildGazMainSection($pdf) {
        $y = $pdf->GetY();
        
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $y, 190, 85, 'F');
        
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, $y, 190, 85, 'D');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(10, $y, 5, 85, 'F');
        
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
        
        $pdf->SetDrawColor(240, 240, 240);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25, $y + 38, 190, $y + 38);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->SetXY(25, $y + 44);
        $pdf->Cell(0, 5, $this->utf8_decode_text('ESTIMATION ANNUELLE GAZ TTC'), 0, 1);
        
        $estimation = number_format($this->data['annualCost'] ?? 0, 0, ',', ' ');
        $pdf->SetFont('Arial', 'B', 40);
        $pdf->SetTextColor(255, 111, 0);
        $pdf->SetXY(25, $y + 52);
        $pdf->Cell(80, 18, $estimation, 0, 0);
        
        $pdf->SetFont('Arial', '', 20);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetXY(25 + strlen($estimation) * 10, $y + 58);
        $pdf->Cell(30, 10, 'EUR', 0, 0);
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->SetXY(25, $y + 72);
        $mensualite = number_format(($this->data['annualCost'] ?? 0) / 10, 0, ',', ' ');
        $pdf->Cell(0, 5, $this->utf8_decode_text('Soit ' . $mensualite . ' EUR/mois (sur 10 mois)'), 0, 1);
        
        $this->buildGazIndicators($pdf, 130, $y + 10);
        
        $pdf->SetY($y + 90);
    }

    private function buildGazIndicators($pdf, $x, $y) {
        $indicators = [
            [
                'label' => 'Consommation', 
                'value' => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' '), 
                'unit' => 'kWh/an', 
                'color' => [255, 111, 0]
            ],
            [
                'label' => 'Commune', 
                'value' => $this->utf8_decode_text(substr($this->data['commune'] ?? 'Non définie', 0, 15)), 
                'unit' => '', 
                'color' => [130, 199, 32]
            ],
            [
                'label' => 'Surface', 
                'value' => $this->data['surface'] ?? '100', 
                'unit' => $this->utf8_decode_text('m²'), 
                'color' => [155, 89, 182]
            ],
            [
                'label' => 'Type gaz', 
                'value' => ucfirst($this->determineTypeGaz()), 
                'unit' => '', 
                'color' => [34, 47, 70]
            ]
        ];
        
        foreach ($indicators as $i => $indicator) {
            $yPos = $y + ($i * 18);
            
            $pdf->SetFillColor(250, 250, 250);
            $pdf->Rect($x, $yPos, 65, 15, 'F');
            
            $pdf->SetFillColor($indicator['color'][0], $indicator['color'][1], $indicator['color'][2]);
            $pdf->Rect($x, $yPos, 3, 15, 'F');
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($x + 6, $yPos + 2);
            $pdf->Cell(55, 4, $this->utf8_decode_text($indicator['label']), 0, 0);
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 6, $yPos + 7);
            $pdf->Cell(35, 6, $indicator['value'], 0, 0);
            
            if (!empty($indicator['unit'])) {
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(20, 6, $indicator['unit'], 0, 0);
            }
        }
    }

    private function buildGazDetailsSection($pdf) {
        $y = $pdf->GetY();
        
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(10, $y + 5);
        $pdf->Cell(0, 8, $this->utf8_decode_text('DÉTAILS DE LA SIMULATION GAZ'), 0, 1);
        
        $pdf->SetDrawColor(34, 47, 70);
        $pdf->SetLineWidth(2);
        $pdf->Line(10, $pdf->GetY(), 70, $pdf->GetY());
        
        $y = $pdf->GetY() + 3;
        
        $this->buildGazDetailBox($pdf, 10, $y, 92, 'LOGEMENT', [
            'Type' => ucfirst($this->data['housingType'] ?? 'Maison'),
            'Surface' => ($this->data['surface'] ?? '100') . ' m²',
            'Occupants' => ($this->data['residents'] ?? '4') . ' personnes',
            'Commune' => $this->utf8_decode_text(substr($this->data['commune'] ?? '--', 0, 20)),
            'Isolation' => ucfirst($this->data['isolation'] ?? 'Moyenne')
        ], [34, 47, 70]);
        
        $this->buildGazDetailBox($pdf, 108, $y, 92, 'USAGES GAZ', [
            'Chauffage' => ($this->data['chauffageGaz'] === 'oui' ? 'Oui' : 'Non'),
            'Eau chaude' => ($this->data['eauChaude'] === 'gaz' ? 'Oui' : 'Non'),
            'Cuisson' => ($this->data['cuisson'] === 'gaz' ? 'Oui' : 'Non'),
            'Offre' => ucfirst($this->data['offre'] ?? 'Base'),
            'Type installation' => ucfirst($this->determineTypeGaz())
        ], [130, 199, 32]);
        
        $pdf->SetY($y + 75);
    }

    private function buildGazRepartitionSection($pdf) {
        $y = $pdf->GetY() + 5;
        
        $repartition = $this->data['repartition'] ?? [];
        $chauffage = intval($repartition['chauffage'] ?? 0);
        $eauChaude = intval($repartition['eau_chaude'] ?? 0);
        $cuisson = intval($repartition['cuisson'] ?? 0);
        $total = $chauffage + $eauChaude + $cuisson;
        
        if ($total > 0) {
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY(10, $y);
            $pdf->Cell(0, 8, $this->utf8_decode_text('RÉPARTITION DE LA CONSOMMATION'), 0, 1);
            
            $pdf->SetDrawColor(255, 111, 0);
            $pdf->SetLineWidth(2);
            $pdf->Line(10, $pdf->GetY(), 85, $pdf->GetY());
            
            $y = $pdf->GetY() + 5;
            
            if ($chauffage > 0) {
                $this->drawGazBar($pdf, 15, $y, 'Chauffage', $chauffage, $total, [255, 87, 34]);
                $y += 20;
            }
            
            if ($eauChaude > 0) {
                $this->drawGazBar($pdf, 15, $y, 'Eau chaude', $eauChaude, $total, [255, 152, 0]);
                $y += 20;
            }
            
            if ($cuisson > 0) {
                $this->drawGazBar($pdf, 15, $y, 'Cuisson', $cuisson, $total, [255, 193, 7]);
                $y += 20;
            }
            
            $pdf->SetY($y + 5);
        }
    }

    private function drawGazBar($pdf, $x, $y, $label, $value, $total, $color) {
        $percentage = round(($value / $total) * 100);
        $barWidth = 150;
        $fillWidth = ($percentage / 100) * $barWidth;
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY($x, $y);
        $pdf->Cell(40, 5, $this->utf8_decode_text($label), 0, 0);
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Rect($x, $y + 6, $barWidth, 8, 'F');
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y + 6, $fillWidth, 8, 'F');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY($x + $barWidth + 5, $y + 7);
        $pdf->Cell(30, 5, number_format($value, 0, ',', ' ') . ' kWh (' . $percentage . '%)', 0, 0);
    }

    private function buildGazDetailBox($pdf, $x, $y, $width, $title, $items, $color) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, 70, 'F');
        
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $width, 70, 'D');
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y, $width, 12, 'F');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($width, 6, $this->utf8_decode_text($title), 0, 1, 'C');
        
        $yPos = $y + 17;
        foreach ($items as $label => $value) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($x + 5, $yPos);
            $pdf->Cell(30, 5, $this->utf8_decode_text($label . ':'), 0, 0);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->Cell($width - 35, 5, $this->utf8_decode_text($value), 0, 1);
            
            $yPos += 10;
        }
    }

    private function buildGazFooter($pdf) {
        $y = 265;
        
        $pdf->SetFillColor(253, 253, 253);
        $pdf->Rect(0, $y, 210, 35, 'F');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, $y, 210, 1, 'F');
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(0, $y + 6);
        $pdf->Cell(210, 6, $this->utf8_decode_text('UN CONSEILLER GAZ VOUS CONTACTERA SOUS 24H'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetY($y + 16);
        
        $pdf->Cell(70, 5, 'contact@ges-solutions.fr', 0, 0, 'C');
        $pdf->Cell(70, 5, '01 23 45 67 89', 0, 0, 'C');
        $pdf->Cell(70, 5, 'www.ges-solutions.fr', 0, 0, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY(0, $y + 26);
        $pdf->Cell(210, 4, $this->utf8_decode_text('Copyright ' . date('Y') . ' GES Solutions - Simulation gaz indicative valable 30 jours'), 0, 0, 'C');
    }

    private function generateSimpleGazPDF($outputPath) {
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(255, 111, 0);
        $pdf->Cell(0, 15, 'SIMULATION GAZ - GES SOLUTIONS', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        
        $content = "Client: " . ($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? '') . "\n";
        $content .= "Email: " . ($this->data['email'] ?? '') . "\n";
        $content .= "Telephone: " . ($this->data['phone'] ?? '') . "\n";
        $content .= "Commune: " . ($this->data['commune'] ?? '') . "\n\n";
        $content .= "RESULTATS DE LA SIMULATION GAZ\n";
        $content .= "================================\n";
        $content .= "Consommation annuelle: " . number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . " kWh/an\n";
        $content .= "Cout annuel TTC: " . number_format($this->data['annualCost'] ?? 0, 2, ',', ' ') . " EUR\n";
        $content .= "Cout mensuel (sur 10 mois): " . number_format(($this->data['monthlyCost'] ?? 0), 2, ',', ' ') . " EUR\n";
        $content .= "Type de gaz: " . $this->determineTypeGaz() . "\n\n";
        $content .= "DETAILS DU LOGEMENT\n";
        $content .= "================================\n";
        $content .= "Type: " . ucfirst($this->data['housingType'] ?? 'Maison') . "\n";
        $content .= "Surface: " . ($this->data['surface'] ?? '100') . " m2\n";
        $content .= "Nombre d'occupants: " . ($this->data['residents'] ?? '4') . " personnes\n";
        $content .= "Chauffage gaz: " . ($this->data['chauffageGaz'] === 'oui' ? 'Oui' : 'Non') . "\n";
        $content .= "Eau chaude gaz: " . ($this->data['eauChaude'] === 'gaz' ? 'Oui' : 'Non') . "\n";
        $content .= "Cuisson gaz: " . ($this->data['cuisson'] === 'gaz' ? 'Oui' : 'Non') . "\n";
        
        $pdf->MultiCell(0, 6, $this->utf8_decode_text($content));
        
        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 10, $this->utf8_decode_text('Document généré le ' . date('d/m/Y') . ' - GES Solutions'), 0, 1, 'C');
        
        $pdf->Output('F', $outputPath);
        return true;
    }

    /*******************************************
     * PDF GAZ PROFESSIONNEL
     *******************************************/

    private function validateGazProfessionnelData() {
        $defaults = [
            'companyName' => 'Entreprise',
            'firstName' => 'Responsable',
            'lastName' => '',
            'email' => 'contact@entreprise.fr',
            'phone' => '',
            'siret' => '',
            'legalForm' => '',
            'commune' => '',
            'annualConsumption' => 0,
            'gasType' => 'Gaz naturel',
            'contractType' => 'principal',
            'isHighConsumption' => false,
            'annualCost' => 0,
            'monthlyCost' => 0,
            'selectedTariff' => 'Standard professionnel',
            'acceptConditions' => false,
            'acceptPrelevement' => false,
            'certifiePouvoir' => false
        ];
        
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($this->data[$key])) {
                $this->data[$key] = $defaultValue;
            }
        }
        
        $this->data['annualConsumption'] = intval($this->data['annualConsumption']);
        $this->data['annualCost'] = floatval($this->data['annualCost']);
        $this->data['monthlyCost'] = floatval($this->data['monthlyCost']);
        
        $this->data['isHighConsumption'] = ($this->data['annualConsumption'] > 35000) || ($this->data['isHighConsumption'] === true);
    }

    private function buildGazProfessionnelHeader($pdf) {
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, 0, 210, 55, 'F');
        
        $logo_path = plugin_dir_path(__FILE__) . '../../logoS.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 20, 12, 25);
        }
        
        $pdf->SetFont('Arial', 'B', 26);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(52, 15);
        $pdf->Cell(0, 10, 'GES SOLUTIONS', 0, 1);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(52, 28);
        $pdf->Cell(0, 5, $this->utf8_decode_text('DEVIS GAZ PROFESSIONNEL'), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(140, 18);
        $pdf->Cell(50, 5, $this->utf8_decode_text('RÉF: GAZ-PRO' . date('Ymd') . substr(md5($this->data['email']), 0, 4)), 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(140, 25);
        $pdf->Cell(50, 5, date('d/m/Y H:i'), 0, 1, 'R');
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(140, 35);
        $pdf->Cell(50, 5, $this->utf8_decode_text('CLIENT PROFESSIONNEL GAZ'), 0, 1, 'R');
        
        return 60;
    }

    private function buildGazProfessionnelMainSection($pdf, $startY) {
        $y = $startY;
        
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $y, 190, 100, 'F');
        
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.8);
        $pdf->Rect(10, $y, 190, 100, 'D');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(10, $y, 5, 100, 'F');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(25, $y + 10);
        $pdf->Cell(0, 5, $this->utf8_decode_text('ENTREPRISE'), 0, 1);
        
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(25);
        $companyName = $this->utf8_decode_text(strtoupper($this->data['companyName'] ?? 'ENTREPRISE'));
        $pdf->Cell(0, 8, $companyName, 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(25);
        $siretText = $this->utf8_decode_text(($this->data['legalForm'] ?? '') . ' - SIRET: ' . ($this->data['siret'] ?? ''));
        $pdf->Cell(0, 5, $siretText, 0, 1);
        
        $pdf->SetX(25);
        $contactName = $this->utf8_decode_text(($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? ''));
        $pdf->Cell(0, 5, $contactName, 0, 1);
        $pdf->SetX(25);
        $pdf->Cell(0, 5, $this->data['email'] ?? '', 0, 1);
        
        if (!empty($this->data['phone'])) {
            $pdf->SetX(25);
            $pdf->Cell(0, 5, $this->data['phone'], 0, 1);
        }
        
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25, $y + 50, 190, $y + 50);
        
        if ($this->data['isHighConsumption']) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY(25, $y + 55);
            $pdf->Cell(0, 5, $this->utf8_decode_text('DEMANDE DE DEVIS PERSONNALISÉ'), 0, 1);
            
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->SetTextColor(34, 47, 70);
            $pdf->SetXY(25, $y + 63);
            $pdf->Cell(80, 12, $this->utf8_decode_text('SUR MESURE'), 0, 0);
            
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->SetXY(25, $y + 80);
            $consumption = number_format($this->data['annualConsumption'], 0, ' ', ' ');
            $pdf->Cell(0, 5, $this->utf8_decode_text("$consumption kWh/an - Grosse consommation"), 0, 1);
            
            $pdf->SetXY(25, $y + 90);
            $pdf->Cell(0, 5, $this->utf8_decode_text('Un expert vous contactera sous 48h'), 0, 1);
        } else {
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY(25, $y + 55);
            $pdf->Cell(0, 5, $this->utf8_decode_text('ESTIMATION ANNUELLE HTVA'), 0, 1);
            
            $estimation = number_format($this->data['annualCost'] ?? 0, 0, ' ', ' ');
            $pdf->SetFont('Arial', 'B', 32);
            $pdf->SetTextColor(34, 47, 70);
            $pdf->SetXY(25, $y + 63);
            $pdf->Cell(80, 15, $estimation, 0, 0);
            
            $pdf->SetFont('Arial', '', 16);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->SetXY(25 + strlen($estimation) * 8, $y + 70);
            $pdf->Cell(20, 8, $this->utf8_decode_text('EUR HTVA'), 0, 0);
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->SetXY(25, $y + 85);
            $pdf->Cell(0, 5, $this->utf8_decode_text('Hors TVA (+ 20% en sus)'), 0, 1);
            
            $pdf->SetXY(25, $y + 93);
            $monthly = number_format(($this->data['monthlyCost'] ?? 0), 0, ' ', ' ');
            $pdf->Cell(0, 5, $this->utf8_decode_text("Soit environ $monthly EUR/mois HTVA (sur 10 mois)"), 0, 1);
        }
        
        $this->buildGazProfessionnelIndicators($pdf, 130, $y + 10);
        
        return $y + 105;
    }

    private function buildGazProfessionnelIndicators($pdf, $x, $y) {
        $gasTypeLabel = $this->utf8_decode_text($this->data['gasType'] ?? 'Gaz naturel');
        $consumption = number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ');
        $commune = $this->utf8_decode_text(substr($this->data['commune'] ?? '--', 0, 15));
        $contractType = $this->data['contractType'] === 'principal' ? 'Principal' : 'Secondaire';
        
        $indicators = [
            [
                'label' => $this->utf8_decode_text('Consommation'), 
                'value' => $consumption, 
                'unit' => 'kWh/an', 
                'color' => [130, 199, 32]
            ],
            [
                'label' => 'Type gaz', 
                'value' => $gasTypeLabel, 
                'unit' => '', 
                'color' => [34, 47, 70]
            ],
            [
                'label' => 'Commune', 
                'value' => $commune, 
                'unit' => '', 
                'color' => [155, 89, 182]
            ],
            [
                'label' => 'Contrat', 
                'value' => $this->utf8_decode_text($contractType), 
                'unit' => '', 
                'color' => [227, 148, 17]
            ]
        ];
        
        foreach ($indicators as $i => $indicator) {
            $yPos = $y + ($i * 20);
            
            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect($x, $yPos, 65, 18, 'F');
            
            $pdf->SetFillColor($indicator['color'][0], $indicator['color'][1], $indicator['color'][2]);
            $pdf->Rect($x, $yPos, 3, 18, 'F');
            
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($x + 6, $yPos + 3);
            $pdf->Cell(55, 4, $indicator['label'], 0, 0);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 6, $yPos + 8);
            $pdf->Cell(35, 5, $indicator['value'], 0, 0);
            
            if (!empty($indicator['unit'])) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->Cell(20, 5, $indicator['unit'], 0, 0);
            }
        }
    }

    private function buildGazProfessionnelDetailsSection($pdf, $startY) {
        $y = $startY + 5;
        
        if ($y > 177) {
            $pdf->AddPage();
            $y = 20;
        }
        
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY(10, $y);
        $title = ($this->data['isHighConsumption'] ?? false)
            ? $this->utf8_decode_text('DEMANDE DE DEVIS GAZ PROFESSIONNEL')
            : $this->utf8_decode_text('DÉTAILS DU DEVIS GAZ PROFESSIONNEL');
        $pdf->Cell(0, 8, $title, 0, 1);
        
        $pdf->SetDrawColor(34, 47, 70);
        $pdf->SetLineWidth(2);
        $pdf->Line(10, $pdf->GetY(), 85, $pdf->GetY());
        
        $y = $pdf->GetY() + 5;
        
        $this->buildGazProfessionnelDetailBox($pdf, 10, $y, 92, $this->utf8_decode_text('CONFIGURATION GAZ'), [
            $this->utf8_decode_text('Type de gaz') => $this->utf8_decode_text($this->data['gasType'] ?? '--'),
            $this->utf8_decode_text('Commune') => $this->utf8_decode_text($this->data['commune'] ?? '--'),
            $this->utf8_decode_text('Consommation prévisionnelle') => number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . ' kWh/an',
            $this->utf8_decode_text('Type de contrat') => $this->utf8_decode_text(($this->data['contractType'] ?? '') === 'principal' ? 'Contrat principal' : 'Site secondaire'),
            $this->utf8_decode_text('Usages prévus') => $this->utf8_decode_text($this->buildUsagesTextGazPro())
        ], [34, 47, 70]);
        
        if ($this->data['isHighConsumption'] ?? false) {
            $this->buildGazProfessionnelDetailBox($pdf, 108, $y, 92, $this->utf8_decode_text('PROCESSUS DE CONTACT'), [
                $this->utf8_decode_text('Délai de contact') => $this->utf8_decode_text('Sous 48h ouvrées'),
                $this->utf8_decode_text('Expert dédié') => $this->utf8_decode_text('Conseiller gaz professionnel'),
                $this->utf8_decode_text('Analyse personnalisée') => $this->utf8_decode_text('Étude complète des besoins'),
                $this->utf8_decode_text('Tarification') => $this->utf8_decode_text('Négociation au meilleur prix'),
                $this->utf8_decode_text('Accompagnement') => $this->utf8_decode_text('Suivi dédié')
            ], [130, 199, 32]);
        } else {
            $this->buildGazProfessionnelDetailBox($pdf, 108, $y, 92, $this->utf8_decode_text('DÉTAILS TARIFAIRES'), [
                $this->utf8_decode_text('Tarif sélectionné') => $this->utf8_decode_text($this->data['selectedTariff'] ?? 'Standard professionnel'),
                $this->utf8_decode_text('Coût annuel HTVA') => number_format($this->data['annualCost'] ?? 0, 0, ' ', ' ') . ' EUR',
                $this->utf8_decode_text('Moyenne mensuelle') => number_format($this->data['monthlyCost'] ?? 0, 0, ' ', ' ') . ' EUR/mois HTVA',
                $this->utf8_decode_text('Hors TVA') => $this->utf8_decode_text('+ 20% TVA en sus'),
                $this->utf8_decode_text('Mise en service') => $this->utf8_decode_text('Sous 15 jours ouvrés')
            ], [130, 199, 32]);
        }
        
        return $y + 85;
    }

    private function buildUsagesTextGazPro() {
        $usages = [];
        
        if (isset($this->data['chauffageGaz']) && $this->data['chauffageGaz'] === 'oui') {
            $usages[] = 'Chauffage';
        }
        if (isset($this->data['eauChaude']) && $this->data['eauChaude'] === 'gaz') {
            $usages[] = 'Eau chaude';
        }
        if (isset($this->data['cuisson']) && $this->data['cuisson'] === 'gaz') {
            $usages[] = 'Cuisson';
        }
        
        if (empty($usages) && isset($this->data['companyName'])) {
            $usages = $this->determineBusinessGasUsages();
        }
        
        if (empty($usages)) {
            $usages[] = 'Usage professionnel';
        }
        
        return implode(', ', $usages);
    }

    private function determineBusinessGasUsages() {
        $nafCode = $this->data['nafCode'] ?? '';
        $legalForm = strtolower($this->data['legalForm'] ?? '');
        $companyName = strtolower($this->data['companyName'] ?? '');
        
        if (strpos($nafCode, '56') === 0 || strpos($companyName, 'restaurant') !== false || strpos($companyName, 'boulangerie') !== false) {
            return ['Chauffage', 'Cuisson professionnelle', 'Eau chaude sanitaire'];
        } elseif (strpos($nafCode, '10') === 0 || strpos($nafCode, '11') === 0 || strpos($companyName, 'industrie') !== false) {
            return ['Chauffage', 'Process industriel', 'Séchage'];
        } elseif (strpos($nafCode, '86') === 0 || strpos($nafCode, '87') === 0 || strpos($companyName, 'clinique') !== false || strpos($companyName, 'hopital') !== false) {
            return ['Chauffage', 'Eau chaude sanitaire', 'Stérilisation'];
        } elseif (strpos($nafCode, '55') === 0 || strpos($companyName, 'hotel') !== false) {
            return ['Chauffage', 'Eau chaude sanitaire', 'Cuisson'];
        } elseif (strpos($nafCode, '68') === 0 || strpos($legalForm, 'sci') !== false) {
            return ['Chauffage collectif', 'Eau chaude sanitaire'];
        } else {
            return ['Chauffage', 'Eau chaude sanitaire'];
        }
    }

    private function buildGazProfessionnelDetailBox($pdf, $x, $y, $width, $title, $items, $color) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, 70, 'F');
        
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $width, 70, 'D');
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y, $width, 12, 'F');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y + 3);
        $pdf->Cell($width, 6, $title, 0, 1, 'C');
        
        $yPos = $y + 16;
        foreach ($items as $label => $value) {
            if ($yPos > $y + 65) break;
            
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($x + 5, $yPos);
            $pdf->Cell(35, 4, $label, 0, 0);
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($x + 5, $yPos + 5);
            
            $maxWidth = $width - 10;
            if ($pdf->GetStringWidth($value) > $maxWidth) {
                $value = substr($value, 0, 25) . '...';
            }
            
            $pdf->Cell($maxWidth, 4, $value, 0, 1);
            
            $yPos += 10;
        }
    }

    private function buildGazProfessionnelFooter($pdf) {
        $currentY = $pdf->GetY();
        $footerHeight = 25;
        $pageHeight = 297;
        
        $minY = $currentY + 5;
        $maxY = $pageHeight - $footerHeight;
        $y = min($maxY, max($minY, $maxY));
        
        if ($y < $currentY + 3) {
            $y = $currentY + 3;
            $footerHeight = $pageHeight - $y;
        }
        
        $pdf->SetY($y);
        
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Rect(0, $y, 210, $footerHeight, 'F');
        
        $pdf->SetFillColor(34, 47, 70);
        $pdf->Rect(0, $y, 210, 1, 'F');
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetY($y + 4);
        
        $advantages = $this->utf8_decode_text('Solutions gaz sur-mesure • Tarifs négociés • Accompagnement dédié');
        $pdf->Cell(210, 4, $advantages, 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetY($y + 12);
        $copyright = $this->utf8_decode_text('Copyright ' . date('Y') . ' GES Solutions - Devis gaz professionnel valable 30 jours - Tarifs HTVA');
        $pdf->Cell(210, 4, $copyright, 0, 0, 'C');
    }

    private function generateSimpleGazProfessionnelPDF($outputPath) {
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(255, 111, 0);
        $pdf->Cell(0, 15, 'DEVIS GAZ PROFESSIONNEL - GES SOLUTIONS', 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(50, 50, 50);
        
        $content = "ENTREPRISE: " . ($this->data['companyName'] ?? '') . "\n";
        $content .= "Contact: " . ($this->data['firstName'] ?? '') . ' ' . ($this->data['lastName'] ?? '') . "\n";
        $content .= "Email: " . ($this->data['email'] ?? '') . "\n";
        $content .= "Telephone: " . ($this->data['phone'] ?? '') . "\n";
        $content .= "SIRET: " . ($this->data['siret'] ?? '') . "\n\n";
        
        if ($this->data['isHighConsumption'] ?? false) {
            $content .= "DEMANDE DE DEVIS PERSONNALISE\n";
            $content .= "================================\n";
            $content .= "Consommation annuelle: " . number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . " kWh/an\n";
            $content .= "Type: GROSSE CONSOMMATION\n";
            $content .= "Contact sous 48h pour devis personnalise\n\n";
        } else {
            $content .= "RESULTATS DU DEVIS GAZ\n";
            $content .= "================================\n";
            $content .= "Estimation annuelle HTVA: " . number_format($this->data['annualCost'] ?? 0, 2, ',', ' ') . " EUR\n";
            $content .= "Consommation annuelle: " . number_format($this->data['annualConsumption'] ?? 0, 0, ' ', ' ') . " kWh/an\n";
            $content .= "Type de gaz: " . ($this->data['gasType'] ?? 'Non défini') . "\n";
            $content .= "Type de contrat: " . (($this->data['contractType'] ?? '') === 'principal' ? 'Principal' : 'Site secondaire') . "\n";
            $content .= "Usages: " . $this->buildUsagesTextGazPro() . "\n\n";
        }
        
        $pdf->MultiCell(0, 6, $this->utf8_decode_text($content));
        
        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 10, 'Devis genere le ' . date('d/m/Y') . ' - GES Solutions Gaz Pro', 0, 1, 'C');
        
        $pdf->Output('F', $outputPath);
        return true;
    }

    /*******************************************
     * UTILITAIRES GAZ
     *******************************************/

    private function determineTypeGaz() {
        if (isset($this->data['type_gaz_autre'])) {
            return $this->data['type_gaz_autre'] === 'naturel' ? 'Gaz Naturel' : 'Gaz Propane';
        }
        return 'Gaz Naturel';
    }

    private function buildUsagesText() {
        $usages = [];
        
        if ($this->data['chauffageGaz'] === 'oui') {
            $usages[] = 'Chauffage';
        }
        if ($this->data['eauChaude'] === 'gaz') {
            $usages[] = 'Eau chaude';
        }
        if ($this->data['cuisson'] === 'gaz') {
            $usages[] = 'Cuisson';
        }
        
        return empty($usages) ? 'Aucun' : implode(', ', $usages);
    }
}
?>