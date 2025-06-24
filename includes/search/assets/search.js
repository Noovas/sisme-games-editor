/**
 * File: /sisme-games-editor/includes/search/assets/search.js
 * JavaScript complet pour le module de recherche
 * 
 * Responsabilités :
 * - Gestion des interactions utilisateur
 * - Requêtes AJAX en temps réel
 * - Historique de recherche local
 * - Filtres et pagination
 * - Gestion des états de l'interface
 */

(function($) {
    'use strict';
    
    /**
     * Classe principale de gestion de la recherche
     */
    class SismeSearchInterface {
        constructor() {
            // État de l'interface
            this.state = {
                isLoading: false,
                currentPage: 1,
                hasMore: true,
                currentQuery: '',
                currentFilters: {
                    genres: [],
                    platforms: [],
                    status: '',
                    quickFilter: '',
                    sort: 'relevance',
                    view: 'grid'
                },
                searchHistory: [],
                abortController: null
            };
            
            // Configuration
            this.config = {
                debounceDelay: sismeSearch.config?.debounceDelay || 500,
                maxHistoryItems: sismeSearch.config?.maxHistoryItems || 5,
                resultsPerPage: sismeSearch.config?.resultsPerPage || 12,
                animationDuration: sismeSearch.config?.animationDuration || 300
            };
            
            // Timers
            this.searchTimeout = null;
            
            this.init();
        }
        
        /**
         * Initialisation de l'interface
         */
        init() {
            this.bindEvents();
            this.loadSearchHistory();
            this.initializeFilters();
            this.loadQuickStats();
            this.checkUrlParams();
            
            // Log d'initialisation
            if (sismeSearch.debug) {
                console.log('🔍 Sisme Search Interface initialized');
            }
        }
        
        /**
         * Lier tous les événements
         */
        bindEvents() {
            // Événements de recherche
            $(document).on('input', '#sismeSearchInput', (e) => {
                this.debounceSearch(e.target.value);
            });
            
            $(document).on('keypress', '#sismeSearchInput', (e) => {
                if (e.which === 13) { // Entrée
                    e.preventDefault();
                    this.performSearch();
                }
            });
            
            $(document).on('click', '#sismeSearchBtn', () => {
                this.performSearch();
            });
            
            // Événements de focus pour l'historique
            $(document).on('focus', '#sismeSearchInput', () => {
                this.showSearchHistory();
            });
            
            $(document).on('blur', '#sismeSearchInput', (e) => {
                // Délai pour permettre le clic sur l'historique
                setTimeout(() => {
                    this.hideSearchHistory();
                }, 200);
            });
            
            // Suggestions populaires
            $(document).on('click', '.sisme-popular-tag', (e) => {
                const term = $(e.target).data('term');
                this.setSearchTerm(term);
                this.performSearch();
            });
            
            // Filtres rapides
            $(document).on('click', '.sisme-quick-filter', (e) => {
                const filter = $(e.target).data('filter');
                this.toggleQuickFilter(filter, e.target);
            });
            
            // Filtres avancés
            $(document).on('click', '#sismeFiltersToggle', () => {
                this.toggleAdvancedFilters();
            });
            
            $(document).on('click', '#sismeApplyFilters', () => {
                this.applyFilters();
            });
            
            $(document).on('click', '#sismeResetFilters', () => {
                this.resetFilters();
            });
            
            // Recherche dans les filtres
            $(document).on('input', '.sisme-genres-search', (e) => {
                this.filterCheckboxes('.sisme-genres-list', e.target.value);
            });
            /*
            $(document).on('input', '.sisme-platforms-search', (e) => {
                this.filterCheckboxes('.sisme-platforms-list', e.target.value);
            });
            */
            // Changement de checkboxes
            $(document).on('change', '.sisme-checkbox-item input[type="checkbox"]', () => {
                this.updateFilterCounts();
            });
            
            // Contrôles de tri et vue
            $(document).on('change', '#sismeSortBy', (e) => {
                this.state.currentFilters.sort = e.target.value;
                this.performSearch();
            });
            
            $(document).on('click', '.sisme-view-btn', (e) => {
                const view = $(e.target).closest('.sisme-view-btn').data('view');
                this.switchView(view);
            });
            
            // Charger plus
            $(document).on('click', '#sismeLoadMore', () => {
                this.loadMore();
            });
            
            // Historique de recherche
            $(document).on('click', '.sisme-history-item', (e) => {
                const term = $(e.target).text();
                this.setSearchTerm(term);
                this.performSearch();
            });
            
            $(document).on('click', '.sisme-history-clear', () => {
                this.clearSearchHistory();
            });
            
            // Suggestions alternatives
            $(document).on('click', '.sisme-suggestion-tag', (e) => {
                const suggestion = $(e.target).data('suggestion');
                this.applySuggestion(suggestion);
            });
        }
        
        /**
         * Recherche avec debounce
         */
        debounceSearch(query) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) {
                    this.state.currentQuery = query;
                    this.performSearch();
                }
            }, this.config.debounceDelay);
        }
        
        /**
         * Effectuer une recherche
         */
        performSearch(loadMore = false) {
            if (this.state.isLoading) {
                return;
            }
            
            // Annuler la requête précédente si elle existe
            if (this.state.abortController) {
                this.state.abortController.abort();
            }
            
            // Réinitialiser la page si nouvelle recherche
            if (!loadMore) {
                this.state.currentPage = 1;
            }
            
            // Collecter les données de recherche
            const searchData = this.collectSearchData();
            searchData.page = this.state.currentPage;
            
            // Afficher le loader
            this.showLoader();
            
            // Marquer comme en cours de chargement
            this.state.isLoading = true;
            
            // Créer un nouveau AbortController
            this.state.abortController = new AbortController();
            
            // Effectuer la requête AJAX
            $.ajax({
                url: sismeSearch.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sisme_search',
                    nonce: sismeSearch.nonce,
                    ...searchData
                },
                signal: this.state.abortController.signal,
                success: (response) => {
                    this.handleSearchSuccess(response, loadMore);
                },
                error: (xhr, status, error) => {
                    if (status !== 'abort') {
                        this.handleSearchError(error);
                    }
                },
                complete: () => {
                    this.state.isLoading = false;
                    this.hideLoader();
                    this.state.abortController = null;
                }
            });
            
            // Log de la recherche
            if (sismeSearch.debug) {
                console.log('🔍 Search performed:', searchData);
            }
        }
        
        /**
         * Collecter les données de recherche
         */
        collectSearchData() {
            // Récupérer la valeur de recherche
            const query = $('#sismeSearchInput').val().trim();
            
            // Collecter les genres sélectionnés
            const genres = [];
            $('.sisme-genres-list input[type="checkbox"]:checked').each(function() {
                genres.push($(this).val());
            });
            
            // Collecter les plateformes sélectionnées
            const platforms = [];
            $('.sisme-platforms-list input[type="checkbox"]:checked').each(function() {
                platforms.push($(this).val());
            });
            
            // Autres filtres
            const status = $('#sismeStatusFilter').val();
            const sort = $('#sismeSortBy').val();
            const view = this.state.currentFilters.view;
            const quickFilter = this.state.currentFilters.quickFilter;
            
            return {
                query: query,
                genres: genres,
                platforms: platforms,
                status: status,
                quick_filter: quickFilter,
                sort: sort,
                view: view,
                per_page: this.config.resultsPerPage
            };
        }
        
        /**
         * Gérer le succès de la recherche
         */
        handleSearchSuccess(response, loadMore = false) {
            if (!response.success) {
                this.handleSearchError(response.data?.message || 'Erreur inconnue');
                return;
            }
            
            const data = response.data;
            
            // Mettre à jour l'état
            this.state.hasMore = data.has_more;
            this.state.currentPage = data.page;
            
            // Mettre à jour l'affichage
            if (loadMore) {
                this.appendResults(data.html);
            } else {
                this.replaceResults(data.html);
            }
            
            // Mettre à jour le compteur
            this.updateResultsCounter(data.summary, data.total);
            
            // Mettre à jour le bouton "Charger plus"
            this.updateLoadMoreButton();
            
            // Mettre à jour l'URL
            this.updateUrl();
            
            // Ajouter à l'historique
            if (data.params.query && !loadMore) {
                this.addToSearchHistory(data.params.query);
            }
            
            // Masquer les suggestions populaires si résultats
            if (data.total > 0) {
                this.hidePopularSuggestions();
            } else {
                this.showPopularSuggestions();
            }
            
            // Animation
            this.animateResults();
            
            if (sismeSearch.debug) {
                console.log('✅ Search success:', data.total, 'results');
            }
        }
        
        /**
         * Gérer les erreurs de recherche
         */
        handleSearchError(error) {
            console.error('❌ Search error:', error);
            
            const errorHtml = `
                <div class="sisme-search-error">
                    <div class="sisme-empty-icon">⚠️</div>
                    <h3>${sismeSearch.i18n.errorText}</h3>
                    <p>Une erreur est survenue lors de la recherche. Veuillez réessayer.</p>
                    <button type="button" class="sisme-filter-apply" onclick="location.reload()">
                        🔄 Recharger la page
                    </button>
                </div>
            `;
            
            this.replaceResults(errorHtml);
        }
        
        /**
         * Remplacer les résultats
         */
        replaceResults(html) {
            const $results = $('#sismeSearchResults');
            $results.addClass('loading');
            
            setTimeout(() => {
                $results.html(html);
                $results.removeClass('loading').addClass('loaded');
            }, 150);
        }
        
        /**
         * Ajouter des résultats (pagination)
         */
        appendResults(html) {
            const $newResults = $(html);
            const $grid = $('#sismeSearchResults .sisme-search-grid, #sismeSearchResults .sisme-search-list');
            
            if ($grid.length) {
                $newResults.find('.sisme-search-card').each(function(index) {
                    $(this).css('animation-delay', (index * 0.1) + 's');
                });
                
                $grid.append($newResults.find('.sisme-search-card'));
            }
        }
        
        /**
         * Mettre à jour le compteur de résultats
         */
        updateResultsCounter(summary, total) {
            const $counter = $('#sismeSearchCounter');
            
            if (total > 0) {
                $counter.html('<strong>' + summary + '</strong>').show();
            } else {
                $counter.hide();
            }
        }
        
        /**
         * Mettre à jour le bouton "Charger plus"
         */
        updateLoadMoreButton() {
            const $loadMore = $('#sismeLoadMore');
            
            if (this.state.hasMore && this.state.currentPage > 0) {
                $loadMore.show();
            } else {
                $loadMore.hide();
            }
        }
        
        /**
         * Charger plus de résultats
         */
        loadMore() {
            if (this.state.hasMore && !this.state.isLoading) {
                this.state.currentPage++;
                this.performSearch(true);
            }
        }
        
        /**
         * Changer de vue (grille/liste)
         */
        switchView(view) {
            if (view === this.state.currentFilters.view) {
                return;
            }
            
            this.state.currentFilters.view = view;
            
            // Mettre à jour les boutons
            $('.sisme-view-btn').removeClass('sisme-view-btn--active');
            $(`.sisme-view-btn[data-view="${view}"]`).addClass('sisme-view-btn--active');
            
            // Relancer la recherche avec la nouvelle vue
            this.performSearch();
        }
        
        /**
         * Basculer un filtre rapide
         */
        toggleQuickFilter(filter, element) {
            const $element = $(element);
            const isActive = $element.hasClass('active');
            
            // Désactiver tous les filtres rapides
            $('.sisme-quick-filter').removeClass('active');
            
            if (!isActive) {
                // Activer le filtre sélectionné
                $element.addClass('active');
                this.state.currentFilters.quickFilter = filter;
            } else {
                // Désactiver le filtre
                this.state.currentFilters.quickFilter = '';
            }
            
            this.performSearch();
        }
        
        /**
         * Basculer les filtres avancés
         */
        toggleAdvancedFilters() {
            const $content = $('#sismeFiltersContent');
            const $toggle = $('#sismeFiltersToggle');
            const $icon = $toggle.find('.sisme-toggle-icon');
            const $text = $toggle.find('.sisme-toggle-text');
            
            if ($content.hasClass('collapsed')) {
                $content.removeClass('collapsed');
                $toggle.removeClass('collapsed');
                $icon.text('▲');
                $text.text('Masquer');
            } else {
                $content.addClass('collapsed');
                $toggle.addClass('collapsed');
                $icon.text('▼');
                $text.text('Afficher');
            }
        }
        
        /**
         * Appliquer les filtres
         */
        applyFilters() {
            this.performSearch();
        }
        
        /**
         * Réinitialiser les filtres
         */
        resetFilters() {
            // Réinitialiser les checkboxes
            $('.sisme-checkbox-item input[type="checkbox"]').prop('checked', false);
            
            // Réinitialiser les selects
            $('#sismeStatusFilter').val('');
            $('#sismeSortBy').val('relevance');
            
            // Réinitialiser les recherches dans les filtres
            $('.sisme-genres-search, .sisme-platforms-search').val('');
            $('.sisme-checkbox-item').show();
            
            // Réinitialiser les filtres rapides
            $('.sisme-quick-filter').removeClass('active');
            this.state.currentFilters.quickFilter = '';
            
            // Réinitialiser la recherche
            $('#sismeSearchInput').val('');
            
            // Mettre à jour les compteurs
            this.updateFilterCounts();
            
            // Relancer la recherche
            this.performSearch();
        }
        
        /**
         * Filtrer les checkboxes selon une recherche
         */
        filterCheckboxes(containerSelector, searchTerm) {
            const term = searchTerm.toLowerCase();
            
            $(containerSelector + ' .sisme-checkbox-item').each(function() {
                const text = $(this).text().toLowerCase();
                if (text.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        
        /**
         * Mettre à jour les compteurs de filtres sélectionnés
         */
        updateFilterCounts() {
            // Genres
            const genresCount = $('.sisme-genres-list input[type="checkbox"]:checked').length;
            const $genresGroup = $('.sisme-filter-group[data-filter="genres"]');
            
            if (genresCount > 0) {
                $genresGroup.addClass('has-selection').attr('data-selected-count', genresCount);
            } else {
                $genresGroup.removeClass('has-selection');
            }
            
            // Plateformes
            const platformsCount = $('.sisme-platforms-list input[type="checkbox"]:checked').length;
            const $platformsGroup = $('.sisme-filter-group[data-filter="platforms"]');
            
            if (platformsCount > 0) {
                $platformsGroup.addClass('has-selection').attr('data-selected-count', platformsCount);
            } else {
                $platformsGroup.removeClass('has-selection');
            }
        }
        
        /**
         * Initialiser les filtres depuis l'URL
         */
        initializeFilters() {
            this.updateFilterCounts();
        }
        
        /**
         * Charger les statistiques des filtres rapides
         */
        loadQuickStats() {
            $.ajax({
                url: sismeSearch.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sisme_search_quick_stats',
                    nonce: sismeSearch.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateQuickStats(response.data);
                    }
                },
                error: (error) => {
                    if (sismeSearch.debug) {
                        console.log('Failed to load quick stats:', error);
                    }
                }
            });
        }
        
        /**
         * Mettre à jour les statistiques des filtres rapides
         */
        updateQuickStats(stats) {
            Object.keys(stats).forEach(filter => {
                const count = stats[filter];
                $(`.sisme-quick-filter[data-filter="${filter}"] .sisme-filter-count`).text(`(${count})`);
            });
        }
        
        /**
         * Gestion de l'historique de recherche
         */
        loadSearchHistory() {
            const history = localStorage.getItem('sismeSearchHistory');
            if (history) {
                try {
                    this.state.searchHistory = JSON.parse(history);
                } catch (e) {
                    this.state.searchHistory = [];
                }
            }
        }
        
        addToSearchHistory(term) {
            if (!term || term.length < 2) {
                return;
            }
            
            // Supprimer le terme s'il existe déjà
            this.state.searchHistory = this.state.searchHistory.filter(item => item !== term);
            
            // Ajouter au début
            this.state.searchHistory.unshift(term);
            
            // Limiter la taille
            if (this.state.searchHistory.length > this.config.maxHistoryItems) {
                this.state.searchHistory = this.state.searchHistory.slice(0, this.config.maxHistoryItems);
            }
            
            // Sauvegarder
            localStorage.setItem('sismeSearchHistory', JSON.stringify(this.state.searchHistory));
        }
        
        showSearchHistory() {
            if (this.state.searchHistory.length === 0) {
                return;
            }
            
            const historyHtml = this.state.searchHistory.map(term => 
                `<button class="sisme-history-item">${term}</button>`
            ).join('');
            
            $('#sismeSearchHistory .sisme-history-list').html(historyHtml);
            $('#sismeSearchHistory').show();
        }
        
        hideSearchHistory() {
            $('#sismeSearchHistory').hide();
        }
        
        clearSearchHistory() {
            this.state.searchHistory = [];
            localStorage.removeItem('sismeSearchHistory');
            this.hideSearchHistory();
        }
        
        /**
         * Utilitaires d'interface
         */
        setSearchTerm(term) {
            $('#sismeSearchInput').val(term);
            this.state.currentQuery = term;
        }
        
        showLoader() {
            $('#sismeSearchLoader').fadeIn(200);
        }
        
        hideLoader() {
            $('#sismeSearchLoader').fadeOut(200);
        }
        
        hidePopularSuggestions() {
            $('#sismePopularSearches').slideUp(300);
        }
        
        showPopularSuggestions() {
            $('#sismePopularSearches').slideDown(300);
        }
        
        animateResults() {
            $('.sisme-search-card').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation-fill-mode': 'both'
                });
            });
        }
        
        /**
         * Gestion de l'URL
         */
        checkUrlParams() {
            const params = new URLSearchParams(window.location.search);
            
            // Recherche
            const query = params.get('s');
            if (query) {
                this.setSearchTerm(query);
            }
            
            // Genres
            const genres = params.get('genres');
            if (genres) {
                const genreIds = genres.split(',');
                genreIds.forEach(id => {
                    $(`.sisme-genres-list input[value="${id}"]`).prop('checked', true);
                });
            }
            
            // Plateformes
            const platforms = params.get('platforms');
            if (platforms) {
                const platformsList = platforms.split(',');
                platformsList.forEach(platform => {
                    $(`.sisme-platforms-list input[value="${platform}"]`).prop('checked', true);
                });
            }
            
            // Autres paramètres
            const status = params.get('status');
            if (status) {
                $('#sismeStatusFilter').val(status);
            }
            
            const sort = params.get('sort');
            if (sort) {
                $('#sismeSortBy').val(sort);
                this.state.currentFilters.sort = sort;
            }
            
            const view = params.get('view');
            if (view) {
                this.state.currentFilters.view = view;
                this.switchView(view);
            }
            
            // Effectuer la recherche si des paramètres sont présents
            if (query || genres || platforms || status) {
                this.updateFilterCounts();
                this.performSearch();
            }
        }
        
        updateUrl() {
            const searchData = this.collectSearchData();
            const params = new URLSearchParams();
            
            if (searchData.query) {
                params.set('s', searchData.query);
            }
            
            if (searchData.genres.length > 0) {
                params.set('genres', searchData.genres.join(','));
            }
            
            if (searchData.platforms.length > 0) {
                params.set('platforms', searchData.platforms.join(','));
            }
            
            if (searchData.status) {
                params.set('status', searchData.status);
            }
            
            if (searchData.sort && searchData.sort !== 'relevance') {
                params.set('sort', searchData.sort);
            }
            
            if (searchData.view && searchData.view !== 'grid') {
                params.set('view', searchData.view);
            }
            
            const newUrl = window.location.pathname + 
                          (params.toString() ? '?' + params.toString() : '');
            
            window.history.replaceState({}, '', newUrl);
        }
        
        /**
         * Appliquer une suggestion alternative
         */
        applySuggestion(suggestion) {
            if (typeof suggestion === 'string') {
                try {
                    suggestion = JSON.parse(suggestion);
                } catch (e) {
                    return;
                }
            }
            
            switch (suggestion.type) {
                case 'genre':
                    // Sélectionner le genre correspondant
                    $(`.sisme-genres-list input[value="${suggestion.value}"]`).prop('checked', true);
                    break;
                case 'platform':
                    // Sélectionner la plateforme correspondante
                    $(`.sisme-platforms-list input[value="${suggestion.value}"]`).prop('checked', true);
                    break;
                case 'quick_filter':
                    // Activer le filtre rapide
                    $(`.sisme-quick-filter[data-filter="${suggestion.value}"]`).click();
                    return; // Le clic va déclencher la recherche
                default:
                    // Recherche textuelle
                    this.setSearchTerm(suggestion.value);
                    break;
            }
            
            this.updateFilterCounts();
            this.performSearch();
        }
    }
    
    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        // Vérifier si l'interface de recherche est présente
        if ($('#sismeSearchInterface').length) {
            window.sismeSearchInstance = new SismeSearchInterface();
            
            if (sismeSearch.debug) {
                console.log('🎮 Sisme Search Interface fully loaded');
            }
        }
    });
    
})(jQuery);