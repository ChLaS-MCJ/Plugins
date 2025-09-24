/**
 * SIMULATEUR ÉLECTRICITÉ RÉSIDENTIEL
 * Plugin WordPress pour GES Solutions
 * 
 * Gère le processus complet de simulation :
 * - Navigation entre les 10 étapes
 * - Validation des données utilisateur
 * - Calculs de consommation et tarifs
 * - Génération de PDF et envoi d'emails
 * - Interface utilisateur interactive
 * 
 * @version 1.0.0
 * @author HTIC / GES Solutions
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // ================================
        // VARIABLES GLOBALES
        // ================================

        let currentStep = 1;
        const totalSteps = 10;
        let formData = {};
        let configData = {};
        let calculationResults = null;

        // ================================
        // INITIALISATION
        // ================================

        init();

        function init() {
            loadConfigData();
            setupEventListeners();
            updateUI();
        }

        function loadConfigData() {
            const configElement = document.getElementById('simulateur-config');
            if (configElement) {
                try {
                    configData = JSON.parse(configElement.textContent);
                } catch (e) {
                    console.error('Erreur configuration:', e);
                    configData = {};
                }
            }
        }

        // ================================
        // GESTION DES ÉVÉNEMENTS
        // ================================

        function setupEventListeners() {
            // Navigation principale
            $('#btn-next').on('click', handleNext);
            $('#btn-previous').on('click', handlePrevious);
            $('#btn-calculate').on('click', handleCalculate);
            $('#btn-restart').on('click', handleRestart);

            // Navigation par étapes
            $('.step').on('click', function () {
                const targetStep = parseInt($(this).data('step'));
                if (targetStep < currentStep || targetStep === 1) {
                    saveCurrentStepData();
                    goToStep(targetStep);
                }
            });

            // Validation temps réel
            $('input[required], select[required]').on('blur', function () {
                validateField($(this));
            });

            $('input[type="number"]').on('input', function () {
                validateNumberField($(this));
            });

            // Chauffage électrique
            $('input[name="chauffage_electrique"]').on('change', handleChauffageChange);

            // Actions des résultats
            $(document).on('click', '#btn-subscribe', function () {
                goToStep(8);
            });

            // Sélections étape 8
            $(document).on('change', 'input[name="tarif_choisi"]', handleTarifChange);
            $(document).on('change click', 'input[name="puissance_choisie"]', handlePuissanceChange);

            // Prévenir la soumission du formulaire
            $('#simulateur-elec-residentiel').on('submit', function (e) {
                e.preventDefault();
                return false;
            });

            // Fermeture des messages d'erreur
            $(document).on('click', '.error-message .close-btn', function () {
                $(this).closest('.error-message').remove();
            });
        }

        // ================================
        // HANDLERS D'ÉVÉNEMENTS
        // ================================

        function handleNext() {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                goToNextStep();
            }
        }

        function handlePrevious() {
            saveCurrentStepData();
            goToPreviousStep();
        }

        function handleCalculate() {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        }

        function handleRestart() {
            if (confirm('Voulez-vous vraiment recommencer la simulation ?')) {
                restartSimulation();
            }
        }

        function handleChauffageChange() {
            const value = $(this).val();
            const detailsSection = $('#chauffage-details');

            if (value === 'oui') {
                detailsSection.show();
                detailsSection.find('input[required]').attr('required', true);
            } else {
                detailsSection.hide();
                detailsSection.find('input').prop('checked', false).attr('required', false);
            }
        }

        function handleTarifChange() {
            const tarif = $(this).val();
            const puissance = $('input[name="puissance_choisie"]:checked').val();

            if (tarif && puissance) {
                updateCalculsSelection(tarif, puissance);
            }
        }

        function handlePuissanceChange() {
            const puissance = parseInt($(this).val());
            const tarif = $('input[name="tarif_choisi"]:checked').val();

            updateTarifVisibility(puissance);

            if (tarif && puissance) {
                recalculateWithNewPower(tarif, puissance);
            }
        }

        // ================================
        // NAVIGATION ENTRE ÉTAPES
        // ================================

        function goToNextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateUI();

                if (currentStep === 8) setupSelectionStep();
                else if (currentStep === 9) initStep9();
                else if (currentStep === 10) setupRecapStep();
            }
        }

        function goToPreviousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        }

        function goToStep(stepNumber) {
            if (stepNumber >= 1 && stepNumber <= totalSteps) {
                currentStep = stepNumber;
                updateUI();

                if (stepNumber === 8) setupSelectionStep();
                else if (stepNumber === 9) initStep9();
                else if (stepNumber === 10) setupRecapStep();
            }
        }

        function updateUI() {
            showStep(currentStep);
            updateProgress();
            updateNavigation();
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
            $('#btn-previous').toggle(currentStep > 1);
            $('#btn-next, #btn-calculate, #btn-restart, #btn-finalize').hide();
            $('.results-actions').hide();

            switch (currentStep) {
                case 6:
                    $('#btn-calculate').show();
                    break;
                case 7:
                    $('#btn-restart').show();
                    $('.results-actions').show();
                    break;
                case 8:
                case 9:
                    $('#btn-next').show();
                    break;
                case 10:
                    $('#btn-finalize').show();
                    break;
                default:
                    $('#btn-next').show();
            }
        }

        // ================================
        // CONFIGURATION ÉTAPE 8 (SÉLECTION)
        // ================================

        function setupSelectionStep() {
            if (currentStep === 8 && window.calculationResults) {
                generateSimplifiedPuissanceOptions();
                preselectRecommendedOptions();
            }
        }

        function generateSimplifiedPuissanceOptions() {
            const results = window.calculationResults;
            const puissanceRecommandee = parseInt(results.puissance_recommandee) || 12;
            const puissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];
            const $container = $('.puissance-selection');

            let html = '<div class="puissance-grid-simple">';

            puissances.forEach(puissance => {
                const isRecommended = puissance === puissanceRecommandee;
                const starIcon = isRecommended ? '<span class="star-icon">⭐</span>' : '';

                html += `
                    <div class="puissance-card ${isRecommended ? 'recommended' : ''}">
                        <input type="radio" 
                               id="puissance_${puissance}" 
                               name="puissance_choisie" 
                               value="${puissance}">
                        <label for="puissance_${puissance}">
                            ${starIcon}
                            <div class="puissance-value">${puissance}</div>
                            <div class="puissance-unit">kVA</div>
                        </label>
                    </div>
                `;
            });

            html += '</div>';
            $container.html(html);
        }

        function preselectRecommendedOptions() {
            const results = window.calculationResults;
            const puissanceRecommandee = parseInt(results.puissance_recommandee) || 12;

            updateTarifVisibility(puissanceRecommandee);

            const tarifs = getTarifsDisponibles(puissanceRecommandee);
            let tarifRecommande = 'base';
            let tarifMin = Infinity;

            tarifs.forEach(tarifKey => {
                const tarif = results.tarifs[tarifKey];
                const total = parseInt(tarif.total_annuel) || 0;
                if (total < tarifMin) {
                    tarifMin = total;
                    tarifRecommande = tarifKey;
                }
            });

            updateAllTarifPrices(results);

            setTimeout(() => {
                $('input[name="tarif_choisi"]').prop('checked', false);
                $('input[name="puissance_choisie"]').prop('checked', false);

                $(`input[name="tarif_choisi"][value="${tarifRecommande}"]`).prop('checked', true);
                $(`input[name="puissance_choisie"][value="${puissanceRecommandee}"]`).prop('checked', true);

                updateCalculsSelection(tarifRecommande, puissanceRecommandee);
            }, 100);
        }

        function updateTarifVisibility(puissance) {
            const $tarifBase = $('.tarif-card-selection').has('input[value="base"]');
            const $inputBase = $('input[name="tarif_choisi"][value="base"]');

            if (puissance > 6) {
                $tarifBase.hide();

                if ($inputBase.is(':checked')) {
                    $inputBase.prop('checked', false);
                    $('input[name="tarif_choisi"][value="hc"]').prop('checked', true);

                    const tarif = 'hc';
                    if (window.calculationResults) {
                        updateCalculsSelection(tarif, puissance);
                        recalculateWithNewPower(tarif, puissance);
                    }
                }
            } else {
                $tarifBase.show();
            }
        }

        function getTarifsDisponibles(puissance) {
            if (puissance > 6) {
                return ['hc', 'tempo'];
            } else {
                return ['base', 'hc', 'tempo'];
            }
        }

        // ================================
        // CONFIGURATION ÉTAPE 9 (INFORMATIONS CLIENT)
        // ================================

        function initStep9() {
            // Toggle pour l'adresse
            const toggleBtn = document.getElementById('btn-no-info');
            const addressSection = document.getElementById('address-section');

            if (toggleBtn && addressSection) {
                $(toggleBtn).off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    $(this).toggleClass('active');
                    $(addressSection).toggleClass('show');

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
            }

            // Gestion upload de fichiers
            $('.form-step[data-step="9"] .upload-card').each(function () {
                setupFileUpload($(this));
            });

            // Validation des champs
            setupFieldValidation();
        }

        function setupFileUpload($card) {
            const trigger = $card.find('.upload-trigger');
            const fileInput = $card.find('input[type="file"]');
            const resultDiv = $card.find('.upload-result');

            if (trigger.length && fileInput.length) {
                trigger.off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.click();
                });

                $card.off('click').on('click', function (e) {
                    if (!$(e.target).is(trigger) && !$(e.target).is(fileInput)) {
                        e.preventDefault();
                        fileInput.click();
                    }
                });

                fileInput.off('change').on('change', function () {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 5 * 1024 * 1024;

                        if (file.size > maxSize) {
                            $card.removeClass('has-file');
                            resultDiv.text('❌ Fichier trop volumineux').addClass('error');
                            this.value = '';
                            return;
                        }

                        $card.addClass('has-file');
                        let fileName = file.name;
                        if (fileName.length > 20) {
                            fileName = fileName.substring(0, 17) + '...';
                        }
                        resultDiv.text('✅ ' + fileName).addClass('success').removeClass('error');
                    } else {
                        $card.removeClass('has-file');
                        resultDiv.text('');
                    }
                });
            }
        }

        function setupFieldValidation() {
            // Email
            $('#client_email').off('blur').on('blur', function () {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    $(this).addClass('field-error');
                } else {
                    $(this).removeClass('field-error');
                }
            });

            // Téléphone
            $('#client_telephone').off('input').on('input', function () {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    const parts = value.match(/.{1,2}/g);
                    if (parts) {
                        this.value = parts.slice(0, 5).join(' ');
                    }
                }
            });

            // Code postal
            $('#client_code_postal').off('input').on('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 5);
            });
        }

        function setupRecapStep() {
            if (currentStep === 10) {
                generateRecapitulatifFinal();
            }
        }

        // ================================
        // VALIDATION DES DONNÉES
        // ================================

        function validateCurrentStep() {
            const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
            let isValid = true;

            currentStepElement.find('.field-error, .field-success').removeClass('field-error field-success');

            switch (currentStep) {
                case 1: isValid = validateStep1(currentStepElement); break;
                case 2: isValid = validateStep2(currentStepElement); break;
                case 3: isValid = validateStep3(currentStepElement); break;
                case 4: isValid = validateStep4(currentStepElement); break;
                case 5: isValid = validateStep5(currentStepElement); break;
                case 6: isValid = validateStep6(currentStepElement); break;
                case 8: isValid = validateStep8(currentStepElement); break;
                case 9: isValid = validateStep9(currentStepElement); break;
                default: isValid = true;
            }

            if (!isValid) {
                showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
            }

            return isValid;
        }

        function validateStep1(stepElement) {
            let isValid = true;

            if (!stepElement.find('input[name="type_logement"]:checked').length) {
                isValid = false;
            }

            const surface = stepElement.find('#surface');
            const surfaceValue = parseInt(surface.val());
            if (!surfaceValue || surfaceValue < 20 || surfaceValue > 500) {
                surface.addClass('field-error');
                isValid = false;
            } else {
                surface.addClass('field-success');
            }

            const nbPersonnes = stepElement.find('#nb_personnes');
            if (!nbPersonnes.val()) {
                nbPersonnes.addClass('field-error');
                isValid = false;
            } else {
                nbPersonnes.addClass('field-success');
            }

            if (!stepElement.find('input[name="isolation"]:checked').length) {
                isValid = false;
            }

            return isValid;
        }

        function validateStep2(stepElement) {
            return stepElement.find('input[name="type_chauffage"]:checked').length > 0;
        }

        function validateStep3(stepElement) {
            return stepElement.find('input[name="type_cuisson"]:checked').length > 0;
        }

        function validateStep4(stepElement) {
            return stepElement.find('input[name="eau_chaude"]:checked').length > 0;
        }

        function validateStep5(stepElement) {
            return stepElement.find('input[name="type_eclairage"]:checked').length > 0;
        }

        function validateStep6(stepElement) {
            return stepElement.find('input[name="piscine"]:checked').length > 0;
        }

        function validateStep8(stepElement) {
            const tarifChoisi = stepElement.find('input[name="tarif_choisi"]:checked').length;
            const puissanceChoisie = stepElement.find('input[name="puissance_choisie"]:checked').length;
            const typeLogementUsage = stepElement.find('input[name="type_logement_usage"]:checked').length;

            return tarifChoisi && puissanceChoisie && typeLogementUsage;
        }

        function validateStep9(stepElement) {
            let isValid = true;

            const requiredFields = [
                'client_nom', 'client_prenom', 'client_email',
                'client_telephone', 'client_date_naissance'
            ];

            const requiredFiles = [
                'rib_file', 'carte_identite_recto', 'carte_identite_verso'
            ];

            const requiredCheckboxes = ['accept_conditions'];

            // Validation des champs texte
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !field.value.trim()) {
                    $(field).addClass('field-error');
                    isValid = false;
                } else if (field) {
                    $(field).removeClass('field-error');
                }
            });

            // Validation des fichiers
            requiredFiles.forEach(fileId => {
                const file = document.getElementById(fileId);
                if (file && !file.files.length) {
                    $(file).closest('.upload-card').addClass('upload-error');
                    isValid = false;
                } else if (file) {
                    $(file).closest('.upload-card').removeClass('upload-error');
                }
            });

            // Validation des checkboxes
            requiredCheckboxes.forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox && !checkbox.checked) {
                    $(checkbox).closest('.check-item').addClass('field-error');
                    isValid = false;
                } else if (checkbox) {
                    $(checkbox).closest('.check-item').removeClass('field-error');
                }
            });

            // Validation conditionnelle de l'adresse
            const addressSection = document.getElementById('address-section');
            if (addressSection && $(addressSection).hasClass('show')) {
                const addressFields = ['client_adresse', 'client_code_postal', 'client_ville'];
                addressFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && !field.value.trim()) {
                        $(field).addClass('field-error');
                        isValid = false;
                    } else if (field) {
                        $(field).removeClass('field-error');
                    }
                });
            } else {
                const pdlFields = ['pdl_adresse', 'numero_compteur'];
                pdlFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && !field.value.trim()) {
                        $(field).addClass('field-error');
                        isValid = false;
                    } else if (field) {
                        $(field).removeClass('field-error');
                    }
                });
            }

            return isValid;
        }

        function validateField($field) {
            const fieldType = $field.attr('type');
            const fieldName = $field.attr('name');
            let isValid = true;

            $field.removeClass('field-error field-success');

            if (fieldType === 'radio') {
                isValid = $(`input[name="${fieldName}"]:checked`).length > 0;
            } else if ($field.is('select')) {
                isValid = $field.val() !== '' && $field.val() !== null;
            } else {
                isValid = $field.val().trim() !== '';
            }

            $field.addClass(isValid ? 'field-success' : 'field-error');
            return isValid;
        }

        function validateNumberField($field) {
            const min = parseFloat($field.attr('min'));
            const max = parseFloat($field.attr('max'));
            const value = parseFloat($field.val());

            $field.removeClass('field-error field-success');

            if (isNaN(value)) {
                $field.addClass('field-error');
                return false;
            }

            if (!isNaN(min) && value < min) {
                $field.addClass('field-error');
                showValidationMessage(`La valeur minimum est ${min}`);
                return false;
            }

            if (!isNaN(max) && value > max) {
                $field.addClass('field-error');
                showValidationMessage(`La valeur maximum est ${max}`);
                return false;
            }

            $field.addClass('field-success');
            return true;
        }

        // ================================
        // COLLECTE DE DONNÉES
        // ================================

        function saveCurrentStepData() {
            const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

            currentStepElement.find('input, select, textarea').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (!name) return;

                const cleanName = name.replace('[]', '');

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[cleanName] = $field.val();
                    }
                } else if (type === 'checkbox') {
                    if (!formData[cleanName]) {
                        formData[cleanName] = [];
                    }

                    const value = $field.val();

                    if ($field.is(':checked')) {
                        if (!formData[cleanName].includes(value)) {
                            formData[cleanName].push(value);
                        }
                    } else {
                        const index = formData[cleanName].indexOf(value);
                        if (index > -1) {
                            formData[cleanName].splice(index, 1);
                        }
                    }
                } else {
                    formData[cleanName] = $field.val();
                }
            });
        }

        function collectAllFormData() {
            formData = {};

            $('.form-step').each(function () {
                const $step = $(this);

                $step.find('input, select, textarea').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');
                    const type = $field.attr('type');

                    if (!name) return;

                    const cleanName = name.replace('[]', '');

                    if (type === 'radio') {
                        if ($field.is(':checked')) {
                            formData[cleanName] = $field.val();
                        }
                    } else if (type === 'checkbox') {
                        if (!formData[cleanName]) {
                            formData[cleanName] = [];
                        }

                        if ($field.is(':checked')) {
                            const value = $field.val();
                            if (!formData[cleanName].includes(value)) {
                                formData[cleanName].push(value);
                            }
                        }
                    } else if ($field.is('select') || type === 'text' || type === 'number' || type === 'email' || type === 'tel' || $field.is('textarea')) {
                        formData[cleanName] = $field.val();
                    }
                });
            });

            if (!validateFormData()) {
                return null;
            }

            return formData;
        }

        function validateFormData() {
            const requiredFields = ['type_logement', 'surface', 'nb_personnes', 'isolation', 'type_chauffage', 'type_cuisson', 'eau_chaude', 'type_eclairage', 'piscine'];
            const missingFields = requiredFields.filter(field => !formData[field] || formData[field] === '');

            if (missingFields.length > 0) {
                console.error('Champs manquants:', missingFields);
                showValidationMessage('Données manquantes : ' + missingFields.join(', '));
                return false;
            }

            if (!Array.isArray(formData.electromenagers)) {
                formData.electromenagers = [];
            }
            if (!Array.isArray(formData.equipements_speciaux)) {
                formData.equipements_speciaux = [];
            }

            formData.surface = parseInt(formData.surface) || 0;
            formData.nb_personnes = parseInt(formData.nb_personnes) || 1;

            if (formData.surface < 20 || formData.surface > 500) {
                console.error('Surface invalide:', formData.surface);
                showValidationMessage('Surface invalide (20-500 m²)');
                return false;
            }

            if (formData.nb_personnes < 1 || formData.nb_personnes > 6) {
                console.error('Nombre de personnes invalide:', formData.nb_personnes);
                showValidationMessage('Nombre de personnes invalide (1-6)');
                return false;
            }

            return true;
        }

        function collectClientData() {
            return {
                nom: $('#client_nom').val().trim(),
                prenom: $('#client_prenom').val().trim(),
                email: $('#client_email').val().trim(),
                telephone: $('#client_telephone').val().trim(),
                adresse: $('#client_adresse').val().trim(),
                code_postal: $('#client_code_postal').val().trim(),
                ville: $('#client_ville').val().trim()
            };
        }

        // ================================
        // CALCULS ET ESTIMATION
        // ================================

        function calculateResults() {
            const allData = collectAllFormData();
            const clientData = collectClientData();

            if (!allData) {
                console.error('Échec de collecte des données');
                return;
            }

            showStep(7);
            updateProgress();
            updateNavigation();

            $('#results-container').html(`
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul de votre estimation personnalisée...</p>
                    <small>Traitement des données par le calculateur HTIC...</small>
                </div>
            `);

            sendDataToCalculator(allData, configData, clientData);
        }

        function sendDataToCalculator(userData, configData, clientData) {
            const dataToSend = {
                action: 'htic_calculate_estimation',
                type: 'elec-residentiel',
                user_data: userData,
                config_data: configData,
                nonce: (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) ? hticSimulateur.nonce : ''
            };

            let ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
                ajaxUrl = hticSimulateur.ajaxUrl;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: dataToSend,
                timeout: 30000,
                success: function (response) {
                    if (response.success) {
                        window.calculationResults = response.data;
                        window.clientData = clientData;
                        window.simulationData = userData;
                        window.originalRecommendedPower = response.data.puissance_recommandee;
                        displayResults(response.data);
                    } else {
                        displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erreur AJAX:', { status, error, responseText: xhr.responseText });

                    let errorMessage = 'Erreur de connexion lors du calcul';
                    if (xhr.status === 0) {
                        errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur interne du serveur.';
                    } else if (status === 'timeout') {
                        errorMessage = 'Le calcul prend trop de temps. Réessayez.';
                    }

                    displayError(errorMessage);
                }
            });
        }

        function recalculateWithNewPower(tarif, nouvellePuissance) {
            $('#calculs-selection').html('<div class="loading-mini">Recalcul en cours...</div>');

            const allData = collectAllFormData();
            allData.puissance_forcee = nouvellePuissance;

            const dataToSend = {
                action: 'htic_calculate_estimation',
                type: 'elec-residentiel',
                user_data: allData,
                config_data: configData,
                nonce: (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) ? hticSimulateur.nonce : ''
            };

            let ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
                ajaxUrl = hticSimulateur.ajaxUrl;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: dataToSend,
                success: function (response) {
                    if (response.success) {
                        window.calculationResults = response.data;

                        if (!window.originalRecommendedPower) {
                            window.originalRecommendedPower = response.data.puissance_recommandee;
                        }

                        updateAllTarifPrices(response.data);
                        updateCalculsSelection(tarif, nouvellePuissance);
                    } else {
                        $('#calculs-selection').html('<div class="error-mini">Erreur de calcul</div>');
                    }
                },
                error: function () {
                    $('#calculs-selection').html('<div class="error-mini">Erreur de connexion</div>');
                }
            });
        }

        // ================================
        // AFFICHAGE DES RÉSULTATS
        // ================================

        function displayResults(results) {
            if (!results || !results.consommation_annuelle || !results.tarifs) {
                displayError('Données de résultats incomplètes');
                return;
            }

            const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
            const puissanceRecommandee = results.puissance_recommandee || '12';

            const tarifBase = results.tarifs.base || {};
            const tarifHC = results.tarifs.hc || {};
            const tarifTempo = results.tarifs.tempo || {};

            const totalAnnuelBase = parseInt(tarifBase.total_annuel) || 0;
            const totalAnnuelHC = parseInt(tarifHC.total_annuel) || 0;
            const totalAnnuelTempo = parseInt(tarifTempo.total_annuel) || 0;

            const totalMensuelBase = Math.round(totalAnnuelBase / 10);
            const totalMensuelHC = Math.round(totalAnnuelHC / 10);
            const totalMensuelTempo = Math.round(totalAnnuelTempo / 10);

            const tarifMin = Math.min(totalAnnuelBase, totalAnnuelHC, totalAnnuelTempo);
            const tarifMax = Math.max(totalAnnuelBase, totalAnnuelHC, totalAnnuelTempo);
            const economie = tarifMax - tarifMin;

            let tarifRecommande = 'base';
            if (totalAnnuelHC === tarifMin) tarifRecommande = 'hc';
            if (totalAnnuelTempo === tarifMin) tarifRecommande = 'tempo';

            const resultsHtml = generateResultsHTML(
                consommationAnnuelle,
                puissanceRecommandee,
                totalAnnuelBase,
                totalAnnuelHC,
                totalAnnuelTempo,
                totalMensuelBase,
                totalMensuelHC,
                totalMensuelTempo,
                tarifRecommande,
                economie,
                tarifBase,
                tarifHC,
                tarifTempo,
                results
            );

            $('#results-container').html(resultsHtml);
            $('.results-summary').hide().fadeIn(600);

            setTimeout(() => {
                createConsumptionPieChart();
            }, 500);
        }

        function generateResultsHTML(consommationAnnuelle, puissanceRecommandee, totalAnnuelBase, totalAnnuelHC, totalAnnuelTempo, totalMensuelBase, totalMensuelHC, totalMensuelTempo, tarifRecommande, economie, tarifBase, tarifHC, tarifTempo, results) {
            return `
                <div class="results-summary">
                    <div class="result-card main-result">
                        <div class="result-icon">⚡</div>
                        <h3>Votre consommation estimée</h3>
                        <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                        <p>Puissance recommandée : <strong>${puissanceRecommandee} kVA</strong></p>
                    </div>
                    
                    <div class="tarifs-comparison">
                        <h3>💰 Comparaison des tarifs</h3>
                        <div class="tarifs-grid">
                            <div class="tarif-card ${tarifRecommande === 'base' ? 'recommended' : ''}">
                                <h4>Base TRV</h4>
                                <div class="tarif-prix">${totalAnnuelBase.toLocaleString()}€ <span>/an (TTC)</span></div>
                                <div class="tarif-mensuel">${totalMensuelBase.toLocaleString()}€/mois*</div>
                                <div class="tarif-details">
                                    <small>Prix unique : ${tarifBase.prix_kwh || '0.2516'}€/kWh</small>
                                </div>
                                ${tarifRecommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                                <small class="tarif-note">* Moyenne mensuelle calculée sur 10 mois</small>
                            </div>
                            
                            <div class="tarif-card ${tarifRecommande === 'hc' ? 'recommended' : ''}">
                                <h4>Heures Creuses TRV</h4>
                                <div class="tarif-prix">${totalAnnuelHC.toLocaleString()}€ <span>/an (TTC)</span></div>
                                <div class="tarif-mensuel">${totalMensuelHC.toLocaleString()}€/mois*</div>
                                <div class="tarif-details">
                                    <small>HP: ${tarifHC.prix_kwh_hp || '0.27'}€ | HC: ${tarifHC.prix_kwh_hc || '0.2068'}€</small>
                                </div>
                                ${tarifRecommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                                <small class="tarif-note">* Moyenne mensuelle calculée sur 10 mois</small>
                            </div>
                            
                            <div class="tarif-card ${tarifRecommande === 'tempo' ? 'recommended' : ''}">
                                <h4>Tempo TRV</h4>
                                <div class="tarif-prix">${totalAnnuelTempo.toLocaleString()}€ <span>/an (TTC)</span></div>
                                <div class="tarif-mensuel">${totalMensuelTempo.toLocaleString()}€/mois*</div>
                                <div class="tarif-details">
                                    <small>300j bleus, 43j blancs, 22j rouges</small>
                                </div>
                                ${tarifRecommande === 'tempo' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                                <small class="tarif-note">* Moyenne mensuelle calculée sur 10 mois</small>
                            </div>
                        </div>
                        
                        ${economie > 0 ? `
                        <div class="economies">
                            <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${economie.toLocaleString()}€/an en choisissant le bon tarif !</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="repartition-conso">
                        <div class="repartition-header">
                            <h3>Répartition de votre consommation</h3>
                            <p class="repartition-subtitle">Analyse détaillée par poste de consommation</p>
                        </div>
                        ${generateConsumptionBreakdown(results)}
                    </div>
                    
                    <div class="results-actions">
                        <button class="btn btn-primary btn-large" id="btn-subscribe">
                            📝 Je souscris
                        </button>
                    </div>
                </div>
            `;
        }

        function displayError(message) {
            $('#results-container').html(`
                <div class="error-state">
                    <div class="error-icon">❌</div>
                    <h3>Erreur lors du calcul</h3>
                    <p>${message}</p>
                    <div class="error-actions">
                        <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger la page</button>
                        <button class="btn btn-secondary" id="btn-back-to-form">← Retour au formulaire</button>
                    </div>
                </div>
            `);

            $('#btn-back-to-form').on('click', function () {
                goToStep(6);
            });
        }

        // ================================
        // GESTION ÉTAPE 8 (MISE À JOUR CALCULS)
        // ================================

        function updateCalculsSelection(tarif, puissance) {
            const results = window.calculationResults;
            if (!results || !results.tarifs) return;

            const tarifData = results.tarifs[tarif] || {};
            const totalAnnuel = parseInt(tarifData.total_annuel) || 0;
            const totalMensuel = Math.round(totalAnnuel / 10);
            const consommation = parseInt(results.consommation_annuelle) || 0;

            const calculHTML = `
                <div class="calcul-resume">
                    <h4>Votre sélection :</h4>
                    <div class="calcul-item">
                        <span class="label">Tarif :</span>
                        <span class="value">${getTarifLabel(tarif)}</span>
                    </div>
                    <div class="calcul-item">
                        <span class="label">Puissance :</span>
                        <span class="value">${puissance} kVA</span>
                    </div>
                    <div class="calcul-item">
                        <span class="label">Consommation estimée :</span>
                        <span class="value">${consommation.toLocaleString()} kWh/an</span>
                    </div>
                    <div class="calcul-item highlight">
                        <span class="label">Coût annuel :</span>
                        <span class="value">${totalAnnuel.toLocaleString()}€ TTC</span>
                    </div>
                    <div class="calcul-item">
                        <span class="label">Coût mensuel moyen :</span>
                        <span class="value">${totalMensuel.toLocaleString()}€/mois (10 mois)</span>
                    </div>
                </div>
            `;

            $('#calculs-selection').html(calculHTML);
        }

        function updateAllTarifPrices(results) {
            if (!results || !results.tarifs) return;

            const tarifBase = parseInt(results.tarifs.base?.total_annuel) || 0;
            const tarifHC = parseInt(results.tarifs.hc?.total_annuel) || 0;
            const tarifTempo = parseInt(results.tarifs.tempo?.total_annuel) || 0;

            updateTarifPrice('prix-base', tarifBase);
            updateTarifPrice('prix-hc', tarifHC);
            updateTarifPrice('prix-tempo', tarifTempo);

            updatePrixMensuels('prix-base', Math.round(tarifBase / 10));
            updatePrixMensuels('prix-hc', Math.round(tarifHC / 10));
            updatePrixMensuels('prix-tempo', Math.round(tarifTempo / 10));
        }

        function updateTarifPrice(containerId, prix) {
            const container = $('#' + containerId);
            if (container.length) {
                const priceElement = container.find('.price-amount');
                if (priceElement.length) {
                    priceElement.text(prix.toLocaleString());
                }
            }
        }

        function updatePrixMensuels(containerId, prixMensuel) {
            const container = $('#' + containerId);
            let mensuelElement = container.find('.price-mensuel');

            if (mensuelElement.length === 0) {
                container.append(`
                    <div class="price-mensuel">
                        <span class="mensuel-amount">${prixMensuel.toLocaleString()}</span>
                        <span class="mensuel-period">€/mois*</span>
                    </div>
                `);
            } else {
                mensuelElement.find('.mensuel-amount').text(prixMensuel.toLocaleString());
            }
        }

        // ================================
        // RÉCAPITULATIF FINAL (ÉTAPE 10)
        // ================================

        function generateRecapitulatifFinal() {
            const allData = collectAllFormData();
            const clientData = collectClientData();
            const results = window.calculationResults;

            if (!allData || !results) {
                console.error('Données manquantes pour le récapitulatif');
                $('#recap-container-final').html(`
                    <div class="error-state">
                        <div class="error-icon">❌</div>
                        <h3>Erreur</h3>
                        <p>Impossible de générer le récapitulatif. Données manquantes.</p>
                    </div>
                `);
                return;
            }

            const tarifChoisi = allData.tarif_choisi || 'base';
            const puissanceChoisie = allData.puissance_choisie || results.puissance_recommandee;
            const puissanceOriginaleRecommandee = window.originalRecommendedPower || results.puissance_recommandee;

            const totalAnnuel = getTotalAnnuelChoisi(results, tarifChoisi);
            const totalMensuel = Math.round(totalAnnuel / 10);
            const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;

            const recapHTML = generateRecapHTML(allData, clientData, tarifChoisi, puissanceChoisie, puissanceOriginaleRecommandee, totalAnnuel, totalMensuel, consommationAnnuelle);

            $('#recap-container-final').html(recapHTML);

            $('#btn-finaliser-souscription').off('click').on('click', function () {
                finaliserSouscription();
            });
        }

        function generateRecapHTML(allData, clientData, tarifChoisi, puissanceChoisie, puissanceOriginaleRecommandee, totalAnnuel, totalMensuel, consommationAnnuelle) {
            return `
                <div class="recap-complet">
                    
                    <!-- SECTION FORMULE SÉLECTIONNÉE -->
                    <div class="formule-selectionnee">
                        <div class="formule-header">
                            <span class="formule-icon">⚡</span>
                            <h3>Votre formule d'électricité</h3>
                        </div>
                        
                        <div class="formule-details">
                            <div class="formule-main">
                                <div class="formule-item tarif">
                                    <div class="formule-label">Tarif sélectionné</div>
                                    <div class="formule-value">${getTarifLabel(tarifChoisi)}</div>
                                    <div class="formule-badge">${getBadgeTarif(tarifChoisi)}</div>
                                </div>
                                
                                <div class="formule-divider"></div>
                                
                                <div class="formule-item puissance">
                                    <div class="formule-label">Puissance souscrite</div>
                                    <div class="formule-value">${puissanceChoisie} kVA</div>
                                    <div class="formule-badge ${puissanceChoisie == puissanceOriginaleRecommandee ? 'recommended' : ''}">
                                        ${puissanceChoisie == puissanceOriginaleRecommandee ? '⭐ Recommandée' : 'Personnalisée'}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="formule-costs">
                                <div class="cost-card annual">
                                    <div class="cost-icon">📅</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Coût annuel TTC</div>
                                        <div class="cost-amount">${totalAnnuel.toLocaleString()}€</div>
                                    </div>
                                </div>
                                
                                <div class="cost-card monthly">
                                    <div class="cost-icon">📆</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Moyenne mensuelle</div>
                                        <div class="cost-amount">${totalMensuel.toLocaleString()}€<span>/mois</span></div>
                                        <div class="cost-note">Sur 10 mois</div>
                                    </div>
                                </div>
                                
                                <div class="cost-card consumption">
                                    <div class="cost-icon">⚡</div>
                                    <div class="cost-details">
                                        <div class="cost-label">Consommation estimée</div>
                                        <div class="cost-amount">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION LOGEMENT -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">🏠</span>
                            Caractéristiques du logement
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Type de logement</span>
                                <span class="detail-value">${allData.type_logement === 'maison' ? '🏠 Maison' : '🏢 Appartement'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Surface habitable</span>
                                <span class="detail-value">${allData.surface} m²</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Nombre d'occupants</span>
                                <span class="detail-value">${allData.nb_personnes} personne(s)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Isolation</span>
                                <span class="detail-value">${getIsolationLabel(allData.isolation)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type de résidence</span>
                                <span class="detail-value highlight">${allData.type_logement_usage === 'principal' ? '🏠 Résidence principale' : '🏖️ Résidence secondaire'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION ÉQUIPEMENTS -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">🔥</span>
                            Chauffage et équipements
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Chauffage principal</span>
                                <span class="detail-value">${getHeatingLabel(allData.type_chauffage)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Eau chaude sanitaire</span>
                                <span class="detail-value">${allData.eau_chaude === 'oui' ? '💧 Électrique' : '🔥 Autre énergie'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type de cuisson</span>
                                <span class="detail-value">${getCuissonLabel(allData.type_cuisson)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Éclairage principal</span>
                                <span class="detail-value">${allData.type_eclairage === 'led' ? '💡 LED' : '🔆 Halogène/Incandescence'}</span>
                            </div>
                        </div>
                        
                        <!-- Électroménagers -->
                        <div style="grid-column: 1 / -1; margin-top: 1.5rem;">
                            <h4 style="margin-bottom: 0.75rem; color: #374151;">Électroménagers sélectionnés :</h4>
                            <div class="equipment-tags">
                                ${allData.electromenagers && allData.electromenagers.length > 0
                    ? allData.electromenagers.map(eq => `
                                        <span class="equipment-tag">
                                            ${getElectromenagerIcon(eq)} ${getElectromenagerLabel(eq)}
                                        </span>
                                    `).join('')
                    : '<span class="equipment-tag none">Aucun électroménager sélectionné</span>'
                }
                            </div>
                        </div>
                        
                        <!-- Piscine -->
                        <div style="grid-column: 1 / -1; margin-top: 1rem;">
                            <div class="detail-item">
                                <span class="detail-label">Piscine</span>
                                <span class="detail-value">${getPiscineLabel(allData.piscine)}</span>
                            </div>
                        </div>
                        
                        <!-- Équipements spéciaux -->
                        ${allData.equipements_speciaux && allData.equipements_speciaux.length > 0 ? `
                        <div style="grid-column: 1 / -1; margin-top: 1rem;">
                            <h4 style="margin-bottom: 0.75rem; color: #374151;">Autres équipements spéciaux :</h4>
                            <div class="equipment-tags">
                                ${allData.equipements_speciaux.map(eq => `
                                    <span class="equipment-tag special">
                                        ${getEquipementSpecialIcon(eq)} ${getEquipementSpecialLabel(eq)}
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                        ` : `
                        <div style="grid-column: 1 / -1; margin-top: 1rem;">
                            <h4 style="margin-bottom: 0.75rem; color: #374151;">Autres équipements spéciaux :</h4>
                            <div class="equipment-tags">
                                <span class="equipment-tag none">Aucun équipement spécial</span>
                            </div>
                        </div>
                        `}
                    </div>
                    
                    <!-- SECTION INFORMATIONS PERSONNELLES -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">👤</span>
                            Vos informations personnelles
                        </h3>
                        <div class="detail-grid">
                            ${clientData.nom ? `
                            <div class="detail-item">
                                <span class="detail-label">Nom complet</span>
                                <span class="detail-value">${clientData.nom} ${clientData.prenom}</span>
                            </div>` : ''}
                            
                            ${clientData.email ? `
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">${clientData.email}</span>
                            </div>` : ''}
                            
                            ${clientData.telephone ? `
                            <div class="detail-item">
                                <span class="detail-label">Téléphone</span>
                                <span class="detail-value">${clientData.telephone}</span>
                            </div>` : ''}
                            
                            ${getAdditionalClientInfo()}
                        </div>
                        
                        ${clientData.adresse ? `
                        <div style="grid-column: 1 / -1; margin-top: 1rem;">
                            <div class="detail-item">
                                <span class="detail-label">Adresse complète</span>
                                <span class="detail-value">${clientData.adresse}<br>${clientData.code_postal} ${clientData.ville}</span>
                            </div>
                        </div>` : ''}
                    </div>
                    
                    <!-- SECTION DOCUMENTS ET VALIDATION -->
                    <div class="recap-section-detail">
                        <h3 class="section-header-detail">
                            <span class="section-icon-detail">📎</span>
                            Documents et validations
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Documents fournis</span>
                                <span class="detail-value">
                                    ${getUploadedFiles()}
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Conditions acceptées</span>
                                <span class="detail-value success">✅ Conditions générales et prélèvement</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
            `;
        }

        // ================================
        // ENVOI DES DONNÉES FINALES
        // ================================

        function envoyerDonneesAuServeur() {
            const allFormData = collectAllFormData();
            const clientData = collectClientData();
            const results = window.calculationResults;

            if (!allFormData || !clientData || !results) {
                console.error('Données manquantes pour l\'envoi');
                showNotification('Données incomplètes', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'process_electricity_form');
            formData.append('nonce', hticSimulateur.nonce);

            const dataToSend = {
                // Informations personnelles
                firstName: clientData.nom || $('#client_nom').val(),
                lastName: clientData.prenom || $('#client_prenom').val(),
                email: clientData.email || $('#client_email').val(),
                phone: clientData.telephone || $('#client_telephone').val(),
                postalCode: $('#client_code_postal').val() || clientData.code_postal,

                // Adresse complète
                adresse: clientData.adresse || $('#client_adresse').val(),
                ville: clientData.ville || $('#client_ville').val(),

                // Informations supplémentaires
                dateNaissance: $('#client_date_naissance').val(),
                lieuNaissance: $('#client_lieu_naissance').val(),
                pdlAdresse: $('#pdl_adresse').val(),
                numeroCompteur: $('#numero_compteur').val(),

                // Données du logement
                housingType: allFormData.type_logement,
                surface: allFormData.surface,
                residents: allFormData.nb_personnes,
                isolation: allFormData.isolation,

                // Chauffage et équipements
                heatingType: allFormData.type_chauffage,
                chauffageElectrique: allFormData.chauffage_electrique,
                waterHeating: allFormData.eau_chaude,
                typeCuisson: allFormData.type_cuisson,
                typeEclairage: allFormData.type_eclairage,
                piscine: allFormData.piscine,

                // Équipements
                appliances: {
                    electromenagers: allFormData.electromenagers || [],
                    equipementsSpeciaux: allFormData.equipements_speciaux || []
                },

                // Sélections finales
                pricingType: allFormData.tarif_choisi,
                contractPower: allFormData.puissance_choisie,
                typeLogementUsage: allFormData.type_logement_usage,

                // Résultats des calculs
                annualConsumption: results.consommation_annuelle,
                monthlyEstimate: getTotalMensuelFromResults(results, allFormData.tarif_choisi),

                // Détails des tarifs calculés
                tarifs: {
                    base: results.tarifs.base,
                    hc: results.tarifs.hc,
                    tempo: results.tarifs.tempo
                },

                // Répartition de la consommation
                repartition: results.repartition,

                // Résumé final
                summary: {
                    provider: 'GES Solutions',
                    tarifChoisi: allFormData.tarif_choisi,
                    puissanceChoisie: allFormData.puissance_choisie,
                    totalAnnuel: getTotalAnnuelChoisi(results, allFormData.tarif_choisi),
                    totalMensuel: getTotalMensuelFromResults(results, allFormData.tarif_choisi),
                    consommationAnnuelle: results.consommation_annuelle,
                    puissanceRecommandee: results.puissance_recommandee
                },

                timestamp: new Date().toISOString()
            };

            formData.append('form_data', JSON.stringify(dataToSend));

            // Ajouter les fichiers uploadés
            const ribFile = document.getElementById('rib_file');
            const carteRectoFile = document.getElementById('carte_identite_recto');
            const carteVersoFile = document.getElementById('carte_identite_verso');

            if (ribFile && ribFile.files[0]) {
                formData.append('rib_file', ribFile.files[0]);
            }

            if (carteRectoFile && carteRectoFile.files[0]) {
                formData.append('carte_identite_recto', carteRectoFile.files[0]);
            }

            if (carteVersoFile && carteVersoFile.files[0]) {
                formData.append('carte_identite_verso', carteVersoFile.files[0]);
            }

            afficherLoader();

            $.ajax({
                url: hticSimulateur.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    cacherLoader();

                    if (response.success) {
                        afficherMessageSucces(response.message);
                    } else {
                        afficherMessageErreur(response.message || 'Une erreur est survenue');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erreur AJAX:', error);
                    cacherLoader();
                    afficherMessageErreur('Erreur de connexion au serveur');
                }
            });
        }

        // ================================
        // GÉNÉRATION DE CONTENU
        // ================================

        function generateConsumptionBreakdown(results) {
            const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
            const repartition = results.repartition || {};

            const chartData = [];
            const chartLabels = [];
            const chartColors = [];

            const colorMap = {
                'chauffage': '#FF6384',
                'eau_chaude': '#36A2EB',
                'electromenagers': '#FFCE56',
                'eclairage': '#4BC0C0',
                'multimedia': '#9966FF',
                'equipements_speciaux': '#FF9F40',
                'autres': '#C9CBCF'
            };

            let breakdownHtml = `
                <div class="chart-container" style="margin: 20px 0; text-align: center; min-height: 400px;">
                    <canvas id="consumptionPieChart" width="400" height="400"></canvas>
                </div>
                <div class="repartition-content">
            `;

            Object.keys(repartition).forEach(key => {
                let value = 0;

                if (key === 'equipements_speciaux') {
                    if (typeof repartition[key] === 'object' && repartition[key] !== null) {
                        for (let subKey in repartition[key]) {
                            value += parseInt(repartition[key][subKey]) || 0;
                        }
                    } else {
                        value = parseInt(repartition[key]) || 0;
                    }
                } else {
                    value = parseInt(repartition[key]) || 0;
                }

                const percentage = Math.round(value / consommationAnnuelle * 100);

                chartData.push(value);
                chartLabels.push(getConsumptionLabel(key));
                chartColors.push(colorMap[key] || '#C9CBCF');

                breakdownHtml += `
                    <div class="repartition-item ${key}">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">${getConsumptionIcon(key)}</div>
                                <div class="item-details">
                                    <div class="item-name">${getConsumptionLabel(key)}</div>
                                    <div class="item-value">${value.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${percentage}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${percentage}%; background-color: ${colorMap[key] || '#C9CBCF'}"></div>
                        </div>
                    </div>
                `;
            });

            breakdownHtml += `</div>`;

            window.chartData = {
                data: chartData,
                labels: chartLabels,
                colors: chartColors
            };

            return breakdownHtml;
        }

        function finaliserSouscription() {
            envoyerDonneesAuServeur();
        }

        function showSuccessMessage() {
            const successHtml = `
                <div class="success-overlay" id="success-modal">
                    <div class="success-content">
                        <div class="success-icon">🎉</div>
                        <h2>Félicitations !</h2>
                        <p class="success-main">Votre souscription a été enregistrée avec succès</p>
                        <div class="success-details">
                            <p>Numéro de dossier : <strong>#${Math.floor(Math.random() * 900000) + 100000}</strong></p>
                            <p>Un email de confirmation vous a été envoyé</p>
                            <p>Notre équipe vous contactera sous 24h</p>
                        </div>
                        <button class="btn btn-primary" onclick="location.reload()">
                            Faire une nouvelle simulation
                        </button>
                    </div>
                </div>
            `;

            $('body').append(successHtml);
            $('#success-modal').fadeIn();
        }

        // ================================
        // GRAPHIQUES (CHART.JS)
        // ================================

        function createConsumptionPieChart() {
            if (typeof Chart === 'undefined') {
                loadChartJS().then(() => {
                    createChart();
                }).catch(error => {
                    console.error('Erreur chargement Chart.js:', error);
                });
                return;
            }

            createChart();
        }

        function loadChartJS() {
            return new Promise((resolve, reject) => {
                if (typeof Chart !== 'undefined') {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                script.onerror = () => reject(new Error('Impossible de charger Chart.js'));
                document.head.appendChild(script);
            });
        }

        function createChart() {
            const ctx = document.getElementById('consumptionPieChart');

            if (!ctx || !window.chartData || !window.chartData.data || window.chartData.data.length === 0) {
                console.error('Impossible de créer le graphique');
                return;
            }

            if (window.consumptionChart) {
                window.consumptionChart.destroy();
            }

            try {
                window.consumptionChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: window.chartData.labels,
                        datasets: [{
                            data: window.chartData.data,
                            backgroundColor: window.chartData.colors,
                            borderColor: '#fff',
                            borderWidth: 2,
                            hoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: { size: 14 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = window.chartData.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value.toLocaleString()} kWh (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            duration: 1500
                        }
                    }
                });
            } catch (error) {
                console.error('Erreur lors de la création du chart:', error);
            }
        }

        // ================================
        // INTERFACE UTILISATEUR
        // ================================

        function showValidationMessage(message) {
            $('.validation-message').remove();

            const $message = $(`<div class="validation-message">${message}</div>`);
            const activeStep = $('.form-step.active');
            const stepHeader = activeStep.find('.step-header');

            stepHeader.after($message);
            $message.hide().slideDown(300);

            setTimeout(() => {
                $message.slideUp(300, () => $message.remove());
            }, 5000);
        }

        function showNotification(message, type = 'info') {
            $('.notification').remove();

            const $notification = $(`
                <div class="notification notification-${type}">
                    ${message}
                </div>
            `);

            $('body').append($notification);

            setTimeout(() => {
                $notification.addClass('show');
            }, 100);

            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 4000);
        }

        function restartSimulation() {
            currentStep = 1;
            formData = {};
            calculationResults = null;

            $('#simulateur-elec-residentiel')[0].reset();
            $('.field-error, .field-success').removeClass('field-error field-success');

            updateUI();
        }

        function afficherLoader() {
            if ($('#ajax-loader').length) return;

            const loader = `
                <div id="ajax-loader" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                            background: rgba(0,0,0,0.7); display: flex; 
                            justify-content: center; align-items: center; z-index: 99999;">
                    <div style="background: white; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                        <div class="spinner" style="border: 5px solid #f3f3f3; border-top: 5px solid #667eea; 
                                    border-radius: 50%; width: 60px; height: 60px; 
                                    animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                        <h3 style="margin: 0 0 10px 0; color: #333;">Envoi en cours...</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">
                            Génération du PDF et envoi des emails<br>
                            <small>Veuillez patienter quelques instants</small>
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
        }

        function cacherLoader() {
            $('#ajax-loader').fadeOut(300, function () {
                $(this).remove();
            });
        }

        function afficherMessageSucces(message) {
            $('.ajax-message').remove();

            const successHtml = `
                <div class="ajax-message success-message" style="position: fixed; top: 20px; right: 20px; 
                            background: linear-gradient(135deg, #82C720 0%, #82C720 100%); color: white; 
                            padding: 20px 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(255, 255, 255, 0.2); 
                            z-index: 100000; max-width: 400px; animation: slideIn 0.5s ease;">
                    <div style="display: flex; align-items: center;">
                        <span style="font-size: 24px; margin-right: 15px;">✅</span>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 16px; colors:white;">Succès !</h4>
                        </div>
                    </div>
                </div>
                <style>
                    @keyframes slideIn {
                        from { transform: translateX(400px); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                </style>
            `;

            $('body').append(successHtml);

            setTimeout(() => {
                window.location.href = '/merci';
            }, 1500);

            setTimeout(() => {
                $('.success-message').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 5000);
        }

        function afficherMessageErreur(message) {
            $('.ajax-message').remove();

            const errorHtml = `
                <div class="ajax-message error-message" style="position: fixed; top: 20px; right: 20px; 
                            background: #dc3545; color: white; padding: 20px 30px; 
                            border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); 
                            z-index: 100000; max-width: 400px; animation: slideIn 0.5s ease;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <span style="font-size: 24px; margin-right: 15px;">❌</span>
                            <div>
                                <h4 style="margin: 0 0 5px 0; font-size: 16px;">Erreur</h4>
                                <p style="margin: 0; font-size: 14px; opacity: 0.95;">${message}</p>
                            </div>
                        </div>
                        <button class="close-btn" style="background: white; color: #dc3545; border: none; 
                            padding: 5px 10px; border-radius: 5px; cursor: pointer; 
                            margin-left: 15px; font-weight: bold;">✕</button>
                    </div>
                </div>
            `;

            $('body').append(errorHtml);
        }

        // ================================
        // FONCTIONS UTILITAIRES
        // ================================

        function getTarifLabel(tarif) {
            const labels = {
                'base': 'Base TRV',
                'hc': 'Heures Creuses TRV',
                'tempo': 'Tempo TRV'
            };
            return labels[tarif] || tarif;
        }

        function getBadgeTarif(tarif) {
            const badges = {
                'base': 'Simple',
                'hc': 'Économique',
                'tempo': 'Expert'
            };
            return badges[tarif] || '';
        }

        function getTotalAnnuelChoisi(results, tarif) {
            if (!results || !results.tarifs || !results.tarifs[tarif]) return 0;
            return parseInt(results.tarifs[tarif].total_annuel) || 0;
        }

        function getTotalMensuelFromResults(results, tarifChoisi) {
            if (!results || !results.tarifs || !results.tarifs[tarifChoisi]) return 0;
            const totalAnnuel = parseInt(results.tarifs[tarifChoisi].total_annuel) || 0;
            return Math.round(totalAnnuel / 10);
        }

        function getConsumptionLabel(key) {
            const labels = {
                'chauffage': 'Chauffage',
                'eau_chaude': 'Eau chaude',
                'electromenagers': 'Électroménager',
                'eclairage': 'Éclairage',
                'multimedia': 'Multimédia',
                'equipements_speciaux': 'Équipements spéciaux',
                'autres': 'Autres'
            };
            return labels[key] || key;
        }

        function getConsumptionIcon(key) {
            const icons = {
                'chauffage': '🔥',
                'eau_chaude': '💧',
                'electromenagers': '🔌',
                'eclairage': '💡',
                'multimedia': '📺',
                'equipements_speciaux': '⚡',
                'autres': '📊'
            };
            return icons[key] || '📊';
        }

        function getIsolationLabel(code) {
            const labels = {
                'avant_1980': 'Avant 1980 (faible isolation)',
                '1980_2000': '1980-2000 (isolation moyenne)',
                'apres_2000': 'Après 2000 (bonne isolation)',
                'renovation': 'Rénovation récente (très bonne isolation)'
            };
            return labels[code] || code;
        }

        function getHeatingLabel(type) {
            const labels = {
                'convecteurs': '🔥 Convecteurs électriques',
                'inertie': '🌡️ Radiateurs à inertie',
                'clim_reversible': '❄️ Climatisation réversible',
                'pac': '💨 Pompe à chaleur',
                'autre': '🚫 Pas de chauffage électrique'
            };
            return labels[type] || type;
        }

        function getCuissonLabel(code) {
            const labels = {
                'plaque_induction': 'Plaques à induction',
                'plaque_vitroceramique': 'Plaques vitrocéramiques',
                'autre': 'Autre (gaz, mixte...)'
            };
            return labels[code] || code;
        }

        function getElectromenagerIcon(code) {
            const icons = {
                'lave_linge': '👕',
                'seche_linge': '🌪️',
                'refrigerateur': '🧊',
                'lave_vaisselle': '🍽️',
                'four': '🔥',
                'congelateur': '❄️',
                'cave_a_vin': '🍷'
            };
            return icons[code] || '🔌';
        }

        function getElectromenagerLabel(code) {
            const labels = {
                'lave_linge': 'Lave-linge',
                'seche_linge': 'Sèche-linge',
                'refrigerateur': 'Réfrigérateur',
                'lave_vaisselle': 'Lave-vaisselle',
                'four': 'Four',
                'congelateur': 'Congélateur',
                'cave_a_vin': 'Cave à vin'
            };
            return labels[code] || code;
        }

        function getEquipementSpecialIcon(code) {
            const icons = {
                'spa_jacuzzi': '🛁',
                'voiture_electrique': '🚗',
                'aquarium': '🐠',
                'climatiseur_mobile': '🌬️'
            };
            return icons[code] || '⚡';
        }

        function getEquipementSpecialLabel(code) {
            const labels = {
                'spa_jacuzzi': 'Spa/Jacuzzi',
                'voiture_electrique': 'Voiture électrique',
                'aquarium': 'Aquarium',
                'climatiseur_mobile': 'Climatiseur mobile'
            };
            return labels[code] || code;
        }

        function getPiscineLabel(value) {
            const labels = {
                'simple': '🏊 Piscine simple (filtration)',
                'chauffee': '🌊 Piscine chauffée',
                'non': '🚫 Pas de piscine'
            };
            return labels[value] || value;
        }

        function getAdditionalClientInfo() {
            let html = '';

            const dateNaissance = $('#client_date_naissance').val();
            if (dateNaissance) {
                html += `
                    <div class="detail-item">
                        <span class="detail-label">Date de naissance</span>
                        <span class="detail-value">${dateNaissance}</span>
                    </div>
                `;
            }

            const lieuNaissance = $('#client_lieu_naissance').val();
            if (lieuNaissance) {
                html += `
                    <div class="detail-item">
                        <span class="detail-label">Lieu de naissance</span>
                        <span class="detail-value">${lieuNaissance}</span>
                    </div>
                `;
            }

            const pdlAdresse = $('#pdl_adresse').val();
            if (pdlAdresse) {
                html += `
                    <div class="detail-item">
                        <span class="detail-label">Point de livraison</span>
                        <span class="detail-value">${pdlAdresse}</span>
                    </div>
                `;
            }

            const numeroCompteur = $('#numero_compteur').val();
            if (numeroCompteur) {
                html += `
                    <div class="detail-item">
                        <span class="detail-label">N° Point Référence Mesure</span>
                        <span class="detail-value">${numeroCompteur}</span>
                    </div>
                `;
            }

            return html;
        }

        function getUploadedFiles() {
            const documents = [];

            if ($('#rib_file')[0] && $('#rib_file')[0].files.length > 0) {
                documents.push('✅ RIB');
            }

            if ($('#carte_identite_recto')[0] && $('#carte_identite_recto')[0].files.length > 0) {
                documents.push('✅ Pièce identité recto');
            }

            if ($('#carte_identite_verso')[0] && $('#carte_identite_verso')[0].files.length > 0) {
                documents.push('✅ Pièce identité verso');
            }

            return documents.length > 0 ? documents.join('<br>') : 'Aucun document uploadé';
        }

        // ================================
        // API PUBLIQUE
        // ================================

        window.HticSimulateurData = {
            getCurrentData: () => formData,
            getAllData: collectAllFormData,
            getConfigData: () => configData,
            getCurrentStep: () => currentStep,
            goToStep: goToStep
        };

    });

})(jQuery);