// ===============================
// SIMULATEUR ÉLECTRICITÉ RÉSIDENTIEL - VERSION SIMPLIFIÉE
// ===============================

(function ($) {
    'use strict';

    $(document).ready(function () {

        // ===============================
        // VARIABLES GLOBALES
        // ===============================

        let currentStep = 1;
        const totalSteps = 10;
        let formData = {};
        let configData = {};
        let calculationResults = null;

        // ===============================
        // INITIALISATION
        // ===============================

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

        // ===============================
        // GESTION DES ÉVÉNEMENTS
        // ===============================

        function setupEventListeners() {
            // Navigation
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

            // Validation
            $('input[required], select[required]').on('blur', function () {
                validateField($(this));
            });

            $('input[type="number"]').on('input', function () {
                validateNumberField($(this));
            });

            // Chauffage
            $('input[name="chauffage_electrique"]').on('change', handleChauffageChange);

            // Actions résultats
            $(document).on('click', '#btn-subscribe', function () {
                goToStep(8);
            });

            // Email
            $(document).on('click', '#btn-send-email-contact', handleEmailSend);

            // Sélections étape 8
            $(document).on('change', 'input[name="tarif_choisi"]', handleTarifChange);
            $(document).on('change click', 'input[name="puissance_choisie"]', handlePuissanceChange);
        }

        // ===============================
        // HANDLERS
        // ===============================

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

            // Gestion de l'affichage des tarifs selon la puissance
            updateTarifVisibility(puissance);

            if (tarif && puissance) {
                recalculateWithNewPower(tarif, puissance);
            }
        }

        function handleEmailSend() {
            const $btn = $(this);
            const originalText = $btn.html();

            if (!window.calculationResults) {
                showNotification('Aucun résultat de calcul disponible', 'error');
                return;
            }

            const allFormData = collectAllFormData();
            const clientData = collectClientData();

            sendEmail($btn, originalText, allFormData, clientData, window.calculationResults);
        }

        // ===============================
        // NAVIGATION
        // ===============================

        function goToNextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateUI();
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
                if (stepNumber === 9) setupContactStep();
                if (stepNumber === 10) setupRecapStep();
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

        // ===============================
        // ÉTAPE 8 - VERSION SIMPLIFIÉE
        // ===============================

        function setupSelectionStep() {
            if (currentStep === 8 && window.calculationResults) {
                generateSimplifiedPuissanceOptions();
                preselectRecommendedOptions();
            }
        }

        function generateSimplifiedPuissanceOptions() {
            const results = window.calculationResults;
            const puissanceRecommandee = parseInt(results.puissance_recommandee) || 12;

            // Toutes les puissances disponibles
            const puissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];

            // Remplacer complètement le contenu du container de puissance
            const $container = $('.puissance-selection');

            let html = `
                <div class="puissance-grid-simple">
            `;

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

            html += `</div>`;

            $container.html(html);

            console.log('✅ Puissances générées en mode simplifié');
        }

        function preselectRecommendedOptions() {
            const results = window.calculationResults;
            const puissanceRecommandee = parseInt(results.puissance_recommandee) || 12;

            // Gérer l'affichage des tarifs selon la puissance recommandée
            updateTarifVisibility(puissanceRecommandee);

            // Déterminer le tarif recommandé (en excluant Base si puissance > 6)
            const tarifs = getTarifsDisponibles(puissanceRecommandee);

            let tarifRecommande = 'base';
            let tarifMin = Infinity;

            // Comparer uniquement les tarifs disponibles
            tarifs.forEach(tarifKey => {
                const tarif = results.tarifs[tarifKey];
                const total = parseInt(tarif.total_annuel) || 0;
                if (total < tarifMin) {
                    tarifMin = total;
                    tarifRecommande = tarifKey;
                }
            });

            // Mise à jour des prix
            updateAllTarifPrices(results);

            // Sélection automatique
            setTimeout(() => {
                $('input[name="tarif_choisi"]').prop('checked', false);
                $('input[name="puissance_choisie"]').prop('checked', false);

                $(`input[name="tarif_choisi"][value="${tarifRecommande}"]`).prop('checked', true);
                $(`input[name="puissance_choisie"][value="${puissanceRecommandee}"]`).prop('checked', true);

                updateCalculsSelection(tarifRecommande, puissanceRecommandee);
            }, 100);
        }

        // Nouvelle fonction pour gérer la visibilité des tarifs
        function updateTarifVisibility(puissance) {
            const $tarifBase = $('.tarif-card-selection').has('input[value="base"]');
            const $inputBase = $('input[name="tarif_choisi"][value="base"]');

            if (puissance > 6) {
                // Masquer le tarif Base pour les puissances > 6 kVA
                $tarifBase.hide();

                // Si Base était sélectionné, décocher et sélectionner HC par défaut
                if ($inputBase.is(':checked')) {
                    $inputBase.prop('checked', false);
                    $('input[name="tarif_choisi"][value="hc"]').prop('checked', true);

                    // Recalculer avec HC
                    const tarif = 'hc';
                    if (window.calculationResults) {
                        updateCalculsSelection(tarif, puissance);
                        recalculateWithNewPower(tarif, puissance);
                    }
                }
            } else {
                // Afficher le tarif Base pour les puissances <= 6 kVA
                $tarifBase.show();
            }
        }

        // Fonction pour obtenir les tarifs disponibles selon la puissance
        function getTarifsDisponibles(puissance) {
            if (puissance > 6) {
                return ['hc', 'tempo']; // Seulement HC et Tempo pour > 6 kVA
            } else {
                return ['base', 'hc', 'tempo']; // Tous les tarifs pour <= 6 kVA
            }
        }

        function setupContactStep() {
            // Actions étape contact
        }

        function setupRecapStep() {
            if (currentStep === 10) {
                generateRecapitulatif();
            }
        }

        // ===============================
        // VALIDATION
        // ===============================

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
            const errors = [];

            const requiredFields = [
                { id: 'client_nom', label: 'Nom' },
                { id: 'client_prenom', label: 'Prénom' },
                { id: 'client_email', label: 'Email' },
                { id: 'client_telephone', label: 'Téléphone' }
            ];

            requiredFields.forEach(field => {
                const $field = stepElement.find(`#${field.id}`);
                const value = $field.val().trim();

                if (!value) {
                    isValid = false;
                    errors.push(`Le champ "${field.label}" est requis`);
                    $field.addClass('field-error');
                } else {
                    $field.removeClass('field-error').addClass('field-success');
                }
            });

            const email = stepElement.find('#client_email').val().trim();
            if (email && !isValidEmail(email)) {
                isValid = false;
                errors.push('L\'adresse email n\'est pas valide');
                stepElement.find('#client_email').addClass('field-error');
            }

            const phone = stepElement.find('#client_telephone').val().trim();
            if (phone && !isValidPhone(phone)) {
                isValid = false;
                errors.push('Le numéro de téléphone n\'est pas valide');
                stepElement.find('#client_telephone').addClass('field-error');
            }

            if (!isValid && errors.length > 0) {
                showValidationMessage(errors.join('<br>'));
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

        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function isValidPhone(phone) {
            const re = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
            return re.test(phone.replace(/\s/g, ''));
        }

        // ===============================
        // COLLECTE DE DONNÉES
        // ===============================

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

        // ===============================
        // CALCULS
        // ===============================

        function calculateResults() {
            console.log('Début du calcul...');

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

            const resultsHtml = `
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
                        <button class="btn btn-outline" onclick="location.reload()">
                            🔄 Nouvelle simulation
                        </button>
                        <button class="btn btn-primary btn-large" id="btn-subscribe">
                            📝 Je souscris
                        </button>
                    </div>
                </div>
            `;

            $('#results-container').html(resultsHtml);
            $('.results-summary').hide().fadeIn(600);

            setTimeout(() => {
                createConsumptionPieChart();
            }, 500);
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

        // ===============================
        // GESTION ÉTAPE 8
        // ===============================

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

        // ===============================
        // EMAIL
        // ===============================

        function sendEmail($btn, originalText, formData, clientData, results) {
            $btn.prop('disabled', true).html('<span class="spinner"></span> Envoi en cours...');

            const emailData = {
                action: 'htic_send_simulation_email',
                type: 'elec-residentiel',
                nonce: typeof hticSimulateur !== 'undefined' ? hticSimulateur.nonce : '',
                client: clientData,
                simulation: formData,
                results: results,
                date_simulation: new Date().toISOString()
            };

            let ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
                ajaxUrl = hticSimulateur.ajaxUrl;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: emailData,
                success: function (response) {
                    if (response.success) {
                        $('#email-confirmation').slideDown();
                        $('#email-display').text(clientData.email);
                        showNotification('✅ Email envoyé avec succès !', 'success');
                    } else {
                        showNotification('❌ Erreur : ' + (response.data || 'Erreur inconnue'), 'error');
                    }
                },
                error: function () {
                    showNotification('❌ Erreur de connexion', 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        }

        // ===============================
        // GÉNÉRATION DE CONTENU
        // ===============================

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

        function generateRecapitulatif() {
            const allData = collectAllFormData();
            const clientData = collectClientData();
            const results = window.calculationResults;

            const recapHTML = `
                <div class="recapitulatif-complet">
                    <h3>Récapitulatif de votre simulation</h3>
                    
                    <div class="recap-section">
                        <h4>🏠 Votre logement</h4>
                        <div class="recap-grid">
                            <div class="recap-item">
                                <span class="label">Type :</span>
                                <span class="value">${getLogementLabel(allData.type_logement)}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Surface :</span>
                                <span class="value">${allData.surface} m²</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Occupants :</span>
                                <span class="value">${allData.nb_personnes} personne(s)</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Isolation :</span>
                                <span class="value">${getIsolationLabel(allData.isolation)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recap-section">
                        <h4>⚡ Vos équipements</h4>
                        <div class="recap-grid">
                            <div class="recap-item">
                                <span class="label">Chauffage :</span>
                                <span class="value">${getHeatingLabel(allData.type_chauffage)}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Cuisson :</span>
                                <span class="value">${getCuissonLabel(allData.type_cuisson)}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Eau chaude :</span>
                                <span class="value">${allData.eau_chaude}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Éclairage :</span>
                                <span class="value">${getEclairageLabel(allData.type_eclairage)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recap-section highlight">
                        <h4>📋 Votre formule</h4>
                        <div class="recap-grid">
                            <div class="recap-item">
                                <span class="label">Tarif :</span>
                                <span class="value">${getTarifLabel(allData.tarif_choisi)}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Puissance :</span>
                                <span class="value">${allData.puissance_choisie} kVA</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Coût annuel :</span>
                                <span class="value">${getTotalAnnuelChoisi(results, allData.tarif_choisi)}€ TTC</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recap-section">
                        <h4>👤 Vos informations</h4>
                        <div class="recap-grid">
                            <div class="recap-item">
                                <span class="label">Nom :</span>
                                <span class="value">${clientData.nom} ${clientData.prenom}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Email :</span>
                                <span class="value">${clientData.email}</span>
                            </div>
                            <div class="recap-item">
                                <span class="label">Téléphone :</span>
                                <span class="value">${clientData.telephone}</span>
                            </div>
                            ${clientData.adresse ? `
                            <div class="recap-item">
                                <span class="label">Adresse :</span>
                                <span class="value">${clientData.adresse} ${clientData.code_postal} ${clientData.ville}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            $('#recap-container').html(recapHTML);
        }

        // ===============================
        // GRAPHIQUE
        // ===============================

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

        // ===============================
        // UTILITAIRES
        // ===============================

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

        function getTarifLabel(tarif) {
            const labels = {
                'base': 'Base TRV',
                'hc': 'Heures Creuses TRV',
                'tempo': 'Tempo TRV'
            };
            return labels[tarif] || tarif;
        }

        function getTotalAnnuelChoisi(results, tarif) {
            if (!results || !results.tarifs || !results.tarifs[tarif]) return 0;
            return parseInt(results.tarifs[tarif].total_annuel) || 0;
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

        function getLogementLabel(type) {
            const labels = {
                'maison': '🏠 Maison',
                'appartement': '🏢 Appartement'
            };
            return labels[type] || type;
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

        function getEclairageLabel(code) {
            const labels = {
                'led': 'LED (basse consommation)',
                'incandescence_halogene': 'Incandescence ou halogène'
            };
            return labels[code] || code;
        }

        // ===============================
        // API PUBLIQUE
        // ===============================

        window.HticSimulateurData = {
            getCurrentData: () => formData,
            getAllData: collectAllFormData,
            getConfigData: () => configData,
            getCurrentStep: () => currentStep,
            goToStep: goToStep
        };

    });

})(jQuery);