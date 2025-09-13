<!-- formulaires/elec-professionnel/elec-professionnel.php -->
<div class="htic-simulateur-wrapper" id="htic-simulateur-elec-professionnel">
    <!-- En-tête -->
    <div class="simulateur-header">
        <span class="header-icon">🏢</span>
        <h1>Simulateur Électricité Professionnel</h1>
        <p>Estimez votre consommation et comparez les tarifs pour votre entreprise</p>
    </div>

    <!-- Barre de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 25%;"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Entreprise</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Locaux</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Activité</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Résultat</div>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="simulateur-form">
        
        <!-- ÉTAPE 1: Informations entreprise -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🏢 Informations sur votre entreprise</h2>
                <p>Ces informations déterminent votre éligibilité aux tarifs réglementés</p>
            </div>
            
            <div class="form-grid">
                <!-- Nombre de salariés -->
                <div class="form-group">
                    <label for="nb_salaries" class="form-label">Nombre de salariés</label>
                    <select id="nb_salaries" name="nb_salaries" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="1-9">1 à 9 salariés (éligible TRV)</option>
                        <option value="10+">10 salariés ou plus (marché libre)</option>
                    </select>
                    <small class="form-help">Seuil d'éligibilité TRV : moins de 10 salariés</small>
                </div>
                
                <!-- Chiffre d'affaires -->
                <div class="form-group">
                    <label for="chiffre_affaires" class="form-label">Chiffre d'affaires annuel</label>
                    <select id="chiffre_affaires" name="chiffre_affaires" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="moins_3m">Moins de 3M€ (éligible TRV)</option>
                        <option value="plus_3m">3M€ ou plus (marché libre)</option>
                    </select>
                    <small class="form-help">Seuil d'éligibilité TRV : moins de 3M€</small>
                </div>
            </div>

            <!-- Info éligibilité TRV -->
            <div class="info-box" id="eligibilite-info" style="display: none;">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                    <h4>Éligibilité aux Tarifs Réglementés de Vente (TRV)</h4>
                    <p id="eligibilite-message"></p>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 2: Informations sur les locaux -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>🏗️ Informations sur vos locaux</h2>
                <p>Décrivez vos espaces pour estimer vos besoins énergétiques</p>
            </div>
            
            <div class="form-grid">
                <!-- Surface totale -->
                <div class="form-group">
                    <label for="surface_totale" class="form-label">Surface totale</label>
                    <div class="input-group">
                        <input type="number" 
                               id="surface_totale" 
                               name="surface_totale" 
                               min="20" 
                               max="10000" 
                               value="200" 
                               required 
                               class="form-input">
                        <span class="input-suffix">m²</span>
                    </div>
                    <small class="form-help">Surface totale de vos locaux</small>
                </div>
                
                <!-- Type d'activité -->
                <div class="form-group">
                    <label for="type_activite" class="form-label">Type d'activité principal</label>
                    <select id="type_activite" name="type_activite" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="bureau">🏢 Bureau / Services</option>
                        <option value="commerce">🛒 Commerce / Magasin</option>
                        <option value="restauration">🍽️ Restaurant / Café</option>
                        <option value="industrie">🔧 Industrie / Artisanat</option>
                        <option value="autre">📋 Autre activité</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 3: Profil d'activité et équipements -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>⚡ Équipements et horaires</h2>
                <p>Précisez vos équipements et horaires de fonctionnement</p>
            </div>
            
            <div class="form-grid">
                <!-- Horaires de fonctionnement -->
                <div class="form-group">
                    <label for="horaires" class="form-label">Horaires de fonctionnement</label>
                    <select id="horaires" name="horaires" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="standard">🕘 Standard (8h-18h, lun-ven)</option>
                        <option value="etendu">🛒 Étendu (7h-20h + samedi)</option>
                        <option value="continu">🏭 Continue (24h/24)</option>
                    </select>
                    <small class="form-help">Influence le choix du tarif optimal</small>
                </div>
                
                <!-- Informatique -->
                <div class="form-group">
                    <label for="nb_postes" class="form-label">Postes informatiques</label>
                    <div class="input-group">
                        <input type="number" 
                               id="nb_postes" 
                               name="nb_postes" 
                               min="0" 
                               max="100" 
                               value="5"
                               class="form-input">
                        <span class="input-suffix">postes</span>
                    </div>
                    <small class="form-help">PC, écrans, imprimantes</small>
                </div>
                
                <!-- Chauffage -->
                <div class="form-group full-width">
                    <label class="form-label">Type de chauffage principal</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="electrique" required>
                            <div class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <strong>Électrique</strong>
                                    <span>Radiateurs, PAC électrique</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="gaz" required>
                            <div class="radio-content">
                                <div class="radio-icon">🔥</div>
                                <div class="radio-text">
                                    <strong>Gaz</strong>
                                    <span>Chaudière gaz</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="type_chauffage" value="aucun" required>
                            <div class="radio-content">
                                <div class="radio-icon">🚫</div>
                                <div class="radio-text">
                                    <strong>Pas de chauffage</strong>
                                    <span>Local non chauffé</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Climatisation -->
                <div class="form-group">
                    <label for="climatisation" class="form-label">Climatisation</label>
                    <select id="climatisation" name="climatisation" class="form-select">
                        <option value="aucune">Aucune</option>
                        <option value="legere">Climatisation légère</option>
                        <option value="complete">Climatisation complète</option>
                    </select>
                </div>
                
                <!-- Éclairage -->
                <div class="form-group">
                    <label for="eclairage" class="form-label">Type d'éclairage</label>
                    <select id="eclairage" name="eclairage" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="led">💡 LED (économique)</option>
                        <option value="fluo">💡 Néon / Fluorescent</option>
                        <option value="mixte">💡 Mixte</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 4: Résultats -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation de consommation et comparaison des tarifs</p>
            </div>
            
            <!-- Zone des résultats (sera remplie par JavaScript) -->
            <div id="resultats-professionnel">
                <div class="loading-moderne">
                    <div class="loading-spinner-moderne"></div>
                    <p>Calcul de votre simulation en cours...</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="form-navigation">
            <button type="button" id="btn-precedent" class="btn btn-secondary" style="display: none;">
                <span>←</span> Précédent
            </button>
            
            <div class="nav-spacer"></div>
            
            <button type="button" id="btn-suivant" class="btn btn-primary">
                Suivant <span>→</span>
            </button>
            
            <button type="button" id="btn-calculer" class="btn btn-primary" style="display: none;">
                Calculer ma facture <span>📊</span>
            </button>
        </div>
    </div>
</div>

<!-- Script de configuration (sera injecté par PHP) -->
<script type="application/json" id="simulateur-config">
{
  "debug": true,
  "ajax_url": "/wp-admin/admin-ajax.php",
  "nonce": "simulateur_nonce_value"
}
</script>