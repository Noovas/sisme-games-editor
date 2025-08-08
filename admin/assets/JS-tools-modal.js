/**
 * File: JS-tools-modal.js
 * Système de modales réutilisables pour remplacer les alertes et confirmations
 */

// Attendre que le DOM soit chargé
(function() {
    'use strict';
    
class SismeModal {
    
    constructor() {
        this.modals = new Map();
        this.currentModal = null;
        this.init();
    }
    
    /**
     * Initialiser le système de modales
     */
    init() {
        // Écouter la touche Escape pour fermer les modales
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.currentModal) {
                this.hide(this.currentModal);
            }
        });
        
        // Fermer la modale en cliquant sur l'overlay
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('sisme-modal-overlay')) {
                this.hide(this.currentModal);
            }
        });
    }
    
    /**
     * Afficher une modale de succès
     */
    success(message, options = {}) {
        return this.show({
            type: 'success',
            title: options.title || '✅ Succès',
            message: message,
            icon: '✅',
            confirmText: options.confirmText || 'OK',
            showCancel: false,
            autoHide: options.autoHide || false,
            autoHideDelay: options.autoHideDelay || 3000,
            onConfirm: options.onConfirm || null
        });
    }
    
    /**
     * Afficher une modale d'erreur
     */
    error(message, options = {}) {
        return this.show({
            type: 'error',
            title: options.title || '❌ Erreur',
            message: message,
            icon: '❌',
            confirmText: options.confirmText || 'OK',
            showCancel: false,
            onConfirm: options.onConfirm || null
        });
    }
    
    /**
     * Afficher une modale d'avertissement
     */
    warning(message, options = {}) {
        return this.show({
            type: 'warning',
            title: options.title || '⚠️ Attention',
            message: message,
            icon: '⚠️',
            confirmText: options.confirmText || 'OK',
            showCancel: false,
            onConfirm: options.onConfirm || null
        });
    }
    
    /**
     * Afficher une modale d'information
     */
    info(message, options = {}) {
        return this.show({
            type: 'info',
            title: options.title || 'ℹ️ Information',
            message: message,
            icon: 'ℹ️',
            confirmText: options.confirmText || 'OK',
            showCancel: false,
            onConfirm: options.onConfirm || null
        });
    }
    
    /**
     * Afficher une modale de confirmation
     */
    confirm(message, options = {}) {
        return this.show({
            type: 'confirm',
            title: options.title || '❓ Confirmation',
            message: message,
            icon: '❓',
            confirmText: options.confirmText || 'Confirmer',
            cancelText: options.cancelText || 'Annuler',
            showCancel: true,
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null
        });
    }
    
    /**
     * Afficher une modale personnalisée
     */
    show(config) {
        return new Promise((resolve) => {
            const modalId = 'sisme-modal-' + Date.now();
            
            // Créer la modale
            const modalHtml = this.createModalHtml(modalId, config);
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const $modal = document.getElementById(modalId);
            const $overlay = $modal.querySelector('.sisme-modal-overlay');
            const $confirmBtn = $modal.querySelector('.sisme-modal-btn-confirm');
            const $cancelBtn = $modal.querySelector('.sisme-modal-btn-cancel');
            
            // Stocker la modale
            this.modals.set(modalId, {
                element: $modal,
                config: config,
                resolve: resolve
            });
            
            this.currentModal = modalId;
            
            // Gérer les clics sur les boutons
            if ($confirmBtn) {
                $confirmBtn.addEventListener('click', () => {
                    if (config.onConfirm) {
                        config.onConfirm();
                    }
                    this.hide(modalId, 'confirm');
                });
            }
            
            if ($cancelBtn) {
                $cancelBtn.addEventListener('click', () => {
                    if (config.onCancel) {
                        config.onCancel();
                    }
                    this.hide(modalId, 'cancel');
                });
            }
            
            // Afficher la modale avec animation
            requestAnimationFrame(() => {
                $modal.classList.add('sisme-modal-visible');
                
                // Focus sur le bouton principal
                const primaryBtn = $confirmBtn || $cancelBtn;
                if (primaryBtn) {
                    primaryBtn.focus();
                }
            });
            
            // Auto-hide pour les modales de succès
            if (config.autoHide) {
                setTimeout(() => {
                    this.hide(modalId, 'auto');
                }, config.autoHideDelay);
            }
        });
    }
    
    /**
     * Masquer une modale
     */
    hide(modalId, result = 'close') {
        if (!modalId || !this.modals.has(modalId)) {
            return;
        }
        
        const modal = this.modals.get(modalId);
        const $modal = modal.element;
        
        // Animation de fermeture
        $modal.classList.remove('sisme-modal-visible');
        
        setTimeout(() => {
            // Supprimer du DOM
            if ($modal.parentNode) {
                $modal.parentNode.removeChild($modal);
            }
            
            // Résoudre la promesse
            modal.resolve(result);
            
            // Nettoyer
            this.modals.delete(modalId);
            
            if (this.currentModal === modalId) {
                this.currentModal = null;
            }
        }, 300);
    }
    
    /**
     * Créer le HTML d'une modale
     */
    createModalHtml(modalId, config) {
        const typeClass = `sisme-modal-${config.type}`;
        
        return `
            <div id="${modalId}" class="sisme-modal ${typeClass}">
                <div class="sisme-modal-overlay">
                    <div class="sisme-modal-content">
                        <div class="sisme-modal-header">
                            <div class="sisme-modal-icon">${config.icon}</div>
                            <h3 class="sisme-modal-title">${config.title}</h3>
                        </div>
                        <div class="sisme-modal-body">
                            <p class="sisme-modal-message">${config.message}</p>
                        </div>
                        <div class="sisme-modal-actions">
                            ${config.showCancel ? `
                                <button type="button" class="sisme-modal-btn sisme-modal-btn-cancel">
                                    ${config.cancelText || 'Annuler'}
                                </button>
                            ` : ''}
                            <button type="button" class="sisme-modal-btn sisme-modal-btn-confirm ${typeClass}">
                                ${config.confirmText || 'OK'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialisation sécurisée
function initSismeModal() {
    // Créer l'instance globale
    const sismeModalInstance = new SismeModal();
    
    // Exposer l'instance
    window.SismeModal = sismeModalInstance;
    
    // Aliases pratiques
    window.sismeSuccess = function(message, options) {
        return sismeModalInstance.success(message, options || {});
    };
    
    window.sismeError = function(message, options) {
        return sismeModalInstance.error(message, options || {});
    };
    
    window.sismeWarning = function(message, options) {
        return sismeModalInstance.warning(message, options || {});
    };
    
    window.sismeInfo = function(message, options) {
        return sismeModalInstance.info(message, options || {});
    };
    
    window.sismeConfirm = function(message, options) {
        return sismeModalInstance.confirm(message, options || {});
    };
    
    // Debug - vérifier que tout est bien chargé
    console.log('Sisme Modal System initialized:', {
        instance: sismeModalInstance,
        functions: {
            success: typeof window.sismeSuccess,
            error: typeof window.sismeError,
            warning: typeof window.sismeWarning,
            info: typeof window.sismeInfo,
            confirm: typeof window.sismeConfirm
        }
    });
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSismeModal);
} else {
    initSismeModal();
}

})();
