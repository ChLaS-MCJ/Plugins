<?php
/**
 * Template du formulaire Gaz Professionnel
 * Fichier: formulaires/gaz-professionnel/gaz-professionnel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données de configuration depuis l'admin
$config_data = get_option('htic_simulateur_gaz_professionnel_data', array());
?>

<div class="htic-simulateur-wrapper" data-type="gaz-professionnel">
    
    <!-- En-tête du simulateur -->
    <div class="simulateur-header">
        <div class="header-icon">🔥</div>
        <h1>Formulaire de souscription gaz professionnel</h1>
        <p>Remplissez tous les champs du formulaire pour passer à l'étape suivante</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="25"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Configuration</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Localisation</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Titulaire</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-gaz-professionnel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Configuration gaz -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🔥 Configuration gaz</h2>
                <p>Informations sur votre consommation de gaz professionnel</p>
            </div>
            
            <div class="form-content">
                <div class="form-grid">
                    
                    <!-- Commune -->
                    <div class="form-group full-width">
                        <label for="commune">Commune de votre entreprise</label>
                        <div class="field-help">Sélectionnez votre commune pour déterminer le type de gaz disponible</div>
                        
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
                
                    <!-- Consommation prévisionnelle -->
                    <div class="form-group full-width">
                        <label for="consommation_previsionnelle">Consommation prévisionnelle annuelle</label>
                        <div class="input-group">
                            <input type="number" 
                                   id="consommation_previsionnelle" 
                                   name="consommation_previsionnelle" 
                                   min="100" 
                                   max="1000000" 
                                   step="100"
                                   placeholder="15000"
                                   required>
                            <span class="input-suffix">kWh/an</span>
                        </div>
                        <div class="field-help">Indiquez votre consommation annuelle estimée en kWh</div>
                    </div>
                    
                    <!-- Info box pour aide -->
                    <div class="form-group full-width">
                        <div class="info-box">
                            <div class="info-icon">💡</div>
                            <div class="info-content">
                                <h4>Comment estimer votre consommation ?</h4>
                                <p>• <strong>Petite entreprise/commerce :</strong> 5 000 à 15 000 kWh/an</p>
                                <p>• <strong>PME/Restaurant :</strong> 15 000 à 50 000 kWh/an</p>
                                <p>• <strong>Industrie/Hôtel :</strong> 50 000 à 200 000 kWh/an</p>
                                <p>• <strong>Grande industrie :</strong> > 200 000 kWh/an</p>
                                <p><em>Vous pouvez retrouver votre consommation sur vos dernières factures.</em></p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- ÉTAPE 2: Localisation (reprise de elec-pro) -->
         <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>📍 Localisation</h2>
                <p>Adresse du point de livraison électrique</p>
            </div>
            
            <!-- Section Point de Livraison -->
            <div class="localisation-section">
                <div class="localisation-header">
                    <div class="localisation-icon">🔌</div>
                    <div class="localisation-title">
                        <h3>Identification du point de livraison</h3>
                        <div class="localisation-subtitle">Ces informations se trouvent sur votre facture d'électricité</div>
                    </div>
                </div>
                
                <div class="pdl-section">
                    <div class="pdl-info">
                        <div class="pdl-info-icon">ℹ️</div>
                        <div class="pdl-info-text">
                            Le Point de Livraison (PDL) ou Point de Référence Mesure (PRM) est un numéro unique qui identifie votre compteur électrique. 
                            Vous le trouverez en haut à gauche de votre facture d'électricité ou sur l'écran n°6 de votre compteur Linky.
                        </div>
                    </div>
                    
                    <div class="pdl-inputs">
                        <div class="form-group">
                            <label for="point_livraison" class="form-label">
                                Point de livraison (PDL)
                                <span class="field-tooltip" data-tooltip="Format: BT4000100001">?</span>
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon">🔌</span>
                                <input type="text" 
                                    id="point_livraison" 
                                    name="point_livraison" 
                                    placeholder="Ex: BT4000100001"
                                    pattern="[A-Z]{2}[0-9]{10}"
                                    class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="num_prm" class="form-label">
                                OU N° PRM (14 chiffres)
                                <span class="field-tooltip" data-tooltip="14 chiffres sur votre compteur Linky">?</span>
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon">📟</span>
                                <input type="text" 
                                    id="num_prm" 
                                    name="num_prm" 
                                    placeholder="Ex: 12345678901234"
                                    pattern="[0-9]{14}"
                                    maxlength="14"
                                    class="form-input">
                            </div>
                        </div>
                        
                        <div class="pdl-checkbox">
                            <input type="checkbox" id="pas_info" name="pas_info">
                            <label for="pas_info">Je n'ai pas cette information pour le moment</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section Adresse -->
            <div class="localisation-section">
                <div class="localisation-header">
                    <div class="localisation-icon">🏢</div>
                    <div class="localisation-title">
                        <h3>Adresse du site</h3>
                        <div class="localisation-subtitle">Adresse complète du point de livraison</div>
                    </div>
                </div>
                
                <div class="address-grid">
                    <div class="form-group">
                        <label for="adresse" class="form-label">Adresse *</label>
                        <input type="text" 
                            id="adresse" 
                            name="adresse" 
                            placeholder="Numéro et nom de rue"
                            required
                            class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                        <input type="text" 
                            id="complement_adresse" 
                            name="complement_adresse" 
                            placeholder="Bâtiment, étage, etc."
                            class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="code_postal" class="form-label">Code postal *</label>
                        <input type="text" 
                            id="code_postal" 
                            name="code_postal" 
                            placeholder="00000"
                            pattern="[0-9]{5}"
                            maxlength="5"
                            required
                            class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="ville" class="form-label">Ville *</label>
                        <input type="text" 
                            id="ville" 
                            name="ville" 
                            placeholder="Ville"
                            required
                            class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 3: Titulaire du contrat (reprise de elec-pro) -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>👤 Titulaire du contrat</h2>
                <p>Informations légales de l'entreprise</p>
            </div>
            
            <div class="titulaire-sections">
                <!-- Section Responsable -->
                <div class="responsable-section">
                    <div class="section-header">
                        <div class="section-icon">👤</div>
                        <div class="section-title">
                            <h4>Responsable du contrat</h4>
                            <p>Personne signataire du contrat</p>
                        </div>
                    </div>
                    
                    <div class="identity-grid">
                        <div class="form-group">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" 
                                id="nom" 
                                name="nom" 
                                placeholder="Nom de famille"
                                required
                                class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" 
                                id="prenom" 
                                name="prenom" 
                                placeholder="Prénom"
                                required
                                class="form-input">
                        </div>
                    </div>
                </div>
                
                <!-- Section Entreprise -->
                <div class="entreprise-section">
                    <div class="section-header">
                        <div class="section-icon">🏢</div>
                        <div class="section-title">
                            <h4>Informations entreprise</h4>
                            <p>Données légales de votre société</p>
                        </div>
                    </div>
                    
                    <div class="company-grid">
                        <div class="form-group">
                            <label for="raison_sociale" class="form-label">Raison Sociale *</label>
                            <input type="text" 
                                id="raison_sociale" 
                                name="raison_sociale" 
                                placeholder="Nom de l'entreprise"
                                required
                                class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="forme_juridique" class="form-label">Forme Juridique *</label>
                            <select id="forme_juridique" name="forme_juridique" required class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="SARL">SARL</option>
                                <option value="SAS">SAS</option>
                                <option value="SA">SA</option>
                                <option value="EURL">EURL</option>
                                <option value="SASU">SASU</option>
                                <option value="SCI">SCI</option>
                                <option value="Association">Association</option>
                                <option value="Auto-entrepreneur">Auto-entrepreneur</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="siret" class="form-label">
                                Numéro SIRET *
                                <span class="validation-badge" id="siret-badge" style="display:none;"></span>
                            </label>
                            <input type="text" 
                                id="siret" 
                                name="siret" 
                                placeholder="00000000000000"
                                pattern="[0-9]{14}"
                                maxlength="14"
                                required
                                class="form-input">
                            <small class="form-help">14 chiffres sans espaces</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="code_naf" class="form-label">Code NAF/APE *</label>
                            <input type="text" 
                                id="code_naf" 
                                name="code_naf" 
                                placeholder="0000A"
                                pattern="[0-9]{4}[A-Z]"
                                maxlength="5"
                                required
                                class="form-input">
                            <small class="form-help">4 chiffres + 1 lettre</small>
                        </div>
                    </div>
                </div>
                
                <!-- Section Contact -->
                <div class="contact-section">
                    <div class="section-header">
                        <div class="section-icon">📧</div>
                        <div class="section-title">
                            <h4>Coordonnées de contact</h4>
                            <p>Pour la gestion de votre contrat</p>
                        </div>
                    </div>
                    
                    <div class="contact-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email professionnel *</label>
                            <div class="input-with-icon">
                                <span class="input-icon">📧</span>
                                <input type="email" 
                                    id="email" 
                                    name="email" 
                                    placeholder="contact@entreprise.fr"
                                    required
                                    class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone" class="form-label">Téléphone *</label>
                            <div class="input-with-icon">
                                <span class="input-icon">📞</span>
                                <input type="tel" 
                                    id="telephone" 
                                    name="telephone" 
                                    placeholder="01 23 45 67 89"
                                    pattern="[0-9\s\-\+\(\)]{10,}"
                                    required
                                    class="form-input">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Document -->
                <div class="document-section">
                    <div class="section-header">
                        <div class="section-icon">📄</div>
                        <div class="section-title">
                            <h4>Document justificatif</h4>
                            <p>Extrait K-bis de moins de 3 mois</p>
                        </div>
                    </div>
                    
                    <div class="file-upload-area" id="upload-area">
                        <input type="file" 
                            id="kbis" 
                            name="kbis" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="file-input"
                            style="display: none;">
                        
                        <div class="file-upload-icon">📤</div>
                        <div class="file-upload-text">
                            <div class="file-upload-label">Glissez votre fichier ici</div>
                            <div class="file-upload-help">ou cliquez pour parcourir</div>
                        </div>
                        <label for="kbis" class="file-button">Choisir un fichier</label>
                        
                        <div class="file-selected-name" style="display: none;">
                            <span class="file-name-text"></span>
                            <span class="file-remove">✕</span>
                        </div>
                    </div>
                    
                    <div class="info-box-pro">
                        <div class="info-icon">ℹ️</div>
                        <div class="info-content">
                            <p>Formats acceptés : PDF, JPG, PNG (max 5 Mo)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 4: Résultat -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>📊 Votre estimation tarifaire professionnelle</h2>
                <p>Estimation basée sur votre consommation prévisionnelle</p>
            </div>
            
            <!-- Container des résultats -->
            <div id="results-container">
                <!-- Les résultats seront injectés ici par JavaScript -->
            </div>
            
            <!-- Section Devis personnalisé (cachée par défaut) -->
            <div id="devis-personnalise-container" style="display: none;">
                <div class="devis-alert">
                    <div class="devis-icon">📞</div>
                    <div class="devis-content">
                        <h3>Devis personnalisé requis</h3>
                        <p class="devis-message">Pour une consommation supérieure à 35 000 kWh/an en gaz naturel, un conseiller vous contactera pour établir un devis personnalisé adapté à vos besoins spécifiques.</p>
                        
                        <div class="devis-details">
                            <div class="devis-info">
                                <span class="label">Entreprise :</span>
                                <span class="value" id="devis-entreprise">--</span>
                            </div>
                            <div class="devis-info">
                                <span class="label">Commune :</span>
                                <span class="value" id="devis-commune">--</span>
                            </div>
                            <div class="devis-info">
                                <span class="label">Consommation prévisionnelle :</span>
                                <span class="value" id="devis-consommation">--</span>
                            </div>
                            <div class="devis-info">
                                <span class="label">Type de gaz :</span>
                                <span class="value" id="devis-type-gaz">--</span>
                            </div>
                        </div>
                        
                        <p class="devis-contact">Un conseiller commercial prendra contact avec vous dans les <strong>48 heures ouvrées</strong> pour analyser vos besoins et vous proposer une offre sur mesure.</p>
                    </div>
                </div>
            </div>
            
            <!-- Actions après résultats -->
            <div class="results-actions" style="display: none;">

                    <!-- Bouton envoyer par mail -->
                    <button type="button" class="btn btn-primary" id="btn-send-email">
                        <span class="btn-icon">✉️</span>
                        Recevoir par email
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="location.reload()">
                        <span class="btn-icon">🔄</span>
                        Nouvelle simulation
                    </button>
                <!-- Message de confirmation (caché par défaut) -->
                <div class="confirmation-message" id="email-confirmation" style="display: none;">
                    <div class="success-icon">✅</div>
                    <p>Votre estimation a été envoyée avec succès à <strong id="email-display"></strong></p>
                </div>
                
                <div class="confirmation-message" id="callback-confirmation" style="display: none;">
                    <div class="success-icon">☎️</div>
                    <p>Votre demande de rappel a été enregistrée. Un conseiller vous contactera dans les plus brefs délais.</p>
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
                🔍 Calculer mon estimation
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