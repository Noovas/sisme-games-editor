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
            'default_view' => 'list',
            'filters_collapsed' => 'true', 
            'container_class' => 'sisme-search-interface',
            'hero_title' => '🔍 Recherche de Jeux indé',
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
            <h2 class="sisme-search-title"><?php echo esc_html($validated_atts['hero_title']); ?></h2>
            <p class="sisme-search-subtitle"><?php echo esc_html($validated_atts['hero_subtitle']); ?></p>
            <?php endif; ?>
            
            <?php if ($validated_atts['show_filters']): ?>
            <!-- Filtres avancés -->
            <?php echo self::render_advanced_filters_html($current_params, $filters_collapsed); ?>
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
                <?php echo self::render_initial_results_state($current_params, $validated_atts['default_view']); ?>
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
            'view' => sanitize_text_field($_GET['view'] ?? ''),
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
     * Rendu des filtres rapides
     */
    private static function render_quick_filters_html() {
        ob_start();
        ?>
        <div class="sisme-quick-filters" id="sismeQuickFilters">
            <!-- 💖 Coups de cœur (is_team_choice)  désactivé pour l'instant) -->
            <button class="sisme-quick-filter sisme-tooltip-enabled" data-filter="featured" style="opacity: 0.6;" disabled title="Bientôt disponible">
                💖 <?php esc_html_e('Coups de cœur', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(<?php echo self::get_quick_filter_count('featured'); ?>)</span>
            </button>
                        
            <!-- 🔥 Populaires (futur - désactivé pour l'instant) -->
            <button class="sisme-quick-filter sisme-tooltip-enabled" data-filter="popular" style="opacity: 0.6;" disabled title="Bientôt disponible">
                🔥 <?php esc_html_e('Populaires', 'sisme-games-editor'); ?> 
                <span class="sisme-filter-count">(Bientôt)</span>
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
            <!-- BARRE DE RECHERCHE INTÉGRÉE -->
            <div class="sisme-search-input-container">    
                <input type="text" 
                       id="sismeSearchInput" 
                       class="sisme-search-input-name"
                       placeholder="<?php esc_attr_e('Rechercher un jeu...', 'sisme-games-editor'); ?>"
                       value="<?php echo esc_attr($current_params['query']); ?>"
                       autocomplete="off">
                <button type="button" class="sisme-search-btn" id="sismeSearchBtn">
                    🔍 <?php esc_html_e('Rechercher', 'sisme-games-editor'); ?>
                </button>
                <!-- Dropdown historique (caché par défaut) -->
                <div class="sisme-search-history" id="sismeSearchHistory" style="display: none;">
                    <div class="sisme-history-header">
                        <span><?php esc_html_e('🕒 Recherches récentes', 'sisme-games-editor'); ?></span>
                        <button class="sisme-history-clear" title="<?php esc_attr_e('Vider l\'historique', 'sisme-games-editor'); ?>">✕</button>
                    </div>
                    <div class="sisme-history-list"></div>
                </div>
            </div>

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
                    
                    <!-- Filtre Plateformes 
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
                    -->
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
                <div class="sisme-filter-footer sisme-flex-row">
                    <?php echo self::render_quick_filters_html(); ?>
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
     * Affiche tous les jeux par défaut au lieu d'un état vide
     */
    private static function render_initial_results_state($current_params, $default_view = 'grid') {
        // Si on a une recherche active, essayer d'afficher les résultats
        if (!empty($current_params['query']) || !empty($current_params['genres']) || !empty($current_params['platforms'])) {
            return self::render_search_results_html($current_params);
        }
        
        // 🎮 NOUVEAU: Par défaut, afficher tous les jeux disponibles
        return self::render_all_games_default($current_params, $default_view);
    }

    /**
     * 🚀 Afficher tous les jeux par défaut
     * 
     * @param array $current_params Paramètres courants
     * @return string HTML avec tous les jeux
     */
    private static function render_all_games_default($current_params, $default_view = 'list') {
        // Paramètres par défaut pour récupérer tous les jeux
        $default_search_params = array(
            'query' => '',
            'genres' => array(),
            'platforms' => array(),
            'status' => '',
            'quick_filter' => '',
            'sort' => 'name_asc',  // Tri alphabétique par défaut
            'page' => 1,
            'per_page' => 12       // Affichage paginé
        );
        
        // Vérifier que le module de filtres est disponible
        if (!class_exists('Sisme_Search_Filters')) {
            return self::render_fallback_games_list();
        }
        
        try {
            // Effectuer la recherche "tous les jeux"
            $results = Sisme_Search_Filters::perform_search($default_search_params);
            
            // Si aucun jeu trouvé, afficher un message
            if (empty($results['games'])) {
                return self::render_no_games_available();
            }
            
            // Générer le HTML avec les résultats
            ob_start();
            ?>
            <div class="sisme-search-results-container">
                <!-- Debug format des données (en mode WP_DEBUG) -->
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <?php echo self::debug_games_format($results['games']); ?>
                <?php endif; ?>
                
                <!-- Compteur de résultats visible -->
                <div class="sisme-search-counter" style="display: block;">
                    <strong><?php echo sprintf(_n('%d jeu disponible', '%d jeux disponibles', $results['total'], 'sisme-games-editor'), $results['total']); ?></strong>
                    <span class="sisme-counter-subtitle"><?php esc_html_e('Utilisez les filtres pour affiner votre recherche', 'sisme-games-editor'); ?></span>
                </div>
                
                <!-- Grille des jeux -->
                <?php 
                $view_type = $current_params['view'] ?: $default_view;
                echo self::render_games_grid($results['games'], $default_view);
                ?>
                
                <!-- Pagination si nécessaire -->
                <?php if ($results['has_more']): ?>
                    <div class="sisme-search-pagination">
                        <button class="sisme-load-more" id="sismeLoadMore" style="display: block;">
                            📚 <?php esc_html_e('Charger plus de jeux', 'sisme-games-editor'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Error loading default games: ' . $e->getMessage());
            }
            
            return self::render_fallback_games_list();
        }
    }

    /**
     * 🔧 FALLBACK: Liste simple si le système Cards n'est pas disponible
     */
    private static function render_fallback_games_list() {
        // Récupérer les jeux directement depuis les terms
        $games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            ),
            'number' => 12,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($games)) {
            return self::render_no_games_available();
        }
        
        ob_start();
        ?>
        <div class="sisme-search-results-container">
            <div class="sisme-search-counter" style="display: block;">
                <strong><?php echo sprintf(_n('%d jeu disponible', '%d jeux disponibles', count($games), 'sisme-games-editor'), count($games)); ?></strong>
                <span class="sisme-counter-subtitle"><?php esc_html_e('Liste des jeux (mode fallback)', 'sisme-games-editor'); ?></span>
            </div>
            
            <div class="sisme-search-grid sisme-fallback-grid">
                <?php foreach ($games as $game): ?>
                    <div class="sisme-search-card sisme-fallback-card">
                        <h3><?php echo esc_html($game->name); ?></h3>
                        <p><?php echo esc_html(wp_trim_words(get_term_meta($game->term_id, 'game_description', true), 20)); ?></p>
                        <div class="sisme-card-meta">
                            <span><?php echo sprintf(__('ID: %d', 'sisme-games-editor'), $game->term_id); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 📭 Message quand aucun jeu n'est disponible
     */
    private static function render_no_games_available() {
        ob_start();
        ?>
        <div class="sisme-search-empty-state" id="sismeEmptyState">
            <div class="sisme-empty-icon">🎮</div>
            <h3><?php esc_html_e('Aucun jeu disponible', 'sisme-games-editor'); ?></h3>
            <p><?php esc_html_e('Il semble qu\'aucun jeu ne soit encore configuré dans le système.', 'sisme-games-editor'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" class="sisme-btn sisme-btn--primary">
                <?php esc_html_e('Ajouter des jeux', 'sisme-games-editor'); ?>
            </a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 🔧 FALLBACK: Grille simple si Cards API non disponible
     * 
     * @param array $games Données des jeux
     * @param string $view_type Type de vue
     * @return string HTML fallback
     */
    private static function render_fallback_grid($games, $view_type) {
        $grid_class = ($view_type === 'list') ? 'sisme-search-list sisme-fallback-list' : 'sisme-search-grid sisme-fallback-grid';
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($grid_class); ?>">
            <?php foreach ($games as $game): ?>
                <?php echo self::render_fallback_card($game); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 🎮 Générer la grille des jeux avec le système Cards
     * 
     * @param array $games Données des jeux
     * @param string $view_type Type de vue (grid/list)
     * @return string HTML de la grille
     */
    private static function render_games_grid($games, $view_type = 'grid') {
        if (empty($games)) {
            return '';
        }
        
        // Vérifier que l'API Cards est disponible
        if (!class_exists('Sisme_Cards_API')) {
            return '<p>' . __('Erreur: Système de cartes non disponible', 'sisme-games-editor') . '</p>';
        }
        
        $grid_class = ($view_type === 'list') ? 'sisme-search-list' : 'sisme-search-grid';
        $card_type = ($view_type === 'list') ? 'details' : 'normal';
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($grid_class); ?>" id="sismeSearchGrid">
            <?php foreach ($games as $index => $game): ?>
                <div class="sisme-search-card">
                    <?php 
                    // DEBUG: Afficher dans la console du navigateur
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        ?>
                        <script>
                        console.group('🎮 Sisme Search Debug - Jeu #<?php echo $index; ?>');
                        console.log('Type:', <?php echo json_encode(gettype($game)); ?>);
                        console.log('Contenu:', <?php echo json_encode($game); ?>);
                        console.groupEnd();
                        </script>
                        <?php
                    }
                    
                    echo Sisme_Cards_API::render_card($game, $card_type);
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 🎴 FALLBACK: Carte simple si problème avec Cards API
     * 
     * @param mixed $game Données du jeu
     * @return string HTML carte simple
     */
    private static function render_fallback_card($game) {
        // Extraire les données selon le format
        $name = '';
        $description = '';
        $game_id = self::extract_game_id($game);
        
        if (is_array($game)) {
            $name = $game['name'] ?? $game['title'] ?? 'Jeu sans nom';
            $description = $game['description'] ?? '';
        } elseif (is_object($game)) {
            $name = $game->name ?? 'Jeu sans nom';
            $description = get_term_meta($game->term_id, 'game_description', true) ?? '';
        }
        
        ob_start();
        ?>
        <div class="sisme-search-card sisme-fallback-card">
            <div class="sisme-fallback-card-header">
                <h3 class="sisme-fallback-card-title"><?php echo esc_html($name); ?></h3>
                <?php if ($game_id): ?>
                    <span class="sisme-fallback-card-id">ID: <?php echo esc_html($game_id); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($description)): ?>
                <div class="sisme-fallback-card-description">
                    <p><?php echo esc_html(wp_trim_words($description, 20)); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="sisme-fallback-card-footer">
                <small>Mode fallback - Vérifiez la compatibilité Cards API</small>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <details>
                        <summary>Debug données</summary>
                        <pre><?php echo esc_html(print_r($game, true)); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 🔍 Extraire l'ID du jeu selon le format des données
     * 
     * @param mixed $game Données du jeu (format variable selon la source)
     * @return int|false ID du jeu ou false si non trouvé
     */
    private static function extract_game_id($game) {
        // Cas 1: Tableau avec clé 'id'
        if (is_array($game) && isset($game['id'])) {
            return intval($game['id']);
        }
        
        // Cas 2: Tableau avec clé 'term_id'  
        if (is_array($game) && isset($game['term_id'])) {
            return intval($game['term_id']);
        }
        
        // Cas 3: Objet WP_Term
        if (is_object($game) && isset($game->term_id)) {
            return intval($game->term_id);
        }
        
        // Cas 4: Tableau avec clé 'game_id'
        if (is_array($game) && isset($game['game_id'])) {
            return intval($game['game_id']);
        }
        
        // Cas 5: ID direct (entier)
        if (is_numeric($game)) {
            return intval($game);
        }
        
        // Debug pour comprendre le format
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Format de données jeu non reconnu: ' . print_r($game, true));
        }
        
        return false;
    }
    
    /**
     * Rendu des résultats de recherche
     * 🚀 Utilise le système Filters + Cards pour les vrais résultats
     * 
     * @param array $search_params Paramètres de recherche
     * @return string HTML des résultats
     */
    private static function render_search_results_html($search_params) {
        // Vérifier que le module de filtres est disponible
        if (!class_exists('Sisme_Search_Filters')) {
            return '<div class="sisme-search-error"><p>' . __('Erreur: Module de filtres non disponible', 'sisme-games-editor') . '</p></div>';
        }
        
        try {
            // Effectuer la recherche avec les paramètres
            $results = Sisme_Search_Filters::perform_search($search_params);
            
            // Si aucun résultat
            if (empty($results['games'])) {
                return self::render_no_results_html($search_params);
            }
            
            // Déterminer le type de vue
            $view_type = $search_params['view'] ?? 'grid';
            
            // Générer le HTML complet
            ob_start();
            ?>
            <div class="sisme-search-results-container">
                <!-- Compteur de résultats -->
                <div class="sisme-search-counter" style="display: block;">
                    <strong>
                        <?php 
                        echo Sisme_Search_Filters::get_search_summary($search_params, $results['total']);
                        ?>
                    </strong>
                    <?php if (!empty($search_params['query']) || !empty($search_params['genres'])): ?>
                        <button class="sisme-clear-search" onclick="window.location.reload();">
                            <?php esc_html_e('🔄 Effacer les filtres', 'sisme-games-editor'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Grille des résultats -->
                <?php echo self::render_games_grid($results['games'], $view_type); ?>
                
                <!-- Pagination -->
                <?php if ($results['has_more']): ?>
                    <div class="sisme-search-pagination">
                        <button class="sisme-load-more" id="sismeLoadMore" 
                                data-page="<?php echo esc_attr($results['page'] + 1); ?>"
                                data-total-pages="<?php echo esc_attr($results['total_pages']); ?>"
                                style="display: block;">
                            📚 <?php esc_html_e('Charger plus de jeux', 'sisme-games-editor'); ?>
                            <span class="sisme-load-more-info">
                                (<?php echo sprintf(__('Page %d sur %d', 'sisme-games-editor'), $results['page'], $results['total_pages']); ?>)
                            </span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Error in render_search_results_html: ' . $e->getMessage());
            }
            
            return '<div class="sisme-search-error"><p>' . __('Erreur lors de la recherche', 'sisme-games-editor') . '</p></div>';
        }
    }

    /**
     * 📭 Rendu quand aucun résultat trouvé
     * 
     * @param array $search_params Paramètres de recherche
     * @return string HTML pour aucun résultat
     */
    private static function render_no_results_html($search_params) {
        ob_start();
        ?>
        <div class="sisme-search-no-results">
            <div class="sisme-empty-icon">🔍</div>
            <h3><?php esc_html_e('Aucun jeu trouvé', 'sisme-games-editor'); ?></h3>
            
            <?php if (!empty($search_params['query'])): ?>
                <p><?php echo sprintf(__('Aucun résultat pour "%s"', 'sisme-games-editor'), esc_html($search_params['query'])); ?></p>
            <?php else: ?>
                <p><?php esc_html_e('Aucun jeu ne correspond aux filtres sélectionnés', 'sisme-games-editor'); ?></p>
            <?php endif; ?>
            
            <!-- Suggestions alternatives -->
            <div class="sisme-no-results-suggestions">
                <h4><?php esc_html_e('Suggestions :', 'sisme-games-editor'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Vérifiez l\'orthographe', 'sisme-games-editor'); ?></li>
                    <li><?php esc_html_e('Essayez des mots-clés plus généraux', 'sisme-games-editor'); ?></li>
                    <li><?php esc_html_e('Réduisez le nombre de filtres', 'sisme-games-editor'); ?></li>
                </ul>
                
                <button class="sisme-btn sisme-btn--secondary" onclick="window.location.reload();">
                    <?php esc_html_e('🔄 Voir tous les jeux', 'sisme-games-editor'); ?>
                </button>
            </div>
            
            <!-- Suggestions populaires -->
            <?php 
            if (class_exists('Sisme_Search_Suggestions')) {
                $popular = Sisme_Search_Suggestions::get_popular_searches(5);
                if (!empty($popular)): 
            ?>
                <div class="sisme-popular-alternatives">
                    <h4><?php esc_html_e('Recherches populaires :', 'sisme-games-editor'); ?></h4>
                    <div class="sisme-popular-tags">
                        <?php foreach ($popular as $term => $display): ?>
                            <button class="sisme-popular-tag" data-term="<?php echo esc_attr($term); ?>">
                                <?php echo esc_html($display); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                endif;
            }
            ?>
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
     * Obtenir le nombre d'éléments pour un filtre rapide
     * 
     * @param string $filter_type Type de filtre
     * @return int Nombre d'éléments
     */
    private static function get_quick_filter_count($filter_type) {
        // Récupérer les stats depuis search-filters.php
        if (class_exists('Sisme_Search_Filters')) {
            $stats = Sisme_Search_Filters::get_quick_filter_stats();
            
            // Mapping des anciens noms vers les nouveaux
            $mapping = array(
                'popular' => 'popular',           // Métrique future (0 pour l'instant)
                'new' => 'new',
                'featured' => 'is_team_choice',
                'upcoming' => 'is_comming'
            );
            
            $stat_key = isset($mapping[$filter_type]) ? $mapping[$filter_type] : $filter_type;
            
            return isset($stats[$stat_key]) ? $stats[$stat_key] : 0;
        }
        
        // Valeurs par défaut
        $defaults = array(
            'popular' => 0,       // Futur
            'new' => 12,
            'featured' => 8,      // Coups de cœur
            'upcoming' => 6       // À venir
        );
        
        return isset($defaults[$filter_type]) ? $defaults[$filter_type] : 0;
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

    /**
     * 🧪 MÉTHODE DE DEBUG: Analyser le format des données retournées par Search_Filters
     * 
     * @param array $games Premier échantillon de jeux
     * @return string Rapport de debug
     */
    private static function debug_games_format($games) {
        if (empty($games) || !defined('WP_DEBUG') || !WP_DEBUG) {
            return '';
        }
        
        $first_game = $games[0];
        
        ob_start();
        ?>
        <div class="sisme-debug-panel" style="background: #333; color: #fff; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa;">
            <h4>🧪 Debug Format des données jeux</h4>
            <p><strong>Type:</strong> <?php echo gettype($first_game); ?></p>
            <p><strong>Nombre de jeux:</strong> <?php echo count($games); ?></p>
            
            <?php if (is_array($first_game)): ?>
                <p><strong>Clés disponibles:</strong> <?php echo implode(', ', array_keys($first_game)); ?></p>
            <?php elseif (is_object($first_game)): ?>
                <p><strong>Propriétés object:</strong> <?php echo implode(', ', array_keys(get_object_vars($first_game))); ?></p>
            <?php endif; ?>
            
            <details>
                <summary>Échantillon données brutes</summary>
                <pre style="background: #222; padding: 10px; overflow: auto; max-height: 200px;"><?php echo esc_html(print_r($first_game, true)); ?></pre>
            </details>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialisation de l'API
Sisme_Search_API::init();