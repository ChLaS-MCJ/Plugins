// gaz-residentiel.js - JavaScript restructuré pour 7 étapes avec récapitulatif à l'étape 7

jQuery(document).ready(function ($) {

    // ========================================
    // VARIABLES GLOBALES
    // ========================================
    let currentStep = 1;
    const totalSteps = 7;
    let formData = {};
    let configData = {};
    let calculationResults = null;
    let uploadedFiles = {};

    // ========================================
    // INITIALISATION
    // ========================================
    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupGazLogic();
        loadCommunes();
        updateConsumptionEstimates();
        setupFileUploadHandlers();
        setupToggleButton();
    }

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('Configuration chargée');
            } catch (e) {
                console.error('Erreur configuration:', e);
                configData = {};
            }
        }
    }

    // ========================================
    // NAVIGATION ENTRE LES ÉTAPES
    // ========================================

    function setupStepNavigation() {
        // Bouton Suivant
        $('#btn-next').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();

                // À l'étape 6, préparer les données du récapitulatif avant de passer à 7
                if (currentStep === 6) {
                    prepareRecapData();
                }

                goToNextStep();
            }
        });

        // Bouton Précédent
        $('#btn-previous').on('click', function () {
            saveCurrentStepData();
            goToPreviousStep();
        });

        // Bouton Calculer (étape 4 → 5)
        $('#btn-calculate').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        });

        // Bouton Finaliser la souscription (étape 7)
        $(document).on('click', '#btn-finalize-subscription', function () {
            finalizeSubscription();
        });

        // Bouton Recommencer
        $('#btn-restart').on('click', function () {
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

        // Gestion spécifique par étape
        handleStepChange(stepNumber);
    }

    function updateProgress() {
        const progressPercent = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');
    }

    function updateNavigation() {
        // Masquer tous les boutons par défaut
        $('#btn-next, #btn-calculate, #btn-send-simulation, #btn-restart').hide();

        // Bouton Précédent (visible sauf étape 1)
        $('#btn-previous').toggle(currentStep > 1);

        // Navigation selon l'étape
        switch (currentStep) {
            case 1:
            case 2:
            case 3:
                $('#btn-next').text('Suivant →').show();
                break;

            case 4:
                // Étape 4 : bouton Calculer
                $('#btn-calculate').show();
                break;

            case 5:
                // Étape 5 (résultats) : bouton "Je Souscris"
                $('#btn-next').text('Je Souscris →').show();
                break;

            case 6:
                // Étape 6 (contact) : bouton "Suivant" vers récapitulatif
                $('#btn-next').text('Voir le récapitulatif →').show();
                break;

            case 7:
                // Étape 7 (récapitulatif) : pas de bouton suivant
                // Le bouton "Finaliser ma souscription" est dans le contenu
                break;
        }
    }

    function handleStepChange(stepNumber) {
        switch (stepNumber) {
            case 5:
                // Résultats déjà chargés par calculateResults()
                break;

            case 6:
                // Initialiser les uploads
                setTimeout(() => {
                    initFileUploads();
                }, 100);
                break;

            case 7:
                // Afficher le récapitulatif complet
                displayFullRecap();
                break;
        }
    }

    // ========================================
    // LOGIQUE SPÉCIFIQUE GAZ
    // ========================================

    function setupGazLogic() {
        // Gestion chauffage au gaz
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

        // Mise à jour des estimations
        $('#nb_personnes').on('change input', updateConsumptionEstimates);

        // Gestion commune
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

    // ========================================
    // GESTION DES UPLOADS
    // ========================================

    function setupFileUploadHandlers() {
        // Gestion des changements de fichiers
        $(document).on('change', 'input[type="file"]', function () {
            const fileInput = $(this);
            const fileType = fileInput.attr('name');
            const file = this.files[0];

            if (file) {
                handleFileUpload(fileInput, file, fileType);
            }
        });

        // Clic sur les boutons d'upload
        $(document).on('click', '.upload-card button', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const fileInput = $(this).closest('.upload-card').find('input[type="file"]');
            if (fileInput.length > 0) {
                fileInput.click();
            }
        });

        // Clic sur la carte d'upload
        $(document).on('click', '.upload-card', function (e) {
            if (!$(e.target).is('button, input')) {
                e.preventDefault();
                const fileInput = $(this).find('input[type="file"]');
                if (fileInput.length > 0) {
                    fileInput.click();
                }
            }
        });
    }

    function setupToggleButton() {
        $(document).on('click', '#btn-no-info', function (e) {
            e.preventDefault();
            toggleOptionalFields();
        });
    }

    function initFileUploads() {
        const uploadContainers = $('.upload-card');

        uploadContainers.each(function () {
            const container = $(this);
            const fileInput = container.find('input[type="file"]');
            const inputName = fileInput.attr('name');

            if (uploadedFiles[inputName]) {
                updateUploadDisplay(container, uploadedFiles[inputName], 'success');
            }
        });
    }

    function handleFileUpload(fileInput, file, fileType) {
        const validation = validateFile(file, fileType);
        if (!validation.isValid) {
            showValidationMessage(validation.message);
            fileInput.val('');
            return;
        }

        const uploadCard = fileInput.closest('.upload-card');
        updateUploadDisplay(uploadCard, { name: file.name, status: 'loading' }, 'loading');

        // Simuler l'upload
        setTimeout(() => {
            uploadedFiles[fileType] = {
                file: file,
                name: file.name,
                size: formatFileSize(file.size),
                type: file.type,
                uploadDate: new Date().toISOString()
            };

            updateUploadDisplay(uploadCard, uploadedFiles[fileType], 'success');
        }, 1500);
    }

    function validateFile(file, fileType) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        if (file.size > maxSize) {
            return {
                isValid: false,
                message: 'Le fichier est trop volumineux. Taille maximum: 5MB'
            };
        }

        if (!allowedTypes.includes(file.type)) {
            return {
                isValid: false,
                message: 'Format de fichier non autorisé. Formats acceptés: JPG, PNG, PDF'
            };
        }

        return { isValid: true };
    }

    function updateUploadDisplay(uploadCard, fileData, status) {
        const uploadResult = uploadCard.find('.upload-result');
        const uploadTrigger = uploadCard.find('.upload-trigger');

        switch (status) {
            case 'loading':
                uploadCard.removeClass('has-file');
                uploadResult.removeClass('success error').addClass('loading')
                    .html('⏳ Upload en cours...');
                uploadTrigger.prop('disabled', true);
                break;

            case 'success':
                uploadCard.addClass('has-file');
                uploadResult.removeClass('loading error').addClass('success')
                    .html(`✅ ${fileData.name} (${fileData.size || formatFileSize(fileData.file?.size || 0)})`);
                uploadTrigger.prop('disabled', false).text('Remplacer');
                break;

            case 'error':
                uploadCard.removeClass('has-file');
                uploadResult.removeClass('loading success').addClass('error')
                    .html('❌ Erreur lors de l\'upload');
                uploadTrigger.prop('disabled', false);
                break;
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function toggleOptionalFields() {
        const toggleBtn = $('#btn-no-info');
        const collapsibleSection = $('.collapsible-section');
        const toggleIcon = toggleBtn.find('.toggle-icon');
        const toggleText = toggleBtn.find('.toggle-text');
        const isExpanded = collapsibleSection.hasClass('show');

        if (isExpanded) {
            collapsibleSection.removeClass('show');
            toggleBtn.removeClass('active');
            toggleIcon.text('+');
            toggleText.text("Je n'ai pas ces informations");
            collapsibleSection.find('input').val('');
        } else {
            collapsibleSection.addClass('show');
            toggleBtn.addClass('active');
            toggleIcon.text('−');
            toggleText.text("Masquer ces informations");
        }
    }

    function getFileLabel(fileType) {
        const labels = {
            'rib_file': 'RIB',
            'carte_identite_recto': 'Pièce d\'identité (recto)',
            'carte_identite_verso': 'Pièce d\'identité (verso)'
        };
        return labels[fileType] || fileType;
    }

    // ========================================
    // VALIDATION DES ÉTAPES
    // ========================================

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
                isValid = true; // Résultats - pas de validation
                break;
            case 6:
                isValid = validateStep6(currentStepElement);
                break;
            case 7:
                isValid = true; // Récapitulatif - pas de validation
                break;
        }

        if (!isValid) {
            showValidationMessage('Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
    }

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

    // Version simplifiée pour test du récapitulatif
    function validateStep6(stepElement) {
        let isValid = true;
        let errors = [];

        console.log('Validation step 6 simplifiée...');

        // Champs obligatoires minimaux
        const requiredFields = [
            { id: 'client_nom', label: 'Nom' },
            { id: 'client_prenom', label: 'Prénom' },
            { id: 'client_email', label: 'Email' }
        ];

        requiredFields.forEach(field => {
            const $field = stepElement.find(`#${field.id}`);
            const value = $field.val()?.trim() || '';

            if (!value) {
                isValid = false;
                errors.push(`Le champ "${field.label}" est requis`);
                $field.addClass('field-error');
            } else {
                $field.removeClass('field-error').addClass('field-success');
            }
        });

        // Validation email basique
        const email = stepElement.find('#client_email').val()?.trim();
        if (email && !email.includes('@')) {
            isValid = false;
            errors.push('L\'adresse email n\'est pas valide');
            stepElement.find('#client_email').addClass('field-error');
        }

        // Condition obligatoire minimale
        const acceptConditions = stepElement.find('#accept_conditions').is(':checked');
        if (!acceptConditions) {
            isValid = false;
            errors.push('Vous devez accepter les conditions générales');
        }

        if (!isValid && errors.length > 0) {
            showValidationMessage(errors.join('<br>'));
            console.log('Erreurs de validation:', errors);
        } else {
            console.log('Validation réussie!');
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

    // ========================================
    // SAUVEGARDE DES DONNÉES
    // ========================================

    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        currentStepElement.find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name || type === 'file') return;

            const cleanName = name.replace('[]', '');

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[cleanName] = $field.val();
                }
            } else if (type === 'checkbox') {
                formData[cleanName] = $field.is(':checked');
            } else {
                formData[cleanName] = $field.val();
            }
        });
    }

    function collectAllFormData() {
        const data = {};

        // Étape 1 - Logement
        data.superficie = parseFloat($('#superficie').val()) || 0;
        data.nb_personnes = parseInt($('#nb_personnes').val()) || 0;
        data.commune = $('#commune').val() || '';
        data.type_logement = $('input[name="type_logement"]:checked').val() || '';

        // Si commune autre
        if (data.commune === 'autre') {
            data.nom_commune_autre = $('#nom_commune_autre').val() || '';
            data.type_gaz_autre = $('input[name="type_gaz_autre"]:checked').val() || '';
        }

        // Étape 2 - Chauffage
        data.chauffage_gaz = $('input[name="chauffage_gaz"]:checked').val() || '';
        if (data.chauffage_gaz === 'oui') {
            data.isolation = $('input[name="isolation"]:checked').val() || '';
        }

        // Étape 3 - Eau chaude
        data.eau_chaude = $('input[name="eau_chaude"]:checked').val() || '';

        // Étape 4 - Cuisson et offre
        data.cuisson = $('input[name="cuisson"]:checked').val() || '';
        data.offre = $('input[name="offre"]:checked').val() || '';

        return data;
    }

    function collectClientData() {
        return {
            // Infos personnelles
            nom: $('#client_nom').val()?.trim() || '',
            prenom: $('#client_prenom').val()?.trim() || '',
            email: $('#client_email').val()?.trim() || '',
            telephone: $('#client_telephone').val()?.trim() || '',
            date_naissance: $('#client_date_naissance').val() || '',
            lieu_naissance: $('#client_lieu_naissance').val()?.trim() || '',

            // Adresse et PDL
            pdl_adresse: $('#pdl_adresse').val()?.trim() || '',
            numero_compteur: $('#numero_compteur').val()?.trim() || '',
            adresse: $('#client_adresse').val()?.trim() || '',
            code_postal: $('#client_code_postal').val()?.trim() || '',
            ville: $('#client_ville').val()?.trim() || '',
            complement: $('#client_complement').val()?.trim() || '',

            // Ancien locataire
            ancien_nom: $('#ancien_nom').val()?.trim() || '',
            ancien_prenom: $('#ancien_prenom').val()?.trim() || '',
            ancien_numero_compteur: $('#ancien_numero_compteur').val()?.trim() || '',

            // Conditions
            accept_conditions: $('#accept_conditions').is(':checked'),
            accept_prelevement: $('#accept_prelevement').is(':checked')
        };
    }

    function prepareRecapData() {
        // Collecter toutes les données pour le récapitulatif
        const allFormData = collectAllFormData();
        const clientData = collectClientData();

        // Stocker les données pour l'étape 7
        window.recapData = {
            form_data: allFormData,
            client_data: clientData,
            results_data: calculationResults,
            uploaded_files: uploadedFiles
        };

        console.log('Données de récapitulatif préparées');
    }

    // ========================================
    // CALCUL DES RÉSULTATS (ÉTAPE 4 → 5)
    // ========================================

    function calculateResults() {
        const allData = collectAllFormData();

        // Validation
        if (!allData.superficie || !allData.nb_personnes) {
            showValidationMessage('Des informations obligatoires sont manquantes.');
            return;
        }

        // Aller à l'étape 5
        goToStep(5);

        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation gaz personnalisée...</p>
            </div>
        `);

        sendDataToCalculator(allData);
    }

    function sendDataToCalculator(userData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'gaz-residentiel',
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

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: dataToSend,
            timeout: 30000,
            success: function (response) {
                if (response.success) {
                    calculationResults = response.data;
                    displayResults(response.data);
                } else {
                    displayError('Erreur lors du calcul: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Erreur AJAX:', { status, error });
                displayError('Erreur de connexion lors du calcul');
            }
        });
    }

    function displayResults(results) {
        if (!results || !results.consommation_annuelle) {
            displayError('Données de résultats incomplètes');
            return;
        }

        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const coutAnnuel = parseFloat(results.cout_annuel_ttc) || 0;
        const coutMensuel = parseFloat(results.total_mensuel) || Math.round(coutAnnuel / 10);

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
                
                <!-- Répartition de la consommation -->
                <div class="repartition-conso">
                    <div class="repartition-header">
                        <h3>🔥 Répartition de votre consommation gaz</h3>
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
                                <div class="item-percentage">${Math.round(chauffage / consommationAnnuelle * 100)}%</div>
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
                                        <div class="item-name">Eau chaude</div>
                                        <div class="item-value">${eauChaude.toLocaleString()} kWh/an</div>
                                    </div>
                                </div>
                                <div class="item-percentage">${Math.round(eauChaude / consommationAnnuelle * 100)}%</div>
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
                                <div class="item-percentage">${Math.round(cuisson / consommationAnnuelle * 100)}%</div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${Math.round(cuisson / consommationAnnuelle * 100)}%"></div>
                            </div>
                        </div>` : ''}
                    </div>
                </div>
            </div>
        `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
    }

    // ========================================
    // RÉCAPITULATIF (ÉTAPE 7) - VERSION CORRIGÉE
    // ========================================

    function findRecapContainer() {
        // Essayer différents containers possibles
        const possibleContainers = [
            '#recap-container',
            '#recap-container-final',
            '#recap-container-final-pro',
            '.form-step[data-step="7"] .recap-container',
            '.form-step[data-step="7"]'
        ];

        for (let selector of possibleContainers) {
            const container = $(selector);
            if (container.length > 0) {
                console.log(`Container trouvé: ${selector}`);
                return container;
            }
        }

        // Si aucun container trouvé, créer un dans l'étape 7
        const step7 = $('.form-step[data-step="7"]');
        if (step7.length > 0) {
            step7.html('<div id="recap-container"></div>');
            console.log('Container créé dans l\'étape 7');
            return $('#recap-container');
        }

        // Dernier recours : créer un container temporaire
        $('body').append('<div id="recap-container-temp" style="padding: 20px; margin: 20px; border: 2px solid #007cba;"></div>');
        console.log('Container temporaire créé');
        return $('#recap-container-temp');
    }

    function displayFullRecap() {
        console.log('Génération du récapitulatif complet gaz résidentiel');

        // Trouver le bon container
        const targetContainer = findRecapContainer();

        if (!window.recapData) {
            console.error('Aucune donnée de récapitulatif disponible');
            targetContainer.html(`
                <div class="error-state">
                    <div class="error-icon">❌</div>
                    <h3>Erreur de données</h3>
                    <p>Les données du récapitulatif ne sont pas disponibles.</p>
                    <button class="btn btn-primary" onclick="goToStep(1)">← Recommencer</button>
                </div>
            `);
            return;
        }

        const data = window.recapData;
        const results = data.results_data || {};
        const formData = data.form_data || {};
        const clientData = data.client_data || {};
        const uploadedFiles = data.uploaded_files || {};

        // Formatage des valeurs principales
        const consommation = parseInt(results.consommation_annuelle) || 0;
        const coutAnnuel = parseFloat(results.cout_annuel_ttc) || 0;
        const coutMensuel = Math.round(coutAnnuel / 10);

        // Répartition de la consommation
        const repartition = results.repartition || {};
        const chauffage = parseInt(repartition.chauffage) || 0;
        const eauChaude = parseInt(repartition.eau_chaude) || 0;
        const cuisson = parseInt(repartition.cuisson) || 0;

        // Génération du HTML complet avec styles inline pour éviter les problèmes CSS
        const recapHtml = `
            <div class="recap-complet" style="max-width: 1000px; margin: 0 auto; font-family: Arial, sans-serif;">
                
                <!-- SECTION FORMULE SÉLECTIONNÉE -->
                <div style="background: linear-gradient(135deg, #222F46 0%, #57709d 100%); border-radius: 20px; padding: 2.5rem; margin-bottom: 2rem; color: white; box-shadow: 0 15px 35px rgb(141 141 141 / 30%); position: relative; overflow: hidden;">
                    <div style="text-align: center; margin-bottom: 2rem; position: relative;">
                        <span style="font-size: 3rem; display: block; margin-bottom: 0.5rem;">🔥</span>
                        <h3 style="margin: 0; font-size: 1.75rem; font-weight: 700; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); color: white;">Votre simulation gaz résidentiel</h3>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 2rem;">
                        <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 2rem; margin-bottom: 2rem;">
                            <div style="text-align: center;">
                                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Offre sélectionnée</div>
                                <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">${getOffreLabel(formData.offre)}</div>
                                <div style="display: inline-block; padding: 0.375rem 0.875rem; background: rgba(255, 255, 255, 0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">${getTypeBadgeGaz(formData)}</div>
                            </div>
                            
                            <div style="width: 2px; height: 60px; background: rgba(255, 255, 255, 0.3);"></div>
                            
                            <div style="text-align: center;">
                                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Type de gaz</div>
                                <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">${getTypeGaz(formData)}</div>
                                <div style="display: inline-block; padding: 0.375rem 0.875rem; background: rgba(255, 255, 255, 0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Résidentiel</div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div style="background: rgba(255, 255, 255, 0.25); border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s ease;">
                                <div style="font-size: 2rem; width: 50px; height: 50px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">📅</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">Coût annuel</div>
                                    <div style="font-size: 1.5rem; font-weight: 700;">${coutAnnuel.toLocaleString()}€ TTC</div>
                                    <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 0.25rem;">Tout compris</div>
                                </div>
                            </div>
                            
                            <div style="background: rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s ease;">
                                <div style="font-size: 2rem; width: 50px; height: 50px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">📆</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">Moyenne mensuelle</div>
                                    <div style="font-size: 1.5rem; font-weight: 700;">${coutMensuel.toLocaleString()}€<span style="font-size: 0.875rem; font-weight: 400; opacity: 0.8;">/mois TTC</span></div>
                                    <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 0.25rem;">Sur 10 mois</div>
                                </div>
                            </div>
                            
                            <div style="background: rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s ease;">
                                <div style="font-size: 2rem; width: 50px; height: 50px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">🔥</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">Consommation annuelle</div>
                                    <div style="font-size: 1.5rem; font-weight: 700;">${consommation.toLocaleString()} <span style="font-size: 0.875rem; font-weight: 400; opacity: 0.8;">kWh/an</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION LOGEMENT -->
                <div style="background: white; border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
                    <h3 style="display: flex; align-items: center; gap: 1rem; font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f3f4f6;">
                        <span style="font-size: 1.5rem; width: 45px; height: 45px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center;">🏠</span>
                        Informations du logement
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Type de logement</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${formData.type_logement === 'maison' ? 'Maison' : 'Appartement'}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Surface habitable</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${formData.superficie} m²</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Nombre d'occupants</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${formData.nb_personnes} personne${formData.nb_personnes > 1 ? 's' : ''}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Commune</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${getCommuneDisplay(formData)}</span>
                        </div>
                        ${formData.chauffage_gaz === 'oui' ? `
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Isolation</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${getIsolationLabel(formData.isolation)}</span>
                        </div>` : ''}
                    </div>
                    
                    <!-- Usages du gaz -->
                    <div style="margin-top: 1.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Usages du gaz</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.5rem;">
                                ${formData.chauffage_gaz === 'oui' ? '<span style="background: #ffebee; color: #c62828; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; border: 1px solid #ffcdd2; display: inline-flex; align-items: center; gap: 0.5rem;">🔥 Chauffage</span>' : ''}
                                ${formData.eau_chaude === 'gaz' ? '<span style="background: #fff3e0; color: #e65100; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; border: 1px solid #ffcc80; display: inline-flex; align-items: center; gap: 0.5rem;">🚿 Eau chaude</span>' : ''}
                                ${formData.cuisson === 'gaz' ? '<span style="background: #fff3e0; color: #e65100; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; border: 1px solid #ffcc80; display: inline-flex; align-items: center; gap: 0.5rem;">🍳 Cuisson</span>' : ''}
                                ${!formData.chauffage_gaz && !formData.eau_chaude && !formData.cuisson ? '<span style="background: #f3f4f6; color: #6b7280; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; border: 1px solid #d1d5db;">Aucun usage spécifié</span>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION CONSOMMATION DÉTAILLÉE -->
                <div style="background: white; border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
                    <h3 style="display: flex; align-items: center; gap: 1rem; font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f3f4f6;">
                        <span style="font-size: 1.5rem; width: 45px; height: 45px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center;">📊</span>
                        Répartition de la consommation
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        ${chauffage > 0 ? `
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Chauffage</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #ff6b35;">${chauffage.toLocaleString()} kWh/an (${Math.round(chauffage / consommation * 100)}%)</span>
                        </div>` : ''}
                        ${eauChaude > 0 ? `
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Eau chaude sanitaire</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${eauChaude.toLocaleString()} kWh/an (${Math.round(eauChaude / consommation * 100)}%)</span>
                        </div>` : ''}
                        ${cuisson > 0 ? `
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Cuisson</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${cuisson.toLocaleString()} kWh/an (${Math.round(cuisson / consommation * 100)}%)</span>
                        </div>` : ''}
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Total estimé</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #66bb6a;">${consommation.toLocaleString()} kWh/an</span>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION CLIENT -->
                <div style="background: white; border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
                    <h3 style="display: flex; align-items: center; gap: 1rem; font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f3f4f6;">
                        <span style="font-size: 1.5rem; width: 45px; height: 45px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center;">👤</span>
                        Titulaire du contrat
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Nom complet</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${clientData.prenom || ''} ${clientData.nom || ''}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Email</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${clientData.email || '--'}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Téléphone</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${formatPhone(clientData.telephone) || '--'}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Date de naissance</span>
                            <span style="font-size: 1rem; font-weight: 600; color: #111827;">${formatDate(clientData.date_naissance) || '--'}</span>
                        </div>
                    </div>
                </div>
                
                <!-- ACTION FINALE -->
                <div style="background: linear-gradient(135deg, #222F46 0%, #57709d 100%); border-radius: 16px; padding: 2rem; text-align: center; color: white;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📧</div>
                    <h4 style="font-size: 1.5rem; margin: 0 0 0.5rem 0; color: white;">Finaliser votre souscription</h4>
                    <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 1.5rem; font-size: 1.1rem;">Votre simulation est complète. Cliquez ci-dessous pour envoyer votre dossier et recevoir votre récapitulatif par email.</p>
                    <button type="button" id="btn-finalize-subscription" style="padding: 1rem 2rem; font-size: 1.125rem; width: 100%; max-width: 400px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border: none; border-radius: 12px; background: #82C720; color: white; font-weight: 600; transition: all 0.3s ease; cursor: pointer;">
                        <span style="font-size: 1.25rem;">📧</span>
                        Finaliser ma souscription gaz
                    </button>
                    
                    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">📞 Un conseiller vous contactera sous 72h pour finaliser votre contrat</p>
                        <p style="margin: 0.5rem 0; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">⚡ Mise en service prévue sous 5 jours ouvrés</p>
                        <p style="margin: 0.5rem 0; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">💡 Votre simulation est valable 30 jours</p>
                    </div>
                </div>
                
            </div>
        `;

        // Injecter le HTML dans le container
        console.log('Injection du HTML...');
        targetContainer.html(recapHtml);

        // Forcer l'affichage
        targetContainer.show();

        // Scroll vers le récapitulatif
        $('html, body').animate({
            scrollTop: targetContainer.offset().top - 50
        }, 500);

        console.log('Récapitulatif complet généré avec succès');
    }

    // Fonctions utilitaires pour le récapitulatif
    function getOffreLabel(offre) {
        const labels = {
            'base': 'Tarif Réglementé Gaz',
            'marche': 'Offre de Marché',
            'verte': 'Gaz Vert',
            'fixe': 'Prix Fixe'
        };
        return labels[offre] || 'Tarif standard';
    }

    function getTypeBadgeGaz(formData) {
        if (formData.commune === 'autre') {
            return formData.type_gaz_autre === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
        }

        // Déduire du type de commune
        const communeSelectionnee = $('#commune option:selected');
        const typeGaz = communeSelectionnee.data('type');
        return typeGaz === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
    }

    function getTypeGaz(formData) {
        if (formData.commune === 'autre') {
            return formData.type_gaz_autre === 'naturel' ? 'Naturel' : 'Propane';
        }

        const communeSelectionnee = $('#commune option:selected');
        const typeGaz = communeSelectionnee.data('type');
        return typeGaz === 'naturel' ? 'Naturel' : 'Propane';
    }

    function getCommuneDisplay(formData) {
        if (formData.commune === 'autre') {
            return formData.nom_commune_autre || 'Autre commune';
        }
        return formData.commune || 'Non précisée';
    }

    function getIsolationLabel(isolation) {
        const labels = {
            'faible': 'Isolation faible',
            'correcte': 'Isolation correcte',
            'bonne': 'Bonne isolation',
            'excellente': 'Très bonne isolation'
        };
        return labels[isolation] || isolation || 'Non précisée';
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

    function formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            return date.toLocaleDateString('fr-FR');
        } catch (e) {
            return dateString;
        }
    }

    // ========================================
    // ENVOI EMAIL (depuis étape 7)
    // ========================================

    function finalizeSubscription() {
        if (!window.recapData) {
            showNotification('Aucune donnée à envoyer', 'error');
            return;
        }

        // Afficher le loader au lieu de modifier le bouton
        afficherLoaderGazResidentiel();

        // Préparation des données pour l'envoi
        const emailData = {
            form_type: 'gaz-residentiel',
            form_data: window.recapData.form_data,
            client_data: window.recapData.client_data,
            results_data: window.recapData.results_data,
            uploaded_files: window.recapData.uploaded_files
        };

        performEmailSend(emailData);
    }


    function performEmailSend(emailData) {
        const formData = new FormData();

        // Ajouter l'action spécifique pour le gaz
        formData.append('action', 'process_gaz_form');
        formData.append('nonce', hticSimulateur.nonce);
        formData.append('form_type', 'gaz-residentiel');
        formData.append('form_data', JSON.stringify(emailData));

        // CORRECTION - Ajouter les fichiers avec debug
        const files = emailData.uploaded_files || {};
        console.log('📎 Fichiers à envoyer:', files);

        Object.keys(files).forEach(fileType => {
            const fileData = files[fileType];
            console.log(`📎 Traitement fichier ${fileType}:`, fileData);

            if (fileData && fileData.file) {
                console.log(`✅ Ajout fichier ${fileType}:`, fileData.file.name);
                formData.append(`file_${fileType}`, fileData.file, fileData.file.name);
            } else {
                console.warn(`⚠️ Fichier ${fileType} manquant ou incorrect:`, fileData);
            }
        });

        // Debug FormData - Afficher ce qui est envoyé
        console.log('📤 FormData contenu:');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`  ${key}: Fichier - ${value.name} (${value.size} bytes)`);
            } else {
                console.log(`  ${key}: ${typeof value === 'string' ? value.substring(0, 100) : value}`);
            }
        }

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000,
            success: function (response) {
                cacherLoaderGazResidentiel();

                if (response.success) {
                    afficherMessageSuccesGazResidentiel(response.data?.referenceNumber || 'GAZ-' + Date.now());
                } else {
                    afficherMessageErreurGazResidentiel(response.data || 'Erreur inconnue');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erreur AJAX:', error);
                cacherLoaderGazResidentiel();
                afficherMessageErreurGazResidentiel('Erreur lors de l\'envoi');
            }
        });
    }

    function displaySuccessMessage() {
        const targetContainer = findRecapContainer();

        const successHtml = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
                <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                <h2 style="color: #66bb6a; margin-bottom: 1rem;">Simulation envoyée avec succès !</h2>
                <p>Votre simulation a été envoyée à <strong>${window.recapData.client_data.email}</strong></p>
                
                <div style="background: #f0fdf4; border-radius: 12px; padding: 2rem; margin: 2rem 0;">
                    <h3 style="color: #16a34a; margin-bottom: 1rem;">📞 Prochaines étapes</h3>
                    <ul style="list-style: none; padding: 0; text-align: left;">
                        <li style="margin-bottom: 0.5rem;">• Un conseiller vous contactera sous 24h</li>
                        <li style="margin-bottom: 0.5rem;">• Vérification de votre éligibilité</li>
                        <li>• Finalisation de votre contrat gaz</li>
                    </ul>
                </div>
                
                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <button style="padding: 0.75rem 1.5rem; background: #66bb6a; color: white; border: none; border-radius: 8px; cursor: pointer;" onclick="location.reload()">
                        🔄 Nouvelle simulation
                    </button>
                    <button style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer;" onclick="window.print()">
                        🖨️ Imprimer
                    </button>
                </div>
            </div>
        `;

        targetContainer.html(successHtml);

        // Scroll vers le haut
        $('html, body').animate({ scrollTop: 0 }, 500);
    }

    function afficherLoaderGazResidentiel() {
        if ($('#ajax-loader-gaz').length) return;

        const loader = `
        <div id="ajax-loader-gaz" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.8); display: flex; 
                    justify-content: center; align-items: center; z-index: 99999;">
            <div style="background: white; padding: 50px; border-radius: 15px; text-align: center; 
                        box-shadow: 0 15px 50px rgba(0,0,0,0.4); max-width: 400px;">
                <div class="spinner-gaz" style="border: 6px solid #f3f3f3; border-top: 6px solid #ff6b35; 
                            border-radius: 50%; width: 80px; height: 80px; 
                            animation: spin 1s linear infinite; margin: 0 auto 25px;"></div>
                <h3 style="margin: 0 0 15px 0; color: #ff6b35; font-size: 20px;">Traitement en cours...</h3>
                <p style="margin: 0; font-size: 16px; color: #666; line-height: 1.5;">
                    <strong>Finalisation de votre souscription gaz</strong><br>
                    Génération du récapitulatif et envoi des emails<br>
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

    function cacherLoaderGazResidentiel() {
        $('#ajax-loader-gaz').fadeOut(400, function () {
            $(this).remove();
        });
    }

    function afficherMessageSuccesGazResidentiel(referenceNumber) {
        $('.ajax-message').remove();

        const successHtml = `
        <div class="ajax-message success-message" style="position: fixed; top: 20px; right: 20px; 
                    background: linear-gradient(135deg, #82C720 0%, #82C720 100%); color: white; 
                    padding: 20px 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2); 
                    z-index: 100000; max-width: 400px; animation: slideIn 0.5s ease;">
            <div style="display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 15px;">✅</span>
                <div>
                    <h4 style="margin: 0 0 5px 0; font-size: 16px; color: white;">Souscription envoyée !</h4>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Redirection en cours...</p>
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

        //Redirection vers la page merci après 2 secondes
        setTimeout(() => {
            window.location.href = '/merci';
        }, 2000);
    }

    function afficherMessageErreurGazResidentiel(message) {
        $('.ajax-message').remove();

        const errorHtml = `
        <div class="ajax-message error-message-gaz" style="position: fixed; top: 20px; right: 20px; 
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
                <button class="close-btn-gaz" style="background: white; color: #DC2626; border: none; 
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
        $('.close-btn-gaz').on('click', function () {
            $('.error-message-gaz').fadeOut(300, function () {
                $(this).remove();
            });
        });

        // Auto-fermeture après 8 secondes
        setTimeout(() => {
            $('.error-message-gaz').fadeOut(500, function () {
                $(this).remove();
            });
        }, 8000);
    }


    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================

    function displayError(message) {
        const targetContainer = findRecapContainer() || $('#results-container');

        targetContainer.html(`
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
                <div style="font-size: 4rem; margin-bottom: 1rem; color: #dc2626;">❌</div>
                <h3 style="color: #dc2626; margin-bottom: 1rem;">Erreur</h3>
                <p style="color: #6b7280; margin-bottom: 2rem;">${message}</p>
                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <button style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;" onclick="location.reload()">🔄 Recharger</button>
                    <button id="btn-back-to-form" style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer;">← Retour</button>
                </div>
            </div>
        `);

        $('#btn-back-to-form').on('click', function () {
            goToStep(4);
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

    function restartSimulation() {
        currentStep = 1;
        formData = {};
        calculationResults = null;
        uploadedFiles = {};
        window.recapData = null;

        $('#simulateur-gaz-residentiel')[0].reset();

        showStep(1);
        updateProgress();
        updateNavigation();

        $('.field-error, .field-success').removeClass('field-error field-success');
    }

    // ========================================
    // API PUBLIQUE ET DEBUG
    // ========================================

    window.HticGazResidentielData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep,
        getCalculationResults: () => calculationResults,
        getUploadedFiles: () => uploadedFiles,
        getRecapData: () => window.recapData
    };

    // Fonction de debug
    window.debugGazResidentiel = function () {
        console.log('DEBUG GAZ RÉSIDENTIEL:');
        console.log('- Étape actuelle:', currentStep);
        console.log('- Résultats:', calculationResults);
        console.log('- Fichiers:', uploadedFiles);
        console.log('- Données formulaire:', collectAllFormData());
        console.log('- Données client:', collectClientData());
        console.log('- Données récap:', window.recapData);
    };

});