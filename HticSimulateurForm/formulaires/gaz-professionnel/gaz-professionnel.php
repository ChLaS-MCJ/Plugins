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
        <div class="header-icon">🏢</div>
        <h1>Simulateur Gaz Professionnel</h1>
        <p>Estimez votre budget énergétique pour votre entreprise</p>
    </div>
    
    <!-- Indicateur de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" data-progress="33"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Localisation</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Contact</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Résultats</span>
            </div>
        </div>
    </div>
    
    <!-- Formulaire principal -->
    <form id="simulateur-gaz-professionnel" class="simulateur-form">
        
        <!-- ÉTAPE 1: Localisation et Consommation -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>📍 Localisation et consommation de votre entreprise</h2>
                <p>Indiquez votre commune et votre consommation annuelle prévisionnelle</p>
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
                            
                        </select>
                        
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
        
        <!-- ÉTAPE 2: Informations entreprise -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>📧 Coordonnées de votre entreprise</h2>
                <p>Pour recevoir votre simulation personnalisée et être recontacté</p>
            </div>
            
            <div class="form-grid">
                <!-- Nom entreprise -->
                <div class="form-group full-width">
                    <label for="entreprise_nom" class="form-label">Nom de l'entreprise *</label>
                    <input type="text" 
                        id="entreprise_nom" 
                        name="entreprise_nom" 
                        required 
                        class="form-input"
                        placeholder="Société ABC">
                </div>
                
                <!-- SIRET -->
                <div class="form-group">
                    <label for="entreprise_siret" class="form-label">N° SIRET</label>
                    <input type="text" 
                        id="entreprise_siret" 
                        name="entreprise_siret" 
                        pattern="[0-9]{14}"
                        maxlength="14"
                        class="form-input"
                        placeholder="12345678901234">
                    <small class="form-help">14 chiffres</small>
                </div>
                
                <!-- Secteur activité -->
                <div class="form-group">
                    <label for="entreprise_secteur" class="form-label">Secteur d'activité *</label>
                    <select id="entreprise_secteur" name="entreprise_secteur" required class="form-input">
                        <option value="">-- Sélectionnez --</option>
                        <option value="commerce">Commerce</option>
                        <option value="restaurant">Restaurant/Hôtellerie</option>
                        <option value="industrie">Industrie</option>
                        <option value="bureau">Bureaux/Services</option>
                        <option value="sante">Santé</option>
                        <option value="education">Éducation</option>
                        <option value="agriculture">Agriculture</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                
                <!-- Contact -->
                <div class="form-group">
                    <label for="contact_nom" class="form-label">Nom du contact *</label>
                    <input type="text" 
                        id="contact_nom" 
                        name="contact_nom" 
                        required 
                        class="form-input"
                        placeholder="Votre nom">
                </div>
                
                <div class="form-group">
                    <label for="contact_prenom" class="form-label">Prénom du contact *</label>
                    <input type="text" 
                        id="contact_prenom" 
                        name="contact_prenom" 
                        required 
                        class="form-input"
                        placeholder="Votre prénom">
                </div>
                
                <div class="form-group">
                    <label for="contact_fonction" class="form-label">Fonction</label>
                    <input type="text" 
                        id="contact_fonction" 
                        name="contact_fonction" 
                        class="form-input"
                        placeholder="Directeur, Gérant, Responsable énergie...">
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="contact_email" class="form-label">Email professionnel *</label>
                    <input type="email" 
                        id="contact_email" 
                        name="contact_email" 
                        required 
                        class="form-input"
                        placeholder="contact@entreprise.fr">
                    <small class="form-help">Pour recevoir votre simulation</small>
                </div>
                
                <!-- Téléphone -->
                <div class="form-group">
                    <label for="contact_telephone" class="form-label">Téléphone *</label>
                    <input type="tel" 
                        id="contact_telephone" 
                        name="contact_telephone" 
                        required 
                        class="form-input"
                        placeholder="05 XX XX XX XX">
                    <small class="form-help">Pour être recontacté</small>
                </div>
                
                <!-- Adresse -->
                <div class="form-group full-width">
                    <label for="entreprise_adresse" class="form-label">Adresse de l'entreprise</label>
                    <input type="text" 
                        id="entreprise_adresse" 
                        name="entreprise_adresse" 
                        class="form-input"
                        placeholder="Numéro et nom de rue">
                </div>
                
                <!-- Code postal et Ville -->
                <div class="form-group">
                    <label for="entreprise_code_postal" class="form-label">Code postal</label>
                    <input type="text" 
                        id="entreprise_code_postal" 
                        name="entreprise_code_postal" 
                        pattern="[0-9]{5}"
                        maxlength="5"
                        class="form-input"
                        placeholder="40000">
                </div>
                
                <div class="form-group">
                    <label for="entreprise_ville" class="form-label">Ville</label>
                    <input type="text" 
                        id="entreprise_ville" 
                        name="entreprise_ville" 
                        class="form-input"
                        placeholder="Votre ville">
                </div>
                
                <!-- Meilleur moment pour contact -->
                <div class="form-group full-width">
                    <label for="contact_horaire" class="form-label">Meilleur moment pour vous contacter</label>
                    <select id="contact_horaire" name="contact_horaire" class="form-input">
                        <option value="">-- Indifférent --</option>
                        <option value="matin">Matin (9h-12h)</option>
                        <option value="apres-midi">Après-midi (14h-18h)</option>
                        <option value="fin-journee">Fin de journée (17h-19h)</option>
                    </select>
                </div>
                
                <!-- Information RGPD -->
                <div class="form-group full-width">
                    <div class="info-box">
                        <div class="info-icon">🔒</div>
                        <div class="info-content">
                            <h4>Vos données sont protégées</h4>
                            <p><strong>Utilisation professionnelle :</strong> Vos données sont utilisées uniquement pour établir votre devis personnalisé.</p>
                            <p><strong>Confidentialité :</strong> Nous respectons le RGPD et vos informations ne sont jamais vendues à des tiers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 3: Résultat -->
        <div class="form-step" data-step="3">
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
                <div class="actions-grid">
                    <!-- Bouton envoyer par mail -->
                    <button type="button" class="btn btn-primary" id="btn-send-email">
                        <span class="btn-icon">✉️</span>
                        Recevoir par email
                    </button>

                </div>
                
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