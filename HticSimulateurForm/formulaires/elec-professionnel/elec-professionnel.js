// elec-professionnel.js - JavaScript pour le formulaire professionnel simplifié

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let configData = {};

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupDynamicLogic();
        setupFileUpload();
    }

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config-pro');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration Pro chargée:', configData);
            } catch (e) {
                console.error('❌ Erreur configuration Pro:', e);
                configData = {};
            }
        }
    }

    // ===============================
    // NAVIGATION
    // ===============================

    function setupStepNavigation() {
        $('#btn-next-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                goToNextStep();
            }
        });

        $('#btn-previous-pro').on('click', function () {
            saveCurrentStepData();
            goToPreviousStep();
        });

        $('#btn-calculate-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        });

        $('#btn-restart-pro').on('click', function () {
            if (confirm('Voulez-vous vraiment recommencer ?')) {
                restartSimulation();
            }
        });
    }

    function goToNextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateProgress();
            updateNavigation();
        }
    }

    function goToPreviousStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
            updateProgress();
            updateNavigation();
        }
    }

    function showStep(stepNumber) {
        $('.form-step').removeClass('active');
        $(`.form-step[data-step="${stepNumber}"]`).addClass('active');

        $('.step').removeClass('active');
        $(`.step[data-step="${stepNumber}"]`).addClass('active');
    }

    function updateProgress() {
        const progressPercent = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');
    }

    function updateNavigation() {
        $('#btn-previous-pro').toggle(currentStep > 1);

        if (currentStep === totalSteps) {
            $('#btn-next-pro, #btn-calculate-pro').hide();
            $('#btn-restart-pro').show();
        } else if (currentStep === totalSteps - 1) {
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').show();
            $('#btn-restart-pro').hide();
        } else {
            $('#btn-next-pro').show();
            $('#btn-calculate-pro, #btn-restart-pro').hide();
        }
    }

    // ===============================
    // LOGIQUE DYNAMIQUE
    // ===============================

    function setupDynamicLogic() {
        // Gestion catégorie et éligibilité TRV
        $('input[name="categorie"]').on('change', function () {
            const categorie = $(this).val();

            // Auto-update éligibilité TRV
            if (categorie === 'BT < 36 kVA') {
                $('input[name="eligible_trv"][value="oui"]').prop('checked', true);
                $('input[name="eligible_trv"]').closest('.form-group').removeClass('disabled');
            } else {
                $('input[name="eligible_trv"][value="non"]').prop('checked', true);
                $('input[name="eligible_trv"]').closest('.form-group').addClass('disabled');
            }

            // Limiter les options de puissance selon la catégorie
            updatePuissanceOptions(categorie);
        });

        // Checkbox "Je n'ai pas l'information"
        $('#pas_info').on('change', function () {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
            } else {
                $('#point_livraison').prop('disabled', false);
            }
        });
    }

    function updatePuissanceOptions(categorie) {
        const $select = $('#puissance');
        $select.find('option').show();

        if (categorie === 'BT > 36 kVA') {
            $select.find('option').each(function () {
                const val = parseInt($(this).val());
                if (val && val <= 36) {
                    $(this).hide();
                }
            });
        }
    }

    // ===============================
    // UPLOAD FICHIER
    // ===============================

    function setupFileUpload() {
        $('.file-input').on('change', function () {
            const fileName = $(this).val().split('\\').pop();
            const $label = $(this).siblings('.file-label');

            if (fileName) {
                $label.find('.file-name').text(fileName);
                $label.addClass('file-selected');
            } else {
                $label.find('.file-name').text('Aucun fichier choisi');
                $label.removeClass('file-selected');
            }
        });
    }

    // ===============================
    // VALIDATION
    // ===============================

    function setupFormValidation() {
        // Validation SIRET
        $('#siret').on('blur', function () {
            const siret = $(this).val().replace(/\s/g, '');
            if (siret.length !== 14) {
                $(this).addClass('field-error');
                showValidationMessage('Le SIRET doit contenir 14 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation Code Postal
        $('#code_postal').on('blur', function () {
            const cp = $(this).val();
            if (!/^[0-9]{5}$/.test(cp)) {
                $(this).addClass('field-error');
                showValidationMessage('Le code postal doit contenir 5 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation Email
        $('#email').on('blur', function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $(this).addClass('field-error');
                showValidationMessage('Email invalide');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });
    }

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        // Retirer les classes d'erreur existantes
        currentStepElement.find('.field-error').removeClass('field-error');

        // Validation par étape
        switch (currentStep) {
            case 1: // Configuration
                isValid = validateStep1(currentStepElement);
                break;
            case 2: // Localisation
                isValid = validateStep2(currentStepElement);
                break;
            case 3: // Titulaire
                isValid = validateStep3(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires');
        }

        return isValid;
    }

    function validateStep1(stepElement) {
        let isValid = true;

        // Catégorie
        if (!stepElement.find('input[name="categorie"]:checked').length) {
            isValid = false;
        }

        // Puissance
        const puissance = stepElement.find('#puissance').val();
        if (!puissance) {
            stepElement.find('#puissance').addClass('field-error');
            isValid = false;
        }

        // Formule tarifaire
        if (!stepElement.find('input[name="formule_tarifaire"]:checked').length) {
            isValid = false;
        }

        // Consommation
        const conso = parseInt(stepElement.find('#conso_annuelle').val());
        if (!conso || conso < 1000) {
            stepElement.find('#conso_annuelle').addClass('field-error');
            isValid = false;
        }

        return isValid;
    }

    function validateStep2(stepElement) {
        let isValid = true;

        // Adresse obligatoire
        if (!stepElement.find('#adresse').val()) {
            stepElement.find('#adresse').addClass('field-error');
            isValid = false;
        }

        // Code postal obligatoire
        if (!stepElement.find('#code_postal').val()) {
            stepElement.find('#code_postal').addClass('field-error');
            isValid = false;
        }

        // Ville obligatoire
        if (!stepElement.find('#ville').val()) {
            stepElement.find('#ville').addClass('field-error');
            isValid = false;
        }

        return isValid;
    }

    function validateStep3(stepElement) {
        let isValid = true;

        const requiredFields = [
            'nom', 'prenom', 'raison_sociale',
            'forme_juridique', 'siret', 'code_naf',
            'email', 'telephone'
        ];

        requiredFields.forEach(field => {
            const $field = stepElement.find(`#${field}`);
            if (!$field.val()) {
                $field.addClass('field-error');
                isValid = false;
            }
        });

        return isValid;
    }

    // ===============================
    // COLLECTE DES DONNÉES
    // ===============================

    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        currentStepElement.find('input, select').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name) return;

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[name] = $field.val();
                }
            } else if (type === 'checkbox') {
                formData[name] = $field.is(':checked');
            } else if (type === 'file') {
                if ($field[0].files.length > 0) {
                    formData[name] = $field[0].files[0].name;
                }
            } else {
                formData[name] = $field.val();
            }
        });

        console.log('📝 Données sauvegardées étape', currentStep, ':', formData);
    }

    function collectAllFormData() {
        formData = {};

        $('.form-step').each(function () {
            $(this).find('input, select').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (!name) return;

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                } else if (type === 'checkbox') {
                    formData[name] = $field.is(':checked');
                } else if (type !== 'file') {
                    formData[name] = $field.val();
                }
            });
        });

        return formData;
    }

    // ===============================
    // CALCUL DES RÉSULTATS
    // ===============================

    function calculateResults() {
        const allData = collectAllFormData();

        console.log('🚀 Données envoyées:', allData);

        // Afficher l'étape des résultats
        showStep(4);
        updateProgress();
        updateNavigation();

        // Simuler le calcul
        setTimeout(() => {
            displayResults(allData);
        }, 1500);
    }

    function displayResults(data) {
        // Calculs simples basés sur les données Excel
        const consommation = parseInt(data.conso_annuelle) || 0;
        const puissance = parseInt(data.puissance) || 0;

        // Tarifs moyens (à adapter selon vos données)
        const tarifBase = 0.2516;
        const tarifHC_HP = 0.2700;
        const tarifHC_HC = 0.2068;

        // Abonnements annuels par puissance
        const abonnements = {
            3: 113.64 * 12,
            6: 151.20 * 12,
            9: 189.48 * 12,
            12: 228.48 * 12,
            15: 267.96 * 12,
            18: 305.28 * 12,
            24: 381.12 * 12,
            30: 459.33 * 12,
            36: 537.84 * 12
        };

        const abonnementAnnuel = abonnements[puissance] || 0;

        let coutTotal = 0;

        if (data.formule_tarifaire === 'Base') {
            coutTotal = (consommation * tarifBase) + abonnementAnnuel;
        } else {
            // Heures Creuses : 40% HC, 60% HP
            const consoHC = consommation * 0.4;
            const consoHP = consommation * 0.6;
            coutTotal = (consoHC * tarifHC_HC) + (consoHP * tarifHC_HP) + abonnementAnnuel;
        }

        const resultsHtml = `
            <div class="results-summary">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">⚡</div>
                    <h3>Estimation pour votre entreprise</h3>
                    <div class="big-number">${coutTotal.toFixed(0).toLocaleString()} <span>€/an</span></div>
                    <p>Soit environ <strong>${(coutTotal / 12).toFixed(0).toLocaleString()}€/mois</strong></p>
                </div>

                <!-- Détails -->
                <div class="pro-recap-table">
                    <div class="pro-recap-header">
                        <h3>📋 Récapitulatif de votre simulation</h3>
                    </div>
                    <div class="pro-recap-body">
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Entreprise</span>
                            <span class="pro-recap-value">${data.raison_sociale || 'Non renseigné'}</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">SIRET</span>
                            <span class="pro-recap-value">${data.siret || 'Non renseigné'}</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Catégorie</span>
                            <span class="pro-recap-value">${data.categorie}</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Puissance souscrite</span>
                            <span class="pro-recap-value">${data.puissance} kVA</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Formule tarifaire</span>
                            <span class="pro-recap-value">${data.formule_tarifaire}</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Consommation annuelle</span>
                            <span class="pro-recap-value">${consommation.toLocaleString()} kWh</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Coût consommation</span>
                            <span class="pro-recap-value">${(coutTotal - abonnementAnnuel).toFixed(0).toLocaleString()}€</span>
                        </div>
                        <div class="pro-recap-row">
                            <span class="pro-recap-label">Abonnement annuel</span>
                            <span class="pro-recap-value">${abonnementAnnuel.toFixed(0).toLocaleString()}€</span>
                        </div>
                        <div class="pro-recap-row total">
                            <span class="pro-recap-label">Total annuel TTC</span>
                            <span class="pro-recap-value">${coutTotal.toFixed(0).toLocaleString()}€</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pro-actions">
                    <button class="btn btn-primary" onclick="window.print()">📄 Imprimer</button>
                    <button class="btn btn-success" onclick="alert('Fonction contact en développement')">📞 Être contacté</button>
                    <button class="btn btn-secondary" onclick="location.reload()">🔄 Nouvelle simulation</button>
                </div>
            </div>
        `;

        $('#results-container-pro').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
    }

    // ===============================
    // UTILITAIRES
    // ===============================

    function showValidationMessage(message) {
        $('.validation-message').remove();

        const $message = $(`<div class="validation-message">${message}</div>`);
        $('.form-step.active .step-header').after($message);

        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 3000);
    }

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        $('#simulateur-elec-professionnel')[0].reset();
        showStep(1);
        updateProgress();
        updateNavigation();
        $('.field-error, .field-success').removeClass('field-error field-success');
    }

});