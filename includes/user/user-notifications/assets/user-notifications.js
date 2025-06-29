/**
 * File: /sisme-games-editor/includes/user/user-notifications/assets/user-notifications.js
 * JavaScript pour le système de notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Gestion clic badge et ouverture panel
 * - Interactions AJAX marquer comme lue
 * - Navigation vers jeux depuis notifications
 * - Animations et transitions
 * - Gestion clavier et accessibilité
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - Configuration window.sismeUserNotifications
 */

(function($) {
    'use strict';
    
    // Configuration par défaut
    const config = window.sismeUserNotifications || {
        ajax_url: '/wp-admin/admin-ajax.php',
        security: '',
        user_id: 0,
        debug: false
    };
    
    // Variables globales
    let isInitialized = false;
    let panelOpen = false;
    let $badge = null;
    let $panel = null;
    let $overlay = null;
    
    /**
     * Initialisation principale
     */
    function init() {
        if (isInitialized) {
            return;
        }
        
        $badge = $('.sisme-notifications-badge');
        $panel = $('.sisme-notifications-panel');
        
        if ($badge.length === 0) {
            return;
        }
        
        createOverlay();
        bindEvents();
        
        isInitialized = true;
        
        if (config.debug) {
            console.log('[Sisme Notifications] Initialisé avec succès');
        }
    }
    
    /**
     * Créer l'overlay pour fermer le panel
     */
    function createOverlay() {
        if ($('.sisme-notifications-overlay').length > 0) {
            return;
        }
        
        $overlay = $('<div class="sisme-notifications-overlay"></div>');
        $('body').append($overlay);
    }
    
    /**
     * Lier tous les événements
     */
    function bindEvents() {
        // Clic sur le badge
        $(document).on('click', '.sisme-notifications-toggle', handleBadgeClick);
        
        // Fermer le panel
        $(document).on('click', '.sisme-notifications-close', closePanel);
        $(document).on('click', '.sisme-notifications-overlay', closePanel);
        
        // Marquer comme lue
        $(document).on('click', '.sisme-notification-mark-read', handleMarkAsRead);
        
        // Clic sur notification pour naviguer
        $(document).on('click', '.sisme-notification-game-link', handleNotificationClick);
        
        // Fermer avec Echap
        $(document).on('keydown', handleKeyDown);
    }
    
    /**
     * Gestion clic sur le badge
     */
    function handleBadgeClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    }
    
    /**
     * Ouvrir le panel de notifications
     */
    function openPanel() {
        if (panelOpen) {
            return;
        }
        
        if ($('.sisme-notifications-panel').length === 0) {
            createPanel();
            $panel = $('.sisme-notifications-panel'); // Re-sélectionner
        }
        
        if ($panel.length === 0) {
            createPanel();
        }
        
        // Afficher overlay et panel
        $overlay.addClass('sisme-notifications-overlay--visible');
        $panel.show().addClass('sisme-notifications-panel--open');
        
        // Récupérer les notifications
        refreshNotifications();
        
        panelOpen = true;
        
        // Focus accessibilité
        $panel.find('.sisme-notifications-close').focus();
        
        if (config.debug) {
            console.log('[Sisme Notifications] Panel ouvert');
        }
    }
    
    /**
     * Fermer le panel de notifications
     */
    function closePanel() {
        if (!panelOpen) {
            return;
        }
        
        $overlay.removeClass('sisme-notifications-overlay--visible');
        $panel.removeClass('sisme-notifications-panel--open');
        
        setTimeout(() => {
            $panel.hide();
        }, 300);
        
        panelOpen = false;
        
        if (config.debug) {
            console.log('[Sisme Notifications] Panel fermé');
        }
    }
    
    /**
     * Créer le panel s'il n'existe pas
     */
    function createPanel() {
        if ($panel.length > 0) {
            return;
        }
        
        const panelHtml = `
            <div class="sisme-notifications-panel" style="display: none;">
                <div class="sisme-notifications-header">
                    <h3>Notifications</h3>
                    <button type="button" class="sisme-notifications-close" aria-label="Fermer">×</button>
                </div>
                <div class="sisme-notifications-content">
                    <div class="sisme-notifications-loading">
                        <p>Chargement...</p>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(panelHtml);
        $panel = $('.sisme-notifications-panel');
    }
    
    /**
     * Rafraîchir les notifications via AJAX
     */
    function refreshNotifications() {
        const $content = $panel.find('.sisme-notifications-content');
        
        $content.html('<div class="sisme-notifications-loading"><p>Chargement...</p></div>');
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'sisme_get_user_notifications',
                security: config.security,
                limit: 20
            },
            success: function(response) {
                if (response.success) {
                    $content.html(response.data.html);
                    updateBadgeCount(response.data.unread_count);
                } else {
                    showError('Erreur lors du chargement des notifications');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de connexion');
                if (config.debug) {
                    console.error('[Sisme Notifications] Erreur AJAX:', error);
                }
            }
        });
    }
    
    /**
     * Marquer une notification comme lue
     */
    function handleMarkAsRead(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const $button = $(event.currentTarget);
        const notificationIndex = parseInt($button.data('index'));
        const $item = $button.closest('.sisme-notification-item');
        
        if (isNaN(notificationIndex)) {
            return;
        }
        
        // Indication visuelle
        $button.prop('disabled', true);
        $item.addClass('sisme-notification-removing');
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'sisme_mark_notification_read',
                security: config.security,
                notification_index: notificationIndex
            },
            success: function(response) {
                if (response.success) {
                    // Animation de suppression
                    $item.fadeOut(300, function() {
                        $item.remove();
                        
                        // Vérifier s'il reste des notifications
                        if ($('.sisme-notification-item').length === 0) {
                            showEmptyState();
                        }
                    });
                    
                    updateBadgeCount(response.data.unread_count);
                    
                    if (config.debug) {
                        console.log('[Sisme Notifications] Notification marquée lue:', notificationIndex);
                    }
                } else {
                    $button.prop('disabled', false);
                    $item.removeClass('sisme-notification-removing');
                    showError(response.data.message || 'Erreur lors du marquage');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false);
                $item.removeClass('sisme-notification-removing');
                showError('Erreur de connexion');
                
                if (config.debug) {
                    console.error('[Sisme Notifications] Erreur AJAX:', error);
                }
            }
        });
    }
    
    /**
     * Gestion clic sur notification pour navigation
     */
    function handleNotificationClick(event) {
        const gameUrl = event.currentTarget.href;
        
        if (config.debug) {
            console.log('[Sisme Notifications] Navigation vers:', gameUrl);
        }
        
        // Fermer le panel après un délai
        setTimeout(() => {
            closePanel();
        }, 100);
    }
    
    /**
     * Gestion des touches clavier
     */
    function handleKeyDown(event) {
        if (event.key === 'Escape' && panelOpen) {
            closePanel();
        }
    }
    
    /**
     * Mettre à jour le compteur du badge
     */
    function updateBadgeCount(count) {
        $badge.attr('data-count', count);
        
        const $countElement = $badge.find('.sisme-notification-count');
        
        if (count > 0) {
            $badge.addClass('sisme-notifications-badge--active');
            
            if ($countElement.length === 0) {
                const displayCount = count > 99 ? '99+' : count;
                $badge.find('.sisme-notifications-toggle').append(
                    `<span class="sisme-notification-count">${displayCount}</span>`
                );
            } else {
                const displayCount = count > 99 ? '99+' : count;
                $countElement.text(displayCount);
            }
        } else {
            $badge.removeClass('sisme-notifications-badge--active');
            $countElement.remove();
        }
    }
    
    /**
     * Afficher l'état vide
     */
    function showEmptyState() {
        const emptyHtml = `
            <div class="sisme-notifications-empty">
                <div class="sisme-empty-icon">🔔</div>
                <h4>Aucune notification</h4>
                <p>Vous serez notifié des nouveaux jeux ici.</p>
            </div>
        `;
        
        $panel.find('.sisme-notifications-content').html(emptyHtml);
    }
    
    /**
     * Afficher un message d'erreur
     */
    function showError(message) {
        const errorHtml = `
            <div class="sisme-notifications-error">
                <p>${message}</p>
                <button type="button" class="sisme-retry-notifications">Réessayer</button>
            </div>
        `;
        
        $panel.find('.sisme-notifications-content').html(errorHtml);
        
        // Bouton réessayer
        $(document).off('click', '.sisme-retry-notifications').on('click', '.sisme-retry-notifications', function() {
            refreshNotifications();
        });
    }
    
    /**
     * API publique pour interactions externes
     */
    window.SismeNotifications = {
        open: openPanel,
        close: closePanel,
        refresh: refreshNotifications,
        updateCount: updateBadgeCount,
        isOpen: () => panelOpen
    };
    
    // Initialisation automatique
    $(document).ready(function() {
        init();
    });
    
})(jQuery);