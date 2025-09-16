<?php
/**
 * Template du formulaire Gaz Résidentiel
 * Fichier: formulaires/gaz-residentiel/gaz-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données de configuration depuis l'admin
$config_data = get_option('htic_simulateur_gaz_residentiel_data', array());
?>

<div class="htic-simulateur-wrapper" data-type="gaz-residentiel">
    
    <!-- En-tête du simulateur -->
    <div class="simulateur-header">
        <div class="header-icon">🔥</div>
        <h1>Simulateur Gaz Résidentiel</h1>
        <p>Estimez votre consommation de gaz naturel et trouvez le meilleur tarif</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="20"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Logement</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Chauffage</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Eau chaude</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Cuisson</span>
            </div>
            <div class="step" data-step="5">
                <span class="step-number">5</span>
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-gaz-residentiel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Informations du logement -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🏠 Informations sur votre logement</h2>
                <p>Quelques questions sur votre habitat pour personnaliser l'estimation</p>
            </div>
            
            <div class="form-grid">
                
                <!-- Superficie -->
                <div class="form-group">
                    <label for="superficie">Superficie de votre logement</label>
                    <div class="input-group">
                        <input type="number" id="superficie" name="superficie" min="20" max="500" value="150" required>
                        <span class="input-suffix">m²</span>
                    </div>
                    <div class="field-help">Surface habitable de votre logement</div>
                </div>
                
                <!-- Nombre de personnes -->
                <div class="form-group">
                    <label for="nb_personnes">Nombre de personnes dans le logement</label>
                    <div class="input-group">
                        <input type="number" id="nb_personnes" name="nb_personnes" min="1" max="10" value="5" required>
                        <span class="input-suffix">personnes</span>
                    </div>
                    <div class="field-help">Nombre d'occupants permanents</div>
                </div>
                
                <!-- Commune -->
                <div class="form-group full-width">
                    <label for="commune">Commune d'habitation</label>
                    <input type="text" id="commune" name="commune" placeholder="Saisissez votre commune" value="BASCONS">
                    <div class="field-help">Votre commune pour déterminer la zone tarifaire</div>
                </div>
                
                <!-- Type de logement -->
                <div class="form-group full-width">
                    <label class="form-label">Type de logement</label>
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="maison" name="type_logement" value="maison" checked>
                            <label for="maison" class="radio-content">
                                <div class="radio-icon">🏠</div>
                                <div class="radio-text">
                                    <div class="radio-title">Maison</div>
                                    <div class="radio-subtitle">Habitation individuelle</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="appartement" name="type_logement" value="appartement">
                            <label for="appartement" class="radio-content">
                                <div class="radio-icon">🏢</div>
                                <div class="radio-text">
                                    <div class="radio-title">Appartement</div>
                                    <div class="radio-subtitle">Logement collectif</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-primary btn-next">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 2: Chauffage -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>🔥 Chauffage au gaz</h2>
                <p>Informations sur le chauffage de votre logement</p>
            </div>
            
            <div class="form-grid">
                
                <!-- Utilisation du gaz pour le chauffage -->
                <div class="form-group full-width">
                    <label class="form-label">Votre logement est-il chauffé au gaz ?</label>
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="chauffage_oui" name="chauffage_gaz" value="oui" checked>
                            <label for="chauffage_oui" class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <div class="radio-title">Oui</div>
                                    <div class="radio-subtitle">Chauffage au gaz naturel</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="chauffage_non" name="chauffage_gaz" value="non">
                            <label for="chauffage_non" class="radio-content">
                                <div class="radio-icon">❄️</div>
                                <div class="radio-text">
                                    <div class="radio-title">Non</div>
                                    <div class="radio-subtitle">Autre mode de chauffage</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Isolation du logement (affiché si chauffage au gaz) -->
                <div class="form-group full-width chauffage-details">
                    <label class="form-label">Isolation de votre logement</label>
                    <div class="radio-group radio-column">
                        <div class="radio-card">
                            <input type="radio" id="iso_avant_1980" name="isolation" value="avant_1980" checked>
                            <label for="iso_avant_1980" class="radio-content">
                                <div class="radio-icon">🏘️</div>
                                <div class="radio-text">
                                    <div class="radio-title">Avant 1980</div>
                                    <div class="radio-subtitle">Logement ancien, isolation faible</div>
                                    <div class="radio-details">Consommation élevée : 180 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_1980_2000" name="isolation" value="1980_2000">
                            <label for="iso_1980_2000" class="radio-content">
                                <div class="radio-icon">🏠</div>
                                <div class="radio-text">
                                    <div class="radio-title">1980 à 2000</div>
                                    <div class="radio-subtitle">Première réglementation thermique</div>
                                    <div class="radio-details">Consommation modérée : 120 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_apres_2000" name="isolation" value="apres_2000">
                            <label for="iso_apres_2000" class="radio-content">
                                <div class="radio-icon">🏡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Après 2000</div>
                                    <div class="radio-subtitle">RT 2000, RT 2005, RT 2012</div>
                                    <div class="radio-details">Bonne isolation : 80 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_renovation" name="isolation" value="renovation">
                            <label for="iso_renovation" class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Rénovation énergétique</div>
                                    <div class="radio-subtitle">Travaux d'isolation récents</div>
                                    <div class="radio-details">Très bonne isolation : 60 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-next">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 3: Eau chaude -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>🚿 Production d'eau chaude</h2>
                <p>Comment est produite l'eau chaude dans votre logement ?</p>
            </div>
            
            <div class="form-grid">
                
                <div class="form-group full-width">
                    <label class="form-label">Eau chaude sanitaire</label>
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="ecs_gaz" name="eau_chaude" value="gaz" checked>
                            <label for="ecs_gaz" class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <div class="radio-title">Gaz naturel</div>
                                    <div class="radio-subtitle">Chauffe-eau ou chaudière gaz</div>
                                    <div class="radio-details">Consommation : 1200 kWh/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="ecs_autre" name="eau_chaude" value="autre">
                            <label for="ecs_autre" class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Autre énergie</div>
                                    <div class="radio-subtitle">Électricité, solaire...</div>
                                    <div class="radio-details">Pas de consommation gaz</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-next">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 4: Cuisson -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>🍳 Cuisson au gaz</h2>
                <p>Utilisez-vous le gaz pour la cuisson ?</p>
            </div>
            
            <div class="form-grid">
                
                <div class="form-group full-width">
                    <label class="form-label">Cuisson</label>
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="cuisson_gaz" name="cuisson" value="gaz" checked>
                            <label for="cuisson_gaz" class="radio-content">
                                <div class="radio-icon">🍳</div>
                                <div class="radio-text">
                                    <div class="radio-title">Gazinière</div>
                                    <div class="radio-subtitle">Cuisson au gaz naturel</div>
                                    <div class="radio-details">Consommation : 365 kWh/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="cuisson_autre" name="cuisson" value="autre">
                            <label for="cuisson_autre" class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Autre</div>
                                    <div class="radio-subtitle">Électrique, induction...</div>
                                    <div class="radio-details">Pas de consommation gaz</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Type d'offre -->
                <div class="form-group full-width">
                    <label class="form-label">Type d'offre souhaitée</label>
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="offre_base" name="offre" value="base" checked>
                            <label for="offre_base" class="radio-content">
                                <div class="radio-icon">📋</div>
                                <div class="radio-text">
                                    <div class="radio-title">Offre de base</div>
                                    <div class="radio-subtitle">Tarif réglementé standard</div>
                                    <div class="radio-details">Prix fixe toute l'année</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="offre_propane" name="offre" value="propane">
                            <label for="offre_propane" class="radio-content">
                                <div class="radio-icon">⛽</div>
                                <div class="radio-text">
                                    <div class="radio-title">Gaz Propane</div>
                                    <div class="radio-subtitle">Pour logements non raccordés</div>
                                    <div class="radio-details">Citerne ou bouteilles</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-calculate">
                    <span class="btn-icon">🔍</span>
                    Calculer mon estimation
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 5: Résultats -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>📊 Votre estimation personnalisée</h2>
                <p>Résultats basés sur vos réponses et les tarifs actuels</p>
            </div>
            
            <!-- Zone des résultats (sera remplie via JavaScript) -->
            <div id="resultats-container"></div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Modifier mes réponses
                </button>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <span class="btn-icon">🖨️</span>
                    Imprimer les résultats
                </button>
            </div>
        </div>
        
    </form>
    
    <!-- Messages d'erreur -->
    <div id="error-container" class="error-container" style="display: none;">
        <div class="error-message">
            <span class="error-icon">⚠️</span>
            <span class="error-text"></span>
        </div>
    </div>
    
    <!-- Indicateur de chargement -->
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Calcul en cours...</div>
    </div>
    
</div>

<!-- Script pour la logique du formulaire -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage conditionnel des détails de chauffage
    const chauffageRadios = document.querySelectorAll('input[name="chauffage_gaz"]');
    const chauffageDetails = document.querySelector('.chauffage-details');
    
    function toggleChauffageDetails() {
        const chauffageOui = document.getElementById('chauffage_oui').checked;
        chauffageDetails.style.display = chauffageOui ? 'block' : 'none';
    }
    
    chauffageRadios.forEach(radio => {
        radio.addEventListener('change', toggleChauffageDetails);
    });
    
    // Initialiser l'affichage
    toggleChauffageDetails();
});
</script>