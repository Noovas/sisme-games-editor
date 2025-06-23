class SismeCarousel {
    constructor(element) {
        this.carousel = element;
        this.config = JSON.parse(element.dataset.carouselConfig || '{}');
        
        // Propriétés existantes
        this.cardsPerView = this.config.cardsPerView || 3;
        this.totalCards = this.config.totalCards || 0;
        this.totalPages = this.config.totalPages || 1;
        this.currentPage = 0;
        this.maxPage = this.totalPages - 1;
        
        // NOUVELLES PROPRIÉTÉS INFINITE LOOP
        this.isInfinite = this.config.infinite || false;
        this.isTransitioning = false;
        this.currentIndex = 0; // Index réel (différent de currentPage en infinite)
        
        // Éléments DOM
        this.container = element.querySelector('.sisme-carousel__container');
        this.track = element.querySelector('.sisme-carousel__track');
        this.slides = element.querySelectorAll('.sisme-carousel__slide');
        this.prevBtn = element.querySelector('.sisme-carousel__btn--prev');
        this.nextBtn = element.querySelector('.sisme-carousel__btn--next');
        this.paginationContainer = element.querySelector('.sisme-carousel__pagination');
        
        // CALCULS INFINITE LOOP
        if (this.isInfinite) {
            this.originalSlides = element.querySelectorAll('.sisme-carousel__slide--original');
            this.totalOriginalCards = this.originalSlides.length;
            this.cloneOffset = this.cardsPerView; // Nombre de clones au début
            this.currentIndex = this.cloneOffset; // Commencer après les clones de fin
            
            // Pas de pagination en infinite
            if (this.paginationContainer) {
                this.paginationContainer.style.display = 'none';
            }
        }
        
        this.init();
    }
    
    init() {
        if (this.isInfinite) {
            this.initInfiniteLoop();
        } else {
            this.initNormalCarousel();
        }
        
        this.bindEvents();
        this.updateDisplay();
        
        // Debug
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log('🎠 Carrousel initialisé:', {
                cardsPerView: this.cardsPerView,
                totalCards: this.totalCards,
                infinite: this.isInfinite,
                currentIndex: this.currentIndex
            });
        }
    }
    
    initInfiniteLoop() {
        // Positionner au début des vraies cartes (après les clones de fin)
        const initialTransform = -(this.currentIndex * (100 / this.cardsPerView));
        this.track.style.transform = `translateX(${initialTransform}%)`;
        this.track.style.transition = 'none'; // Pas de transition au démarrage
        
        // Forcer le reflow puis remettre la transition
        this.track.offsetHeight;
        this.track.style.transition = 'transform 0.5s ease';
    }
    
    initNormalCarousel() {
        // Logique existante pour carrousel normal
        this.createPagination();
    }
    
    bindEvents() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.goToPrevious());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.goToNext());
        }
        
        // Touch/swipe events
        this.bindTouchEvents();
    }
    
    goToPrevious() {
        if (this.isTransitioning) return;
        
        if (this.isInfinite) {
            this.goToPreviousInfinite();
        } else {
            this.goToPreviousNormal();
        }
    }
    
    goToNext() {
        if (this.isTransitioning) return;
        
        if (this.isInfinite) {
            this.goToNextInfinite();
        } else {
            this.goToNextNormal();
        }
    }
    
    // ========================================
    // LOGIQUE INFINITE LOOP
    // ========================================
    
    goToPreviousInfinite() {
        this.isTransitioning = true;
        this.currentIndex--;
        
        this.updateTransform();
        
        // Vérifier si on doit faire le saut magique
        setTimeout(() => {
            this.checkInfiniteLoop();
            this.isTransitioning = false;
        }, 500); // Durée de la transition CSS
    }
    
    goToNextInfinite() {
        this.isTransitioning = true;
        this.currentIndex++;
        
        this.updateTransform();
        
        // Vérifier si on doit faire le saut magique
        setTimeout(() => {
            this.checkInfiniteLoop();
            this.isTransitioning = false;
        }, 500); // Durée de la transition CSS
    }
    
    checkInfiniteLoop() {
        const totalSlides = this.slides.length;
        const lastOriginalIndex = this.cloneOffset + this.totalOriginalCards - 1;
        
        // Si on est sur les clones de début (à droite), revenir au début des originales
        if (this.currentIndex >= this.cloneOffset + this.totalOriginalCards) {
            this.currentIndex = this.cloneOffset;
            this.jumpToPosition(this.currentIndex);
        }
        
        // Si on est sur les clones de fin (à gauche), revenir à la fin des originales  
        if (this.currentIndex < this.cloneOffset) {
            this.currentIndex = lastOriginalIndex;
            this.jumpToPosition(this.currentIndex);
        }
    }
    
    jumpToPosition(index) {
        // Saut instantané sans transition
        this.track.style.transition = 'none';
        const transform = -(index * (100 / this.cardsPerView));
        this.track.style.transform = `translateX(${transform}%)`;
        
        // Forcer le reflow puis remettre la transition
        this.track.offsetHeight;
        this.track.style.transition = 'transform 0.5s ease';
        
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log(`🔄 Saut infinite: index ${index}, transform ${transform}%`);
        }
    }
    
    updateTransform() {
        const transform = -(this.currentIndex * (100 / this.cardsPerView));
        this.track.style.transform = `translateX(${transform}%)`;
        
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log(`➡️ Move: index ${this.currentIndex}, transform ${transform}%`);
        }
    }
    
    // ========================================
    // LOGIQUE NORMALE
    // ========================================
    
    goToPreviousNormal() {
        if (this.currentPage > 0) {
            this.currentPage--;
            this.updateCarousel();
        }
    }
    
    goToNextNormal() {
        if (this.currentPage < this.maxPage) {
            this.currentPage++;
            this.updateCarousel();
        }
    }
    
    updateCarousel() {
        // Logique existante pour carrousel normal
        const translateXPercent = -(this.currentPage * 100);
        this.track.style.transform = `translateX(${translateXPercent}%)`;
        
        this.updatePagination();
        this.updateButtons();
    }
    
    updateDisplay() {
        if (this.isInfinite) {
            // En infinite, pas de pagination, boutons toujours actifs
            if (this.prevBtn) this.prevBtn.disabled = false;
            if (this.nextBtn) this.nextBtn.disabled = false;
        } else {
            this.updateButtons();
            this.updatePagination();
        }
    }
    
    updateButtons() {
        if (!this.isInfinite) {
            // Logique existante pour carrousel normal
            if (this.prevBtn) {
                this.prevBtn.disabled = this.currentPage === 0;
            }
            if (this.nextBtn) {
                this.nextBtn.disabled = this.currentPage === this.maxPage;
            }
        }
        // En infinite, les boutons restent toujours actifs
    }
    
    createPagination() {
        if (!this.paginationContainer || this.isInfinite) return;
        
        this.paginationContainer.innerHTML = '';
        
        for (let i = 0; i < this.totalPages; i++) {
            const dot = document.createElement('button');
            dot.className = 'sisme-carousel__dot';
            dot.setAttribute('aria-label', `Page ${i + 1}`);
            dot.setAttribute('type', 'button');
            
            if (i === this.currentPage) {
                dot.classList.add('active');
                dot.setAttribute('aria-selected', 'true');
            } else {
                dot.setAttribute('aria-selected', 'false');
            }
            
            dot.addEventListener('click', () => this.goToPage(i));
            this.paginationContainer.appendChild(dot);
        }
        
        this.dots = this.paginationContainer.querySelectorAll('.sisme-carousel__dot');
    }
    
    updatePagination() {
        if (!this.dots || this.isInfinite) return;
        
        this.dots.forEach((dot, index) => {
            if (index === this.currentPage) {
                dot.classList.add('active');
                dot.setAttribute('aria-selected', 'true');
            } else {
                dot.classList.remove('active');
                dot.setAttribute('aria-selected', 'false');
            }
        });
    }
    
    goToPage(pageIndex) {
        if (this.isInfinite) return; // Pas de pagination en infinite
        
        if (pageIndex >= 0 && pageIndex < this.totalPages) {
            this.currentPage = pageIndex;
            this.updateCarousel();
        }
    }
    
    bindTouchEvents() {
        // Touch events pour mobile (à implémenter si besoin)
        // Fonctionne pour infinite et normal
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.sisme-cards-carousel');
    carousels.forEach(carousel => {
        new SismeCarousel(carousel);
    });
});