// elec-residentiel.js - JavaScript pour collecte de données et calcul

jQuery(document).ready(function ($) {

    // Variables
    let currentStep = 1;
    const totalSteps = 7;
    let formData = {};
    let configData = {};

    // Initialisation
    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupIsolationLogic();
    }

    // Chargement configuration
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

    // Navigation entre étapes
    function setupStepNavigation() {
        $('#btn-next').on('click', function () {
            if (validateCurrentStep()) {
                goToNextStep();
            }
        });

        $('#btn-previous').on('click', function () {
            goToPreviousStep();
        });

        $('#btn-calculate').on('click', function () {
            if (validateCurrentStep()) {
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
                goToStep(targetStep);
            }
        });
    }

    function goToNextStep() {
        if (currentStep < totalSteps) {
            saveCurrentStepData();
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

        // Boutons principaux
        if (currentStep === totalSteps) {
            $('#btn-next, #btn-calculate').hide();
            $('#btn-restart').show();
        } else if (currentStep === totalSteps - 1) {
            $('#btn-next').hide();
            $('#btn-calculate').show();
            $('#btn-restart').hide();
        } else {
            $('#btn-next').show();
            $('#btn-calculate, #btn-restart').hide();
        }
    }

    // Logique isolation conditionnelle
    function setupIsolationLogic() {
        $('input[name="type_chauffage"]').on('change', function () {
            const value = $(this).val();
            const isElectric = ['convecteurs', 'inertie', 'clim_reversible', 'pac'].includes(value);
            const isolationSection = $('#isolation-section');
            const isolationInputs = $('input[name="isolation"]');

            if (isElectric) {
                isolationSection.show();
                isolationInputs.attr('required', true);
            } else {
                isolationSection.hide();
                isolationInputs.attr('required', false).prop('checked', false);
            }
        });
    }

    // Validation
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

        // Retirer anciennes classes
        currentStepElement.find('.field-error, .field-success').removeClass('field-error field-success');

        // Validation spécifique par étape
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
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
    }

    function validateStep1(stepElement) {
        let isValid = true;

        // Type logement
        const typeLogement = stepElement.find('input[name="type_logement"]:checked');
        if (!typeLogement.length) {
            stepElement.find('input[name="type_logement"]').closest('.radio-card').addClass('field-error');
            isValid = false;
        }

        // Surface
        const surface = stepElement.find('#surface');
        const surfaceValue = parseInt(surface.val());
        if (!surfaceValue || surfaceValue < 20 || surfaceValue > 500) {
            surface.addClass('field-error');
            isValid = false;
        } else {
            surface.addClass('field-success');
        }

        // Nombre de personnes
        const nbPersonnes = stepElement.find('#nb_personnes');
        if (!nbPersonnes.val()) {
            nbPersonnes.addClass('field-error');
            isValid = false;
        } else {
            nbPersonnes.addClass('field-success');
        }

        return isValid;
    }

    function validateStep2(stepElement) {
        let isValid = true;

        // Type chauffage
        const typeChauffage = stepElement.find('input[name="type_chauffage"]:checked');
        if (!typeChauffage.length) {
            stepElement.find('input[name="type_chauffage"]').closest('.radio-card').addClass('field-error');
            isValid = false;
        } else {
            // Si chauffage électrique, vérifier isolation
            const isElectric = ['convecteurs', 'inertie', 'clim_reversible', 'pac'].includes(typeChauffage.val());
            if (isElectric) {
                const isolation = stepElement.find('input[name="isolation"]:checked');
                if (!isolation.length) {
                    stepElement.find('input[name="isolation"]').closest('.radio-card').addClass('field-error');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    function validateStep3(stepElement) {
        // Type cuisson obligatoire
        const typeCuisson = stepElement.find('input[name="type_cuisson"]:checked');
        if (!typeCuisson.length) {
            stepElement.find('input[name="type_cuisson"]').closest('.radio-card').addClass('field-error');
            return false;
        }
        return true;
    }

    function validateStep4(stepElement) {
        // Eau chaude obligatoire
        const eauChaude = stepElement.find('input[name="eau_chaude"]:checked');
        if (!eauChaude.length) {
            stepElement.find('input[name="eau_chaude"]').closest('.radio-card').addClass('field-error');
            return false;
        }
        return true;
    }

    function validateStep5(stepElement) {
        // Éclairage obligatoire
        const eclairage = stepElement.find('input[name="type_eclairage"]:checked');
        if (!eclairage.length) {
            stepElement.find('input[name="type_eclairage"]').closest('.radio-card').addClass('field-error');
            return false;
        }
        return true;
    }

    function validateStep6(stepElement) {
        // Piscine obligatoire
        const piscine = stepElement.find('input[name="piscine"]:checked');
        if (!piscine.length) {
            stepElement.find('input[name="piscine"]').closest('.radio-card').addClass('field-error');
            return false;
        }
        return true;
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

    // Collecte des données
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
                if (!formData[name]) formData[name] = [];
                if ($field.is(':checked')) {
                    formData[name].push($field.val());
                }
            } else {
                formData[name] = $field.val();
            }
        });
    }

    function collectAllFormData() {
        formData = {};

        $('.form-step input, .form-step select').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name) return;

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[name] = $field.val();
                }
            } else if (type === 'checkbox') {
                if (!formData[name]) formData[name] = [];
                if ($field.is(':checked')) {
                    formData[name].push($field.val());
                }
            } else {
                formData[name] = $field.val();
            }
        });

        return formData;
    }

    // Lancement du calcul - ENVOI DES DONNÉES
    function calculateResults() {
        // Collecter toutes les données
        const allData = collectAllFormData();

        // Afficher l'étape des résultats
        showStep(7);
        updateProgress();
        updateNavigation();

        // Afficher l'état de chargement
        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation personnalisée...</p>
            </div>
        `);

        // ENVOYER LES DONNÉES AU CALCULATEUR
        sendDataToCalculator(allData, configData);
    }

    // Envoi des données au calculateur externe - CORRIGÉ
    // Dans elec-residentiel.js, remplacer la fonction sendDataToCalculator par :

    function sendDataToCalculator(userData, configData) {
        // Vérifier quelle variable de localisation est disponible
        let ajaxConfig;

        if (typeof hticSimulateur !== 'undefined') {
            ajaxConfig = hticSimulateur;
        } else if (typeof hticSimulateurUnifix !== 'undefined') {
            ajaxConfig = {
                ajaxUrl: hticSimulateurUnifix.ajaxUrl,
                nonce: hticSimulateurUnifix.calculateNonce,
                type: 'elec-residentiel'
            };
        } else {
            ajaxConfig = {
                ajaxUrl: '/wp-admin/admin-ajax.php',
                nonce: '',
                type: 'elec-residentiel'
            };
        }

        console.log('📤 Envoi des données au calculateur PHP:', userData);

        // AJAX vers le fichier de calcul PHP
        $.ajax({
            url: ajaxConfig.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'htic_calculate_estimation',
                nonce: ajaxConfig.nonce,
                type: 'elec-residentiel',
                user_data: userData,
                config_data: configData
            },
            success: function (response) {
                console.log('📥 Réponse complète du serveur:', response);

                if (response.success) {
                    // AFFICHER LES LOGS PHP DANS LA CONSOLE JAVASCRIPT
                    if (response.data.console_logs) {
                        console.log('💬 === LOGS DU CALCULATEUR PHP ===');
                        response.data.console_logs.forEach(function (log) {
                            console.log(log);
                        });
                        console.log('💬 === FIN LOGS PHP ===');
                    }

                    displayResults(response.data);
                } else {
                    displayError('Erreur lors du calcul: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', error);
                displayError('Erreur de connexion lors du calcul');
            }
        });
    }

    // Affichage des résultats
    function displayResults(results) {

        const resultsHtml = `
            <div class="results-summary">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">⚡</div>
                    <h3>Votre consommation estimée</h3>
                    <div class="big-number">${results.consommation_annuelle.toLocaleString()} <span>kWh/an</span></div>
                    <p>Puissance recommandée : <strong>${results.puissance_recommandee} kVA</strong></p>
                </div>
                
                <!-- Comparaison des tarifs -->
                <div class="tarifs-comparison">
                    <h3>💰 Comparaison des tarifs</h3>
                    <div class="tarifs-grid">
                        <div class="tarif-card ${results.tarifs.recommande === 'base' ? 'recommended' : ''}">
                            <h4>Tarif BASE</h4>
                            <div class="tarif-prix">${results.tarifs.base.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.base.total_mensuel}€/mois</div>
                            ${results.tarifs.recommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                        </div>
                        <div class="tarif-card ${results.tarifs.recommande === 'hc' ? 'recommended' : ''}">
                            <h4>Heures Creuses</h4>
                            <div class="tarif-prix">${results.tarifs.hc.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.hc.total_mensuel}€/mois</div>
                            ${results.tarifs.recommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                        </div>
                    </div>
                    <div class="economies">
                        <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${Math.round(results.tarifs.economies)}€/an en choisissant le meilleur tarif !</p>
                    </div>
                </div>
                
                <!-- Répartition de la consommation -->
                <div class="repartition-conso">
                    <h3>📊 Répartition de votre consommation</h3>
                    <div class="repartition-details">
                        ${results.repartition.chauffage > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #ef4444;"></span>
                            <span>Chauffage : ${Math.round(results.repartition.chauffage).toLocaleString()} kWh</span>
                        </div>` : ''}
                        ${results.repartition.eau_chaude > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #3b82f6;"></span>
                            <span>Eau chaude : ${Math.round(results.repartition.eau_chaude).toLocaleString()} kWh</span>
                        </div>` : ''}
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #10b981;"></span>
                            <span>Électroménager : ${Math.round(results.repartition.electromenagers).toLocaleString()} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #f59e0b;"></span>
                            <span>Éclairage : ${Math.round(results.repartition.eclairage).toLocaleString()} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #8b5cf6;"></span>
                            <span>Autres : ${Math.round((results.repartition.cuisson || 0) + (results.repartition.piscine || 0) + (results.repartition.equipements_speciaux || 0) + (results.repartition.multimedia || 0)).toLocaleString()} kWh</span>
                        </div>
                    </div>
                </div>
                
                <!-- Récapitulatif -->
                <div class="recap-section">
                    <h3>📋 Récapitulatif de vos informations</h3>
                    <div class="recap-grid">
                        <div class="recap-item">
                            <strong>Type de logement :</strong> ${getLogementLabel(results.recap.type_logement)}
                        </div>
                        <div class="recap-item">
                            <strong>Surface :</strong> ${results.recap.surface} m²
                        </div>
                        <div class="recap-item">
                            <strong>Nombre de personnes :</strong> ${results.recap.nb_personnes}
                        </div>
                        <div class="recap-item">
                            <strong>Chauffage :</strong> ${getHeatingLabel(results.recap.type_chauffage)}
                        </div>
                        <div class="recap-item">
                            <strong>Eau chaude :</strong> ${results.recap.eau_chaude === 'oui' ? 'Électrique' : 'Autre énergie'}
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="results-actions">
                    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer les résultats</button>
                    <button class="btn btn-secondary" onclick="downloadPDF()">📄 Télécharger PDF</button>
                </div>
            </div>
        `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
    }

    function displayError(message) {
        $('#results-container').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">🔄 Réessayer</button>
            </div>
        `);
    }

    // Fonctions utilitaires pour l'affichage
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

        $('#simulateur-elec-residentiel')[0].reset();

        showStep(1);
        updateProgress();
        updateNavigation();

        $('.field-error, .field-success').removeClass('field-error field-success');
    }

    // Fonctions globales
    window.downloadPDF = function () {
        alert('Fonction de téléchargement PDF en cours de développement');
    };

    // API publique pour récupérer les données
    window.HticSimulateurData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
    };

});