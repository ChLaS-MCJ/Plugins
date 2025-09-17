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
        <div class="header-icon">⚡</div>
        <h1>Simulateur Gaz Résidentiel</h1>
        <p>Estimez votre consommation et trouvez le meilleur tarif pour votre logement</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="12.5"></div>
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
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="6">
                <span class="step-number">6</span>
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-elec-residentiel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Informations du logement -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🏠 Informations sur votre logement</h2>
                <p>Quelques questions sur votre habitat pour personnaliser l'estimation</p>
            </div>
            
            <div class="form-content">
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
                            <input type="number" id="nb_personnes" name="nb_personnes" min="1" max="10" value="4" required>
                            <span class="input-suffix">personnes</span>
                        </div>
                        <div class="field-help">Nombre d'occupants permanents</div>
                    </div>
                    
                    <!-- Commune -->
                    <div class="form-group full-width">
                        <label for="commune">Commune d'habitation</label>
                        <div class="field-help">Votre commune pour déterminer le type de gaz disponible</div>
                        
                        <select id="commune" name="commune" required>
                            <option value="">-- Sélectionnez votre commune --</option>
                            
                            <optgroup label="🌱 Communes Gaz Naturel" id="communes-naturel">
                                <!-- Sera rempli par JavaScript -->
                            </optgroup>
                            
                            <optgroup label="⛽ Communes Gaz Propane" id="communes-propane">
                                <!-- Sera rempli par JavaScript -->
                            </optgroup>
                            
                            <optgroup label="🗺️ Autres">
                                <option value="autre" data-type="autre">Autre commune (saisie libre)</option>
                            </optgroup>
                        </select>
                        
                        <!-- Section conditionnelle pour "Autre commune" -->
                        <div id="autre-commune-details" class="conditional-section" style="display: none;">
                            <div class="form-subgroup">
                                <label for="nom_commune_autre">Nom de votre commune</label>
                                <input type="text" id="nom_commune_autre" name="nom_commune_autre" placeholder="Saisissez le nom de votre commune">
                                
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
                            <span class="info-icon"></span>
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
            </div>
        </div>
        
        <!-- ÉTAPE 2: Chauffage -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>🔥 Chauffage au gaz</h2>
                <p>Informations sur le chauffage de votre logement</p>
            </div>
            
            <div class="form-content">
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
                                        <div class="radio-subtitle">Chauffage au gaz</div>
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
                    <div class="form-group full-width" id="chauffage-details">
                        <label class="form-label">Isolation de votre logement</label>
                        <div class="radio-group radio-column">
                            <div class="radio-card">
                                <input type="radio" id="isolation_faible" name="isolation" value="faible" checked>
                                <label for="isolation_faible" class="radio-content">
                                    <div class="radio-icon">🏘️</div>
                                    <div class="radio-text">
                                        <div class="radio-title">Isolation faible</div>
                                        <div class="radio-subtitle">Logement ancien, peu isolé</div>
                                        <div class="radio-details">Consommation élevée : 160 kWh/m²/an</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="radio-card">
                                <input type="radio" id="isolation_correcte" name="isolation" value="correcte">
                                <label for="isolation_correcte" class="radio-content">
                                    <div class="radio-icon">🏡</div>
                                    <div class="radio-text">
                                        <div class="radio-title">Isolation correcte</div>
                                        <div class="radio-subtitle">RT 2000, RT 2005, RT 2012</div>
                                        <div class="radio-details">Consommation modérée : 110 kWh/m²/an</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="radio-card">
                                <input type="radio" id="isolation_bonne" name="isolation" value="bonne">
                                <label for="isolation_bonne" class="radio-content">
                                    <div class="radio-icon">🏠</div>
                                    <div class="radio-text">
                                        <div class="radio-title">Bonne isolation</div>
                                        <div class="radio-subtitle">Travaux d'isolation réalisés</div>
                                        <div class="radio-details">Consommation réduite : 70 kWh/m²/an</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="radio-card">
                                <input type="radio" id="isolation_excellente" name="isolation" value="excellente">
                                <label for="isolation_excellente" class="radio-content">
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
            </div>
        </div>
        
        <!-- ÉTAPE 3: Eau chaude -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>🚿 Production d'eau chaude</h2>
                <p>Comment est produite l'eau chaude dans votre logement ?</p>
            </div>
            
            <div class="form-content">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label class="form-label">Eau chaude sanitaire</label>
                        <div class="radio-group">
                            <div class="radio-card">
                                <input type="radio" id="ecs_gaz" name="eau_chaude" value="gaz" checked>
                                <label for="ecs_gaz" class="radio-content">
                                    <div class="radio-icon">🔥</div>
                                    <div class="radio-text">
                                        <div class="radio-title">Gaz</div>
                                        <div class="radio-subtitle">Chauffe-eau ou chaudière gaz</div>
                                        <div class="radio-details">Consommation : <span id="eau-chaude-estimation">1600 kWh/an</span></div>
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
            </div>
        </div>
        
        <!-- ÉTAPE 4: Cuisson -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>🍳 Cuisson au gaz</h2>
                <p>Utilisez-vous le gaz pour la cuisson ?</p>
            </div>
            
            <div class="form-content">
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
                                        <div class="radio-subtitle">Cuisson au gaz</div>
                                        <div class="radio-details">Consommation : <span id="cuisson-estimation">200 kWh/an</span></div>
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
            </div>
        </div>
        
        <!-- ÉTAPE 5: Informations client -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>📧 Vos coordonnées</h2>
                <p>Pour recevoir votre simulation personnalisée et être recontacté si vous le souhaitez</p>
            </div>
            
            <div class="form-grid">
                <!-- Nom -->
                <div class="form-group">
                    <label for="client_nom" class="form-label">Nom *</label>
                    <input type="text" 
                        id="client_nom" 
                        name="client_nom" 
                        required 
                        class="form-input"
                        placeholder="Votre nom">
                </div>
                
                <!-- Prénom -->
                <div class="form-group">
                    <label for="client_prenom" class="form-label">Prénom *</label>
                    <input type="text" 
                        id="client_prenom" 
                        name="client_prenom" 
                        required 
                        class="form-input"
                        placeholder="Votre prénom">
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="client_email" class="form-label">Email *</label>
                    <input type="email" 
                        id="client_email" 
                        name="client_email" 
                        required 
                        class="form-input"
                        placeholder="exemple@email.com">
                    <small class="form-help">Pour recevoir votre simulation</small>
                </div>
                
                <!-- Téléphone -->
                <div class="form-group">
                    <label for="client_telephone" class="form-label">Téléphone *</label>
                    <input type="tel" 
                        id="client_telephone" 
                        name="client_telephone" 
                        required 
                        class="form-input"
                        placeholder="06 XX XX XX XX">
                    <small class="form-help">Pour être recontacté si besoin</small>
                </div>
                
                <!-- Adresse -->
                <div class="form-group full-width">
                    <label for="client_adresse" class="form-label">Adresse complète (optionnel)</label>
                    <input type="text" 
                        id="client_adresse" 
                        name="client_adresse" 
                        class="form-input"
                        placeholder="Numéro et nom de rue">
                </div>
                
                <!-- Code postal et Ville sur la même ligne -->
                <div class="form-group">
                    <label for="client_code_postal" class="form-label">Code postal (optionnel)</label>
                    <input type="text" 
                        id="client_code_postal" 
                        name="client_code_postal" 
                        pattern="[0-9]{5}"
                        maxlength="5"
                        class="form-input"
                        placeholder="40000">
                </div>
                
                <div class="form-group">
                    <label for="client_ville" class="form-label">Ville (optionnel)</label>
                    <input type="text" 
                        id="client_ville" 
                        name="client_ville" 
                        class="form-input"
                        placeholder="Votre ville">
                </div>
                
                <!-- Information RGPD avec le même style que les autres info-box -->
                <div class="form-group full-width">
                    <div class="info-box">
                        <div class="info-icon">🔒</div>
                        <div class="info-content">
                            <h4>Vos données sont protégées</h4>
                            <p><strong>Envoi immédiat :</strong> Vos résultats détaillés seront envoyés directement à notre adresse email.</p>
                            <p><strong>Confidentialité :</strong> Aucune donnée n'est conservée sur nos serveurs après l'envoi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 6: Résultat -->
        <div class="form-step" data-step="6">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos informations</p>
            </div>
            
            <!-- Container des résultats -->
            <div id="results-container">
                <!-- Les résultats seront injectés ici par JavaScript -->
            </div>
            
            <!-- NOUVELLE SECTION : Actions après résultats -->
            <div class="results-actions" style="display: none;">
                <div class="actions-grid">
                    <!-- Bouton télécharger PDF -->
                    <button type="button" class="btn btn-secondary" id="btn-download-pdf">
                        <span class="btn-icon">📄</span>
                        Télécharger le PDF
                    </button>
                    
                    <!-- Bouton envoyer par mail -->
                    <button type="button" class="btn btn-primary" id="btn-send-email">
                        <span class="btn-icon">✉️</span>
                        Recevoir par email
                    </button>
                    
                </div>
                
                <!-- Message de confirmation (caché par défaut) -->
                <div class="confirmation-message" id="email-confirmation" style="display: none;">
                    <div class="success-icon">✅</div>
                    <p>Votre simulation a été envoyée avec succès à <strong id="email-display"></strong></p>
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