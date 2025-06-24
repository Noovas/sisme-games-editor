<?php
/**
 * File: /sisme-games-editor/includes/search/search-api.php
 * Module de recherche gaming - API principale et shortcodes
 * 
 * Responsabilités :
 * - Enregistrement des shortcodes de recherche
 * - Rendu de l'interface HTML complète
 * - Validation des paramètres
 * - Génération des options de filtres
 * - API publique pour les autres modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_API {
    
    /**
     * Initialisation de l'API
     */
    public static function init() {
        // Enregistrer les shortcodes
        add_shortcode('sisme_search', array(__CLASS__, 'render_search_interface'));
        add_shortcode('sisme_search_bar', array(__CLASS__, 'render_search_bar'));
        add_shortcode('sisme_search_filters', array(__CLASS__, 'render_search_filters'));
        add_shortcode('sisme_search_results', array(__CLASS__, 'render_search_results'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search API: Shortcodes registered');
        }
    }
    
    /**
     * Shortcode principal - Interface de recherche complète
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML de l'interface
     */
    public static function render_search_interface($atts = array()) {
        // Valeurs par défaut
        $defaults = array(
            'show_hero' => 'true',
            'show_filters' => 'true',
            'show_sorting' => 'true',
            'show_suggestions' => 'true',
            'show_quick_filters' => 'true',
            'results_per_page' => '12',
            'default_view' => 'grid',
            'container_class' => 'sisme-search-interface',
            'hero_title' => '🎮 Recherche de Jeux Gaming',
            'hero_subtitle' => 'Trouvez vos jeux préférés parmi notre collection'
        );
        
        // Fusionner avec les attributs fournis
        $atts = shortcode_atts($defaults, $atts, 'sisme_search');
        
        // Valider les paramètres
        $validated_atts = self::validate_shortcode_attributes($atts);
        
        // Forcer le chargement des assets si pas déjà fait
        if (class_exists('Sisme_Search_Loader')) {
            $loader = Sisme_Search_Loader::get_instance();
            $loader->force_load_assets();
        }
        
        // Récupérer les paramètres de recherche actuels depuis l'URL
        $current_params = self::get_current_search_params();
        
        // Générer l'HTML complet
        ob_start();
        ?>
        <div class="<?php echo esc_attr($validated_atts['container_class']); ?>" id="sismeSearchInterface">
            
            <?php if ($validated_atts['show_hero']): ?>
            <!-- Hero Section -->
            <section class="sisme-search-hero">
                <h1 class="sisme-search-title"><?php echo esc_html($validated_atts['hero_title']); ?></h1>
                <p class="sisme-search-subtitle"><?php echo esc_html($validated_atts['hero_subtitle']); ?></p>
            </section>
            <?php endif; ?>
            
            <!-- Barre de recherche principale -->
            <?php echo self::render_search_bar_html($current_params); ?>
            
            <?php if ($validated_atts['show_suggestions']): ?>
            <!-- Tags suggestions populaires -->
            <?php echo self::render_popular_suggestions_html(); ?>
            <?php endif; ?>
            
            <?php if ($validated_atts['show_quick_filters']): ?>
            <!-- Badges filtres rapides -->
            <?php echo self::render_quick_filters_html(); ?>
            <?php endif; ?>
            
            <?php if ($validated_atts['show_filters']): ?>
            <!-- Filtres avancés -->
            <?php echo self::render_advanced_filters_html($current_params); ?>
            <?php endif; ?>
            
            <?php if ($validated_atts['show_sorting']): ?>
            <!-- Contrôles (Tri + Vue) -->
            <?php echo self::render_controls_html($current_params, $validated_atts['default_view']); ?>
            <?php endif; ?>
            
            <!-- Compteur de résultats -->
            <div class="sisme-search-counter" id="sismeSearchCounter" style="display: none;">
                <strong>0 jeux trouvés</strong>
            </div>
            
            <!-- Zone de résultats -->
            <div class="sisme-search-results" id="sismeSearchResults">
                <?php echo self::render_initial_results_state($current_params); ?>
            </div>
            
            <!-- Bouton charger plus -->
            <button class="sisme-load-more" id="sismeLoadMore" style="display: none;">
                📚 Charger plus de jeux
            </button>
            
            <!-- Loader global -->
            <div class="sisme-search-loader" id="sismeSearchLoader" style="display: none;">
                <div class="sisme-loader-backdrop"></div>
                <div class="sisme-loader-content">
                    <div class="sisme-loader-spinner"></div>
                    <p class="sisme-loader-text"><?php esc_html_e('Recherche en cours...', 'sisme-games-editor'); ?></p>
                </div>
            </div>
            
        </div>
        
        <?php
        // Ajouter les données de configuration JavaScript
        echo self::render_javascript_config($validated_atts);
        
        return ob_get_clean();
    }
    
    /**
     * Valider les attributs du shortcode
     */
    private static function validate_shortcode_attributes($atts) {
        $validated = array();
        
        // Valeurs booléennes
        $boolean_fields = array('show_hero', 'show_filters', 'show_sorting', 'show_suggestions', 'show_quick_filters');
        foreach ($boolean_fields as $field) {
            $validated[$field] = filter_var($atts[$field], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Valeurs numériques
        $validated['results_per_page'] = max(1, min(50, intval($atts['results_per_page'])));
        
        // Valeurs de chaîne
        $validated['default_view'] = in_array($atts['default_view'], array('grid', 'list')) ? $atts['default_view'] : 'grid';
        $validated['container_class'] = sanitize_html_class($atts['container_class']);
        $validated['hero_title'] = sanitize_text_field($atts['hero_title']);
        $validated['hero_subtitle'] = sanitize_text_field($atts['hero_subtitle']);
        
        return $validated;
    }
    
    /**
     * Récupérer les paramètres de recherche actuels depuis l'URL
     */
    private static function get_current_search_params() {
        return array(
            'query' => get_search_query(),
            'genres' => self::get_url_array_param('genres'),
            'platforms' => self::get_url_array_param('platforms'),
            'status' => sanitize_text_field($_GET['status'] ?? ''),
            'sort' => sanitize_text_field($_GET['sort'] ?? 'relevance'),
            'view' => sanitize_text_field($_GET['view'] ?? 'grid'),
            'page' => max(1, intval($_GET['page'] ?? 1))
        );
    }
    
    /**
     * Récupérer un paramètre d'URL sous forme de tableau
     */
    private static function get_url_array_param($param_name) {
        if (!isset($_GET[$param_name]) || empty($_GET[$param_name])) {
            return array();
        }
        
        $values = explode(',', sanitize_text_field($_GET[$param_name]));
        return array_filter(array_map('trim', $values));
    }
    
    /**
     * Rendu de la barre de recherche
     */
    private static function render_search_bar_html($current_params) {
        ob_start();
        ?>
        <div class="sisme-search-bar">
            <div class="sisme-search-input-container">
                <input type="text" 
                       id="sismeSearchInput" 
                       class="sisme-search-input"
                       placeholder="<?php esc_attr_e('Rechercher un jeu...', 'sisme-games-editor'); ?>"
                       value="<?php echo esc_attr($current_params['query']); ?>"
                       autocomplete="off">
                <button type="button" class="sisme-search-btn" id="sismeSearchBtn">
                    🔍 <?php esc_html_e('Rechercher', 'sisme-games-editor'); ?>
                </button>
            </div>
            
            <!-- Dropdown historique (caché par défaut) -->
            <div class="sisme-search-history" id="sismeSearchHistory" style="display: none;">
                <div class="sisme-history-header">
                    <span><?php esc_html_e('🕒 Recherches récentes', 'sisme-games-editor'); ?></span>
                    <button class="sisme-history-clear" title="<?php esc_attr_e('Vider l\'historique', 'sisme-games-editor'); ?>">✕</button>
                </div>
                <div class="sisme-history-list"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des suggestions populaires
     */
    private static function render_popular_suggestions_html() {
        // Récupérer les suggestions depuis le module dédié
        $suggestions = array();
        if (class_exists('Sisme_Search_Suggestions')) {
            $suggestions = Sisme_Search_Suggestions::get_popular_searches();
        }
        
        // Suggestions par défaut si pas de classe
        if (empty($suggestions)) {
            $suggestions = array(
                'Action' => 'Action',
                'RPG' => 'RPG', 
                'PlayStation 5' => 'PlayStation 5',
                '2024' => '2024',
                'Multijoueur' => 'Multijoueur'
            );
        }
        
        ob_start();
        ?>
        <div class="sisme-popular-searches" id="sismePopularSearches">
            <p class="sisme-popular-label"><?php esc_html_e('🔥 Recherches populaires:', 'sisme-games-editor'); ?></p>
            <div class="sisme-popular-tags">
                <?php foreach ($suggestions as $term => $display): ?>
                <button class="sisme-popular-tag" data-term="<?php echo esc_attr($term); ?>">
                    <?php echo esc_html($display); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des filtres rapides
     */
    private static function render_quick_filters_html() {
        ob_start();
        ?>
        <div class="sisme-quick-filters" id="sismeQuickFilters">
            <button class="sisme-quick-filter" data-filter="popular">
                🔥 <?php esc_html_e('Populaires', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(<?php echo self::get_quick_filter_count('popular'); ?>)</span>
            </button>
            <button class="sisme-quick-filter" data-filter="new">
                🆕 <?php esc_html_e('Nouveautés', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(<?php echo self::get_quick_filter_count('new'); ?>)</span>
            </button>
            <button class="sisme-quick-filter" data-filter="featured">
                ⭐ <?php esc_html_e('Coups de cœur', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(<?php echo self::get_quick_filter_count('featured'); ?>)</span>
            </button>
            <button class="sisme-quick-filter" data-filter="exclusive">
                🎮 <?php esc_html_e('Exclusivités', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(<?php echo self::get_quick_filter_count('exclusive'); ?>)</span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des filtres avancés
     */
    private static function render_advanced_filters_html($current_params) {
        ob_start();
        ?>
        <div class="sisme-search-filters" id="sismeSearchFilters">
            <div class="sisme-filters-header">
                <h3 class="sisme-filters-title"><?php esc_html_e('🎛️ Filtres Avancés', 'sisme-games-editor'); ?></h3>
                <button class="sisme-filters-toggle" id="sismeFiltersToggle">
                    <span class="sisme-toggle-text"><?php esc_html_e('Masquer', 'sisme-games-editor'); ?></span>
                    <span class="sisme-toggle-icon">▲</span>
                </button>
            </div>
            
            <div class="sisme-filters-content" id="sismeFiltersContent">
                <div class="sisme-filters-row">
                    
                    <!-- Filtre Genres -->
                    <div class="sisme-filter-group" data-filter="genres">
                        <label class="sisme-filter-label"><?php esc_html_e('🎮 Genres', 'sisme-games-editor'); ?></label>
                        <div class="sisme-genres-filter">
                            <input type="text" 
                                   class="sisme-genres-search" 
                                   placeholder="<?php esc_attr_e('Rechercher un genre...', 'sisme-games-editor'); ?>" 
                                   autocomplete="off">
                            <div class="sisme-genres-list">
                                <?php echo self::render_genres_checkboxes($current_params['genres']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtre Plateformes -->
                    <div class="sisme-filter-group" data-filter="platforms">
                        <label class="sisme-filter-label"><?php esc_html_e('💻 Plateformes', 'sisme-games-editor'); ?></label>
                        <div class="sisme-platforms-filter">
                            <input type="text" 
                                   class="sisme-platforms-search" 
                                   placeholder="<?php esc_attr_e('Rechercher une plateforme...', 'sisme-games-editor'); ?>" 
                                   autocomplete="off">
                            <div class="sisme-platforms-list">
                                <?php echo self::render_platforms_checkboxes($current_params['platforms']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtre Statut -->
                    <div class="sisme-filter-group" data-filter="status">
                        <label class="sisme-filter-label"><?php esc_html_e('📅 Statut', 'sisme-games-editor'); ?></label>
                        <select class="sisme-filter-select" id="sismeStatusFilter">
                            <option value=""><?php esc_html_e('Tous', 'sisme-games-editor'); ?></option>
                            <option value="released" <?php selected($current_params['status'], 'released'); ?>><?php esc_html_e('Sortis', 'sisme-games-editor'); ?></option>
                            <option value="upcoming" <?php selected($current_params['status'], 'upcoming'); ?>><?php esc_html_e('À venir', 'sisme-games-editor'); ?></option>
                        </select>
                    </div>
                    
                </div>
                
                <!-- Actions filtres -->
                <div class="sisme-filters-actions">
                    <button type="button" class="sisme-filter-apply" id="sismeApplyFilters">
                        ✅ <?php esc_html_e('Appliquer', 'sisme-games-editor'); ?>
                    </button>
                    <button type="button" class="sisme-filter-reset" id="sismeResetFilters">
                        🔄 <?php esc_html_e('Réinitialiser', 'sisme-games-editor'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des contrôles (tri + vue)
     */
    private static function render_controls_html($current_params, $default_view) {
        $current_view = !empty($current_params['view']) ? $current_params['view'] : $default_view;
        
        ob_start();
        ?>
        <div class="sisme-search-controls">
            
            <!-- Options de tri -->
            <div class="sisme-search-sorting">
                <label for="sismeSortBy" class="sisme-sort-label"><?php esc_html_e('📊 Trier par:', 'sisme-games-editor'); ?></label>
                <select id="sismeSortBy" class="sisme-sort-select">
                    <option value="relevance" <?php selected($current_params['sort'], 'relevance'); ?>><?php esc_html_e('Pertinence', 'sisme-games-editor'); ?></option>
                    <option value="name_asc" <?php selected($current_params['sort'], 'name_asc'); ?>><?php esc_html_e('Nom A→Z', 'sisme-games-editor'); ?></option>
                    <option value="name_desc" <?php selected($current_params['sort'], 'name_desc'); ?>><?php esc_html_e('Nom Z→A', 'sisme-games-editor'); ?></option>
                    <option value="date_desc" <?php selected($current_params['sort'], 'date_desc'); ?>><?php esc_html_e('Plus récents', 'sisme-games-editor'); ?></option>
                    <option value="date_asc" <?php selected($current_params['sort'], 'date_asc'); ?>><?php esc_html_e('Plus anciens', 'sisme-games-editor'); ?></option>
                </select>
            </div>
            
            <!-- Sélecteur de vue -->
            <div class="sisme-view-selector" id="sismeViewSelector">
                <button class="sisme-view-btn <?php echo ($current_view === 'grid') ? 'sisme-view-btn--active' : ''; ?>" 
                        data-view="grid">
                    <span class="sisme-view-icon">🔲</span>
                    <span class="sisme-view-text"><?php esc_html_e('Grille', 'sisme-games-editor'); ?></span>
                </button>
                <button class="sisme-view-btn <?php echo ($current_view === 'list') ? 'sisme-view-btn--active' : ''; ?>" 
                        data-view="list">
                    <span class="sisme-view-icon">📋</span>
                    <span class="sisme-view-text"><?php esc_html_e('Liste', 'sisme-games-editor'); ?></span>
                </button>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de l'état initial des résultats
     */
    private static function render_initial_results_state($current_params) {
        // Si on a une recherche active, essayer d'afficher les résultats
        if (!empty($current_params['query']) || !empty($current_params['genres']) || !empty($current_params['platforms'])) {
            return self::render_search_results_html($current_params);
        }
        
        // Sinon, afficher l'état vide
        ob_start();
        ?>
        <div class="sisme-search-empty-state" id="sismeEmptyState">
            <div class="sisme-empty-icon">🎮</div>
            <h3><?php esc_html_e('Recherchez votre jeu', 'sisme-games-editor'); ?></h3>
            <p><?php esc_html_e('Utilisez la barre de recherche ci-dessus pour trouver des jeux', 'sisme-games-editor'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des résultats de recherche
     */
    private static function render_search_results_html($search_params) {
        // Cette méthode sera complétée quand on aura les modules de filtres
        // Pour l'instant, on retourne un placeholder
        ob_start();
        ?>
        <div class="sisme-search-grid" id="sismeSearchGrid">
            <p><?php esc_html_e('Résultats de recherche (à implémenter)', 'sisme-games-editor'); ?></p>
            <p><?php esc_html_e('Paramètres:', 'sisme-games-editor'); ?> <?php echo esc_html(json_encode($search_params)); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Générer les checkboxes des genres
     */
    private static function render_genres_checkboxes($selected_genres) {
        // Récupérer SEULEMENT les genres enfants de la catégorie "jeux-vidéo"
        $parent_category = get_category_by_slug('jeux-vidéo');
        
        if ($parent_category) {
            // Récupérer les catégories enfants de "jeux-vidéo"
            $genres = get_categories(array(
                'taxonomy' => 'category',
                'parent' => $parent_category->term_id,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Found ' . count($genres) . ' game genres under "jeux-vidéo" category');
            }
        } else {
            $genres = array();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Parent category "jeux-vidéo" not found');
            }
        }
        
        ob_start();
        if (!empty($genres)) {
            foreach ($genres as $genre) {
                $is_selected = in_array($genre->term_id, $selected_genres);
                // Nettoyer le nom en supprimant le préfixe "jeux-" s'il existe
                $genre_name = preg_replace('/^jeux-/i', '', $genre->name);
                $genre_name = ucfirst(trim($genre_name)); // Première lettre en majuscule et trim
                ?>
                <label class="sisme-checkbox-item">
                    <input type="checkbox" 
                           value="<?php echo esc_attr($genre->term_id); ?>" 
                           <?php checked($is_selected); ?>>
                    <?php echo esc_html($genre_name); ?>
                </label>
                <?php
            }
        } else {
            // Genres par défaut si aucun trouvé
            $default_genres = array(
                'action' => 'Action',
                'rpg' => 'RPG',
                'aventure' => 'Aventure',
                'strategie' => 'Stratégie',
                'simulation' => 'Simulation',
                'fps' => 'FPS',
                'plateforme' => 'Plateforme',
                'course' => 'Course'
            );
            
            foreach ($default_genres as $key => $name) {
                ?>
                <label class="sisme-checkbox-item">
                    <input type="checkbox" value="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($name); ?>
                </label>
                <?php
            }
            
            // Message informatif en mode debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<p style="color: orange; font-size: 0.8em; margin: 10px;">DEBUG: Utilisation des genres par défaut - Vérifiez la catégorie parent "jeux-vidéo"</p>';
            }
        }
        return ob_get_clean();
    }
    
    /**
     * Générer les checkboxes des plateformes
     */
    private static function render_platforms_checkboxes($selected_platforms) {
        // Plateformes simplifiées
        $platforms = array(
            'pc' => 'PC',
            'console' => 'Console',
            'mobile' => 'Mobile'
        );
        
        ob_start();
        foreach ($platforms as $platform_key => $platform_name) {
            $is_selected = in_array($platform_key, $selected_platforms);
            ?>
            <label class="sisme-checkbox-item">
                <input type="checkbox" 
                       value="<?php echo esc_attr($platform_key); ?>" 
                       <?php checked($is_selected); ?>>
                <?php echo esc_html($platform_name); ?>
            </label>
            <?php
        }
        return ob_get_clean();
    }
    
    /**
     * Obtenir le nombre d'éléments pour les filtres rapides
     */
    private static function get_quick_filter_count($filter_type) {
        // Placeholder - sera implémenté avec les vraies données
        $counts = array(
            'popular' => 24,
            'new' => 12,
            'featured' => 8,
            'exclusive' => 6
        );
        
        return isset($counts[$filter_type]) ? $counts[$filter_type] : 0;
    }
    
    /**
     * Générer la configuration JavaScript
     */
    private static function render_javascript_config($atts) {
        ob_start();
        ?>
        <script type="text/javascript">
            // Configuration spécifique à cette instance
            window.sismeSearchConfig = window.sismeSearchConfig || {};
            window.sismeSearchConfig.current = <?php echo json_encode(array(
                'resultsPerPage' => $atts['results_per_page'],
                'defaultView' => $atts['default_view'],
                'showSuggestions' => $atts['show_suggestions'],
                'showQuickFilters' => $atts['show_quick_filters']
            )); ?>;
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour la barre de recherche seule
     */
    public static function render_search_bar($atts = array()) {
        $current_params = self::get_current_search_params();
        return self::render_search_bar_html($current_params);
    }
    
    /**
     * Shortcode pour les filtres seuls
     */
    public static function render_search_filters($atts = array()) {
        $current_params = self::get_current_search_params();
        return self::render_advanced_filters_html($current_params);
    }
    
    /**
     * Shortcode pour la zone de résultats seule
     */
    public static function render_search_results($atts = array()) {
        $current_params = self::get_current_search_params();
        return self::render_search_results_html($current_params);
    }
}

// Initialisation de l'API
Sisme_Search_API::init();