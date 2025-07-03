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
        validSections: ['overview', 'favorites', 'library', 'activity', 'settings', 'social']
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
        const urlHash = window.location.hash.replace('#', '');
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
        if (window.location.hash !== '#' + section) {
            history.replaceState(null, null, '#' + section);
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

    // Système de recherche d'utilisateurs
    const SismeUserSearch = {
        searchInput: null,
        searchResults: null,
        searchTimeout: null,
        
        init() {
            this.searchInput = document.querySelector('.sisme-search-input');
            this.searchResults = document.querySelector('.sisme-search-results');
            
            if (!this.searchInput || !this.searchResults) {
                return;
            }
            
            // Activer le champ de recherche
            this.searchInput.disabled = false;
            this.searchInput.placeholder = "Rechercher des utilisateurs...";
            
            // Événements
            this.searchInput.addEventListener('input', (e) => this.handleInput(e));
            this.searchInput.addEventListener('focus', () => this.handleFocus());
            this.searchInput.addEventListener('blur', () => this.handleBlur());
            
            // Activer le bouton de recherche (optionnel)
            const searchButton = document.querySelector('.sisme-search-button');
            if (searchButton) {
                searchButton.disabled = false;
                searchButton.addEventListener('click', () => this.performSearch());
            }
        },
        
        handleInput(e) {
            const term = e.target.value.trim();
            
            // Annuler la recherche précédente
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // Attendre 300ms après la dernière frappe
            this.searchTimeout = setTimeout(() => {
                this.performSearch(term);
            }, 300);
        },
        
        handleFocus() {
            // Afficher les résultats si il y en a
            if (this.searchResults.children.length > 0) {
                this.searchResults.style.display = 'block';
            }
        },
        
        handleBlur() {
            // Masquer les résultats après un délai (pour permettre les clics)
            setTimeout(() => {
                this.searchResults.style.display = 'none';
            }, 200);
        },
        
        performSearch(term = null) {
            if (term === null) {
                term = this.searchInput.value.trim();
            }
            
            if (term.length < 2) {
                this.showEmptyState('Tapez au moins 2 caractères');
                return;
            }
            
            // Afficher le loading
            this.showLoading();
            
            // Requête AJAX
            const formData = new FormData();
            formData.append('action', 'sisme_search_users');
            formData.append('search_term', term);
            formData.append('nonce', sismeUserSocial.nonce);
            
            fetch(sismeUserSocial.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayResults(data.data.results, data.data.message);
                } else {
                    this.showError(data.data.message || 'Erreur de recherche');
                }
            })
            .catch(error => {
                console.error('Erreur recherche utilisateurs:', error);
                this.showError('Erreur de connexion');
            });
        },
        
        displayResults(users, message) {
            if (users.length === 0) {
                this.showEmptyState(message);
                return;
            }
            
            // Créer la liste des résultats
            let html = '<div class="sisme-search-results-list">';
            
            users.forEach(user => {
                html += `
                    <div class="sisme-search-result-item" data-user-id="${user.id}">
                        <a href="${user.profile_url}" class="sisme-search-result-link">
                            <div class="sisme-search-result-info">
                                <span class="sisme-search-result-name">${user.display_name}</span>
                                <span class="sisme-search-result-slug">@${user.user_nicename}</span>
                            </div>
                            <span class="sisme-search-result-icon">👤</span>
                        </a>
                    </div>
                `;
            });
            
            html += '</div>';
            
            this.searchResults.innerHTML = html;
            this.searchResults.style.display = 'block';
        },
        
        showLoading() {
            this.searchResults.innerHTML = `
                <div class="sisme-search-loading">
                    <div class="sisme-empty-icon">⏳</div>
                    <span>Recherche en cours...</span>
                </div>
            `;
            this.searchResults.style.display = 'block';
        },
        
        showEmptyState(message) {
            this.searchResults.innerHTML = `
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">🔍</div>
                    <span>${message}</span>
                </div>
            `;
            this.searchResults.style.display = 'block';
        },
        
        showError(message) {
            this.searchResults.innerHTML = `
                <div class="sisme-search-error">
                    <div class="sisme-empty-icon">❌</div>
                    <span>Erreur : ${message}</span>
                </div>
            `;
            this.searchResults.style.display = 'block';
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Attendre que la section sociale soit visible
        const socialSection = document.querySelector('[data-section="social"]');
        if (socialSection) {
            SismeUserSearch.init();
        }
        
        // Observer les changements de section pour réinitialiser si nécessaire
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.target.dataset?.section === 'social' && 
                    mutation.target.style.display !== 'none') {
                    SismeUserSearch.init();
                }
            });
        });
        
        if (socialSection) {
            observer.observe(socialSection, { 
                attributes: true, 
                attributeFilter: ['style'] 
            });
        }
    });
    
    // ✨ INITIALISATION AUTOMATIQUE
    $(document).ready(function() {
        // Vérifier si on est sur une page avec dashboard
        if ($('.sisme-user-dashboard').length) {
            SismeDashboard.init();
        }
    });
    
})(jQuery);