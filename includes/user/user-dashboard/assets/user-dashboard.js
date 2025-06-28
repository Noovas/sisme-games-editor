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
        validSections: ['overview', 'favorites', 'library', 'activity', 'settings']
    };
    
    /**
     * Initialisation du dashboard
     */
    SismeDashboard.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        this.bindEvents();
        this.initNavigation();
        this.initMobileSupport();
        this.initAnimations();
        
        this.isInitialized = true;
        this.log('Dashboard JavaScript initialisé');
        
        // Message de bienvenue après un délai
        setTimeout(() => {
            this.showNotification('Bienvenue sur votre dashboard ! 🎮', 'success', 3000);
        }, 1000);
    };
    
    /**
     * Liaison des événements
     */
    SismeDashboard.bindEvents = function() {
        // Navigation principale entre sections
        $(document).on('click', '.sisme-nav-link', this.handleNavigation.bind(this));
        
        // Liens "Voir tous" dans les widgets
        $(document).on('click', '.sisme-view-all', this.handleViewAll.bind(this));
        
        // Actions rapides du header
        $(document).on('click', '.sisme-quick-btn', this.handleQuickAction.bind(this));
        
        // Actions sur les jeux
        $(document).on('click', '.sisme-game-card', this.handleGameClick.bind(this));
        $(document).on('click', '.sisme-favorite-item', this.handleFavoriteClick.bind(this));
        
        // Fermeture notifications
        $(document).on('click', '.sisme-notification-close', this.closeNotification.bind(this));
        
        // Gestion du changement d'hash dans l'URL
        $(window).on('hashchange', this.handleHashChange.bind(this));
        
        this.log('Événements liés');
    };
    
    /**
     * Initialisation de la navigation par onglets
     */
    SismeDashboard.initNavigation = function() {
        // Récupérer la section active depuis l'URL ou localStorage
        const urlHash = window.location.hash.replace('#', '');
        const savedSection = localStorage.getItem('sisme_dashboard_section');
        
        let initialSection = 'overview';
        
        if (urlHash && this.isValidSection(urlHash)) {
            initialSection = urlHash;
        } else if (savedSection && this.isValidSection(savedSection)) {
            initialSection = savedSection;
        }
        
        // Appliquer la section active
        this.setActiveSection(initialSection, false);
        
        this.log('Navigation initialisée, section active:', initialSection);
    };
    
    /**
     * Support mobile
     */
    SismeDashboard.initMobileSupport = function() {
        // Toggle sidebar sur mobile
        this.createMobileToggle();
        
        // Gérer le redimensionnement
        $(window).on('resize', this.handleResize.bind(this));
        
        // Fermer sidebar au clic sur overlay (mobile)
        $(document).on('click', '.sisme-mobile-overlay', this.closeMobileSidebar.bind(this));
        
        // Fermer sidebar après navigation sur mobile
        $(document).on('sisme:dashboard:section-changed', (e, section) => {
            if (this.utils.isMobile()) {
                this.closeMobileSidebar();
            }
        });
        
        this.log('Support mobile initialisé');
    };
    
    /**
     * Initialisation des animations
     */
    SismeDashboard.initAnimations = function() {
        // Animation d'apparition du dashboard
        $('.sisme-user-dashboard').addClass('dashboard-loaded');
        
        // Animations au scroll (si nécessaire)
        this.initScrollAnimations();
        
        this.log('Animations initialisées');
    };
    
    /**
     * ✨ FONCTION PRINCIPALE - Gestion de la navigation entre sections
     */
    SismeDashboard.handleNavigation = function(e) {
        e.preventDefault();
        
        const $link = $(e.currentTarget);
        const section = $link.data('section') || $link.attr('href').replace('#', '');
        
        if (this.isValidSection(section)) {
            this.setActiveSection(section, true);
        }
        
        this.log('Navigation vers:', section);
    };
    
    /**
     * ✨ FONCTION PRINCIPALE - Définir la section active avec animation
     */
    SismeDashboard.setActiveSection = function(section, animate = true) {
        if (!this.isValidSection(section)) {
            section = 'overview';
        }
        
        const previousSection = this.currentSection;
        this.currentSection = section;
        
        // 1. Mettre à jour les liens de navigation
        $('.sisme-nav-link').removeClass('active');
        $(`.sisme-nav-link[data-section="${section}"]`).addClass('active');
        
        // 2. Masquer toutes les sections avec animation
        $('.sisme-dashboard-section').each(function() {
            const $section = $(this);
            if ($section.data('section') !== section && $section.is(':visible')) {
                if (animate) {
                    $section.fadeOut(200);
                } else {
                    $section.hide();
                }
            }
        });
        
        // 3. Afficher la section cible avec animation
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
        
        // 4. Mettre à jour l'URL sans déclencher hashchange
        if (window.location.hash !== '#' + section) {
            history.replaceState(null, null, '#' + section);
        }
        
        // 5. Sauvegarder dans localStorage
        localStorage.setItem('sisme_dashboard_section', section);
        
        // 6. Émettre un événement personnalisé
        $(document).trigger('sisme:dashboard:section-changed', [section, previousSection]);
        
        // 7. Notification de changement de section
        if (animate && section !== previousSection) {
            const sectionNames = {
                'overview': 'Vue d\'ensemble',
                'favorites': 'Mes Favoris',
                'library': 'La Sismothèque',
                'activity': 'Mon Activité',
                'settings': 'Paramètres'
            };
            
            this.showNotification(`Section ${sectionNames[section] || section}`, 'info', 2000);
        }
        
        this.log('Section active:', section);
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
     * Gestion des liens "Voir tous" dans les widgets
     */
    SismeDashboard.handleViewAll = function(e) {
        e.preventDefault();
        
        const $link = $(e.currentTarget);
        const section = $link.data('section');
        
        if (section && this.isValidSection(section)) {
            this.setActiveSection(section, true);
        }
    };
    
    /**
     * Actions rapides du header
     */
    SismeDashboard.handleQuickAction = function(e) {
        e.preventDefault();
        
        const $btn = $(e.currentTarget);
        const section = $btn.data('section');
        const label = $btn.find('.sisme-label').text();
        
        // Animation du bouton
        $btn.addClass('clicked');
        setTimeout(() => $btn.removeClass('clicked'), 200);
        
        if (section && this.isValidSection(section)) {
            this.setActiveSection(section, true);
        }
        
        this.showNotification(`Accès à ${label}`, 'info', 2000);
        this.log('Action rapide:', label);
    };
    
    /**
     * Clic sur une carte de jeu
     */
    SismeDashboard.handleGameClick = function(e) {
        const $card = $(e.currentTarget);
        const $link = $card.find('a').first();
        const gameName = $card.find('.sisme-game-title, .sisme-game-name').text() || 'ce jeu';
        
        // Animation de clic
        $card.addClass('game-clicked');
        setTimeout(() => $card.removeClass('game-clicked'), 300);
        
        if ($link.length) {
            this.showNotification(`Ouverture de la fiche : ${gameName}`, 'info', 2000);
            // Laisser le navigateur suivre le lien naturellement
        }
        
        this.log('Clic sur jeu:', gameName);
    };
    
    /**
     * Clic sur un favori
     */
    SismeDashboard.handleFavoriteClick = function(e) {
        const $item = $(e.currentTarget);
        const gameName = $item.find('.sisme-favorite-name').text() || 'ce jeu';
        
        // Animation
        $item.addClass('favorite-clicked');
        setTimeout(() => $item.removeClass('favorite-clicked'), 300);
        
        this.showNotification(`Accès au favori : ${gameName}`, 'info', 2000);
        this.log('Clic sur favori:', gameName);
    };
    
    /**
     * Vérifier si une section est valide
     */
    SismeDashboard.isValidSection = function(section) {
        return this.validSections.includes(section);
    };
    
    /**
     * ✨ Système de notifications toast
     */
    SismeDashboard.showNotification = function(message, type = 'info', duration = 3000) {
        const icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        
        const icon = icons[type] || icons.info;
        const $notification = $(`
            <div class="sisme-notification sisme-notification--${type}">
                <span class="sisme-notification-icon">${icon}</span>
                <span class="sisme-notification-message">${message}</span>
                <button class="sisme-notification-close">×</button>
            </div>
        `);
        
        // Container des notifications
        let $container = $('.sisme-notifications-container');
        if (!$container.length) {
            $container = $('<div class="sisme-notifications-container"></div>');
            $('body').append($container);
        }
        
        // Ajouter et animer
        $container.append($notification);
        $notification.slideDown(300);
        
        // Auto-fermeture
        setTimeout(() => {
            this.closeNotification($notification);
        }, duration);
        
        this.log('Notification:', message, type);
    };
    
    /**
     * Fermer une notification
     */
    SismeDashboard.closeNotification = function(target) {
        let $notification;
        
        if (target instanceof jQuery) {
            $notification = target;
        } else {
            $notification = $(target.currentTarget).closest('.sisme-notification');
        }
        
        $notification.slideUp(200, function() {
            $(this).remove();
        });
    };
    
    /**
     * ✨ Support mobile - Créer le toggle sidebar
     */
    SismeDashboard.createMobileToggle = function() {
        if ($('.sisme-mobile-toggle').length) {
            return; // Déjà créé
        }
        
        const $toggle = $(`
            <button class="sisme-mobile-toggle">
                <span class="sisme-hamburger"></span>
                <span class="sisme-hamburger"></span>
                <span class="sisme-hamburger"></span>
            </button>
        `);
        
        const $overlay = $('<div class="sisme-mobile-overlay"></div>');
        
        $('.sisme-dashboard-header').prepend($toggle);
        $('.sisme-user-dashboard').append($overlay);
        
        // Événement toggle
        $toggle.on('click', this.toggleMobileSidebar.bind(this));
        
        this.log('Toggle mobile créé');
    };
    
    /**
     * Toggle sidebar mobile
     */
    SismeDashboard.toggleMobileSidebar = function() {
        const $sidebar = $('.sisme-dashboard-sidebar');
        const $overlay = $('.sisme-mobile-overlay');
        const $toggle = $('.sisme-mobile-toggle');
        
        if ($sidebar.hasClass('mobile-open')) {
            this.closeMobileSidebar();
        } else {
            $sidebar.addClass('mobile-open');
            $overlay.addClass('active');
            $toggle.addClass('active');
            $('body').addClass('sidebar-open');
        }
    };
    
    /**
     * Fermer sidebar mobile
     */
    SismeDashboard.closeMobileSidebar = function() {
        $('.sisme-dashboard-sidebar').removeClass('mobile-open');
        $('.sisme-mobile-overlay').removeClass('active');
        $('.sisme-mobile-toggle').removeClass('active');
        $('body').removeClass('sidebar-open');
    };
    
    /**
     * Gestion du redimensionnement
     */
    SismeDashboard.handleResize = function() {
        // Fermer sidebar mobile si on passe en desktop
        if ($(window).width() > 767) {
            this.closeMobileSidebar();
        }
    };
    
    /**
     * Animations au scroll
     */
    SismeDashboard.initScrollAnimations = function() {
        // Animation simple des éléments qui entrent dans le viewport
        const $elements = $('.sisme-game-card, .sisme-activity-item, .sisme-stat-item');
        
        $elements.each(function(index) {
            const $element = $(this);
            setTimeout(() => {
                $element.addClass('animated');
            }, index * 50);
        });
    };
    
    /**
     * Utilitaires
     */
    SismeDashboard.utils = {
        isMobile: function() {
            return $(window).width() <= 767;
        },
        
        isTablet: function() {
            return $(window).width() >= 768 && $(window).width() <= 1199;
        },
        
        isDesktop: function() {
            return $(window).width() >= 1200;
        },
        
        scrollTo: function(target, duration = 500) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, duration);
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    /**
     * Debug et logging
     */
    SismeDashboard.log = function(...args) {
        if (this.config.debug || (typeof window.WP_DEBUG !== 'undefined' && window.WP_DEBUG)) {
            console.log('[Sisme Dashboard]', ...args);
        }
    };
    
    /**
     * API publique pour interactions externes
     */
    SismeDashboard.api = {
        // Changer de section programmatiquement
        goToSection: function(section) {
            if (SismeDashboard.isValidSection(section)) {
                SismeDashboard.setActiveSection(section, true);
                return true;
            }
            return false;
        },
        
        // Afficher une notification
        notify: function(message, type = 'info', duration = 3000) {
            SismeDashboard.showNotification(message, type, duration);
        },
        
        // Obtenir la section actuelle
        getCurrentSection: function() {
            return SismeDashboard.currentSection;
        },
        
        // Vérifier si initialisé
        isReady: function() {
            return SismeDashboard.isInitialized;
        }
    };
    
    // ✨ INITIALISATION AUTOMATIQUE
    $(document).ready(function() {
        // Vérifier si on est sur une page avec dashboard
        if ($('.sisme-user-dashboard').length) {
            SismeDashboard.init();
        }
    });
    
})(jQuery);