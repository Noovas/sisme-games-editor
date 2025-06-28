/**
 * File: /sisme-games-editor/includes/user/user-preferences/assets/user-preferences.js
 * Script pour les préférences utilisateur avec auto-save
 * 
 * RESPONSABILITÉ:
 * - Auto-sauvegarde des préférences avec debouncing
 * - Gestion des toggles iOS et multi-sélections
 * - Reset des préférences avec confirmation
 * - Indicateurs visuels de sauvegarde
 * - Événements personnalisés pour intégrations externes
 */
(function($) {
    'use strict';
    
    // Namespace global
    window.SismeUserPreferences = window.SismeUserPreferences || {};
    
    // Configuration
    const config = window.sismeUserPreferences || {
        ajax_url: '',
        security: '',
        auto_save: true,
        save_delay: 1000,
        user_id: 0,
        i18n: {
            saving: 'Sauvegarde en cours...',
            saved: 'Sauvegardé !',
            error: 'Erreur lors de la sauvegarde',
            reset_confirm: 'Êtes-vous sûr de vouloir réinitialiser vos préférences ?',
            reset_success: 'Préférences réinitialisées'
        },
        debug: false
    };
    
    // Variables globales
    let saveTimeout = null;
    let isInitialized = false;
    let saveIndicator = null;
    
    /**
     * Initialisation principale
     */
    function init() {
        if (isInitialized) {
            return;
        }
        
        // Vérifier la présence du formulaire
        if (!$('.sisme-preferences-form').length) {
            return;
        }
        
        // Initialiser les composants
        initSaveIndicator();
        initToggleHandlers();
        initMultiSelectHandlers();
        initResetHandler();
        initGlobalActions();
        
        isInitialized = true;
        
        log('✅ Sisme User Preferences initialisé', config);
        
        // Déclencher événement d'initialisation
        $(document).trigger('sisme_preferences_initialized');
    }
    
    /**
     * Initialiser l'indicateur de sauvegarde
     */
    function initSaveIndicator() {
        saveIndicator = $('.sisme-save-indicator');
        
        if (!saveIndicator.length) {
            // Créer l'indicateur s'il n'existe pas
            saveIndicator = $('<div class="sisme-save-indicator" style="display: none;"><span class="sisme-save-text"></span></div>');
            $('.sisme-preferences-form').prepend(saveIndicator);
        }
    }
    
    /**
     * Initialiser les gestionnaires de toggles
     */
    function initToggleHandlers() {
        $(document).on('change', '.sisme-preference-toggle', function() {
            const $toggle = $(this);
            const key = $toggle.data('preference-key');
            const value = $toggle.is(':checked');
            
            log('🔄 Toggle modifié:', {key, value});
            
            // Animation du toggle
            $toggle.closest('.sisme-toggle-container').addClass('sisme-toggle-animating');
            setTimeout(() => {
                $toggle.closest('.sisme-toggle-container').removeClass('sisme-toggle-animating');
            }, 300);
            
            // Auto-save
            if (config.auto_save) {
                autoSavePreference(key, value);
            }
            
            // Déclencher événement
            $(document).trigger('sisme_preference_changed', [key, value, 'toggle']);
        });
    }
    
    /**
     * Initialiser les gestionnaires de multi-sélection
     */
    function initMultiSelectHandlers() {
        // Changement d'un checkbox individuel
        $(document).on('change', '.sisme-multi-select-checkbox', function() {
            const $checkbox = $(this);
            const $multiSelect = $checkbox.closest('.sisme-multi-select');
            const key = $multiSelect.data('preference-key');
            
            // Mettre à jour l'état visuel
            updateMultiSelectItem($checkbox);
            
            // Récupérer toutes les valeurs sélectionnées
            const selectedValues = getMultiSelectValues($multiSelect);
            
            log('🔄 Multi-select modifié:', {key, selectedValues});
            
            // Auto-save
            if (config.auto_save) {
                autoSavePreference(key, selectedValues);
            }
            
            // Déclencher événement
            $(document).trigger('sisme_preference_changed', [key, selectedValues, 'multi_select']);
        });
        
        // Boutons "Tout sélectionner" / "Tout désélectionner"
        $(document).on('click', '.sisme-select-all', function(e) {
            e.preventDefault();
            const $multiSelect = $(this).closest('.sisme-multi-select');
            toggleAllMultiSelect($multiSelect, true);
        });
        
        $(document).on('click', '.sisme-select-none', function(e) {
            e.preventDefault();
            const $multiSelect = $(this).closest('.sisme-multi-select');
            toggleAllMultiSelect($multiSelect, false);
        });
    }
    
    /**
     * Initialiser le gestionnaire de reset
     */
    function initResetHandler() {
        $(document).on('click', '.sisme-reset-preferences', function(e) {
            e.preventDefault();
            
            if (confirm(config.i18n.reset_confirm)) {
                resetAllPreferences();
            }
        });
    }
    
    /**
     * Initialiser les actions globales
     */
    function initGlobalActions() {
        // Raccourcis clavier (optionnel)
        $(document).on('keydown', function(e) {
            // Ctrl+S pour sauvegarder manuellement
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                manualSave();
            }
        });
    }
    
    /**
     * Auto-sauvegarde d'une préférence avec debouncing
     */
    function autoSavePreference(key, value) {
        if (!config.auto_save) {
            return;
        }
        
        // Annuler la sauvegarde précédente si elle est en attente
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        
        // Afficher l'indicateur de sauvegarde
        showSaveIndicator('saving');
        
        // Programmer la sauvegarde avec délai
        saveTimeout = setTimeout(() => {
            savePreference(key, value);
        }, config.save_delay);
    }
    
    /**
     * Sauvegarder une préférence via AJAX
     */
    function savePreference(key, value) {
        log('💾 Sauvegarde préférence:', {key, value});
        
        // Traitement spécial pour les notifications (clé avec point)
        let ajaxData = {
            action: 'sisme_update_user_preference',
            security: config.security,
            preference_key: key,
            preference_value: value
        };
        
        // Si c'est une notification (clé avec point), traiter différemment
        if (key.includes('.')) {
            const [mainKey, subKey] = key.split('.');
            
            // Récupérer toutes les notifications actuelles
            const currentNotifications = getCurrentNotificationValues();
            currentNotifications[subKey] = value;
            
            ajaxData.preference_key = mainKey;
            ajaxData.preference_value = currentNotifications;
        }
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                log('✅ Sauvegarde réussie:', response);
                
                if (response.success) {
                    showSaveIndicator('success');
                    
                    // Déclencher événement de succès
                    $(document).trigger('sisme_preference_saved', [key, value, true]);
                } else {
                    showSaveIndicator('error');
                    log('❌ Erreur serveur:', response.data);
                    
                    // Déclencher événement d'erreur
                    $(document).trigger('sisme_preference_error', [key, response.data.message || 'Erreur inconnue']);
                }
            },
            error: function(xhr, status, error) {
                log('❌ Erreur AJAX:', {xhr, status, error});
                showSaveIndicator('error');
                
                // Déclencher événement d'erreur
                $(document).trigger('sisme_preference_error', [key, error]);
            }
        });
    }
    
    /**
     * Récupérer les valeurs actuelles des notifications
     */
    function getCurrentNotificationValues() {
        const notifications = {};
        
        $('.sisme-preference-toggle[data-preference-key^="notifications."]').each(function() {
            const $toggle = $(this);
            const fullKey = $toggle.data('preference-key');
            const subKey = fullKey.split('.')[1];
            notifications[subKey] = $toggle.is(':checked');
        });
        
        return notifications;
    }
    
    /**
     * Réinitialiser toutes les préférences
     */
    function resetAllPreferences() {
        log('🔄 Reset toutes les préférences');
        
        showSaveIndicator('saving');
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'sisme_reset_user_preferences',
                security: config.security
            },
            success: function(response) {
                log('✅ Reset réussi:', response);
                
                if (response.success) {
                    // Mettre à jour l'interface avec les nouvelles valeurs
                    updateInterfaceWithPreferences(response.data.preferences);
                    showSaveIndicator('success', config.i18n.reset_success);
                    
                    // Déclencher événement
                    $(document).trigger('sisme_preferences_reset', [true, response.data.preferences]);
                } else {
                    showSaveIndicator('error');
                    log('❌ Erreur reset:', response.data);
                    
                    $(document).trigger('sisme_preferences_reset', [false, response.data.message]);
                }
            },
            error: function(xhr, status, error) {
                log('❌ Erreur AJAX reset:', {xhr, status, error});
                showSaveIndicator('error');
                
                $(document).trigger('sisme_preferences_reset', [false, error]);
            }
        });
    }
    
    /**
     * Mettre à jour l'interface avec de nouvelles préférences
     */
    function updateInterfaceWithPreferences(preferences) {
        // Mettre à jour les toggles
        $('.sisme-preference-toggle').each(function() {
            const $toggle = $(this);
            const key = $toggle.data('preference-key');
            
            if (key.includes('.')) {
                const [mainKey, subKey] = key.split('.');
                const value = preferences[mainKey] && preferences[mainKey][subKey];
                $toggle.prop('checked', !!value);
            } else {
                const value = preferences[key];
                $toggle.prop('checked', !!value);
            }
        });
        
        // Mettre à jour les multi-sélections
        $('.sisme-multi-select').each(function() {
            const $multiSelect = $(this);
            const key = $multiSelect.data('preference-key');
            const selectedValues = preferences[key] || [];
            
            $multiSelect.find('.sisme-multi-select-checkbox').each(function() {
                const $checkbox = $(this);
                const value = $checkbox.val();
                const isSelected = selectedValues.includes(value) || selectedValues.includes(parseInt(value));
                
                $checkbox.prop('checked', isSelected);
                updateMultiSelectItem($checkbox);
            });
        });
    }
    
    /**
     * Mettre à jour l'état visuel d'un item multi-select
     */
    function updateMultiSelectItem($checkbox) {
        const $item = $checkbox.closest('.sisme-multi-select-item');
        
        if ($checkbox.is(':checked')) {
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
        }
    }
    
    /**
     * Récupérer les valeurs sélectionnées d'un multi-select
     */
    function getMultiSelectValues($multiSelect) {
        const values = [];
        
        $multiSelect.find('.sisme-multi-select-checkbox:checked').each(function() {
            const value = $(this).val();
            // Convertir en nombre si c'est numérique (pour les IDs de genres)
            values.push(isNaN(value) ? value : parseInt(value));
        });
        
        return values;
    }
    
    /**
     * Basculer tous les éléments d'un multi-select
     */
    function toggleAllMultiSelect($multiSelect, selectAll) {
        const key = $multiSelect.data('preference-key');
        
        $multiSelect.find('.sisme-multi-select-checkbox').each(function() {
            const $checkbox = $(this);
            $checkbox.prop('checked', selectAll);
            updateMultiSelectItem($checkbox);
        });
        
        // Sauvegarder la nouvelle sélection
        const selectedValues = selectAll ? getAllMultiSelectValues($multiSelect) : [];
        
        if (config.auto_save) {
            autoSavePreference(key, selectedValues);
        }
        
        $(document).trigger('sisme_preference_changed', [key, selectedValues, 'multi_select_all']);
    }
    
    /**
     * Récupérer toutes les valeurs possibles d'un multi-select
     */
    function getAllMultiSelectValues($multiSelect) {
        const values = [];
        
        $multiSelect.find('.sisme-multi-select-checkbox').each(function() {
            const value = $(this).val();
            values.push(isNaN(value) ? value : parseInt(value));
        });
        
        return values;
    }
    
    /**
     * Afficher l'indicateur de sauvegarde
     */
    function showSaveIndicator(type, customMessage = null) {
        if (!saveIndicator || !saveIndicator.length) {
            return;
        }
        
        let message = customMessage;
        if (!message) {
            switch (type) {
                case 'saving':
                    message = config.i18n.saving;
                    break;
                case 'success':
                    message = config.i18n.saved;
                    break;
                case 'error':
                    message = config.i18n.error;
                    break;
                default:
                    message = '';
            }
        }
        
        saveIndicator.removeClass('sisme-save-success sisme-save-error sisme-save-saving');
        saveIndicator.addClass(`sisme-save-${type}`);
        saveIndicator.find('.sisme-save-text').text(message);
        saveIndicator.fadeIn(200);
        
        // Masquer automatiquement après succès ou erreur
        if (type === 'success' || type === 'error') {
            setTimeout(() => {
                saveIndicator.fadeOut(200);
            }, 3000);
        }
    }
    
    /**
     * Sauvegarde manuelle (Ctrl+S)
     */
    function manualSave() {
        log('💾 Sauvegarde manuelle déclenchée');
        
        // Déclencher la sauvegarde immédiatement si une est en attente
        if (saveTimeout) {
            clearTimeout(saveTimeout);
            saveTimeout = null;
        }
        
        showSaveIndicator('success', 'Sauvegarde manuelle effectuée !');
    }
    
    /**
     * Fonctions utilitaires publiques
     */
    window.SismeUserPreferences = {
        // Initialisation
        init: init,
        isReady: () => isInitialized,
        
        // Sauvegarde
        savePreference: savePreference,
        autoSavePreference: autoSavePreference,
        resetAllPreferences: resetAllPreferences,
        
        // Interface
        showSaveIndicator: showSaveIndicator,
        updateInterfaceWithPreferences: updateInterfaceWithPreferences,
        
        // État
        getCurrentNotificationValues: getCurrentNotificationValues,
        getMultiSelectValues: function(selector) {
            return getMultiSelectValues($(selector));
        },
        
        // Configuration
        getConfig: () => config,
        setConfig: function(newConfig) {
            Object.assign(config, newConfig);
        }
    };
    
    /**
     * Debug et logging
     */
    function log(...args) {
        if (config.debug || (typeof window.WP_DEBUG !== 'undefined' && window.WP_DEBUG)) {
            console.log('[Sisme User Preferences]', ...args);
        }
    }
    
    /**
     * Fonction de debug globale
     */
    window.debugUserPreferences = function() {
        console.log('=== DEBUG SISME USER PREFERENCES ===');
        console.log('Config:', config);
        console.log('Initialisé:', isInitialized);
        console.log('Save timeout actif:', !!saveTimeout);
        console.log('Nombre de toggles:', $('.sisme-preference-toggle').length);
        console.log('Nombre de multi-selects:', $('.sisme-multi-select').length);
        console.log('Notifications actuelles:', getCurrentNotificationValues());
        console.log('=== FIN DEBUG ===');
    };
    
    // ✨ INITIALISATION AUTOMATIQUE
    $(document).ready(function() {
        // Vérifier si on est sur une page avec préférences
        if ($('.sisme-user-preferences, .sisme-preferences-form').length) {
            init();
        }
    });
    
})(jQuery);