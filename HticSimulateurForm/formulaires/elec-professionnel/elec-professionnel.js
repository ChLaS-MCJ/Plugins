// formulaires/elec-professionnel/elec-professionnel.js - JavaScript Électricité Professionnel

(function ($) {
    'use strict';

    // ================================
    // VARIABLES GLOBALES
    // ================================
    let currentStep = 1;
    const totalSteps = 6;
    let formData = {};
    let config = {};
    let isCalculating = false;

    // ================================
    // INITIALISATION
    // ================================
    $(document).ready(function () {
        console.log('🏢 Initialisation Simulateur Électricité Professionnel');

        // Charger la configuration
        loadConfig();

        // Initialiser les événements
        initEvents();

        // Mise à jour initiale de la UI
        updateUI();

        console.log('✅ Simulateur Professionnel initialisé');
    });

    // ================================
    // CONFIGURATION
    // ================================
    function loadConfig() {
        const configElement = $('#simulateur-config');
        if (configElement.length) {
            try {
                config = JSON.parse(configElement.text());
                console.log('📋 Configuration chargée:', config);
            } catch (e) {
                console.error('❌ Erreur configuration:', e);
                config = { debug: false };
            }
        }
    }

    // ================================
    // ÉVÉNEMENTS
    // ================================
    function initEvents() {
        // Navigation
        $('#btn-suivant').on('click', nextStep);
        $('#btn-precedent').on('click', prevStep);
        $('#btn-calculer').on('click', calculateResults);

        // Sauvegarde automatique
        $('.form-step input, .form-step select').on('change', function () {
            saveStepData();
            updateEligibilityInfo();
        });

        // Validation en temps réel
        $('.form-step input[required], .form-step select[required]').on('change', validateCurrentStep);

        // Événements spéciaux
        $('input[name="nb_salaries"], input[name="chiffre_affaires"]').on('change', updateEligibilityInfo);
        $('input[name="secteur_activite"]').on('change', updateSectorInfo);
    }

    // ================================
    // NAVIGATION ENTRE ÉTAPES
    // ================================
    function nextStep() {
        if (!validateCurrentStep()) {
            showValidationError();
            return;
        }

        saveStepData();

        if (currentStep < totalSteps) {
            currentStep++;
            updateUI();
            scrollToTop();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateUI();
            scrollToTop();
        }
    }

    function updateUI() {
        // Mise à jour des étapes
        $('.form-step').removeClass('active');
        $(`.form-step[data-step="${currentStep}"]`).addClass('active');

        // Mise à jour de la barre de progression
        const progressPercent = (currentStep / totalSteps) * 100;
        $('.progress-fill').css('width', progressPercent + '%');

        // Mise à jour des indicateurs d'étape
        $('.step').removeClass('active');
        $(`.step[data-step="${currentStep}"]`).addClass('active');

        // Mise à jour des boutons
        $('#btn-precedent').toggle(currentStep > 1);
        $('#btn-suivant').toggle(currentStep < totalSteps);
        $('#btn-calculer').toggle(currentStep === totalSteps);

        // Mise à jour dynamique selon l'étape
        if (currentStep === 1) {
            updateEligibilityInfo();
        }

        debugLog('Navigation étape', currentStep);
    }

    function scrollToTop() {
        $('html, body').animate({ scrollTop: $('.simulateur-header').offset().top - 20 }, 500);
    }

    // ================================
    // VALIDATION
    // ================================
    function validateCurrentStep() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);
        const requiredFields = currentStepElement.find('input[required], select[required]');
        let isValid = true;

        requiredFields.each(function () {
            const $field = $(this);
            const fieldType = $field.attr('type');

            if (fieldType === 'radio') {
                const groupName = $field.attr('name');
                const isGroupValid = $(`input[name="${groupName}"]:checked`).length > 0;
                if (!isGroupValid) {
                    isValid = false;
                    highlightError($field.closest('.radio-group'));
                }
            } else {
                if (!$field.val() || $field.val().trim() === '') {
                    isValid = false;
                    highlightError($field);
                } else {
                    clearError($field);
                }
            }
        });

        return isValid;
    }

    function highlightError($element) {
        $element.addClass('error');
        setTimeout(() => $element.removeClass('error'), 3000);
    }

    function clearError($element) {
        $element.removeClass('error');
    }

    function showValidationError() {
        // Créer une notification d'erreur moderne
        const notification = $(`
            <div class="notification error" style="display: none;">
                <div class="notification-content">
                    <span class="notification-icon">⚠️</span>
                    <span>Veuillez remplir tous les champs obligatoires</span>
                </div>
            </div>
        `);

        $('body').append(notification);
        notification.slideDown(300);

        setTimeout(() => {
            notification.slideUp(300, () => notification.remove());
        }, 4000);
    }

    // ================================
    // GESTION DES DONNÉES
    // ================================
    function saveStepData() {
        const currentStepElement = $(`.form-step[data-step="${currentStep}"]`);

        currentStepElement.find('input, select').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name) return;

            const cleanName = name.replace('[]', '');

            if (type === 'radio') {
                if ($field.is(':checked')) {
                    formData[cleanName] = $field.val();
                }
            } else if (type === 'checkbox') {
                if (!formData[cleanName]) {
                    formData[cleanName] = [];
                }

                const value = $field.val();
                if ($field.is(':checked')) {
                    if (!formData[cleanName].includes(value)) {
                        formData[cleanName].push(value);
                    }
                } else {
                    const index = formData[cleanName].indexOf(value);
                    if (index > -1) {
                        formData[cleanName].splice(index, 1);
                    }
                }
            } else {
                formData[cleanName] = $field.val();
            }
        });

        debugLog('Données sauvegardées étape', currentStep, formData);
    }

    function collectAllFormData() {
        formData = {};

        $('.form-step').each(function () {
            const $step = $(this);

            $step.find('input, select').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (!name) return;

                const cleanName = name.replace('[]', '');

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[cleanName] = $field.val();
                    }
                } else if (type === 'checkbox') {
                    if (!formData[cleanName]) {
                        formData[cleanName] = [];
                    }

                    const value = $field.val();
                    if ($field.is(':checked')) {
                        if (!formData[cleanName].includes(value)) {
                            formData[cleanName].push(value);
                        }
                    } else {
                        const index = formData[cleanName].indexOf(value);
                        if (index > -1) {
                            formData[cleanName].splice(index, 1);
                        }
                    }
                } else {
                    formData[cleanName] = $field.val();
                }
            });
        });

        debugLog('Toutes les données collectées:', formData);
        return formData;
    }

    // ================================
    // LOGIQUE MÉTIER SPÉCIFIQUE
    // ================================
    function updateEligibilityInfo() {
        const nbSalaries = $('select[name="nb_salaries"]').val();
        const chiffreAffaires = $('select[name="chiffre_affaires"]').val();

        if (!nbSalaries || !chiffreAffaires) {
            $('#eligibilite-info').hide();
            return;
        }

        // Logique d'éligibilité TRV
        const salariesEligible = ['1', '2-5', '6-9'].includes(nbSalaries);
        const caEligible = ['0-100000', '100000-500000', '500000-1000000', '1000000-3000000'].includes(chiffreAffaires);

        const isEligible = salariesEligible && caEligible;

        let message = '';
        let badgeClass = '';

        if (isEligible) {
            message = '✅ Votre entreprise est éligible aux Tarifs Réglementés de Vente (TRV). Vous bénéficiez des tarifs les plus avantageux.';
            badgeClass = 'eligible';
        } else {
            message = '❌ Votre entreprise n\'est pas éligible aux TRV ';
            if (!salariesEligible) message += '(plus de 10 salariés) ';
            if (!caEligible) message += '(CA > 3M€) ';
            message += '. Vous devrez souscrire une offre de marché.';
            badgeClass = 'non-eligible';
        }

        $('#eligibilite-message').html(message);
        $('#eligibilite-info').removeClass('eligible non-eligible').addClass(badgeClass).show();

        // Sauvegarder le statut d'éligibilité
        formData.eligible_trv = isEligible;
    }

    function updateSectorInfo() {
        const secteur = $('input[name="secteur_activite"]:checked').val();

        // Logique pour adapter les questions selon le secteur
        // (peut être étendue selon les besoins)
        if (secteur === 'restauration') {
            // Ajouter des conseils spécifiques restauration
            console.log('Secteur restauration détecté - équipements spécialisés');
        }
    }

    // ================================
    // CALCUL DES RÉSULTATS
    // ================================
    function calculateResults() {
        if (isCalculating) return;

        isCalculating = true;
        $('#btn-calculer').prop('disabled', true).text('Calcul en cours...');

        // Collecter toutes les données
        const allData = collectAllFormData();

        // Afficher un état de chargement
        showLoadingState();

        // Simulation d'un appel AJAX
        setTimeout(() => {
            processCalculation(allData);
        }, 1500);
    }

    function showLoadingState() {
        $('#resultats-professionnel').html(`
            <div class="loading-moderne">
                <div class="loading-spinner-moderne"></div>
                <p>Calcul de votre simulation personnalisée...</p>
                <small>Analyse de vos données et comparaison des tarifs</small>
            </div>
        `);
    }

    function processCalculation(data) {
        try {
            // Calculs basés sur les données collectées
            const results = performCalculations(data);

            // Afficher les résultats
            displayResults(results);

            // Réactiver le bouton
            $('#btn-calculer').prop('disabled', false).html('Recalculer <span>📊</span>');
            isCalculating = false;

        } catch (error) {
            console.error('Erreur de calcul:', error);
            showCalculationError();
        }
    }

    function performCalculations(data) {
        // Simulation des calculs (à remplacer par la vraie logique)

        // 1. Calcul de la consommation estimée
        const consommationBase = calculateBaseConsumption(data);
        const consommationTotale = Math.round(consommationBase);

        // 2. Détermination de la puissance recommandée
        const puissanceRecommandee = calculateRecommendedPower(consommationTotale, data);

        // 3. Calcul des différents tarifs
        const tarifs = calculateAllTariffs(consommationTotale, puissanceRecommandee, data);

        // 4. Répartition de la consommation
        const repartition = calculateConsumptionBreakdown(data);

        return {
            consommation_totale: consommationTotale,
            puissance_recommandee: puissanceRecommandee,
            tarifs: tarifs,
            repartition: repartition,
            eligible_trv: data.eligible_trv,
            secteur: data.secteur_activite
        };
    }

    function calculateBaseConsumption(data) {
        let consommation = 0;

        // Base selon la surface et le type de local
        const surface = parseInt(data.surface_totale) || 200;

        switch (data.type_local) {
            case 'bureau':
                consommation += surface * 120; // 120 kWh/m²/an
                break;
            case 'commerce':
                consommation += surface * 150; // 150 kWh/m²/an
                break;
            case 'atelier':
                consommation += surface * 80; // 80 kWh/m²/an (hors machines)
                break;
            case 'mixte':
                consommation += surface * 130; // 130 kWh/m²/an
                break;
            default:
                consommation += surface * 100;
        }

        // Ajustements selon les équipements
        if (data.climatisation === 'clim_complete') {
            consommation *= 1.4;
        } else if (data.climatisation === 'clim_legere') {
            consommation *= 1.2;
        }

        // Équipements informatiques
        const nbPostes = parseInt(data.nb_postes_informatiques) || 5;
        consommation += nbPostes * 800; // 800 kWh/an par poste

        // Équipements spéciaux
        if (data.equipements_speciaux && Array.isArray(data.equipements_speciaux)) {
            data.equipements_speciaux.forEach(equipement => {
                switch (equipement) {
                    case 'frigo_pro':
                        consommation += 3000;
                        break;
                    case 'machines_outils':
                        consommation += 5000;
                        break;
                    case 'serveur':
                        consommation += 8000;
                        break;
                    case 'eclairage_securite':
                        consommation += 1000;
                        break;
                }
            });
        }

        // Chauffage électrique
        if (data.type_chauffage === 'electrique') {
            const chauffageConsommation = calculateHeatingConsumption(surface, data.isolation);
            consommation += chauffageConsommation;
        }

        return consommation;
    }

    function calculateHeatingConsumption(surface, isolation) {
        let kwhParM2 = 60; // Base

        switch (isolation) {
            case 'mauvaise':
                kwhParM2 = 100;
                break;
            case 'moyenne':
                kwhParM2 = 70;
                break;
            case 'bonne':
                kwhParM2 = 45;
                break;
        }

        return surface * kwhParM2;
    }

    function calculateRecommendedPower(consommation, data) {
        // Calcul simplifié de la puissance recommandée
        let puissanceEstimee = consommation / 2000; // Approximation

        // Ajustement selon le type d'activité
        switch (data.horaires) {
            case 'production_continue':
                puissanceEstimee *= 0.8; // Utilisation continue
                break;
            case 'bureau_classique':
                puissanceEstimee *= 1.2; // Pics de consommation
                break;
        }

        // Arrondir à la puissance standard supérieure
        const puissancesDisponibles = [3, 6, 9, 12, 15, 18, 24, 30, 36];
        return puissancesDisponibles.find(p => p >= puissanceEstimee) || 36;
    }

    function calculateAllTariffs(consommation, puissance, data) {
        // Tarifs simulés (à remplacer par les vrais tarifs du config)
        const tarifs = {};

        // TRV Base (si éligible)
        if (data.eligible_trv) {
            tarifs.trv_base = calculateTrvBase(consommation, puissance);
            tarifs.trv_hc = calculateTrvHeuresCreuses(consommation, puissance);

            // Tempo (si puissance >= 9)
            if (puissance >= 9) {
                tarifs.tempo = calculateTempo(consommation, puissance);
            }
        }

        // Offres de marché
        tarifs.offre_francaise = calculateOffreFrancaise(consommation, puissance);
        tarifs.offre_marche = calculateOffreMarche(consommation, puissance);

        return tarifs;
    }

    function calculateTrvBase(consommation, puissance) {
        // Prix simulés - à remplacer par les vrais prix
        const aboMensuel = puissance <= 6 ? 12.67 : puissance <= 12 ? 19.16 : 31.96;
        const prixKwh = 0.2516;

        const coutAbo = aboMensuel * 12;
        const coutConso = consommation * prixKwh;
        const totalHT = coutAbo + coutConso;
        const totalTTC = totalHT * 1.20; // TVA 20%

        return {
            nom: 'TRV Base',
            total_annuel: Math.round(totalTTC),
            total_mensuel: Math.round(totalTTC / 12),
            abonnement_annuel: Math.round(coutAbo * 1.20),
            cout_consommation: Math.round(coutConso * 1.20),
            prix_kwh: prixKwh
        };
    }

    function calculateTrvHeuresCreuses(consommation, puissance) {
        const aboMensuel = puissance <= 6 ? 13.28 : puissance <= 12 ? 20.28 : 33.70;
        const prixKwhHP = 0.27;
        const prixKwhHC = 0.2068;

        // Répartition HP/HC selon le profil d'activité
        const repartitionHP = 0.6; // 60% en HP par défaut
        const consoHP = consommation * repartitionHP;
        const consoHC = consommation * (1 - repartitionHP);

        const coutAbo = aboMensuel * 12;
        const coutConsoHP = consoHP * prixKwhHP;
        const coutConsoHC = consoHC * prixKwhHC;
        const totalHT = coutAbo + coutConsoHP + coutConsoHC;
        const totalTTC = totalHT * 1.20;

        return {
            nom: 'TRV Heures Creuses',
            total_annuel: Math.round(totalTTC),
            total_mensuel: Math.round(totalTTC / 12),
            abonnement_annuel: Math.round(coutAbo * 1.20),
            cout_consommation: Math.round((coutConsoHP + coutConsoHC) * 1.20),
            prix_kwh_hp: prixKwhHP,
            prix_kwh_hc: prixKwhHC
        };
    }

    function calculateTempo(consommation, puissance) {
        const aboMensuel = puissance <= 12 ? 16.55 : puissance <= 18 ? 26.18 : 39.50;

        // Prix Tempo simulés
        const prixBleuHP = 0.1609;
        const prixBleuHC = 0.1296;
        const prixBlancHP = 0.1894;
        const prixBlancHC = 0.1486;
        const prixRougeHP = 0.7562;
        const prixRougeHC = 0.1568;

        // Répartition des jours (300 bleus, 43 blancs, 22 rouges)
        const consoBleu = consommation * (300 / 365);
        const consoBlanc = consommation * (43 / 365);
        const consoRouge = consommation * (22 / 365);

        // Répartition HP/HC (60/40)
        const repartitionHP = 0.6;

        const coutAbo = aboMensuel * 12;
        const coutBleu = (consoBleu * repartitionHP * prixBleuHP) + (consoBleu * (1 - repartitionHP) * prixBleuHC);
        const coutBlanc = (consoBlanc * repartitionHP * prixBlancHP) + (consoBlanc * (1 - repartitionHP) * prixBlancHC);
        const coutRouge = (consoRouge * repartitionHP * prixRougeHP) + (consoRouge * (1 - repartitionHP) * prixRougeHC);

        const totalHT = coutAbo + coutBleu + coutBlanc + coutRouge;
        const totalTTC = totalHT * 1.20;

        return {
            nom: 'Tempo',
            total_annuel: Math.round(totalTTC),
            total_mensuel: Math.round(totalTTC / 12),
            abonnement_annuel: Math.round(coutAbo * 1.20),
            cout_consommation: Math.round((coutBleu + coutBlanc + coutRouge) * 1.20),
            details_tempo: {
                cout_bleu: Math.round(coutBleu * 1.20),
                cout_blanc: Math.round(coutBlanc * 1.20),
                cout_rouge: Math.round(coutRouge * 1.20)
            }
        };
    }

    function calculateOffreFrancaise(consommation, puissance) {
        // Offre française = TRV + 5%
        const tarifBase = calculateTrvBase(consommation, puissance);
        return {
            nom: 'Offre 100% française',
            total_annuel: Math.round(tarifBase.total_annuel * 1.05),
            total_mensuel: Math.round(tarifBase.total_mensuel * 1.05),
            abonnement_annuel: tarifBase.abonnement_annuel,
            cout_consommation: Math.round(tarifBase.cout_consommation * 1.05),
            prix_kwh: tarifBase.prix_kwh * 1.05
        };
    }

    function calculateOffreMarche(consommation, puissance) {
        // Offre marché = TRV + 15%
        const tarifBase = calculateTrvBase(consommation, puissance);
        return {
            nom: 'Offre de marché',
            total_annuel: Math.round(tarifBase.total_annuel * 1.15),
            total_mensuel: Math.round(tarifBase.total_mensuel * 1.15),
            abonnement_annuel: tarifBase.abonnement_annuel,
            cout_consommation: Math.round(tarifBase.cout_consommation * 1.15),
            prix_kwh: tarifBase.prix_kwh * 1.15
        };
    }

    function calculateConsumptionBreakdown(data) {
        const surface = parseInt(data.surface_totale) || 200;
        const nbPostes = parseInt(data.nb_postes_informatiques) || 5;

        return {
            eclairage: Math.round(surface * 25), // 25 kWh/m²
            informatique: nbPostes * 800,
            climatisation: data.climatisation === 'clim_complete' ? Math.round(surface * 40) : 0,
            chauffage: data.type_chauffage === 'electrique' ? calculateHeatingConsumption(surface, data.isolation) : 0,
            equipements_speciaux: (data.equipements_speciaux && data.equipements_speciaux.length) ? data.equipements_speciaux.length * 2000 : 0,
            autres: Math.round(surface * 10)
        };
    }

    // ================================
    // AFFICHAGE DES RÉSULTATS
    // ================================
    function displayResults(results) {
        const { consommation_totale, puissance_recommandee, tarifs, repartition, eligible_trv } = results;

        // Trouver le meilleur tarif
        const tarifsArray = Object.values(tarifs);
        const meilleurTarif = tarifsArray.reduce((min, tarif) =>
            tarif.total_annuel < min.total_annuel ? tarif : min
        );
        const economiesMax = Math.max(...tarifsArray.map(t => t.total_annuel)) - meilleurTarif.total_annuel;

        const html = `
            <div class="results-summary">
                <!-- Résultat principal -->
                <div class="result-card main-result">
                    <div class="result-icon">🏢</div>
                    <h3>Estimation pour votre entreprise</h3>
                    <div class="big-number">${consommation_totale.toLocaleString()} <span>kWh/an</span></div>
                    <p>Puissance recommandée : <strong>${puissance_recommandee} kVA</strong></p>
                    <p>Secteur : <strong>${getSectorLabel(results.secteur)}</strong></p>
                    ${eligible_trv ?
                '<div class="badge-eligibilite eligible">✅ Éligible aux tarifs réglementés</div>' :
                '<div class="badge-eligibilite non-eligible">❌ Non éligible TRV - Marché libre</div>'
            }
                </div>
                
                <!-- Comparaison des tarifs -->
                <div class="tarifs-comparison">
                    <h3>💰 Comparaison des tarifs disponibles</h3>
                    <div class="tarifs-grid">
                        ${generateTariffCards(tarifs, meilleurTarif.nom)}
                    </div>
                    
                    ${economiesMax > 0 ? `
                        <div class="economies">
                            <h4>💡 Économies potentielles</h4>
                            <p><strong>Jusqu'à ${economiesMax.toLocaleString()}€/an</strong> en choisissant le tarif optimal !</p>
                            <small>Le tarif ${meilleurTarif.nom} est actuellement le plus avantageux pour votre profil.</small>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Répartition de la consommation -->
                <div class="consumption-breakdown">
                    <div class="consumption-header">
                        <h4>📊 Répartition de votre consommation</h4>
                    </div>
                    ${generateConsumptionBreakdown(repartition, consommation_totale)}
                </div>
                
                ${tarifs.tempo ? generateTempoDetails(tarifs.tempo) : ''}
            </div>
        `;

        $('#resultats-professionnel').html(html);

        // Animation d'apparition
        $('.result-card, .tarif-card, .consumption-item').hide().fadeIn(600);
    }

    function generateTariffCards(tarifs, meilleurNom) {
        return Object.values(tarifs).map(tarif => {
            const isRecommended = tarif.nom === meilleurNom;
            const typeClass = getTariffTypeClass(tarif.nom);

            return `
                <div class="tarif-card ${typeClass} ${isRecommended ? 'recommended' : ''}">
                    <h4>${tarif.nom}</h4>
                    <div class="tarif-prix">${tarif.total_annuel.toLocaleString()}€<span>/an</span></div>
                    <div class="tarif-mensuel">${tarif.total_mensuel.toLocaleString()}€/mois</div>
                    <div class="tarif-details">
                        <div>Abonnement : ${tarif.abonnement_annuel.toLocaleString()}€/an</div>
                        <div>Consommation : ${tarif.cout_consommation.toLocaleString()}€/an</div>
                        ${tarif.prix_kwh ? `<div>Prix kWh : ${tarif.prix_kwh.toFixed(4)}€</div>` : ''}
                    </div>
                    ${isRecommended ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                </div>
            `;
        }).join('');
    }

    function getTariffTypeClass(nom) {
        if (nom.includes('TRV')) return 'trv';
        if (nom.includes('française')) return 'offre-francaise';
        if (nom.includes('Tempo')) return 'tempo';
        return 'offre-marche';
    }

    function generateConsumptionBreakdown(repartition, total) {
        const items = [
            { label: 'Éclairage', icon: '💡', value: repartition.eclairage, description: 'Éclairage des locaux' },
            { label: 'Informatique', icon: '💻', value: repartition.informatique, description: 'PC, écrans, imprimantes' },
            { label: 'Climatisation', icon: '❄️', value: repartition.climatisation, description: 'Climatisation et ventilation' },
            { label: 'Chauffage', icon: '🔥', value: repartition.chauffage, description: 'Chauffage électrique' },
            { label: 'Équipements spéciaux', icon: '⚙️', value: repartition.equipements_speciaux, description: 'Machines, frigos pro, serveurs' },
            { label: 'Autres usages', icon: '🔌', value: repartition.autres, description: 'Divers équipements' }
        ];

        return items.map(item => {
            if (item.value === 0) return '';

            const percentage = ((item.value / total) * 100).toFixed(1);

            return `
                <div class="consumption-item">
                    <div class="consumption-row">
                        <div class="consumption-label">
                            <div class="consumption-icon">${item.icon}</div>
                            <div class="consumption-text">
                                <strong>${item.label}</strong>
                                <span>${item.description}</span>
                            </div>
                        </div>
                        <div class="consumption-value">
                            ${item.value.toLocaleString()} <span class="unit">kWh/an</span>
                            <div style="font-size: 0.85rem; color: var(--gray-500);">${percentage}%</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function generateTempoDetails(tempo) {
        if (!tempo.details_tempo) return '';

        return `
            <div class="tempo-details">
                <div class="tempo-header">
                    <div class="tempo-icon"></div>
                    <div class="tempo-title">
                        <h4>Détails du tarif Tempo</h4>
                        <div class="tempo-subtitle">Répartition sur 365 jours</div>
                    </div>
                </div>
                <div class="tempo-periods">
                    <div class="period-card period-bleu">
                        <div class="period-header">
                            <strong>Jours Bleus (300j)</strong>
                        </div>
                        <div class="period-cost">${tempo.details_tempo.cout_bleu.toLocaleString()}€</div>
                        <div class="period-details">Tarif avantageux toute l'année</div>
                    </div>
                    <div class="period-card period-blanc">
                        <div class="period-header">
                            <strong>Jours Blancs (43j)</strong>
                        </div>
                        <div class="period-cost">${tempo.details_tempo.cout_blanc.toLocaleString()}€</div>
                        <div class="period-details">Tarif intermédiaire</div>
                    </div>
                    <div class="period-card period-rouge">
                        <div class="period-header">
                            <strong>Jours Rouges (22j)</strong>
                        </div>
                        <div class="period-cost">${tempo.details_tempo.cout_rouge.toLocaleString()}€</div>
                        <div class="period-details">Tarif élevé - à éviter</div>
                    </div>
                </div>
            </div>
        `;
    }

    function getSectorLabel(secteur) {
        const labels = {
            bureau: 'Bureau / Services',
            commerce: 'Commerce',
            restauration: 'Restauration',
            industrie_legere: 'Industrie légère',
            sante: 'Santé',
            education: 'Éducation',
            autre: 'Autre activité'
        };
        return labels[secteur] || 'Non spécifié';
    }

    function showCalculationError() {
        $('#resultats-professionnel').html(`
            <div class="error-state">
                <div class="error-icon">⚠️</div>
                <h3>Erreur de calcul</h3>
                <p>Une erreur s'est produite lors du calcul de votre simulation.</p>
                <button type="button" class="btn btn-primary" onclick="calculateResults()">
                    Réessayer
                </button>
            </div>
        `);

        $('#btn-calculer').prop('disabled', false).html('Calculer ma facture <span>📊</span>');
        isCalculating = false;
    }

    // ================================
    // UTILITAIRES
    // ================================
    function debugLog(...args) {
        if (config.debug) {
            console.log('🏢 [DEBUG]', ...args);
        }
    }

    // ================================
    // API PUBLIQUE
    // ================================
    window.HticSimulateurElecProfessionnel = {
        getCurrentStep: () => currentStep,
        getFormData: () => formData,
        goToStep: (step) => {
            if (step >= 1 && step <= totalSteps) {
                currentStep = step;
                updateUI();
            }
        },
        recalculate: calculateResults,
        reset: () => {
            formData = {};
            currentStep = 1;
            $('.form-step input, .form-step select').val('').prop('checked', false);
            updateUI();
        }
    };

})(jQuery);