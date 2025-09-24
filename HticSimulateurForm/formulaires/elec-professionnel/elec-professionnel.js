/**
 * SIMULATEUR ÉLECTRICITÉ PROFESSIONNEL
 * Plugin WordPress pour GES Solutions
 * 
 * Gère le processus complet de simulation électricité professionnelle en 5 étapes :
 * 1. Configuration technique (catégorie, puissance, formule tarifaire)
 * 2. Résultats et comparaison des tarifs
 * 3. Sélection de l'offre et type de contrat
 * 4. Informations entreprise et responsable
 * 5. Récapitulatif et finalisation
 * 
 * @version 1.0.0
 * @author HTIC / GES Solutions
 */

jQuery(document).ready(function ($) {
    'use strict';

    // ===========================================
    // CONFIGURATION ET VARIABLES GLOBALES
    // ===========================================

    let currentStep = 1;
    const totalSteps = 5;
    let formData = {};
    let configData = {};
    let uploadedFiles = {};
    let calculResults = {};

    // ===========================================
    // INITIALISATION PRINCIPALE
    // ===========================================

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupProLogic();
        setupFileUpload();
        updateUI();
    }

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config-pro');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
            } catch (e) {
                configData = {};
            }
        }

        if (!window.hticSimulateur && (configData.ajax_url || configData.nonce)) {
            window.hticSimulateur = {
                ajaxUrl: configData.ajax_url || '/wp-admin/admin-ajax.php',
                nonce: configData.nonce || configData.calculate_nonce,
                type: 'elec-professionnel'
            };
        }
    }

    // ===========================================
    // LOGIQUE MÉTIER PROFESSIONNELLE
    // ===========================================

    function setupProLogic() {
        $('input[name="categorie"]').on('change', function () {
            const categorie = $(this).val();
            updateEligibiliteTRV(categorie);
            updateFormulesTarifaires(categorie);
            updatePuissanceOptions(categorie);
        });

        $('input[name="formule_tarifaire"]').on('change', function () {
            const formule = $(this).val();
            const categorie = $('input[name="categorie"]:checked').val();
            updatePuissanceOptions(categorie, formule);
        });

        $('#conso_annuelle').on('input', function () {
            updateConsoHelp(parseInt($(this).val()));
        });

        const categorieInitiale = $('input[name="categorie"]:checked').val() || 'BT < 36 kVA';
        updateEligibiliteTRV(categorieInitiale);
        updateFormulesTarifaires(categorieInitiale);
        updatePuissanceOptions(categorieInitiale);
    }

    function updateEligibiliteTRV(categorie) {
        const $eligibiliteContainer = $('input[name="eligible_trv"]').closest('.form-group');
        $('.trv-info-message').remove();

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            $eligibiliteContainer.show();
            showTRVConditions($eligibiliteContainer);
            if (!$('input[name="eligible_trv"]:checked').length) {
                $('input[name="eligible_trv"][value="oui"]').prop('checked', true);
            }
        } else {
            $eligibiliteContainer.hide();
            $('input[name="eligible_trv"][value="non"]').prop('checked', true);
            showNonEligibilityWarning(categorie);
        }
    }

    function showTRVConditions($container) {
        const conditionsHTML = `
            <div class="trv-info-message">
                <div class="info-box">
                    <div class="info-icon">ℹ️</div>
                    <div class="info-content">
                        <strong>Conditions d'éligibilité au Tarif Réglementé (TRV) :</strong>
                        <ul class="conditions-list">
                            <li>Puissance souscrite ≤ 36 kVA</li>
                            <li>Moins de 10 salariés</li>
                            <li>Chiffre d'affaires annuel < 2 millions €</li>
                            <li>Ou recettes annuelles < 2 millions € pour les collectivités</li>
                        </ul>
                        <small>Si vous ne remplissez pas ces conditions, sélectionnez "Non"</small>
                    </div>
                </div>
            </div>
        `;
        $container.after(conditionsHTML);
    }

    function showNonEligibilityWarning(categorie) {
        const messageHTML = `
            <div class="trv-info-message warning">
                <div class="warning-box">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <strong>Non éligible au Tarif Réglementé</strong>
                        <p>Les entreprises en ${categorie} ne sont pas éligibles au tarif réglementé de vente (TRV). 
                        Vous bénéficierez automatiquement d'une offre de marché adaptée.</p>
                    </div>
                </div>
            </div>
        `;
        $('input[name="categorie"]').closest('.form-group').after(messageHTML);
    }

    function updateFormulesTarifaires(categorie) {
        const $formuleContainer = $('input[name="formule_tarifaire"]').closest('.radio-group');
        $('.formule-note').remove();

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            $formuleContainer.find('.radio-card').show();
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text strong').text('Option Base');
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text span').text('Tarif unique toute la journée');
        } else if (categorie === 'BT > 36 kVA') {
            setupHTATarification($formuleContainer, 'BT > 36 kVA');
        } else if (categorie === 'HTA') {
            setupHTATarification($formuleContainer, 'HTA');
        }
    }

    function setupHTATarification($formuleContainer, categorie) {
        $formuleContainer.find('.radio-card').hide();
        $formuleContainer.find('.radio-card:has(input[value="Base"])').show();
        $('input[name="formule_tarifaire"][value="Base"]').prop('checked', true);

        $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text strong').text('Tarif HTA');
        $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text span').text(
            categorie === 'HTA' ? 'Haute tension' : 'Tarification haute tension applicable'
        );

        const note = categorie === 'HTA' ? 'Tarification haute tension sur mesure' : 'Pour BT > 36 kVA, tarification HTA appliquée';
        $formuleContainer.after(`<p class="formule-note">ℹ️ ${note}</p>`);
    }

    function updatePuissanceOptions(categorie, formule = null) {
        const $puissanceSelect = $('#puissance');
        const currentPuissance = $puissanceSelect.val();

        if (!formule) {
            formule = $('input[name="formule_tarifaire"]:checked').val() || 'Base';
        }

        let options = '';
        let availablePuissances = [];

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            if (formule === 'Base') {
                availablePuissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];
                options = generatePuissanceOptions(availablePuissances);
            } else if (formule === 'Heures creuses') {
                availablePuissances = [6, 9, 12, 15, 18, 24, 30, 36];
                options = generatePuissanceOptions(availablePuissances, 6);
            }
        } else {
            availablePuissances = [36];
            const label = categorie === 'HTA' ? 'HTA' : 'Tarif HTA (>36 kVA)';
            options = `<option value="">Choisir...</option><option value="36" selected>${label}</option>`;
        }

        $puissanceSelect.html(options);
        handlePuissanceSelection(currentPuissance, availablePuissances, formule);
        updatePuissanceHelp(categorie, formule);
    }

    function generatePuissanceOptions(puissances, start = 3) {
        let options = '<option value="">Choisir...</option>';
        puissances.forEach(p => {
            options += `<option value="${p}">${p} kVA</option>`;
        });
        return options;
    }

    function handlePuissanceSelection(currentPuissance, availablePuissances, formule) {
        const $puissanceSelect = $('#puissance');

        if (currentPuissance && availablePuissances.includes(parseInt(currentPuissance))) {
            $puissanceSelect.val(currentPuissance);
        } else if (currentPuissance && !availablePuissances.includes(parseInt(currentPuissance))) {
            if (formule === 'Heures creuses' && parseInt(currentPuissance) === 3) {
                $puissanceSelect.val('6');
                showInfoMessage('La puissance a été ajustée à 6 kVA (minimum pour l\'option Heures Creuses)');
            } else {
                const closestPuissance = findClosestPuissance(parseInt(currentPuissance), availablePuissances);
                if (closestPuissance) {
                    $puissanceSelect.val(closestPuissance);
                }
            }
        } else if (!currentPuissance) {
            $puissanceSelect.val(formule === 'Base' ? '6' : '9');
        }
    }

    function findClosestPuissance(targetPuissance, availablePuissances) {
        if (availablePuissances.length === 0) return null;
        return availablePuissances.reduce((prev, curr) => {
            return Math.abs(curr - targetPuissance) < Math.abs(prev - targetPuissance) ? curr : prev;
        });
    }

    function updateConsoHelp(conso) {
        const $helpText = $('#conso_annuelle').closest('.form-group').find('.form-help');
        if (conso < 10000) {
            $helpText.text('Petite consommation - Bureau ou petit commerce');
        } else if (conso < 50000) {
            $helpText.text('Consommation moyenne - PME ou commerce');
        } else if (conso < 200000) {
            $helpText.text('Grande consommation - Industrie ou gros consommateur');
        } else {
            $helpText.text('Très grande consommation - Industrie lourde');
        }
    }

    function updatePuissanceHelp(categorie, formule) {
        const $helpText = $('#puissance').closest('.form-group').find('.form-help');

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            if (formule === 'Base') {
                $helpText.text('Toutes les puissances disponibles de 3 à 36 kVA');
            } else if (formule === 'Heures creuses') {
                $helpText.text('Heures Creuses : disponible à partir de 6 kVA uniquement');
            }
        } else if (categorie === 'BT > 36 kVA') {
            $helpText.text('Pour BT > 36 kVA, tarification HTA applicable sur devis');
        } else if (categorie === 'HTA') {
            $helpText.text('Tarification haute tension sur devis personnalisé');
        }
    }

    function showInfoMessage(message) {
        $('.info-toast').remove();
        const infoToast = $(`
            <div class="info-toast">
                <span class="info-icon">ℹ️</span>
                <span class="info-text">${message}</span>
            </div>
        `);

        $('#puissance').closest('.form-group').append(infoToast);
        setTimeout(() => infoToast.addClass('show'), 10);
        setTimeout(() => {
            infoToast.removeClass('show');
            setTimeout(() => infoToast.remove(), 300);
        }, 4000);
    }

    // ===========================================
    // NAVIGATION ENTRE ÉTAPES
    // ===========================================

    function setupStepNavigation() {
        $('#btn-next-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                nextStep();
            }
        });

        $('#btn-previous-pro').on('click', function () {
            saveCurrentStepData();
            prevStep();
        });

        $('#btn-calculate-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        });

        $('#btn-restart-pro').on('click', function () {
            if (confirm('Voulez-vous vraiment recommencer la simulation ?')) {
                restartSimulation();
            }
        });

        $('.step').on('click', function () {
            const targetStep = parseInt($(this).data('step'));
            if (targetStep < currentStep || targetStep === 1) {
                saveCurrentStepData();
                goToStep(targetStep);
            }
        });
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            updateUI();
            scrollToTop();
            setupStepSpecificLogic();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateUI();
            scrollToTop();
        }
    }

    function goToStep(stepNumber) {
        if (stepNumber >= 1 && stepNumber <= totalSteps) {
            currentStep = stepNumber;
            updateUI();
            scrollToTop();
            setupStepSpecificLogic();
        }
    }

    function setupStepSpecificLogic() {
        if (currentStep === 3) {
            setupSelectionStep();
        } else if (currentStep === 4) {
            setupContactStep();
        } else if (currentStep === 5) {
            setupRecapStep();
        }
    }

    function updateUI() {
        $('.form-step').removeClass('active');
        $(`.form-step[data-step="${currentStep}"]`).addClass('active');

        const progressPercent = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');

        $('.step').removeClass('active completed');
        for (let i = 1; i < currentStep; i++) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        }
        $(`.step[data-step="${currentStep}"]`).addClass('active');

        updateNavigationButtons();
    }

    function updateNavigationButtons() {
        $('#btn-previous-pro').toggle(currentStep > 1);

        if (currentStep === totalSteps) {
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').show();
        } else if (currentStep === 1) {
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').show();
            $('#btn-restart-pro').hide();
        } else if (currentStep === 2) {
            $('#btn-next-pro').show().html('<span class="btn-icon">✅</span> Je souscris');
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').hide();
        } else {
            $('#btn-next-pro').show().html('Suivant →');
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').hide();
        }
    }

    function scrollToTop() {
        $('html, body').animate({
            scrollTop: $('.simulateur-header').offset().top - 20
        }, 500);
    }

    // ===========================================
    // VALIDATION DES DONNÉES
    // ===========================================

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        currentStepElement.find('.field-error').removeClass('field-error');

        switch (currentStep) {
            case 1:
                isValid = validateStep1(currentStepElement);
                break;
            case 3:
                isValid = validateStep3(currentStepElement);
                break;
            case 4:
                isValid = validateStep4(currentStepElement);
                break;
            default:
                isValid = true;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires');
        }

        return isValid;
    }

    function validateStep1(stepElement) {
        let isValid = true;
        const categorie = stepElement.find('input[name="categorie"]:checked').val();

        const requiredFields = ['categorie', 'puissance', 'formule_tarifaire', 'conso_annuelle'];

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            requiredFields.push('eligible_trv');
        }

        requiredFields.forEach(field => {
            const $field = stepElement.find(`[name="${field}"]`);

            if ($field.attr('type') === 'radio') {
                if (!stepElement.find(`input[name="${field}"]:checked`).length) {
                    $field.addClass('field-error');
                    isValid = false;
                }
            } else {
                if (!$field.val()) {
                    $field.addClass('field-error');
                    isValid = false;
                }
            }
        });

        const consoValue = parseFloat(stepElement.find('#conso_annuelle').val());
        if (consoValue && (consoValue < 1000 || consoValue > 1000000)) {
            stepElement.find('#conso_annuelle').addClass('field-error');
            showValidationMessage('La consommation doit être entre 1 000 et 1 000 000 kWh/an');
            isValid = false;
        }

        return isValid;
    }

    function validateStep3(stepElement) {
        let isValid = true;

        if (!stepElement.find('input[name="tarif_choisi"]:checked').length) {
            showValidationMessage('Veuillez sélectionner un tarif');
            isValid = false;
        }

        if (!stepElement.find('input[name="type_contrat"]:checked').length) {
            showValidationMessage('Veuillez sélectionner un type de contrat');
            isValid = false;
        }

        return isValid;
    }

    function validateStep4(stepElement) {
        let isValid = true;

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

        if (!validateSiret(stepElement)) isValid = false;
        if (!validateEmail(stepElement)) isValid = false;
        if (!validateRequiredCheckboxes(stepElement)) isValid = false;
        if (!validateRequiredFiles()) isValid = false;

        return isValid;
    }

    function validateSiret(stepElement) {
        const siret = stepElement.find('#siret').val();
        if (siret && siret.length !== 14) {
            stepElement.find('#siret').addClass('field-error');
            showValidationMessage('Le SIRET doit contenir exactement 14 chiffres');
            return false;
        }
        return true;
    }

    function validateEmail(stepElement) {
        const email = stepElement.find('#responsable_email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            stepElement.find('#responsable_email').addClass('field-error');
            showValidationMessage('Format d\'email invalide');
            return false;
        }
        return true;
    }

    function validateRequiredCheckboxes(stepElement) {
        const requiredCheckboxes = ['accept_conditions_pro', 'certifie_pouvoir'];

        for (const checkbox of requiredCheckboxes) {
            if (!stepElement.find(`#${checkbox}`).is(':checked')) {
                stepElement.find(`#${checkbox}`).addClass('field-error');
                showValidationMessage('Veuillez accepter les conditions obligatoires');
                return false;
            }
        }
        return true;
    }

    function validateRequiredFiles() {
        if (!uploadedFiles.kbis_file) {
            showValidationMessage('Le K-bis est obligatoire');
            return false;
        }

        if (!uploadedFiles.rib_entreprise) {
            showValidationMessage('Le RIB de l\'entreprise est obligatoire');
            return false;
        }

        return true;
    }

    function setupFormValidation() {
        $('#responsable_email').off('blur').on('blur', validateEmailField);
        $('#responsable_telephone').off('blur').on('blur', validatePhoneField);
        $('#entreprise_code_postal').off('blur').on('blur', validatePostalCodeField);
        $('#siret').off('input').on('input', validateSiretField);
    }

    function validateEmailField() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $(this).addClass('field-error');
            showValidationMessage('Format d\'email invalide');
        } else {
            $(this).removeClass('field-error').addClass('field-success');
        }
    }

    function validatePhoneField() {
        const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
        if (tel && tel.length < 10) {
            $(this).addClass('field-error');
            showValidationMessage('Numéro de téléphone trop court');
        } else {
            $(this).removeClass('field-error').addClass('field-success');
        }
    }

    function validatePostalCodeField() {
        const cp = $(this).val();
        if (cp && !/^[0-9]{5}$/.test(cp)) {
            $(this).addClass('field-error');
            showValidationMessage('Le code postal doit contenir 5 chiffres');
        } else {
            $(this).removeClass('field-error').addClass('field-success');
        }
    }

    function validateSiretField() {
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

    // ===========================================
    // CALCULS ET RÉSULTATS
    // ===========================================

    function calculateResults() {
        const allData = collectAllFormData();

        if (!allData.conso_annuelle || !allData.puissance || !allData.categorie) {
            showValidationMessage('Données manquantes pour le calcul');
            return;
        }

        currentStep = 2;
        updateUI();

        $('#results-container-pro').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
                <small>Analyse de votre contrat électrique professionnel...</small>
            </div>
        `);

        sendToCalculator(allData);
    }

    function sendToCalculator(userData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-professionnel',
            user_data: userData,
            config_data: configData
        };

        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        }

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
            success: handleCalculationSuccess,
            error: handleCalculationError
        });
    }

    function handleCalculationSuccess(response) {
        if (response.success && response.data) {
            calculResults = response.data;
            displayResults(response.data);
        } else {
            displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
        }
    }

    function handleCalculationError(xhr, status, error) {
        displayError('Erreur de connexion lors du calcul');
    }

    function displayResults(results) {
        if (!results || !results.offres || !results.consommation_annuelle) {
            displayError('Données de résultats incomplètes');
            return;
        }

        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const puissance = parseInt(results.puissance) || parseInt(formData.puissance) || 0;
        const meilleureOffre = results.meilleure_offre;
        const economieMax = parseFloat(results.economie_max) || 0;

        const offresCards = results.offres.map(offre => createOffreCard(offre)).join('');
        const resultsHtml = generateResultsHTML(consommationAnnuelle, meilleureOffre, economieMax, offresCards);

        $('#results-container-pro').html(resultsHtml);
        $('.results-summary-pro').hide().fadeIn(600);
    }

    function createOffreCard(offre) {
        const isRecommended = offre.meilleure;
        const totalAnnuel = Math.round(parseFloat(offre.total_ttc));
        const totalMensuel = Math.round(totalAnnuel / 10);

        let typeClass = 'offre-marche';
        if (offre.nom.includes('TRV') || offre.nom.includes('Bleu')) typeClass = 'trv';
        if (offre.nom.includes('Tempo')) typeClass = 'tempo';
        if (offre.nom.includes('française') || offre.nom.includes('100%')) typeClass = 'verte';

        return `
            <div class="tarif-card ${typeClass} ${isRecommended ? 'recommended' : ''}">
                <h4>${offre.nom}</h4>
                <div class="tarif-prix">${totalAnnuel.toLocaleString()}€<span>/an HTVA</span></div>
                <div class="tarif-mensuel">${totalMensuel.toLocaleString()}€/mois HTVA (sur 10 mois)</div>
                <div class="tarif-details">
                    <div>Abonnement : ${Math.round(offre.abonnement_annuel).toLocaleString()}€/an HTVA</div>
                    <div>Consommation : ${Math.round(offre.cout_consommation).toLocaleString()}€/an HTVA</div>
                    <div>Prix : ${offre.details}</div>
                </div>
                ${isRecommended ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
            </div>
        `;
    }

    function generateResultsHTML(consommationAnnuelle, meilleureOffre, economieMax, offresCards) {
        const economiesSection = economieMax > 0 ? `
            <div class="economies">
                <h4>Économies potentielles</h4>
                <p><strong>Jusqu'à ${Math.round(economieMax).toLocaleString()}€ HTVA/an</strong> en choisissant le tarif optimal !</p>
                <small>Le tarif ${meilleureOffre.nom} est actuellement le plus avantageux pour votre profil.</small>
            </div>
        ` : '';

        return `
            <div class="results-summary-pro">
                <div class="result-card main-result">
                    <div class="result-icon">🏢</div>
                    <h3>Votre estimation professionnelle</h3>
                    <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                    <div class="result-price">${Math.round(meilleureOffre.total_ttc).toLocaleString()}€ <span>/an HTVA</span></div>
                    <p>Soit environ <strong>${Math.round(meilleureOffre.total_ttc / 10).toLocaleString()}€/mois HTVA</strong> (sur 10 mois)</p>
                    <p class="tva-note">+ TVA 20% (non incluse)</p>
                </div>
                
                <div class="tarifs-comparison">
                    <h3>Comparaison des tarifs professionnels (HTVA)</h3>
                    <div class="tarifs-grid">${offresCards}</div>
                    ${economiesSection}
                </div>
            </div>
        `;
    }

    function displayError(message) {
        $('#results-container-pro').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button class="btn btn-primary" onclick="location.reload()">Recharger</button>
                    <button class="btn btn-secondary" onclick="goToStep(1)">← Retour à la configuration</button>
                </div>
            </div>
        `);
    }

    // ===========================================
    // GESTION DES ÉTAPES SPÉCIFIQUES
    // ===========================================

    function setupSelectionStep() {
        if (!calculResults.offres) {
            return;
        }

        displayTarifCards();
        $('.field-group:has(.puissance-selection)').hide();

        $('input[name="tarif_choisi"]').off('change').on('change', updateSelectionSummary);
        $('input[name="type_contrat"]').off('change').on('change', updateSelectionSummary);

        selectBestOffer();
    }

    function displayTarifCards() {
        if (!calculResults.offres) return;

        $('.tarif-card-selection').each(function () {
            $(this).hide();
        });

        calculResults.offres.forEach(offre => {
            const totalAnnuel = Math.round(parseFloat(offre.total_ttc));
            let $card = null;

            if (offre.nom.toLowerCase().includes('bleu') || offre.nom.toLowerCase().includes('trv')) {
                $card = $('#tarif_base_pro').closest('.tarif-card-selection');
                $('#prix-base-pro .price-amount').text(totalAnnuel.toLocaleString());
                $('#prix-base-pro .price-period').text('€/an HTVA');
                $card.find('.tarif-header h4').text(offre.nom);
                $card.show();
            } else if (offre.nom.toLowerCase().includes('tempo')) {
                $card = $('#tarif_tempo_pro').closest('.tarif-card-selection');
                $('#prix-tempo-pro .price-amount').text(totalAnnuel.toLocaleString());
                $('#prix-tempo-pro .price-period').text('€/an HTVA');
                $card.show();
            } else if (offre.nom.toLowerCase().includes('française') || offre.nom.toLowerCase().includes('100%')) {
                $card = $('#tarif_francaise_pro').closest('.tarif-card-selection');
                $('#prix-francaise-pro .price-amount').text(totalAnnuel.toLocaleString());
                $('#prix-francaise-pro .price-period').text('€/an HTVA');
                $card.show();
            } else {
                $card = $('#tarif_hc_pro').closest('.tarif-card-selection');
                $('#prix-hc-pro .price-amount').text(totalAnnuel.toLocaleString());
                $('#prix-hc-pro .price-period').text('€/an HTVA');
                $card.find('.tarif-header h4').text(offre.nom);
                $card.show();
            }
        });
    }

    function selectBestOffer() {
        if (!calculResults.meilleure_offre) return;

        const meilleureOffre = calculResults.meilleure_offre.nom.toLowerCase();

        if (meilleureOffre.includes('bleu') || meilleureOffre.includes('trv')) {
            $('#tarif_base_pro').prop('checked', true);
        } else if (meilleureOffre.includes('tempo')) {
            $('#tarif_tempo_pro').prop('checked', true);
        } else if (meilleureOffre.includes('française') || meilleureOffre.includes('100%')) {
            $('#tarif_francaise_pro').prop('checked', true);
        } else {
            $('#tarif_hc_pro').prop('checked', true);
        }

        updateSelectionSummary();
    }

    function updateSelectionSummary() {
        const tarifChoisi = $('input[name="tarif_choisi"]:checked').val();
        const typeContrat = $('input[name="type_contrat"]:checked').val() || 'principal';

        if (!tarifChoisi) {
            $('#calculs-selection-pro').html(`
                <div class="info-message">
                    <p>Sélectionnez un tarif pour continuer</p>
                </div>
            `);
            return;
        }

        const offreSelectionnee = findSelectedOffer(tarifChoisi);

        if (offreSelectionnee) {
            const summaryHTML = generateSelectionSummary(offreSelectionnee, typeContrat);
            $('#calculs-selection-pro').html(summaryHTML);
        }
    }

    function findSelectedOffer(tarifChoisi) {
        if (!calculResults.offres) return null;

        return calculResults.offres.find(offre => {
            const nomOffre = offre.nom.toLowerCase();
            if (tarifChoisi === 'base' && (nomOffre.includes('bleu') || nomOffre.includes('trv'))) return true;
            if (tarifChoisi === 'tempo' && nomOffre.includes('tempo')) return true;
            if (tarifChoisi === 'francaise' && (nomOffre.includes('française') || nomOffre.includes('100%'))) return true;
            if (tarifChoisi === 'hc') return true;
            return false;
        });
    }

    function generateSelectionSummary(offreSelectionnee, typeContrat) {
        const totalAnnuel = Math.round(parseFloat(offreSelectionnee.total_ttc));
        const totalMensuel = Math.round(totalAnnuel / 10);
        const tva = Math.round(totalAnnuel * 0.2);
        const totalTTC = totalAnnuel + tva;
        const puissance = calculResults.puissance || formData.puissance;
        const consommation = calculResults.consommation_annuelle || formData.conso_annuelle;

        return `
            <div class="selection-summary">
                <div class="summary-header">
                    <h4>Récapitulatif de votre sélection</h4>
                </div>
                <div class="summary-content">
                    <div class="summary-item">
                        <span class="item-label">Offre sélectionnée:</span>
                        <span class="item-value">${offreSelectionnee.nom}</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Type de contrat:</span>
                        <span class="item-value">${typeContrat === 'principal' ? 'Contrat principal' : 'Site secondaire'}</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-item">
                        <span class="item-label">Puissance souscrite:</span>
                        <span class="item-value">${puissance} kVA</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Consommation annuelle:</span>
                        <span class="item-value">${parseInt(consommation).toLocaleString()} kWh</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-item">
                        <span class="item-label">Coût annuel HTVA:</span>
                        <span class="item-value">${totalAnnuel.toLocaleString()}€</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">TVA (20%):</span>
                        <span class="item-value">+${tva.toLocaleString()}€</span>
                    </div>
                    <div class="summary-item highlight">
                        <span class="item-label">Total annuel TTC:</span>
                        <span class="item-value">${totalTTC.toLocaleString()}€</span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Soit par mois HTVA (sur 10 mois):</span>
                        <span class="item-value">${totalMensuel.toLocaleString()}€</span>
                    </div>
                </div>
            </div>
        `;
    }

    function setupContactStep() {
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
    }

    function setupRecapStep() {
        generateRecapitulatifFinalPro();
    }

    // ===========================================
    // GESTION DES FICHIERS
    // ===========================================

    function setupFileUpload() {
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

                setupDragAndDrop(card, fileType, resultDiv);

                fileInput.on('change', function () {
                    if (this.files && this.files[0]) {
                        handleFileUpload(this.files[0], fileType, resultDiv);
                    }
                });
            }
        });
    }

    function setupDragAndDrop(card, fileType, resultDiv) {
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
                handleFileUpload(files[0], fileType, resultDiv);
            }
        });
    }

    function handleFileUpload(file, fileType, resultDiv) {
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showValidationMessage('Format non supporté. Utilisez PDF, JPG ou PNG.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showValidationMessage('Le fichier est trop volumineux (max 5 Mo)');
            return;
        }

        uploadedFiles[fileType] = file;

        resultDiv.html(`
            <div class="upload-success">
                <span class="success-icon">✅</span>
                <span class="file-name">${file.name}</span>
                <button type="button" class="remove-file" onclick="removeUploadedFile('${fileType}', this)">×</button>
            </div>
        `);
    }

    window.removeUploadedFile = function (fileType, button) {
        delete uploadedFiles[fileType];
        $(button).closest('.upload-result').empty();
        $(`input[name="${fileType}"]`).val('');
    };

    // ===========================================
    // RÉCAPITULATIF FINAL
    // ===========================================

    function generateRecapitulatifFinalPro() {
        const allData = collectAllFormData();
        const tarifChoisi = allData.tarif_choisi;
        const typeContrat = allData.type_contrat || 'principal';

        const offreSelectionnee = findSelectedOffer(tarifChoisi);
        const totalAnnuel = offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc)) : 0;
        const totalMensuel = offreSelectionnee ? Math.round(totalAnnuel / 10) : 0;
        const tva = Math.round(totalAnnuel * 0.2);
        const totalTTC = totalAnnuel + tva;
        const puissance = calculResults.puissance || formData.puissance || '--';
        const consommation = calculResults.consommation_annuelle || formData.conso_annuelle || 0;

        const recapHtml = generateRecapHTML(allData, offreSelectionnee, totalAnnuel, totalMensuel, totalTTC, puissance, consommation, typeContrat);

        $('#recap-container-final-pro').html(recapHtml);

        $('#btn-finaliser-souscription-pro').off('click').on('click', function () {
            finalizeSouscriptionPro();
        });
    }

    function generateRecapHTML(allData, offreSelectionnee, totalAnnuel, totalMensuel, totalTTC, puissance, consommation, typeContrat) {
        return `
            <div class="recap-complet">
                <div class="formule-selectionnee">
                    <div class="formule-header">
                        <span class="formule-icon">⚡</span>
                        <h3>Votre formule électricité professionnelle</h3>
                    </div>
                    
                    <div class="formule-details">
                        <div class="formule-main">
                            <div class="formule-item tarif">
                                <div class="formule-label">Offre sélectionnée</div>
                                <div class="formule-value">${offreSelectionnee ? offreSelectionnee.nom : '--'}</div>
                                <div class="formule-badge">${getTypeBadge(offreSelectionnee)}</div>
                            </div>
                            
                            <div class="formule-divider"></div>
                            
                            <div class="formule-item puissance">
                                <div class="formule-label">Puissance souscrite</div>
                                <div class="formule-value">${puissance} kVA</div>
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
                                <div class="cost-icon">⚡</div>
                                <div class="cost-details">
                                    <div class="cost-label">Consommation prévisionnelle</div>
                                    <div class="cost-amount">${parseInt(consommation).toLocaleString()} <span>kWh/an</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${generateEnterpriseSection(allData)}
                ${generateConfigurationSection(allData)}
                ${generateResponsableSection(allData, typeContrat)}
                ${generateDocumentsSection(allData)}
                
            </div>
        `;
    }

    function generateEnterpriseSection(allData) {
        return `
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
                        <span class="detail-value">${formatSiret(allData.siret) || '--'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Code NAF/APE</span>
                        <span class="detail-value">${allData.code_naf || '--'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Type de contrat</span>
                        <span class="detail-value highlight">
                            ${allData.type_contrat === 'principal' ? '🏢 Contrat principal' : '🏪 Site secondaire'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    function generateConfigurationSection(allData) {
        return `
            <div class="recap-section-detail">
                <h3 class="section-header-detail">
                    <span class="section-icon-detail">⚡</span>
                    Configuration électrique
                </h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Catégorie de raccordement</span>
                        <span class="detail-value">${allData.categorie || '--'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Formule tarifaire</span>
                        <span class="detail-value">${allData.formule_tarifaire || '--'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Éligibilité TRV</span>
                        <span class="detail-value">
                            ${allData.eligible_trv === 'oui' ? '✅ Éligible' : '❌ Non éligible'}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Consommation annuelle</span>
                        <span class="detail-value">${parseInt(allData.conso_annuelle || 0).toLocaleString()} kWh</span>
                    </div>
                </div>
            </div>
        `;
    }

    function generateResponsableSection(allData, typeContrat) {
        return `
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
                        <span class="detail-value">${formatPhone(allData.responsable_telephone) || '--'}</span>
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
                
                ${(allData.pdl_entreprise || allData.prm_entreprise) ? `
                <div style="grid-column: 1 / -1; margin-top: 1rem;">
                    ${allData.pdl_entreprise ? `
                    <div class="detail-item">
                        <span class="detail-label">Point de livraison (PDL)</span>
                        <span class="detail-value">${allData.pdl_entreprise}</span>
                    </div>
                    ` : ''}
                    ${allData.prm_entreprise ? `
                    <div class="detail-item" style="margin-top: 0.5rem;">
                        <span class="detail-label">N° Point Référence Mesure (PRM)</span>
                        <span class="detail-value">${allData.prm_entreprise}</span>
                    </div>
                    ` : ''}
                </div>
                ` : ''}
            </div>
        `;
    }

    function generateDocumentsSection(allData) {
        return `
            <div class="recap-section-detail">
                <h3 class="section-header-detail">
                    <span class="section-icon-detail">📎</span>
                    Documents et validations
                </h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Documents fournis</span>
                        <span class="detail-value">
                            ${uploadedFiles.kbis_file ? '✅ K-bis<br>' : '❌ K-bis manquant<br>'}
                            ${uploadedFiles.rib_entreprise ? '✅ RIB entreprise<br>' : '❌ RIB entreprise manquant<br>'}
                            ${uploadedFiles.mandat_signature ? '✅ Mandat de signature' : 'ℹ️ Mandat de signature (optionnel)'}
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
        `;
    }

    // ===========================================
    // GESTION DES DONNÉES
    // ===========================================

    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        const categorie = $('input[name="categorie"]:checked').val();

        currentStepElement.find('input, select').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name || type === 'file') return;

            if (name === 'eligible_trv' && (categorie === 'BT > 36 kVA' || categorie === 'HTA')) {
                formData[name] = 'non';
                return;
            }

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[name] = $field.val();
                }
            } else if (type === 'checkbox') {
                formData[name] = $field.is(':checked');
            } else {
                formData[name] = $field.val();
            }
        });
    }

    function collectAllFormData() {
        formData = {};

        $('.form-step').each(function () {
            $(this).find('input, select').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (!name || type === 'file') return;

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                } else if (type === 'checkbox') {
                    formData[name] = $field.is(':checked');
                } else {
                    formData[name] = $field.val();
                }
            });
        });

        return formData;
    }

    // ===========================================
    // SOUMISSION FINALE
    // ===========================================

    function finalizeSouscriptionPro() {
        const allData = collectAllFormData();

        if (!validateCurrentStep()) {
            showValidationMessage('Veuillez vérifier toutes les informations obligatoires');
            return;
        }

        if (!allData.raison_sociale || !allData.siret || !allData.responsable_email) {
            showValidationMessage('Informations entreprise incomplètes');
            return;
        }

        if (!uploadedFiles.kbis_file || !uploadedFiles.rib_entreprise) {
            showValidationMessage('Documents obligatoires manquants (K-bis et RIB)');
            return;
        }

        if (!allData.accept_conditions_pro || !allData.certifie_pouvoir) {
            showValidationMessage('Veuillez accepter les conditions obligatoires');
            return;
        }

        envoyerDonneesProfessionnellesAuServeur();
    }

    function envoyerDonneesProfessionnellesAuServeur() {
        const allFormData = collectAllFormData();
        const results = calculResults;
        const uploads = uploadedFiles;

        if (!allFormData || !results) {
            showNotification('Données incomplètes', 'error');
            return;
        }

        const offreSelectionnee = findSelectedOffer(allFormData.tarif_choisi);
        const formData = new FormData();

        formData.append('action', 'process_electricity_form');
        formData.append('nonce', hticSimulateur.nonce);

        const dataToSend = createDataToSend(allFormData, results, offreSelectionnee);
        formData.append('form_data', JSON.stringify(dataToSend));

        if (uploads.kbis_file) formData.append('kbis_file', uploads.kbis_file);
        if (uploads.rib_entreprise) formData.append('rib_entreprise', uploads.rib_entreprise);
        if (uploads.mandat_signature) formData.append('mandat_signature', uploads.mandat_signature);

        afficherLoaderProfessionnel();

        $.ajax({
            url: hticSimulateur.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000,
            success: handleSubmissionSuccess,
            error: handleSubmissionError
        });
    }

    function createDataToSend(allFormData, results, offreSelectionnee) {
        return {
            simulationType: 'elec-professionnel',
            companyName: allFormData.raison_sociale || '',
            legalForm: allFormData.forme_juridique || '',
            siret: allFormData.siret || '',
            nafCode: allFormData.code_naf || '',
            companyAddress: allFormData.entreprise_adresse || '',
            companyPostalCode: allFormData.entreprise_code_postal || '',
            companyCity: allFormData.entreprise_ville || '',
            companyComplement: allFormData.entreprise_complement || '',
            firstName: allFormData.responsable_prenom || '',
            lastName: allFormData.responsable_nom || '',
            email: allFormData.responsable_email || '',
            phone: allFormData.responsable_telephone || '',
            function: allFormData.responsable_fonction || '',
            category: allFormData.categorie || '',
            contractPower: allFormData.puissance || '',
            tarifFormula: allFormData.formule_tarifaire || '',
            eligibleTRV: allFormData.eligible_trv === 'oui',
            annualConsumption: parseInt(allFormData.conso_annuelle) || 0,
            pricingType: allFormData.tarif_choisi || '',
            contractType: allFormData.type_contrat || 'principal',
            pdlAddress: allFormData.pdl_entreprise || '',
            prmNumber: allFormData.prm_entreprise || '',
            acceptConditions: allFormData.accept_conditions_pro || false,
            acceptPrelevement: allFormData.accept_prelevement_pro || false,
            certifiePouvoir: allFormData.certifie_pouvoir || false,
            monthlyEstimate: offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc) / 10) : 0,
            annualEstimate: offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc)) : 0,
            selectedOffer: offreSelectionnee ? {
                name: offreSelectionnee.nom,
                totalHTVA: Math.round(parseFloat(offreSelectionnee.total_ttc)),
                totalTTC: Math.round(parseFloat(offreSelectionnee.total_ttc) * 1.2),
                details: offreSelectionnee.details || ''
            } : null,
            offers: results.offres || [],
            bestOffer: results.meilleure_offre || null,
            maxSaving: results.economie_max || 0,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        };
    }

    function handleSubmissionSuccess(response) {
        cacherLoaderProfessionnel();
        if (response.success) {
            afficherMessageSuccesProfessionnel(response.data?.referenceNumber || 'PRO-' + Date.now());
        } else {
            afficherMessageErreurProfessionnel(response.data || 'Une erreur est survenue');
        }
    }

    function handleSubmissionError(xhr, status, error) {
        cacherLoaderProfessionnel();
        afficherMessageErreurProfessionnel('Erreur de connexion au serveur');
    }

    // ===========================================
    // INTERFACE UTILISATEUR
    // ===========================================

    function afficherLoaderProfessionnel() {
        if ($('#ajax-loader-pro').length) return;

        const loader = `
        <div id="ajax-loader-pro" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.8); display: flex; 
                    justify-content: center; align-items: center; z-index: 99999;">
            <div style="background: white; padding: 50px; border-radius: 15px; text-align: center; 
                        box-shadow: 0 15px 50px rgba(0,0,0,0.4); max-width: 400px;">
                <div class="spinner-pro" style="border: 6px solid #f3f3f3; border-top: 6px solid #1E40AF; 
                            border-radius: 50%; width: 80px; height: 80px; 
                            animation: spin 1s linear infinite; margin: 0 auto 25px;"></div>
                <h3 style="margin: 0 0 15px 0; color: #1E40AF; font-size: 20px;">Traitement en cours...</h3>
                <p style="margin: 0; font-size: 16px; color: #666; line-height: 1.5;">
                    <strong>Génération du devis professionnel</strong><br>
                    Création du PDF et envoi des emails<br>
                    <small style="color: #999;">Cela peut prendre quelques instants</small>
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

    function cacherLoaderProfessionnel() {
        $('#ajax-loader-pro').fadeOut(400, function () {
            $(this).remove();
        });
    }

    function afficherMessageSuccesProfessionnel(referenceNumber) {
        $('.ajax-message').remove();

        const successHtml = `
        <div class="ajax-message success-message" style="position: fixed; top: 20px; right: 20px; 
                    background: linear-gradient(135deg, #82C720 0%, #82C720 100%); color: white; 
                    padding: 20px 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(255, 255, 255, 0.2); 
                    z-index: 100000; max-width: 400px; animation: slideIn 0.5s ease;">
            <div style="display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 15px;">✅</span>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 16px; color: white;">Succès !</h4>
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
            $('.success-message').fadeOut(500, function () {
                $(this).remove();
            });
        }, 1000);

        setTimeout(() => {
            window.location.href = '/merci';
        }, 2000);
    }

    function afficherMessageErreurProfessionnel(message) {
        $('.ajax-message').remove();

        const errorHtml = `
        <div class="ajax-message error-message-pro" style="position: fixed; top: 20px; right: 20px; 
                    background: #DC2626; color: white; padding: 25px; 
                    border-radius: 12px; box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3); 
                    z-index: 100000; max-width: 450px; animation: errorSlideIn 0.5s ease;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; flex: 1;">
                    <span style="font-size: 28px; margin-right: 15px;">❌</span>
                    <div>
                        <h4 style="margin: 0 0 8px 0; font-size: 18px;">Erreur d'envoi</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.95;">${message}</p>
                        <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.8;">
                            Veuillez réessayer ou contacter notre support
                        </p>
                    </div>
                </div>
                <button class="close-btn-pro" style="background: white; color: #DC2626; border: none; 
                    padding: 8px 12px; border-radius: 6px; cursor: pointer; 
                    margin-left: 15px; font-weight: bold;">✕</button>
            </div>
        </div>
        <style>
            @keyframes errorSlideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        </style>
    `;

        $('body').append(errorHtml);

        $('.close-btn-pro').on('click', function () {
            $('.error-message-pro').fadeOut(300, function () {
                $(this).remove();
            });
        });

        setTimeout(() => {
            $('.error-message-pro').fadeOut(500, function () {
                $(this).remove();
            });
        }, 8000);
    }

    function showValidationMessage(message) {
        $('.validation-message').remove();

        const $message = $(`<div class="validation-message error">${message}</div>`);
        $('.form-step.active .step-header').after($message);

        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 4000);
    }

    function showNotification(message, type = 'success') {
        $('.notification-toast').remove();

        const notification = $(`
            <div class="notification-toast ${type}">
                <span class="notification-icon">${type === 'success' ? '✓' : '⚠'}</span>
                <span class="notification-text">${message}</span>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => notification.addClass('show'), 100);

        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        uploadedFiles = {};
        calculResults = {};
        $('#simulateur-elec-professionnel')[0].reset();
        updateUI();
        $('.field-error, .field-success').removeClass('field-error field-success');
        $('.upload-result').empty();
        $('.formule-note').remove();
        setupProLogic();
    }

    // ===========================================
    // FONCTIONS UTILITAIRES
    // ===========================================

    function getTypeBadge(offre) {
        if (!offre) return 'Standard';
        const nom = offre.nom.toLowerCase();
        if (nom.includes('trv') || nom.includes('bleu')) return 'Tarif réglementé';
        if (nom.includes('tempo')) return 'Tempo';
        if (nom.includes('française') || nom.includes('100%')) return 'Énergie verte';
        return 'Offre de marché';
    }

    function formatSiret(siret) {
        if (!siret || siret.length !== 14) return siret;
        return siret.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4');
    }

    function formatPhone(phone) {
        if (!phone) return null;
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
        }
        return phone;
    }

    // ===========================================
    // INITIALISATION ET API PUBLIQUE
    // ===========================================

    init();

    window.HticElecProfessionnelData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfig: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep,
        validateStep: validateCurrentStep,
        restart: restartSimulation,
        getResults: () => calculResults,
        getUploadedFiles: () => uploadedFiles
    };

});