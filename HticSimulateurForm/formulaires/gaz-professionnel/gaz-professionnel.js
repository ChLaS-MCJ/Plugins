// gaz-professionnel.js - JavaScript pour collecte de données et calcul professionnel

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 3; // Seulement 3 étapes pour pro
    let formData = {};
    let configData = {};
    let calculationResults = null;

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupProLogic();
        loadCommunes();
    }

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
            } catch (e) {
                console.error('❌ Erreur configuration:', e);
                configData = {};
            }
        }
    }

    // ===============================
    // NAVIGATION ENTRE LES ÉTAPES
    // ===============================

    function setupStepNavigation() {
        $('#btn-next').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                goToNextStep();
            }
        });

        $('#btn-previous').on('click', function () {
            saveCurrentStepData();
            goToPreviousStep();
        });

        $('#btn-calculate').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        });

        $('#btn-restart').on('click', function () {
            if (confirm('Voulez-vous vraiment recommencer la simulation ?')) {
                restartSimulation();
            }
        });

        $('#btn-callback').on('click', function () {
            registerCallback();
        });

        $('.step').on('click', function () {
            const targetStep = parseInt($(this).data('step'));
            if (targetStep < currentStep || targetStep === 1) {
                saveCurrentStepData();
                goToStep(targetStep);
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

    function goToStep(stepNumber) {
        if (stepNumber >= 1 && stepNumber <= totalSteps) {
            currentStep = stepNumber;
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
        // Bouton Précédent
        $('#btn-previous').toggle(currentStep > 1);

        // Gestion des boutons pour 3 étapes
        if (currentStep === 3) { // Étape résultats
            $('#btn-next, #btn-calculate').hide();
            $('#btn-restart').show();
            $('.results-actions').show();
        } else if (currentStep === 2) { // Étape contact
            $('#btn-next').hide();
            $('#btn-calculate').show();
            $('#btn-restart').hide();
            $('.results-actions').hide();
        } else if (currentStep === 1) { // Étape 1
            $('#btn-next').show();
            $('#btn-calculate, #btn-restart').hide();
            $('.results-actions').hide();
        }
    }

    // ===============================
    // LOGIQUE SPÉCIFIQUE PRO
    // ===============================

    function setupProLogic() {
        // Gestion commune autre
        $('#commune').on('change', handleCommuneSelection);

        // Validation de la consommation en temps réel
        $('#consommation_previsionnelle').on('input', function () {
            const value = parseFloat($(this).val());
            const $helpText = $(this).closest('.form-group').find('.field-help');

            // Afficher une aide contextuelle selon la valeur
            if (value > 0 && value < 5000) {
                $helpText.html('💡 <strong>Très petite consommation</strong> - Tarif P0/GOM0');
            } else if (value >= 5000 && value < 15000) {
                $helpText.html('💡 <strong>Petite entreprise</strong> - Tarif adapté aux commerces');
            } else if (value >= 15000 && value < 35000) {
                $helpText.html('💡 <strong>PME</strong> - Tarif optimisé pour les moyens consommateurs');
            } else if (value >= 35000 && value < 100000) {
                $helpText.html('⚠️ <strong>Grande consommation</strong> - Un devis personnalisé sera établi pour le gaz naturel');
            } else if (value >= 100000) {
                $helpText.html('🏭 <strong>Très grande consommation</strong> - Offre sur mesure requise');
            }
        });

        // Format SIRET automatique
        $('#entreprise_siret').on('input', function () {
            let value = $(this).val().replace(/\s/g, '');
            if (value.length > 14) {
                value = value.substr(0, 14);
            }
            $(this).val(value);
        });
    }

    function handleCommuneSelection() {
        const selectedValue = $('#commune').val();
        const selectedOption = $('#commune option:selected');

        if (selectedValue === 'autre') {
            $('#autre-commune-details').slideDown();
            $('#type-gaz-info').hide();
        } else if (selectedValue && selectedValue !== '') {
            $('#autre-commune-details').slideUp();
            showTypeGazInfo(selectedOption);
        } else {
            $('#autre-commune-details').hide();
            $('#type-gaz-info').hide();
        }
    }

    function showTypeGazInfo(selectedOption) {
        const typeGaz = selectedOption.data('type');
        if (!typeGaz) return;

        const typeText = typeGaz === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
        const icon = typeGaz === 'naturel' ? '🌱' : '⛽';

        $('#type-gaz-text').html(`${icon} <strong>${typeText}</strong> disponible dans cette commune`);
        $('#type-gaz-info').fadeIn();
    }

    function loadCommunes() {
        // Communes par défaut (identiques au résidentiel)
        const defaultCommunes = [
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
        ];

        populateCommunesSelect(defaultCommunes);
    }

    function populateCommunesSelect(communes) {
        const communesNaturel = communes.filter(c => c.type === 'naturel');
        const communesPropane = communes.filter(c => c.type === 'propane');

        // Remplir le groupe naturel
        $('#communes-naturel').empty();
        communesNaturel.forEach(commune => {
            $('#communes-naturel').append(`<option value="${commune.nom}" data-type="naturel">${commune.nom}</option>`);
        });

        // Remplir le groupe propane
        $('#communes-propane').empty();
        communesPropane.forEach(commune => {
            $('#communes-propane').append(`<option value="${commune.nom}" data-type="propane">${commune.nom}</option>`);
        });
    }

    // ===============================
    // VALIDATION
    // ===============================

    function setupFormValidation() {
        $('input[required], select[required]').on('blur', function () {
            validateField($(this));
        });

        $('input[type="number"]').on('input', function () {
            validateNumberField($(this));
        });
    }

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        currentStepElement.find('.field-error, .field-success').removeClass('field-error field-success');

        switch (currentStep) {
            case 1:
                isValid = validateStep1(currentStepElement);
                break;
            case 2:
                isValid = validateStep2(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
    }

    function validateStep1(stepElement) {
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
            showValidationMessage('La consommation doit être entre 100 et 1 000 000 kWh');
            isValid = false;
        } else {
            conso.addClass('field-success');
        }

        return isValid;
    }

    function validateStep2(stepElement) {
        let isValid = true;
        let errors = [];

        // Champs obligatoires entreprise
        const requiredFields = [
            { id: 'entreprise_nom', label: 'Nom de l\'entreprise' },
            { id: 'entreprise_secteur', label: 'Secteur d\'activité' },
            { id: 'contact_nom', label: 'Nom du contact' },
            { id: 'contact_prenom', label: 'Prénom du contact' },
            { id: 'contact_email', label: 'Email' },
            { id: 'contact_telephone', label: 'Téléphone' }
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

        // Validation SIRET si rempli
        const siret = stepElement.find('#entreprise_siret').val().trim();
        if (siret && !/^[0-9]{14}$/.test(siret)) {
            isValid = false;
            errors.push('Le SIRET doit contenir exactement 14 chiffres');
            stepElement.find('#entreprise_siret').addClass('field-error');
        }

        // Validation email
        const email = stepElement.find('#contact_email').val().trim();
        if (email && !isValidEmail(email)) {
            isValid = false;
            errors.push('L\'adresse email n\'est pas valide');
            stepElement.find('#contact_email').addClass('field-error');
        }

        // Validation téléphone
        const phone = stepElement.find('#contact_telephone').val().trim();
        if (phone && !isValidPhone(phone)) {
            isValid = false;
            errors.push('Le numéro de téléphone n\'est pas valide');
            stepElement.find('#contact_telephone').addClass('field-error');
        }

        // Validation code postal
        const codePostal = stepElement.find('#entreprise_code_postal').val().trim();
        if (codePostal && !/^[0-9]{5}$/.test(codePostal)) {
            isValid = false;
            errors.push('Le code postal doit contenir 5 chiffres');
            stepElement.find('#entreprise_code_postal').addClass('field-error');
        }

        if (!isValid && errors.length > 0) {
            showValidationMessage(errors.join('<br>'));
        }

        return isValid;
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
    // COLLECTE DES DONNÉES
    // ===============================

    function collectEntrepriseData() {
        return {
            entreprise_nom: $('#entreprise_nom').val().trim(),
            entreprise_siret: $('#entreprise_siret').val().trim(),
            entreprise_secteur: $('#entreprise_secteur').val(),
            entreprise_adresse: $('#entreprise_adresse').val().trim(),
            entreprise_code_postal: $('#entreprise_code_postal').val().trim(),
            entreprise_ville: $('#entreprise_ville').val().trim(),
            contact_nom: $('#contact_nom').val().trim(),
            contact_prenom: $('#contact_prenom').val().trim(),
            contact_fonction: $('#contact_fonction').val().trim(),
            contact_email: $('#contact_email').val().trim(),
            contact_telephone: $('#contact_telephone').val().trim(),
            contact_horaire: $('#contact_horaire').val()
        };
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

    // ===============================
    // SAUVEGARDE DES DONNÉES
    // ===============================

    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        currentStepElement.find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name) return;

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[name] = $field.val();
                }
            } else {
                formData[name] = $field.val();
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

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                } else {
                    formData[name] = $field.val();
                }
            });
        });

        return formData;
    }

    // ===============================
    // CALCUL - SIMULATION PRO
    // ===============================

    function calculateResults() {
        const allData = collectAllFormData();
        const entrepriseData = collectEntrepriseData();

        // Validation des données essentielles
        if (!allData.commune || !allData.consommation_previsionnelle) {
            showValidationMessage('Des informations obligatoires sont manquantes.');
            return;
        }

        showStep(3);
        updateProgress();
        updateNavigation();

        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation professionnelle...</p>
            </div>
        `);

        sendDataToCalculator(allData, configData, entrepriseData);
    }

    // ===============================
    // ENVOI DONNÉES AU CALCULATEUR
    // ===============================

    function sendDataToCalculator(userData, configData, entrepriseData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'gaz-professionnel',
            user_data: userData,
            config_data: configData
        };

        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.calculateNonce) {
            dataToSend.nonce = hticSimulateurUnifix.calculateNonce;
        }

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.ajaxUrl) {
            ajaxUrl = hticSimulateurUnifix.ajaxUrl;
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
                    window.entrepriseData = entrepriseData;
                    window.simulationData = userData;

                    // Vérifier si c'est un devis personnalisé
                    if (response.data.devis_personnalise) {
                        displayDevisPersonnalise(response.data, entrepriseData);
                    } else {
                        displayResults(response.data);
                    }

                    setupEmailActions();
                } else {
                    displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                let errorMessage = 'Erreur de connexion lors du calcul';

                if (xhr.status === 0) {
                    errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Erreur interne du serveur. Contactez l\'administrateur.';
                } else if (status === 'timeout') {
                    errorMessage = 'Le calcul prend trop de temps. Réessayez.';
                }

                displayError(errorMessage);
            }
        });
    }

    // ===============================
    // GESTION EMAIL ET RAPPEL
    // ===============================

    function setupEmailActions() {
        // Bouton envoyer par email
        $(document).on('click', '#btn-send-email', function () {
            const $btn = $(this);
            const originalText = $btn.html();

            $btn.prop('disabled', true).html('<span class="spinner"></span> Envoi en cours...');

            const emailData = {
                action: 'htic_send_simulation_email',
                type: 'gaz-professionnel',
                results: calculationResults,
                entreprise: entrepriseData || formData
            };

            if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
                emailData.nonce = hticSimulateur.nonce;
            }

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
                        $('#email-display').text(formData.contact_email);

                        setTimeout(() => {
                            $('#email-confirmation').slideUp();
                        }, 5000);

                        showNotification('✅ Email envoyé avec succès !', 'success');
                    } else {
                        showNotification('❌ Erreur lors de l\'envoi', 'error');
                    }
                },
                error: function () {
                    showNotification('❌ Erreur de connexion', 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    function registerCallback() {
        const $btn = $('#btn-callback');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner"></span> Enregistrement...');

        const callbackData = {
            action: 'htic_register_callback',
            type: 'gaz-professionnel',
            entreprise: entrepriseData || formData,
            horaire: formData.contact_horaire
        };

        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            callbackData.nonce = hticSimulateur.nonce;
        }

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: callbackData,
            success: function (response) {
                if (response.success) {
                    $('#callback-confirmation').slideDown();
                    setTimeout(() => {
                        $('#callback-confirmation').slideUp();
                    }, 5000);
                    showNotification('☎️ Demande de rappel enregistrée', 'success');
                } else {
                    showNotification('❌ Erreur lors de l\'enregistrement', 'error');
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

    // Fonction de notification
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

    // ===============================
    // AFFICHAGE DEVIS PERSONNALISÉ
    // ===============================

    function displayDevisPersonnalise(data, entrepriseData) {
        $('#results-container').hide();
        $('#devis-personnalise-container').show();

        // Remplir les informations du devis
        $('#devis-entreprise').text(entrepriseData.entreprise_nom || formData.entreprise_nom);
        $('#devis-commune').text(data.commune || formData.commune);
        $('#devis-consommation').text((data.consommation_annuelle || 0).toLocaleString() + ' kWh/an');
        $('#devis-type-gaz').text(data.type_gaz || 'Gaz naturel');

        $('.results-actions').show();
    }

    // ===============================
    // AFFICHAGE RÉSULTATS NORMAUX
    // ===============================

    function displayResults(results) {
        if (!results || !results.consommation_annuelle) {
            displayError('Données de résultats incomplètes');
            return;
        }

        $('#devis-personnalise-container').hide();
        $('#results-container').show();

        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const coutAnnuel = parseFloat(results.cout_annuel_ttc) || 0;
        const coutMensuel = Math.round(coutAnnuel / 12);
        const prixKwh = parseFloat(results.prix_kwh) || 0;
        const abonnementAnnuel = parseFloat(results.cout_abonnement) || 0;
        const abonnementMensuel = Math.round(abonnementAnnuel / 12);

        const resultsHtml = `
            <div class="results-summary">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">🏢</div>
                    <h3>Votre estimation professionnelle</h3>
                    <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                    <div class="result-price">${coutAnnuel.toLocaleString()}€ <span>/an HT</span></div>
                    <p>Soit environ <strong>${coutMensuel}€/mois HT</strong></p>
                </div>
                
                <!-- Détails de l'offre - Design amélioré -->
                <div class="offer-details-modern">
                    <div class="offer-header">
                        <h3>Détails de votre offre professionnelle</h3>
                        <span class="offer-badge">${results.type_gaz || 'Gaz'}</span>
                    </div>
                    
                    <div class="offer-main-grid">
                        <!-- Carte tarification -->
                        <div class="offer-card pricing-card">
                            <div class="card-header">
                                <div class="card-icon">💰</div>
                                <h4>Tarification</h4>
                            </div>
                            <div class="card-content">
                                <div class="pricing-row">
                                    <span class="pricing-label">Tranche tarifaire</span>
                                    <span class="pricing-value badge-primary">${results.tranche_tarifaire || '--'}</span>
                                </div>
                                <div class="pricing-row">
                                    <span class="pricing-label">Prix du kWh HT</span>
                                    <span class="pricing-value">${prixKwh.toFixed(4)}€</span>
                                </div>
                                <div class="pricing-row">
                                    <span class="pricing-label">Abonnement HT</span>
                                    <span class="pricing-value">${abonnementMensuel}€/mois</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Carte coûts -->
                        <div class="offer-card costs-card">
                            <div class="card-header">
                                <div class="card-icon">📊</div>
                                <h4>Répartition des coûts</h4>
                            </div>
                            <div class="card-content">
                                <div class="cost-breakdown">
                                    <div class="cost-item">
                                        <div class="cost-label">
                                            <span class="cost-icon">⚡</span>
                                            Consommation annuelle
                                        </div>
                                        <div class="cost-value">${(results.cout_consommation || 0).toLocaleString()}€</div>
                                    </div>
                                    <div class="cost-item">
                                        <div class="cost-label">
                                            <span class="cost-icon">📅</span>
                                            Abonnement annuel
                                        </div>
                                        <div class="cost-value">${(abonnementAnnuel || 0).toLocaleString()}€</div>
                                    </div>
                                    <div class="cost-separator"></div>
                                    <div class="cost-item total">
                                        <div class="cost-label">
                                            <strong>Total annuel HT</strong>
                                        </div>
                                        <div class="cost-value primary">${coutAnnuel.toLocaleString()}€</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barre d'économies potentielles -->
                    <div class="savings-bar">
                        <div class="savings-content">
                            <div class="savings-icon">💡</div>
                            <div class="savings-text">
                                <strong>Économisez jusqu'à 15%</strong> en optimisant votre contrat professionnel
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Récapitulatif entreprise -->
                <div class="recap-section">
                    <div class="recap-header">
                        <h3>Récapitulatif de votre simulation</h3>
                    </div>
                    
                    <div class="recap-content">
                        <div class="recap-categories">
                            
                            <!-- Entreprise -->
                            <div class="recap-category">
                                <div class="category-header">
                                    <div class="category-icon">🏢</div>
                                    <div class="category-title">Entreprise</div>
                                </div>
                                <div class="category-items">
                                    <div class="recap-item">
                                        <span class="recap-label">Nom</span>
                                        <span class="recap-value">${formData.entreprise_nom || '--'}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Secteur</span>
                                        <span class="recap-value">${getSecteurLabel(formData.entreprise_secteur)}</span>
                                    </div>
                                    ${formData.entreprise_siret ? `
                                    <div class="recap-item">
                                        <span class="recap-label">SIRET</span>
                                        <span class="recap-value">${formatSiret(formData.entreprise_siret)}</span>
                                    </div>` : ''}
                                </div>
                            </div>
                            
                            <!-- Localisation -->
                            <div class="recap-category">
                                <div class="category-header">
                                    <div class="category-icon">📍</div>
                                    <div class="category-title">Localisation</div>
                                </div>
                                <div class="category-items">
                                    <div class="recap-item">
                                        <span class="recap-label">Commune</span>
                                        <span class="recap-value">${formData.commune || 'Non spécifiée'}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Type de gaz</span>
                                        <span class="recap-value highlight">${results.type_gaz || 'Non défini'}</span>
                                    </div>
                                    ${formData.entreprise_code_postal ? `
                                    <div class="recap-item">
                                        <span class="recap-label">Code postal</span>
                                        <span class="recap-value">${formData.entreprise_code_postal}</span>
                                    </div>` : ''}
                                </div>
                            </div>
                            
                            <!-- Contact -->
                            <div class="recap-category">
                                <div class="category-header">
                                    <div class="category-icon">📞</div>
                                    <div class="category-title">Contact</div>
                                </div>
                                <div class="category-items">
                                    <div class="recap-item">
                                        <span class="recap-label">Contact</span>
                                        <span class="recap-value">${formData.contact_prenom} ${formData.contact_nom}</span>
                                    </div>
                                    ${formData.contact_fonction ? `
                                    <div class="recap-item">
                                        <span class="recap-label">Fonction</span>
                                        <span class="recap-value">${formData.contact_fonction}</span>
                                    </div>` : ''}
                                    <div class="recap-item">
                                        <span class="recap-label">Email</span>
                                        <span class="recap-value">${formData.contact_email || '--'}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Téléphone</span>
                                        <span class="recap-value">${formData.contact_telephone || '--'}</span>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
            </div>
        `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
        $('.results-actions').show();
    }

    // ===============================
    // FONCTIONS UTILITAIRES PRO
    // ===============================

    function getSecteurLabel(code) {
        const labels = {
            'commerce': 'Commerce',
            'restaurant': 'Restaurant/Hôtellerie',
            'industrie': 'Industrie',
            'bureau': 'Bureaux/Services',
            'sante': 'Santé',
            'education': 'Éducation',
            'agriculture': 'Agriculture',
            'autre': 'Autre'
        };
        return labels[code] || code;
    }

    function formatSiret(siret) {
        if (!siret || siret.length !== 14) return siret;
        return siret.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4');
    }

    function displayError(message) {
        $('#results-container').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    <button class="btn btn-secondary" id="btn-back-to-form">← Retour au formulaire</button>
                </div>
            </div>
        `);

        $('#btn-back-to-form').on('click', function () {
            goToStep(2);
        });
    }

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

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        calculationResults = null;
        entrepriseData = null;

        $('#simulateur-gaz-professionnel')[0].reset();

        showStep(1);
        updateProgress();
        updateNavigation();

        $('.field-error, .field-success').removeClass('field-error field-success');
    }

    // ===============================
    // FONCTIONS GLOBALES
    // ===============================

    window.downloadPDF = function () {
        alert('Fonction de téléchargement PDF en cours de développement');
    };

    // API publique pour récupérer les données
    window.HticGazProfessionnelData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep
    };

});