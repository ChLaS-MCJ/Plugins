/**
 * JavaScript pour le formulaire Gaz Résidentiel
 * Fichier: formulaires/gaz-residentiel/gaz-residentiel.js
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Initialisation du simulateur gaz résidentiel');

    // Variables globales
    const communeSelect = document.getElementById('commune');
    const nbPersonnesInput = document.getElementById('nb_personnes');
    const chauffageRadios = document.querySelectorAll('input[name="chauffage_gaz"]');
    const chauffageDetails = document.querySelector('.chauffage-details');
    const autreCommuneDetails = document.getElementById('autre-commune-details');
    const typeGazInfo = document.getElementById('type-gaz-info');
    const typeGazText = document.getElementById('type-gaz-text');

    // Initialisation
    init();

    function init() {
        // Charger les communes depuis le back-office
        loadCommunesFromBackoffice();

        // Mettre à jour les estimations initiales
        updateConsumptionEstimates();

        // Gérer l'affichage conditionnel du chauffage
        toggleChauffageDetails();

        // Events listeners
        setupEventListeners();
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

        // Boutons navigation (si vous avez un système d'étapes)
        setupNavigationButtons();

        // Bouton calcul
        const btnCalculate = document.querySelector('.btn-calculate');
        if (btnCalculate) {
            btnCalculate.addEventListener('click', calculateGazEstimation);
        }
    }

    function setupNavigationButtons() {
        // Gestion des boutons suivant/précédent si vous les implémentez
        const btnNext = document.querySelectorAll('.btn-next');
        const btnPrev = document.querySelectorAll('.btn-prev');

        btnNext.forEach(btn => {
            btn.addEventListener('click', function () {
                // Logique de navigation entre étapes
                console.log('Étape suivante');
            });
        });

        btnPrev.forEach(btn => {
            btn.addEventListener('click', function () {
                // Logique de navigation entre étapes
                console.log('Étape précédente');
            });
        });
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

        // Récupérer toutes les données du formulaire
        const formData = new FormData(document.getElementById('simulateur-gaz-residentiel'));

        // Validation de base
        if (!validateForm(formData)) {
            return;
        }

        // Préparer les données pour l'envoi
        const userData = prepareUserData(formData);

        console.log('Données utilisateur préparées:', userData);

        // Afficher un indicateur de chargement
        showLoadingState();

        // Appel AJAX vers le calculateur (à implémenter)
        sendCalculationRequest(userData);
    }

    function validateForm(formData) {
        const communeValue = formData.get('commune');

        // Vérifier la commune
        if (!communeValue) {
            showError('Veuillez sélectionner votre commune');
            return false;
        }

        // Si "Autre" est sélectionné, vérifier les champs supplémentaires
        if (communeValue === 'autre') {
            const nomCommune = formData.get('nom_commune_autre');
            const typeGaz = formData.get('type_gaz_autre');

            if (!nomCommune || !nomCommune.trim()) {
                showError('Veuillez saisir le nom de votre commune');
                return false;
            }

            if (!typeGaz) {
                showError('Veuillez sélectionner le type de gaz disponible');
                return false;
            }
        }

        // Autres validations
        const superficie = formData.get('superficie');
        if (!superficie || parseInt(superficie) < 20) {
            showError('Veuillez saisir une superficie valide (minimum 20 m²)');
            return false;
        }

        const nbPersonnes = formData.get('nb_personnes');
        if (!nbPersonnes || parseInt(nbPersonnes) < 1) {
            showError('Veuillez saisir un nombre de personnes valide');
            return false;
        }

        return true;
    }

    function prepareUserData(formData) {
        const communeValue = formData.get('commune');
        let communeFinale, typeGazFinal;

        if (communeValue === 'autre') {
            // Pour les communes "autre"
            communeFinale = formData.get('nom_commune_autre')?.trim().toUpperCase() || '';
            typeGazFinal = formData.get('type_gaz_autre') || 'naturel';
        } else {
            // Pour les communes pré-configurées
            communeFinale = communeValue;
            const selectedOption = communeSelect.options[communeSelect.selectedIndex];
            typeGazFinal = selectedOption?.getAttribute('data-type') || 'naturel';
        }

        return {
            // Logement
            commune: communeFinale,
            type_gaz: typeGazFinal,
            superficie: parseInt(formData.get('superficie')) || 150,
            nb_personnes: parseInt(formData.get('nb_personnes')) || 5,
            type_logement: formData.get('type_logement') || 'maison',

            // Usages
            chauffage_gaz: formData.get('chauffage_gaz') || 'oui',
            isolation: formData.get('isolation') || 'niveau_1',
            eau_chaude: formData.get('eau_chaude') || 'gaz',
            cuisson: formData.get('cuisson') || 'gaz',
            offre: formData.get('offre') || 'base',

            // Métadonnées
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent
        };
    }

    function sendCalculationRequest(userData) {
        // Vérifier si hticSimulateur est disponible
        if (typeof hticSimulateur === 'undefined') {
            showError('Configuration manquante. Veuillez recharger la page.');
            hideLoadingState();
            return;
        }

        // Appel AJAX vers le calculateur
        fetch(hticSimulateur.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'htic_calculate_estimation',
                nonce: hticSimulateur.nonce,
                type: 'gaz-residentiel',
                user_data: JSON.stringify(userData),
                config_data: '' // Sera récupéré côté serveur
            })
        })
            .then(response => response.json())
            .then(data => {
                hideLoadingState();

                if (data.success) {
                    displayResults(data.data);
                } else {
                    showError(data.data || 'Erreur lors du calcul');
                }
            })
            .catch(error => {
                console.error('Erreur calcul:', error);
                hideLoadingState();
                showError('Erreur technique. Veuillez réessayer.');
            });
    }

    // ================================
    // AFFICHAGE DES RÉSULTATS
    // ================================

    function displayResults(results) {
        console.log('Affichage des résultats:', results);

        const calculContainer = document.getElementById('calcul-container');
        if (!calculContainer) return;

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
            </div>
        `;

        // Afficher les actions
        const calculActions = document.getElementById('calcul-actions');
        if (calculActions) {
            calculActions.style.display = 'block';
        }
    }

    function formatUsageLabel(key) {
        const labels = {
            'chauffage': '🔥 Chauffage',
            'eau_chaude': '🚿 Eau chaude',
            'cuisson': '🍳 Cuisson'
        };
        return labels[key] || key;
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

        // Désactiver le bouton de calcul
        const btnCalculate = document.querySelector('.btn-calculate');
        if (btnCalculate) {
            btnCalculate.disabled = true;
            btnCalculate.textContent = 'Calcul en cours...';
        }
    }

    function hideLoadingState() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }

        // Réactiver le bouton de calcul
        const btnCalculate = document.querySelector('.btn-calculate');
        if (btnCalculate) {
            btnCalculate.disabled = false;
            btnCalculate.innerHTML = '<span class="btn-icon">🔍</span> Calculer mon estimation';
        }
    }

    // ================================
    // FONCTIONS PUBLIQUES
    // ================================

    // Exposer certaines fonctions globalement si nécessaire
    window.hticGazResidentiel = {
        calculateEstimation: calculateGazEstimation,
        updateEstimates: updateConsumptionEstimates,
        validateForm: function () {
            const formData = new FormData(document.getElementById('simulateur-gaz-residentiel'));
            return validateForm(formData);
        }
    };

    console.log('Simulateur gaz résidentiel initialisé');
});