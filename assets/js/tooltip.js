/**
 * File: /sisme-games-editor/assets/js/tooltip.js
 * Système de Tooltip Gaming - Version CORRIGÉE et SIMPLIFIÉE
 */

class SismeTooltip {
    constructor() {
        this.tooltip = null;
        this.currentElement = null;
        
        this.init();
    }
    
    init() {
        // Créer le tooltip
        this.createTooltip();
        
        // Écouter les événements
        this.bindEvents();
    }
    
    createTooltip() {
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'sisme-tooltip';
        document.body.appendChild(this.tooltip);
    }
    
    bindEvents() {
        // Délégation d'événements pour les éléments dynamiques
        document.addEventListener('mouseenter', (e) => {
            const element = e.target.closest('[data-sisme-tooltip]');
            if (element) {
                this.show(element, e);
            }
        }, true);
        
        document.addEventListener('mouseleave', (e) => {
            const element = e.target.closest('[data-sisme-tooltip]');
            if (element && element === this.currentElement) {
                this.hide();
            }
        }, true);
        
        // Masquer au scroll
        document.addEventListener('scroll', () => {
            this.hide();
        }, true);
        
        // Masquer au redimensionnement
        window.addEventListener('resize', () => {
            this.hide();
        });
    }
    
    show(element, event) {
        const tooltipText = element.dataset.sismeTooltip;
        const tooltipType = element.dataset.sismeTooltipType || 'default';
        const tooltipIcon = element.dataset.sismeTooltipIcon;
        
        if (!tooltipText) return;
        
        this.currentElement = element;
        
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
        
        // POSITIONNEMENT SIMPLIFIÉ
        this.positionTooltip(element);
        
        // Afficher
        this.tooltip.classList.add('sisme-tooltip--visible');
    }
    
    positionTooltip(element) {
        // Récupérer la position de l'élément
        const elementRect = element.getBoundingClientRect();
        
        // Positionner temporairement pour mesurer
        this.tooltip.style.visibility = 'hidden';
        this.tooltip.style.display = 'block';
        this.tooltip.style.position = 'fixed';
        this.tooltip.style.left = '0';
        this.tooltip.style.top = '0';
        
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };
        
        // Position par défaut : au-dessus et centré
        let x = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
        let y = elementRect.top - tooltipRect.height - 10;
        
        // Ajustements horizontaux
        const padding = 15;
        if (x < padding) {
            x = padding;
        } else if (x + tooltipRect.width > viewport.width - padding) {
            x = viewport.width - tooltipRect.width - padding;
        }
        
        // Ajustement vertical : si pas de place en haut, aller en bas
        if (y < padding) {
            y = elementRect.bottom + 10;
            this.tooltip.classList.remove('sisme-tooltip--top');
            this.tooltip.classList.add('sisme-tooltip--bottom');
        } else {
            this.tooltip.classList.remove('sisme-tooltip--bottom');
            this.tooltip.classList.add('sisme-tooltip--top');
        }
        
        // Appliquer la position finale
        this.tooltip.style.left = Math.round(x) + 'px';
        this.tooltip.style.top = Math.round(y) + 'px';
        this.tooltip.style.visibility = 'visible';
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
    }
    
    // API publique
    static show(element, text, type = 'default', icon = null) {
        element.setAttribute('data-sisme-tooltip', text);
        if (type !== 'default') element.setAttribute('data-sisme-tooltip-type', type);
        if (icon) element.setAttribute('data-sisme-tooltip-icon', icon);
        
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

// Initialisation automatique
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sismeTooltip = new SismeTooltip();
    });
} else {
    window.sismeTooltip = new SismeTooltip();
}