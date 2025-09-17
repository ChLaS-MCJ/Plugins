/**
 * JavaScript pour le formulaire Gaz Résidentiel
 * Fichier: formulaires/gaz-residentiel/gaz-residentiel.js
 */
jQuery(document).ready(function ($) {
    console.log('Initialisation du simulateur gaz résidentiel');

    // Variables globales
    const communeSelect = document.getElementById('commune');
    const nbPersonnesInput = document.getElementById('nb_personnes');
    const chauffageRadios = document.querySelectorAll('input[name="chauffage_gaz"]');
    const chauffageDetails = document.querySelector('.chauffage-details');
    const autreCommuneDetails = document.getElementById('autre-commune-details');
    const typeGazInfo = document.getElementById('type-gaz-info');
    const typeGazText = document.getElementById('type-gaz-text');

    // Variables pour la navigation
    let currentStep = 1;
    const totalSteps = 6;
    let calculationResults = null;
    let formData = {};
    let configData = {};

    // Initialisation
    init();

    function init() {
        // Initialiser l'étape courante
        currentStep = 1;
        showStep(currentStep);
        updateProgressBar();

        // Charger les données de configuration
        loadConfigData();

        // Charger les communes depuis le back-office
        loadCommunesFromBackoffice();

        // Mettre à jour les estimations initiales
        updateConsumptionEstimates();

        // Gérer l'affichage conditionnel du chauffage
        toggleChauffageDetails();

        // Events listeners
        setupEventListeners();
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

    function setupEventListeners() {
        // Changement du nombre de personnes
        if (nbPersonnesInput) {
            nbPersonnesInput.addEventListener('change', updateConsumptionEstimates);
            nbPersonnesInput.addEventListener('input', updateConsumptionEstimates);
        }

        // Changement de commune
        if (communeSelect) {
            communeSelect.addEventListener('change', handleCommuneSelection);
        }

        // Gestion du chauffage au gaz
        chauffageRadios.forEach(radio => {
            radio.addEventListener('change', toggleChauffageDetails);
        });

        // Configuration des boutons de navigation comme dans elec-residentiel
        $('#btn-next, .btn-next').on('click', function () {
            if (validateCurrentStep()) {
                saveCurrentStepData();
                goToNextStep();
            }
        });

        $('#btn-previous, .btn-prev').on('click', function () {
            saveCurrentStepData();
            goToPreviousStep();
        });

        // IMPORTANT: Le calcul se fait seulement à l'étape 6
        $('#btn-calculate, .btn-calculate').on('click', function () {
            // Empêcher le calcul s'il n'est pas sur l'étape 6
            console.log('Tentative de calcul à l\'étape:', currentStep);
            if (currentStep !== 6) {
                console.log('Calcul bloqué - pas à l\'étape 6');
                return false;
            }
            calculateGazEstimation();
        });

        // Bouton envoyer par email
        $(document).on('click', '#btn-send-email', function () {
            sendResultsByEmail();
        });
    }

    // ================================
    // NAVIGATION ENTRE ÉTAPES
    // ================================

    function setupNavigationButtons() {
        const btnNext = document.querySelectorAll('.btn-next');
        const btnPrev = document.querySelectorAll('.btn-prev');

        btnNext.forEach(btn => {
            btn.addEventListener('click', function () {
                console.log('Étape suivante');
                if (validateCurrentStep()) {
                    saveCurrentStepData();
                    goToNextStep();
                }
            });
        });

        btnPrev.forEach(btn => {
            btn.addEventListener('click', function () {
                console.log('Étape précédente');
                saveCurrentStepData();
                goToPreviousStep();
            });
        });
    }

    function goToNextStep() {
        if (currentStep < totalSteps) {
            hideStep(currentStep);
            currentStep++;
            showStep(currentStep);
            updateProgressBar();
            updateNavigation();
        }
    }

    function goToPreviousStep() {
        if (currentStep > 1) {
            hideStep(currentStep);
            currentStep--;
            showStep(currentStep);
            updateProgressBar();
            updateNavigation();
        }
    }

    function showStep(stepNumber) {
        const step = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
        if (step) {
            step.classList.add('active');
            step.style.display = 'block';
        }

        // Mettre à jour l'indicateur de progression
        const progressStep = document.querySelector(`.step[data-step="${stepNumber}"]`);
        if (progressStep) {
            progressStep.classList.add('active');
        }
    }

    function hideStep(stepNumber) {
        const step = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
        if (step) {
            step.classList.remove('active');
            step.style.display = 'none';
        }

        // Marquer les étapes précédentes comme complétées
        if (stepNumber < currentStep) {
            const progressStep = document.querySelector(`.step[data-step="${stepNumber}"]`);
            if (progressStep) {
                progressStep.classList.remove('active');
                progressStep.classList.add('completed');
            }
        }
    }

    function updateProgressBar() {
        const progress = (currentStep / totalSteps) * 100;
        const progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = progress + '%';
            progressFill.setAttribute('data-progress', Math.round(progress));
        }

        // Marquer les étapes comme complétées
        for (let i = 1; i < currentStep; i++) {
            const step = document.querySelector(`.step[data-step="${i}"]`);
            if (step) {
                step.classList.add('completed');
                step.classList.remove('active');
            }
        }
    }

    function updateNavigation() {
        // Afficher/masquer les boutons selon l'étape
        const btnPrev = document.querySelector('.btn-prev, #btn-previous');
        const btnNext = document.querySelector('.btn-next, #btn-next');
        const btnCalculate = document.querySelector('.btn-calculate, #btn-calculate');

        if (btnPrev) {
            btnPrev.style.display = currentStep > 1 ? 'inline-block' : 'none';
        }

        if (currentStep === 6) { // Étape 6 - afficher le bouton calcul
            if (btnNext) btnNext.style.display = 'none';
            if (btnCalculate) btnCalculate.style.display = 'inline-block';
        } else if (currentStep >= 1 && currentStep <= 5) { // Étapes 1-5 - bouton suivant
            if (btnNext) btnNext.style.display = 'inline-block';
            if (btnCalculate) btnCalculate.style.display = 'none';
        } else {
            // Après calcul, masquer tous les boutons de navigation
            if (btnNext) btnNext.style.display = 'none';
            if (btnCalculate) btnCalculate.style.display = 'none';
        }
    }

    function saveCurrentStepData() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);

        if (currentStepElement) {
            currentStepElement.querySelectorAll('input, select, textarea').forEach(function (field) {
                const name = field.getAttribute('name');
                const type = field.getAttribute('type');

                if (!name) return;

                const cleanName = name.replace('[]', '');

                if (type === 'radio') {
                    if (field.checked) {
                        formData[cleanName] = field.value;
                    }
                } else if (type === 'checkbox') {
                    if (!formData[cleanName]) {
                        formData[cleanName] = [];
                    }

                    if (field.checked) {
                        const value = field.value;
                        if (!formData[cleanName].includes(value)) {
                            formData[cleanName].push(value);
                        }
                    }
                } else {
                    formData[cleanName] = field.value;
                }
            });
        }
    }

    function validateCurrentStep() {
        switch (currentStep) {
            case 1:
                return validateStep1();
            case 2:
                return validateStep2();
            case 3:
                return validateStep3();
            case 4:
                return validateStep4();
            case 5:
                return validateStep5();
            default:
                return true;
        }
    }

    function validateStep1() {
        const superficie = document.getElementById('superficie');
        const nbPersonnes = document.getElementById('nb_personnes');
        const commune = document.getElementById('commune');

        if (!superficie || !superficie.value || parseInt(superficie.value) < 20) {
            showError('Veuillez saisir une superficie valide (minimum 20 m²)');
            return false;
        }

        if (!nbPersonnes || !nbPersonnes.value || parseInt(nbPersonnes.value) < 1) {
            showError('Veuillez saisir un nombre de personnes valide');
            return false;
        }

        if (!commune || !commune.value) {
            showError('Veuillez sélectionner votre commune');
            return false;
        }

        // Validation spéciale pour "autre commune"
        if (commune.value === 'autre') {
            const nomCommune = document.getElementById('nom_commune_autre');
            const typeGaz = document.querySelector('input[name="type_gaz_autre"]:checked');

            if (!nomCommune || !nomCommune.value.trim()) {
                showError('Veuillez saisir le nom de votre commune');
                return false;
            }

            if (!typeGaz) {
                showError('Veuillez sélectionner le type de gaz disponible');
                return false;
            }
        }

        return true;
    }

    function validateStep2() {
        const chauffageGaz = document.querySelector('input[name="chauffage_gaz"]:checked');
        if (!chauffageGaz) {
            showError('Veuillez indiquer si votre logement est chauffé au gaz');
            return false;
        }

        // Si chauffage au gaz, vérifier l'isolation
        if (chauffageGaz.value === 'oui') {
            const isolation = document.querySelector('input[name="isolation"]:checked');
            if (!isolation) {
                showError('Veuillez sélectionner le niveau d\'isolation de votre logement');
                return false;
            }
        }

        return true;
    }

    function validateStep3() {
        const eauChaude = document.querySelector('input[name="eau_chaude"]:checked');
        if (!eauChaude) {
            showError('Veuillez indiquer le mode de production d\'eau chaude');
            return false;
        }
        return true;
    }

    function validateStep4() {
        const cuisson = document.querySelector('input[name="cuisson"]:checked');
        if (!cuisson) {
            showError('Veuillez indiquer le mode de cuisson');
            return false;
        }

        const offre = document.querySelector('input[name="offre"]:checked');
        if (!offre) {
            showError('Veuillez sélectionner le type d\'offre');
            return false;
        }

        return true;
    }

    function validateStep5() {
        // Validation des données de contact
        const civilite = document.querySelector('input[name="civilite"]:checked');
        const prenom = document.getElementById('prenom');
        const nom = document.getElementById('nom');
        const email = document.getElementById('email');
        const codePostal = document.getElementById('code_postal');
        const source = document.getElementById('comment_nous_avez_vous_connu');
        const accepteCGU = document.getElementById('accepte_cgu');

        if (!civilite) {
            showError('Veuillez sélectionner votre civilité');
            return false;
        }

        if (!prenom || !prenom.value.trim()) {
            showError('Veuillez saisir votre prénom');
            return false;
        }

        if (!nom || !nom.value.trim()) {
            showError('Veuillez saisir votre nom');
            return false;
        }

        if (!email || !email.value.trim()) {
            showError('Veuillez saisir votre adresse email');
            return false;
        }

        // Validation email basique
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            showError('Veuillez saisir une adresse email valide');
            return false;
        }

        if (!codePostal || !/^[0-9]{5}$/.test(codePostal.value)) {
            showError('Veuillez saisir un code postal valide (5 chiffres)');
            return false;
        }

        if (!source || !source.value) {
            showError('Veuillez indiquer comment vous nous avez connus');
            return false;
        }

        if (!accepteCGU || !accepteCGU.checked) {
            showError('Veuillez accepter les conditions générales d\'utilisation');
            return false;
        }

        return true;
    }

    // ================================
    // GESTION DES COMMUNES
    // ================================

    function loadCommunesFromBackoffice() {
        console.log('Chargement des communes depuis le back-office...');

        // Vérifier si hticSimulateur est disponible
        if (typeof hticSimulateur === 'undefined') {
            console.warn('hticSimulateur non disponible, utilisation des communes par défaut');
            populateDefaultCommunes();
            return;
        }

        // Appel AJAX pour récupérer les communes configurées dans l'admin
        fetch(hticSimulateur.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'htic_get_communes_gaz',
                nonce: hticSimulateur.nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse AJAX communes:', data);

                if (data.success && data.data && data.data.communes) {
                    console.log('Communes récupérées:', data.data.communes);
                    populateCommunesSelect(data.data.communes);
                } else {
                    console.log('Pas de communes en base, utilisation des valeurs par défaut');
                    populateDefaultCommunes();
                }
            })
            .catch(error => {
                console.error('Erreur chargement communes:', error);
                populateDefaultCommunes();
            });
    }

    function populateCommunesSelect(communes) {
        console.log('Remplissage du select avec', communes.length, 'communes');

        if (!communeSelect) {
            console.error('Select commune non trouvé');
            return;
        }

        const groupeNaturel = document.getElementById('communes-naturel');
        const groupePropane = document.getElementById('communes-propane');

        if (!groupeNaturel || !groupePropane) {
            console.error('Groupes communes non trouvés');
            return;
        }

        // Vider les groupes existants
        groupeNaturel.innerHTML = '';
        groupePropane.innerHTML = '';

        // Trier les communes par type et nom
        const communesNaturel = communes.filter(c => c.type === 'naturel').sort((a, b) => a.nom.localeCompare(b.nom));
        const communesPropane = communes.filter(c => c.type === 'propane').sort((a, b) => a.nom.localeCompare(b.nom));

        // Ajouter les communes gaz naturel
        communesNaturel.forEach(commune => {
            const option = document.createElement('option');
            option.value = commune.nom;
            option.textContent = commune.nom;
            option.setAttribute('data-type', 'naturel');
            groupeNaturel.appendChild(option);
        });

        // Ajouter les communes gaz propane
        communesPropane.forEach(commune => {
            const option = document.createElement('option');
            option.value = commune.nom;
            option.textContent = commune.nom;
            option.setAttribute('data-type', 'propane');
            groupePropane.appendChild(option);
        });

        console.log('Select rempli:', communesNaturel.length, 'naturel +', communesPropane.length, 'propane');
    }

    function populateDefaultCommunes() {
        console.log('Utilisation des communes par défaut Excel');

        const defaultCommunes = [
            // Communes Gaz Naturel (données Excel exactes)
            { nom: 'AIRE SUR L\'ADOUR', type: 'naturel' },
            { nom: 'BARCELONNE DU GERS', type: 'naturel' },
            { nom: 'GAAS', type: 'naturel' },
            { nom: 'LABATUT', type: 'naturel' },
            { nom: 'LALUQUE', type: 'naturel' },
            { nom: 'MISSON', type: 'naturel' },
            { nom: 'POUILLON', type: 'naturel' },

            // Communes Gaz Propane (données Excel exactes)
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

    function handleCommuneSelection() {
        const selectedOption = communeSelect.options[communeSelect.selectedIndex];
        const selectedValue = selectedOption.value;

        console.log('Commune sélectionnée:', selectedValue);

        if (selectedValue === 'autre') {
            // Afficher la section "Autre commune"
            if (autreCommuneDetails) {
                autreCommuneDetails.style.display = 'block';
            }

            // Masquer l'info type de gaz
            if (typeGazInfo) {
                typeGazInfo.style.display = 'none';
            }
        } else if (selectedValue && selectedValue !== '') {
            // Masquer la section "Autre commune"
            if (autreCommuneDetails) {
                autreCommuneDetails.style.display = 'none';
            }

            // Afficher le type de gaz détecté
            showTypeGazInfo(selectedOption);
        } else {
            // Aucune commune sélectionnée
            if (autreCommuneDetails) {
                autreCommuneDetails.style.display = 'none';
            }
            if (typeGazInfo) {
                typeGazInfo.style.display = 'none';
            }
        }
    }

    function showTypeGazInfo(selectedOption) {
        const typeGaz = selectedOption.getAttribute('data-type');
        if (!typeGaz || !typeGazInfo || !typeGazText) return;

        const typeText = typeGaz === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
        const icon = typeGaz === 'naturel' ? '🌱' : '⛽';

        typeGazText.innerHTML = `${icon} <strong>${typeText}</strong> disponible dans cette commune`;
        typeGazInfo.style.display = 'block';
    }

    // ================================
    // GESTION DES ESTIMATIONS
    // ================================

    function updateConsumptionEstimates() {
        const nbPersonnes = parseInt(nbPersonnesInput?.value) || 5;

        // Eau chaude : 400 kWh/personne/an (valeur Excel K29)
        const eauChaudeConsommation = nbPersonnes * 400;
        const eauChaudeEl = document.getElementById('eau-chaude-estimation');
        if (eauChaudeEl) {
            eauChaudeEl.textContent = `${eauChaudeConsommation} kWh/an`;
        }

        // Cuisson : 50 kWh/personne/an (valeur Excel K28)
        const cuissonConsommation = nbPersonnes * 50;
        const cuissonEl = document.getElementById('cuisson-estimation');
        if (cuissonEl) {
            cuissonEl.textContent = `${cuissonConsommation} kWh/an`;
        }

        console.log('Estimations mises à jour:', {
            personnes: nbPersonnes,
            eauChaude: eauChaudeConsommation,
            cuisson: cuissonConsommation
        });
    }

    function toggleChauffageDetails() {
        const chauffageOui = document.getElementById('chauffage_oui');

        if (chauffageOui && chauffageDetails) {
            chauffageDetails.style.display = chauffageOui.checked ? 'block' : 'none';
        }
    }

    // ================================
    // CALCUL ET VALIDATION
    // ================================

    function calculateGazEstimation() {
        console.log('Début du calcul de l\'estimation gaz...');

        // Collecter toutes les données du formulaire
        const allData = collectAllFormData();

        // Stocker les données utilisateur
        formData = allData;

        console.log('Données utilisateur préparées:', allData);

        // Afficher le loading dans l'étape 6 actuelle
        showLoadingInCurrentStep();

        // Envoyer au calculateur
        sendDataToCalculator(allData, configData);
    }

    function collectAllFormData() {
        const data = {};

        // Collecter toutes les données du formulaire
        document.querySelectorAll('#simulateur-gaz-residentiel input, #simulateur-gaz-residentiel select, #simulateur-gaz-residentiel textarea').forEach(function (field) {
            const name = field.getAttribute('name');
            const type = field.getAttribute('type');

            if (!name) return;

            const cleanName = name.replace('[]', '');

            if (type === 'radio') {
                if (field.checked) {
                    data[cleanName] = field.value;
                }
            } else if (type === 'checkbox') {
                if (!data[cleanName]) {
                    data[cleanName] = [];
                }

                if (field.checked) {
                    const value = field.value;
                    if (!data[cleanName].includes(value)) {
                        data[cleanName].push(value);
                    }
                }
            } else {
                data[cleanName] = field.value;
            }
        });

        return data;
    }

    function sendDataToCalculator(userData, configData) {
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'gaz-residentiel',
            user_data: JSON.stringify(userData),
            config_data: JSON.stringify(configData)
        };

        // Ajouter le nonce selon la méthode de elec-residentiel
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
                hideLoadingInCurrentStep();

                if (response.success) {
                    calculationResults = response.data;

                    // Afficher les résultats dans l'étape 6 actuelle (ne pas changer d'étape)
                    displayResults(response.data);
                    setupEmailActions();
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

                hideLoadingInCurrentStep();

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

    // ================================
    // AFFICHAGE DES RÉSULTATS
    // ================================

    function displayResults(results) {
        console.log('Affichage des résultats:', results);

        const calculContainer = document.getElementById('calcul-container');
        if (!calculContainer) {
            console.error('Container calcul-container non trouvé');
            return;
        }

        // Créer l'affichage des résultats
        calculContainer.innerHTML = `
            <div class="results-summary">
                <div class="result-card main-result">
                    <div class="result-icon">🔥</div>
                    <h3>Votre estimation gaz</h3>
                    <div class="big-number">${results.consommation_totale || 0} <span>kWh/an</span></div>
                    <div class="result-price">${results.cout_annuel_ttc || 0}€ <span>/an TTC</span></div>
                </div>
                
                <div class="result-breakdown">
                    <h4>Répartition de votre consommation</h4>
                    <div class="breakdown-items">
                        ${results.repartition ? Object.entries(results.repartition).map(([key, value]) => `
                            <div class="breakdown-item">
                                <span class="breakdown-label">${formatUsageLabel(key)}</span>
                                <span class="breakdown-value">${value} kWh/an</span>
                            </div>
                        `).join('') : ''}
                    </div>
                </div>
                
                <div class="result-details">
                    <h4>Détails de votre estimation</h4>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Type de gaz</span>
                            <span class="detail-value">${results.type_gaz === 'naturel' ? '🌱 Gaz naturel' : '⛽ Gaz propane'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Abonnement annuel</span>
                            <span class="detail-value">${results.cout_abonnement || 0}€ HT</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Consommation</span>
                            <span class="detail-value">${results.cout_consommation || 0}€ HT</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Prix du kWh</span>
                            <span class="detail-value">${results.prix_kwh || 0}€</span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
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
    }

    function formatUsageLabel(key) {
        const labels = {
            'chauffage': '🔥 Chauffage',
            'eau_chaude': '🚿 Eau chaude',
            'cuisson': '🍳 Cuisson'
        };
        return labels[key] || key;
    }

    function displayError(message) {
        const calculContainer = document.getElementById('calcul-container');
        if (calculContainer) {
            calculContainer.innerHTML = `
                <div class="error-state">
                    <div class="error-icon">❌</div>
                    <h3>Erreur lors du calcul</h3>
                    <p>${message}</p>
                    <div class="error-actions">
                        <button class="btn btn-primary" onclick="location.reload()">🔄 Recharger</button>
                        <button class="btn btn-secondary" onclick="goToStep(4)">← Retour au formulaire</button>
                    </div>
                </div>
            `;
        }
    }

    // ================================
    // ENVOI EMAIL
    // ================================

    function setupEmailActions() {
        // Bouton envoyer par email
        $(document).on('click', '#btn-send-email', function () {
            sendResultsByEmail();
        });
    }

    function sendResultsByEmail() {
        console.log('Envoi des résultats par email');

        if (!calculationResults || !formData) {
            showError('Aucun calcul disponible. Veuillez refaire le calcul.');
            return;
        }

        // Préparer les données pour l'email
        const emailData = {
            action: 'htic_send_simulation_email',
            type: 'gaz-residentiel',
            results: calculationResults,
            client: {
                civilite: formData.civilite,
                nom: formData.nom,
                prenom: formData.prenom,
                email: formData.email,
                telephone: formData.telephone,
                code_postal: formData.code_postal,
                commentaires: formData.commentaires
            },
            simulation: {
                commune: formData.commune,
                type_gaz: formData.type_gaz || 'naturel',
                superficie: formData.superficie,
                nb_personnes: formData.nb_personnes,
                type_logement: formData.type_logement,
                chauffage_gaz: formData.chauffage_gaz,
                isolation: formData.isolation,
                eau_chaude: formData.eau_chaude,
                cuisson: formData.cuisson,
                offre: formData.offre
            }
        };

        // Ajouter le nonce
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce) {
            emailData.nonce = hticSimulateur.nonce;
        }

        // État de chargement
        const $btn = $('#btn-send-email');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner"></span> Envoi en cours...');

        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl) {
            ajaxUrl = hticSimulateur.ajaxUrl;
        }

        // Envoi AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: emailData,
            success: function (response) {
                if (response.success) {
                    // Afficher la confirmation
                    $('#email-confirmation').slideDown();
                    $('#email-display').text(formData.email);

                    // Masquer après 5 secondes
                    setTimeout(() => {
                        $('#email-confirmation').slideUp();
                    }, 5000);

                    showNotification('✅ Email envoyé avec succès !', 'success');
                } else {
                    showNotification('❌ Erreur lors de l\'envoi : ' + (response.data || 'Erreur inconnue'), 'error');
                }
            },
            error: function () {
                showNotification('❌ Erreur de connexion', 'error');
            },
            complete: function () {
                // Restaurer le bouton
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    // ================================
    // UTILITAIRES
    // ================================

    function showError(message) {
        console.error('Erreur formulaire:', message);

        const errorContainer = document.getElementById('error-container');
        const errorText = document.querySelector('.error-text');

        if (errorContainer && errorText) {
            errorText.textContent = message;
            errorContainer.style.display = 'block';

            // Masquer après 5 secondes
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        } else {
            // Fallback avec alert
            alert(message);
        }
    }

    function showLoadingState() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }

        // Afficher un message dans le container
        const calculContainer = document.getElementById('calcul-container');
        if (calculContainer) {
            calculContainer.innerHTML = `
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Calcul de votre estimation gaz en cours...</p>
                    <small>Traitement des données par le calculateur HTIC...</small>
                </div>
            `;
        }
    }

    function showLoadingInCurrentStep() {
        // Désactiver le bouton de calcul et montrer le loading
        const btnCalculate = document.querySelector('.btn-calculate, #btn-calculate');
        if (btnCalculate) {
            btnCalculate.disabled = true;
            btnCalculate.innerHTML = '<span class="spinner"></span> Calcul en cours...';
        }

        // Optionnel: afficher un overlay de loading
        showLoadingState();
    }

    function hideLoadingInCurrentStep() {
        // Réactiver le bouton de calcul
        const btnCalculate = document.querySelector('.btn-calculate, #btn-calculate');
        if (btnCalculate) {
            btnCalculate.disabled = false;
            btnCalculate.innerHTML = '<span class="btn-icon">🔍</span> Calculer mon estimation';
        }

        // Masquer l'overlay
        hideLoadingState();
    }

    function hideLoadingState() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

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

    function goToStep(stepNumber) {
        if (stepNumber >= 1 && stepNumber <= totalSteps) {
            hideStep(currentStep);
            currentStep = stepNumber;
            showStep(currentStep);
            updateProgressBar();
            updateNavigation();
        }
    }

    // ================================
    // FONCTIONS PUBLIQUES
    // ================================

    // Exposer certaines fonctions globalement si nécessaire
    window.hticGazResidentiel = {
        calculateEstimation: calculateGazEstimation,
        updateEstimates: updateConsumptionEstimates,
        goToStep: goToStep,
        validateForm: function () {
            return validateCurrentStep();
        },
        getResults: function () {
            return calculationResults;
        },
        getUserData: function () {
            return formData;
        },
        getCurrentData: () => formData,
        getAllData: collectAllFormData,
        getConfigData: () => configData,
        getCurrentStep: () => currentStep
    };

    console.log('Simulateur gaz résidentiel initialisé');
});