/**
 * Carrousel Modulable 2-5 Cartes - JavaScript
 * Version qui respecte le cards_per_view du shortcode
 */

class SismeCarrouselModulable {
    constructor(element) {
        this.carousel = element;
        this.track = element.querySelector('.sisme-carousel__track');
        this.slides = element.querySelectorAll('.sisme-carousel__slide');
        this.prevBtn = element.querySelector('.sisme-carousel__btn--prev');
        this.nextBtn = element.querySelector('.sisme-carousel__btn--next');
        this.paginationContainer = element.querySelector('.sisme-carousel__pagination');
        
        // Configuration depuis l'attribut data
        this.cardsPerViewDesktop = parseInt(this.carousel.dataset.cardsPerView) || 3;
        this.currentPage = 0;
        
        // Calculer selon responsive
        this.updateCardsPerView();
        
        // Debug
        const isDebug = this.carousel.classList.contains('sisme-cards-carousel--debug') || 
                       (typeof WP_DEBUG !== 'undefined' && WP_DEBUG);
        
        if (isDebug) {
            console.log('🎠 Carrousel Modulable initialisé:', {
                cardsPerViewDesktop: this.cardsPerViewDesktop,
                cardsPerViewActuel: this.cardsPerView,
                totalSlides: this.totalSlides,
                totalPages: this.totalPages
            });
        }
        
        this.init();
    }
    
    updateCardsPerView() {
        const width = window.innerWidth;
        
        // Responsive intelligent selon le nombre de cartes demandé
        if (width <= 480) {
            // Très petit mobile : toujours 1 carte
            this.cardsPerView = 1;
            this.cardsPerPage = 1;
        } else if (width <= 768) {
            // Mobile : maximum 2 cartes ou 1 si c'était 3+
            if (this.cardsPerViewDesktop >= 3) {
                this.cardsPerView = 1;
                this.cardsPerPage = 1;
            } else {
                this.cardsPerView = 2;
                this.cardsPerPage = 2;
            }
        } else if (width <= 1024) {
            // Tablette : adapter selon desktop
            if (this.cardsPerViewDesktop === 5) {
                this.cardsPerView = 3;
                this.cardsPerPage = 3;
            } else if (this.cardsPerViewDesktop === 4) {
                this.cardsPerView = 2;
                this.cardsPerPage = 2;
            } else {
                this.cardsPerView = this.cardsPerViewDesktop;
                this.cardsPerPage = this.cardsPerViewDesktop;
            }
        } else {
            // Desktop : utiliser la valeur demandée
            this.cardsPerView = this.cardsPerViewDesktop;
            this.cardsPerPage = this.cardsPerViewDesktop;
        }
        
        this.totalSlides = this.slides.length;
        this.totalPages = Math.ceil(this.totalSlides / this.cardsPerPage);
        this.maxPage = this.totalPages - 1;
    }
    
    init() {
        // Générer la pagination
        this.generatePagination();
        
        // Events
        if (this.prevBtn && this.nextBtn) {
            this.prevBtn.addEventListener('click', () => this.goToPreviousPage());
            this.nextBtn.addEventListener('click', () => this.goToNextPage());
        }
        
        // Responsive
        window.addEventListener('resize', this.debounce(() => {
            this.updateCardsPerView();
            this.generatePagination();
            this.currentPage = Math.min(this.currentPage, this.maxPage);
            this.updateCarousel();
        }, 250));
        
        // Initialiser l'affichage
        this.updateCarousel();
    }
    
    generatePagination() {
        if (!this.paginationContainer) return;
        
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
    
    goToPreviousPage() {
        if (this.currentPage > 0) {
            this.currentPage--;
            this.updateCarousel();
        }
    }
    
    goToNextPage() {
        if (this.currentPage < this.maxPage) {
            this.currentPage++;
            this.updateCarousel();
        }
    }
    
    goToPage(pageIndex) {
        if (pageIndex >= 0 && pageIndex < this.totalPages) {
            this.currentPage = pageIndex;
            this.updateCarousel();
        }
    }
    
    updateCarousel() {
        // CALCUL SIMPLE : chaque page = translateX de -100%
        const translateXPercent = -(this.currentPage * 100);
        
        this.track.style.transform = `translateX(${translateXPercent}%)`;
        
        // Mettre à jour l'interface
        this.updatePagination();
        this.updateButtons();
        
        // Debug
        if (this.carousel.classList.contains('sisme-cards-carousel--debug')) {
            console.log(`🎯 Page ${this.currentPage}: Transform ${translateXPercent}% | ${this.cardsPerView} cartes visibles`);
        }
    }
    
    updatePagination() {
        if (!this.dots) return;
        
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
    
    updateButtons() {
        if (this.prevBtn) {
            this.prevBtn.disabled = this.currentPage === 0;
        }
        
        if (this.nextBtn) {
            this.nextBtn.disabled = this.currentPage === this.maxPage;
        }
    }
    
    // Utility: Debounce pour le resize
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
}

// Auto-initialisation pour WordPress
document.addEventListener('DOMContentLoaded', () => {
    // Attendre un peu pour être sûr que le DOM est prêt
    setTimeout(() => {
        const carousels = document.querySelectorAll('.sisme-cards-carousel');
        
        carousels.forEach(carousel => {
            // Éviter la double-initialisation
            if (!carousel.hasAttribute('data-carousel-initialized')) {
                carousel.setAttribute('data-carousel-initialized', 'true');
                new SismeCarrouselModulable(carousel);
            }
        });
        
        if (carousels.length > 0) {
            console.log(`🎠 ${carousels.length} carrousel(s) modulable(s) initialisé(s)`);
        }
    }, 100);
});

// Export pour utilisation manuelle
window.SismeCarrouselModulable = SismeCarrouselModulable;

// Fonction helper pour forcer l'initialisation
window.initSismeCarrouselsModulables = function() {
    const carousels = document.querySelectorAll('.sisme-cards-carousel');
    carousels.forEach(carousel => {
        carousel.removeAttribute('data-carousel-initialized');
        new SismeCarrouselModulable(carousel);
    });
    console.log('🔄 Carrousels modulables réinitialisés manuellement');
};