/**
 * File : assets/user-actions-favorites.js
 * Script spécifique pour les boutons favoris
 * Étend les fonctionnalités de base pour des interactions spécifiques aux favoris
 */

(function($) {
    'use strict';
    
    // Attendre que le document soit prêt
    $(document).ready(function() {
        // Événement personnalisé déclenché lorsqu'un jeu est ajouté/supprimé des favoris
        $(document).on('sisme_game_collection_updated', function(event, gameId, actionType, isActive) {
            // Ne réagir qu'aux actions de type favoris
            if (actionType !== 'favorite') {
                return;
            }
            
            // Trouver tous les boutons de favoris pour ce jeu et mettre à jour leur état
            $('.sisme-action-favorite[data-game-id="' + gameId + '"]').each(function() {
                const $button = $(this);
                
                // Ne pas mettre à jour le bouton qui a déclenché l'événement (déjà mis à jour)
                if ($button.hasClass('clicked')) {
                    return;
                }
                
                // Mettre à jour l'état
                if (isActive) {
                    $button.removeClass('sisme-action-inactive').addClass('sisme-action-active');
                    $button.data('is-active', 'true');
                    $button.attr('title', 'Retirer des favoris');
                } else {
                    $button.removeClass('sisme-action-active').addClass('sisme-action-inactive');
                    $button.data('is-active', 'false');
                    $button.attr('title', 'Ajouter aux favoris');
                }
            });
        });
    });
    
})(jQuery);