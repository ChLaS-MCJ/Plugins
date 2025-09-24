<?php
/**
 * Template du formulaire de contact avec étape statut client
 * Fichier: formulaires/contact/contact-form.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="htic-contact-form" id="htic-contact-form">
    <!-- Barre de progression -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Statut client</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Informations</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Votre demande</span>
            </div>
            <div class="step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Confirmation</span>
            </div>
        </div>
    </div>

    <!-- Formulaire multi-étapes -->
    <form id="contact-form" class="contact-form" enctype="multipart/form-data">
        
        <!-- ÉTAPE 1 : Statut client -->
        <div class="form-step active" data-step="1">
            <div class="step-content">
                <h3 class="step-title">
                    <span class="step-icon">🏠</span>
                    Êtes-vous déjà client GES ?
                </h3>
                
                <div class="status-choice-container">
                    <div class="status-choice">
                        <label class="status-radio-card">
                            <input type="radio" name="client_status" value="not-client">
                            <div class="status-radio-content">
                                <div class="status-radio-icon">✉️</div>
                                <div class="status-radio-text">
                                    <h4>Je ne suis pas client</h4>
                                    <p>Je souhaite obtenir des informations ou faire une demande</p>
                                </div>
                                <span class="status-radio-check"></span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="status-choice">
                        <label class="status-radio-card">
                            <input type="radio" name="client_status" value="is-client">
                            <div class="status-radio-content">
                                <div class="status-radio-icon">👤</div>
                                <div class="status-radio-text">
                                    <h4>Je suis client</h4>
                                    <p>J'ai déjà un contrat chez GES</p>
                                </div>
                                <span class="status-radio-check"></span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Bloc d'information pour création de compte (toujours visible) -->
                <div class="modern-info-bubble">
                    <div class="info-bubble-content">
                        <div class="info-bubble-header">
                            <div class="info-bubble-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
                                    <circle cx="12" cy="20" r="2" fill="currentColor"/>
                                </svg>
                            </div>
                            <h4 class="info-bubble-title">Espace client en ligne</h4>
                        </div>
                        <div class="info-bubble-body">
                            <p class="info-bubble-description">
                                <strong>Gérez facilement vos contrats d'énergie :</strong><br>
                                Suivi de consommation, factures, paiements, messagerie directe...
                            </p>
                            <button type="button" class="info-bubble-cta" id="show-general-account-info">
                                <span class="cta-text">Guide de création</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="info-bubble-bg-decoration"></div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-primary btn-next" id="btn-next-status" data-next="2" disabled>
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>

        <!-- ÉTAPE 2 : Informations personnelles (ancienne étape 1) -->
        <div class="form-step" data-step="2">
            <div class="step-content">
                <h3 class="step-title">
                    <span class="step-icon">👤</span>
                    Vos informations personnelles
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="civilite" class="form-label required">Civilité</label>
                        <select id="civilite" name="civilite" class="form-control" required>
                            <option value="">Sélectionnez...</option>
                            <option value="M.">Monsieur</option>
                            <option value="Mme">Madame</option>
                        </select>
                        <span class="error-message"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="nom" class="form-label required">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control" required>
                        <span class="error-message"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom" class="form-label required">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" required>
                        <span class="error-message"></span>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                        <span class="error-message"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone" class="form-label required">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" required>
                        <span class="error-message"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="adresse" class="form-label">Adresse</label>
                    <input type="text" id="adresse" name="adresse" class="form-control" placeholder="Numéro et nom de rue">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="code_postal" class="form-label required">Code postal</label>
                        <input type="text" id="code_postal" name="code_postal" class="form-control" required placeholder="40000" pattern="[0-9]{5}">
                        <span class="error-message"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville" class="form-label required">Ville</label>
                        <input type="text" id="ville" name="ville" class="form-control" required placeholder="Votre ville">
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev" data-prev="1">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-next" data-next="3">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>

        <!-- ÉTAPE 3 : Demande (ancienne étape 2) -->
        <div class="form-step" data-step="3">
            <div class="step-content">
                <h3 class="step-title">
                    <span class="step-icon">📋</span>
                    Votre demande
                </h3>
                
                <div class="form-group">
                    <label for="objet_demande" class="form-label required">Objet de votre demande</label>
                    <input type="text" id="objet_demande" name="objet_demande" class="form-control" required 
                           placeholder="Ex: Demande d'information, Résiliation, Modification contrat...">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label required">Votre message</label>
                    <textarea id="message" name="message" class="form-control" rows="8" required
                              placeholder="Décrivez votre demande en détail..."></textarea>
                    <span class="error-message"></span>
                    <small class="form-text">Soyez le plus précis possible pour que nous puissions traiter votre demande rapidement.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pièce jointe (optionnel)</label>
                    <div class="upload-area" id="upload-area">
                        <input type="file" id="file-input" name="fichier" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="upload-content">
                            <span class="upload-icon">📎</span>
                            <p class="upload-text">
                                Cliquez pour sélectionner un fichier<br>
                                <small>ou glissez-déposez votre fichier ici</small>
                            </p>
                            <p class="upload-info">
                                Formats acceptés : JPG, PNG, PDF, DOC, DOCX (max. 5 Mo)
                            </p>
                        </div>
                        <div class="upload-progress" style="display: none;">
                            <div class="upload-progress-bar">
                                <div class="upload-progress-fill"></div>
                            </div>
                            <p class="upload-progress-text">Upload en cours...</p>
                        </div>
                        <div class="upload-success" style="display: none;">
                            <span class="upload-success-icon">✅</span>
                            <p class="upload-success-text">Fichier uploadé avec succès</p>
                            <button type="button" class="btn-remove-file">Supprimer</button>
                        </div>
                    </div>
                    <span class="error-message"></span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="button" class="btn btn-primary btn-next" data-next="4">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>

        <!-- ÉTAPE 4 : Confirmation (ancienne étape 3) -->
        <div class="form-step" data-step="4">
            <div class="step-content">
                <h3 class="step-title">
                    <span class="step-icon">✅</span>
                    Confirmation de votre demande
                </h3>
                
                <div class="summary-card">
                    <h4>Récapitulatif de votre demande</h4>
                    <div id="form-summary"></div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-container" style="color:black;">
                        <input type="checkbox" id="captcha-check" name="captcha" required>
                        <span class="checkmark"></span>
                        Je ne suis pas un robot
                    </label>
                    <span class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-container" style="color:black;">
                        <input type="checkbox" id="rgpd-consent" name="rgpd_consent" required>
                        <span class="checkmark"></span>
                        <span class="checkbox-text">J'accepte que mes données soient utilisées pour traiter ma demande 
                        conformément à la <a href="/mentions-legales" target="_blank">politique de confidentialité</a></span>
                    </label>
                    <span class="error-message"></span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev" data-prev="3">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button id="btn-submit-contact" class="btn btn-success btn-submit" type="button">
                    <span class="submit-icon">📨</span>
                    Envoyer ma demande
                </button>
            </div>
        </div>
        
        <!-- Messages de feedback -->
        <div id="form-messages" class="form-messages"></div>
        
        <!-- Champs cachés -->
        <input type="hidden" name="action" value="htic_process_contact">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('htic_simulateur_calculate'); ?>">
    </form>
</div>

<!-- Modal pour création de compte -->
<div class="modal-overlay" id="account-modal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h2>Créez votre compte Agence en Ligne</h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="ael-info">
                <h3>Pour toute modification ou information concernant vos contrats</h3>
                <p>Avec votre espace en ligne, consultez et gérez vos contrats d'énergie de manière simple et rapide :</p>
                <ul>
                    <li>Suivez votre consommation</li>
                    <li>Recherchez et téléchargez vos factures</li>
                    <li>Payez vos factures ou vos échéances en cas de plan de règlement</li>
                    <li>Contactez-nous directement via la messagerie pour toutes vos demandes</li>
                    <li>Modifiez vos informations personnelles ou coordonnées bancaires</li>
                </ul>
            </div>
            
            <div class="account-steps">
                <h3>Comment créer mon compte ?</h3>
                <p>Munissez-vous de votre contrat ou de votre facture et suivez ces étapes :</p>
                
                <ol class="creation-steps">
                    <li>Rendez-vous sur notre site internet <a href="https://www.gascogne-energies-services.com/" target="_blank">https://www.gascogne-energies-services.com/</a></li>
                    <li>Cliquez sur « AEL » en haut à droite de la page puis sur « créer un compte »</li>
                </ol>
                
                <div class="account-modes">
                    <div class="mode-card">
                        <div class="mode-icon">📱</div>
                        <h4>Mode d'inscription</h4>
                        <div class="mode-options">
                            <strong>⚠️ Important</strong>
                            <p>Si vous choisissez de créer votre compte avec votre numéro de facture, <strong>saisissez le numéro GL+ les 10 chiffres</strong></p>
                        </div>
                    </div>
                </div>
                
                <ol class="creation-steps" start="3">
                    <li>Saisissez un identifiant, renseignez votre adresse mail, votre n° de téléphone, et enfin choisissez un mot de passe.</li>
                    <li>Validez, votre compte est créé.</li>
                    <li>Vous recevrez un code de validation par mail afin d'activer votre compte (si vous ne l'avez pas reçu, vérifiez vos spams)</li>
                </ol>
                
                <div class="success-message">
                    <h3>✅ Et voilà, c'est terminé !</h3>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Fermer</button>
            <a href="https://www.gascogne-energies-services.com/ael" target="_blank" class="btn btn-primary">
                Créer mon compte maintenant
            </a>
        </div>
    </div>
</div>

<!-- Modal pour clients existants -->
<div class="modal-overlay" id="client-redirect-modal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h2>Vous êtes déjà client GES</h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="redirect-info">
                <h3>Pour toute modification ou information sur vos contrats</h3>
                <p class="redirect-message">
                    Nous vous recommandons d'utiliser votre <strong>Agence en Ligne (AEL)</strong> qui vous donne accès à :
                </p>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">📊</div>
                        <span>Suivi de consommation</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📄</div>
                        <span>Téléchargement factures</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">💳</div>
                        <span>Paiement en ligne</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">✉️</div>
                        <span>Messagerie directe</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">⚙️</div>
                        <span>Modification coordonnées</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📋</div>
                        <span>Gestion contrats</span>
                    </div>
                </div>
                
                <div class="redirect-choice">
                    <h4>Que souhaitez-vous faire ?</h4>
                    <div class="choice-buttons">
                        <a href="https://www.gascogne-energies-services.com/ael" target="_blank" class="btn btn-primary btn-large">
                            <span class="btn-icon">🌐</span>
                            Aller sur l'AEL
                        </a>
                        <button type="button" class="btn btn-secondary btn-large" id="continue-form-anyway">
                            <span class="btn-icon">📝</span>
                            Continuer le formulaire
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <p class="footer-note">L'Agence en Ligne est l'outil le plus rapide pour gérer vos contrats</p>
        </div>
    </div>
</div>