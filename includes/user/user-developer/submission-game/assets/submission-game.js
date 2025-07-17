/**
 * File: /sisme-games-editor/includes/user/user-developer/submission-game/assets/submission-game.js
 * JavaScript pour l'éditeur de soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Gestion du formulaire de soumission
 * - Validation temps réel
 * - Sauvegarde automatique
 * - Compteur de caractères
 * - Soumission AJAX
 */

(function($) {
    'use strict';
    
    window.SismeSubmissionGame = {
        config: {
            formSelector: '#sisme-submission-game-form',
            editorSelector: '.sisme-submission-game-editor',
            feedbackSelector: '#sisme-submission-game-feedback',
            autoSaveInterval: 30000, // 30 secondes
            ajaxUrl: sismeAjax.ajaxurl,
            nonce: sismeAjax.nonce || sismeAjax.developer_nonce
        },
        
        state: {
            submissionId: null,
            isInitialized: false,
            isSaving: false,
            isSubmitting: false,
            autoSaveTimer: null,
            hasUnsavedChanges: false
        },
        
        init: function() {
            if (this.state.isInitialized) {
                return;
            }
            
            this.state.submissionId = $(this.config.editorSelector).data('submission-id') || null;
            this.bindEvents();
            this.initCharacterCounter();
            this.startAutoSave();
            this.state.isInitialized = true;
            
            this.log('Module SismeSubmissionGame initialisé');
        },
        
        bindEvents: function() {
            $(document).on('submit', this.config.formSelector, this.handleSubmit.bind(this));
            $(document).on('input change', this.config.formSelector + ' input, ' + this.config.formSelector + ' textarea', this.handleFieldChange.bind(this));
            $(document).on('blur', this.config.formSelector + ' input, ' + this.config.formSelector + ' textarea', this.validateField.bind(this));
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            
            if (this.state.isSubmitting) {
                return;
            }
            
            this.submitForValidation();
        },
        
        handleFieldChange: function(e) {
            this.state.hasUnsavedChanges = true;
            this.validateField(e);
            this.updateCharacterCounter();
        },
        
        validateField: function(e) {
            const $field = $(e.target);
            const $fieldContainer = $field.closest('.sisme-form-field');
            const value = $field.val().trim();
            const fieldName = $field.attr('name');
            
            $fieldContainer.removeClass('valid invalid');
            $fieldContainer.find('.sisme-form-field-error').remove();
            
            let isValid = true;
            let errorMessage = '';
            
            if (fieldName === 'game_name') {
                if (value.length === 0) {
                    isValid = false;
                    errorMessage = 'Le nom du jeu est obligatoire';
                } else if (value.length < 3) {
                    isValid = false;
                    errorMessage = 'Le nom doit faire au moins 3 caractères';
                } else if (value.length > 100) {
                    isValid = false;
                    errorMessage = 'Le nom ne peut pas dépasser 100 caractères';
                }
            } else if (fieldName === 'description') {
                if (value.length === 0) {
                    isValid = false;
                    errorMessage = 'La description est obligatoire';
                } else if (value.length < 140) {
                    isValid = false;
                    errorMessage = 'La description doit faire au moins 140 caractères';
                } else if (value.length > 180) {
                    isValid = false;
                    errorMessage = 'La description ne peut pas dépasser 180 caractères';
                }
            }
            
            if (isValid) {
                $fieldContainer.addClass('valid');
            } else {
                $fieldContainer.addClass('invalid');
                if (errorMessage) {
                    $fieldContainer.find('.sisme-form-field-feedback').append(
                        '<div class="sisme-form-field-error">' + errorMessage + '</div>'
                    );
                }
            }
            
            return isValid;
        },
        
        initCharacterCounter: function() {
            const $description = $('textarea[name="description"]');
            if ($description.length) {
                this.updateCharacterCounter();
            }
        },
        
        updateCharacterCounter: function() {
            const $description = $('textarea[name="description"]');
            const $counter = $('.sisme-char-current');
            
            if ($description.length && $counter.length) {
                const currentLength = $description.val().length;
                $counter.text(currentLength);
                
                $counter.removeClass('valid invalid');
                if (currentLength >= 140 && currentLength <= 180) {
                    $counter.addClass('valid');
                } else {
                    $counter.addClass('invalid');
                }
            }
        },
        
        startAutoSave: function() {
            this.state.autoSaveTimer = setInterval(() => {
                if (this.state.hasUnsavedChanges && !this.state.isSaving) {
                    this.saveAsDraft(true);
                }
            }, this.config.autoSaveInterval);
        },
        
        stopAutoSave: function() {
            if (this.state.autoSaveTimer) {
                clearInterval(this.state.autoSaveTimer);
                this.state.autoSaveTimer = null;
            }
        },
        
        saveAsDraft: function(isAutoSave = false) {
            if (this.state.isSaving) {
                return;
            }
            
            const formData = this.getFormData();
            
            this.state.isSaving = true;
            this.showAutoSaveIndicator('saving');
            
            if (!isAutoSave) {
                this.showFeedback('Sauvegarde en cours...', 'loading');
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_save_submission_game',
                    security: this.config.nonce,
                    submission_id: this.state.submissionId,
                    ...formData
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        this.state.submissionId = response.data.submission_id;
                        this.state.hasUnsavedChanges = false;
                        
                        if (isAutoSave) {
                            this.showAutoSaveIndicator('saved');
                        } else {
                            this.showFeedback('Brouillon sauvegardé avec succès', 'success');
                        }
                        
                        this.log('Sauvegarde réussie:', response.data);
                    } else {
                        this.handleSaveError(response.data, isAutoSave);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    this.handleSaveError({message: 'Erreur de connexion'}, isAutoSave);
                }.bind(this),
                complete: function() {
                    this.state.isSaving = false;
                    if (isAutoSave) {
                        setTimeout(() => {
                            this.hideAutoSaveIndicator();
                        }, 2000);
                    }
                }.bind(this)
            });
        },
        
        submitForValidation: function() {
            if (this.state.isSubmitting) {
                return;
            }
            
            if (!this.validateForm()) {
                this.showFeedback('Veuillez corriger les erreurs avant de soumettre', 'error');
                return;
            }
            
            const formData = this.getFormData();
            
            this.state.isSubmitting = true;
            this.showFeedback('Soumission en cours...', 'loading');
            
            const $submitBtn = $('button[type="submit"]');
            const originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('🚀 Envoi en cours...');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_submit_submission_game',
                    security: this.config.nonce,
                    submission_id: this.state.submissionId,
                    ...formData
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        this.state.hasUnsavedChanges = false;
                        this.stopAutoSave();
                        this.showFeedback('Soumission envoyée avec succès ! Redirection...', 'success');
                        
                        setTimeout(() => {
                            if (typeof SismeDeveloperAjax !== 'undefined' && SismeDeveloperAjax.reloadDashboardSection) {
                                SismeDeveloperAjax.reloadDashboardSection();
                            } else {
                                location.reload();
                            }
                        }, 2000);
                    } else {
                        this.handleSubmitError(response.data);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    this.handleSubmitError({message: 'Erreur de connexion'});
                }.bind(this),
                complete: function() {
                    this.state.isSubmitting = false;
                    $submitBtn.prop('disabled', false).text(originalText);
                }.bind(this)
            });
        },
        
        getFormData: function() {
            const $form = $(this.config.formSelector);
            return {
                game_name: $form.find('[name="game_name"]').val().trim(),
                description: $form.find('[name="description"]').val().trim()
            };
        },
        
        validateForm: function() {
            let isValid = true;
            
            $(this.config.formSelector + ' input, ' + this.config.formSelector + ' textarea').each((index, field) => {
                const fieldValid = this.validateField({target: field});
                if (!fieldValid) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        showFeedback: function(message, type) {
            const $feedback = $(this.config.feedbackSelector);
            $feedback
                .removeClass('success error loading')
                .addClass(type)
                .text(message)
                .show();
        },
        
        showAutoSaveIndicator: function(type) {
            const $indicator = $('.sisme-auto-save-indicator');
            const $text = $('.sisme-auto-save-text');
            
            $indicator.removeClass('saving saved error').addClass(type);
            
            const messages = {
                saving: 'Sauvegarde automatique...',
                saved: 'Sauvegardé automatiquement',
                error: 'Erreur de sauvegarde'
            };
            
            $text.text(messages[type] || '');
            $indicator.show();
        },
        
        hideAutoSaveIndicator: function() {
            $('.sisme-auto-save-indicator').hide();
        },
        
        handleSaveError: function(error, isAutoSave) {
            this.log('Erreur sauvegarde:', error);
            
            if (isAutoSave) {
                this.showAutoSaveIndicator('error');
            } else {
                this.showFeedback(error.message || 'Erreur lors de la sauvegarde', 'error');
            }
        },
        
        handleSubmitError: function(error) {
            this.log('Erreur soumission:', error);
            
            if (error.validation_errors) {
                this.showFeedback('Validation échouée', 'error');
            } else {
                this.showFeedback(error.message || 'Erreur lors de la soumission', 'error');
            }
        },
        
        log: function(message, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('[SismeSubmissionGame] ' + message, data || '');
            }
        }
    };
    
    $(document).ready(function() {
        if ($('.sisme-submission-game-editor').length) {
            SismeSubmissionGame.init();
        }
    });
    
})(jQuery);