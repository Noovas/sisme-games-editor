/**
 * File: /sisme-games-editor/includes/search/assets/search.js
 * JavaScript simple pour le module de recherche
 */

(function($) {
    'use strict';
    
    class SismeSearchInterface {
        constructor() {
            this.state = {
                isLoading: false,
                currentPage: 1,
                hasMore: true,
                currentQuery: ''
            };
            
            this.config = {
                debounceDelay: 500,
                resultsPerPage: 12
            };
            
            this.searchTimeout = null;
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            // Recherche avec debounce
            $(document).on('input', '#sismeSearchInput', (e) => {
                this.debounceSearch(e.target.value);
            });
            
            // Recherche directe
            $(document).on('click', '#sismeSearchBtn', () => {
                this.performSearch();
            });
            
            // Filtres
            $(document).on('change', '.sisme-genres-list input[type="checkbox"]', () => {
                this.performSearch();
            });
            
            $(document).on('change', '#sismeStatusFilter', () => {
                this.performSearch();
            });
            
            $(document).on('change', '#sismeSortBy', () => {
                this.performSearch();
            });
            
            // Filtres rapides
            $(document).on('click', '.sisme-quick-filter', (e) => {
                this.toggleQuickFilter(e.target);
            });
            
            // Boutons filtres
            $(document).on('click', '#sismeApplyFilters', () => {
                this.performSearch();
            });
            
            $(document).on('click', '#sismeResetFilters', () => {
                this.resetFilters();
            });
            
            // Charger plus
            $(document).on('click', '#sismeLoadMore', () => {
                this.loadMore();
            });
        }
        
        debounceSearch(query) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) {
                    this.state.currentQuery = query;
                    this.performSearch();
                }
            }, this.config.debounceDelay);
        }
        
        performSearch(loadMore = false) {
            if (this.state.isLoading) return;
            
            if (!loadMore) {
                this.state.currentPage = 1;
            }
            
            const searchData = this.collectSearchData();
            searchData.page = this.state.currentPage;
            
            this.state.isLoading = true;
            this.showLoader();
            
            $.ajax({
                url: sismeSearch.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'sisme_search',
                    nonce: sismeSearch.nonce,
                    ...searchData
                },
                success: (response) => {
                    this.handleSearchSuccess(response, loadMore);
                },
                error: () => {
                    this.handleSearchError();
                },
                complete: () => {
                    this.state.isLoading = false;
                    this.hideLoader();
                }
            });
        }
        
        collectSearchData() {
            const query = $('#sismeSearchInput').val().trim();
            
            const genres = [];
            $('.sisme-genres-list input[type="checkbox"]:checked').each(function() {
                genres.push($(this).val());
            });
            
            const status = $('#sismeStatusFilter').val();
            const sort = $('#sismeSortBy').val() || 'relevance';
            
            let quickFilter = '';
            const activeQuickFilter = $('.sisme-quick-filter.active');
            if (activeQuickFilter.length) {
                quickFilter = activeQuickFilter.data('filter');
            }
            
            return {
                query: query,
                genres: genres,
                status: status,
                sort: sort,
                quick_filter: quickFilter,
                per_page: this.config.resultsPerPage
            };
        }
        
        handleSearchSuccess(response, loadMore = false) {
            if (!response.success) {
                this.handleSearchError();
                return;
            }
            
            const data = response.data;
            this.state.hasMore = data.has_more;
            this.state.currentPage = data.page;
            
            if (loadMore && data.is_pagination) {
                // Mode pagination : ajouter les cartes à la grille existante
                const $grid = $('.sisme-cards-grid');
                if ($grid.length) {
                    $grid.append(data.html);
                }
            } else {
                // Mode normal : remplacer tout le contenu
                $('#sismeSearchResults').html(data.html);
                this.updateResultsCounter(data.total);
            }
            
            this.updateLoadMoreButton();
        }
        
        handleSearchError() {
            const errorHtml = '<div class="sisme-search-error"><h3>Erreur lors de la recherche</h3></div>';
            $('#sismeSearchResults').html(errorHtml);
            this.hideLoadMoreButton();
        }
        
        updateResultsCounter(total) {
            const $counter = $('#sismeSearchCounter');
            if (total > 0) {
                $counter.html(`<strong>${total} jeux trouvés</strong>`).show();
            } else {
                $counter.hide();
            }
        }
        
        updateLoadMoreButton() {
            const $btn = $('#sismeLoadMore');
            if (this.state.hasMore) {
                $btn.show().prop('disabled', false);
            } else {
                $btn.hide();
            }
        }
        
        hideLoadMoreButton() {
            $('#sismeLoadMore').hide();
        }
        
        loadMore() {
            if (this.state.hasMore && !this.state.isLoading) {
                this.state.currentPage++;
                $('#sismeLoadMore').prop('disabled', true).text('⏳ Chargement...');
                this.performSearch(true);
            }
        }
        
        toggleQuickFilter(element) {
            const $element = $(element);
            const isActive = $element.hasClass('active');
            
            // Désactiver tous les filtres rapides
            $('.sisme-quick-filter').removeClass('active');
            
            if (!isActive) {
                $element.addClass('active');
            }
            
            this.performSearch();
        }
        
        resetFilters() {
            // Réinitialiser tous les filtres
            $('#sismeSearchInput').val('');
            $('.sisme-genres-list input[type="checkbox"]').prop('checked', false);
            $('#sismeStatusFilter').val('');
            $('#sismeSortBy').val('relevance');
            $('.sisme-quick-filter').removeClass('active');
            
            this.performSearch();
        }
        
        showLoader() {
            $('#sismeSearchLoader').fadeIn(200);
        }
        
        hideLoader() {
            $('#sismeSearchLoader').fadeOut(200);
        }
    }
    
    // Initialisation
    $(document).ready(function() {
        if ($('#sismeSearchInterface').length) {
            window.sismeSearchInstance = new SismeSearchInterface();
        }
    });
    
})(jQuery);