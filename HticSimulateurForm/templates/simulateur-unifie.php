<?php
/**
 * Template du simulateur unifié - Design moderne et professionnel
 * Fichier: templates/simulateur-unifie.php - Version 3.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres du shortcode
$type_defaut = isset($atts['type']) ? $atts['type'] : '';
$theme = isset($atts['theme']) ? $atts['theme'] : 'moderne';
?>

<div class="htic-simulateur-unifie htic-simulateur-moderne" data-theme="<?php echo esc_attr($theme); ?>">
    
    <!-- Hero Section -->
    <div class="simulateur-hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-icon">⚡</span>
                <span class="badge-text">Simulation énergétique</span>
            </div>
            <h1 class="hero-title">
                Estimez votre consommation 
                <span class="gradient-text">en 3 minutes</span>
            </h1>
            <p class="hero-subtitle">
                Obtenez une estimation précise de votre consommation énergétique et découvrez les meilleures offres adaptées à vos besoins.
            </p>
        </div>
    </div>
    
    <!-- Sélecteur principal -->
    <div class="simulateur-selector-moderne" id="simulateur-selector">
        
        <!-- Étapes de progression -->
        <div class="progress-indicator">
            <div class="step-indicator active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Profil</div>
            </div>
            <div class="progress-line"></div>
            <div class="step-indicator" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Énergie</div>
            </div>
            <div class="progress-line"></div>
            <div class="step-indicator" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Simulation</div>
            </div>
        </div>
        
        <!-- Container de sélection -->
        <div class="selection-container">
            
            <!-- Étape 1: Sélection du profil -->
            <div class="selection-step active" data-step="1">
                <div class="step-header">
                    <h2>Quel est votre profil ?</h2>
                    <p>Sélectionnez le type qui correspond à votre situation</p>
                </div>
                
                <div class="profile-cards">
                    <div class="profile-card active" data-profile="particulier">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9,22 9,12 15,12 15,22"/>
                                </svg>
                            </div>
                            <div class="card-check">✓</div>
                        </div>
                        <div class="card-content">
                            <h3>Particulier</h3>
                            <p>Maison, appartement, logement résidentiel</p>
                            <ul class="card-features">
                                <li>Consommation domestique</li>
                                <li>Tarifs résidentiels</li>
                                <li>Options heures creuses</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="profile-card" data-profile="professionnel">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18"/>
                                    <path d="M5 21V7l8-4v18"/>
                                    <path d="M19 21V11l-6-4"/>
                                </svg>
                            </div>
                            <div class="card-check">✓</div>
                        </div>
                        <div class="card-content">
                            <h3>Professionnel</h3>
                            <p>Entreprise, commerce, bureau, industrie</p>
                            <ul class="card-features">
                                <li>Consommation professionnelle</li>
                                <li>Tarifs entreprise</li>
                                <li>Solutions sur mesure</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button class="btn-next" disabled>
                        <span>Continuer</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Étape 2: Sélection de l'énergie -->
            <div class="selection-step" data-step="2">
                <div class="step-header">
                    <h2>Quel type d'énergie ?</h2>
                    <p>Choisissez l'énergie pour laquelle vous souhaitez une estimation</p>
                </div>
                
                <div class="energy-cards">
                    <div class="energy-card active" data-energy="elec">
                        <div class="card-visual">
                            <div class="visual-icon">⚡</div>
                            <div class="visual-bg"></div>
                        </div>
                        <div class="card-content">
                            <h3>Électricité</h3>
                            <p>Simulation complète de votre consommation électrique</p>
                            <div class="card-tags">
                                <span class="tag">Chauffage</span>
                                <span class="tag">Eau chaude</span>
                                <span class="tag">Équipements</span>
                            </div>
                        </div>
                        <div class="card-check">✓</div>
                    </div>
                    
                    <div class="energy-card" data-energy="gaz">
                        <div class="card-visual">
                            <div class="visual-icon">🔥</div>
                            <div class="visual-bg"></div>
                        </div>
                        <div class="card-content">
                            <h3>Gaz</h3>
                            <p>Estimation de votre consommation de gaz</p>
                            <div class="card-tags">
                                <span class="tag">Chauffage</span>
                                <span class="tag">Eau chaude</span>
                                <span class="tag">Cuisson</span>
                            </div>
                        </div>
                        <div class="card-check">✓</div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button class="btn-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        <span>Retour</span>
                    </button>
                    <button class="btn-next" disabled>
                        <span>Continuer</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Étape 3: Récapitulatif et lancement -->
            <div class="selection-step" data-step="3">
                <div class="step-header">
                    <h2>Récapitulatif de votre sélection</h2>
                    <p>Vérifiez vos choix avant de commencer la simulation</p>
                </div>
                
                <div class="summary-card">
                    <div class="summary-visual">
                        <div class="summary-icon" id="summary-icon">⚡</div>
                        <div class="summary-badge" id="summary-badge">Particulier</div>
                    </div>
                    <div class="summary-content">
                        <h3 id="summary-title">Simulateur Électricité Résidentiel</h3>
                        <p id="summary-description">Estimation personnalisée pour votre logement</p>
                        
                        <div class="summary-features">
                            <div class="feature-row">
                                <span class="feature-icon">🎯</span>
                                <span>Estimation précise basée sur vos données</span>
                            </div>
                            <div class="feature-row">
                                <span class="feature-icon">💰</span>
                                <span>Comparaison des différents tarifs</span>
                            </div>
                            <div class="feature-row">
                                <span class="feature-icon">📊</span>
                                <span>Répartition détaillée par usage</span>
                            </div>
                            <div class="feature-row">
                                <span class="feature-icon">💡</span>
                                <span>Conseils pour optimiser</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button class="btn-back">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        <span>Retour</span>
                    </button>
                    <button class="btn-start-simulation">
                        <span class="btn-icon">🚀</span>
                        <span>Commencer la simulation</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Aide et informations -->
        <div class="help-section">
            <div class="help-card">
                <div class="help-icon">💡</div>
                <div class="help-content">
                    <h4>Besoin d'aide ?</h4>
                    <p>Notre simulateur vous guide étape par étape pour obtenir une estimation précise et personnalisée.</p>
                </div>
            </div>
            
            <div class="trust-indicators">
                <div class="trust-item">
                    <span class="trust-icon">🛡️</span>
                    <span>Données sécurisées</span>
                </div>
                <div class="trust-item">
                    <span class="trust-icon">⚡</span>
                    <span>Calcul instantané</span>
                </div>
                <div class="trust-item">
                    <span class="trust-icon">📱</span>
                    <span>100% gratuit</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Container pour les formulaires -->
    <div class="formulaire-container" style="display: none;">
        <div class="formulaire-header-moderne">
            <button class="btn-back-to-selector">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Changer de simulateur</span>
            </button>
            
            <div class="formulaire-info">
                <div class="formulaire-icon-moderne">⚡</div>
                <div class="formulaire-text-moderne">
                    <h2 class="formulaire-title-moderne">Simulateur Électricité Résidentiel</h2>
                    <p class="formulaire-subtitle-moderne">Estimation personnalisée pour votre logement</p>
                </div>
            </div>
        </div>
        
        <div class="formulaire-content">
            <div class="loading-moderne">
                <div class="loading-spinner-moderne"></div>
                <p>Chargement de votre simulateur personnalisé...</p>
            </div>
        </div>
    </div>
    
    <!-- Configuration JSON -->
    <script type="application/json" id="simulateur-config-global">
        {
            "types": {
                "elec-residentiel": {
                    "title": "Simulateur Électricité Résidentiel",
                    "subtitle": "Estimation personnalisée pour votre logement",
                    "icon": "⚡",
                    "data": <?php echo json_encode(get_option('htic_simulateur_elec_residentiel_data', array())); ?>
                },
                "gaz-residentiel": {
                    "title": "Simulateur Gaz Résidentiel", 
                    "subtitle": "Estimation de votre consommation de gaz naturel",
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