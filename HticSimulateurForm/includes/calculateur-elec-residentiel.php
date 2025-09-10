<?php
/**
 * Calculateur Électricité Résidentiel
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
        
        // Affichage des données récupérées
        $this->displayReceivedData($validatedData);
        
        // TODO: Ici sera ajouté le calcul réel
        $results = $this->performCalculation($validatedData);
        
        $this->logDebug("Calcul terminé avec succès");
        
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
            if (empty($extractedData[$field])) {
                $this->logDebug("ERREUR: Champ obligatoire manquant: " . $field);
                return false;
            }
        }
        
        // Validation spécifique: isolation obligatoire si chauffage électrique
        $chauffagesElectriques = array('convecteurs', 'inertie', 'clim_reversible', 'pac');
        if (in_array($extractedData['type_chauffage'], $chauffagesElectriques) && empty($extractedData['isolation'])) {
            $this->logDebug("ERREUR: Isolation obligatoire pour le chauffage électrique");
            return false;
        }
        
        $this->logDebug("Validation des données: OK");
        
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
        
        $this->logDebug("Extraction: {$key} = " . print_r($value, true));
        
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
        $this->logDebug("- Électroménagers sélectionnés: " . implode(', ', $data['electromenagers']));
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
            if (isset($this->configData['eau_chaude'])) {
                $this->logDebug("- Consommation eau chaude: " . $this->configData['eau_chaude'] . " kWh/an");
            }
            if (isset($this->configData['electromenagers'])) {
                $this->logDebug("- Consommation électroménagers: " . $this->configData['electromenagers'] . " kWh/an");
            }
        } else {
            $this->logDebug("ATTENTION: Aucune configuration disponible");
        }
    }
    
    /**
     * Calcul factice pour le moment (sera remplacé par le vrai calcul)
     */
    private function performCalculation($data) {
        $this->logDebug("=== CALCUL EN COURS ===");
        $this->logDebug("(Pour le moment, calcul factice - sera implémenté plus tard)");
        
        // Calcul factice basique pour test
        $surface = $data['surface'];
        $consommationEstimee = $surface * 50; // 50 kWh/m² de base
        
        $this->logDebug("Consommation estimée (calcul factice): " . $consommationEstimee . " kWh/an");
        
        return array(
            'consommation_annuelle' => $consommationEstimee,
            'puissance_recommandee' => $surface > 120 ? '15' : '12',
            'tarifs' => array(
                'base' => array(
                    'total_annuel' => 1200,
                    'total_mensuel' => 100
                ),
                'hc' => array(
                    'total_annuel' => 1100,
                    'total_mensuel' => 92
                )
            ),
            'repartition' => array(
                'chauffage' => 800,
                'eau_chaude' => $data['eau_chaude'] === 'oui' ? 1800 : 0,
                'electromenagers' => 1497,
                'eclairage' => 750,
                'autres' => 300
            ),
            'recap' => $data
        );
    }
    
    /**
     * Logging pour debug
     */
    private function logDebug($message) {
        if ($this->debugMode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("[HTIC CALCULATEUR ELEC] " . $message);
        }
        
        // Toujours afficher en console navigateur si c'est une requête AJAX
        if (wp_doing_ajax()) {
            echo "<!-- DEBUG: " . esc_html($message) . " -->\n";
        }
    }
    
    /**
     * Retourner une erreur
     */
    private function returnError($message) {
        $this->logDebug("ERREUR: " . $message);
        
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