<?php
/**
 * Template du formulaire Électricité Résidentiel - Version mise à jour
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
            <div class="progress-fill" data-progress="25"></div>
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
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-elec-residentiel" class="simulateur-form">
        
        <!-- Étape 1: Informations du logement -->
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
                        <option value="3" selected>3 personnes</option>
                        <option value="4">4 personnes</option>
                        <option value="5">5 personnes</option>
                        <option value="6">6 personnes ou plus</option>
                    </select>
                </div>
                
                <!-- Période de construction -->
                <div class="form-group full-width">
                    <label class="form-label">Période de construction / Isolation</label>
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
                                    <strong>Rénovée récemment</strong>
                                    <span>Très bonne isolation</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Étape 2: Chauffage et eau chaude (MISE À JOUR) -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>🔥 Chauffage et eau chaude</h2>
                <p>Sélectionnez vos équipements électriques - Nouveaux types de chauffage disponibles</p>
            </div>
            
            <div class="form-grid">
                <!-- Chauffage électrique (ÉTENDU) -->
                <div class="form-group full-width">
                    <label class="form-label">Type de chauffage électrique</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage_electrique" value="convecteurs">
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Convecteurs électriques</strong>
                                    <span>Radiateurs électriques classiques (consommation élevée)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage_electrique" value="inertie">
                            <div class="radio-content">
                                <div class="radio-icon">🌡️</div>
                                <div class="radio-text">
                                    <strong>Radiateurs à inertie</strong>
                                    <span>Chaleur douce et diffuse (consommation modérée)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage_electrique" value="clim_reversible">
                            <div class="radio-content">
                                <div class="radio-icon">❄️</div>
                                <div class="radio-text">
                                    <strong>Climatisation réversible</strong>
                                    <span>Pompe à chaleur air/air (économique)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage_electrique" value="pac_air_eau">
                            <div class="radio-content">
                                <div class="radio-icon">💨</div>
                                <div class="radio-text">
                                    <strong>PAC Air/Eau</strong>
                                    <span>Pompe à chaleur air/eau (très économique)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage_electrique" value="aucun">
                            <div class="radio-content">
                                <div class="radio-icon">🚫</div>
                                <div class="radio-text">
                                    <strong>Pas de chauffage électrique</strong>
                                    <span>Autre énergie (gaz, bois, etc.)</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Eau chaude électrique -->
                <div class="form-group full-width">
                    <label class="form-label">Eau chaude sanitaire</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="eau_chaude_electrique" value="oui">
                            <div class="radio-content">
                                <div class="radio-icon">💧</div>
                                <div class="radio-text">
                                    <strong>Eau chaude électrique</strong>
                                    <span>Ballon électrique, chauffe-eau instantané</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="eau_chaude_electrique" value="non">
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
                
                <!-- Info sur les consommations -->
                <div class="info-box">
                    <div class="info-icon">💡</div>
                    <div class="info-content">
                        <h4>Consommations estimées par type</h4>
                        <p><strong>PAC Air/Eau :</strong> Solution la plus économique (jusqu'à 75% d'économie)</p>
                        <p><strong>Climatisation réversible :</strong> Économique et polyvalente (50% d'économie)</p>
                        <p><strong>Radiateurs à inertie :</strong> Plus efficaces que les convecteurs (20% d'économie)</p>
                        <p><strong>Convecteurs :</strong> Solution basique, consommation la plus élevée</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Étape 3: Équipements électriques -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>⚡ Équipements électriques</h2>
                <p>Sélectionnez vos équipements spécifiques</p>
            </div>
            
            <div class="form-grid">
                <!-- Cuisson électrique -->
                <div class="form-group">
                    <label class="checkbox-card">
                        <input type="checkbox" name="cuisson_electrique" value="oui">
                        <div class="checkbox-content">
                            <div class="checkbox-icon">🍳</div>
                            <div class="checkbox-text">
                                <strong>Cuisson électrique</strong>
                                <span>Plaques électriques, induction, four électrique</span>
                            </div>
                        </div>
                    </label>
                </div>
                
                <!-- Équipements spéciaux -->
                <div class="form-group full-width">
                    <label class="form-label">Équipements spéciaux (optionnel)</label>
                    <div class="checkbox-group horizontal">
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="piscine_simple">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🏊</div>
                                <div class="checkbox-text">
                                    <strong>Piscine</strong>
                                    <span>Filtration uniquement (+1400 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="piscine_chauffee">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🌊</div>
                                <div class="checkbox-text">
                                    <strong>Piscine chauffée</strong>
                                    <span>Avec chauffage électrique (+4000 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="spa">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🛁</div>
                                <div class="checkbox-text">
                                    <strong>Spa / Jacuzzi</strong>
                                    <span>Chauffage et pompes (+2000 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="voiture_electrique">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🚗</div>
                                <div class="checkbox-text">
                                    <strong>Voiture électrique</strong>
                                    <span>Recharge à domicile (+1500 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="climatiseur_mobile">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🌬️</div>
                                <div class="checkbox-text">
                                    <strong>Climatiseur mobile</strong>
                                    <span>Climatisation d'appoint (+800 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="equipements_speciaux" value="cave_a_vin">
                            <div class="checkbox-content">
                                <div class="checkbox-icon">🍷</div>
                                <div class="checkbox-text">
                                    <strong>Cave à vin</strong>
                                    <span>Réfrigération spécialisée (+400 kWh/an)</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Info automatique MISE À JOUR -->
                <div class="info-box">
                    <div class="info-icon">ℹ️</div>
                    <div class="info-content">
                        <h4>Inclus automatiquement dans le calcul</h4>
                        <p><strong>Électroménager (1497 kWh/an) :</strong> Réfrigérateur, lave-linge, lave-vaisselle, four micro-ondes, aspirateur</p>
                        <p><strong>Éclairage (750 kWh/an) :</strong> Éclairage LED et traditionnel selon la surface</p>
                        <p><strong>Multimédia (300 kWh/an) :</strong> TV, ordinateurs, box internet, décodeurs</p>
                        <p><strong>Petits équipements (1500 kWh/an) :</strong> Autres appareils du quotidien</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Étape 4: Résultats -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos informations - Maintenant avec tarif TEMPO</p>
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
    
    <!-- Données de configuration pour JavaScript -->
    <script type="application/json" id="simulateur-config">
        <?php echo json_encode($config_data, JSON_PRETTY_PRINT); ?>
    </script>
</div>