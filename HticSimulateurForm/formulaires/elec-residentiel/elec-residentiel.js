// formulaires/elec-residentiel/elec-residentiel.js

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

        console.log('🚀 Simulateur Électricité Résidentiel initialisé');
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
            showValidationMessage('⚠️ Veuillez remplir tous les champs obligatoires avant de continuer.');
        }

        return isValid;
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
            showValidationMessage(`⚠️ La valeur minimum est ${min}`);
            return false;
        }

        if (!isNaN(max) && value > max) {
            $field.addClass('field-error');
            showValidationMessage(`⚠️ La valeur maximum est ${max}`);
            return false;
        }

        $field.addClass('field-success');
        return true;
    }

    function validateStep1() {
        const surface = parseInt($('#surface').val());
        if (surface < 20 || surface > 500) {
            showValidationMessage('⚠️ La surface doit être entre 20 et 500 m²');
            return false;
        }
        return true;
    }

    function validateStep2() {
        // Validation optionnelle pour l'étape 2
        return true;
    }

    function validateStep3() {
        // Validation optionnelle pour l'étape 3
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
    // CALCUL DES RÉSULTATS
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
                <p>Calcul de votre estimation personnalisée...</p>
            </div>
        `);

        // Simulation d'un calcul (remplacer par l'appel AJAX réel)
        setTimeout(() => {
            performCalculation(allData);
        }, 2000);
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
        // Résultats fictifs pour le développement
        const mockResults = {
            consommation_annuelle: Math.round(3000 + (parseInt(userData.surface) || 100) * 25),
            puissance_recommandee: userData.surface > 120 ? '15' : '12',
            tarifs: {
                base: {
                    total_annuel: 1250,
                    total_mensuel: 104
                },
                hc: {
                    total_annuel: 1180,
                    total_mensuel: 98
                },
                tempo: {
                    total_annuel: 1100,
                    total_mensuel: 92
                }
            },
            repartition: {
                chauffage: 1800,
                eau_chaude: 600,
                electromenagers: 400,
                eclairage: 200,
                autres: 300
            },
            recap: userData
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
                        <div class="tarif-card">
                            <h4>Tempo</h4>
                            <div class="tarif-prix">${results.tarifs.tempo.total_annuel}€<span>/an</span></div>
                            <div class="tarif-mensuel">${results.tarifs.tempo.total_mensuel}€/mois</div>
                        </div>
                    </div>
                    <div class="economies">
                        <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${results.tarifs.base.total_annuel - results.tarifs.tempo.total_annuel}€/an en choisissant le bon tarif !</p>
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
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #ef4444;"></span>
                            <span>Chauffage : ${results.repartition.chauffage} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #3b82f6;"></span>
                            <span>Eau chaude : ${results.repartition.eau_chaude} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #10b981;"></span>
                            <span>Électroménager : ${results.repartition.electromenagers} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #f59e0b;"></span>
                            <span>Éclairage : ${results.repartition.eclairage} kWh</span>
                        </div>
                        <div class="repartition-item">
                            <span class="repartition-color" style="background: #8b5cf6;"></span>
                            <span>Autres : ${results.repartition.autres} kWh</span>
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
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="results-actions">
                    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer les résultats</button>
                    <button class="btn btn-secondary" onclick="downloadPDF()">📄 Télécharger PDF</button>
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

        console.log('✅ Résultats affichés');
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
    // GRAPHIQUE DE RÉPARTITION
    // ================================

    function createConsumptionChart(data) {
        const canvas = document.getElementById('consumption-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const total = Object.values(data).reduce((sum, val) => sum + val, 0);

        // Données pour le graphique
        const chartData = [
            { label: 'Chauffage', value: data.chauffage, color: '#ef4444' },
            { label: 'Eau chaude', value: data.eau_chaude, color: '#3b82f6' },
            { label: 'Électroménager', value: data.electromenagers, color: '#10b981' },
            { label: 'Éclairage', value: data.eclairage, color: '#f59e0b' },
            { label: 'Autres', value: data.autres, color: '#8b5cf6' }
        ];

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

    function showValidationMessage(message) {
        // Supprimer les anciens messages
        $('.validation-message').remove();

        // Créer et afficher le nouveau message
        const $message = $(`<div class="validation-message">${message}</div>`);
        $('.form-step.active').prepend($message);

        // Animation et suppression automatique
        $message.hide().slideDown(300);
        setTimeout(() => {
            $message.slideUp(300, () => $message.remove());
        }, 4000);
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
                .validation-message { 
                    background: #fef3cd; 
                    border: 1px solid #faebcc; 
                    color: #8a6d3b; 
                    padding: 1rem; 
                    border-radius: 6px; 
                    margin-bottom: 1rem; 
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
        alert('📄 Fonction de téléchargement PDF en cours de développement');
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