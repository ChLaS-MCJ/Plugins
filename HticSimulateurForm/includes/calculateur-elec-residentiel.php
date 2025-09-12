<?php
/**
 * Calculateur Électricité Résidentiel - Version organisée par catégories
 * Fichier: includes/calculateur-elec-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class HticCalculateurElecResidentiel {
    
    private $userData;
    private $configData;
    private $debugMode;
    private $resultats = array();
    
    public function __construct($userData = array(), $configData = array(), $debugMode = false) {
        $this->userData = $userData;
        $this->configData = $configData;
        $this->debugMode = $debugMode;
        
        $this->logDebug("=== CALCULATEUR ÉLECTRICITÉ RÉSIDENTIEL INITIALISÉ ===");
    }
    
    /**
     * Point d'entrée principal pour le calcul
     */
    public function calculate() {
        $this->logDebug("Début du calcul pour électricité résidentiel");
        
        // Récupération et validation des données
        $validatedData = $this->validateAndExtractData();
        
        if (!$validatedData) {
            return $this->returnError("Données invalides");
        }
        
        // Initialiser les résultats avec données de base
        $this->resultats = array(
            'data_utilisateur' => $validatedData,
            'consommations' => array(),
            'puissances' => array(),
            'totaux' => array(
                'consommation_totale' => 0,
                'puissance_totale' => 0
            )
        );
        
        // CALCULS PAR CATÉGORIE
        $this->calculerChauffage();
        $this->calculerChauffeEau();
        $this->calculerElectromenagers();
        $this->calculerMultimedia();
        $this->calculerEquipementsSupplementaires();
        $this->calculerEclairage();
        
        // Calculs finaux
        $this->calculerTotaux();
        $this->calculerPuissanceRecommandee();
        $this->calculerTarifs();
        $this->genererRecommandations();
        
        $this->logDebug("=== RÉSULTATS FINAUX ===");
        $this->logDebug("Consommation totale: " . $this->resultats['totaux']['consommation_totale'] . " kWh/an");
        $this->logDebug("Puissance totale: " . $this->resultats['totaux']['puissance_totale'] . " kW");
        
        return array(
            'success' => true,
            'data' => $this->resultats
        );
    }
    
    // ===============================
    // CALCUL CHAUFFAGE
    // ===============================
    
    private function calculerChauffage() {
        $this->logDebug("=== CALCUL CHAUFFAGE ===");
        
        $userData = $this->resultats['data_utilisateur'];
        $chauffageElec = isset($userData['chauffage_electrique']) ? $userData['chauffage_electrique'] : 'non';
        
        $consommation = 0;
        $puissance = 0;
        
        if ($chauffageElec === 'oui') {
            $surface = floatval($userData['surface']);
            $typeLogement = $userData['type_logement'];
            $isolation = $userData['isolation'];
            $typeChauffage = isset($userData['type_chauffage_elec']) ? $userData['type_chauffage_elec'] : 'convecteurs';
            
            // Récupérer les données de consommation par m² selon le type et isolation
            $conso_m2_key = $typeLogement . '_' . $typeChauffage . '_' . $isolation;
            $conso_m2 = isset($this->configData[$conso_m2_key]) ? $this->configData[$conso_m2_key] : 0;
            
            $consommation = $surface * $conso_m2;
            
            // Calcul puissance chauffage (50W par m² avec simultanéité 80%)
            $puissance_base = isset($this->configData['chauffage_m2_puissance']) ? $this->configData['chauffage_m2_puissance'] : 50;
            $simultaneite = isset($this->configData['chauffage_m2_simultaneite']) ? ($this->configData['chauffage_m2_simultaneite'] / 100) : 0.8;
            
            $puissance = ($surface * $puissance_base * $simultaneite) / 1000; // kW
            
            $this->logDebug("Surface: {$surface} m²");
            $this->logDebug("Type: {$typeLogement} - Chauffage: {$typeChauffage} - Isolation: {$isolation}");
            $this->logDebug("Consommation/m²: {$conso_m2} kWh/m²/an");
            $this->logDebug("Consommation chauffage: {$consommation} kWh/an");
            $this->logDebug("Puissance chauffage: {$puissance} kW");
        } else {
            $this->logDebug("Pas de chauffage électrique");
        }
        
        $this->resultats['consommations']['chauffage'] = $consommation;
        $this->resultats['puissances']['chauffage'] = $puissance;
    }
    
    // ===============================
    // CALCUL CHAUFFE-EAU
    // ===============================
    
    private function calculerChauffeEau() {
        $this->logDebug("=== CALCUL CHAUFFE-EAU ===");
        
        $userData = $this->resultats['data_utilisateur'];
        $eauChaude = isset($userData['eau_chaude']) ? $userData['eau_chaude'] : 'non';
        
        $consommation = 0;
        $puissance = 0;
        
        if ($eauChaude === 'oui') {
            $nbPersonnes = intval($userData['nb_personnes']);
            if ($nbPersonnes > 6) $nbPersonnes = 6;
            
            // Consommation de base
            $conso_base = isset($this->configData['chauffe_eau']) ? $this->configData['chauffe_eau'] : 900;
            
            // Coefficient selon nombre de personnes
            $coeff_key = 'coeff_chauffe_eau_' . $nbPersonnes;
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            
            // Puissance chauffe-eau avec simultanéité
            $puissance_base = isset($this->configData['chauffe_eau_puissance']) ? $this->configData['chauffe_eau_puissance'] : 2400;
            $simultaneite = isset($this->configData['chauffe_eau_simultaneite']) ? ($this->configData['chauffe_eau_simultaneite'] / 100) : 0.3;
            
            $puissance = ($puissance_base * $simultaneite) / 1000; // kW
            
            $this->logDebug("Nombre de personnes: {$nbPersonnes}");
            $this->logDebug("Consommation de base: {$conso_base} kWh/an");
            $this->logDebug("Coefficient: {$coefficient}");
            $this->logDebug("Consommation chauffe-eau: {$consommation} kWh/an");
            $this->logDebug("Puissance chauffe-eau: {$puissance} kW");
        } else {
            $this->logDebug("Pas d'eau chaude électrique");
        }
        
        $this->resultats['consommations']['chauffe_eau'] = $consommation;
        $this->resultats['puissances']['chauffe_eau'] = $puissance;
    }
    
    // ===============================
    // CALCUL ÉLECTROMÉNAGERS
    // ===============================
    
    private function calculerElectromenagers() {
        $this->logDebug("=== CALCUL ÉLECTROMÉNAGERS ===");
        
        $userData = $this->resultats['data_utilisateur'];
        $electromenagers = isset($userData['electromenagers']) && is_array($userData['electromenagers']) ? $userData['electromenagers'] : array();
        $nbPersonnes = intval($userData['nb_personnes']);
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        
        $consommation_totale = 0;
        $puissance_totale = 0;
        
        // Liste des électroménagers possibles
        $electromenagers_disponibles = array(
            'lave_linge', 'four', 'seche_linge', 'lave_vaisselle', 'cave_vin',
            'refrigerateur', 'congelateur', 'plaque_cuisson'
        );
        
        // Ajouter cuisson électrique si sélectionnée
        $cuisson = isset($userData['cuisson_electrique']) ? $userData['cuisson_electrique'] : 'non';
        if ($cuisson === 'oui' && !in_array('plaque_cuisson', $electromenagers)) {
            $electromenagers[] = 'plaque_cuisson';
        }
        
        foreach ($electromenagers_disponibles as $equipement) {
            if (in_array($equipement, $electromenagers)) {
                
                // Consommation de base
                $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 0;
                
                // Coefficient selon nombre de personnes
                $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
                $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
                
                $consommation = $conso_base * $coefficient;
                $consommation_totale += $consommation;
                
                // Puissance avec simultanéité
                $puissance_base = isset($this->configData[$equipement . '_puissance']) ? $this->configData[$equipement . '_puissance'] : 1000;
                $simultaneite = isset($this->configData[$equipement . '_simultaneite']) ? ($this->configData[$equipement . '_simultaneite'] / 100) : 0.5;
                
                $puissance = ($puissance_base * $simultaneite) / 1000; // kW
                $puissance_totale += $puissance;
                
                $this->logDebug("- {$equipement}: {$consommation} kWh/an, {$puissance} kW");
            }
        }
        
        // Ajouter forfait autres petits électroménagers
        $forfait_petits = isset($this->configData['forfait_petits_electromenagers']) ? $this->configData['forfait_petits_electromenagers'] : 150;
        $consommation_totale += $forfait_petits;
        
        $this->logDebug("Forfait petits électroménagers: {$forfait_petits} kWh/an");
        $this->logDebug("TOTAL Électroménagers: {$consommation_totale} kWh/an, {$puissance_totale} kW");
        
        $this->resultats['consommations']['electromenagers'] = $consommation_totale;
        $this->resultats['puissances']['electromenagers'] = $puissance_totale;
    }
    
    // ===============================
    // CALCUL MULTIMÉDIA (automatique)
    // ===============================
    
    private function calculerMultimedia() {
        $this->logDebug("=== CALCUL MULTIMÉDIA ===");
        
        // TV/PC/Box inclus automatiquement
        $consommation = isset($this->configData['tv_pc_box']) ? $this->configData['tv_pc_box'] : 300;
        
        // Puissance multimédia avec simultanéité
        $puissance_base = isset($this->configData['tv_pc_box_puissance']) ? $this->configData['tv_pc_box_puissance'] : 500;
        $simultaneite = isset($this->configData['tv_pc_box_simultaneite']) ? ($this->configData['tv_pc_box_simultaneite'] / 100) : 0.8;
        
        $puissance = ($puissance_base * $simultaneite) / 1000; // kW
        
        $this->logDebug("TV/PC/Box: {$consommation} kWh/an, {$puissance} kW");
        
        $this->resultats['consommations']['multimedia'] = $consommation;
        $this->resultats['puissances']['multimedia'] = $puissance;
    }
    
    // ===============================
    // CALCUL ÉQUIPEMENTS SUPPLÉMENTAIRES
    // ===============================
    
    private function calculerEquipementsSupplementaires() {
        $this->logDebug("=== CALCUL ÉQUIPEMENTS SUPPLÉMENTAIRES ===");
        
        $userData = $this->resultats['data_utilisateur'];
        $equipements = isset($userData['equipements_speciaux']) && is_array($userData['equipements_speciaux']) ? $userData['equipements_speciaux'] : array();
        
        $consommation_totale = 0;
        $puissance_totale = 0;
        
        // Piscine
        $piscine = isset($userData['piscine']) ? $userData['piscine'] : 'non';
        if ($piscine === 'simple') {
            $conso = isset($this->configData['piscine']) ? $this->configData['piscine'] : 1400;
            $consommation_totale += $conso;
            
            $puissance_base = isset($this->configData['piscine_puissance']) ? $this->configData['piscine_puissance'] : 2500;
            $simultaneite = isset($this->configData['piscine_simultaneite']) ? ($this->configData['piscine_simultaneite'] / 100) : 0.8;
            $puissance_totale += ($puissance_base * $simultaneite) / 1000;
            
            $this->logDebug("- Piscine simple: {$conso} kWh/an");
            
        } elseif ($piscine === 'chauffee') {
            $conso = isset($this->configData['piscine_chauffee']) ? $this->configData['piscine_chauffee'] : 4000;
            $consommation_totale += $conso;
            $this->logDebug("- Piscine chauffée: {$conso} kWh/an");
        }
        
        // Autres équipements
        $equipements_config = array(
            'spa_jacuzzi' => 'spa_jacuzzi',
            'voiture_electrique' => 'voiture_electrique', 
            'aquarium_petit' => 'aquarium',
            'aquarium_grand' => 'aquarium',
            'climatiseur_mobile' => 'climatiseur_mobile'
        );
        
        foreach ($equipements_config as $equipement_user => $equipement_config) {
            if (in_array($equipement_user, $equipements)) {
                $conso = isset($this->configData[$equipement_config]) ? $this->configData[$equipement_config] : 0;
                
                // Aquarium grand = double de la consommation
                if ($equipement_user === 'aquarium_grand') {
                    $conso *= 2;
                }
                
                $consommation_totale += $conso;
                
                // Puissance si disponible
                if (isset($this->configData[$equipement_config . '_puissance'])) {
                    $puissance_base = $this->configData[$equipement_config . '_puissance'];
                    $simultaneite = isset($this->configData[$equipement_config . '_simultaneite']) ? ($this->configData[$equipement_config . '_simultaneite'] / 100) : 0.5;
                    $puissance_totale += ($puissance_base * $simultaneite) / 1000;
                }
                
                $this->logDebug("- {$equipement_user}: {$conso} kWh/an");
            }
        }
        
        $this->logDebug("TOTAL Équipements supplémentaires: {$consommation_totale} kWh/an, {$puissance_totale} kW");
        
        $this->resultats['consommations']['equipements_supplementaires'] = $consommation_totale;
        $this->resultats['puissances']['equipements_supplementaires'] = $puissance_totale;
    }
    
    // ===============================
    // CALCUL ÉCLAIRAGE
    // ===============================
    
    private function calculerEclairage() {
        $this->logDebug("=== CALCUL ÉCLAIRAGE ===");
        
        $userData = $this->resultats['data_utilisateur'];
        $surface = floatval($userData['surface']);
        $typeEclairage = isset($userData['type_eclairage']) ? $userData['type_eclairage'] : 'led';
        
        // Consommation par m² selon le type d'éclairage
        $conso_m2_key = 'eclairage_' . str_replace('_', '_', $typeEclairage) . '_m2';
        $conso_m2 = isset($this->configData[$conso_m2_key]) ? $this->configData[$conso_m2_key] : 5;
        
        // Si pas trouvé, utiliser les valeurs par défaut
        if (!isset($this->configData[$conso_m2_key])) {
            $conso_m2 = ($typeEclairage === 'led') ? 5 : 15;
        }
        
        $consommation = $surface * $conso_m2;
        
        // Puissance éclairage avec simultanéité
        $puissance_base = isset($this->configData['eclairage_puissance']) ? $this->configData['eclairage_puissance'] : 500;
        $simultaneite = isset($this->configData['eclairage_simultaneite']) ? ($this->configData['eclairage_simultaneite'] / 100) : 0.8;
        
        $puissance = ($puissance_base * $simultaneite) / 1000; // kW
        
        $this->logDebug("Surface: {$surface} m²");
        $this->logDebug("Type éclairage: {$typeEclairage}");
        $this->logDebug("Consommation/m²: {$conso_m2} kWh/m²/an");
        $this->logDebug("Consommation éclairage: {$consommation} kWh/an, {$puissance} kW");
        
        $this->resultats['consommations']['eclairage'] = $consommation;
        $this->resultats['puissances']['eclairage'] = $puissance;
    }
    
    // ===============================
    // CALCULS FINAUX
    // ===============================
    
    private function calculerTotaux() {
        $this->logDebug("=== CALCUL TOTAUX ===");
        
        $consommation_totale = 0;
        $puissance_totale = 0;
        
        foreach ($this->resultats['consommations'] as $categorie => $conso) {
            $consommation_totale += $conso;
        }
        
        foreach ($this->resultats['puissances'] as $categorie => $puissance) {
            $puissance_totale += $puissance;
        }
        
        $this->resultats['totaux']['consommation_totale'] = $consommation_totale;
        $this->resultats['totaux']['puissance_totale'] = $puissance_totale;
        
        $this->logDebug("Consommation totale: {$consommation_totale} kWh/an");
        $this->logDebug("Puissance totale: {$puissance_totale} kW");
    }
    
    private function calculerPuissanceRecommandee() {
        $puissance_totale = $this->resultats['totaux']['puissance_totale'];
        
        // Ajouter marge de sécurité 20%
        $puissance_avec_marge = $puissance_totale * 1.2;
        
        // Puissances standard disponibles
        $puissances_standard = array(3, 6, 9, 12, 15, 18, 24, 30, 36);
        
        $puissance_recommandee = 3;
        foreach ($puissances_standard as $puissance) {
            if ($puissance_avec_marge <= $puissance) {
                $puissance_recommandee = $puissance;
                break;
            }
        }
        
        $this->resultats['totaux']['puissance_recommandee'] = $puissance_recommandee;
        
        $this->logDebug("Puissance avec marge: {$puissance_avec_marge} kW");
        $this->logDebug("Puissance recommandée: {$puissance_recommandee} kVA");
    }
    
    private function calculerTarifs() {
        $this->logDebug("=== CALCUL TARIFS ===");
        
        $consommation = $this->resultats['totaux']['consommation_totale'];
        $puissance = $this->resultats['totaux']['puissance_recommandee'];
        
        // Tarif BASE
        $abo_base = isset($this->configData['base_abo_' . $puissance]) ? $this->configData['base_abo_' . $puissance] : 0;
        $kwh_base = isset($this->configData['base_kwh_' . $puissance]) ? $this->configData['base_kwh_' . $puissance] : 0;
        
        $cout_base_annuel = ($abo_base * 12) + ($consommation * $kwh_base);
        
        // Tarif HEURES CREUSES
        $abo_hc = isset($this->configData['hc_abo_' . $puissance]) ? $this->configData['hc_abo_' . $puissance] : 0;
        $kwh_hp = isset($this->configData['hc_hp_' . $puissance]) ? $this->configData['hc_hp_' . $puissance] : 0;
        $kwh_hc = isset($this->configData['hc_hc_' . $puissance]) ? $this->configData['hc_hc_' . $puissance] : 0;
        
        // Répartition HP/HC
        $repartition_hp = isset($this->configData['repartition_hp']) ? $this->configData['repartition_hp'] : 60;
        $repartition_hc = isset($this->configData['repartition_hc']) ? $this->configData['repartition_hc'] : 40;
        
        $conso_hp = $consommation * ($repartition_hp / 100);
        $conso_hc = $consommation * ($repartition_hc / 100);
        
        $cout_hc_annuel = ($abo_hc * 12) + ($conso_hp * $kwh_hp) + ($conso_hc * $kwh_hc);
        
        $this->resultats['tarifs'] = array(
            'base' => array(
                'total_annuel' => round($cout_base_annuel, 2),
                'total_mensuel' => round($cout_base_annuel / 12, 2)
            ),
            'hc' => array(
                'total_annuel' => round($cout_hc_annuel, 2),
                'total_mensuel' => round($cout_hc_annuel / 12, 2)
            ),
            'recommande' => ($cout_hc_annuel < $cout_base_annuel) ? 'hc' : 'base',
            'economies' => round(abs($cout_base_annuel - $cout_hc_annuel), 2)
        );
        
        $this->logDebug("Tarif BASE: " . $cout_base_annuel . "€/an");
        $this->logDebug("Tarif HC: " . $cout_hc_annuel . "€/an");
        $this->logDebug("Recommandé: " . $this->resultats['tarifs']['recommande']);
    }
    
    private function genererRecommandations() {
        $recommandations = array();
        
        $consommation = $this->resultats['totaux']['consommation_totale'];
        $surface = floatval($this->resultats['data_utilisateur']['surface']);
        
        // Consommation par m²
        $conso_m2 = $surface > 0 ? $consommation / $surface : 0;
        
        if ($conso_m2 > 150) {
            $recommandations[] = "⚠️ Votre consommation est élevée (" . round($conso_m2) . " kWh/m²/an). Considérez l'amélioration de l'isolation.";
        } elseif ($conso_m2 < 50) {
            $recommandations[] = "👍 Excellente maîtrise de votre consommation !";
        }
        
        // Recommandation tarifaire
        if ($this->resultats['tarifs']['recommande'] === 'hc') {
            $recommandations[] = "💡 Le tarif Heures Creuses vous ferait économiser " . $this->resultats['tarifs']['economies'] . "€/an.";
        }
        
        $this->resultats['recommandations'] = $recommandations;
    }
    
    // ===============================
    // MÉTHODES UTILITAIRES
    // ===============================
    
    private function validateAndExtractData() {
        // Validation basique - à compléter selon vos besoins
        $required_fields = array('surface', 'nb_personnes', 'isolation', 'type_logement');
        
        foreach ($required_fields as $field) {
            if (!isset($this->userData[$field]) || empty($this->userData[$field])) {
                $this->logDebug("Champ obligatoire manquant: " . $field);
                return false;
            }
        }
        
        return $this->userData;
    }
    
    private function logDebug($message) {
        if ($this->debugMode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("[HTIC CALCULATEUR] " . $message);
        }
    }
    
    private function returnError($message) {
        $this->logDebug("ERREUR: " . $message);
        return array('success' => false, 'error' => $message);
    }
}

/**
 * Fonction d'entrée pour les appels AJAX
 */
function htic_calculateur_elec_residentiel($userData, $configData) {
    $calculateur = new HticCalculateurElecResidentiel($userData, $configData, true);
    return $calculateur->calculate();
}

/**
 * Handler AJAX pour WordPress
 */
add_action('wp_ajax_htic_calculate_estimation', 'htic_ajax_calculate_estimation');
add_action('wp_ajax_nopriv_htic_calculate_estimation', 'htic_ajax_calculate_estimation');

function htic_ajax_calculate_estimation() {
    // Vérification sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'htic_simulateur_calculate')) {
        wp_send_json_error('Nonce invalide');
        return;
    }
    
    $type = sanitize_text_field($_POST['type']);
    $userData = $_POST['user_data'];
    $configData = $_POST['config_data'];
    
    if ($type === 'elec-residentiel') {
        $result = htic_calculateur_elec_residentiel($userData, $configData);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['error']);
        }
    } else {
        wp_send_json_error('Type de calculateur non supporté: ' . $type);
    }
}
?>