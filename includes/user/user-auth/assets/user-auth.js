/**
 * File: /sisme-games-editor/includes/user/user-auth/assets/user-auth.js
 * JavaScript pour l'authentification utilisateur
 * 
 * FONCTIONNALITÉS:
 * - Validation en temps réel des formulaires
 * - Soumission AJAX (optionnelle)
 * - Messages d'erreur dynamiques
 * - Amélioration UX
 */

(function($) {
    'use strict';
    
    // Configuration par défaut
    const SismeUserAuth = {
        config: {
            debounceDelay: 300,
            showRealTimeValidation: true,
            enableAjaxSubmission: false, // Désactivé par défaut, utilise POST classique
            messages: {
                required: 'Ce champ est requis',
                email: 'Adresse email invalide',
                password: 'Minimum 8 caractères',
                passwordMatch: 'Les mots de passe ne correspondent pas',
                loading: 'Connexion en cours...',
                error: 'Une erreur est survenue'
            }
        },
        
        // État global
        state: {
            isSubmitting: false,
            validationTimeout: null
        },
        
        /**
         * Initialisation
         */
        init: function() {
            this.bindEvents();
            this.initExistingForms();
            this.setupGlobalConfig();
        },
        
        /**
         * Configuration globale depuis WordPress
         */
        setupGlobalConfig: function() {
            if (window.sismeUserAuth) {
                this.config = $.extend(true, this.config, window.sismeUserAuth.config || {});
                this.config.messages = $.extend(true, this.config.messages, window.sismeUserAuth.messages || {});
            }
        },
        
        /**
         * Liaison des événements
         */
        bindEvents: function() {
            $(document).on('submit', '.sisme-user-auth-form', this.handleFormSubmit.bind(this));
            $(document).on('input', '.sisme-auth-input', this.handleInputChange.bind(this));
            $(document).on('blur', '.sisme-auth-input', this.handleInputBlur.bind(this));
            $(document).on('focus', '.sisme-auth-input', this.handleInputFocus.bind(this));
            $(document).on('change', '.sisme-auth-checkbox', this.handleCheckboxChange.bind(this));
            
            // Gestion des messages automatiques
            $(document).on('click', '.sisme-auth-message', this.dismissMessage.bind(this));
        },
        
        /**
         * Initialisation des formulaires existants
         */
        initExistingForms: function() {
            $('.sisme-user-auth-form').each((index, form) => {
                this.setupForm($(form));
            });
        },
        
        /**
         * Configuration d'un formulaire
         */
        setupForm: function($form) {
            // Ajouter les attributs de validation HTML5
            $form.find('.sisme-auth-input').each((index, input) => {
                this.setupInput($(input));
            });
            
            // Message de validation personnalisé
            if (!$form.find('.sisme-validation-summary').length) {
                $form.find('.sisme-auth-form-fields').append(
                    '<div class="sisme-validation-summary" style="display: none;"></div>'
                );
            }
        },
        
        /**
         * Configuration d'un champ
         */
        setupInput: function($input) {
            const type = $input.attr('type') || 'text';
            const name = $input.attr('name') || '';
            
            // Configuration selon le type
            switch (type) {
                case 'email':
                    $input.attr('autocomplete', 'email');
                    break;
                case 'password':
                    if (name.includes('confirm')) {
                        $input.attr('autocomplete', 'new-password');
                    } else {
                        $input.attr('autocomplete', name.includes('new') ? 'new-password' : 'current-password');
                    }
                    break;
            }
            
            // Attributs d'accessibilité
            if ($input.attr('required')) {
                $input.attr('aria-required', 'true');
            }
        },
        
        /**
         * Gestion de la soumission du formulaire
         */
        handleFormSubmit: function(e) {
            const $form = $(e.target);
            const $submitBtn = $form.find('.sisme-auth-submit');
            
            // Validation finale
            if (!this.validateForm($form)) {
                e.preventDefault();
                this.showValidationSummary($form);
                return false;
            }
            
            // État de chargement
            this.setSubmittingState($submitBtn, true);
            
            // Si AJAX activé (optionnel)
            if (this.config.enableAjaxSubmission) {
                e.preventDefault();
                this.submitFormAjax($form);
                return false;
            }
            
            // Laisser la soumission POST classique se faire
            return true;
        },
        
        /**
         * Validation complète du formulaire
         */
        validateForm: function($form) {
            let isValid = true;
            const errors = [];
            
            // Valider chaque champ requis
            $form.find('.sisme-auth-input[required]').each((index, input) => {
                const $input = $(input);
                const validation = this.validateInput($input);
                
                if (!validation.isValid) {
                    errors.push(validation.message);
                    this.setInputState($input, 'error');
                    isValid = false;
                } else {
                    this.setInputState($input, 'valid');
                }
            });
            
            // Validation spéciale : confirmation mot de passe
            const $password = $form.find('input[name="user_password"]');
            const $confirmPassword = $form.find('input[name="user_confirm_password"]');
            
            if ($password.length && $confirmPassword.length) {
                if ($password.val() !== $confirmPassword.val()) {
                    errors.push(this.config.messages.passwordMatch);
                    this.setInputState($confirmPassword, 'error');
                    isValid = false;
                }
            }
            
            // Stocker les erreurs pour affichage
            $form.data('validation-errors', errors);
            
            return isValid;
        },
        
        /**
         * Validation d'un champ individuel
         */
        validateInput: function($input) {
            const type = $input.attr('type') || 'text';
            const value = $input.val().trim();
            const name = $input.attr('name') || '';
            const required = $input.prop('required');
            
            // Validation de base
            if (required && !value) {
                return { isValid: false, message: 'Ce champ est obligatoire' };
            }
            
            if (!value) {
                return { isValid: true, message: '' };
            }
            
            // Validation spécifique selon le type
            switch (type) {
                case 'email':
                    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    if (!emailValid) {
                        return { isValid: false, message: 'Adresse email invalide' };
                    }
                    break;
                    
                case 'password':
                    if (name === 'user_password' && value.length < 8) {
                        return { isValid: false, message: 'Le mot de passe doit contenir au moins 8 caractères' };
                    }
                    
                    if (name === 'user_confirm_password') {
                        const password = $('input[name="user_password"]').val();
                        if (value !== password) {
                            return { isValid: false, message: 'Les mots de passe ne correspondent pas' };
                        }
                    }
                    break;
                    
                case 'text':
                    if (name === 'display_name') {
                        // Validation locale d'abord
                        if (value.length < 3) {
                            return { isValid: false, message: 'Le pseudo doit contenir au moins 3 caractères' };
                        }
                        
                        if (value.length > 30) {
                            return { isValid: false, message: 'Le pseudo ne peut pas dépasser 30 caractères' };
                        }
                        
                        if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                            return { isValid: false, message: 'Seules les lettres, chiffres et underscores sont autorisés' };
                        }
                        
                        // Validation AJAX pour l'unicité (si configuré)
                        if ($input.data('validate-ajax')) {
                            this.validateDisplayNameAjax($input, value);
                            return { isValid: true, message: 'Vérification en cours...', pending: true };
                        }
                    }
                    break;
            }
            
            return { isValid: true, message: '' };
        },

        /**
         * Nouvelle méthode pour validation AJAX du display name
         */
        validateDisplayNameAjax: function($input, value) {
            const self = this;
            
            // Debounce pour éviter trop de requêtes
            clearTimeout(this.state.ajaxTimeout);
            this.state.ajaxTimeout = setTimeout(() => {
                
                // Indiquer que la validation est en cours
                self.setInputState($input, 'validating', 'Vérification de disponibilité...');
                
                $.ajax({
                    url: sisme_auth_config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sisme_validate_display_name',
                        display_name: value,
                        security: sisme_auth_config.register_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.setInputState($input, 'valid', '✓ Pseudo disponible');
                        } else {
                            let message = response.data.message;
                            
                            // Afficher les suggestions si disponibles
                            if (response.data.suggestions && response.data.suggestions.length > 0) {
                                message += ' Suggestions : ' + response.data.suggestions.join(', ');
                            }
                            
                            self.setInputState($input, 'error', message);
                            
                            // Optionnel : Ajouter des boutons de suggestion
                            self.showSuggestions($input, response.data.suggestions);
                        }
                    },
                    error: function() {
                        self.setInputState($input, 'neutral', 'Erreur de vérification');
                    }
                });
                
            }, 800); // Attendre 800ms après l'arrêt de frappe
        },

        /**
         * Afficher les suggestions de pseudos
         */
        showSuggestions: function($input, suggestions) {
            if (!suggestions || suggestions.length === 0) return;
            
            const $wrapper = $input.closest('.sisme-auth-field');
            let $suggestionsContainer = $wrapper.find('.sisme-suggestions');
            
            if ($suggestionsContainer.length === 0) {
                $suggestionsContainer = $('<div class="sisme-suggestions"></div>');
                $wrapper.append($suggestionsContainer);
            }
            
            $suggestionsContainer.empty();
            
            const $suggestionsList = $('<div class="sisme-suggestions-list"></div>');
            $suggestionsList.append('<span class="sisme-suggestions-label">Suggestions :</span>');
            
            suggestions.forEach(suggestion => {
                const $btn = $('<button type="button" class="sisme-suggestion-btn">' + suggestion + '</button>');
                $btn.on('click', () => {
                    $input.val(suggestion).trigger('blur');
                    $suggestionsContainer.hide();
                });
                $suggestionsList.append($btn);
            });
            
            $suggestionsContainer.append($suggestionsList).show();
        },
        
        /**
         * Gestion du changement de valeur
         */
        handleInputChange: function(e) {
            if (!this.config.showRealTimeValidation) return;
            
            const $input = $(e.target);
            
            // Debounce pour éviter trop de validations
            clearTimeout(this.state.validationTimeout);
            this.state.validationTimeout = setTimeout(() => {
                this.validateInputRealTime($input);
            }, this.config.debounceDelay);
        },
        
        /**
         * Gestion du blur (perte de focus)
         */
        handleInputBlur: function(e) {
            const $input = $(e.target);
            this.validateInputRealTime($input);
        },
        
        /**
         * Gestion du focus
         */
        handleInputFocus: function(e) {
            const $input = $(e.target);
            this.setInputState($input, 'focus');
        },
        
        /**
         * Validation en temps réel
         */
        validateInputRealTime: function($input) {
            const validation = this.validateInput($input);
            
            if (validation.isValid) {
                this.setInputState($input, 'valid');
            } else {
                this.setInputState($input, 'error', validation.message);
            }
        },
        
        /**
         * Définir l'état visuel d'un champ
         */
        setInputState: function($input, state, message = '') {
            // Supprimer les classes d'état précédentes
            $input.removeClass('sisme-auth-input--valid sisme-auth-input--error sisme-auth-input--focus sisme-auth-input--validating');
            
            // Ajouter la nouvelle classe
            if (state !== 'neutral') {
                $input.addClass(`sisme-auth-input--${state}`);
            }
            
            // Gestion du message d'erreur/succès
            this.handleInputMessage($input, message, state);
        },
        
        /**
         * Gestion des messages d'erreur par champ
         */
        handleInputMessage: function($input, message, state) {
            const $field = $input.closest('.sisme-auth-field');
            let $errorMsg = $field.find('.sisme-field-error');
            
            if (state === 'error' && message) {
                if (!$errorMsg.length) {
                    $errorMsg = $('<div class="sisme-field-error"></div>');
                    $field.append($errorMsg);
                }
                $errorMsg.text(message).fadeIn(200);
            } else {
                $errorMsg.fadeOut(200);
            }
        },
        
        /**
         * Afficher le résumé des erreurs de validation
         */
        showValidationSummary: function($form) {
            const errors = $form.data('validation-errors') || [];
            const $summary = $form.find('.sisme-validation-summary');
            
            if (errors.length > 0) {
                const errorList = errors.map(error => `<li>${error}</li>`).join('');
                $summary.html(`
                    <div class="sisme-auth-message sisme-auth-message--error">
                        <span class="sisme-message-icon">⚠️</span>
                        <div>
                            <strong>Veuillez corriger les erreurs suivantes :</strong>
                            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                ${errorList}
                            </ul>
                        </div>
                    </div>
                `).fadeIn(300);
                
                // Scroll vers les erreurs
                $summary[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                $summary.fadeOut(200);
            }
        },
        
        /**
         * Soumission AJAX (optionnelle)
         */
        submitFormAjax: function($form) {
            const formData = new FormData($form[0]);
            const action = $form.hasClass('sisme-user-auth-form--register') ? 'sisme_user_register' : 'sisme_user_login';
            
            // Ajouter les données AJAX
            formData.append('action', action);
            formData.append('nonce', window.sismeUserAuth?.nonce || '');
            
            $.ajax({
                url: window.sismeUserAuth?.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.handleAjaxSuccess($form, response);
                },
                error: (xhr, status, error) => {
                    this.handleAjaxError($form, error);
                },
                complete: () => {
                    this.setSubmittingState($form.find('.sisme-auth-submit'), false);
                }
            });
        },
        
        /**
         * Gestion du succès AJAX
         */
        handleAjaxSuccess: function($form, response) {
            if (response.success) {
                // Message de succès
                this.showMessage($form, response.data.message, 'success');
                
                // Redirection après délai
                if (response.data.redirect_to) {
                    setTimeout(() => {
                        window.location.href = response.data.redirect_to;
                    }, window.sismeUserAuth?.config?.redirect_delay || 2000);
                }
            } else {
                this.showMessage($form, response.data || this.config.messages.error, 'error');
            }
        },
        
        /**
         * Gestion des erreurs AJAX
         */
        handleAjaxError: function($form, error) {
            this.showMessage($form, this.config.messages.error, 'error');
        },
        
        /**
         * Afficher un message global
         */
        showMessage: function($form, message, type = 'info') {
            const $wrapper = $form.closest('.sisme-user-auth-form-wrapper');
            let $messageContainer = $wrapper.find('.sisme-auth-message');
            
            // Créer le container si nécessaire
            if (!$messageContainer.length) {
                $messageContainer = $('<div class="sisme-auth-message"></div>');
                $wrapper.prepend($messageContainer);
            }
            
            // Icônes par type
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            // Afficher le message
            $messageContainer
                .removeClass('sisme-auth-message--success sisme-auth-message--error sisme-auth-message--warning sisme-auth-message--info')
                .addClass(`sisme-auth-message--${type}`)
                .html(`
                    <span class="sisme-message-icon">${icons[type] || icons.info}</span>
                    <div>${message}</div>
                `)
                .fadeIn(300);
            
            // Auto-masquage pour les succès
            if (type === 'success' && window.sismeUserAuth?.config?.auto_hide_messages) {
                setTimeout(() => {
                    $messageContainer.fadeOut(300);
                }, window.sismeUserAuth.config.auto_hide_messages);
            }
        },
        
        /**
         * Masquer un message
         */
        dismissMessage: function(e) {
            $(e.currentTarget).fadeOut(300);
        },
        
        /**
         * Définir l'état de soumission
         */
        setSubmittingState: function($submitBtn, isSubmitting) {
            if (isSubmitting) {
                this.state.isSubmitting = true;
                const originalText = $submitBtn.html();
                $submitBtn
                    .data('original-text', originalText)
                    .prop('disabled', true)
                    .html(`
                        <span class="sisme-btn-icon">⏳</span>
                        ${this.config.messages.loading}
                    `);
            } else {
                this.state.isSubmitting = false;
                const originalText = $submitBtn.data('original-text');
                if (originalText) {
                    $submitBtn
                        .prop('disabled', false)
                        .html(originalText);
                }
            }
        },
        
        /**
         * Gestion des checkboxes
         */
        handleCheckboxChange: function(e) {
            const $checkbox = $(e.target);
            const $wrapper = $checkbox.closest('.sisme-auth-checkbox-wrapper');
            
            // Animation visuelle
            if ($checkbox.is(':checked')) {
                $wrapper.addClass('sisme-checkbox--checked');
            } else {
                $wrapper.removeClass('sisme-checkbox--checked');
            }
        },
        
        /**
         * Validation email
         */
        isValidEmail: function(email) {
            // Validation de base
            const basicPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!basicPattern.test(email)) {
                return false;
            }
            
            // Vérifier les caractères interdits
            const forbiddenChars = ['+', '\'', '"', '\\', '/', '<', '>', '(', ')', '[', ']', 
                                ',', ';', ':', '|', '?', '*', '=', '&', '%', '#', 
                                '!', '$', '^', '~', '`', '{', '}'];
            
            for (let char of forbiddenChars) {
                if (email.includes(char)) {
                    return false;
                }
            }
            
            // Vérifier longueur
            if (email.length > 254 || email.length < 5) {
                return false;
            }
            
            // Parties local et domaine
            const parts = email.split('@');
            if (parts.length !== 2) {
                return false;
            }
            
            const [local, domain] = parts;
            
            // Validation partie locale
            if (local.length > 64 || local.length < 1) {
                return false;
            }
            
            if (local.startsWith('.') || local.endsWith('.') || local.includes('..')) {
                return false;
            }
            
            // Validation domaine
            if (domain.length > 253 || domain.length < 4) {
                return false;
            }
            
            if (!domain.includes('.') || domain.startsWith('.') || domain.endsWith('.')) {
                return false;
            }
            
            return true;
        },
        
        /**
         * Amélioration de l'accessibilité
         */
        enhanceAccessibility: function() {
            // Amélioration des labels
            $('.sisme-auth-input').each(function() {
                const $input = $(this);
                const $label = $input.closest('.sisme-auth-field').find('.sisme-auth-label');
                
                if ($label.length && !$input.attr('aria-labelledby')) {
                    const labelId = 'label-' + Math.random().toString(36).substr(2, 9);
                    $label.attr('id', labelId);
                    $input.attr('aria-labelledby', labelId);
                }
            });
            
            // Amélioration des messages d'erreur
            $('.sisme-field-error').attr('role', 'alert');
        },
        
        /**
         * Gestion du mode sombre/clair (si applicable)
         */
        handleThemeChange: function() {
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                mediaQuery.addListener((e) => {
                    document.body.classList.toggle('sisme-theme-auto-dark', e.matches);
                });
            }
        },
        
        /**
         * Nettoyage et destruction
         */
        destroy: function() {
            $(document).off('.sismeUserAuth');
            clearTimeout(this.state.validationTimeout);
            this.state = { isSubmitting: false, validationTimeout: null };
        }
    };
    
    /**
     * Extension jQuery pour faciliter l'utilisation
     */
    $.fn.sismeUserAuth = function(options) {
        return this.each(function() {
            const $form = $(this);
            if ($form.hasClass('sisme-user-auth-form')) {
                SismeUserAuth.setupForm($form);
            }
        });
    };
    
    /**
     * Initialisation automatique au chargement du DOM
     */
    $(document).ready(function() {
        SismeUserAuth.init();
        SismeUserAuth.enhanceAccessibility();
        SismeUserAuth.handleThemeChange();
        
        // Gestion des formulaires chargés dynamiquement
        $(document).on('DOMNodeInserted', function(e) {
            const $target = $(e.target);
            if ($target.hasClass('sisme-user-auth-form') || $target.find('.sisme-user-auth-form').length) {
                setTimeout(() => {
                    $target.find('.sisme-user-auth-form').sismeUserAuth();
                }, 100);
            }
        });
    });
    
    /**
     * API publique
     */
    window.SismeUserAuth = SismeUserAuth;
    
})(jQuery);

/**
 * Styles CSS additionnels injectés via JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    const additionalStyles = `
        <style id="sisme-user-auth-dynamic-styles">
        .sisme-field-error {
            color: var(--sisme-auth-error, #ef4444);
            font-size: 0.85rem;
            margin-top: 4px;
            display: none;
            animation: fadeInError 0.3s ease;
        }
        
        @keyframes fadeInError {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sisme-checkbox--checked .sisme-auth-checkbox-label {
            color: var(--sisme-color-primary, #a1b78d);
        }
        
        .sisme-auth-input--focus {
            transform: scale(1.02);
        }
        
        @media (prefers-reduced-motion: reduce) {
            .sisme-field-error,
            .sisme-auth-input--focus {
                animation: none;
                transform: none;
            }
        }
        </style>
    `;
    
    if (!document.getElementById('sisme-user-auth-dynamic-styles')) {
        document.head.insertAdjacentHTML('beforeend', additionalStyles);
    }
});