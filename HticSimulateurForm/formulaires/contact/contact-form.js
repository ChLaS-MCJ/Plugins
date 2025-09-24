/**
 * JavaScript modifié pour le formulaire de contact avec étape statut client
 * Fichier: formulaires/contact/contact-form.js
 */

(function ($) {
    'use strict';

    // Configuration globale
    let currentStep = 1;
    const totalSteps = 4;
    let uploadedFile = null;
    let clientStatus = null;

    // Initialisation
    $(document).ready(function () {
        console.log('📞 Initialisation du formulaire de contact');

        // Vérifier la configuration
        if (typeof hticContactConfig === 'undefined') {
            console.warn('⚠️ Configuration manquante - Utilisation des valeurs par défaut');
            window.hticContactConfig = {
                ajaxUrl: '/wp-admin/admin-ajax.php',
                nonce: $('#contact-form input[name="nonce"]').val()
            };
        }

        // Initialiser les événements
        initEvents();

        // Initialiser la progression
        updateProgress();

        console.log('✅ Formulaire de contact prêt');
    });

    function initEvents() {
        // ====== GESTION ÉTAPE 1 - STATUT CLIENT ======

        // Gestion du statut client
        $('input[name="client_status"]').on('change', function () {
            const status = $(this).val();
            console.log('📍 Statut sélectionné:', status);
            clientStatus = status;

            // Activer le bouton suivant
            $('#btn-next-status').prop('disabled', false);

            // Plus besoin d'afficher/masquer conditionnellement car la bannière est toujours visible
        });

        // Ouvrir la modal de création de compte
        $('#show-account-modal').on('click', function (e) {
            e.preventDefault();
            $('#account-modal').fadeIn(300);
            $('body').addClass('modal-open');
        });

        // Fermer la modal
        $('.modal-close').on('click', function () {
            $('#account-modal, #client-redirect-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        });

        $('.modal-overlay').on('click', function (e) {
            if (e.target === this) {
                $('#account-modal, #client-redirect-modal').fadeOut(300);
                $('body').removeClass('modal-open');
            }
        });

        // Bouton pour continuer le formulaire depuis la modal client
        $('#continue-form-anyway').on('click', function () {
            $('#client-redirect-modal').fadeOut(300);
            $('body').removeClass('modal-open');
            goToStep(2); // Aller directement à l'étape 2
        });

        $('#show-general-account-info').on('click', function (e) {
            e.preventDefault();
            $('#account-modal').fadeIn(300);
            $('body').addClass('modal-open');
        });

        // ====== NAVIGATION ENTRE ÉTAPES ======

        // Navigation entre étapes
        $('.btn-next').on('click', handleNextStep);
        $('.btn-prev').on('click', handlePrevStep);

        // Validation en temps réel
        $('.form-control').on('blur', validateField);

        // Upload de fichiers
        initFileUpload();

        // Soumission du formulaire
        $('#btn-submit-contact').on('click', handleFormSubmit);

        // Empêcher la soumission native du formulaire
        $('#contact-form').on('submit', function (e) {
            e.preventDefault();
            return false;
        });

        // Formatage automatique du téléphone
        $('#telephone').on('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                const parts = value.match(/.{1,2}/g);
                if (parts) {
                    this.value = parts.slice(0, 5).join(' ');
                }
            }
        });

        // Code postal - limiter à 5 chiffres
        $('#code_postal').on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 5);
        });
    }

    // Navigation entre étapes
    function handleNextStep(e) {
        e.preventDefault();
        const $btn = $(this);
        let nextStep = parseInt($btn.data('next'));

        // Si on est sur l'étape 1, vérifier le statut
        if (currentStep === 1) {
            if (!$('input[name="client_status"]:checked').length) {
                alert('Veuillez sélectionner votre statut');
                return;
            }

            clientStatus = $('input[name="client_status"]:checked').val();
            console.log('Statut sélectionné pour navigation:', clientStatus);

            // Si c'est un client existant, afficher la modal de redirection
            if (clientStatus === 'is-client') {
                $('#client-redirect-modal').fadeIn(300);
                $('body').addClass('modal-open');
                return; // Arrêter ici, la modal gère la suite
            } else {
                // Si ce n'est pas un client, continuer normalement vers l'étape 2
                nextStep = 2;
            }
        }

        if (validateCurrentStep()) {
            goToStep(nextStep);
        }
    }

    function handlePrevStep(e) {
        e.preventDefault();
        const prevStep = parseInt($(this).data('prev'));

        // Si on revient à l'étape 1
        if (prevStep === 1) {
            $('#client-info-box').hide();
        }

        goToStep(prevStep);
    }

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        console.log('Navigation vers étape', step);

        // Masquer l'étape actuelle
        $(`.form-step[data-step="${currentStep}"]`).removeClass('active');

        // Afficher la nouvelle étape
        $(`.form-step[data-step="${step}"]`).addClass('active');

        // Mettre à jour les indicateurs
        updateStepIndicators(step);

        // Mettre à jour la progression
        currentStep = step;
        updateProgress();

        // Actions spéciales par étape
        if (step === 4) {
            generateSummary();
        }

        // Scroll vers le haut
        const $form = $('#htic-contact-form');
        if ($form.length) {
            $('html, body').animate({
                scrollTop: $form.offset().top - 50
            }, 300);
        }
    }

    function updateStepIndicators(step) {
        $('.step').removeClass('active completed');

        // Marquer les étapes complétées
        for (let i = 1; i < step; i++) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        }

        // Marquer l'étape active
        $(`.step[data-step="${step}"]`).addClass('active');
    }

    function updateProgress() {
        const progressPercent = (currentStep / totalSteps) * 100;
        $('#progress-fill').css('width', progressPercent + '%');
    }

    // Validation
    function validateCurrentStep() {
        let isValid = true;
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        // Réinitialiser les erreurs
        currentStepElement.find('.error-message').text('');
        currentStepElement.find('.form-control').removeClass('error');

        // Validation spéciale pour l'étape 1
        if (currentStep === 1) {
            return true; // Déjà vérifié dans handleNextStep
        }

        // Valider tous les champs requis de l'étape courante
        currentStepElement.find('.form-control[required]').each(function () {
            if (!validateField.call(this)) {
                isValid = false;
            }
        });

        // Validation spéciale pour l'étape 4 (confirmation)
        if (currentStep === 4) {
            if (!$('#captcha-check').is(':checked')) {
                showFieldError('#captcha-check', 'Veuillez confirmer que vous n\'êtes pas un robot');
                isValid = false;
            }
            if (!$('#rgpd-consent').is(':checked')) {
                showFieldError('#rgpd-consent', 'Vous devez accepter la politique de confidentialité');
                isValid = false;
            }
        }

        return isValid;
    }

    function validateField() {
        const $field = $(this);
        const value = $field.val().trim();
        const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
        let isValid = true;
        let errorMessage = '';

        // Réinitialiser l'état
        $field.removeClass('error success');
        $field.siblings('.error-message').text('');

        // Validation des champs requis
        if ($field.prop('required') && !value) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        }
        // Validation email
        else if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Format d\'email invalide';
            }
        }
        // Validation téléphone français
        else if ($field.attr('name') === 'telephone' && value) {
            const phoneClean = value.replace(/[\s\-\+\(\)\.]/g, '');
            if (phoneClean.length < 10) {
                isValid = false;
                errorMessage = 'Le téléphone doit contenir au moins 10 chiffres';
            }
        }
        // Validation code postal français
        else if ($field.attr('name') === 'code_postal' && value) {
            const postalRegex = /^[0-9]{5}$/;
            if (!postalRegex.test(value)) {
                isValid = false;
                errorMessage = 'Le code postal doit contenir 5 chiffres';
            }
        }

        // Afficher le résultat
        if (!isValid) {
            showFieldError($field, errorMessage);
        } else if (value) {
            $field.addClass('success');
        }

        return isValid;
    }

    function showFieldError($field, message) {
        if (typeof $field === 'string') {
            $field = $($field);
        }
        $field.addClass('error');
        $field.siblings('.error-message').text(message);
    }

    // Soumission du formulaire
    function handleFormSubmit(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        if ($btn.hasClass('submitting')) {
            console.log('Soumission déjà en cours');
            return false;
        }

        console.log('📤 Début soumission formulaire de contact');

        // Valider l'étape actuelle
        if (!validateCurrentStep()) {
            console.log('❌ Validation échouée');
            return false;
        }

        // Marquer comme en cours de soumission
        $btn.addClass('submitting loading').prop('disabled', true);

        // Changer le texte du bouton
        const originalText = $btn.html();
        $btn.html('<span class="spinner"></span> Envoi en cours...');

        // Préparer FormData pour les fichiers
        const formData = new FormData();

        // Action AJAX
        formData.append('action', 'htic_process_contact');

        // Utiliser le nonce
        const nonce = (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce)
            ? hticSimulateur.nonce
            : (typeof hticContactConfig !== 'undefined' && hticContactConfig.nonce)
                ? hticContactConfig.nonce
                : $('#contact-form input[name="nonce"]').val();

        formData.append('nonce', nonce);

        // Collecter toutes les données du formulaire
        const contactData = {
            clientStatus: clientStatus,
            civilite: $('#civilite').val(),
            firstName: $('#prenom').val(),
            lastName: $('#nom').val(),
            email: $('#email').val(),
            phone: $('#telephone').val(),
            postalCode: $('#code_postal').val(),
            ville: $('#ville').val(),
            adresse: $('#adresse').val(),
            objet: $('#objet_demande').val(),
            message: $('#message').val(),
            type: 'contact',
            timestamp: new Date().toISOString(),
            source: 'website'
        };

        console.log('📋 Données collectées:', contactData);
        formData.append('form_data', JSON.stringify(contactData));

        // Ajouter le fichier si présent
        if (uploadedFile) {
            formData.append('fichier', uploadedFile);
            console.log('📎 Fichier ajouté:', uploadedFile.name);
        }

        // URL AJAX
        const ajaxUrl = (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl)
            ? hticSimulateur.ajaxUrl
            : '/wp-admin/admin-ajax.php';

        console.log('📡 Envoi vers:', ajaxUrl);

        // Envoyer la requête AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function (response) {
                console.log('✅ Réponse reçue:', response);

                if (response.success) {
                    showMessage('✅ Votre demande a été envoyée avec succès !', 'success');
                    setTimeout(() => {
                        resetForm();
                        window.location.href = '/merci-contact';
                    }, 100);
                } else {
                    const errorMsg = response.data || 'Une erreur est survenue lors de l\'envoi';
                    showMessage('❌ ' + errorMsg, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showMessage('❌ Erreur de connexion. Veuillez réessayer.', 'error');
            },
            complete: function () {
                $btn.removeClass('submitting loading disabled').prop('disabled', false).html(originalText);
            }
        });

        return false;
    }

    function showMessage(text, type) {
        let $messages = $('#form-messages');

        if (!$messages.length) {
            $messages = $('<div id="form-messages" class="form-messages"></div>');
            $('#contact-form').prepend($messages);
        }

        const messageClass = `message-${type}`;

        $messages.html(`
            <div class="message ${messageClass}">
                ${text}
            </div>
        `).fadeIn();

        $('html, body').animate({
            scrollTop: $messages.offset().top - 100
        }, 300);

        if (type === 'success') {
            setTimeout(() => {
                $messages.fadeOut();
            }, 10000);
        }
    }

    function resetForm() {
        currentStep = 1;
        clientStatus = null;
        $('#contact-form')[0].reset();
        resetFileUpload();
        $('.error-message').text('');
        $('.form-control').removeClass('error success');
        $('#client-info-box').hide();
        $('#btn-next-status').prop('disabled', true);
    }

    // Upload de fichiers
    function initFileUpload() {
        const $uploadArea = $('#upload-area');
        const $fileInput = $('#file-input');

        if (!$uploadArea.length || !$fileInput.length) {
            console.log('Zone upload non trouvée');
            return;
        }

        $uploadArea.on('click', function (e) {
            if (!$(e.target).hasClass('btn-remove-file')) {
                $fileInput.click();
            }
        });

        $uploadArea.on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $uploadArea.on('dragleave dragend', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $uploadArea.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files[0]);
            }
        });

        $fileInput.on('change', function () {
            if (this.files.length > 0) {
                handleFileSelection(this.files[0]);
            }
        });

        $(document).on('click', '.btn-remove-file', function (e) {
            e.stopPropagation();
            resetFileUpload();
        });
    }

    function handleFileSelection(file) {
        console.log('📎 Fichier sélectionné:', file.name, file.size, file.type);

        // Vérifier la taille (5 Mo max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showUploadError('Le fichier est trop volumineux (max. 5 Mo)');
            return;
        }

        // Vérifier le type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if (!allowedTypes.includes(file.type)) {
            showUploadError('Type de fichier non autorisé. Formats acceptés : JPG, PNG, PDF, DOC, DOCX');
            return;
        }

        simulateFileUpload(file);
    }

    function simulateFileUpload(file) {
        const $uploadArea = $('#upload-area');

        $uploadArea.find('.upload-content').hide();
        $uploadArea.find('.upload-progress').show();

        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 30;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                showUploadSuccess(file);
            }
            $('.upload-progress-fill').css('width', progress + '%');
        }, 200);

        uploadedFile = file;
    }

    function showUploadSuccess(file) {
        const $uploadArea = $('#upload-area');

        $uploadArea.find('.upload-progress').hide();
        $uploadArea.find('.upload-success').show();

        let fileName = file.name;
        if (fileName.length > 30) {
            fileName = fileName.substring(0, 27) + '...';
        }
        $uploadArea.find('.upload-success-text').text(fileName);

        $('#upload-area').siblings('.error-message').text('');
    }

    function showUploadError(message) {
        $('#upload-area').siblings('.error-message').text(message);
        resetFileUpload();
    }

    function resetFileUpload() {
        const $uploadArea = $('#upload-area');

        $uploadArea.find('.upload-progress, .upload-success').hide();
        $uploadArea.find('.upload-content').show();
        $('.upload-progress-fill').css('width', '0%');

        $('#file-input').val('');
        uploadedFile = null;
    }

    // Récapitulatif
    function generateSummary() {
        const $summary = $('#form-summary');
        if (!$summary.length) return;

        $summary.empty();

        const formData = {
            statut_client: clientStatus === 'is-client' ? 'Client existant' : 'Non client',
            civilite: $('#civilite').val(),
            nom: $('#nom').val(),
            prenom: $('#prenom').val(),
            email: $('#email').val(),
            telephone: $('#telephone').val(),
            adresse: $('#adresse').val(),
            code_postal: $('#code_postal').val(),
            ville: $('#ville').val(),
            objet_demande: $('#objet_demande').val(),
            message: $('#message').val()
        };

        let summaryHtml = `
            <div class="summary-section">
                <h5>🏠 Statut</h5>
                <div class="summary-item">
                    <span class="summary-label">Type de demande :</span>
                    <span class="summary-value">${formData.statut_client}</span>
                </div>
            </div>
            
            <div class="summary-section">
                <h5>👤 Vos informations</h5>
                <div class="summary-item">
                    <span class="summary-label">Nom complet :</span>
                    <span class="summary-value">${formData.civilite || ''} ${formData.prenom || ''} ${formData.nom || ''}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Email :</span>
                    <span class="summary-value">${formData.email || ''}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Téléphone :</span>
                    <span class="summary-value">${formData.telephone || ''}</span>
                </div>`;

        if (formData.adresse) {
            summaryHtml += `
                <div class="summary-item">
                    <span class="summary-label">Adresse :</span>
                    <span class="summary-value">${formData.adresse}</span>
                </div>`;
        }

        summaryHtml += `
                <div class="summary-item">
                    <span class="summary-label">Ville :</span>
                    <span class="summary-value">${formData.code_postal || ''} ${formData.ville || ''}</span>
                </div>
            </div>
            
            <div class="summary-section">
                <h5>📋 Votre demande</h5>
                <div class="summary-item">
                    <span class="summary-label">Objet :</span>
                    <span class="summary-value">${formData.objet_demande || ''}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Message :</span>
                    <span class="summary-value">${formData.message || ''}</span>
                </div>`;

        if (uploadedFile) {
            summaryHtml += `
                <div class="summary-item">
                    <span class="summary-label">Pièce jointe :</span>
                    <span class="summary-value">📎 ${uploadedFile.name}</span>
                </div>`;
        }

        summaryHtml += `</div>`;

        $summary.html(summaryHtml);
    }

})(jQuery);