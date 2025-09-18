// gaz-residentiel.js - JavaScript complet pour collecte de données et calcul

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 6; // 6 étapes pour gaz (pas 8 comme elec)
    let formData = {};
    let configData = {};
    let calculationResults = null;

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupGazLogic();
        loadCommunes();
        updateConsumptionEstimates();
        setupEmailActionsGazResidentiel();
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

        // Gestion des boutons pour 6 étapes
        if (currentStep === 6) { // Étape résultats
            $('#btn-next, #btn-calculate').hide();
            $('#btn-restart').show();
            $('.results-actions').show();
        } else if (currentStep === 5) { // Étape contact
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
    // LOGIQUE SPÉCIFIQUE GAZ
    // ===============================

    function setupGazLogic() {
        // Gestion chauffage au gaz vs autres
        $('input[name="chauffage_gaz"]').on('change', function () {
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

        // Mise à jour des estimations selon le nombre de personnes
        $('#nb_personnes').on('change input', updateConsumptionEstimates);

        // Gestion commune autre
        $('#commune').on('change', handleCommuneSelection);
    }

    function updateConsumptionEstimates() {
        const nbPersonnes = parseInt($('#nb_personnes').val()) || 4;

        // Eau chaude : 400 kWh/personne/an
        const eauChaudeConsommation = nbPersonnes * 400;
        $('#eau-chaude-estimation').text(`${eauChaudeConsommation} kWh/an`);

        // Cuisson : 50 kWh/personne/an  
        const cuissonConsommation = nbPersonnes * 50;
        $('#cuisson-estimation').text(`${cuissonConsommation} kWh/an`);
    }

    function handleCommuneSelection() {
        const selectedValue = $('#commune').val();
        const selectedOption = $('#commune option:selected');

        if (selectedValue === 'autre') {
            $('#autre-commune-details').show();
            $('#type-gaz-info').hide();
        } else if (selectedValue && selectedValue !== '') {
            $('#autre-commune-details').hide();
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
        $('#type-gaz-info').show();
    }

    function loadCommunes() {
        // Communes par défaut
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
            case 3:
                isValid = validateStep3(currentStepElement);
                break;
            case 4:
                isValid = validateStep4(currentStepElement);
                break;
            case 5:
                isValid = validateStep5(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
    }

    // Validations par étape adaptées au gaz
    function validateStep1(stepElement) {
        let isValid = true;

        const typeLogement = stepElement.find('input[name="type_logement"]:checked');
        if (!typeLogement.length) {
            isValid = false;
        }

        const surface = stepElement.find('#superficie');
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

        return isValid;
    }

    function validateStep2(stepElement) {
        const chauffageGaz = stepElement.find('input[name="chauffage_gaz"]:checked');
        if (!chauffageGaz.length) {
            return false;
        }

        // Si chauffage au gaz, vérifier l'isolation
        if (chauffageGaz.val() === 'oui') {
            const isolation = stepElement.find('input[name="isolation"]:checked');
            if (!isolation.length) {
                return false;
            }
        }

        return true;
    }

    function validateStep3(stepElement) {
        const eauChaude = stepElement.find('input[name="eau_chaude"]:checked');
        return eauChaude.length > 0;
    }

    function validateStep4(stepElement) {
        const cuisson = stepElement.find('input[name="cuisson"]:checked');
        if (!cuisson.length) {
            return false;
        }

        const offre = stepElement.find('input[name="offre"]:checked');
        return offre.length > 0;
    }

    function validateStep5(stepElement) {
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
        const formData = {};

        console.log('🔍 DEBUG: Collecte des données du formulaire');

        // Données de base - AVEC DEBUG
        const superficieElement = $('#superficie');
        console.log('Élément superficie trouvé:', superficieElement.length > 0);
        console.log('Valeur superficie:', superficieElement.val());

        formData.surface = parseFloat(superficieElement.val()) || 0;

        const nbPersonnesElement = $('#nb_personnes');
        formData.nb_personnes = parseInt(nbPersonnesElement.val()) || 0;

        const typeLogementElement = $('input[name="type_logement"]:checked');
        formData.type_logement = typeLogementElement.val() || '';

        // Données spécifiques gaz
        const communeElement = $('#commune');
        formData.commune = communeElement.val() || '';

        const chauffageGazElement = $('input[name="chauffage_gaz"]:checked');
        formData.chauffage_gaz = chauffageGazElement.val() || '';

        // Isolation seulement si chauffage au gaz
        if (formData.chauffage_gaz === 'oui') {
            const isolationElement = $('input[name="isolation"]:checked');
            formData.isolation = isolationElement.val() || '';
        }

        const eauChaudeElement = $('input[name="eau_chaude"]:checked');
        formData.eau_chaude = eauChaudeElement.val() || '';

        const cuissonElement = $('input[name="cuisson"]:checked');
        formData.cuisson = cuissonElement.val() || '';

        const offreElement = $('input[name="offre"]:checked');
        formData.offre = offreElement.val() || '';

        // Si commune "autre", récupérer les infos supplémentaires
        if (formData.commune === 'autre') {
            formData.nom_commune_autre = $('#nom_commune_autre').val() || '';
            formData.type_gaz_autre = $('input[name="type_gaz_autre"]:checked').val() || '';
        }

        console.log('📊 Données collectées:', formData);

        return formData;
    }

    // ===============================
    // CALCUL - SIMULATION PERSONNALISÉE
    // ===============================

    function calculateResults() {
        const allData = collectAllFormData();
        const clientData = collectClientData();

        // Validation des données essentielles pour le gaz
        if (!allData.surface || !allData.nb_personnes || !allData.type_logement || !allData.commune) {
            showValidationMessage('Des informations obligatoires sont manquantes.');
            console.error('❌ Données manquantes:', {
                superficie: allData.surface,
                nb_personnes: allData.nb_personnes,
                type_logement: allData.type_logement,
                commune: allData.commune
            });
            return;
        }

        showStep(6);
        updateProgress();
        updateNavigation();

        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation gaz personnalisée...</p>
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
            type: 'gaz-residentiel', // Type modifié pour gaz
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
    // AFFICHAGE RÉSULTATS ADAPTÉ GAZ
    // ===============================

    function displayResults(results) {
        if (!results || !results.consommation_annuelle) {
            displayError('Données de résultats incomplètes');
            return;
        }

        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const coutAnnuel = parseFloat(results.cout_annuel_ttc) || 0;
        const coutMensuel = Math.round(coutAnnuel / 12);

        // Répartition spécifique gaz
        const repartition = results.repartition || {};
        const chauffage = parseInt(repartition.chauffage) || 0;
        const eauChaude = parseInt(repartition.eau_chaude) || 0;
        const cuisson = parseInt(repartition.cuisson) || 0;

        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">🔥</div>
                <h3>Votre estimation gaz</h3>
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <div class="result-price">${coutAnnuel.toLocaleString()}€ <span>/an TTC</span></div>
                <p>Soit environ <strong>${coutMensuel}€/mois</strong></p>
            </div>
            
            <!-- Répartition de la consommation gaz -->
            <div class="repartition-conso">
                <div class="repartition-header">
                    <h3>🔥 Répartition de votre consommation gaz</h3>
                    <p class="repartition-subtitle">Analyse détaillée par usage</p>
                </div>
                
                <div class="repartition-content">
                    ${chauffage > 0 ? `
                    <div class="repartition-item chauffage">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">🔥</div>
                                <div class="item-details">
                                    <div class="item-name">Chauffage au gaz</div>
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
                                <div class="item-icon">🚿</div>
                                <div class="item-details">
                                    <div class="item-name">Eau chaude sanitaire</div>
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
                    
                    ${cuisson > 0 ? `
                    <div class="repartition-item cuisson">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">🍳</div>
                                <div class="item-details">
                                    <div class="item-name">Cuisson</div>
                                    <div class="item-value">${cuisson.toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">${Math.round(cuisson / consommationAnnuelle * 100)}%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${Math.round(cuisson / consommationAnnuelle * 100)}%"></div>
                        </div>
                    </div>` : ''}
                </div>
            </div>
            
            <!-- Récapitulatif gaz -->
            <div class="recap-section">
                <div class="recap-header">
                    <h3>Récapitulatif de votre simulation gaz</h3>
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
                                    <span class="recap-value">${getLogementLabel(formData.type_logement)}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Surface habitable</span>
                                    <span class="recap-value highlight">${formData.superficie || '0'} m²</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Nombre d'occupants</span>
                                    <span class="recap-value">${formData.nb_personnes || '0'} personne${formData.nb_personnes > 1 ? 's' : ''}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Commune</span>
                                    <span class="recap-value">${formData.commune || 'Non spécifiée'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chauffage Gaz -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">🔥</div>
                                <div class="category-title">Chauffage</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">Chauffage au gaz</span>
                                    <span class="recap-value highlight">${formData.chauffage_gaz === 'oui' ? 'Oui' : 'Non'}</span>
                                </div>
                                ${formData.chauffage_gaz === 'oui' ? `
                                <div class="recap-item">
                                    <span class="recap-label">Isolation</span>
                                    <span class="recap-value">${getIsolationLabel(formData.isolation)}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Usages Gaz -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">💧</div>
                                <div class="category-title">Autres usages</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">Eau chaude</span>
                                    <span class="recap-value">${formData.eau_chaude === 'gaz' ? '🔥 Au gaz' : '⚡ Autre énergie'}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Cuisson</span>
                                    <span class="recap-value">${formData.cuisson === 'gaz' ? '🍳 Gazinière' : '⚡ Autre'}</span>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Actions avec bouton email -->
            <div class="results-actions">
                <button class="btn btn-primary" id="btn-send-email">✉️ Recevoir par email</button>
                <button class="btn btn-secondary" onclick="location.reload()">🔄 Nouvelle simulation</button>
            </div>
            
            <!-- Message de confirmation email -->
            <div class="confirmation-message" id="email-confirmation" style="display: none;">
                <div class="success-icon">✅</div>
                <p>Votre simulation a été envoyée avec succès à <strong id="email-display"></strong></p>
            </div>
        </div>
    `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
    }

    // ===============================
    // FONCTIONS UTILITAIRES ADAPTÉES GAZ
    // ===============================

    function getLogementLabel(type) {
        const labels = {
            'maison': '🏠 Maison',
            'appartement': '🏢 Appartement'
        };
        return labels[type] || type;
    }

    function getIsolationLabel(code) {
        const labels = {
            'faible': 'Faible (160 kWh/m²/an)',
            'correcte': 'Correcte (110 kWh/m²/an)',
            'bonne': 'Bonne (70 kWh/m²/an)',
            'excellente': 'Excellente (20 kWh/m²/an)'
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
            goToStep(5);
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
    // FONCTIONS EMAIL
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

        // Validation spécifique gaz résidentiel
        if (formType === 'gaz-residentiel') {
            if (!formData.surface || formData.surface < 20 || formData.surface > 1000) {
                errors.push({ code: 'INVALID_SURFACE', message: 'Surface invalide (20-1000 m²)' });
            }
            if (!formData.nb_personnes || formData.nb_personnes < 1 || formData.nb_personnes > 20) {
                errors.push({ code: 'INVALID_PERSONS', message: 'Nombre de personnes invalide (1-20)' });
            }
            if (!formData.type_logement) {
                errors.push({ code: 'MISSING_HOUSING_TYPE', message: 'Type de logement requis' });
            }
            if (!formData.commune) {
                errors.push({ code: 'MISSING_COMMUNE', message: 'Commune requise' });
            }
            if (!formData.chauffage_gaz) {
                errors.push({ code: 'MISSING_HEATING', message: 'Chauffage au gaz requis' });
            }
            if (!formData.eau_chaude) {
                errors.push({ code: 'MISSING_HOT_WATER', message: 'Eau chaude requise' });
            }
            if (!formData.cuisson) {
                errors.push({ code: 'MISSING_COOKING', message: 'Cuisson requise' });
            }
            if (!formData.offre) {
                errors.push({ code: 'MISSING_OFFER', message: 'Type d\'offre requis' });
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

    function setupEmailActionsGazResidentiel() {
        // Supprimer les anciens handlers pour éviter les doublons
        $(document).off('click', '#btn-send-email');

        $(document).on('click', '#btn-send-email', function () {
            const $btn = $(this);
            const originalText = $btn.html();

            // Vérifier que les résultats sont disponibles
            if (!window.calculationResults) {
                showNotification('Aucun résultat de calcul disponible', 'error');
                return;
            }

            // Collecter toutes les données avec les IDs corrects
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

            console.log('Validation email gaz-residentiel');
            console.log('Données formulaire:', allFormData);
            console.log('Données client:', clientData);

            // VALIDATION AVANT ENVOI
            const validationResult = validateAndSendEmail(
                'gaz-residentiel',
                allFormData,
                clientData,
                window.calculationResults,
                function (validatedData) {
                    // Envoi après validation réussie
                    sendEmailGazResidentiel($btn, originalText, validatedData);
                }
            );

            // Log du résultat
            if (!validationResult.isValid) {
                console.error('Validation échouée pour gaz-residentiel:', validationResult.errors);
            }
        });
    }

    function sendEmailGazResidentiel($btn, originalText, validatedData) {
        $btn.prop('disabled', true).html('<span class="spinner"></span> Envoi en cours...');

        // Préparer les données pour l'envoi AJAX
        const emailData = {
            action: 'htic_send_simulation_email',
            type: 'gaz-residentiel',
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

        console.log('Envoi données email:', emailData);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: emailData,
            success: function (response) {
                console.log('Réponse serveur:', response);

                if (response.success) {
                    $('#email-confirmation').slideDown();
                    $('#email-display').text(validatedData.client_data.email);
                    showNotification('Email envoyé avec succès !', 'success');
                } else {
                    showNotification('Erreur: ' + (response.data || 'Erreur inconnue'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erreur AJAX:', error);
                let errorMessage = 'Erreur lors de l\'envoi de l\'email.';

                if (xhr.status === 0) {
                    errorMessage = 'Problème de connexion. Vérifiez votre connexion.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Erreur interne du serveur. Contactez l\'administrateur.';
                } else if (status === 'timeout') {
                    errorMessage = 'L\'envoi prend trop de temps. Réessayez.';
                }

                showNotification(errorMessage, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    // ===============================
    // FONCTIONS GLOBALES
    // ===============================

    window.downloadPDF = function () {
        alert('Fonction de téléchargement PDF en cours de développement');
    };

    // API publique pour récupérer les données
    window.HticGazResidentielData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep
    };

});