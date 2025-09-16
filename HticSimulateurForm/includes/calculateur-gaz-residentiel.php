<?php
/**
 * Calculateur Gaz Résidentiel CORRIGÉ - Logique Excel exacte
 * Reproduit fidèlement les calculs du fichier Excel "Conso Gaz Résidentiel"
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class HticCalculateurGazResidentiel {
    
    private $userData;
    private $configData;
    private $debugMode;
    
    public function __construct($userData = array(), $configData = array(), $debugMode = false) {
        $this->userData = $userData;
        $this->configData = $configData;
        $this->debugMode = $debugMode;
    }
    
    /**
     * Point d'entrée principal - reproduit exactement l'Excel
     */
    public function calculate() {
        if (!$this->validateUserData()) {
            return $this->returnError("Données utilisateur invalides");
        }
        
        // ÉTAPE 1: Déterminer type de gaz (cellule I9)
        $typeGaz = $this->determineTypeGaz();
        
        // ÉTAPE 2: Calculer consommations (C26, C27, C28)
        $consommations = $this->calculateConsommationsExcel();
        
        // ÉTAPE 3: Déterminer tranche tarifaire (cellule J9)
        $tranche = $this->determineTrancheTarifaire($consommations['total'], $typeGaz);
        
        // ÉTAPE 4: Calculer coûts (K9, K10, K11)
        $couts = $this->calculateCostsExcel($consommations, $typeGaz, $tranche);
        
        // Résultats finaux
        $results = array(
            'type_gaz' => $typeGaz,
            'tranche_tarifaire' => $tranche,
            'commune' => $this->userData['commune'] ?? '',
            
            // Consommations détaillées (Excel C26:C28)
            'detail_chauffage' => $consommations['chauffage'],
            'detail_eau_chaude' => $consommations['eau_chaude'], 
            'detail_cuisson' => $consommations['cuisson'],
            'consommation_totale' => $consommations['total'], // Excel F11
            
            // Coûts détaillés (Excel K9:K11)
            'cout_abonnement_annuel' => $couts['abonnement'], // K9
            'cout_consommation' => $couts['consommation'], // K10
            'total_ttc' => $couts['total'], // K11
            'total_annuel' => $couts['total'], // Alias
            'total_mensuel' => round($couts['total'] / 12, 2),
            
            // Informations tarif appliqué
            'abonnement_mensuel' => $couts['abo_mensuel'],
            'prix_kwh_applique' => $couts['prix_kwh'],
            
            // Debug Excel
            'debug_excel' => $this->debugMode ? array(
                'formule_I9' => "VLOOKUP(commune, table_communes, 2)",
                'formule_F11' => "SUM(C26:C28) = {$consommations['total']}",
                'formule_J9' => "IF(type_gaz=propane, [logique_propane], [logique_naturel]) = {$tranche}",
                'formule_K9' => "abonnement × 12 = {$couts['abonnement']}",
                'formule_K10' => "consommation × prix_kwh = {$couts['consommation']}",
                'formule_K11' => "K9 + K10 = {$couts['total']}"
            ) : null
        );
        
        return array('success' => true, 'data' => $results);
    }
    
    /**
     * ÉTAPE 1: Déterminer type de gaz (Excel I9)
     * Formule Excel: =VLOOKUP(F5,$M$20:$N$38,2,FALSE)
     */
    private function determineTypeGaz() {
        $commune = strtoupper(trim($this->userData['commune'] ?? ''));
        $communesGaz = $this->configData['communes_gaz'] ?? array();
        $communesTypes = $this->configData['communes_types'] ?? array();
        
        // Recherche exacte dans la table des communes
        if (isset($communesTypes[$commune])) {
            return $communesTypes[$commune];
        }
        
        foreach ($communesGaz as $communeNom) {
            if (strtoupper(trim($communeNom)) === $commune) {
                return $communesTypes[$communeNom] ?? 'naturel';
            }
        }
        
        // Fallback sur choix utilisateur
        if (($this->userData['offre'] ?? 'base') === 'propane') {
            return 'propane';
        }
        
        return 'naturel'; // Défaut
    }
    
    /**
     * ÉTAPE 2: Calculer consommations (Excel C26, C27, C28)
     */
    private function calculateConsommationsExcel() {
        $nbPersonnes = max(1, intval($this->userData['nb_personnes'] ?? 1));
        $superficie = max(1, intval($this->userData['superficie'] ?? 0));
        
        $consommations = array(
            'chauffage' => 0,
            'eau_chaude' => 0,
            'cuisson' => 0,
            'total' => 0
        );
        
        // CUISSON (C26) - Excel: IF(B26=TRUE,(C6*K28),0)
        if (($this->userData['cuisson'] ?? 'autre') === 'gaz') {
            $facteurCuisson = floatval($this->configData['gaz_cuisson_par_personne'] ?? 50);
            $consommations['cuisson'] = $nbPersonnes * $facteurCuisson;
        }
        
        // EAU CHAUDE (C27) - Excel: IF(B27=TRUE,(C6*K29),0)  
        if (($this->userData['eau_chaude'] ?? 'autre') === 'gaz') {
            $facteurEauChaude = floatval($this->configData['gaz_eau_chaude_par_personne'] ?? 400);
            $consommations['eau_chaude'] = $nbPersonnes * $facteurEauChaude;
        }
        
        // CHAUFFAGE (C28) - Excel: IF(B28=TRUE,(A6*C29),0)
        if (($this->userData['chauffage_gaz'] ?? 'non') === 'oui') {
            $consommations['chauffage'] = $this->calculateChauffageExcel($superficie);
        }
        
        // TOTAL (F11) - Excel: SUM(C26:C28)
        $consommations['total'] = $consommations['chauffage'] + 
                                  $consommations['eau_chaude'] + 
                                  $consommations['cuisson'];
        
        return $consommations;
    }
    
    /**
     * Calcul chauffage selon isolation Excel (C28 = A6 * C29)
     */
    private function calculateChauffageExcel($superficie) {
        $isolation = $this->userData['isolation'] ?? 'avant_1980';
        
        // Mapping isolation utilisateur → niveau Excel
        $niveauExcel = $this->mapIsolationToNiveau($isolation);
        
        // Consommation par m² selon niveau Excel (G28:H31)
        $consoParM2 = $this->getConsoParM2Excel($niveauExcel);
        
        // Coefficient type logement
        $coefficient = $this->getCoefficient();
        
        // Calcul final
        $surfaceChauffee = $superficie * $coefficient;
        return round($surfaceChauffee * $consoParM2);
    }
    
    /**
     * Mapping isolation formulaire → niveau Excel
     */
    private function mapIsolationToNiveau($isolation) {
        switch ($isolation) {
            case 'avant_1980': return 1;
            case '1980_2000': return 2;  
            case 'apres_2000': return 3;
            case 'renovation': return 4;
            default: return 1;
        }
    }
    
    /**
     * Consommation par m² selon niveau Excel (G28:H31)
     */
    private function getConsoParM2Excel($niveau) {
        $configKey = "gaz_chauffage_niveau_{$niveau}";
        $defaults = array(1 => 160, 2 => 70, 3 => 110, 4 => 20);
        return floatval($this->configData[$configKey] ?? $defaults[$niveau]);
    }
    
    /**
     * ÉTAPE 3: Déterminer tranche tarifaire (Excel J9)
     * Formule complexe avec IF imbriqués
     */
    private function determineTrancheTarifaire($consommationTotale, $typeGaz) {
        if ($typeGaz === 'propane') {
            // Logique propane (5 tranches)
            if ($consommationTotale < 1000) return 'P0';
            if ($consommationTotale < 6000) return 'P1';
            if ($consommationTotale < 30000) return 'P2';
            if ($consommationTotale < 350000) return 'P3';
            return 'P4';
        } else {
            // Logique gaz naturel (2 tranches)
            $seuil = intval($this->configData['seuil_gom_naturel'] ?? 4000);
            return ($consommationTotale < $seuil) ? 'GOM0' : 'GOM1';
        }
    }
    
    /**
     * ÉTAPE 4: Calcul coûts Excel (K9, K10, K11)
     */
    private function calculateCostsExcel($consommations, $typeGaz, $tranche) {
        // Récupérer tarifs selon tranche
        $tarifs = $this->getTarifsForTranche($typeGaz, $tranche);
        
        // ABONNEMENT ANNUEL (K9) - Excel: abonnement_mensuel * 12
        $abonnementAnnuel = $tarifs['abo_mensuel'] * 12;
        
        // COÛT CONSOMMATION (K10) - Excel: prix_kwh * consommation_totale
        $coutConsommation = $tarifs['prix_kwh'] * $consommations['total'];
        
        // TOTAL (K11) - Excel: K9 + K10
        $total = $abonnementAnnuel + $coutConsommation;
        
        return array(
            'abonnement' => round($abonnementAnnuel, 2),
            'consommation' => round($coutConsommation, 2),
            'total' => round($total, 2),
            'abo_mensuel' => $tarifs['abo_mensuel'],
            'prix_kwh' => $tarifs['prix_kwh']
        );
    }
    
    /**
     * Récupérer tarifs selon tranche
     */
    private function getTarifsForTranche($typeGaz, $tranche) {
        if ($typeGaz === 'propane') {
            $tranches = array('P0' => 'p0', 'P1' => 'p1', 'P2' => 'p2', 'P3' => 'p3', 'P4' => 'p4');
            $suffixe = $tranches[$tranche] ?? 'p0';
            return array(
                'abo_mensuel' => floatval($this->configData["gaz_propane_{$suffixe}_abo"] ?? 4.64),
                'prix_kwh' => floatval($this->configData["gaz_propane_{$suffixe}_kwh"] ?? 0.12479)
            );
        } else {
            $suffixe = strtolower($tranche); // gom0 ou gom1
            return array(
                'abo_mensuel' => floatval($this->configData["gaz_naturel_{$suffixe}_abo"] ?? 8.92),
                'prix_kwh' => floatval($this->configData["gaz_naturel_{$suffixe}_kwh"] ?? 0.1265)
            );
        }
    }
    
    /**
     * Coefficient logement
     */
    private function getCoefficient() {
        $typeLogement = $this->userData['type_logement'] ?? 'maison';
        if ($typeLogement === 'appartement') {
            return floatval($this->configData['coefficient_appartement'] ?? 0.8);
        }
        return floatval($this->configData['coefficient_maison'] ?? 1.0);
    }
    
    /**
     * Validation données
     */
    private function validateUserData() {
        $superficie = intval($this->userData['superficie'] ?? 0);
        $nbPersonnes = intval($this->userData['nb_personnes'] ?? 0);
        
        return ($superficie >= 10 && $superficie <= 1000 && 
                $nbPersonnes >= 1 && $nbPersonnes <= 20);
    }
    
    /**
     * Retour erreur
     */
    private function returnError($message) {
        return array('success' => false, 'error' => $message);
    }
}