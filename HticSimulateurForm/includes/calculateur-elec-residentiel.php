<?php
/**
 * Calculateur Électricité Résidentiel - Version Complète
 * Fichier: includes/calculateur-elec-residentiel.php
 * Version: 2.0 - Affichage détaillé complet
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class HticCalculateurElecResidentiel {
    
    private $userData;
    private $configData;
    private $debugMode;
    
    public function __construct($userData = array(), $configData = array(), $debugMode = false) {
        $this->userData = $userData;
        $this->configData = $configData;
        $this->debugMode = $debugMode;
    }
    
    /**
     * Point d'entrée principal pour le calcul
     */
    public function calculate() {
        
        // Récupération et validation des données
        $validatedData = $this->validateAndExtractData();
        
        if (!$validatedData) {
            return $this->returnError("Données invalides ou incomplètes");
        }
        
        // Calcul détaillé complet
        $results = $this->performCompleteCalculation($validatedData);
        
        return array(
            'success' => true,
            'data' => $results
        );
    }
    
    /**
     * Validation et extraction des données utilisateur
     */
    private function validateAndExtractData() {
        
        $extractedData = array();
        
        // Informations du logement
        $extractedData['type_logement'] = $this->extractValue('type_logement', 'string');
        $extractedData['surface'] = $this->extractValue('surface', 'int');
        $extractedData['nb_personnes'] = $this->extractValue('nb_personnes', 'int');
        
        // Chauffage et isolation
        $extractedData['type_chauffage'] = $this->extractValue('type_chauffage', 'string');
        $extractedData['isolation'] = $this->extractValue('isolation', 'string');
        
        // Électroménagers
        $extractedData['electromenagers'] = $this->extractValue('electromenagers', 'array');
        $extractedData['type_cuisson'] = $this->extractValue('type_cuisson', 'string');
        
        // Eau chaude
        $extractedData['eau_chaude'] = $this->extractValue('eau_chaude', 'string');
        
        // Éclairage
        $extractedData['type_eclairage'] = $this->extractValue('type_eclairage', 'string');
        
        // Équipements spéciaux
        $extractedData['piscine'] = $this->extractValue('piscine', 'string');
        $extractedData['equipements_speciaux'] = $this->extractValue('equipements_speciaux', 'array');
        $extractedData['preference_tarif'] = $this->extractValue('preference_tarif', 'string');
        
        // Validation des champs obligatoires
        $requiredFields = array('type_logement', 'surface', 'nb_personnes', 'type_chauffage', 'type_cuisson', 'eau_chaude', 'type_eclairage', 'piscine');
        
        foreach ($requiredFields as $field) {
            if (empty($extractedData[$field]) && $extractedData[$field] !== '0') {
                $this->logDebug("❌ ERREUR: Champ obligatoire manquant: " . $field);
                return false;
            }
        }
        
        // Validation spécifique: isolation obligatoire si chauffage électrique
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (in_array($extractedData['type_chauffage'], $chauffagesElectriques) && empty($extractedData['isolation'])) {
            $this->logDebug("❌ ERREUR: Isolation obligatoire pour le chauffage électrique");
            return false;
        }
        
        return $extractedData;
    }
    
    /**
     * Extraction d'une valeur avec validation de type
     */
    private function extractValue($key, $type = 'string') {
        $value = isset($this->userData[$key]) ? $this->userData[$key] : null;
        
        switch ($type) {
            case 'int':
                $value = intval($value);
                break;
            case 'float':
                $value = floatval($value);
                break;
            case 'array':
                $value = is_array($value) ? $value : array();
                break;
            case 'string':
            default:
                $value = strval($value);
                break;
        }
        
        return $value;
    }
    
    /**
     * CALCUL DÉTAILLÉ COMPLET
     */
    private function performCompleteCalculation($data) {
        
        $surface = (int)$data['surface'];
        $nbPersonnes = (int)$data['nb_personnes'];
        $typeLogement = $data['type_logement'];
        
        // ==========================================
        // CALCULS DÉTAILLÉS AVEC TOUTES LES INFOS
        // ==========================================
        
        // 1. CHAUFFAGE
        $chauffageDetails = $this->calculateChauffage($data);
        $chauffageKwh = $chauffageDetails['total'];
        
        // 2. EAU CHAUDE
        $eauChaudeDetails = $this->calculateEauChaude($data);
        $eauChaudeKwh = $eauChaudeDetails['total'];
        
        // 3. ÉLECTROMÉNAGERS
        $electromenagersDetails = $this->calculateElectromenager($data);
        $electromenagersKwh = $electromenagersDetails['total'];
        
        // 4. ÉCLAIRAGE
        $eclairageDetails = $this->calculateEclairageDetaille($data);
        $eclairageKwh = $eclairageDetails['total'];
        
        // 5. MULTIMÉDIA
        $multimediaDetails = $this->calculateMultimediaDetaille($data);
        $multimediaKwh = $multimediaDetails['total'];
        
        // 6. ÉQUIPEMENTS SPÉCIAUX
        $equipementsDetails = $this->calculateEquipementsSpeciauxDetaille($data);
        $equipementsKwh = $equipementsDetails['total'];
        

        
        

        $puissanceChauffage = $this->calculatePuissanceChauffage($data);
        $puissanceEauChaude = $this->calculatePuissanceEauChaude($data);
        $puissanceElectromenagers = $this->calculatePuissanceElectromenagers($data);
        $puissanceMultimedia = $this->calculatePuissanceMultimedia($data);
        $puissanceEquipements = $this->calculatePuissanceEquipementsSpeciaux($data);
        $puissanceEclairage = $this->calculatePuissanceEclairage($data);

        // TOTAL RETENUE PUISSANCE
        $puissanceTotaleRetenue = $puissanceChauffage + $puissanceEauChaude + $puissanceElectromenagers + 
                                $puissanceMultimedia + $puissanceEquipements + $puissanceEclairage;

        // PUISSANCE RECOMMANDÉE - Mettre ICI avec le bon paramètre
        $puissanceRecommandee = $this->calculatePuissanceRecommandee($puissanceTotaleRetenue);

        // TOTAL GÉNÉRAL
        $consommationTotale = $chauffageKwh + $eauChaudeKwh + $electromenagersKwh + 
                            $eclairageKwh + $multimediaKwh + $equipementsKwh;

        // CALCUL DES TARIFS - passer la puissance recommandée
        $tarifsCalcules = $this->calculateTarifsDetaille($consommationTotale, $data, $puissanceRecommandee);
        // ==========================================
        // STRUCTURE DE RETOUR COMPLÈTE
        // ==========================================
        
        return array(
            // Résultat principal
            'consommation_annuelle' => (int)round($consommationTotale),
            'puissance_recommandee' => $puissanceRecommandee,
            
            // TARIFS
            'tarifs' => $tarifsCalcules,
            
            // RÉPARTITION SIMPLE (pour graphique)
            'repartition' => array(
                'chauffage' => (int)round($chauffageKwh),
                'eau_chaude' => (int)round($eauChaudeKwh),
                'electromenagers' => (int)round($electromenagersKwh),
                'eclairage' => (int)round($eclairageKwh),
                'multimedia' => (int)round($multimediaKwh),
                'tv_pc_box' => (int)round($multimediaKwh),
                'autres' => 0,
                'equipements_speciaux' => $equipementsDetails['repartition']
            ),
            
            // DÉTAILS COMPLETS DE CALCUL
            'details_calcul' => array(
                'chauffage' => $chauffageDetails,
                'eau_chaude' => $eauChaudeDetails,
                'electromenagers' => $electromenagersDetails,
                'eclairage' => $eclairageDetails,
                'multimedia' => $multimediaDetails,
                'tv_pc_box' => $multimediaDetails,
                'equipements_speciaux' => $equipementsDetails,
                'coefficients' => array(
                    'logement' => $typeLogement === 'appartement' ? 0.95 : 1.0,
                ),
                'methode_calcul' => 'HTIC Simulateur v2.0 - Calcul détaillé complet',
                'timestamp' => date('Y-m-d H:i:s'),
                'donnees_config_utilisees' => $this->getConfigSummary()
            ),

            'puissances_retenues' => array(
                'chauffage' => round($puissanceChauffage, 2),
                'eau_chaude' => round($puissanceEauChaude, 2),
                'electromenagers' => round($puissanceElectromenagers, 2),
                'multimedia' => round($puissanceMultimedia, 2),
                'equipements_speciaux' => round($puissanceEquipements, 2),
                'eclairage' => round($puissanceEclairage, 2),
                'total' => round($puissanceTotaleRetenue, 2)
            ),
            
            // RÉCAPITULATIF DES DONNÉES UTILISATEUR (pour affichage)
            'recap' => $data
        );
    }
    
    // ==========================================
    // CALCULS DÉTAILLÉS PAR POSTE
    // ==========================================
    
    private function calculateChauffage($data) {
        $typeChauffage = isset($data['type_chauffage']) ? $data['type_chauffage'] : '';
        $typeLogement = isset($data['type_logement']) ? $data['type_logement'] : 'maison';
        $surface = floatval($data['surface']);
        $isolation = isset($data['isolation']) ? $data['isolation'] : '';
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        
        if (!in_array($typeChauffage, $chauffagesElectriques)) {
            return array(
                'total' => 0,
                'type_chauffage' => $typeChauffage,
                'methode' => 'Pas de chauffage électrique',
                'calcul' => 'Chauffage non électrique - consommation électrique: 0 kWh/an',
                'explication' => 'Le chauffage principal n\'est pas électrique'
            );
        }
        
        // Mapping des isolations
        $isolationMapping = array(
            'avant_1980' => 'mauvaise',
            '1980_2000' => 'moyenne', 
            'apres_2000' => 'bonne',
            'renovation' => 'tres_bonne'
        );
        
        $isolationNormalisee = isset($isolationMapping[$isolation]) ? $isolationMapping[$isolation] : 'moyenne';
        
        // Mapping des types de chauffage vers les clés de config
        $chauffageMapping = array(
            'convecteurs' => 'convecteurs',
            'inertie' => 'inertie',
            'clim_reversible' => 'clim',
            'pac' => 'pac'
        );
        
        $chauffageKey = isset($chauffageMapping[$typeChauffage]) ? $chauffageMapping[$typeChauffage] : $typeChauffage;
        
        // Construire la clé de configuration avec le nom mappé
        $config_key = $typeLogement . '_' . $chauffageKey . '_' . $isolationNormalisee;
        
        // Récupérer la consommation par m² depuis la configuration
        $conso_par_m2 = isset($this->configData[$config_key]) ? $this->configData[$config_key] : 0;
        
        // Si toujours pas trouvé, essayer avec le nom complet (pour compatibilité)
        if ($conso_par_m2 == 0) {
            $config_key_alt = $typeLogement . '_' . $typeChauffage . '_' . $isolationNormalisee;
            $conso_par_m2 = isset($this->configData[$config_key_alt]) ? $this->configData[$config_key_alt] : 0;
        }
        
        if ($conso_par_m2 > 0) {
            $consommation = $surface * $conso_par_m2;
            
            return array(
                'total' => $consommation,
                'consommation_m2' => $conso_par_m2,
                'surface' => $surface,
                'type_chauffage' => $typeChauffage,
                'type_logement' => $typeLogement,
                'isolation' => $isolation,
                'config_key' => $config_key,
                'methode' => 'Surface × Consommation chauffage par m²',
                'calcul' => "{$surface} m² × {$conso_par_m2} kWh/m²/an = {$consommation} kWh/an",
                'explication' => "Chauffage {$typeChauffage} en {$typeLogement} avec isolation {$isolation}"
            );
        } else {
            return array(
                'total' => 0,
                'erreur' => "Configuration manquante pour {$config_key}",
                'methode' => 'Erreur de configuration',
                'calcul' => 'Impossible de calculer - configuration manquante',
                'explication' => "Données de chauffage manquantes pour ce type de configuration"
            );
        }
    }

    private function calculateEauChaude($data) {
        
        $eauChaude = isset($data['eau_chaude']) ? $data['eau_chaude'] : 'non';
        $nbPersonnes = intval($data['nb_personnes']);
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        
        if ($eauChaude === 'oui') {
            $conso_base = isset($this->configData['chauffe_eau']) ? $this->configData['chauffe_eau'] : 0;
            
            if ($conso_base === 0) {
                
                return array(
                    'total' => 0,
                    'erreur' => 'Configuration chauffe-eau manquante',
                    'methode' => 'Erreur de configuration',
                    'calcul' => 'Configuration manquante pour chauffe-eau',
                    'explication' => 'Données de consommation eau chaude non configurées'
                );
            }
            
            $coeff_key = 'coeff_chauffe_eau_' . $nbPersonnes;
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            
            return array(
                'total' => $consommation,
                'base_kwh' => $conso_base,
                'coefficient' => $coefficient,
                'nb_personnes' => $nbPersonnes,
                'coeff_key' => $coeff_key,
                'methode' => 'Base chauffe-eau × Coefficient personnes',
                'calcul' => "{$conso_base} kWh/an × {$coefficient} = {$consommation} kWh/an",
                'explication' => "Eau chaude électrique pour {$nbPersonnes} personne(s)"
            );
            
        } else {
            
            return array(
                'total' => 0,
                'methode' => 'Pas d\'eau chaude électrique',
                'calcul' => 'Eau chaude non électrique - consommation: 0 kWh/an',
                'explication' => 'Production d\'eau chaude par autre énergie (gaz, solaire, etc.)'
            );
        }
    }

    private function calculateElectromenager($data) {
        
        $electromenagers = isset($data['electromenagers']) && is_array($data['electromenagers']) ? $data['electromenagers'] : array();
        $nbPersonnes = intval($data['nb_personnes']);
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        
        $consommation_totale = 0;
        $details_calcul = array();
        
        $electromenagers_disponibles = array(
            'lave_linge', 'four', 'seche_linge', 'lave_vaisselle', 
            'cave_a_vin', 'refrigerateur', 'congelateur'
        );
        
        foreach ($electromenagers as $equipement) {
            if (in_array($equipement, $electromenagers_disponibles)) {
                $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 0;
                $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
                $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
                
                $consommation = $conso_base * $coefficient;
                $consommation_totale += $consommation;
                
                $details_calcul[$equipement] = array(
                    'nom' => $this->getEquipementLabel($equipement),
                    'base_kwh' => $conso_base,
                    'coefficient' => $coefficient,
                    'final_kwh' => $consommation
                );
                
            }
        }
        
        // === PLAQUE DE CUISSON ===
        $type_cuisson = isset($data['type_cuisson']) ? $data['type_cuisson'] : '';
        
        if ($type_cuisson === 'induction' || $type_cuisson === 'plaque_induction') {
            $equipement = 'plaque_induction';
            $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 365;
            
            $coeff_key = 'coeff_plaque_induction_' . $nbPersonnes;
            
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            $consommation_totale += $consommation;
            
            $details_calcul['plaque_induction'] = array(
                'nom' => 'Plaque à induction',
                'base_kwh' => $conso_base,
                'coefficient' => $coefficient,
                'final_kwh' => $consommation,
                'coeff_key' => $coeff_key
            );
            
        } elseif ($type_cuisson === 'vitroceramique' || $type_cuisson === 'plaque_vitroceramique') {
            $equipement = 'plaque_vitroceramique';
            $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 400;
            
            $coeff_key = 'coeff_plaque_vitroceramique_' . $nbPersonnes;
            
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            $consommation_totale += $consommation;
            
            $details_calcul['plaque_vitroceramique'] = array(
                'nom' => 'Plaque vitrocéramique',
                'base_kwh' => $conso_base,
                'coefficient' => $coefficient,
                'final_kwh' => $consommation,
                'coeff_key' => $coeff_key
            );

        }
        
        $forfait = isset($this->configData['forfait_petits_electromenagers']) ? $this->configData['forfait_petits_electromenagers'] : 100;
        if ($forfait > 0) {
            $consommation_totale += $forfait;
            $details_calcul['forfait_petits_electromenagers'] = array(
                'nom' => 'Forfait petits appareils',
                'base_kwh' => $forfait,
                'coefficient' => 1,
                'final_kwh' => $forfait
            );
        }
        
        return array(
            'total' => $consommation_totale,
            'details' => $details_calcul,
            'repartition' => $details_calcul,
            'nb_personnes' => $nbPersonnes,
            'electromenagers_selectionnes' => $electromenagers,
            'type_cuisson' => $type_cuisson,
            'nb_equipements' => count($electromenagers),
            'methode' => 'Somme des équipements sélectionnés × Coefficient personnes',
            'explication' => 'Consommations ajustées selon le nombre de personnes et équipements sélectionnés'
        );
    }
    
    private function getEquipementLabel($equipement) {
        $labels = array(
            'lave_linge' => 'Lave-linge',
            'four' => 'Four',
            'seche_linge' => 'Sèche-linge',
            'lave_vaisselle' => 'Lave-vaisselle',
            'cave_a_vin' => 'Cave à vin',
            'refrigerateur' => 'Réfrigérateur',
            'congelateur' => 'Congélateur',
            'plaque_induction' => 'Plaque à induction',
            'plaque_vitroceramique' => 'Plaque vitrocéramique',
            'tv_pc_box' => 'TV/PC/Box'
        );
        
        return isset($labels[$equipement]) ? $labels[$equipement] : ucfirst(str_replace('_', ' ', $equipement));
    }
    
    private function calculateEclairageDetaille($data) {
        $typeEclairage = $data['type_eclairage'] ?? '';
        $surface = (int)$data['surface'];
        
        $configKey = 'eclairage_' . ($typeEclairage === 'led' ? 'led' : 'incandescent') . '_m2';
        $consommationM2 = $this->getConfigValue($configKey, ($typeEclairage === 'led' ? 5 : 15));
        
        $total = $surface * $consommationM2;
        
        return array(
            'total' => $total,
            'consommation_m2' => $consommationM2,
            'surface' => $surface,
            'type_eclairage' => $typeEclairage,
            'methode' => 'Surface × Consommation éclairage par m²',
            'calcul' => "{$surface} m² × {$consommationM2} kWh/m²/an = " . round($total) . " kWh/an",
            'explication' => "Éclairage {$typeEclairage} avec une consommation de {$consommationM2} kWh/m²/an"
        );
    }
    
    private function calculateMultimediaDetaille($data) {
        $nbPersonnes = (int)$data['nb_personnes'];
        
        $baseKwh = $this->getConfigValue('tv_pc_box', 300);
        
        $coeff_key = 'coeff_tv_pc_box_' . $nbPersonnes;
        $coefficient = $this->getConfigValue($coeff_key, 1.0);
        
        $total = $baseKwh * $coefficient;
        
        return array(
            'total' => $total,
            'base_kwh' => $baseKwh,
            'coefficient' => $coefficient,
            'nb_personnes' => $nbPersonnes,
            'methode' => 'Base multimédia × Coefficient personnes (inclus automatiquement)',
            'calcul' => "{$baseKwh} kWh/an × {$coefficient} = " . round($total) . " kWh/an",
            'explication' => 'Consommation TV, ordinateur, box internet incluse automatiquement et ajustée selon le nombre de personnes'
        );
    }
    
    private function calculateEquipementsSpeciauxDetaille($data) {
        $equipementsSpeciaux = $data['equipements_speciaux'] ?? array();
        $piscine = $data['piscine'] ?? 'non';
        
        $repartition = array();
        $total = 0;
        $details = array();
        
        // PISCINE
        if ($piscine === 'simple') {
            $kwhPiscine = $this->getConfigValue('piscine', 1400);
            $repartition['piscine'] = $kwhPiscine;
            $total += $kwhPiscine;
            $details['piscine'] = "Piscine simple: {$kwhPiscine} kWh/an";
        } elseif ($piscine === 'chauffee') {
            $kwhPiscine = $this->getConfigValue('piscine_chauffee', 4000);
            $repartition['piscine'] = $kwhPiscine;
            $total += $kwhPiscine;
            $details['piscine'] = "Piscine chauffée: {$kwhPiscine} kWh/an";
        } else {
            $repartition['piscine'] = 0;
            $details['piscine'] = "Pas de piscine: 0 kWh/an";
        }
        
        // ÉQUIPEMENTS SPÉCIAUX
        $equipementsPossibles = array(
            'spa_jacuzzi' => array('config' => 'spa_jacuzzi', 'default' => 2000, 'nom' => 'Spa/Jacuzzi'),
            'voiture_electrique' => array('config' => 'voiture_electrique', 'default' => 1500, 'nom' => 'Voiture électrique'),
            'aquarium' => array('config' => 'aquarium', 'default' => 240, 'nom' => 'Aquarium'),
            'climatiseur_mobile' => array('config' => 'climatiseur_mobile', 'default' => 150, 'nom' => 'Climatiseur mobile')
        );
        
        foreach (array('spa_jacuzzi', 'voiture_electrique', 'aquarium', 'climatiseur_mobile') as $eq) {
            $repartition[$eq] = 0;
        }
        
        foreach ($equipementsSpeciaux as $equipement) {
            if (isset($equipementsPossibles[$equipement])) {
                $config = $equipementsPossibles[$equipement];
                $kwh = $this->getConfigValue($config['config'], $config['default']);
                
                $repartition[$equipement] = $kwh;
                $total += $kwh;
                
                $details[$equipement] = "{$config['nom']}: {$kwh} kWh/an";
            }
        }
        
        return array(
            'total' => $total,
            'repartition' => $repartition,
            'details_calcul' => $details,
            'piscine_type' => $piscine,
            'equipements_selectionnes' => $equipementsSpeciaux,
            'nb_equipements' => count($equipementsSpeciaux),
            'methode' => 'Somme des équipements spéciaux sélectionnés',
            'explication' => 'Consommations spécifiques selon les équipements de confort et loisirs sélectionnés'
        );
    }
    
    private function calculateTarifsDetaille($consommationTotale, $data, $puissanceRecommandee) {
    
    // ===================================
    // RÉCUPÉRATION DES DONNÉES DU BACK
    // ===================================
    
    // 1. TARIF BASE TRV
    // Abonnement et prix kWh depuis la config
    $abo_base_mensuel = $this->getConfigValue('base_abo_' . $puissanceRecommandee, 21.53);
    $prix_kwh_base = $this->getConfigValue('base_kwh_' . $puissanceRecommandee, 0.2516);
    
    // 2. TARIF HEURES CREUSES TRV  
    // Abonnement et prix HP/HC depuis la config
    $abo_hc_mensuel = $this->getConfigValue('hc_abo_' . $puissanceRecommandee, 22.76);
    $prix_kwh_hp = $this->getConfigValue('hc_hp_' . $puissanceRecommandee, 0.2700);
    $prix_kwh_hc = $this->getConfigValue('hc_hc_' . $puissanceRecommandee, 0.2068);
    
    // Répartition HP/HC (paramétrable dans le back)
    $repartition_hp = $this->getConfigValue('repartition_hp', 60) / 100;
    $repartition_hc = $this->getConfigValue('repartition_hc', 40) / 100;
    
    // 3. TARIF TEMPO TRV
    // Abonnement Tempo
    $abo_tempo_mensuel = $this->getConfigValue('tempo_abo_' . $puissanceRecommandee, 23.59);
    
    // Prix des 6 périodes Tempo
    $prix_tempo_bleu_hp = $this->getConfigValue('tempo_bleu_hp', 0.1609);
    $prix_tempo_bleu_hc = $this->getConfigValue('tempo_bleu_hc', 0.1296);
    $prix_tempo_blanc_hp = $this->getConfigValue('tempo_blanc_hp', 0.1894);
    $prix_tempo_blanc_hc = $this->getConfigValue('tempo_blanc_hc', 0.1486);
    $prix_tempo_rouge_hp = $this->getConfigValue('tempo_rouge_hp', 0.7562);
    $prix_tempo_rouge_hc = $this->getConfigValue('tempo_rouge_hc', 0.1568);
    
    // Nombre de jours par couleur (paramétrable)
    $jours_bleus = $this->getConfigValue('tempo_jours_bleus', 300);
    $jours_blancs = $this->getConfigValue('tempo_jours_blancs', 43);
    $jours_rouges = $this->getConfigValue('tempo_jours_rouges', 22);
    
    // ===================================
    // CALCUL 1 : TARIF BASE TRV
    // ===================================
    /* 
     * Formule BASE = Abonnement annuel + (Consommation totale × Prix kWh)
     * - Abonnement annuel = Abo mensuel × 12 mois
     * - Coût consommation = Total kWh × Prix unitaire
     */
    
    $cout_abo_base_annuel = $abo_base_mensuel * 12;
    $cout_conso_base = $consommationTotale * $prix_kwh_base;
    $cout_total_base = $cout_abo_base_annuel + $cout_conso_base;
    
    $this->logDebug("=== CALCUL TARIF BASE ===");
    $this->logDebug("Puissance: {$puissanceRecommandee} kVA");
    $this->logDebug("Abo mensuel: {$abo_base_mensuel}€/mois");
        // ===================================
        // CALCUL 2 : TARIF HEURES CREUSES TRV
        // ===================================
        /*
        * Formule HC = Abonnement annuel + (Conso HP × Prix HP) + (Conso HC × Prix HC)
        * - Répartition : généralement 60% en HP, 40% en HC
        * - HP = Heures Pleines (journée)
        * - HC = Heures Creuses (nuit/weekend)
        */
        
        $consommation_hp = $consommationTotale * $repartition_hp;
        $consommation_hc = $consommationTotale * $repartition_hc;
        
        $cout_abo_hc_annuel = $abo_hc_mensuel * 12;
        $cout_conso_hp = $consommation_hp * $prix_kwh_hp;
        $cout_conso_hc = $consommation_hc * $prix_kwh_hc;
        $cout_total_hc = $cout_abo_hc_annuel + $cout_conso_hp + $cout_conso_hc;
        
        // ===================================
        // CALCUL 3 : TARIF TEMPO TRV
        // ===================================
        /*
        * Formule TEMPO = Abonnement + Σ(Conso période × Prix période)
        * - 300 jours BLEUS (tarif bas)
        * - 43 jours BLANCS (tarif moyen)  
        * - 22 jours ROUGES (tarif élevé)
        * - Chaque jour a HP et HC
        * - Total = 365 jours/an
        */
        
        // Répartition de la consommation sur les jours
        $ratio_bleus = $jours_bleus / 365;
        $ratio_blancs = $jours_blancs / 365;
        $ratio_rouges = $jours_rouges / 365;
        
        // Consommation par période (avec répartition HP/HC)
        $conso_bleu_hp = $consommationTotale * $ratio_bleus * $repartition_hp;
        $conso_bleu_hc = $consommationTotale * $ratio_bleus * $repartition_hc;
        $conso_blanc_hp = $consommationTotale * $ratio_blancs * $repartition_hp;
        $conso_blanc_hc = $consommationTotale * $ratio_blancs * $repartition_hc;
        $conso_rouge_hp = $consommationTotale * $ratio_rouges * $repartition_hp;
        $conso_rouge_hc = $consommationTotale * $ratio_rouges * $repartition_hc;
        
        // Coûts par période
        $cout_bleu = ($conso_bleu_hp * $prix_tempo_bleu_hp) + ($conso_bleu_hc * $prix_tempo_bleu_hc);
        $cout_blanc = ($conso_blanc_hp * $prix_tempo_blanc_hp) + ($conso_blanc_hc * $prix_tempo_blanc_hc);
        $cout_rouge = ($conso_rouge_hp * $prix_tempo_rouge_hp) + ($conso_rouge_hc * $prix_tempo_rouge_hc);
        
        $cout_abo_tempo_annuel = $abo_tempo_mensuel * 12;
        $cout_total_tempo = $cout_abo_tempo_annuel + $cout_bleu + $cout_blanc + $cout_rouge;
        
        // ===================================
        // DÉTERMINER LE MEILLEUR TARIF
        // ===================================
        
        $tarifs = array(
            'base' => $cout_total_base,
            'hc' => $cout_total_hc,
            'tempo' => $cout_total_tempo
        );
        
        $tarif_recommande = array_keys($tarifs, min($tarifs))[0];
        
        // ===================================
        // RETOUR DES RÉSULTATS STRUCTURÉS
        // ===================================
        
        return array(
            // Compatible avec l'ancien format pour la rétrocompatibilité
            'base' => array(
                'total_annuel' => (int)round($cout_total_base),
                'total_mensuel' => (int)round($cout_total_base / 12),
                'abonnement_mensuel' => $abo_base_mensuel,
                'prix_kwh' => $prix_kwh_base,
                'puissance_kva' => $puissanceRecommandee,
                'calcul_detail' => sprintf(
                    "Abo: %.2f€/mois × 12 = %.2f€/an + Conso: %d kWh × %.4f€ = %.2f€",
                    $abo_base_mensuel,
                    $cout_abo_base_annuel,
                    $consommationTotale,
                    $prix_kwh_base,
                    $cout_conso_base
                )
            ),
            
            'hc' => array(
                'total_annuel' => (int)round($cout_total_hc),
                'total_mensuel' => (int)round($cout_total_hc / 12),
                'abonnement_mensuel' => $abo_hc_mensuel,
                'prix_kwh_hp' => $prix_kwh_hp,
                'prix_kwh_hc' => $prix_kwh_hc,
                'consommation_hp' => (int)round($consommation_hp),
                'consommation_hc' => (int)round($consommation_hc),
                'repartition_hp' => $repartition_hp * 100,
                'repartition_hc' => $repartition_hc * 100,
                'puissance_kva' => $puissanceRecommandee,
                'calcul_detail' => sprintf(
                    "Abo: %.2f€ × 12 + HP: %d kWh × %.4f€ + HC: %d kWh × %.4f€",
                    $abo_hc_mensuel,
                    round($consommation_hp),
                    $prix_kwh_hp,
                    round($consommation_hc),
                    $prix_kwh_hc
                )
            ),
            
            // NOUVEAU : Ajout du tarif TEMPO
            'tempo' => array(
                'total_annuel' => (int)round($cout_total_tempo),
                'total_mensuel' => (int)round($cout_total_tempo / 12),
                'abonnement_mensuel' => $abo_tempo_mensuel,
                'details_periodes' => array(
                    'bleu' => array(
                        'jours' => $jours_bleus,
                        'cout_total' => round($cout_bleu, 2),
                        'hp_kwh' => round($conso_bleu_hp),
                        'hc_kwh' => round($conso_bleu_hc),
                        'hp_prix' => $prix_tempo_bleu_hp,
                        'hc_prix' => $prix_tempo_bleu_hc
                    ),
                    'blanc' => array(
                        'jours' => $jours_blancs,
                        'cout_total' => round($cout_blanc, 2),
                        'hp_kwh' => round($conso_blanc_hp),
                        'hc_kwh' => round($conso_blanc_hc),
                        'hp_prix' => $prix_tempo_blanc_hp,
                        'hc_prix' => $prix_tempo_blanc_hc
                    ),
                    'rouge' => array(
                        'jours' => $jours_rouges,
                        'cout_total' => round($cout_rouge, 2),
                        'hp_kwh' => round($conso_rouge_hp),
                        'hc_kwh' => round($conso_rouge_hc),
                        'hp_prix' => $prix_tempo_rouge_hp,
                        'hc_prix' => $prix_tempo_rouge_hc
                    )
                ),
                'puissance_kva' => $puissanceRecommandee,
                'calcul_detail' => sprintf(
                    "Abo: %.2f€ × 12 + Bleu: %.2f€ + Blanc: %.2f€ + Rouge: %.2f€",
                    $abo_tempo_mensuel,
                    $cout_bleu,
                    $cout_blanc,
                    $cout_rouge
                )
            ),
            
            'economie_potentielle' => abs(max($tarifs) - min($tarifs)),
            'tarif_recommande' => $tarif_recommande
        );
    }


    // ==========================================
    // FONCTIONS CALCUL DE PUISSANCE
    // ==========================================

    private function calculatePuissanceChauffage($data) {
        $typeChauffage = $data['type_chauffage'] ?? '';
        $surface = floatval($data['surface']);
        
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (!in_array($typeChauffage, $chauffagesElectriques)) {
            return 0;
        }
        
        $puissance_m2 = $this->getConfigValue('chauffage_m2_puissance', 50);
        $simultaneite = $this->getConfigValue('chauffage_m2_simultaneite', 80) / 100;
        
        // Calcul : (surface × puissance/m² × simultanéité) / 1000 / 0.95
        $puissance = ($surface * $puissance_m2 * $simultaneite) / 1000 / 0.95;
        
        return $puissance;
    }

    private function calculatePuissanceEauChaude($data) {
        $eauChaude = $data['eau_chaude'] ?? 'non';
        
        if ($eauChaude !== 'oui') {
            return 0;
        }
        
        $puissance = $this->getConfigValue('chauffe_eau_puissance', 2400);
        $simultaneite = $this->getConfigValue('chauffe_eau_simultaneite', 30) / 100;
        
        // Calcul : (puissance × simultanéité) / 1000 / 0.95
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        return $puissance_kw;
    }

    private function calculatePuissanceElectromenagers($data) {
        $electromenagers = $data['electromenagers'] ?? array();
        $type_cuisson = $data['type_cuisson'] ?? '';
        $puissance_totale = 0;
        
        $equipements_config = array(
            'lave_linge' => 'lave_linge',
            'four' => 'four',
            'seche_linge' => 'seche_linge',
            'lave_vaisselle' => 'lave_vaisselle',
            'cave_a_vin' => 'cave_a_vin',
            'refrigerateur' => 'refrigerateur',
            'congelateur' => 'congelateur'
        );
        
        foreach ($electromenagers as $equipement) {
            if (isset($equipements_config[$equipement])) {
                $config_key = $equipements_config[$equipement];
                $puissance = $this->getConfigValue($config_key . '_puissance', 0);
                $simultaneite = $this->getConfigValue($config_key . '_simultaneite', 50) / 100;
                
                if ($puissance > 0) {
                    $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
                    $puissance_totale += $puissance_kw;
                }
            }
        }
        
        // Ajouter la plaque de cuisson
        if ($type_cuisson === 'plaque_induction' || $type_cuisson === 'induction') {
            $puissance = $this->getConfigValue('plaque_induction_puissance', 3500);
            $simultaneite = $this->getConfigValue('plaque_induction_simultaneite', 30) / 100;
            $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
            $puissance_totale += $puissance_kw;
        } elseif ($type_cuisson === 'plaque_vitroceramique' || $type_cuisson === 'vitroceramique') {
            $puissance = $this->getConfigValue('plaque_vitroceramique_puissance', 3000);
            $simultaneite = $this->getConfigValue('plaque_vitroceramique_simultaneite', 30) / 100;
            $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
            $puissance_totale += $puissance_kw;
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceMultimedia($data) {
        // TV/PC/Box toujours inclus
        $puissance = $this->getConfigValue('tv_pc_box_puissance', 500);
        $simultaneite = $this->getConfigValue('tv_pc_box_simultaneite', 80) / 100;
        
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        return $puissance_kw;
    }

    private function calculatePuissanceEquipementsSpeciaux($data) {
        $equipementsSpeciaux = $data['equipements_speciaux'] ?? array();
        $piscine = $data['piscine'] ?? 'non';
        $puissance_totale = 0;
        
        // Piscine
        if ($piscine === 'simple' || $piscine === 'chauffee') {
            $puissance = $this->getConfigValue('piscine_puissance', 2500);
            $simultaneite = $this->getConfigValue('piscine_simultaneite', 80) / 100;
            $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
            $puissance_totale += $puissance_kw;
        }
        
        // Équipements spéciaux
        $equipements_config = array(
            'spa_jacuzzi' => array('puissance' => 'spa_jacuzzi_puissance', 'simultaneite' => 'spa_jacuzzi_simultaneite'),
            'voiture_electrique' => array('puissance' => 'voiture_electrique_puissance', 'simultaneite' => 'voiture_electrique_simultaneite'),
            'aquarium' => array('puissance' => 'aquarium_puissance', 'simultaneite' => 'aquarium_simultaneite'),
            'climatiseur_mobile' => array('puissance' => 'climatiseur_mobile_puissance', 'simultaneite' => 'climatiseur_mobile_simultaneite')
        );
        
        foreach ($equipementsSpeciaux as $equipement) {
            if (isset($equipements_config[$equipement])) {
                $config = $equipements_config[$equipement];
                $puissance = $this->getConfigValue($config['puissance'], 0);
                $simultaneite = $this->getConfigValue($config['simultaneite'], 50) / 100;
                
                if ($puissance > 0) {
                    $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
                    $puissance_totale += $puissance_kw;
                }
            }
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceEclairage($data) {
        $puissance = $this->getConfigValue('eclairage_puissance', 500);
        $simultaneite = $this->getConfigValue('eclairage_simultaneite', 80) / 100;
        
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        return $puissance_kw;
    }
    
    // ==========================================
    // FONCTIONS UTILITAIRES
    // ==========================================
    
    private function calculatePuissanceRecommandee($puissanceTotaleRetenue) {
    // Basé sur la puissance retenue avec marge de sécurité
        $puissanceNecessaire = ceil($puissanceTotaleRetenue * 1.1); // Marge 10%
        
        // Pour 13.93 kW -> 15.3 kW -> recommander 18 kVA
        if ($puissanceNecessaire <= 3) return 3;
        if ($puissanceNecessaire <= 6) return 6;
        if ($puissanceNecessaire <= 9) return 9;
        if ($puissanceNecessaire <= 12) return 12;
        if ($puissanceNecessaire <= 15) return 15;
        if ($puissanceNecessaire <= 18) return 18;
        if ($puissanceNecessaire <= 24) return 24;
        if ($puissanceNecessaire <= 30) return 30;
        return 36;
    }
    
    private function getCoefficientEquipement($equipement, $nbPersonnes) {
        // Limiter à 6 personnes max
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        if ($nbPersonnes < 1) $nbPersonnes = 1;
        
        // Construire la clé
        $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
        
        $coefficient = $this->getConfigValue($coeff_key, 1.0);
        
        return $coefficient;
    }
    
    
    private function getIsolationConfigSuffix($isolation) {
        $mapping = array(
            'avant_1980' => 'mauvaise',
            '1980_2000' => 'moyenne', 
            'apres_2000' => 'bonne',
            'renovation' => 'tres_bonne'
        );
        return $mapping[$isolation] ?? 'moyenne';
    }
    
    
    private function getConfigValue($key, $default = 0) {
        return isset($this->configData[$key]) ? $this->configData[$key] : $default;
    }
    
    
    private function getConfigSummary() {
        return array(
            'nb_parametres' => count($this->configData),
            'derniere_maj' => date('Y-m-d'),
            'version_config' => '2.0'
        );
    }
    
    /**
     * Logging pour debug
     */
    private function logDebug($message) {
        if ($this->debugMode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("[HTIC CALCULATEUR] " . $message);
        }
        
        // Debug dans le navigateur UNIQUEMENT si pas une requête AJAX OU si debug explicite demandé
        if (!wp_doing_ajax() && $this->debugMode && isset($_GET['debug_html'])) {
            echo "<!-- DEBUG: " . esc_html($message) . " -->\n";
        }
    }
    
    /**
     * Retourner une erreur
     */
    private function returnError($message) {
        $this->logDebug("❌ ERREUR: " . $message);
        
        return array(
            'success' => false,
            'error' => $message
        );
    }
}

/**
 * Fonction d'entrée pour les appels AJAX
 */
function htic_calculateur_elec_residentiel($userData, $configData) {
    $calculateur = new HticCalculateurElecResidentiel($userData, $configData, true);
    return $calculateur->calculate();
}

?>