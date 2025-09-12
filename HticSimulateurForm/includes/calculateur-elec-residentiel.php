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
        
        $this->logDebug("=== CALCULATEUR ÉLECTRICITÉ RÉSIDENTIEL INITIALISÉ ===");
        $this->logDebug("Données utilisateur reçues: " . count($userData) . " champs");
        $this->logDebug("Configuration disponible: " . count($configData) . " paramètres");
    }
    
    /**
     * Point d'entrée principal pour le calcul
     */
    public function calculate() {
        $this->logDebug("🚀 DÉBUT DU CALCUL COMPLET");
        
        // Récupération et validation des données
        $validatedData = $this->validateAndExtractData();
        
        if (!$validatedData) {
            return $this->returnError("Données invalides ou incomplètes");
        }
        
        // Affichage des données récupérées pour debug
        $this->displayReceivedData($validatedData);
        
        // Calcul détaillé complet
        $results = $this->performCompleteCalculation($validatedData);
        
        $this->logDebug("✅ CALCUL TERMINÉ - Total: " . $results['consommation_annuelle'] . " kWh/an");
        
        return array(
            'success' => true,
            'data' => $results
        );
    }
    
    /**
     * Validation et extraction des données utilisateur
     */
    private function validateAndExtractData() {
        $this->logDebug("=== VALIDATION ET EXTRACTION DES DONNÉES ===");
        
        $extractedData = array();
        
        // ÉTAPE 1: Informations du logement
        $extractedData['type_logement'] = $this->extractValue('type_logement', 'string');
        $extractedData['surface'] = $this->extractValue('surface', 'int');
        $extractedData['nb_personnes'] = $this->extractValue('nb_personnes', 'int');
        
        // ÉTAPE 2: Chauffage et isolation
        $extractedData['type_chauffage'] = $this->extractValue('type_chauffage', 'string');
        $extractedData['isolation'] = $this->extractValue('isolation', 'string');
        
        // ÉTAPE 3: Électroménagers
        $extractedData['electromenagers'] = $this->extractValue('electromenagers', 'array');
        $extractedData['type_cuisson'] = $this->extractValue('type_cuisson', 'string');
        
        // ÉTAPE 4: Eau chaude
        $extractedData['eau_chaude'] = $this->extractValue('eau_chaude', 'string');
        
        // ÉTAPE 5: Éclairage
        $extractedData['type_eclairage'] = $this->extractValue('type_eclairage', 'string');
        
        // ÉTAPE 6: Équipements spéciaux
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
        
        $this->logDebug("✅ Validation des données: OK");
        
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
        
        $this->logDebug("Extraction: {$key} = " . (is_array($value) ? '[' . implode(', ', $value) . ']' : $value));
        
        return $value;
    }
    
    /**
     * Affichage détaillé des données récupérées
     */
    private function displayReceivedData($data) {
        $this->logDebug("=== DONNÉES RÉCUPÉRÉES DU FORMULAIRE ===");
        
        $this->logDebug("ÉTAPE 1 - LOGEMENT:");
        $this->logDebug("- Type de logement: " . $data['type_logement']);
        $this->logDebug("- Surface: " . $data['surface'] . " m²");
        $this->logDebug("- Nombre de personnes: " . $data['nb_personnes']);
        
        $this->logDebug("ÉTAPE 2 - CHAUFFAGE:");
        $this->logDebug("- Type de chauffage: " . $data['type_chauffage']);
        if (!empty($data['isolation'])) {
            $this->logDebug("- Isolation: " . $data['isolation']);
        } else {
            $this->logDebug("- Isolation: Non applicable (pas de chauffage électrique)");
        }
        
        $this->logDebug("ÉTAPE 3 - ÉLECTROMÉNAGERS:");
        $this->logDebug("- Électroménagers sélectionnés: " . (empty($data['electromenagers']) ? 'Aucun' : implode(', ', $data['electromenagers'])));
        $this->logDebug("- Type de cuisson: " . $data['type_cuisson']);
        
        $this->logDebug("ÉTAPE 4 - EAU CHAUDE:");
        $this->logDebug("- Eau chaude électrique: " . $data['eau_chaude']);
        
        $this->logDebug("ÉTAPE 5 - ÉCLAIRAGE:");
        $this->logDebug("- Type d'éclairage: " . $data['type_eclairage']);
        
        $this->logDebug("ÉTAPE 6 - ÉQUIPEMENTS SPÉCIAUX:");
        $this->logDebug("- Piscine: " . $data['piscine']);
        if (!empty($data['equipements_speciaux'])) {
            $this->logDebug("- Équipements spéciaux: " . implode(', ', $data['equipements_speciaux']));
        } else {
            $this->logDebug("- Équipements spéciaux: Aucun");
        }
        if (!empty($data['preference_tarif'])) {
            $this->logDebug("- Préférence tarifaire: " . $data['preference_tarif']);
        }
        
        $this->logDebug("=== CONFIGURATION DISPONIBLE ===");
        if (!empty($this->configData)) {
            $this->logDebug("Configuration chargée avec " . count($this->configData) . " paramètres");
            
            // Afficher quelques exemples de configuration
            if (isset($this->configData['puissance_defaut'])) {
                $this->logDebug("- Puissance par défaut: " . $this->configData['puissance_defaut'] . " kVA");
            }
            if (isset($this->configData['chauffe_eau'])) {
                $this->logDebug("- Consommation chauffe-eau: " . $this->configData['chauffe_eau'] . " kWh/an");
            }
        } else {
            $this->logDebug("⚠️ ATTENTION: Aucune configuration disponible");
        }
    }
    
    /**
     * CALCUL DÉTAILLÉ COMPLET
     */
    private function performCompleteCalculation($data) {
        $this->logDebug("=== CALCUL DÉTAILLÉ COMPLET EN COURS ===");
        
        $surface = (int)$data['surface'];
        $nbPersonnes = (int)$data['nb_personnes'];
        $typeLogement = $data['type_logement'];
        
        $this->logDebug("🏠 PARAMÈTRES DE BASE: {$typeLogement}, {$surface}m², {$nbPersonnes} personne(s)");
        
        // ==========================================
        // CALCULS DÉTAILLÉS AVEC TOUTES LES INFOS
        // ==========================================
        
        // 1. CHAUFFAGE
        $chauffageDetails = $this->calculateChauffageDetaille($data);
        $chauffageKwh = $chauffageDetails['total'];
        $this->logDebug("🔥 Chauffage calculé: {$chauffageKwh} kWh/an");
        
        // 2. EAU CHAUDE
        $eauChaudeDetails = $this->calculateEauChaudeDetaille($data);
        $eauChaudeKwh = $eauChaudeDetails['total'];
        $this->logDebug("💧 Eau chaude calculée: {$eauChaudeKwh} kWh/an");
        
        // 3. ÉLECTROMÉNAGERS
        $electromenagersDetails = $this->calculateElectromenagetDetaille($data);
        $electromenagersKwh = $electromenagersDetails['total'];
        $this->logDebug("🏠 Électroménagers calculés: {$electromenagersKwh} kWh/an");
        
        // 4. CUISSON
        $cuissonDetails = $this->calculateCuissonDetaille($data);
        $cuissonKwh = $cuissonDetails['total'];
        $this->logDebug("🍳 Cuisson calculée: {$cuissonKwh} kWh/an");
        
        // 5. ÉCLAIRAGE
        $eclairageDetails = $this->calculateEclairageDetaille($data);
        $eclairageKwh = $eclairageDetails['total'];
        $this->logDebug("💡 Éclairage calculé: {$eclairageKwh} kWh/an");
        
        // 6. MULTIMÉDIA
        $multimediaDetails = $this->calculateMultimediaDetaille($data);
        $multimediaKwh = $multimediaDetails['total'];
        $this->logDebug("📺 Multimédia calculé: {$multimediaKwh} kWh/an");
        
        // 7. ÉQUIPEMENTS SPÉCIAUX
        $equipementsDetails = $this->calculateEquipementsSpeciauxDetaille($data);
        $equipementsKwh = $equipementsDetails['total'];
        $this->logDebug("⚡ Équipements spéciaux calculés: {$equipementsKwh} kWh/an");
        
        // TOTAL GÉNÉRAL
        $consommationTotale = $chauffageKwh + $eauChaudeKwh + $electromenagersKwh + 
                             $cuissonKwh + $eclairageKwh + $multimediaKwh + $equipementsKwh;
        
        $this->logDebug("📊 CONSOMMATION TOTALE: {$consommationTotale} kWh/an");
        
        // CALCUL DES TARIFS
        $tarifsCalcules = $this->calculateTarifsDetaille($consommationTotale, $data);
        
        // PUISSANCE RECOMMANDÉE
        $puissanceRecommandee = $this->calculatePuissanceRecommandee($consommationTotale, $data);
        
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
                'cuisson' => (int)round($cuissonKwh),
                'eclairage' => (int)round($eclairageKwh),
                'multimedia' => (int)round($multimediaKwh),
                'tv_pc_box' => (int)round($multimediaKwh), // Alias pour rétrocompatibilité
                'autres' => 0, // Divers non catégorisé
                'equipements_speciaux' => $equipementsDetails['repartition'] // Détail par équipement
            ),
            
            // DÉTAILS COMPLETS DE CALCUL
            'details_calcul' => array(
                'chauffage' => $chauffageDetails,
                'eau_chaude' => $eauChaudeDetails,
                'electromenagers' => $electromenagersDetails,
                'cuisson' => $cuissonDetails,
                'eclairage' => $eclairageDetails,
                'multimedia' => $multimediaDetails,
                'tv_pc_box' => $multimediaDetails, // Alias
                'equipements_speciaux' => $equipementsDetails,
                'coefficients' => array(
                    'logement' => $typeLogement === 'appartement' ? 0.95 : 1.0,
                    'personnes' => $this->getCoefficientPersonnes($nbPersonnes),
                    'surface' => $this->getCoefficientSurface($surface)
                ),
                'methode_calcul' => 'HTIC Simulateur v2.0 - Calcul détaillé complet',
                'timestamp' => date('Y-m-d H:i:s'),
                'donnees_config_utilisees' => $this->getConfigSummary()
            ),
            
            // RÉCAPITULATIF DES DONNÉES UTILISATEUR (pour affichage)
            'recap' => $data
        );
    }
    
    // ==========================================
    // CALCULS DÉTAILLÉS PAR POSTE
    // ==========================================
    
    /**
     * Calcul détaillé du chauffage
     */
    private function calculateChauffageDetaille($data) {
        $typeChauffage = $data['type_chauffage'];
        $surface = (int)$data['surface'];
        $isolation = $data['isolation'] ?? '';
        $typeLogement = $data['type_logement'];
        
        $this->logDebug("🔥 CALCUL CHAUFFAGE: {$typeChauffage}, isolation: {$isolation}");
        
        // Si pas de chauffage électrique
        if ($typeChauffage === 'autre') {
            return array(
                'total' => 0,
                'methode' => 'Chauffage non électrique',
                'type_chauffage' => $typeChauffage,
                'consommation_m2' => 0,
                'surface_chauffee' => 0,
                'coefficient_logement' => 0,
                'coefficient_isolation' => 0,
                'calcul' => '0 kWh/an - Chauffage non électrique sélectionné',
                'explication' => 'Aucune consommation électrique car le chauffage principal n\'est pas électrique'
            );
        }
        
        // Récupération des données de config selon logement et chauffage
        $configPrefix = $typeLogement . '_' . $typeChauffage . '_';
        $isolationSuffix = $this->getIsolationConfigSuffix($isolation);
        
        $consommationM2 = $this->getConfigValue($configPrefix . $isolationSuffix, $this->getDefaultChauffage($typeChauffage, $isolation));
        
        // Coefficient logement (appartement généralement moins consommateur)
        $coefficientLogement = $typeLogement === 'appartement' ? 0.95 : 1.0;
        
        // Calcul final
        $consommationTotale = $surface * $consommationM2 * $coefficientLogement;
        
        $this->logDebug("Chauffage - Surface: {$surface}m², Conso/m²: {$consommationM2}, Coeff: {$coefficientLogement}");
        
        return array(
            'total' => $consommationTotale,
            'methode' => 'Surface × Consommation/m² × Coefficient logement',
            'type_chauffage' => $typeChauffage,
            'isolation' => $isolation,
            'consommation_m2' => $consommationM2,
            'surface_chauffee' => $surface,
            'coefficient_logement' => $coefficientLogement,
            'coefficient_isolation' => $this->getIsolationCoefficient($isolation),
            'calcul' => "{$surface} m² × {$consommationM2} kWh/m²/an × {$coefficientLogement} = " . round($consommationTotale) . " kWh/an",
            'explication' => "Consommation basée sur votre type de chauffage ({$typeChauffage}), l'isolation ({$isolation}) et la surface à chauffer"
        );
    }
    
    /**
     * Calcul détaillé de l'eau chaude
     */
    private function calculateEauChaudeDetaille($data) {
        $this->logDebug("💧 CALCUL EAU CHAUDE");
        
        if ($data['eau_chaude'] !== 'oui') {
            return array(
                'total' => 0,
                'methode' => 'Eau chaude non électrique',
                'type_production' => $data['eau_chaude'],
                'base_kwh' => 0,
                'coefficient' => 0,
                'nb_personnes' => (int)$data['nb_personnes'],
                'calcul' => '0 kWh/an - Eau chaude non électrique sélectionnée',
                'explication' => 'Aucune consommation électrique car l\'eau chaude n\'est pas produite électriquement'
            );
        }
        
        $nbPersonnes = (int)$data['nb_personnes'];
        $baseKwh = $this->getConfigValue('chauffe_eau', 900); // kWh pour 1 personne
        $coefficient = $this->getCoefficientEauChaude($nbPersonnes);
        
        $total = $baseKwh * $coefficient;
        
        $this->logDebug("Eau chaude - Base: {$baseKwh} kWh, Coeff personnes: {$coefficient}");
        
        return array(
            'total' => $total,
            'methode' => 'Base eau chaude × Coefficient personnes',
            'base_kwh' => $baseKwh,
            'coefficient' => $coefficient,
            'nb_personnes' => $nbPersonnes,
            'calcul' => "{$baseKwh} kWh/an × {$coefficient} = " . round($total) . " kWh/an",
            'explication' => "Consommation basée sur {$nbPersonnes} personne(s) avec un coefficient de {$coefficient}"
        );
    }
    
    /**
     * Calcul détaillé des électroménagers
     */
    private function calculateElectromenagetDetaille($data) {
        $electromenagers = $data['electromenagers'] ?? array();
        $nbPersonnes = (int)$data['nb_personnes'];
        
        $this->logDebug("🏠 CALCUL ÉLECTROMÉNAGERS: " . count($electromenagers) . " équipements sélectionnés");
        
        $details = array();
        $total = 0;
        
        // Consommations de base des équipements
        $equipementsList = array(
            'lave_linge' => 'Lave-linge',
            'seche_linge' => 'Sèche-linge', 
            'refrigerateur' => 'Réfrigérateur',
            'congelateur' => 'Congélateur',
            'lave_vaisselle' => 'Lave-vaisselle',
            'four' => 'Four électrique',
            'cave_a_vin' => 'Cave à vin'
        );
        
        foreach ($electromenagers as $equipement) {
            if (isset($equipementsList[$equipement])) {
                $baseKwh = $this->getConfigValue($equipement, $this->getDefaultElectro($equipement));
                $coefficient = $this->getCoefficientEquipement($equipement, $nbPersonnes);
                $consommation = $baseKwh * $coefficient;
                
                $details[$equipement] = array(
                    'nom' => $equipementsList[$equipement],
                    'base_kwh' => $baseKwh,
                    'coefficient' => $coefficient,
                    'final_kwh' => $consommation
                );
                
                $total += $consommation;
                
                $this->logDebug("- {$equipementsList[$equipement]}: {$baseKwh} × {$coefficient} = {$consommation} kWh");
            }
        }
        
        // Ajouter le forfait petits électroménagers (toujours inclus)
        $forfaitPetits = $this->getConfigValue('forfait_petits_electromenagers', 150);
        $details['forfait_petits'] = array(
            'nom' => 'Forfait petits électroménagers',
            'base_kwh' => $forfaitPetits,
            'coefficient' => 1,
            'final_kwh' => $forfaitPetits
        );
        $total += $forfaitPetits;
        
        return array(
            'total' => $total,
            'details' => $details,
            'nb_equipements' => count($electromenagers),
            'forfait_inclus' => $forfaitPetits,
            'methode' => 'Somme des équipements sélectionnés × Coefficient personnes + Forfait petits électroménagers',
            'explication' => 'Chaque électroménager est ajusté selon le nombre de personnes. Un forfait couvre les petits appareils.'
        );
    }
    
    /**
     * Calcul détaillé de la cuisson
     */
    private function calculateCuissonDetaille($data) {
        $typeCuisson = $data['type_cuisson'] ?? '';
        $nbPersonnes = (int)$data['nb_personnes'];
        
        $this->logDebug("🍳 CALCUL CUISSON: {$typeCuisson}");
        
        if ($typeCuisson === 'autre') {
            return array(
                'total' => 0,
                'methode' => 'Cuisson non électrique',
                'type_cuisson' => $typeCuisson,
                'calcul' => '0 kWh/an - Cuisson non électrique',
                'explication' => 'Cuisson au gaz ou mixte, pas de consommation électrique pour la cuisson'
            );
        }
        
        $baseKwh = $this->getConfigValue('plaque_cuisson', 215);
        $coefficient = $this->getCoefficientEquipement('plaque_cuisson', $nbPersonnes);
        $total = $baseKwh * $coefficient;
        
        return array(
            'total' => $total,
            'base_kwh' => $baseKwh,
            'coefficient' => $coefficient,
            'nb_personnes' => $nbPersonnes,
            'type_cuisson' => $typeCuisson,
            'methode' => 'Base cuisson × Coefficient personnes',
            'calcul' => "{$baseKwh} kWh/an × {$coefficient} = " . round($total) . " kWh/an",
            'explication' => "Consommation des plaques de cuisson {$typeCuisson} ajustée pour {$nbPersonnes} personne(s)"
        );
    }
    
    /**
     * Calcul détaillé de l'éclairage  
     */
    private function calculateEclairageDetaille($data) {
        $typeEclairage = $data['type_eclairage'] ?? '';
        $surface = (int)$data['surface'];
        
        $this->logDebug("💡 CALCUL ÉCLAIRAGE: {$typeEclairage}");
        
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
    
    /**
     * Calcul détaillé du multimédia
     */
    private function calculateMultimediaDetaille($data) {
        $nbPersonnes = (int)$data['nb_personnes'];
        
        $this->logDebug("📺 CALCUL MULTIMÉDIA (inclus automatiquement)");
        
        $baseKwh = $this->getConfigValue('tv_pc_box', 300);
        $coefficient = $this->getCoefficientEquipement('tv_pc_box', $nbPersonnes);
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
    
    /**
     * Calcul détaillé des équipements spéciaux
     */
    private function calculateEquipementsSpeciauxDetaille($data) {
        $equipementsSpeciaux = $data['equipements_speciaux'] ?? array();
        $piscine = $data['piscine'] ?? 'non';
        
        $this->logDebug("⚡ CALCUL ÉQUIPEMENTS SPÉCIAUX");
        
        $repartition = array();
        $total = 0;
        $details = array();
        
        // PISCINE
        if ($piscine === 'simple') {
            $kwhPiscine = $this->getConfigValue('piscine', 1400);
            $repartition['piscine'] = $kwhPiscine;
            $total += $kwhPiscine;
            $details['piscine'] = "Piscine simple: {$kwhPiscine} kWh/an";
            $this->logDebug("- Piscine simple: {$kwhPiscine} kWh");
        } elseif ($piscine === 'chauffee') {
            $kwhPiscine = $this->getConfigValue('piscine_chauffee', 4000);
            $repartition['piscine'] = $kwhPiscine;
            $total += $kwhPiscine;
            $details['piscine'] = "Piscine chauffée: {$kwhPiscine} kWh/an";
            $this->logDebug("- Piscine chauffée: {$kwhPiscine} kWh");
        } else {
            $repartition['piscine'] = 0;
            $details['piscine'] = "Pas de piscine: 0 kWh/an";
        }
        
        // ÉQUIPEMENTS SPÉCIAUX
        $equipementsPossibles = array(
            'spa_jacuzzi' => array('config' => 'spa_jacuzzi', 'default' => 2000, 'nom' => 'Spa/Jacuzzi'),
            'voiture_electrique' => array('config' => 'voiture_electrique', 'default' => 1500, 'nom' => 'Voiture électrique'),
            'aquarium_petit' => array('config' => 'aquarium', 'default' => 240, 'coeff' => 0.5, 'nom' => 'Petit aquarium'),
            'aquarium_grand' => array('config' => 'aquarium', 'default' => 240, 'coeff' => 2.0, 'nom' => 'Grand aquarium'),
            'climatiseur_mobile' => array('config' => 'climatiseur_mobile', 'default' => 150, 'nom' => 'Climatiseur mobile')
        );
        
        // Initialiser tous les équipements à 0
        foreach (array('spa_jacuzzi', 'voiture_electrique', 'aquarium', 'climatiseur_mobile') as $eq) {
            $repartition[$eq] = 0;
        }
        
        foreach ($equipementsSpeciaux as $equipement) {
            if (isset($equipementsPossibles[$equipement])) {
                $config = $equipementsPossibles[$equipement];
                $kwh = $this->getConfigValue($config['config'], $config['default']);
                
                if (isset($config['coeff'])) {
                    $kwh *= $config['coeff'];
                }
                
                $key = str_replace(['_petit', '_grand'], '', $equipement);
                $repartition[$key] += $kwh;
                $total += $kwh;
                
                $details[$equipement] = "{$config['nom']}: {$kwh} kWh/an";
                $this->logDebug("- {$config['nom']}: {$kwh} kWh");
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
    
    /**
     * Calcul des tarifs détaillé
     */
    private function calculateTarifsDetaille($consommationTotale, $data) {
        $surface = (int)$data['surface'];
        $puissanceRecommandee = $this->calculatePuissanceRecommandee($consommationTotale, $data);
        
        $this->logDebug("💰 CALCUL TARIFS pour {$consommationTotale} kWh/an, puissance {$puissanceRecommandee} kVA");
        
        // Récupération des tarifs BASE
        $aboBase = $this->getConfigValue('base_abo_' . $puissanceRecommandee, 22.21);
        $kwhBase = $this->getConfigValue('base_kwh_' . $puissanceRecommandee, 0.2516);
        
        // Récupération des tarifs HC
        $aboHC = $this->getConfigValue('hc_abo_' . $puissanceRecommandee, 23.57);
        $kwhHP = $this->getConfigValue('hc_hp_' . $puissanceRecommandee, 0.27);
        $kwhHC = $this->getConfigValue('hc_hc_' . $puissanceRecommandee, 0.2068);
        
        // Répartition HP/HC
        $repartitionHP = ($this->getConfigValue('repartition_hp', 60)) / 100;
        $repartitionHC = ($this->getConfigValue('repartition_hc', 40)) / 100;
        
        $consommationHP = $consommationTotale * $repartitionHP;
        $consommationHC = $consommationTotale * $repartitionHC;
        
        // Calculs des coûts
        $coutBase = ($aboBase * 12) + ($consommationTotale * $kwhBase);
        $coutHC = ($aboHC * 12) + ($consommationHP * $kwhHP) + ($consommationHC * $kwhHC);
        
        return array(
            'base' => array(
                'total_annuel' => (int)round($coutBase),
                'total_mensuel' => (int)round($coutBase / 12),
                'abonnement_mensuel' => $aboBase,
                'prix_kwh' => $kwhBase,
                'puissance_kva' => $puissanceRecommandee,
                'calcul_detail' => "({$aboBase}€ × 12 mois) + ({$consommationTotale} kWh × {$kwhBase}€) = " . round($coutBase) . "€/an"
            ),
            'hc' => array(
                'total_annuel' => (int)round($coutHC),
                'total_mensuel' => (int)round($coutHC / 12),
                'abonnement_mensuel' => $aboHC,
                'prix_kwh_hp' => $kwhHP,
                'prix_kwh_hc' => $kwhHC,
                'consommation_hp' => (int)round($consommationHP),
                'consommation_hc' => (int)round($consommationHC),
                'repartition_hp' => $repartitionHP * 100,
                'repartition_hc' => $repartitionHC * 100,
                'puissance_kva' => $puissanceRecommandee,
                'calcul_detail' => "({$aboHC}€ × 12) + (" . round($consommationHP) . " kWh × {$kwhHP}€) + (" . round($consommationHC) . " kWh × {$kwhHC}€) = " . round($coutHC) . "€/an"
            ),
            'economie_potentielle' => abs($coutBase - $coutHC),
            'tarif_recommande' => ($coutHC < $coutBase) ? 'hc' : 'base'
        );
    }
    
    // ==========================================
    // FONCTIONS UTILITAIRES
    // ==========================================
    
    private function calculatePuissanceRecommandee($consommationTotale, $data) {
        $surface = (int)$data['surface'];
        
        // Logique de calcul de puissance basée sur consommation et surface
        if ($consommationTotale < 4000 && $surface <= 70) return '9';
        if ($consommationTotale < 6000 && $surface <= 100) return '12';
        if ($consommationTotale < 10000 && $surface <= 150) return '15';
        if ($consommationTotale < 15000 && $surface <= 200) return '18';
        if ($consommationTotale < 20000) return '24';
        return '30';
    }
    
    private function getCoefficientEauChaude($nbPersonnes) {
        $coefficients = array(1 => 1.0, 2 => 2.0, 3 => 2.8, 4 => 3.7, 5 => 3.9, 6 => 5.5);
        return $coefficients[$nbPersonnes] ?? 5.5;
    }
    
    private function getCoefficientEquipement($equipement, $nbPersonnes) {
        $configKey = 'coeff_' . $equipement . '_' . $nbPersonnes;
        
        // Coefficients par défaut si pas dans la config
        $defaultCoeffs = array(
            1 => 1.0, 2 => 1.0, 3 => 1.2, 4 => 1.4, 5 => 1.6, 6 => 1.8
        );
        
        return $this->getConfigValue($configKey, $defaultCoeffs[$nbPersonnes] ?? 1.8);
    }
    
    private function getCoefficientPersonnes($nbPersonnes) {
        $coefficients = array(1 => 0.7, 2 => 1.0, 3 => 1.2, 4 => 1.4, 5 => 1.6, 6 => 1.8);
        return $coefficients[$nbPersonnes] ?? 1.8;
    }
    
    private function getCoefficientSurface($surface) {
        // Pas de coefficient surface dans ce calcul, mais peut être ajouté
        return 1.0;
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
    
    private function getIsolationCoefficient($isolation) {
        $coefficients = array(
            'avant_1980' => 1.5,
            '1980_2000' => 1.2,
            'apres_2000' => 0.8,
            'renovation' => 0.5
        );
        return $coefficients[$isolation] ?? 1.0;
    }
    
    private function getConfigValue($key, $default = 0) {
        return isset($this->configData[$key]) ? $this->configData[$key] : $default;
    }
    
    private function getDefaultChauffage($typeChauffage, $isolation) {
        // Valeurs par défaut selon type chauffage et isolation
        $defaults = array(
            'convecteurs' => array('avant_1980' => 200, '1980_2000' => 140, 'apres_2000' => 80, 'renovation' => 50),
            'inertie' => array('avant_1980' => 170, '1980_2000' => 120, 'apres_2000' => 65, 'renovation' => 40),
            'clim_reversible' => array('avant_1980' => 120, '1980_2000' => 80, 'apres_2000' => 50, 'renovation' => 25),
            'pac' => array('avant_1980' => 100, '1980_2000' => 65, 'apres_2000' => 40, 'renovation' => 20)
        );
        
        return $defaults[$typeChauffage][$isolation] ?? 100;
    }
    
    private function getDefaultElectro($equipement) {
        $defaults = array(
            'lave_linge' => 100, 'seche_linge' => 175, 'refrigerateur' => 125,
            'congelateur' => 125, 'lave_vaisselle' => 100, 'four' => 125, 'cave_a_vin' => 150
        );
        return $defaults[$equipement] ?? 100;
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