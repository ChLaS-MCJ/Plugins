<?php
/**
 * Template du formulaire Gaz Résidentiel - 8 étapes
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
                <span class="step-label">Résultats</span>
            </div>
            <div class="step" data-step="6">
                <span class="step-number">6</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="7">
                <span class="step-number">7</span>
                <span class="step-label">Récapitulatif</span>
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
        
        <!-- ÉTAPE 5: Résultats -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h2>📊 Vos résultats personnalisés</h2>
                <p>Estimation basée sur vos informations</p>
            </div>
            
            <!-- Container des résultats -->
            <div id="results-container">
                <!-- Les résultats seront injectés ici par JavaScript -->
            </div>
        </div>
        
        <!-- ÉTAPE 6: Informations client -->
        <div class="form-step" data-step="6">
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

        <div class="form-step" data-step="7">
            <div class="step-header">
                <h2>📋 Récapitulatif de votre simulation gaz</h2>
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
                🔍 Calculer ma consommation
            </button>
            
            <button type="button" id="btn-select-offer" class="btn btn-primary" style="display: none;">
                📋 Sélectionner cette offre
            </button>
            
            <button type="button" id="btn-send-simulation" class="btn btn-success" style="display: none;">
                📧 Envoyer ma simulation
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