// templates/simulateur-unifie.js - Version moderne et professionnelle

jQuery(document).ready(function ($) {

    // Variables globales
    let currentStep = 1;
    let selectedProfile = 'particulier';
    let selectedEnergy = 'elec';
    let currentType = '';
    let config = {};
    let formulaireLoaded = {};

    // Initialisation
    init();

    function init() {
        loadConfiguration();
        setupStepNavigation();
        setupSelectionHandlers();
        setupFormulaireMethods();

        // Pré-sélectionner les options par défaut
        selectProfile('particulier');
        selectEnergy('elec');

    }

    // ================================
    // CHARGEMENT DE LA CONFIGURATION
    // ================================

    function loadConfiguration() {
        const configElement = document.getElementById('simulateur-config-global');
        if (configElement) {
            try {
                config = JSON.parse(configElement.textContent);
            } catch (e) {
                config = { types: {}, ajaxUrl: '', nonce: '', pluginUrl: '' };
            }
        }
    }

    // ================================
    // NAVIGATION ENTRE LES ÉTAPES
    // ================================

    function setupStepNavigation() {
        // Boutons suivant
        $('.btn-next').on('click', function () {
            if (currentStep < 3) {
                goToStep(currentStep + 1);
            }
        });

        // Boutons retour
        $('.btn-back').on('click', function () {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });

        // Bouton de démarrage de simulation
        $('.btn-start-simulation').on('click', function () {
            startSimulation();
        });
    }

    function goToStep(step) {
        // Masquer l'étape actuelle
        $('.selection-step.active').removeClass('active');

        // Afficher la nouvelle étape
        $(`.selection-step[data-step="${step}"]`).addClass('active');

        // Mettre à jour l'indicateur de progression
        $('.step-indicator').removeClass('active');
        $(`.step-indicator[data-step="${step}"]`).addClass('active');

        currentStep = step;

        // Mettre à jour les boutons
        updateNavigationButtons();

        // Actions spécifiques par étape
        if (step === 3) {
            updateSummary();
        }

    }

    function updateNavigationButtons() {
        const $btnNext = $('.btn-next');
        const $btnBack = $('.btn-back');

        // Gérer l'état du bouton suivant selon l'étape
        switch (currentStep) {
            case 1:
                $btnNext.prop('disabled', !selectedProfile);
                break;
            case 2:
                $btnNext.prop('disabled', !selectedEnergy);
                break;
            case 3:
                // Pas de bouton suivant à l'étape 3
                break;
        }
    }

    // ================================
    // GESTION DES SÉLECTIONS
    // ================================

    function setupSelectionHandlers() {
        // Sélection du profil
        $('.profile-card').on('click', function () {
            const profile = $(this).data('profile');
            selectProfile(profile);
        });

        // Sélection de l'énergie
        $('.energy-card').on('click', function () {
            const energy = $(this).data('energy');
            selectEnergy(energy);
        });
    }

    function selectProfile(profile) {
        selectedProfile = profile;

        // Mettre à jour l'interface
        $('.profile-card').removeClass('active');
        $(`.profile-card[data-profile="${profile}"]`).addClass('active');

        // Mettre à jour le type actuel
        updateCurrentType();

        // Débloquer le bouton suivant
        $('.btn-next').prop('disabled', false);

    }

    function selectEnergy(energy) {
        selectedEnergy = energy;

        // Mettre à jour l'interface
        $('.energy-card').removeClass('active');
        $(`.energy-card[data-energy="${energy}"]`).addClass('active');

        // Mettre à jour le type actuel
        updateCurrentType();

        // Débloquer le bouton suivant
        $('.btn-next').prop('disabled', false);

    }

    function updateCurrentType() {
        // Construire le type à partir des sélections
        if (selectedProfile === 'particulier') {
            currentType = selectedEnergy + '-residentiel';
        } else {
            currentType = selectedEnergy + '-professionnel';
        }

    }

    function updateSummary() {
        const typeConfig = config.types[currentType];

        if (typeConfig) {
            // Mettre à jour l'icône
            $('#summary-icon').text(typeConfig.icon);

            // Mettre à jour le badge
            const profileLabel = selectedProfile === 'particulier' ? 'Particulier' : 'Professionnel';
            $('#summary-badge').text(profileLabel);

            // Mettre à jour le titre et description
            $('#summary-title').text(typeConfig.title);
            $('#summary-description').text(typeConfig.subtitle);

        }
    }

    // ================================
    // DÉMARRAGE DE LA SIMULATION
    // ================================

    function startSimulation() {
        if (!currentType || !config.types[currentType]) {
            showNotification('⚠️ Configuration manquante pour ce type de simulation', 'warning');
            return;
        }

        // Animation de transition
        $('.simulateur-selector-moderne').fadeOut(400, function () {
            loadFormulaire(currentType);
        });

        // Mettre à jour l'URL
        updateURL(currentType);
    }

    function loadFormulaire(type) {
        const typeConfig = config.types[type];

        // Mettre à jour l'en-tête du formulaire
        updateFormulaireHeader(typeConfig);

        // Afficher le container de formulaire
        $('.formulaire-container').fadeIn(500);

        // Charger le contenu du formulaire
        if (formulaireLoaded[type]) {
            showCachedFormulaire(type);
        } else {
            loadFormulaireAjax(type);
        }

        // Smooth scroll vers le formulaire
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('.formulaire-container').offset().top - 50
            }, 600);
        }, 500);
    }

    function updateFormulaireHeader(typeConfig) {
        $('.formulaire-icon-moderne').text(typeConfig.icon);
        $('.formulaire-title-moderne').text(typeConfig.title);
        $('.formulaire-subtitle-moderne').text(typeConfig.subtitle);
    }

    function loadFormulaireAjax(type) {
        showLoadingState();

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
                    formulaireLoaded[type] = response.data;
                } else {
                    showErrorState('Erreur lors du chargement: ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function (xhr, status, error) {
                showErrorState('Erreur de connexion au serveur');
            }
        });
    }

    function displayFormulaire(type, data) {
        $('.formulaire-content').html(data.html || data);
        loadFormulaireAssets(type);
        initializeFormulaire(type);
    }

    function showCachedFormulaire(type) {
        const cachedData = formulaireLoaded[type];
        $('.formulaire-content').html(cachedData.html || cachedData);
        initializeFormulaire(type);
    }

    function loadFormulaireAssets(type) {
        const baseUrl = config.pluginUrl + 'formulaires/' + type + '/';

        // Charger CSS s'il n'est pas déjà présent
        if (!$('link[href*="' + type + '.css"]').length) {
            $('<link>')
                .attr('rel', 'stylesheet')
                .attr('href', baseUrl + type + '.css?v=' + Date.now())
                .appendTo('head');
        }

        // Charger JS s'il n'est pas déjà présent
        if (!$('script[src*="' + type + '.js"]').length) {
            $.getScript(baseUrl + type + '.js?v=' + Date.now())
                .done(function () {
                })
                .fail(function () {
                    console.warn('⚠️ Impossible de charger le script', type);
                });
        }
    }

    function initializeFormulaire(type) {
        const typeConfig = config.types[type];

        // Injecter la configuration spécifique
        let configElement = $('.formulaire-content #simulateur-config');
        if (configElement.length === 0) {
            configElement = $('<script>')
                .attr('type', 'application/json')
                .attr('id', 'simulateur-config')
                .appendTo('.formulaire-content');
        }

        configElement.text(JSON.stringify(typeConfig.data, null, 2));

        // Déclencher un événement pour signaler que le formulaire est prêt
        $(document).trigger('htic:formulaire:ready', { type: type, config: typeConfig });
    }

    // ================================
    // GESTION DES ÉTATS
    // ================================

    function showLoadingState() {
        $('.formulaire-content').html(`
            <div class="loading-moderne">
                <div class="loading-spinner-moderne"></div>
                <p>Chargement de votre simulateur personnalisé...</p>
            </div>
        `);
    }

    function showErrorState(message) {
        $('.formulaire-content').html(`
            <div class="error-moderne">
                <div class="error-icon">⚠️</div>
                <h3>Erreur de chargement</h3>
                <p>${message}</p>
                <div class="error-actions">
                    <button type="button" class="btn-retry">
                        🔄 Réessayer
                    </button>
                    <button type="button" class="btn-back-to-selector">
                        ← Retour au menu
                    </button>
                </div>
            </div>
        `);

        // Gestionnaires pour les boutons d'erreur
        $('.btn-retry').on('click', function () {
            loadFormulaireAjax(currentType);
        });
    }

    // ================================
    // MÉTHODES DU FORMULAIRE
    // ================================

    function setupFormulaireMethods() {
        // Bouton retour vers le sélecteur
        $(document).on('click', '.btn-back-to-selector', function () {
            returnToSelector();
        });

        // Gestion de l'historique navigateur
        $(window).on('popstate', function (e) {
            if (e.originalEvent.state && e.originalEvent.state.simulateurType) {
                const type = e.originalEvent.state.simulateurType;
                setTypeFromString(type);
                loadFormulaire(type);
            } else {
                returnToSelector();
            }
        });
    }

    function returnToSelector() {
        $('.formulaire-container').fadeOut(400, function () {
            $('.simulateur-selector-moderne').fadeIn(500);
        });

        // Smooth scroll vers le sélecteur
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('.simulateur-selector-moderne').offset().top - 100
            }, 600);
        }, 400);

        // Mettre à jour l'historique
        if (history.pushState) {
            history.pushState(null, '', window.location.pathname);
        }

    }

    function setTypeFromString(type) {
        // Parser le type pour mettre à jour les sélections
        if (type.includes('residentiel')) {
            selectedProfile = 'particulier';
        } else if (type.includes('professionnel')) {
            selectedProfile = 'professionnel';
        }

        if (type.includes('elec')) {
            selectedEnergy = 'elec';
        } else if (type.includes('gaz')) {
            selectedEnergy = 'gaz';
        }

        currentType = type;

        // Mettre à jour l'interface
        selectProfile(selectedProfile);
        selectEnergy(selectedEnergy);

        // Aller directement à l'étape 3
        goToStep(3);
    }

    // ================================
    // UTILITAIRES
    // ================================

    function showNotification(message, type = 'info', duration = 4000) {
        // Supprimer les anciens messages
        $('.notification-moderne').remove();

        const notificationClass = `notification-moderne notification-${type}`;
        const $notification = $(`
            <div class="${notificationClass}">
                <span class="notification-icon">
                    ${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'error' ? '❌' : 'ℹ️'}
                </span>
                <span class="notification-text">${message}</span>
            </div>
        `);

        // Ajouter le message au container principal
        $('.htic-simulateur-unifie').prepend($notification);

        // Animation d'entrée
        $notification.hide().slideDown(400);

        // Suppression automatique
        if (duration > 0) {
            setTimeout(() => {
                $notification.slideUp(400, () => $notification.remove());
            }, duration);
        }
    }

    function updateURL(type) {
        if (history.pushState) {
            const url = new URL(window.location);
            url.searchParams.set('simulateur', type);
            history.pushState({ simulateurType: type }, '', url);
        }
    }

    // ================================
    // ANIMATIONS ET EFFETS
    // ================================

    function addNotificationStyles() {
        if (!$('#notification-styles').length) {
            $(`
                <style id="notification-styles">
                .notification-moderne {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    margin-bottom: 1rem;
                    font-weight: 500;
                    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
                }
                
                .notification-info {
                    background: #eff6ff;
                    color: #1d4ed8;
                    border-left: 4px solid #3b82f6;
                }
                
                .notification-success {
                    background: #f0fdf4;
                    color: #16a34a;
                    border-left: 4px solid #22c55e;
                }
                
                .notification-warning {
                    background: #fffbeb;
                    color: #d97706;
                    border-left: 4px solid #f59e0b;
                }
                
                .notification-error {
                    background: #fef2f2;
                    color: #dc2626;
                    border-left: 4px solid #ef4444;
                }
                
                .error-moderne {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 4rem 2rem;
                    text-align: center;
                }
                
                .error-moderne .error-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    color: #ef4444;
                }
                
                .error-moderne h3 {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #1f2937;
                    margin: 0 0 1rem 0;
                }
                
                .error-moderne p {
                    color: #6b7280;
                    margin: 0 0 2rem 0;
                    font-size: 1.1rem;
                }
                
                .error-actions {
                    display: flex;
                    gap: 1rem;
                    flex-wrap: wrap;
                    justify-content: center;
                }
                
                .error-actions button {
                    padding: 0.75rem 1.5rem;
                    border-radius: 8px;
                    font-weight: 500;
                    cursor: pointer;
                    border: none;
                    transition: all 0.2s ease;
                }
                
                .btn-retry {
                    background: #3b82f6;
                    color: white;
                }
                
                .btn-retry:hover {
                    background: #2563eb;
                    transform: translateY(-1px);
                }
                </style>
            `).appendTo('head');
        }
    }

    // ================================
    // INITIALISATION AVANCÉE
    // ================================

    // Détecter le type depuis l'URL au chargement
    $(window).on('load', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const urlType = urlParams.get('simulateur');

        if (urlType && config.types && config.types[urlType]) {
            setTimeout(() => {
                setTypeFromString(urlType);
                startSimulation();
            }, 1000);
        }
    });

    // Gestion du redimensionnement
    $(window).on('resize', debounce(function () {
        // Ajustements responsive si nécessaire
        updateResponsiveElements();
    }, 250));

    function updateResponsiveElements() {
        // Ajustements pour mobile/tablet
        if ($(window).width() < 768) {
            // Logique spécifique mobile
        }
    }

    // Fonction utilitaire debounce
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ================================
    // EFFETS VISUELS AVANCÉS
    // ================================

    function initVisualEffects() {
        // Animation des éléments flottants
        animateFloatingElements();

        // Effet parallax léger sur le hero
        setupParallaxEffect();

        // Animation au scroll
        setupScrollAnimations();
    }

    function animateFloatingElements() {
        $('.element').each(function (index) {
            const $element = $(this);
            const delay = index * 1500;

            setInterval(() => {
                $element.addClass('pulse-effect');
                setTimeout(() => {
                    $element.removeClass('pulse-effect');
                }, 1000);
            }, 6000 + delay);
        });
    }

    function setupParallaxEffect() {
        $(window).on('scroll', throttle(function () {
            const scrolled = $(window).scrollTop();
            const parallax = scrolled * 0.3;

            $('.floating-elements').css('transform', `translateY(${parallax}px)`);
        }, 16));
    }

    function setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observer les éléments à animer
        $('.profile-card, .energy-card, .help-card').each(function () {
            observer.observe(this);
        });
    }

    // Fonction utilitaire throttle
    function throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // ================================
    // ACCESSIBILITÉ
    // ================================

    function setupAccessibility() {
        // Navigation au clavier
        setupKeyboardNavigation();

        // Annonces pour lecteurs d'écran
        setupScreenReaderAnnouncements();

        // Focus management
        setupFocusManagement();
    }

    function setupKeyboardNavigation() {
        $(document).on('keydown', function (e) {
            // Navigation avec les flèches
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                handleArrowNavigation(e);
            }

            // Enter/Space pour sélectionner
            if (e.key === 'Enter' || e.key === ' ') {
                handleSelectionKeys(e);
            }

            // Échap pour revenir en arrière
            if (e.key === 'Escape') {
                handleEscapeKey(e);
            }
        });
    }

    function handleArrowNavigation(e) {
        const $focused = $(document.activeElement);

        if ($focused.hasClass('profile-card')) {
            e.preventDefault();
            const $cards = $('.profile-card');
            const currentIndex = $cards.index($focused);
            const nextIndex = e.key === 'ArrowRight' ?
                (currentIndex + 1) % $cards.length :
                (currentIndex - 1 + $cards.length) % $cards.length;

            $cards.eq(nextIndex).focus();
        }

        if ($focused.hasClass('energy-card')) {
            e.preventDefault();
            const $cards = $('.energy-card');
            const currentIndex = $cards.index($focused);
            const nextIndex = e.key === 'ArrowRight' ?
                (currentIndex + 1) % $cards.length :
                (currentIndex - 1 + $cards.length) % $cards.length;

            $cards.eq(nextIndex).focus();
        }
    }

    function handleSelectionKeys(e) {
        const $focused = $(document.activeElement);

        if ($focused.hasClass('profile-card')) {
            e.preventDefault();
            $focused.click();
        }

        if ($focused.hasClass('energy-card')) {
            e.preventDefault();
            $focused.click();
        }

        if ($focused.hasClass('btn-next') || $focused.hasClass('btn-start-simulation')) {
            e.preventDefault();
            $focused.click();
        }
    }

    function handleEscapeKey(e) {
        if ($('.formulaire-container').is(':visible')) {
            e.preventDefault();
            returnToSelector();
        }
    }

    function setupScreenReaderAnnouncements() {
        // Créer une zone d'annonces cachée
        if (!$('#sr-announcements').length) {
            $('<div id="sr-announcements" aria-live="polite" aria-atomic="true" class="sr-only"></div>')
                .appendTo('body');
        }
    }

    function announceToScreenReader(message) {
        $('#sr-announcements').text(message);

        // Nettoyer après un délai
        setTimeout(() => {
            $('#sr-announcements').empty();
        }, 1000);
    }

    function setupFocusManagement() {
        // Rendre les cartes focusables
        $('.profile-card, .energy-card').attr('tabindex', '0');

        // Ajouter des labels ARIA
        $('.profile-card').attr('role', 'button').attr('aria-pressed', 'false');
        $('.energy-card').attr('role', 'button').attr('aria-pressed', 'false');

        // Mettre à jour les états ARIA lors des sélections
        $('.profile-card').on('click', function () {
            $('.profile-card').attr('aria-pressed', 'false');
            $(this).attr('aria-pressed', 'true');

            const profileText = $(this).find('h3').text();
            announceToScreenReader(`${profileText} sélectionné`);
        });

        $('.energy-card').on('click', function () {
            $('.energy-card').attr('aria-pressed', 'false');
            $(this).attr('aria-pressed', 'true');

            const energyText = $(this).find('h3').text();
            announceToScreenReader(`${energyText} sélectionné`);
        });
    }

    // ================================
    // INITIALISATION FINALE
    // ================================

    // Ajouter les styles de notification
    addNotificationStyles();

    // Initialiser les effets visuels
    initVisualEffects();

    // Initialiser l'accessibilité
    setupAccessibility();

    // ================================
    // API PUBLIQUE
    // ================================

    window.HticSimulateurModerne = {
        // Méthodes publiques
        selectProfile: selectProfile,
        selectEnergy: selectEnergy,
        goToStep: goToStep,
        startSimulation: startSimulation,
        returnToSelector: returnToSelector,

        // Getters
        getCurrentStep: () => currentStep,
        getCurrentType: () => currentType,
        getSelectedProfile: () => selectedProfile,
        getSelectedEnergy: () => selectedEnergy,
        getConfig: () => config,

        // Utilitaires
        showNotification: showNotification,
        reloadFormulaire: (type) => {
            formulaireLoaded[type || currentType] = null;
            loadFormulaire(type || currentType);
        }
    };

    // Ajouter des styles CSS supplémentaires
    $(`
        <style>
        .pulse-effect {
            animation: pulse 1s ease-in-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .animate-in {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .profile-card:focus,
        .energy-card:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        </style>
    `).appendTo('head');

    // Déclencher un événement personnalisé
    $(document).trigger('htic:simulateur:ready', {
        currentStep: currentStep,
        selectedProfile: selectedProfile,
        selectedEnergy: selectedEnergy,
        currentType: currentType
    });
});