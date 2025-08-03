/**
 * File: /sisme-games-editor/includes/user/user-developer/assets/user-developer.js
 * JavaScript pour la section développeur du dashboard
 * 
 * RESPONSABILITÉ:
 * - Validation du formulaire de candidature
 * - Soumission AJAX du formulaire
 * - Feedback utilisateur temps réel
 * - Intégration avec le système dashboard existant
 */

(function($) {
    'use strict';
    
    // Namespace pour le module développeur
    window.SismeDeveloper = {
        config: {
            formSelector: '#sisme-developer-form',
            feedbackSelector: '#sisme-form-feedback'
        },
        isInitialized: false
    };
    
    /**
     * Initialisation du module développeur
     */
    SismeDeveloper.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        this.bindEvents();
        this.isInitialized = true;
    };
    
    /**
     * Événements et interactions
     */
    SismeDeveloper.bindEvents = function() {
        $(document).on('submit', this.config.formSelector, this.handleFormSubmit.bind(this));
        $(document).on('blur', this.config.formSelector + ' input, ' + this.config.formSelector + ' textarea', this.validateField.bind(this));
    };
    
    /**
     * Gérer la soumission du formulaire
     */
    SismeDeveloper.handleFormSubmit = function(e) {
        e.preventDefault();
        
        const $form = $(e.target);
        const formData = this.collectFormData($form);
        
        // Validation complète
        if (!this.validateForm(formData)) {
            this.showFeedback('Veuillez corriger les erreurs avant de soumettre', 'error');
            return;
        }
        
        // Soumission AJAX (à implémenter)
        this.submitApplication(formData);
    };
    
    /**
     * Collecter les données du formulaire
     */
    SismeDeveloper.collectFormData = function($form) {
        const formData = {};
        
        $form.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const value = $field.val();
            
            if (name && value) {
                formData[name] = value;
            }
        });
        
        return formData;
    };
    
    /**
     * Valider un champ individuel
     */
    SismeDeveloper.validateField = function(e) {
        const $field = $(e.target);
        const name = $field.attr('name');
        const value = $field.val();
        
        let isValid = true;
        let errorMessage = '';
        
        // Validation selon le type de champ
        if ($field.prop('required') && !value.trim()) {
            isValid = false;
            errorMessage = 'Ce champ est requis';
        } else if (name === 'studio_website' && value && !this.isValidUrl(value)) {
            isValid = false;
            errorMessage = 'URL non valide';
        } else if (name === 'representative_email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Email non valide';
        } else if (name === 'representative_birthdate' && value && !this.isValidAge(value)) {
            isValid = false;
            errorMessage = 'Vous devez être majeur';
        }
        
        this.showFieldError(name, isValid ? '' : errorMessage);
        return isValid;
    };
    
    /**
     * Valider le formulaire complet
     */
    SismeDeveloper.validateForm = function(formData) {
        let isValid = true;
        
        // Champs requis
        const requiredFields = [
            'studio_name', 'studio_description', 'representative_firstname', 
            'representative_lastname', 'representative_birthdate', 'representative_address',
            'representative_city', 'representative_country', 'representative_email', 
            'representative_phone'
        ];
        
        requiredFields.forEach(field => {
            if (!formData[field] || !formData[field].trim()) {
                this.showFieldError(field, 'Ce champ est requis');
                isValid = false;
            }
        });
        
        return isValid;
    };
    
    /**
     * Soumettre la candidature
     */
    SismeDeveloper.submitApplication = function(formData) {
        this.showFeedback('Envoi en cours...', 'loading');
        setTimeout(() => {
            this.showFeedback('Candidature envoyée avec succès ! Vous recevrez une réponse dans les 48h.', 'success');
        }, 2000);
    };
    
    /**
     * Afficher une erreur de champ
     */
    SismeDeveloper.showFieldError = function(fieldName, message) {
        const $errorElement = $('#error-' + fieldName);
        
        if (message) {
            $errorElement.text(message).addClass('show');
        } else {
            $errorElement.text('').removeClass('show');
        }
    };
    
    /**
     * Afficher un feedback général
     */
    SismeDeveloper.showFeedback = function(message, type) {
        const $feedback = $(this.config.feedbackSelector);
        
        $feedback
            .removeClass('success error loading')
            .addClass(type)
            .text(message)
            .show();
    };
    
    /**
     * Utilitaires de validation
     */
    SismeDeveloper.isValidUrl = function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    };
    
    SismeDeveloper.isValidEmail = function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };
    
    SismeDeveloper.isValidAge = function(birthdate) {
        const today = new Date();
        const birth = new Date(birthdate);
        const age = today.getFullYear() - birth.getFullYear();
        
        return age >= 18;
    };
    
    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        // Initialiser le module développeur si on est sur le dashboard
        if ($('.sisme-user-dashboard').length > 0) {
            SismeDeveloper.init();
        }
    });
    
    /**
     * Initialisation lors du changement de section dashboard
     */
    $(document).on('sisme:dashboard:section-changed', function(e, section) {
        if (section === 'developer') {
            SismeDeveloper.init();
        }
    });
    
})(jQuery);