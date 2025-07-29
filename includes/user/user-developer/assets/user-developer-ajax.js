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
            this.log('Erreur: sismeAjax non défini');
            return;
        }
        
        this.bindEvents();
        this.isInitialized = true;
        
        this.log('Module AJAX développeur initialisé');
    };
    
    /**
     * Événements AJAX
     */
    SismeDeveloperAjax.bindEvents = function() {
        // Soumission du formulaire
        $(document).on('submit', this.config.formSelector, this.handleFormSubmit.bind(this));
        
        // Bouton reset candidature rejetée
        $(document).on('click', this.config.retryButtonSelector, this.handleRetryApplication.bind(this));

        // Sauvegarde du brouillon (bouton submit du form)
        $(document).on('click', '#sisme-submit-game-btn', this.handleSaveDraft.bind(this));

        // Soumission finale du jeu (bouton séparé)
        $(document).on('click', '#sisme-submit-game-button', this.handleSubmitGame.bind(this));
        
        this.log('Événements AJAX développeur liés');
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
        
        this.log('Erreur reset candidature:', xhr);
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
            
            this.log('Candidature soumise avec succès', response.data);
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
        this.log('Erreur soumission candidature', response);
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
    
    /**
     * Logger pour debug
     */
    SismeDeveloperAjax.log = function(message, data) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Sisme Developer Ajax] ' + message, data || '');
        }
    };
    
    // Initialisation automatique quand le DOM est prêt
    $(document).ready(function() {
        SismeDeveloperAjax.init();
    });

    // ======================================================
    // 🎮 EXTENSION - GESTION DES SOUMISSIONS DE JEUX
    // ======================================================

    /**
     * Démarrer une nouvelle soumission
     */
    SismeDeveloperAjax.startNewSubmission = function() {
        console.log('[DEBUG] startNewSubmission appelée');
        this.log('Ouverture onglet soumission');
        
        // Vérifier si SismeDashboard existe
        if (window.SismeDashboard) {
            console.log('[DEBUG] SismeDashboard existe');
            
            if (typeof SismeDashboard.setActiveSection === 'function') {
                console.log('[DEBUG] setActiveSection existe, appel avec submit-game');
                SismeDashboard.setActiveSection('submit-game', true);
            } else {
                console.log('[DEBUG] setActiveSection n\'existe pas, fallback hash');
                window.location.hash = 'submit-game';
            }
        } else {
            console.log('[DEBUG] SismeDashboard n\'existe pas, fallback hash');
            window.location.hash = 'submit-game';
        }
        
        console.log('[DEBUG] Fin startNewSubmission');
    };

    /**
     * Continuer une soumission existante
     */
    SismeDeveloperAjax.continueSubmission = function(submissionId) {
        this.log('Continuation soumission:', submissionId);
        this.openSubmissionEditor(submissionId);
    };

    /**
     * Ouvrir l'éditeur de soumission (placeholder pour futur module)
     */
    SismeDeveloperAjax.openSubmissionEditor = function(submissionId) {
        this.log('Ouverture éditeur pour soumission:', submissionId);
        
        // Afficher le loader immédiatement
        this.showLoader('Préparation de l\'éditeur...');
        
        // Stocker l'ID pour après le changement de section
        window.sismeEditingSubmissionId = submissionId;
        
        // Naviguer vers submit-game
        if (typeof SismeDashboard !== 'undefined') {
            SismeDashboard.setActiveSection('submit-game', true);
        } else {
            window.location.hash = 'submit-game';
        }
        
        // Mettre à jour le message du loader
        setTimeout(() => {
            this.updateLoaderMessage('Chargement du brouillon...');
        }, 300);
        
        // Attendre que la section soit chargée puis charger les données
        setTimeout(() => {
            this.loadSubmissionDataIntoForm(submissionId);
        }, 800);
    };

    SismeDeveloperAjax.loadSubmissionDataIntoForm = function(submissionId) {
        // Mettre à jour le message du loader
        this.updateLoaderMessage('Récupération des données...');
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_full_submission_data',
                security: this.config.nonce,
                submission_id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    this.updateLoaderMessage('Remplissage du formulaire...');
                    
                    const gameData = response.data.game_data;
                    
                    // ✅ MAPPING BDD → FORMULAIRE pour les champs texte
                    const fieldMapping = {
                        // BDD → Formulaire
                        'game_name': 'game_name',
                        'description': 'game_description',        // ← CORRECTION
                        'release_date': 'game_release_date',       // ← CORRECTION
                        'trailer_link': 'game_trailer',            // ← CORRECTION
                        'game_studio_name': 'game_studio_name',
                        'game_publisher_name': 'game_publisher_name'
                    };
                    
                    Object.entries(fieldMapping).forEach(([dbField, formField]) => {
                        const element = document.getElementById(formField);
                        if (element && gameData[dbField]) {
                            element.value = gameData[dbField];
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                            element.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    
                    // ✅ CHAMPS COVERS - Structure spéciale
                    if (gameData.covers) {
                        ['horizontal', 'vertical'].forEach(type => {
                            const element = document.getElementById(`cover_${type}`);
                            if (element && gameData.covers[type]) {
                                element.value = gameData.covers[type];
                                element.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        });
                    }
                    
                    // ✅ SCREENSHOTS
                    if (gameData.screenshots) {
                        const screenshotsField = document.getElementById('screenshots');
                        if (screenshotsField) {
                            screenshotsField.value = gameData.screenshots;
                            screenshotsField.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                    
                    // Ajouter l'ID de soumission au formulaire
                    let hiddenField = document.getElementById('current-submission-id');
                    if (!hiddenField) {
                        hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.id = 'current-submission-id';
                        hiddenField.name = 'submission_id';
                        const form = document.getElementById('sisme-submit-game-form');
                        if (form) {
                            form.appendChild(hiddenField);
                        }
                    }
                    hiddenField.value = submissionId;
                    
                    // Remplir les champs avancés (genres, plateformes, modes, liens)
                    this.fillAdvancedFields(gameData);
                    
                    // Finalisation
                    setTimeout(() => {
                        this.updateLoaderMessage('Finalisation...');
                        
                        // Déclencher la revalidation si le validator existe
                        if (typeof window.submissionValidator !== 'undefined') {
                            window.submissionValidator.validateForm();
                        }
                        
                        // Masquer le loader après un court délai
                        setTimeout(() => {
                            this.hideLoader();
                            this.showFeedback('✅ Brouillon chargé avec succès', 'success');
                        }, 500);
                        
                    }, 300);
                    
                } else {
                    this.hideLoader();
                    this.showFeedback('❌ Impossible de charger les données du brouillon', 'error');
                }
            }.bind(this),
            error: function(xhr, status, error) {
                this.log('Erreur chargement données:', {xhr, status, error});
                this.hideLoader();
                this.showFeedback('❌ Erreur de connexion lors du chargement', 'error');
            }.bind(this)
        });
    };

    /**
     * Remplir les champs avancés (genres, plateformes, liens, etc.)
     */
    SismeDeveloperAjax.fillAdvancedFields = function(gameData) {
        // ✅ GENRES - Mapping BDD → Formulaire
        if (gameData.genres && Array.isArray(gameData.genres)) {
            gameData.genres.forEach(genreId => {
                const checkbox = document.querySelector(`input[name="game_genres[]"][value="${genreId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        // ✅ PLATEFORMES - Mapping BDD → Formulaire  
        if (gameData.platforms && Array.isArray(gameData.platforms)) {
            gameData.platforms.forEach(platform => {
                const checkbox = document.querySelector(`input[name="game_platforms[]"][value="${platform}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        // ✅ MODES DE JEU - Mapping BDD → Formulaire
        if (gameData.modes && Array.isArray(gameData.modes)) {
            gameData.modes.forEach(mode => {
                const checkbox = document.querySelector(`input[name="game_modes[]"][value="${mode}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        // ✅ LIENS EXTERNES - OK
        if (gameData.external_links && typeof gameData.external_links === 'object') {
            Object.entries(gameData.external_links).forEach(([platform, url]) => {
                const field = document.querySelector(`input[name="external_links[${platform}]"]`);
                if (field && url) {
                    field.value = url;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }
        
        // ✅ SCREENSHOTS - OK
        if (gameData.screenshots) {
            const screenshotsField = document.querySelector('[name="screenshots"]');
            if (screenshotsField) {
                screenshotsField.value = gameData.screenshots;
                screenshotsField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        
        // ✅ COVERS - Mapping structure BDD → Formulaire
        if (gameData.covers) {
            if (gameData.covers.horizontal) {
                const horizontalField = document.querySelector('[name="cover_horizontal"]');
                if (horizontalField) {
                    horizontalField.value = gameData.covers.horizontal;
                }
            }
            
            if (gameData.covers.vertical) {
                const verticalField = document.querySelector('[name="cover_vertical"]');
                if (verticalField) {
                    verticalField.value = gameData.covers.vertical;
                }
            }
        }
        
        this.log('Champs avancés remplis avec mapping correct:', gameData);
    };

    /**
     * Supprimer une soumission
     */
    SismeDeveloperAjax.deleteSubmission = function(submissionId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette soumission ? Cette action est irréversible.')) {
            return;
        }
        
        this.log('Suppression soumission:', submissionId);
        
        if (this.isSubmitting) {
            return;
        }
        
        // Animer l'élément pendant la suppression
        const gameItem = document.querySelector(`[data-submission-id="${submissionId}"]`);
        if (gameItem) {
            gameItem.style.opacity = '0.5';
            gameItem.style.pointerEvents = 'none';
        }
        
        this.isSubmitting = true;
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_delete_submission',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                this.log('Suppression résultat:', response);
                
                if (response.success) {
                    // Animer la suppression et supprimer l'élément
                    if (gameItem) {
                        gameItem.style.transform = 'translateX(-100%)';
                        gameItem.style.transition = 'transform 0.3s ease';
                        setTimeout(() => {
                            gameItem.remove();
                            this.updateGamesCounts();
                            
                            // ✅ FIX: Vérifier s'il faut réafficher l'état vide
                            const allRemainingItems = document.querySelectorAll('.sisme-game-item');
                            const emptyState = document.querySelector('.sisme-games-empty');
                            
                            if (allRemainingItems.length === 0 && emptyState) {
                                emptyState.style.display = 'block';
                            }
                        }, 300);
                    }
                    this.showFeedback('Soumission supprimée avec succès', 'success');
                } else {
                    console.error('[SismeDeveloperAjax] Erreur suppression:', response.data);
                    this.showFeedback('Erreur lors de la suppression', 'error');
                    // Restaurer l'élément
                    if (gameItem) {
                        gameItem.style.opacity = '1';
                        gameItem.style.pointerEvents = 'auto';
                    }
                }
            }.bind(this),
            error: function(xhr, status, error) {
                console.error('[SismeDeveloperAjax] Erreur AJAX suppression:', error);
                this.showFeedback('Erreur de connexion', 'error');
                // Restaurer l'élément
                if (gameItem) {
                    gameItem.style.opacity = '1';
                    gameItem.style.pointerEvents = 'auto';
                }
            }.bind(this),
            complete: function() {
                this.isSubmitting = false;
            }.bind(this)
        });
    };

    /**
     * Remplir le formulaire avec les données d'une soumission existante
     */
    SismeDeveloperAjax.fillFormWithSubmissionData = function(submissionId, submissionData) {
        this.log('Remplissage formulaire avec données:', submissionData);
        
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
            // TODO: Intégration avec votre système de crop
            // Cela dépend de l'implémentation de SimpleCropper
            this.log(`Image à charger pour ${ratioType}:`, imageUrl);
        }
    };

    /**
     * Voir une soumission en attente
     */
    SismeDeveloperAjax.viewSubmission = function(submissionId) {
        this.log('Consultation soumission:', submissionId);
        // TODO: Ouvrir modal de consultation
        this.showFeedback('Mode consultation en cours de développement', 'info');
    };

    /**
     * Voir un jeu publié
     */
    SismeDeveloperAjax.viewPublishedGame = function(submissionId) {
        this.log('Consultation jeu publié:', submissionId);
        // TODO: Rediriger vers la page du jeu
        this.showFeedback('Redirection vers la page du jeu en cours de développement', 'info');
    };

    /**
     * Voir les statistiques d'un jeu
     */
    SismeDeveloperAjax.viewStats = function(submissionId) {
        this.log('Consultation stats:', submissionId);
        // TODO: Ouvrir modal de statistiques
        this.showFeedback('Statistiques en cours de développement', 'info');
    };

    /**
     * Voir les notes de rejet
     */
    SismeDeveloperAjax.viewRejectionNotes = function(submissionId) {
        this.log('Consultation notes rejet:', submissionId);
        
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

    /**
     * Réessayer une soumission rejetée
     */
    SismeDeveloperAjax.retrySubmission = function(submissionId) {
        if (!confirm('Voulez-vous créer une nouvelle version de cette soumission ?')) {
            return;
        }
        
        this.log('Nouvelle tentative soumission:', submissionId);
        
        if (this.isSubmitting) {
            return;
        }
        
        this.isSubmitting = true;
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_retry_submission',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success && response.data.new_submission_id) {
                    this.openSubmissionEditor(response.data.new_submission_id);
                    this.showFeedback('Nouvelle version créée avec succès', 'success');
                } else {
                    this.showFeedback('Erreur lors de la création de la nouvelle version', 'error');
                }
            }.bind(this),
            error: function() {
                this.showFeedback('Erreur de connexion', 'error');
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
     * Mettre à jour les compteurs de jeux
     */
    SismeDeveloperAjax.updateGamesCounts = function() {
        const groups = document.querySelectorAll('.sisme-games-group');
        let totalGames = 0;
        
        groups.forEach(group => {
            const gamesList = group.querySelector('.sisme-games-list');
            const title = group.querySelector('.sisme-games-group-title');
            const gameItems = gamesList.querySelectorAll('.sisme-game-item');
            const count = gameItems.length;
            
            if (count === 0) {
                // Cacher le groupe vide
                group.style.display = 'none';
            } else {
                // Afficher le groupe et mettre à jour le compteur
                group.style.display = 'block';
                const titleText = title.textContent.replace(/\(\d+\)/, `(${count})`);
                title.textContent = titleText;
                totalGames += count;
            }
        });
        
        // ✅ Gestion de l'état vide
        const emptyState = document.querySelector('.sisme-games-empty');
        if (emptyState) {
            if (totalGames === 0) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }
        }
    };

    /**
     * Gérer la sauvegarde du brouillon
     */
    SismeDeveloperAjax.handleSaveDraft = function(e) {
        e.preventDefault();

        if (this.isSubmitting) {
            return;
        }
        
        this.log('Sauvegarde brouillon déclenchée');
        
        const form = document.getElementById('sisme-submit-game-form');
        if (!form) {
            this.showFeedback('Formulaire introuvable', 'error');
            return;
        }
        
        // Collecter toutes les données du formulaire
        const formData = this.collectGameFormData(form);
        
        // Ajouter les métadonnées de sauvegarde
        formData.action = 'sisme_save_submission_game';
        formData.security = this.config.nonce;
        
        // Récupérer l'ID de soumission existant si présent
        const submissionIdField = document.getElementById('current-submission-id');
        if (submissionIdField && submissionIdField.value) {
            formData.submission_id = submissionIdField.value;
        }
        
        this.log('Données brouillon à sauvegarder:', formData);
        
        // Soumettre via AJAX
        this.submitDraftAjax(formData);
    };

    /**
     * Gérer la soumission finale du jeu
     */
    SismeDeveloperAjax.handleSubmitGame = function(e) {
        e.preventDefault();
        
        if (this.isSubmitting) {
            return;
        }
        
        this.log('Soumission finale déclenchée');
        
        // Vérifier la validation complète via le validator
        if (typeof window.submissionValidator !== 'undefined') {
            if (!window.submissionValidator.isFormValid()) {
                this.showFeedback('Le formulaire contient des erreurs. Veuillez les corriger avant de soumettre.', 'error');
                
                // Afficher le debug de validation
                console.log('Validation state:', window.submissionValidator.getValidationState());
                return;
            }
        } else {
            // Fallback : validation basique sans le validator
            if (!this.validateBasicRequiredFields()) {
                this.showFeedback('Veuillez remplir tous les champs obligatoires', 'error');
                return;
            }
        }
        
        // Confirmation utilisateur
        const gameName = document.getElementById('game_name')?.value || 'votre jeu';
        if (!confirm(`Êtes-vous sûr de vouloir soumettre "${gameName}" pour validation ?\n\nUne fois soumis, vous ne pourrez plus le modifier jusqu'à la réponse de notre équipe.`)) {
            return;
        }
        
        const form = document.getElementById('sisme-submit-game-form');
        if (!form) {
            this.showFeedback('Formulaire introuvable', 'error');
            return;
        }
        
        // Collecter les données du formulaire
        const formData = this.collectGameFormData(form);
        
        // Ajouter les métadonnées de soumission
        formData.action = 'sisme_submit_submission_game';
        formData.security = this.config.nonce;
        formData.final_submission = true;
        
        this.log('Données finales à soumettre:', formData);
        
        // Soumettre via AJAX
        this.submitGameAjax(formData);
    };

    /**
     * Collecter les données du formulaire de soumission
     */
    SismeDeveloperAjax.collectGameFormData = function(form) {
        const formData = {};
        
        // ✅ CHAMPS TEXTE - Mapping correct formulaire → BDD
        const textFieldMapping = {
            // Nom formulaire → Nom BDD
            'game_name': 'game_name',                    // OK
            'game_description': 'description',           // ← CORRECTION
            'game_release_date': 'release_date',         // ← CORRECTION  
            'game_trailer': 'trailer_link',              // ← CORRECTION
            'game_studio_name': 'game_studio_name',      // OK
            'game_publisher_name': 'game_publisher_name', // OK
            'cover_horizontal': 'cover_horizontal',       // OK
            'cover_vertical': 'cover_vertical',          // OK
            'screenshots': 'screenshots'                 // OK
        };
        
        Object.entries(textFieldMapping).forEach(([formName, dbName]) => {
            const field = form.querySelector(`[name="${formName}"]`);
            if (field) {
                formData[dbName] = field.value.trim();
            }
        });
        
        // ✅ CHAMPS MULTI-SÉLECTION - Noms corrects
        const multiFieldMapping = {
            // Nom formulaire → Nom BDD
            'game_genres': 'genres',
            'game_platforms': 'platforms',
            'game_modes': 'modes'
            // ❌ game_developers et game_publishers n'existent pas dans le formulaire
        };
        
        Object.entries(multiFieldMapping).forEach(([formName, dbName]) => {
            const fields = form.querySelectorAll(`[name="${formName}[]"]:checked, [name="${formName}"] option:checked`);
            formData[dbName] = Array.from(fields).map(field => field.value);
        });
        
        // ✅ CHAMPS INEXISTANTS - Valeurs par défaut
        formData.developers = [];  // Le formulaire n'a que le nom du studio
        formData.publishers = [];  // Le formulaire n'a que le nom de l'éditeur
        
        // ✅ LIENS EXTERNES - OK
        const externalLinks = {};
        const linkFields = form.querySelectorAll('[name^="external_links["]');
        linkFields.forEach(field => {
            const match = field.name.match(/external_links\[([^\]]+)\]/);
            if (match && field.value.trim()) {
                externalLinks[match[1]] = field.value.trim();
            }
        });
        formData.external_links = externalLinks;
        
        // ✅ STRUCTURE COVERS - Format attendu par la BDD
        formData.covers = {
            horizontal: formData.cover_horizontal || '',
            vertical: formData.cover_vertical || ''
        };
        
        // Nettoyer les champs temporaires
        delete formData.cover_horizontal;
        delete formData.cover_vertical;
        
        return formData;
    };

    /**
     * Soumettre le brouillon via AJAX
     */
    SismeDeveloperAjax.submitDraftAjax = function(formData) {
        const saveButton = document.getElementById('sisme-submit-game-btn');
        const originalText = saveButton ? saveButton.textContent : '';
        
        this.isSubmitting = true;
        
        // Feedback visuel avec loader
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = '💾 Sauvegarde...';
        }
        
        this.showFeedback('💾 Sauvegarde en cours...', 'info');
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                this.log('Sauvegarde brouillon résultat:', response);
                
                if (response.success) {
                    // ÉTAPE 1 : Confirmer sauvegarde
                    this.showFeedback('✅ Brouillon sauvegardé ! Redirection...', 'success');
                    
                    setTimeout(() => {
                        // Juste changer le hash et recharger
                        window.location.hash = 'developer';
                        window.location.reload();
                    }, 800);
                    
                } else {
                    this.showFeedback('❌ ' + (response.data?.message || 'Erreur lors de la sauvegarde'), 'error');
                    
                    // Réactiver le bouton en cas d'erreur
                    if (saveButton) {
                        saveButton.disabled = false;
                        saveButton.textContent = originalText;
                    }
                }
            }.bind(this),
            error: function(xhr, status, error) {
                this.log('Erreur sauvegarde brouillon:', {xhr, status, error});
                
                this.showFeedback('❌ Erreur de connexion lors de la sauvegarde', 'error');
                
                // Réactiver le bouton
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = originalText;
                }
            }.bind(this),
            complete: function() {
                this.isSubmitting = false;
            }.bind(this)
        });
    };

    /**
     * Soumettre le jeu final via AJAX  
     */
    SismeDeveloperAjax.submitGameAjax = function(formData) {
        const submitButton = document.getElementById('sisme-submit-game-button');
        const originalText = submitButton ? submitButton.textContent : '';
        
        this.isSubmitting = true;
        
        // Feedback visuel
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = '🚀 Soumission...';
        }
        
        this.showFeedback('Soumission en cours...', 'info');
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                this.log('Soumission finale résultat:', response);
                
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    
                    // Rediriger vers "Mes Jeux" après 3 secondes
                    setTimeout(() => {
                        if (typeof SismeDashboard !== 'undefined') {
                            SismeDashboard.setActiveSection('developer', true);
                        } else {
                            location.reload();
                        }
                    }, 3000);
                    
                } else {
                    this.showFeedback(response.data?.message || 'Erreur lors de la soumission', 'error');
                    
                    // Réactiver le bouton en cas d'erreur
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            }.bind(this),
            error: function(xhr, status, error) {
                this.log('Erreur soumission finale:', {xhr, status, error});
                
                let errorMessage = 'Erreur de connexion lors de la soumission';
                if (xhr.status === 403) {
                    errorMessage = 'Session expirée. Veuillez recharger la page.';
                }
                
                this.showFeedback(errorMessage, 'error');
                
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }.bind(this),
            complete: function() {
                this.isSubmitting = false;
            }.bind(this)
        });
    };

    /**
     * Validation basique des champs requis (fallback)
     */
    SismeDeveloperAjax.validateBasicRequiredFields = function() {
        const requiredFields = ['game_name', 'game_description', 'game_release_date', 'game_studio_name', 'game_publisher_name'];
        
        for (let fieldName of requiredFields) {
            const field = document.getElementById(fieldName);
            if (!field || !field.value.trim()) {
                console.log(`Champ requis manquant: ${fieldName}`);
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
                SismeDeveloperAjax.log('Extension Mes Jeux chargée');
                
                // Initialiser les compteurs si la section est déjà affichée
                SismeDeveloperAjax.updateGamesCounts();
            }
        }, 100);
    });

    // ======================================================
    // 🔗 ALIAS POUR COMPATIBILITÉ
    // ======================================================

    // Créer des alias pour que les boutons HTML fonctionnent
    window.SismeDeveloper = window.SismeDeveloper || {};

    // Rediriger les appels vers SismeDeveloperAjax
    SismeDeveloper.startNewSubmission = function() {
        return SismeDeveloperAjax.startNewSubmission.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.continueSubmission = function(submissionId) {
        return SismeDeveloperAjax.continueSubmission.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.deleteSubmission = function(submissionId) {
        return SismeDeveloperAjax.deleteSubmission.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewSubmission = function(submissionId) {
        return SismeDeveloperAjax.viewSubmission.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewPublishedGame = function(submissionId) {
        return SismeDeveloperAjax.viewPublishedGame.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewStats = function(submissionId) {
        return SismeDeveloperAjax.viewStats.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.viewRejectionNotes = function(submissionId) {
        return SismeDeveloperAjax.viewRejectionNotes.apply(SismeDeveloperAjax, arguments);
    };

    SismeDeveloper.retrySubmission = function(submissionId) {
        return SismeDeveloperAjax.retrySubmission.apply(SismeDeveloperAjax, arguments);
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
        
        this.log('Loader modal affiché:', message);
    };

    /**
     * Cacher le loader modal
     */
    SismeDeveloperAjax.hideLoader = function() {
        const loader = document.getElementById('sisme-loader-modal');
        if (loader) {
            loader.remove();
            this.log('Loader modal masqué');
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