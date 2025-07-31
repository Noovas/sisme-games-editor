/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/assets/game-submission.js
 * JavaScript pour la gestion des soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Gestion CRUD des soumissions de jeux
 * - Auto-sauvegarde des brouillons
 * - Interface utilisateur dynamique
 * - Validation et workflow des soumissions
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - sismeAjax (variables AJAX globales)
 * - SismeDashboard (navigation sections)
 */

(function($) {
    'use strict';
    
    window.SismeGameSubmission = window.SismeGameSubmission || {
        config: {
            formSelector: '#sisme-submit-game-form',
            feedbackSelector: '#sisme-submit-game-feedback',
            submitButtonSelector: '#sisme-submit-game-button',
            draftButtonSelector: '#sisme-submit-game-btn',
            ajaxUrl: sismeAjax.ajaxurl,
            nonce: sismeAjax.nonce,
            autoSaveInterval: 30000, // 30 secondes
            currentSubmissionId: null,
            autoSaveTimer: null,
            isSubmitting: false,
            isDraftSaving: false
        },
        isInitialized: false
    };
    
    /**
     * Initialisation du module soumissions
     */
    SismeGameSubmission.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        if (typeof sismeAjax === 'undefined') {
            this.log('Erreur: sismeAjax non défini');
            return;
        }
        
        this.bindEvents();
        this.initFormValidation();
        this.isInitialized = true;
        
        this.log('Module Soumissions Jeux initialisé');
    };
    
    /**
     * Liaison des événements
     */
    SismeGameSubmission.bindEvents = function() {
        // Boutons soumissions dans la liste
        $(document).on('click', '.sisme-submission-item button', this.handleSubmissionAction.bind(this));
        
        // Formulaire de soumission
        $(document).on('click', this.config.draftButtonSelector, this.saveDraft.bind(this));
        $(document).on('click', this.config.submitButtonSelector, this.submitForReview.bind(this));
        
        // Auto-save sur changement de champs
        $(document).on('input change', this.config.formSelector + ' input, ' + this.config.formSelector + ' textarea, ' + this.config.formSelector + ' select', 
            this.scheduleAutoSave.bind(this));
        
        // Navigation dashboard
        $(document).on('sisme:section:changed', this.onSectionChanged.bind(this));
    };
    
    /**
     * Gérer les actions sur les soumissions
     */
    SismeGameSubmission.handleSubmissionAction = function(e) {
        const $button = $(e.target);
        const $item = $button.closest('.sisme-submission-item');
        const submissionId = $item.data('submission-id');
        const action = $button.text().trim();
        
        if (!submissionId) {
            this.log('Erreur: ID soumission manquant');
            return;
        }
        
        if (action.includes('Continuer')) {
            this.editSubmission(submissionId);
        } else if (action.includes('Supprimer')) {
            this.deleteSubmission(submissionId);
        } else if (action.includes('Voir')) {
            this.viewSubmission(submissionId);
        } else if (action.includes('Réessayer')) {
            this.retrySubmission(submissionId);
        }
    };
    
    /**
     * Éditer une soumission existante
     */
    SismeGameSubmission.editSubmission = function(submissionId) {
        this.log('Édition soumission: ' + submissionId);
        
        // Charger les données de la soumission
        this.loadSubmissionData(submissionId).then(() => {
            // Naviguer vers le formulaire
            if (typeof SismeDashboard !== 'undefined') {
                SismeDashboard.setActiveSection('submit-game', true);
            }
            
            // Activer l'auto-save pour cette soumission
            this.config.currentSubmissionId = submissionId;
            this.enableAutoSave();
        }).catch(error => {
            this.showFeedback('Erreur lors du chargement de la soumission', 'error');
            this.log('Erreur chargement soumission: ' + error);
        });
    };
    
    /**
     * Supprimer une soumission (brouillons uniquement)
     */
    SismeGameSubmission.deleteSubmission = function(submissionId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette soumission ? Cette action est irréversible.')) {
            return;
        }
        
        this.log('Suppression soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_delete_game_submission',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    this.refreshSubmissionsList();
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur réseau lors de la suppression', 'error');
            }
        });
    };
    
    /**
     * Voir les détails d'une soumission
     */
    SismeGameSubmission.viewSubmission = function(submissionId) {
        this.log('Affichage détails soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_submission_details',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showSubmissionModal(response.data.submission);
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur lors du chargement des détails', 'error');
            }
        });
    };
    
    /**
     * Créer nouvelle version après rejet
     */
    SismeGameSubmission.retrySubmission = function(submissionId) {
        if (!confirm('Créer une nouvelle version de cette soumission ?')) {
            return;
        }
        
        this.log('Retry soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_retry_rejected_submission',
                security: this.config.nonce,
                original_submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    this.refreshSubmissionsList();
                    
                    // Naviguer vers l'édition de la nouvelle soumission
                    if (response.data.new_submission_id) {
                        this.editSubmission(response.data.new_submission_id);
                    }
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur lors de la création de la nouvelle version', 'error');
            }
        });
    };
    
    /**
     * Sauvegarder brouillon
     */
    SismeGameSubmission.saveDraft = function(e) {
        if (e) e.preventDefault();
        
        if (this.isDraftSaving) {
            return;
        }
        
        this.isDraftSaving = true;
        const $button = $(this.config.draftButtonSelector);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('💾 Sauvegarde...');
        
        const gameData = this.collectFormData();
        const isNewSubmission = !this.config.currentSubmissionId;
        
        const ajaxData = {
            security: this.config.nonce,
            ...gameData
        };
        
        if (isNewSubmission) {
            ajaxData.action = 'sisme_create_game_submission';
        } else {
            ajaxData.action = 'sisme_save_draft_submission';
            ajaxData.submission_id = this.config.currentSubmissionId;
        }
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    if (isNewSubmission && response.data.submission_id) {
                        this.config.currentSubmissionId = response.data.submission_id;
                        this.enableAutoSave();
                    }
                    
                    this.showFeedback(response.data.message, 'success');
                    this.updateCompletionProgress(response.data.completion_percentage);
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur réseau lors de la sauvegarde', 'error');
            },
            complete: () => {
                $button.prop('disabled', false).text(originalText);
                this.isDraftSaving = false;
            }
        });
    };
    
    /**
     * Soumettre pour validation
     */
    SismeGameSubmission.submitForReview = function(e) {
        if (e) e.preventDefault();
        
        if (this.isSubmitting || !this.config.currentSubmissionId) {
            return;
        }
        
        if (!this.validateForm()) {
            this.showFeedback('Veuillez corriger les erreurs dans le formulaire', 'error');
            return;
        }
        
        if (!confirm('Soumettre ce jeu pour validation ? Vous ne pourrez plus le modifier.')) {
            return;
        }
        
        this.isSubmitting = true;
        const $button = $(this.config.submitButtonSelector);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('🚀 Soumission...');
        this.disableAutoSave();
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_submit_game_for_review',
                security: this.config.nonce,
                submission_id: this.config.currentSubmissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    
                    // Retourner à la liste après soumission
                    setTimeout(() => {
                        if (typeof SismeDashboard !== 'undefined') {
                            SismeDashboard.setActiveSection('developer', true);
                        }
                    }, 2000);
                } else {
                    this.showFeedback(response.data.message, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: () => {
                this.showFeedback('Erreur réseau lors de la soumission', 'error');
                $button.prop('disabled', false).text(originalText);
            },
            complete: () => {
                this.isSubmitting = false;
            }
        });
    };
    
    /**
     * Charger les données d'une soumission
     */
    SismeGameSubmission.loadSubmissionData = function(submissionId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_get_submission_details',
                    security: this.config.nonce,
                    submission_id: submissionId
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.populateForm(response.data.submission);
                        resolve(response.data.submission);
                    } else {
                        reject(response.data.message);
                    }
                },
                error: () => {
                    reject('Erreur réseau');
                }
            });
        });
    };
    
    /**
     * Remplir le formulaire avec les données d'une soumission
     */
    SismeGameSubmission.populateForm = function(submission) {
        const gameData = submission.game_data || {};
        const $form = $(this.config.formSelector);

        Object.keys(gameData).forEach(key => {
            const $field = $form.find('[name="' + key + '"]');
            if ($field.length && gameData[key]) {
                $field.val(gameData[key]);
            }
        });
        
        if (gameData.external_links) {
            Object.entries(gameData.external_links).forEach(([platform, url]) => {
                $form.find(`input[name="external_links[${platform}]"]`).val(url);
            });
        }

        ['game_genres', 'game_platforms', 'game_modes'].forEach(fieldName => {
            if (gameData[fieldName] && Array.isArray(gameData[fieldName])) {
                gameData[fieldName].forEach(value => {
                    $form.find(`input[name="${fieldName}[]"][value="${value}"]`).prop('checked', true);
                });
            }
        });

        const completion = submission.metadata?.completion_percentage || 0;
        this.updateCompletionProgress(completion);
    };
    
    /**
     * Collecter les données du formulaire
     */
    SismeGameSubmission.collectFormData = function() {
        const $form = $(this.config.formSelector);
        const gameData = {};
        
        $form.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');
            
            
            if (!name || $field.is(':disabled')) return;
            
            if (type === 'checkbox' || type === 'radio') {
                if ($field.is(':checked')) {
                    if (name.endsWith('[]')) {
                        if (!gameData[name]) gameData[name] = [];
                        gameData[name].push($field.val());
                    } else {
                        gameData[name] = $field.val();
                    }
                }
            } else if ($field.is('select[multiple]')) {
                gameData[name] = $field.val() || [];
            } else {
                gameData[name] = $field.val();
            }
        });
        
        const externalLinks = {};
        $form.find('input[name^="external_links["]').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const match = name.match(/external_links\[([^\]]+)\]/);
            if (match && $field.val().trim()) {
                externalLinks[match[1]] = $field.val().trim();
            }
        });
        if (Object.keys(externalLinks).length > 0) {
            gameData['external_links'] = externalLinks;
        }
        
        return gameData;
    };
    
    /**
     * Auto-sauvegarde programmée
     */
    SismeGameSubmission.scheduleAutoSave = function() {
        if (!this.config.currentSubmissionId) {
            return;
        }
        
        clearTimeout(this.config.autoSaveTimer);
        this.config.autoSaveTimer = setTimeout(() => {
            this.performAutoSave();
        }, this.config.autoSaveInterval);
    };
    
    /**
     * Effectuer auto-sauvegarde
     */
    SismeGameSubmission.performAutoSave = function() {
        if (this.isDraftSaving || this.isSubmitting) {
            return;
        }
        
        const gameData = this.collectFormData();
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_save_draft_submission',
                security: this.config.nonce,
                submission_id: this.config.currentSubmissionId,
                ...gameData
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showAutoSaveIndicator(response.data.last_auto_save);
                    this.updateCompletionProgress(response.data.completion_percentage);
                }
            }
        });
    };
    
    /**
     * Activer l'auto-sauvegarde
     */
    SismeGameSubmission.enableAutoSave = function() {
        this.scheduleAutoSave();
    };
    
    /**
     * Désactiver l'auto-sauvegarde
     */
    SismeGameSubmission.disableAutoSave = function() {
        clearTimeout(this.config.autoSaveTimer);
    };
    
    /**
     * Validation du formulaire
     */
    SismeGameSubmission.validateForm = function() {
        // Validation basique - sera étendue avec game-submission-validator.js
        const $form = $(this.config.formSelector);
        let isValid = true;
        
        // Champs requis
        $form.find('[required]').each(function() {
            const $field = $(this);
            if (!$field.val().trim()) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        return isValid;
    };
    
    /**
     * Initialiser la validation du formulaire
     */
    SismeGameSubmission.initFormValidation = function() {
        // Validation temps réel
        $(document).on('blur', this.config.formSelector + ' [required]', function() {
            const $field = $(this);
            if ($field.val().trim()) {
                $field.removeClass('error');
            }
        });
    };
    
    /**
     * Mettre à jour l'indicateur de progression
     */
    SismeGameSubmission.updateCompletionProgress = function(percentage) {
        const $button = $(this.config.submitButtonSelector);
        
        if (percentage >= 100) {
            $button.prop('disabled', false).text('🚀 Soumettre pour Validation');
        } else {
            $button.prop('disabled', true).text('📝 Complétez le formulaire (' + percentage + '%)');
        }
    };
    
    /**
     * Afficher l'indicateur d'auto-sauvegarde
     */
    SismeGameSubmission.showAutoSaveIndicator = function(time) {
        // Indicateur discret d'auto-save
        $('.sisme-auto-save-indicator').remove();
        $(this.config.formSelector).append(
            '<div class="sisme-auto-save-indicator">💾 Sauvegardé automatiquement à ' + time + '</div>'
        );
        
        setTimeout(() => {
            $('.sisme-auto-save-indicator').fadeOut();
        }, 3000);
    };
    
    /**
     * Rafraîchir la liste des soumissions
     */
    SismeGameSubmission.refreshSubmissionsList = function() {
        // Recharger la section développeur
        if (typeof SismeDashboard !== 'undefined') {
            SismeDashboard.refreshSection('developer');
        }
    };
    
    /**
     * Afficher une modal avec les détails
     */
    SismeGameSubmission.showSubmissionModal = function(submission) {
        // Modal simple pour l'instant
        const gameName = submission.game_data?.game_name || 'Jeu sans nom';
        const status = submission.status || 'unknown';
        
        alert('Détails de "' + gameName + '":\nStatut: ' + status);
        // TODO: Créer une vraie modal
    };
    
    /**
     * Gérer le changement de section dashboard
     */
    SismeGameSubmission.onSectionChanged = function(e, section) {
        if (section === 'submit-game') {
            // Nouveau formulaire
            this.config.currentSubmissionId = null;
            this.disableAutoSave();
        } else if (section !== 'submit-game' && this.config.currentSubmissionId) {
            // Quitter le formulaire - désactiver auto-save
            this.disableAutoSave();
        }
    };
    
    /**
     * Afficher un feedback utilisateur
     */
    SismeGameSubmission.showFeedback = function(message, type = 'info') {
        const $feedback = $(this.config.feedbackSelector);
        
        $feedback.removeClass('success error info warning')
                .addClass(type)
                .html(message)
                .show();
        
        if (type === 'success') {
            setTimeout(() => {
                $feedback.fadeOut();
            }, 5000);
        }
    };
    
    /**
     * Log de débogage
     */
    SismeGameSubmission.log = function(message) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[SismeGameSubmission] ' + message);
        }
    };
    
    // Initialisation automatique
    $(document).ready(() => {
        SismeGameSubmission.init();
    });
    
})(jQuery);