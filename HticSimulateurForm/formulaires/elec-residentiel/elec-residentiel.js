// elec-residentiel.js - JavaScript complet pour collecte de données et calcul

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 8;
    let formData = {};
    let configData = {};
    let calculationResults = null;

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupChauffageLogic();
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

        // MODIFIÉ : Gestion des boutons pour 8 étapes
        if (currentStep === 8) { // Étape résultats
            $('#btn-next, #btn-calculate').hide();
            $('#btn-restart').show();
            $('.results-actions').show(); // Afficher les actions email
        } else if (currentStep === 7) { // Étape client
            $('#btn-next').hide();
            $('#btn-calculate').show();
            $('#btn-restart').hide();
        } else {
            $('#btn-next').show();
            $('#btn-calculate, #btn-restart').hide();
            $('.results-actions').hide();
        }
    }

    // ===============================
    // LOGIQUE CHAUFFAGE ÉLECTRIQUE
    // ===============================

    function setupChauffageLogic() {
        $('input[name="chauffage_electrique"]').on('change', function () {
            const value = $(this).val();
            const detailsSection = $('#chauffage-details');

            if (value === 'oui') {
                detailsSection.show();
                detailsSection.find('input[required]').attr('required', true);
            } else {
                detailsSection.hide();
                detailsSection.find('input').prop('checked', false).attr('required', false);
            }
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
            case 3:
                isValid = validateStep3(currentStepElement);
                break;
            case 4:
                isValid = validateStep4(currentStepElement);
                break;
            case 5:
                isValid = validateStep5(currentStepElement);
                break;
            case 6:
                isValid = validateStep6(currentStepElement);
                break;
            case 7:
                isValid = validateStep7(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
    }

    // Validations par étape
    function validateStep1(stepElement) {
        let isValid = true;

        const typeLogement = stepElement.find('input[name="type_logement"]:checked');
        if (!typeLogement.length) {
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

        const isolation = stepElement.find('input[name="isolation"]:checked');
        if (!isolation.length) {
            isValid = false;
        }

        return isValid;
    }

    function validateStep2(stepElement) {
        const typeChauffage = stepElement.find('input[name="type_chauffage"]:checked');
        if (!typeChauffage.length) {
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
    }

    function validateStep3(stepElement) {
        const typeCuisson = stepElement.find('input[name="type_cuisson"]:checked');
        if (!typeCuisson.length) {
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
    }

    function validateStep4(stepElement) {
        const eauChaude = stepElement.find('input[name="eau_chaude"]:checked');
        return eauChaude.length > 0;
    }

    function validateStep5(stepElement) {
        const eclairage = stepElement.find('input[name="type_eclairage"]:checked');
        return eclairage.length > 0;
    }

    function validateStep6(stepElement) {
        const piscine = stepElement.find('input[name="piscine"]:checked');
        return piscine.length > 0;
    }

    // AJOUT : Validation étape 7 (client)
    function validateStep7(stepElement) {
        let isValid = true;
        let errors = [];

        // Champs obligatoires
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

        // Validation email
        const email = stepElement.find('#client_email').val().trim();
        if (email && !isValidEmail(email)) {
            isValid = false;
            errors.push('L\'adresse email n\'est pas valide');
            stepElement.find('#client_email').addClass('field-error');
        }

        // Validation téléphone
        const phone = stepElement.find('#client_telephone').val().trim();
        if (phone && !isValidPhone(phone)) {
            isValid = false;
            errors.push('Le numéro de téléphone n\'est pas valide');
            stepElement.find('#client_telephone').addClass('field-error');
        }

        // Validation code postal (optionnel)
        const codePostal = stepElement.find('#client_code_postal').val().trim();
        if (codePostal && !/^[0-9]{5}$/.test(codePostal)) {
            isValid = false;
            errors.push('Le code postal doit contenir 5 chiffres');
            stepElement.find('#client_code_postal').addClass('field-error');
        }

        if (!isValid && errors.length > 0) {
            showValidationMessage(errors.join('<br>'));
        }

        return isValid;
    }


    // AJOUT : Fonctions de validation
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function isValidPhone(phone) {
        const re = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
        return re.test(phone.replace(/\s/g, ''));
    }

    // ===============================
    // COLLECTE DES DONNÉES CLIENT
    // ===============================
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

    // Validation des champs
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

    // ===============================
    // ENVOI DES RÉSULTATS PAR EMAIL
    // ===============================
    function sendResultsByEmail(simulationData, clientData, results) {
        // Préparer toutes les données pour l'envoi
        const dataToSend = {
            action: 'htic_send_simulation_email',
            nonce: typeof hticSimulateur !== 'undefined' ? hticSimulateur.nonce : '',
            type: 'elec-residentiel',

            // Données client
            client: {
                nom: clientData.nom,
                prenom: clientData.prenom,
                email: clientData.email,
                telephone: clientData.telephone,
                adresse: clientData.adresse,
                code_postal: clientData.code_postal,
                ville: clientData.ville
            },

            // Données de simulation
            simulation: {
                // Logement
                type_logement: simulationData.type_logement,
                surface: simulationData.surface,
                nb_personnes: simulationData.nb_personnes,
                isolation: simulationData.isolation,

                // Chauffage
                type_chauffage: simulationData.type_chauffage,

                // Équipements
                electromenagers: simulationData.electromenagers || [],
                type_cuisson: simulationData.type_cuisson,

                // Eau chaude
                eau_chaude: simulationData.eau_chaude,

                // Éclairage
                type_eclairage: simulationData.type_eclairage,

                // Options
                piscine: simulationData.piscine,
                equipements_speciaux: simulationData.equipements_speciaux || [],
                preference_tarif: simulationData.preference_tarif
            },

            // Résultats calculés
            resultats: {
                consommation_annuelle: results.consommation_annuelle,
                puissance_recommandee: results.puissance_recommandee,

                // Tarifs
                tarif_base: {
                    total_annuel: results.tarifs.base.total_annuel,
                    total_mensuel: results.tarifs.base.total_mensuel,
                    prix_kwh: results.tarifs.base.prix_kwh
                },
                tarif_hc: {
                    total_annuel: results.tarifs.hc.total_annuel,
                    total_mensuel: results.tarifs.hc.total_mensuel,
                    prix_kwh_hp: results.tarifs.hc.prix_kwh_hp,
                    prix_kwh_hc: results.tarifs.hc.prix_kwh_hc
                },
                tarif_tempo: {
                    total_annuel: results.tarifs.tempo.total_annuel,
                    total_mensuel: results.tarifs.tempo.total_mensuel
                },

                // Répartition
                repartition: results.repartition,

                // Tarif recommandé
                tarif_recommande: results.tarif_recommande
            },

            // Date et heure de la simulation
            date_simulation: new Date().toISOString()
        };

        // URL AJAX
        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        // Envoi AJAX
        return $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            timeout: 30000
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

        return formData;
    }

    // ===============================
    // CALCUL - SIMULATION PERSONNALISÉE
    // ===============================

    function calculateResults() {
        const allData = collectAllFormData();

        const clientData = collectClientData();

        if (!allData.surface || !allData.nb_personnes || !allData.type_logement || !allData.isolation) {
            showValidationMessage('Des informations obligatoires sont manquantes.');
            console.error('❌ Données manquantes:', {
                surface: allData.surface,
                nb_personnes: allData.nb_personnes,
                type_logement: allData.type_logement,
                isolation: allData.isolation
            });
            return;
        }

        showStep(8);
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

    // ===============================
    // ENVOI DONNÉES AU CALCULATEUR
    // ===============================

    function sendDataToCalculator(userData, configData, clientData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-residentiel',
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
                    window.clientData = clientData;
                    window.simulationData = userData;

                    displayResults(response.data);


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
    // AJOUT : GESTION EMAIL
    // ===============================

    /**
 * Validation basique intégrée si EmailValidationSystem n'est pas disponible
 */
    function validateEmailData(formType, formData, clientData) {
        const errors = [];
        const warnings = [];

        // Validation des données client
        if (!clientData.email || !clientData.email.trim()) {
            errors.push({ code: 'MISSING_EMAIL', message: 'Email client requis' });
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(clientData.email)) {
                errors.push({ code: 'INVALID_EMAIL', message: 'Format email invalide' });
            }
        }

        if (!clientData.nom || !clientData.nom.trim()) {
            errors.push({ code: 'MISSING_NAME', message: 'Nom client requis' });
        }

        if (!clientData.prenom || !clientData.prenom.trim()) {
            errors.push({ code: 'MISSING_FIRSTNAME', message: 'Prénom client requis' });
        }

        // Validation des données de formulaire essentielles
        if (formType === 'elec-residentiel') {
            if (!formData.surface || formData.surface < 20 || formData.surface > 1000) {
                errors.push({ code: 'INVALID_SURFACE', message: 'Surface invalide (20-1000 m²)' });
            }
            if (!formData.nb_personnes || formData.nb_personnes < 1 || formData.nb_personnes > 20) {
                errors.push({ code: 'INVALID_PERSONS', message: 'Nombre de personnes invalide (1-20)' });
            }
            if (!formData.type_logement) {
                errors.push({ code: 'MISSING_HOUSING_TYPE', message: 'Type de logement requis' });
            }
        }

        // Vérifications de sécurité basiques
        const dangerousPatterns = [/<script/i, /javascript:/i, /<iframe/i];
        Object.values(clientData).forEach(value => {
            if (typeof value === 'string') {
                dangerousPatterns.forEach(pattern => {
                    if (pattern.test(value)) {
                        errors.push({ code: 'SECURITY_VIOLATION', message: 'Contenu suspect détecté' });
                    }
                });
            }
        });

        return {
            isValid: errors.length === 0,
            hasWarnings: warnings.length > 0,
            canSendEmail: errors.length === 0,
            errors: errors,
            warnings: warnings
        };
    }

    function validateAndSendEmail(formType, formData, clientData, results, successCallback) {
        console.log('🔍 Début validation email pour:', formType);

        let validationResult;

        // Utiliser EmailValidationSystem si disponible, sinon validation basique
        if (typeof EmailValidationSystem !== 'undefined') {
            const validator = new EmailValidationSystem();
            validationResult = validator.validateForEmail(formType, formData);

            // Validation additionnelle pour les données client
            const clientValidation = validator.validateForEmail('client', clientData);
            validationResult.errors.push(...clientValidation.errors);
            validationResult.warnings.push(...clientValidation.warnings);
            validationResult.isValid = validationResult.isValid && clientValidation.isValid;
        } else {
            // Validation basique intégrée
            validationResult = validateEmailData(formType, formData, clientData);
        }

        // Afficher les résultats
        if (validationResult.warnings.length > 0) {
            validationResult.warnings.forEach(warning => {
                console.warn('⚠️ Warning:', warning.message);
            });
        }

        if (validationResult.errors.length > 0) {
            validationResult.errors.forEach(error => {
                console.error('❌ Error:', error.message);
            });

            // Afficher les erreurs à l'utilisateur
            const errorMessages = validationResult.errors.map(e => e.message).join('\n• ');
            showNotification(`Erreurs de validation:\n• ${errorMessages}`, 'error');
            return validationResult;
        }

        // Validation réussie - préparer les données
        if (validationResult.isValid) {
            console.log('✅ Validation réussie, préparation des données');

            const emailData = {
                form_type: formType,
                validation_timestamp: new Date().toISOString(),
                validation_warnings: validationResult.warnings.length,
                form_data: formData,
                client_data: clientData,
                results_data: results
            };

            successCallback(emailData);
        }

        return validationResult;
    }

    function setupEmailActionsElecResidentiel() {
        $(document).on('click', '#btn-send-email', function () {
            const $btn = $(this);
            const originalText = $btn.html();

            // Vérifier que les résultats sont disponibles
            if (!window.calculationResults) {
                showNotification('❌ Aucun résultat de calcul disponible', 'error');
                return;
            }

            // Collecter toutes les données
            const allFormData = collectAllFormData();
            const clientData = {
                nom: $('#client_nom').val() || '',
                prenom: $('#client_prenom').val() || '',
                email: $('#client_email').val() || '',
                telephone: $('#client_telephone').val() || '',
                adresse: $('#client_adresse').val() || '',
                code_postal: $('#client_code_postal').val() || '',
                ville: $('#client_ville').val() || ''
            };

            console.log('📋 Validation email elec-residentiel');
            console.log('📊 Données formulaire:', allFormData);
            console.log('👤 Données client:', clientData);

            // VALIDATION AVANT ENVOI
            const validationResult = validateAndSendEmail(
                'elec-residentiel',
                allFormData,
                clientData,
                window.calculationResults,
                function (validatedData) {
                    // Envoi après validation réussie
                    sendEmailElecResidentiel($btn, originalText, validatedData);
                }
            );

            // Log du résultat
            if (!validationResult.isValid) {
                console.error('❌ Validation échouée pour elec-residentiel:', validationResult.errors);
            }
        });
    }

    function sendEmailElecResidentiel($btn, originalText, validatedData) {
        $btn.prop('disabled', true).html('<span class="spinner"></span> Envoi en cours...');

        // Préparer les données pour l'envoi AJAX
        const emailData = {
            action: 'htic_send_simulation_email',
            type: 'elec-residentiel',
            nonce: typeof hticSimulateur !== 'undefined' ? hticSimulateur.nonce : '',

            // Données validées
            form_type: validatedData.form_type,
            validation_timestamp: validatedData.validation_timestamp,

            // Données client
            client: validatedData.client_data,

            // Données de simulation
            simulation: validatedData.form_data,

            // Résultats
            results: validatedData.results_data,

            // Date de simulation
            date_simulation: new Date().toISOString()
        };

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        console.log('📤 Envoi données email:', emailData);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: emailData,
            success: function (response) {
                console.log('📥 Réponse serveur:', response);

                if (response.success) {
                    $('#email-confirmation').slideDown();
                    $('#email-display').text(validatedData.client_data.email);
                    showNotification('✅ Email envoyé avec succès !', 'success');
                } else {
                    showNotification('❌ Erreur : ' + (response.data || 'Erreur inconnue'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', { status, error, response: xhr.responseText });
                showNotification('❌ Erreur de connexion', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Fonction de notification
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        $('.notification').remove();

        const $notification = $(`
        <div class="notification notification-${type}">
            ${message}
        </div>
    `);

        $('body').append($notification);

        // Animation d'entrée
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);

        // Suppression après 4 secondes
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, 4000);
    }

    // ===============================
    // AFFICHAGE RÉSULTATS (reste identique)
    // ===============================

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
        const totalMensuelBase = parseInt(tarifBase.total_mensuel) || Math.round(totalAnnuelBase / 12);

        const totalAnnuelHC = parseInt(tarifHC.total_annuel) || 0;
        const totalMensuelHC = parseInt(tarifHC.total_mensuel) || Math.round(totalAnnuelHC / 12);

        const totalAnnuelTempo = parseInt(tarifTempo.total_annuel) || 0;
        const totalMensuelTempo = parseInt(tarifTempo.total_mensuel) || Math.round(totalAnnuelTempo / 12);

        const tarifs = {
            'base': totalAnnuelBase,
            'hc': totalAnnuelHC,
            'tempo': totalAnnuelTempo
        };

        const tarifMin = Math.min(totalAnnuelBase, totalAnnuelHC, totalAnnuelTempo);
        const tarifMax = Math.max(totalAnnuelBase, totalAnnuelHC, totalAnnuelTempo);
        const economie = tarifMax - tarifMin;

        let tarifRecommande = 'base';
        if (totalAnnuelHC === tarifMin) tarifRecommande = 'hc';
        if (totalAnnuelTempo === tarifMin) tarifRecommande = 'tempo';

        const repartition = results.repartition || {};
        const chauffage = parseInt(repartition.chauffage) || 0;
        const eauChaude = parseInt(repartition.eau_chaude) || 0;
        const electromenagers = parseInt(repartition.electromenagers) || 0;
        const eclairage = parseInt(repartition.eclairage) || 0;
        const multimedia = parseInt(repartition.multimedia) || 0;

        let equipementsSpeciaux = 0;
        if (typeof repartition.equipements_speciaux === 'object') {
            for (let key in repartition.equipements_speciaux) {
                equipementsSpeciaux += parseInt(repartition.equipements_speciaux[key]) || 0;
            }
        } else {
            equipementsSpeciaux = parseInt(repartition.equipements_speciaux) || 0;
        }

        const autres = parseInt(repartition.autres) || 0;

        // Le HTML des résultats reste identique à votre version originale
        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">⚡</div>
                <h3>Votre consommation estimée</h3>
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <p>Puissance recommandée : <strong>${puissanceRecommandee} kVA</strong></p>
            </div>
            
            <!-- Comparaison des 3 tarifs -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des tarifs</h3>
                <div class="tarifs-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <!-- TARIF BASE TRV -->
                    <div class="tarif-card ${tarifRecommande === 'base' ? 'recommended' : ''}">
                        <h4>Base TRV</h4>
                        <div class="tarif-prix">${totalAnnuelBase.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelBase.toLocaleString()}€/mois</div>
                        <div class="tarif-details">
                            <small>Prix unique : ${tarifBase.prix_kwh || '0.2516'}€/kWh</small>
                        </div>
                        ${tarifRecommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                    
                    <!-- TARIF HEURES CREUSES -->
                    <div class="tarif-card ${tarifRecommande === 'hc' ? 'recommended' : ''}">
                        <h4>Heures Creuses TRV</h4>
                        <div class="tarif-prix">${totalAnnuelHC.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelHC.toLocaleString()}€/mois</div>
                        <div class="tarif-details">
                            <small>HP: ${tarifHC.prix_kwh_hp || '0.27'}€ | HC: ${tarifHC.prix_kwh_hc || '0.2068'}€</small>
                        </div>
                        ${tarifRecommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                    
                    <!-- TARIF TEMPO -->
                    <div class="tarif-card ${tarifRecommande === 'tempo' ? 'recommended' : ''}">
                        <h4>Tempo TRV</h4>
                        <div class="tarif-prix">${totalAnnuelTempo.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelTempo.toLocaleString()}€/mois</div>
                        <div class="tarif-details">
                            <small>300j bleus, 43j blancs, 22j rouges</small>
                        </div>
                        ${tarifRecommande === 'tempo' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                </div>
                
                ${economie > 0 ? `
                <div class="economies">
                    <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${economie.toLocaleString()}€/an en choisissant le bon tarif !</p>
                    <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;">
                        ${tarifRecommande === 'tempo' ?
                    '⚠️ Le tarif Tempo nécessite de décaler votre consommation hors jours rouges.' :
                    tarifRecommande === 'hc' ?
                        '⏰ Les Heures Creuses nécessitent de décaler 40% de votre consommation la nuit.' :
                        '✅ Le tarif Base est simple, sans contrainte horaire.'}
                    </p>
                </div>
                ` : ''}
                
                
                ${tarifTempo.details_periodes ? `
                <div class="tempo-details">
                    <div class="tempo-header">
                        <div class="tempo-icon"></div>
                        <div class="tempo-title">
                            <h4>Détails du tarif Tempo</h4>
                            <div class="tempo-subtitle">Répartition sur 365 jours</div>
                        </div>
                    </div>
                    
                    <div class="tempo-periods">
                        <!-- Jours Bleus -->
                        <div class="period-card period-bleu">
                            <div class="period-header">
                                <span class="period-name">Jours Bleus</span>
                                <span class="period-days">${tarifTempo.details_periodes.bleu.jours} jours</span>
                            </div>
                            <div class="period-cost">${Math.round(tarifTempo.details_periodes.bleu.cout_total).toLocaleString()}€</div>
                            <div class="period-details">
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Pleines:</span>
                                    <span class="detail-value">${tarifTempo.details_periodes.bleu.hp_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Creuses:</span>
                                    <span class="detail-value">${tarifTempo.details_periodes.bleu.hc_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <span class="detail-label">% de l'année:</span>
                                    <span class="detail-value">82%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Jours Blancs -->
                        <div class="period-card period-blanc">
                            <div class="period-indicator"></div>
                            <div class="period-header">
                                <span class="period-name">Jours Blancs</span>
                                <span class="period-days">${tarifTempo.details_periodes.blanc.jours} jours</span>
                            </div>
                            <div class="period-cost">${Math.round(tarifTempo.details_periodes.blanc.cout_total).toLocaleString()}€</div>
                            <div class="period-details">
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Pleines:</span>
                                    <span class="detail-value">${tarifTempo.details_periodes.blanc.hp_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Creuses:</span>
                                    <span class="detail-value">${tarifTempo.details_periodes.blanc.hc_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <span class="detail-label">% de l'année:</span>
                                    <span class="detail-value">12%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Jours Rouges -->
                        <div class="period-card period-rouge">
                            <div class="period-indicator"></div>
                            <div class="period-header">
                                <span class="period-name">Jours Rouges</span>
                                <span class="period-days">${tarifTempo.details_periodes.rouge.jours} jours</span>
                            </div>
                            <div class="period-cost">${Math.round(tarifTempo.details_periodes.rouge.cout_total).toLocaleString()}€</div>
                            <div class="period-details">
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Pleines:</span>
                                    <span class="detail-value" style="color: #c62828;">${tarifTempo.details_periodes.rouge.hp_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row">
                                    <span class="detail-label">Heures Creuses:</span>
                                    <span class="detail-value">${tarifTempo.details_periodes.rouge.hc_prix}€/kWh</span>
                                </div>
                                <div class="period-detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <span class="detail-label">% de l'année:</span>
                                    <span class="detail-value">6%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tempo-footer">
                        <div class="tempo-info">
                            <strong>💡 Conseil :</strong> Le tarif Tempo est avantageux si vous pouvez réduire fortement votre consommation les 22 jours rouges (tarif jusqu'à 4× plus cher en heures pleines). Idéal avec un chauffage d'appoint non électrique.
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            
            <!-- Répartition de la consommation -->
            <div class="repartition-conso">
                <div class="repartition-header">
                    <h3>Répartition de votre consommation</h3>
                    <p class="repartition-subtitle">Analyse détaillée par poste de consommation</p>
                </div>
                
                <div class="repartition-content">
                    ${chauffage > 0 ? `
                    <div class="repartition-item chauffage">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">🔥</div>
                                <div class="item-details">
                                    <div class="item-name">Chauffage</div>
                                    <div class="item-value">${chauffage.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(chauffage / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(chauffage / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${eauChaude > 0 ? `
                    <div class="repartition-item eau-chaude">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">💧</div>
                                <div class="item-details">
                                    <div class="item-name">Eau chaude</div>
                                    <div class="item-value">${eauChaude.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(eauChaude / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(eauChaude / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${electromenagers > 0 ? `
                    <div class="repartition-item electromenager">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">🔌</div>
                                <div class="item-details">
                                    <div class="item-name">Électroménager</div>
                                    <div class="item-value">${electromenagers.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(electromenagers / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(electromenagers / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${eclairage > 0 ? `
                    <div class="repartition-item eclairage">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">💡</div>
                                <div class="item-details">
                                    <div class="item-name">Éclairage</div>
                                    <div class="item-value">${eclairage.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(eclairage / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(eclairage / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${multimedia > 0 ? `
                    <div class="repartition-item multimedia">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">📺</div>
                                <div class="item-details">
                                    <div class="item-name">Multimédia</div>
                                    <div class="item-value">${multimedia.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(multimedia / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(multimedia / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${equipementsSpeciaux > 0 ? `
                    <div class="repartition-item equipements">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">⚡</div>
                                <div class="item-details">
                                    <div class="item-name">Équipements spéciaux</div>
                                    <div class="item-value">${equipementsSpeciaux.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(equipementsSpeciaux / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(equipementsSpeciaux / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                    
                    ${autres > 0 ? `
                    <div class="repartition-item autres">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">📊</div>
                                <div class="item-details">
                                    <div class="item-name">Autres</div>
                                    <div class="item-value">${autres.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(autres / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(autres / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                </div>
                
            </div>
            
            <!-- Récapitulatif -->
            <div class="recap-section">
                <div class="recap-header">
                    <h3>Récapitulatif complet de votre simulation</h3>
                </div>
                
                <div class="recap-content">
                    <div class="recap-categories">
                        
                        <!-- Logement -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">🏠</div>
                                <div class="category-title">Logement</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">Type de logement</span>
                                    <span class="recap-value">${getLogementLabel(results.recap?.type_logement)}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Surface habitable</span>
                                    <span class="recap-value highlight">${results.recap?.surface || '0'} m²</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Nombre d'occupants</span>
                                    <span class="recap-value">${results.recap?.nb_personnes || '0'} personne${results.recap?.nb_personnes > 1 ? 's' : ''}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Isolation thermique</span>
                                    <span class="recap-value ${getIsolationClass(results.recap?.isolation)}">${getIsolationLabel(results.recap?.isolation)}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chauffage & Climatisation -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">🌡️</div>
                                <div class="category-title">Chauffage & Climatisation</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">Mode de chauffage principal</span>
                                    <span class="recap-value highlight">${getHeatingLabel(results.recap?.type_chauffage)}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Consommation estimée</span>
                                    <span class="recap-value">${(results.repartition?.chauffage || 0).toLocaleString()} kWh/an</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Suite du récapitulatif... (reste identique) -->
                        
                    </div>
                </div>
            </div>
            
            <!-- Actions MODIFIÉES avec bouton email -->
            <div class="results-actions">
                <button class="btn btn-primary" id="btn-send-email">✉️ Recevoir par email</button>
                <button class="btn btn-secondary" onclick="location.reload()">🔄 Nouvelle simulation</button>
            </div>
            
            <!-- Message de confirmation email (caché par défaut) -->
            <div class="confirmation-message" id="email-confirmation" style="display: none;">
                <div class="success-icon">✅</div>
                <p>Votre simulation a été envoyée avec succès à <strong id="email-display"></strong></p>
            </div>
        </div>
    `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        setupEmailActionsElecResidentiel();
    }

    // ===============================
    // FONCTIONS UTILITAIRES (reste identique)
    // ===============================

    function getIsolationClass(isolation) {
        switch (isolation) {
            case 'renovation':
            case 'apres_2000':
                return 'success';
            case '1980_2000':
                return 'warning';
            case 'avant_1980':
                return 'warning';
            default:
                return '';
        }
    }

    function getLogementLabel(type) {
        const labels = {
            'maison': '🏠 Maison',
            'appartement': '🏢 Appartement'
        };
        return labels[type] || type;
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

    function getIsolationLabel(code) {
        const labels = {
            'avant_1980': 'Avant 1980 (faible isolation)',
            '1980_2000': '1980-2000 (isolation moyenne)',
            'apres_2000': 'Après 2000 (bonne isolation)',
            'renovation': 'Rénovation récente (très bonne isolation)'
        };
        return labels[code] || code;
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

    function getPiscineLabel(code) {
        const labels = {
            'simple': 'Piscine simple (filtration)',
            'chauffee': 'Piscine chauffée',
            'non': 'Pas de piscine'
        };
        return labels[code] || code;
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

    function getEquipementSpecialLabel(code) {
        const labels = {
            'spa_jacuzzi': 'Spa/Jacuzzi',
            'voiture_electrique': 'Voiture électrique',
            'aquarium': 'Aquarium',
            'climatiseur_mobile': 'Climatiseur mobile'
        };
        return labels[code] || code;
    }

    function getPreferenceLabel(code) {
        const labels = {
            'indifferent': 'Indifférent',
            'hc': 'Optimisé Heures Creuses',
            'base': 'Tarif Base'
        };
        return labels[code] || code;
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
            goToStep(6);
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

        $('#simulateur-elec-residentiel')[0].reset();

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
    window.HticSimulateurData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep,
        testEmailValidation: () => validateEmailData('elec-residentiel', collectAllFormData(), collectClientData())
    };

});