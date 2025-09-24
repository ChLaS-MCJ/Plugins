/**
 * Simulateur Gaz Professionnel
 * 
 * Application de simulation de devis gaz pour entreprises
 * Gère le processus complet de souscription en 5 étapes avec validation des données,
 * calcul des tarifs et génération de devis personnalisés.
 */

jQuery(document).ready(function ($) {
    'use strict';

    // ===========================================
    // CONFIGURATION ET ÉTAT GLOBAL
    // ===========================================

    const GazProfessionnelState = {
        currentStep: 1,
        totalSteps: 5,
        formData: {},
        configData: {},
        uploadedFiles: {},
        calculResults: {}
    };

    const GazProfessionnelConfig = {
        init() {
            this.loadConfigData();
            this.createGlobalVariables();
        },

        loadConfigData() {
            const configElement = document.getElementById('simulateur-config');
            if (configElement) {
                try {
                    GazProfessionnelState.configData = JSON.parse(configElement.textContent);
                } catch (e) {
                    GazProfessionnelState.configData = {};
                }
            }
        },

        createGlobalVariables() {
            const { configData } = GazProfessionnelState;
            if (!window.hticSimulateur && (configData.ajax_url || configData.nonce)) {
                window.hticSimulateur = {
                    ajaxUrl: configData.ajax_url || '/wp-admin/admin-ajax.php',
                    nonce: configData.nonce || configData.calculate_nonce,
                    type: 'gaz-professionnel'
                };
            }
        }
    };

    // ===========================================
    // GESTION DES COMMUNES
    // ===========================================

    const CommunesManager = {
        defaultCommunes: [
            // Gaz Naturel
            { nom: 'AIRE SUR L\'ADOUR', type: 'naturel' },
            { nom: 'BARCELONNE DU GERS', type: 'naturel' },
            { nom: 'GAAS', type: 'naturel' },
            { nom: 'LABATUT', type: 'naturel' },
            { nom: 'LALUQUE', type: 'naturel' },
            { nom: 'MISSON', type: 'naturel' },
            { nom: 'POUILLON', type: 'naturel' },
            // Gaz Propane
            { nom: 'BASCONS', type: 'propane' },
            { nom: 'BENESSE LES DAX', type: 'propane' },
            { nom: 'CAMPAGNE', type: 'propane' },
            { nom: 'CARCARES SAINTE CROIX', type: 'propane' },
            { nom: 'GEAUNE', type: 'propane' },
            { nom: 'MAZEROLLES', type: 'propane' },
            { nom: 'MEILHAN', type: 'propane' },
            { nom: 'PONTONX SUR L\'ADOUR', type: 'propane' },
            { nom: 'SAINT MAURICE', type: 'propane' },
            { nom: 'SOUPROSSE', type: 'propane' },
            { nom: 'TETHIEU', type: 'propane' },
            { nom: 'YGOS SAINT SATURNIN', type: 'propane' }
        ],

        init() {
            this.populateSelect();
        },

        populateSelect() {
            const communesNaturel = this.defaultCommunes.filter(c => c.type === 'naturel');
            const communesPropane = this.defaultCommunes.filter(c => c.type === 'propane');

            $('#communes-naturel').empty();
            communesNaturel.forEach(commune => {
                $('#communes-naturel').append(`<option value="${commune.nom}" data-type="naturel">${commune.nom}</option>`);
            });

            $('#communes-propane').empty();
            communesPropane.forEach(commune => {
                $('#communes-propane').append(`<option value="${commune.nom}" data-type="propane">${commune.nom}</option>`);
            });
        },

        handleSelection() {
            const selectedValue = $('#commune').val();
            const selectedOption = $('#commune option:selected');

            if (selectedValue === 'autre') {
                $('#autre-commune-details').slideDown();
                $('#type-gaz-info').hide();
            } else if (selectedValue && selectedValue !== '') {
                $('#autre-commune-details').slideUp();
                this.showTypeGazInfo(selectedOption);
            } else {
                $('#autre-commune-details').hide();
                $('#type-gaz-info').hide();
            }
        },

        showTypeGazInfo(selectedOption) {
            const typeGaz = selectedOption.data('type');
            if (!typeGaz) return;

            const typeText = typeGaz === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
            const icon = typeGaz === 'naturel' ? '🌱' : '⛽';

            $('#type-gaz-text').html(`${icon} <strong>${typeText}</strong> disponible dans cette commune`);
            $('#type-gaz-info').fadeIn();
        },

        determineTypeFromCommune(commune) {
            if (commune === 'autre') {
                const typeChoisi = $('input[name="type_gaz_autre"]:checked').val();
                return typeChoisi === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
            }

            const selectedOption = $('#commune option:selected');
            if (selectedOption.length > 0) {
                const dataType = selectedOption.data('type');
                if (dataType) {
                    return (dataType === 'naturel') ? 'Gaz naturel' : 'Gaz propane';
                }
            }

            // Fallback: chercher dans toutes les options
            let typeGaz = 'Gaz propane';
            $('#commune option').each(function () {
                if ($(this).val() === commune) {
                    const dataType = $(this).data('type');
                    if (dataType) {
                        typeGaz = (dataType === 'naturel') ? 'Gaz naturel' : 'Gaz propane';
                        return false;
                    }
                }
            });

            return typeGaz;
        }
    };

    // ===========================================
    // NAVIGATION ENTRE ÉTAPES
    // ===========================================

    const NavigationManager = {
        init() {
            this.bindEvents();
            this.updateUI();
        },

        bindEvents() {
            $('#btn-next-pro').on('click', () => {
                if (ValidationManager.validateCurrentStep()) {
                    DataManager.saveCurrentStepData();
                    this.nextStep();
                }
            });

            $('#btn-previous-pro').on('click', () => {
                DataManager.saveCurrentStepData();
                this.prevStep();
            });

            $('#btn-calculate-pro').on('click', () => {
                if (ValidationManager.validateCurrentStep()) {
                    DataManager.saveCurrentStepData();
                    CalculationManager.calculateResults();
                }
            });

            $('#btn-restart-pro').on('click', () => {
                if (confirm('Voulez-vous vraiment recommencer la simulation ?')) {
                    this.restartSimulation();
                }
            });

            $('.step').on('click', (e) => {
                const targetStep = parseInt($(e.currentTarget).data('step'));
                if (targetStep < GazProfessionnelState.currentStep || targetStep === 1) {
                    DataManager.saveCurrentStepData();
                    this.goToStep(targetStep);
                }
            });
        },

        nextStep() {
            const { currentStep, totalSteps } = GazProfessionnelState;
            if (currentStep < totalSteps) {
                GazProfessionnelState.currentStep++;
                this.updateUI();
                this.scrollToTop();
                this.executeStepActions();
            }
        },

        prevStep() {
            if (GazProfessionnelState.currentStep > 1) {
                GazProfessionnelState.currentStep--;
                this.updateUI();
                this.scrollToTop();
            }
        },

        goToStep(stepNumber) {
            if (stepNumber >= 1 && stepNumber <= GazProfessionnelState.totalSteps) {
                if (stepNumber === 1) {
                    this.restartSimulation();
                    return;
                }

                GazProfessionnelState.currentStep = stepNumber;
                this.updateUI();
                this.scrollToTop();
                this.executeStepActions();
            }
        },

        executeStepActions() {
            const { currentStep } = GazProfessionnelState;
            switch (currentStep) {
                case 3:
                    StepManager.setupSelectionStep();
                    break;
                case 4:
                    StepManager.setupContactStep();
                    break;
                case 5:
                    StepManager.setupRecapStep();
                    break;
            }
        },

        updateUI() {
            const { currentStep, calculResults } = GazProfessionnelState;

            // Mise à jour des étapes actives
            $('.form-step').removeClass('active');
            $(`.form-step[data-step="${currentStep}"]`).addClass('active');

            // Progression
            this.updateProgressBar();
            this.updateStepIndicators();
            this.updateNavigationButtons();
        },

        updateProgressBar() {
            const { currentStep, totalSteps, calculResults } = GazProfessionnelState;
            let progressPercent;

            if (calculResults.isHighConsumption) {
                if (currentStep === 1) progressPercent = 33;
                else if (currentStep === 4) progressPercent = 66;
                else if (currentStep === 5) progressPercent = 100;
            } else {
                progressPercent = (currentStep / totalSteps) * 100;
            }

            $('.progress-fill').css('width', progressPercent + '%');
        },

        updateStepIndicators() {
            const { currentStep, calculResults } = GazProfessionnelState;

            $('.step').removeClass('active completed');

            if (calculResults.isHighConsumption) {
                $('.step[data-step="2"]').hide();
                $('.step[data-step="3"]').hide();
                $('.step[data-step="1"] .step-label').text('Configuration');
                $('.step[data-step="4"] .step-label').text('Contact');
                $('.step[data-step="5"] .step-label').text('Demande');
            } else {
                $('.step').show();
                $('.step[data-step="1"] .step-label').text('Configuration');
                $('.step[data-step="2"] .step-label').text('Résultats');
                $('.step[data-step="3"] .step-label').text('Sélection');
                $('.step[data-step="4"] .step-label').text('Contact');
                $('.step[data-step="5"] .step-label').text('Récapitulatif');
            }

            // Marquer étapes complétées
            for (let i = 1; i < currentStep; i++) {
                $(`.step[data-step="${i}"]`).addClass('completed');
            }
            $(`.step[data-step="${currentStep}"]`).addClass('active');
        },

        updateNavigationButtons() {
            const { currentStep, totalSteps, calculResults } = GazProfessionnelState;

            if (calculResults.isHighConsumption && currentStep === 4) {
                $('#btn-previous-pro').hide();
            } else {
                $('#btn-previous-pro').toggle(currentStep > 1);
            }

            if (currentStep === totalSteps || (calculResults.isHighConsumption && currentStep === 5)) {
                $('#btn-next-pro').hide();
                $('#btn-calculate-pro').hide();
                $('#btn-restart-pro').show();
            } else if (currentStep === 1) {
                $('#btn-next-pro').hide();
                $('#btn-calculate-pro').show();
                $('#btn-restart-pro').hide();
            } else if (currentStep === 2 && !calculResults.isHighConsumption) {
                $('#btn-next-pro').show().html('<span class="btn-icon">✅</span> Je souscris');
                $('#btn-calculate-pro').hide();
                $('#btn-restart-pro').hide();
            } else {
                $('#btn-next-pro').show().html('Suivant →');
                $('#btn-calculate-pro').hide();
                $('#btn-restart-pro').hide();
            }
        },

        scrollToTop() {
            $('html, body').animate({
                scrollTop: $('.simulateur-header').offset().top - 20
            }, 500);
        },

        restartSimulation() {
            if (confirm('Voulez-vous vraiment recommencer la simulation ?')) {
                location.reload();
            }
        }
    };

    // ===========================================
    // VALIDATION DES DONNÉES
    // ===========================================

    const ValidationManager = {
        init() {
            this.bindValidationEvents();
        },

        bindValidationEvents() {
            $('#responsable_email').off('blur').on('blur', function () {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    $(this).addClass('field-error');
                    UIManager.showValidationMessage('Format d\'email invalide');
                } else {
                    $(this).removeClass('field-error').addClass('field-success');
                }
            });

            $('#responsable_telephone').off('blur').on('blur', function () {
                const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
                if (tel && tel.length < 10) {
                    $(this).addClass('field-error');
                    UIManager.showValidationMessage('Numéro de téléphone trop court');
                } else {
                    $(this).removeClass('field-error').addClass('field-success');
                }
            });

            $('#entreprise_code_postal').off('blur').on('blur', function () {
                const cp = $(this).val();
                if (cp && !/^[0-9]{5}$/.test(cp)) {
                    $(this).addClass('field-error');
                    UIManager.showValidationMessage('Le code postal doit contenir 5 chiffres');
                } else {
                    $(this).removeClass('field-error').addClass('field-success');
                }
            });
        },

        validateCurrentStep() {
            const { currentStep } = GazProfessionnelState;
            const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
            let isValid = true;

            // Retirer les classes d'erreur existantes
            currentStepElement.find('.field-error').removeClass('field-error');

            switch (currentStep) {
                case 1:
                    isValid = this.validateStep1(currentStepElement);
                    break;
                case 3:
                    isValid = this.validateStep3(currentStepElement);
                    break;
                case 4:
                    isValid = this.validateStep4(currentStepElement);
                    break;
                default:
                    isValid = true;
            }

            if (!isValid) {
                UIManager.showValidationMessage('Veuillez remplir tous les champs obligatoires');
            }

            return isValid;
        },

        validateStep1(stepElement) {
            let isValid = true;

            // Validation commune
            const commune = stepElement.find('#commune');
            if (!commune.val()) {
                commune.addClass('field-error');
                isValid = false;
            } else {
                commune.addClass('field-success');
            }

            // Validation pour autre commune
            if (commune.val() === 'autre') {
                const nomCommune = stepElement.find('#nom_commune_autre').val().trim();
                const typeGaz = stepElement.find('input[name="type_gaz_autre"]:checked');

                if (!nomCommune) {
                    stepElement.find('#nom_commune_autre').addClass('field-error');
                    isValid = false;
                }

                if (!typeGaz.length) {
                    isValid = false;
                }
            }

            // Validation consommation
            const conso = stepElement.find('#consommation_previsionnelle');
            const consoValue = parseFloat(conso.val());
            if (!consoValue || consoValue < 100 || consoValue > 1000000) {
                conso.addClass('field-error');
                UIManager.showValidationMessage('La consommation doit être entre 100 et 1 000 000 kWh');
                isValid = false;
            } else {
                conso.addClass('field-success');
            }

            return isValid;
        },

        validateStep3(stepElement) {
            let isValid = true;

            if (!stepElement.find('input[name="tarif_choisi"]:checked').length) {
                UIManager.showValidationMessage('Veuillez sélectionner un tarif');
                isValid = false;
            }

            if (!stepElement.find('input[name="type_contrat"]:checked').length) {
                UIManager.showValidationMessage('Veuillez sélectionner un type de contrat');
                isValid = false;
            }

            return isValid;
        },

        validateStep4(stepElement) {
            let isValid = true;
            const { uploadedFiles } = GazProfessionnelState;

            // Champs obligatoires
            const requiredFields = [
                'entreprise_adresse', 'entreprise_code_postal', 'entreprise_ville',
                'raison_sociale', 'forme_juridique', 'siret', 'code_naf',
                'responsable_nom', 'responsable_prenom', 'responsable_email', 'responsable_telephone'
            ];

            requiredFields.forEach(field => {
                const $field = stepElement.find(`#${field}`);
                if (!$field.val()) {
                    $field.addClass('field-error');
                    isValid = false;
                }
            });

            // Validation SIRET
            const siret = stepElement.find('#siret').val();
            if (siret && siret.length !== 14) {
                stepElement.find('#siret').addClass('field-error');
                UIManager.showValidationMessage('Le SIRET doit contenir exactement 14 chiffres');
                isValid = false;
            }

            // Validation email
            const email = stepElement.find('#responsable_email').val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                stepElement.find('#responsable_email').addClass('field-error');
                UIManager.showValidationMessage('Format d\'email invalide');
                isValid = false;
            }

            // Checkboxes obligatoires
            const requiredCheckboxes = ['accept_conditions_pro', 'certifie_pouvoir'];
            requiredCheckboxes.forEach(checkbox => {
                if (!stepElement.find(`#${checkbox}`).is(':checked')) {
                    stepElement.find(`#${checkbox}`).addClass('field-error');
                    UIManager.showValidationMessage('Veuillez accepter les conditions obligatoires');
                    isValid = false;
                }
            });

            // Fichiers obligatoires
            if (!uploadedFiles.kbis_file) {
                UIManager.showValidationMessage('Le K-bis est obligatoire');
                isValid = false;
            }

            if (!uploadedFiles.rib_entreprise) {
                UIManager.showValidationMessage('Le RIB de l\'entreprise est obligatoire');
                isValid = false;
            }

            return isValid;
        }
    };

    // ===========================================
    // GESTION DES DONNÉES
    // ===========================================

    const DataManager = {
        saveCurrentStepData() {
            const { currentStep } = GazProfessionnelState;
            const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

            currentStepElement.find('input, select').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (!name || type === 'file') return;

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        GazProfessionnelState.formData[name] = $field.val();
                    }
                } else if (type === 'checkbox') {
                    GazProfessionnelState.formData[name] = $field.is(':checked');
                } else {
                    GazProfessionnelState.formData[name] = $field.val();
                }
            });

            // Ajouter les fichiers si étape 4
            if (Object.keys(GazProfessionnelState.uploadedFiles).length > 0 && currentStep === 4) {
                Object.keys(GazProfessionnelState.uploadedFiles).forEach(fileType => {
                    const file = GazProfessionnelState.uploadedFiles[fileType];
                    GazProfessionnelState.formData[fileType + '_filename'] = file.name;
                    GazProfessionnelState.formData[fileType + '_size'] = file.size;
                    GazProfessionnelState.formData[fileType + '_type'] = file.type;
                });
            }
        },

        collectAllFormData() {
            GazProfessionnelState.formData = {};

            $('.form-step').each(function () {
                $(this).find('input, select').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');
                    const type = $field.attr('type');

                    if (!name || type === 'file') return;

                    if (type === 'radio') {
                        if ($field.is(':checked')) {
                            GazProfessionnelState.formData[name] = $field.val();
                        }
                    } else if (type === 'checkbox') {
                        GazProfessionnelState.formData[name] = $field.is(':checked');
                    } else {
                        GazProfessionnelState.formData[name] = $field.val();
                    }
                });
            });

            // Ajouter les fichiers uploadés
            if (Object.keys(GazProfessionnelState.uploadedFiles).length > 0) {
                Object.keys(GazProfessionnelState.uploadedFiles).forEach(fileType => {
                    const file = GazProfessionnelState.uploadedFiles[fileType];
                    GazProfessionnelState.formData[fileType + '_filename'] = file.name;
                    GazProfessionnelState.formData[fileType + '_size'] = file.size;
                    GazProfessionnelState.formData[fileType + '_type'] = file.type;
                });
            }

            return GazProfessionnelState.formData;
        }
    };

    // ===========================================
    // GESTION DES FICHIERS
    // ===========================================

    const FileManager = {
        init() {
            this.bindUploadEvents();
        },

        bindUploadEvents() {
            $('.form-step[data-step="4"] .upload-card').each(function () {
                const card = $(this);
                const trigger = card.find('.upload-trigger');
                const fileInput = card.find('input[type="file"]');
                const resultDiv = card.find('.upload-result');
                const fileType = card.data('file');

                if (trigger.length && fileInput.length) {
                    trigger.off('click').on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        fileInput.trigger('click');
                    });

                    // Drag & Drop
                    card.on('dragover', function (e) {
                        e.preventDefault();
                        card.addClass('drag-over');
                    });

                    card.on('dragleave', function (e) {
                        e.preventDefault();
                        card.removeClass('drag-over');
                    });

                    card.on('drop', function (e) {
                        e.preventDefault();
                        card.removeClass('drag-over');
                        const files = e.originalEvent.dataTransfer.files;
                        if (files.length > 0) {
                            FileManager.handleUpload(files[0], fileType, resultDiv);
                        }
                    });

                    fileInput.on('change', function () {
                        if (this.files && this.files[0]) {
                            FileManager.handleUpload(this.files[0], fileType, resultDiv);
                        }
                    });
                }
            });
        },

        handleUpload(file, fileType, resultDiv) {
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

            if (!allowedTypes.includes(file.type)) {
                UIManager.showValidationMessage('Format non supporté. Utilisez PDF, JPG ou PNG.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                UIManager.showValidationMessage('Le fichier est trop volumineux (max 5 Mo)');
                return;
            }

            GazProfessionnelState.uploadedFiles[fileType] = file;

            resultDiv.html(`
                <div class="upload-success">
                    <span class="success-icon">✅</span>
                    <span class="file-name">${file.name}</span>
                    <button type="button" class="remove-file" onclick="FileManager.removeFile('${fileType}', this)">×</button>
                </div>
            `);
        },

        removeFile(fileType, button) {
            delete GazProfessionnelState.uploadedFiles[fileType];
            $(button).closest('.upload-result').empty();
            $(`input[name="${fileType}"]`).val('');
        }
    };

    // Fonction globale pour compatibilité
    window.removeUploadedFileGaz = (fileType, button) => FileManager.removeFile(fileType, button);

    // ===========================================
    // CALCULS ET RÉSULTATS
    // ===========================================

    const CalculationManager = {
        calculateResults() {
            const allData = DataManager.collectAllFormData();
            const consommation = parseInt(allData.consommation_previsionnelle) || 0;

            if (!allData.commune || !allData.consommation_previsionnelle) {
                UIManager.showValidationMessage('Données manquantes pour le calcul');
                return;
            }

            if (consommation > 35000) {
                this.handleHighConsumption(allData);
                return;
            }

            // Flux normal
            GazProfessionnelState.currentStep = 2;
            NavigationManager.updateUI();

            $('#results-container').html(`
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul en cours...</p>
                    <small>Analyse de votre offre gaz professionnel...</small>
                </div>
            `);

            this.sendToCalculator(allData);
        },

        handleHighConsumption(allData) {
            GazProfessionnelState.formData = allData;
            GazProfessionnelState.calculResults.isHighConsumption = true;
            GazProfessionnelState.calculResults.consommation_annuelle = allData.consommation_previsionnelle;
            GazProfessionnelState.calculResults.type_gaz = CommunesManager.determineTypeFromCommune(allData.commune);

            this.showHighConsumptionMessage(allData);

            GazProfessionnelState.currentStep = 4;
            NavigationManager.updateUI();
            StepManager.setupContactStep();
        },

        showHighConsumptionMessage(allData) {
            const consommation = parseInt(allData.consommation_previsionnelle);

            const messageHtml = `
                <div class="high-consumption-message">
                    <div class="message-header">
                        <div class="message-icon">📞</div>
                        <h2>Devis personnalisé requis</h2>
                    </div>
                    
                    <div class="message-content">
                        <div class="consumption-info">
                            <div class="info-badge high-consumption">
                                🔥 ${consommation.toLocaleString()} kWh/an
                            </div>
                            <p class="main-message">
                                Votre consommation prévisionnelle nécessite une étude personnalisée par nos experts.
                            </p>
                        </div>
                        
                        <div class="benefits-list">
                            <h4>✨ Avantages d'un devis sur-mesure :</h4>
                            <ul>
                                <li>📊 Analyse détaillée de vos besoins énergétiques</li>
                                <li>💰 Négociation des meilleurs tarifs du marché</li>
                                <li>🎯 Solutions adaptées à votre secteur d'activité</li>
                                <li>🤝 Accompagnement personnalisé par un expert</li>
                            </ul>
                        </div>
                        
                        <div class="auto-redirect">
                            <div class="redirect-info">
                                <div class="spinner-small"></div>
                                <span>Redirection vers le formulaire de contact...</span>
                            </div>
                            <div class="progress-auto"></div>
                        </div>
                    </div>
                </div>
            `;

            $('.form-step.active').html(`
                <div class="step-header">
                    <h2>📞 Devis personnalisé requis</h2>
                    <p>Votre consommation nécessite une étude sur-mesure</p>
                </div>
                <div id="high-consumption-message-container">
                    ${messageHtml}
                </div>
            `);

            setTimeout(() => {
                $('.progress-auto').css('width', '100%');
            }, 100);
        },

        sendToCalculator(userData) {
            const { configData } = GazProfessionnelState;
            const dataToSend = {
                action: 'htic_calculate_estimation',
                type: 'gaz-professionnel',
                user_data: userData,
                config_data: configData
            };

            if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
                dataToSend.nonce = hticSimulateur.nonce;
            }

            const ajaxUrl = (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl)
                ? hticSimulateur.ajaxUrl
                : '/wp-admin/admin-ajax.php';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: dataToSend,
                timeout: 30000,
                success: (response) => {
                    if (response.success) {
                        GazProfessionnelState.calculResults = response.data;
                        this.displayResults(response.data);
                    } else {
                        this.displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: (xhr, status, error) => {
                    let errorMessage = 'Erreur de connexion lors du calcul';

                    if (xhr.status === 0) {
                        errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur interne du serveur. Contactez l\'administrateur.';
                    } else if (status === 'timeout') {
                        errorMessage = 'Le calcul prend trop de temps. Réessayez.';
                    }

                    this.displayError(errorMessage);
                }
            });
        },

        displayResults(results) {
            if (!results || !results.consommation_annuelle) {
                this.displayError('Données de résultats incomplètes');
                return;
            }

            const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
            const totalAnnuel = Math.round(parseFloat(results.total_annuel || results.cout_annuel_ttc)) || 0;
            const totalMensuel = Math.round(totalAnnuel / 10);
            const typeGaz = results.type_gaz || 'Gaz';
            const trancheTarifaire = results.tranche_tarifaire || '--';
            const abonnementAnnuel = parseFloat(results.cout_abonnement || 0);
            const coutConsommation = parseFloat(results.cout_consommation || 0);
            const prixKwh = parseFloat(results.prix_kwh || 0);

            const isHighConsumption = consommationAnnuelle > 35000;
            const estimationWarning = isHighConsumption ? `
                <div class="estimation-warning">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <h4>Estimation indicative</h4>
                        <p>Pour une consommation de ${consommationAnnuelle.toLocaleString()} kWh/an, notre équipe commerciale vous contactera pour affiner cette estimation et vous proposer les meilleures conditions tarifaires.</p>
                    </div>
                </div>
            ` : '';

            const offreUnique = {
                nom: `${typeGaz} Pro ${trancheTarifaire}`,
                total_ttc: totalAnnuel,
                abonnement_annuel: abonnementAnnuel,
                cout_consommation: coutConsommation,
                details: `${prixKwh.toFixed(4)}€/kWh`,
                meilleure: true,
                needs_custom_quote: isHighConsumption
            };

            GazProfessionnelState.calculResults.offres = [offreUnique];
            GazProfessionnelState.calculResults.meilleure_offre = offreUnique;
            GazProfessionnelState.calculResults.needs_custom_quote = isHighConsumption;

            const resultsHtml = `
                <div class="results-summary-pro">
                    ${estimationWarning}
                    
                    <!-- Résultat principal -->
                    <div class="result-card main-result">
                        <div class="result-icon">🔥</div>
                        <h3>Votre estimation gaz professionnel</h3>
                        <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                        <div class="result-price">${totalAnnuel.toLocaleString()}€ <span>/an HTVA</span></div>
                        <p>Soit environ <strong>${totalMensuel.toLocaleString()}€/mois HTVA</strong> (sur 10 mois)</p>
                        <p class="tva-note">💡 + TVA 20% (non incluse)</p>
                        ${isHighConsumption ? '<p class="estimation-note">📞 Estimation sous réserve de validation commerciale</p>' : ''}
                    </div>
                    
                    <!-- Détails de l'offre unique -->
                    <div class="offer-details-unique">
                        <h3>💰 Votre tarif gaz professionnel (HTVA)</h3>
                        
                        <div class="tarif-card-unique recommended">
                            <div class="tarif-header">
                                <h4>${offreUnique.nom}</h4>
                                <span class="tarif-badge ${isHighConsumption ? 'estimate' : 'optimal'}">${isHighConsumption ? 'Estimation' : 'Tarif applicable'}</span>
                            </div>
                            <div class="tarif-prix">${totalAnnuel.toLocaleString()}€<span>/an HTVA</span></div>
                            <div class="tarif-mensuel">${totalMensuel.toLocaleString()}€/mois HTVA (sur 10 mois)</div>
                            <div class="tarif-details">
                                <div class="detail-row">
                                    <span class="detail-label">Abonnement annuel :</span>
                                    <span class="detail-value">${Math.round(abonnementAnnuel).toLocaleString()}€ HTVA</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Consommation annuelle :</span>
                                    <span class="detail-value">${Math.round(coutConsommation).toLocaleString()}€ HTVA</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Prix unitaire :</span>
                                    <span class="detail-value">${prixKwh.toFixed(4)}€/kWh</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tranche tarifaire :</span>
                                    <span class="detail-value">${trancheTarifaire}</span>
                                </div>
                            </div>
                            <div class="tarif-type-info">
                                <div class="type-icon">${typeGaz.includes('naturel') ? '🌱' : '⛽'}</div>
                                <div class="type-text">
                                    ${typeGaz.includes('naturel') ? 'Raccordé au réseau GRDF' : 'Citerne GPL sur site'}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations complémentaires -->
                        <div class="info-supplementaires">
                            <div class="info-card">
                                <div class="info-icon">📋</div>
                                <div class="info-content">
                                    <h4>Commune desservie</h4>
                                    <p>${results.commune || GazProfessionnelState.formData.commune || '--'}</p>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-icon">⚡</div>
                                <div class="info-content">
                                    <h4>Consommation prévisionnelle</h4>
                                    <p>${consommationAnnuelle.toLocaleString()} kWh/an</p>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-icon">💰</div>
                                <div class="info-content">
                                    <h4>Coût mensuel moyen</h4>
                                    <p>${totalMensuel.toLocaleString()}€ HTVA</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#results-container').html(resultsHtml);
            $('.results-summary-pro').hide().fadeIn(600);
        },

        displayError(message) {
            $('#results-container').html(`
                <div class="error-state">
                    <div class="error-icon">❌</div>
                    <h3>Erreur lors du calcul</h3>
                    <p>${message}</p>
                    <div class="error-actions">
                        <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    </div>
                </div>
            `);
        }
    };

    // ===========================================
    // GESTION DES ÉTAPES SPÉCIFIQUES
    // ===========================================

    const StepManager = {
        setupSelectionStep() {
            const { calculResults } = GazProfessionnelState;
            if (!calculResults.offres || calculResults.offres.length === 0) {
                return;
            }

            const offreUnique = calculResults.offres[0];

            if (offreUnique.nom.toLowerCase().includes('naturel')) {
                $('#tarif_naturel_pro').prop('checked', true);
                $('#tarif_propane_pro').closest('.tarif-card-selection').hide();
                $('#tarif_naturel_pro').closest('.tarif-card-selection').show();
                $('#prix-naturel-pro .price-amount').text(Math.round(offreUnique.total_ttc).toLocaleString());
            } else if (offreUnique.nom.toLowerCase().includes('propane')) {
                $('#tarif_propane_pro').prop('checked', true);
                $('#tarif_naturel_pro').closest('.tarif-card-selection').hide();
                $('#tarif_propane_pro').closest('.tarif-card-selection').show();
                $('#prix-propane-pro .price-amount').text(Math.round(offreUnique.total_ttc).toLocaleString());
            }
        },

        setupContactStep() {
            const toggleBtn = $('#btn-no-info-pro');
            const addressSection = $('#address-section-pro');

            toggleBtn.off('click').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                $(this).toggleClass('active');
                addressSection.toggleClass('show');

                const toggleText = $(this).find('.toggle-text');
                const toggleIcon = $(this).find('.toggle-icon');

                if ($(this).hasClass('active')) {
                    toggleIcon.text('×');
                    toggleText.text("Masquer l'adresse");
                } else {
                    toggleIcon.text('+');
                    toggleText.text("Je n'ai pas ces informations");
                }
            });
        },

        setupRecapStep() {
            RecapManager.generate();
        }
    };

    // ===========================================
    // GESTION DU RÉCAPITULATIF
    // ===========================================

    const RecapManager = {
        generate() {
            const allData = DataManager.collectAllFormData();
            const { calculResults, formData } = GazProfessionnelState;
            const isHighConsumption = calculResults.isHighConsumption || false;
            const consommation = calculResults.consommation_annuelle || formData.consommation_previsionnelle || 0;

            let recapHtml;
            if (isHighConsumption) {
                recapHtml = this.generateHighConsumptionRecap(allData, consommation);
            } else {
                recapHtml = this.generateNormalRecap(allData);
            }

            $('#recap-container-final-pro').html(recapHtml);

            $('#btn-finaliser-souscription-pro').off('click').on('click', () => {
                SubmissionManager.finalize();
            });
        },

        generateHighConsumptionRecap(allData, consommation) {
            const { calculResults } = GazProfessionnelState;
            const typeGaz = calculResults.type_gaz || 'Gaz naturel';

            return `
                <div class="recap-complet high-consumption-recap">
                    <div class="demande-personnalisee">
                        <div class="demande-header">
                            <span class="demande-icon">📞</span>
                            <h3>Votre demande de devis personnalisé</h3>
                            <span class="priority-badge">À finaliser</span>
                        </div>
                        
                        <div class="demande-details">
                            <div class="consumption-highlight">
                                <div class="consumption-badge">
                                    🔥 ${parseInt(consommation).toLocaleString()} kWh/an
                                </div>
                                <div class="consumption-text">
                                    <h4>Grande consommation ${typeGaz.toLowerCase()}</h4>
                                    <p>Nécessite une étude personnalisée pour optimiser vos coûts énergétiques</p>
                                </div>
                            </div>
                            
                            <div class="timeline">
                                <div class="timeline-item completed">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <h5>✅ Informations collectées</h5>
                                        <span>Vos données de consommation et entreprise sont prêtes</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-item next pending">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <h5>⏳ En attente de validation</h5>
                                        <span>Cliquez sur "Finaliser" pour confirmer votre demande de contact</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-item future">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <h5>📞 Contact sous 48h</h5>
                                        <span>Un expert vous appellera après validation de votre demande</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-item future">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <h5>📋 Devis personnalisé</h5>
                                        <span>Proposition commerciale avec les meilleures conditions du marché</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        generateNormalRecap(allData) {
            const { calculResults, formData } = GazProfessionnelState;
            const tarifChoisi = allData.tarif_choisi;
            const typeContrat = allData.type_contrat || 'principal';

            // Trouver l'offre sélectionnée
            let offreSelectionnee = null;
            if (calculResults.offres) {
                offreSelectionnee = calculResults.offres.find(offre => {
                    const nomOffre = offre.nom.toLowerCase();
                    if (tarifChoisi === 'naturel' && nomOffre.includes('naturel')) return true;
                    if (tarifChoisi === 'propane' && nomOffre.includes('propane')) return true;
                    return false;
                });
            }

            const totalAnnuel = offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc)) : 0;
            const totalMensuel = offreSelectionnee ? Math.round(totalAnnuel / 10) : 0;
            const tva = Math.round(totalAnnuel * 0.2);
            const totalTTC = totalAnnuel + tva;
            const consommation = calculResults.consommation_annuelle || formData.consommation_previsionnelle || 0;

            return `
                <div class="recap-complet">
                    <div class="formule-selectionnee">
                        <div class="formule-header">
                            <span class="formule-icon">🔥</span>
                            <h3>Votre formule gaz professionnelle</h3>
                        </div>
                        
                        <div class="formule-details">
                            <div class="formule-main">
                                <div class="formule-item tarif">
                                    <div class="formule-label">Offre sélectionnée</div>
                                    <div class="formule-value">${offreSelectionnee ? offreSelectionnee.nom : '--'}</div>
                                    <div class="formule-badge">${this.getTypeGazBadge(offreSelectionnee)}</div>
                                </div>
                                
                                <div class="formule-divider"></div>
                                
                                <div class="formule-item commune">
                                    <div class="formule-label">Commune</div>
                                    <div class="formule-value">${allData.commune || '--'}</div>
                                    <div class="formule-badge">Professionnelle</div>
                                </div>
                            </div>
                            
                            <div class="formule-costs">
                                <div class="cost-card annual">
                                    <div class="cost-icon">📅</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Coût annuel</div>
                                        <div class="cost-amount">${totalAnnuel.toLocaleString()}€ HTVA</div>
                                        <div class="cost-note">+ TVA 20% = ${totalTTC.toLocaleString()}€ TTC</div>
                                    </div>
                                </div>
                                
                                <div class="cost-card monthly">
                                    <div class="cost-icon">📆</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Moyenne mensuelle</div>
                                        <div class="cost-amount">${totalMensuel.toLocaleString()}€<span>/mois HTVA</span></div>
                                        <div class="cost-note">Sur 10 mois (hors TVA)</div>
                                    </div>
                                </div>
                                
                                <div class="cost-card consumption">
                                    <div class="cost-icon">🔥</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Consommation prévisionnelle</div>
                                        <div class="cost-amount">${parseInt(consommation).toLocaleString()} <span>kWh/an</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION ENTREPRISE -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">🏢</span>
                            Informations entreprise
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Raison sociale</span>
                                <span class="detail-value">${allData.raison_sociale || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Forme juridique</span>
                                <span class="detail-value">${allData.forme_juridique || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">N° SIRET</span>
                                <span class="detail-value">${Utils.formatSiret(allData.siret) || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Code NAF/APE</span>
                                <span class="detail-value">${allData.code_naf || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type de contrat</span>
                                <span class="detail-value highlight">
                                    ${typeContrat === 'principal' ? '🏢 Contrat principal' : '🏪 Site secondaire'}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION RESPONSABLE -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">👤</span>
                            Responsable du contrat
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nom complet</span>
                                <span class="detail-value">${allData.responsable_prenom} ${allData.responsable_nom}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email professionnel</span>
                                <span class="detail-value">${allData.responsable_email || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Téléphone</span>
                                <span class="detail-value">${Utils.formatPhone(allData.responsable_telephone) || '--'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fonction</span>
                                <span class="detail-value">${allData.responsable_fonction || 'Non précisée'}</span>
                            </div>
                        </div>
                        
                        ${allData.entreprise_adresse ? `
                        <div style="grid-column: 1 / -1; margin-top: 1rem;">
                            <div class="detail-item">
                                <span class="detail-label">Adresse du site</span>
                                <span class="detail-value">
                                    ${allData.entreprise_adresse}
                                    ${allData.entreprise_complement ? `<br>${allData.entreprise_complement}` : ''}
                                    <br>${allData.entreprise_code_postal} ${allData.entreprise_ville}
                                </span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- SECTION DOCUMENTS -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">📎</span>
                            Documents et validations
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Documents fournis</span>
                                <span class="detail-value">
                                    ${GazProfessionnelState.uploadedFiles.kbis_file ? '✅ K-bis<br>' : '❌ K-bis manquant<br>'}
                                    ${GazProfessionnelState.uploadedFiles.rib_entreprise ? '✅ RIB entreprise<br>' : '❌ RIB entreprise manquant<br>'}
                                    ${GazProfessionnelState.uploadedFiles.mandat_signature ? '✅ Mandat de signature' : 'ℹ️ Mandat de signature (optionnel)'}
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Conditions acceptées</span>
                                <span class="detail-value ${allData.accept_conditions_pro ? 'success' : 'error'}">
                                    ${allData.accept_conditions_pro ? '✅' : '❌'} Conditions générales professionnelles
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Prélèvement automatique</span>
                                <span class="detail-value">
                                    ${allData.accept_prelevement_pro ? '✅ Autorisé' : 'ℹ️ Non autorisé (autre moyen de paiement)'}
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Pouvoir d'engagement</span>
                                <span class="detail-value ${allData.certifie_pouvoir ? 'success' : 'error'}">
                                    ${allData.certifie_pouvoir ? '✅ Certifié' : '❌ Non certifié'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        getTypeGazBadge(offre) {
            if (!offre) return 'Standard';
            const nom = offre.nom.toLowerCase();
            if (nom.includes('naturel')) return 'Gaz naturel';
            if (nom.includes('propane')) return 'Gaz propane';
            return 'Professionnel';
        }
    };

    // ===========================================
    // SOUMISSION FINALE
    // ===========================================

    const SubmissionManager = {
        finalize() {
            const allData = DataManager.collectAllFormData();
            const { uploadedFiles } = GazProfessionnelState;

            if (!ValidationManager.validateCurrentStep()) {
                UIManager.showValidationMessage('Veuillez vérifier toutes les informations obligatoires');
                return;
            }

            if (!allData.raison_sociale || !allData.siret || !allData.responsable_email) {
                UIManager.showValidationMessage('Informations entreprise incomplètes');
                return;
            }

            if (!uploadedFiles.kbis_file || !uploadedFiles.rib_entreprise) {
                UIManager.showValidationMessage('Documents obligatoires manquants (K-bis et RIB)');
                return;
            }

            if (!allData.accept_conditions_pro || !allData.certifie_pouvoir) {
                UIManager.showValidationMessage('Veuillez accepter les conditions obligatoires');
                return;
            }

            this.sendToServer();
        },

        sendToServer() {
            const { formData, calculResults, uploadedFiles } = GazProfessionnelState;
            const allFormData = DataManager.collectAllFormData();

            const formDataToSend = new FormData();

            formDataToSend.append('action', 'process_gaz_form');
            formDataToSend.append('nonce', hticSimulateur.nonce);

            const dataToSend = {
                simulationType: 'gaz-professionnel',

                raison_sociale: allFormData.raison_sociale || '',
                forme_juridique: allFormData.forme_juridique || '',
                siret: allFormData.siret || '',
                code_naf: allFormData.code_naf || '',

                entreprise_adresse: allFormData.entreprise_adresse || '',
                entreprise_code_postal: allFormData.entreprise_code_postal || '',
                entreprise_ville: allFormData.entreprise_ville || '',

                responsable_prenom: allFormData.responsable_prenom || '',
                responsable_nom: allFormData.responsable_nom || '',
                responsable_email: allFormData.responsable_email || '',
                responsable_telephone: allFormData.responsable_telephone || '',
                responsable_fonction: allFormData.responsable_fonction || '',

                commune: allFormData.commune || '',
                consommation_previsionnelle: parseInt(allFormData.consommation_previsionnelle) || 0,
                type_contrat: allFormData.type_contrat || 'principal',
                tarif_choisi: allFormData.tarif_choisi || '',

                type_gaz_autre: allFormData.type_gaz_autre || null,
                nom_commune_autre: allFormData.nom_commune_autre || null,

                cout_annuel: calculResults.isHighConsumption ? 0 : (calculResults.total_annuel || 0),

                accept_conditions_pro: Boolean(allFormData.accept_conditions_pro),
                accept_prelevement_pro: Boolean(allFormData.accept_prelevement_pro),
                certifie_pouvoir: Boolean(allFormData.certifie_pouvoir),

                timestamp: new Date().toISOString(),
                reference: 'GAZ-PRO-' + Date.now()
            };

            formDataToSend.append('form_data', JSON.stringify(dataToSend));

            let filesAdded = 0;

            if (uploadedFiles.kbis_file && uploadedFiles.kbis_file instanceof File) {
                formDataToSend.append('kbis_file', uploadedFiles.kbis_file);
                filesAdded++;
            }

            if (uploadedFiles.rib_entreprise && uploadedFiles.rib_entreprise instanceof File) {
                formDataToSend.append('rib_entreprise', uploadedFiles.rib_entreprise);
                filesAdded++;
            }

            if (uploadedFiles.mandat_signature && uploadedFiles.mandat_signature instanceof File) {
                formDataToSend.append('mandat_signature', uploadedFiles.mandat_signature);
                filesAdded++;
            }

            if (!hticSimulateur?.ajaxUrl) {
                UIManager.showErrorMessage('Configuration manquante');
                return;
            }

            if (!hticSimulateur?.nonce) {
                UIManager.showErrorMessage('Token de sécurité manquant');
                return;
            }

            UIManager.showLoader();

            $.ajax({
                url: hticSimulateur.ajaxUrl,
                type: 'POST',
                data: formDataToSend,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 60000,
                success: (response) => {
                    UIManager.hideLoader();

                    if (response && response.success) {
                        UIManager.showSuccessMessage(
                            response.data?.referenceNumber ||
                            dataToSend.reference ||
                            'GAZ-PRO-' + Date.now()
                        );

                        setTimeout(() => {
                            window.location.href = '/merci';
                        }, 2000);
                    } else {
                        UIManager.showErrorMessage(
                            response?.data ||
                            response?.message ||
                            'Erreur inconnue du serveur'
                        );
                    }
                },
                error: (xhr, status, error) => {
                    UIManager.hideLoader();

                    let errorMessage = 'Erreur de connexion au serveur';

                    if (xhr.status === 0) {
                        errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                    } else if (xhr.status === 400) {
                        errorMessage = 'Données invalides (erreur 400)';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Accès refusé (erreur 403) - Problème de sécurité';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Endpoint non trouvé (erreur 404)';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur interne du serveur (erreur 500)';
                    } else if (status === 'timeout') {
                        errorMessage = 'Le traitement prend trop de temps (timeout)';
                    } else {
                        errorMessage = `Erreur ${xhr.status}: ${error}`;
                    }

                    UIManager.showErrorMessage(errorMessage);
                }
            });
        }
    };

    // ===========================================
    // GESTION DE L'INTERFACE
    // ===========================================

    const UIManager = {
        showValidationMessage(message) {
            $('.validation-message').remove();
            const $message = $(`<div class="validation-message error">${message}</div>`);
            $('.form-step.active .step-header').after($message);

            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 4000);
        },

        showLoader() {
            if ($('#ajax-loader-gaz-pro').length) return;

            const loader = `
                <div id="ajax-loader-gaz-pro" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                            background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 99999;">
                    <div style="background: white; padding: 50px; border-radius: 15px; text-align: center; 
                                box-shadow: 0 15px 50px rgba(0,0,0,0.4); max-width: 400px;">
                        <div class="spinner-gaz-pro" style="border: 6px solid #f3f3f3; border-top: 6px solid #FF6B35; 
                                    border-radius: 50%; width: 80px; height: 80px; 
                                    animation: spin 1s linear infinite; margin: 0 auto 25px;"></div>
                        <h3 style="margin: 0 0 15px 0; color: #FF6B35; font-size: 20px;">Traitement en cours...</h3>
                        <p style="margin: 0; font-size: 16px; color: #666; line-height: 1.5;">
                            <strong>Génération du devis gaz professionnel</strong><br>
                            Création du PDF et envoi des emails
                        </p>
                    </div>
                </div>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            $('body').append(loader);
        },

        hideLoader() {
            $('#ajax-loader-gaz-pro').fadeOut(400, function () {
                $(this).remove();
            });
        },

        showSuccessMessage(referenceNumber) {
            $('.ajax-message').remove();
            const successHtml = `
                <div class="ajax-message success-message-gaz" style="position: fixed; top: 20px; right: 20px; 
                            background: linear-gradient(135deg, #82C720 0%, #82C720 100%); color: white; 
                            padding: 20px 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(255, 255, 255, 0.2); 
                            z-index: 100000; max-width: 400px;">
                    <div style="display: flex; align-items: center;">
                        <span style="font-size: 24px; margin-right: 15px;">✅</span>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 16px; color: white;">Devis gaz envoyé !</h4>
                            <p style="margin: 0; font-size: 14px; opacity: 0.95;">Référence: ${referenceNumber}</p>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(successHtml);

            setTimeout(() => {
                $('.success-message-gaz').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 2000);
        },

        showErrorMessage(message) {
            $('.ajax-message').remove();
            const errorHtml = `
                <div class="ajax-message error-message-gaz-pro" style="position: fixed; top: 20px; right: 20px; 
                            background: #DC2626; color: white; padding: 25px; border-radius: 12px; 
                            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3); z-index: 100000; max-width: 450px;">
                    <div style="display: flex; align-items: center;">
                        <span style="font-size: 28px; margin-right: 15px;">❌</span>
                        <div>
                            <h4 style="margin: 0 0 8px 0; font-size: 18px;">Erreur d'envoi gaz</h4>
                            <p style="margin: 0; font-size: 14px;">${message}</p>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(errorHtml);

            setTimeout(() => {
                $('.error-message-gaz-pro').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 8000);
        }
    };

    // ===========================================
    // LOGIQUE MÉTIER SPÉCIFIQUE
    // ===========================================

    const BusinessLogic = {
        init() {
            this.bindBusinessEvents();
        },

        bindBusinessEvents() {
            $('#commune').on('change', () => CommunesManager.handleSelection());
            $('#consommation_previsionnelle').on('input', this.handleConsumptionInput);
            $('#pas_info').on('change', this.handleTechnicalInfoCheckbox);
            $('#siret').on('input', this.handleSiretInput);
        },

        handleConsumptionInput() {
            const value = parseFloat($(this).val());
            const $helpText = $(this).closest('.form-group').find('.field-help');

            if (value > 0 && value < 5000) {
                $helpText.html('💡 <strong>Très petite consommation</strong> - Tarif P0/GOM0');
            } else if (value >= 5000 && value < 15000) {
                $helpText.html('💡 <strong>Petite entreprise</strong> - Tarif adapté aux commerces');
            } else if (value >= 15000 && value < 35000) {
                $helpText.html('💡 <strong>PME</strong> - Tarif optimisé pour les moyens consommateurs');
            } else if (value >= 35000 && value < 100000) {
                $helpText.html('⚠️ <strong>Grande consommation</strong> - Un devis personnalisé sera établi');
            } else if (value >= 100000) {
                $helpText.html('🏭 <strong>Très grande consommation</strong> - Offre sur mesure requise');
            }
        },

        handleTechnicalInfoCheckbox() {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
                $('#num_prm').prop('disabled', true).val('');
            } else {
                $('#point_livraison').prop('disabled', false);
                $('#num_prm').prop('disabled', false);
            }
        },

        handleSiretInput() {
            let value = $(this).val().replace(/\s/g, '');
            if (value.length > 14) {
                value = value.substr(0, 14);
            }
            $(this).val(value);

            if (value.length === 14) {
                $(this).removeClass('field-error').addClass('field-success');
            } else if (value.length > 0) {
                $(this).removeClass('field-success').addClass('field-error');
            } else {
                $(this).removeClass('field-error field-success');
            }
        }
    };

    // ===========================================
    // UTILITAIRES
    // ===========================================

    const Utils = {
        formatSiret(siret) {
            if (!siret || siret.length !== 14) return siret;
            return siret.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4');
        },

        formatPhone(phone) {
            if (!phone) return null;
            const cleaned = phone.replace(/\D/g, '');
            if (cleaned.length === 10) {
                return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
            }
            return phone;
        }
    };

    // ===========================================
    // INITIALISATION PRINCIPALE
    // ===========================================

    function init() {
        GazProfessionnelConfig.init();
        CommunesManager.init();
        NavigationManager.init();
        ValidationManager.init();
        FileManager.init();
        BusinessLogic.init();
    }

    // ===========================================
    // API PUBLIQUE
    // ===========================================

    window.HticGazProfessionnelData = {
        getCurrentData: () => GazProfessionnelState.formData,
        getAllData: () => DataManager.collectAllFormData(),
        getConfig: () => GazProfessionnelState.configData,
        getCurrentStep: () => GazProfessionnelState.currentStep,
        goToStep: (step) => NavigationManager.goToStep(step),
        validateStep: () => ValidationManager.validateCurrentStep(),
        restart: () => NavigationManager.restartSimulation(),
        getResults: () => GazProfessionnelState.calculResults,
        getUploadedFiles: () => GazProfessionnelState.uploadedFiles
    };

    // Fonction globale pour compatibilité avec les templates
    window.removeUploadedFileGaz = (fileType, button) => FileManager.removeFile(fileType, button);

    // Lancement de l'application
    init();
});