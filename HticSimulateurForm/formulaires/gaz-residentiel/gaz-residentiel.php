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
                <span class="step-label">Calcul</span>
            </div>
            <div class="step" data-step="6">
                <span class="step-number">6</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="7">
                <span class="step-number">7</span>
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
                <div class="form-group">
                    <label for="commune">Commune d'habitation</label>
                    <div class="field-help">Votre commune pour déterminer la zone tarifaire</div>
                    
                    <select id="commune" name="commune" required>
                        <option value="">-- Sélectionnez votre commune --</option>
                        
                        <!-- Communes depuis le back-office (seront chargées dynamiquement) -->
                        <optgroup label="🌱 Communes Gaz Naturel" id="communes-naturel">
                            <!-- Sera rempli par AJAX depuis le back-office -->
                        </optgroup>
                        
                        <optgroup label="⛽ Communes Gaz Propane" id="communes-propane">
                            <!-- Sera rempli par AJAX depuis le back-office -->
                        </optgroup>
                        
                        <!-- Option Autre -->
                        <optgroup label="🗺️ Autres">
                            <option value="autre" data-type="autre">Autre commune (saisie libre)</option>
                        </optgroup>
                    </select>
                    
                    <!-- Section conditionnelle pour "Autre commune" -->
                    <div id="autre-commune-details" class="conditional-section" style="display: none;">
                        <div class="form-subgroup">
                            <label for="nom_commune_autre">Nom de votre commune</label>
                            <input type="text" 
                                id="nom_commune_autre" 
                                name="nom_commune_autre" 
                                placeholder="Saisissez le nom de votre commune">
                            
                            <label>Type de gaz disponible</label>
                            <div class="radio-group">
                                <div class="radio-card">
                                    <input type="radio" id="type_gaz_naturel_autre" name="type_gaz_autre" value="naturel" checked>
                                    <label for="type_gaz_naturel_autre" class="radio-content">
                                        <div class="radio-icon">🌱</div>
                                        <div class="radio-text">
                                            <div class="radio-title">Gaz naturel</div>
                                            <div class="radio-subtitle">Réseau GRDF</div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="radio-card">
                                    <input type="radio" id="type_gaz_propane_autre" name="type_gaz_autre" value="propane">
                                    <label for="type_gaz_propane_autre" class="radio-content">
                                        <div class="radio-icon">⛽</div>
                                        <div class="radio-text">
                                            <div class="radio-title">Gaz propane</div>
                                            <div class="radio-subtitle">Citerne GPL</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Affichage du type de gaz détecté -->
                    <div id="type-gaz-info" class="info-box" style="display: none;">
                        <span class="info-icon">⛽</span>
                        <span id="type-gaz-text"></span>
                    </div>
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
                
                <!-- Isolation du logement (affiché si chauffage au gaz) - VALEURS EXCEL CORRIGÉES -->
                <div class="form-group full-width chauffage-details">
                    <label class="form-label">Isolation de votre logement</label>
                    <div class="radio-group radio-column">
                        <div class="radio-card">
                            <input type="radio" id="iso_niveau_1" name="isolation" value="niveau_1" checked>
                            <label for="iso_niveau_1" class="radio-content">
                                <div class="radio-icon">🏘️</div>
                                <div class="radio-text">
                                    <div class="radio-title">Isolation faible</div>
                                    <div class="radio-subtitle">Logement ancien, peu isolé</div>
                                    <div class="radio-details">Consommation élevée : 160 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_niveau_2" name="isolation" value="niveau_2">
                            <label for="iso_niveau_2" class="radio-content">
                                <div class="radio-icon">🏠</div>
                                <div class="radio-text">
                                    <div class="radio-title">Bonne isolation</div>
                                    <div class="radio-subtitle">Travaux d'isolation réalisés</div>
                                    <div class="radio-details">Consommation réduite : 70 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_niveau_3" name="isolation" value="niveau_3">
                            <label for="iso_niveau_3" class="radio-content">
                                <div class="radio-icon">🏡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Isolation correcte</div>
                                    <div class="radio-subtitle">RT 2000, RT 2005, RT 2012</div>
                                    <div class="radio-details">Consommation modérée : 110 kWh/m²/an</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="iso_niveau_4" name="isolation" value="niveau_4">
                            <label for="iso_niveau_4" class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <div class="radio-title">Très bonne isolation</div>
                                    <div class="radio-subtitle">RT 2012+, maison passive</div>
                                    <div class="radio-details">Consommation faible : 20 kWh/m²/an</div>
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
                                    <div class="radio-details">Consommation : <span id="eau-chaude-estimation">2000 kWh/an</span> (400 kWh/personne)</div>
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
                                    <div class="radio-details">Consommation : <span id="cuisson-estimation">250 kWh/an</span> (50 kWh/personne)</div>
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
        
        <!-- ÉTAPE 5: Calcul en cours et aperçu -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>🧮 Calcul de votre estimation</h2>
                <p>Traitement de vos données en cours...</p>
            </div>
            
            <!-- Zone de calcul (sera remplie via JavaScript) -->
            <div id="calcul-container">
                <div class="calcul-loading">
                    <div class="loading-spinner"></div>
                    <p>Calcul de votre consommation gaz en cours...</p>
                </div>
            </div>
            
            <div class="form-actions" style="display: none;" id="calcul-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Modifier mes réponses
                </button>
                <button type="button" class="btn btn-primary btn-next">
                    Recevoir mon devis personnalisé
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 6: Données de contact -->
        <div class="form-step" data-step="6">
            <div class="step-header">
                <h2>📧 Recevez votre estimation détaillée</h2>
                <p>Laissez-nous vos coordonnées pour recevoir votre devis personnalisé par email</p>
            </div>
            
            <div class="contact-benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">📊</span>
                    <span>Récapitulatif détaillé de votre consommation</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">💰</span>
                    <span>Estimation précise de vos économies</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📞</span>
                    <span>Conseils personnalisés de nos experts</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">🛡️</span>
                    <span>Vos données sont sécurisées et ne seront pas revendues</span>
                </div>
            </div>
            
            <div class="form-grid">
                
                <!-- Civilité -->
                <div class="form-group">
                    <label class="form-label">Civilité *</label>
                    <div class="radio-group inline-radio">
                        <div class="radio-card compact">
                            <input type="radio" id="civilite_m" name="civilite" value="M." required>
                            <label for="civilite_m" class="radio-content compact">
                                <span class="radio-title">M.</span>
                            </label>
                        </div>
                        
                        <div class="radio-card compact">
                            <input type="radio" id="civilite_mme" name="civilite" value="Mme" required>
                            <label for="civilite_mme" class="radio-content compact">
                                <span class="radio-title">Mme</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Prénom -->
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                </div>
                
                <!-- Nom -->
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Adresse email *</label>
                    <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                    <div class="field-help">Vous recevrez votre estimation à cette adresse</div>
                </div>
                
                <!-- Téléphone -->
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" placeholder="06 12 34 56 78">
                    <div class="field-help">Optionnel - pour un conseil personnalisé</div>
                </div>
                
                <!-- Code postal -->
                <div class="form-group">
                    <label for="code_postal">Code postal *</label>
                    <input type="text" id="code_postal" name="code_postal" pattern="[0-9]{5}" placeholder="75000" required>
                </div>
                
                <!-- Source -->
                <div class="form-group full-width">
                    <label for="comment_nous_avez_vous_connu">Comment nous avez-vous connus ? *</label>
                    <select id="comment_nous_avez_vous_connu" name="comment_nous_avez_vous_connu" required>
                        <option value="">Sélectionnez une option...</option>
                        <option value="recherche_google">Recherche Google</option>
                        <option value="reseaux_sociaux">Réseaux sociaux</option>
                        <option value="bouche_a_oreille">Bouche à oreille</option>
                        <option value="site_comparateur">Site comparateur</option>
                        <option value="publicite">Publicité</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                
                <!-- Commentaires -->
                <div class="form-group full-width">
                    <label for="commentaires">Commentaires ou questions</label>
                    <textarea id="commentaires" name="commentaires" rows="3" placeholder="Des questions particulières ou des précisions sur votre projet ?"></textarea>
                </div>
                
                <!-- Acceptation CGU -->
                <div class="form-group full-width">
                    <div class="checkbox-card legal">
                        <input type="checkbox" id="accepte_cgu" name="accepte_cgu" required>
                        <label for="accepte_cgu" class="checkbox-content legal">
                            <div class="checkbox-text">
                                <div class="checkbox-title">
                                    J'accepte les <a href="#" target="_blank">conditions générales d'utilisation</a> 
                                    et la <a href="#" target="_blank">politique de confidentialité</a> *
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Acceptation contact commercial -->
                <div class="form-group full-width">
                    <div class="checkbox-card legal">
                        <input type="checkbox" id="accepte_contact" name="accepte_contact">
                        <label for="accepte_contact" class="checkbox-content legal">
                            <div class="checkbox-text">
                                <div class="checkbox-title">
                                    J'accepte d'être contacté(e) par HTIC pour des offres commerciales
                                </div>
                                <div class="checkbox-subtitle">
                                    Optionnel - Vous pouvez refuser et recevoir uniquement votre estimation
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-send-results">
                    <span class="btn-icon">📧</span>
                    Recevoir mon estimation détaillée
                </button>
            </div>
        </div>
        
        <!-- ÉTAPE 7: Résultats et confirmation -->
        <div class="form-step" data-step="7">
            <div class="step-header">
                <h2>✅ Estimation envoyée !</h2>
                <p>Votre estimation détaillée a été envoyée à votre adresse email</p>
            </div>
            
            <!-- Zone des résultats (sera remplie via JavaScript) -->
            <div id="resultats-container"></div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                    <span class="btn-icon">🔄</span>
                    Nouvelle simulation
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