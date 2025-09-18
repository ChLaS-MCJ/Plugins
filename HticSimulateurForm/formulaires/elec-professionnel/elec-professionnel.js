// formulaires/elec-professionnel/elec-professionnel.js - Version complète avec calculs fonctionnels

jQuery(document).ready(function ($) {
    'use strict';

    // Variables globales
    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let configData = {};
    let uploadedFile = null;

    // Initialisation
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
    // CONFIGURATION AVEC NONCE
    // ================================
    function loadConfigData() {
        // Utiliser l'ID correct du template PHP
        const configElement = document.getElementById('simulateur-config-pro');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration chargée:', configData);

                // Vérifier si le nonce est présent dans la config
                if (configData.nonce || configData.calculate_nonce) {
                    console.log('✅ Nonce trouvé dans la configuration');
                }
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
            console.log('✅ Variables globales hticSimulateur créées:', window.hticSimulateur);
        }
    }

    // ================================
    // NAVIGATION ENTRE ÉTAPES
    // ================================
    function setupStepNavigation() {
        // IDs corrigés pour correspondre exactement au template PHP
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

        // Navigation par clic sur les étapes
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
        $('.step').removeClass('active');
        $(`.step[data-step="${currentStep}"]`).addClass('active');

        // Mise à jour des boutons - IDs corrigés selon template PHP
        $('#btn-previous-pro').toggle(currentStep > 1);

        if (currentStep === totalSteps) {
            // Étape 4 : Résultats
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').show();
        } else if (currentStep === totalSteps - 1) {
            // Étape 3 : Avant résultats
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').show();
            $('#btn-restart-pro').hide();
        } else {
            // Étapes 1 et 2
            $('#btn-next-pro').show();
            $('#btn-calculate-pro').hide();
            $('#btn-restart-pro').hide();
        }

        // Actions spécifiques par étape
        if (currentStep === 1) {
            updateEligibilityInfo();
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
            case 2:
                isValid = validateStep2(currentStepElement);
                break;
            case 3:
                isValid = validateStep3(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires');
        }

        console.log('✔️ Validation résultat:', isValid);
        return isValid;
    }

    function validateStep1(stepElement) {
        let isValid = true;

        // Vérifier les champs requis de l'étape 1
        const requiredFields = [
            'categorie',
            'eligible_trv',
            'puissance',
            'formule_tarifaire',
            'conso_annuelle'
        ];

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

    function validateStep2(stepElement) {
        let isValid = true;

        // Si "Je n'ai pas l'information" est coché, validation allégée
        if ($('#pas_info').is(':checked')) {
            // Vérifier seulement l'adresse principale
            const requiredFields = ['adresse', 'code_postal', 'ville'];

            requiredFields.forEach(field => {
                const $field = stepElement.find(`#${field}`);
                if (!$field.val()) {
                    $field.addClass('field-error');
                    isValid = false;
                    console.log('❌ Champ adresse manquant:', field);
                }
            });
        } else {
            // Vérification complète avec PDL/PRM
            const pdl = stepElement.find('#point_livraison').val();
            const prm = stepElement.find('#num_prm').val();

            if (!pdl && !prm) {
                stepElement.find('#point_livraison, #num_prm').addClass('field-error');
                showValidationMessage('Veuillez renseigner le point de livraison (PDL) ou le numéro PRM');
                isValid = false;
            }

            // Adresse obligatoire
            const requiredFields = ['adresse', 'code_postal', 'ville'];

            requiredFields.forEach(field => {
                const $field = stepElement.find(`#${field}`);
                if (!$field.val()) {
                    $field.addClass('field-error');
                    isValid = false;
                }
            });
        }

        return isValid;
    }

    function validateStep3(stepElement) {
        let isValid = true;

        const requiredFields = [
            'nom', 'prenom', 'raison_sociale',
            'forme_juridique', 'siret', 'code_naf',
            'email', 'telephone'
        ];

        requiredFields.forEach(field => {
            const $field = stepElement.find(`#${field}`);
            if (!$field.val()) {
                $field.addClass('field-error');
                isValid = false;
                console.log('❌ Champ entreprise manquant:', field);
            }
        });

        // Validation SIRET (14 chiffres)
        const siret = stepElement.find('#siret').val();
        if (siret && siret.length !== 14) {
            stepElement.find('#siret').addClass('field-error');
            showValidationMessage('Le SIRET doit contenir exactement 14 chiffres');
            isValid = false;
        }

        // Validation email
        const email = stepElement.find('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            stepElement.find('#email').addClass('field-error');
            showValidationMessage('Format d\'email invalide');
            isValid = false;
        }

        return isValid;
    }

    // ================================
    // LOGIQUE PROFESSIONNELLE
    // ================================
    function setupProLogic() {
        $('#pas_info').off('change').on('change', function () {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
                $('#num_prm').prop('disabled', true).val('');
                console.log('Mode "pas d\'info" activé');
            } else {
                $('#point_livraison').prop('disabled', false);
                $('#num_prm').prop('disabled', false);
                console.log('Mode "pas d\'info" désactivé');
            }
        });

        $('#siret').off('input').on('input', function () {
            let value = $(this).val().replace(/\s/g, '');
            if (value.length > 14) {
                value = value.substr(0, 14);
            }
            $(this).val(value);

            const $badge = $('#siret-badge');
            if (value.length === 14) {
                $badge.text('✓').removeClass('invalid').addClass('valid').show();
            } else if (value.length > 0) {
                $badge.text('✗').removeClass('valid').addClass('invalid').show();
            } else {
                $badge.hide();
            }
        });


        // Information sur la puissance en temps réel
        $('#puissance').on('change', function () {
            const puissance = parseInt($(this).val());
            const $helpText = $(this).closest('.form-group').find('.form-help');

            if (puissance <= 12) {
                $helpText.text('💡 Petite installation - Idéal pour bureaux et commerces');
            } else if (puissance <= 36) {
                $helpText.text('💡 Installation moyenne - Adaptée aux PME');
            } else {
                $helpText.text('💡 Installation importante - Pour les grandes entreprises');
            }
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
    }

    function setupFormValidation() {
        // Utiliser .off() pour éviter la multiplication des événements
        $('#email').off('blur').on('blur', function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                $(this).addClass('field-error');
                showValidationMessage('Format d\'email invalide');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        $('#telephone').off('blur').on('blur', function () {
            const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
            if (tel && tel.length < 10) {
                $(this).addClass('field-error');
                showValidationMessage('Numéro de téléphone trop court');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        $('#code_postal').off('blur').on('blur', function () {
            const cp = $(this).val();
            if (cp && !/^[0-9]{5}$/.test(cp)) {
                $(this).addClass('field-error');
                showValidationMessage('Le code postal doit contenir 5 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });
    }

    function setupProLogic() {
        $('#pas_info').off('change').on('change', function () {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
                $('#num_prm').prop('disabled', true).val('');
                console.log('Mode "pas d\'info" activé');
            } else {
                $('#point_livraison').prop('disabled', false);
                $('#num_prm').prop('disabled', false);
                console.log('Mode "pas d\'info" désactivé');
            }
        });

        $('#siret').off('input').on('input', function () {
            let value = $(this).val().replace(/\s/g, '');
            if (value.length > 14) {
                value = value.substr(0, 14);
            }
            $(this).val(value);

            const $badge = $('#siret-badge');
            if (value.length === 14) {
                $badge.text('✓').removeClass('invalid').addClass('valid').show();
            } else if (value.length > 0) {
                $badge.text('✗').removeClass('valid').addClass('invalid').show();
            } else {
                $badge.hide();
            }
        });

    }

    function updateEligibilityInfo() {
        // Information sur l'éligibilité au tarif réglementé
        const eligible = $('input[name="eligible_trv"]:checked').val();
        const $infoBox = $('.eligibility-info');

        if (eligible === 'oui') {
            $infoBox.html('✅ Vous pouvez bénéficier des tarifs réglementés').show();
        } else if (eligible === 'non') {
            $infoBox.html('⚠️ Vous devrez souscrire à une offre de marché').show();
        }
    }

    // ================================
    // UPLOAD DE FICHIER
    // ================================
    function setupFileUpload() {
        const $fileInput = $('#kbis');
        const $uploadArea = $('#upload-area');
        const $fileName = $('.file-name-text');
        const $fileSelected = $('.file-selected-name');
        const $fileRemove = $('.file-remove');

        // Click sur la zone d'upload
        $uploadArea.on('click', function (e) {
            if (!$(e.target).hasClass('file-remove')) {
                $fileInput.trigger('click');
            }
        });

        // Drag & Drop
        $uploadArea.on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $uploadArea.on('dragleave', function (e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });

        $uploadArea.on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        // Sélection de fichier
        $fileInput.on('change', function () {
            if (this.files && this.files[0]) {
                handleFileSelect(this.files[0]);
            }
        });

        // Supprimer le fichier
        $fileRemove.on('click', function (e) {
            e.stopPropagation();
            removeFile();
        });

        function handleFileSelect(file) {
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
            uploadedFile = file;
            formData.kbis_file = file;

            // Afficher le nom du fichier
            $fileName.text(file.name);
            $fileSelected.show();
            $uploadArea.addClass('has-file');
            $('.file-upload-text').hide();

            console.log('📎 Fichier K-bis uploadé:', {
                name: file.name,
                size: (file.size / 1024).toFixed(2) + ' Ko',
                type: file.type
            });
        }

        function removeFile() {
            uploadedFile = null;
            delete formData.kbis_file;
            $fileInput.val('');
            $fileName.text('');
            $fileSelected.hide();
            $uploadArea.removeClass('has-file');
            $('.file-upload-text').show();

            console.log('🗑️ Fichier supprimé');
        }
    }

    // ================================
    // GESTION DES DONNÉES
    // ================================
    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        currentStepElement.find('input, select').each(function () {
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

        // Ajouter le fichier uploadé si présent
        if (uploadedFile && currentStep === 3) {
            formData.kbis_filename = uploadedFile.name;
            formData.kbis_size = uploadedFile.size;
            formData.kbis_type = uploadedFile.type;
        }

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

        // Ajouter le fichier uploadé si présent
        if (uploadedFile) {
            formData.kbis_filename = uploadedFile.name;
            formData.kbis_size = uploadedFile.size;
            formData.kbis_type = uploadedFile.type;
        }

        console.log('📊 Données complètes collectées:', formData);
        return formData;
    }

    // ================================
    // CALCUL DES RÉSULTATS - VERSION FONCTIONNELLE
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
        currentStep = totalSteps;
        updateUI();

        // Afficher le loader
        $('#results-container-pro').html(`
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Calcul en cours...</p>
            <small>Analyse de votre contrat électrique professionnel...</small>
        </div>
    `);

        // Envoyer directement au calculateur comme elec-residentiel
        sendToCalculator(allData);
    }

    function sendToCalculator(userData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-professionnel',
            user_data: userData,
            config_data: configData
        };

        // Ajouter le nonce si disponible (comme elec-residentiel)
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.calculateNonce) {
            dataToSend.nonce = hticSimulateurUnifix.calculateNonce;
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
                if (response.success) {
                    displayResults(response.data);
                } else {
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
    // AFFICHAGE RÉSULTATS
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
        const userData = results.user_data || formData;
        const economieMax = parseFloat(results.economie_max) || 0;

        // Générer les cartes d'offres selon le style de vos autres formulaires
        const offresCards = results.offres.map(offre => {
            const isRecommended = offre.meilleure;
            const totalTTC = Math.round(parseFloat(offre.total_ttc));
            const totalMensuel = Math.round(totalTTC / 12);

            let typeClass = 'offre-marche';
            if (offre.nom.includes('TRV') || offre.nom.includes('Bleu')) typeClass = 'trv';
            if (offre.nom.includes('Tempo')) typeClass = 'tempo';
            if (offre.nom.includes('française') || offre.nom.includes('verte')) typeClass = 'offre-francaise';

            return `
            <div class="tarif-card ${typeClass} ${isRecommended ? 'recommended' : ''}">
                <h4>${offre.nom}</h4>
                <div class="tarif-prix">${totalTTC.toLocaleString()}€<span>/an</span></div>
                <div class="tarif-mensuel">${totalMensuel.toLocaleString()}€/mois</div>
                <div class="tarif-details">
                    <div>Abonnement : ${Math.round(offre.abonnement_annuel).toLocaleString()}€/an</div>
                    <div>Consommation : ${Math.round(offre.cout_consommation).toLocaleString()}€/an</div>
                    <div>Prix : ${offre.details}</div>
                </div>
                ${isRecommended ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                ${offre.details_tempo ? generateTempoDetails(offre.details_tempo) : ''}
            </div>
        `;
        }).join('');

        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">🏢</div>
                <h3>Votre estimation professionnelle</h3>
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <div class="result-price">${Math.round(meilleureOffre.total_ttc).toLocaleString()}€ <span>/an TTC</span></div>
                <p>Soit environ <strong>${Math.round(meilleureOffre.total_ttc / 12).toLocaleString()}€/mois TTC</strong></p>
            </div>
            
            <!-- Comparaison des tarifs (même style que elec-residentiel) -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des tarifs professionnels</h3>
                <div class="tarifs-grid">
                    ${offresCards}
                </div>
                
                ${economieMax > 0 ? `
                <div class="economies">
                    <h4>💡 Économies potentielles</h4>
                    <p><strong>Jusqu'à ${Math.round(economieMax).toLocaleString()}€/an</strong> en choisissant le tarif optimal !</p>
                    <small>Le tarif ${meilleureOffre.nom} est actuellement le plus avantageux pour votre profil.</small>
                </div>
                ` : ''}
                
                ${meilleureOffre.details_tempo ? generateTempoDetailsComplete(meilleureOffre.details_tempo) : ''}
            </div>
            
            <!-- Répartition de la consommation (même style que elec-residentiel) -->
            <div class="repartition-conso">
                <div class="repartition-header">
                    <h3>Répartition de votre consommation</h3>
                    <p class="repartition-subtitle">Analyse détaillée par poste de consommation</p>
                </div>
                
                <div class="repartition-content">
                    <!-- Éclairage -->
                    <div class="repartition-item eclairage">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">💡</div>
                                <div class="item-details">
                                    <div class="item-name">Éclairage</div>
                                    <div class="item-value">${Math.round(consommationAnnuelle * 0.25).toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">25%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 25%"></div>
                        </div>
                    </div>
                    
                    <!-- Informatique -->
                    <div class="repartition-item multimedia">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">💻</div>
                                <div class="item-details">
                                    <div class="item-name">Informatique</div>
                                    <div class="item-value">${Math.round(consommationAnnuelle * 0.30).toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">30%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 30%"></div>
                        </div>
                    </div>
                    
                    <!-- Chauffage/Climatisation -->
                    <div class="repartition-item chauffage">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">🌡️</div>
                                <div class="item-details">
                                    <div class="item-name">Chauffage/Climatisation</div>
                                    <div class="item-value">${Math.round(consommationAnnuelle * 0.35).toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">35%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 35%"></div>
                        </div>
                    </div>
                    
                    <!-- Autres équipements -->
                    <div class="repartition-item equipements">
                        <div class="item-header">
                            <div class="item-info">
                                <div class="item-icon">⚙️</div>
                                <div class="item-details">
                                    <div class="item-name">Autres équipements</div>
                                    <div class="item-value">${Math.round(consommationAnnuelle * 0.10).toLocaleString()} kWh/an</div>
                                </div>
                            </div>
                            <div class="item-stats">
                                <div class="item-percentage">10%</div>
                                <div class="item-kwh">du total</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 10%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Récapitulatif complet (même style que elec-residentiel) -->
            <div class="recap-section">
                <div class="recap-header">
                    <h3>Récapitulatif complet de votre simulation</h3>
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
                                    <span class="recap-label">Raison sociale</span>
                                    <span class="recap-value">${userData.raison_sociale || '--'}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Forme juridique</span>
                                    <span class="recap-value">${formData.forme_juridique || '--'}</span>
                                </div>
                                ${userData.siret ? `
                                <div class="recap-item">
                                    <span class="recap-label">SIRET</span>
                                    <span class="recap-value">${formatSiret(userData.siret)}</span>
                                </div>` : ''}
                            </div>
                        </div>
                        
                        <!-- Configuration électrique -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">⚡</div>
                                <div class="category-title">Configuration électrique</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">Puissance souscrite</span>
                                    <span class="recap-value highlight">${puissance} kVA</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Catégorie</span>
                                    <span class="recap-value">${results.categorie || formData.categorie}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Formule tarifaire</span>
                                    <span class="recap-value">${results.formule || formData.formule_tarifaire}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Éligibilité TRV</span>
                                    <span class="recap-value ${formData.eligible_trv === 'oui' ? 'success' : 'warning'}">${formData.eligible_trv === 'oui' ? '✅ Oui' : '❌ Non'}</span>
                                </div>
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
                                    <span class="recap-value">${userData.prenom} ${userData.nom}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Email</span>
                                    <span class="recap-value">${userData.email || '--'}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Téléphone</span>
                                    <span class="recap-value">${userData.telephone || '--'}</span>
                                </div>
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
                                    <span class="recap-label">Adresse</span>
                                    <span class="recap-value">${userData.adresse || '--'}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Code postal</span>
                                    <span class="recap-value">${userData.code_postal || '--'}</span>
                                </div>
                                <div class="recap-item">
                                    <span class="recap-label">Ville</span>
                                    <span class="recap-value">${userData.ville || '--'}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${userData.kbis_filename ? `
                        <!-- Document -->
                        <div class="recap-category">
                            <div class="category-header">
                                <div class="category-icon">📄</div>
                                <div class="category-title">Document</div>
                            </div>
                            <div class="category-items">
                                <div class="recap-item">
                                    <span class="recap-label">K-bis</span>
                                    <span class="recap-value success">✅ ${userData.kbis_filename}</span>
                                </div>
                            </div>
                        </div>` : ''}
                        
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="results-actions">
                <button class="btn btn-success" id="btn-send-email">✉️ Recevoir par email</button>
                <button class="btn btn-secondary" onclick="location.reload()">🔄 Nouvelle simulation</button>
            </div>
        </div>
    `;

        $('#results-container-pro').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        // Préparer les données pour l'email
        prepareEmailData(results);
    }

    // Fonction pour les détails Tempo (version compacte)
    function generateTempoDetails(tempoData) {
        if (!tempoData) return '';

        return `
        <div class="tempo-details-compact">
            <div class="tempo-breakdown">
                <div class="tempo-jour bleu">Bleu: ${Math.round(tempoData.cout_bleu).toLocaleString()}€</div>
                <div class="tempo-jour blanc">Blanc: ${Math.round(tempoData.cout_blanc).toLocaleString()}€</div>
                <div class="tempo-jour rouge">Rouge: ${Math.round(tempoData.cout_rouge).toLocaleString()}€</div>
            </div>
        </div>
    `;
    }

    // Fonction pour les détails Tempo complets (si c'est la meilleure offre)
    function generateTempoDetailsComplete(tempoData) {
        if (!tempoData) return '';

        return `
        <div class="tempo-details">
            <div class="tempo-header">
                <div class="tempo-icon">📅</div>
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
                        <span class="period-days">${tempoData.jours_bleu} jours</span>
                    </div>
                    <div class="period-cost">${Math.round(tempoData.cout_bleu).toLocaleString()}€</div>
                    <div class="period-details">
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Pleines:</span>
                            <span class="detail-value">${tempoData.prix_bleu_hp}€/kWh</span>
                        </div>
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Creuses:</span>
                            <span class="detail-value">${tempoData.prix_bleu_hc}€/kWh</span>
                        </div>
                    </div>
                </div>
                
                <!-- Jours Blancs -->
                <div class="period-card period-blanc">
                    <div class="period-header">
                        <span class="period-name">Jours Blancs</span>
                        <span class="period-days">${tempoData.jours_blanc} jours</span>
                    </div>
                    <div class="period-cost">${Math.round(tempoData.cout_blanc).toLocaleString()}€</div>
                    <div class="period-details">
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Pleines:</span>
                            <span class="detail-value">${tempoData.prix_blanc_hp}€/kWh</span>
                        </div>
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Creuses:</span>
                            <span class="detail-value">${tempoData.prix_blanc_hc}€/kWh</span>
                        </div>
                    </div>
                </div>
                
                <!-- Jours Rouges -->
                <div class="period-card period-rouge">
                    <div class="period-header">
                        <span class="period-name">Jours Rouges</span>
                        <span class="period-days">${tempoData.jours_rouge} jours</span>
                    </div>
                    <div class="period-cost">${Math.round(tempoData.cout_rouge).toLocaleString()}€</div>
                    <div class="period-details">
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Pleines:</span>
                            <span class="detail-value" style="color: #c62828;">${tempoData.prix_rouge_hp}€/kWh</span>
                        </div>
                        <div class="period-detail-row">
                            <span class="detail-label">Heures Creuses:</span>
                            <span class="detail-value">${tempoData.prix_rouge_hc}€/kWh</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tempo-footer">
                <div class="tempo-info">
                    <strong>💡 Conseil :</strong> Le tarif Tempo est avantageux si vous pouvez réduire votre consommation les ${tempoData.jours_rouge} jours rouges.
                </div>
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
                    <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    <button class="btn btn-secondary" id="btn-back-to-form-elec">← Retour au formulaire</button>
                </div>
            </div>
        `);

        $('#btn-back-to-form-elec').on('click', function () {
            goToStep(3);
        });
    }

    // ================================
    // UTILITAIRES
    // ================================
    function formatSiret(siret) {
        if (!siret || siret.length !== 14) return siret;
        return siret.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4');
    }

    function showValidationMessage(message) {
        $('.validation-message').remove();

        const $message = $(`<div class="validation-message error">${message}</div>`);
        $('.form-step.active .step-header').after($message);

        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 4000);
    }

    function prepareEmailData(results) {
        const emailData = {
            entreprise: {
                raison_sociale: formData.raison_sociale,
                siret: formData.siret,
                forme_juridique: formData.forme_juridique,
                code_naf: formData.code_naf
            },
            contact: {
                nom: formData.nom,
                prenom: formData.prenom,
                email: formData.email,
                telephone: formData.telephone
            },
            results: results,
            document_kbis: formData.kbis_filename || null,
            date_simulation: new Date().toISOString()
        };

        window.emailData = emailData;
        console.log('📧 Données email préparées:', emailData);
    }

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        uploadedFile = null;
        $('#simulateur-elec-professionnel')[0].reset();
        updateUI();
        $('.field-error, .field-success').removeClass('field-error field-success');
        $('.file-selected-name').hide();
        $('.file-upload-area').removeClass('has-file');
        $('.file-upload-text').show();
        $('#siret-badge').hide();

        console.log('🔄 Simulation redémarrée');
    }

    // ================================
    // FONCTIONS PUBLIQUES
    // ================================
    window.sendResultsByEmail = function () {
        if (!window.emailData) {
            alert('Aucune donnée à envoyer');
            return;
        }

        console.log('📮 Envoi email électricité pro avec les données:', window.emailData);
        alert('Fonctionnalité d\'envoi d\'email en cours de développement');
    };

    // API publique pour debugging
    window.HticElecProfessionnelData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfig: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep,
        validateStep: validateCurrentStep,
        restart: restartSimulation,
    };

    console.log('🎯 API publique HticElecProfessionnelData disponible dans la console');
});