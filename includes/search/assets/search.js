/**
 * File: /sisme-games-editor/includes/search/assets/search.js
 * MODULE SEARCH REFAIT - JavaScript
 * 
 * FONCTIONNALITÉS:
 * - Recherche en temps réel
 * - Gestion des états (loading, erreur, succès)
 * - Intégration AJAX
 * - Debouncing pour performance
 */

(function($) {
    'use strict';
    
    // Configuration globale
    const SISME_SEARCH = {
        instances: {},
        debounceDelay: 500,
        minSearchLength: 2
    };
    
    /**
     * Classe principale pour gérer une instance de recherche
     */
    class SismeSearchInstance {
        constructor(containerId, options = {}) {
            this.containerId = containerId;
            this.container = document.getElementById(containerId);
            this.options = options;
            this.currentRequest = null;
            this.debounceTimer = null;
            
            if (!this.container) {
                console.error('[Sisme Search] Container non trouvé:', containerId);
                return;
            }
            
            this.init();
        }
        
        /**
         * Initialiser l'instance
         */
        init() {
            this.bindElements();
            this.bindEvents();
            this.log('Instance initialisée');
        }
        
        /**
         * Lier les éléments DOM
         */
        bindElements() {
            this.form = this.container.querySelector('.sisme-search-form');
            this.queryInput = this.container.querySelector('input[name="query"]');
            this.genreSelect = this.container.querySelector('select[name="genre"]');
            this.statusSelect = this.container.querySelector('select[name="status"]');
            this.submitButton = this.container.querySelector('.sisme-search-button');
            this.resultsContainer = this.container.querySelector('.sisme-search-results');
            
            // Éléments du bouton
            this.buttonText = this.submitButton.querySelector('.sisme-search-button-text');
            this.buttonLoading = this.submitButton.querySelector('.sisme-search-button-loading');
        }
        
        /**
         * Lier les événements
         */
        bindEvents() {
            // Soumission du formulaire
            if (this.form) {
                this.form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.performSearch();
                });
            }
            
            // Recherche en temps réel sur le champ texte
            if (this.queryInput) {
                this.queryInput.addEventListener('input', (e) => {
                    this.handleInputChange(e);
                });
            }
            
            // Recherche sur changement de filtres
            if (this.genreSelect) {
                this.genreSelect.addEventListener('change', () => {
                    this.performSearch();
                });
            }
            
            if (this.statusSelect) {
                this.statusSelect.addEventListener('change', () => {
                    this.performSearch();
                });
            }
        }
        
        /**
         * Gérer le changement du champ de recherche
         */
        handleInputChange(event) {
            const query = event.target.value.trim();
            
            // Annuler le timer précédent
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Rechercher après un délai
            this.debounceTimer = setTimeout(() => {
                if (query.length >= SISME_SEARCH.minSearchLength || query.length === 0) {
                    this.performSearch();
                }
            }, SISME_SEARCH.debounceDelay);
        }
        
        /**
         * Effectuer la recherche
         */
        performSearch() {
            const params = this.getSearchParams();
            
            // Vérifier si une recherche est nécessaire
            if (!this.shouldPerformSearch(params)) {
                this.log('Recherche ignorée - critères insuffisants');
                return;
            }
            
            // Annuler la requête précédente
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            // Mettre à jour l'état
            this.setLoadingState(true);
            
            // Préparer les données
            const formData = new FormData();
            formData.append('action', 'sisme_search_games');
            formData.append('nonce', sismeSearch.nonce);
            
            // Ajouter les paramètres
            Object.keys(params).forEach(key => {
                formData.append(key, params[key]);
            });
            
            // Faire la requête AJAX
            this.currentRequest = $.ajax({
                url: sismeSearch.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.handleSearchSuccess(response);
                },
                error: (xhr, status, error) => {
                    this.handleSearchError(xhr, status, error);
                },
                complete: () => {
                    this.setLoadingState(false);
                    this.currentRequest = null;
                }
            });
        }
        
        /**
         * Récupérer les paramètres de recherche
         */
        getSearchParams() {
            return {
                query: this.queryInput ? this.queryInput.value.trim() : '',
                genre: this.genreSelect ? this.genreSelect.value : '',
                status: this.statusSelect ? this.statusSelect.value : '',
                columns: this.options.columns || 4,
                max_results: this.options.max_results || 12
            };
        }
        
        /**
         * Vérifier si une recherche doit être effectuée
         */
        shouldPerformSearch(params) {
            const hasQuery = params.query.length >= SISME_SEARCH.minSearchLength;
            const hasFilters = params.genre || params.status;
            
            return hasQuery || hasFilters;
        }
        
        /**
         * Gérer le succès de la recherche
         */
        handleSearchSuccess(response) {
            if (response.success) {
                this.displayResults(response.data);
                this.log('Recherche réussie', response.data);
            } else {
                this.handleSearchError(null, 'error', response.data.message);
            }
        }
        
        /**
         * Gérer l'erreur de recherche
         */
        handleSearchError(xhr, status, error) {
            if (status === 'abort') {
                this.log('Recherche annulée');
                return;
            }
            
            let errorMessage = sismeSearch.messages.error;
            
            if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage = xhr.responseJSON.data.message;
            }
            
            this.displayError(errorMessage);
            this.log('Erreur de recherche', error);
        }
        
        /**
         * Afficher les résultats
         */
        displayResults(data) {
            if (!this.resultsContainer) return;
            
            // Mettre à jour le contenu
            this.resultsContainer.innerHTML = data.html;
            
            // Animer l'apparition
            this.resultsContainer.style.opacity = '0';
            setTimeout(() => {
                this.resultsContainer.style.opacity = '1';
            }, 50);
            
            // Faire défiler vers les résultats
            this.scrollToResults();
        }
        
        /**
         * Afficher une erreur
         */
        displayError(message) {
            if (!this.resultsContainer) return;
            
            this.resultsContainer.innerHTML = `
                <div class="sisme-search-error">
                    ${message}
                </div>
            `;
        }
        
        /**
         * Définir l'état de chargement
         */
        setLoadingState(isLoading) {
            if (!this.submitButton) return;
            
            if (isLoading) {
                this.submitButton.disabled = true;
                this.submitButton.classList.add('loading');
                
                if (this.buttonText) this.buttonText.style.display = 'none';
                if (this.buttonLoading) this.buttonLoading.style.display = 'block';
            } else {
                this.submitButton.disabled = false;
                this.submitButton.classList.remove('loading');
                
                if (this.buttonText) this.buttonText.style.display = 'block';
                if (this.buttonLoading) this.buttonLoading.style.display = 'none';
            }
        }
        
        /**
         * Faire défiler vers les résultats
         */
        scrollToResults() {
            if (!this.resultsContainer) return;
            
            const rect = this.resultsContainer.getBoundingClientRect();
            const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
            
            if (!isVisible) {
                this.resultsContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
        
        /**
         * Logger pour debug
         */
        log(message, data = null) {
            if (sismeSearch.debug) {
                console.log(`[Sisme Search] ${this.containerId}: ${message}`, data);
            }
        }
        
        /**
         * Détruire l'instance
         */
        destroy() {
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            delete SISME_SEARCH.instances[this.containerId];
            this.log('Instance détruite');
        }
    }
    
    /**
     * Fonction publique pour initialiser une instance
     */
    window.sismeSearchInit = function(containerId, options = {}) {
        if (SISME_SEARCH.instances[containerId]) {
            SISME_SEARCH.instances[containerId].destroy();
        }
        
        SISME_SEARCH.instances[containerId] = new SismeSearchInstance(containerId, options);
        
        return SISME_SEARCH.instances[containerId];
    };
    
    /**
     * Fonction publique pour obtenir une instance
     */
    window.sismeSearchGet = function(containerId) {
        return SISME_SEARCH.instances[containerId] || null;
    };
    
    /**
     * Fonction publique pour détruire une instance
     */
    window.sismeSearchDestroy = function(containerId) {
        if (SISME_SEARCH.instances[containerId]) {
            SISME_SEARCH.instances[containerId].destroy();
        }
    };
    
    /**
     * Initialisation automatique au chargement de la page
     */
    $(document).ready(function() {
        // Rechercher les containers de recherche existants
        $('.sisme-search-container').each(function() {
            const containerId = $(this).attr('id');
            if (containerId) {
                sismeSearchInit(containerId);
            }
        });
    });
    
    /**
     * Nettoyage lors du déchargement de la page
     */
    $(window).on('beforeunload', function() {
        Object.keys(SISME_SEARCH.instances).forEach(containerId => {
            sismeSearchDestroy(containerId);
        });
    });
    
})(jQuery);