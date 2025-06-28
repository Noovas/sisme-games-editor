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
        initAvatarUploader();
        initMultiSelectHandlers();
        initResetHandler();
        initGlobalActions();
        
        isInitialized = true;
        
        log('✅ Sisme User Preferences initialisé', config);
        
        // Déclencher événement d'initialisation
        $(document).trigger('sisme_preferences_initialized');
    }
    
    /**
     * Masquer l'ancien indicateur de sauvegarde dans le dashboard
     */
    function initSaveIndicator() {
        // Dans le dashboard, on masque l'ancien indicateur
        if (typeof window.SismeDashboard !== 'undefined') {
            $('.sisme-save-indicator').hide();
            return;
        }
        
        // Garder l'ancien comportement si pas dans le dashboard
        saveIndicator = $('.sisme-save-indicator');
        
        if (!saveIndicator.length) {
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
            const checkedCount = $multiSelect.find('.sisme-multi-select-checkbox:checked').length;
            if (checkedCount === 0) {
                $checkbox.prop('checked', true);
                alert('Vous devez sélectionner au moins un élément');
                return;
            }
            const key = $multiSelect.data('preference-key');
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
        
        let ajaxData = {
            action: 'sisme_update_user_preference',
            security: config.security,
            preference_key: key,
            preference_value: value
        };
        
        // Traitement spécial pour les notifications
        if (key.includes('.')) {
            const [mainKey, subKey] = key.split('.');
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
                    // Message de succès plus discret pour les sauvegardes auto
                    showSaveIndicator('success', 'Sauvegardé');
                    
                    $(document).trigger('sisme_preference_saved', [key, value, true]);
                } else {
                    showSaveIndicator('error', 'Erreur de sauvegarde');
                    log('❌ Erreur serveur:', response.data);
                    
                    $(document).trigger('sisme_preference_error', [key, response.data.message || 'Erreur inconnue']);
                }
            },
            error: function(xhr, status, error) {
                log('❌ Erreur AJAX:', {xhr, status, error});
                showSaveIndicator('error', 'Erreur de connexion');
                
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
        
        showSaveIndicator('saving', 'Réinitialisation en cours...');
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'sisme_reset_user_preferences',
                security: config.security
            },
            success: function(response) {
                if (response.success) {
                    showSaveIndicator('success', 'Préférences réinitialisées !');
                    
                    // Refresh après 1.5 secondes si on est dans le dashboard
                    if (typeof window.SismeDashboard !== 'undefined') {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Mise à jour interface si pas dans dashboard
                        updateInterfaceWithPreferences(response.data.preferences);
                    }
                    
                    $(document).trigger('sisme_preferences_reset', [true, response.data.preferences]);
                } else {
                    showSaveIndicator('error', 'Erreur lors de la réinitialisation');
                    $(document).trigger('sisme_preferences_reset', [false, response.data.message]);
                }
            },
            error: function(xhr, status, error) {
                showSaveIndicator('error', 'Erreur de connexion');
                log('❌ Erreur AJAX reset:', {xhr, status, error});
                $(document).trigger('sisme_preferences_reset', [false, error]);
            }
        });
    }
    
    /**
     * Mettre à jour l'interface avec de nouvelles préférences
     */
    function updateInterfaceWithPreferences(preferences) {
        log('🔄 Mise à jour interface avec:', preferences);
        
        // 1. Mettre à jour les toggles (notifications + privacy)
        $('.sisme-preference-toggle').each(function() {
            const $toggle = $(this);
            const key = $toggle.data('preference-key');
            
            if (key.includes('.')) {
                // Cas des notifications (key = "notifications.newsletter")
                const [mainKey, subKey] = key.split('.');
                const value = preferences[mainKey] && preferences[mainKey][subKey];
                $toggle.prop('checked', !!value);
            } else {
                // Cas des autres toggles (key = "privacy_public")
                const value = preferences[key];
                $toggle.prop('checked', !!value);
            }
        });
        
        // 2. Mettre à jour les multi-sélections (plateformes, genres, types)
        $('.sisme-multi-select').each(function() {
            const $multiSelect = $(this);
            const key = $multiSelect.data('preference-key');
            const selectedValues = preferences[key] || [];
            
            log(`📋 Mise à jour multi-select ${key}:`, selectedValues);
            
            // Réinitialiser tous les checkboxes
            $multiSelect.find('.sisme-multi-select-checkbox').each(function() {
                const $checkbox = $(this);
                const value = $checkbox.val();
                
                // Vérifier si cette valeur est dans les sélectionnées
                const isSelected = selectedValues.includes(value) || 
                                 selectedValues.includes(parseInt(value)) ||
                                 selectedValues.includes(String(value));
                
                // Mettre à jour le checkbox ET l'état visuel
                $checkbox.prop('checked', isSelected);
                updateMultiSelectItem($checkbox);
            });
        });
        
        // 3. Mettre à jour les compteurs/statistiques si présents
        updateInterfaceStats(preferences);
        
        log('✅ Interface mise à jour complète');
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
     * Mettre à jour les statistiques/compteurs de l'interface (optionnel)
     */
    function updateInterfaceStats(preferences) {
        // Compter les sélections pour affichage
        Object.keys(preferences).forEach(key => {
            if (Array.isArray(preferences[key])) {
                const count = preferences[key].length;
                const $counter = $(`.sisme-${key}-counter`);
                if ($counter.length) {
                    $counter.text(count);
                }
            }
        });
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
    function showSaveIndicator(type, message) {
        // Essayer d'utiliser le système de notifications du dashboard
        if (typeof window.SismeDashboard !== 'undefined' && window.SismeDashboard.showNotification) {
            const messages = {
                'saving': 'Sauvegarde en cours...',
                'success': 'Préférences sauvegardées !',
                'error': 'Erreur lors de la sauvegarde'
            };
            
            const finalMessage = message || messages[type] || messages['success'];
            
            // Mapper les types pour le dashboard
            const dashboardType = type === 'saving' ? 'info' : type;
            const duration = type === 'saving' ? 2000 : 3000;
            
            window.SismeDashboard.showNotification(finalMessage, dashboardType, duration);
            return;
        }
        
        // Fallback: utiliser l'ancien système si pas dans le dashboard
        const indicator = $('.sisme-save-indicator');
        if (!indicator.length) return;
        
        const text = indicator.find('.sisme-save-text');
        const messages = {
            'saving': config.i18n.saving || 'Sauvegarde...',
            'success': config.i18n.saved || 'Sauvegardé !',
            'error': config.i18n.error || 'Erreur'
        };
        
        text.text(message || messages[type] || messages['success']);
        
        indicator
            .removeClass('sisme-save-saving sisme-save-success sisme-save-error')
            .addClass(`sisme-save-${type}`)
            .fadeIn(200);
        
        if (type !== 'saving') {
            setTimeout(() => {
                indicator.fadeOut(300);
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
     * Initialiser l'uploader d'avatar
     */
    function initAvatarUploader() {
        // Déclencher l'input file
        $(document).on('click', '.sisme-avatar-upload-btn', function() {
            $('#sisme-avatar-input').click();
        });

        // Gérer la sélection de fichier
        $(document).on('change', '#sisme-avatar-input', function() {
            const file = this.files[0];
            if (file) {
                uploadAvatar(file);
            }
            // Reset input pour permettre re-sélection du même fichier
            this.value = '';
        });

        // Gérer la suppression
        $(document).on('click', '.sisme-avatar-delete', function(e) {
            e.preventDefault();
            if (confirm('Êtes-vous sûr de vouloir supprimer votre avatar ?')) {
                deleteAvatar();
            }
        });
    }

    /**
     * Upload d'avatar via AJAX
     */
    function uploadAvatar(file) {
        // Validation côté client
        const maxSize = 2 * 1024 * 1024; // 2Mo
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (file.size > maxSize) {
            showNotification('Fichier trop volumineux (max 2Mo)', 'error');
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            showNotification('Type de fichier non autorisé (JPG, PNG, GIF)', 'error');
            return;
        }

        // Afficher indicateur de chargement
        showSaveIndicator('Upload en cours...');
        
        const formData = new FormData();
        formData.append('action', 'sisme_upload_user_avatar');
        formData.append('security', sismeUserPreferences.security);
        formData.append('avatar_file', file);

        $.ajax({
            url: sismeUserPreferences.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideSaveIndicator();
                
                if (response.success) {
                    updateAvatarPreview(response.data.url);
                    showNotification(response.data.message, 'success');
                    
                    // Déclencher événement custom
                    $(document).trigger('sisme_avatar_updated', [response.data.attachment_id, response.data.url]);
                } else {
                    showNotification(response.data.message || 'Erreur lors de l\'upload', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideSaveIndicator();
                console.error('Erreur AJAX avatar upload:', error);
                showNotification('Erreur de connexion lors de l\'upload', 'error');
            }
        });
    }

    /**
     * Suppression d'avatar via AJAX
     */
    function deleteAvatar() {
        showSaveIndicator('Suppression...');
        
        $.ajax({
            url: sismeUserPreferences.ajax_url,
            type: 'POST',
            data: {
                action: 'sisme_delete_user_avatar',
                security: sismeUserPreferences.security
            },
            success: function(response) {
                hideSaveIndicator();
                
                if (response.success) {
                    updateAvatarPreview(null);
                    showNotification(response.data.message, 'success');
                    
                    // Déclencher événement custom
                    $(document).trigger('sisme_avatar_deleted');
                } else {
                    showNotification(response.data.message || 'Erreur lors de la suppression', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideSaveIndicator();
                console.error('Erreur AJAX avatar delete:', error);
                showNotification('Erreur de connexion lors de la suppression', 'error');
            }
        });
    }

    /**
     * Mettre à jour la prévisualisation d'avatar
     */
    function updateAvatarPreview(url) {
        const $preview = $('.sisme-avatar-preview');
        const $uploadBtn = $('.sisme-avatar-upload-btn');
        
        if (url) {
            $preview.html(`
                <img src="${url}" alt="Avatar" class="sisme-avatar-current">
                <button type="button" class="sisme-avatar-delete" title="Supprimer">❌</button>
            `);
            $uploadBtn.text('Changer');
        } else {
            $preview.html('<div class="sisme-avatar-placeholder">👤</div>');
            $uploadBtn.text('Ajouter');
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