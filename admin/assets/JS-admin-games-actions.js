/**
 * File: JS-admin-games-actions.js
 * Actions JavaScript pour la gestion des jeux (dépublication, etc.)
 */

jQuery(document).ready(function($) {
    
    console.log('🎮 JS-admin-games-actions.js chargé');
    console.log('📊 sismeAdminAjax:', window.sismeAdminAjax);
    
    /**
     * Dépublier un jeu avec modale de confirmation
     */
    $(document).on('click', '[id^="unpublish-game-"]', function(e) {
        e.preventDefault();
        console.log('🚫 Clic dépublier détecté:', $(this).attr('id'));
        
        const gameTermId = $(this).attr('id').replace('unpublish-game-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        console.log('🎯 Game Term ID:', gameTermId, 'Game Name:', gameName);
        
        // Créer la modale de confirmation
        showUnpublishModal(gameTermId, gameName);
    });
    
    /**
     * Republier un jeu avec modale de confirmation
     */
    $(document).on('click', '[id^="publish-game-"]', function(e) {
        e.preventDefault();
        console.log('🌐 Clic publier détecté:', $(this).attr('id'));
        
        const gameTermId = $(this).attr('id').replace('publish-game-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        console.log('🎯 Game Term ID:', gameTermId, 'Game Name:', gameName);
        
        // Créer la modale de confirmation
        showRepublishModal(gameTermId, gameName);
    });
    
    /**
     * Afficher la modale de dépublication
     */
    function showUnpublishModal(gameTermId, gameName) {
        // Trouver la ligne du tableau
        const $gameRow = $(`#game-row-${gameTermId}`);
        
        // Créer la modale directement dans la ligne
        const modalHtml = `
            <td colspan="5" style="position: relative; padding: 0; background-color: rgba(0, 0, 0, 0.9); z-index: 9999;">
                <div style="display: flex; align-items: center; justify-content: center; min-height: 60px; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; min-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div class="sisme-admin-modal-header">
                            <h3 class="sisme-admin-modal-title">🚫 Dépublier le jeu</h3>
                            <p class="sisme-admin-modal-subtitle">Confirmer la dépublication de "${gameName}"</p>
                        </div>
                        <div class="sisme-admin-modal-body">
                            <p><strong>Cette action va :</strong></p>
                            <ul>
                                <li>• Rendre le jeu inaccessible publiquement</li>
                                <li>• Mettre le post WordPress en brouillon</li>
                                <li>• Conserver toutes les données (action réversible)</li>
                            </ul>
                        </div>
                        <div class="sisme-admin-modal-actions">
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-cancel" onclick="closeUnpublishModal()">
                                Annuler
                            </button>
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-confirm" onclick="confirmUnpublishGame(${gameTermId})">
                                Dépublier
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        // Sauvegarder le contenu original et le remplacer
        $gameRow.data('original-content', $gameRow.html());
        $gameRow.html(modalHtml);
        $gameRow.attr('id', 'unpublish-modal-row');
    }
    
    /**
     * Afficher la modale de republication
     */
    function showRepublishModal(gameTermId, gameName) {
        // Trouver la ligne du tableau
        const $gameRow = $(`#game-row-${gameTermId}`);
        
        // Créer la modale directement dans la ligne
        const modalHtml = `
            <td colspan="5" style="position: relative; padding: 0; background-color: rgba(0, 0, 0, 0.9); z-index: 9999;">
                <div style="display: flex; align-items: center; justify-content: center; min-height: 60px; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; min-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div class="sisme-admin-modal-header">
                            <h3 class="sisme-admin-modal-title">🌐 Republier le jeu</h3>
                            <p class="sisme-admin-modal-subtitle">Confirmer la republication de "${gameName}"</p>
                        </div>
                        <div class="sisme-admin-modal-body">
                            <p><strong>Cette action va :</strong></p>
                            <ul>
                                <li>• Rendre le jeu accessible publiquement</li>
                                <li>• Remettre le post WordPress en ligne</li>
                                <li>• Réactiver la page dynamique</li>
                            </ul>
                        </div>
                        <div class="sisme-admin-modal-actions">
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-cancel" onclick="closeRepublishModal()">
                                Annuler
                            </button>
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-confirm" onclick="confirmRepublishGame(${gameTermId})">
                                Republier
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        // Sauvegarder le contenu original et le remplacer
        $gameRow.data('original-content', $gameRow.html());
        $gameRow.html(modalHtml);
        $gameRow.attr('id', 'republish-modal-row');
    }
    
    /**
     * Fermer la modale de dépublication
     */
    window.closeUnpublishModal = function() {
        const $modalRow = $('#unpublish-modal-row');
        const originalContent = $modalRow.data('original-content');
        const gameTermId = $modalRow.html().match(/confirmUnpublishGame\((\d+)\)/)?.[1];
        
        if (originalContent && gameTermId) {
            $modalRow.html(originalContent);
            $modalRow.attr('id', `game-row-${gameTermId}`);
        }
    };
    
    /**
     * Fermer la modale de republication
     */
    window.closeRepublishModal = function() {
        const $modalRow = $('#republish-modal-row');
        const originalContent = $modalRow.data('original-content');
        const gameTermId = $modalRow.html().match(/confirmRepublishGame\((\d+)\)/)?.[1];
        
        if (originalContent && gameTermId) {
            $modalRow.html(originalContent);
            $modalRow.attr('id', `game-row-${gameTermId}`);
        }
    };
    
    /**
     * Confirmer la dépublication
     */
    window.confirmUnpublishGame = function(gameTermId) {
        const $confirmBtn = $('#unpublish-modal .sisme-admin-modal-btn-confirm');
        $confirmBtn.prop('disabled', true).text('⏳ Dépublication...');
        
        $.post(sismeAdminAjax.ajaxurl, {
            action: 'sisme_admin_unpublish_game',
            game_term_id: gameTermId,
            security: sismeAdminAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Mettre à jour l'interface
                updateGamePublicationStatus(gameTermId, 'unpublished');
                closeUnpublishModal();
                
                // Message de succès (optionnel)
                console.log('Jeu dépublié avec succès');
            } else {
                alert('Erreur : ' + response.data.message);
                $confirmBtn.prop('disabled', false).text('Dépublier');
            }
        })
        .fail(function() {
            alert('Erreur de connexion');
            $confirmBtn.prop('disabled', false).text('Dépublier');
        });
    };
    
    /**
     * Confirmer la republication
     */
    window.confirmRepublishGame = function(gameTermId) {
        const $confirmBtn = $('#republish-modal .sisme-admin-modal-btn-confirm');
        $confirmBtn.prop('disabled', true).text('⏳ Republication...');
        
        $.post(sismeAdminAjax.ajaxurl, {
            action: 'sisme_admin_republish_game',
            game_term_id: gameTermId,
            security: sismeAdminAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Mettre à jour l'interface
                updateGamePublicationStatus(gameTermId, 'published');
                closeRepublishModal();
                
                // Message de succès (optionnel)
                console.log('Jeu republié avec succès');
            } else {
                alert('Erreur : ' + response.data.message);
                $confirmBtn.prop('disabled', false).text('Republier');
            }
        })
        .fail(function() {
            alert('Erreur de connexion');
            $confirmBtn.prop('disabled', false).text('Republier');
        });
    };
    
    /**
     * Mettre à jour l'interface après changement de statut
     */
    function updateGamePublicationStatus(gameTermId, newStatus) {
        const $row = $(`#unpublish-game-${gameTermId}, #publish-game-${gameTermId}`).closest('tr');
        
        if (newStatus === 'unpublished') {
            // Changer le bouton de dépublication en republication
            $(`#unpublish-game-${gameTermId}`)
                .attr('id', `publish-game-${gameTermId}`)
                .attr('title', 'Publier');
            
            // Ajouter le badge dépublié dans la colonne statut
            const $statusCol = $row.find('td').eq(2); // 3ème colonne (État)
            $statusCol.find('.sisme-admin-flex-col-sm').append(`
                <span class="sisme-admin-badge sisme-admin-badge-danger">🚫 Dépublié</span>
            `);
            
        } else if (newStatus === 'published') {
            // Changer le bouton de republication en dépublication
            $(`#publish-game-${gameTermId}`)
                .attr('id', `unpublish-game-${gameTermId}`)
                .attr('title', 'Dépublier');
            
            // Retirer le badge dépublié
            const $statusCol = $row.find('td').eq(2);
            $statusCol.find('.sisme-admin-badge-danger:contains("Dépublié")').remove();
        }
    }
});
