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
        
        // Calculer les résultats factices
        $results = $this->getFakeResults();
        
        $this->consoleLog("✅ Calcul terminé avec succès");
        
        return array(
            'success' => true,
            'data' => $results,
            'console_logs' => $this->consoleLogs // Envoyer les logs au JavaScript
        );
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
        
        // Quelques exemples de config
        $exemples = ['puissance_defaut', 'chauffe_eau', 'base_kwh_15'];
        foreach ($exemples as $exemple) {
            if (isset($this->configData[$exemple])) {
                $this->consoleLog("⚙️ {$exemple}: " . $this->configData[$exemple]);
            }
        }
        
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
     * Résultats factices pour test
     */
    private function getFakeResults() {
        $surface = isset($this->userData['surface']) ? intval($this->userData['surface']) : 100;
        $nbPersonnes = isset($this->userData['nb_personnes']) ? intval($this->userData['nb_personnes']) : 2;
        
        $consommationBase = $surface * 50;
        $consommationPersonnes = $nbPersonnes * 500;
        $consommationTotale = $consommationBase + $consommationPersonnes;
        
        $this->consoleLog("🧮 === CALCUL FACTICE ===");
        $this->consoleLog("🏠 Surface: {$surface}m² × 50 = {$consommationBase} kWh");
        $this->consoleLog("👥 Personnes: {$nbPersonnes} × 500 = {$consommationPersonnes} kWh");
        $this->consoleLog("⚡ Total: {$consommationTotale} kWh/an");
        
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
                'chauffage' => 800,
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