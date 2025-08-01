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
            // Réinitialiser la navigation même si déjà initialisé
            this.currentSection = 'overview'; // Reset
            this.initNavigation(); // Relire localStorage
            return;
        }
        
        // Réinitialiser l'état
        this.currentSection = 'overview';
        
        this.bindEvents();
        this.initNavigation();
        this.initMobileSupport();
        this.initAnimations();
        this.social.init();
        
        this.isInitialized = true;
        this.log('Dashboard JavaScript initialisé');
        
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
        // Détecter si on est sur un profil public ou le dashboard personnel
        const isOwnDashboard = this.isOwnDashboard();
        
        // Récupérer la section active depuis l'URL ou localStorage
        const hashParts = window.location.hash.replace('#', '').split('?');
        const urlHash = hashParts[0];
        const params = new URLSearchParams(hashParts[1] || '');
        const savedSection = localStorage.getItem('sisme_dashboard_section');
        
        let initialSection = 'overview';
        
        // Priorité 1: Hash dans l'URL (toujours respecté)
        if (urlHash && this.isValidSection(urlHash)) {
            initialSection = urlHash;
        } 
        // Priorité 2: localStorage SEULEMENT pour le dashboard personnel
        else if (isOwnDashboard && savedSection && this.isValidSection(savedSection)) {
            initialSection = savedSection;
        }
        // Priorité 3: overview par défaut (surtout pour les profils publics)
        
        // Appliquer la section active
        this.setActiveSection(initialSection, false);
        
        this.log('Navigation initialisée, section active:', initialSection, 'dashboard personnel:', isOwnDashboard);
    };

    /**
     * NOUVELLE FONCTION - Détecter si on est sur son propre dashboard
     */
    SismeDashboard.isOwnDashboard = function() {
        // Méthode 1: Vérifier l'URL
        const currentUrl = window.location.href;
        if (currentUrl.includes('/sisme-user-tableau-de-bord/')) {
            return true; // Dashboard personnel
        }
        
        // Méthode 2: Vérifier si on est sur un profil avec paramètre utilisateur
        if (currentUrl.includes('/sisme-user-profil/') && currentUrl.includes('?user=')) {
            return false; // Profil public
        }
        
        // Méthode 3: Vérifier les attributs data du container
        const $dashboard = $('.sisme-user-dashboard');
        if ($dashboard.length) {
            const dashboardUserId = $dashboard.data('user-id');
            const currentUserId = this.config.currentUserId;
            
            // Si les IDs correspondent et qu'on n'a pas de paramètre ?user=, c'est son propre dashboard
            if (dashboardUserId && currentUserId && dashboardUserId == currentUserId) {
                const urlParams = new URLSearchParams(window.location.search);
                return !urlParams.has('user'); // Pas de paramètre ?user= = dashboard personnel
            }
        }
        
        // Par défaut, considérer comme dashboard personnel
        return true;
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
     * Gestion de la navigation entre sections
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
     * Définir la section active avec animation
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
        const currentHash = window.location.hash;
        const currentHashWithoutParams = currentHash.split('?')[0];
        const expectedHash = '#' + section;

        if (currentHashWithoutParams !== expectedHash) {
            // Si on est sur submit-game et qu'il y a des paramètres, les préserver
            if (section === 'submit-game' && currentHash.includes('?')) {
                // Ne pas changer l'URL si on est déjà sur submit-game avec paramètres
                const currentSection = currentHash.substring(1).split('?')[0];
                if (currentSection === 'submit-game') {
                    // On reste sur submit-game avec paramètres - ne pas modifier l'URL
                    this.log('Préservation des paramètres URL pour submit-game:', currentHash);
                } else {
                    history.replaceState(null, null, expectedHash);
                }
            } else {
                history.replaceState(null, null, expectedHash);
            }
        }
        
        // 5. Sauvegarder dans localStorage SEULEMENT pour le dashboard personnel
        if (this.isOwnDashboard()) {
            localStorage.setItem('sisme_dashboard_section', section);
            this.log('Section sauvée dans localStorage:', section);
        } else {
            this.log('Profil public - pas de sauvegarde localStorage');
        }
        
        // 6. Émettre un événement personnalisé
        $(document).trigger('sisme:dashboard:section-changed', [section, previousSection]);
        
        // 7. Notification de changement de section
        if (animate && section !== previousSection) {
            const sectionNames = {
                'overview': 'Vue d\'ensemble',
                'favorites': 'Mes Favoris',
                'library': 'La Sismothèque',
                'activity': 'Mon Activité',
                'settings': 'Paramètres',
                'social': 'Social'
            };
            
            this.showNotification(`Section ${sectionNames[section] || section}`, 'info', 2000);
        }
        
        this.log('Section active:', section);
    };
    
    /**
     * Gestion du changement d'hash dans l'URL
     */
    SismeDashboard.handleHashChange = function() {
        const hashParts = window.location.hash.replace('#', '').split('?');
        const newSection = hashParts[0] || 'overview';
        const params = new URLSearchParams(hashParts[1] || '');
        
        if (this.isValidSection(newSection) && newSection !== this.currentSection) {
            this.setActiveSection(newSection, true);
            
            // Transmettre les paramètres à la section
            if (newSection === 'submit-game') {
                $(document).trigger('sisme:submit-game:url-params', [params]);
            }
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

    /**
     * Module de gestion des interactions sociales dans le header
     */
    SismeDashboard.social = {
        init: function() {
            this.bindSocialEvents();
            this.bindSocialListEvents();
            this.checkSocialConfig();
            SismeDashboard.log('Module social initialisé');
        },

        /**
         * Lier les événements des listes sociales
         */
        bindSocialListEvents: function() {
            // Boutons d'actions dans les listes
            $(document).on('click', '.sisme-social-action-btn', this.handleListAction.bind(this));
            
            // Recherche d'amis
            $(document).on('input', '#sisme-friend-search', 
                SismeDashboard.utils.debounce(this.handleFriendSearch.bind(this), 300));
            
            // Clic sur résultats de recherche
            $(document).on('click', '.sisme-search-result-item', this.handleSearchResultClick.bind(this));
            
            // Cacher les résultats si clic ailleurs
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sisme-friend-search-widget').length) {
                    $('#sisme-search-results').hide();
                }
            });
        },
        
        /**
         * Gérer les actions dans les listes (accepter, refuser, supprimer, etc.)
         */
        handleListAction: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!this.checkSocialConfig()) {
                SismeDashboard.api.notify('Configuration sociale manquante', 'error');
                return;
            }
            
            const $button = $(e.currentTarget);
            const $item = $button.closest('.sisme-friend-item, .sisme-request-item, .sisme-sent-request-item');
            const userId = $button.data('user-id');
            const action = $button.data('action');
            
            if (!userId || !action) {
                SismeDashboard.log('Données manquantes pour l\'action de liste', { userId, action });
                return;
            }
            
            // Empêcher les clics multiples
            if ($button.hasClass('loading')) {
                return;
            }
            
            // Traiter l'action selon le type
            switch(action) {
                case 'accept_request':
                    this.acceptFriendRequestFromList(userId, $button, $item);
                    break;
                case 'decline_request':
                    this.declineFriendRequestFromList(userId, $button, $item);
                    break;
                case 'remove_friend':
                    this.removeFriendFromList(userId, $button, $item);
                    break;
                case 'cancel_request':
                    this.cancelFriendRequestFromList(userId, $button, $item);
                    break;
                default:
                    SismeDashboard.log('Action de liste inconnue:', action);
            }
        },
        
        /**
         * Accepter une demande d'ami depuis la liste
         */
        acceptFriendRequestFromList: function(userId, $button, $item) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_accept_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Supprimer l'élément de la liste des demandes reçues
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            this.updateSectionBadges();
                        }.bind(this));
                        
                        SismeDashboard.api.notify('Demande d\'ami acceptée !', 'success');
                        setTimeout(() => {
                            if (SismeDashboard.api && SismeDashboard.api.goToSection) {
                                SismeDashboard.api.goToSection('social');
                            }
                        }, 500);
                        
                        // Mettre à jour les compteurs
                        this.updateAllSectionBadges();
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX acceptFriendRequestFromList:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Refuser une demande d'ami depuis la liste
         */
        declineFriendRequestFromList: function(userId, $button, $item) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_decline_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Supprimer l'élément de la liste
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            this.updateSectionBadges();
                        }.bind(this));
                        
                        SismeDashboard.api.notify('Demande refusée', 'info');
                        this.updateAllSectionBadges();
                        setTimeout(() => {
                            if (SismeDashboard.api && SismeDashboard.api.goToSection) {
                                SismeDashboard.api.goToSection('social');
                            }
                        }, 500);
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX declineFriendRequestFromList:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Supprimer un ami depuis la liste
         */
        removeFriendFromList: function(userId, $button, $item) {
            // Confirmation avant suppression
            const userName = $item.find('.sisme-friend-name a, .sisme-request-name a').text().trim();
            if (!confirm(`Êtes-vous sûr de vouloir retirer ${userName} de vos amis ?`)) {
                return;
            }
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_remove_friend',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Supprimer l'élément de la liste
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            this.updateSectionBadges();
                        }.bind(this));
                        
                        SismeDashboard.api.notify('Ami supprimé', 'info');
                        this.updateAllSectionBadges();
                        setTimeout(() => {
                            if (SismeDashboard.api && SismeDashboard.api.goToSection) {
                                SismeDashboard.api.goToSection('social');
                            }
                        }, 500);
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX removeFriendFromList:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Annuler une demande d'ami depuis la liste
         */
        cancelFriendRequestFromList: function(userId, $button, $item) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_cancel_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Supprimer l'élément de la liste
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            this.updateSectionBadges();
                        }.bind(this));                        
                        
                        SismeDashboard.api.notify('Demande annulée', 'info');
                        this.updateAllSectionBadges();
                        setTimeout(() => {
                            if (SismeDashboard.api && SismeDashboard.api.goToSection) {
                                SismeDashboard.api.goToSection('social');
                            }
                        }, 500);
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX cancelFriendRequestFromList:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Recherche d'amis en temps réel
         */
        handleFriendSearch: function(e) {
            const searchTerm = $(e.target).val().trim();
            const $results = $('#sisme-search-results');
            
            if (searchTerm.length < 2) {
                $results.hide();
                return;
            }
            
            this.showSearchLoading();
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_search_users',
                    search_term: searchTerm,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySearchResults(response.data.results);
                    } else {
                        this.showSearchError(response.data.message || 'Erreur de recherche');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX handleFriendSearch:', error);
                    this.showSearchError('Erreur de connexion');
                }
            });
        },
        
        /**
         * Afficher les résultats de recherche
         */
        displaySearchResults: function(users) {
            const $results = $('#sisme-search-results');
            
            if (!users || users.length === 0) {
                $results.html(`
                    <div class="sisme-empty-state">
                        <div class="sisme-empty-icon">🔍</div>
                        <p>Aucun utilisateur trouvé</p>
                    </div>
                `).show();
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const avatarUrl = user.avatar_url || 'https://www.gravatar.com/avatar/?d=mp&s=48';
                const profileUrl = user.profile_url || '#';
                
                html += `
                    <div class="sisme-search-result-item" data-user-id="${user.id}">
                        <div class="sisme-search-result-avatar">
                            <img src="${avatarUrl}" alt="Avatar" class="sisme-avatar-small">
                        </div>
                        <div class="sisme-search-result-info">
                            <h4 class="sisme-search-result-name">${user.display_name}</h4>
                            <span class="sisme-search-result-meta">Cliquer pour voir le profil</span>
                        </div>
                        <div class="sisme-search-result-actions">
                            <a href="${profileUrl}" class="sisme-social-action-btn sisme-btn-view" title="Voir le profil">
                                👁️
                            </a>
                        </div>
                    </div>
                `;
            });
            
            $results.html(html).show();
        },
        
        /**
         * Gérer le clic sur un résultat de recherche
         */
        handleSearchResultClick: function(e) {
            e.preventDefault();
            
            const $item = $(e.currentTarget);
            const $link = $item.find('.sisme-btn-view');
            
            if ($link.length) {
                window.open($link.attr('href'), '_blank');
            }
        },
        
        /**
         * Afficher l'état de chargement de la recherche
         */
        showSearchLoading: function() {
            $('#sisme-search-results').html(`
                <div class="sisme-search-loading">
                    <div class="sisme-empty-icon">⏳</div>
                    <span>Recherche en cours...</span>
                </div>
            `).show();
        },
        
        /**
         * Afficher une erreur de recherche
         */
        showSearchError: function(message) {
            $('#sisme-search-results').html(`
                <div class="sisme-search-error">
                    <div class="sisme-empty-icon">❌</div>
                    <span>Erreur : ${message}</span>
                </div>
            `).show();
        },
        
        /**
         * Mettre à jour tous les badges des sections
         */
        updateAllSectionBadges: function() {
            // Recompter les éléments dans chaque section
            setTimeout(() => {
                // Compter les amis
                const friendsCount = $('.sisme-friends-list .sisme-friend-item').length;
                $('.sisme-social-friends .sisme-badge').text(friendsCount);
                
                // Compter les demandes reçues
                const receivedCount = $('.sisme-requests-list .sisme-request-item').length;
                $('.sisme-social-requests-received .sisme-badge').text(receivedCount);
                
                // Compter les demandes envoyées
                const sentCount = $('.sisme-sent-requests-list .sisme-sent-request-item').length;
                $('.sisme-social-requests-sent .sisme-badge').text(sentCount);
                
                // Mettre à jour le compteur du header si présent
                this.updateMyFriendsCounter();
                
                // Afficher les états vides si nécessaire
                this.checkEmptyStates();
            }, 100);
        },
        
        /**
         * Vérifier et afficher les états vides
         */
        checkEmptyStates: function() {
            // Vérifier section amis
            const $friendsList = $('.sisme-friends-list');
            if ($friendsList.find('.sisme-friend-item').length === 0 && 
                $friendsList.find('.sisme-empty-state').length === 0) {
                $friendsList.html(`
                    <div class="sisme-empty-state">
                        <div class="sisme-empty-icon">👥</div>
                        <h4>Aucun ami pour le moment</h4>
                        <p>Commencez à vous faire des amis en visitant d'autres profils !</p>
                    </div>
                `);
            }
            
            // Vérifier section demandes reçues
            const $requestsList = $('.sisme-requests-list');
            if ($requestsList.find('.sisme-request-item').length === 0 && 
                $requestsList.find('.sisme-empty-state').length === 0) {
                $requestsList.html(`
                    <div class="sisme-empty-state">
                        <div class="sisme-empty-icon">📩</div>
                        <h4>Aucune demande en attente</h4>
                        <p>Les nouvelles demandes d'ami apparaîtront ici.</p>
                    </div>
                `);
            }
            
            // Vérifier section demandes envoyées
            const $sentRequestsList = $('.sisme-sent-requests-list');
            if ($sentRequestsList.find('.sisme-sent-request-item').length === 0 && 
                $sentRequestsList.find('.sisme-empty-state').length === 0) {
                $sentRequestsList.html(`
                    <div class="sisme-empty-state">
                        <div class="sisme-empty-icon">📤</div>
                        <h4>Aucune demande envoyée</h4>
                        <p>Vos demandes d'ami en attente s'afficheront ici.</p>
                    </div>
                `);
            }
        },
        
        /**
         * Vérifier la configuration sociale
         */
        checkSocialConfig: function() {
            if (typeof window.sismeUserSocial === 'undefined') {
                SismeDashboard.log('Configuration sismeUserSocial non trouvée');
                return false;
            }
            return true;
        },
        
        /**
         * Lier les événements des boutons sociaux
         */
        bindSocialEvents: function() {
            // Event delegation pour les boutons sociaux du header
            $(document).on('click', '.sisme-social-button', this.handleSocialAction.bind(this));
            
            // Event delegation pour les liens de compteur d'amis (optionnel futur)
            $(document).on('click', '.sisme-social-counter', this.handleFriendsCount.bind(this));
        },
        
        /**
         * Gérer les actions des boutons sociaux
         */
        handleSocialAction: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!this.checkSocialConfig()) {
                SismeDashboard.api.notify('Configuration sociale manquante', 'error');
                return;
            }
            
            const $button = $(e.currentTarget);
            const $container = $button.closest('.sisme-social-action');
            const userId = $container.data('user-id');
            const action = $button.data('action');
            const currentStatus = $container.data('status');
            
            if (!userId || !action) {
                SismeDashboard.log('Données manquantes pour l\'action sociale', { userId, action });
                return;
            }
            
            // Empêcher les clics multiples
            if ($button.hasClass('loading')) {
                return;
            }
            
            // Traiter l'action selon le type
            switch(action) {
                case 'add_friend':
                    this.sendFriendRequest(userId, $button, $container);
                    break;
                case 'cancel_request':
                    this.cancelFriendRequest(userId, $button, $container);
                    break;
                case 'accept_request':
                    this.acceptFriendRequest(userId, $button, $container);
                    break;
                case 'remove_friend':
                    this.removeFriend(userId, $button, $container);
                    break;
                default:
                    SismeDashboard.log('Action sociale inconnue:', action);
            }
        },
        
        /**
         * Envoyer une demande d'ami
         */
        sendFriendRequest: function(userId, $button, $container) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_send_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateButtonState($button, $container, 'pending_from_user1');
                        SismeDashboard.api.notify('Demande d\'ami envoyée !', 'success');
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX sendFriendRequest:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Annuler une demande d'ami
         */
        cancelFriendRequest: function(userId, $button, $container) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_cancel_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateButtonState($button, $container, 'none');
                        SismeDashboard.api.notify('Demande annulée', 'info');
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX cancelFriendRequest:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Accepter une demande d'ami
         */
        acceptFriendRequest: function(userId, $button, $container) {
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_accept_friend_request',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateButtonState($button, $container, 'friends');
                        SismeDashboard.api.notify('Nouvel ami ajouté !', 'success');
                        
                        // Optionnel: Mettre à jour le compteur sur mon profil
                        this.updateMyFriendsCounter();
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX acceptFriendRequest:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Supprimer un ami
         */
        removeFriend: function(userId, $button, $container) {
            // Confirmation avant suppression
            if (!confirm('Êtes-vous sûr de vouloir retirer cette personne de vos amis ?')) {
                return;
            }
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: window.sismeUserSocial.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_remove_friend',
                    user_id: userId,
                    nonce: window.sismeUserSocial.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateButtonState($button, $container, 'none');
                        SismeDashboard.api.notify('Ami supprimé', 'info');
                        
                        // Optionnel: Mettre à jour le compteur sur mon profil
                        this.updateMyFriendsCounter();
                    } else {
                        SismeDashboard.api.notify('Erreur: ' + response.data, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    SismeDashboard.log('Erreur AJAX removeFriend:', error);
                    SismeDashboard.api.notify('Erreur de connexion', 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Mettre le bouton en état de chargement
         */
        setButtonLoading: function($button, isLoading) {
            if (isLoading) {
                $button.addClass('loading');
                $button.prop('disabled', true);
                
                // Animation de la pastille pendant le chargement
                const $pastille = $button.find('.sisme-social-pastille');
                $pastille.removeClass('state-change');
            } else {
                $button.removeClass('loading');
                $button.prop('disabled', false);
            }
        },
        
        /**
         * Mettre à jour l'état du bouton selon le nouveau statut
         */
        updateButtonState: function($button, $container, newStatus) {
            // Configurations des boutons selon l'état
            const buttonConfigs = {
                'none': {
                    icon: '➕',
                    class: 'sisme-social-success',
                    title: 'Ajouter en ami',
                    action: 'add_friend'
                },
                'pending_from_user1': {
                    icon: '⏳',
                    class: 'sisme-social-warning',
                    title: 'Demande envoyée',
                    action: 'cancel_request'
                },
                'pending_from_user2': {
                    icon: '✓',
                    class: 'sisme-social-success',
                    title: 'Accepter la demande',
                    action: 'accept_request'
                },
                'friends': {
                    icon: '❌',
                    class: 'sisme-social-error',
                    title: 'Retirer de mes amis',
                    action: 'remove_friend'
                }
            };
            
            const config = buttonConfigs[newStatus];
            if (!config) {
                SismeDashboard.log('Configuration de bouton manquante pour:', newStatus);
                return;
            }
            
            // Mettre à jour la pastille
            const $pastille = $button.find('.sisme-social-pastille');
            
            // Animation de changement d'état
            $pastille.addClass('state-change');
            
            setTimeout(() => {
                // Mettre à jour les classes CSS
                $pastille.removeClass('sisme-social-success sisme-social-warning sisme-social-error')
                        .addClass(config.class)
                        .text(config.icon);
                
                // Mettre à jour les attributs du bouton
                $button.attr('title', config.title)
                       .attr('data-action', config.action);
                
                // Mettre à jour le statut du container
                $container.attr('data-status', newStatus);
                
                // Retirer l'animation après un délai
                setTimeout(() => {
                    $pastille.removeClass('state-change');
                }, 400);
            }, 200);
        },
        
        /**
         * Mettre à jour le compteur d'amis sur mon profil (optionnel)
         */
        updateMyFriendsCounter: function() {
            // Seulement si on est sur notre profil et qu'il y a un compteur
            const $counter = $('.sisme-social-counter .sisme-social-count');
            if ($counter.length) {
                // Récupérer le nouveau nombre d'amis via AJAX
                $.ajax({
                    url: window.sismeUserSocial.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sisme_get_friends_count',
                        nonce: window.sismeUserSocial.nonce
                    },
                    success: (response) => {
                        if (response.success && typeof response.data.count !== 'undefined') {
                            $counter.text(response.data.count);
                            
                            // Petite animation pour signaler le changement
                            $counter.addClass('state-change');
                            setTimeout(() => {
                                $counter.removeClass('state-change');
                            }, 400);
                        }
                    },
                    error: (xhr, status, error) => {
                        SismeDashboard.log('Erreur lors de la mise à jour du compteur:', error);
                    }
                });
            }
        },
        
        /**
         * Gérer le clic sur le compteur d'amis (optionnel - peut rediriger vers l'onglet social)
         */
        handleFriendsCount: function(e) {
            e.preventDefault();
            
            // Si on est sur notre dashboard, aller à l'onglet social
            if ($('.sisme-user-dashboard').length && SismeDashboard.api) {
                const socialSection = $('[data-section="social"]');
                if (socialSection.length) {
                    SismeDashboard.api.goToSection('social');
                    SismeDashboard.api.notify('Accès à vos amis', 'info');
                }
            }
        }
    };
    
    // INITIALISATION AUTOMATIQUE
    $(document).ready(function() {
        // Vérifier si on est sur une page avec dashboard
        if ($('.sisme-user-dashboard').length) {
            SismeDashboard.init();
        }
    });
    
})(jQuery);