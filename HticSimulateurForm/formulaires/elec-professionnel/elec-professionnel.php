<?php
/**
 * Template du formulaire Électricité Professionnel
 * Fichier: formulaires/elec-professionnel/elec-professionnel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données de configuration depuis l'admin
$config_data = get_option('htic_simulateur_elec_professionnel_data', array());
?>

<div class="htic-simulateur-wrapper" data-type="elec-professionnel">
    
    <!-- En-tête du simulateur -->
    <div class="simulateur-header pro-header">
        <div class="header-icon">⚡</div>
        <h1>Simulateur Électricité Professionnel</h1>
        <p>Trouvez le meilleur tarif électrique pour votre entreprise</p>
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
    <form id="simulateur-elec-professionnel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Configuration électrique -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>⚡ Configuration électrique professionnelle</h2>
                <p>Informations sur votre contrat électrique professionnel</p>
            </div>
            
            <div class="form-grid">
                <!-- Catégorie -->
                <div class="form-group full-width">
                    <label class="form-label">Catégorie de raccordement</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="categorie" value="BT < 36 kVA" checked required>
                            <div class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <strong>BT ≤ 36 kVA</strong>
                                    <span>Basse tension, petite puissance</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="categorie" value="BT > 36 kVA" required>
                            <div class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <strong>BT > 36 kVA</strong>
                                    <span>Basse tension, forte puissance</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="categorie" value="HTA" required>
                            <div class="radio-content">
                                <div class="radio-icon">⚡</div>
                                <div class="radio-text">
                                    <strong>HTA</strong>
                                    <span>Haute tension</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Éligibilité TRV -->
                <div class="form-group">
                    <label class="form-label">Êtes-vous éligible au tarif réglementé ?</label>
                    <div class="radio-group horizontal">
                        <label class="radio-card">
                            <input type="radio" name="eligible_trv" value="oui" checked required>
                            <div class="radio-content">
                                <div class="radio-icon">✅</div>
                                <div class="radio-text">
                                    <strong>Oui</strong>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="eligible_trv" value="non" required>
                            <div class="radio-content">
                                <div class="radio-icon">❌</div>
                                <div class="radio-text">
                                    <strong>Non</strong>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Puissance souscrite -->
                <div class="form-group">
                    <label for="puissance" class="form-label">Puissance souscrite</label>
                    <select id="puissance" name="puissance" required class="form-select">
                        <option value="">Choisir...</option>
                        <option value="3">3 kVA</option>
                        <option value="6" selected>6 kVA</option>
                        <option value="9">9 kVA</option>
                        <option value="12">12 kVA</option>
                        <option value="15">15 kVA</option>
                        <option value="18">18 kVA</option>
                        <option value="24">24 kVA</option>
                        <option value="30">30 kVA</option>
                        <option value="36">36 kVA</option>
                    </select>
                </div>
                
                <!-- Formule tarifaire -->
                <div class="form-group full-width">
                    <label class="form-label">Formule tarifaire souscrite</label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="formule_tarifaire" value="Base" checked required>
                            <div class="radio-content">
                                <div class="radio-icon">📊</div>
                                <div class="radio-text">
                                    <strong>Option Base</strong>
                                    <span>Tarif unique toute la journée</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="radio-card">
                            <input type="radio" name="formule_tarifaire" value="Heures creuses" required>
                            <div class="radio-content">
                                <div class="radio-icon">🌙</div>
                                <div class="radio-text">
                                    <strong>Option Heures Creuses</strong>
                                    <span>Tarif réduit 8h par jour</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Consommation annuelle -->
                <div class="form-group full-width">
                    <label for="conso_annuelle" class="form-label">Consommation prévisionnelle annuelle</label>
                    <div class="input-group">
                        <input type="number" 
                               id="conso_annuelle" 
                               name="conso_annuelle" 
                               min="1000" 
                               max="1000000" 
                               value="50000" 
                               required 
                               class="form-input">
                        <span class="input-suffix">kWh/an</span>
                    </div>
                    <small class="form-help">Saisie libre basée sur vos factures précédentes</small>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 2: Résultats -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos données professionnelles</p>
            </div>
            
            <div id="results-container-pro">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul en cours...</p>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 3: Sélection (adaptée d'elec-residentiel) -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>🎯 Finalisons votre choix professionnel</h2>
                <p>Sélectionnez le tarif et la puissance qui conviennent le mieux à votre entreprise</p>
            </div>
            
            <div class="form-content">
                
                <!-- Sélection du tarif -->
                <div class="field-group">
                    <label class="field-label required">
                        <span class="label-icon">💰</span>
                        Choisissez votre tarif professionnel
                    </label>
                    <div class="tarif-selection">
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_base_pro" 
                                name="tarif_choisi" 
                                value="base">
                            <label for="tarif_base_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Base TRV Pro</h4>
                                    <span class="tarif-badge simple">Simple</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Prix unique toute la journée</p>
                                    <small>Idéal pour une consommation régulière</small>
                                </div>
                                <div class="tarif-price" id="prix-base-pro">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_hc_pro" 
                                name="tarif_choisi" 
                                value="hc">
                            <label for="tarif_hc_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Heures Creuses TRV Pro</h4>
                                    <span class="tarif-badge economique">Économique</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Prix réduit 8h par jour</p>
                                    <small>Économies en décalant vos usages</small>
                                </div>
                                <div class="tarif-price" id="prix-hc-pro">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_tempo_pro" 
                                name="tarif_choisi" 
                                value="tempo">
                            <label for="tarif_tempo_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Tempo TRV Pro</h4>
                                    <span class="tarif-badge expert">Expert</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Tarif variable selon les jours</p>
                                    <small>Maximum d'économies avec contraintes</small>
                                </div>
                                <div class="tarif-price" id="prix-tempo-pro">
                                    <span class="price-amount">--</span>
                                    <span class="price-period">€/an</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="tarif-card-selection">
                            <input type="radio" 
                                id="tarif_francaise_pro" 
                                name="tarif_choisi" 
                                value="francaise">
                            <label for="tarif_francaise_pro" class="radio-label tarif-label">
                                <div class="tarif-header">
                                    <h4>Offre 100% Française</h4>
                                    <span class="tarif-badge verte">Verte</span>
                                </div>
                                <div class="tarif-description">
                                    <p>Énergie française et renouvelable</p>
                                    <small>Soutien à la production locale</small>
                                </div>
                                <div class="tarif-price" id="prix-francaise-pro">
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
                                id="contrat_principal" 
                                name="type_contrat" 
                                value="principal" 
                                checked>
                            <label for="contrat_principal" class="radio-label">
                                <div class="option-icon">🏢</div>
                                <div class="option-content">
                                    <h4>Contrat principal</h4>
                                    <p>Siège social, bureau principal</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-option">
                            <input type="radio" 
                                id="contrat_secondaire" 
                                name="type_contrat" 
                                value="secondaire">
                            <label for="contrat_secondaire" class="radio-label">
                                <div class="option-icon">🏪</div>
                                <div class="option-content">
                                    <h4>Site secondaire</h4>
                                    <p>Filiale, antenne, succursale</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ÉTAPE 4: Contact professionnel (adaptée d'elec-residentiel) -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>🏢 Informations entreprise et contact</h2>
                <p>Finalisez votre dossier professionnel</p>
            </div>
            
            <div class="form-content">
                
                <!-- Localisation -->
                <div class="modern-card">
                    <div class="card-header">
                        <div class="card-icon location">📍</div>
                        <div class="card-title">Localisation du site</div>
                    </div>
                    
                    <div class="card-body">
                        <div class="dual-input">
                            <div class="input-box">
                                <input type="text" 
                                    id="pdl_entreprise" 
                                    name="pdl_entreprise" 
                                    placeholder=" ">
                                <label for="pdl_entreprise">Point de livraison (PDL)</label>
                                <span class="input-hint">Format: BT4000100001</span>
                            </div>
                            
                            <div class="input-box">
                                <input type="text" 
                                    id="prm_entreprise" 
                                    name="prm_entreprise" 
                                    placeholder=" ">
                                <label for="prm_entreprise">N° PRM (14 chiffres)</label>
                                <span class="input-hint">Sur votre compteur Linky</span>
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
                                    J'accepte les <a href="#" target="_blank">conditions générales professionnelles</a> 
                                    et <a href="#" target="_blank">conditions particulières</a>
                                </span>
                            </label>
                            
                            <label class="check-item">
                                <input type="checkbox" 
                                    id="accept_prelevement_pro" 
                                    name="accept_prelevement_pro" 
                                    required>
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
        
        <!-- ÉTAPE 5: Récapitulatif final professionnel -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>📋 Récapitulatif de votre simulation professionnelle</h2>
                <p>Vérifiez toutes vos informations avant finalisation</p>
            </div>
            
            <div class="form-content">
                <!-- Container pour le récapitulatif généré dynamiquement -->
                <div id="recap-container-final-pro">
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
                            <h4>Finaliser le contrat professionnel</h4>
                            <p>Complétez votre souscription électrique professionnelle</p>
                        </div>
                        <button type="button" 
                                class="btn btn-primary btn-large" 
                                id="btn-finaliser-souscription-pro">
                            <span class="btn-icon">✅</span>
                            Finaliser ma souscription pro
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
    
    <script type="application/json" id="simulateur-config-pro">
        <?php echo json_encode($config_data, JSON_PRETTY_PRINT); ?>
    </script>
</div>