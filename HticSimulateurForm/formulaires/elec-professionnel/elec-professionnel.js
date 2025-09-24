// formulaires/elec-professionnel/elec-professionnel.js - Version finale complète

jQuery(document).ready(function ($) {
    'use strict';

    // ================================
    // VARIABLES GLOBALES
    // ================================
    let currentStep = 1;
    const totalSteps = 5;
    let formData = {};
    let configData = {};
    let uploadedFiles = {};
    let calculResults = {};

    // ================================
    // INITIALISATION
    // ================================
    init();

    function init() {
        console.log('🏢 Initialisation Simulateur Électricité Professionnel');
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupProLogic();
        setupFileUpload();
        updateUI();
        console.log('✅ Simulateur Professionnel initialisé');
    }

    // ================================
    // CONFIGURATION
    // ================================
    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config-pro');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration chargée:', configData);
            } catch (e) {
                console.error('❌ Erreur configuration:', e);
                configData = {};
            }
        }

        // Créer les variables globales pour la compatibilité
        if (!window.hticSimulateur && (configData.ajax_url || configData.nonce)) {
            window.hticSimulateur = {
                ajaxUrl: configData.ajax_url || '/wp-admin/admin-ajax.php',
                nonce: configData.nonce || configData.calculate_nonce,
                type: 'elec-professionnel'
            };
            console.log('✅ Variables globales hticSimulateur créées');
        }
    }

    // ================================
    // LOGIQUE PROFESSIONNELLE (SELON EXCEL)
    // ================================
    function setupProLogic() {
        console.log('🏢 Configuration de la logique professionnelle');

        // Gestion du changement de catégorie
        $('input[name="categorie"]').on('change', function () {
            const categorie = $(this).val();
            console.log('📊 Changement catégorie:', categorie);
            updateEligibiliteTRV(categorie);
            updateFormulesTarifaires(categorie);
            updatePuissanceOptions(categorie);
        });

        // Gestion du changement de formule tarifaire
        $('input[name="formule_tarifaire"]').on('change', function () {
            const formule = $(this).val();
            const categorie = $('input[name="categorie"]:checked').val();
            console.log('📊 Changement formule:', formule, 'pour catégorie:', categorie);
            updatePuissanceOptions(categorie, formule);
        });

        // Information sur la consommation en temps réel
        $('#conso_annuelle').on('input', function () {
            const conso = parseInt($(this).val());
            const $helpText = $(this).closest('.form-group').find('.form-help');

            if (conso < 10000) {
                $helpText.text('💡 Petite consommation - Bureau ou petit commerce');
            } else if (conso < 50000) {
                $helpText.text('💡 Consommation moyenne - PME ou commerce');
            } else if (conso < 200000) {
                $helpText.text('💡 Grande consommation - Industrie ou gros consommateur');
            } else {
                $helpText.text('💡 Très grande consommation - Industrie lourde');
            }
        });

        // Au chargement, initialiser selon la catégorie par défaut
        const categorieInitiale = $('input[name="categorie"]:checked').val() || 'BT < 36 kVA';
        updateEligibiliteTRV(categorieInitiale);
        updateFormulesTarifaires(categorieInitiale);
        updatePuissanceOptions(categorieInitiale);
    }

    // Gestion de l'éligibilité TRV selon la catégorie
    function updateEligibiliteTRV(categorie) {
        const $eligibiliteContainer = $('input[name="eligible_trv"]').closest('.form-group');

        // Retirer les messages d'info existants
        $('.trv-info-message').remove();

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            // BT ≤ 36 kVA : Éligibilité TRV possible
            $eligibiliteContainer.show();

            // Ajouter les conditions d'éligibilité
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
            $eligibiliteContainer.after(conditionsHTML);

            // S'assurer qu'une option est sélectionnée
            if (!$('input[name="eligible_trv"]:checked').length) {
                $('input[name="eligible_trv"][value="oui"]').prop('checked', true);
            }

        } else if (categorie === 'BT > 36 kVA' || categorie === 'HTA') {
            // BT > 36 kVA et HTA : Pas éligible au TRV
            $eligibiliteContainer.hide();

            // Forcer la valeur à "non" pour ces catégories
            $('input[name="eligible_trv"][value="non"]').prop('checked', true);

            // Ajouter un message explicatif après la catégorie
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

            // Insérer le message après la sélection de catégorie
            $('input[name="categorie"]').closest('.form-group').after(messageHTML);
        }
    }

    // Mise à jour des formules tarifaires selon la catégorie
    function updateFormulesTarifaires(categorie) {
        const $formuleContainer = $('input[name="formule_tarifaire"]').closest('.radio-group');

        // Retirer les notes existantes
        $('.formule-note').remove();

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            // Pour BT ≤ 36 kVA : Base et Heures Creuses disponibles
            $formuleContainer.find('.radio-card').show();
            // Restaurer les textes originaux
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text strong').text('Option Base');
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text span').text('Tarif unique toute la journée');

        } else if (categorie === 'BT > 36 kVA') {
            // Pour BT > 36 kVA : seulement HTA
            $formuleContainer.find('.radio-card').hide();
            $formuleContainer.find('.radio-card:has(input[value="Base"])').show();
            $('input[name="formule_tarifaire"][value="Base"]').prop('checked', true);

            // Modifier le texte
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text strong').text('Tarif HTA');
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text span').text('Tarification haute tension applicable');

            // Ajouter une note
            $formuleContainer.after('<p class="formule-note">ℹ️ Pour BT > 36 kVA, tarification HTA appliquée</p>');

        } else if (categorie === 'HTA') {
            // Pour HTA : formule spécifique
            $formuleContainer.find('.radio-card').hide();
            $formuleContainer.find('.radio-card:has(input[value="Base"])').show();
            $('input[name="formule_tarifaire"][value="Base"]').prop('checked', true);

            // Modifier le texte
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text strong').text('Tarif HTA');
            $formuleContainer.find('.radio-card:has(input[value="Base"]) .radio-text span').text('Haute tension');

            $formuleContainer.after('<p class="formule-note">ℹ️ Tarification haute tension sur mesure</p>');
        }
    }

    // Mise à jour des options de puissance selon les règles Excel
    function updatePuissanceOptions(categorie, formule = null) {
        const $puissanceSelect = $('#puissance');

        // Sauvegarder la valeur actuelle avant de modifier les options
        const currentPuissance = $puissanceSelect.val();
        console.log('💾 Puissance actuelle sauvegardée:', currentPuissance);

        let options = '';
        let availablePuissances = [];

        if (!formule) {
            formule = $('input[name="formule_tarifaire"]:checked').val() || 'Base';
        }

        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            if (formule === 'Base') {
                // Base : toutes les puissances disponibles
                availablePuissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];
                options = `
                    <option value="">Choisir...</option>
                    <option value="3">3 kVA</option>
                    <option value="6">6 kVA</option>
                    <option value="9">9 kVA</option>
                    <option value="12">12 kVA</option>
                    <option value="15">15 kVA</option>
                    <option value="18">18 kVA</option>
                    <option value="24">24 kVA</option>
                    <option value="30">30 kVA</option>
                    <option value="36">36 kVA</option>
                `;
            } else if (formule === 'Heures creuses') {
                // HC : seulement à partir de 6 kVA
                availablePuissances = [6, 9, 12, 15, 18, 24, 30, 36];
                options = `
                    <option value="">Choisir...</option>
                    <option value="6">6 kVA</option>
                    <option value="9">9 kVA</option>
                    <option value="12">12 kVA</option>
                    <option value="15">15 kVA</option>
                    <option value="18">18 kVA</option>
                    <option value="24">24 kVA</option>
                    <option value="30">30 kVA</option>
                    <option value="36">36 kVA</option>
                `;
            }
        } else if (categorie === 'BT > 36 kVA') {
            // BT > 36 kVA : utiliser 36 kVA pour les calculs HTA
            availablePuissances = [36];
            options = `
                <option value="">Choisir...</option>
                <option value="36" selected>Tarif HTA (>36 kVA)</option>
            `;
        } else if (categorie === 'HTA') {
            // HTA : puissance symbolique
            availablePuissances = [36];
            options = `
                <option value="">Choisir...</option>
                <option value="36" selected>HTA</option>
            `;
        }

        // Mettre à jour les options
        $puissanceSelect.html(options);

        // Restaurer la valeur précédente si elle est toujours valide
        if (currentPuissance && availablePuissances.includes(parseInt(currentPuissance))) {
            $puissanceSelect.val(currentPuissance);
            console.log('✅ Puissance restaurée:', currentPuissance);
        } else if (currentPuissance && !availablePuissances.includes(parseInt(currentPuissance))) {
            // Si la puissance précédente n'est plus disponible
            if (formule === 'Heures creuses' && parseInt(currentPuissance) === 3) {
                // Passage de Base 3kVA vers HC : prendre la puissance minimum disponible
                $puissanceSelect.val('6');
                console.log('⚡ Puissance ajustée de 3 kVA à 6 kVA (minimum pour HC)');

                // Afficher un message d'information
                showInfoMessage('La puissance a été ajustée à 6 kVA (minimum pour l\'option Heures Creuses)');
            } else {
                // Essayer de trouver la puissance la plus proche
                const closestPuissance = findClosestPuissance(parseInt(currentPuissance), availablePuissances);
                if (closestPuissance) {
                    $puissanceSelect.val(closestPuissance);
                    console.log(`⚡ Puissance ajustée de ${currentPuissance} à ${closestPuissance} kVA`);
                }
            }
        } else if (!currentPuissance) {
            // Si aucune puissance n'était sélectionnée, mettre une valeur par défaut intelligente
            if (formule === 'Base') {
                $puissanceSelect.val('6'); // 6 kVA par défaut pour Base
            } else if (formule === 'Heures creuses') {
                $puissanceSelect.val('9'); // 9 kVA par défaut pour HC (plus courant)
            }
        }

        // Afficher une aide contextuelle
        updatePuissanceHelp(categorie, formule);
    }

    // Fonction helper pour trouver la puissance la plus proche
    function findClosestPuissance(targetPuissance, availablePuissances) {
        if (availablePuissances.length === 0) return null;

        return availablePuissances.reduce((prev, curr) => {
            return Math.abs(curr - targetPuissance) < Math.abs(prev - targetPuissance) ? curr : prev;
        });
    }

    // Fonction pour afficher un message d'information temporaire
    function showInfoMessage(message) {
        // Supprimer les messages existants
        $('.info-toast').remove();

        const infoToast = $(`
            <div class="info-toast">
                <span class="info-icon">ℹ️</span>
                <span class="info-text">${message}</span>
            </div>
        `);

        // Ajouter le message près du select de puissance
        $('#puissance').closest('.form-group').append(infoToast);

        // Animation d'entrée
        setTimeout(() => {
            infoToast.addClass('show');
        }, 10);

        // Disparition automatique après 4 secondes
        setTimeout(() => {
            infoToast.removeClass('show');
            setTimeout(() => {
                infoToast.remove();
            }, 300);
        }, 4000);
    }

    // Aide contextuelle pour la puissance
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

    // ================================
    // NAVIGATION ENTRE ÉTAPES
    // ================================
    function setupStepNavigation() {
        $('#btn-next-pro').on('click', function () {
            console.log('📍 Clic sur bouton suivant - Étape actuelle:', currentStep);
            if (validateCurrentStep()) {
                saveCurrentStepData();
                nextStep();
            }
        });

        $('#btn-previous-pro').on('click', function () {
            console.log('📍 Clic sur bouton précédent - Étape actuelle:', currentStep);
            saveCurrentStepData();
            prevStep();
        });

        $('#btn-calculate-pro').on('click', function () {
            console.log('📍 Clic sur bouton calculer');
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

        // Navigation par clic sur les étapes (seulement retour en arrière)
        $('.step').on('click', function () {
            const targetStep = parseInt($(this).data('step'));
            if (targetStep < currentStep || targetStep === 1) {
                saveCurrentStepData();
                goToStep(targetStep);
            }
        });
    }

    function nextStep() {
        console.log('🔄 Passage à l\'étape suivante. Étape actuelle:', currentStep);

        if (currentStep < totalSteps) {
            currentStep++;
            updateUI();
            scrollToTop();

            // Actions spécifiques par étape
            if (currentStep === 3) {
                setupSelectionStep();
            } else if (currentStep === 4) {
                setupContactStep();
            } else if (currentStep === 5) {
                setupRecapStep();
            }

            console.log('✅ Nouvelle étape:', currentStep);
        }
    }

    function prevStep() {
        console.log('🔄 Retour à l\'étape précédente. Étape actuelle:', currentStep);

        if (currentStep > 1) {
            currentStep--;
            updateUI();
            scrollToTop();
        }
    }

    function goToStep(stepNumber) {
        if (stepNumber >= 1 && stepNumber <= totalSteps) {
            console.log('🎯 Navigation vers étape:', stepNumber);
            currentStep = stepNumber;
            updateUI();
            scrollToTop();

            // Actions spécifiques
            if (currentStep === 3) {
                setupSelectionStep();
            } else if (currentStep === 4) {
                setupContactStep();
            } else if (currentStep === 5) {
                setupRecapStep();
            }
        }
    }

    function updateUI() {
        console.log('🎨 Mise à jour UI pour étape:', currentStep);

        // Mise à jour des étapes
        $('.form-step').removeClass('active');
        $(`.form-step[data-step="${currentStep}"]`).addClass('active');

        // Mise à jour de la barre de progression
        const progressPercent = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');

        // Mise à jour des indicateurs d'étape
        $('.step').removeClass('active completed');
        for (let i = 1; i < currentStep; i++) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        }
        $(`.step[data-step="${currentStep}"]`).addClass('active');

        // Mise à jour des boutons
        $('#btn-previous-pro').toggle(currentStep > 1);

        if (currentStep === totalSteps) {
            // Étape 5 : Récapitulatif
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').show();
        } else if (currentStep === 1) {
            // Étape 1 : Configuration
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').show();
            $('#btn-restart-pro').hide();
        } else if (currentStep === 2) {
            // Étape 2 : Résultats
            $('#btn-next-pro').show().html('<span class="btn-icon">✅</span> Je souscris');
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').hide();
        } else {
            // Étapes 3, 4
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

    // ================================
    // VALIDATION
    // ================================
    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        console.log('🔍 Validation étape:', currentStep);

        // Retirer les classes d'erreur existantes
        currentStepElement.find('.field-error').removeClass('field-error');

        // Validation par étape
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

        console.log('✔️ Validation résultat:', isValid);
        return isValid;
    }

    function validateStep1(stepElement) {
        let isValid = true;
        const categorie = stepElement.find('input[name="categorie"]:checked').val();

        // Liste des champs requis de base
        const requiredFields = [
            'categorie',
            'puissance',
            'formule_tarifaire',
            'conso_annuelle'
        ];

        // Ajouter eligible_trv uniquement si visible (BT ≤ 36 kVA)
        if (categorie === 'BT < 36 kVA' || categorie === 'BT ≤ 36 kVA') {
            requiredFields.push('eligible_trv');
        }

        requiredFields.forEach(field => {
            const $field = stepElement.find(`[name="${field}"]`);

            if ($field.attr('type') === 'radio') {
                if (!stepElement.find(`input[name="${field}"]:checked`).length) {
                    $field.addClass('field-error');
                    isValid = false;
                    console.log('❌ Champ radio manquant:', field);
                }
            } else {
                if (!$field.val()) {
                    $field.addClass('field-error');
                    isValid = false;
                    console.log('❌ Champ vide:', field);
                }
            }
        });

        // Validation spéciale pour la consommation
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

        // Vérifier la sélection du tarif
        if (!stepElement.find('input[name="tarif_choisi"]:checked').length) {
            showValidationMessage('Veuillez sélectionner un tarif');
            isValid = false;
        }

        // Vérifier le type de contrat
        if (!stepElement.find('input[name="type_contrat"]:checked').length) {
            showValidationMessage('Veuillez sélectionner un type de contrat');
            isValid = false;
        }

        return isValid;
    }

    function validateStep4(stepElement) {
        let isValid = true;

        // Champs obligatoires (sans accept_prelevement_pro qui est optionnel)
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
                console.log('❌ Champ entreprise manquant:', field);
            }
        });

        // Validation SIRET
        const siret = stepElement.find('#siret').val();
        if (siret && siret.length !== 14) {
            stepElement.find('#siret').addClass('field-error');
            showValidationMessage('Le SIRET doit contenir exactement 14 chiffres');
            isValid = false;
        }

        // Validation email
        const email = stepElement.find('#responsable_email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            stepElement.find('#responsable_email').addClass('field-error');
            showValidationMessage('Format d\'email invalide');
            isValid = false;
        }

        // Vérifier SEULEMENT les checkboxes vraiment obligatoires
        const requiredCheckboxes = [
            'accept_conditions_pro',  // Conditions générales : obligatoire
            'certifie_pouvoir'        // Pouvoir d'engagement : obligatoire
        ];
        // accept_prelevement_pro est OPTIONNEL

        requiredCheckboxes.forEach(checkbox => {
            if (!stepElement.find(`#${checkbox}`).is(':checked')) {
                stepElement.find(`#${checkbox}`).addClass('field-error');
                showValidationMessage('Veuillez accepter les conditions obligatoires');
                isValid = false;
            }
        });

        // Vérifier les fichiers obligatoires
        if (!uploadedFiles.kbis_file) {
            showValidationMessage('Le K-bis est obligatoire');
            isValid = false;
        }

        if (!uploadedFiles.rib_entreprise) {
            showValidationMessage('Le RIB de l\'entreprise est obligatoire');
            isValid = false;
        }

        // Note: mandat_signature est optionnel

        return isValid;
    }

    function setupFormValidation() {
        // Validation email
        $('#responsable_email').off('blur').on('blur', function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                $(this).addClass('field-error');
                showValidationMessage('Format d\'email invalide');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation téléphone
        $('#responsable_telephone').off('blur').on('blur', function () {
            const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
            if (tel && tel.length < 10) {
                $(this).addClass('field-error');
                showValidationMessage('Numéro de téléphone trop court');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation code postal
        $('#entreprise_code_postal').off('blur').on('blur', function () {
            const cp = $(this).val();
            if (cp && !/^[0-9]{5}$/.test(cp)) {
                $(this).addClass('field-error');
                showValidationMessage('Le code postal doit contenir 5 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation SIRET
        $('#siret').off('input').on('input', function () {
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
        });
    }

    // ================================
    // CALCUL DES RÉSULTATS
    // ================================
    function calculateResults() {
        console.log('🧮 Démarrage calcul des résultats');

        const allData = collectAllFormData();

        // Validation des données essentielles
        if (!allData.conso_annuelle || !allData.puissance || !allData.categorie) {
            showValidationMessage('Données manquantes pour le calcul');
            return;
        }

        // Afficher l'étape des résultats
        currentStep = 2;
        updateUI();

        // Afficher le loader
        $('#results-container-pro').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
                <small>Analyse de votre contrat électrique professionnel...</small>
            </div>
        `);

        // Envoyer au calculateur
        sendToCalculator(allData);
    }

    function sendToCalculator(userData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-professionnel',
            user_data: userData,
            config_data: configData
        };

        // Ajouter le nonce si disponible
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        }

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        console.log('📤 Envoi AJAX électricité pro:', dataToSend);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: dataToSend,
            timeout: 30000,
            success: function (response) {
                console.log('📥 Réponse du calculateur:', response);

                if (response.success && response.data) {
                    console.log('✅ Calcul réussi');
                    calculResults = response.data;
                    displayResults(response.data);
                } else {
                    console.error('❌ ERREUR CALCULATEUR:', response.data || 'Erreur inconnue');
                    displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', xhr.responseText);
                displayError('Erreur de connexion lors du calcul');
            }
        });
    }

    // ================================
    // AFFICHAGE DES RÉSULTATS (avec HTVA)
    // ================================
    function displayResults(results) {
        if (!results || !results.offres || !results.consommation_annuelle) {
            displayError('Données de résultats incomplètes');
            return;
        }

        console.log('📊 Affichage des résultats professionnels:', results);

        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const puissance = parseInt(results.puissance) || parseInt(formData.puissance) || 0;
        const meilleureOffre = results.meilleure_offre;
        const economieMax = parseFloat(results.economie_max) || 0;

        // Générer les cartes d'offres (les prix sont déjà en HTVA)
        const offresCards = results.offres.map(offre => {
            const isRecommended = offre.meilleure;
            const totalAnnuel = Math.round(parseFloat(offre.total_ttc)); // C'est déjà HTVA
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
        }).join('');

        const resultsHtml = `
            <div class="results-summary-pro">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">🏢</div>
                    <h3>Votre estimation professionnelle</h3>
                    <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                    <div class="result-price">${Math.round(meilleureOffre.total_ttc).toLocaleString()}€ <span>/an HTVA</span></div>
                    <p>Soit environ <strong>${Math.round(meilleureOffre.total_ttc / 10).toLocaleString()}€/mois HTVA</strong> (sur 10 mois)</p>
                    <p class="tva-note">💡 + TVA 20% (non incluse)</p>
                </div>
                
                <!-- Comparaison des tarifs -->
                <div class="tarifs-comparison">
                    <h3>💰 Comparaison des tarifs professionnels (HTVA)</h3>
                    <div class="tarifs-grid">
                        ${offresCards}
                    </div>
                    
                    ${economieMax > 0 ? `
                    <div class="economies">
                        <h4>💡 Économies potentielles</h4>
                        <p><strong>Jusqu'à ${Math.round(economieMax).toLocaleString()}€ HTVA/an</strong> en choisissant le tarif optimal !</p>
                        <small>Le tarif ${meilleureOffre.nom} est actuellement le plus avantageux pour votre profil.</small>
                    </div>
                    ` : ''}
                    
                </div>
            </div>
        `;

        $('#results-container-pro').html(resultsHtml);
        $('.results-summary-pro').hide().fadeIn(600);
    }

    function displayError(message) {
        $('#results-container-pro').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    <button class="btn btn-secondary" onclick="goToStep(1)">← Retour à la configuration</button>
                </div>
            </div>
        `);
    }

    // ================================
    // ÉTAPE 3 - SÉLECTION (SANS RECALCUL)
    // ================================
    function setupSelectionStep() {
        console.log('🎯 Initialisation étape sélection (sans recalcul)');

        if (!calculResults.offres) {
            console.warn('⚠️ Pas de résultats de calcul disponibles');
            return;
        }

        // Afficher les prix calculés à l'étape 2
        displayTarifCards();

        // Masquer la sélection de puissance à cette étape
        $('.field-group:has(.puissance-selection)').hide();

        // Événements de sélection simple
        $('input[name="tarif_choisi"]').off('change').on('change', function () {
            updateSelectionSummary();
        });

        $('input[name="type_contrat"]').off('change').on('change', function () {
            updateSelectionSummary();
        });

        // Sélectionner automatiquement la meilleure offre
        selectBestOffer();
    }

    // Afficher les cartes de tarifs avec les prix déjà calculés (HTVA)
    function displayTarifCards() {
        console.log('💰 Affichage des tarifs calculés');

        if (!calculResults.offres) return;

        // Réinitialiser l'affichage
        $('.tarif-card-selection').each(function () {
            $(this).hide();
        });

        // Afficher et remplir les tarifs disponibles
        calculResults.offres.forEach(offre => {
            const totalAnnuel = Math.round(parseFloat(offre.total_ttc)); // Déjà en HTVA
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
                // Autre offre
                $card = $('#tarif_hc_pro').closest('.tarif-card-selection');
                $('#prix-hc-pro .price-amount').text(totalAnnuel.toLocaleString());
                $('#prix-hc-pro .price-period').text('€/an HTVA');
                $card.find('.tarif-header h4').text(offre.nom);
                $card.show();
            }
        });
    }

    // Sélectionner automatiquement la meilleure offre
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

    // Mise à jour du résumé de sélection (avec HTVA)
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

        // Trouver l'offre correspondante
        let offreSelectionnee = null;
        if (calculResults.offres) {
            offreSelectionnee = calculResults.offres.find(offre => {
                const nomOffre = offre.nom.toLowerCase();
                if (tarifChoisi === 'base' && (nomOffre.includes('bleu') || nomOffre.includes('trv'))) return true;
                if (tarifChoisi === 'tempo' && nomOffre.includes('tempo')) return true;
                if (tarifChoisi === 'francaise' && (nomOffre.includes('française') || nomOffre.includes('100%'))) return true;
                if (tarifChoisi === 'hc') return true;
                return false;
            });
        }

        if (offreSelectionnee) {
            const totalAnnuel = Math.round(parseFloat(offreSelectionnee.total_ttc)); // Déjà HTVA
            const totalMensuel = Math.round(totalAnnuel / 10);
            const tva = Math.round(totalAnnuel * 0.2);
            const totalTTC = totalAnnuel + tva;
            const puissance = calculResults.puissance || formData.puissance;
            const consommation = calculResults.consommation_annuelle || formData.conso_annuelle;

            $('#calculs-selection-pro').html(`
                <div class="selection-summary">
                    <div class="summary-header">
                        <h4>📊 Récapitulatif de votre sélection</h4>
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
            `);
        }
    }

    // ================================
    // ÉTAPE 4 - CONTACT
    // ================================
    function setupContactStep() {
        console.log('📞 Initialisation étape contact');

        // Toggle pour l'adresse
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

        console.log('✅ Étape contact initialisée');
    }

    // ================================
    // ÉTAPE 5 - RÉCAPITULATIF
    // ================================
    function setupRecapStep() {
        console.log('📋 Génération du récapitulatif final');
        generateRecapitulatifFinalPro();
    }

    function generateRecapitulatifFinalPro() {
        const allData = collectAllFormData();
        const tarifChoisi = allData.tarif_choisi;
        const typeContrat = allData.type_contrat || 'principal';

        // Trouver l'offre sélectionnée
        let offreSelectionnee = null;
        if (calculResults.offres) {
            offreSelectionnee = calculResults.offres.find(offre => {
                const nomOffre = offre.nom.toLowerCase();
                if (tarifChoisi === 'base' && (nomOffre.includes('bleu') || nomOffre.includes('trv'))) return true;
                if (tarifChoisi === 'tempo' && nomOffre.includes('tempo')) return true;
                if (tarifChoisi === 'francaise' && (nomOffre.includes('française') || nomOffre.includes('100%'))) return true;
                if (tarifChoisi === 'hc') return true;
                return false;
            });
        }

        const totalAnnuel = offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc)) : 0; // Déjà HTVA
        const totalMensuel = offreSelectionnee ? Math.round(totalAnnuel / 10) : 0;
        const tva = Math.round(totalAnnuel * 0.2);
        const totalTTC = totalAnnuel + tva;
        const puissance = calculResults.puissance || formData.puissance || '--';
        const consommation = calculResults.consommation_annuelle || formData.conso_annuelle || 0;

        const recapHtml = `
            <div class="recap-complet">
                
                <!-- SECTION FORMULE SÉLECTIONNÉE -->
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
                
                <!-- SECTION ENTREPRISE -->
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
                                ${typeContrat === 'principal' ? '🏢 Contrat principal' : '🏪 Site secondaire'}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION CONFIGURATION ÉLECTRIQUE -->
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
                
                <!-- SECTION RESPONSABLE -->
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
                    
                    <!-- Adresse de l'entreprise -->
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
                    
                    <!-- PDL/PRM si disponibles -->
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
                                ${uploadedFiles.kbis_file ?
                `✅ K-bis<br>` :
                '❌ K-bis manquant<br>'}
                                ${uploadedFiles.rib_entreprise ?
                `✅ RIB entreprise<br>` :
                '❌ RIB entreprise manquant<br>'}
                                ${uploadedFiles.mandat_signature ?
                `✅ Mandat de signature` :
                'ℹ️ Mandat de signature (optionnel)'}
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
                                ${allData.accept_prelevement_pro ?
                '✅ Autorisé' :
                'ℹ️ Non autorisé (autre moyen de paiement)'}
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
                
            </div>
        `;

        $('#recap-container-final-pro').html(recapHtml);

        // Événements pour les boutons finaux
        $('#btn-finaliser-souscription-pro').off('click').on('click', function () {
            finalizeSouscriptionPro();
        });

    }

    // ================================
    // UPLOAD DE FICHIERS
    // ================================
    function setupFileUpload() {
        $('.form-step[data-step="4"] .upload-card').each(function () {
            const card = $(this);
            const trigger = card.find('.upload-trigger');
            const fileInput = card.find('input[type="file"]');
            const resultDiv = card.find('.upload-result');
            const fileType = card.data('file');

            if (trigger.length && fileInput.length) {
                // Click sur le bouton
                trigger.off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.trigger('click');
                });

                // Drag & Drop
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

                // Changement de fichier
                fileInput.on('change', function () {
                    if (this.files && this.files[0]) {
                        handleFileUpload(this.files[0], fileType, resultDiv);
                    }
                });
            }
        });
    }

    function handleFileUpload(file, fileType, resultDiv) {
        // Vérifier le type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showValidationMessage('Format non supporté. Utilisez PDF, JPG ou PNG.');
            return;
        }

        // Vérifier la taille (5 Mo max)
        if (file.size > 5 * 1024 * 1024) {
            showValidationMessage('Le fichier est trop volumineux (max 5 Mo)');
            return;
        }

        // Stocker le fichier
        uploadedFiles[fileType] = file;

        // Afficher le résultat
        resultDiv.html(`
            <div class="upload-success">
                <span class="success-icon">✅</span>
                <span class="file-name">${file.name}</span>
                <button type="button" class="remove-file" onclick="removeUploadedFile('${fileType}', this)">×</button>
            </div>
        `);

        console.log(`📎 Fichier ${fileType} uploadé:`, {
            name: file.name,
            size: (file.size / 1024).toFixed(2) + ' Ko',
            type: file.type
        });
    }

    // Fonction globale pour supprimer un fichier
    window.removeUploadedFile = function (fileType, button) {
        delete uploadedFiles[fileType];
        $(button).closest('.upload-result').empty();
        $(`input[name="${fileType}"]`).val('');
        console.log(`🗑️ Fichier ${fileType} supprimé`);
    };

    // ================================
    // GESTION DES DONNÉES
    // ================================
    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        const categorie = $('input[name="categorie"]:checked').val();

        currentStepElement.find('input, select').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name || type === 'file') return;

            // Pour eligible_trv, forcer "non" si BT > 36 kVA ou HTA
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

        console.log('💾 Données sauvegardées étape', currentStep, ':', formData);
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

        console.log('📊 Données complètes collectées:', formData);
        return formData;
    }

    // ================================
    // ACTIONS FINALES
    // ================================
    function finalizeSouscriptionPro() {
        console.log('🎯 Finalisation souscription professionnelle');

        const allData = collectAllFormData();

        // Validation finale complète
        if (!validateCurrentStep()) {
            showValidationMessage('Veuillez vérifier toutes les informations obligatoires');
            return;
        }

        // Vérifications spécifiques au professionnel
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

        // Envoyer les données directement
        envoyerDonneesProfessionnellesAuServeur();
    }

    // ================================
    // FONCTIONS UTILITAIRES
    // ================================
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
        // Nettoyer le numéro
        const cleaned = phone.replace(/\D/g, '');
        // Formater en XX XX XX XX XX
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
        }
        return phone;
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
        // Supprimer les notifications existantes
        $('.notification-toast').remove();

        const notification = $(`
            <div class="notification-toast ${type}">
                <span class="notification-icon">${type === 'success' ? '✓' : '⚠'}</span>
                <span class="notification-text">${message}</span>
            </div>
        `);

        $('body').append(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.addClass('show');
        }, 100);

        // Disparition automatique
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
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

        // Réinitialiser la logique pro
        setupProLogic();

        console.log('🔄 Simulation redémarrée');
    }

    // ================================
    // ENVOI DES DONNÉES AU SERVEUR
    // ================================
    function envoyerDonneesProfessionnellesAuServeur() {
        console.log('📤 Préparation envoi données professionnelles');

        // Récupérer toutes les données
        const allFormData = collectAllFormData();
        const results = calculResults;
        const uploads = uploadedFiles;

        // Vérifier qu'on a bien toutes les données
        if (!allFormData || !results) {
            console.error('❌ Données manquantes pour l\'envoi professionnel');
            showNotification('Données incomplètes', 'error');
            return;
        }

        // Trouver l'offre sélectionnée
        let offreSelectionnee = null;
        if (results.offres && allFormData.tarif_choisi) {
            offreSelectionnee = results.offres.find(offre => {
                const nomOffre = offre.nom.toLowerCase();
                const tarif = allFormData.tarif_choisi;

                if (tarif === 'base' && (nomOffre.includes('bleu') || nomOffre.includes('trv'))) return true;
                if (tarif === 'tempo' && nomOffre.includes('tempo')) return true;
                if (tarif === 'francaise' && (nomOffre.includes('française') || nomOffre.includes('100%'))) return true;
                if (tarif === 'hc') return true;
                return false;
            });
        }

        // CRÉER UN FORMDATA POUR TOUT (comme dans le résidentiel)
        const formData = new FormData();

        // Ajouter l'action et le nonce
        formData.append('action', 'process_electricity_form');
        formData.append('nonce', hticSimulateur.nonce);

        // Préparer l'objet complet avec TOUTES les données
        const dataToSend = {
            // Type de simulation
            simulationType: 'elec-professionnel',

            // Informations entreprise
            companyName: allFormData.raison_sociale || '',
            legalForm: allFormData.forme_juridique || '',
            siret: allFormData.siret || '',
            nafCode: allFormData.code_naf || '',

            // Adresse entreprise
            companyAddress: allFormData.entreprise_adresse || '',
            companyPostalCode: allFormData.entreprise_code_postal || '',
            companyCity: allFormData.entreprise_ville || '',
            companyComplement: allFormData.entreprise_complement || '',

            // Responsable/Contact
            firstName: allFormData.responsable_prenom || '',
            lastName: allFormData.responsable_nom || '',
            email: allFormData.responsable_email || '',
            phone: allFormData.responsable_telephone || '',
            function: allFormData.responsable_fonction || '',

            // Informations techniques
            category: allFormData.categorie || '',
            contractPower: allFormData.puissance || '',
            tarifFormula: allFormData.formule_tarifaire || '',
            eligibleTRV: allFormData.eligible_trv === 'oui',
            annualConsumption: parseInt(allFormData.conso_annuelle) || 0,

            // Sélections finales
            pricingType: allFormData.tarif_choisi || '',
            contractType: allFormData.type_contrat || 'principal',

            // Informations complémentaires
            pdlAddress: allFormData.pdl_entreprise || '',
            prmNumber: allFormData.prm_entreprise || '',

            // Acceptations
            acceptConditions: allFormData.accept_conditions_pro || false,
            acceptPrelevement: allFormData.accept_prelevement_pro || false,
            certifiePouvoir: allFormData.certifie_pouvoir || false,

            // Résultats des calculs
            monthlyEstimate: offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc) / 10) : 0,
            annualEstimate: offreSelectionnee ? Math.round(parseFloat(offreSelectionnee.total_ttc)) : 0,
            selectedOffer: offreSelectionnee ? {
                name: offreSelectionnee.nom,
                totalHTVA: Math.round(parseFloat(offreSelectionnee.total_ttc)),
                totalTTC: Math.round(parseFloat(offreSelectionnee.total_ttc) * 1.2),
                details: offreSelectionnee.details || ''
            } : null,

            // Toutes les offres calculées
            offers: results.offres || [],
            bestOffer: results.meilleure_offre || null,
            maxSaving: results.economie_max || 0,

            // Métadonnées
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        };

        // Ajouter les données JSON au FormData
        formData.append('form_data', JSON.stringify(dataToSend));

        // Ajouter les fichiers uploadés
        if (uploads.kbis_file) {
            formData.append('kbis_file', uploads.kbis_file);
        }
        if (uploads.rib_entreprise) {
            formData.append('rib_entreprise', uploads.rib_entreprise);
        }
        if (uploads.mandat_signature) {
            formData.append('mandat_signature', uploads.mandat_signature);
        }

        // Afficher le loader
        afficherLoaderProfessionnel();

        // Envoyer via AJAX
        $.ajax({
            url: hticSimulateur.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false, // OBLIGATOIRE pour FormData
            contentType: false, // OBLIGATOIRE pour FormData
            dataType: 'json',
            timeout: 60000, // 60 secondes pour les entreprises
            success: function (response) {
                cacherLoaderProfessionnel();

                if (response.success) {
                    afficherMessageSuccesProfessionnel(response.data?.referenceNumber || 'PRO-' + Date.now());
                } else {
                    afficherMessageErreurProfessionnel(response.data || 'Une erreur est survenue');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX professionnel:', error);
                cacherLoaderProfessionnel();
                afficherMessageErreurProfessionnel('Erreur de connexion au serveur');
            }
        });
    }

    // Fonctions UI spécifiques au professionnel
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

        // setTimeout(() => {
        //     window.location.href = '/merci';
        // }, 8000);

        setTimeout(() => {
            $('.success-message').fadeOut(500, function () {
                $(this).remove();
            });
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

        // Fermer au clic
        $('.close-btn-pro').on('click', function () {
            $('.error-message-pro').fadeOut(300, function () {
                $(this).remove();
            });
        });

        // Auto-fermeture après 8 secondes
        setTimeout(() => {
            $('.error-message-pro').fadeOut(500, function () {
                $(this).remove();
            });
        }, 8000);
    }

    // ================================
    // API PUBLIQUE
    // ================================
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

    console.log('🎯 API publique HticElecProfessionnelData disponible dans la console');
});