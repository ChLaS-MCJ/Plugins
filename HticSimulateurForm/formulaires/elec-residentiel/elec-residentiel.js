// elec-residentiel.js - JavaScript complet pour collecte de données et calcul

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 7;
    let formData = {};
    let configData = {};

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupChauffageLogic();
        setupSimulationsRapides();
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

    // ===============================
    // LOGIQUE CHAUFFAGE ÉLECTRIQUE
    // ===============================

    function setupChauffageLogic() {
        // Gestion chauffage électrique vs autres
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

    // Validations par étape
    function validateStep1(stepElement) {
        let isValid = true;

        // Type logement
        const typeLogement = stepElement.find('input[name="type_logement"]:checked');
        if (!typeLogement.length) {
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

        // Isolation
        const isolation = stepElement.find('input[name="isolation"]:checked');
        if (!isolation.length) {
            isValid = false;
        }

        return isValid;
    }

    function validateStep2(stepElement) {
        // Type chauffage obligatoire
        const typeChauffage = stepElement.find('input[name="type_chauffage"]:checked');
        if (!typeChauffage.length) {
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
    }

    function validateStep3(stepElement) {
        // Type cuisson obligatoire
        const typeCuisson = stepElement.find('input[name="type_cuisson"]:checked');
        if (!typeCuisson.length) {
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
    }

    function validateStep4(stepElement) {
        // Eau chaude obligatoire
        const eauChaude = stepElement.find('input[name="eau_chaude"]:checked');
        return eauChaude.length > 0;
    }

    function validateStep5(stepElement) {
        // Éclairage obligatoire
        const eclairage = stepElement.find('input[name="type_eclairage"]:checked');
        return eclairage.length > 0;
    }

    function validateStep6(stepElement) {
        // Piscine obligatoire
        const piscine = stepElement.find('input[name="piscine"]:checked');
        return piscine.length > 0;
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
                } else {
                    // Retirer de la liste si décoché
                    const index = formData[name].indexOf($field.val());
                    if (index > -1) {
                        formData[name].splice(index, 1);
                    }
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

    // ===============================
    // CALCUL - SIMULATION PERSONNALISÉE
    // ===============================

    function calculateResults() {
        // Collecter toutes les données
        const allData = collectAllFormData();

        // Validation finale
        if (!allData.surface || !allData.nb_personnes || !allData.type_logement || !allData.isolation) {
            showValidationMessage('Des informations obligatoires sont manquantes.');
            return;
        }

        // Afficher l'étape des résultats
        showStep(7);
        updateProgress();
        updateNavigation();

        // Afficher l'état de chargement
        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation personnalisée...</p>
                <small>Traitement des données par le calculateur HTIC...</small>
            </div>
        `);

        // ENVOYER AU CALCULATEUR
        sendDataToCalculator(allData, configData);
    }

    // ===============================
    // ENVOI DONNÉES AU CALCULATEUR
    // ===============================

    function sendDataToCalculator(userData, configData) {
        // Préparer les données pour le calculateur
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-residentiel',
            user_data: userData,
            config_data: configData
        };

        // Ajouter le nonce si disponible
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.calculateNonce) {
            dataToSend.nonce = hticSimulateurUnifix.calculateNonce;
        }

        // Déterminer l'URL AJAX
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
                console.log('📥 Réponse du calculateur:', response);

                if (response.success) {
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
    // AFFICHAGE RÉSULTATS
    // ===============================

    function displayResults(results) {

        // Vérifier que toutes les données nécessaires sont présentes
        if (!results || !results.consommation_annuelle || !results.tarifs) {
            displayError('Données de résultats incomplètes');
            return;
        }

        // Adapter les données au format attendu
        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const puissanceRecommandee = results.puissance_recommandee || '12';

        // Tarifs avec gestion des différents formats
        const tarifBase = results.tarifs.base || {};
        const tarifHC = results.tarifs.hc || {};

        const totalAnnuelBase = parseInt(tarifBase.total_annuel) || parseInt(tarifBase.annuel) || 0;
        const totalMensuelBase = parseInt(tarifBase.total_mensuel) || parseInt(tarifBase.mensuel) || Math.round(totalAnnuelBase / 12);

        const totalAnnuelHC = parseInt(tarifHC.total_annuel) || parseInt(tarifHC.annuel) || 0;
        const totalMensuelHC = parseInt(tarifHC.total_mensuel) || parseInt(tarifHC.mensuel) || Math.round(totalAnnuelHC / 12);

        // Répartition avec gestion flexible
        const repartition = results.repartition || {};
        const chauffage = parseInt(repartition.chauffage) || 0;
        const eauChaude = parseInt(repartition.eau_chaude) || 0;
        const electromenagers = parseInt(repartition.electromenagers) || 0;
        const eclairage = parseInt(repartition.eclairage) || 0;
        const multimedia = parseInt(repartition.multimedia) || 0;
        const equipementsSpeciaux = parseInt(repartition.equipements_speciaux) || 0;
        const autres = parseInt(repartition.autres) || 0;

        // Calculer l'économie potentielle
        const economie = Math.abs(totalAnnuelBase - totalAnnuelHC);
        const tarifRecommande = results.tarifs.tarif_recommande || (totalAnnuelHC < totalAnnuelBase ? 'hc' : 'base');

        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">⚡</div>
                <h3>Votre consommation estimée</h3>
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <p>Puissance recommandée : <strong>${puissanceRecommandee} kVA</strong></p>
            </div>
            
            <!-- Comparaison des tarifs -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des tarifs</h3>
                <div class="tarifs-grid">
                    <div class="tarif-card ${tarifRecommande === 'base' ? 'recommended' : ''}">
                        <h4>Tarif BASE</h4>
                        <div class="tarif-prix">${totalAnnuelBase.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelBase.toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                    <div class="tarif-card ${tarifRecommande === 'hc' ? 'recommended' : ''}">
                        <h4>Heures Creuses</h4>
                        <div class="tarif-prix">${totalAnnuelHC.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelHC.toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                </div>
                ${economie > 0 ? `
                <div class="economies">
                    <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${economie.toLocaleString()}€/an en choisissant le bon tarif !</p>
                </div>
                ` : ''}
            </div>
            
            <!-- Répartition de la consommation -->
            <div class="repartition-conso">
                <h3>Répartition de votre consommation</h3>
                <div class="repartition-details">
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #ef4444;"></span>
                        <span>Chauffage : ${chauffage.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #3b82f6;"></span>
                        <span>Eau chaude : ${eauChaude.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #10b981;"></span>
                        <span>Électroménager : ${electromenagers.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #f59e0b;"></span>
                        <span>Éclairage : ${eclairage.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #8b5cf6;"></span>
                        <span>Multimédia : ${multimedia.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #06b6d4;"></span>
                        <span>Équipements spéciaux : ${equipementsSpeciaux.toLocaleString()} kWh</span>
                    </div>
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #6b7280;"></span>
                        <span>Autres : ${autres.toLocaleString()} kWh</span>
                    </div>
                </div>

            </div>
            
            <!-- Récapitulatif -->
           <!-- Récapitulatif -->
            <div class="recap-section">
                <h3>Récapitulatif de vos informations</h3>
                <div class="recap-grid">
                    <div class="recap-item">
                        <strong>Type de logement :</strong> ${getLogementLabel(results.recap?.type_logement ?? 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Surface :</strong> ${results.recap?.surface ?? 'Non spécifié'} m²
                    </div>
                    <div class="recap-item">
                        <strong>Nombre de personnes :</strong> ${results.recap?.nb_personnes ?? 'Non spécifié'}
                    </div>
                    <div class="recap-item">
                        <strong>Chauffage :</strong> ${getHeatingLabel(results.recap?.type_chauffage ?? 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Eau chaude :</strong> ${results.recap?.eau_chaude === 'oui' ? 'Électrique' : 'Autre énergie'}
                    </div>
                    <div class="recap-item">
                        <strong>Isolation :</strong> ${getIsolationLabel(results.recap?.isolation ?? 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Type de cuisson :</strong> ${getCuissonLabel(results.recap?.type_cuisson ?? 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Éclairage :</strong> ${getEclairageLabel(results.recap?.type_eclairage ?? 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Piscine :</strong> ${results.recap?.piscine === 'oui' ? 'Oui' : 'Non'}
                    </div>
                    <div class="recap-item">
                        <strong>Équipements spéciaux :</strong> 
                        ${(results.recap?.equipements_speciaux?.length ?? 0) > 0
                ? results.recap.equipements_speciaux.join(', ')
                : 'Aucun'}
                    </div>
                    <div class="recap-item">
                        <strong>Préférence tarifaire :</strong> ${getPreferenceLabel(results.recap?.preference_tarif ?? 'Non spécifié')}
                    </div>
                </div>
            </div>

            
            <!-- Actions -->
            <div class="results-actions">
                <button class="btn btn-primary" onclick="window.print()">Imprimer les résultats</button>
            </div>
        </div>
    `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

    }

    function getIsolationLabel(code) {
        switch (code) {
            case 'avant_1975': return 'Avant 1975 (peu isolé)';
            case '1975_2000': return '1975 - 2000';
            case 'apres_2000': return 'Après 2000 (bonne isolation)';
            default: return code;
        }
    }

    function getCuissonLabel(code) {
        switch (code) {
            case 'plaque_induction': return 'Plaques à induction';
            case 'plaque_vitro': return 'Plaques vitrocéramiques';
            case 'plaque_gaz': return 'Gaz';
            default: return code;
        }
    }

    function getEclairageLabel(code) {
        switch (code) {
            case 'led': return 'LED';
            case 'halogene': return 'Halogène';
            case 'fluorescent': return 'Fluorescent (néons, tubes)';
            default: return code;
        }
    }

    function getPreferenceLabel(code) {
        switch (code) {
            case 'indifferent': return 'Indifférent';
            case 'hc': return 'Optimisé Heures Creuses';
            case 'base': return 'Tarif Base';
            default: return code;
        }
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

        // Gestionnaire retour au formulaire
        $('#btn-back-to-form').on('click', function () {
            goToStep(6);
        });
    }

    // ===============================
    // FONCTIONS UTILITAIRES
    // ===============================

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
        goToStep: goToStep
    };

});