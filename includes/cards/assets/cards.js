/**
 * File: /sisme-games-editor/includes/cards/assets/cards.js
 * JAVASCRIPT POUR LES CARTES INDIVIDUELLES
 * 
 * FONCTIONNALITÉS:
 * - Interactions sur les cartes
 * - Lazy loading des images
 * - Animations d'entrée
 * - Gestion des tooltips
 */

/**
 * Gestionnaire principal des cartes
 */
class SismeCards {
    constructor() {
        this.cards = [];
        this.observers = {};
        this.init();
    }
    
    /**
     * Initialisation
     */
    init() {
        this.findCards();
        this.setupLazyLoading();
        this.setupInteractions();
        this.setupAnimations();
        this.debug('Cartes initialisées', this.cards.length);
    }
    
    /**
     * Trouve toutes les cartes sur la page
     */
    findCards() {
        this.cards = Array.from(document.querySelectorAll('.sisme-game-card'));
    }
    
    /**
     * Configure le lazy loading des images
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            this.observers.images = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadCardImage(entry.target);
                        this.observers.images.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px'
            });
            
            // Observer toutes les images de cartes
            this.cards.forEach(card => {
                const image = card.querySelector('.sisme-card-image');
                if (image && image.dataset.src) {
                    this.observers.images.observe(image);
                }
            });
        } else {
            // Fallback pour les navigateurs sans IntersectionObserver
            this.cards.forEach(card => {
                const image = card.querySelector('.sisme-card-image');
                if (image && image.dataset.src) {
                    this.loadCardImage(image);
                }
            });
        }
    }
    
    /**
     * Charge une image de carte
     */
    loadCardImage(imageElement) {
        const src = imageElement.dataset.src;
        if (!src) return;
        
        const img = new Image();
        img.onload = () => {
            imageElement.style.backgroundImage = `url(${src})`;
            imageElement.classList.add('loaded');
            this.debug('Image chargée', src);
        };
        img.onerror = () => {
            imageElement.classList.add('error');
            this.debug('Erreur chargement image', src);
        };
        img.src = src;
    }
    
    /**
     * Configure les interactions
     */
    setupInteractions() {
        this.cards.forEach(card => {
            // Hover effects
            card.addEventListener('mouseenter', () => {
                this.onCardHover(card, true);
            });
            
            card.addEventListener('mouseleave', () => {
                this.onCardHover(card, false);
            });
            
            // Click tracking
            card.addEventListener('click', (e) => {
                this.onCardClick(card, e);
            });
            
            // Focus pour accessibilité
            const links = card.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('focus', () => {
                    card.classList.add('focused');
                });
                
                link.addEventListener('blur', () => {
                    card.classList.remove('focused');
                });
            });
        });
    }
    
    /**
     * Gère l'hover sur une carte
     */
    onCardHover(card, isHovering) {
        card.classList.toggle('hovered', isHovering);
        
        // Animation des éléments internes
        const image = card.querySelector('.sisme-card-image');
        const genres = card.querySelectorAll('.sisme-card-genre');
        const modes = card.querySelectorAll('.sisme-card-mode');
        
        if (isHovering) {
            // Effet parallaxe léger sur l'image
            if (image) {
                image.style.transform = 'scale(1.05)';
            }
            
            // Animation des tags
            genres.forEach((genre, index) => {
                setTimeout(() => {
                    genre.style.transform = 'translateY(-2px)';
                }, index * 50);
            });
            
            modes.forEach((mode, index) => {
                setTimeout(() => {
                    mode.style.transform = 'translateY(-2px) scale(1.05)';
                }, index * 50);
            });
        } else {
            // Retour à la normale
            if (image) {
                image.style.transform = '';
            }
            
            [...genres, ...modes].forEach(element => {
                element.style.transform = '';
            });
        }
    }
    
    /**
     * Gère le click sur une carte
     */
    onCardClick(card, event) {
        // Ne pas interférer avec les liens
        if (event.target.tagName === 'A') {
            return;
        }
        
        // Tracking analytics si disponible
        if (typeof gtag !== 'undefined') {
            const gameTitle = card.querySelector('.sisme-card-title')?.textContent;
            gtag('event', 'card_click', {
                'game_title': gameTitle,
                'card_type': card.dataset.type || 'normal'
            });
        }
        
        // Effet de ripple
        this.createRippleEffect(card, event);
    }
    
    /**
     * Crée un effet de ripple
     */
    createRippleEffect(card, event) {
        const ripple = document.createElement('span');
        ripple.className = 'sisme-card-ripple';
        
        const rect = card.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        card.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
    
    /**
     * Configure les animations d'entrée
     */
    setupAnimations() {
        if ('IntersectionObserver' in window) {
            this.observers.animations = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        this.observers.animations.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            this.cards.forEach(card => {
                this.observers.animations.observe(card);
            });
        } else {
            // Fallback : animer toutes les cartes immédiatement
            this.cards.forEach(card => {
                card.classList.add('animated');
            });
        }
    }
    
    /**
     * Rafraîchit les cartes (pour contenu AJAX)
     */
    refresh() {
        this.destroy();
        this.init();
    }
    
    /**
     * Ajoute de nouvelles cartes
     */
    addCards(newCards) {
        newCards.forEach(card => {
            this.cards.push(card);
            
            // Appliquer les interactions
            this.setupCardInteractions(card);
            
            // Lazy loading
            const image = card.querySelector('.sisme-card-image');
            if (image && image.dataset.src && this.observers.images) {
                this.observers.images.observe(image);
            }
            
            // Animations
            if (this.observers.animations) {
                this.observers.animations.observe(card);
            }
        });
    }
    
    /**
     * Détruit les observers
     */
    destroy() {
        Object.values(this.observers).forEach(observer => {
            if (observer) {
                observer.disconnect();
            }
        });
        this.observers = {};
        this.cards = [];
    }
    
    /**
     * Debug utilitaire
     */
    debug(message, data = null) {
        if (window.sismeCarousel && window.sismeCarousel.debug) {
            console.log(`[Sisme Cards] ${message}`, data || '');
        }
    }
}

/**
 * Styles CSS pour les animations
 */
const cardStyles = `
    .sisme-game-card {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.4s ease, transform 0.4s ease;
    }
    
    .sisme-game-card.animated {
        opacity: 1;
        transform: translateY(0);
    }
    
    .sisme-game-card.focused {
        outline: 2px solid var(--sisme-gaming-accent, #64ffda);
        outline-offset: 2px;
    }
    
    .sisme-card-image {
        transition: transform 0.3s ease;
    }
    
    .sisme-card-image.loaded {
        opacity: 1;
    }
    
    .sisme-card-image.error {
        background-color: var(--sisme-gaming-dark, #0d0d0d);
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>');
        background-repeat: no-repeat;
        background-position: center;
        background-size: 24px 24px;
    }
    
    .sisme-card-genre,
    .sisme-card-mode {
        transition: transform 0.2s ease;
    }
    
    .sisme-card-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        pointer-events: none;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        z-index: 1;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @media (prefers-reduced-motion: reduce) {
        .sisme-game-card,
        .sisme-card-image,
        .sisme-card-genre,
        .sisme-card-mode {
            transition: none !important;
        }
        
        .sisme-card-ripple {
            display: none !important;
        }
    }
`;

/**
 * Injection des styles
 */
function injectCardStyles() {
    const styleId = 'sisme-cards-dynamic-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = cardStyles;
        document.head.appendChild(style);
    }
}

/**
 * Initialisation automatique
 */
let sismeCardsInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    injectCardStyles();
    sismeCardsInstance = new SismeCards();
    
    // Exposer pour usage externe
    window.sismeCards = sismeCardsInstance;
    
    // Fonction utilitaire pour recharger
    window.sismeReloadCards = function() {
        if (sismeCardsInstance) {
            sismeCardsInstance.refresh();
        }
    };
});

// Export de la classe pour usage externe
window.SismeCards = SismeCards;