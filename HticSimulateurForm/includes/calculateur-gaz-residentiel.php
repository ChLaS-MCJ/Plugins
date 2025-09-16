<?php
// ==========================================
// FICHIER: calculateur-gaz-residentiel.php
// ==========================================
/**
 * Calculateur Gaz Résidentiel - Backend de calcul
 * Fichier: calculateur-gaz-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour le calcul des consommations et coûts gaz résidentiel
 */
class HticCalculateurGazResidentiel {
    
    private $config;
    
    public function __construct() {
        $this->config = $this->get_configuration();
    }
    
    /**
     * Récupérer la configuration depuis la base de données
     */
    private function get_configuration() {
        $default_config = array(
            // Tarification
            'gaz_abo_mensuel' => 102.12,
            'gaz_prix_kwh' => 0.0878,
            
            // Consommations de base
            'gaz_cuisson_annuel' => 250,
            'gaz_eau_chaude_base' => 2000,
            'gaz_eau_chaude_par_personne' => 400,
            'gaz_cuisson_par_personne' => 50,
            
            // Chauffage par isolation (kWh/m²/an)
            'gaz_chauffage_avant_1980' => 160,
            'gaz_chauffage_1980_2000' => 70,
            'gaz_chauffage_apres_2000' => 110,
            'gaz_chauffage_renovation' => 20,
            
            // Coefficients
            'coefficient_maison' => 1.0,
            'coefficient_appartement' => 0.85,
            'temperature_reference' => 19.0,
            'majoration_par_degre' => 7.0,
            
            // Limites
            'surface_min_chauffage' => 15,
            'nb_personnes_min' => 1
        );
        
        $config = get_option('htic_simulateur_gaz_residentiel_data', $default_config);
        return array_merge($default_config, $config);
    }
    
    /**
     * Calculer la consommation et le coût total
     */
    public function calculer($donnees_formulaire) {
        try {
            // Validation des données
            $donnees_validees = $this->valider_donnees($donnees_formulaire);
            
            // Calculs détaillés
            $resultats = array(
                'donnees_utilisateur' => $donnees_validees,
                'consommation' => $this->calculer_consommation($donnees_validees),
                'cout' => array(),
                'details' => array(),
                'comparaisons' => array(),
                'conseils' => array()
            );
            
            // Calcul des coûts
            $resultats['cout'] = $this->calculer_cout($resultats['consommation']);
            
            // Détails par usage
            $resultats['details'] = $this->generer_details($donnees_validees, $resultats['consommation']);
            
            // Comparaisons et conseils
            $resultats['comparaisons'] = $this->generer_comparaisons($donnees_validees, $resultats['consommation']);
            $resultats['conseils'] = $this->generer_conseils($donnees_validees, $resultats['consommation']);
            
            return $resultats;
            
        } catch (Exception $e) {
            return array(
                'error' => true,
                'message' => $e->getMessage(),
                'details' => 'Erreur dans le calcul gaz résidentiel'
            );
        }
    }
    
    /**
     * Valider les données du formulaire
     */
    private function valider_donnees($donnees) {
        $donnees_validees = array();
        
        // Données obligatoires
        $donnees_validees['surface'] = max(10, floatval($donnees['surface'] ?? 100));
        $donnees_validees['nb_personnes'] = max(1, intval($donnees['nb_personnes'] ?? 2));
        $donnees_validees['type_logement'] = sanitize_text_field($donnees['type_logement'] ?? 'maison');
        $donnees_validees['isolation'] = sanitize_text_field($donnees['isolation'] ?? 'apres_2000');
        
        // Usages (checkboxes)
        $donnees_validees['usages'] = array();
        if (isset($donnees['chauffage']) && $donnees['chauffage']) {
            $donnees_validees['usages']['chauffage'] = true;
        }
        if (isset($donnees['eau_chaude']) && $donnees['eau_chaude']) {
            $donnees_validees['usages']['eau_chaude'] = true;
        }
        if (isset($donnees['cuisson']) && $donnees['cuisson']) {
            $donnees_validees['usages']['cuisson'] = true;
        }
        
        // Paramètres optionnels
        $donnees_validees['temperature_souhaitee'] = floatval($donnees['temperature_souhaitee'] ?? 19);
        $donnees_validees['commune'] = sanitize_text_field($donnees['commune'] ?? '');
        
        return $donnees_validees;
    }
    
    /**
     * Calculer la consommation totale
     */
    private function calculer_consommation($donnees) {
        $consommation = array(
            'chauffage' => 0,
            'eau_chaude' => 0,
            'cuisson' => 0,
            'total' => 0
        );
        
        // CHAUFFAGE
        if (isset($donnees['usages']['chauffage']) && $donnees['surface'] >= $this->config['surface_min_chauffage']) {
            $conso_chauffage_m2 = $this->get_consommation_chauffage_par_isolation($donnees['isolation']);
            
            // Consommation de base par m²
            $consommation['chauffage'] = $donnees['surface'] * $conso_chauffage_m2;
            
            // Coefficient type de logement
            if ($donnees['type_logement'] === 'appartement') {
                $consommation['chauffage'] *= $this->config['coefficient_appartement'];
            } else {
                $consommation['chauffage'] *= $this->config['coefficient_maison'];
            }
            
            // Ajustement température
            $ecart_temperature = $donnees['temperature_souhaitee'] - $this->config['temperature_reference'];
            if ($ecart_temperature > 0) {
                $majoration = 1 + ($ecart_temperature * $this->config['majoration_par_degre'] / 100);
                $consommation['chauffage'] *= $majoration;
            }
        }
        
        // EAU CHAUDE SANITAIRE
        if (isset($donnees['usages']['eau_chaude'])) {
            $consommation['eau_chaude'] = $this->config['gaz_eau_chaude_base'];
            $consommation['eau_chaude'] += ($donnees['nb_personnes'] * $this->config['gaz_eau_chaude_par_personne']);
        }
        
        // CUISSON
        if (isset($donnees['usages']['cuisson'])) {
            $consommation['cuisson'] = $this->config['gaz_cuisson_annuel'];
            $consommation['cuisson'] += ($donnees['nb_personnes'] * $this->config['gaz_cuisson_par_personne']);
        }
        
        // TOTAL
        $consommation['total'] = $consommation['chauffage'] + $consommation['eau_chaude'] + $consommation['cuisson'];
        
        return $consommation;
    }
    
    /**
     * Obtenir la consommation de chauffage selon l'isolation
     */
    private function get_consommation_chauffage_par_isolation($isolation) {
        $consommations = array(
            'avant_1980' => $this->config['gaz_chauffage_avant_1980'],
            '1980_2000' => $this->config['gaz_chauffage_1980_2000'],
            'apres_2000' => $this->config['gaz_chauffage_apres_2000'],
            'renovation' => $this->config['gaz_chauffage_renovation']
        );
        
        return $consommations[$isolation] ?? $consommations['apres_2000'];
    }
    
    /**
     * Calculer les coûts
     */
    private function calculer_cout($consommation) {
        $cout_variable = $consommation['total'] * $this->config['gaz_prix_kwh'];
        $cout_fixe_annuel = $this->config['gaz_abo_mensuel'] * 12;
        
        return array(
            'consommation_kwh' => round($consommation['total'], 0),
            'cout_variable_annuel' => round($cout_variable, 2),
            'cout_fixe_annuel' => round($cout_fixe_annuel, 2),
            'cout_total_annuel' => round($cout_variable + $cout_fixe_annuel, 2),
            'cout_mensuel_moyen' => round(($cout_variable + $cout_fixe_annuel) / 12, 2),
            'prix_moyen_kwh' => $this->config['gaz_prix_kwh']
        );
    }
    
    /**
     * Générer les détails par usage
     */
    private function generer_details($donnees, $consommation) {
        $details = array();
        
        if ($consommation['chauffage'] > 0) {
            $cout_chauffage = $consommation['chauffage'] * $this->config['gaz_prix_kwh'];
            $details['chauffage'] = array(
                'consommation_kwh' => round($consommation['chauffage'], 0),
                'cout_annuel' => round($cout_chauffage, 2),
                'pourcentage' => round(($consommation['chauffage'] / $consommation['total']) * 100, 1),
                'consommation_par_m2' => round($consommation['chauffage'] / $donnees['surface'], 1)
            );
        }
        
        if ($consommation['eau_chaude'] > 0) {
            $cout_eau_chaude = $consommation['eau_chaude'] * $this->config['gaz_prix_kwh'];
            $details['eau_chaude'] = array(
                'consommation_kwh' => round($consommation['eau_chaude'], 0),
                'cout_annuel' => round($cout_eau_chaude, 2),
                'pourcentage' => round(($consommation['eau_chaude'] / $consommation['total']) * 100, 1),
                'consommation_par_personne' => round($consommation['eau_chaude'] / $donnees['nb_personnes'], 0)
            );
        }
        
        if ($consommation['cuisson'] > 0) {
            $cout_cuisson = $consommation['cuisson'] * $this->config['gaz_prix_kwh'];
            $details['cuisson'] = array(
                'consommation_kwh' => round($consommation['cuisson'], 0),
                'cout_annuel' => round($cout_cuisson, 2),
                'pourcentage' => round(($consommation['cuisson'] / $consommation['total']) * 100, 1)
            );
        }
        
        return $details;
    }
    
    /**
     * Générer des comparaisons
     */
    private function generer_comparaisons($donnees, $consommation) {
        $comparaisons = array();
        
        // Comparaison avec une maison moyenne
        $comparaisons['maison_moyenne'] = array(
            'surface_reference' => 100,
            'nb_personnes_reference' => 4,
            'consommation_reference' => 15000, // kWh/an
            'ecart_pourcentage' => round((($consommation['total'] - 15000) / 15000) * 100, 1)
        );
        
        // Impact amélioration isolation
        if ($donnees['isolation'] !== 'renovation') {
            $conso_avec_renovation = $donnees['surface'] * $this->config['gaz_chauffage_renovation'];
            if ($donnees['type_logement'] === 'appartement') {
                $conso_avec_renovation *= $this->config['coefficient_appartement'];
            }
            
            $economie_chauffage = $consommation['chauffage'] - $conso_avec_renovation;
            $economie_cout = $economie_chauffage * $this->config['gaz_prix_kwh'];
            
            $comparaisons['avec_renovation'] = array(
                'economie_kwh' => round($economie_chauffage, 0),
                'economie_euros' => round($economie_cout, 2),
                'economie_pourcentage' => round(($economie_chauffage / $consommation['chauffage']) * 100, 1)
            );
        }
        
        return $comparaisons;
    }
    
    /**
     * Générer des conseils personnalisés
     */
    private function generer_conseils($donnees, $consommation) {
        $conseils = array();
        
        // Conseils selon l'isolation
        switch ($donnees['isolation']) {
            case 'avant_1980':
                $conseils[] = array(
                    'type' => 'isolation',
                    'priorite' => 'haute',
                    'titre' => 'Amélioration de l\'isolation prioritaire',
                    'message' => 'Votre logement construit avant 1980 consomme beaucoup pour le chauffage. Une rénovation énergétique pourrait réduire votre facture de 60% à 80%.',
                    'economie_potentielle' => round(($consommation['chauffage'] * 0.7 * $this->config['gaz_prix_kwh']), 2)
                );
                break;
                
            case '1980_2000':
                $conseils[] = array(
                    'type' => 'isolation',
                    'priorite' => 'moyenne',
                    'titre' => 'Isolation à améliorer',
                    'message' => 'Des travaux d\'isolation (combles, murs) pourraient réduire significativement votre consommation de chauffage.',
                    'economie_potentielle' => round(($consommation['chauffage'] * 0.4 * $this->config['gaz_prix_kwh']), 2)
                );
                break;
        }
        
        // Conseils température
        if ($donnees['temperature_souhaitee'] > 20) {
            $economie = $consommation['chauffage'] * (($donnees['temperature_souhaitee'] - 19) * $this->config['majoration_par_degre'] / 100);
            $conseils[] = array(
                'type' => 'temperature',
                'priorite' => 'faible',
                'titre' => 'Optimisation de la température',
                'message' => 'Baisser la température de 1°C permet d\'économiser environ 7% sur le chauffage.',
                'economie_potentielle' => round($economie * $this->config['gaz_prix_kwh'], 2)
            );
        }
        
        // Conseils sur les usages
        if ($consommation['eau_chaude'] > 3000) {
            $conseils[] = array(
                'type' => 'eau_chaude',
                'priorite' => 'moyenne',
                'titre' => 'Consommation d\'eau chaude élevée',
                'message' => 'Installer un programmateur ou réduire la température du ballon peut diminuer la consommation.',
                'economie_potentielle' => round($consommation['eau_chaude'] * 0.15 * $this->config['gaz_prix_kwh'], 2)
            );
        }
        
        return $conseils;
    }
    
    /**
     * Méthode publique pour traiter les requêtes AJAX
     */
    public static function traiter_requete_ajax() {
        // Vérification de sécurité
        check_ajax_referer('htic_simulateur_nonce', 'nonce');
        
        if (!isset($_POST['donnees_formulaire'])) {
            wp_send_json_error('Données manquantes');
        }
        
        $calculateur = new self();
        $resultats = $calculateur->calculer($_POST['donnees_formulaire']);
        
        if (isset($resultats['error'])) {
            wp_send_json_error($resultats['message']);
        } else {
            wp_send_json_success($resultats);
        }
    }
}

// Hook AJAX pour les calculs
add_action('wp_ajax_calculer_gaz_residentiel', array('HticCalculateurGazResidentiel', 'traiter_requete_ajax'));
add_action('wp_ajax_nopriv_calculer_gaz_residentiel', array('HticCalculateurGazResidentiel', 'traiter_requete_ajax'));