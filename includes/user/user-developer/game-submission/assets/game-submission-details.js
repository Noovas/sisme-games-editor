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
    };
    
    /**
     * Construction HTML des détails
     */
    SismeSubmissionDetails.buildDetailsHtml = function(data) {
        let html = '<div class="sisme-submission-details">';
        
        if (data.game_name) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🎮 Informations du jeu</h4>
                <div class="sisme-detail-content">
                    <p><strong>Nom :</strong> ${this.escapeHtml(data.game_name)}</p>`;
            
            if (data.game_description) {
                html += `<p><strong>Description :</strong> ${this.escapeHtml(data.game_description)}</p>`;
            }
            
            if (data.game_release_date) {
                html += `<p><strong>Date de sortie :</strong> ${this.formatDate(data.game_release_date)}</p>`;
            }
            
            if (data.game_trailer) {
                html += `<p><strong>Trailer :</strong> <a href="${this.escapeHtml(data.game_trailer)}" target="_blank" rel="noopener">Voir la vidéo</a></p>`;
            }
            
            html += '</div></div>';
        }
        
        if (data.game_studio_name || data.game_publisher_name) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🏢 Studio & Éditeur</h4>
                <div class="sisme-detail-content">`;
            
            if (data.game_studio_name) {
                html += `<p><strong>Studio :</strong> ${this.escapeHtml(data.game_studio_name)}`;
                if (data.game_studio_url) {
                    html += ` <a href="${this.escapeHtml(data.game_studio_url)}" target="_blank" rel="noopener">🔗</a>`;
                }
                html += '</p>';
            }
            
            if (data.game_publisher_name) {
                html += `<p><strong>Éditeur :</strong> ${this.escapeHtml(data.game_publisher_name)}`;
                if (data.game_publisher_url) {
                    html += ` <a href="${this.escapeHtml(data.game_publisher_url)}" target="_blank" rel="noopener">🔗</a>`;
                }
                html += '</p>';
            }
            
            html += '</div></div>';
        }
        
        if (data.game_genres || data.game_platforms || data.game_modes) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">🏷️ Catégories</h4>
                <div class="sisme-detail-content">`;
            
            if (data.game_genres && data.game_genres.length) {
                html += `<p><strong>Genres :</strong> ${data.game_genres.map(g => this.escapeHtml(g)).join(', ')}</p>`;
            }
            
            if (data.game_platforms && data.game_platforms.length) {
                html += `<p><strong>Plateformes :</strong> ${data.game_platforms.map(p => this.escapeHtml(p)).join(', ')}</p>`;
            }
            
            if (data.game_modes && data.game_modes.length) {
                html += `<p><strong>Modes :</strong> ${data.game_modes.map(m => this.escapeHtml(m)).join(', ')}</p>`;
            }
            
            html += '</div></div>';
        }
        
        if (data.metadata) {
            html += `<div class="sisme-detail-section">
                <h4 class="sisme-detail-title">📊 Métadonnées</h4>
                <div class="sisme-detail-content">`;
            
            if (data.metadata.submitted_at) {
                html += `<p><strong>Soumis le :</strong> ${this.formatDateTime(data.metadata.submitted_at)}</p>`;
            }
            
            if (data.metadata.completion_percentage) {
                html += `<p><strong>Complétude :</strong> ${data.metadata.completion_percentage}%</p>`;
            }
            
            html += '</div></div>';
        }
        
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
    
    SismeSubmissionDetails.log = function(message, type = 'info') {
        if (typeof console !== 'undefined' && console[type]) {
            console[type]('[SismeSubmissionDetails]', message);
        }
    };
    
    $(document).ready(function() {
        SismeSubmissionDetails.init();
    });
    
})(jQuery);