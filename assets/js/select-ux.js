/**
 * File: /sisme-games-editor/assets/js/select-ux.js
 * JavaScript simple pour transformer les selects en beaux sélecteurs
 */

class SismeCustomSelect {
    constructor(selectElement) {
        this.originalSelect = selectElement;
        this.isMultiple = selectElement.hasAttribute('multiple');
        this.isOpen = false;
        this.selectedValues = [];
        
        this.init();
    }
    
    init() {
        this.createCustomSelect();
        this.bindEvents();
        this.updateFromOriginal();
    }
    
    createCustomSelect() {
        // Container principal
        this.container = document.createElement('div');
        this.container.className = 'sisme-custom-select';
        
        // Ajouter classes spécialisées basées sur le name
        const selectName = this.originalSelect.name;
        if (selectName.includes('game')) {
            this.container.classList.add('sisme-select--game');
        } else if (selectName.includes('genre')) {
            this.container.classList.add('sisme-select--genre');
        } else if (selectName.includes('mode')) {
            this.container.classList.add('sisme-select--mode');
        } else if (selectName.includes('platform')) {
            this.container.classList.add('sisme-select--platform');
        }
        
        if (this.isMultiple) {
            this.container.classList.add('sisme-select-multiple');
        }
        
        // Zone d'affichage cliquable
        this.display = document.createElement('div');
        this.display.className = 'sisme-select-display';
        this.display.setAttribute('tabindex', '0');
        
        this.textContainer = document.createElement('div');
        this.textContainer.className = 'sisme-select-text';
        
        this.arrow = document.createElement('span');
        this.arrow.className = 'sisme-select-arrow';
        this.arrow.textContent = '▼';
        
        this.display.appendChild(this.textContainer);
        this.display.appendChild(this.arrow);
        
        // Dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'sisme-select-dropdown';
        
        // Recherche (pour les listes longues)
        if (this.originalSelect.options.length > 8) {
            this.searchContainer = document.createElement('div');
            this.searchContainer.className = 'sisme-select-search';
            
            this.searchInput = document.createElement('input');
            this.searchInput.type = 'text';
            this.searchInput.placeholder = 'Rechercher...';
            
            this.searchContainer.appendChild(this.searchInput);
            this.dropdown.appendChild(this.searchContainer);
        }
        
        // Liste des options
        this.optionsList = document.createElement('div');
        this.optionsList.className = 'sisme-select-options';
        this.dropdown.appendChild(this.optionsList);
        
        // Assemblage
        this.container.appendChild(this.display);
        this.container.appendChild(this.dropdown);
        
        // Remplacer le select original
        this.originalSelect.parentNode.insertBefore(this.container, this.originalSelect);
        this.originalSelect.style.display = 'none';
        
        this.buildOptions();
    }
    
    buildOptions() {
        this.optionsList.innerHTML = '';
        this.options = [];
        
        Array.from(this.originalSelect.options).forEach((option, index) => {
            if (option.value === '') return; // Skip placeholder
            
            const optionElement = document.createElement('div');
            optionElement.className = 'sisme-select-option';
            optionElement.textContent = option.textContent;
            optionElement.dataset.value = option.value;
            optionElement.dataset.index = index;
            
            if (option.selected) {
                optionElement.classList.add('sisme-select-option--selected');
            }
            
            this.optionsList.appendChild(optionElement);
            this.options.push(optionElement);
        });
    }
    
    bindEvents() {
        // Clic sur l'affichage
        this.display.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Navigation clavier sur l'affichage
        this.display.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            }
        });
        
        // Clic sur les options
        this.optionsList.addEventListener('click', (e) => {
            if (e.target.classList.contains('sisme-select-option')) {
                this.selectOption(e.target);
            }
        });
        
        // Recherche
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterOptions(e.target.value);
            });
            
            this.searchInput.addEventListener('keydown', (e) => {
                e.stopPropagation();
            });
        }
        
        // Fermer en cliquant dehors
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
        
        // Supprimer tags (mode multiple)
        if (this.isMultiple) {
            this.textContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('sisme-select-tag-remove')) {
                    e.stopPropagation();
                    const value = e.target.closest('.sisme-select-tag').dataset.value;
                    this.removeValue(value);
                }
            });
        }
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.isOpen = true;
        this.display.classList.add('sisme-select-display--open');
        this.dropdown.classList.add('sisme-select-dropdown--open');
        
        if (this.searchInput) {
            setTimeout(() => this.searchInput.focus(), 100);
        }
    }
    
    close() {
        this.isOpen = false;
        this.display.classList.remove('sisme-select-display--open');
        this.dropdown.classList.remove('sisme-select-dropdown--open');
        
        if (this.searchInput) {
            this.searchInput.value = '';
            this.filterOptions('');
        }
    }
    
    selectOption(optionElement) {
        const value = optionElement.dataset.value;
        const index = parseInt(optionElement.dataset.index);
        
        if (this.isMultiple) {
            // Mode multiple
            if (this.selectedValues.includes(value)) {
                this.removeValue(value);
            } else {
                this.addValue(value);
            }
        } else {
            // Mode simple
            this.selectedValues = [value];
            this.originalSelect.value = value;
            this.originalSelect.options[index].selected = true;
            
            // Déclencher le change event
            this.originalSelect.dispatchEvent(new Event('change'));
            
            this.updateDisplay();
            this.updateOptionsSelection();
            this.close();
        }
    }
    
    addValue(value) {
        if (!this.selectedValues.includes(value)) {
            this.selectedValues.push(value);
            
            // Mettre à jour le select original
            Array.from(this.originalSelect.options).forEach(option => {
                if (option.value === value) {
                    option.selected = true;
                }
            });
            
            this.originalSelect.dispatchEvent(new Event('change'));
            this.updateDisplay();
            this.updateOptionsSelection();
        }
    }
    
    removeValue(value) {
        this.selectedValues = this.selectedValues.filter(v => v !== value);
        
        // Mettre à jour le select original
        Array.from(this.originalSelect.options).forEach(option => {
            if (option.value === value) {
                option.selected = false;
            }
        });
        
        this.originalSelect.dispatchEvent(new Event('change'));
        this.updateDisplay();
        this.updateOptionsSelection();
    }
    
    updateDisplay() {
        if (this.selectedValues.length === 0) {
            // Placeholder
            const placeholder = this.originalSelect.querySelector('option[value=""]');
            this.textContainer.innerHTML = placeholder ? 
                `<span class="sisme-select-text--placeholder">${placeholder.textContent}</span>` : 
                '<span class="sisme-select-text--placeholder">Sélectionnez...</span>';
        } else if (this.isMultiple) {
            // Tags pour sélection multiple
            this.textContainer.innerHTML = '';
            this.selectedValues.forEach(value => {
                const option = this.originalSelect.querySelector(`option[value="${value}"]`);
                if (option) {
                    const tag = document.createElement('span');
                    tag.className = 'sisme-select-tag';
                    tag.dataset.value = value;
                    tag.innerHTML = `
                        ${option.textContent}
                        <span class="sisme-select-tag-remove">×</span>
                    `;
                    this.textContainer.appendChild(tag);
                }
            });
        } else {
            // Sélection simple
            const value = this.selectedValues[0];
            const option = this.originalSelect.querySelector(`option[value="${value}"]`);
            this.textContainer.textContent = option ? option.textContent : '';
        }
    }
    
    updateOptionsSelection() {
        this.options.forEach(optionElement => {
            const value = optionElement.dataset.value;
            if (this.selectedValues.includes(value)) {
                optionElement.classList.add('sisme-select-option--selected');
            } else {
                optionElement.classList.remove('sisme-select-option--selected');
            }
        });
    }
    
    updateFromOriginal() {
        // Synchroniser avec l'état du select original
        this.selectedValues = [];
        Array.from(this.originalSelect.options).forEach(option => {
            if (option.selected && option.value !== '') {
                this.selectedValues.push(option.value);
            }
        });
        
        this.updateDisplay();
        this.updateOptionsSelection();
    }
    
    filterOptions(searchTerm) {
        const term = searchTerm.toLowerCase();
        
        this.options.forEach(option => {
            const text = option.textContent.toLowerCase();
            if (text.includes(term)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    // Transformer tous les selects dans les formulaires Sisme
    const selects = document.querySelectorAll('.sisme-admin-page select, .sisme-game-form-module select');
    
    selects.forEach(select => {
        // Skip si déjà transformé
        if (select.style.display === 'none') return;
        
        new SismeCustomSelect(select);
    });
    
    // Observer pour les selects ajoutés dynamiquement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    const newSelects = node.querySelectorAll && node.querySelectorAll('select');
                    if (newSelects) {
                        newSelects.forEach(select => {
                            if (select.style.display !== 'none' && 
                                (select.closest('.sisme-admin-page') || select.closest('.sisme-game-form-module'))) {
                                new SismeCustomSelect(select);
                            }
                        });
                    }
                }
            });
        });
    });
    
    // Observer les changements dans les formulaires admin
    const adminPages = document.querySelectorAll('.sisme-admin-page, .sisme-game-form-module');
    adminPages.forEach(page => {
        observer.observe(page, {
            childList: true,
            subtree: true
        });
    });
});