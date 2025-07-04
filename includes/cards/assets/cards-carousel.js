/**
 * File: /sisme-games-editor/includes/cards/assets/cards-carousel.js
 * JAVASCRIPT POUR LES CARROUSELS DE CARTES
 * 
 * FONCTIONNALITÉS:
 * - Navigation avec boutons
 * - Responsive automatique
 * - Mode infinite
 * - Pagination
 * - Touch/swipe support
 */

class SismeCarousel {
    constructor(carousel) {
        this.carousel = carousel;
        this.track = carousel.querySelector('.sisme-carousel__track');
        this.slides = Array.from(carousel.querySelectorAll('.sisme-carousel__slide'));
        this.prevBtn = carousel.querySelector('.sisme-carousel__btn--prev');
        this.nextBtn = carousel.querySelector('.sisme-carousel__btn--next');
        this.pagination = carousel.querySelector('.sisme-carousel__pagination');
        this.dots = [];
        
        // Configuration depuis les attributs
        this.config = this.parseConfig();
        
        // État du carrousel
        this.state = {
            currentIndex: 0,
            isInfinite: this.config.infinite,
            cardsPerView: this.config.cardsPerView,
            totalCards: this.config.totalCards,
            totalPages: 0,
            isTransitioning: false
        };
        
        // Responsive breakpoints
        this.breakpoints = {
            mobile: 480,
            tablet: 768,
            desktop: 1024
        };
        
        this.init();
    }
    
    /**
     * Parse la configuration depuis les attributs HTML
     */
    parseConfig() {
        const config = this.carousel.dataset.carouselConfig;
        const defaultConfig = {
            cardsPerView: 3,
            totalCards: 0,
            infinite: false,
            autoplay: false,
            navigation: true,
            pagination: true,
            smoothAnimation: true,
            touchEnabled: true
        };
        
        if (config) {
            try {
                return { ...defaultConfig, ...JSON.parse(config) };
            } catch (e) {
                console.warn('Erreur parsing config carrousel:', e);
            }
        }
        
        // Fallback depuis les attributs individuels
        return {
            ...defaultConfig,
            cardsPerView: parseInt(this.carousel.dataset.cardsPerView) || 3,
            totalCards: parseInt(this.carousel.dataset.cardsCount) || this.slides.length,
            infinite: this.carousel.dataset.infinite === 'true'
        };
    }
    
    /**
     * Initialisation du carrousel
     */
    init() {
        this.updateResponsiveConfig();
        this.setupSlides();
        this.setupNavigation();
        this.setupPagination();
        this.setupTouchEvents();
        this.setupResizeListener();
        
        // Position initiale pour mode infinite
        if (this.state.isInfinite) {
            this.setInitialInfinitePosition();
        }
        
        this.updateUI();
        this.debug('Carrousel initialisé', this.state);
    }
    
    /**
     * Met à jour la configuration responsive
     */
    updateResponsiveConfig() {
        const breakpoint = this.getCurrentBreakpoint();
        const originalCardsPerView = this.config.cardsPerView;
        
        switch (breakpoint) {
            case 'mobile':
                // TOUJOURS 1 carte sur mobile
                this.state.cardsPerView = 1;
                break;
            case 'tablet':
                // Maximum 2 cartes sur tablet
                this.state.cardsPerView = Math.min(2, originalCardsPerView);
                break;
            default:
                // Desktop : utiliser la configuration originale
                this.state.cardsPerView = originalCardsPerView;
        }
        
        this.state.totalPages = Math.ceil(this.state.totalCards / this.state.cardsPerView);
        this.debug(`Responsive: ${breakpoint}, ${this.state.cardsPerView} cartes par vue`);
    }
    
    /**
     * Détecte le breakpoint actuel
     */
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width <= 768) return 'mobile';  // 768px au lieu de 480px
        if (width <= 1024) return 'tablet';
        return 'desktop';
    }
    
    /**
     * Configure les slides
     */
    setupSlides() {
        const slideWidth = 100 / this.state.cardsPerView;
        
        this.slides.forEach(slide => {
            slide.style.width = `${slideWidth}%`;
            slide.style.flex = `0 0 ${slideWidth}%`;
        });
    }
    
    /**
     * Configure les boutons de navigation
     */
    setupNavigation() {
        if (!this.config.navigation) return;
        
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.goToPrevious());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.goToNext());
        }
    }
    
    /**
     * Configure la pagination
     */
    setupPagination() {
        if (!this.config.pagination || this.state.isInfinite) return;
        
        if (this.pagination) {
            this.createPaginationDots();
        }
    }
    
    /**
     * Crée les dots de pagination
     */
    createPaginationDots() {
        this.pagination.innerHTML = '';
        this.dots = [];
        
        for (let i = 0; i < this.state.totalPages; i++) {
            const dot = document.createElement('button');
            dot.className = 'sisme-carousel__dot';
            dot.setAttribute('aria-label', `Aller à la page ${i + 1}`);
            dot.addEventListener('click', () => this.goToPage(i));
            
            this.pagination.appendChild(dot);
            this.dots.push(dot);
        }
    }
    
    /**
     * Configure les événements tactiles
     */
    setupTouchEvents() {
        if (!this.config.touchEnabled) return;
        
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            currentX = e.touches[0].clientX;
            const diff = startX - currentX;
            
            // Prévenir le scroll pendant le swipe
            if (Math.abs(diff) > 10) {
                e.preventDefault();
            }
        });
        
        this.track.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            
            const diff = startX - currentX;
            const threshold = 50;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.goToNext();
                } else {
                    this.goToPrevious();
                }
            }
            
            isDragging = false;
        });
    }
    
    /**
     * Configure l'écoute du redimensionnement
     */
    setupResizeListener() {
        let resizeTimeout;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.updateResponsiveConfig();
                this.setupSlides();
                this.setupPagination();
                this.updateUI();
            }, 150);
        });
    }
    
    /**
     * Position initiale pour le mode infinite
     */
    setInitialInfinitePosition() {
        // En mode infinite, positionner au début des slides originaux
        const cloneOffset = this.state.cardsPerView;
        this.state.currentIndex = cloneOffset;
        this.updateTrackPosition(false);
    }
    
    /**
     * Va à la slide précédente
     */
    goToPrevious() {
        if (this.state.isTransitioning) return;
        
        if (this.state.isInfinite) {
            this.state.currentIndex--;
            this.updateTrackPosition(true);
            this.handleInfiniteLoop();
        } else {
            const newIndex = Math.max(0, this.state.currentIndex - this.state.cardsPerView);
            this.goToSlide(newIndex);
        }
    }
    
    /**
     * Va à la slide suivante
     */
    goToNext() {
        if (this.state.isTransitioning) return;
        
        if (this.state.isInfinite) {
            this.state.currentIndex++;
            this.updateTrackPosition(true);
            this.handleInfiniteLoop();
        } else {
            const maxIndex = this.state.totalCards - this.state.cardsPerView;
            const newIndex = Math.min(maxIndex, this.state.currentIndex + this.state.cardsPerView);
            this.goToSlide(newIndex);
        }
    }
    
    /**
     * Va à une slide spécifique
     */
    goToSlide(index) {
        if (this.state.isTransitioning) return;
        
        this.state.currentIndex = Math.max(0, Math.min(index, this.state.totalCards - this.state.cardsPerView));
        this.updateTrackPosition(true);
        this.updateUI();
    }
    
    /**
     * Va à une page spécifique (pagination)
     */
    goToPage(pageIndex) {
        const slideIndex = pageIndex * this.state.cardsPerView;
        this.goToSlide(slideIndex);
    }
    
    /**
     * Met à jour la position du track
     */
    updateTrackPosition(animated = true) {
        const slideWidth = 100 / this.state.cardsPerView;
        const translateX = -this.state.currentIndex * slideWidth;
        
        if (animated) {
            this.state.isTransitioning = true;
            this.track.style.transition = 'transform 0.3s ease';
        } else {
            this.track.style.transition = 'none';
        }
        
        this.track.style.transform = `translateX(${translateX}%)`;
        
        if (animated) {
            setTimeout(() => {
                this.state.isTransitioning = false;
            }, 300);
        }
    }
    
    /**
     * Gère la boucle infinie
     */
    handleInfiniteLoop() {
        if (!this.state.isInfinite) return;
        
        const totalSlides = this.slides.length;
        const cloneOffset = this.state.cardsPerView;
        
        setTimeout(() => {
            if (this.state.currentIndex <= 0) {
                // Retour à la fin
                this.state.currentIndex = totalSlides - (2 * cloneOffset);
                this.updateTrackPosition(false);
            } else if (this.state.currentIndex >= totalSlides - cloneOffset) {
                // Retour au début
                this.state.currentIndex = cloneOffset;
                this.updateTrackPosition(false);
            }
        }, 300);
    }
    
    /**
     * Met à jour l'interface utilisateur
     */
    updateUI() {
        this.updateNavButtons();
        this.updatePagination();
    }
    
    /**
     * Met à jour les boutons de navigation
     */
    updateNavButtons() {
        if (!this.config.navigation) return;
        
        if (this.state.isInfinite) {
            // En mode infinite, les boutons sont toujours actifs
            if (this.prevBtn) this.prevBtn.disabled = false;
            if (this.nextBtn) this.nextBtn.disabled = false;
        } else {
            // En mode normal, désactiver selon la position
            if (this.prevBtn) {
                this.prevBtn.disabled = this.state.currentIndex <= 0;
            }
            if (this.nextBtn) {
                this.nextBtn.disabled = this.state.currentIndex >= this.state.totalCards - this.state.cardsPerView;
            }
        }
    }
    
    /**
     * Met à jour la pagination
     */
    updatePagination() {
        if (!this.config.pagination || this.state.isInfinite) return;
        
        const currentPage = Math.floor(this.state.currentIndex / this.state.cardsPerView);
        
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentPage);
            dot.setAttribute('aria-selected', index === currentPage);
        });
    }
    
    /**
     * Debug utilitaire
     */
    debug(message, data = null) {
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log(`[Carrousel Debug] ${message}`, data || '');
        }
    }
    
    /**
     * Détruit le carrousel
     */
    destroy() {
        // Nettoyer les événements
        if (this.prevBtn) this.prevBtn.removeEventListener('click', this.goToPrevious);
        if (this.nextBtn) this.nextBtn.removeEventListener('click', this.goToNext);
        
        this.dots.forEach(dot => {
            dot.removeEventListener('click', this.goToPage);
        });
        
        // Réinitialiser les styles
        this.track.style.transform = '';
        this.track.style.transition = '';
        
        this.slides.forEach(slide => {
            slide.style.width = '';
            slide.style.flex = '';
        });
    }
}

/**
 * Initialisation automatique des carrousels
 */
document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.sisme-cards-carousel');
    
    carousels.forEach(carousel => {
        try {
            new SismeCarousel(carousel);
        } catch (error) {
            console.error('Erreur initialisation carrousel:', error);
        }
    });
    
    // Log pour debug
    if (carousels.length > 0) {
        console.log(`[Sisme Carrousel] ${carousels.length} carrousel(s) initialisé(s)`);
    }
});

/**
 * Utilitaires pour l'API externe
 */
window.SismeCarousel = SismeCarousel;

/**
 * Réinitialiser tous les carrousels (utile pour le contenu AJAX)
 */
window.sismeReinitCarousels = function() {
    const carousels = document.querySelectorAll('.sisme-cards-carousel');
    
    carousels.forEach(carousel => {
        // Détruire l'instance existante si elle existe
        if (carousel._sismeCarousel) {
            carousel._sismeCarousel.destroy();
        }
        
        // Créer une nouvelle instance
        try {
            carousel._sismeCarousel = new SismeCarousel(carousel);
        } catch (error) {
            console.error('Erreur réinitialisation carrousel:', error);
        }
    });
};