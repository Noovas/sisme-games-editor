/**
 * File: /sisme-games-editor/assets/js/tooltip.js
 * Système de Tooltip Gaming - Instantané et Intelligent
 */

class SismeTooltip {
    constructor() {
        this.tooltip = null;
        this.currentElement = null;
        this.showDelay = 0; // INSTANTANÉ
        this.hideDelay = 0; // INSTANTANÉ
        this.showTimer = null;
        this.hideTimer = null;
        
        this.init();
    }
    
    init() {
        // Créer le tooltip
        this.createTooltip();
        
        // Écouter les événements sur tous les éléments avec data-sisme-tooltip
        this.bindEvents();
        
        // Écouter les nouveaux éléments ajoutés dynamiquement
        this.observeDOM();
    }
    
    createTooltip() {
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'sisme-tooltip';
        this.tooltip.style.position = 'absolute';
        this.tooltip.style.zIndex = '9999';
        document.body.appendChild(this.tooltip);
    }
    
    bindEvents() {
        // Utiliser la délégation d'événements pour les éléments dynamiques
        document.addEventListener('mouseenter', (e) => {
            const element = e.target.closest('[data-sisme-tooltip]');
            if (element) {
                this.handleMouseEnter(element, e);
            }
        }, true);
        
        document.addEventListener('mouseleave', (e) => {
            const element = e.target.closest('[data-sisme-tooltip]');
            if (element) {
                this.handleMouseLeave(element, e);
            }
        }, true);
        
        document.addEventListener('mousemove', (e) => {
            if (this.currentElement) {
                this.updatePosition(e);
            }
        });
        
        // Masquer au scroll
        document.addEventListener('scroll', () => {
            this.hide();
        }, true);
        
        // Masquer au redimensionnement
        window.addEventListener('resize', () => {
            this.hide();
        });
    }
    
    observeDOM() {
        // Observer les changements DOM pour les éléments ajoutés dynamiquement
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    // Les événements sont déjà gérés par délégation, rien à faire
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    handleMouseEnter(element, event) {
        // Annuler le timer de masquage
        if (this.hideTimer) {
            clearTimeout(this.hideTimer);
            this.hideTimer = null;
        }
        
        // Si c'est le même élément, ne pas relancer
        if (this.currentElement === element) {
            return;
        }
        
        this.currentElement = element;
        
        // Affichage INSTANTANÉ
        this.show(element, event);
    }
    
    handleMouseLeave(element, event) {
        // Annuler le timer d'affichage
        if (this.showTimer) {
            clearTimeout(this.showTimer);
            this.showTimer = null;
        }
        
        // Si on sort de l'élément actuel, masquer immédiatement
        if (this.currentElement === element) {
            this.hide();
        }
    }
    
    show(element, event) {
        const tooltipText = element.dataset.sismeTooltip;
        const tooltipType = element.dataset.sismeTooltipType || 'default';
        const tooltipIcon = element.dataset.sismeTooltipIcon;
        
        if (!tooltipText) return;
        
        // Supprimer l'attribut title pour éviter les conflits
        if (element.hasAttribute('title')) {
            element.setAttribute('data-original-title', element.getAttribute('title'));
            element.removeAttribute('title');
        }
        
        // Configurer le contenu
        this.tooltip.className = `sisme-tooltip sisme-tooltip--${tooltipType}`;
        
        if (tooltipIcon) {
            this.tooltip.className += ' sisme-tooltip--icon';
            this.tooltip.innerHTML = `
                <span class="sisme-tooltip-icon">${tooltipIcon}</span>
                <span>${tooltipText}</span>
            `;
        } else {
            this.tooltip.textContent = tooltipText;
        }
        
        // Gérer les tooltips multi-lignes
        if (tooltipText.length > 50 || tooltipText.includes('\n')) {
            this.tooltip.className += ' sisme-tooltip--multiline';
        }
        
        // Positionner et afficher
        this.updatePosition(event);
        this.tooltip.classList.add('sisme-tooltip--visible');
    }
    
    updatePosition(event) {
        if (!this.tooltip || !this.tooltip.classList.contains('sisme-tooltip--visible')) {
            return;
        }
        
        const mouseX = event.clientX;
        const mouseY = event.clientY;
        
        // Force le recalcul de la taille
        this.tooltip.style.visibility = 'hidden';
        this.tooltip.style.display = 'block';
        
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Remettre visible
        this.tooltip.style.visibility = 'visible';
        
        // Position de base : à droite et légèrement en bas de la souris
        let x = mouseX + 15;
        let y = mouseY + 10;
        
        // Ajuster horizontalement si déborde à droite
        if (x + tooltipRect.width > viewportWidth - 20) {
            x = mouseX - tooltipRect.width - 15; // À gauche de la souris
        }
        
        // Ajuster horizontalement si déborde à gauche
        if (x < 20) {
            x = 20;
        }
        
        // Ajuster verticalement si déborde en bas
        if (y + tooltipRect.height > viewportHeight - 20) {
            y = mouseY - tooltipRect.height - 10; // Au-dessus de la souris
            this.tooltip.classList.remove('sisme-tooltip--bottom');
            this.tooltip.classList.add('sisme-tooltip--top');
        } else {
            this.tooltip.classList.remove('sisme-tooltip--top');
            this.tooltip.classList.add('sisme-tooltip--bottom');
        }
        
        // Ajuster verticalement si déborde en haut
        if (y < 20) {
            y = 20;
        }
        
        // Appliquer la position avec transformation pour éviter le flicker
        this.tooltip.style.transform = `translate(${x}px, ${y}px)`;
        this.tooltip.style.left = '0px';
        this.tooltip.style.top = '0px';
    }
    
    hide() {
        if (this.tooltip) {
            this.tooltip.classList.remove('sisme-tooltip--visible');
        }
        
        // Restaurer l'attribut title si nécessaire
        if (this.currentElement && this.currentElement.hasAttribute('data-original-title')) {
            this.currentElement.setAttribute('title', this.currentElement.getAttribute('data-original-title'));
            this.currentElement.removeAttribute('data-original-title');
        }
        
        this.currentElement = null;
        
        // Nettoyer les timers
        if (this.showTimer) {
            clearTimeout(this.showTimer);
            this.showTimer = null;
        }
        if (this.hideTimer) {
            clearTimeout(this.hideTimer);
            this.hideTimer = null;
        }
    }
    
    // Méthodes publiques pour l'API
    static show(element, text, type = 'default', icon = null) {
        element.setAttribute('data-sisme-tooltip', text);
        if (type !== 'default') element.setAttribute('data-sisme-tooltip-type', type);
        if (icon) element.setAttribute('data-sisme-tooltip-icon', icon);
        
        // Déclencher l'affichage immédiatement
        const event = new MouseEvent('mouseenter', { bubbles: true });
        element.dispatchEvent(event);
    }
    
    static hide(element) {
        if (window.sismeTooltip && window.sismeTooltip.currentElement === element) {
            window.sismeTooltip.hide();
        }
    }
    
    static success(element, text) {
        SismeTooltip.show(element, text, 'success', '✅');
    }
    
    static error(element, text) {
        SismeTooltip.show(element, text, 'error', '❌');
    }
    
    static info(element, text) {
        SismeTooltip.show(element, text, 'info', 'ℹ️');
    }
}

// Initialiser automatiquement quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.sismeTooltip = new SismeTooltip();
});

// Initialiser immédiatement si le DOM est déjà prêt
if (document.readyState === 'loading') {
    // DOM pas encore chargé
} else {
    // DOM déjà chargé
    window.sismeTooltip = new SismeTooltip();
}