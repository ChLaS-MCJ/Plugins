<?php
/**
 * Template du formulaire de contact multi-étapes
 * Fichier: formulaires/contact/contact-form.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Types de demandes avec configuration
$types_demandes = array(
    'releve_index' => array(
        'label' => 'Relevé d\'index',
        'description' => 'Transmission de votre relevé de compteur',
        'upload' => true,
        'upload_label' => 'Photo du compteur',
        'upload_required' => true,
        'champ_libre' => true,
        'champ_libre_label' => 'Informations complémentaires'
    ),
    'changement_rib' => array(
        'label' => 'Changement de RIB',
        'description' => 'Modification de vos coordonnées bancaires',
        'upload' => true,
        'upload_label' => 'Nouveau RIB',
        'upload_required' => true,
        'champ_libre' => true,
        'champ_libre_label' => 'Informations complémentaires'
    ),
    'resiliation_contrat' => array(
        'label' => 'Résiliation de contrat',
        'description' => 'Demande de résiliation de votre contrat',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Motif de résiliation et informations'
    ),
    'modification_contrat' => array(
        'label' => 'Modification de contrat',
        'description' => 'Demande de modification de votre contrat',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Modifications souhaitées'
    ),
    'depannage_urgent' => array(
        'label' => 'Dépannage urgent',
        'description' => 'Intervention urgente nécessaire',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Description du problème'
    ),
    'mise_aux_normes' => array(
        'label' => 'Mise aux normes',
        'description' => 'Demande de mise aux normes électriques',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Détails de la demande'
    ),
    'renovation_electrique' => array(
        'label' => 'Rénovation électrique',
        'description' => 'Projet de rénovation électrique',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Description du projet'
    ),
    'maintenance_preventive' => array(
        'label' => 'Maintenance préventive',
        'description' => 'Demande de maintenance préventive',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Éléments à vérifier'
    ),
    'raccordement' => array(
        'label' => 'Raccordement',
        'description' => 'Demande de nouveau raccordement',
        'upload' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Détails du raccordement'
    ),
    'autre' => array(
        'label' => 'Autre',
        'description' => 'Autre demande',
        'upload' => true,
        'upload_label' => 'Fichier joint (optionnel)',
        'upload_required' => false,
        'champ_libre' => true,
        'champ_libre_label' => 'Votre demande'
    )
);
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
                <span class="step-label">Informations</span>
            </div>
            <div class="step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Demande</span>
            </div>
            <div class="step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Confirmation</span>
            </div>
        </div>
    </div>

    <!-- Formulaire multi-étapes -->
    <form id="contact-form" class="contact-form" enctype="multipart/form-data">
        
        <!-- ÉTAPE 1 : Informations personnelles -->
        <div class="form-step active" data-step="1">
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
                    <label for="adresse" class="form-label required">Adresse</label>
                    <input type="text" id="adresse" name="adresse" class="form-control" required placeholder="Numéro et nom de rue">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="complement_adresse" class="form-label">Complément d'adresse</label>
                        <input type="text" id="complement_adresse" name="complement_adresse" class="form-control" placeholder="Bâtiment, étage, etc.">
                        <span class="error-message"></span>
                    </div>
                    
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
                <button type="button" class="btn btn-primary btn-next" data-next="2">
                    Suivant
                    <span class="btn-icon">→</span>
                </button>
            </div>
        </div>

        <!-- ÉTAPE 2 : Type de demande -->
        <div class="form-step" data-step="2">
            <div class="step-content">
                <h3 class="step-title">
                    <span class="step-icon">📋</span>
                    Votre demande
                </h3>
                
                <div class="form-group">
                    <label for="type_demande" class="form-label required">Type de demande</label>
                    <select id="type_demande" name="type_demande" class="form-control" required>
                        <option value="">Sélectionnez le type de votre demande...</option>
                        <?php foreach ($types_demandes as $key => $type): ?>
                            <option value="<?php echo esc_attr($key); ?>" 
                                    data-config="<?php echo esc_attr(json_encode($type)); ?>">
                                <?php echo esc_html($type['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message"></span>
                </div>
                
                <div id="demande-description" class="demande-description" style="display: none;">
                    <p class="description-text"></p>
                </div>
                
                <!-- Zone d'upload conditionnel -->
                <div id="upload-zone" class="upload-zone" style="display: none;">
                    <label class="upload-label"><span id="upload-label-text">Fichier joint</span></label>
                    <div class="upload-area" id="upload-area">
                        <input type="file" id="file-input" name="fichier" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="upload-content">
                            <span class="upload-icon">📎</span>
                            <p class="upload-text">
                                Cliquez pour sélectionner un fichier<br>
                                <small>ou glissez-déposez votre fichier ici</small>
                            </p>
                            <p class="upload-info">
                                Formats acceptés : JPG, PNG, PDF, DOC, DOCX (max. 10 Mo)
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
                
                <!-- Champ libre conditionnel -->
                <div id="champ-libre-zone" class="form-group" style="display: none;">
                    <label for="message" class="form-label" id="champ-libre-label">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" placeholder="Décrivez votre demande..."></textarea>
                    <span class="error-message"></span>
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

        <!-- ÉTAPE 3 : Confirmation -->
        <div class="form-step" data-step="3">
            <div class="step-content">
                <h3 class="step-title">
                    Confirmation de votre demande
                </h3>
                
                <div class="summary-card">
                    <h4>Récapitulatif de votre demande</h4>
                    <div id="form-summary"></div>
                </div>
                
                <!-- Anti-spam -->
                <div class="form-group">
                    <div class="captcha-container">
                        <label class="captcha-label">
                            Êtes-vous un humain ? Cochez cette case :
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" id="captcha-check" name="captcha" required>
                            <span class="checkmark"></span>
                            Je ne suis pas un robot
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="rgpd-consent" name="rgpd_consent" required>
                        <span class="checkmark"></span>
                        <p class="checkbox-containertexte">J'accepte que mes données soient utilisées pour traiter ma demande 
                        conformément à la <a href="/mentions-legales" target="_blank">politique de confidentialité</a></p>
                    </label>
                    <span class="error-message"></span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                    <span class="btn-icon">←</span>
                    Précédent
                </button>
                <button type="submit" class="btn btn-success btn-submit">
                    <span class="submit-icon">📨</span>
                    Envoyer ma demande
                </button>
            </div>
        </div>
        
        <!-- Messages de feedback -->
        <div id="form-messages" class="form-messages"></div>
        
        <!-- Champs cachés -->
        <input type="hidden" name="action" value="htic_contact_submit">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('htic_contact_nonce'); ?>">
        <input type="hidden" name="uploaded_file" id="uploaded-file" value="">
        
    </form>
    
    <!-- Configuration JSON pour JavaScript -->
    <script type="application/json" id="contact-config">
        <?php echo json_encode($types_demandes); ?>
    </script>
</div>