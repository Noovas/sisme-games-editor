/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/assets/game-submission-details.js
 * Gestion de l'expansion des détails de soumissions pending
 * 
 * RESPONSABILITÉ:
 * - Affichage déroulant des détails soumissions pending
 * - Toggle "Voir plus" / "Voir moins"
 * - Récupération AJAX des données complètes
 * - Animation smooth expand/collapse
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - sismeAjax (variables AJAX globales)
 */

(function($) {
    'use strict';
    
    window.SismeSubmissionDetails = window.SismeSubmissionDetails || {
        config: {
            ajaxUrl: null,
            nonce: null,
            animationDuration: 300
        },
        cache: {},
        taxonomyCache: {},
        isInitialized: false
    };
    
    /**
     * Initialisation du module
     */
    SismeSubmissionDetails.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        if (typeof sismeAjax !== 'undefined') {
            this.config.ajaxUrl = sismeAjax.ajaxurl;
            this.config.nonce = sismeAjax.nonce;
        } else {
            this.log('Variables AJAX non disponibles', 'error');
            return;
        }
        
        this.bindEvents();
        this.isInitialized = true;
        this.log('Module Submission Details initialisé');
    };
    
    /**
     * Liaison des événements
     */
    SismeSubmissionDetails.bindEvents = function() {
        $(document).on('click', '.sisme-expand-btn', this.handleToggleClick.bind(this));
        $(document).on('sisme:submissions:reloaded', this.onSubmissionsReloaded.bind(this));
    };
    
    /**
     * Gestion du clic sur bouton toggle
     */
    SismeSubmissionDetails.handleToggleClick = function(e) {
        e.preventDefault();
        
        const $button = $(e.currentTarget);
        const submissionId = $button.data('submission-id');
        const currentState = $button.data('state') || 'collapsed';
        
        if (!submissionId) {
            this.log('ID soumission manquant', 'error');
            return;
        }
        
        if (currentState === 'collapsed') {
            this.expandDetails(submissionId, $button);
        } else {
            this.collapseDetails(submissionId, $button);
        }
    };
    
    /**
     * Étendre les détails d'une soumission
     */
    SismeSubmissionDetails.expandDetails = function(submissionId, $button) {
        const $item = $button.closest('.sisme-submission-item');
        const $meta = $item.find('.sisme-submission-meta');
        
        if (this.cache[submissionId]) {
            this.renderDetails($meta, this.cache[submissionId]);
            this.animateExpand($meta, $button);
            return;
        }
        
        this.setButtonLoading($button, true);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_submission_details',
                submission_id: submissionId,
                security: this.config.nonce
            },
            success: (response) => {
                this.setButtonLoading($button, false);
                
                if (response.success && response.data) {
                    this.cache[submissionId] = response.data;
                    this.renderDetails($meta, response.data);
                    this.animateExpand($meta, $button);
                } else {
                    this.showError($meta, response.data?.message || 'Erreur lors du chargement');
                }
            },
            error: () => {
                this.setButtonLoading($button, false);
                this.showError($meta, 'Erreur de connexion');
            }
        });
    };
    
    /**
     * Réduire les détails d'une soumission
     */
    SismeSubmissionDetails.collapseDetails = function(submissionId, $button) {
        const $item = $button.closest('.sisme-submission-item');
        const $meta = $item.find('.sisme-submission-meta');
        
        this.animateCollapse($meta, $button);
    };
    
    /**
     * Rendu HTML des détails
     */
    SismeSubmissionDetails.renderDetails = function($meta, data) {
        const originalContent = $meta.find('.sisme-meta-original').html() || $meta.html();
        
        if (!$meta.find('.sisme-meta-original').length) {
            $meta.wrapInner('<div class="sisme-meta-original"></div>');
        }
        
        const detailsHtml = this.buildDetailsHtml(data);
        
        if ($meta.find('.sisme-meta-details').length) {
            $meta.find('.sisme-meta-details').html(detailsHtml);
        } else {
            $meta.append(`<div class="sisme-meta-details">${detailsHtml}</div>`);
        }
        
        // Charger les images après insertion du HTML
        this.loadMediaImages($meta);
    };
    
    /**
     * Construction HTML des détails
     */
    SismeSubmissionDetails.buildDetailsHtml = function(responseData) {
        const gameData = responseData.game_data || {};
        const metadata = responseData.metadata || {};
        
        let html = '<div class="sisme-submission-details">';
        
        if (gameData.game_name) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🎮 Informations du jeu</h4>
                <div class="sisme-detail-content">
                    <p><strong>Nom :</strong> ${this.escapeHtml(gameData.game_name)}</p>`;
            
            if (gameData.game_description) {
                html += `<p><strong>Description :</strong> ${this.escapeHtml(gameData.game_description)}</p>`;
            }
            
            if (gameData.game_release_date) {
                html += `<p><strong>Date de sortie :</strong> ${this.formatDate(gameData.game_release_date)}</p>`;
            }
            
            if (gameData.game_trailer) {
                html += `<p><strong>Trailer :</strong> <a href="${this.escapeHtml(gameData.game_trailer)}" target="_blank" rel="noopener">Voir la vidéo</a></p>`;
            }
            
            html += '</div></div>';
        }
        
        if (gameData.game_studio_name || gameData.game_publisher_name) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🏢 Studio & Éditeur</h4>
                <div class="sisme-detail-content">`;
            
            if (gameData.game_studio_name) {
                html += `<p><strong>Studio :</strong> ${this.escapeHtml(gameData.game_studio_name)}`;
                if (gameData.game_studio_url) {
                    html += ` - <a href="${this.escapeHtml(gameData.game_studio_url)}" target="_blank" rel="noopener">Voir la page du studio</a>`;
                }
                html += '</p>';
            }
            
            if (gameData.game_publisher_name) {
                html += `<p><strong>Éditeur :</strong> ${this.escapeHtml(gameData.game_publisher_name)}`;
                if (gameData.game_publisher_url) {
                    html += ` - <a href="${this.escapeHtml(gameData.game_publisher_url)}" target="_blank" rel="noopener">Voir la page de l'éditeur</a>`;
                }
                html += '</p>';
            }
            
            html += '</div></div>';
        }
        
        if (gameData.game_genres || gameData.game_platforms || gameData.game_modes) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🏷️ Catégories</h4>
                <div class="sisme-detail-content">`;
            
            if (gameData.game_genres && gameData.game_genres.length) {
                const genreNames = this.convertGenreIds(gameData.game_genres);
                html += `<p><strong>Genres :</strong> ${genreNames.join(', ')}</p>`;
            }
            
            if (gameData.game_platforms && gameData.game_platforms.length) {
                const platformNames = this.convertPlatformIds(gameData.game_platforms);
                html += `<p><strong>Plateformes :</strong> ${platformNames.join(', ')}</p>`;
            }
            
            if (gameData.game_modes && gameData.game_modes.length) {
                const modeNames = this.convertModeIds(gameData.game_modes);
                html += `<p><strong>Modes :</strong> ${modeNames.join(', ')}</p>`;
            }
            
            html += '</div></div>';
        }
        
        if (gameData.covers || gameData.screenshots) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🖼️ Médias</h4>
                <div class="sisme-detail-content">`;
            
            // Covers
            if (gameData.covers) {
                html += `<div class="sisme-media-group">
                    <strong>Covers :</strong>
                    <div class="sisme-media-gallery">`;
                
                if (gameData.covers.horizontal) {
                    html += `<div class="sisme-media-item" data-attachment-id="${gameData.covers.horizontal}">
                        <img src="#" alt="Cover horizontal" class="sisme-media-thumb" data-type="cover-horizontal">
                        <span class="sisme-media-label">Horizontal</span>
                    </div>`;
                }
                
                if (gameData.covers.vertical) {
                    html += `<div class="sisme-media-item" data-attachment-id="${gameData.covers.vertical}">
                        <img src="#" alt="Cover vertical" class="sisme-media-thumb" data-type="cover-vertical">
                        <span class="sisme-media-label">Vertical</span>
                    </div>`;
                }
                
                html += `</div></div>`;
            }
            
            // Screenshots
            if (gameData.screenshots && gameData.screenshots.length > 0) {
                html += `<div class="sisme-media-group">
                    <strong>Screenshots (${gameData.screenshots.length}) :</strong>
                    <div class="sisme-media-gallery">`;
                
                gameData.screenshots.forEach((screenshotId, index) => {
                    html += `<div class="sisme-media-item" data-attachment-id="${screenshotId}">
                        <img src="#" alt="Screenshot ${index + 1}" class="sisme-media-thumb" data-type="screenshot">
                        <span class="sisme-media-label">#${index + 1}</span>
                    </div>`;
                });
                
                html += `</div></div>`;
            }
            
            html += '</div></div>';
        }
        
        if (gameData.external_links && Object.keys(gameData.external_links).length > 0) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🔗 Liens externes</h4>
                <div class="sisme-detail-content">`;
            
            Object.entries(gameData.external_links).forEach(([platform, url]) => {
                if (url) {
                    const platformName = this.getPlatformDisplayName(platform);
                    html += `<p><strong>${platformName} :</strong> <a href="${this.escapeHtml(url)}" target="_blank" rel="noopener">Voir la page ${platformName}</a></p>`;
                }
            });
            
            html += '</div></div>';
        }
        
        if (gameData.sections && gameData.sections.length > 0) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">📝 Sections détaillées</h4>
                <div class="sisme-detail-content">
                    <div class="sisme-sections-container">`;
            
            gameData.sections.forEach((section, index) => {
                if (section.title && section.content) {
                    html += `<div class="sisme-section-detail">
                        <h5 class="sisme-section-title">${this.escapeHtml(section.title)}</h5>
                        <div class="sisme-section-content">${this.escapeHtml(section.content)}</div>`;
                    
                    // Image de section si présente
                    if (section.image_attachment_id) {
                        html += `<div class="sisme-section-image">
                            <img src="#" alt="${this.escapeHtml(section.title)}" 
                                 class="sisme-section-img" 
                                 data-attachment-id="${section.image_attachment_id}">
                        </div>`;
                    }
                    
                    html += `</div>`;
                    
                    // Séparateur si ce n'est pas la dernière section
                    if (index < gameData.sections.length - 1) {
                        html += `<div class="sisme-section-separator"></div>`;
                    }
                }
            });
            
            html += `</div></div></div>`;
        }
        
        html += `<div class="sisme-detail-section">
            <h4 class="sisme-detail-title">📊 Métadonnées</h4>
            <div class="sisme-detail-content">`;
        
        if (metadata.submitted_at) {
            html += `<p><strong>Soumis le :</strong> ${this.formatDateTime(metadata.submitted_at)}</p>`;
        }
        
        if (metadata.completion_percentage) {
            html += `<p><strong>Complétude :</strong> ${metadata.completion_percentage}%</p>`;
        }
        
        if (metadata.created_at) {
            html += `<p><strong>Créé le :</strong> ${this.formatDateTime(metadata.created_at)}</p>`;
        }
        
        if (metadata.updated_at) {
            html += `<p><strong>Dernière mise à jour :</strong> ${this.formatDateTime(metadata.updated_at)}</p>`;
        }
        
        html += '</div></div>';
        html += '</div>';
        return html;
    };
    
    /**
     * Animation d'expansion
     */
    SismeSubmissionDetails.animateExpand = function($meta, $button) {
        const $details = $meta.find('.sisme-meta-details');
        
        $details.hide().slideDown(this.config.animationDuration, () => {
            this.updateButton($button, 'expanded');
        });
        
        $meta.addClass('sisme-meta-expanded');
    };
    
    /**
     * Animation de réduction
     */
    SismeSubmissionDetails.animateCollapse = function($meta, $button) {
        const $details = $meta.find('.sisme-meta-details');
        
        $details.slideUp(this.config.animationDuration, () => {
            this.updateButton($button, 'collapsed');
            $meta.removeClass('sisme-meta-expanded');
        });
    };
    
    /**
     * Mise à jour du bouton
     */
    SismeSubmissionDetails.updateButton = function($button, state) {
        const config = {
            collapsed: { text: '👁️ Voir plus', state: 'collapsed' },
            expanded: { text: '👁️ Voir moins', state: 'expanded' }
        };
        
        const buttonConfig = config[state];
        if (buttonConfig) {
            $button.text(buttonConfig.text).data('state', buttonConfig.state);
        }
    };
    
    /**
     * État de chargement du bouton
     */
    SismeSubmissionDetails.setButtonLoading = function($button, loading) {
        if (loading) {
            $button.data('original-text', $button.text());
            $button.text('⏳ Chargement...').prop('disabled', true);
        } else {
            const originalText = $button.data('original-text') || '👁️ Voir plus';
            $button.text(originalText).prop('disabled', false);
        }
    };
    
    /**
     * Affichage d'erreur
     */
    SismeSubmissionDetails.showError = function($meta, message) {
        const errorHtml = `<div class="sisme-submission-error">
            <p>❌ ${this.escapeHtml(message)}</p>
        </div>`;
        
        if ($meta.find('.sisme-meta-details').length) {
            $meta.find('.sisme-meta-details').html(errorHtml);
        } else {
            $meta.append(`<div class="sisme-meta-details">${errorHtml}</div>`);
        }
    };
    
    /**
     * Gestionnaire de rechargement des soumissions
     */
    SismeSubmissionDetails.onSubmissionsReloaded = function() {
        this.cache = {};
        this.log('Cache vidé après rechargement des soumissions');
    };
    
    /**
     * Charger les images des médias et des sections
     */
    SismeSubmissionDetails.loadMediaImages = function($meta) {
        // Charger les images de la galerie médias
        $meta.find('.sisme-media-thumb').each((index, img) => {
            const $img = $(img);
            const attachmentId = $img.closest('.sisme-media-item').data('attachment-id');
            
            if (attachmentId) {
                this.loadAttachmentImage($img, attachmentId, 'thumbnail');
            }
        });
        
        // Charger les images des sections
        $meta.find('.sisme-section-img').each((index, img) => {
            const $img = $(img);
            const attachmentId = $img.data('attachment-id');
            
            if (attachmentId) {
                this.loadAttachmentImage($img, attachmentId, 'medium');
            }
        });
    };
    
    /**
     * Charger une image d'attachment
     */
    SismeSubmissionDetails.loadAttachmentImage = function($img, attachmentId, size = 'thumbnail') {
        $img.addClass('sisme-loading');
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_attachment_url',
                attachment_id: attachmentId,
                security: this.config.nonce
            },
            success: (response) => {
                if (response.success && response.data.url) {
                    // Pour les images de section, utiliser l'URL complète
                    // Pour les thumbnails, on pourrait demander une taille spécifique
                    $img.attr('src', response.data.url);
                    $img.removeClass('sisme-loading');
                } else {
                    $img.addClass('sisme-error');
                    $img.attr('alt', 'Image non trouvée');
                    $img.removeClass('sisme-loading');
                }
            },
            error: () => {
                $img.addClass('sisme-error');
                $img.attr('alt', 'Erreur de chargement');
                $img.removeClass('sisme-loading');
            }
        });
    };
    
    /**
     * Utilitaires
     */
    SismeSubmissionDetails.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    SismeSubmissionDetails.formatDate = function(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        } catch (e) {
            return dateString;
        }
    };
    
    SismeSubmissionDetails.formatDateTime = function(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR');
        } catch (e) {
            return dateString;
        }
    };
    
    SismeSubmissionDetails.truncateText = function(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };
    
    SismeSubmissionDetails.convertGenreIds = function(genreIds) {
        // Si on a déjà les noms en cache, les utiliser
        if (this.taxonomyCache && this.taxonomyCache.genres) {
            const cachedNames = [];
            let needsAjax = false;
            
            genreIds.forEach(id => {
                if (this.taxonomyCache.genres[id]) {
                    cachedNames.push(this.taxonomyCache.genres[id]);
                } else {
                    cachedNames.push(`Genre ${id}`);
                    needsAjax = true;
                }
            });
            
            // Si on a besoin de données manquantes, faire la requête
            if (needsAjax) {
                this.requestTaxonomyConversion(genreIds, 'genre');
            }
            
            return cachedNames;
        }
        
        // Première fois, demander la conversion via AJAX
        this.requestTaxonomyConversion(genreIds, 'genre');
        
        // En attendant, afficher les IDs
        return genreIds.map(id => `Genre ${id}`);
    };
    
    SismeSubmissionDetails.convertPlatformIds = function(platformIds) {
        const platformNames = {
            'windows': 'Windows',
            'mac': 'Mac',
            'linux': 'Linux',
            'playstation': 'PlayStation',
            'xbox': 'Xbox',
            'nintendo': 'Nintendo Switch',
            'android': 'Android',
            'ios': 'iOS'
        };
        
        return platformIds.map(id => platformNames[id] || this.ucfirst(id));
    };
    
    SismeSubmissionDetails.convertModeIds = function(modeIds) {
        const modeNames = {
            'solo': 'Solo',
            'multijoueur': 'Multijoueur',
            'coop': 'Coopératif',
            'pvp': 'Joueur vs Joueur',
            'mmo': 'MMO'
        };
        
        return modeIds.map(id => modeNames[id] || this.ucfirst(id));
    };
    
    /**
     * Demander la conversion de taxonomies via AJAX
     */
    SismeSubmissionDetails.requestTaxonomyConversion = function(ids, taxonomy) {
        if (!this.taxonomyCache) {
            this.taxonomyCache = {};
        }
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_convert_taxonomy_ids',
                ids: ids,
                taxonomy: taxonomy,
                security: this.config.nonce
            },
            success: (response) => {
                if (response.success && response.data.names) {
                    if (!this.taxonomyCache[taxonomy + 's']) {
                        this.taxonomyCache[taxonomy + 's'] = {};
                    }
                    
                    // Mapper les IDs aux noms retournés
                    ids.forEach((id, index) => {
                        if (response.data.names[index]) {
                            this.taxonomyCache[taxonomy + 's'][id] = response.data.names[index];
                        }
                    });
                    
                    this.log(`Noms ${taxonomy} récupérés:`, response.data.names);
                    
                    // Actualiser l'affichage des détails expandés
                    this.refreshExpandedDetails();
                }
            },
            error: () => {
                this.log(`Erreur conversion taxonomie ${taxonomy}`, 'error');
            }
        });
    };
    
    /**
     * Actualiser l'affichage des détails déjà expandés
     */
    SismeSubmissionDetails.refreshExpandedDetails = function() {
        $('.sisme-submission-item .sisme-meta-expanded').each((index, metaElement) => {
            const $meta = $(metaElement);
            const $item = $meta.closest('.sisme-submission-item');
            const submissionId = $item.data('submission-id');
            
            if (this.cache[submissionId]) {
                // Régénérer le HTML avec les nouvelles données en cache
                this.renderDetails($meta, this.cache[submissionId]);
            }
        });
    };
    
    /**
     * Utilitaire ucfirst
     */
    function ucfirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    SismeSubmissionDetails.getPlatformDisplayName = function(platform) {
        const platformNames = {
            'steam': 'Steam',
            'gog': 'GOG',
            'epic': 'Epic Games Store',
            'itch': 'itch.io',
            'microsoft': 'Microsoft Store',
            'playstation': 'PlayStation Store',
            'nintendo': 'Nintendo eShop',
            'xbox': 'Xbox Store'
        };
        
        return platformNames[platform] || platform.charAt(0).toUpperCase() + platform.slice(1);
    };
    
    SismeSubmissionDetails.log = function(message, type = 'info') {
        if (typeof console !== 'undefined' && console[type]) {
            console[type]('[SismeSubmissionDetails]', message);
        }
    };
    
    $(document).ready(function() {
        SismeSubmissionDetails.init();
    });
    
})(jQuery);