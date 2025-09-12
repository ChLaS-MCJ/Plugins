// elec-residentiel.js - JavaScript complet pour collecte de données et calcul

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
        setupChauffageLogic();
        setupSimulationsRapides(); // Gestion des profils rapides
    }

    // Chargement configuration
    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('✅ Configuration chargée:', Object.keys(configData).length, 'paramètres');
            } catch (e) {
                console.error('❌ Erreur configuration:', e);
                configData = {};
            }
        }
    }

    // ===============================
    // SIMULATIONS RAPIDES
    // ===============================

    function setupSimulationsRapides() {
        $('.profil-rapide-card').on('click', function () {
            const profil = $(this).data('profil');
            lancerSimulationRapide(profil);
        });
    }

    // Définition des profils prédéfinis
    function getProfilData(profil) {
        const profils = {
            'petit-logement': {
                // Données utilisateur COMPLÈTES
                type_logement: 'appartement',
                surface: '50',
                nb_personnes: '2',
                isolation: '1980_2000',
                type_chauffage: 'convecteurs', // ← AJOUTÉ
                type_cuisson: 'induction',     // ← AJOUTÉ  
                electromenagers: ['lave_linge', 'refrigerateur', 'four'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'non',
                equipements_speciaux: [],
                preference_tarif: 'indifferent', // ← AJOUTÉ
                // Métadonnées
                nom: 'Petit logement',
                description: 'Appartement 50m² • 1-2 personnes • Chauffage électrique'
            },

            'logement-moyen': {
                // Données utilisateur COMPLÈTES
                type_logement: 'maison',
                surface: '100',
                nb_personnes: '4',
                isolation: 'apres_2000',
                type_chauffage: 'inertie',     // ← AJOUTÉ
                type_cuisson: 'induction',     // ← AJOUTÉ
                electromenagers: ['lave_linge', 'seche_linge', 'refrigerateur', 'lave_vaisselle', 'four', 'congelateur'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'non',
                equipements_speciaux: [],
                preference_tarif: 'hc',        // ← AJOUTÉ
                // Métadonnées
                nom: 'Logement moyen',
                description: 'Maison 100m² • 3-4 personnes • Tout électrique'
            },

            'grand-logement': {
                // Données utilisateur COMPLÈTES
                type_logement: 'maison',
                surface: '150',
                nb_personnes: '5',
                isolation: 'renovation',
                type_chauffage: 'pac',         // ← AJOUTÉ
                type_cuisson: 'induction',     // ← AJOUTÉ
                electromenagers: ['lave_linge', 'seche_linge', 'refrigerateur', 'lave_vaisselle', 'four', 'congelateur', 'cave_a_vin'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'simple',
                equipements_speciaux: ['spa_jacuzzi', 'voiture_electrique'],
                preference_tarif: 'hc',        // ← AJOUTÉ
                // Métadonnées
                nom: 'Grand logement',
                description: 'Maison 150m² • 4-5 personnes • Tout électrique + Piscine'
            }
        };

        return profils[profil] || null;
    }

    // Fonction pour lancer une simulation rapide
    function lancerSimulationRapide(profil) {
        const profilData = getProfilData(profil);

        if (!profilData) {
            console.error('Profil non trouvé:', profil);
            return;
        }

        console.log('🚀 Lancement simulation rapide:', profilData.nom);

        // Afficher l'état de chargement sur le bouton
        const $button = $(`.profil-rapide-card[data-profil="${profil}"]`);
        $button.addClass('loading');

        // Remplir le formulaire avec les données du profil (pour debug/historique)
        remplirFormulaireAvecProfil(profilData);

        // Aller directement à l'étape résultats
        currentStep = 7;
        showStep(7);
        updateProgress();
        updateNavigation();

        // Afficher l'état de chargement dans les résultats
        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul rapide en cours...</p>
                <small>Simulation : ${profilData.nom}</small>
            </div>
        `);

        // Préparer les données (sans les métadonnées)
        const userData = { ...profilData };
        delete userData.nom;
        delete userData.description;

        // Envoyer directement au calculateur
        sendDataToCalculatorRapide(userData, configData, profilData.nom);

        // Masquer l'état de chargement du bouton après un délai
        setTimeout(() => {
            $button.removeClass('loading');
        }, 2000);
    }

    // Version spécialisée pour les simulations rapides
    function sendDataToCalculatorRapide(userData, configData, nomProfil) {
        // Préparer les données pour le calculateur
        const dataToSend = {
            action: 'htic_calculate_estimation',
            type: 'elec-residentiel',
            user_data: userData,
            config_data: configData,
            simulation_rapide: true,
            profil_nom: nomProfil
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

        console.log('📤 Envoi simulation rapide:', dataToSend);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: dataToSend,
            timeout: 30000,
            success: function (response) {
                console.log('📥 Réponse simulation rapide:', response);

                if (response.success) {
                    displayResultsRapide(response.data, nomProfil);
                } else {
                    displayError('Erreur lors du calcul rapide: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX simulation rapide:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                let errorMessage = 'Erreur de connexion lors du calcul rapide';

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

    // Affichage spécialisé pour simulations rapides
    function displayResultsRapide(results, nomProfil) {
        console.log('🎯 Affichage résultats rapides:', results);

        // Vérifier que nous avons les données nécessaires
        if (!results.totaux || !results.consommations || !results.tarifs) {
            displayError('Données de résultats incomplètes pour la simulation rapide');
            return;
        }

        const resultsHtml = `
            <div class="results-summary">
                <!-- Badge simulation rapide -->
                <div class="simulation-rapide-badge">
                    <span class="badge-icon">🚀</span>
                    <span>Simulation rapide : ${nomProfil}</span>
                </div>
                
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">⚡</div>
                    <h3>Estimation pour : ${nomProfil}</h3>
                    <div class="big-number">${Math.round(results.totaux.consommation_totale).toLocaleString()} <span>kWh/an</span></div>
                    <p>Puissance recommandée : <strong>${results.totaux.puissance_recommandee} kVA</strong></p>
                    <small>Basé sur un profil type - Utilisez le formulaire personnalisé pour plus de précision</small>
                </div>
                
                <!-- Comparaison des tarifs -->
                <div class="tarifs-comparison">
                    <h3>💰 Comparaison des tarifs</h3>
                    <div class="tarifs-grid">
                        <div class="tarif-card ${results.tarifs.recommande === 'base' ? 'recommended' : ''}">
                            <h4>Tarif BASE</h4>
                            <div class="tarif-prix">${Math.round(results.tarifs.base.total_annuel)}€<span>/an</span></div>
                            <div class="tarif-mensuel">${Math.round(results.tarifs.base.total_mensuel)}€/mois</div>
                            ${results.tarifs.recommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                        </div>
                        <div class="tarif-card ${results.tarifs.recommande === 'hc' ? 'recommended' : ''}">
                            <h4>Heures Creuses</h4>
                            <div class="tarif-prix">${Math.round(results.tarifs.hc.total_annuel)}€<span>/an</span></div>
                            <div class="tarif-mensuel">${Math.round(results.tarifs.hc.total_mensuel)}€/mois</div>
                            ${results.tarifs.recommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                        </div>
                    </div>
                    <div class="economies">
                        <p>💡 <strong>Économies potentielles :</strong> ${Math.round(results.tarifs.economies)}€/an en choisissant le meilleur tarif !</p>
                    </div>
                </div>
                
                <!-- Répartition simplifiée -->
                <div class="repartition-conso">
                    <h3>📊 Répartition de la consommation</h3>
                    <div class="repartition-details">
                        ${results.consommations.chauffage > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #ef4444;"></span>
                            <span>Chauffage : ${Math.round(results.consommations.chauffage).toLocaleString()} kWh/an</span>
                        </div>` : ''}
                        ${results.consommations.chauffe_eau > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #3b82f6;"></span>
                            <span>Chauffe-eau : ${Math.round(results.consommations.chauffe_eau).toLocaleString()} kWh/an</span>
                        </div>` : ''}
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #10b981;"></span>
                            <span>Électroménagers : ${Math.round(results.consommations.electromenagers).toLocaleString()} kWh/an</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #8b5cf6;"></span>
                            <span>Multimédia : ${Math.round(results.consommations.multimedia).toLocaleString()} kWh/an</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #f59e0b;"></span>
                            <span>Éclairage : ${Math.round(results.consommations.eclairage).toLocaleString()} kWh/an</span>
                        </div>
                        ${results.consommations.equipements_supplementaires > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #06b6d4;"></span>
                            <span>Équipements spéciaux : ${Math.round(results.consommations.equipements_supplementaires).toLocaleString()} kWh/an</span>
                        </div>` : ''}
                    </div>
                </div>
                
                <!-- Recommandations -->
                ${results.recommandations && results.recommandations.length > 0 ? `
                <div class="recommandations-section">
                    <h3>💡 Nos recommandations</h3>
                    <div class="recommandations-list">
                        ${results.recommandations.map(rec => `<div class="recommandation-item">${rec}</div>`).join('')}
                    </div>
                </div>` : ''}
                
                <!-- Actions -->
                <div class="results-actions">
                    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer</button>
                    <button class="btn btn-secondary" id="btn-simulation-personnalisee">📝 Simulation personnalisée</button>
                    <button class="btn btn-outline" id="btn-autre-profil">🔄 Autre profil type</button>
                </div>
            </div>
        `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        // Gestionnaires pour les boutons d'actions rapides
        $('#btn-simulation-personnalisee').on('click', function () {
            // Retourner au début du formulaire
            restartSimulation();
            // Scroll vers le formulaire
            $('html, body').animate({
                scrollTop: $('.progress-container').offset().top - 50
            }, 600);
        });

        $('#btn-autre-profil').on('click', function () {
            // Retourner au début avec focus sur les profils rapides
            restartSimulation();
            $('html, body').animate({
                scrollTop: $('.simulations-rapides').offset().top - 50
            }, 600);
        });
    }

    // Fonction pour remplir le formulaire (utile pour debug et historique)
    function remplirFormulaireAvecProfil(profilData) {
        // Remplir les champs radio
        if (profilData.type_logement) {
            $(`input[name="type_logement"][value="${profilData.type_logement}"]`).prop('checked', true);
        }
        if (profilData.isolation) {
            $(`input[name="isolation"][value="${profilData.isolation}"]`).prop('checked', true);
        }
        if (profilData.chauffage_electrique) {
            $(`input[name="chauffage_electrique"][value="${profilData.chauffage_electrique}"]`).prop('checked', true);
        }
        if (profilData.type_chauffage_elec) {
            $(`input[name="type_chauffage_elec"][value="${profilData.type_chauffage_elec}"]`).prop('checked', true);
        }
        if (profilData.cuisson_electrique) {
            $(`input[name="cuisson_electrique"][value="${profilData.cuisson_electrique}"]`).prop('checked', true);
        }
        if (profilData.eau_chaude) {
            $(`input[name="eau_chaude"][value="${profilData.eau_chaude}"]`).prop('checked', true);
        }
        if (profilData.type_eclairage) {
            $(`input[name="type_eclairage"][value="${profilData.type_eclairage}"]`).prop('checked', true);
        }
        if (profilData.piscine) {
            $(`input[name="piscine"][value="${profilData.piscine}"]`).prop('checked', true);
        }

        // Remplir les champs de saisie
        if (profilData.surface) {
            $('#surface').val(profilData.surface);
        }
        if (profilData.nb_personnes) {
            $('#nb_personnes').val(profilData.nb_personnes);
        }

        // Remplir les checkboxes électroménagers
        $('input[name="electromenagers[]"]').prop('checked', false); // Tout décocher d'abord
        if (profilData.electromenagers && Array.isArray(profilData.electromenagers)) {
            profilData.electromenagers.forEach(function (electromenager) {
                $(`input[name="electromenagers[]"][value="${electromenager}"]`).prop('checked', true);
            });
        }

        // Remplir les checkboxes équipements spéciaux
        $('input[name="equipements_speciaux[]"]').prop('checked', false); // Tout décocher d'abord
        if (profilData.equipements_speciaux && Array.isArray(profilData.equipements_speciaux)) {
            profilData.equipements_speciaux.forEach(function (equipement) {
                $(`input[name="equipements_speciaux[]"][value="${equipement}"]`).prop('checked', true);
            });
        }

        console.log('📝 Formulaire rempli avec le profil:', profilData.nom);
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
        // Chauffage électrique obligatoire
        const chauffageElec = stepElement.find('input[name="chauffage_electrique"]:checked');
        if (!chauffageElec.length) {
            return false;
        }

        // Si chauffage électrique = oui, vérifier le type
        if (chauffageElec.val() === 'oui') {
            const typeChauffage = stepElement.find('input[name="type_chauffage_elec"]:checked');
            if (!typeChauffage.length) {
                return false;
            }
        }

        return true;
    }

    function validateStep3(stepElement) {
        // Cuisson électrique obligatoire
        const cuissonElec = stepElement.find('input[name="cuisson_electrique"]:checked');
        return cuissonElec.length > 0;
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

        console.log('💾 Données sauvegardées étape', currentStep, ':', formData);
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

        console.log('📋 Données complètes collectées:', formData);
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

        console.log('📤 Envoi vers le calculateur:', dataToSend);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: dataToSend,
            timeout: 30000, // 30 secondes
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
        console.log('🔍 RÉSULTATS DÉTAILLÉS REÇUS:', results);

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
                    <div class="tarif-card">
                        <h4>Tarif BASE</h4>
                        <div class="tarif-prix">${results.tarifs.base.total_annuel}€<span>/an</span></div>
                        <div class="tarif-mensuel">${results.tarifs.base.total_mensuel}€/mois</div>
                    </div>
                    <div class="tarif-card recommended">
                        <h4>Heures Creuses</h4>
                        <div class="tarif-prix">${results.tarifs.hc.total_annuel}€<span>/an</span></div>
                        <div class="tarif-mensuel">${results.tarifs.hc.total_mensuel}€/mois</div>
                        <span class="recommended-badge">⭐ Recommandé</span>
                    </div>
                </div>
                <div class="economies">
                    <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${Math.abs(results.tarifs.base.total_annuel - results.tarifs.hc.total_annuel)}€/an en choisissant le bon tarif !</p>
                </div>
            </div>
            
            <!-- RÉPARTITION DÉTAILLÉE AVEC CALCULS -->
            <div class="repartition-detaillee">
                <h3>🧮 Détail complet des calculs de consommation</h3>
                
                ${generateDetailedConsumptionHTML(results)}
            </div>
            
            <!-- Récapitulatif complet -->
            ${generateCompleteRecapHTML(results.recap, results.details_calcul)}
            
            <!-- Actions -->
            <div class="results-actions">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer les résultats</button>
                <button class="btn btn-secondary" onclick="downloadPDF()">📄 Télécharger PDF</button>
                <button class="btn btn-outline" onclick="showDebugInfo()">🔧 Voir détails techniques</button>
            </div>
        </div>
    `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);
    }

    function generateDetailedConsumptionHTML(results) {
        const details = results.details_calcul || {};
        const repartition = results.repartition || {};

        let html = '<div class="consumption-breakdown">';

        // 1. CHAUFFAGE (même si 0)
        html += generateConsumptionCategory(
            'chauffage',
            '🔥 Chauffage électrique',
            repartition.chauffage || 0,
            details.chauffage || {}
        );

        // 2. EAU CHAUDE
        html += generateConsumptionCategory(
            'eau_chaude',
            '💧 Chauffe-eau électrique',
            repartition.eau_chaude || 0,
            details.eau_chaude || {}
        );

        // 3. ÉLECTROMÉNAGERS
        html += generateConsumptionCategory(
            'electromenagers',
            '🏠 Électroménagers',
            repartition.electromenagers || 0,
            details.electromenagers || {}
        );

        // 4. CUISSON
        html += generateConsumptionCategory(
            'cuisson',
            '🍳 Cuisson électrique',
            repartition.cuisson || 0,
            details.cuisson || {}
        );

        // 5. ÉCLAIRAGE  
        html += generateConsumptionCategory(
            'eclairage',
            '💡 Éclairage',
            repartition.eclairage || 0,
            details.eclairage || {}
        );

        // 6. MULTIMÉDIA
        html += generateConsumptionCategory(
            'multimedia',
            '📺 TV/PC/Box',
            repartition.multimedia || repartition.tv_pc_box || 0,
            details.multimedia || details.tv_pc_box || {}
        );

        // 7. ÉQUIPEMENTS SPÉCIAUX
        html += generateSpecialEquipmentHTML(results);

        // 8. TOTAL
        html += `
        <div class="consumption-item total-item">
            <div class="consumption-header">
                <div class="consumption-title">
                    <span class="consumption-icon">⚡</span>
                    <h4>TOTAL GÉNÉRAL</h4>
                </div>
                <div class="consumption-value total-value">
                    ${results.consommation_annuelle.toLocaleString()} kWh/an
                </div>
            </div>
        </div>
    `;

        html += '</div>';
        return html;
    }

    // Génération d'une catégorie de consommation
    function generateConsumptionCategory(key, title, totalKwh, details) {
        const isZero = totalKwh === 0;

        let html = `
        <div class="consumption-item ${isZero ? 'zero-consumption' : ''}">
            <div class="consumption-header" onclick="toggleConsumptionDetails('${key}')">
                <div class="consumption-title">
                    <span class="consumption-icon">${getConsumptionIcon(key)}</span>
                    <h4>${title}</h4>
                    ${isZero ? '<span class="zero-badge">Non utilisé</span>' : ''}
                </div>
                <div class="consumption-value">
                    ${totalKwh.toLocaleString()} kWh/an
                    <span class="toggle-icon">▼</span>
                </div>
            </div>
            <div class="consumption-details" id="details-${key}">
                ${generateCategoryDetails(key, details, totalKwh)}
            </div>
        </div>
    `;

        return html;
    }

    // Génération des détails d'une catégorie
    function generateCategoryDetails(category, details, total) {
        if (!details || Object.keys(details).length === 0) {
            return `
            <div class="detail-row">
                <span class="detail-label">Calcul</span>
                <span class="detail-calculation">Non calculé ou inclus dans autre poste</span>
            </div>
        `;
        }

        let html = '';

        // Afficher chaque élément de calcul
        Object.entries(details).forEach(([key, data]) => {
            if (typeof data === 'object' && data !== null) {
                html += `
                <div class="detail-row">
                    <span class="detail-label">${formatDetailLabel(key)}</span>
                    <span class="detail-calculation">
                        ${data.base_kwh || 0} kWh × ${data.coefficient || 1} (coeff.) = ${data.final_kwh || 0} kWh/an
                    </span>
                </div>
            `;
            } else {
                html += `
                <div class="detail-row">
                    <span class="detail-label">${formatDetailLabel(key)}</span>
                    <span class="detail-value">${data}</span>
                </div>
            `;
            }
        });

        // Sous-total si pertinent
        if (total > 0) {
            html += `
            <div class="detail-row subtotal-row">
                <span class="detail-label"><strong>Sous-total ${category}</strong></span>
                <span class="detail-value"><strong>${total.toLocaleString()} kWh/an</strong></span>
            </div>
        `;
        }

        return html;
    }

    // Génération HTML pour équipements spéciaux
    function generateSpecialEquipmentHTML(results) {
        const special = results.repartition.equipements_speciaux || {};
        const details = results.details_calcul.equipements_speciaux || {};

        let totalSpecial = 0;
        Object.values(special).forEach(val => totalSpecial += (typeof val === 'number' ? val : 0));

        let html = `
        <div class="consumption-item ${totalSpecial === 0 ? 'zero-consumption' : ''}">
            <div class="consumption-header" onclick="toggleConsumptionDetails('equipements_speciaux')">
                <div class="consumption-title">
                    <span class="consumption-icon">🔌</span>
                    <h4>Équipements spéciaux</h4>
                    ${totalSpecial === 0 ? '<span class="zero-badge">Aucun</span>' : ''}
                </div>
                <div class="consumption-value">
                    ${totalSpecial.toLocaleString()} kWh/an
                    <span class="toggle-icon">▼</span>
                </div>
            </div>
            <div class="consumption-details" id="details-equipements_speciaux">
    `;

        // Piscine
        const piscineKwh = special.piscine || 0;
        html += `
        <div class="detail-row">
            <span class="detail-label">🏊 Piscine</span>
            <span class="detail-calculation">${piscineKwh.toLocaleString()} kWh/an</span>
        </div>
    `;

        // Spa/Jacuzzi
        const spaKwh = special.spa_jacuzzi || 0;
        html += `
        <div class="detail-row">
            <span class="detail-label">🛁 Spa/Jacuzzi</span>
            <span class="detail-calculation">${spaKwh.toLocaleString()} kWh/an</span>
        </div>
    `;

        // Voiture électrique
        const voitureKwh = special.voiture_electrique || 0;
        html += `
        <div class="detail-row">
            <span class="detail-label">🚗 Voiture électrique</span>
            <span class="detail-calculation">${voitureKwh.toLocaleString()} kWh/an</span>
        </div>
    `;

        // Aquarium
        const aquariumKwh = special.aquarium || 0;
        html += `
        <div class="detail-row">
            <span class="detail-label">🐠 Aquarium</span>
            <span class="detail-calculation">${aquariumKwh.toLocaleString()} kWh/an</span>
        </div>
    `;

        // Climatiseur mobile
        const climKwh = special.climatiseur_mobile || 0;
        html += `
        <div class="detail-row">
            <span class="detail-label">🌬️ Climatiseur mobile</span>
            <span class="detail-calculation">${climKwh.toLocaleString()} kWh/an</span>
        </div>
    `;

        if (totalSpecial > 0) {
            html += `
            <div class="detail-row subtotal-row">
                <span class="detail-label"><strong>Sous-total équipements</strong></span>
                <span class="detail-value"><strong>${totalSpecial.toLocaleString()} kWh/an</strong></span>
            </div>
        `;
        }

        html += '</div></div>';

        return html;
    }

    // Génération du récapitulatif complet
    function generateCompleteRecapHTML(recap, detailsCalcul) {
        return `
        <div class="recap-complet">
            <h3>📋 Récapitulatif complet et paramètres utilisés</h3>
            
            <!-- Informations logement -->
            <div class="recap-section">
                <h4>🏠 Votre logement</h4>
                <div class="recap-grid">
                    <div class="recap-item">
                        <span class="recap-label">Type de logement :</span>
                        <span class="recap-value">${getLogementLabel(recap.type_logement)}</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Surface habitable :</span>
                        <span class="recap-value">${recap.surface} m²</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Nombre de personnes :</span>
                        <span class="recap-value">${recap.nb_personnes} personne${recap.nb_personnes > 1 ? 's' : ''}</span>
                    </div>
                </div>
            </div>
            
            <!-- Chauffage -->
            <div class="recap-section">
                <h4>🔥 Chauffage et isolation</h4>
                <div class="recap-grid">
                    <div class="recap-item">
                        <span class="recap-label">Mode de chauffage :</span>
                        <span class="recap-value">${getHeatingLabel(recap.type_chauffage)}</span>
                    </div>
                    ${recap.isolation ? `
                    <div class="recap-item">
                        <span class="recap-label">Isolation :</span>
                        <span class="recap-value">${getIsolationLabel(recap.isolation)}</span>
                    </div>
                    ` : ''}
                    ${detailsCalcul?.chauffage?.consommation_m2 ? `
                    <div class="recap-item">
                        <span class="recap-label">Consommation chauffage :</span>
                        <span class="recap-value">${detailsCalcul.chauffage.consommation_m2} kWh/m²/an</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Équipements -->
            <div class="recap-section">
                <h4>🔌 Vos équipements</h4>
                <div class="recap-grid">
                    <div class="recap-item">
                        <span class="recap-label">Eau chaude :</span>
                        <span class="recap-value">${recap.eau_chaude === 'oui' ? '💧 Électrique' : '🚫 Autre énergie'}</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Cuisson :</span>
                        <span class="recap-value">${getCuissonLabel(recap.type_cuisson)}</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Éclairage :</span>
                        <span class="recap-value">${getEclairageLabel(recap.type_eclairage)}</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Piscine :</span>
                        <span class="recap-value">${getPiscineLabel(recap.piscine)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Électroménagers sélectionnés -->
            ${recap.electromenagers && recap.electromenagers.length > 0 ? `
            <div class="recap-section">
                <h4>🏠 Électroménagers sélectionnés</h4>
                <div class="electromenagers-list">
                    ${recap.electromenagers.map(eq => `
                        <span class="electromenager-tag">${getElectroLabel(eq)}</span>
                    `).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Équipements spéciaux -->
            ${recap.equipements_speciaux && recap.equipements_speciaux.length > 0 ? `
            <div class="recap-section">
                <h4>⚡ Équipements spéciaux</h4>
                <div class="equipements-list">
                    ${recap.equipements_speciaux.map(eq => `
                        <span class="equipement-tag">${getEquipementLabel(eq)}</span>
                    `).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Coefficients appliqués -->
            ${detailsCalcul?.coefficients ? `
            <div class="recap-section">
                <h4>🧮 Coefficients appliqués</h4>
                <div class="recap-grid">
                    <div class="recap-item">
                        <span class="recap-label">Coefficient logement :</span>
                        <span class="recap-value">${detailsCalcul.coefficients.logement || 1}</span>
                    </div>
                    <div class="recap-item">
                        <span class="recap-label">Coefficient personnes :</span>
                        <span class="recap-value">${detailsCalcul.coefficients.personnes || 1}</span>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    }

    // Fonctions utilitaires de formatage
    function getConsumptionIcon(category) {
        const icons = {
            'chauffage': '🔥',
            'eau_chaude': '💧',
            'electromenagers': '🏠',
            'cuisson': '🍳',
            'eclairage': '💡',
            'multimedia': '📺',
            'equipements_speciaux': '🔌'
        };
        return icons[category] || '⚡';
    }

    function formatDetailLabel(key) {
        const labels = {
            'base_kwh': 'Consommation de base',
            'coefficient': 'Coefficient multiplicateur',
            'final_kwh': 'Consommation finale',
            'nb_personnes': 'Nombre de personnes',
            'surface': 'Surface',
            'type_logement': 'Type de logement'
        };
        return labels[key] || key.replace('_', ' ');
    }

    // Nouvelles fonctions de label
    function getIsolationLabel(isolation) {
        const labels = {
            'avant_1980': '❌ Faible (avant 1980)',
            '1980_2000': '⚠️ Moyenne (1980-2000)',
            'apres_2000': '✅ Bonne (après 2000)',
            'renovation': '⭐ Excellente (rénovée)'
        };
        return labels[isolation] || isolation;
    }

    function getCuissonLabel(cuisson) {
        const labels = {
            'induction': '⚡ Induction électrique',
            'vitroceramique': '🔥 Vitrocéramique',
            'autre': '🚫 Autre (gaz, mixte...)'
        };
        return labels[cuisson] || cuisson;
    }

    function getEclairageLabel(eclairage) {
        const labels = {
            'led': '💡 LED (basse consommation)',
            'incandescence_halogene': '🔆 Incandescence/Halogène'
        };
        return labels[eclairage] || eclairage;
    }

    function getPiscineLabel(piscine) {
        const labels = {
            'simple': '🏊 Piscine simple',
            'chauffee': '🌊 Piscine chauffée',
            'non': '🚫 Pas de piscine'
        };
        return labels[piscine] || piscine;
    }

    function getElectroLabel(electro) {
        const labels = {
            'lave_linge': '👕 Lave-linge',
            'seche_linge': '🌪️ Sèche-linge',
            'refrigerateur': '🧊 Réfrigérateur',
            'congelateur': '❄️ Congélateur',
            'lave_vaisselle': '🍽️ Lave-vaisselle',
            'four': '🔥 Four électrique',
            'cave_a_vin': '🍷 Cave à vin'
        };
        return labels[electro] || electro;
    }

    function getEquipementLabel(equipement) {
        const labels = {
            'spa_jacuzzi': '🛁 Spa/Jacuzzi',
            'voiture_electrique': '🚗 Voiture électrique',
            'aquarium_petit': '🐠 Petit aquarium',
            'aquarium_grand': '🐟 Grand aquarium',
            'climatiseur_mobile': '🌬️ Climatiseur mobile'
        };
        return labels[equipement] || equipement;
    }

    // Fonction pour toggle les détails
    function toggleConsumptionDetails(categoryId) {
        const details = $(`#details-${categoryId}`);
        const icon = details.siblings('.consumption-header').find('.toggle-icon');

        details.slideToggle(300);
        icon.text(details.is(':visible') ? '▲' : '▼');
    }

    // Fonction pour afficher les infos de debug
    function showDebugInfo() {
        const debugData = window.hticSimulateurDebug ? window.hticSimulateurDebug.formData() : 'Debug non disponible';

        alert('Données de debug:\n\n' + JSON.stringify(debugData, null, 2));
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
            goToStep(6); // Retourner à la dernière étape
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

    function getIsolationLabel(type) {
        const labels = {
            'avant_1980': '🔴 Faible (avant 1980)',
            '1980_2000': '🟠 Moyenne (1980-2000)',
            'apres_2000': '🟢 Bonne (après 2000)',
            'renovation': '🔵 Excellente (rénovée)'
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

    // Debug
    if (window.location.search.includes('debug=1')) {
        console.log('🐛 Mode debug activé');
        window.hticDebug = {
            formData: () => formData,
            configData: () => configData,
            collectData: collectAllFormData,
            step: () => currentStep,
            calculate: () => calculateResults()
        };
    }

});