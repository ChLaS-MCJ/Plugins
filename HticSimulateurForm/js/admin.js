// js/admin.js - Scripts pour l'interface d'administration HTIC Simulateur

jQuery(document).ready(function ($) {

    // Variables globales
    let saveTimeout;
    let hasUnsavedChanges = false;
    let currentActiveTab = 'tab-elec-residentiel';

    // Initialisation
    init();

    function init() {
        setupTabNavigation();
        setupAutoSave();
        setupFormValidation();
        setupImportExport();
        setupSearchFunctionality();
        setupTooltips();
        restoreActiveTab();
        setupCollapsibleSections();
    }

    // =================
    // GESTION DES ONGLETS
    // =================

    function setupTabNavigation() {
        $('.nav-tab').on('click', function (e) {
            e.preventDefault();

            if (hasUnsavedChanges) {
                if (!confirm('Vous avez des modifications non sauvegardées. Voulez-vous continuer ?')) {
                    return;
                }
                hasUnsavedChanges = false;
            }

            // Retirer la classe active de tous les onglets et contenus
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-pane').removeClass('active');

            // Ajouter la classe active à l'onglet cliqué
            $(this).addClass('nav-tab-active');

            // Afficher le contenu correspondant
            const targetTab = $(this).attr('href');
            $(targetTab).addClass('active');

            // Sauvegarder l'onglet actif
            currentActiveTab = targetTab.replace('#', '');
            localStorage.setItem('htic_simulateur_active_tab', currentActiveTab);

            // Animation d'entrée
            $(targetTab).hide().fadeIn(300);

            showNotification('Onglet "' + $(this).text() + '" activé', 'info', 2000);
        });
    }

    function restoreActiveTab() {
        const savedTab = localStorage.getItem('htic_simulateur_active_tab');
        if (savedTab && $('#' + savedTab).length) {
            $('.nav-tab[href="#' + savedTab + '"]').trigger('click');
        }
    }

    // =================
    // SAUVEGARDE AUTOMATIQUE
    // =================

    function setupAutoSave() {
        // Détecter les changements
        $('input[type="number"]').on('input change', function () {
            hasUnsavedChanges = true;
            markFieldAsModified($(this));

            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function () {
                autoSaveCurrentTab();
            }, 2000); // Sauvegarde après 2 secondes d'inactivité
        });

        // Sauvegarde manuelle via boutons
        $('.submit input[type="submit"]').on('click', function (e) {
            e.preventDefault();
            saveCurrentTab(true); // Force save
        });
    }

    function autoSaveCurrentTab() {
        saveCurrentTab(false);
    }

    function saveCurrentTab(manual = false) {
        const activeTabPane = $('.tab-pane.active');
        const tabId = activeTabPane.attr('id');
        const formData = {};

        // Collecter toutes les données du formulaire actif
        activeTabPane.find('input[type="number"]').each(function () {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value !== '') {
                // Extraire le nom du champ en retirant le préfixe
                const fieldName = name.replace(/^htic_simulateur_\w+_data\[/, '').replace(/\]$/, '');
                formData[fieldName] = parseFloat(value) || 0;
            }
        });

        // Vérifier s'il y a des données à sauvegarder
        if (Object.keys(formData).length === 0) {
            if (manual) {
                showNotification('Aucune donnée à sauvegarder', 'warning');
            }
            return;
        }

        // Afficher l'indicateur de sauvegarde
        showSaveStatus('saving');

        // Envoyer via AJAX
        $.ajax({
            url: htic_simulateur_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_simulateur_data',
                nonce: htic_simulateur_ajax.nonce,
                tab: getTabTypeFromId(tabId),
                data: formData
            },
            beforeSend: function () {
                if (manual) {
                    showNotification('Sauvegarde en cours...', 'info');
                }
            },
            success: function (response) {
                if (response.success) {
                    hasUnsavedChanges = false;
                    showSaveStatus('success');
                    markAllFieldsAsSaved();

                    if (manual) {
                        showNotification('✅ Données sauvegardées avec succès !', 'success');
                    } else {
                        showNotification('💾 Sauvegarde automatique effectuée', 'success', 3000);
                    }

                    // Animation de confirmation
                    $('.submit input[type="submit"]').addClass('button-pulse');
                    setTimeout(() => {
                        $('.submit input[type="submit"]').removeClass('button-pulse');
                    }, 300);

                } else {
                    showSaveStatus('error');
                    showNotification('❌ Erreur lors de la sauvegarde: ' + (response.data || 'Erreur inconnue'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showSaveStatus('error');
                showNotification('❌ Erreur de connexion lors de la sauvegarde', 'error');
            }
        });
    }

    function showSaveStatus(status) {
        let $status = $('.save-status');
        if (!$status.length) {
            $status = $('<div class="save-status"></div>').appendTo('body');
        }

        $status.removeClass('show error');

        switch (status) {
            case 'saving':
                $status.text('💾 Sauvegarde...').addClass('show');
                break;
            case 'success':
                $status.text('✅ Sauvegardé').addClass('show');
                setTimeout(() => $status.removeClass('show'), 2000);
                break;
            case 'error':
                $status.text('❌ Erreur').addClass('show error');
                setTimeout(() => $status.removeClass('show'), 4000);
                break;
        }
    }

    // =================
    // VALIDATION DES FORMULAIRES
    // =================

    function setupFormValidation() {
        $('input[type="number"]').on('blur', function () {
            validateField($(this));
        });

        $('input[type="number"]').on('input', function () {
            const $field = $(this);
            const value = parseFloat($field.val());
            const min = parseFloat($field.attr('min'));
            const max = parseFloat($field.attr('max'));

            // Validation en temps réel
            $field.removeClass('field-error field-success');

            if (isNaN(value)) {
                $field.addClass('field-error');
                setFieldStatus($field, 'invalid');
                return;
            }

            if ((min !== undefined && value < min) || (max !== undefined && value > max)) {
                $field.addClass('field-error');
                setFieldStatus($field, 'invalid');
            } else {
                $field.addClass('field-success');
                setFieldStatus($field, 'valid');
            }
        });
    }

    function validateField($field) {
        const value = parseFloat($field.val());
        const min = parseFloat($field.attr('min'));
        const max = parseFloat($field.attr('max'));
        let isValid = true;
        let message = '';

        if (isNaN(value)) {
            isValid = false;
            message = 'Veuillez entrer une valeur numérique valide';
        } else if (min !== undefined && value < min) {
            $field.val(min);
            message = 'Valeur ajustée au minimum autorisé (' + min + ')';
            showNotification(message, 'warning', 3000);
        } else if (max !== undefined && value > max) {
            $field.val(max);
            message = 'Valeur ajustée au maximum autorisé (' + max + ')';
            showNotification(message, 'warning', 3000);
        }

        return isValid;
    }

    function setFieldStatus($field, status) {
        const $parent = $field.parent();
        $parent.find('.field-status').remove();

        if (status !== 'none') {
            $('<span class="field-status ' + status + '"></span>').appendTo($parent);
        }
    }

    function markFieldAsModified($field) {
        $field.addClass('preview-change');
        setFieldStatus($field, 'modified');
    }

    function markAllFieldsAsSaved() {
        $('input[type="number"]').removeClass('preview-change field-error field-success');
        $('.field-status').remove();
    }

    // =================
    // IMPORT/EXPORT
    // =================

    function setupImportExport() {

        // Import des données
        $('#import-data').on('click', function () {
            $('#import-file').trigger('click');
        });

        $('#import-file').on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                importConfiguration(file);
            }
        });

        // Reset aux valeurs par défaut
        $('#reset-defaults').on('click', function () {
            if (confirm('⚠️ Êtes-vous sûr de vouloir réinitialiser TOUTES les données aux valeurs par défaut ?\n\nCette action est irréversible !')) {
                resetToDefaults();
            }
        });
    }



    function importConfiguration(file) {
        const reader = new FileReader();

        reader.onload = function (e) {
            try {
                const importedData = JSON.parse(e.target.result);

                // Valider la structure des données
                if (!validateImportData(importedData)) {
                    showNotification('❌ Format de fichier invalide ou incompatible', 'error');
                    return;
                }

                // Afficher les informations du fichier
                const metadata = importedData.metadata || {};
                const confirmMessage = `📁 Fichier d'import détecté :\n\n` +
                    `📅 Date d'export: ${metadata.export_date ? new Date(metadata.export_date).toLocaleDateString() : 'Inconnue'}\n` +
                    `📊 Nombre de champs: ${metadata.total_fields || 'Non spécifié'}\n\n` +
                    `⚠️ Cette action remplacera TOUTES les données actuelles.\n\n` +
                    `Voulez-vous continuer ?`;

                if (confirm(confirmMessage)) {
                    processImport(importedData);
                }

            } catch (error) {
                showNotification('❌ Erreur lors de la lecture du fichier: ' + error.message, 'error');
            }
        };

        reader.readAsText(file);

        // Reset du input file
        $('#import-file').val('');
    }

    function processImport(data) {
        let importedTabs = 0;
        const totalTabs = 4;
        const tabTypes = ['elec_residentiel', 'gaz_residentiel', 'elec_professionnel', 'gaz_professionnel'];

        showNotification('📥 Import en cours...', 'info');

        // Fonction pour importer un onglet
        function importTab(tabType, tabData) {
            $.ajax({
                url: htic_simulateur_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_simulateur_data',
                    nonce: htic_simulateur_ajax.nonce,
                    tab: tabType,
                    data: tabData
                },
                success: function (response) {
                    importedTabs++;

                    if (response.success) {
                        showNotification(`✅ ${tabType} importé (${importedTabs}/${totalTabs})`, 'success', 2000);
                    } else {
                        showNotification(`❌ Erreur lors de l'import de ${tabType}`, 'error');
                    }

                    if (importedTabs === totalTabs) {
                        // Toutes les données sont importées
                        setTimeout(() => {
                            showNotification('🎉 Import terminé avec succès ! Rechargement...', 'success');
                            setTimeout(() => location.reload(), 2000);
                        }, 1000);
                    }
                },
                error: function (xhr, status, error) {
                    importedTabs++;
                    showNotification(`❌ Erreur de connexion pour ${tabType}`, 'error');

                    if (importedTabs === totalTabs) {
                        showNotification('⚠️ Import terminé avec des erreurs', 'warning');
                    }
                }
            });
        }

        // Importer chaque onglet
        tabTypes.forEach(tabType => {
            if (data[tabType]) {
                importTab(tabType, data[tabType]);
            } else {
                importedTabs++;
                showNotification(`⚠️ Onglet ${tabType} non trouvé dans le fichier`, 'warning');
            }
        });
    }

    function resetToDefaults() {
        showNotification('🔄 Réinitialisation en cours...', 'info');

        // Simuler une réinitialisation (vous devrez implémenter la logique côté serveur)
        $.ajax({
            url: htic_simulateur_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'reset_simulateur_defaults',
                nonce: htic_simulateur_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    showNotification('✅ Réinitialisation réussie ! Rechargement...', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('❌ Erreur lors de la réinitialisation', 'error');
                }
            },
            error: function () {
                showNotification('❌ Erreur de connexion lors de la réinitialisation', 'error');
            }
        });
    }

    // =================
    // FONCTIONS UTILITAIRES
    // =================

    function getTabTypeFromId(tabId) {
        const mapping = {
            'tab-elec-residentiel': 'elec_residentiel',
            'tab-gaz-residentiel': 'gaz_residentiel',
            'tab-elec-professionnel': 'elec_professionnel',
            'tab-gaz-professionnel': 'gaz_professionnel'
        };
        return mapping[tabId] || 'elec_residentiel';
    }

    function collectTabData(tabId) {
        const data = {};
        $('#' + tabId + ' input[type="number"]').each(function () {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value !== '') {
                const fieldName = name.replace(/^htic_simulateur_\w+_data\[/, '').replace(/\]$/, '');
                data[fieldName] = parseFloat(value) || 0;
            }
        });
        return data;
    }

    function validateImportData(data) {
        const requiredTabs = ['elec_residentiel', 'gaz_residentiel', 'elec_professionnel', 'gaz_professionnel'];

        // Vérifier qu'au moins un onglet est présent
        const hasAtLeastOneTab = requiredTabs.some(tab => data[tab] && typeof data[tab] === 'object');

        if (!hasAtLeastOneTab) {
            return false;
        }

        // Vérifier la structure des données présentes
        for (let tab of requiredTabs) {
            if (data[tab] && typeof data[tab] !== 'object') {
                return false;
            }
        }

        return true;
    }

    function showNotification(message, type = 'info', duration = 5000) {
        // Supprimer les anciens messages
        $('.htic-simulateur-message').remove();

        // Déterminer la classe CSS
        const messageClass = type === 'success' ? 'htic-simulateur-updated' :
            type === 'error' ? 'htic-simulateur-error' :
                'htic-simulateur-info';

        // Créer et afficher le nouveau message
        const $message = $('<div class="htic-simulateur-message ' + messageClass + '">' + message + '</div>');

        // Insérer le message en haut de l'onglet actif ou en haut de la page
        const $target = $('.tab-pane.active').length ? $('.tab-pane.active') : $('.wrap');
        $target.prepend($message);

        // Animation d'entrée
        $message.hide().slideDown(300);

        // Supprimer automatiquement
        if (duration > 0) {
            setTimeout(function () {
                $message.slideUp(300, function () {
                    $(this).remove();
                });
            }, duration);
        }
    }

    // =================
    // RECHERCHE ET FILTRES
    // =================

    function setupSearchFunctionality() {
        // Ajouter un champ de recherche
        const searchHtml = `
            <div class="search-container">
                <input type="text" id="field-search" class="field-search" placeholder="Rechercher un champ ou une valeur...">
            </div>
        `;
        $('.htic-simulateur-tabs .nav-tab-wrapper').after(searchHtml);

        $('#field-search').on('input', function () {
            const searchTerm = $(this).val().toLowerCase();
            filterFields(searchTerm);
        });
    }

    function filterFields(searchTerm) {
        $('.form-table tr, .wp-list-table tr').each(function () {
            const $row = $(this);
            const text = $row.text().toLowerCase();
            const isHeader = $row.find('th').length > 0 && $row.find('input').length === 0;

            if (isHeader || text.includes(searchTerm) || searchTerm === '') {
                $row.show();
            } else {
                $row.hide();
            }
        });

        // Afficher un message si aucun résultat
        if (searchTerm && $('.form-table tr:visible, .wp-list-table tr:visible').length <= 1) {
            showNotification('🔍 Aucun champ trouvé pour "' + searchTerm + '"', 'info', 3000);
        }
    }

    // =================
    // TOOLTIPS ET AIDE
    // =================

    function setupTooltips() {
        // Ajouter des tooltips aux champs importants
        $('input[name*="chauffage"]').attr('data-tip', 'Consommation de chauffage en kWh par m² et par an');
        $('input[name*="abo"]').attr('data-tip', 'Abonnement mensuel en euros TTC');
        $('input[name*="kwh"]').attr('data-tip', 'Prix du kWh en euros TTC');
        $('input[name*="coeff"]').attr('data-tip', 'Coefficient multiplicateur pour ajuster la consommation');

        // Tooltips hover
        $('[data-tip]').hover(
            function () {
                const tip = $(this).attr('data-tip');
                if (tip) {
                    $(this).attr('title', tip);
                }
            },
            function () {
                $(this).removeAttr('title');
            }
        );
    }



    // =================
    // SECTIONS PLIABLES
    // =================

    function setupCollapsibleSections() {
        // Convertir les sections en sections pliables si beaucoup de contenu
        $('.htic-simulateur-section').each(function () {
            const $section = $(this);
            const $tables = $section.find('table');

            if ($tables.length > 1) {
                $section.addClass('collapsible-section');
                const $header = $section.find('h3').first();
                $header.addClass('collapsible-header').append('<span class="toggle-icon">▼</span>');

                const $content = $section.find('table, h4, .form-table').wrapAll('<div class="collapsible-content active"></div>');

                $header.on('click', function () {
                    $(this).toggleClass('active');
                    $(this).next('.collapsible-content').toggleClass('active').slideToggle(300);
                });
            }
        });
    }

    // =================
    // GESTION DES ÉVÉNEMENTS GLOBAUX
    // =================

    // Confirmation avant de quitter si des modifications non sauvegardées
    $(window).on('beforeunload', function () {
        if (hasUnsavedChanges) {
            return '⚠️ Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
        }
    });

    // Reset du flag lors de la sauvegarde réussie
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (settings.data && settings.data.indexOf('save_simulateur_data') !== -1) {
            hasUnsavedChanges = false;
        }
    });

    // Gestion des erreurs AJAX globales
    $(document).ajaxError(function (event, xhr, settings, thrownError) {
        if (settings.data && settings.data.indexOf('htic_simulateur') !== -1) {
            showNotification('❌ Erreur de communication avec le serveur', 'error');
        }
    });

});
