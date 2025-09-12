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
        setupSimulationsRapides();
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
                type_chauffage: 'convecteurs',
                type_cuisson: 'induction',
                electromenagers: ['lave_linge', 'refrigerateur', 'four'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'non',
                equipements_speciaux: [],
                preference_tarif: 'indifferent',
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
                type_chauffage: 'inertie',
                type_cuisson: 'induction',
                electromenagers: ['lave_linge', 'seche_linge', 'refrigerateur', 'lave_vaisselle', 'four', 'congelateur'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'non',
                equipements_speciaux: [],
                preference_tarif: 'hc',
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
                type_chauffage: 'pac',
                type_cuisson: 'induction',
                electromenagers: ['lave_linge', 'seche_linge', 'refrigerateur', 'lave_vaisselle', 'four', 'congelateur', 'cave_a_vin'],
                eau_chaude: 'oui',
                type_eclairage: 'led',
                piscine: 'simple',
                equipements_speciaux: ['spa_jacuzzi', 'voiture_electrique'],
                preference_tarif: 'hc',
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

        // IMPORTANT: Retirer l'attribut required des champs non visibles
        $('.form-step:not(.active)').find('input[required], select[required]').each(function () {
            $(this).removeAttr('required').attr('data-was-required', 'true');
        });

        // Afficher l'état de chargement sur le bouton
        const $button = $(`.profil-rapide-card[data-profil="${profil}"]`);
        $button.addClass('loading');

        // Remplir le formulaire avec les données du profil
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
        console.log('🎯 Structure exacte des résultats:', JSON.stringify(results, null, 2));
        console.log('🎯 Affichage résultats rapides:', results);

        // VÉRIFICATION CORRIGÉE - Vérifier la vraie structure des données
        if (!results.consommation_annuelle || !results.tarifs) {
            displayError('Données de résultats incomplètes pour la simulation rapide');
            return;
        }

        // Extraire les données avec la vraie structure
        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const puissanceRecommandee = results.puissance_recommandee || '12';

        // Tarifs 
        const tarifBase = results.tarifs.base || {};
        const tarifHC = results.tarifs.hc || {};
        const economie = results.tarifs.economie_potentielle || 0;
        const tarifRecommande = results.tarifs.tarif_recommande || 'base';

        // Répartition
        const repartition = results.repartition || {};

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
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <p>Puissance recommandée : <strong>${puissanceRecommandee} kVA</strong></p>
                <small>Basé sur un profil type - Utilisez le formulaire personnalisé pour plus de précision</small>
            </div>
            
            <!-- Comparaison des tarifs -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des tarifs</h3>
                <div class="tarifs-grid">
                    <div class="tarif-card ${tarifRecommande === 'base' ? 'recommended' : ''}">
                        <h4>Tarif BASE</h4>
                        <div class="tarif-prix">${(tarifBase.total_annuel || 0).toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${(tarifBase.total_mensuel || 0).toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                    <div class="tarif-card ${tarifRecommande === 'hc' ? 'recommended' : ''}">
                        <h4>Heures Creuses</h4>
                        <div class="tarif-prix">${(tarifHC.total_annuel || 0).toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${(tarifHC.total_mensuel || 0).toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                </div>
                <div class="economies">
                    <p>💡 <strong>Économies potentielles :</strong> ${Math.round(economie).toLocaleString()}€/an en choisissant le meilleur tarif !</p>
                </div>
            </div>
            
            <!-- Répartition simplifiée -->
            <div class="repartition-conso">
                <h3>📊 Répartition de la consommation</h3>
                <div class="repartition-details">
                    ${(repartition.chauffage || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #ef4444;"></span>
                        <span>Chauffage : ${(repartition.chauffage || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${(repartition.eau_chaude || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #3b82f6;"></span>
                        <span>Eau chaude : ${(repartition.eau_chaude || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${(repartition.electromenagers || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #10b981;"></span>
                        <span>Électroménagers : ${(repartition.electromenagers || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${(repartition.cuisson || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #f97316;"></span>
                        <span>Cuisson : ${(repartition.cuisson || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${(repartition.multimedia || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #8b5cf6;"></span>
                        <span>Multimédia : ${(repartition.multimedia || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${(repartition.eclairage || 0) > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #f59e0b;"></span>
                        <span>Éclairage : ${(repartition.eclairage || 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                    ${repartition.equipements_speciaux && Object.values(repartition.equipements_speciaux).some(v => v > 0) ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #06b6d4;"></span>
                        <span>Équipements spéciaux : ${Object.values(repartition.equipements_speciaux).reduce((a, b) => (a || 0) + (b || 0), 0).toLocaleString()} kWh/an</span>
                    </div>` : ''}
                </div>
            </div>
            
            <!-- Détails techniques rapides - AFFICHAGE DIRECT -->
            ${results.details_calcul ? `
            <div class="details-rapide" style="margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4>📊 Détails complets du calcul</h4>
                
                <!-- Informations générales -->
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <p><strong>Version :</strong> ${results.details_calcul.methode_calcul || 'HTIC v2.0'}</p>
                    <p><strong>Timestamp :</strong> ${results.details_calcul.timestamp || ''}</p>
                    <p><strong>Paramètres utilisés :</strong> ${results.details_calcul.donnees_config_utilisees?.nb_parametres || 0}</p>
                </div>
                
                <!-- Détails par poste de consommation -->
                <h5>📋 Détails par poste :</h5>
                
                ${results.details_calcul.chauffage ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #ef4444;">
                    <h6>🔥 Chauffage : ${Math.round(results.repartition.chauffage || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        • Type : ${results.details_calcul.chauffage.type_chauffage || 'Non spécifié'}<br>
                        • Surface : ${results.details_calcul.chauffage.surface_chauffee || 0} m²<br>
                        • Conso/m² : ${results.details_calcul.chauffage.consommation_m2 || 0} kWh/m²/an<br>
                        • Isolation : ${results.details_calcul.chauffage.isolation || 'N/A'}<br>
                        • <strong>Calcul :</strong> ${results.details_calcul.chauffage.calcul || 'N/A'}
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.eau_chaude && results.repartition.eau_chaude > 0 ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #3b82f6;">
                    <h6>💧 Eau chaude : ${Math.round(results.repartition.eau_chaude || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        • Base : ${results.details_calcul.eau_chaude.base_kwh || 0} kWh<br>
                        • Personnes : ${results.details_calcul.eau_chaude.nb_personnes || 0}<br>
                        • Coefficient : ${results.details_calcul.eau_chaude.coefficient || 1}<br>
                        • <strong>Calcul :</strong> ${results.details_calcul.eau_chaude.calcul || 'N/A'}
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.electromenagers ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #10b981;">
                    <h6>🏠 Électroménagers : ${Math.round(results.repartition.electromenagers || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        ${results.details_calcul.electromenagers.details ?
                        Object.entries(results.details_calcul.electromenagers.details).map(([key, item]) =>
                            `• ${item.nom} : ${Math.round(item.final_kwh || 0)} kWh`
                        ).join('<br>') : 'Détails non disponibles'
                    }
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.cuisson && results.repartition.cuisson > 0 ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #f97316;">
                    <h6>🍳 Cuisson : ${Math.round(results.repartition.cuisson || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        • Type : ${results.details_calcul.cuisson.type_cuisson || 'N/A'}<br>
                        • <strong>Calcul :</strong> ${results.details_calcul.cuisson.calcul || 'N/A'}
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.eclairage ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #f59e0b;">
                    <h6>💡 Éclairage : ${Math.round(results.repartition.eclairage || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        • Type : ${results.details_calcul.eclairage.type_eclairage || 'N/A'}<br>
                        • Surface : ${results.details_calcul.eclairage.surface || 0} m²<br>
                        • <strong>Calcul :</strong> ${results.details_calcul.eclairage.calcul || 'N/A'}
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.multimedia ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #8b5cf6;">
                    <h6>📺 Multimédia : ${Math.round(results.repartition.multimedia || 0)} kWh/an</h6>
                    <small style="color: #666;">
                        • Inclus automatiquement<br>
                        • <strong>Calcul :</strong> ${results.details_calcul.multimedia.calcul || 'N/A'}
                    </small>
                </div>
                ` : ''}
                
                ${results.details_calcul.equipements_speciaux && Object.keys(results.details_calcul.equipements_speciaux.details_calcul || {}).length > 0 ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 4px solid #06b6d4;">
                    <h6>⚡ Équipements spéciaux : ${Math.round(Object.values(results.repartition.equipements_speciaux || {}).reduce((a, b) => (a || 0) + (b || 0), 0))} kWh/an</h6>
                    <small style="color: #666;">
                        ${Object.entries(results.details_calcul.equipements_speciaux.details_calcul || {})
                        .map(([key, value]) => `• ${value}`)
                        .join('<br>')}
                    </small>
                </div>
                ` : ''}
                
                <!-- Résumé total -->
                <div style="background: #e8f4fd; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                    <h6>⚡ Total général : ${results.consommation_annuelle.toLocaleString()} kWh/an</h6>
                </div>
            </div>
            ` : ''}
            
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
            restartSimulation();
            $('html, body').animate({
                scrollTop: $('.progress-container').offset().top - 50
            }, 600);
        });

        $('#btn-autre-profil').on('click', function () {
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
        // Type chauffage obligatoire
        const typeChauffage = stepElement.find('input[name="type_chauffage"]:checked');
        if (!typeChauffage.length) {
            // Ne pas bloquer si l'étape n'est pas visible
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
    }

    function validateStep3(stepElement) {
        // Type cuisson obligatoire
        const typeCuisson = stepElement.find('input[name="type_cuisson"]:checked');
        if (!typeCuisson.length) {
            // Ne pas bloquer si l'étape n'est pas visible
            if (!stepElement.is(':visible')) return true;
            return false;
        }
        return true;
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
        console.log('Affichage des résultats:', results);

        // Vérifier que toutes les données nécessaires sont présentes
        if (!results || !results.consommation_annuelle || !results.tarifs) {
            displayError('Données de résultats incomplètes');
            return;
        }

        // Adapter les données au format attendu
        const consommationAnnuelle = parseInt(results.consommation_annuelle) || 0;
        const puissanceRecommandee = results.puissance_recommandee || '12';

        // Tarifs avec gestion des différents formats
        const tarifBase = results.tarifs.base || {};
        const tarifHC = results.tarifs.hc || {};

        const totalAnnuelBase = parseInt(tarifBase.total_annuel) || parseInt(tarifBase.annuel) || 0;
        const totalMensuelBase = parseInt(tarifBase.total_mensuel) || parseInt(tarifBase.mensuel) || Math.round(totalAnnuelBase / 12);

        const totalAnnuelHC = parseInt(tarifHC.total_annuel) || parseInt(tarifHC.annuel) || 0;
        const totalMensuelHC = parseInt(tarifHC.total_mensuel) || parseInt(tarifHC.mensuel) || Math.round(totalAnnuelHC / 12);

        // Répartition avec gestion flexible
        const repartition = results.repartition || {};
        const chauffage = parseInt(repartition.chauffage) || 0;
        const eauChaude = parseInt(repartition.eau_chaude) || 0;
        const electromenagers = parseInt(repartition.electromenagers) || 0;
        const cuisson = parseInt(repartition.cuisson) || 0;
        const eclairage = parseInt(repartition.eclairage) || 0;
        const multimedia = parseInt(repartition.multimedia) || 0;
        const equipementsSpeciaux = parseInt(repartition.equipements_speciaux) || 0;
        const autres = parseInt(repartition.autres) || 0;

        // Calculer l'économie potentielle
        const economie = Math.abs(totalAnnuelBase - totalAnnuelHC);
        const tarifRecommande = results.tarifs.tarif_recommande || (totalAnnuelHC < totalAnnuelBase ? 'hc' : 'base');

        const resultsHtml = `
        <div class="results-summary">
            <!-- Résultat principal -->
            <div class="result-card main-result">
                <div class="result-icon">⚡</div>
                <h3>Votre consommation estimée</h3>
                <div class="big-number">${consommationAnnuelle.toLocaleString()} <span>kWh/an</span></div>
                <p>Puissance recommandée : <strong>${puissanceRecommandee} kVA</strong></p>
            </div>
            
            <!-- Comparaison des tarifs -->
            <div class="tarifs-comparison">
                <h3>💰 Comparaison des tarifs</h3>
                <div class="tarifs-grid">
                    <div class="tarif-card ${tarifRecommande === 'base' ? 'recommended' : ''}">
                        <h4>Tarif BASE</h4>
                        <div class="tarif-prix">${totalAnnuelBase.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelBase.toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'base' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                    <div class="tarif-card ${tarifRecommande === 'hc' ? 'recommended' : ''}">
                        <h4>Heures Creuses</h4>
                        <div class="tarif-prix">${totalAnnuelHC.toLocaleString()}€<span>/an</span></div>
                        <div class="tarif-mensuel">${totalMensuelHC.toLocaleString()}€/mois</div>
                        ${tarifRecommande === 'hc' ? '<span class="recommended-badge">⭐ Recommandé</span>' : ''}
                    </div>
                </div>
                ${economie > 0 ? `
                <div class="economies">
                    <p>💡 <strong>Économies potentielles :</strong> jusqu'à ${economie.toLocaleString()}€/an en choisissant le bon tarif !</p>
                </div>
                ` : ''}
            </div>
            
            <!-- Répartition de la consommation -->
            <div class="repartition-conso">
                <h3>📊 Répartition de votre consommation</h3>
                <div class="repartition-details">
                    ${chauffage > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #ef4444;"></span>
                        <span>Chauffage : ${chauffage.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${eauChaude > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #3b82f6;"></span>
                        <span>Eau chaude : ${eauChaude.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${electromenagers > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #10b981;"></span>
                        <span>Électroménager : ${electromenagers.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${cuisson > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #f97316;"></span>
                        <span>Cuisson : ${cuisson.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${eclairage > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #f59e0b;"></span>
                        <span>Éclairage : ${eclairage.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${multimedia > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #8b5cf6;"></span>
                        <span>Multimédia : ${multimedia.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${equipementsSpeciaux > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #06b6d4;"></span>
                        <span>Équipements spéciaux : ${equipementsSpeciaux.toLocaleString()} kWh</span>
                    </div>` : ''}
                    ${autres > 0 ? `
                    <div class="repartition-item">
                        <span class="repartition-color" style="background: #6b7280;"></span>
                        <span>Autres : ${autres.toLocaleString()} kWh</span>
                    </div>` : ''}
                </div>
            </div>
            
            <!-- Récapitulatif -->
            <div class="recap-section">
                <h3>📋 Récapitulatif de vos informations</h3>
                <div class="recap-grid">
                    <div class="recap-item">
                        <strong>Type de logement :</strong> ${getLogementLabel(results.recap?.type_logement || 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Surface :</strong> ${results.recap?.surface || 'Non spécifié'} m²
                    </div>
                    <div class="recap-item">
                        <strong>Nombre de personnes :</strong> ${results.recap?.nb_personnes || 'Non spécifié'}
                    </div>
                    <div class="recap-item">
                        <strong>Chauffage :</strong> ${getHeatingLabel(results.recap?.type_chauffage || 'Non spécifié')}
                    </div>
                    <div class="recap-item">
                        <strong>Eau chaude :</strong> ${results.recap?.eau_chaude === 'oui' ? 'Électrique' : 'Autre énergie'}
                    </div>
                </div>
            </div>
            
            <!-- Détails techniques - AFFICHAGE DIRECT -->
            ${results.details_calcul ? `
            <div class="details-technique" style="margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4>📊 Détails complets du calcul</h4>
                
                <!-- Méthode de calcul -->
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <p><strong>🔧 Méthode :</strong> ${results.details_calcul.methode_calcul || 'Calcul standard'}</p>
                    <p><strong>📅 Timestamp :</strong> ${results.details_calcul.timestamp || 'Non spécifié'}</p>
                    <p><strong>📊 Paramètres utilisés :</strong> ${results.details_calcul.donnees_config_utilisees?.nb_parametres || 0}</p>
                </div>
                
                <!-- Détails par poste -->
                <h5>🔍 Détails par poste de consommation :</h5>
                
                <!-- CHAUFFAGE -->
                ${results.details_calcul.chauffage ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>🔥 Chauffage (${results.repartition.chauffage} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Type : ${results.details_calcul.chauffage.type_chauffage || 'Non électrique'}</li>
                        <li>Isolation : ${results.details_calcul.chauffage.isolation || 'N/A'}</li>
                        <li>Consommation/m² : ${results.details_calcul.chauffage.consommation_m2 || 0} kWh/m²/an</li>
                        <li>Surface chauffée : ${results.details_calcul.chauffage.surface_chauffee || 0} m²</li>
                        <li>Coefficient logement : ${results.details_calcul.chauffage.coefficient_logement || 1}</li>
                        <li><strong>Calcul :</strong> ${results.details_calcul.chauffage.calcul || 'N/A'}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- EAU CHAUDE -->
                ${results.details_calcul.eau_chaude ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>💧 Eau chaude (${results.repartition.eau_chaude} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Base : ${results.details_calcul.eau_chaude.base_kwh || 0} kWh/an</li>
                        <li>Nombre de personnes : ${results.details_calcul.eau_chaude.nb_personnes || 1}</li>
                        <li>Coefficient : ${results.details_calcul.eau_chaude.coefficient || 1}</li>
                        <li><strong>Calcul :</strong> ${results.details_calcul.eau_chaude.calcul || 'N/A'}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- ÉLECTROMÉNAGERS -->
                ${results.details_calcul.electromenagers && results.details_calcul.electromenagers.details ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>🏠 Électroménagers (${results.repartition.electromenagers} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        ${Object.entries(results.details_calcul.electromenagers.details).map(([key, item]) => `
                            <li>${item.nom} : ${Math.round(item.base_kwh)} kWh × ${item.coefficient} = ${Math.round(item.final_kwh)} kWh/an</li>
                        `).join('')}
                    </ul>
                </div>
                ` : ''}
                
                <!-- CUISSON -->
                ${results.details_calcul.cuisson ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>🍳 Cuisson (${results.repartition.cuisson} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Type : ${results.details_calcul.cuisson.type_cuisson || 'N/A'}</li>
                        <li>Base : ${results.details_calcul.cuisson.base_kwh || 0} kWh/an</li>
                        <li>Coefficient : ${results.details_calcul.cuisson.coefficient || 1}</li>
                        <li><strong>Calcul :</strong> ${results.details_calcul.cuisson.calcul || 'N/A'}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- ÉCLAIRAGE -->
                ${results.details_calcul.eclairage ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>💡 Éclairage (${results.repartition.eclairage} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Type : ${results.details_calcul.eclairage.type_eclairage || 'N/A'}</li>
                        <li>Surface : ${results.details_calcul.eclairage.surface || 0} m²</li>
                        <li>Consommation/m² : ${results.details_calcul.eclairage.consommation_m2 || 0} kWh/m²/an</li>
                        <li><strong>Calcul :</strong> ${results.details_calcul.eclairage.calcul || 'N/A'}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- MULTIMÉDIA -->
                ${results.details_calcul.multimedia ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>📺 Multimédia/TV/PC (${results.repartition.multimedia} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Base : ${results.details_calcul.multimedia.base_kwh || 0} kWh/an</li>
                        <li>Coefficient : ${results.details_calcul.multimedia.coefficient || 1}</li>
                        <li><strong>Calcul :</strong> ${results.details_calcul.multimedia.calcul || 'N/A'}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- ÉQUIPEMENTS SPÉCIAUX -->
                ${results.details_calcul.equipements_speciaux ? `
                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 0.5rem;">
                    <h6>⚡ Équipements spéciaux (${Object.values(results.repartition.equipements_speciaux || {}).reduce((a, b) => a + b, 0)} kWh/an)</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        ${Object.entries(results.details_calcul.equipements_speciaux.details_calcul || {}).map(([key, value]) => `
                            <li>${value}</li>
                        `).join('')}
                    </ul>
                </div>
                ` : ''}
                
                <!-- COEFFICIENTS APPLIQUÉS -->
                ${results.details_calcul.coefficients ? `
                <div style="background: #e8f4fd; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                    <h6>🧮 Coefficients globaux appliqués</h6>
                    <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <li>Coefficient logement (${results.recap.type_logement}) : ${results.details_calcul.coefficients.logement}</li>
                        <li>Coefficient personnes (${results.recap.nb_personnes} pers.) : ${results.details_calcul.coefficients.personnes}</li>
                    </ul>
                </div>
                ` : ''}
                
                <!-- TARIFS DÉTAILLÉS -->
                ${results.tarifs ? `
                <div style="background: #f0f9ff; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                    <h6>💰 Détails des tarifs</h6>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                        <div>
                            <strong>Tarif BASE :</strong>
                            <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                                <li>Abonnement : ${results.tarifs.base.abonnement_mensuel}€/mois</li>
                                <li>Prix kWh : ${results.tarifs.base.prix_kwh}€</li>
                                <li>Puissance : ${results.tarifs.base.puissance_kva} kVA</li>
                            </ul>
                        </div>
                        <div>
                            <strong>Tarif Heures Creuses :</strong>
                            <ul style="margin: 0.5rem 0; font-size: 0.9rem;">
                                <li>Abonnement : ${results.tarifs.hc.abonnement_mensuel}€/mois</li>
                                <li>HP : ${results.tarifs.hc.prix_kwh_hp}€ (${results.tarifs.hc.repartition_hp}%)</li>
                                <li>HC : ${results.tarifs.hc.prix_kwh_hc}€ (${results.tarifs.hc.repartition_hc}%)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            <!-- Actions -->
            <div class="results-actions">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimer les résultats</button>
                <button class="btn btn-secondary" onclick="downloadPDF()">📄 Télécharger PDF</button>
                <button class="btn btn-outline" onclick="shareResults()">📤 Partager</button>
            </div>
        </div>
    `;

        $('#results-container').html(resultsHtml);
        $('.results-summary').hide().fadeIn(600);

        console.log('✅ Résultats affichés avec succès');
    }

    // Fonction pour partager les résultats
    window.shareResults = function () {
        if (navigator.share) {
            navigator.share({
                title: 'Mon estimation de consommation électrique',
                text: `Ma consommation estimée: ${results.consommation_annuelle} kWh/an`,
                url: window.location.href
            });
        } else {
            // Fallback pour les navigateurs qui ne supportent pas Web Share API
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Lien copié dans le presse-papier !');
            });
        }
    };

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

    function getHeatingLabel(type) {
        const labels = {
            'convecteurs': '🔥 Convecteurs électriques',
            'inertie': '🌡️ Radiateurs à inertie',
            'clim_reversible': '❄️ Climatisation réversible',
            'pac': '💨 Pompe à chaleur',
            'autre': '🚫 Pas de chauffage électrique'
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