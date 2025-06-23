/**
 * File: /sisme-games-editor/includes/cards/assets/cards-carousel.js
 * JavaScript pour les carrousels de cartes
 * 
 * FONCTIONNALITÉS:
 * - Infinite loop sans extrémité
 * - Navigation boutons + dots
 * - Swipe tactile mobile
 * - Animation smooth
 * - Auto-initialisation
 */

class SismeCarousel {
    constructor(element, options = {}) {
        this.carousel = element;
        this.config = this.parseConfig();
        this.options = { ...this.getDefaultOptions(), ...options };
        
        // Éléments DOM
        this.container = this.carousel.querySelector('.sisme-carousel__container');
        this.track = this.carousel.querySelector('.sisme-carousel__track');
        this.slides = this.carousel.querySelectorAll('.sisme-carousel__slide');
        this.prevBtn = this.carousel.querySelector('.sisme-carousel__btn--prev');
        this.nextBtn = this.carousel.querySelector('.sisme-carousel__btn--next');
        this.dots = this.carousel.querySelectorAll('.sisme-carousel__dot');
        
        // État du carrousel
        this.currentIndex = 0;
        this.isAnimating = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        
        // Vérifications et initialisation
        if (!this.isValidCarousel()) {
            this.logError('Carrousel invalide - éléments manquants');
            return;
        }
        
        this.init();
    }
    
    /**
     * 🔧 Options par défaut
     */
    getDefaultOptions() {
        return {
            cardsPerView: 3,
            infinite: true,
            navigation: true,
            pagination: true,
            smoothAnimation: true,
            touchEnabled: true,
            animationDuration: 300,
            swipeThreshold: 50
        };
    }
    
    /**
     * 📋 Parser la configuration JSON du PHP
     */
    parseConfig() {
        try {
            const configData = this.carousel.dataset.carouselConfig;
            return configData ? JSON.parse(configData) : {};
        } catch (error) {
            this.logError('Erreur parsing config JSON:', error);
            return {};
        }
    }
    
    /**
     * ✅ Vérifier que le carrousel est valide
     */
    isValidCarousel() {
        return this.container && this.track && this.slides.length > 0;
    }
    
    /**
     * 🚀 Initialiser le carrousel
     */
    init() {
        this.log('Initialisation carrousel', {
            slides: this.slides.length,
            cardsPerView: this.config.cardsPerView || this.options.cardsPerView
        });
        
        // Configuration finale
        this.cardsPerView = this.config.cardsPerView || this.options.cardsPerView;
        this.totalSlides = this.slides.length;
        this.totalPages = Math.ceil(this.totalSlides / this.cardsPerView);
        
        // Dupliquer les slides pour l'infinite loop
        if (this.config.infinite !== false) {
            this.setupInfiniteLoop();
        }
        
        // Configurer les styles
        this.setupStyles();
        
        // Attacher les événements
        this.bindEvents();
        
        // Position initiale
        this.goToSlide(0, false);
        
        this.log('Carrousel initialisé avec succès');
    }
    
    /**
     * 🔄 Configurer l'infinite loop
     */
    setupInfiniteLoop() {
        // Cloner les slides du début à la fin
        for (let i = 0; i < this.cardsPerView; i++) {
            const clone = this.slides[i].cloneNode(true);
            clone.classList.add('sisme-carousel__slide--clone');
            this.track.appendChild(clone);
        }
        
        // Cloner les slides de la fin au début
        for (let i = this.totalSlides - this.cardsPerView; i < this.totalSlides; i++) {
            if (i >= 0) {
                const clone = this.slides[i].cloneNode(true);
                clone.classList.add('sisme-carousel__slide--clone');
                this.track.insertBefore(clone, this.track.firstChild);
            }
        }
        
        // Mettre à jour les références
        this.allSlides = this.track.querySelectorAll('.sisme-carousel__slide');
        this.cloneOffset = this.cardsPerView;
        
        // Position initiale (après les clones du début)
        this.currentIndex = this.cloneOffset;
    }
    
    /**
     * 🎨 Configurer les styles CSS
     */
    setupStyles() {
        // Largeur des slides
        const slideWidth = `${100 / this.cardsPerView}%`;
        this.allSlides = this.allSlides || this.slides;
        
        this.allSlides.forEach(slide => {
            slide.style.flex = `0 0 ${slideWidth}`;
            slide.style.maxWidth = slideWidth;
        });
        
        // Largeur totale du track
        const totalWidth = this.allSlides.length * (100 / this.cardsPerView);
        this.track.style.width = `${totalWidth}%`;
        
        // Position initiale
        if (this.cloneOffset) {
            const initialTransform = -(this.cloneOffset * (100 / this.cardsPerView));
            this.track.style.transform = `translateX(${initialTransform}%)`;
        }
    }
    
    /**
     * 🎣 Attacher les événements
     */
    bindEvents() {
        // Navigation boutons
        if (this.prevBtn && this.nextBtn) {
            this.prevBtn.addEventListener('click', () => this.goToPrevious());
            this.nextBtn.addEventListener('click', () => this.goToNext());
        }
        
        // Pagination dots
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToPage(index));
        });
        
        // Touch/swipe
        if (this.config.touchEnabled !== false) {
            this.bindTouchEvents();
        }
        
        // Responsive
        window.addEventListener('resize', this.debounce(() => this.handleResize(), 250));
    }
    
    /**
     * 👆 Événements tactiles
     */
    bindTouchEvents() {
        this.track.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
        this.track.addEventListener('touchmove', (e) => this.handleTouchMove(e), { passive: true });
        this.track.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });
        
        // Mouse events pour desktop
        this.track.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.track.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.track.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.track.addEventListener('mouseleave', () => this.isMouseDown = false);
    }
    
    /**
     * ⬅️ Aller à la diapositive précédente
     */
    goToPrevious() {
        if (this.isAnimating) return;
        
        if (this.config.infinite !== false) {
            this.currentIndex--;
            this.goToSlide(this.currentIndex, true);
        } else {
            const newIndex = Math.max(0, this.currentIndex - this.cardsPerView);
            this.goToSlide(newIndex, true);
        }
    }
    
    /**
     * ➡️ Aller à la diapositive suivante
     */
    goToNext() {
        if (this.isAnimating) return;
        
        if (this.config.infinite !== false) {
            this.currentIndex++;
            this.goToSlide(this.currentIndex, true);
        } else {
            const maxIndex = this.totalSlides - this.cardsPerView;
            const newIndex = Math.min(maxIndex, this.currentIndex + this.cardsPerView);
            this.goToSlide(newIndex, true);
        }
    }
    
    /**
     * 📄 Aller à une page spécifique
     */
    goToPage(pageIndex) {
        if (this.isAnimating) return;
        
        const slideIndex = pageIndex * this.cardsPerView;
        
        if (this.config.infinite !== false) {
            this.currentIndex = slideIndex + this.cloneOffset;
        } else {
            this.currentIndex = slideIndex;
        }
        
        this.goToSlide(this.currentIndex, true);
    }
    
    /**
     * 🎯 Aller à une diapositive spécifique
     */
    goToSlide(index, animate = true) {
        if (this.isAnimating && animate) return;
        
        this.isAnimating = animate;
        
        // Calculer la position
        const translateX = -(index * (100 / this.cardsPerView));
        
        // Appliquer la transformation
        if (animate) {
            this.track.style.transition = `transform ${this.options.animationDuration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        } else {
            this.track.style.transition = 'none';
        }
        
        this.track.style.transform = `translateX(${translateX}%)`;
        
        // Gestion infinite loop
        if (animate && this.config.infinite !== false) {
            setTimeout(() => {
                this.handleInfiniteLoop();
                this.isAnimating = false;
            }, this.options.animationDuration);
        } else {
            this.isAnimating = false;
        }
        
        // Mettre à jour l'interface
        this.updateUI();
    }
    
    /**
     * 🔄 Gérer le bouclage infini
     */
    handleInfiniteLoop() {
        let realIndex = this.currentIndex;
        
        // Si on dépasse à droite
        if (this.currentIndex >= this.totalSlides + this.cloneOffset) {
            realIndex = this.cloneOffset;
        }
        // Si on dépasse à gauche
        else if (this.currentIndex < this.cloneOffset) {
            realIndex = this.totalSlides + this.cloneOffset - this.cardsPerView;
        }
        
        // Repositionner sans animation si nécessaire
        if (realIndex !== this.currentIndex) {
            this.currentIndex = realIndex;
            this.track.style.transition = 'none';
            const translateX = -(this.currentIndex * (100 / this.cardsPerView));
            this.track.style.transform = `translateX(${translateX}%)`;
            
            // Forcer le reflow
            this.track.offsetHeight;
        }
    }
    
    /**
     * 🔄 Mettre à jour l'interface (dots)
     */
    updateUI() {
        if (!this.dots.length) return;
        
        // Calculer la page actuelle
        let actualIndex = this.currentIndex;
        if (this.config.infinite !== false) {
            actualIndex = this.currentIndex - this.cloneOffset;
        }
        
        const currentPage = Math.floor(actualIndex / this.cardsPerView);
        
        // Mettre à jour les dots
        this.dots.forEach((dot, index) => {
            const isActive = index === currentPage;
            dot.classList.toggle('active', isActive);
            dot.setAttribute('aria-selected', isActive);
        });
    }
    
    /**
     * 👆 Gestion touch start
     */
    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.isDragging = true;
    }
    
    /**
     * 👆 Gestion touch move
     */
    handleTouchMove(e) {
        if (!this.isDragging) return;
        this.touchEndX = e.touches[0].clientX;
    }
    
    /**
     * 👆 Gestion touch end
     */
    handleTouchEnd(e) {
        if (!this.isDragging) return;
        this.isDragging = false;
        
        const swipeDistance = this.touchStartX - this.touchEndX;
        const threshold = this.options.swipeThreshold;
        
        if (Math.abs(swipeDistance) > threshold) {
            if (swipeDistance > 0) {
                this.goToNext();
            } else {
                this.goToPrevious();
            }
        }
    }
    
    /**
     * 🖱️ Gestion mouse events (desktop drag)
     */
    handleMouseDown(e) {
        this.isMouseDown = true;
        this.touchStartX = e.clientX;
        this.track.style.cursor = 'grabbing';
        e.preventDefault();
    }
    
    handleMouseMove(e) {
        if (!this.isMouseDown) return;
        this.touchEndX = e.clientX;
    }
    
    handleMouseUp(e) {
        if (!this.isMouseDown) return;
        this.isMouseDown = false;
        this.track.style.cursor = 'grab';
        
        const swipeDistance = this.touchStartX - this.touchEndX;
        const threshold = this.options.swipeThreshold;
        
        if (Math.abs(swipeDistance) > threshold) {
            if (swipeDistance > 0) {
                this.goToNext();
            } else {
                this.goToPrevious();
            }
        }
    }
    
    /**
     * 📱 Gérer le redimensionnement
     */
    handleResize() {
        this.setupStyles();
        this.goToSlide(this.currentIndex, false);
    }
    
    /**
     * 🛠️ Utilitaire debounce
     */
    debounce(func, wait) {
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
    
    /**
     * 📝 Logging pour debug
     */
    log(...args) {
        if (window.sismeCarousel && window.sismeCarousel.debug) {
            console.log('[Sisme Carousel]', ...args);
        }
    }
    
    logError(...args) {
        console.error('[Sisme Carousel]', ...args);
    }
}

/**
 * 🚀 Auto-initialisation des carrousels
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les carrousels trouvés
    const carousels = document.querySelectorAll('.sisme-cards-carousel');
    
    if (carousels.length > 0) {
        console.log(`[Sisme Carousel] ${carousels.length} carrousel(s) trouvé(s)`);
        
        carousels.forEach((carousel, index) => {
            try {
                new SismeCarousel(carousel);
                console.log(`[Sisme Carousel] Carrousel ${index + 1} initialisé`);
            } catch (error) {
                console.error(`[Sisme Carousel] Erreur initialisation carrousel ${index + 1}:`, error);
            }
        });
    }
});

/**
 * 🔄 Support pour chargement dynamique de contenu
 */
window.SismeCarousel = SismeCarousel;

// Fonction globale pour initialiser un carrousel spécifique
window.initSismeCarousel = function(element) {
    if (element && element.classList.contains('sisme-cards-carousel')) {
        return new SismeCarousel(element);
    }
    return null;
};