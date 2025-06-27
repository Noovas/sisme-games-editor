/**
 * File: /sisme-games-editor/assets/js/frontend-tooltip.js
 * Système de tooltip frontend global - Version avec classes prédéfinies
 * Inspecte uniquement les classes spécifiques au plugin Sisme Games Editor
 */

class SismeFrontendTooltip {
    constructor() {
        this.tooltip = null;
        
        // 🎯 Classes spécifiques Sisme Games Editor qui peuvent avoir des tooltips
        this.allowedClasses = [
            'sisme-badge-platform',
            'sisme-store-icon',
            'sisme-tooltip-enabled',
            'sisme-action-btn',
            'sisme-info-icon',
            'sisme-disabled'
        ];
        
        this.init();
    }
    
    init() {
        // Créer le tooltip au chargement
        this.createTooltipSystem();
        
        // Auto-initialiser pour les éléments avec classes autorisées
        this.autoInitializeTooltips();
    }
    
    /**
     * Créer l'élément tooltip (extrait de hero-section-module.php)
     */
    createTooltipSystem() {
        if (document.getElementById("sismeTooltip")) {
            this.tooltip = document.getElementById("sismeTooltip");
            return;
        }
        
        const tooltip = document.createElement("div");
        tooltip.id = "sismeTooltip";
        tooltip.style.cssText = `
            position: absolute;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: #e2e8f0;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.3;
            pointer-events: none;
            z-index: 9999;
            opacity: 0;
            transform: translateY(5px) scale(0.95);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4), 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            max-width: 250px;
            word-wrap: break-word;
        `;
        
        document.body.appendChild(tooltip);
        this.tooltip = tooltip;
    }
    
    /**
     * Afficher le tooltip (extrait de hero-section-module.php)
     */
    showTooltip(element, text) {
        if (!this.tooltip || !text) return;
        
        this.tooltip.textContent = text;
        
        const rect = element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        let top = rect.top - tooltipRect.height - 8;
        
        const padding = 10;
        if (left < padding) left = padding;
        if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }
        if (top < padding) {
            top = rect.bottom + 8;
        }
        
        this.tooltip.style.left = left + "px";
        this.tooltip.style.top = (top + window.scrollY) + "px";
        
        requestAnimationFrame(() => {
            this.tooltip.style.opacity = "1";
            this.tooltip.style.transform = "translateY(0) scale(1)";
        });
    }
    
    /**
     * Cacher le tooltip (extrait de hero-section-module.php)
     */
    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.style.opacity = "0";
            this.tooltip.style.transform = "translateY(5px) scale(0.95)";
        }
    }
    
    /**
     * Vérifier si un élément a une classe autorisée
     */
    hasAllowedClass(element) {
        return this.allowedClasses.some(className => 
            element.classList.contains(className)
        );
    }
    
    /**
     * Auto-initialiser UNIQUEMENT pour les éléments avec classes autorisées
     */
    autoInitializeTooltips() {
        // Fonction pour initialiser un élément
        const initElement = (element) => {
            // Seulement si classe autorisée ET title présent
            if (this.hasAllowedClass(element)) {
                const tooltipText = element.getAttribute("title");
                if (tooltipText) {
                    // Supprimer le title="" natif pour éviter les doublons
                    element.removeAttribute("title");
                    
                    // Ajouter les événements
                    element.addEventListener("mouseenter", () => {
                        this.showTooltip(element, tooltipText);
                    });
                    
                    element.addEventListener("mouseleave", () => {
                        this.hideTooltip();
                    });
                    
                    // Debug log (optionnel)
                    if (console && console.debug) {
                        console.debug(`[Sisme Tooltip] Initialized: ${element.className} - "${tooltipText}"`);
                    }
                }
            }
        };
        
        // Initialiser tous les éléments existants avec classes autorisées
        this.allowedClasses.forEach(className => {
            const elements = document.querySelectorAll(`.${className}[title]`);
            elements.forEach(initElement);
        });
        
        // Observer les nouveaux éléments ajoutés dynamiquement
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Vérifier l'élément lui-même
                        if (node.hasAttribute && node.hasAttribute("title") && this.hasAllowedClass(node)) {
                            initElement(node);
                        }
                        // Vérifier les enfants avec classes autorisées
                        this.allowedClasses.forEach(className => {
                            const childrenWithClass = node.querySelectorAll ? node.querySelectorAll(`.${className}[title]`) : [];
                            childrenWithClass.forEach(initElement);
                        });
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * API publique pour usage manuel
     */
    show(element, text) {
        this.showTooltip(element, text);
    }
    
    hide() {
        this.hideTooltip();
    }
    
    /**
     * Initialiser un élément manuellement (bypass la vérification de classe)
     */
    initElement(element, text) {
        element.addEventListener("mouseenter", () => {
            this.showTooltip(element, text);
        });
        
        element.addEventListener("mouseleave", () => {
            this.hideTooltip();
        });
    }
    
    /**
     * Ajouter une classe autorisée dynamiquement
     */
    addAllowedClass(className) {
        if (!this.allowedClasses.includes(className)) {
            this.allowedClasses.push(className);
            
            // Re-scanner les éléments avec cette nouvelle classe
            const elements = document.querySelectorAll(`.${className}[title]`);
            elements.forEach(element => {
                const tooltipText = element.getAttribute("title");
                if (tooltipText) {
                    element.removeAttribute("title");
                    
                    element.addEventListener("mouseenter", () => {
                        this.showTooltip(element, tooltipText);
                    });
                    
                    element.addEventListener("mouseleave", () => {
                        this.hideTooltip();
                    });
                }
            });
        }
    }
}

// Auto-initialisation quand le DOM est prêt
document.addEventListener("DOMContentLoaded", () => {
    window.sismeFrontendTooltip = new SismeFrontendTooltip();
    
    // API globale simplifiée
    window.sismeTooltip = {
        show: (element, text) => window.sismeFrontendTooltip.show(element, text),
        hide: () => window.sismeFrontendTooltip.hide(),
        init: (element, text) => window.sismeFrontendTooltip.initElement(element, text),
        addAllowedClass: (className) => window.sismeFrontendTooltip.addAllowedClass(className)
    };
});

// Si le DOM est déjà chargé
if (document.readyState === 'loading') {
    // DOM pas encore chargé, attendre l'événement
} else {
    // DOM déjà chargé, initialiser immédiatement
    window.sismeFrontendTooltip = new SismeFrontendTooltip();
    window.sismeTooltip = {
        show: (element, text) => window.sismeFrontendTooltip.show(element, text),
        hide: () => window.sismeFrontendTooltip.hide(),
        init: (element, text) => window.sismeFrontendTooltip.initElement(element, text),
        addAllowedClass: (className) => window.sismeFrontendTooltip.addAllowedClass(className)
    };
}