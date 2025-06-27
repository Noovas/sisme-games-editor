/**
 * File : assets/user-actions.js
 * Script principal pour les interactions utilisateur avec les jeux
 */
(function($) {
    'use strict';
    
    // Namespace global
    window.sismeUserActions = window.sismeUserActions || {};
    
    // Configuration
    const config = window.sismeUserActions || {
        ajax_url: '',
        security: '',
        is_logged_in: false,
        login_url: '',
        i18n: {
            error: 'Une erreur est survenue',
            login_required: 'Vous devez être connecté'
        }
    };
    
    /**
     * Initialiser les événements
     */
    function init() {
        // Déléguer les clics sur les boutons d'action
        $(document).on('click', '.sisme-action-btn', handleActionButtonClick);
    }
    
    /**
     * Gérer le clic sur un bouton d'action
     */
    function handleActionButtonClick(e) {
        e.preventDefault();
        
        const $button = $(this);
        const gameId = $button.data('game-id');
        const actionType = $button.data('action-type');
        
        // Si utilisateur non connecté, rediriger vers login
        if (!config.is_logged_in) {
            window.location.href = config.login_url;
            return;
        }
        
        // Ajouter classe pour animation
        $button.addClass('clicked');
        setTimeout(() => {
            $button.removeClass('clicked');
        }, 600);
        
        // Appel AJAX
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'sisme_toggle_game_collection',
                security: config.security,
                game_id: gameId,
                collection_type: actionType
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour l'état du bouton
                    updateButtonState($button, response.status);
                    
                    // Mettre à jour le compteur si présent
                    if (response.stats && response.stats.count !== undefined) {
                        updateButtonCount($button, response.stats.count);
                    }
                    
                    // Déclencher un événement personnalisé
                    $(document).trigger('sisme_game_collection_updated', [gameId, actionType, response.status]);
                } else {
                    console.error('Erreur:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    }
    
    /**
     * Mettre à jour l'état visuel du bouton
     */
    function updateButtonState($button, isActive) {
        if (isActive) {
            $button.removeClass('sisme-action-inactive').addClass('sisme-action-active');
            $button.data('is-active', 'true');
        } else {
            $button.removeClass('sisme-action-active').addClass('sisme-action-inactive');
            $button.data('is-active', 'false');
        }
        
        // Mettre à jour le titre selon l'état
        const actionType = $button.data('action-type');
        if (actionType === 'favorite') {
            $button.attr('title', isActive ? 'Retirer des favoris' : 'Ajouter aux favoris');
        } else if (actionType === 'owned') {
            $button.attr('title', isActive ? 'Retirer de ma collection' : 'Ajouter à ma collection');
        }
    }
    
    /**
     * Mettre à jour le compteur d'un bouton
     */
    function updateButtonCount($button, count) {
        let $counter = $button.find('.sisme-action-count');
        
        if (count > 0) {
            if ($counter.length === 0) {
                $counter = $('<span class="sisme-action-count"></span>');
                $button.append($counter);
            }
            $counter.text(count);
        } else {
            $counter.remove();
        }
    }
    
    // Initialiser au chargement du DOM
    $(document).ready(init);
    
})(jQuery);