/**
 * File : assets/user-actions.js
 * Script principal pour les interactions utilisateur avec les jeux
 * ✅ FIX: Correction de la logique updateButtonState
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
        
        // Debug: vérifier la configuration
        if (window.console && window.console.log) {
            console.log('🎮 Sisme User Actions: Initialisé', config);
        }
    }
    
    /**
     * Gérer le clic sur un bouton d'action
     */
    function handleActionButtonClick(e) {
        e.preventDefault();
        
        const $button = $(this);
        const gameId = $button.data('game-id');
        const actionType = $button.data('action-type');
        
        // Debug
        console.log('🎯 Clic bouton:', {gameId, actionType, logged: config.is_logged_in});
        
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
                console.log('✅ Réponse AJAX:', response);
                
                if (response.success) {
                    // ✅ FIX: Convertir response.status en boolean
                    const isActive = (response.data.status === 'added' || response.data.status === true);
                    
                    // Mettre à jour l'état du bouton
                    updateButtonState($button, isActive);
                    
                    // Mettre à jour le compteur si présent
                    if (response.data.stats && response.data.stats.count !== undefined) {
                        updateButtonCount($button, response.data.stats.count);
                    }
                    
                    // Déclencher un événement personnalisé
                    $(document).trigger('sisme_game_collection_updated', [
                        gameId, 
                        actionType, 
                        isActive
                    ]);
                    
                    console.log('🔄 Bouton mis à jour:', {gameId, actionType, isActive});
                } else {
                    console.error('❌ Erreur serveur:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX:', {xhr, status, error});
                
                // Feedback utilisateur en cas d'erreur
                alert(config.i18n.error);
            }
        });
    }
    
    /**
     * ✅ FIX: Mettre à jour l'état visuel du bouton
     */
    function updateButtonState($button, isActive) {
        console.log('🔄 updateButtonState:', {
            gameId: $button.data('game-id'),
            actionType: $button.data('action-type'),
            isActive: isActive,
            type: typeof isActive
        });
        
        // ✅ FIX: S'assurer qu'on a un boolean
        const active = Boolean(isActive);
        
        // Mettre à jour les classes CSS
        if (active) {
            $button.removeClass('sisme-action-inactive').addClass('sisme-action-active');
            $button.data('is-active', 'true');
        } else {
            $button.removeClass('sisme-action-active').addClass('sisme-action-inactive');
            $button.data('is-active', 'false');
        }
        
        // Mettre à jour le titre selon l'état
        const actionType = $button.data('action-type');
        if (actionType === 'favorite') {
            $button.attr('title', active ? 'Retirer des favoris' : 'Ajouter aux favoris');
        } else if (actionType === 'owned') {
            $button.attr('title', active ? 'Retirer de ma collection' : 'Ajouter à ma collection');
        }
        
        // ✅ FIX: Forcer le re-rendu CSS
        $button.trigger('sisme:state-updated');
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
    
    // ✅ FIX: Fonction utilitaire pour debug
    function debugButtonState($button) {
        const classes = $button.attr('class');
        const dataActive = $button.data('is-active');
        const hasActive = $button.hasClass('sisme-action-active');
        const hasInactive = $button.hasClass('sisme-action-inactive');
        
        console.log('🔍 Debug bouton:', {
            gameId: $button.data('game-id'),
            classes: classes,
            dataActive: dataActive,
            hasActive: hasActive,
            hasInactive: hasInactive
        });
    }
    
    // Initialiser au chargement du DOM
    $(document).ready(init);
    
    // ✅ FIX: Ajouter fonction debug globale
    window.debugUserActions = function() {
        $('.sisme-action-btn').each(function() {
            debugButtonState($(this));
        });
    };
    
})(jQuery);