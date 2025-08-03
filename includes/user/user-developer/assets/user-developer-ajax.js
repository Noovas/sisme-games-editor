/**
 * File: /sisme-games-editor/includes/user/user-developer/assets/user-developer-ajax.js
 * Gestion AJAX pour le formulaire de candidature développeur
 * 
 * RESPONSABILITÉ:
 * - Soumission AJAX du formulaire de candidature
 * - Gestion des réponses serveur et feedback utilisateur
 * - Validation côté client avant soumission
 * - Intégration avec le système de nonces WordPress
 */

(function($) {
    'use strict';
    
    // Namespace pour l'AJAX développeur
    window.SismeDeveloperAjax = window.SismeDeveloperAjax || {
        config: {
            formSelector: '#sisme-developer-form',
            feedbackSelector: '#sisme-form-feedback',
            submitButtonSelector: '#sisme-developer-submit',
            retryButtonSelector: '#sisme-retry-application',
            retryFeedbackSelector: '#sisme-retry-feedback',
            ajaxUrl: sismeAjax.ajaxurl,
            nonce: sismeAjax.nonce || sismeAjax.developer_nonce
        },
        isInitialized: false,
        isSubmitting: false
    };
    
    /**
     * Initialisation du module AJAX développeur
     */
    SismeDeveloperAjax.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        // Vérifier que les dépendances sont disponibles
        if (typeof sismeAjax === 'undefined') {
            return;
        }
        
        this.bindEvents();
        this.isInitialized = true;
    };
    
    /**
     * Événements AJAX
     */
    SismeDeveloperAjax.bindEvents = function() {
        $(document).on('submit', this.config.formSelector, this.handleFormSubmit.bind(this));
        $(document).on('click', this.config.retryButtonSelector, this.handleRetryApplication.bind(this));
    };

    /**
     * Gérer le reset d'une candidature rejetée
     */
    SismeDeveloperAjax.handleRetryApplication = function(e) {
        e.preventDefault();
        
        if (this.isSubmitting) {
            return;
        }
        
        const $button = $(e.target);
        const $feedback = $(this.config.retryFeedbackSelector);
        
        // Confirmer l'action
        if (!confirm('Êtes-vous sûr de vouloir refaire une candidature ? Cela supprimera votre candidature actuelle.')) {
            return;
        }
        
        this.isSubmitting = true;
        
        // UI Loading
        $button.prop('disabled', true).html('🔄 Réinitialisation...');
        $feedback.removeClass('sisme-feedback-success sisme-feedback-error').hide();
        
        // Requête AJAX
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_developer_reset_rejection',
                security: this.config.nonce
            },
            dataType: 'json',
            timeout: 30000
        })
        .done(this.handleRetrySuccess.bind(this, $button, $feedback))
        .fail(this.handleRetryError.bind(this, $button, $feedback));
    };
    
    /**
     * Succès du reset
     */
    SismeDeveloperAjax.handleRetrySuccess = function($button, $feedback, response) {
        this.isSubmitting = false;
        
        if (response.success) {
            // Afficher le succès
            $feedback
                .addClass('sisme-feedback-success')
                .html('<strong>✅ ' + response.data.message + '</strong>')
                .show();
            
            // Recharger le dashboard après un délai
            setTimeout(function() {
                if (response.data.reload_dashboard) {
                    window.location.reload();
                } else {
                    // Fallback: recharger manuellement la section développeur
                    if (typeof SismeDashboard !== 'undefined' && SismeDashboard.loadSection) {
                        SismeDashboard.loadSection('developer');
                    }
                }
            }, 2000);
            
        } else {
            // Erreur côté serveur
            this.handleRetryError($button, $feedback, {
                responseJSON: {
                    data: {
                        message: response.data.message || 'Erreur lors du reset de la candidature.'
                    }
                }
            });
        }
    };
    
    /**
     * Erreur du reset
     */
    SismeDeveloperAjax.handleRetryError = function($button, $feedback, xhr) {
        this.isSubmitting = false;
        
        // Restaurer le bouton
        $button.prop('disabled', false).html('🔄 Faire une nouvelle demande');
        
        // Message d'erreur
        let errorMessage = 'Erreur lors de la réinitialisation. Veuillez réessayer.';
        
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            errorMessage = xhr.responseJSON.data.message;
        } else if (xhr.status === 0) {
            errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
        } else if (xhr.status >= 500) {
            errorMessage = 'Erreur du serveur. Veuillez réessayer plus tard.';
        }
        
        // Afficher l'erreur
        $feedback
            .addClass('sisme-feedback-error')
            .html('<strong>❌ ' + errorMessage + '</strong>')
            .show();
    };
    
    /**
     * Gérer la soumission du formulaire
     */
    SismeDeveloperAjax.handleFormSubmit = function(e) {
        e.preventDefault();
        
        if (this.isSubmitting) {
            return;
        }
        
        const $form = $(e.target);
        const formData = this.collectFormData($form);
        
        // Validation complète avant soumission
        const validationErrors = this.validateFormData(formData);
        if (Object.keys(validationErrors).length > 0) {
            this.showValidationErrors(validationErrors);
            this.showFeedback('Veuillez corriger les erreurs avant de soumettre', 'error');
            return;
        }
        
        // Soumission AJAX
        this.submitApplication(formData);
    };
    
    /**
     * Collecter les données du formulaire
     */
    SismeDeveloperAjax.collectFormData = function($form) {
        const formData = {};
        
        // Collecter tous les champs du formulaire
        $form.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const value = $field.val();
            
            if (name && value !== undefined) {
                // Traitement spécial pour les liens sociaux
                if (name.startsWith('social_')) {
                    const platform = name.replace('social_', '');
                    if (!formData.studio_social_links) {
                        formData.studio_social_links = {};
                    }
                    if (value.trim() !== '') {
                        formData.studio_social_links[platform] = value;
                    }
                } else {
                    formData[name] = value;
                }
            }
        });
        
        return formData;
    };
    
    /**
     * Soumettre la candidature via AJAX
     */
    SismeDeveloperAjax.submitApplication = function(formData) {
        this.isSubmitting = true;
        
        const $submitButton = $(this.config.submitButtonSelector);
        const originalText = $submitButton.text();
        
        // Désactiver le bouton et changer le texte
        $submitButton.prop('disabled', true).text('Soumission en cours...');
        
        // Afficher un feedback de chargement
        this.showFeedback('Soumission de votre candidature...', 'loading');
        
        // Préparer les données AJAX
        const ajaxData = {
            action: 'sisme_developer_submit',
            security: this.config.nonce,
            ...formData
        };
        
        // Requête AJAX
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            timeout: 30000,
            success: this.handleSubmitSuccess.bind(this),
            error: this.handleSubmitError.bind(this),
            complete: function() {
                // Réactiver le bouton
                $submitButton.prop('disabled', false).text(originalText);
                this.isSubmitting = false;
            }.bind(this)
        });
    };
    
    /**
     * Gérer le succès de la soumission
     */
    SismeDeveloperAjax.handleSubmitSuccess = function(response) {
        if (response.success) {
            this.showFeedback(response.data.message, 'success');
            
            // Réinitialiser le formulaire
            $(this.config.formSelector)[0].reset();
            
            // Recharger le dashboard si nécessaire
            if (response.data.reload_dashboard) {
                setTimeout(() => {
                    if (typeof SismeDashboard !== 'undefined' && SismeDashboard.reloadCurrentSection) {
                        SismeDashboard.reloadCurrentSection();
                    } else {
                        location.reload();
                    }
                }, 2000);
            }
        } else {
            this.handleSubmitError(response);
        }
    };
    
    /**
     * Gérer les erreurs de soumission
     */
    SismeDeveloperAjax.handleSubmitError = function(response) {
        let errorMessage = 'Erreur lors de la soumission de votre candidature.';
        
        if (response.responseJSON && response.responseJSON.data) {
            errorMessage = response.responseJSON.data.message || errorMessage;
            
            // Afficher les erreurs de validation spécifiques
            if (response.responseJSON.data.errors) {
                this.showValidationErrors(response.responseJSON.data.errors);
            }
        } else if (response.data && response.data.message) {
            errorMessage = response.data.message;
        }
        
        this.showFeedback(errorMessage, 'error');
    };
    
    /**
     * Validation complète des données du formulaire
     */
    SismeDeveloperAjax.validateFormData = function(formData) {
        const errors = {};
        
        // Validation nom du studio
        if (!formData.studio_name || formData.studio_name.trim().length < 2) {
            errors.studio_name = 'Le nom du studio doit contenir au moins 2 caractères.';
        }
        
        // Validation description du studio
        if (!formData.studio_description || formData.studio_description.trim().length < 10) {
            errors.studio_description = 'La description du studio doit contenir au moins 10 caractères.';
        }
        
        // Validation URL du site web (optionnel)
        if (formData.studio_website && !this.isValidUrl(formData.studio_website)) {
            errors.studio_website = 'L\'URL du site web n\'est pas valide.';
        }
        
        // Validation liens sociaux (tous doivent être des URLs)
        if (formData.studio_social_links) {
            const platformDomains = {
                'twitter': ['twitter.com', 'x.com'],
                'discord': ['discord.gg', 'discord.com', 'discordapp.com'],
                'instagram': ['instagram.com'],
                'youtube': ['youtube.com', 'youtu.be'],
                'twitch': ['twitch.tv'],
                'facebook': ['facebook.com', 'fb.com'],
                'linkedin': ['linkedin.com']
            };
            
            Object.keys(formData.studio_social_links).forEach(platform => {
                const value = formData.studio_social_links[platform];
                if (value) {
                    // Vérifier que c'est une URL valide
                    if (!this.isValidUrl(value)) {
                        errors[`social_${platform}`] = `Le lien ${platform} doit être une URL valide.`;
                        return;
                    }
                    
                    // Vérifier le domaine spécifique
                    if (platformDomains[platform]) {
                        const validDomain = platformDomains[platform].some(domain => 
                            value.toLowerCase().includes(domain)
                        );
                        
                        if (!validDomain) {
                            const allowedDomains = platformDomains[platform].join(', ');
                            errors[`social_${platform}`] = `Le lien ${platform} doit contenir un domaine valide (${allowedDomains}).`;
                        }
                    }
                }
            });
        }
        
        // Validation prénom
        if (!formData.representative_firstname || formData.representative_firstname.trim().length < 2) {
            errors.representative_firstname = 'Le prénom doit contenir au moins 2 caractères.';
        }
        
        // Validation nom
        if (!formData.representative_lastname || formData.representative_lastname.trim().length < 2) {
            errors.representative_lastname = 'Le nom doit contenir au moins 2 caractères.';
        }
        
        // Validation date de naissance
        if (!formData.representative_birthdate) {
            errors.representative_birthdate = 'La date de naissance est requise.';
        } else if (!this.isValidAge(formData.representative_birthdate)) {
            errors.representative_birthdate = 'Vous devez avoir au moins 18 ans pour candidater.';
        }
        
        // Validation adresse
        if (!formData.representative_address || formData.representative_address.trim().length < 5) {
            errors.representative_address = 'L\'adresse doit contenir au moins 5 caractères.';
        }
        
        // Validation ville
        if (!formData.representative_city || formData.representative_city.trim().length < 2) {
            errors.representative_city = 'La ville doit contenir au moins 2 caractères.';
        }
        
        // Validation pays
        if (!formData.representative_country || formData.representative_country.trim().length < 2) {
            errors.representative_country = 'Le pays doit contenir au moins 2 caractères.';
        }
        
        // Validation email
        if (!formData.representative_email) {
            errors.representative_email = 'L\'email est requis.';
        } else if (!this.isValidEmail(formData.representative_email)) {
            errors.representative_email = 'L\'email n\'est pas valide.';
        }
        
        // Validation téléphone
        if (!formData.representative_phone || formData.representative_phone.trim().length < 8) {
            errors.representative_phone = 'Le numéro de téléphone doit contenir au moins 8 caractères.';
        }
        
        return errors;
    };
    
    /**
     * Afficher les erreurs de validation
     */
    SismeDeveloperAjax.showValidationErrors = function(errors) {
        // Nettoyer les erreurs précédentes
        $('.sisme-field-error').remove();
        $('.sisme-form-field').removeClass('has-error');
        
        // Afficher les nouvelles erreurs
        Object.keys(errors).forEach(fieldName => {
            const $field = $(`[name="${fieldName}"]`);
            if ($field.length) {
                const $fieldContainer = $field.closest('.sisme-form-field');
                $fieldContainer.addClass('has-error');
                
                const errorHtml = `<div class="sisme-field-error">${errors[fieldName]}</div>`;
                $fieldContainer.append(errorHtml);
            }
        });
    };
    
    /**
     * Afficher un feedback utilisateur
     */
    SismeDeveloperAjax.showFeedback = function(message, type) {
        const $feedback = $(this.config.feedbackSelector);
        
        if ($feedback.length) {
            $feedback
                .removeClass('success error warning loading')
                .addClass(type)
                .html(message)
                .fadeIn();
            
            // Auto-masquer après 5 secondes pour les succès
            if (type === 'success') {
                setTimeout(() => {
                    $feedback.fadeOut();
                }, 5000);
            }
        }
    };
    
    /**
     * Valider une URL
     */
    SismeDeveloperAjax.isValidUrl = function(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    };
    
    /**
     * Valider un email
     */
    SismeDeveloperAjax.isValidEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };
    
    /**
     * Valider l'âge (18 ans minimum)
     */
    SismeDeveloperAjax.isValidAge = function(birthdate) {
        const today = new Date();
        const birth = new Date(birthdate);
        const age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            return age - 1 >= 18;
        }
        
        return age >= 18;
    };
    
    // Initialisation automatique quand le DOM est prêt
    $(document).ready(function() {
        SismeDeveloperAjax.init();
    });

    /**
     * Remplir le formulaire avec les données d'une soumission existante
     */
    SismeDeveloperAjax.fillFormWithSubmissionData = function(submissionId, submissionData) {
        
        // Récupérer les vraies données via AJAX car get_submission_details ne retourne que le résumé
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_full_submission_data',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.game_data) {
                    const gameData = response.data.game_data;
                    
                    // Remplir les champs texte
                    const textFields = [
                        'game_name', 'game_description', 'game_release_date', 
                        'game_trailer', 'game_studio_name', 'game_publisher_name'
                    ];
                    
                    textFields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && gameData[fieldName]) {
                            field.value = gameData[fieldName];
                            
                            // Déclencher les événements pour la validation
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    
                    // Ajouter l'ID de soumission pour les futures sauvegardes
                    let submissionIdField = document.getElementById('current-submission-id');
                    if (!submissionIdField) {
                        submissionIdField = document.createElement('input');
                        submissionIdField.type = 'hidden';
                        submissionIdField.id = 'current-submission-id';
                        submissionIdField.name = 'submission_id';
                        const form = document.getElementById('sisme-submit-game-form');
                        if (form) {
                            form.appendChild(submissionIdField);
                        }
                    }
                    submissionIdField.value = submissionId;
                    
                    // Remplir les sélections multiples (genres, plateformes, etc.)
                    this.fillMultiSelectFields(gameData);
                    
                    // Remplir les images si elles existent
                    this.fillImageFields(gameData);
                    
                    // Déclencher la revalidation du formulaire
                    if (typeof window.submissionValidator !== 'undefined') {
                        setTimeout(() => {
                            window.submissionValidator.validateForm();
                        }, 100);
                    }
                    
                    this.showFeedback('Brouillon chargé avec succès', 'success');
                    
                } else {
                    this.showFeedback('Impossible de charger les données complètes du brouillon', 'error');
                }
            }.bind(this),
            error: function() {
                this.showFeedback('Erreur lors du chargement des données', 'error');
            }.bind(this)
        });
    };

    /**
     * Remplir les champs de sélection multiple
     */
    SismeDeveloperAjax.fillMultiSelectFields = function(gameData) {
        // Genres
        if (gameData.genres && Array.isArray(gameData.genres)) {
            gameData.genres.forEach(genreId => {
                const checkbox = document.querySelector(`input[name="game_genres[]"][value="${genreId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Plateformes
        if (gameData.platforms && Array.isArray(gameData.platforms)) {
            gameData.platforms.forEach(platform => {
                const checkbox = document.querySelector(`input[name="game_platforms[]"][value="${platform}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Liens externes
        if (gameData.external_links && typeof gameData.external_links === 'object') {
            Object.entries(gameData.external_links).forEach(([platform, url]) => {
                const field = document.querySelector(`input[name="external_links[${platform}]"]`);
                if (field && url) {
                    field.value = url;
                }
            });
        }
    };

    /**
     * Remplir les champs d'images
     */
    SismeDeveloperAjax.fillImageFields = function(gameData) {
        if (gameData.covers) {
            // Cover horizontale
            if (gameData.covers.horizontal) {
                this.setImageInCropper('cover_horizontal', gameData.covers.horizontal);
            }
            
            // Cover verticale  
            if (gameData.covers.vertical) {
                this.setImageInCropper('cover_vertical', gameData.covers.vertical);
            }
        }
    };

    /**
     * Définir une image dans un cropper
     */
    SismeDeveloperAjax.setImageInCropper = function(ratioType, imageUrl) {
        const container = document.querySelector(`[data-ratio-type="${ratioType}"]`);
        if (container && imageUrl) {
        }
    };

    /**
     * Voir un jeu publié
     */
    SismeDeveloperAjax.viewPublishedGame = function(submissionId) {
        this.showFeedback('Redirection vers la page du jeu en cours de développement', 'info');
    };

    /**
     * Voir les statistiques d'un jeu
     */
    SismeDeveloperAjax.viewStats = function(submissionId) {
        this.showFeedback('Statistiques en cours de développement', 'info');
    };

    /**
     * Voir les notes de rejet
     */
    SismeDeveloperAjax.viewRejectionNotes = function(submissionId) {        
        if (this.isSubmitting) {
            return;
        }
        
        this.isSubmitting = true;
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_submission_details',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success && response.data.admin_notes) {
                    this.showRejectionModal(response.data.admin_notes, response.data.game_name);
                } else {
                    this.showFeedback('Aucune note disponible', 'info');
                }
            }.bind(this),
            error: function() {
                this.showFeedback('Erreur lors du chargement des notes', 'error');
            }.bind(this),
            complete: function() {
                this.isSubmitting = false;
            }.bind(this)
        });
    };

    // ======================================================
    // 🔧 UTILITAIRES POUR MES JEUX
    // ======================================================

    /**
     * Validation basique des champs requis (fallback)
     */
    SismeDeveloperAjax.validateBasicRequiredFields = function() {
        const requiredFields = ['game_name', 'game_description', 'game_release_date', 'game_studio_name', 'game_publisher_name'];
        
        for (let fieldName of requiredFields) {
            const field = document.getElementById(fieldName);
            if (!field || !field.value.trim()) {
                return false;
            }
        }
        
        return true;
    };

    /**
     * Afficher modal avec notes de rejet (réutilise le style existant)
     */
    SismeDeveloperAjax.showRejectionModal = function(adminNotes, gameName) {
        // Créer le modal avec le même style que vos feedbacks
        const modal = document.createElement('div');
        modal.className = 'sisme-rejection-modal';
        modal.innerHTML = `
            <div class="sisme-modal-backdrop">
                <div class="sisme-modal-content">
                    <div class="sisme-modal-header">
                        <h3>📄 Notes de rejet - ${gameName}</h3>
                        <button class="sisme-modal-close" onclick="this.closest('.sisme-rejection-modal').remove()">×</button>
                    </div>
                    <div class="sisme-modal-body">
                        <p><strong>Raisons du rejet :</strong></p>
                        <div class="sisme-admin-notes">${adminNotes}</div>
                    </div>
                    <div class="sisme-modal-footer">
                        <button class="sisme-btn sisme-btn-secondary" onclick="this.closest('.sisme-rejection-modal').remove()">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Styles inline cohérents avec votre thème
        const modalStyles = `
            <style>
            .sisme-rejection-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10001;
            }
            .sisme-modal-backdrop {
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .sisme-modal-content {
                background: #4a4a4a;
                border-radius: 8px;
                max-width: 500px;
                width: 100%;
                max-height: 80vh;
                overflow-y: auto;
                color: #ffffff;
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            }
            .sisme-modal-header {
                padding: 20px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: rgba(0,0,0,0.3);
            }
            .sisme-modal-header h3 {
                margin: 0;
                font-size: 18px;
            }
            .sisme-modal-close {
                background: none;
                border: none;
                color: #ffffff;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
            }
            .sisme-modal-close:hover {
                background: rgba(255,255,255,0.1);
            }
            .sisme-modal-body {
                padding: 20px;
            }
            .sisme-admin-notes {
                background: rgba(0,0,0,0.3);
                padding: 15px;
                border-radius: 6px;
                margin-top: 10px;
                border-left: 3px solid #dc3545;
                line-height: 1.5;
            }
            .sisme-modal-footer {
                padding: 20px;
                border-top: 1px solid rgba(255,255,255,0.1);
                text-align: right;
                background: rgba(0,0,0,0.2);
            }
            .sisme-btn {
                padding: 8px 16px;
                border-radius: 4px;
                border: none;
                cursor: pointer;
                font-weight: 500;
            }
            .sisme-btn-secondary {
                background: rgba(255,255,255,0.1);
                color: #ffffff;
            }
            .sisme-btn-secondary:hover {
                background: rgba(255,255,255,0.2);
            }
            </style>
        `;
        
        // Ajouter les styles
        document.head.insertAdjacentHTML('beforeend', modalStyles);
        
        // Ajouter au DOM
        document.body.appendChild(modal);
        
        // Fermer avec échap
        const closeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeHandler);
            }
        };
        document.addEventListener('keydown', closeHandler);
        
        // Fermer en cliquant sur le backdrop
        modal.querySelector('.sisme-modal-backdrop').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                modal.remove();
            }
        });
    };

    // ======================================================
    // 🚀 INITIALISATION ÉTENDUE
    // ======================================================

    // Étendre l'initialisation existante (au lieu de remplacer)
    $(document).ready(function() {
        // Attendre que le module principal soit initialisé
        setTimeout(() => {
            if (SismeDeveloperAjax.isInitialized) {
                //SismeDeveloperAjax.log('Extension Mes Jeux chargée');
                
                // Initialiser les compteurs si la section est déjà affichée
                //SismeDeveloperAjax.updateGamesCounts();
            }
        }, 100);
    });

    // ======================================================
    // 🔗 ALIAS POUR COMPATIBILITÉ
    // ======================================================

    // Créer des alias pour que les boutons HTML fonctionnent
    window.SismeDeveloper = window.SismeDeveloper || {};

    SismeDeveloper.viewPublishedGame = function(submissionId) {
        return SismeDeveloperAjax.viewPublishedGame.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewStats = function(submissionId) {
        return SismeDeveloperAjax.viewStats.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewRejectionNotes = function(submissionId) {
        return SismeDeveloperAjax.viewRejectionNotes.apply(SismeDeveloperAjax, arguments);
    };

    // ========================================
    // FONCTIONS UTILITAIRES POUR LE LOADER
    // ========================================
    /**
     * Afficher le loader modal
     */
    SismeDeveloperAjax.showLoader = function(message = 'Chargement en cours...') {
        // Supprimer ancien loader s'il existe
        this.hideLoader();
        
        // Créer le loader modal
        const loaderHtml = `
            <div id="sisme-loader-modal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 999999;
                font-family: Arial, sans-serif;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    min-width: 300px;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #3498db;
                        border-radius: 50%;
                        animation: sisme-spin 1s linear infinite;
                        margin: 0 auto 20px auto;
                    "></div>
                    <p style="
                        margin: 0;
                        color: #333;
                        font-size: 16px;
                        font-weight: 500;
                    ">${message}</p>
                </div>
            </div>
            
            <style>
                @keyframes sisme-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
        
        // Ajouter au DOM
        document.body.insertAdjacentHTML('beforeend', loaderHtml);
    };

    /**
     * Cacher le loader modal
     */
    SismeDeveloperAjax.hideLoader = function() {
        const loader = document.getElementById('sisme-loader-modal');
        if (loader) {
            loader.remove();
        }
    };

    /**
     * Mettre à jour le message du loader
     */
    SismeDeveloperAjax.updateLoaderMessage = function(message) {
        const loader = document.getElementById('sisme-loader-modal');
        if (loader) {
            const messageElement = loader.querySelector('p');
            if (messageElement) {
                messageElement.textContent = message;
            }
        }
    };
    
})(jQuery);