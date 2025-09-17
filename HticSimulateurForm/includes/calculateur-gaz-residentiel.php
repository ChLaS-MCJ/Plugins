<?php
/**
 * Calculateur Gaz Résidentiel - Version Corrigée selon Excel
 * Conforme aux formules exactes du fichier Excel
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction principale de calcul gaz résidentiel
 */
function htic_calculateur_gaz_residentiel($userData, $configData) {
    try {
        $calculator = new HticCalculateurGazResidentiel($userData, $configData);
        return $calculator->calculate();
    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => 'Erreur de calcul: ' . $e->getMessage()
        );
    }
}

class HticCalculateurGazResidentiel {
    
    private $userData;
    private $configData;
    
    public function __construct($userData = array(), $configData = array()) {
        $this->userData = $userData;
        $this->configData = $configData;
    }
    
    /**
     * Point d'entrée principal du calcul
     */
    public function calculate() {
        if (!$this->validateUserData()) {
            return $this->returnError("Données utilisateur invalides");
        }
        
        // 1. Déterminer le type de gaz selon la commune (VLOOKUP Excel)
        $typeGaz = $this->determineTypeGaz();
        
        // 2. Calculer les consommations par usage
        $consommations = $this->calculateConsommations();
        
        // 3. Déterminer la tranche tarifaire selon la logique Excel
        $trancheTarifaire = $this->determineTrancheTarifaire($typeGaz, $consommations['total']);
        
        // 4. Calculer les coûts avec la bonne tranche
        $couts = $this->calculateCostsWithTranche($consommations['total'], $trancheTarifaire);
        
        // 5. Préparer la répartition pour l'affichage
        $repartition = $this->buildRepartition($consommations);
        
        // 6. Résultats finaux
        $results = array(
            // Informations générales
            'type_gaz' => $typeGaz,
            'commune' => $this->userData['commune'] ?? '',
            'tranche_tarifaire' => $trancheTarifaire['nom'],
            
            // Consommation totale
            'consommation_annuelle' => $consommations['total'],
            'consommation_totale' => $consommations['total'],
            
            // Coûts
            'cout_annuel_ttc' => $couts['total_ttc'],
            'cout_abonnement' => $couts['abonnement_annuel'],
            'cout_consommation' => $couts['consommation'],
            'prix_kwh' => $couts['prix_kwh'],
            'total_annuel' => $couts['total_ttc'],
            'total_mensuel' => round($couts['total_ttc'] / 12, 2),
            
            // Répartition détaillée
            'repartition' => $repartition,
            
            // Récapitulatif pour affichage
            'recap' => array(
                'type_logement' => $this->userData['type_logement'] ?? 'maison',
                'superficie' => $this->userData['superficie'] ?? 0,
                'nb_personnes' => $this->userData['nb_personnes'] ?? 1,
                'commune' => $this->userData['commune'] ?? '',
                'chauffage_gaz' => $this->userData['chauffage_gaz'] ?? 'non',
                'isolation' => $this->userData['isolation'] ?? 'faible',
                'eau_chaude' => $this->userData['eau_chaude'] ?? 'autre',
                'cuisson' => $this->userData['cuisson'] ?? 'autre',
                'offre' => $this->userData['offre'] ?? 'base'
            )
        );
        
        return array('success' => true, 'data' => $results);
    }
    
    /**
     * Déterminer le type de gaz selon la commune - Équivalent VLOOKUP(F5,$M$20:$N$38,2,FALSE)
     */
    private function determineTypeGaz() {
        $commune = strtoupper(trim($this->userData['commune'] ?? ''));
        
        // Table de correspondance exacte selon Excel M20:N38
        $tableCommunes = array(
            'AIRE SUR L\'ADOUR' => 'Gaz naturel',
            'BARCELONNE DU GERS' => 'Gaz naturel',
            'BASCONS' => 'Gaz Propane',
            'BENESSE LES DAX' => 'Gaz Propane',
            'CAMPAGNE' => 'Gaz Propane',
            'CARCARES SAINTE CROIX' => 'Gaz Propane',
            'GAAS' => 'Gaz naturel',
            'GEAUNE' => 'Gaz Propane',
            'LABATUT' => 'Gaz naturel',
            'LALUQUE' => 'Gaz naturel',
            'MAZEROLLES' => 'Gaz Propane',
            'MEILHAN' => 'Gaz Propane',
            'MISSON' => 'Gaz naturel',
            'PONTONX SUR L\'ADOUR' => 'Gaz Propane',
            'POUILLON' => 'Gaz naturel',
            'SAINT MAURICE' => 'Gaz Propane',
            'SOUPROSSE' => 'Gaz Propane',
            'TETHIEU' => 'Gaz Propane',
            'YGOS SAINT SATURNIN' => 'Gaz Propane'
        );
        
        // Si commune = "autre", utiliser le choix de l'utilisateur
        if ($commune === 'AUTRE') {
            $typeChoisi = $this->userData['type_gaz_autre'] ?? 'naturel';
            return ($typeChoisi === 'naturel') ? 'Gaz naturel' : 'Gaz Propane';
        }
        
        // Recherche exacte dans la table
        if (isset($tableCommunes[$commune])) {
            return $tableCommunes[$commune];
        }
        
        // Par défaut: Gaz naturel
        return 'Gaz naturel';
    }
    
    /**
     * Déterminer la tranche tarifaire - Équivalent formule J9 Excel
     * IF(I9=P19,IF(F11<P6,P7,IF(F11<Q6,Q7,"nous consulter")),
     * IF(F11<P11,P12,IF(F11<Q11,Q12,IF(F11<R11,R12,IF(F11<S11,S12,T12)))))
     */
    private function determineTrancheTarifaire($typeGaz, $consommationTotale) {
        if ($typeGaz === 'Gaz naturel') {
            // Logique Gaz Naturel (P19 = "Gaz naturel")
            if ($consommationTotale < 4000) { // P6 = 4000
                return array(
                    'nom' => 'GOM0',
                    'abo_mensuel' => floatval($this->configData['gaz_naturel_gom0_abo'] ?? 8.92),
                    'prix_kwh' => floatval($this->configData['gaz_naturel_gom0_kwh'] ?? 0.1265)
                );
            } elseif ($consommationTotale < 35000) { // Q6 = 35000
                return array(
                    'nom' => 'GOM1',
                    'abo_mensuel' => floatval($this->configData['gaz_naturel_gom1_abo'] ?? 22.4175),
                    'prix_kwh' => floatval($this->configData['gaz_naturel_gom1_kwh'] ?? 0.0978)
                );
            } else {
                // "nous consulter" - On utilise GOM1 par défaut pour les très grosses consommations
                return array(
                    'nom' => 'GOM1_PLUS',
                    'abo_mensuel' => floatval($this->configData['gaz_naturel_gom1_abo'] ?? 22.4175),
                    'prix_kwh' => floatval($this->configData['gaz_naturel_gom1_kwh'] ?? 0.0978)
                );
            }
        } else {
            // Logique Gaz Propane
            if ($consommationTotale < 1000) { // P11 = 1000
                return array(
                    'nom' => 'P0',
                    'abo_mensuel' => floatval($this->configData['gaz_propane_p0_abo'] ?? 4.64),
                    'prix_kwh' => floatval($this->configData['gaz_propane_p0_kwh'] ?? 0.12479)
                );
            } elseif ($consommationTotale < 6000) { // Q11 = 6000
                return array(
                    'nom' => 'P1',
                    'abo_mensuel' => floatval($this->configData['gaz_propane_p1_abo'] ?? 5.26),
                    'prix_kwh' => floatval($this->configData['gaz_propane_p1_kwh'] ?? 0.11852)
                );
            } elseif ($consommationTotale < 30000) { // R11 = 30000
                return array(
                    'nom' => 'P2',
                    'abo_mensuel' => floatval($this->configData['gaz_propane_p2_abo'] ?? 16.06),
                    'prix_kwh' => floatval($this->configData['gaz_propane_p2_kwh'] ?? 0.11305)
                );
            } elseif ($consommationTotale < 350000) { // S11 = 350000
                return array(
                    'nom' => 'P3',
                    'abo_mensuel' => floatval($this->configData['gaz_propane_p3_abo'] ?? 34.56),
                    'prix_kwh' => floatval($this->configData['gaz_propane_p3_kwh'] ?? 0.10273)
                );
            } else {
                // P4 pour le reste
                return array(
                    'nom' => 'P4',
                    'abo_mensuel' => floatval($this->configData['gaz_propane_p4_abo'] ?? 311.01),
                    'prix_kwh' => floatval($this->configData['gaz_propane_p4_kwh'] ?? 0.10064)
                );
            }
        }
    }
    
    /**
     * Calculer les coûts avec la tranche tarifaire déterminée
     */
    private function calculateCostsWithTranche($consommationTotale, $trancheTarifaire) {
        // Abonnement annuel (abo_mensuel * 12)
        $abonnementAnnuel = $trancheTarifaire['abo_mensuel'] * 12;
        
        // Coût consommation (prix_kwh * consommation)
        $coutConsommation = $consommationTotale * $trancheTarifaire['prix_kwh'];
        
        // Total TTC
        $totalTTC = $abonnementAnnuel + $coutConsommation;
        
        return array(
            'abonnement_annuel' => round($abonnementAnnuel, 2),
            'consommation' => round($coutConsommation, 2),
            'total_ttc' => round($totalTTC, 2),
            'prix_kwh' => $trancheTarifaire['prix_kwh'],
            'abo_mensuel' => $trancheTarifaire['abo_mensuel']
        );
    }
    
    /**
     * Calculer les consommations par usage
     */
    private function calculateConsommations() {
        $nbPersonnes = max(1, intval($this->userData['nb_personnes'] ?? 1));
        $superficie = max(1, intval($this->userData['superficie'] ?? 0));
        
        $consommations = array(
            'chauffage' => 0,
            'eau_chaude' => 0,
            'cuisson' => 0,
            'total' => 0
        );
        
        // CHAUFFAGE AU GAZ
        if (($this->userData['chauffage_gaz'] ?? 'non') === 'oui') {
            $consommations['chauffage'] = $this->calculateChauffage($superficie);
        }
        
        // EAU CHAUDE (400 kWh/personne/an selon Excel)
        if (($this->userData['eau_chaude'] ?? 'autre') === 'gaz') {
            $consommations['eau_chaude'] = $this->calculateEauChaude($nbPersonnes);
        }
        
        // CUISSON (50 kWh/personne/an selon Excel)
        if (($this->userData['cuisson'] ?? 'autre') === 'gaz') {
            $consommations['cuisson'] = $this->calculateCuisson($nbPersonnes);
        }
        
        // TOTAL
        $consommations['total'] = $consommations['chauffage'] + 
                                  $consommations['eau_chaude'] + 
                                  $consommations['cuisson'];
        
        return $consommations;
    }
    
    /**
     * Calcul chauffage selon superficie et isolation
     */
    private function calculateChauffage($superficie) {
        $isolation = $this->userData['isolation'] ?? 'faible';
        $typeLogement = $this->userData['type_logement'] ?? 'maison';
        
        // Consommation par m² selon isolation (valeurs Excel G28:H31)
        $consoParM2 = $this->getConsoParM2($isolation);
        
        // Coefficient type logement
        $coefficient = ($typeLogement === 'appartement') ? 0.85 : 1.0;
        
        // Calcul final
        $surfaceChauffee = $superficie * $coefficient;
        return round($surfaceChauffee * $consoParM2);
    }
    
    /**
     * Consommation par m² selon isolation - Valeurs Excel exactes
     */
    private function getConsoParM2($isolation) {
        // Mapping des niveaux d'isolation avec les valeurs Excel
        $defaults = array(
            'faible' => 160,      // Niveau 1: Très mal isolé
            'correcte' => 110,     // Niveau 2: Mal isolé
            'bonne' => 70,       // Niveau 3: Bien isolé
            'excellente' => 20    // Niveau 4: Très bien isolé
        );
        
        // Utiliser la config si disponible, sinon les defaults
        $configKey = "gaz_chauffage_niveau_" . $this->getIsolationLevel($isolation);
        return floatval($this->configData[$configKey] ?? $defaults[$isolation] ?? 110);
    }
    
    /**
     * Convertir le nom d'isolation en niveau (1-4)
     */
    private function getIsolationLevel($isolation) {
        $mapping = array(
            'faible' => 1,
            'correcte' => 2,
            'bonne' => 3,
            'excellente' => 4
        );
        return $mapping[$isolation] ?? 3;
    }
    
    /**
     * Calcul eau chaude selon nombre de personnes
     */
    private function calculateEauChaude($nbPersonnes) {
        // 400 kWh par personne selon Excel (K29)
        $consoParPersonne = floatval($this->configData['gaz_eau_chaude_par_personne'] ?? 400);
        return $nbPersonnes * $consoParPersonne;
    }
    
    /**
     * Calcul cuisson selon nombre de personnes
     */
    private function calculateCuisson($nbPersonnes) {
        // 50 kWh par personne selon Excel (K28)
        $consoParPersonne = floatval($this->configData['gaz_cuisson_par_personne'] ?? 50);
        return $nbPersonnes * $consoParPersonne;
    }
    
    /**
     * Construire la répartition pour l'affichage
     */
    private function buildRepartition($consommations) {
        $repartition = array();
        
        if ($consommations['chauffage'] > 0) {
            $repartition['chauffage'] = $consommations['chauffage'];
        }
        
        if ($consommations['eau_chaude'] > 0) {
            $repartition['eau_chaude'] = $consommations['eau_chaude'];
        }
        
        if ($consommations['cuisson'] > 0) {
            $repartition['cuisson'] = $consommations['cuisson'];
        }
        
        return $repartition;
    }
    
    /**
     * Validation des données utilisateur
     */
    private function validateUserData() {
        $superficie = intval($this->userData['superficie'] ?? 0);
        $nbPersonnes = intval($this->userData['nb_personnes'] ?? 0);
        
        if ($superficie < 20 || $superficie > 1000) {
            return false;
        }
        
        if ($nbPersonnes < 1 || $nbPersonnes > 20) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Retourner une erreur
     */
    private function returnError($message) {
        return array('success' => false, 'error' => $message);
    }
}