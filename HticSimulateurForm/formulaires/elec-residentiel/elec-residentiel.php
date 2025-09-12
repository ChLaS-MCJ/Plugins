<?php
/**
 * Template du formulaire Électricité Résidentiel
 * Fichier: formulaires/elec-residentiel/elec-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données de configuration depuis l'admin
$config_data = get_option('htic_simulateur_elec_residentiel_data', array());
?>

<div class="htic-simulateur-wrapper" data-type="elec-residentiel">
    
    <!-- En-tête du simulateur -->
    <div class="simulateur-header">
        <div class="header-icon">⚡</div>
        <h1>Simulateur Électricité Résidentiel</h1>
        <p>Estimez votre consommation et trouvez le meilleur tarif pour votre logement</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="14"></div>
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
                <span class="step-label">Équipements</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Eau chaude</span>
            </div>
            <div class="step" data-step="5">
                <span class="step-number">5</span>
                <span class="step-label">Éclairage</span>
            </div>
            <div class="step" data-step="6">
                <span class="step-number">6</span>
                <span class="step-label">Options</span>
            </div>
            <div class="step" data-step="7">
                <span class="step-number">7</span>
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-elec-residentiel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Informations du logement ET Isolation -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🏠 Informations sur votre logement</h2>
                <p>Quelques questions sur votre habitat pour personnaliser l'estimation</p>
            </div>
            
            <div class="form-grid">
                <!-- Type de logement -->
                <div class="form-group full-width">
                    <label class="form-label">Type de logement</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_logement" value="maison" required>
                            <div class="radio-content">
                                <div class="radio-icon">🏠</div>
                                <div class="radio-text">
                                    <strong>Maison</strong>
                                    <span>Individuelle ou mitoyenne</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_logement" value="appartement" required>
                            <div class="radio-content">
                                <div class="radio-icon">🏢</div>
                                <div class="radio-text">
                                    <strong>Appartement</strong>
                                    <span>En résidence ou immeuble</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Surface -->
                <div class="form-group">
                    <label for="surface" class="form-label">Surface habitable</label>
                    <div class="input-group">
                        <input type="number" 
                               id="surface" 
                               name="surface" 
                               min="20" 
                               max="500" 
                               value="100" 
                               required 
                               class="form-input">
                        <span class="input-suffix">m²</span>
                    </div>
                    <small class="form-help">Entre 20 et 500 m²</small>
                </div>
                
                <!-- Nombre de personnes -->
                <div class="form-group">
                    <label for="nb_personnes" class="form-label">Nombre de personnes</label>
                    <select id="nb_personnes" name="nb_personnes" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="1">1 personne</option>
                        <option value="2">2 personnes</option>
                        <option value="3">3 personnes</option>
                        <option value="4">4 personnes</option>
                        <option value="5">5 personnes</option>
                        <option value="6">6 personnes ou plus</option>
                    </select>
                </div>

                <!-- ISOLATION -->
                <div class="form-group full-width">
                    <label class="form-label">Période de construction / Niveau d'isolation</label>
                    <div class="radio-group horizontal">
                        <label class="radio-card">
                            <input type="radio" name="isolation" value="avant_1980" required>
                            <div class="radio-content">
                                <div class="radio-badge red">Faible</div>
                                <div class="radio-text">
                                    <strong>Avant 1980</strong>
                                    <span>Isolation faible</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="isolation" value="1980_2000" required>
                            <div class="radio-content">
                                <div class="radio-badge orange">Moyenne</div>
                                <div class="radio-text">
                                    <strong>1980 - 2000</strong>
                                    <span>Isolation moyenne</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="isolation" value="apres_2000" required>
                            <div class="radio-content">
                                <div class="radio-badge green">Bonne</div>
                                <div class="radio-text">
                                    <strong>Après 2000</strong>
                                    <span>Bonne isolation</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="isolation" value="renovation" required>
                            <div class="radio-content">
                                <div class="radio-badge blue">Excellente</div>
                                <div class="radio-text">
                                    <strong>Rénovation récente</strong>
                                    <span>Très bonne isolation</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 2: Mode de chauffage principal -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>🔥 Mode de chauffage principal</h2>
                <p>Sélectionnez votre système de chauffage principal</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Type de chauffage principal</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="convecteurs" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Convecteurs électriques</strong>
                                    <span>Radiateurs électriques classiques</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="inertie" required>
                            <div class="radio-content">
                                <div class="radio-icon">🌡️</div>
                                <div class="radio-text">
                                    <strong>Radiateurs à inertie</strong>
                                    <span>Chaleur douce et diffuse</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="clim_reversible" required>
                            <div class="radio-content">
                                <div class="radio-icon">❄️</div>
                                <div class="radio-text">
                                    <strong>Climatisation réversible</strong>
                                    <span>Pompe à chaleur air/air</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="pac" required>
                            <div class="radio-content">
                                <div class="radio-icon">💨</div>
                                <div class="radio-text">
                                    <strong>Pompe à chaleur (PAC)</strong>
                                    <span>Système de chauffage performant</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="autre" required>
                            <div class="radio-content">
                                <div class="radio-icon">🚫</div>
                                <div class="radio-text">
                                    <strong>Autre mode de chauffage</strong>
                                    <span>Gaz, fioul, bois...</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 3: Électroménagers -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>🍳 Électroménagers</h2>
                <p>Sélectionnez vos équipements électroménagers</p>
            </div>
            
            <div class="form-grid">
                <!-- Électroménagers de base -->
                <div class="form-group full-width">
                    <label class="form-label">Électroménagers de base</label>
                    <div class="checkbox-group horizontal">
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="lave_linge" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">👕</div>
                                <div class="checkbox-text">
                                    <strong>Lave-linge</strong>
                                    <span>Machine à laver</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="seche_linge" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🌪️</div>
                                <div class="checkbox-text">
                                    <strong>Sèche-linge</strong>
                                    <span>Séchoir électrique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="refrigerateur" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🧊</div>
                                <div class="checkbox-text">
                                    <strong>Réfrigérateur</strong>
                                    <span>Réfrigérateur / congélateur</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="lave_vaisselle" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🍽️</div>
                                <div class="checkbox-text">
                                    <strong>Lave-vaisselle</strong>
                                    <span>Machine à laver la vaisselle</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="four" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🔥</div>
                                <div class="checkbox-text">
                                    <strong>Four</strong>
                                    <span>Four électrique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="congelateur" checked>
                            <div class="checkbox-content">
                                <div class="checkbox-icon">❄️</div>
                                <div class="checkbox-text">
                                    <strong>Congélateur</strong>
                                    <span>Congélateur séparé</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="electromenagers[]" value="cave_a_vin">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🍷</div>
                                <div class="checkbox-text">
                                    <strong>Cave à vin</strong>
                                    <span>Réfrigération spécialisée</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Cuisson / Plaques -->
                <div class="form-group full-width">
                    <label class="form-label">Cuisson / Plaques</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_cuisson" value="plaque_induction" required>
                            <div class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <strong>Plaque cuisson induction</strong>
                                    <span>Cuisson par induction électromagnétique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_cuisson" value="plaque_vitroceramique" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Plaque cuisson vitrocéramique</strong>
                                    <span>Plaques électriques en vitrocéramique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_cuisson" value="autre" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Autre</strong>
                                    <span>Gaz, mixte...</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 4: Eau chaude -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>💧 Eau chaude sanitaire</h2>
                <p>Comment est produite votre eau chaude ?</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Production d'eau chaude</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="eau_chaude" value="oui" required>
                            <div class="radio-content">
                                <div class="radio-icon">💧</div>
                                <div class="radio-text">
                                    <strong>Eau chaude électrique</strong>
                                    <span>Ballon électrique, chauffe-eau instantané</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="eau_chaude" value="non" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Autre énergie</strong>
                                    <span>Gaz, solaire, thermodynamique</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 5: Éclairage -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>💡 Éclairage</h2>
                <p>Quel type d'éclairage utilisez-vous principalement ?</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Type d'éclairage principal</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_eclairage" value="led" required>
                            <div class="radio-content">
                                <div class="radio-icon">💡</div>
                                <div class="radio-text">
                                    <strong>LED</strong>
                                    <span>Éclairage LED basse consommation</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_eclairage" value="incandescence_halogene" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔆</div>
                                <div class="radio-text">
                                    <strong>Incandescence ou halogène</strong>
                                    <span>Ampoules traditionnelles</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 6: Équipements spéciaux -->
        <div class="form-step" data-step="6">
            <div class="step-header">
                <h2>⚡ Équipements spéciaux</h2>
                <p>Avez-vous des équipements spéciaux consommateurs d'électricité ?</p>
            </div>
            
            <div class="form-grid">
                <!-- Piscine -->
                <div class="form-group full-width">
                    <label class="form-label">Piscine</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="piscine" value="simple" required>
                            <div class="radio-content">
                                <div class="radio-icon">🏊</div>
                                <div class="radio-text">
                                    <strong>Piscine simple</strong>
                                    <span>Filtration uniquement</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="piscine" value="chauffee" required>
                            <div class="radio-content">
                                <div class="radio-icon">🌊</div>
                                <div class="radio-text">
                                    <strong>Piscine chauffée</strong>
                                    <span>Avec chauffage électrique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="piscine" value="non" required>
                            <div class="radio-content">
                                <div class="radio-icon">🚫</div>
                                <div class="radio-text">
                                    <strong>Pas de piscine</strong>
                                    <span>Aucune piscine</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Autres équipements -->
                <div class="form-group full-width">
                    <label class="form-label">Autres équipements spéciaux</label>
                    <div class="checkbox-group horizontal">
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux[]" value="spa_jacuzzi">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🛁</div>
                                <div class="checkbox-text">
                                    <strong>Spa / Jacuzzi</strong>
                                    <span>Chauffage et pompes</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux[]" value="voiture_electrique">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🚗</div>
                                <div class="checkbox-text">
                                    <strong>Voiture électrique</strong>
                                    <span>Recharge à domicile</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux[]" value="aquarium">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🐠</div>
                                <div class="checkbox-text">
                                    <strong>Aquarium</strong>
                                    <span>Éclairage et filtration</span>
                                </div>
                            </div>
                        </label>
                        
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux[]" value="climatiseur_mobile">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🌬️</div>
                                <div class="checkbox-text">
                                    <strong>Climatiseur mobile</strong>
                                    <span>Climatisation d'appoint</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Préférence tarifaire -->
                <div class="form-group full-width">
                    <label class="form-label">Préférence tarifaire (optionnel)</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="preference_tarif" value="base">
                            <div class="radio-content">
                                <div class="radio-icon">📊</div>
                                <div class="radio-text">
                                    <strong>Tarif BASE</strong>
                                    <span>Tarif unique toute la journée</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="preference_tarif" value="hc">
                            <div class="radio-content">
                                <div class="radio-icon">🌙</div>
                                <div class="radio-text">
                                    <strong>Heures Creuses</strong>
                                    <span>Tarif réduit la nuit</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="preference_tarif" value="indifferent">
                            <div class="radio-content">
                                <div class="radio-icon">🔍</div>
                                <div class="radio-text">
                                    <strong>Indifférent</strong>
                                    <span>Comparaison automatique</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Info automatique -->
                    <div class="info-box">
                        <div class="info-icon">ℹ️</div>
                        <div class="info-content">
                            <h4>Inclus automatiquement dans le calcul</h4>
                            <p><strong>Multimédia :</strong> Télévision, ordinateur, box internet </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 7: Résultats -->
        <div class="form-step" data-step="7">
            <div class="step-header">
                <h2>Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos informations</p>
            </div>
            
            <div id="results-container">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul en cours...</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="form-navigation">
            <button type="button" id="btn-previous" class="btn btn-secondary" style="display: none;">
                ← Précédent
            </button>
            
            <div class="nav-spacer"></div>
            
            <button type="button" id="btn-next" class="btn btn-primary">
                Suivant →
            </button>
            
            <button type="button" id="btn-calculate" class="btn btn-success" style="display: none;">
                🔍 Calculer
            </button>
            
            <button type="button" id="btn-restart" class="btn btn-outline" style="display: none;">
                🔄 Nouvelle simulation
            </button>
        </div>
    </form>
    
    <script type="application/json" id="simulateur-config">
        <?php echo json_encode($config_data, JSON_PRETTY_PRINT); ?>
    </script>
</div>