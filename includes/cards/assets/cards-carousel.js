/**
 * File: /sisme-games-editor/includes/cards/assets/cards-carousel.js
 * Carrousel avec scroll horizontal natif - Simple et efficace
 * 
 * APPROCHE SIMPLIFIÉE:
 * - Utilise le scroll horizontal natif du navigateur
 * - Boutons prev/next pour navigation
 * - Pas de momentum artificiel, juste le scroll naturel
 * - Performance optimale
 */

class SismeCarousel {
    constructor(carousel) {
        this.carousel = carousel;
        this.track = carousel.querySelector('.sisme-carousel__track');
        this.slides = Array.from(carousel.querySelectorAll('.sisme-carousel__slide'));
        this.prevBtn = carousel.querySelector('.sisme-carousel__btn--prev');
        this.nextBtn = carousel.querySelector('.sisme-carousel__btn--next');
        this.paginationContainer = carousel.querySelector('.sisme-carousel__pagination');
        
        // Configuration depuis les attributs
        this.initialCardsPerView = parseInt(carousel.dataset.cardsPerView) || 3;
        this.totalCards = parseInt(carousel.dataset.cardsCount) || this.slides.length;
        
        // Configuration responsive
        this.breakpoints = {
            mobile: 480,
            tablet: 768,
            desktop: 1024
        };
        
        // État du carrousel
        this.currentCardsPerView = this.initialCardsPerView;
        this.slideWidth = 0;
        this.totalPages = 0;
        this.currentPage = 0;
        
        this.init();
    }
    
    init() {
        this.updateCardsPerView();
        this.setupScrollContainer();
        this.calculateDimensions();
        this.createPagination();
        this.bindEvents();
        this.bindResizeEvent();
        this.updateButtonsState();
        this.updatePagination();
        
        this.debug('🎠 Carrousel scroll simple initialisé:', {
            cardsPerView: this.currentCardsPerView,
            slideWidth: this.slideWidth,
            totalCards: this.totalCards,
            totalPages: this.totalPages
        });
    }
    
    // ========================================
    // RESPONSIVE ET DIMENSIONS
    // ========================================
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width <= this.breakpoints.mobile) return 'mobile';
        if (width <= this.breakpoints.tablet) return 'tablet';
        return 'desktop';
    }
    
    updateCardsPerView() {
        const breakpoint = this.getCurrentBreakpoint();
        const initial = this.initialCardsPerView;
        
        switch (breakpoint) {
            case 'mobile':
                this.currentCardsPerView = 1;
                break;
            case 'tablet':
                if (initial >= 5) this.currentCardsPerView = 3;
                else if (initial >= 4) this.currentCardsPerView = 2;
                else this.currentCardsPerView = initial;
                break;
            case 'desktop':
            default:
                this.currentCardsPerView = initial;
                break;
        }
        
        this.debug(`📱 Responsive: ${breakpoint} → ${this.currentCardsPerView} cartes`);
    }
    
    setupScrollContainer() {
        // Configurer le container pour scroll horizontal natif
        this.track.style.overflowX = 'auto';
        this.track.style.overflowY = 'hidden';
        this.track.style.scrollBehavior = 'smooth';
        
        // Masquer la scrollbar native mais garder la fonctionnalité
        this.track.style.scrollbarWidth = 'none'; // Firefox
        this.track.style.msOverflowStyle = 'none'; // IE/Edge
        
        // Webkit (Chrome, Safari)
        const style = document.createElement('style');
        style.textContent = `
            .sisme-carousel__track::-webkit-scrollbar {
                display: none;
            }
        `;
        document.head.appendChild(style);
    }
    
    calculateDimensions() {
        // Calculer la largeur d'une slide
        let containerWidth = this.carousel.offsetWidth - 120; // -120px pour les boutons
        
        // Sur mobile, utiliser la largeur complète du container (sans marges boutons)
        if (this.getCurrentBreakpoint() === 'mobile') {
            this.slideWidth = this.carousel.offsetWidth - 20; // -20px pour un petit padding
        } else {
            // Sur desktop/tablet, augmenter la taille des cartes
            // Réduire l'espace disponible pour rendre les cartes plus larges
            containerWidth = containerWidth * 0.85; // Utiliser seulement 85% de l'espace
            this.slideWidth = containerWidth / this.currentCardsPerView;
            
            // Largeur minimum pour éviter des cartes trop petites
            const minSlideWidth = 280; // 280px minimum par carte
            if (this.slideWidth < minSlideWidth) {
                this.slideWidth = minSlideWidth;
            }
        }
        
        // Calculer le nombre de pages pour la pagination
        this.totalPages = Math.ceil(this.totalCards / this.currentCardsPerView);
        
        // Appliquer la largeur aux slides
        this.slides.forEach(slide => {
            slide.style.minWidth = `${this.slideWidth}px`;
            slide.style.maxWidth = `${this.slideWidth}px`;
        });
        
        this.debug(`📐 Dimensions: container=${containerWidth}px, slide=${this.slideWidth}px, pages=${this.totalPages}`);
    }
    
    bindResizeEvent() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.updateCardsPerView();
                this.calculateDimensions();
                this.createPagination(); // Recréer la pagination
                this.updateButtonsState();
                this.updatePagination();
            }, 150);
        });
    }
    
    // ========================================
    // ÉVÉNEMENTS ET NAVIGATION
    // ========================================
    
    bindEvents() {
        // Boutons prev/next
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.scrollToPrevious());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.scrollToNext());
        }
        
        // Écouter le scroll pour mettre à jour les boutons et pagination
        this.track.addEventListener('scroll', () => {
            this.updateButtonsState();
            this.updatePagination();
        });
        
        // Contrôler la vitesse de scroll touch sur mobile uniquement
        this.bindTouchScrollControl();
    }
    
    scrollToPrevious() {
        const scrollAmount = this.slideWidth;
        this.track.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
        
        this.debug(`⬅️ Scroll previous: -${scrollAmount}px`);
    }
    
    scrollToNext() {
        const scrollAmount = this.slideWidth;
        this.track.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
        
        this.debug(`➡️ Scroll next: +${scrollAmount}px`);
    }
    
    // ========================================
    // CONTRÔLE SCROLL MOBILE
    // ========================================
    
    bindTouchScrollControl() {
        let isScrolling = false;
        let startScrollLeft = 0;
        let startTouchX = 0;
        
        this.track.addEventListener('touchstart', (e) => {
            isScrolling = false;
            startScrollLeft = this.track.scrollLeft;
            startTouchX = e.touches[0].clientX;
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!isScrolling) {
                isScrolling = true;
                
                // Calculer le déplacement avec un facteur de réduction
                const touchX = e.touches[0].clientX;
                const deltaX = (startTouchX - touchX) * 0.7; // Réduire la sensibilité
                
                // Appliquer le scroll avec limitation
                this.track.scrollLeft = startScrollLeft + deltaX;
            }
        }, { passive: true });
        
        this.track.addEventListener('touchend', () => {
            isScrolling = false;
        }, { passive: true });
    }
    
    // ========================================
    // PAGINATION DOTS
    // ========================================
    
    createPagination() {
        if (!this.paginationContainer || this.totalPages <= 1) return;
        
        this.paginationContainer.innerHTML = '';
        
        for (let i = 0; i < this.totalPages; i++) {
            const dot = document.createElement('button');
            dot.className = 'sisme-carousel__dot';
            dot.setAttribute('aria-label', `Page ${i + 1}`);
            dot.setAttribute('type', 'button');
            
            dot.addEventListener('click', () => this.goToPage(i));
            this.paginationContainer.appendChild(dot);
        }
        
        this.debug(`🔘 Pagination créée: ${this.totalPages} dots`);
    }
    
    updatePagination() {
        if (!this.paginationContainer || this.totalPages <= 1) return;
        
        // Calculer la page courante basée sur la position de scroll
        const scrollLeft = this.track.scrollLeft;
        const pageWidth = this.slideWidth * this.currentCardsPerView;
        this.currentPage = Math.round(scrollLeft / pageWidth);
        
        // Assurer que currentPage reste dans les limites
        this.currentPage = Math.max(0, Math.min(this.currentPage, this.totalPages - 1));
        
        // Mettre à jour les dots
        const dots = this.paginationContainer.querySelectorAll('.sisme-carousel__dot');
        dots.forEach((dot, index) => {
            if (index === this.currentPage) {
                dot.classList.add('active');
                dot.setAttribute('aria-selected', 'true');
            } else {
                dot.classList.remove('active');
                dot.setAttribute('aria-selected', 'false');
            }
        });
        
        this.debug(`🎯 Page courante: ${this.currentPage + 1}/${this.totalPages}`);
    }
    
    goToPage(pageIndex) {
        if (pageIndex < 0 || pageIndex >= this.totalPages) return;
        
        const scrollPosition = pageIndex * this.slideWidth * this.currentCardsPerView;
        this.track.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });
        
        this.debug(`📄 Aller à la page: ${pageIndex + 1}`);
    }
    
    // ========================================
    // BOUTONS ET INTERFACE
    // ========================================
    
    updateButtonsState() {
        if (!this.prevBtn || !this.nextBtn) return;
        
        const scrollLeft = this.track.scrollLeft;
        const maxScroll = this.track.scrollWidth - this.track.clientWidth;
        
        // Désactiver boutons selon la position
        this.prevBtn.disabled = scrollLeft <= 0;
        this.nextBtn.disabled = scrollLeft >= maxScroll;
        
        // Classes pour le style
        this.prevBtn.classList.toggle('disabled', scrollLeft <= 0);
        this.nextBtn.classList.toggle('disabled', scrollLeft >= maxScroll);
        
        this.debug(`🎯 Scroll: ${scrollLeft.toFixed(0)}px / ${maxScroll.toFixed(0)}px`);
    }
    
    // ========================================
    // MÉTHODES PUBLIQUES
    // ========================================
    
    scrollToCard(cardIndex) {
        const scrollPosition = cardIndex * this.slideWidth;
        this.track.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });
    }
    
    getCurrentCardIndex() {
        return Math.round(this.track.scrollLeft / this.slideWidth);
    }
    
    // ========================================
    // UTILITAIRES
    // ========================================
    
    debug(message, data = null) {
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log(message, data || '');
        }
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    try {
        const carousels = document.querySelectorAll('.sisme-cards-carousel');
        carousels.forEach(carousel => {
            new SismeCarousel(carousel);
        });
        
        console.log(`✅ ${carousels.length} carrousel(s) scroll simple initialisé(s)`);
    } catch (error) {
        console.error('❌ Erreur initialisation carrousels:', error);
    }
});