/**
 * File: JS-admin-games-actions.js
 * Actions JavaScript pour la gestion des jeux (dépublication, etc.)
 */

jQuery(document).ready(function($) {
    
    /**
     * Dépublier un jeu avec modale de confirmation
     */
    $(document).on('click', '[id^="unpublish-game-"]', function(e) {
        e.preventDefault();
        
        const gameTermId = $(this).attr('id').replace('unpublish-game-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        // Créer la modale de confirmation
        showUnpublishModal(gameTermId, gameName);
    });
    
    /**
     * Republier un jeu avec modale de confirmation
     */
    $(document).on('click', '[id^="publish-game-"]', function(e) {
        e.preventDefault();
        
        const gameTermId = $(this).attr('id').replace('publish-game-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        // Créer la modale de confirmation
        showRepublishModal(gameTermId, gameName);
    });
    
    /**
     * Ajouter un jeu aux coups de cœur
     */
    $(document).on('click', '[id^="set-team-choice-"]', function(e) {
        e.preventDefault();
        
        const gameTermId = $(this).attr('id').replace('set-team-choice-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        // Créer la modale de confirmation
        showSetTeamChoiceModal(gameTermId, gameName);
    });
    
    /**
     * Retirer un jeu des coups de cœur
     */
    $(document).on('click', '[id^="unset-team-choice-"]', function(e) {
        e.preventDefault();
        
        const gameTermId = $(this).attr('id').replace('unset-team-choice-', '');
        const gameName = $(this).data('game-name') || 'ce jeu';
        
        // Créer la modale de confirmation
        showUnsetTeamChoiceModal(gameTermId, gameName);
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
     * Afficher la modale d'ajout aux coups de cœur
     */
    function showSetTeamChoiceModal(gameTermId, gameName) {
        // Trouver la ligne du tableau
        const $gameRow = $(`#game-row-${gameTermId}`);
        
        // Créer la modale directement dans la ligne
        const modalHtml = `
            <td colspan="5" style="position: relative; padding: 0; background-color: rgba(0, 0, 0, 0.9); z-index: 9999;">
                <div style="display: flex; align-items: center; justify-content: center; min-height: 60px; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; min-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div class="sisme-admin-modal-header">
                            <h3 class="sisme-admin-modal-title">❤️ Ajouter aux coups de cœur</h3>
                            <p class="sisme-admin-modal-subtitle">Confirmer l'ajout de "${gameName}" aux coups de cœur de l'équipe</p>
                        </div>
                        <div class="sisme-admin-modal-body">
                            <p><strong>Cette action va :</strong></p>
                            <ul>
                                <li>• Marquer le jeu comme coup de cœur de l'équipe</li>
                                <li>• Afficher un badge spécial sur la fiche du jeu</li>
                                <li>• Mettre en valeur le jeu dans les listings</li>
                            </ul>
                        </div>
                        <div class="sisme-admin-modal-actions">
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-cancel" onclick="closeSetTeamChoiceModal()">
                                Annuler
                            </button>
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-confirm" onclick="confirmSetTeamChoice(${gameTermId})">
                                Ajouter aux coups de cœur
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        // Sauvegarder le contenu original et le remplacer
        $gameRow.data('original-content', $gameRow.html());
        $gameRow.html(modalHtml);
        $gameRow.attr('id', 'set-team-choice-modal-row');
    }
    
    /**
     * Afficher la modale de retrait des coups de cœur
     */
    function showUnsetTeamChoiceModal(gameTermId, gameName) {
        // Trouver la ligne du tableau
        const $gameRow = $(`#game-row-${gameTermId}`);
        
        // Créer la modale directement dans la ligne
        const modalHtml = `
            <td colspan="5" style="position: relative; padding: 0; background-color: rgba(0, 0, 0, 0.9); z-index: 9999;">
                <div style="display: flex; align-items: center; justify-content: center; min-height: 60px; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; min-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div class="sisme-admin-modal-header">
                            <h3 class="sisme-admin-modal-title">💔 Retirer des coups de cœur</h3>
                            <p class="sisme-admin-modal-subtitle">Confirmer le retrait de "${gameName}" des coups de cœur de l'équipe</p>
                        </div>
                        <div class="sisme-admin-modal-body">
                            <p><strong>Cette action va :</strong></p>
                            <ul>
                                <li>• Retirer le statut coup de cœur du jeu</li>
                                <li>• Supprimer le badge spécial</li>
                                <li>• Retirer la mise en valeur dans les listings</li>
                            </ul>
                        </div>
                        <div class="sisme-admin-modal-actions">
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-cancel" onclick="closeUnsetTeamChoiceModal()">
                                Annuler
                            </button>
                            <button type="button" class="sisme-admin-modal-btn sisme-admin-modal-btn-confirm" onclick="confirmUnsetTeamChoice(${gameTermId})">
                                Retirer des coups de cœur
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        // Sauvegarder le contenu original et le remplacer
        $gameRow.data('original-content', $gameRow.html());
        $gameRow.html(modalHtml);
        $gameRow.attr('id', 'unset-team-choice-modal-row');
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
     * Fermer la modale d'ajout aux coups de cœur
     */
    window.closeSetTeamChoiceModal = function() {
        const $modalRow = $('#set-team-choice-modal-row');
        const originalContent = $modalRow.data('original-content');
        const gameTermId = $modalRow.html().match(/confirmSetTeamChoice\((\d+)\)/)?.[1];
        
        if (originalContent && gameTermId) {
            $modalRow.html(originalContent);
            $modalRow.attr('id', `game-row-${gameTermId}`);
        }
    };
    
    /**
     * Fermer la modale de retrait des coups de cœur
     */
    window.closeUnsetTeamChoiceModal = function() {
        const $modalRow = $('#unset-team-choice-modal-row');
        const originalContent = $modalRow.data('original-content');
        const gameTermId = $modalRow.html().match(/confirmUnsetTeamChoice\((\d+)\)/)?.[1];
        
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
                // Recharger automatiquement la page
                location.reload();
            } else {
                $confirmBtn.prop('disabled', false).text('Dépublier');
            }
        })
        .fail(function() {
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
                // Recharger automatiquement la page
                location.reload();
            } else {
                $confirmBtn.prop('disabled', false).text('Republier');
            }
        })
        .fail(function() {
            $confirmBtn.prop('disabled', false).text('Republier');
        });
    };
    
    /**
     * Confirmer l'ajout aux coups de cœur
     */
    window.confirmSetTeamChoice = function(gameTermId) {
        const $confirmBtn = $('#set-team-choice-modal-row .sisme-admin-modal-btn-confirm');
        $confirmBtn.prop('disabled', true).text('⏳ Ajout...');
        
        $.post(sismeAdminAjax.ajaxurl, {
            action: 'sisme_admin_set_team_choice',
            game_term_id: gameTermId,
            security: sismeAdminAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Recharger automatiquement la page
                location.reload();
            } else {
                $confirmBtn.prop('disabled', false).text('Ajouter aux coups de cœur');
            }
        })
        .fail(function() {
            $confirmBtn.prop('disabled', false).text('Ajouter aux coups de cœur');
        });
    };
    
    /**
     * Confirmer le retrait des coups de cœur
     */
    window.confirmUnsetTeamChoice = function(gameTermId) {
        const $confirmBtn = $('#unset-team-choice-modal-row .sisme-admin-modal-btn-confirm');
        $confirmBtn.prop('disabled', true).text('⏳ Retrait...');
        
        $.post(sismeAdminAjax.ajaxurl, {
            action: 'sisme_admin_unset_team_choice',
            game_term_id: gameTermId,
            security: sismeAdminAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Recharger automatiquement la page
                location.reload();
            } else {
                $confirmBtn.prop('disabled', false).text('Retirer des coups de cœur');
            }
        })
        .fail(function() {
            $confirmBtn.prop('disabled', false).text('Retirer des coups de cœur');
        });
    };
    
});
