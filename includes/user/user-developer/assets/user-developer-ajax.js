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
    window.SismeDeveloperAjax = {
        config: {
            formSelector: '#sisme-developer-form',
            feedbackSelector: '#sisme-form-feedback',
            submitButtonSelector: '#sisme-developer-submit',
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
        
        this.log('Événements AJAX développeur liés');
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
    
})(jQuery);