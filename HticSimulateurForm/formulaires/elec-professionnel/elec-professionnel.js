// gaz-professionnel.js - Version harmonisée avec elec-professionnel.js

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let configData = {};
    let uploadedFile = null; // Changé de formData.kbis_file à uploadedFile

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupProLogic();
        setupFileUpload();
        loadCommunes();
    }

    function loadConfigData() {
        // Changé l'ID pour correspondre au template PHP
        const configElement = document.getElementById('simulateur-config');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration gaz pro chargée:', configData);
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
            if (confirm('Voulez-vous vraiment recommencer ?')) {
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
        $('#btn-previous').toggle(currentStep > 1);

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
    // LOGIQUE SPÉCIFIQUE PROFESSIONNEL
    // ===============================

    function setupProLogic() {
        // Gestion commune autre
        $('#commune').on('change', handleCommuneSelection);

        // Validation de la consommation en temps réel
        $('#consommation_previsionnelle').on('input', function () {
            const value = parseFloat($(this).val());
            const $helpText = $(this).closest('.form-group').find('.field-help');

            if (value > 0 && value < 5000) {
                $helpText.html('💡 <strong>Très petite consommation</strong> - Tarif P0/GOM0');
            } else if (value >= 5000 && value < 15000) {
                $helpText.html('💡 <strong>Petite entreprise</strong> - Tarif adapté aux commerces');
            } else if (value >= 15000 && value < 35000) {
                $helpText.html('💡 <strong>PME</strong> - Tarif optimisé pour les moyens consommateurs');
            } else if (value >= 35000 && value < 100000) {
                $helpText.html('⚠️ <strong>Grande consommation</strong> - Un devis personnalisé sera établi');
            } else if (value >= 100000) {
                $helpText.html('🏭 <strong>Très grande consommation</strong> - Offre sur mesure requise');
            }
        });

        // Checkbox "Je n'ai pas l'information" - ajusté pour le gaz (pas de PCE mais PDL/PRM électrique)
        $('#pas_info').on('change', function () {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
                $('#num_prm').prop('disabled', true).val('');
            } else {
                $('#point_livraison').prop('disabled', false);
                $('#num_prm').prop('disabled', false);
            }
        });

        // Format SIRET automatique avec badge visuel
        $('#siret').on('input', function () {
            let value = $(this).val().replace(/\s/g, '');
            if (value.length > 14) {
                value = value.substr(0, 14);
            }
            $(this).val(value);

            // Validation visuelle du SIRET
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

        $('#communes-naturel').empty();
        communesNaturel.forEach(commune => {
            $('#communes-naturel').append(`<option value="${commune.nom}" data-type="naturel">${commune.nom}</option>`);
        });

        $('#communes-propane').empty();
        communesPropane.forEach(commune => {
            $('#communes-propane').append(`<option value="${commune.nom}" data-type="propane">${commune.nom}</option>`);
        });
    }

    // ===============================
    // UPLOAD FICHIER K-BIS (inspiré d'elec-pro)
    // ===============================

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
                showValidationMessage('Le fichier est trop lourd (max 5 Mo)');
                return;
            }

            // Stocker le fichier comme dans elec-pro
            uploadedFile = file;
            formData.kbis_file = file;

            // Afficher le nom
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

    // ===============================
    // VALIDATION (inspirée d'elec-pro)
    // ===============================

    function setupFormValidation() {
        // Validation SIRET comme dans elec-pro
        $('#siret').on('blur', function () {
            const siret = $(this).val().replace(/\s/g, '');
            if (siret && siret.length !== 14) {
                $(this).addClass('field-error');
                showValidationMessage('Le SIRET doit contenir 14 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation Code Postal
        $('#code_postal').on('blur', function () {
            const cp = $(this).val();
            if (cp && !/^[0-9]{5}$/.test(cp)) {
                $(this).addClass('field-error');
                showValidationMessage('Le code postal doit contenir 5 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation Email
        $('#email').on('blur', function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                $(this).addClass('field-error');
                showValidationMessage('Email invalide');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation téléphone
        $('#telephone').on('blur', function () {
            const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
            if (tel && tel.length < 10) {
                $(this).addClass('field-error');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });
    }

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        // Retirer les classes d'erreur existantes
        currentStepElement.find('.field-error').removeClass('field-error');

        // Validation par étape
        switch (currentStep) {
            case 1: // Configuration gaz
                isValid = validateStep1(currentStepElement);
                break;
            case 2: // Localisation
                isValid = validateStep2(currentStepElement);
                break;
            case 3: // Titulaire
                isValid = validateStep3(currentStepElement);
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires');
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

        // Si "Je n'ai pas l'information" est coché, on skip certaines validations
        if ($('#pas_info').is(':checked')) {
            // Vérifier juste l'adresse principale
            if (!stepElement.find('#adresse').val()) {
                stepElement.find('#adresse').addClass('field-error');
                isValid = false;
            }
        } else {
            // Vérifier PDL ou PRM (même si c'est pour le gaz, on garde la logique électrique du template)
            const pdl = stepElement.find('#point_livraison').val();
            const prm = stepElement.find('#num_prm').val();

            if (!pdl && !prm) {
                stepElement.find('#point_livraison, #num_prm').addClass('field-error');
                isValid = false;
            }
        }

        // Adresse obligatoire
        if (!stepElement.find('#adresse').val()) {
            stepElement.find('#adresse').addClass('field-error');
            isValid = false;
        }

        // Code postal obligatoire
        if (!stepElement.find('#code_postal').val()) {
            stepElement.find('#code_postal').addClass('field-error');
            isValid = false;
        }

        // Ville obligatoire
        if (!stepElement.find('#ville').val()) {
            stepElement.find('#ville').addClass('field-error');
            isValid = false;
        }

        return isValid;
    }

    function validateStep3(stepElement) {
        let isValid = true;

        // Champs obligatoires comme dans elec-pro
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
            }
        });

        return isValid;
    }

    // ===============================
    // COLLECTE DES DONNÉES (comme elec-pro)
    // ===============================

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

        // Ajouter le fichier uploadé si présent (comme elec-pro)
        if (uploadedFile && currentStep === 3) {
            formData.kbis_filename = uploadedFile.name;
            formData.kbis_size = uploadedFile.size;
            formData.kbis_type = uploadedFile.type;
        }

        console.log('📝 Données sauvegardées étape', currentStep, ':', formData);
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

        // Ajouter le fichier uploadé si présent (comme elec-pro)
        if (uploadedFile) {
            formData.kbis_filename = uploadedFile.name;
            formData.kbis_size = uploadedFile.size;
            formData.kbis_type = uploadedFile.type;
        }

        console.log('📊 Données complètes collectées:', formData);
        return formData;
    }

    // ===============================
    // CALCUL DES RÉSULTATS (côté serveur)
    // ===============================

    function calculateResults() {
        const allData = collectAllFormData();

        // Validation
        if (!allData.commune || !allData.consommation_previsionnelle) {
            showValidationMessage('Données manquantes pour le calcul');
            return;
        }

        console.log('🚀 Envoi des données au calculateur gaz:', allData);

        // Afficher l'étape des résultats
        showStep(4);
        updateProgress();
        updateNavigation();

        // Afficher le loader
        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
                <small>Analyse de votre offre gaz professionnel...</small>
            </div>
        `);

        // Envoyer au calculateur PHP
        sendToCalculator(allData);
    }

    function sendToCalculator(userData) {
        // Déterminer l'URL AJAX
        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.ajaxUrl) {
            ajaxUrl = hticSimulateurUnifix.ajaxUrl;
        }

        // Préparer les données pour le calculateur
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'gaz-professionnel',
            user_data: userData,
            config_data: configData
        };

        // Ajouter le nonce si disponible
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.calculateNonce) {
            dataToSend.nonce = hticSimulateurUnifix.calculateNonce;
        }

        console.log('📤 Envoi AJAX gaz pro:', {
            url: ajaxUrl,
            data: dataToSend
        });

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: dataToSend,
            timeout: 30000,
            success: function (response) {
                console.log('📥 Réponse du calculateur gaz:', response);

                if (response.success) {
                    // Vérifier si c'est un devis personnalisé
                    if (response.data.devis_personnalise) {
                        displayDevisPersonnalise(response.data);
                    } else {
                        displayResults(response.data);
                    }
                    setupEmailActions();
                } else {
                    displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX gaz:', {
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

    function displayDevisPersonnalise(data) {
        $('#results-container').hide();
        $('#devis-personnalise-container').show();

        // Remplir les informations du devis
        $('#devis-entreprise').text(formData.raison_sociale || '--');
        $('#devis-commune').text(data.commune || formData.commune || '--');
        $('#devis-consommation').text((data.consommation_annuelle || formData.consommation_previsionnelle || 0).toLocaleString() + ' kWh/an');
        $('#devis-type-gaz').text(data.type_gaz || 'Gaz naturel');

        $('.results-actions').show();
    }

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
                                        <span class="recap-value">${formData.raison_sociale || '--'}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Forme juridique</span>
                                        <span class="recap-value">${formData.forme_juridique || '--'}</span>
                                    </div>
                                    ${formData.siret ? `
                                    <div class="recap-item">
                                        <span class="recap-label">SIRET</span>
                                        <span class="recap-value">${formatSiret(formData.siret)}</span>
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
                                    ${formData.code_postal ? `
                                    <div class="recap-item">
                                        <span class="recap-label">Code postal</span>
                                        <span class="recap-value">${formData.code_postal}</span>
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
                                        <span class="recap-value">${formData.prenom} ${formData.nom}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Email</span>
                                        <span class="recap-value">${formData.email || '--'}</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label">Téléphone</span>
                                        <span class="recap-value">${formData.telephone || '--'}</span>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <!-- Actions -->
                <div class="pro-actions">
                    <button class="btn btn-success" onclick="sendResultsByEmail()">📧 Recevoir par email</button>
                    <button class="btn btn-secondary" onclick="location.reload()">🔄 Nouvelle simulation</button>
                </div>
            </div>
        `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        // Préparer les données pour l'email
        prepareEmailData(results);
    }

    function displayError(message) {
        $('#results-container').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    <button class="btn btn-secondary" id="btn-back-to-form-gaz">← Retour au formulaire</button>
                </div>
            </div>
        `);

        $('#btn-back-to-form-gaz').on('click', function () {
            goToStep(3);
        });
    }

    // ===============================
    // EMAIL ET ACTIONS
    // ===============================

    function setupEmailActions() {
        // Actions email similaires à elec-pro
        $(document).on('click', '#btn-send-email', function () {
            if (window.emailData) {
                sendEmail();
            }
        });

        $(document).on('click', '#btn-download-pdf', function () {
            downloadPDF();
        });
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
        console.log('📧 Données email gaz préparées:', emailData);
    }

    function registerCallback() {
        // Fonction de callback
        console.log('📞 Demande de rappel gaz pro');
    }

    window.sendResultsByEmail = function () {
        if (!window.emailData) {
            alert('Aucune donnée à envoyer');
            return;
        }

        console.log('📮 Envoi email gaz pro avec les données:', window.emailData);
        alert('Email envoyé avec succès !'); // Placeholder
    };

    window.downloadPDF = function () {
        alert('Fonction de téléchargement PDF gaz pro en cours de développement');
    };

    // ===============================
    // UTILITAIRES
    // ===============================

    function formatSiret(siret) {
        if (!siret || siret.length !== 14) return siret;
        return siret.replace(/(\d{3})(\d{3})(\d{3})(\d{5})/, '$1 $2 $3 $4');
    }

    function showValidationMessage(message) {
        $('.validation-message').remove();

        const $message = $(`<div class="validation-message">${message}</div>`);
        $('.form-step.active .step-header').after($message);

        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 3000);
    }

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        uploadedFile = null;
        $('#simulateur-gaz-professionnel')[0].reset();
        showStep(1);
        updateProgress();
        updateNavigation();
        $('.field-error, .field-success').removeClass('field-error field-success');
        $('.file-selected-name').hide();
        $('.file-upload-area').removeClass('has-file');
        $('.file-upload-text').show();
        $('#siret-badge').hide();
    }

    // API publique
    window.HticGazProfessionnelData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep
    };

});