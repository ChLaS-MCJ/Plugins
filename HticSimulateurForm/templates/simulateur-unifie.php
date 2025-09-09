<?php
/**
 * Template du simulateur unifié avec sélection par onglets
 * Fichier: templates/simulateur-unifie.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres du shortcode
$type_defaut = isset($atts['type']) ? $atts['type'] : '';
$theme = isset($atts['theme']) ? $atts['theme'] : 'default';
?>

<div class="htic-simulateur-unifie" data-theme="<?php echo esc_attr($theme); ?>">
    
    <!-- En-tête principal -->
    <div class="simulateur-main-header">
        <h1>🏡 Simulateur de Consommation Énergétique</h1>
        <p>Estimez votre consommation électrique ou gaz en quelques clics</p>
    </div>
    
    <!-- Sélecteur de type -->
    <div class="type-selector-container" id="type-selector">
        
        <!-- Navigation principale -->
        <div class="main-tabs">
            <button class="main-tab active" data-category="particulier">
                <div class="tab-icon">🏠</div>
                <div class="tab-text">
                    <strong>Particuliers</strong>
                    <span>Logement résidentiel</span>
                </div>
            </button>
            
            <button class="main-tab" data-category="professionnel">
                <div class="tab-icon">🏢</div>
                <div class="tab-text">
                    <strong>Professionnels</strong>
                    <span>Entreprise, commerce</span>
                </div>
            </button>
        </div>
        
        <!-- Sous-navigation énergies -->
        <div class="energy-tabs">
            
            <!-- Onglets Particuliers -->
            <div class="energy-group active" data-category="particulier">
                <div class="energy-tab active" data-type="elec-residentiel">
                    <div class="energy-icon">⚡</div>
                    <div class="energy-content">
                        <h3>Électricité</h3>
                        <p>Simulation complète de votre consommation électrique</p>
                        <ul class="features-list">
                            <li>Chauffage électrique</li>
                            <li>Eau chaude</li>
                            <li>Équipements spéciaux</li>
                            <li>Comparaison des tarifs</li>
                        </ul>
                        <div class="start-button">
                            <span class="btn-text">Commencer</span>
                            <span class="btn-arrow">→</span>
                        </div>
                    </div>
                </div>
                
                <div class="energy-tab" data-type="gaz-residentiel">
                    <div class="energy-icon">🔥</div>
                    <div class="energy-content">
                        <h3>Gaz naturel</h3>
                        <p>Estimation de votre consommation de gaz</p>
                        <ul class="features-list">
                            <li>Chauffage au gaz</li>
                            <li>Eau chaude sanitaire</li>
                            <li>Cuisson</li>
                            <li>Tarifs réglementés</li>
                        </ul>
                        <div class="start-button">
                            <span class="btn-text">Commencer</span>
                            <span class="btn-arrow">→</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Onglets Professionnels -->
            <div class="energy-group" data-category="professionnel">
                <div class="energy-tab active" data-type="elec-professionnel">
                    <div class="energy-icon">⚡</div>
                    <div class="energy-content">
                        <h3>Électricité Pro</h3>
                        <p>Simulation pour votre activité professionnelle</p>
                        <ul class="features-list">
                            <li>Bureau, commerce</li>
                            <li>Restaurant, artisanat</li>
                            <li>Industrie légère</li>
                            <li>Tarifs professionnels</li>
                        </ul>
                        <div class="start-button">
                            <span class="btn-text">Commencer</span>
                            <span class="btn-arrow">→</span>
                        </div>
                    </div>
                </div>
                
                <div class="energy-tab" data-type="gaz-professionnel">
                    <div class="energy-icon">🔥</div>
                    <div class="energy-content">
                        <h3>Gaz Pro</h3>
                        <p>Estimation gaz pour votre entreprise</p>
                        <ul class="features-list">
                            <li>Chauffage locaux</li>
                            <li>Process industriels</li>
                            <li>Restauration</li>
                            <li>Tarifs négociés</li>
                        </ul>
                        <div class="start-button">
                            <span class="btn-text">Commencer</span>
                            <span class="btn-arrow">→</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Aide à la sélection -->
        <div class="selection-help">
            <div class="help-icon">💡</div>
            <div class="help-content">
                <h4>Besoin d'aide pour choisir ?</h4>
                <p><strong>Particuliers :</strong> Pour votre domicile (maison, appartement)</p>
                <p><strong>Professionnels :</strong> Pour votre entreprise, commerce, bureau</p>
            </div>
        </div>
    </div>
    
    <!-- Container pour les formulaires -->
    <div class="formulaire-container" id="formulaire-container" style="display: none;">
        
        <!-- En-tête du formulaire sélectionné -->
        <div class="formulaire-header">
            <button class="back-to-selection" id="back-to-selection">
                ← Changer de simulateur
            </button>
            <div class="formulaire-title">
                <div class="formulaire-icon"></div>
                <div class="formulaire-text">
                    <h2 id="formulaire-title-text"></h2>
                    <p id="formulaire-subtitle-text"></p>
                </div>
            </div>
        </div>
        
        <!-- Zone de chargement dynamique -->
        <div class="formulaire-content" id="formulaire-content">
            <div class="loading-formulaire">
                <div class="loading-spinner"></div>
                <p>Chargement du formulaire...</p>
            </div>
        </div>
    </div>
    
    <!-- Scripts de configuration -->
    <script type="application/json" id="simulateur-config-global">
        {
            "types": {
                "elec-residentiel": {
                    "title": "Simulateur Électricité Résidentiel",
                    "subtitle": "Estimez votre consommation électrique résidentielle",
                    "icon": "⚡",
                    "data": <?php echo json_encode(get_option('htic_simulateur_elec_residentiel_data', array())); ?>
                },
                "gaz-residentiel": {
                    "title": "Simulateur Gaz Résidentiel", 
                    "subtitle": "Estimez votre consommation de gaz naturel",
                    "icon": "🔥",
                    "data": <?php echo json_encode(get_option('htic_simulateur_gaz_residentiel_data', array())); ?>
                },
                "elec-professionnel": {
                    "title": "Simulateur Électricité Professionnel",
                    "subtitle": "Estimation pour votre activité professionnelle", 
                    "icon": "🏢",
                    "data": <?php echo json_encode(get_option('htic_simulateur_elec_professionnel_data', array())); ?>
                },
                "gaz-professionnel": {
                    "title": "Simulateur Gaz Professionnel",
                    "subtitle": "Estimation gaz pour votre entreprise",
                    "icon": "🏭", 
                    "data": <?php echo json_encode(get_option('htic_simulateur_gaz_professionnel_data', array())); ?>
                }
            },
            "defaultType": "<?php echo esc_js($type_defaut); ?>",
            "ajaxUrl": "<?php echo admin_url('admin-ajax.php'); ?>",
            "nonce": "<?php echo wp_create_nonce('htic_simulateur_nonce'); ?>",
            "pluginUrl": "<?php echo HTIC_SIMULATEUR_URL; ?>"
        }
    </script>
</div>