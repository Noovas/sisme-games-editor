/**
 * File: /sisme-games-editor/assets/js/fiche-form.js
 * JavaScript pour améliorer l'expérience de création de fiche (OPTIONNEL)
 * 
 * Fonctionnalités:
 * - Animation smooth des sections
 * - Auto-resize des textareas
 * - Indicateur de changements non sauvegardés
 * - Validation en temps réel
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ================================================
    // 💬 INITIALISATION DES TOOLTIPS
    // ================================================
    
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-sisme-tooltip]');
        tooltipElements.forEach(function(element) {
            // Utiliser le système de tooltip existant de Sisme
            if (typeof window.sismeTooltip !== 'undefined') {
                window.sismeTooltip.init(element);
            }
        });
    }
    
    // Initialiser les tooltips au chargement
    initTooltips();
    
    // Réinitialiser les tooltips quand de nouveaux éléments sont ajoutés
    function reinitTooltips() {
        setTimeout(initTooltips, 100); // Petit délai pour laisser le DOM se mettre à jour
    }
    
    // ================================================
    // 📏 AUTO-RESIZE DES TEXTAREAS
    // ================================================
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    // Appliquer auto-resize à tous les textareas
    const textareas = document.querySelectorAll('.sisme-field-textarea, #sections-container textarea');
    textareas.forEach(function(textarea) {
        // Auto-resize initial
        autoResizeTextarea(textarea);
        
        // Auto-resize à chaque input
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
    
    // ================================================
    // 💾 INDICATEUR DE CHANGEMENTS NON SAUVEGARDÉS
    // ================================================
    
    let hasUnsavedChanges = false;
    
    // Détecter les changements dans les champs
    const formFields = document.querySelectorAll('#sections-container input, #sections-container textarea');
    formFields.forEach(function(field) {
        field.addEventListener('input', function() {
            hasUnsavedChanges = true;
            updateUnsavedIndicator();
        });
    });
    
    // Réinitialiser l'indicateur quand le formulaire est soumis
    const form = document.querySelector('#sections-container').closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    }
    
    function updateUnsavedIndicator() {
        const submitButton = document.querySelector('input[type="submit"].button-primary');
        if (submitButton && hasUnsavedChanges) {
            if (!submitButton.textContent.includes('*')) {
                submitButton.value = submitButton.value + ' *';
                submitButton.title = 'Changements non sauvegardés';
            }
        }
    }
    
    // Avertir avant de quitter la page si changements non sauvegardés
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
    
    // ================================================
    // ✨ ANIMATIONS SUBTILES
    // ================================================
    
    // Animation des sections au focus
    const sectionItems = document.querySelectorAll('.sisme-section-item, #sections-container > div');
    sectionItems.forEach(function(section) {
        const inputs = section.querySelectorAll('input, textarea');
        
        inputs.forEach(function(input) {
            input.addEventListener('focus', function() {
                section.style.transform = 'scale(1.01)';
                section.style.transition = 'transform 0.2s ease';
            });
            
            input.addEventListener('blur', function() {
                section.style.transform = 'scale(1)';
            });
        });
    });
    
    // ================================================
    // 📊 COMPTEUR DE CARACTÈRES (optionnel)
    // ================================================
    
    function addCharacterCounter(textarea) {
        const counter = document.createElement('div');
        counter.className = 'sisme-char-counter';
        counter.style.cssText = `
            font-size: 0.8rem;
            color: var(--sisme-gaming-text-muted);
            text-align: right;
            margin-top: 4px;
            opacity: 0.7;
        `;
        
        function updateCounter() {
            const length = textarea.value.length;
            counter.textContent = length + ' caractères';
            
            // Changer la couleur selon la longueur
            if (length > 1000) {
                counter.style.color = '#ef4444';
            } else if (length > 500) {
                counter.style.color = '#f59e0b';
            } else {
                counter.style.color = 'var(--sisme-gaming-text-muted)';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        textarea.parentNode.appendChild(counter);
        updateCounter();
    }
    
    // Ajouter compteurs aux textareas de contenu
    const contentTextareas = document.querySelectorAll('#sections-container textarea');
    contentTextareas.forEach(addCharacterCounter);
    
    // ================================================
    // 🎨 AMÉLIORATION VISUELLE SUBTLE
    // ================================================
    
    // Effet de typing pour les placeholders (optionnel)
    function animatePlaceholder(input) {
        const originalPlaceholder = input.placeholder;
        const words = originalPlaceholder.split(' ');
        let currentWord = 0;
        
        input.addEventListener('focus', function() {
            if (this.value === '') {
                let displayText = '';
                const interval = setInterval(() => {
                    if (currentWord < words.length) {
                        displayText += (currentWord > 0 ? ' ' : '') + words[currentWord];
                        this.placeholder = displayText;
                        currentWord++;
                    } else {
                        clearInterval(interval);
                        setTimeout(() => {
                            this.placeholder = originalPlaceholder;
                            currentWord = 0;
                        }, 1000);
                    }
                }, 100);
            }
        });
    }
    
    // Appliquer l'animation aux champs titre (subtil)
    const titleInputs = document.querySelectorAll('#sections-container input[type="text"]');
    titleInputs.forEach(function(input) {
        if (input.placeholder && input.placeholder.length > 10) {
            // animatePlaceholder(input); // Décommenter si souhaité
        }
    });
    
    // ================================================
    // 📱 AMÉLIORATIONS RESPONSIVE
    // ================================================
    
    // Ajuster la hauteur minimum des textareas sur mobile
    function adjustForMobile() {
        if (window.innerWidth <= 768) {
            textareas.forEach(function(textarea) {
                textarea.style.minHeight = '100px';
            });
        }
    }
    
    adjustForMobile();
    window.addEventListener('resize', adjustForMobile);
    
    // ================================================
    // 🔍 VALIDATION EN TEMPS RÉEL (subtile)
    // ================================================
    
    function validateField(field) {
        const value = field.value.trim();
        const isTitle = field.type === 'text';
        
        // Validation très légère
        if (isTitle && value.length > 0 && value.length < 3) {
            field.style.borderColor = '#f59e0b';
            field.title = 'Le titre doit faire au moins 3 caractères';
        } else if (!isTitle && value.length > 2000) {
            field.style.borderColor = '#ef4444';
            field.title = 'Le contenu est trop long (max 2000 caractères)';
        } else {
            field.style.borderColor = '';
            field.title = '';
        }
    }
    
    // Appliquer validation aux champs
    formFields.forEach(function(field) {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
    // Validation en temps réel pour le comptage de caractères
        if (field.tagName === 'TEXTAREA') {
            field.addEventListener('input', function() {
                if (this.value.length > 1800) {
                    validateField(this);
                }
            });
        }
    });
    
    // ================================================
    // 💬 RÉINITIALISER TOOLTIPS APRÈS MODIFICATIONS
    // ================================================
    
    // Observer les changements DOM pour réinitialiser les tooltips
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Vérifier si des éléments avec tooltips ont été ajoutés
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (node.hasAttribute('data-sisme-tooltip') || node.querySelector('[data-sisme-tooltip]'))) {
                        shouldReinit = true;
                    }
                });
            }
        });
        
        if (shouldReinit) {
            reinitTooltips();
        }
    });
    
    // Observer le container des sections
    const sectionsContainer = document.getElementById('sections-container');
    if (sectionsContainer) {
        observer.observe(sectionsContainer, {
            childList: true,
            subtree: true
        });
    }
    
    console.log('🎮 Sisme Fiche Form: Scripts chargés avec succès');
});