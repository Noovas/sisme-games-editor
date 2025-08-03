/**
 * File: /sisme-games-editor/admin/assets/admin-submissions.js
 * JavaScript pour l'interface admin des soumissions
 * 
 * RESPONSABILITÉ:
 * - Interactions admin spécifiques
 * - Gestion des boutons d'actions
 * - Intégration avec le système front existant
 */

jQuery(document).ready(function($) {
    
    // Configuration
    const config = {
        ajaxUrl: sismeAdminAjax.ajaxurl,
        nonce: sismeAdminAjax.nonce
    };
    
    // Cache des détails pour éviter les requêtes multiples
    const detailsCache = {};
    
    /**
     * Gestionnaire pour les boutons "Voir détails"
     */
    $('.view-btn').click(function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const submissionId = $button.data('submission-id');
        const userId = $button.data('user-id');
        const $row = $button.closest('tr');
        const $detailsRow = $row.next('.sisme-details-row');
        
        // Toggle : si déjà ouvert, fermer
        if ($detailsRow.is(':visible')) {
            closeDetails($detailsRow, $button);
            return;
        }
        
        // Ouvrir les détails
        openDetails(submissionId, userId, $detailsRow, $button);
    });
    
    /**
     * Ouvrir les détails d'une soumission
     */
    function openDetails(submissionId, userId, $detailsRow, $button) {
        // Vérifier le cache
        if (detailsCache[submissionId]) {
            renderDetails($detailsRow, detailsCache[submissionId]);
            $detailsRow.slideDown(300);
            updateButton($button, 'expanded');
            return;
        }
        
        // État loading
        updateButton($button, 'loading');
        $detailsRow.find('.admin-loading').text('⏳ Chargement des détails...');
        $detailsRow.show();
        
        // Requête AJAX (utilise l'endpoint existant du module)
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_admin_get_submission_details',
                submission_id: submissionId,
                user_id: userId,
                security: config.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Mettre en cache et afficher
                    detailsCache[submissionId] = response.data;
                    renderDetails($detailsRow, response.data);
                    updateButton($button, 'expanded');
                } else {
                    showError($detailsRow, response.data?.message || 'Erreur lors du chargement');
                    updateButton($button, 'collapsed');
                }
            },
            error: function() {
                showError($detailsRow, 'Erreur de connexion au serveur');
                updateButton($button, 'collapsed');
            }
        });
    }
    
    /**
     * Fermer les détails
     */
    function closeDetails($detailsRow, $button) {
        $detailsRow.slideUp(300);
        updateButton($button, 'collapsed');
    }
    
    /**
     * Afficher les détails dans le format admin
     */
    function renderDetails($detailsRow, data) {
        const gameData = data.game_data || {};
        const metadata = data.metadata || {};
        
        let html = '<div class="admin-details-data">';
        
        // === SECTION INFORMATIONS ===
        html += '<div class="admin-detail-section">';
        html += '<div class="admin-detail-title">🎮 Informations du Jeu</div>';
        html += '<div class="admin-detail-content">';
        
        if (gameData.game_name) {
            html += `<p><strong>Nom :</strong> ${escapeHtml(gameData.game_name)}</p>`;
        }
        if (gameData.game_studio_name) {
            html += `<p><strong>Studio :</strong> ${escapeHtml(gameData.game_studio_name)}</p>`;
        }
        if (gameData.game_publisher_name) {
            html += `<p><strong>Éditeur :</strong> ${escapeHtml(gameData.game_publisher_name)}</p>`;
        }
        if (gameData.game_release_date) {
            html += `<p><strong>Date de sortie :</strong> ${escapeHtml(gameData.game_release_date)}</p>`;
        }
        if (gameData.game_description) {
            const shortDesc = gameData.game_description.length > 200 
                ? gameData.game_description.substring(0, 200) + '...'
                : gameData.game_description;
            html += `<p><strong>Description :</strong><br>${escapeHtml(shortDesc)}</p>`;
        }
        
        html += '</div></div>';
        
        // === SECTION MÉTADONNÉES ===
        html += '<div class="admin-detail-section">';
        html += '<div class="admin-detail-title">📋 Métadonnées</div>';
        html += '<div class="admin-detail-content">';
        
        if (metadata.submitted_at) {
            html += `<p><strong>Soumis le :</strong> ${formatDate(metadata.submitted_at)}</p>`;
        }
        if (metadata.updated_at) {
            html += `<p><strong>Modifié le :</strong> ${formatDate(metadata.updated_at)}</p>`;
        }
        if (metadata.version) {
            html += `<p><strong>Version :</strong> ${escapeHtml(metadata.version)}</p>`;
        }
        
        html += '</div></div>';
        
        // === SECTION CATÉGORIES (si disponibles) ===
        if (gameData.game_genres || gameData.game_platforms || gameData.game_modes) {
            html += '<div class="admin-detail-section">';
            html += '<div class="admin-detail-title">🏷️ Catégories</div>';
            html += '<div class="admin-detail-content">';
            
            if (gameData.game_genres && gameData.game_genres.length) {
                html += `<p><strong>Genres :</strong> <span class="genre-names" data-genre-ids="${gameData.game_genres.join(',')}">${convertGenreIds(gameData.game_genres)}</span></p>`;
            }
            if (gameData.game_platforms && gameData.game_platforms.length) {
                html += `<p><strong>Plateformes :</strong> ${gameData.game_platforms.join(', ')}</p>`;
            }
            if (gameData.game_modes && gameData.game_modes.length) {
                html += `<p><strong>Modes :</strong> ${gameData.game_modes.join(', ')}</p>`;
            }
            
            html += '</div></div>';
        }
        
        // === SECTION LIENS (si disponibles) ===
        if (gameData.external_links || gameData.game_trailer) {
            html += '<div class="admin-detail-section">';
            html += '<div class="admin-detail-title">🔗 Liens</div>';
            html += '<div class="admin-detail-content">';
            
            if (gameData.game_trailer) {
                html += `<p><strong>Trailer :</strong> <a href="${escapeHtml(gameData.game_trailer)}" target="_blank">Voir la vidéo</a></p>`;
            }
            
            if (gameData.external_links) {
                for (const [platform, url] of Object.entries(gameData.external_links)) {
                    if (url) {
                        html += `<p><strong>${escapeHtml(platform)} :</strong> <a href="${escapeHtml(url)}" target="_blank">Lien</a></p>`;
                    }
                }
            }
            
            html += '</div></div>';
        }
        
        // === SECTION MÉDIAS ===
        if ((data.covers && (data.covers.horizontal || data.covers.vertical)) || (data.screenshots && data.screenshots.length)) {
            html += '<div class="admin-detail-section admin-media-full">';
            html += '<div class="admin-detail-title">🖼️ Médias</div>';
            html += '<div class="admin-detail-content">';
            // Covers sur une ligne
            if (data.covers && (data.covers.horizontal || data.covers.vertical)) {
                html += '<div class="admin-covers-row">';
                if (data.covers.horizontal) {
                    html += `<div class="admin-cover-item"><a href="${escapeHtml(data.covers.horizontal.url)}" target="_blank"><img src="${escapeHtml(data.covers.horizontal.thumb)}" alt="Cover H" /></a><span class="admin-media-label">Horizontal</span></div>`;
                }
                if (data.covers.vertical) {
                    html += `<div class="admin-cover-item"><a href="${escapeHtml(data.covers.vertical.url)}" target="_blank"><img src="${escapeHtml(data.covers.vertical.thumb)}" alt="Cover V" /></a><span class="admin-media-label">Vertical</span></div>`;
                }
                html += '</div>';
            }
            // Screenshots en grid
            if (data.screenshots && data.screenshots.length) {
                html += `<div class="admin-screenshots-grid">`;
                data.screenshots.forEach((shot, i) => {
                    html += `<div class="admin-screenshot-item"><a href="${escapeHtml(shot.url)}" target="_blank"><img src="${escapeHtml(shot.thumb)}" alt="Screenshot ${i+1}" /></a><span class="admin-media-label">Screenshot #${i+1}</span></div>`;
                });
                html += '</div>';
            }
            html += '</div></div>';
        }

        // === SECTION SECTIONS DÉTAILLÉES ===
        if (data.sections && data.sections.length) {
            html += '<div class="admin-detail-section admin-sections-full">';
            html += '<div class="admin-detail-title">📝 Sections détaillées</div>';
            html += '<div class="admin-detail-content">';
            data.sections.forEach((section, i) => {
                html += `<div class="admin-section-detail" style="text-align:center;">`;
                html += `<h5>${escapeHtml(section.title)}</h5>`;
                html += `<div class="admin-section-content">${escapeHtml(section.content)}</div>`;
                if (section.image) {
                    html += `<div class="admin-section-image-full"><a href="${escapeHtml(section.image.url)}" target="_blank"><img src="${escapeHtml(section.image.url)}" alt="${escapeHtml(section.title)}" style="width:100%;max-width:900px;display:block;margin:0 auto;" /></a></div>`;
                }
                html += '</div>';
                if (i < data.sections.length - 1) {
                    html += '<div class="admin-section-separator"></div>';
                }
            });
            html += '</div></div>';
        }

        html += '</div>';
        // Injecter le HTML
        $detailsRow.find('.admin-details-content').html(html);
    }
    
    /**
     * Afficher une erreur
     */
    function showError($detailsRow, message) {
        const html = `<div class="admin-error" style="text-align: center; padding: 20px; color: #e74c3c;">
            ❌ ${escapeHtml(message)}
        </div>`;
        $detailsRow.find('.admin-details-content').html(html);
    }
    
    /**
     * Mettre à jour l'état du bouton
     */
    function updateButton($button, state) {
        $button.attr('data-state', state);
        
        switch(state) {
            case 'loading':
                $button.text('⏳');
                break;
            case 'expanded':
                $button.text('🔼');
                break;
            case 'collapsed':
            default:
                $button.text('👁️');
                break;
        }
    }
    
    /**
     * Échapper le HTML pour éviter les XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Formater une date
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Convertir les IDs de genres en noms
     */
    function convertGenreIds(genreIds) {
        if (!Array.isArray(genreIds) || genreIds.length === 0) {
            return '';
        }
        
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_convert_taxonomy_ids',
                ids: genreIds,
                taxonomy: 'genre',
                security: config.nonce
            },
            success: function(response) {
                if (response.success && response.data.names) {
                    $('.genre-names').text(response.data.names.join(', '));
                }
            }
        });
        
        return genreIds.map(id => `Genre ${id}`).join(', ');
    }

    // Gestionnaire pour les boutons "Rejeter"
    $(document).on('click', '.reject-btn.active', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const submissionId = $button.data('submission-id');
        const userId = $button.data('user-id');
        const gameName = $button.closest('tr').find('.game-info strong').text() || 'ce jeu';
        
        const reason = prompt(`Motif du rejet pour "${gameName}" :`);
        
        if (reason && reason.trim()) {
            rejectSubmission(submissionId, userId, reason.trim());
        }
    });

    function rejectSubmission(submissionId, userId, reason) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_admin_reject_submission',
                submission_id: submissionId,
                user_id: userId,
                rejection_reason: reason,
                security: config.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Soumission rejetée et email envoyé');
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + (response.data?.message || 'Erreur inconnue'));
                }
            },
            error: function() {
                alert('❌ Erreur de connexion');
            }
        });
    }
});