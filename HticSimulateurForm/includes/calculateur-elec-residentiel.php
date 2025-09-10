<?php
/**
 * Calculateur Électricité Résidentiel - VERSION AVEC CONSOLE LOG
 * Fichier: includes/calculateur-elec-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class HticCalculateurElecResidentiel {
    
    private $userData;
    private $configData;
    private $consoleLogs = [];
    
    public function __construct($userData = array(), $configData = array()) {
        $this->userData = $userData;
        $this->configData = $configData;
        
        $this->consoleLog("=== CALCULATEUR INITIALISÉ ===");
    }
    
    /**
     * Point d'entrée principal
     */
    public function calculate() {
        $this->consoleLog("🚀 Début du calcul électricité résidentiel");
        
        // Afficher les données reçues
        $this->displayReceivedData();
        
        // Calculer le chauffage
        $consoChauffage = $this->calculateChauffage();
        
        // Pour l'instant, utiliser des valeurs factices pour le reste
        $results = $this->getFakeResults();
        
        // Remplacer la consommation chauffage par le vrai calcul
        $results['repartition']['chauffage'] = $consoChauffage;
        
        // Recalculer la consommation totale
        $consommationTotale = array_sum($results['repartition']);
        $results['consommation_annuelle'] = $consommationTotale;
        
        $this->consoleLog("✅ Calcul terminé avec succès");
        
        return array(
            'success' => true,
            'data' => $results,
            'console_logs' => $this->consoleLogs
        );
    }
    
    /**
     * Calcul de la consommation chauffage électrique
     */
    private function calculateChauffage() {
        // Vérifier si c'est un chauffage électrique
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (!in_array($this->userData['type_chauffage'], $chauffagesElectriques)) {
            $this->consoleLog("🔥 Pas de chauffage électrique sélectionné");
            return 0;
        }

        $surface = intval($this->userData['surface']);
        $typeLogement = $this->userData['type_logement'];
        $typeChauffage = $this->userData['type_chauffage'];
        $isolation = $this->userData['isolation'];

        // Coefficient logement (maison = 1.0, appartement = 0.95)
        $coeffLogement = ($typeLogement === 'appartement') ? 0.95 : 1.0;

        // Mapping des types d'isolation vers les clés de configuration
        $isolationMapping = array(
            'avant_1980' => 'mauvaise',
            '1980_2000' => 'moyenne', 
            'apres_2000' => 'bonne',
            'renovation' => 'tres_bonne'
        );

        $isolationKey = $isolationMapping[$isolation];

        // Construire la clé de configuration selon le format du back
        $configKey = $typeLogement . '_' . $typeChauffage . '_' . $isolationKey;

        // Récupérer la consommation par m² depuis la configuration
        $consoParM2 = 0;
        if (isset($this->configData[$configKey])) {
            $consoParM2 = floatval($this->configData[$configKey]);
        } else {
            $this->consoleLog("❌ ERREUR: Clé de configuration manquante: {$configKey}");
            return 0;
        }

        // Calcul final selon la formule Excel
        $consommationChauffage = $surface * $consoParM2 * $coeffLogement;

        $this->consoleLog("🔥 CALCUL CHAUFFAGE:");
        $this->consoleLog("   Surface: {$surface} m²");
        $this->consoleLog("   Type logement: {$typeLogement} (coeff: {$coeffLogement})");
        $this->consoleLog("   Type chauffage: {$typeChauffage}");
        $this->consoleLog("   Isolation: {$isolation} → {$isolationKey}");
        $this->consoleLog("   Clé config: {$configKey}");
        $this->consoleLog("   Conso/m²: {$consoParM2} kWh");
        $this->consoleLog("   Résultat: {$surface} × {$consoParM2} × {$coeffLogement} = {$consommationChauffage} kWh/an");

        return round($consommationChauffage);
    }
    
    /**
     * Afficher toutes les données reçues
     */
    private function displayReceivedData() {
        $this->consoleLog("📋 === DONNÉES REÇUES DU FORMULAIRE ===");
        
        // Données individuelles
        foreach ($this->userData as $key => $value) {
            if (is_array($value)) {
                $this->consoleLog("📄 {$key}: [" . implode(', ', $value) . "]");
            } else {
                $this->consoleLog("📄 {$key}: {$value}");
            }
        }
        
        $this->consoleLog("⚙️ === CONFIGURATION DISPONIBLE ===");
        $this->consoleLog("📊 Nombre de paramètres config: " . count($this->configData));
        
        // Vérification des données obligatoires
        $obligatoires = ['type_logement', 'surface', 'nb_personnes', 'type_chauffage'];
        $this->consoleLog("🔍 === VÉRIFICATION DONNÉES OBLIGATOIRES ===");
        
        foreach ($obligatoires as $champ) {
            $valeur = isset($this->userData[$champ]) ? $this->userData[$champ] : 'MANQUANT';
            $status = ($valeur !== 'MANQUANT') ? '✅' : '❌';
            $this->consoleLog("{$status} {$champ}: {$valeur}");
        }
    }
    
    /**
     * Résultats factices pour test (sera remplacé progressivement)
     */
    private function getFakeResults() {
        $surface = isset($this->userData['surface']) ? intval($this->userData['surface']) : 100;
        $nbPersonnes = isset($this->userData['nb_personnes']) ? intval($this->userData['nb_personnes']) : 2;
        
        $consommationBase = $surface * 50;
        $consommationPersonnes = $nbPersonnes * 500;
        $consommationTotale = $consommationBase + $consommationPersonnes;
        
        return array(
            'consommation_annuelle' => $consommationTotale,
            'puissance_recommandee' => ($surface > 120) ? '15' : '12',
            'tarifs' => array(
                'base' => array(
                    'total_annuel' => 1200,
                    'total_mensuel' => 100,
                    'abonnement_mensuel' => 22.21,
                    'cout_kwh' => 900
                ),
                'hc' => array(
                    'total_annuel' => 1100,
                    'total_mensuel' => 92,
                    'abonnement_mensuel' => 23.57,
                    'cout_kwh' => 800
                )
            ),
            'repartition' => array(
                'chauffage' => 0,
                'eau_chaude' => (isset($this->userData['eau_chaude']) && $this->userData['eau_chaude'] === 'oui') ? 1800 : 0,
                'electromenagers' => 1500,
                'eclairage' => 400,
                'autres' => 300
            ),
            'recap' => $this->userData
        );
    }
    
    /**
     * Ajouter un message à la console (sera envoyé au JavaScript)
     */
    private function consoleLog($message) {
        $this->consoleLogs[] = "[CALCULATEUR PHP] " . $message;
    }
}

/**
 * Fonction d'entrée pour les appels AJAX
 */
function htic_calculateur_elec_residentiel($userData, $configData) {
    $calculateur = new HticCalculateurElecResidentiel($userData, $configData);
    return $calculateur->calculate();
}