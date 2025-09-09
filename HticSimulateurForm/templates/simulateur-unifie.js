// templates/simulateur-unifie.js

jQuery(document).ready(function ($) {

    // Variables globales
    let currentCategory = 'particulier';
    let currentType = '';
    let config = {};
    let formulaireLoaded = {};

    // Initialisation
    init();

    function init() {
        loadConfiguration();
        setupTabNavigation();
        setupTypeSelection();
        setupFormulaireMethods();

        // Vérifier s'il y a un type par défaut
        if (config.defaultType && config.defaultType !== '') {
            selectTypeDirectly(config.defaultType);
        }

        console.log('🚀 Simulateur unifié initialisé');
    }

    // ================================
    // CHARGEMENT DE LA CONFIGURATION
    // ================================

    function loadConfiguration() {
        const configElement = document.getElementById('simulateur-config-global');
        if (configElement) {
            try {
                config = JSON.parse(configElement.textContent);
                console.log('📊 Configuration globale chargée:', Object.keys(config.types).length, 'types');
            } catch (e) {
                console.error('❌ Erreur chargement configuration:', e);
                config = { types: {}, defaultType: '', ajaxUrl: '', nonce: '', pluginUrl: '' };
            }
        }
    }

    // ================================
    // NAVIGATION ENTRE ONGLETS
    // ================================

    function setupTabNavigation() {
        // Navigation principale (Particulier/Professionnel)
        $('.main-tab').on('click', function () {
            const category = $(this).data('category');
            switchMainCategory(category);
        });
    }

    function switchMainCategory(category) {
        if (category === currentCategory) return;

        // Mettre à jour les onglets principaux
        $('.main-tab').removeClass('active');
        $('.main-tab[data-category="' + category + '"]').addClass('active');

        // Mettre à jour les groupes d'énergie
        $('.energy-group').removeClass('active');
        $('.energy-group[data-category="' + category + '"]').addClass('active');

        // Réinitialiser la sélection des énergies
        $('.energy-tab').removeClass('active');
        $('.energy-group[data-category="' + category + '"] .energy-tab:first').addClass('active');

        currentCategory = category;
        currentType = '';

        console.log('📂 Catégorie changée:', category);
    }

    // ================================
    // SÉLECTION DU TYPE D'ÉNERGIE
    // ================================

    function setupTypeSelection() {
        // Sélection au clic sur une carte énergie
        $('.energy-tab').on('click', function () {
            const type = $(this).data('type');
            selectType(type);
        });

        // Navigation par clavier
        $(document).on('keydown', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                navigateEnergyTabs(e.key === 'ArrowRight');
            }
            if (e.key === 'Enter') {
                const activeTab = $('.energy-tab.active');
                if (activeTab.length) {
                    const type = activeTab.data('type');
                    selectType(type);
                }
            }
        });
    }

    function selectType(type) {
        if (!config.types[type]) {
            console.error('❌ Type non trouvé:', type);
            return;
        }

        // Mettre à jour la sélection visuelle
        $('.energy-group.active .energy-tab').removeClass('active');
        $('.energy-tab[data-type="' + type + '"]').addClass('active');

        currentType = type;

        // Animation de transition
        $('#type-selector').fadeOut(300, function () {
            loadFormulaire(type);
        });

        console.log('⚡ Type sélectionné:', type);
    }

    function selectTypeDirectly(type) {
        // Sélection directe d'un type (via paramètre defaultType)
        if (!config.types[type]) return;

        // Déterminer la catégorie
        const category = type.includes('residentiel') ? 'particulier' : 'professionnel';

        // Mettre à jour l'interface
        switchMainCategory(category);
        selectType(type);
    }

    function navigateEnergyTabs(goRight) {
        const activeGroup = $('.energy-group.active');
        const tabs = activeGroup.find('.energy-tab');
        const currentIndex = tabs.index($('.energy-tab.active'));

        let newIndex;
        if (goRight) {
            newIndex = (currentIndex + 1) % tabs.length;
        } else {
            newIndex = currentIndex - 1 < 0 ? tabs.length - 1 : currentIndex - 1;
        }

        tabs.removeClass('active');
        tabs.eq(newIndex).addClass('active');
    }

    // ================================
    // CHARGEMENT DES FORMULAIRES
    // ================================

    function loadFormulaire(type) {
        const typeConfig = config.types[type];

        // Mettre à jour l'en-tête du formulaire
        updateFormulaireHeader(typeConfig);

        // Afficher le container de formulaire
        $('#formulaire-container').fadeIn(400);

        // Charger le contenu du formulaire
        if (formulaireLoaded[type]) {
            // Si déjà chargé, réutiliser
            showCachedFormulaire(type);
        } else {
            // Charger via AJAX
            loadFormulaireAjax(type);
        }

        // Smooth scroll vers le formulaire
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('#formulaire-container').offset().top - 50
            }, 500);
        }, 400);
    }

    function updateFormulaireHeader(typeConfig) {
        $('.formulaire-icon').text(typeConfig.icon);
        $('#formulaire-title-text').text(typeConfig.title);
        $('#formulaire-subtitle-text').text(typeConfig.subtitle);
    }

    function loadFormulaireAjax(type) {
        // Afficher l'état de chargement
        showLoadingState();

        // Charger le formulaire via AJAX
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'htic_load_formulaire',
                type: type,
                nonce: config.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayFormulaire(type, response.data);

                    // Mettre en cache
                    formulaireLoaded[type] = response.data;
                } else {
                    showErrorState('Erreur lors du chargement du formulaire');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Erreur AJAX:', error);

                // Fallback: charger le formulaire directement
                loadFormulaireFallback(type);
            }
        });
    }

    function loadFormulaireFallback(type) {
        // Fallback: charger le template directement via fetch
        const templateUrl = config.pluginUrl + 'formulaires/' + type.replace('-', '-') + '/' + type + '.php';

        fetch(templateUrl)
            .then(response => response.text())
            .then(html => {
                displayFormulaire(type, { html: html });
                formulaireLoaded[type] = { html: html };
            })
            .catch(error => {
                console.error('❌ Erreur fallback:', error);
                showErrorState('Impossible de charger le formulaire');
            });
    }

    function displayFormulaire(type, data) {
        // Injecter le HTML du formulaire
        $('#formulaire-content').html(data.html || data);

        // Charger les ressources spécifiques (CSS/JS)
        loadFormulaireAssets(type);

        // Initialiser le formulaire
        initializeFormulaire(type);

        console.log('✅ Formulaire', type, 'chargé et initialisé');
    }

    function showCachedFormulaire(type) {
        const cachedData = formulaireLoaded[type];
        $('#formulaire-content').html(cachedData.html || cachedData);

        // Réinitialiser le formulaire
        initializeFormulaire(type);

        console.log('📋 Formulaire', type, 'restauré depuis le cache');
    }

    function loadFormulaireAssets(type) {
        const baseUrl = config.pluginUrl + 'formulaires/' + type + '/';

        // Charger le CSS spécifique
        if (!$('link[href="' + baseUrl + type + '.css"]').length) {
            $('<link>')
                .attr('rel', 'stylesheet')
                .attr('href', baseUrl + type + '.css?v=' + Date.now())
                .appendTo('head');
        }

        // Charger le JS spécifique
        if (!$('script[src="' + baseUrl + type + '.js"]').length) {
            $('<script>')
                .attr('src', baseUrl + type + '.js?v=' + Date.now())
                .appendTo('body');
        }
    }

    function initializeFormulaire(type) {
        // Injecter la configuration spécifique au type
        const typeConfig = config.types[type];

        // Créer ou mettre à jour l'élément de configuration
        let configElement = $('#simulateur-config');
        if (configElement.length === 0) {
            configElement = $('<script>')
                .attr('type', 'application/json')
                .attr('id', 'simulateur-config')
                .appendTo('#formulaire-content');
        }

        configElement.text(JSON.stringify(typeConfig.data, null, 2));

        // Déclencher un événement personnalisé pour signaler que le formulaire est prêt
        $(document).trigger('htic:formulaire:ready', { type: type, config: typeConfig });
    }

    // ================================
    // MÉTHODES DU FORMULAIRE
    // ================================

    function setupFormulaireMethods() {
        // Bouton retour à la sélection
        $(document).on('click', '#back-to-selection', function () {
            returnToSelection();
        });

        // Écouter les événements de changement de formulaire
        $(document).on('htic:formulaire:change', function (e, data) {
            if (data.type && data.type !== currentType) {
                selectType(data.type);
            }
        });

        // Gestion de l'historique du navigateur
        $(window).on('popstate', function (e) {
            if (e.originalEvent.state && e.originalEvent.state.hticType) {
                selectType(e.originalEvent.state.hticType);
            } else {
                returnToSelection();
            }
        });
    }

    function returnToSelection() {
        // Masquer le formulaire et revenir à la sélection
        $('#formulaire-container').fadeOut(300, function () {
            $('#type-selector').fadeIn(400);
        });

        // Smooth scroll vers la sélection
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('#type-selector').offset().top - 50
            }, 500);
        }, 300);

        currentType = '';

        // Mettre à jour l'historique
        if (history.pushState) {
            history.pushState(null, '', window.location.pathname);
        }

        console.log('🔙 Retour à la sélection');
    }

    // ================================
    // ÉTATS DE CHARGEMENT ET D'ERREUR
    // ================================

    function showLoadingState() {
        $('#formulaire-content').html(`
            <div class="loading-formulaire">
                <div class="loading-spinner"></div>
                <p>Chargement du formulaire...</p>
            </div>
        `);
    }

    function showErrorState(message) {
        $('#formulaire-content').html(`
            <div class="error-formulaire">
                <div class="error-icon">⚠️</div>
                <h3>Erreur de chargement</h3>
                <p>${message}</p>
                <button type="button" class="btn btn-primary" id="retry-load">
                    Réessayer
                </button>
                <button type="button" class="btn btn-secondary" id="back-to-selection-error">
                    Retour à la sélection
                </button>
            </div>
        `);

        // Gérer les boutons d'erreur
        $('#retry-load').on('click', function () {
            if (currentType) {
                loadFormulaireAjax(currentType);
            }
        });

        $('#back-to-selection-error').on('click', function () {
            returnToSelection();
        });
    }

    // ================================
    // MÉTHODES UTILITAIRES
    // ================================

    function updateURL(type) {
        // Mettre à jour l'URL sans recharger la page
        if (history.pushState) {
            const url = new URL(window.location);
            url.searchParams.set('simulateur', type);
            history.pushState({ hticType: type }, '', url);
        }
    }

    function getTypeFromURL() {
        // Récupérer le type depuis l'URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('simulateur');
    }

    // ================================
    // ÉVÉNEMENTS GLOBAUX
    // ================================

    // Gestion du redimensionnement
    $(window).on('resize', function () {
        // Ajustements responsive si nécessaire
    });

    // Initialisation basée sur l'URL
    $(window).on('load', function () {
        const urlType = getTypeFromURL();
        if (urlType && config.types[urlType] && !config.defaultType) {
            selectTypeDirectly(urlType);
        }
    });

    // Gestion des erreurs globales
    $(document).ajaxError(function (event, xhr, settings, thrownError) {
        if (settings.data && settings.data.indexOf('htic_load_formulaire') !== -1) {
            console.error('❌ Erreur AJAX formulaire:', thrownError);
        }
    });

    // ================================
    // API PUBLIQUE
    // ================================

    // Exposer des méthodes publiques
    window.HticSimulateurUnifie = {
        selectType: selectType,
        returnToSelection: returnToSelection,
        getCurrentType: () => currentType,
        getCurrentCategory: () => currentCategory,
        getConfig: () => config,
        reloadFormulaire: (type) => {
            formulaireLoaded[type] = null;
            loadFormulaire(type || currentType);
        }
    };

    // Événement personnalisé pour signaler que le simulateur est prêt
    $(document).trigger('htic:simulateur:ready', {
        currentType: currentType,
        currentCategory: currentCategory
    });

    console.log('✅ Simulateur unifié prêt !');
});

// ================================
// STYLES CSS POUR LES ÉTATS D'ERREUR
// ================================

// Injecter les styles pour les états d'erreur
const errorStyles = `
<style>
.error-formulaire {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
}

.error-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.error-formulaire h3 {
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-800);
}

.error-formulaire p {
    margin: 0 0 2rem 0;
    color: var(--gray-600);
    font-size: 1.1rem;
}

.error-formulaire .btn {
    margin: 0 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.error-formulaire .btn-primary {
    background: var(--primary);
    color: white;
}

.error-formulaire .btn-secondary {
    background: var(--gray-500);
    color: white;
}

.error-formulaire .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
</style>
`;

if (!document.querySelector('#htic-error-styles')) {
    $('head').append(errorStyles.replace('<style>', '<style id="htic-error-styles">'));
}