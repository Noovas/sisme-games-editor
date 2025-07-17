/**
 * File: /sisme-games-editor/includes/user/user-dashboard/assets/user-dashboard.js
 * JavaScript pour le dashboard utilisateur avec système d'onglets
 * 
 * RESPONSABILITÉ:
 * - Navigation dynamique entre sections du dashboard
 * - Gestion de l'affichage/masquage des sections
 * - Deep linking avec hash URL
 * - Système de notifications toast
 * - Support mobile responsive
 * - Animations et transitions fluides
 */

(function($) {
    'use strict';
    
    // Namespace principal
    window.SismeDashboard = {
        config: window.sismeUserDashboard || {},
        currentSection: 'overview',
        isInitialized: false,
        validSections: ['overview', 'favorites', 'library', 'activity', 'settings', 'social', 'developer', 'submit-game']
    };
    
    /**
     * Initialisation du dashboard
     */
    SismeDashboard.init = function() {
        if (this.isInitialized) {
            this.currentSection = 'overview';
            this.initNavigation();
            return;
        }
        
        this.currentSection = 'overview';
        
        this.bindEvents();
        this.initNavigation();
        this.initMobileSupport();
        this.initAnimations();
        this.social.init();
        
        this.isInitialized = true;
        this.log('Dashboard JavaScript initialisé');
        
        setTimeout(() => {
            this.showNotification('Bienvenue sur votre dashboard !🎮', 'success', 3000);
        }, 1000);
    };
    
    /**
     * Liaison des événements
     */
    SismeDashboard.bindEvents = function() {
        $(document).on('click', '.sisme-nav-link', this.handleNavigation.bind(this));
        $(document).on('click', '.sisme-view-all', this.handleViewAll.bind(this));
        $(document).on('click', '.sisme-quick-btn', this.handleQuickAction.bind(this));
        $(document).on('click', '.sisme-game-card', this.handleGameClick.bind(this));
        $(document).on('click', '.sisme-favorite-item', this.handleFavoriteClick.bind(this));
        $(document).on('click', '.sisme-notification-close', this.closeNotification.bind(this));
        $(window).on('hashchange', this.handleHashChange.bind(this));
        
        this.log('Événements liés');
    };
    
    /**
     * Initialisation de la navigation par onglets
     */
    SismeDashboard.initNavigation = function() {
        const isOwnDashboard = this.isOwnDashboard();
        
        const urlHash = window.location.hash.replace('#', '');
        const savedSection = localStorage.getItem('sisme_dashboard_section');
        
        let initialSection = 'overview';
        
        if (urlHash && this.isValidSection(urlHash)) {
            initialSection = urlHash;
        } 
        else if (isOwnDashboard && savedSection && this.isValidSection(savedSection)) {
            initialSection = savedSection;
        }
        
        this.setActiveSection(initialSection, false);
        
        this.log('Navigation initialisée, section active:', initialSection, 'dashboard personnel:', isOwnDashboard);
    };

    /**
     * Détecter si on est sur son propre dashboard
     */
    SismeDashboard.isOwnDashboard = function() {
        const currentUrl = window.location.href;
        if (currentUrl.includes('/sisme-user-tableau-de-bord/')) {
            return true;
        }
        
        if (currentUrl.includes('/sisme-user-profil/') && currentUrl.includes('?user=')) {
            return false;
        }
        
        const $dashboard = $('.sisme-user-dashboard');
        if ($dashboard.length) {
            const dashboardUserId = $dashboard.data('user-id');
            const currentUserId = this.config.currentUserId;
            
            if (dashboardUserId && currentUserId && dashboardUserId == currentUserId) {
                const urlParams = new URLSearchParams(window.location.search);
                return !urlParams.has('user');
            }
        }
        
        return true;
    };
    
    /**
     * Vérifier si une section est valide
     */
    SismeDashboard.isValidSection = function(section) {
        return this.validSections.includes(section);
    };
    
    /**
     * Définir la section active
     */
    SismeDashboard.setActiveSection = function(section, animate = true) {
        if (!this.isValidSection(section)) {
            this.log('Section invalide:', section);
            return;
        }
        
        const previousSection = this.currentSection;
        this.currentSection = section;
        
        $('.sisme-dashboard-section').hide();
        $('.sisme-nav-link').removeClass('active');
        
        const $targetSection = $(`.sisme-dashboard-section[data-section="${section}"]`);
        if ($targetSection.length) {
            if (animate) {
                setTimeout(() => {
                    $targetSection.fadeIn(300);
                }, animate ? 200 : 0);
            } else {
                $targetSection.show();
            }
        }
        
        if (window.location.hash !== '#' + section) {
            history.replaceState(null, null, '#' + section);
        }
        
        if (this.isOwnDashboard()) {
            localStorage.setItem('sisme_dashboard_section', section);
            this.log('Section sauvée dans localStorage:', section);
        } else {
            this.log('Profil public - pas de sauvegarde localStorage');
        }
        
        $(document).trigger('sisme:dashboard:section-changed', [section, previousSection]);
        
        this.log('Section active:', section, 'précédente:', previousSection);
    };
    
    /**
     * Gestion de la navigation
     */
    SismeDashboard.handleNavigation = function(e) {
        e.preventDefault();
        
        const targetSection = $(e.currentTarget).data('section');
        
        if (targetSection && this.isValidSection(targetSection)) {
            this.setActiveSection(targetSection, true);
        }
    };
    
    /**
     * Gestion du changement d'hash dans l'URL
     */
    SismeDashboard.handleHashChange = function() {
        const newSection = window.location.hash.replace('#', '') || 'overview';
        
        if (this.isValidSection(newSection) && newSection !== this.currentSection) {
            this.setActiveSection(newSection, true);
        }
    };
    
    /**
     * Navigation programmatique vers une section
     */
    SismeDashboard.navigateToSection = function(section) {
        if (this.isValidSection(section)) {
            this.setActiveSection(section, true);
        } else {
            this.log('Tentative de navigation vers une section invalide:', section);
        }
    };
    
    /**
     * Gestion des liens "Voir tous"
     */
    SismeDashboard.handleViewAll = function(e) {
        e.preventDefault();
        
        const targetSection = $(e.currentTarget).data('target');
        
        if (targetSection && this.isValidSection(targetSection)) {
            this.setActiveSection(targetSection, true);
        }
    };
    
    /**
     * Gestion des actions rapides du header
     */
    SismeDashboard.handleQuickAction = function(e) {
        e.preventDefault();
        
        const action = $(e.currentTarget).data('action');
        
        switch(action) {
            case 'edit-profile':
                this.setActiveSection('settings', true);
                break;
            case 'add-friend':
                this.setActiveSection('social', true);
                break;
            case 'view-notifications':
                this.showNotification('Notifications chargées', 'info');
                break;
            default:
                this.log('Action rapide non gérée:', action);
        }
    };
    
    /**
     * Gestion des clics sur les cartes de jeux
     */
    SismeDashboard.handleGameClick = function(e) {
        e.preventDefault();
        
        const gameId = $(e.currentTarget).data('game-id');
        
        if (gameId) {
            this.log('Clic sur le jeu:', gameId);
            this.showNotification('Redirection vers le jeu...', 'info');
        }
    };
    
    /**
     * Gestion des clics sur les favoris
     */
    SismeDashboard.handleFavoriteClick = function(e) {
        e.preventDefault();
        
        const gameId = $(e.currentTarget).data('game-id');
        
        if (gameId) {
            this.log('Clic sur le favori:', gameId);
            this.showNotification('Favori sélectionné', 'info');
        }
    };
    
    /**
     * Afficher une notification toast
     */
    SismeDashboard.showNotification = function(message, type = 'info', duration = 5000) {
        const $notification = $(`
            <div class="sisme-notification sisme-notification-${type}">
                <span class="sisme-notification-text">${message}</span>
                <button class="sisme-notification-close">✖</button>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, duration);
        
        this.log('Notification affichée:', message, type);
    };
    
    /**
     * Fermer une notification
     */
    SismeDashboard.closeNotification = function(e) {
        e.preventDefault();
        
        const $notification = $(e.currentTarget).closest('.sisme-notification');
        $notification.removeClass('show');
        
        setTimeout(() => {
            $notification.remove();
        }, 300);
    };
    
    /**
     * Support mobile
     */
    SismeDashboard.initMobileSupport = function() {
        if ($(window).width() <= 768) {
            $('.sisme-dashboard-nav').addClass('mobile-nav');
            this.log('Mode mobile activé');
        }
        
        $(window).on('resize', () => {
            if ($(window).width() <= 768) {
                $('.sisme-dashboard-nav').addClass('mobile-nav');
            } else {
                $('.sisme-dashboard-nav').removeClass('mobile-nav');
            }
        });
    };
    
    /**
     * Animations et transitions
     */
    SismeDashboard.initAnimations = function() {
        $('.sisme-dashboard-section').hide();
        $('.sisme-dashboard-section[data-section="overview"]').show();
        
        this.log('Animations initialisées');
    };
    
    /**
     * Système social intégré
     */
    SismeDashboard.social = {
        init: function() {
            this.bindSocialEvents();
        },
        
        bindSocialEvents: function() {
            $(document).on('click', '.sisme-social-tab', this.handleSocialTabClick.bind(this));
            $(document).on('click', '.sisme-friend-request-btn', this.handleFriendRequest.bind(this));
            $(document).on('click', '.sisme-friend-action-btn', this.handleFriendAction.bind(this));
        },
        
        handleSocialTabClick: function(e) {
            e.preventDefault();
            
            const targetTab = $(e.currentTarget).data('tab');
            
            $('.sisme-social-tab').removeClass('active');
            $(e.currentTarget).addClass('active');
            
            $('.sisme-social-content').hide();
            $(`.sisme-social-content[data-tab="${targetTab}"]`).show();
        },
        
        handleFriendRequest: function(e) {
            e.preventDefault();
            
            const userId = $(e.currentTarget).data('user-id');
            SismeDashboard.showNotification('Demande d\'ami envoyée', 'success');
        },
        
        handleFriendAction: function(e) {
            e.preventDefault();
            
            const action = $(e.currentTarget).data('action');
            const userId = $(e.currentTarget).data('user-id');
            
            switch(action) {
                case 'accept':
                    SismeDashboard.showNotification('Demande acceptée', 'success');
                    break;
                case 'decline':
                    SismeDashboard.showNotification('Demande refusée', 'info');
                    break;
                case 'remove':
                    SismeDashboard.showNotification('Ami supprimé', 'info');
                    break;
            }
        }
    };
    
    /**
     * Logging conditionnel
     */
    SismeDashboard.log = function(...args) {
        if (this.config.debug) {
            console.log('[SismeDashboard]', ...args);
        }
    };
    
    /**
     * Auto-initialisation
     */
    $(document).ready(function() {
        if ($('.sisme-user-dashboard').length) {
            SismeDashboard.init();
        }
    });
    
})(jQuery);