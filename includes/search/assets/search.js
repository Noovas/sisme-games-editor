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
            console.log('Nouvelle instance créée:', containerId);
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
            this.saveInitialResults();
            this.bindEvents();
            this.log('Instance initialisée');
        }
        
        /**
         * Sauvegarder les résultats initiaux
         */
        saveInitialResults() {
            if (this.resultsContainer) {
                this.initialResultsHtml = this.resultsContainer.innerHTML;
                this.log('Résultats initiaux sauvegardés');
            }
            
            // Vérifier s'il y a des métadonnées de pagination initiale
            this.checkInitialPagination();
        }

        /**
         * Vérifier les métadonnées de pagination initiale
         */
        checkInitialPagination() {
            const paginationData = this.container.querySelector('.sisme-initial-pagination');
            if (paginationData) {
                try {
                    const data = JSON.parse(paginationData.textContent);
                    this.currentPage = data.current_page || 1;
                    this.hasMore = data.has_more || false;
                    this.lastSearchParams = {
                        query: '',
                        genre: '',
                        status: '',
                        columns: this.options.columns || 4,
                        max_results: data.max_results || 12
                    };
                    
                    if (this.hasMore) {
                        this.showLoadMoreButton();
                    }
                    
                    this.log('Pagination initiale configurée', data);
                } catch (e) {
                    this.log('Erreur lors du parsing des métadonnées initiales', e);
                }
            }
        }
        
        /**
         * Afficher le bouton charger plus
         */
        showLoadMoreButton() {
            if (this.loadMoreContainer) {
                this.loadMoreContainer.style.display = 'block';
            }
        }
        
        /**
         * Cacher le bouton charger plus
         */
        hideLoadMoreButton() {
            if (this.loadMoreContainer) {
                this.loadMoreContainer.style.display = 'none';
            }
        }
        
        /**
         * Définir l'état de chargement du bouton "charger plus"
         */
        setLoadMoreState(isLoading) {
            if (!this.loadMoreBtn) return;
            
            if (isLoading) {
                this.loadMoreBtn.disabled = true;
                if (this.loadMoreText) this.loadMoreText.style.display = 'none';
                if (this.loadMoreLoading) this.loadMoreLoading.style.display = 'block';
            } else {
                this.loadMoreBtn.disabled = false;
                if (this.loadMoreText) this.loadMoreText.style.display = 'block';
                if (this.loadMoreLoading) this.loadMoreLoading.style.display = 'none';
            }
        }
        
        /**
         * Charger plus de résultats
         */
        loadMoreResults() {
            console.log('loadMoreResults appelée !', new Date().getTime());
            if (!this.lastSearchParams || !this.hasMore) {
                this.log('Charger plus impossible:', {
                    hasParams: !!this.lastSearchParams,
                    hasMore: this.hasMore
                });
                return;
            }
            
            // Annuler la requête précédente
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            // Mettre à jour l'état du bouton charger plus
            this.setLoadMoreState(true);
            
            // Préparer les paramètres avec la page suivante
            const params = { ...this.lastSearchParams };
            params.page = this.currentPage + 1;
            params.load_more = true;
            
            this.log('Chargement page:', params.page);
            
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
                    this.handleLoadMoreSuccess(response);
                },
                error: (xhr, status, error) => {
                    this.handleSearchError(xhr, status, error);
                },
                complete: () => {
                    this.setLoadMoreState(false);
                    this.currentRequest = null;
                }
            });
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
            this.loadMoreContainer = this.container.querySelector('.sisme-search-load-more');
            this.loadMoreBtn = this.container.querySelector('.sisme-search-load-more-btn');
            
            // Éléments du bouton de recherche
            this.buttonText = this.submitButton.querySelector('.sisme-search-button-text');
            this.buttonLoading = this.submitButton.querySelector('.sisme-search-button-loading');
            
            // Éléments du bouton charger plus
            this.loadMoreText = this.loadMoreBtn ? this.loadMoreBtn.querySelector('.sisme-load-more-text') : null;
            this.loadMoreLoading = this.loadMoreBtn ? this.loadMoreBtn.querySelector('.sisme-load-more-loading') : null;
            
            // Variables de pagination
            this.currentPage = 1;
            this.hasMore = false;
            this.lastSearchParams = null;
        }
        
        /**
         * Lier les événements
         */
        bindEvents() {
            console.log('bindEvents() appelée pour container:', this.containerId);
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
                    const query = e.target.value.trim();
                    if (query.length > 0) {
                        this.handleInputChange(e);
                    }
                });
            }
            
            // Recherche sur changement de filtres
            if (this.genreSelect) {
                this.genreSelect.addEventListener('change', (e) => {
                    if (e.target.value) {
                        this.performSearch();
                    } else {
                        this.checkAndResetOrSearch();
                    }
                });
            }
            
            if (this.statusSelect) {
                this.statusSelect.addEventListener('change', (e) => {
                    if (e.target.value) {
                        this.performSearch();
                    } else {
                        this.checkAndResetOrSearch();
                    }
                });
            }
            
            // CORRECTION : Event listener pour le bouton "charger plus"
            if (this.loadMoreBtn) {
                console.log('Ajout event listener sur loadMoreBtn pour:', this.containerId);
                this.loadMoreBtn.addEventListener('click', () => {
                    this.loadMoreResults();
                });
            }
        }
        
        /**
         * Vérifier s'il faut réinitialiser ou faire une recherche
         */
        checkAndResetOrSearch() {
            const params = this.getSearchParams();
            
            if (this.shouldPerformSearch(params)) {
                // Il y a encore des critères, faire la recherche
                this.performSearch();
            } else {
                // Plus aucun critère, réinitialiser à l'affichage initial
                this.resetToInitialResults();
            }
        }
        
        /**
         * Réinitialiser aux résultats initiaux
         */
        resetToInitialResults() {
            // Récupérer le contenu initial sauvegardé
            if (!this.initialResultsHtml) {
                // Si pas sauvegardé, faire une recherche vide pour récupérer l'initial
                this.log('Réinitialisation aux résultats par défaut');
                return;
            }
            
            if (this.resultsContainer) {
                this.resultsContainer.innerHTML = this.initialResultsHtml;
                this.log('Résultats réinitialisés');
            }

            this.restoreInitialPagination();
        }

        /**
         * Restaurer les données de pagination initiales
         */
        restoreInitialPagination() {
            // Récupérer les métadonnées initiales
            const metadataScript = this.container.querySelector('.sisme-initial-pagination');
            if (metadataScript) {
                try {
                    const initialData = JSON.parse(metadataScript.textContent);
                    
                    // Restaurer les variables de pagination
                    this.currentPage = 1;
                    this.hasMore = initialData.has_more || false;
                    this.lastSearchParams = null; // Pas de recherche active
                    
                    // Gérer l'affichage du bouton "Load more"
                    if (this.hasMore) {
                        this.showLoadMoreButton();
                    } else {
                        this.hideLoadMoreButton();
                    }
                    
                    this.log('Pagination initiale restaurée', {
                        hasMore: this.hasMore,
                        totalGames: initialData.total_games
                    });
                    
                } catch (e) {
                    this.log('Erreur lors de la restauration des métadonnées initiales', e);
                }
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
            
            // CORRECTION : Sauvegarder les paramètres de la dernière recherche
            this.lastSearchParams = this.getSearchParams();
            
            // CORRECTION : Mettre à jour la pagination avec les vraies données de réponse
            this.updatePagination(response.data);
            
            this.log('Recherche réussie', response.data);
        } else {
            this.handleSearchError(null, 'error', response.data.message);
        }
    }
        
        /**
         * Gérer le succès du chargement de plus de résultats
         */
        handleLoadMoreSuccess(response) {
            if (response.success) {
                this.appendResults(response.data);
                this.updatePagination(response.data);
                this.log('Résultats supplémentaires chargés', response.data);
            } else {
                this.handleSearchError(null, 'error', response.data.message);
            }
        }
        
        /**
         * Mettre à jour les informations de pagination
         */
        updatePagination(data) {
            this.currentPage = data.current_page || 1;
            this.hasMore = data.has_more || false;
            
            // Gérer l'affichage du bouton "Load more"
            if (this.hasMore) {
                this.showLoadMoreButton();
            } else {
                this.hideLoadMoreButton();
            }
            
            this.log('Pagination mise à jour', {
                currentPage: this.currentPage,
                hasMore: this.hasMore,
                total: data.total_available || data.total
            });
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
            
            // Mettre à jour le contenu sans animation
            this.resultsContainer.innerHTML = data.html;
            
            // Pas d'animation JavaScript, le CSS s'en charge
            this.log('Résultats affichés');
            
            // Faire défiler vers les résultats
            this.scrollToResults();
        }
        
        /**
         * Ajouter des résultats supplémentaires
         */
        appendResults(data) {
            if (!this.resultsContainer) return;
            
            // Trouver la grille existante
            const existingGrid = this.resultsContainer.querySelector('.sisme-cards-grid');
            if (!existingGrid || !data.html) return;
            
            // Créer un conteneur temporaire pour parser le HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            
            // Vérifier si on a reçu une grille complète ou juste des cartes
            const newGrid = tempDiv.querySelector('.sisme-cards-grid');
            
            if (newGrid) {
                // Cas 1: On a reçu une grille complète, extraire les cartes
                const newCards = newGrid.querySelectorAll('.sisme-cards-grid__item');
                newCards.forEach(card => {
                    existingGrid.appendChild(card);
                });
            } else {
                // Cas 2: On a reçu seulement des cartes HTML
                // Chercher directement les cartes dans le HTML reçu
                const cardElements = tempDiv.querySelectorAll('.sisme-cards-grid__item');
                if (cardElements.length > 0) {
                    cardElements.forEach(card => {
                        existingGrid.appendChild(card);
                    });
                } else {
                    // Si aucune carte trouvée avec la classe, c'est peut-être du HTML brut
                    // On l'ajoute tel quel
                    existingGrid.insertAdjacentHTML('beforeend', data.html);
                }
            }
            
            this.log('Résultats supplémentaires ajoutés');
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
            console.log('Instance déjà existante pour:', containerId);
            return SISME_SEARCH.instances[containerId];
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