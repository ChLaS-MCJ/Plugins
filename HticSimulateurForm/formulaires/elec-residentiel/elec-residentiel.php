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
            <div class="progress-fill" data-progress="10"></div>
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
            <div class="step" data-step="8">
                <span class="step-number">8</span>
                <span class="step-label">Sélection</span>
            </div>
            <div class="step" data-step="9">
                <span class="step-number">9</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="10">
                <span class="step-number">10</span>
                <span class="step-label">Récapitulatif</span>
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

                <div class="form-group full-width">
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
        
        <!-- ÉTAPE 7: Informations client -->
        <div class="form-step" data-step="7">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos informations</p>
            </div>
            
            <!-- Container des résultats -->
            <div id="results-container">
                <!-- Les résultats seront injectés ici par JavaScript -->
            </div>
            
            <!-- Les actions seront gérées par JavaScript -->
        </div>

        <div class="form-step" data-step="8">
            <div class="step-header">
                <h2>🎯 Finalisons votre choix</h2>
                <p>Sélectionnez le tarif et la puissance qui vous conviennent le mieux</p>
            </div>
            
            <div class="form-content">
                
                <!-- Sélection du tarif -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">💰</span>
                        Choisissez votre tarif
                    </label>
                    <div class="tarif-selection">
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_base" 
                                name="tarif_choisi" 
                                value="base">
                            <label for="tarif_base" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Base TRV</h4>
                                    <span class="tarif-badge simple">Simple</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Prix unique toute la journée</p>
                                    <small>Idéal si vous consommez régulièrement</small>
                                </div>
                                <div class="tarif-price" id="prix-base">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_hc" 
                                name="tarif_choisi" 
                                value="hc">
                            <label for="tarif_hc" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Heures Creuses TRV</h4>
                                    <span class="tarif-badge economique">Économique</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Prix réduit 8h par jour</p>
                                    <small>Économies en décalant vos usages</small>
                                </div>
                                <div class="tarif-price" id="prix-hc">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_tempo" 
                                name="tarif_choisi" 
                                value="tempo">
                            <label for="tarif_tempo" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Tempo TRV</h4>
                                    <span class="tarif-badge expert">Expert</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Tarif variable selon les jours</p>
                                    <small>Maximum d'économies avec contraintes</small>
                                </div>
                                <div class="tarif-price" id="prix-tempo">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Type de logement -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">🏠</span>
                        Type de résidence
                    </label>
                    <div class="radio-grid">
                        <div class="radio-option">
                            <input type="radio" 
                                id="residence_principale" 
                                name="type_logement_usage" 
                                value="principal">
                            <label for="residence_principale" class="radio-label">
                                <div class="option-icon">🏠</div>
                                <div class="option-content">
                                    <h4>Résidence principale</h4>
                                    <p>Votre logement principal</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-option">
                            <input type="radio" 
                                id="residence_secondaire" 
                                name="type_logement_usage" 
                                value="secondaire">
                            <label for="residence_secondaire" class="radio-label">
                                <div class="option-icon">🏖️</div>
                                <div class="option-content">
                                    <h4>Résidence secondaire</h4>
                                    <p>Maison de vacances, etc.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Sélection de puissance -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">⚡</span>
                        Puissance souscrite (kVA)
                    </label>
                    <div class="puissance-recommandee">
                            <div class="recommendation-badge">
                                <span class="badge-icon">⭐</span>
                                <span>Recommandé pour vous</span>
                            </div>
                            
                        </div>
                    <div class="puissance-selection">
                        
                       
                    </div>
                </div>
                
                <!-- Résumé des calculs -->
                <div class="calculs-resume" id="calculs-selection">
                    <div class="loading-calculs">
                        <div class="spinner-mini"></div>
                        <p>Chargement des calculs...</p>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 9: Informations client -->
        <div class="form-step" data-step="9">
            <div class="step-header">
                <h2>Informations de contact</h2>
                <p>Dernière étape avant votre souscription</p>
            </div>
            
            <div class="form-content">
                
                <!-- Localisation -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon location">📍</div>
                        <div class="card-title">Localisation</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="pdl_adresse" 
                                    name="pdl_adresse" 
                                    placeholder=" " 
                                    >
                                <label for="pdl_adresse">Point de livraison</label>
                                <span class="input-hint">Format: BT/40001/000001</span>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="numero_compteur" 
                                    name="numero_compteur" 
                                    placeholder=" " 
                                    >
                                <label for="numero_compteur">N° Point Référence Mesure</label>
                                <span class="input-hint">Écran N°6 Linky</span>
                            </div>
                        </div>
                        
                        <button type="button" class="toggle-btn" id="btn-no-info">
                            <span class="toggle-text">Je n'ai pas ces informations</span>
                            <span class="toggle-icon">+</span>
                        </button>
                        
                        <div class="collapsible-section" id="address-section">
                            <div class="dual-input">
                                <div class="input-box flex-2">
                                    <input type="text" 
                                        id="client_adresse" 
                                        name="client_adresse" 
                                        placeholder=" ">
                                    <label for="client_adresse">Adresse complète</label>
                                </div>
                                
                                <div class="input-box">
                                    <input type="text" 
                                        id="client_code_postal" 
                                        name="client_code_postal" 
                                        placeholder=" " 
                                        maxlength="5">
                                    <label for="client_code_postal">Code postal</label>
                                </div>
                            </div>
                            
                            <div class="dual-input">
                                <div class="input-box">
                                    <input type="text" 
                                        id="client_complement" 
                                        name="client_complement" 
                                        placeholder=" ">
                                    <label for="client_complement">Complément (optionnel)</label>
                                </div>
                                
                                <div class="input-box">
                                    <input type="text" 
                                        id="client_ville" 
                                        name="client_ville" 
                                        placeholder=" ">
                                    <label for="client_ville">Ville</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ancien locataire -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon user">👤</div>
                        <div class="card-title">Ancien locataire <span class="optional">(optionnel)</span></div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="ancien_nom" 
                                    name="ancien_nom" 
                                    placeholder=" ">
                                <label for="ancien_nom">Nom</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="ancien_prenom" 
                                    name="ancien_prenom" 
                                    placeholder=" ">
                                <label for="ancien_prenom">Prénom</label>
                            </div>
                        </div>
                        
                        <div class="input-box">
                            <input type="text" 
                                id="ancien_numero_compteur" 
                                name="ancien_numero_compteur" 
                                placeholder=" ">
                            <label for="ancien_numero_compteur">Numéro de compteur</label>
                        </div>
                    </div>
                </div>
                
                <!-- Informations personnelles -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon profile">✏️</div>
                        <div class="card-title">Vos informations</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="client_nom" 
                                    name="client_nom" 
                                    placeholder=" " 
                                    >
                                <label for="client_nom">Nom *</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="client_prenom" 
                                    name="client_prenom" 
                                    placeholder=" " 
                                    >
                                <label for="client_prenom">Prénom *</label>
                            </div>
                        </div>
                        
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="email" 
                                    id="client_email" 
                                    name="client_email" 
                                    placeholder=" " 
                                    >
                                <label for="client_email">Email *</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="tel" 
                                    id="client_telephone" 
                                    name="client_telephone" 
                                    placeholder=" " 
                                    >
                                <label for="client_telephone">Téléphone *</label>
                            </div>
                        </div>
                        
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="date" 
                                    id="client_date_naissance" 
                                    name="client_date_naissance" 
                                    >
                                <label for="client_date_naissance">Date de naissance *</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="client_lieu_naissance" 
                                    name="client_lieu_naissance" 
                                    placeholder=" ">
                                <label for="client_lieu_naissance">Lieu de naissance</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Documents -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon documents">📎</div>
                        <div class="card-title">Documents requis</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="upload-grid">
                            <div class="upload-card" data-file="rib_file">
                                <div class="upload-visual">
                                    <div class="upload-type rib">🏦</div>
                                    <h4>RIB</h4>
                                    <p>Relevé d'Identité Bancaire</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">PDF, JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="rib_file" 
                                    name="rib_file" 
                                    accept=".pdf,.jpg,.jpeg,.png" 
                                    >
                                <div class="upload-result" id="rib-status"></div>
                            </div>
                            
                            <div class="upload-card" data-file="carte_identite_recto">
                                <div class="upload-visual">
                                    <div class="upload-type identity">🆔</div>
                                    <h4>Pièce d'identité</h4>
                                    <p>Recto</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="carte_identite_recto" 
                                    name="carte_identite_recto" 
                                    accept=".jpg,.jpeg,.png" 
                                    >
                                <div class="upload-result" id="recto-status"></div>
                            </div>
                            
                            <div class="upload-card" data-file="carte_identite_verso">
                                <div class="upload-visual">
                                    <div class="upload-type identity">🆔</div>
                                    <h4>Pièce d'identité</h4>
                                    <p>Verso</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="carte_identite_verso" 
                                    name="carte_identite_verso" 
                                    accept=".jpg,.jpeg,.png" 
                                    >
                                <div class="upload-result" id="verso-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conditions -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon validation">✅</div>
                        <div class="card-title">Validation finale</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="check-list">
                            <label class="check-item">
                                <input type="checkbox" 
                                    id="accept_conditions" 
                                    name="accept_conditions" 
                                    >
                                <span class="checkmark"></span>
                                <span class="check-text">
                                    J'accepte les <a href="#" target="_blank">conditions générales</a> 
                                    et <a href="#" target="_blank">conditions particulières</a>
                                </span>
                            </label>
                            
                            <label class="check-item">
                                <input type="checkbox" 
                                    id="accept_prelevement" 
                                    name="accept_prelevement" 
                                    >
                                <span class="checkmark"></span>
                                <span class="check-text">
                                    J'autorise le prélèvement automatique
                                </span>
                            </label>
                            
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 10: Récapitulatif final -->
        <div class="form-step" data-step="10">
            <div class="step-header">
                <h2>📋 Récapitulatif de votre simulation</h2>
                <p>Vérifiez toutes vos informations avant finalisation</p>
            </div>
            
            <div class="form-content">
                <!-- Container pour le récapitulatif généré dynamiquement -->
                <div id="recap-container-final">
                    <div class="loading-recap">
                        <div class="spinner"></div>
                        <p>Génération du récapitulatif complet...</p>
                    </div>
                </div>
                
                <!-- Actions finales -->
                <div class="final-actions">
                    <div class="action-card highlight">
                        <div class="action-icon">🎯</div>
                        <div class="action-content">
                            <h4>Souscrire maintenant</h4>
                            <p>Finalisez directement votre souscription en ligne</p>
                        </div>
                        <button type="button" 
                                class="btn btn-primary btn-large" 
                                id="btn-finaliser-souscription">
                            <span class="btn-icon">✅</span>
                            Finaliser ma souscription
                        </button>
                    </div>
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