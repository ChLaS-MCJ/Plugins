/**
 * JavaScript pour le formulaire de contact multi-étapes
 * Fichier: formulaires/contact/contact-form.js
 */

(function ($) {
    'use strict';

    // Configuration globale
    let currentStep = 1;
    let totalSteps = 3;
    let formData = {};
    let uploadedFile = null;
    let typesConfig = {};

    // Initialisation
    $(document).ready(function () {
        initContactForm();
    });

    function initContactForm() {
        // Charger la configuration des types de demandes
        loadTypesConfig();

        // Initialiser les événements
        initEvents();

        // Initialiser la première étape
        updateProgress();

        console.log('📞 Formulaire de contact initialisé');
    }

    function loadTypesConfig() {
        const configElement = $('#contact-config');
        if (configElement.length) {
            try {
                typesConfig = JSON.parse(configElement.text());
            } catch (e) {
                console.error('Erreur lors du chargement de la configuration:', e);
            }
        }
    }

    function initEvents() {
        // Navigation entre étapes
        $(document).on('click', '.btn-next', handleNextStep);
        $(document).on('click', '.btn-prev', handlePrevStep);

        // Validation en temps réel
        $(document).on('input change', '.form-control', validateField);

        // Type de demande
        $(document).on('change', '#type_demande', handleTypeDemandeChange);

        // Upload de fichiers
        initFileUpload();

        // Soumission du formulaire
        $(document).on('submit', '#contact-form', handleFormSubmit);

        // Checkbox CAPTCHA
        $(document).on('change', '#captcha-check', function () {
            // Simple vérification côté client (le vrai contrôle sera côté serveur)
            if (this.checked) {
                setTimeout(() => {
                    $(this).closest('.form-group').addClass('success');
                }, 500);
            }
        });
    }

    // ==========================================
    // NAVIGATION ENTRE ÉTAPES
    // ==========================================

    function handleNextStep(e) {
        e.preventDefault();
        const nextStep = parseInt($(this).data('next'));

        if (validateCurrentStep()) {
            goToStep(nextStep);
        }
    }

    function handlePrevStep(e) {
        e.preventDefault();
        const prevStep = parseInt($(this).data('prev'));
        goToStep(prevStep);
    }

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

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
        if (step === 3) {
            generateSummary();
        }

        // Scroll vers le haut
        $('html, body').animate({
            scrollTop: $('#htic-contact-form').offset().top - 50
        }, 300);
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

    // ==========================================
    // VALIDATION
    // ==========================================

    function validateCurrentStep() {
        let isValid = true;
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        // Valider tous les champs requis de l'étape actuelle
        currentStepElement.find('.form-control[required]').each(function () {
            if (!validateField.call(this)) {
                isValid = false;
            }
        });

        // Validations spéciales par étape
        if (currentStep === 2) {
            isValid = validateStep2() && isValid;
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
        // Validation téléphone
        else if ($field.attr('name') === 'telephone' && value) {
            const phoneRegex = /^[\d\s\-\+\(\)\.]{10,}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Format de téléphone invalide';
            }
        }
        // Validation code postal
        else if ($field.attr('name') === 'code_postal' && value) {
            const postalRegex = /^[0-9]{5}$/;
            if (!postalRegex.test(value)) {
                isValid = false;
                errorMessage = 'Le code postal doit contenir 5 chiffres';
            }
        }

        // Afficher le résultat
        if (!isValid) {
            $field.addClass('error');
            $field.siblings('.error-message').text(errorMessage);
        } else if (value) {
            $field.addClass('success');
        }

        return isValid;
    }

    function validateStep2() {
        let isValid = true;
        const typeDemandeValue = $('#type_demande').val();

        if (!typeDemandeValue) {
            $('#type_demande').addClass('error');
            $('#type_demande').siblings('.error-message').text('Veuillez sélectionner un type de demande');
            isValid = false;
        }

        // Vérifier l'upload si requis
        const typeConfig = typesConfig[typeDemandeValue];
        if (typeConfig && typeConfig.upload && typeConfig.upload_required && !uploadedFile) {
            $('#upload-zone .error-message').text('Un fichier est requis pour ce type de demande');
            isValid = false;
        }

        return isValid;
    }

    // ==========================================
    // GESTION DU TYPE DE DEMANDE
    // ==========================================

    function handleTypeDemandeChange() {
        const selectedValue = $(this).val();
        const typeConfig = typesConfig[selectedValue];

        if (!typeConfig) {
            hideConditionalFields();
            return;
        }

        // Afficher la description
        showDescription(typeConfig.description);

        // Gérer l'upload conditionnel
        handleConditionalUpload(typeConfig);

        // Gérer le champ libre conditionnel
        handleConditionalMessage(typeConfig);
    }

    function showDescription(description) {
        const $desc = $('#demande-description');
        $desc.find('.description-text').text(description);
        $desc.slideDown();
    }

    function handleConditionalUpload(config) {
        const $uploadZone = $('#upload-zone');

        if (config.upload) {
            // Mettre à jour le label
            $('#upload-label-text').text(config.upload_label || 'Fichier joint');

            // Marquer comme requis si nécessaire
            const $fileInput = $('#file-input');
            if (config.upload_required) {
                $fileInput.prop('required', true);
                $('.upload-label').addClass('required');
            } else {
                $fileInput.prop('required', false);
                $('.upload-label').removeClass('required');
            }

            $uploadZone.slideDown();
        } else {
            $uploadZone.slideUp();
            $('#file-input').prop('required', false);
        }
    }

    function handleConditionalMessage(config) {
        const $champLibre = $('#champ-libre-zone');

        if (config.champ_libre) {
            // Mettre à jour le label
            $('#champ-libre-label').text(config.champ_libre_label || 'Message');
            $champLibre.slideDown();
        } else {
            $champLibre.slideUp();
        }
    }

    function hideConditionalFields() {
        $('#demande-description').slideUp();
        $('#upload-zone').slideUp();
        $('#champ-libre-zone').slideUp();
    }

    // ==========================================
    // UPLOAD DE FICHIERS
    // ==========================================

    function initFileUpload() {
        const $uploadArea = $('#upload-area');
        const $fileInput = $('#file-input');

        // Événements de drag & drop
        $uploadArea.on('dragover dragenter', function (e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $uploadArea.on('dragleave dragend', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $uploadArea.on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files[0]);
            }
        });

        // Sélection de fichier classique
        $fileInput.on('change', function () {
            if (this.files.length > 0) {
                handleFileSelection(this.files[0]);
            }
        });

        // Supprimer le fichier
        $(document).on('click', '.btn-remove-file', function () {
            resetFileUpload();
        });
    }

    function handleFileSelection(file) {
        // Vérifier la taille
        const maxSize = 10 * 1024 * 1024; // 10 Mo
        if (file.size > maxSize) {
            showUploadError('Le fichier est trop volumineux (max. 10 Mo)');
            return;
        }

        // Vérifier le type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(file.type)) {
            showUploadError('Type de fichier non autorisé');
            return;
        }

        // Simuler l'upload
        simulateFileUpload(file);
    }

    function simulateFileUpload(file) {
        const $uploadArea = $('#upload-area');

        // Masquer le contenu et afficher la progression
        $uploadArea.find('.upload-content').hide();
        $uploadArea.find('.upload-progress').show();

        // Simuler la progression
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 20;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                showUploadSuccess(file);
            }
            $('.upload-progress-fill').css('width', progress + '%');
        }, 200);

        // Stocker le fichier
        uploadedFile = file;
        $('#uploaded-file').val(file.name);
    }

    function showUploadSuccess(file) {
        const $uploadArea = $('#upload-area');

        $uploadArea.find('.upload-progress').hide();
        $uploadArea.find('.upload-success').show();
        $uploadArea.find('.upload-success-text').text(`${file.name} uploadé avec succès`);

        // Effacer les erreurs
        $('#upload-zone .error-message').text('');
    }

    function showUploadError(message) {
        $('#upload-zone .error-message').text(message);
        resetFileUpload();
    }

    function resetFileUpload() {
        const $uploadArea = $('#upload-area');

        $uploadArea.find('.upload-progress, .upload-success').hide();
        $uploadArea.find('.upload-content').show();
        $('.upload-progress-fill').css('width', '0%');

        $('#file-input').val('');
        $('#uploaded-file').val('');
        uploadedFile = null;
    }

    // ==========================================
    // RÉCAPITULATIF
    // ==========================================

    function generateSummary() {
        const $summary = $('#form-summary');
        $summary.empty();

        // Récupérer toutes les données
        const formDataCurrent = {
            civilite: $('#civilite').val(),
            nom: $('#nom').val(),
            prenom: $('#prenom').val(),
            email: $('#email').val(),
            telephone: $('#telephone').val(),
            adresse: $('#adresse').val(),
            complement_adresse: $('#complement_adresse').val(),
            code_postal: $('#code_postal').val(),
            ville: $('#ville').val(),
            type_demande: $('#type_demande').val(),
            message: $('#message').val()
        };

        // Générer le récapitulatif
        const items = [
            { label: 'Civilité', value: formDataCurrent.civilite },
            { label: 'Nom', value: formDataCurrent.nom },
            { label: 'Prénom', value: formDataCurrent.prenom },
            { label: 'Email', value: formDataCurrent.email },
            { label: 'Téléphone', value: formDataCurrent.telephone },
            { label: 'Adresse', value: formDataCurrent.adresse },
        ];

        // Ajouter le complément d'adresse si rempli
        if (formDataCurrent.complement_adresse) {
            items.push({ label: 'Complément d\'adresse', value: formDataCurrent.complement_adresse });
        }

        items.push(
            { label: 'Code postal', value: formDataCurrent.code_postal },
            { label: 'Ville', value: formDataCurrent.ville },
            { label: 'Type de demande', value: $('#type_demande option:selected').text() }
        );

        if (formDataCurrent.message) {
            items.push({ label: 'Message', value: formDataCurrent.message });
        }

        if (uploadedFile) {
            items.push({ label: 'Fichier joint', value: uploadedFile.name });
        }

        // Afficher les items
        items.forEach(item => {
            if (item.value) {
                $summary.append(`
                    <div class="summary-item">
                        <span class="summary-label">${item.label}:</span>
                        <span class="summary-value">${item.value}</span>
                    </div>
                `);
            }
        });
    }

    // ==========================================
    // SOUMISSION DU FORMULAIRE
    // ==========================================

    function handleFormSubmit(e) {
        e.preventDefault();

        if (!validateCurrentStep()) {
            return;
        }

        const $submitBtn = $('.btn-submit');
        $submitBtn.addClass('loading').prop('disabled', true);

        // Préparer les données
        const formData = new FormData();

        // Ajouter tous les champs
        $('#contact-form').find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const value = $field.val();

            if (name && value && $field.attr('type') !== 'file') {
                formData.append(name, value);
            }
        });

        // Ajouter le fichier si présent
        if (uploadedFile) {
            formData.append('fichier', uploadedFile);
        }

        // Envoyer via AJAX
        $.ajax({
            url: hticContactConfig.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    showMessage('Votre demande a été envoyée avec succès ! Nous vous recontacterons dans les plus brefs délais.', 'success');
                    resetForm();
                } else {
                    showMessage(response.data || 'Une erreur est survenue lors de l\'envoi', 'error');
                }
            },
            error: function () {
                showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
            },
            complete: function () {
                $submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    function showMessage(text, type) {
        const $messages = $('#form-messages');
        const messageClass = `message-${type}`;

        $messages.html(`
            <div class="message ${messageClass}">
                ${type === 'success' ? '✅' : '❌'} ${text}
            </div>
        `);

        // Scroll vers le message
        $('html, body').animate({
            scrollTop: $messages.offset().top - 100
        }, 300);
    }

    function resetForm() {
        // Remettre à l'étape 1
        currentStep = 1;
        goToStep(1);

        // Vider le formulaire
        $('#contact-form')[0].reset();
        $('.form-control').removeClass('error success');
        $('.error-message').text('');

        // Reset de l'upload
        resetFileUpload();

        // Masquer les champs conditionnels
        hideConditionalFields();
    }

})(jQuery);