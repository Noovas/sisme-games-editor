/**
 * File: /sisme-games-editor/includes/user/user-developer/assets/user-developer.js
 * JavaScript pour la section développeur du dashboard
 * 
 * RESPONSABILITÉ:
 * - Gestion du formulaire de candidature
 * - Interactions avec les boutons et modals
 * - Animations et transitions
 * - Intégration avec le système dashboard existant
 */

(function($) {
    'use strict';
    
    // Namespace pour le module développeur
    window.SismeDeveloper = {
        config: {
            formSelector: '#sisme-developer-application-form',
            containerSelector: '.sisme-developer-form-container'
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
        
        this.log('Module développeur initialisé');
    };
    
    /**
     * Événements et interactions
     */
    SismeDeveloper.bindEvents = function() {
        // Fermer le formulaire en cliquant sur l'overlay
        $(document).on('click', this.config.containerSelector, function(e) {
            if (e.target === this) {
                SismeDeveloper.hideApplicationForm();
            }
        });
        
        // Fermer avec la touche Escape
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $(SismeDeveloper.config.formSelector).is(':visible')) {
                SismeDeveloper.hideApplicationForm();
            }
        });
        
        this.log('Événements développeur liés');
    };
    
    /**
     * Afficher le formulaire de candidature
     */
    SismeDeveloper.showApplicationForm = function() {
        const $form = $(this.config.formSelector);
        
        if ($form.length === 0) {
            this.log('Formulaire de candidature non trouvé');
            return;
        }
        
        // Désactiver le scroll du body
        $('body').css('overflow', 'hidden');
        
        // Afficher le formulaire avec animation
        $form.fadeIn(300, function() {
            // Focus sur le premier champ (quand il sera ajouté)
            $form.find('input, textarea').first().focus();
        });
        
        this.log('Formulaire de candidature affiché');
    };
    
    /**
     * Masquer le formulaire de candidature
     */
    SismeDeveloper.hideApplicationForm = function() {
        const $form = $(this.config.formSelector);
        
        if ($form.length === 0) {
            return;
        }
        
        // Réactiver le scroll du body
        $('body').css('overflow', '');
        
        // Masquer le formulaire avec animation
        $form.fadeOut(300);
        
        this.log('Formulaire de candidature masqué');
    };
    
    /**
     * Afficher les détails de candidature (pour statut pending)
     */
    SismeDeveloper.showApplicationDetails = function() {
        // Pour l'instant, juste un log
        // Sera implémenté dans les prochaines étapes
        this.log('Affichage des détails de candidature');
        
        // Placeholder : afficher une modal simple
        alert('Détails de candidature - À implémenter dans la prochaine étape');
    };
    
    /**
     * Soumettre le formulaire de candidature
     */
    SismeDeveloper.submitApplication = function(formData) {
        this.log('Soumission candidature', formData);
        
        // Sera implémenté avec AJAX dans les prochaines étapes
        return false;
    };
    
    /**
     * Gérer les erreurs
     */
    SismeDeveloper.handleError = function(error) {
        this.log('Erreur développeur:', error);
        
        // Afficher une notification d'erreur
        if (typeof SismeDashboard !== 'undefined' && SismeDashboard.showNotification) {
            SismeDashboard.showNotification('Une erreur est survenue', 'error');
        }
    };
    
    /**
     * Gérer le succès
     */
    SismeDeveloper.handleSuccess = function(message) {
        this.log('Succès développeur:', message);
        
        // Afficher une notification de succès
        if (typeof SismeDashboard !== 'undefined' && SismeDashboard.showNotification) {
            SismeDashboard.showNotification(message, 'success');
        }
    };
    
    /**
     * Logging utilitaire
     */
    SismeDeveloper.log = function(message, data) {
        if (typeof console !== 'undefined' && console.log) {
            if (data) {
                console.log('[Sisme Developer]', message, data);
            } else {
                console.log('[Sisme Developer]', message);
            }
        }
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