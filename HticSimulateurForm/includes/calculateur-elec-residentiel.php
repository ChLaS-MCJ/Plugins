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
        $chauffageDetails = $this->calculateChauffage($data);
        $chauffageKwh = $chauffageDetails['total'];
        $this->logDebug("🔥 Chauffage calculé: {$chauffageKwh} kWh/an");
        
        // 2. EAU CHAUDE
        $eauChaudeDetails = $this->calculateEauChaude($data);
        $eauChaudeKwh = $eauChaudeDetails['total'];
        $this->logDebug("💧 Eau chaude calculée: {$eauChaudeKwh} kWh/an");
        
        // 3. ÉLECTROMÉNAGERS
        $electromenagersDetails = $this->calculateElectromenager($data);
        $electromenagersKwh = $electromenagersDetails['total'];
        $this->logDebug("🏠 Électroménagers calculés: {$electromenagersKwh} kWh/an");
        
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
                              $eclairageKwh + $multimediaKwh + $equipementsKwh;
        
        $this->logDebug("📊 CONSOMMATION TOTALE: {$consommationTotale} kWh/an");
        
        // CALCUL DES TARIFS
        $tarifsCalcules = $this->calculateTarifsDetaille($consommationTotale, $data);
        
        // PUISSANCE RECOMMANDÉE
        $puissanceRecommandee = $this->calculatePuissanceRecommandee($consommationTotale, $data);

        $puissanceChauffage = $this->calculatePuissanceChauffage($data);
        $puissanceEauChaude = $this->calculatePuissanceEauChaude($data);
        $puissanceElectromenagers = $this->calculatePuissanceElectromenagers($data);
        $puissanceMultimedia = $this->calculatePuissanceMultimedia($data);
        $puissanceEquipements = $this->calculatePuissanceEquipementsSpeciaux($data);
        $puissanceEclairage = $this->calculatePuissanceEclairage($data);

        $puissanceTotaleRetenue = $puissanceChauffage + $puissanceEauChaude + $puissanceElectromenagers + 
                                $puissanceMultimedia + $puissanceEquipements + $puissanceEclairage;

        
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
                'tv_pc_box' => (int)round($multimediaKwh), // Alias pour rétrocompatibilité
                'autres' => 0, // Divers non catégorisé
                'equipements_speciaux' => $equipementsDetails['repartition'] // Détail par équipement
            ),
            
            // DÉTAILS COMPLETS DE CALCUL
            'details_calcul' => array(
                'chauffage' => $chauffageDetails,
                'eau_chaude' => $eauChaudeDetails,
                'electromenagers' => $electromenagersDetails,
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
        $this->logDebug("=== CALCUL CHAUFFAGE DÉTAILLÉ ===");
        
        // Utiliser directement $data passé en paramètre
        $typeChauffage = isset($data['type_chauffage']) ? $data['type_chauffage'] : '';
        $typeLogement = isset($data['type_logement']) ? $data['type_logement'] : 'maison';
        $surface = floatval($data['surface']);
        $isolation = isset($data['isolation']) ? $data['isolation'] : '';
        
        // Vérifier si c'est du chauffage électrique
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        
        if (!in_array($typeChauffage, $chauffagesElectriques)) {
            $this->logDebug("Pas de chauffage électrique ({$typeChauffage})");
            
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
        
        // Construire la clé de configuration
        // Les valeurs appartement_* sont déjà avec le coefficient 0.95 appliqué
        $config_key = $typeLogement . '_' . $typeChauffage . '_' . $isolationNormalisee;
        
        // Récupérer la consommation par m² depuis la configuration
        $conso_par_m2 = isset($this->configData[$config_key]) ? $this->configData[$config_key] : 0;
        
        if ($conso_par_m2 > 0) {
            // Calcul simple : surface × conso/m² (pas de coefficient, déjà dans les valeurs)
            $consommation = $surface * $conso_par_m2;
            
            $this->logDebug("Type chauffage: {$typeChauffage}");
            $this->logDebug("Clé de config: {$config_key}");
            $this->logDebug("Consommation par m²: {$conso_par_m2} kWh/m²/an");
            $this->logDebug("Consommation chauffage: {$consommation} kWh/an");
            
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
            $this->logDebug("ERREUR: Configuration manquante pour {$config_key}");
            
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
        $this->logDebug("=== CALCUL EAU CHAUDE DÉTAILLÉ ===");
        
        // Utiliser directement $data passé en paramètre
        $eauChaude = isset($data['eau_chaude']) ? $data['eau_chaude'] : 'non';
        $nbPersonnes = intval($data['nb_personnes']);
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        
        if ($eauChaude === 'oui') {
            // Récupération des valeurs depuis la configuration
            $conso_base = isset($this->configData['chauffe_eau']) ? $this->configData['chauffe_eau'] : 0;
            
            if ($conso_base === 0) {
                $this->logDebug("ERREUR: Consommation de base chauffe-eau non trouvée");
                
                return array(
                    'total' => 0,
                    'erreur' => 'Configuration chauffe-eau manquante',
                    'methode' => 'Erreur de configuration',
                    'calcul' => 'Configuration manquante pour chauffe-eau',
                    'explication' => 'Données de consommation eau chaude non configurées'
                );
            }
            
            // Coefficient selon nombre de personnes
            $coeff_key = 'coeff_chauffe_eau_' . $nbPersonnes;
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            
            $this->logDebug("Consommation eau chaude: {$consommation} kWh/an");
            
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
            $this->logDebug("Pas d'eau chaude électrique");
            
            return array(
                'total' => 0,
                'methode' => 'Pas d\'eau chaude électrique',
                'calcul' => 'Eau chaude non électrique - consommation: 0 kWh/an',
                'explication' => 'Production d\'eau chaude par autre énergie (gaz, solaire, etc.)'
            );
        }
    }

    private function calculateElectromenager($data) {
        $this->logDebug("=== CALCUL ÉLECTROMÉNAGERS ===");
        
        // Utiliser directement $data passé en paramètre
        $electromenagers = isset($data['electromenagers']) && is_array($data['electromenagers']) ? $data['electromenagers'] : array();
        $nbPersonnes = intval($data['nb_personnes']);
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        
        $consommation_totale = 0;
        $details_calcul = array();
        
        // === ÉLECTROMÉNAGERS SÉLECTIONNÉS ===
        $electromenagers_disponibles = array(
            'lave_linge', 'four', 'seche_linge', 'lave_vaisselle', 
            'cave_a_vin', 'refrigerateur', 'congelateur'
        );
        
        foreach ($electromenagers as $equipement) {
            if (in_array($equipement, $electromenagers_disponibles)) {
                // Calculer pour cet équipement
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
                
                $this->logDebug("{$equipement}: {$conso_base} × {$coefficient} = {$consommation} kWh/an");
            }
        }
        
        // === PLAQUE DE CUISSON ===
        $type_cuisson = isset($data['type_cuisson']) ? $data['type_cuisson'] : '';
        
        if ($type_cuisson === 'induction' || $type_cuisson === 'plaque_induction') {
            $equipement = 'plaque_induction';
            $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 365;
            
            // La clé exacte comme dans votre configuration
            $coeff_key = 'coeff_plaque_induction_' . $nbPersonnes;
            
            // Debug pour voir ce qui se passe
            $this->logDebug("Recherche coefficient: {$coeff_key}");
            $this->logDebug("Coefficient trouvé: " . (isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 'NON TROUVÉ'));
            
            // Récupérer le coefficient depuis la config
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            $consommation_totale += $consommation;
            
            $details_calcul['plaque_induction'] = array(
                'nom' => 'Plaque à induction',
                'base_kwh' => $conso_base,
                'coefficient' => $coefficient,
                'final_kwh' => $consommation,
                'coeff_key' => $coeff_key // Ajouter pour debug
            );
            
            $this->logDebug("Plaque induction: {$conso_base} × {$coefficient} = {$consommation} kWh/an");
            
        } elseif ($type_cuisson === 'vitroceramique' || $type_cuisson === 'plaque_vitroceramique') {
            $equipement = 'plaque_vitroceramique';
            $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 400;
            
            // La clé exacte comme dans votre configuration
            $coeff_key = 'coeff_plaque_vitroceramique_' . $nbPersonnes;
            
            // Debug
            $this->logDebug("Recherche coefficient: {$coeff_key}");
            $this->logDebug("Coefficient trouvé: " . (isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 'NON TROUVÉ'));
            
            $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
            
            $consommation = $conso_base * $coefficient;
            $consommation_totale += $consommation;
            
            $details_calcul['plaque_vitroceramique'] = array(
                'nom' => 'Plaque vitrocéramique',
                'base_kwh' => $conso_base,
                'coefficient' => $coefficient,
                'final_kwh' => $consommation,
                'coeff_key' => $coeff_key // Pour debug
            );
            
            $this->logDebug("Plaque vitrocéramique: {$conso_base} × {$coefficient} = {$consommation} kWh/an");
        }
        
        // === FORFAIT PETITS ÉLECTROMÉNAGERS ===
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
        
        $this->logDebug("Total électroménagers: {$consommation_totale} kWh/an");
        
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
    
    /**
     * Calcule la consommation d'un équipement spécifique
     */
    private function calculerEquipement($equipement, $nbPersonnes, &$consommation_totale, &$details_calcul) {
        // Consommation de base
        $conso_base = isset($this->configData[$equipement]) ? $this->configData[$equipement] : 0;
        
        // Coefficient selon nombre de personnes
        $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
        $coefficient = isset($this->configData[$coeff_key]) ? $this->configData[$coeff_key] : 1;
        
        $consommation = $conso_base * $coefficient;
        
        // Puissance avec simultanéité
        $puissance_base = isset($this->configData[$equipement . '_puissance']) ? $this->configData[$equipement . '_puissance'] : 0;
        $simultaneite = isset($this->configData[$equipement . '_simultaneite']) ? ($this->configData[$equipement . '_simultaneite'] / 100) : 0.5;
        $puissance = ($puissance_base * $simultaneite) / 1000; // en kW
        
        $consommation_totale += $consommation;
        $puissance_totale += $puissance;
        
        // Stocker les détails pour l'affichage
        $details_calcul[$equipement] = array(
            'consommation' => $consommation,
            'puissance' => $puissance,
            'label' => $this->getEquipementLabel($equipement),
            'coefficient' => $coefficient
        );
        
        $this->logDebug("{$equipement}: {$conso_base} × {$coefficient} = {$consommation} kWh/an, {$puissance} kW");
    }

    /**
     * Retourne le label d'affichage d'un équipement
     */
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
        
        // Récupérer la base depuis la config
        $baseKwh = $this->getConfigValue('tv_pc_box', 300);
        
        // Récupérer directement le coefficient depuis la config
        $coeff_key = 'coeff_tv_pc_box_' . $nbPersonnes;
        $coefficient = $this->getConfigValue($coeff_key, 1.0);
        
        $this->logDebug("Coefficient multimédia pour {$nbPersonnes} personnes: {$coefficient}");
        
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
            'aquarium' => array('config' => 'aquarium', 'default' => 240, 'nom' => 'Aquarium'),
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
                
                $repartition[$equipement] = $kwh;
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
    // FONCTIONS CALCUL DE PUISSANCE
    // ==========================================

    private function calculatePuissanceChauffage($data) {
        $typeChauffage = $data['type_chauffage'] ?? '';
        $surface = floatval($data['surface']);
        
        // Pas de chauffage électrique = 0
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (!in_array($typeChauffage, $chauffagesElectriques)) {
            return 0;
        }
        
        // Récupérer puissance et simultanéité depuis config
        $puissance_m2 = $this->getConfigValue('chauffage_m2_puissance', 50); // W/m²
        $simultaneite = $this->getConfigValue('chauffage_m2_simultaneite', 80) / 100;
        
        // Calcul : (surface × puissance/m² × simultanéité) / 1000 / 0.95
        $puissance = ($surface * $puissance_m2 * $simultaneite) / 1000 / 0.95;
        
        $this->logDebug("Puissance chauffage: {$surface}m² × {$puissance_m2}W/m² × {$simultaneite} / 1000 / 0.95 = {$puissance} kW");
        
        return $puissance;
    }

    private function calculatePuissanceEauChaude($data) {
        $eauChaude = $data['eau_chaude'] ?? 'non';
        
        if ($eauChaude !== 'oui') {
            return 0;
        }
        
        // Récupérer puissance et simultanéité
        $puissance = $this->getConfigValue('chauffe_eau_puissance', 2400); // W
        $simultaneite = $this->getConfigValue('chauffe_eau_simultaneite', 30) / 100;
        
        // Calcul : (puissance × simultanéité) / 1000 / 0.95
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        $this->logDebug("Puissance eau chaude: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
        
        return $puissance_kw;
    }

    private function calculatePuissanceElectromenagers($data) {
        $electromenagers = $data['electromenagers'] ?? array();
        $type_cuisson = $data['type_cuisson'] ?? '';
        $puissance_totale = 0;
        
        // Liste des équipements avec leur clé de config
        $equipements_config = array(
            'lave_linge' => 'lave_linge',
            'four' => 'four',
            'seche_linge' => 'seche_linge',
            'lave_vaisselle' => 'lave_vaisselle',
            'cave_a_vin' => 'cave_a_vin',
            'refrigerateur' => 'refrigerateur',
            'congelateur' => 'congelateur'
        );
        
        // Calculer pour chaque équipement sélectionné
        foreach ($electromenagers as $equipement) {
            if (isset($equipements_config[$equipement])) {
                $config_key = $equipements_config[$equipement];
                $puissance = $this->getConfigValue($config_key . '_puissance', 0);
                $simultaneite = $this->getConfigValue($config_key . '_simultaneite', 50) / 100;
                
                if ($puissance > 0) {
                    $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
                    $puissance_totale += $puissance_kw;
                    $this->logDebug("Puissance {$equipement}: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
                }
            }
        }
        
        // Ajouter la plaque de cuisson
        if ($type_cuisson === 'plaque_induction' || $type_cuisson === 'induction') {
            $puissance = $this->getConfigValue('plaque_induction_puissance', 3500);
            $simultaneite = $this->getConfigValue('plaque_induction_simultaneite', 30) / 100;
            $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
            $puissance_totale += $puissance_kw;
            $this->logDebug("Puissance plaque induction: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
        } elseif ($type_cuisson === 'plaque_vitroceramique' || $type_cuisson === 'vitroceramique') {
            $puissance = $this->getConfigValue('plaque_vitroceramique_puissance', 3000);
            $simultaneite = $this->getConfigValue('plaque_vitroceramique_simultaneite', 30) / 100;
            $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
            $puissance_totale += $puissance_kw;
            $this->logDebug("Puissance plaque vitro: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceMultimedia($data) {
        // TV/PC/Box toujours inclus
        $puissance = $this->getConfigValue('tv_pc_box_puissance', 500);
        $simultaneite = $this->getConfigValue('tv_pc_box_simultaneite', 80) / 100;
        
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        $this->logDebug("Puissance multimédia: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
        
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
            $this->logDebug("Puissance piscine: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
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
                    $this->logDebug("Puissance {$equipement}: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
                }
            }
        }
        
        return $puissance_totale;
    }

    private function calculatePuissanceEclairage($data) {
        $puissance = $this->getConfigValue('eclairage_puissance', 500);
        $simultaneite = $this->getConfigValue('eclairage_simultaneite', 80) / 100;
        
        $puissance_kw = ($puissance * $simultaneite) / 1000 / 0.95;
        
        $this->logDebug("Puissance éclairage: {$puissance}W × {$simultaneite} / 1000 / 0.95 = {$puissance_kw} kW");
        
        return $puissance_kw;
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
        // Limiter à 6 personnes max
        if ($nbPersonnes > 6) $nbPersonnes = 6;
        if ($nbPersonnes < 1) $nbPersonnes = 1;
        
        // Construire la clé
        $coeff_key = 'coeff_' . $equipement . '_' . $nbPersonnes;
        
        // Debug
        $this->logDebug("Recherche coefficient: {$coeff_key}");
        
        // Récupérer depuis la config avec fallback intelligent
        $coefficient = $this->getConfigValue($coeff_key, 1.0);
        
        $this->logDebug("Coefficient trouvé: {$coefficient}");
        
        return $coefficient;
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