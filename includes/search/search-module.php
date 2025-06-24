<?php
/**
 * File: /sisme-games-editor/includes/search/search-module.php
 * Module de recherche gaming personnalisé - Sisme Games Editor
 * 
 * Remplace la recherche WordPress standard par une recherche gaming avancée
 * avec filtres, tri et affichage par cartes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init_search_system'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_search_assets'));
        add_action('wp_ajax_sisme_search', array($this, 'handle_ajax_search'));
        add_action('wp_ajax_nopriv_sisme_search', array($this, 'handle_ajax_search'));
        
        // Remplacer la recherche WordPress par défaut
        add_filter('template_include', array($this, 'override_search_template'));
        add_action('pre_get_posts', array($this, 'modify_search_query'));
    }
    
    /**
     * Initialiser le système de recherche
     */
    public function init_search_system() {
        // Enregistrer les shortcodes de recherche
        add_shortcode('sisme_search', array($this, 'render_search_interface'));
        add_shortcode('sisme_search_results', array($this, 'render_search_results'));
        add_shortcode('sisme_search_filters', array($this, 'render_search_filters'));
    }
    
    /**
     * Charger les assets CSS/JS pour la recherche
     */
    public function enqueue_search_assets() {
        // Charger uniquement sur les pages de recherche ou avec shortcode
        if (is_search() || $this->has_search_shortcode()) {
            wp_enqueue_style(
                'sisme-search',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/search.css',
                array('sisme-frontend-tokens-global'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_script(
                'sisme-search-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/search.js',
                array('jquery'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            // Variables pour JavaScript
            wp_localize_script('sisme-search-js', 'sismeSearch', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_search_nonce'),
                'loadingText' => __('Recherche en cours...', 'sisme-games-editor'),
                'noResultsText' => __('Aucun jeu trouvé', 'sisme-games-editor'),
                'searchPlaceholder' => __('Rechercher un jeu...', 'sisme-games-editor')
            ));
        }
    }
    
    /**
     * Shortcode interface de recherche complète
     */
    public function render_search_interface($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'true',
            'show_sorting' => 'true',
            'results_per_page' => '12',
            'cards_per_row' => '4',
            'card_type' => 'normal',
            'container_class' => 'sisme-search-interface'
        ), $atts);
        
        // Récupérer les paramètres de recherche actuels
        $search_query = get_search_query();
        $selected_genres = $this->get_search_param('genres', array());
        $selected_platforms = $this->get_search_param('platforms', array());
        $sort_by = $this->get_search_param('sort', 'relevance');
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>" id="sismeSearchInterface">
            
            <!-- Barre de recherche principale -->
            <div class="sisme-search-bar">
                <div class="sisme-search-input-container">
                    <input type="text" 
                           id="sismeSearchInput" 
                           class="sisme-search-input"
                           placeholder="<?php echo esc_attr__('Rechercher un jeu...', 'sisme-games-editor'); ?>"
                           value="<?php echo esc_attr($search_query); ?>">
                    <button type="button" class="sisme-search-btn" id="sismeSearchBtn">
                        🔍 <?php esc_html_e('Rechercher', 'sisme-games-editor'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN)): ?>
            <!-- Filtres avancés -->
            <div class="sisme-search-filters" id="sismeSearchFilters">
                <div class="sisme-filters-row">
                    
                    <!-- Filtre genres -->
                    <div class="sisme-filter-group">
                        <label class="sisme-filter-label">
                            🎮 <?php esc_html_e('Genres', 'sisme-games-editor'); ?>
                        </label>
                        <select multiple class="sisme-filter-select" id="sismeGenresFilter">
                            <?php echo $this->render_genres_options($selected_genres); ?>
                        </select>
                    </div>
                    
                    <!-- Filtre plateformes -->
                    <div class="sisme-filter-group">
                        <label class="sisme-filter-label">
                            💻 <?php esc_html_e('Plateformes', 'sisme-games-editor'); ?>
                        </label>
                        <select multiple class="sisme-filter-select" id="sismePlatformsFilter">
                            <?php echo $this->render_platforms_options($selected_platforms); ?>
                        </select>
                    </div>
                    
                    <!-- Filtre statut -->
                    <div class="sisme-filter-group">
                        <label class="sisme-filter-label">
                            📅 <?php esc_html_e('Statut', 'sisme-games-editor'); ?>
                        </label>
                        <select class="sisme-filter-select" id="sismeStatusFilter">
                            <option value=""><?php esc_html_e('Tous', 'sisme-games-editor'); ?></option>
                            <option value="released"><?php esc_html_e('Sortis', 'sisme-games-editor'); ?></option>
                            <option value="upcoming"><?php esc_html_e('À venir', 'sisme-games-editor'); ?></option>
                        </select>
                    </div>
                    
                </div>
                
                <div class="sisme-filters-actions">
                    <button type="button" class="sisme-filter-apply" id="sismeApplyFilters">
                        ✅ <?php esc_html_e('Appliquer', 'sisme-games-editor'); ?>
                    </button>
                    <button type="button" class="sisme-filter-reset" id="sismeResetFilters">
                        🔄 <?php esc_html_e('Réinitialiser', 'sisme-games-editor'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (filter_var($atts['show_sorting'], FILTER_VALIDATE_BOOLEAN)): ?>
            <!-- Options de tri -->
            <div class="sisme-search-sorting">
                <label for="sismeSortBy" class="sisme-sort-label">
                    📊 <?php esc_html_e('Trier par:', 'sisme-games-editor'); ?>
                </label>
                <select id="sismeSortBy" class="sisme-sort-select">
                    <option value="relevance" <?php selected($sort_by, 'relevance'); ?>>
                        <?php esc_html_e('Pertinence', 'sisme-games-editor'); ?>
                    </option>
                    <option value="name_asc" <?php selected($sort_by, 'name_asc'); ?>>
                        <?php esc_html_e('Nom A→Z', 'sisme-games-editor'); ?>
                    </option>
                    <option value="name_desc" <?php selected($sort_by, 'name_desc'); ?>>
                        <?php esc_html_e('Nom Z→A', 'sisme-games-editor'); ?>
                    </option>
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>
                        <?php esc_html_e('Plus récents', 'sisme-games-editor'); ?>
                    </option>
                    <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>>
                        <?php esc_html_e('Plus anciens', 'sisme-games-editor'); ?>
                    </option>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Zone de résultats -->
            <div class="sisme-search-results" id="sismeSearchResults">
                <?php
                // Afficher les résultats initiaux si on est sur une page de recherche
                if (is_search() && !empty($search_query)) {
                    echo $this->render_search_results_content($atts);
                } else {
                    echo $this->render_empty_state();
                }
                ?>
            </div>
            
            <!-- Loader -->
            <div class="sisme-search-loader" id="sismeSearchLoader" style="display: none;">
                <div class="sisme-loader-spinner"></div>
                <p><?php esc_html_e('Recherche en cours...', 'sisme-games-editor'); ?></p>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Traitement AJAX des recherches
     */
    public function handle_ajax_search() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            wp_die('Sécurité échouée');
        }
        
        // Récupérer les paramètres de recherche
        $search_params = array(
            'query' => sanitize_text_field($_POST['query'] ?? ''),
            'genres' => array_map('intval', $_POST['genres'] ?? array()),
            'platforms' => array_map('sanitize_text_field', $_POST['platforms'] ?? array()),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'sort' => sanitize_text_field($_POST['sort'] ?? 'relevance'),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 12),
            'cards_per_row' => intval($_POST['cards_per_row'] ?? 4),
            'card_type' => sanitize_text_field($_POST['card_type'] ?? 'normal')
        );
        
        // Effectuer la recherche
        $results = $this->perform_search($search_params);
        
        // Retourner les résultats en JSON
        wp_send_json_success($results);
    }
    
    /**
     * Effectuer la recherche avec les paramètres donnés
     */
    private function perform_search($params) {
        // Construire les critères pour le système Cards existant
        $criteria = array();
        
        // Recherche textuelle
        if (!empty($params['query'])) {
            $criteria['search'] = $params['query'];
        }
        
        // Filtres genres
        if (!empty($params['genres'])) {
            $criteria['genres'] = $params['genres'];
        }
        
        // Filtres plateformes
        if (!empty($params['platforms'])) {
            $criteria['platforms'] = $params['platforms'];
        }
        
        // Filtre statut (sorti/à venir)
        if (!empty($params['status'])) {
            $criteria['released'] = ($params['status'] === 'released') ? 1 : 0;
        }
        
        // Tri
        switch ($params['sort']) {
            case 'name_asc':
                $criteria['orderby'] = 'name';
                $criteria['order'] = 'ASC';
                break;
            case 'name_desc':
                $criteria['orderby'] = 'name';
                $criteria['order'] = 'DESC';
                break;
            case 'date_desc':
                $criteria['sort_by_date'] = true;
                $criteria['order'] = 'DESC';
                break;
            case 'date_asc':
                $criteria['sort_by_date'] = true;
                $criteria['order'] = 'ASC';
                break;
            default:
                // Pertinence par défaut
                break;
        }
        
        // Utiliser le système Cards existant pour récupérer les jeux
        if (!class_exists('Sisme_Cards_Functions')) {
            return array(
                'html' => '<p>Erreur: Module Cards non disponible</p>',
                'count' => 0,
                'has_more' => false
            );
        }
        
        $games = Sisme_Cards_Functions::get_games_by_criteria($criteria);
        
        // Pagination
        $total_games = count($games);
        $offset = ($params['page'] - 1) * $params['per_page'];
        $games_page = array_slice($games, $offset, $params['per_page']);
        
        // Générer le HTML avec le système Cards
        $html = $this->render_games_grid($games_page, $params);
        
        return array(
            'html' => $html,
            'count' => $total_games,
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'has_more' => ($offset + $params['per_page']) < $total_games,
            'query_info' => $this->get_search_summary($params, $total_games)
        );
    }
    
    /**
     * Générer la grille de jeux avec le système Cards
     */
    private function render_games_grid($games, $params) {
        if (empty($games)) {
            return $this->render_no_results();
        }
        
        // Utiliser le système Cards API existant
        if (!class_exists('Sisme_Cards_API')) {
            return '<p>Erreur: Cards API non disponible</p>';
        }
        
        $cards_html = '';
        foreach ($games as $game) {
            $cards_html .= Sisme_Cards_API::render_card(
                $game['id'], 
                $params['card_type'], 
                array(
                    'show_description' => true,
                    'show_genres' => true,
                    'show_date' => true,
                    'css_class' => 'sisme-search-card'
                )
            );
        }
        
        // Wrapper grille
        $grid_class = 'sisme-search-grid sisme-cards-grid-' . $params['cards_per_row'];
        
        return '<div class="' . esc_attr($grid_class) . '">' . $cards_html . '</div>';
    }
    
    /**
     * Générer les options de genres pour le filtre
     */
    private function render_genres_options($selected = array()) {
        $genres = get_categories(array(
            'taxonomy' => 'category',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_sisme_is_game_genre',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        ));
        
        $options = '<option value="">' . esc_html__('Tous les genres', 'sisme-games-editor') . '</option>';
        
        foreach ($genres as $genre) {
            $selected_attr = in_array($genre->term_id, $selected) ? 'selected' : '';
            $genre_name = str_replace('jeux-', '', $genre->name);
            $options .= sprintf(
                '<option value="%d" %s>%s</option>',
                $genre->term_id,
                $selected_attr,
                esc_html($genre_name)
            );
        }
        
        return $options;
    }
    
    /**
     * Générer les options de plateformes pour le filtre
     */
    private function render_platforms_options($selected = array()) {
        $platforms = array(
            'PC' => 'PC',
            'PlayStation 5' => 'PS5',
            'PlayStation 4' => 'PS4',
            'Xbox Series X|S' => 'Xbox Series',
            'Xbox One' => 'Xbox One',
            'Nintendo Switch' => 'Switch',
            'Mobile' => 'Mobile'
        );
        
        $options = '<option value="">' . esc_html__('Toutes les plateformes', 'sisme-games-editor') . '</option>';
        
        foreach ($platforms as $platform_key => $platform_label) {
            $selected_attr = in_array($platform_key, $selected) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($platform_key),
                $selected_attr,
                esc_html($platform_label)
            );
        }
        
        return $options;
    }
    
    /**
     * État vide (aucune recherche)
     */
    private function render_empty_state() {
        return '<div class="sisme-search-empty-state">
            <div class="sisme-empty-icon">🎮</div>
            <h3>' . esc_html__('Recherchez votre jeu', 'sisme-games-editor') . '</h3>
            <p>' . esc_html__('Utilisez la barre de recherche ci-dessus pour trouver des jeux', 'sisme-games-editor') . '</p>
        </div>';
    }
    
    /**
     * Aucun résultat trouvé
     */
    private function render_no_results() {
        return '<div class="sisme-search-no-results">
            <div class="sisme-empty-icon">😞</div>
            <h3>' . esc_html__('Aucun jeu trouvé', 'sisme-games-editor') . '</h3>
            <p>' . esc_html__('Essayez de modifier vos critères de recherche', 'sisme-games-editor') . '</p>
        </div>';
    }
    
    /**
     * Résumé de la recherche
     */
    private function get_search_summary($params, $total) {
        if (empty($params['query']) && empty($params['genres']) && empty($params['platforms'])) {
            return sprintf(
                esc_html__('%d jeux au total', 'sisme-games-editor'),
                $total
            );
        }
        
        $summary = sprintf(
            esc_html__('%d jeu(x) trouvé(s)', 'sisme-games-editor'),
            $total
        );
        
        if (!empty($params['query'])) {
            $summary .= sprintf(
                esc_html__(' pour "%s"', 'sisme-games-editor'),
                esc_html($params['query'])
            );
        }
        
        return $summary;
    }
    
    /**
     * Vérifier si la page contient un shortcode de recherche
     */
    private function has_search_shortcode() {
        global $post;
        if (is_object($post)) {
            return has_shortcode($post->post_content, 'sisme_search') ||
                   has_shortcode($post->post_content, 'sisme_search_results') ||
                   has_shortcode($post->post_content, 'sisme_search_filters');
        }
        return false;
    }
    
    /**
     * Récupérer un paramètre de recherche depuis URL
     */
    private function get_search_param($param, $default = '') {
        return isset($_GET[$param]) ? sanitize_text_field($_GET[$param]) : $default;
    }
    
    /**
     * Remplacer le template de recherche WordPress
     */
    public function override_search_template($template) {
        if (is_search()) {
            $custom_template = SISME_GAMES_EDITOR_PLUGIN_DIR . 'templates/search.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    /**
     * Modifier la requête de recherche WordPress
     */
    public function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            // Modifier la requête pour inclure les tags de jeux
            $query->set('post_type', array('post'));
            // Autres modifications si nécessaire
        }
    }
}

// Initialiser le module
Sisme_Search_Module::get_instance();