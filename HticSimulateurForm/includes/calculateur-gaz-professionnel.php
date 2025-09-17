<?php
/**
 * Calculateur Gaz Professionnel - Version simplifiée
 * Basé sur Excel "Conso Gaz Professionnel"
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction principale de calcul gaz professionnel
 */
function htic_calculateur_gaz_professionnel($userData, $configData) {
    try {
        $calculator = new HticCalculateurGazProfessionnel($userData, $configData);
        return $calculator->calculate();
    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => 'Erreur de calcul: ' . $e->getMessage()
        );
    }
}

class HticCalculateurGazProfessionnel {
    
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
        
        // 1. Déterminer le type de gaz selon la commune
        $typeGaz = $this->determineTypeGaz();
        
        // 2. Récupérer la consommation prévisionnelle
        $consommationTotale = floatval($this->userData['consommation_previsionnelle'] ?? 0);
        
        // 3. Déterminer la tranche tarifaire
        $trancheTarifaire = $this->determineTrancheTarifaire($typeGaz, $consommationTotale);
        
        // 4. Vérifier si c'est un devis personnalisé (GOM2 pour gaz naturel > 35000 kWh)
        if ($trancheTarifaire['nom'] === 'GOM2') {
            return array(
                'success' => true,
                'data' => array(
                    'devis_personnalise' => true,
                    'message' => 'Pour une consommation supérieure à 35 000 kWh/an en gaz naturel, nous vous contacterons pour établir un devis personnalisé.',
                    'type_gaz' => $typeGaz,
                    'commune' => $this->userData['commune'] ?? '',
                    'consommation_annuelle' => $consommationTotale,
                    'recap' => array(
                        'commune' => $this->userData['commune'] ?? '',
                        'consommation_previsionnelle' => $consommationTotale,
                        'type_gaz' => $typeGaz
                    )
                )
            );
        }
        
        // 5. Calculer les coûts
        $couts = $this->calculateCosts($consommationTotale, $trancheTarifaire);
        
        // 6. Résultats finaux
        $results = array(
            'devis_personnalise' => false,
            
            // Informations générales
            'type_gaz' => $typeGaz,
            'commune' => $this->userData['commune'] ?? '',
            'tranche_tarifaire' => $trancheTarifaire['nom'],
            
            // Consommation
            'consommation_annuelle' => $consommationTotale,
            'consommation_totale' => $consommationTotale,
            
            // Coûts
            'cout_annuel_ttc' => $couts['total_ttc'],
            'cout_abonnement' => $couts['abonnement_annuel'],
            'cout_consommation' => $couts['consommation'],
            'prix_kwh' => $couts['prix_kwh'],
            'total_annuel' => $couts['total_ttc'],
            'total_mensuel' => round($couts['total_ttc'] / 12, 2),
            
            // Détails tarifs
            'abo_mensuel' => $couts['abo_mensuel'],
            
            // Récapitulatif pour affichage
            'recap' => array(
                'commune' => $this->userData['commune'] ?? '',
                'consommation_previsionnelle' => $consommationTotale,
                'type_gaz' => $typeGaz,
                'tranche' => $trancheTarifaire['nom'],
                'entreprise' => $this->userData['entreprise'] ?? '',
                'email' => $this->userData['email'] ?? '',
                'telephone' => $this->userData['telephone'] ?? ''
            )
        );
        
        return array('success' => true, 'data' => $results);
    }
    
    /**
     * Déterminer le type de gaz selon la commune
     */
    private function determineTypeGaz() {
        $commune = strtoupper(trim($this->userData['commune'] ?? ''));
        
        // Table de correspondance exacte selon Excel
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
     * Déterminer la tranche tarifaire selon le type de gaz et la consommation
     */
    private function determineTrancheTarifaire($typeGaz, $consommationTotale) {
        if ($typeGaz === 'Gaz naturel') {
            // Logique Gaz Naturel Pro
            if ($consommationTotale < 4000) {
                return array(
                    'nom' => 'GOM0',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_naturel_gom0_abo'] ?? 8.92),
                    'prix_kwh' => floatval($this->configData['pro_gaz_naturel_gom0_kwh'] ?? 0.1265)
                );
            } elseif ($consommationTotale < 35000) {
                return array(
                    'nom' => 'GOM1',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_naturel_gom1_abo'] ?? 22.4175),
                    'prix_kwh' => floatval($this->configData['pro_gaz_naturel_gom1_kwh'] ?? 0.0978)
                );
            } else {
                // GOM2 - Devis personnalisé
                return array(
                    'nom' => 'GOM2',
                    'abo_mensuel' => 0,
                    'prix_kwh' => 0
                );
            }
        } else {
            // Logique Gaz Propane Pro (identique au résidentiel)
            if ($consommationTotale < 1000) {
                return array(
                    'nom' => 'P0',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_propane_p0_abo'] ?? 4.64),
                    'prix_kwh' => floatval($this->configData['pro_gaz_propane_p0_kwh'] ?? 0.12479)
                );
            } elseif ($consommationTotale < 6000) {
                return array(
                    'nom' => 'P1',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_propane_p1_abo'] ?? 5.26),
                    'prix_kwh' => floatval($this->configData['pro_gaz_propane_p1_kwh'] ?? 0.11852)
                );
            } elseif ($consommationTotale < 30000) {
                return array(
                    'nom' => 'P2',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_propane_p2_abo'] ?? 16.06),
                    'prix_kwh' => floatval($this->configData['pro_gaz_propane_p2_kwh'] ?? 0.11305)
                );
            } elseif ($consommationTotale < 350000) {
                return array(
                    'nom' => 'P3',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_propane_p3_abo'] ?? 34.56),
                    'prix_kwh' => floatval($this->configData['pro_gaz_propane_p3_kwh'] ?? 0.10273)
                );
            } else {
                // P4
                return array(
                    'nom' => 'P4',
                    'abo_mensuel' => floatval($this->configData['pro_gaz_propane_p4_abo'] ?? 311.01),
                    'prix_kwh' => floatval($this->configData['pro_gaz_propane_p4_kwh'] ?? 0.10064)
                );
            }
        }
    }
    
    /**
     * Calculer les coûts
     */
    private function calculateCosts($consommationTotale, $trancheTarifaire) {
        // Abonnement annuel
        $abonnementAnnuel = $trancheTarifaire['abo_mensuel'] * 12;
        
        // Coût consommation
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
     * Validation des données utilisateur
     */
    private function validateUserData() {
        $consommation = floatval($this->userData['consommation_previsionnelle'] ?? 0);
        $consoMin = floatval($this->configData['pro_conso_min'] ?? 100);
        $consoMax = floatval($this->configData['pro_conso_max'] ?? 1000000);
        
        if ($consommation < $consoMin || $consommation > $consoMax) {
            return false;
        }
        
        if (empty($this->userData['commune'])) {
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