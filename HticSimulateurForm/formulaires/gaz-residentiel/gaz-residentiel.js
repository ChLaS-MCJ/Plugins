/**
 * JavaScript pour le formulaire Gaz Résidentiel
 * Fichier: formulaires/gaz-residentiel/gaz-residentiel.js
 */

(function ($) {
    'use strict';

    // Variables globales
    let currentStep = 1;
    const totalSteps = 5;
    let formData = {};
    let calculationInProgress = false;

    // ===============================
    // INITIALISATION
    // ===============================

    $(document).ready(function () {
        console.log('🔥 Simulateur Gaz Résidentiel - Initialisation');

        initializeForm();
        bindEvents();
        updateProgressBar();

        console.log('✅ Simulateur Gaz Résidentiel - Prêt');
    });

    function initializeForm() {
        // Masquer toutes les étapes sauf la première
        $('.form-step').hide();
        $('.form-step[data-step="1"]').show().addClass('active');

        // Initialiser les données du formulaire
        collectFormData();

        // Gérer l'affichage conditionnel initial
        toggleChauffageDetails();
    }

    function bindEvents() {
        // Navigation entre les étapes
        $('.btn-next').on('click', handleNext);
        $('.btn-prev').on('click', handlePrevious);
        $('.btn-calculate').on('click', handleCalculation);

        // Navigation directe via les étapes
        $('.step').on('click', function () {
            const targetStep = parseInt($(this).data('step'));
            if (targetStep <= currentStep || $(this).hasClass('completed')) {
                navigateToStep(targetStep);
            }
        });

        // Mise à jour en temps réel des données
        $('.simulateur-form input, .simulateur-form select').on('change', function () {
            collectFormData();
            updateProgressBar();
        });

        // Gestion de l'affichage conditionnel
        $('input[name="chauffage_gaz"]').on('change', toggleChauffageDetails);

        // Validation des champs numériques
        $('input[type="number"]').on('input', validateNumericInput);

        // Gestion des erreurs
        $(document).on('click', '.error-container', hideError);
    }

    // ===============================
    // NAVIGATION ENTRE LES ÉTAPES
    // ===============================

    function handleNext() {
        console.log('📍 Navigation: Étape suivante demandée');

        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                navigateToStep(currentStep + 1);
            }
        }
    }

    function handlePrevious() {
        console.log('📍 Navigation: Étape précédente demandée');

        if (currentStep > 1) {
            navigateToStep(currentStep - 1);
        }
    }

    function navigateToStep(stepNumber) {
        console.log('🔄 Navigation vers l\'étape:', stepNumber);

        // Masquer l'étape actuelle
        $('.form-step.active').removeClass('active').fadeOut(200);

        // Mettre à jour les indicateurs
        updateStepIndicators(stepNumber);

        // Afficher la nouvelle étape
        setTimeout(function () {
            $(`.form-step[data-step="${stepNumber}"]`).addClass('active').fadeIn(300);
            currentStep = stepNumber;
            updateProgressBar();

            // Scroll vers le haut
            $('html, body').animate({ scrollTop: 0 }, 300);
        }, 200);
    }

    function updateStepIndicators(stepNumber) {
        $('.step').removeClass('active completed');

        for (let i = 1; i <= totalSteps; i++) {
            const $step = $(`.step[data-step="${i}"]`);

            if (i < stepNumber) {
                $step.addClass('completed');
            } else if (i === stepNumber) {
                $step.addClass('active');
            }
        }
    }

    function updateProgressBar() {
        const progress = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progress + '%');
        $('.progress-fill').attr('data-progress', Math.round(progress));
    }

    // ===============================
    // VALIDATION DES ÉTAPES
    // ===============================

    function validateCurrentStep() {
        const $currentStepForm = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;
        let errorMessage = '';

        // Validation selon l'étape
        switch (currentStep) {
            case 1:
                // Validation étape logement
                const superficie = parseInt($('#superficie').val());
                const nbPersonnes = parseInt($('#nb_personnes').val());

                if (!superficie || superficie < 20 || superficie > 500) {
                    errorMessage = 'Veuillez saisir une superficie entre 20 et 500 m²';
                    isValid = false;
                } else if (!nbPersonnes || nbPersonnes < 1 || nbPersonnes > 10) {
                    errorMessage = 'Veuillez saisir un nombre de personnes entre 1 et 10';
                    isValid = false;
                }
                break;

            case 2:
                // Validation étape chauffage
                if (!$('input[name="chauffage_gaz"]:checked').length) {
                    errorMessage = 'Veuillez indiquer si vous utilisez le gaz pour le chauffage';
                    isValid = false;
                } else if ($('input[name="chauffage_gaz"]:checked').val() === 'oui' &&
                    !$('input[name="isolation"]:checked').length) {
                    errorMessage = 'Veuillez sélectionner le niveau d\'isolation de votre logement';
                    isValid = false;
                }
                break;

            case 3:
                // Validation étape eau chaude
                if (!$('input[name="eau_chaude"]:checked').length) {
                    errorMessage = 'Veuillez indiquer le mode de production d\'eau chaude';
                    isValid = false;
                }
                break;

            case 4:
                // Validation étape cuisson
                if (!$('input[name="cuisson"]:checked').length) {
                    errorMessage = 'Veuillez indiquer si vous utilisez le gaz pour la cuisson';
                    isValid = false;
                } else if (!$('input[name="offre"]:checked').length) {
                    errorMessage = 'Veuillez sélectionner le type d\'offre souhaité';
                    isValid = false;
                }
                break;
        }

        if (!isValid) {
            showError(errorMessage);
            return false;
        }

        return true;
    }

    function validateNumericInput() {
        const $input = $(this);
        const value = parseFloat($input.val());
        const min = parseFloat($input.attr('min'));
        const max = parseFloat($input.attr('max'));

        if (value < min) {
            $input.val(min);
        } else if (value > max) {
            $input.val(max);
        }

        collectFormData();
    }

    // ===============================
    // GESTION DES DONNÉES DU FORMULAIRE
    // ===============================

    function collectFormData() {
        formData = {
            // Étape 1 : Logement
            superficie: parseInt($('#superficie').val()) || 150,
            nb_personnes: parseInt($('#nb_personnes').val()) || 5,
            commune: $('#commune').val() || 'BASCONS',
            type_logement: $('input[name="type_logement"]:checked').val() || 'maison',

            // Étape 2 : Chauffage
            chauffage_gaz: $('input[name="chauffage_gaz"]:checked').val() || 'oui',
            isolation: $('input[name="isolation"]:checked').val() || 'avant_1980',

            // Étape 3 : Eau chaude
            eau_chaude: $('input[name="eau_chaude"]:checked').val() || 'gaz',

            // Étape 4 : Cuisson et offre
            cuisson: $('input[name="cuisson"]:checked').val() || 'gaz',
            offre: $('input[name="offre"]:checked').val() || 'base'
        };

        console.log('📋 Données du formulaire mises à jour:', formData);
    }

    // ===============================
    // AFFICHAGE CONDITIONNEL
    // ===============================

    function toggleChauffageDetails() {
        const chauffageGaz = $('input[name="chauffage_gaz"]:checked').val();
        const $details = $('.chauffage-details');

        if (chauffageGaz === 'oui') {
            $details.slideDown(300);
        } else {
            $details.slideUp(300);
        }
    }

    // ===============================
    // CALCUL ET AFFICHAGE DES RÉSULTATS
    // ===============================

    function handleCalculation() {
        console.log('🧮 Démarrage du calcul de l\'estimation');

        if (!validateCurrentStep()) {
            return;
        }

        if (calculationInProgress) {
            console.log('⏳ Calcul déjà en cours...');
            return;
        }

        collectFormData();
        calculateEstimation();
    }

    function calculateEstimation() {
        calculationInProgress = true;
        showLoading();

        console.log('📊 Envoi des données pour calcul:', formData);

        $.ajax({
            url: hticSimulateurUnifix.ajaxUrl,
            type: 'POST',
            data: {
                action: 'htic_calculate_estimation',
                nonce: hticSimulateurUnifix.calculateNonce,
                type: 'gaz-residentiel',
                data: formData
            },
            timeout: 30000,
            success: function (response) {
                console.log('✅ Réponse du calcul reçue:', response);
                hideLoading();

                if (response.success && response.data) {
                    displayResults(response.data);
                    navigateToStep(5);
                } else {
                    const errorMsg = response.data?.message || 'Erreur lors du calcul';
                    displayError(errorMsg);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', { xhr, status, error });
                hideLoading();

                let errorMessage = 'Une erreur est survenue lors du calcul.';

                if (xhr.status === 0) {
                    errorMessage = 'Problème de connexion. Vérifiez votre connexion internet.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Erreur interne du serveur. Contactez l\'administrateur.';
                } else if (status === 'timeout') {
                    errorMessage = 'Le calcul prend trop de temps. Réessayez.';
                }

                displayError(errorMessage);
            },
            complete: function () {
                calculationInProgress = false;
            }
        });
    }

    // ===============================
    // AFFICHAGE DES RÉSULTATS
    // ===============================

    function displayResults(results) {
        console.log('📊 Affichage des résultats:', results);

        if (!results || typeof results.total_annuel === 'undefined') {
            displayError('Données de résultats incomplètes');
            return;
        }

        const totalAnnuel = parseFloat(results.total_annuel) || 0;
        const totalMensuel = Math.round(totalAnnuel / 12);

        const resultatsHTML = `
            <div class="resultats-content">
                <!-- Estimation principale -->
                <div class="estimation-principale">
                    <h3>💰 Votre budget gaz estimé</h3>
                    <div class="montant-annuel">${formatPrice(totalAnnuel)}</div>
                    <div class="montant-mensuel">Soit environ ${formatPrice(totalMensuel)}/mois</div>
                    <p>Estimation basée sur vos réponses et les tarifs actuels</p>
                </div>
                
                <!-- Détail de la consommation -->
                <div class="details-consommation">
                    <h4>🔍 Détail de votre consommation</h4>
                    <div class="conso-grid">
                        ${results.detail_chauffage ? `
                        <div class="conso-item">
                            <span class="conso-label">🏠 Chauffage</span>
                            <span class="conso-valeur">${results.detail_chauffage} kWh/an</span>
                        </div>` : ''}
                        
                        ${results.detail_eau_chaude ? `
                        <div class="conso-item">
                            <span class="conso-label">🚿 Eau chaude</span>
                            <span class="conso-valeur">${results.detail_eau_chaude} kWh/an</span>
                        </div>` : ''}
                        
                        ${results.detail_cuisson ? `
                        <div class="conso-item">
                            <span class="conso-label">🍳 Cuisson</span>
                            <span class="conso-valeur">${results.detail_cuisson} kWh/an</span>
                        </div>` : ''}
                        
                        <div class="conso-item">
                            <span class="conso-label">⚡ Total</span>
                            <span class="conso-valeur">${results.consommation_totale || 0} kWh/an</span>
                        </div>
                    </div>
                </div>
                
                <!-- Informations complémentaires -->
                <div class="informations-complementaires">
                    <h4>ℹ️ À savoir</h4>
                    <ul>
                        <li>Cette estimation est basée sur des moyennes statistiques</li>
                        <li>Votre consommation réelle peut varier selon vos habitudes</li>
                        <li>Les prix incluent l'abonnement et les taxes</li>
                        ${results.offre === 'propane' ? '<li>Tarifs gaz propane approximatifs (variables selon fournisseur)</li>' : ''}
                    </ul>
                </div>
                
                <!-- Conseils personnalisés -->
                ${generatePersonalizedTips()}
            </div>
        `;

        $('#resultats-container').html(resultatsHTML);

        // Animation d'apparition
        $('.resultats-content').hide().fadeIn(600);
    }

    function generatePersonalizedTips() {
        let tips = '<div class="conseils-personnalises"><h4>💡 Conseils pour économiser</h4><ul>';

        // Conseils basés sur les données du formulaire
        if (formData.superficie > 200) {
            tips += '<li>Logement spacieux : pensez à la programmation du chauffage par zones</li>';
        }

        if (formData.isolation === 'avant_1980') {
            tips += '<li>Isolation ancienne : des travaux d\'isolation pourraient réduire votre facture de 30%</li>';
        }

        if (formData.nb_personnes > 4) {
            tips += '<li>Famille nombreuse : optimisez les heures de cuisson et d\'eau chaude</li>';
        }

        if (formData.chauffage_gaz === 'oui') {
            tips += '<li>Réduisez la température de 1°C pour économiser environ 7% sur le chauffage</li>';
        }

        tips += '<li>Entretenez régulièrement vos appareils gaz pour optimiser leur rendement</li>';
        tips += '</ul></div>';

        return tips;
    }

    // ===============================
    // UTILITAIRES
    // ===============================

    function formatPrice(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    function showLoading() {
        $('#loading-overlay').fadeIn(200);
        $('.btn-calculate').prop('disabled', true).addClass('loading');
    }

    function hideLoading() {
        $('#loading-overlay').fadeOut(200);
        $('.btn-calculate').prop('disabled', false).removeClass('loading');
    }

    function showError(message) {
        hideError();

        $('.error-text').text(message);
        $('#error-container').fadeIn(300);

        // Masquer automatiquement après 5 secondes
        setTimeout(hideError, 5000);

        console.warn('⚠️ Erreur affichée:', message);
    }

    function hideError() {
        $('#error-container').fadeOut(200);
    }

    function displayError(message) {
        showError(message);

        // Scroll vers le haut pour voir l'erreur
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    // ===============================
    // GESTION DES RACCOURCIS CLAVIER
    // ===============================

    $(document).on('keydown', function (e) {
        // Échap pour masquer les erreurs
        if (e.key === 'Escape') {
            hideError();
        }

        // Flèches pour navigation
        if (e.altKey) {
            if (e.key === 'ArrowRight' && currentStep < totalSteps) {
                e.preventDefault();
                handleNext();
            } else if (e.key === 'ArrowLeft' && currentStep > 1) {
                e.preventDefault();
                handlePrevious();
            }
        }

        // Entrée pour action principale
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const $activeBtn = $('.form-step.active .btn-primary:visible');
            if ($activeBtn.length) {
                e.preventDefault();
                $activeBtn.click();
            }
        }
    });

    // ===============================
    // EXPOSITION DES FONCTIONS GLOBALES
    // ===============================

    // Exposer certaines fonctions pour utilisation externe
    window.gazResidentielSimulateur = {
        navigateToStep: navigateToStep,
        getCurrentStep: function () { return currentStep; },
        getFormData: function () { return formData; },
        recalculate: function () {
            collectFormData();
            calculateEstimation();
        }
    };

    console.log('🔥 JavaScript Gaz Résidentiel - Chargé');

})(jQuery);