// formulaires/elec-residentiel/elec-residentiel.js - Version mise à jour avec TEMPO et nouveaux chauffages

jQuery(document).ready(function ($) {

    // Variables globales
    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let configData = {};

    // Initialisation
    init();

    function init() {
        loadConfigData();
        setupStepNavigation();
        setupFormValidation();
        setupProgressTracking();
        setupCalculation();
        setupEquipmentCalculator();

        console.log('🚀 Simulateur Électricité Résidentiel initialisé (version mise à jour)');
    }

    // ================================
    // CHARGEMENT DE LA CONFIGURATION
    // ================================

    function loadConfigData() {
        const configElement = document.getElementById('simulateur-config');
        if (configElement) {
            try {
                configData = JSON.parse(configElement.textContent);
                console.log('📊 Configuration chargée:', configData);
            } catch (e) {
                console.error('❌ Erreur lors du chargement de la configuration:', e);
                configData = {};
            }
        }
    }

    // ================================
    // CALCULATEUR D'ÉQUIPEMENTS EN TEMPS RÉEL
    // ================================

    function setupEquipmentCalculator() {
        // Calculateur en temps réel lors des changements
        $('input[type="radio"], input[type="checkbox"], input[type="number"], select').on('change', function () {
            updateConsumptionPreview();
        });

        // Affichage des informations contextuelles
        $('input[name="type_chauffage_electrique"]').on('change', function () {
            updateHeatingInfo();
        });

        $('input[name="equipements_speciaux"]').on('change', function () {
            updateSpecialEquipmentInfo();
        });
    }

    function updateConsumptionPreview() {
        // Mise à jour en temps réel de l'estimation (optionnel)
        const surface = parseInt($('#surface').val()) || 100;
        const isolation = $('input[name="isolation"]:checked').val();
        const chauffage = $('input[name="type_chauffage_electrique"]:checked').val();

        if (surface && isolation && chauffage && chauffage !== 'aucun') {
            const estimatedHeating = calculateHeatingConsumption(surface, isolation, chauffage);
            showHeatingEstimate(estimatedHeating);
        }
    }

    function calculateHeatingConsumption(surface, isolation, type) {
        // Consommations par m² selon le type et l'isolation
        const consommations = {
            'convecteurs': {
                'avant_1980': 215,
                '1980_2000': 150,
                'apres_2000': 75,
                'renovation': 37.5
            },
            'inertie': {
                'avant_1980': 185,
                '1980_2000': 125,
                'apres_2000': 65,
                'renovation': 30
            },
            'clim_reversible': {
                'avant_1980': 100,
                '1980_2000': 70,
                'apres_2000': 45,
                'renovation': 17.5
            },
            'pac_air_eau': {
                'avant_1980': 80,
                '1980_2000': 60,
                'apres_2000': 40,
                'renovation': 20
            }
        };

        const consoParM2 = consommations[type]?.[isolation] || 0;
        return surface * consoParM2;
    }

    function showHeatingEstimate(consumption) {
        // Afficher l'estimation dans une bulle d'info (optionnel)
        const message = `Estimation chauffage : ${consumption.toLocaleString()} kWh/an`;

        // Supprimer l'ancienne bulle
        $('.heating-estimate').remove();

        // Ajouter la nouvelle bulle
        const $estimate = $(`<div class="heating-estimate">${message}</div>`);
        $('.form-step[data-step="2"]').prepend($estimate);

        // Animation
        $estimate.hide().slideDown(300);
        setTimeout(() => {
            $estimate.slideUp(300, () => $estimate.remove());
        }, 3000);
    }

    function updateHeatingInfo() {
        const selectedType = $('input[name="type_chauffage_electrique"]:checked').val();

        // Informations contexttuelles par type de chauffage
        const infos = {
            'convecteurs': '🔥 Les convecteurs ont la consommation la plus élevée mais sont moins chers à l\'achat.',
            'inertie': '🌡️ Les radiateurs à inertie offrent un meilleur confort avec 15-20% d\'économie.',
            'clim_reversible': '❄️ La climatisation réversible peut diviser votre facture de chauffage par 2 !',
            'pac_air_eau': '💨 La PAC Air/Eau est la solution la plus économique avec jusqu\'à 75% d\'économie.',
            'aucun': '🚫 Aucune consommation de chauffage électrique ne sera comptabilisée.'
        };

        if (selectedType && infos[selectedType]) {
            showNotification(infos[selectedType], 'info', 4000);
        }
    }

    function updateSpecialEquipmentInfo() {
        const selectedEquipments = $('input[name="equipements_speciaux"]:checked');
        let totalExtra = 0;
        const equipmentCosts = {
            'piscine_simple': 1400,
            'piscine_chauffee': 4000,
            'spa': 2000,
            'voiture_electrique': 1500,
            'climatiseur_mobile': 800,
            'cave_a_vin': 400
        };

        selectedEquipments.each(function () {
            totalExtra += equipmentCosts[$(this).val()] || 0;
        });

        if (totalExtra > 0) {
            showNotification(`📈 Équipements spéciaux : +${totalExtra.toLocaleString()} kWh/an`, 'info', 3000);
        }
    }

    // ================================
    // NAVIGATION ENTRE LES ÉTAPES
    // ================================

    function setupStepNavigation() {
        // Bouton Suivant
        $('#btn-next').on('click', function () {
            if (validateCurrentStep()) {
                goToNextStep();
            }
        });

        // Bouton Précédent
        $('#btn-previous').on('click', function () {
            goToPreviousStep();
        });

        // Bouton Calculer
        $('#btn-calculate').on('click', function () {
            if (validateCurrentStep()) {
                calculateResults();
            }
        });

        // Bouton Recommencer
        $('#btn-restart').on('click', function () {
            if (confirm('🔄 Voulez-vous vraiment recommencer la simulation ?')) {
                restartSimulation();
            }
        });

        // Navigation directe par clic sur les étapes
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

            // Animation
            $('.form-step.active').addClass('slide-out-left');
            setTimeout(() => {
                $('.form-step').removeClass('active slide-out-left');
                $(`.form-step[data-step="${currentStep}"]`).addClass('active slide-in-right');
                setTimeout(() => {
                    $('.form-step').removeClass('slide-in-right');
                }, 300);
            }, 150);
        }
    }

    function goToPreviousStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
            updateProgress();
            updateNavigation();

            // Animation
            $('.form-step.active').addClass('slide-out-right');
            setTimeout(() => {
                $('.form-step').removeClass('active slide-out-right');
                $(`.form-step[data-step="${currentStep}"]`).addClass('active slide-in-left');
                setTimeout(() => {
                    $('.form-step').removeClass('slide-in-left');
                }, 300);
            }, 150);
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
        $('.progress-fill').css('width', progressPercent + '%').attr('data-progress', progressPercent);
    }

    function updateNavigation() {
        // Bouton Précédent
        if (currentStep === 1) {
            $('#btn-previous').hide();
        } else {
            $('#btn-previous').show();
        }

        // Boutons principaux
        if (currentStep === totalSteps) {
            $('#btn-next').hide();
            $('#btn-calculate').hide();
            $('#btn-restart').show();
        } else if (currentStep === totalSteps - 1) {
            $('#btn-next').hide();
            $('#btn-calculate').show();
            $('#btn-restart').hide();
        } else {
            $('#btn-next').show();
            $('#btn-calculate').hide();
            $('#btn-restart').hide();
        }
    }

    // ================================
    // VALIDATION DES FORMULAIRES
    // ================================

    function setupFormValidation() {
        // Validation en temps réel
        $('input[required], select[required]').on('blur', function () {
            validateField($(this));
        });

        // Validation lors du changement
        $('input[type="radio"], input[type="checkbox"]').on('change', function () {
            validateField($(this));
        });

        // Validation des nombres
        $('input[type="number"]').on('input', function () {
            validateNumberField($(this));
        });
    }

    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;

        // Valider tous les champs requis de l'étape actuelle
        currentStepElement.find('input[required], select[required]').each(function () {
            if (!validateField($(this))) {
                isValid = false;
            }
        });

        // Validation spécifique par étape
        switch (currentStep) {
            case 1:
                isValid = validateStep1() && isValid;
                break;
            case 2:
                isValid = validateStep2() && isValid;
                break;
            case 3:
                isValid = validateStep3() && isValid;
                break;
        }

        if (!isValid) {
            showNotification('⚠️ Veuillez remplir tous les champs obligatoires avant de continuer.', 'warning');
        }

        return isValid;
    }

    function validateStep1() {
        const surface = parseInt($('#surface').val());
        if (surface < 20 || surface > 500) {
            showNotification('⚠️ La surface doit être entre 20 et 500 m²', 'warning');
            return false;
        }
        return true;
    }

    function validateStep2() {
        // Vérifier qu'un type de chauffage est sélectionné
        const chauffage = $('input[name="type_chauffage_electrique"]:checked').val();
        const eauChaude = $('input[name="eau_chaude_electrique"]:checked').val();

        if (!chauffage) {
            showNotification('⚠️ Veuillez sélectionner un type de chauffage', 'warning');
            return false;
        }

        if (!eauChaude) {
            showNotification('⚠️ Veuillez indiquer votre type d\'eau chaude', 'warning');
            return false;
        }

        return true;
    }

    function validateStep3() {
        // Pas de validation obligatoire pour l'étape 3
        return true;
    }

    function validateField($field) {
        const fieldType = $field.attr('type');
        const fieldName = $field.attr('name');
        let isValid = true;

        // Retirer les anciennes classes de validation
        $field.removeClass('field-error field-success');

        if (fieldType === 'radio') {
            isValid = $(`input[name="${fieldName}"]:checked`).length > 0;
        } else if (fieldType === 'checkbox') {
            // Pour les checkboxes optionnelles, toujours valide
            isValid = true;
        } else if ($field.is('select')) {
            isValid = $field.val() !== '' && $field.val() !== null;
        } else {
            isValid = $field.val().trim() !== '';
        }

        // Ajouter les classes de validation
        if (isValid) {
            $field.addClass('field-success');
        } else {
            $field.addClass('field-error');
        }

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
            showNotification(`⚠️ La valeur minimum est ${min}`, 'warning');
            return false;
        }

        if (!isNaN(max) && value > max) {
            $field.addClass('field-error');
            showNotification(`⚠️ La valeur maximum est ${max}`, 'warning');
            return false;
        }

        $field.addClass('field-success');
        return true;
    }

    // ================================
    // COLLECTE DES DONNÉES
    // ================================

    function saveCurrentStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        // Collecter tous les inputs de l'étape actuelle
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
                    if (Array.isArray(formData[name])) {
                        formData[name].push($field.val());
                    } else {
                        formData[name] = [$field.val()];
                    }
                }
            } else {
                formData[name] = $field.val();
            }
        });

        console.log(`📝 Données de l'étape ${currentStep} sauvegardées:`, formData);
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
                    if (Array.isArray(formData[name])) {
                        formData[name].push($field.val());
                    } else {
                        formData[name] = [$field.val()];
                    }
                }
            } else {
                formData[name] = $field.val();
            }
        });

        return formData;
    }

    // ================================
    // SUIVI DE LA PROGRESSION
    // ================================

    function setupProgressTracking() {
        // Mise à jour en temps réel de l'interface
        $('input, select').on('change', function () {
            updateFieldPreview($(this));
        });
    }

    function updateFieldPreview($field) {
        // Ajouter des indicateurs visuels lors des changements
        $field.addClass('field-changed');
        setTimeout(() => {
            $field.removeClass('field-changed');
        }, 1000);
    }

    // ================================
    // CALCUL DES RÉSULTATS MISE À JOUR
    // ================================

    function setupCalculation() {
        // Préparer le container des résultats
        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul en cours...</p>
            </div>
        `);
    }

    function calculateResults() {
        console.log('🧮 Début du calcul...');

        // Collecter toutes les données
        const allData = collectAllFormData();

        // Afficher l'état de chargement
        showStep(4);
        updateProgress();
        updateNavigation();

        $('#results-container').html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Calcul de votre estimation personnalisée avec nouveaux tarifs...</p>
            </div>
        `);

        // Appel AJAX vers le backend
        setTimeout(() => {
            performCalculation(allData);
        }, 1500);
    }

    function performCalculation(userData) {
        // AJAX vers le backend pour le calcul
        $.ajax({
            url: hticSimulateur.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'htic_calculate_estimation',
                nonce: hticSimulateur.nonce,
                type: 'elec-residentiel',
                user_data: userData,
                config_data: configData
            },
            success: function (response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    displayError('Erreur lors du calcul: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', error);
                // Pour le développement, afficher des résultats fictifs
                displayMockResults(userData);
            }
        });
    }

    function displayMockResults(userData) {
        // Résultats fictifs améliorés pour le développement
        const surface = parseInt(userData.surface) || 100;
        const chauffage = userData.type_chauffage_electrique || 'convecteurs';

        // Calcul de base selon le type de chauffage
        const baseConso = {
            'convecteurs': surface * 150,
            'inertie': surface * 125,
            'clim_reversible': surface * 70,
            'pac_air_eau': surface * 60,
            'aucun': 0
        };

        const consoBase = baseConso[chauffage] || (surface * 100);
        const consoTotale = consoBase + 3047; // Base + autres équipements

        const mockResults = {
            consommation_annuelle: Math.round(consoTotale),
            puissance_recommandee: surface > 120 ? '15' : '12',
            tarifs: {
                base: {
                    total_annuel: Math.round(consoTotale * 0.2516 + (22.21 * 12)),
                    total_mensuel: Math.round((consoTotale * 0.2516 + (22.21 * 12)) / 12)
                },
                hc: {
                    total_annuel: Math.round(consoTotale * 0.24 + (23.57 * 12)),
                    total_mensuel: Math.round((consoTotale * 0.24 + (23.57 * 12)) / 12)
                },
                tempo: {
                    total_annuel: Math.round(consoTotale * 0.18 + (38.22 * 12)),
                    total_mensuel: Math.round((consoTotale * 0.18 + (38.22 * 12)) / 12)
                }
            },
            repartition: {
                chauffage: Math.round(consoBase),
                eau_chaude: userData.eau_chaude_electrique === 'oui' ? 1800 : 0,
                electromenagers: 1497,
                eclairage: 750,
                autres: 300
            },
            recap: userData,
            type_chauffage: chauffage
        };

        displayResults(mockResults);
    }

    function displayResults(results) {
        const resultsHtml = `
            <div class="results-summary">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">⚡</div>
                    <h3>Votre consommation estimée</h3>
                    <div class="big-number">${results.consommation_annuelle.toLocaleString()} <span>kWh/an</span></div>
                    <p>Puissance recommandée : <strong>${results.puissance_recommandee} kVA</strong></p>
                    ${results.type_chauffage ? `<p>Chauffage : <strong>${getHeatingLabel(results.type_chauffage)}</strong></p>` : ''}
                </div>
                
                <!-- Comparaison des tarifs MISE À JOUR -->
                <div class="tarifs-comparison">
                    <h3>💰 Comparaison des tarifs (NOUVEAU : Tarif TEMPO inclus)</h3>
                    <div class="tarifs-grid">
                        <div class="tarif-card">
                            <h4>Tarif BASE</h4>
                            <div class="tarif-prix">${results.tarifs.base.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.base.total_mensuel}€/mois</div>
                            <div class="tarif-desc">Tarif fixe toute l'année</div>
                        </div>
                        <div class="tarif-card recommended">
                            <h4>Heures Creuses</h4>
                            <div class="tarif-prix">${results.tarifs.hc.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.hc.total_mensuel}€/mois</div>
                            <div class="tarif-desc">40% en heures creuses</div>
                            <span class="recommended-badge">💡 Classique</span>
                        </div>
                        <div class="tarif-card tempo">
                            <h4>TEMPO</h4>
                            <div class="tarif-prix">${results.tarifs.tempo.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.tempo.total_mensuel}€/mois</div>
                            <div class="tarif-desc">Jours bleus/blancs/rouges</div>
                            <span class="tempo-badge">⭐ NOUVEAU</span>
                        </div>
                    </div>
                    <div class="economies">
                        ${(() => {
                let meilleurTarif = results.tarifs.base.total_annuel;
                let meilleurNom = 'BASE';
                let economies = 0;

                // Vérifier HC si disponible
                if (results.tarifs.hc.disponible !== false && results.tarifs.hc.total_annuel < meilleurTarif) {
                    meilleurTarif = results.tarifs.hc.total_annuel;
                    meilleurNom = 'Heures Creuses';
                }

                // Vérifier TEMPO si disponible
                if (results.tarifs.tempo.disponible !== false && results.tarifs.tempo.total_annuel < meilleurTarif) {
                    meilleurTarif = results.tarifs.tempo.total_annuel;
                    meilleurNom = 'TEMPO';
                }

                economies = results.tarifs.base.total_annuel - meilleurTarif;

                if (economies > 0) {
                    return `<p>💡 <strong>Économies potentielles :</strong> jusqu'à ${economies}€/an avec le tarif ${meilleurNom} !</p>`;
                } else {
                    return `<p>💡 <strong>Tarif optimal :</strong> Le tarif BASE semble le plus avantageux pour votre profil.</p>`;
                }
            })()}
                        <p>ℹ️ <strong>Disponibilité des tarifs :</strong> 
                        ${results.tarifs.hc.disponible === false ? 'HC non disponible en 3 KVA. ' : ''}
                        ${results.tarifs.tempo.disponible === false ? 'TEMPO non disponible en dessous de 9 KVA.' : ''}
                        </p>
                    </div>
                </div>
                
                <!-- Répartition de la consommation -->
                <div class="repartition-conso">
                    <h3>📊 Répartition de votre consommation</h3>
                    <div class="repartition-chart">
                        <div class="chart-container">
                            <canvas id="consumption-chart"></canvas>
                        </div>
                    </div>
                    <div class="repartition-details">
                        ${results.repartition.chauffage > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #ef4444;"></span>
                            <span>Chauffage : ${results.repartition.chauffage.toLocaleString()} kWh</span>
                        </div>` : ''}
                        ${results.repartition.eau_chaude > 0 ? `
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #3b82f6;"></span>
                            <span>Eau chaude : ${results.repartition.eau_chaude.toLocaleString()} kWh</span>
                        </div>` : ''}
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #10b981;"></span>
                            <span>Électroménager : ${results.repartition.electromenagers.toLocaleString()} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #f59e0b;"></span>
                            <span>Éclairage : ${results.repartition.eclairage.toLocaleString()} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #8b5cf6;"></span>
                            <span>Autres : ${results.repartition.autres.toLocaleString()} kWh</span>
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
                            <strong>Isolation :</strong> ${getIsolationLabel(results.recap.isolation)}
                        </div>
                        <div class="recap-item">
                            <strong>Type de chauffage :</strong> ${getHeatingLabel(results.recap.type_chauffage_electrique)}
                        </div>
                        <div class="recap-item">
                            <strong>Eau chaude :</strong> ${results.recap.eau_chaude_electrique === 'oui' ? 'Électrique' : 'Autre énergie'}
                        </div>
                    </div>
                </div>
                
                <!-- Conseils personnalisés NOUVEAU -->
                <div class="conseils-section">
                    <h3>💡 Conseils personnalisés</h3>
                    <div class="conseils-grid">
                        ${generatePersonalizedAdvice(results)}
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="results-actions">
                    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer les résultats</button>
                    <button class="btn btn-secondary" onclick="downloadPDF()">📄 Télécharger PDF</button>
                    <button class="btn btn-success" onclick="shareResults()">📤 Partager</button>
                </div>
            </div>
        `;

        $('#results-container').html(resultsHtml);

        // Créer le graphique
        setTimeout(() => {
            createConsumptionChart(results.repartition);
        }, 300);

        // Animation d'entrée
        $('.results-summary').hide().fadeIn(600);

        console.log('✅ Résultats affichés avec nouveaux tarifs');
    }

    function generatePersonalizedAdvice(results) {
        let conseils = [];

        // Conseils selon le chauffage
        if (results.type_chauffage === 'convecteurs') {
            conseils.push('<div class="conseil">🔥 Remplacer vos convecteurs par des radiateurs à inertie pourrait vous faire économiser 15-20%</div>');
        }

        if (results.type_chauffage !== 'pac_air_eau' && results.repartition.chauffage > 3000) {
            conseils.push('<div class="conseil">💨 Une PAC Air/Eau pourrait diviser votre facture de chauffage par 3 !</div>');
        }

        // Conseils tarifs
        if (results.tarifs.tempo.total_annuel < results.tarifs.base.total_annuel) {
            conseils.push('<div class="conseil">⚡ Le tarif TEMPO pourrait vous faire économiser ' + (results.tarifs.base.total_annuel - results.tarifs.tempo.total_annuel) + '€/an</div>');
        }

        return conseils.join('');
    }

    // ================================
    // GRAPHIQUE DE RÉPARTITION
    // ================================

    function createConsumptionChart(data) {
        const canvas = document.getElementById('consumption-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const validData = Object.entries(data).filter(([key, value]) => value > 0);
        const total = validData.reduce((sum, [key, val]) => sum + val, 0);

        // Couleurs correspondantes
        const colors = {
            chauffage: '#ef4444',
            eau_chaude: '#3b82f6',
            electromenagers: '#10b981',
            eclairage: '#f59e0b',
            autres: '#8b5cf6'
        };

        // Données pour le graphique
        const chartData = validData.map(([key, value]) => ({
            label: key,
            value: value,
            color: colors[key] || '#6b7280'
        }));

        // Dessiner un graphique simple (doughnut chart basique)
        drawSimpleChart(ctx, chartData, total);
    }

    function drawSimpleChart(ctx, data, total) {
        const centerX = ctx.canvas.width / 2;
        const centerY = ctx.canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 20;
        const innerRadius = radius * 0.6;

        let currentAngle = -Math.PI / 2;

        data.forEach(item => {
            const sliceAngle = (item.value / total) * 2 * Math.PI;

            // Dessiner la part
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            ctx.arc(centerX, centerY, innerRadius, currentAngle + sliceAngle, currentAngle, true);
            ctx.closePath();
            ctx.fillStyle = item.color;
            ctx.fill();

            currentAngle += sliceAngle;
        });

        // Texte central
        ctx.font = 'bold 24px sans-serif';
        ctx.fillStyle = '#374151';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(total.toLocaleString(), centerX, centerY - 10);

        ctx.font = '14px sans-serif';
        ctx.fillText('kWh/an', centerX, centerY + 15);
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

    // ================================
    // FONCTIONS UTILITAIRES
    // ================================

    function getLogementLabel(type) {
        const labels = {
            'maison': '🏠 Maison',
            'appartement': '🏢 Appartement'
        };
        return labels[type] || type;
    }

    function getIsolationLabel(isolation) {
        const labels = {
            'avant_1980': 'Avant 1980 (faible)',
            '1980_2000': '1980-2000 (moyenne)',
            'apres_2000': 'Après 2000 (bonne)',
            'renovation': 'Rénovation récente (excellente)'
        };
        return labels[isolation] || isolation;
    }

    function getHeatingLabel(type) {
        const labels = {
            'convecteurs': '🔥 Convecteurs électriques',
            'inertie': '🌡️ Radiateurs à inertie',
            'clim_reversible': '❄️ Climatisation réversible',
            'pac_air_eau': '💨 PAC Air/Eau',
            'aucun': '🚫 Pas de chauffage électrique'
        };
        return labels[type] || type;
    }

    function showNotification(message, type = 'info', duration = 5000) {
        // Supprimer les anciens messages
        $('.notification-message').remove();

        // Déterminer la classe CSS
        const messageClass = type === 'success' ? 'notification-success' :
            type === 'error' ? 'notification-error' :
                type === 'warning' ? 'notification-warning' :
                    'notification-info';

        // Créer et afficher le nouveau message
        const $message = $(`<div class="notification-message ${messageClass}">${message}</div>`);

        // Insérer le message en haut de l'étape active
        $('.form-step.active').prepend($message);

        // Animation d'entrée
        $message.hide().slideDown(300);

        // Supprimer automatiquement
        if (duration > 0) {
            setTimeout(function () {
                $message.slideUp(300, () => $message.remove());
            }, duration);
        }
    }

    function restartSimulation() {
        // Reset toutes les variables
        currentStep = 1;
        formData = {};

        // Reset le formulaire
        $('#simulateur-elec-residentiel')[0].reset();

        // Reset l'interface
        showStep(1);
        updateProgress();
        updateNavigation();

        // Reset les classes de validation
        $('.field-error, .field-success, .field-changed').removeClass('field-error field-success field-changed');

        console.log('🔄 Simulation redémarrée');
    }

    // ================================
    // ANIMATIONS CSS SUPPLÉMENTAIRES
    // ================================

    function addAnimationClasses() {
        $(`
            <style>
                .slide-out-left { transform: translateX(-100%); opacity: 0; }
                .slide-out-right { transform: translateX(100%); opacity: 0; }
                .slide-in-left { transform: translateX(-100%); opacity: 0; }
                .slide-in-right { transform: translateX(100%); opacity: 0; }
                .form-step { transition: all 0.3s ease; }
                .field-changed { transform: scale(1.02); transition: transform 0.2s ease; }
                .notification-message { 
                    padding: 1rem; 
                    border-radius: 6px; 
                    margin-bottom: 1rem; 
                    border-left: 4px solid;
                }
                .notification-info { background: #e0f2fe; border-color: #0288d1; color: #01579b; }
                .notification-success { background: #e8f5e8; border-color: #4caf50; color: #2e7d32; }
                .notification-warning { background: #fff3e0; border-color: #ff9800; color: #ef6c00; }
                .notification-error { background: #ffebee; border-color: #f44336; color: #c62828; }
                .heating-estimate {
                    background: #e3f2fd;
                    border: 1px solid #2196f3;
                    padding: 0.75rem;
                    border-radius: 6px;
                    margin-bottom: 1rem;
                    color: #1565c0;
                    font-weight: 500;
                }
                .tarif-card.tempo { border: 2px solid #9c27b0; }
                .tempo-badge { 
                    background: linear-gradient(45deg, #9c27b0, #e91e63);
                    color: white;
                    padding: 0.25rem 0.5rem;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 600;
                }
                .conseils-section {
                    background: #f8fffe;
                    border: 1px solid #10b981;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin: 1.5rem 0;
                }
                .conseil {
                    background: white;
                    border-left: 4px solid #10b981;
                    padding: 0.75rem;
                    margin-bottom: 0.5rem;
                    border-radius: 0 6px 6px 0;
                }
            </style>
        `).appendTo('head');
    }

    // Ajouter les styles d'animation
    addAnimationClasses();

    // ================================
    // FONCTIONS GLOBALES EXPOSÉES
    // ================================

    window.downloadPDF = function () {
        // TODO: Implémenter la génération PDF
        showNotification('📄 Fonction de téléchargement PDF en cours de développement', 'info');
    };

    window.shareResults = function () {
        // TODO: Implémenter le partage
        showNotification('📤 Fonction de partage en cours de développement', 'info');
    };

    // Exposer des fonctions pour le debug
    if (window.location.search.includes('debug=1')) {
        window.hticSimulateurDebug = {
            currentStep: () => currentStep,
            formData: () => formData,
            configData: () => configData,
            goToStep: goToStep,
            calculate: calculateResults
        };
    }

});