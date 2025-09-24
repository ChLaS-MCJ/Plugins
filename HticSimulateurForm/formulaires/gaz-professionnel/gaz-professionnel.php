<?php
/**
 * Template du formulaire Gaz Professionnel - Version 5 étapes
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
    <div class="simulateur-header pro-header">
        <div class="header-icon">🔥</div>
        <h1>Simulateur Gaz Professionnel</h1>
        <p>Trouvez la meilleure offre gaz pour votre entreprise</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="20"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Configuration</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Résultats</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Sélection</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="5">
                <span class="step-number">5</span>
                <span class="step-label">Récapitulatif</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-gaz-professionnel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Configuration gaz -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>🔥 Configuration gaz professionnelle</h2>
                <p>Informations sur votre consommation de gaz professionnel</p>
            </div>
            
            <div class="form-grid">
                    
                <!-- Commune -->
                <div class="form-group full-width">
                    <label for="commune" class="form-label">Commune de votre entreprise</label>
                    <div class="field-help">Sélectionnez votre commune pour déterminer le type de gaz disponible</div>
                    
                    <select id="commune" name="commune" required class="form-select">
                        <option value="">-- Sélectionnez votre commune --</option>
                        
                        <optgroup label="🌱 Communes Gaz Naturel" id="communes-naturel">
                            <!-- Sera rempli par JavaScript -->
                        </optgroup>
                        
                        <optgroup label="⛽ Communes Gaz Propane" id="communes-propane">
                            <!-- Sera rempli par JavaScript -->
                        </optgroup>
                    </select>
                    
                    <!-- Section conditionnelle pour "Autre commune" -->
                    <div id="autre-commune-details" class="conditional-section" style="display: none;">
                        <div class="form-subgroup">
                            <label for="nom_commune_autre" class="form-label">Nom de votre commune</label>
                            <input type="text" 
                                   id="nom_commune_autre" 
                                   name="nom_commune_autre" 
                                   placeholder="Saisissez le nom de votre commune"
                                   class="form-input">
                            
                            <label class="form-label">Type de gaz disponible</label>
                            <div class="radio-group">
                                <label class="radio-card">
                                    <input type="radio" 
                                           id="type_gaz_naturel_autre" 
                                           name="type_gaz_autre" 
                                           value="naturel" 
                                           checked>
                                    <div class="radio-content">
                                        <div class="radio-icon">🌱</div>
                                        <div class="radio-text">
                                            <div class="radio-title">Gaz naturel</div>
                                            <div class="radio-subtitle">Réseau GRDF</div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="radio-card">
                                    <input type="radio" 
                                           id="type_gaz_propane_autre" 
                                           name="type_gaz_autre" 
                                           value="propane">
                                    <div class="radio-content">
                                        <div class="radio-icon">⛽</div>
                                        <div class="radio-text">
                                            <div class="radio-title">Gaz propane</div>
                                            <div class="radio-subtitle">Citerne GPL</div>
                                        </div>
                                    </div>
                                </label>
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
                    <label for="consommation_previsionnelle" class="form-label">Consommation prévisionnelle annuelle</label>
                    <div class="input-group">
                        <input type="number" 
                               id="consommation_previsionnelle" 
                               name="consommation_previsionnelle" 
                               min="100" 
                               max="1000000" 
                               step="100"
                               value="15000"
                               placeholder="15000"
                               required
                               class="form-input">
                        <span class="input-suffix">kWh/an</span>
                    </div>
                    <div class="field-help">Indiquez votre consommation annuelle estimée en kWh</div>
                </div>
                
                <!-- Info box pour aide -->
                <div class="form-group full-width">
                    <div class="info-box">
                        <div class="info-icon">💡</div>
                        <div class="info-content">
                            <h4>Comment estimer votre consommation gaz ?</h4>
                            <p>• <strong>Petite entreprise/commerce :</strong> 5 000 à 15 000 kWh/an</p>
                            <p>• <strong>PME/Restaurant :</strong> 15 000 à 50 000 kWh/an</p>
                            <p>• <strong>Industrie/Hôtel :</strong> 50 000 à 200 000 kWh/an</p>
                            <p>• <strong>Grande industrie :</strong> > 200 000 kWh/an</p>
                            <p><em>Vous pouvez retrouver votre consommation sur vos dernières factures gaz.</em></p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 2: Résultats -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>📊 Vos résultats gaz personnalisés</h2>
                <p>Estimation basée sur votre consommation prévisionnelle</p>
            </div>
            
            <div id="results-container">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul en cours...</p>
                </div>
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
        </div>

        <!-- ÉTAPE 3: Sélection (adaptée d'elec-pro) -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>🎯 Finalisons votre choix gaz professionnel</h2>
                <p>Sélectionnez le tarif et le type qui conviennent le mieux à votre entreprise</p>
            </div>
            
            <div class="form-content">
                
                <!-- Sélection du tarif gaz -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">🔥</span>
                        Choisissez votre tarif gaz professionnel
                    </label>
                    <div class="tarif-selection">
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_naturel_pro" 
                                name="tarif_choisi" 
                                value="naturel">
                            <label for="tarif_naturel_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Gaz Naturel Pro</h4>
                                    <span class="tarif-badge naturel">Réseau</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Raccordé au réseau GRDF</p>
                                    <small>Idéal pour zones urbaines et péri-urbaines</small>
                                </div>
                                <div class="tarif-price" id="prix-naturel-pro">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_propane_pro" 
                                name="tarif_choisi" 
                                value="propane">
                            <label for="tarif_propane_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Gaz Propane Pro</h4>
                                    <span class="tarif-badge propane">Citerne</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Citerne GPL sur site</p>
                                    <small>Solution pour zones non desservies</small>
                                </div>
                                <div class="tarif-price" id="prix-propane-pro">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Type de contrat -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">🏢</span>
                        Type de contrat
                    </label>
                    <div class="radio-grid">
                        <div class="radio-option">
                            <input type="radio" 
                                id="contrat_principal_gaz" 
                                name="type_contrat" 
                                value="principal" 
                                checked>
                            <label for="contrat_principal_gaz" class="radio-label">
                                <div class="option-icon">🏢</div>
                                <div class="option-content">
                                    <h4>Contrat principal</h4>
                                    <p>Siège social, bureau principal</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-option">
                            <input type="radio" 
                                id="contrat_secondaire_gaz" 
                                name="type_contrat" 
                                value="secondaire">
                            <label for="contrat_secondaire_gaz" class="radio-label">
                                <div class="option-icon">🏪</div>
                                <div class="option-content">
                                    <h4>Site secondaire</h4>
                                    <p>Filiale, antenne, succursale</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Calculs de sélection -->
                <div id="calculs-selection-pro"></div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 4: Contact professionnel (similaire à elec-pro) -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>🏢 Informations entreprise et contact</h2>
                <p>Finalisez votre dossier gaz professionnel</p>
            </div>
            
            <div class="form-content">
                
                <!-- Localisation -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon location">📍</div>
                        <div class="card-title">Localisation du site gaz</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="point_livraison_gaz" 
                                    name="point_livraison_gaz" 
                                    placeholder=" ">
                                <label for="point_livraison_gaz">Point de livraison gaz (PCE)</label>
                                <span class="input-hint">Format: GI12345678901234</span>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="num_compteur_gaz" 
                                    name="num_compteur_gaz" 
                                    placeholder=" ">
                                <label for="num_compteur_gaz">N° de compteur gaz</label>
                                <span class="input-hint">Sur votre compteur gaz</span>
                            </div>
                        </div>
                        
                        <button type="button" class="toggle-btn" id="btn-no-info-pro">
                            <span class="toggle-text">Je n'ai pas ces informations</span>
                            <span class="toggle-icon">+</span>
                        </button>
                        
                        <div class="collapsible-section" id="address-section-pro">
                            <div class="dual-input">
                                <div class="input-box flex-2">
                                    <input type="text" 
                                        id="entreprise_adresse" 
                                        name="entreprise_adresse" 
                                        placeholder=" " 
                                        required>
                                    <label for="entreprise_adresse">Adresse complète *</label>
                                </div>
                                
                                <div class="input-box">
                                    <input type="text" 
                                        id="entreprise_code_postal" 
                                        name="entreprise_code_postal" 
                                        placeholder=" " 
                                        maxlength="5" 
                                        required>
                                    <label for="entreprise_code_postal">Code postal *</label>
                                </div>
                            </div>
                            
                            <div class="dual-input">
                                <div class="input-box">
                                    <input type="text" 
                                        id="entreprise_complement" 
                                        name="entreprise_complement" 
                                        placeholder=" ">
                                    <label for="entreprise_complement">Complément (optionnel)</label>
                                </div>
                                
                                <div class="input-box">
                                    <input type="text" 
                                        id="entreprise_ville" 
                                        name="entreprise_ville" 
                                        placeholder=" " 
                                        required>
                                    <label for="entreprise_ville">Ville *</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations entreprise -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon profile">🏢</div>
                        <div class="card-title">Informations légales</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="raison_sociale" 
                                    name="raison_sociale" 
                                    placeholder=" " 
                                    required>
                                <label for="raison_sociale">Raison Sociale *</label>
                            </div>
                            
                            <div class="input-box">
                                <select id="forme_juridique" 
                                        name="forme_juridique" 
                                        required>
                                    <option value="">Choisir...</option>
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
                                <label for="forme_juridique">Forme Juridique *</label>
                            </div>
                        </div>
                        
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="siret" 
                                    name="siret" 
                                    placeholder=" " 
                                    pattern="[0-9]{14}" 
                                    maxlength="14" 
                                    required>
                                <label for="siret">Numéro SIRET *</label>
                                <span class="input-hint">14 chiffres sans espaces</span>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="code_naf" 
                                    name="code_naf" 
                                    placeholder=" " 
                                    pattern="[0-9]{4}[A-Z]" 
                                    maxlength="5" 
                                    required>
                                <label for="code_naf">Code NAF/APE *</label>
                                <span class="input-hint">4 chiffres + 1 lettre</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact responsable -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon profile">👤</div>
                        <div class="card-title">Responsable du contrat</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="responsable_nom" 
                                    name="responsable_nom" 
                                    placeholder=" " 
                                    required>
                                <label for="responsable_nom">Nom *</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="responsable_prenom" 
                                    name="responsable_prenom" 
                                    placeholder=" " 
                                    required>
                                <label for="responsable_prenom">Prénom *</label>
                            </div>
                        </div>
                        
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="email" 
                                    id="responsable_email" 
                                    name="responsable_email" 
                                    placeholder=" " 
                                    required>
                                <label for="responsable_email">Email professionnel *</label>
                            </div>
                            
                            <div class="input-box">
                                <input type="tel" 
                                    id="responsable_telephone" 
                                    name="responsable_telephone" 
                                    placeholder=" " 
                                    required>
                                <label for="responsable_telephone">Téléphone *</label>
                            </div>
                        </div>
                        
                        <div class="input-box">
                            <input type="text" 
                                id="responsable_fonction" 
                                name="responsable_fonction" 
                                placeholder=" ">
                            <label for="responsable_fonction">Fonction dans l'entreprise</label>
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
                            <div class="upload-card" data-file="kbis_file">
                                <div class="upload-visual">
                                    <div class="upload-type kbis">🏢</div>
                                    <h4>Extrait K-bis</h4>
                                    <p>Moins de 3 mois</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">PDF, JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="kbis_file" 
                                    name="kbis_file" 
                                    accept=".pdf,.jpg,.jpeg,.png" 
                                    required>
                                <div class="upload-result" id="kbis-status"></div>
                            </div>
                            
                            <div class="upload-card" data-file="rib_entreprise">
                                <div class="upload-visual">
                                    <div class="upload-type rib">🏦</div>
                                    <h4>RIB Entreprise</h4>
                                    <p>Relevé d'Identité Bancaire</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">PDF, JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="rib_entreprise" 
                                    name="rib_entreprise" 
                                    accept=".pdf,.jpg,.jpeg,.png" 
                                    required>
                                <div class="upload-result" id="rib-entreprise-status"></div>
                            </div>
                            
                            <div class="upload-card" data-file="mandat_signature">
                                <div class="upload-visual">
                                    <div class="upload-type mandat">✍️</div>
                                    <h4>Mandat de signature</h4>
                                    <p>Pouvoir du signataire</p>
                                </div>
                                <div class="upload-action">
                                    <button type="button" class="upload-trigger">Parcourir</button>
                                    <span class="upload-info">PDF, JPG, PNG - 5Mo max</span>
                                </div>
                                <input type="file" 
                                    id="mandat_signature" 
                                    name="mandat_signature" 
                                    accept=".pdf,.jpg,.jpeg,.png">
                                <div class="upload-result" id="mandat-status"></div>
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
                                    id="accept_conditions_pro" 
                                    name="accept_conditions_pro" 
                                    required>
                                <span class="checkmark"></span>
                                <span class="check-text">
                                    J'accepte les <a href="#" target="_blank">conditions générales gaz professionnelles</a> 
                                    et <a href="#" target="_blank">conditions particulières</a>
                                </span>
                            </label>
                            
                            <label class="check-item">
                                <input type="checkbox" 
                                    id="accept_prelevement_pro" 
                                    name="accept_prelevement_pro">
                                <span class="checkmark"></span>
                                <span class="check-text">
                                    J'autorise le prélèvement automatique pour l'entreprise
                                </span>
                            </label>
                            
                            <label class="check-item">
                                <input type="checkbox" 
                                    id="certifie_pouvoir" 
                                    name="certifie_pouvoir" 
                                    required>
                                <span class="checkmark"></span>
                                <span class="check-text">
                                    Je certifie avoir le pouvoir d'engager l'entreprise
                                </span>
                            </label>
                            
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 5: Récapitulatif final gaz professionnel -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>📋 Récapitulatif de votre simulation gaz professionnelle</h2>
                <p>Vérifiez toutes vos informations avant finalisation</p>
            </div>
            
            <div class="form-content">
                <!-- Container pour le récapitulatif généré dynamiquement -->
                <div id="recap-container-final-pro">
                    <div class="loading-recap">
                        <div class="spinner"></div>
                        <p>Génération du récapitulatif gaz complet...</p>
                    </div>
                </div>
                
                <!-- Actions finales -->
                <div class="final-actions">
                    <div class="action-card highlight">
                        <div class="action-icon">🔥</div>
                        <div class="action-content">
                            <h4>Finaliser le contrat gaz professionnel</h4>
                            <p>Complétez votre souscription gaz professionnelle</p>
                        </div>
                        <button type="button" 
                                class="btn btn-primary btn-large" 
                                id="btn-finaliser-souscription-pro">
                            <span class="btn-icon">✅</span>
                            Finaliser ma souscription gaz pro
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="form-navigation">
            <button type="button" id="btn-previous-pro" class="btn btn-secondary" style="display: none;">
                ← Précédent
            </button>
            
            <div class="nav-spacer"></div>
            
            <button type="button" id="btn-next-pro" class="btn btn-primary">
                Suivant →
            </button>
            
            <button type="button" id="btn-calculate-pro" class="btn btn-success" style="display: none;">
                🔍 Calculer
            </button>
            
            <button type="button" id="btn-restart-pro" class="btn btn-outline" style="display: none;">
                🔄 Nouvelle simulation
            </button>
        </div>
    </form>
    
    <script type="application/json" id="simulateur-config">
        <?php echo json_encode($config_data, JSON_PRETTY_PRINT); ?>
    </script>
</div>