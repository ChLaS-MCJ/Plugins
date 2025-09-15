// elec-professionnel.js - Version complète avec calculs côté serveur

jQuery(document).ready(function ($) {

    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let configData = {};
    let uploadedFile = null;

    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupDynamicLogic();
        setupFileUpload();
    }

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config-pro');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration Pro chargée:', configData);
            } catch (e) {
                console.error('❌ Erreur configuration Pro:', e);
                configData = {};
            }
        }
    }

    // ===============================
    // NAVIGATION ENTRE LES ÉTAPES
    // ===============================

    function setupStepNavigation() {
        $('#btn-next-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                goToNextStep();
            }
        });

        $('#btn-previous-pro').on('click', function () {
            saveCurrentStepData();
            goToPreviousStep();
        });

        $('#btn-calculate-pro').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                calculateResults();
            }
        });

        $('#btn-restart-pro').on('click', function () {
            if (confirm('Voulez-vous vraiment recommencer ?')) {
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
        $('#btn-previous-pro').toggle(currentStep > 1);

        if (currentStep === totalSteps) {
            $('#btn-next-pro, #btn-calculate-pro').hide();
            $('#btn-restart-pro').show();
        } else if (currentStep === totalSteps - 1) {
            $('#btn-next-pro').hide();
            $('#btn-calculate-pro').show();
            $('#btn-restart-pro').hide();
        } else {
            $('#btn-next-pro').show();
            $('#btn-calculate-pro, #btn-restart-pro').hide();
        }
    }

    // ===============================
    // LOGIQUE DYNAMIQUE
    // ===============================

    function setupDynamicLogic() {
        // Gestion catégorie et éligibilité TRV
        $('input[name="categorie"]').on('change', function () {
            const categorie = $(this).val();

            // Auto-update éligibilité TRV
            if (categorie === 'BT < 36 kVA') {
                $('input[name="eligible_trv"][value="oui"]').prop('checked', true);
                $('input[name="eligible_trv"]').closest('.form-group').removeClass('disabled');
            } else {
                $('input[name="eligible_trv"][value="non"]').prop('checked', true);
                $('input[name="eligible_trv"]').closest('.form-group').addClass('disabled');
            }

            // Limiter les options de puissance selon la catégorie
            updatePuissanceOptions(categorie);
        });

        // Checkbox "Je n'ai pas l'information"
        $('#pas_info').on('change', function () {
            if ($(this).is(':checked')) {
                $('#point_livraison').prop('disabled', true).val('');
                $('#num_prm').prop('disabled', true).val('');
            } else {
                $('#point_livraison').prop('disabled', false);
                $('#num_prm').prop('disabled', false);
            }
        });

        // Initialiser avec les valeurs par défaut
        const defaultCategorie = $('input[name="categorie"]:checked').val();
        if (defaultCategorie) {
            updatePuissanceOptions(defaultCategorie);
        }
    }

    function updatePuissanceOptions(categorie) {
        const $select = $('#puissance');
        $select.find('option').show();

        if (categorie === 'BT > 36 kVA') {
            $select.find('option').each(function () {
                const val = parseInt($(this).val());
                if (val && val <= 36) {
                    $(this).hide();
                }
            });
            // Sélectionner une valeur valide si la valeur actuelle est cachée
            if (parseInt($select.val()) <= 36) {
                $select.val('42');
            }
        } else if (categorie === 'HTA') {
            $select.find('option').each(function () {
                const val = parseInt($(this).val());
                if (val && val < 250) {
                    $(this).hide();
                }
            });
            if (parseInt($select.val()) < 250) {
                $select.val('250');
            }
        }
    }

    // ===============================
    // UPLOAD FICHIER
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

            // Stocker le fichier
            uploadedFile = file;
            formData.kbis_file = file;

            // Afficher le nom
            $fileName.text(file.name);
            $fileSelected.show();
            $uploadArea.addClass('has-file');
            $('.file-upload-text').hide();

            console.log('📎 Fichier uploadé:', {
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
    // VALIDATION
    // ===============================

    function setupFormValidation() {
        // Validation SIRET
        $('#siret').on('blur', function () {
            const siret = $(this).val().replace(/\s/g, '');
            if (siret.length !== 14) {
                $(this).addClass('field-error');
                showValidationMessage('Le SIRET doit contenir 14 chiffres');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation Code Postal
        $('#code_postal').on('blur', function () {
            const cp = $(this).val();
            if (!/^[0-9]{5}$/.test(cp)) {
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
            if (!emailRegex.test(email)) {
                $(this).addClass('field-error');
                showValidationMessage('Email invalide');
            } else {
                $(this).removeClass('field-error').addClass('field-success');
            }
        });

        // Validation téléphone
        $('#telephone').on('blur', function () {
            const tel = $(this).val().replace(/[\s\-\(\)\.]/g, '');
            if (tel.length < 10) {
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
            case 1: // Configuration
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

        // Catégorie
        if (!stepElement.find('input[name="categorie"]:checked').length) {
            isValid = false;
        }

        // Puissance
        const puissance = stepElement.find('#puissance').val();
        if (!puissance) {
            stepElement.find('#puissance').addClass('field-error');
            isValid = false;
        }

        // Formule tarifaire
        if (!stepElement.find('input[name="formule_tarifaire"]:checked').length) {
            isValid = false;
        }

        // Consommation
        const conso = parseInt(stepElement.find('#conso_annuelle').val());
        if (!conso || conso < 1000) {
            stepElement.find('#conso_annuelle').addClass('field-error');
            isValid = false;
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
            // Vérifier PDL ou PRM
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
    // COLLECTE DES DONNÉES
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

        // Ajouter le fichier uploadé si présent
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

        // Ajouter le fichier uploadé si présent
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
        if (!allData.categorie || !allData.conso_annuelle || !allData.puissance) {
            showValidationMessage('Données manquantes pour le calcul');
            return;
        }

        console.log('🚀 Envoi des données au calculateur:', allData);

        // Afficher l'étape des résultats
        showStep(4);
        updateProgress();
        updateNavigation();

        // Afficher le loader
        $('#results-container-pro').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
                <small>Analyse des 4 offres tarifaires...</small>
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
            type: 'elec-professionnel',
            user_data: userData,
            config_data: configData
        };

        // Ajouter le nonce si disponible
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            dataToSend.nonce = hticSimulateur.nonce;
        } else if (typeof hticSimulateurUnifix !== 'undefined' && hticSimulateurUnifix.calculateNonce) {
            dataToSend.nonce = hticSimulateurUnifix.calculateNonce;
        }

        console.log('📤 Envoi AJAX:', {
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
    // AFFICHAGE DES RÉSULTATS
    // ===============================

    function displayResults(results) {
        console.log('📊 Affichage des résultats Pro:', results);

        // Vérifier que les données sont présentes
        if (!results || !results.offres) {
            displayError('Données de résultats incomplètes');
            return;
        }

        // Déclarer les variables
        const offres = results.offres || [];
        const offresValides = offres.filter(o => o.total_ttc > 0); // Filtrer les offres valides
        const meilleureOffre = results.meilleure_offre || offresValides[0];
        const consommation = parseInt(results.consommation_annuelle) || 0;
        const userData = results.user_data || formData;

        // Vérifier qu'on a des offres à afficher
        if (offresValides.length === 0) {
            displayError('Aucune offre disponible pour votre profil');
            return;
        }

        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">⚡</div>
                <h3>Analyse tarifaire pour votre entreprise</h3>
                <div class="big-number">${consommation.toLocaleString()} <span>kWh/an</span></div>
                <p>Puissance : <strong>${results.puissance} kVA</strong></p>
                <p>Catégorie : <strong>${results.categorie}</strong></p>
                <p>Meilleure offre : <strong>${meilleureOffre.total_ttc.toFixed(0).toLocaleString()}€ TTC/an</strong></p>
            </div>

            <!-- Comparaison des offres -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des ${offresValides.length} offres disponibles</h3>
                <div class="tarifs-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    ${offresValides.map((offre, index) => `
                        <div class="tarif-card ${offre.meilleure ? 'recommended' : ''}" 
                             style="background: ${offre.meilleure ? 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)' : 'white'};">
                            ${offre.meilleure ? '<span class="recommended-badge">⭐ Meilleure offre</span>' : ''}
                            
                            <div style="text-align: center; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                                <h4 style="margin: 0; color: #111827; font-size: 1.1rem;">${offre.nom}</h4>
                                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                                    ${offre.type}
                                </div>
                            </div>
                            
                            <div class="tarif-prix">${offre.total_ttc.toFixed(0).toLocaleString()}€<span>/an TTC</span></div>
                            <div class="tarif-mensuel">${(offre.total_ttc / 12).toFixed(0).toLocaleString()}€/mois</div>
                            
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: #6b7280;">Total HT:</span>
                                    <span style="font-weight: 600;">${offre.total_ht.toFixed(0).toLocaleString()}€</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: #6b7280;">Abonnement:</span>
                                    <span style="font-weight: 600;">${offre.abonnement_annuel.toFixed(0)}€/an</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: #6b7280;">Consommation:</span>
                                    <span style="font-weight: 600;">${offre.cout_consommation.toFixed(0).toLocaleString()}€</span>
                                </div>
                            </div>

                            ${offre.nom === 'Tempo Pro' && offre.details_tempo ? `
                                <div style="margin-top: 1rem; padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem; font-size: 0.7rem;">
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Détail Tempo:</div>
                                    <div style="margin-bottom: 0.25rem;">
                                        <strong>Rouge (${offre.details_tempo.jours_rouge}j):</strong> 
                                        HP ${offre.details_tempo.prix_rouge_hp}€ | HC ${offre.details_tempo.prix_rouge_hc}€
                                        <br>Coût: ${offre.details_tempo.cout_rouge.toFixed(0)}€
                                    </div>
                                    <div style="margin-bottom: 0.25rem;">
                                        <strong>Blanc (${offre.details_tempo.jours_blanc}j):</strong>
                                        HP ${offre.details_tempo.prix_blanc_hp}€ | HC ${offre.details_tempo.prix_blanc_hc}€
                                        <br>Coût: ${offre.details_tempo.cout_blanc.toFixed(0)}€
                                    </div>
                                    <div>
                                        <strong>Bleu (${offre.details_tempo.jours_bleu}j):</strong>
                                        HP ${offre.details_tempo.prix_bleu_hp}€ | HC ${offre.details_tempo.prix_bleu_hc}€
                                        <br>Coût: ${offre.details_tempo.cout_bleu.toFixed(0)}€
                                    </div>
                                </div>
                            ` : ''}

                            ${offre.details && offre.nom !== 'Tempo Pro' ? `
                                <div style="margin-top: 1rem; padding: 0.5rem; background: #f9fafb; border-radius: 0.5rem;">
                                    <div style="font-size: 0.75rem; color: #6b7280;">
                                        ${offre.details}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
                
                ${offresValides.length > 1 ? `
                    <div class="economies" style="margin-top: 1.5rem;">
                        <p>💡 <strong>Économie potentielle :</strong> 
                        jusqu'à ${results.economie_max ? results.economie_max.toFixed(0).toLocaleString() : '0'}€/an 
                        en choisissant la meilleure offre !</p>
                    </div>
                ` : ''}
            </div>

            <!-- Détail des taxes (meilleure offre) -->
            ${meilleureOffre.taxes ? `
            <div class="pro-recap-table" style="margin-top: 2rem;">
                <div class="pro-recap-header">
                    <h3>📊 Détail des taxes et contributions</h3>
                </div>
                <div class="pro-recap-body">
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">CSPE</span>
                        <span class="pro-recap-value">${meilleureOffre.taxes.cspe.toFixed(2)}€</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">TCFE</span>
                        <span class="pro-recap-value">${meilleureOffre.taxes.tcfe.toFixed(2)}€</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">CTA</span>
                        <span class="pro-recap-value">${meilleureOffre.taxes.cta.toFixed(2)}€</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">TVA (20%)</span>
                        <span class="pro-recap-value">${meilleureOffre.taxes.tva.toFixed(2)}€</span>
                    </div>
                    <div class="pro-recap-row total">
                        <span class="pro-recap-label">Total taxes</span>
                        <span class="pro-recap-value">${meilleureOffre.total_taxes.toFixed(2)}€</span>
                    </div>
                </div>
            </div>
            ` : ''}

            <!-- Récapitulatif client -->
            <div class="pro-recap-table">
                <div class="pro-recap-header">
                    <h3>📋 Récapitulatif de votre demande</h3>
                </div>
                <div class="pro-recap-body">
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Entreprise</span>
                        <span class="pro-recap-value">${userData.raison_sociale || '-'}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">SIRET</span>
                        <span class="pro-recap-value">${userData.siret || '-'}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Contact</span>
                        <span class="pro-recap-value">${userData.nom || ''} ${userData.prenom || ''}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Email</span>
                        <span class="pro-recap-value">${userData.email || '-'}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Téléphone</span>
                        <span class="pro-recap-value">${userData.telephone || '-'}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Adresse</span>
                        <span class="pro-recap-value">${userData.adresse || ''} ${userData.code_postal || ''} ${userData.ville || ''}</span>
                    </div>
                    <div class="pro-recap-row">
                        <span class="pro-recap-label">Document K-bis</span>
                        <span class="pro-recap-value">${userData.kbis_filename || 'Non fourni'}</span>
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

        $('#results-container-pro').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        // Préparer les données pour l'email avec toutes les infos
        prepareEmailData(userData, offresValides, meilleureOffre);
    }

    function displayError(message) {
        $('#results-container-pro').html(`
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h3>Erreur lors du calcul</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                    <button class="btn btn-secondary" id="btn-back-to-form-pro">← Retour au formulaire</button>
                </div>
            </div>
        `);

        $('#btn-back-to-form-pro').on('click', function () {
            goToStep(3);
        });
    }

    // ===============================
    // PRÉPARATION EMAIL
    // ===============================

    function prepareEmailData(userData, offres, meilleureOffre) {
        const emailData = {
            entreprise: {
                raison_sociale: userData.raison_sociale || formData.raison_sociale,
                siret: userData.siret || formData.siret,
                forme_juridique: userData.forme_juridique || formData.forme_juridique,
                code_naf: userData.code_naf || formData.code_naf
            },
            contact: {
                nom: userData.nom || formData.nom,
                prenom: userData.prenom || formData.prenom,
                email: userData.email || formData.email,
                telephone: userData.telephone || formData.telephone
            },
            adresse: {
                rue: userData.adresse || formData.adresse,
                complement: userData.complement_adresse || formData.complement_adresse,
                code_postal: userData.code_postal || formData.code_postal,
                ville: userData.ville || formData.ville
            },
            consommation: {
                annuelle: userData.conso_annuelle || formData.conso_annuelle || userData.consommation_annuelle,
                puissance: userData.puissance || formData.puissance,
                formule: userData.formule_tarifaire || formData.formule_tarifaire || userData.formule,
                categorie: userData.categorie || formData.categorie
            },
            offres: offres,
            meilleure_offre: meilleureOffre,
            document_kbis: userData.kbis_filename || formData.kbis_filename || null,
            date_simulation: new Date().toISOString()
        };

        window.emailData = emailData;
        console.log('📧 Données email préparées:', emailData);
    }

    window.sendResultsByEmail = function () {
        if (!window.emailData) {
            alert('Aucune donnée à envoyer');
            return;
        }

        // Déterminer l'URL AJAX
        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        console.log('📮 Envoi email avec les données:', window.emailData);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_pro_simulation_email',
                email_data: window.emailData,
                nonce: typeof hticSimulateur !== 'undefined' ? hticSimulateur.nonce : ''
            },
            success: function (response) {
                if (response.success) {
                    alert('Email envoyé avec succès !');
                } else {
                    alert('Erreur lors de l\'envoi: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function () {
                alert('Erreur de connexion lors de l\'envoi de l\'email');
            }
        });
    };

    // ===============================
    // UTILITAIRES
    // ===============================

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
        $('#simulateur-elec-professionnel')[0].reset();
        showStep(1);
        updateProgress();
        updateNavigation();
        $('.field-error, .field-success').removeClass('field-error field-success');
        $('.file-selected-name').hide();
        $('.file-upload-area').removeClass('has-file');
        $('.file-upload-text').show();
    }

    // API publique
    window.HticSimulateurProData = {
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep,
        goToStep: goToStep
    };

});