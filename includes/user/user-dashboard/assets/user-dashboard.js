/**
 * File: /sisme-games-editor/includes/user/user-dashboard/assets/user-dashboard.js
 * JavaScript pour le dashboard utilisateur
 * 
 * RESPONSABILITÉ:
 * - Navigation entre sections du dashboard
 * - Interactions avec les boutons et liens
 * - Système de notifications simple
 * - Animations et transitions fluides
 * - Mobile responsive behavior
 */

(function($) {
    'use strict';
    
    // Namespace principal
    window.SismeDashboard = {
        config: window.sismeUserDashboard || {},
        currentSection: 'overview',
        isInitialized: false
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
        
        // Message de bienvenue
        setTimeout(() => {
            this.showNotification('Bienvenue sur votre dashboard gaming ! 🎮', 'success');
        }, 1000);
    };
    
    /**
     * Liaison des événements
     */
    SismeDashboard.bindEvents = function() {
        // Navigation principale
        $(document).on('click', '.sisme-nav-link', this.handleNavigation.bind(this));
        $(document).on('click', '.sisme-quick-btn', this.handleQuickAction.bind(this));
        
        // Actions sur les jeux
        $(document).on('click', '.sisme-game-card', this.handleGameClick.bind(this));
        $(document).on('click', '.sisme-favorite-item', this.handleFavoriteClick.bind(this));
        
        // Actions diverses
        $(document).on('click', '.sisme-btn', this.handleButtonClick.bind(this));
        
        // Fermeture notifications
        $(document).on('click', '.sisme-notification-close', this.closeNotification.bind(this));
        
        this.log('Événements liés');
    };
    
    /**
     * Initialisation de la navigation
     */
    SismeDashboard.initNavigation = function() {
        // Récupérer la section active depuis l'URL ou localStorage
        const urlHash = window.location.hash.replace('#', '');
        const savedSection = localStorage.getItem('sisme_dashboard_section');
        
        if (urlHash && this.isValidSection(urlHash)) {
            this.currentSection = urlHash;
        } else if (savedSection && this.isValidSection(savedSection)) {
            this.currentSection = savedSection;
        }
        
        // Appliquer la section active
        this.setActiveSection(this.currentSection);
        
        // Gérer les changements d'URL
        $(window).on('hashchange', () => {
            const newSection = window.location.hash.replace('#', '') || 'overview';
            if (this.isValidSection(newSection)) {
                this.setActiveSection(newSection);
            }
        });
        
        this.log('Navigation initialisée, section active:', this.currentSection);
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
        
        this.log('Support mobile initialisé');
    };
    
    /**
     * Créer le bouton toggle mobile
     */
    SismeDashboard.createMobileToggle = function() {
        if ($(window).width() <= 767 && !$('.sisme-mobile-toggle').length) {
            const $toggle = $(`
                <button class="sisme-mobile-toggle" aria-label="Menu">
                    <span class="sisme-toggle-icon">☰</span>
                </button>
            `);
            
            $toggle.css({
                position: 'fixed',
                top: '20px',
                left: '20px',
                background: 'var(--sisme-gaming-accent, #58a6ff)',
                color: 'white',
                border: 'none',
                borderRadius: '50%',
                width: '50px',
                height: '50px',
                fontSize: '1.2rem',
                cursor: 'pointer',
                zIndex: '1001',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
                transition: 'all 0.3s ease'
            });
            
            $toggle.on('click', this.toggleMobileSidebar.bind(this));
            $('body').append($toggle);
        }
    };
    
    /**
     * Toggle sidebar mobile
     */
    SismeDashboard.toggleMobileSidebar = function() {
        const $sidebar = $('.sisme-dashboard-sidebar');
        const $toggle = $('.sisme-mobile-toggle');
        const $overlay = $('.sisme-mobile-overlay');
        
        if ($sidebar.hasClass('mobile-open')) {
            // Fermer
            $sidebar.removeClass('mobile-open');
            $toggle.find('.sisme-toggle-icon').text('☰');
            $overlay.remove();
        } else {
            // Ouvrir
            $sidebar.addClass('mobile-open');
            $toggle.find('.sisme-toggle-icon').text('×');
            
            // Créer overlay
            const $newOverlay = $('<div class="sisme-mobile-overlay"></div>');
            $newOverlay.css({
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                background: 'rgba(0, 0, 0, 0.5)',
                zIndex: '999',
                display: 'block'
            });
            $('body').append($newOverlay);
        }
    };
    
    /**
     * Fermer sidebar mobile
     */
    SismeDashboard.closeMobileSidebar = function() {
        $('.sisme-dashboard-sidebar').removeClass('mobile-open');
        $('.sisme-mobile-toggle .sisme-toggle-icon').text('☰');
        $('.sisme-mobile-overlay').remove();
    };
    
    /**
     * Gestion du redimensionnement
     */
    SismeDashboard.handleResize = function() {
        if ($(window).width() > 767) {
            // Desktop: nettoyer mobile
            $('.sisme-mobile-toggle').remove();
            $('.sisme-mobile-overlay').remove();
            $('.sisme-dashboard-sidebar').removeClass('mobile-open');
        } else {
            // Mobile: créer toggle si nécessaire
            this.createMobileToggle();
        }
    };
    
    /**
     * Initialisation des animations
     */
    SismeDashboard.initAnimations = function() {
        // Fade in du dashboard
        $('.sisme-user-dashboard').addClass('dashboard-loaded');
        
        // Animation des statistiques (compteurs)
        this.animateCounters();
        
        // Lazy loading des images si nécessaire
        this.initLazyLoading();
        
        this.log('Animations initialisées');
    };
    
    /**
     * Animation des compteurs de stats
     */
    SismeDashboard.animateCounters = function() {
        $('.sisme-stat-value').each(function() {
            const $this = $(this);
            const finalValue = $this.text();
            const numericValue = parseInt(finalValue);
            
            if (isNaN(numericValue)) return;
            
            let currentValue = 0;
            const increment = Math.ceil(numericValue / 20);
            const duration = 1000;
            const stepTime = duration / (numericValue / increment);
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= numericValue) {
                    $this.text(finalValue);
                    clearInterval(timer);
                } else {
                    $this.text(currentValue);
                }
            }, stepTime);
        });
    };
    
    /**
     * Lazy loading simple
     */
    SismeDashboard.initLazyLoading = function() {
        $('img[data-src]').each(function() {
            const $img = $(this);
            $img.attr('src', $img.data('src')).removeAttr('data-src');
        });
    };
    
    /**
     * Gestion de la navigation
     */
    SismeDashboard.handleNavigation = function(e) {
        e.preventDefault();
        
        const $link = $(e.currentTarget);
        const section = $link.data('section') || $link.attr('href').replace('#', '');
        
        if (this.isValidSection(section)) {
            this.setActiveSection(section);
            
            // Fermer sidebar mobile après navigation
            if ($(window).width() <= 767) {
                this.closeMobileSidebar();
            }
        }
        
        this.log('Navigation vers:', section);
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
            this.setActiveSection(section);
        }
        
        this.showNotification(`Accès à ${label}`, 'info');
        this.log('Action rapide:', label);
    };
    
    /**
     * Clic sur une carte de jeu
     */
    SismeDashboard.handleGameClick = function(e) {
        const $card = $(e.currentTarget);
        const $link = $card.find('a').first();
        const gameName = $card.find('.sisme-game-name').text() || 'ce jeu';
        
        // Animation de clic
        $card.addClass('game-clicked');
        setTimeout(() => $card.removeClass('game-clicked'), 300);
        
        if ($link.length) {
            this.showNotification(`Ouverture de la fiche : ${gameName}`, 'info');
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
        
        this.showNotification(`Accès au favori : ${gameName}`, 'info');
        this.log('Clic sur favori:', gameName);
    };
    
    /**
     * Gestion des boutons génériques
     */
    SismeDashboard.handleButtonClick = function(e) {
        const $btn = $(e.currentTarget);
        
        // Ignorer si c'est un lien externe ou avec href
        if ($btn.attr('href') && $btn.attr('href') !== '#') {
            return; // Laisser le comportement normal
        }
        
        // Animation pour les boutons sans action spécifique
        $btn.addClass('btn-loading');
        $btn.find('span').first().text('⏳');
        
        setTimeout(() => {
            $btn.removeClass('btn-loading');
            $btn.find('span').first().text($btn.data('original-icon') || '');
        }, 1500);
        
        this.log('Clic sur bouton:', $btn.text().trim());
    };
    
    /**
     * Définir la section active
     */
    SismeDashboard.setActiveSection = function(section) {
        if (!this.isValidSection(section)) {
            section = 'overview';
        }
        
        this.currentSection = section;
        
        // Mettre à jour les liens de navigation
        $('.sisme-nav-link').removeClass('active');
        $(`.sisme-nav-link[data-section="${section}"]`).addClass('active');
        
        // Mettre à jour l'URL
        window.location.hash = section;
        
        // Sauvegarder dans localStorage
        localStorage.setItem('sisme_dashboard_section', section);
        
        // Émettre un événement personnalisé
        $(document).trigger('sisme:dashboard:section-changed', [section]);
        
        this.log('Section active:', section);
    };
    
    /**
     * Vérifier si une section est valide
     */
    SismeDashboard.isValidSection = function(section) {
        const validSections = ['overview', 'library', 'favorites', 'activity', 'stats'];
        return validSections.includes(section);
    };
    
    /**
     * Système de notifications
     */
    SismeDashboard.showNotification = function(message, type = 'info', duration = 3000) {
        if (!this.config.notifications) {
            return;
        }
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        const $notification = $(`
            <div class="sisme-notification sisme-notification-${type}">
                <div class="sisme-notification-content">
                    <span class="sisme-notification-icon">${icons[type] || icons.info}</span>
                    <span class="sisme-notification-text">${message}</span>
                    <button class="sisme-notification-close" aria-label="Fermer">×</button>
                </div>
            </div>
        `);
        
        // Styles inline pour assurer l'affichage
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: this.getNotificationColor(type),
            color: 'white',
            padding: '12px 16px',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)',
            zIndex: '1000',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease',
            maxWidth: '300px',
            fontSize: '14px'
        });
        
        $notification.find('.sisme-notification-content').css({
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
        });
        
        $notification.find('.sisme-notification-close').css({
            background: 'none',
            border: 'none',
            color: 'white',
            fontSize: '16px',
            cursor: 'pointer',
            padding: '0',
            marginLeft: 'auto'
        });
        
        $('body').append($notification);
        
        // Animer l'entrée
        setTimeout(() => {
            $notification.css('transform', 'translateX(0)');
        }, 100);
        
        // Auto-suppression
        setTimeout(() => {
            this.removeNotification($notification);
        }, duration);
        
        this.log('Notification affichée:', message, type);
    };
    
    /**
     * Couleur des notifications
     */
    SismeDashboard.getNotificationColor = function(type) {
        const colors = {
            success: '#3fb950',
            error: '#f85149',
            warning: '#d29922',
            info: '#58a6ff'
        };
        return colors[type] || colors.info;
    };
    
    /**
     * Fermer une notification
     */
    SismeDashboard.closeNotification = function(e) {
        e.preventDefault();
        const $notification = $(e.currentTarget).closest('.sisme-notification');
        this.removeNotification($notification);
    };
    
    /**
     * Supprimer une notification
     */
    SismeDashboard.removeNotification = function($notification) {
        $notification.css('transform', 'translateX(100%)');
        setTimeout(() => {
            $notification.remove();
        }, 300);
    };
    
    /**
     * Logging conditionnel
     */
    SismeDashboard.log = function(...args) {
        if (this.config.debug) {
            console.log('[Sisme Dashboard]', ...args);
        }
    };
    
    /**
     * Utilitaires publiques
     */
    SismeDashboard.utils = {
        /**
         * Détecter si on est sur mobile
         */
        isMobile: function() {
            return $(window).width() <= 767;
        },
        
        /**
         * Smooth scroll vers un élément
         */
        scrollTo: function(selector) {
            const $target = $(selector);
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 500);
            }
        },
        
        /**
         * Débounce pour les événements
         */
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
    
    // Initialisation automatique au chargement du DOM
    $(document).ready(function() {
        // Vérifier qu'on est bien sur une page avec dashboard
        if ($('.sisme-user-dashboard').length) {
            SismeDashboard.init();
        }
    });
    
    // API publique pour extensions futures
    window.SismeDashboard = SismeDashboard;
    
})(jQuery);