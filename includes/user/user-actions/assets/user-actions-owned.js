/**
 * File : assets/user-actions-owned.js
 * Script spécifique pour les boutons collection (owned)
 * Étend les fonctionnalités de base pour des interactions spécifiques aux jeux possédés
 */

(function($) {
    'use strict';
    
    // Attendre que le document soit prêt
    $(document).ready(function() {
        // Événement personnalisé déclenché lorsqu'un jeu est ajouté/supprimé de la collection
        $(document).on('sisme_game_collection_updated', function(event, gameId, actionType, isActive) {
            // Ne réagir qu'aux actions de type owned
            if (actionType !== 'owned') {
                return;
            }
            
            // Trouver tous les boutons de collection pour ce jeu et mettre à jour leur état
            $('.sisme-action-owned[data-game-id="' + gameId + '"]').each(function() {
                const $button = $(this);
                
                // Ne pas mettre à jour le bouton qui a déclenché l'événement (déjà mis à jour)
                if ($button.hasClass('clicked')) {
                    return;
                }
                
                // Mettre à jour l'état
                if (isActive) {
                    $button.removeClass('sisme-action-inactive').addClass('sisme-action-active');
                    $button.data('is-active', 'true');
                    $button.attr('title', 'Retirer de ma collection');
                } else {
                    $button.removeClass('sisme-action-active').addClass('sisme-action-inactive');
                    $button.data('is-active', 'false');
                    $button.attr('title', 'Ajouter à ma collection');
                }
            });
            
            // 🎯 ÉVÉNEMENT DASHBOARD : Déclencher mise à jour dashboard si présent
            if (window.sismeUserDashboard && typeof window.sismeUserDashboard.refreshCollectionStats === 'function') {
                window.sismeUserDashboard.refreshCollectionStats('owned');
            }
            
            // 🎯 ÉVÉNEMENT GLOBAL : Notifier d'autres modules
            $(document).trigger('sisme_user_owned_collection_changed', [gameId, isActive]);
        });
    });
    
})(jQuery);