<?php
/**
 * Calculateur Électricité Résidentiel - Version Finale Restructurée
 * Fichier: includes/calculateur-elec-residentiel.php
 * Version: 3.0 - Code restructuré et optimisé
 */

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
    

    public function calculate() {
        try {
            
            $validatedData = $this->validateAndExtractData();
            if (!$validatedData) {
                return $this->returnError("Données invalides ou incomplètes");
            }
     
            $puissanceForcee = isset($this->userData['puissance_forcee']) ? intval($this->userData['puissance_forcee']) : null;
            $tarifForce = isset($this->userData['tarif_force']) ? $this->userData['tarif_force'] : null;
            $results = $this->performCompleteCalculation($validatedData, $puissanceForcee, $tarifForce);
            
            return array(
                'success' => true,
                'data' => $results
            );
            
        } catch (Exception $e) {
            $this->logDebug("Exception: " . $e->getMessage());
            return $this->returnError("Erreur technique: " . $e->getMessage());
        } catch (Error $e) {
            $this->logDebug("Erreur fatale: " . $e->getMessage());
            return $this->returnError("Erreur critique lors du calcul");
        }
    }
    
    private function validateAndExtractData() {
        $extractedData = array();
 
        $extractedData['type_logement'] = $this->extractValue('type_logement', 'string');
        $extractedData['surface'] = $this->extractValue('surface', 'int');
        $extractedData['nb_personnes'] = $this->extractValue('nb_personnes', 'int');
        $extractedData['type_chauffage'] = $this->extractValue('type_chauffage', 'string');
        $extractedData['isolation'] = $this->extractValue('isolation', 'string');
        $extractedData['electromenagers'] = $this->extractValue('electromenagers', 'array');
        $extractedData['type_cuisson'] = $this->extractValue('type_cuisson', 'string');
        $extractedData['eau_chaude'] = $this->extractValue('eau_chaude', 'string');
        $extractedData['type_eclairage'] = $this->extractValue('type_eclairage', 'string');
        $extractedData['piscine'] = $this->extractValue('piscine', 'string');
        $extractedData['equipements_speciaux'] = $this->extractValue('equipements_speciaux', 'array');
     
        $requiredFields = array('type_logement', 'surface', 'nb_personnes', 'type_chauffage', 'type_cuisson', 'eau_chaude', 'type_eclairage', 'piscine');
        
        foreach ($requiredFields as $field) {
            if (empty($extractedData[$field]) && $extractedData[$field] !== '0') {
                return false;
            }
        }
        
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (in_array($extractedData['type_chauffage'], $chauffagesElectriques) && empty($extractedData['isolation'])) {
            return false;
        }
        
        if ($extractedData['surface'] < 20 || $extractedData['surface'] > 500) {
            return false;
        }
        
        if ($extractedData['nb_personnes'] < 1 || $extractedData['nb_personnes'] > 6) {
            return false;
        }
        
        return $extractedData;
    }
    
    private function extractValue($key, $type = 'string') {
        $value = isset($this->userData[$key]) ? $this->userData[$key] : null;
        
        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'array':
                return is_array($value) ? $value : array();
            case 'string':
            default:
                return strval($value);
        }
    }
    
    private function performCompleteCalculation($data, $puissanceForcee = null, $tarifForce = null) {
        $surface = (int)$data['surface'];
        $nbPersonnes = (int)$data['nb_personnes'];
        $typeLogement = $data['type_logement'];
        
        $chauffageDetails = $this->calculateChauffage($data);
        $eauChaudeDetails = $this->calculateEauChaude($data);
        $electromenagersDetails = $this->calculateElectromenagers($data);
        $eclairageDetails = $this->calculateEclairage($data);
        $multimediaDetails = $this->calculateMultimedia($data);
        $equipementsDetails = $this->calculateEquipementsSpeciaux($data);
        
        $consommationBrute = $chauffageDetails['total'] + $eauChaudeDetails['total'] + 
                            $electromenagersDetails['total'] + $eclairageDetails['total'] + 
                            $multimediaDetails['total'] + $equipementsDetails['total'];
        
        $coeffLogement = ($typeLogement === 'appartement') ? 0.95 : 1.0;
        $consommationAnnuelle = $consommationBrute * $coeffLogement;
        
        $puissanceCalculee = $this->calculatePuissanceTotale($data, $surface);
        $puissanceRecommandee = $puissanceForcee ?: $this->getPuissanceStandard($puissanceCalculee);
        
        $tarifs = $this->calculateAllTarifs($consommationAnnuelle, $puissanceRecommandee, $tarifForce);
        $tarifRecommande = $this->determineMeilleurTarif($tarifs);
        
        return array(
            'consommation_annuelle' => (int)round($consommationAnnuelle),
            'puissance_recommandee' => $puissanceRecommandee,
            'tarif_recommande' => $tarifRecommande,
            'tarifs' => $tarifs,
            'repartition' => array(
                'chauffage' => (int)round($chauffageDetails['total']),
                'eau_chaude' => (int)round($eauChaudeDetails['total']),
                'electromenagers' => (int)round($electromenagersDetails['total']),
                'eclairage' => (int)round($eclairageDetails['total']),
                'multimedia' => (int)round($multimediaDetails['total']),
                'tv_pc_box' => (int)round($multimediaDetails['total']),
                'autres' => 0,
                'equipements_speciaux' => $equipementsDetails['repartition']
            ),
            'details_calcul' => array(
                'chauffage' => $chauffageDetails,
                'eau_chaude' => $eauChaudeDetails,
                'electromenagers' => $electromenagersDetails,
                'eclairage' => $eclairageDetails,
                'multimedia' => $multimediaDetails,
                'tv_pc_box' => $multimediaDetails,
                'equipements_speciaux' => $equipementsDetails,
                'coefficients' => array('logement' => $coeffLogement),
                'methode_calcul' => 'HTIC Simulateur v3.0',
                'timestamp' => date('Y-m-d H:i:s')
            ),
            'puissances_retenues' => $this->getPuissanceDetails($data, $surface, $puissanceCalculee, $puissanceRecommandee),
            'recap' => $data
        );
    }
    
    private function calculateChauffage($data) {
        $typeChauffage = $data['type_chauffage'] ?? '';
        $typeLogement = $data['type_logement'] ?? 'maison';
        $surface = floatval($data['surface']);
        $isolation = $data['isolation'] ?? '';
        
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
        
        $isolationMapping = array(
            'avant_1980' => 'mauvaise',
            '1980_2000' => 'moyenne', 
            'apres_2000' => 'bonne',
            'renovation' => 'tres_bonne'
        );
        $isolationNormalisee = $isolationMapping[$isolation] ?? 'moyenne';
        
        $config_key = $typeLogement . '_' . $typeChauffage . '_' . $isolationNormalisee;
        $conso_par_m2 = $this->getConfigValue($config_key, 0);
        
        if ($conso_par_m2 == 0 && $typeChauffage === 'clim_reversible') {
            $config_key_alt = $typeLogement . '_clim_' . $isolationNormalisee;
            $conso_par_m2 = $this->getConfigValue($config_key_alt, 0);
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
        }
        
        return array(
            'total' => 0,
            'erreur' => "Configuration manquante pour {$config_key}",
            'methode' => 'Erreur de configuration',
            'calcul' => 'Impossible de calculer - configuration manquante',
            'explication' => "Données de chauffage manquantes pour ce type de configuration"
        );
    }
    
    private function calculateEauChaude($data) {
        $eauChaude = $data['eau_chaude'] ?? 'non';
        $nbPersonnes = min(intval($data['nb_personnes']), 6);
        
        if ($eauChaude !== 'oui') {
            return array(
                'total' => 0,
                'methode' => 'Pas d\'eau chaude électrique',
                'calcul' => 'Eau chaude non électrique - consommation: 0 kWh/an',
                'explication' => 'Production d\'eau chaude par autre énergie'
            );
        }
        
        $conso_base = $this->getConfigValue('chauffe_eau', 900);
        $coeff_key = 'coeff_chauffe_eau_' . $nbPersonnes;
        $coefficient = $this->getConfigValue($coeff_key, 1);
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
    }
    
    private function calculateElectromenagers($data) {
        $electromenagers = $data['electromenagers'] ?? array();
        $nbPersonnes = min(intval($data['nb_personnes']), 6);
        $type_cuisson = $data['type_cuisson'] ?? '';
        
        $consommation_totale = 0;
        $details_calcul = array();
        
        $electromenagers_disponibles = array(
            'lave_linge', 'four', 'seche_linge', 'lave_vaisselle', 
            'cave_a_vin', 'refrigerateur', 'congelateur'
        );
        
        foreach ($electromenagers as $equipement) {
            if (in_array($equipement, $electromenagers_disponibles)) {
                $conso_base = $this->getConfigValue($equipement, 0);
                $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
                $coefficient = $this->getConfigValue($coeff_key, 1);
                
                if ($conso_base > 0) {
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
        }
        
        if ($type_cuisson === 'induction' || $type_cuisson === 'plaque_induction') {
            $consommation_totale += $this->addCuissonEquipement('plaque_induction', $nbPersonnes, $details_calcul);
        } elseif ($type_cuisson === 'vitroceramique' || $type_cuisson === 'plaque_vitroceramique') {
            $consommation_totale += $this->addCuissonEquipement('plaque_vitroceramique', $nbPersonnes, $details_calcul);
        }
  
        $forfait = $this->getConfigValue('forfait_petits_electromenagers', 150);
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
    
    private function addCuissonEquipement($equipement, $nbPersonnes, &$details_calcul) {
        $conso_base = $this->getConfigValue($equipement, ($equipement === 'plaque_induction' ? 180 : 250));
        $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
        $coefficient = $this->getConfigValue($coeff_key, 1);
        $consommation = $conso_base * $coefficient;
        
        $details_calcul[$equipement] = array(
            'nom' => $this->getEquipementLabel($equipement),
            'base_kwh' => $conso_base,
            'coefficient' => $coefficient,
            'final_kwh' => $consommation,
            'coeff_key' => $coeff_key
        );
        
        return $consommation;
    }
    
    private function calculateEclairage($data) {
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
    
    private function calculateMultimedia($data) {
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
            'explication' => 'Consommation TV, ordinateur, box internet incluse automatiquement'
        );
    }
    
    private function calculateEquipementsSpeciaux($data) {
        $equipementsSpeciaux = $data['equipements_speciaux'] ?? array();
        $piscine = $data['piscine'] ?? 'non';
        
        $repartition = array();
        $total = 0;
        $details = array();
        
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
            'explication' => 'Consommations spécifiques selon les équipements de confort et loisirs'
        );
    }
    
    private function calculatePuissanceTotale($data, $surface) {
        $puissanceChauffage = $this->calculatePuissanceChauffage($data, $surface);
        $puissanceEauChaude = $this->calculatePuissanceEauChaude($data);
        $puissanceElectromenagers = $this->calculatePuissanceElectromenagers($data);
        $puissanceMultimedia = $this->calculatePuissanceMultimedia($data);
        $puissanceEquipements = $this->calculatePuissanceEquipements($data);
        $puissanceEclairage = $this->calculatePuissanceEclairage($data);
        
        return $puissanceChauffage + $puissanceEauChaude + $puissanceElectromenagers + 
            $puissanceMultimedia + $puissanceEquipements + $puissanceEclairage;
    }
    
    private function calculatePuissanceChauffage($data, $surface) {
        $typeChauffage = $data['type_chauffage'] ?? '';
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        
        if (!in_array($typeChauffage, $chauffagesElectriques)) {
            return 0;
        }
        
        $puissance_m2 = $this->getConfigValue('chauffage_m2_puissance', 50);
        $simultaneite = $this->getConfigValue('chauffage_m2_simultaneite', 80) / 100;
        $facteur_securite = 0.95;
        
        return ($surface * $puissance_m2 * $simultaneite / 1000) / $facteur_securite;
    }
    
    private function calculatePuissanceEauChaude($data) {
        if (($data['eau_chaude'] ?? 'non') !== 'oui') {
            return 0;
        }
        
        $puissance = $this->getConfigValue('chauffe_eau_puissance', 2400);
        $simultaneite = $this->getConfigValue('chauffe_eau_simultaneite', 30) / 100;
        
        return ($puissance * $simultaneite) / 1000 / 0.95;
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
                    $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
                }
            }
        }
        
        if ($type_cuisson === 'plaque_induction' || $type_cuisson === 'induction') {
            $puissance = $this->getConfigValue('plaque_induction_puissance', 3500);
            $simultaneite = $this->getConfigValue('plaque_induction_simultaneite', 30) / 100;
            $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
        } elseif ($type_cuisson === 'plaque_vitroceramique' || $type_cuisson === 'vitroceramique') {
            $puissance = $this->getConfigValue('plaque_vitroceramique_puissance', 3000);
            $simultaneite = $this->getConfigValue('plaque_vitroceramique_simultaneite', 30) / 100;
            $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceEclairage($data) {
        $puissance = $this->getConfigValue('eclairage_puissance', 8);
        $simultaneite = $this->getConfigValue('eclairage_simultaneite', 80) / 100;
        
        return ($puissance * $simultaneite) / 1000 / 0.95;
    }
    
    private function calculatePuissanceEquipements($data) {
        $equipementsSpeciaux = $data['equipements_speciaux'] ?? array();
        $piscine = $data['piscine'] ?? 'non';
        $puissance_totale = 0;
        
        if ($piscine === 'simple' || $piscine === 'chauffee') {
            $puissance = $this->getConfigValue('piscine_puissance', 2500);
            $simultaneite = $this->getConfigValue('piscine_simultaneite', 80) / 100;
            $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
        }
   
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
                    $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
                }
            }

            if (isset($data['chauffage_appoint']) && $data['chauffage_appoint'] === 'oui') {
                $puissance = 2100;
                $simultaneite = 0.8;
                $puissance_totale += ($puissance * $simultaneite) / 1000 / 0.95;
            }
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceMultimedia($data) {
        $puissance = $this->getConfigValue('tv_pc_box_puissance', 500);
        $simultaneite = $this->getConfigValue('tv_pc_box_simultaneite', 80) / 100;
        
        return ($puissance * $simultaneite) / 1000 / 0.95;
    }
    
    private function getPuissanceStandard($puissanceCalculee) {
        $puissancesStandard = array(3, 6, 9, 12, 15, 18, 24, 30, 36);
        
        foreach ($puissancesStandard as $p) {
            if ($p >= $puissanceCalculee) {
                return $p;
            }
        }
        
        return 36;
    }
    
    private function getPuissanceDetails($data, $surface, $puissanceCalculee, $puissanceRecommandee) {
        return array(
            'chauffage' => round($this->calculatePuissanceChauffage($data, $surface), 2),
            'eau_chaude' => round($this->calculatePuissanceEauChaude($data), 2),
            'electromenagers' => round($this->calculatePuissanceElectromenagers($data), 2),
            'multimedia' => round($this->calculatePuissanceMultimedia($data), 2),
            'equipements_speciaux' => round($this->calculatePuissanceEquipements($data), 2),
            'eclairage' => round($this->calculatePuissanceEclairage($data), 2),
            'total' => round($puissanceCalculee, 2),
            'calculee' => round($puissanceCalculee, 2),
            'recommandee_calculee' => $this->getPuissanceStandard($puissanceCalculee),
            'retenue' => $puissanceRecommandee
        );
    }
    
    private function calculateAllTarifs($consommationAnnuelle, $puissanceRecommandee, $tarifForce = null) {
        if ($tarifForce) {
            return $this->calculateTarifForce($consommationAnnuelle, $puissanceRecommandee, $tarifForce);
        }
        
        return $this->calculateTarifsComplets($consommationAnnuelle, $puissanceRecommandee);
    }
    
    private function calculateTarifForce($consommationAnnuelle, $puissanceRecommandee, $tarifForce) {
        $tarifs = array(
            'base' => array('total_annuel' => 0, 'total_mensuel' => 0),
            'hc' => array('total_annuel' => 0, 'total_mensuel' => 0),
            'tempo' => array('total_annuel' => 0, 'total_mensuel' => 0)
        );
        
        switch ($tarifForce) {
            case 'base':
                $tarifs['base'] = $this->calculateTarifBase($consommationAnnuelle, $puissanceRecommandee);
                break;
            case 'hc':
                $tarifs['hc'] = $this->calculateTarifHC($consommationAnnuelle, $puissanceRecommandee);
                break;
            case 'tempo':
                $tarifs['tempo'] = $this->calculateTarifTempo($consommationAnnuelle, $puissanceRecommandee);
                break;
        }
        
        return $tarifs;
    }
    
    private function calculateTarifsComplets($consommationAnnuelle, $puissanceRecommandee) {
        return array(
            'base' => $this->calculateTarifBase($consommationAnnuelle, $puissanceRecommandee),
            'hc' => $this->calculateTarifHC($consommationAnnuelle, $puissanceRecommandee),
            'tempo' => $this->calculateTarifTempo($consommationAnnuelle, $puissanceRecommandee)
        );
    }
    
    private function calculateTarifBase($consommationAnnuelle, $puissanceRecommandee) {
        $abo_mensuel = $this->getConfigValue('base_abo_' . $puissanceRecommandee, 20);
        $prix_kwh = $this->getConfigValue('base_kwh_' . $puissanceRecommandee, 0.2516);
        
        $cout_abo_annuel = $abo_mensuel * 12;
        $cout_conso = $consommationAnnuelle * $prix_kwh;
        $cout_total = $cout_abo_annuel + $cout_conso;
        
        return array(
            'total_annuel' => (int)round($cout_total),
            'total_mensuel' => (int)round($cout_total / 12),
            'abonnement_mensuel' => $abo_mensuel,
            'prix_kwh' => $prix_kwh,
            'puissance_kva' => $puissanceRecommandee,
            'cout_abonnement' => $cout_abo_annuel,
            'cout_consommation' => $cout_conso
        );
    }
    
    private function calculateTarifHC($consommationAnnuelle, $puissanceRecommandee) {
        $abo_mensuel = $this->getConfigValue('hc_abo_' . $puissanceRecommandee, 22);
        $prix_hp = $this->getConfigValue('hc_hp_' . $puissanceRecommandee, 0.27);
        $prix_hc = $this->getConfigValue('hc_hc_' . $puissanceRecommandee, 0.2068);
        
        $repartition_hp = $this->getConfigValue('repartition_hp', 60) / 100;
        $repartition_hc = $this->getConfigValue('repartition_hc', 40) / 100;
        
        $conso_hp = $consommationAnnuelle * $repartition_hp;
        $conso_hc = $consommationAnnuelle * $repartition_hc;
        
        $cout_abo_annuel = $abo_mensuel * 12;
        $cout_total = $cout_abo_annuel + ($conso_hp * $prix_hp) + ($conso_hc * $prix_hc);
        
        return array(
            'total_annuel' => (int)round($cout_total),
            'total_mensuel' => (int)round($cout_total / 12),
            'abonnement_mensuel' => $abo_mensuel,
            'prix_kwh_hp' => $prix_hp,
            'prix_kwh_hc' => $prix_hc,
            'consommation_hp' => (int)round($conso_hp),
            'consommation_hc' => (int)round($conso_hc),
            'puissance_kva' => $puissanceRecommandee
        );
    }
    
    private function calculateTarifTempo($consommationAnnuelle, $puissanceRecommandee) {
        $abo_mensuel = $this->getConfigValue('tempo_abo_' . $puissanceRecommandee, 25);
        
        $prix_bleu_hp = $this->getConfigValue('tempo_bleu_hp_' . $puissanceRecommandee, 0.1609);
        $prix_bleu_hc = $this->getConfigValue('tempo_bleu_hc_' . $puissanceRecommandee, 0.1296);
        $prix_blanc_hp = $this->getConfigValue('tempo_blanc_hp_' . $puissanceRecommandee, 0.1894);
        $prix_blanc_hc = $this->getConfigValue('tempo_blanc_hc_' . $puissanceRecommandee, 0.1486);
        $prix_rouge_hp = $this->getConfigValue('tempo_rouge_hp_' . $puissanceRecommandee, 0.7562);
        $prix_rouge_hc = $this->getConfigValue('tempo_rouge_hc_' . $puissanceRecommandee, 0.1568);
        
        $jours_bleus = $this->getConfigValue('tempo_jours_bleus', 300);
        $jours_blancs = $this->getConfigValue('tempo_jours_blancs', 43);
        $jours_rouges = $this->getConfigValue('tempo_jours_rouges', 22);
        
        $repartition_hp = $this->getConfigValue('repartition_hp', 60) / 100;
        $repartition_hc = $this->getConfigValue('repartition_hc', 40) / 100;
        
        $ratio_bleus = $jours_bleus / 365;
        $ratio_blancs = $jours_blancs / 365;
        $ratio_rouges = $jours_rouges / 365;
        
        $cout_bleu = ($consommationAnnuelle * $ratio_bleus * $repartition_hp * $prix_bleu_hp) + 
                     ($consommationAnnuelle * $ratio_bleus * $repartition_hc * $prix_bleu_hc);
        
        $cout_blanc = ($consommationAnnuelle * $ratio_blancs * $repartition_hp * $prix_blanc_hp) + 
                      ($consommationAnnuelle * $ratio_blancs * $repartition_hc * $prix_blanc_hc);
        
        $cout_rouge = ($consommationAnnuelle * $ratio_rouges * $repartition_hp * $prix_rouge_hp) + 
                      ($consommationAnnuelle * $ratio_rouges * $repartition_hc * $prix_rouge_hc);
        
        $cout_total = ($abo_mensuel * 12) + $cout_bleu + $cout_blanc + $cout_rouge;
        
        return array(
            'total_annuel' => (int)round($cout_total),
            'total_mensuel' => (int)round($cout_total / 12),
            'abonnement_mensuel' => $abo_mensuel,
            'puissance_kva' => $puissanceRecommandee,
            'details_periodes' => array(
                'bleu' => array('jours' => $jours_bleus, 'cout_total' => round($cout_bleu, 2)),
                'blanc' => array('jours' => $jours_blancs, 'cout_total' => round($cout_blanc, 2)),
                'rouge' => array('jours' => $jours_rouges, 'cout_total' => round($cout_rouge, 2))
            )
        );
    }
    
    private function determineMeilleurTarif($tarifs) {
        $totaux = array(
            'base' => $tarifs['base']['total_annuel'] ?? 0,
            'hc' => $tarifs['hc']['total_annuel'] ?? 0,
            'tempo' => $tarifs['tempo']['total_annuel'] ?? 0
        );
        
        $totaux = array_filter($totaux, function($value) { return $value > 0; });
        
        if (empty($totaux)) {
            return 'base';
        }
        
        return array_keys($totaux, min($totaux))[0];
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
            'plaque_vitroceramique' => 'Plaque vitrocéramique'
        );
        
        return $labels[$equipement] ?? ucfirst(str_replace('_', ' ', $equipement));
    }
    
    private function getConfigValue($key, $default = 0) {
        return isset($this->configData[$key]) ? floatval($this->configData[$key]) : $default;
    }
    
    private function logDebug($message) {
        if ($this->debugMode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("[HTIC CALCULATEUR] " . $message);
        }
    }
    
    private function returnError($message) {
        return array(
            'success' => false,
            'error' => $message
        );
    }
}

function htic_calculateur_elec_residentiel($userData, $configData) {
    $calculateur = new HticCalculateurElecResidentiel($userData, $configData, true);
    return $calculateur->calculate();
}
?>