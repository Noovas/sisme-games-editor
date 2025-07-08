<?php
/**
 * File: /sisme-games-editor/includes/search/search-api.php
 * MODULE SEARCH REFAIT - API et Shortcode
 * 
 * FONCTIONNALITÉS:
 * - Shortcode unique [sisme_search]
 * - Recherche par titre, genre, statut
 * - Affichage dans section hero
 * - Intégration avec cards-grid
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_API {
    
    /**
     * Initialiser l'API
     */
    public static function init() {
        // Enregistrer le shortcode principal
        add_shortcode('sisme_search', array(__CLASS__, 'render_search_interface'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Search API] Shortcode enregistré');
        }
    }
    
    /**
     * Rendu de l'interface de recherche
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML de l'interface
     */
    public static function render_search_interface($atts) {
        $atts = shortcode_atts(array(
            'hero_title' => '🔍 Rechercher un jeu',
            'hero_subtitle' => 'Trouvez vos jeux préférés parmi notre collection',
            'placeholder' => 'Nom du jeu...',
            'columns' => '4',
            'max_results' => '12',
            'debug' => 'false'
        ), $atts);
        
        // Vérifier les dépendances
        if (!class_exists('Sisme_Utils_Games') || !class_exists('Sisme_Cards_API')) {
            return '<div class="sisme-search-error">Module de recherche non disponible</div>';
        }
        
        // Générer l'HTML
        ob_start();
        self::render_search_html($atts);
        return ob_get_clean();
    }
    
    /**
     * Rendu HTML de l'interface de recherche
     * 
     * @param array $atts Attributs validés
     */
    private static function render_search_html($atts) {
        $debug = filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN);
        $unique_id = 'sisme-search-' . uniqid();
        
        ?>
        <div class="sisme-search-container" id="<?php echo esc_attr($unique_id); ?>">
            
            <!-- Section Hero avec formulaire -->
            <section class="sisme-game-hero sisme-search-hero">
                <div class="sisme-search-hero-content">
                    
                    <!-- Titre et sous-titre -->
                    <div class="sisme-search-hero-header">
                        <h1 class="sisme-search-hero-title"><?php echo esc_html($atts['hero_title']); ?></h1>
                        <p class="sisme-search-hero-subtitle"><?php echo esc_html($atts['hero_subtitle']); ?></p>
                    </div>
                    
                    <!-- Formulaire de recherche -->
                    <form class="sisme-search-form" role="search">
                        
                        <!-- Champ texte -->
                        <div class="sisme-search-field sisme-search-field--text">
                            <label for="<?php echo esc_attr($unique_id); ?>-query" class="sisme-search-label">
                                Recherche par titre
                            </label>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr($unique_id); ?>-query"
                                name="query"
                                class="sisme-search-input"
                                placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                                autocomplete="off"
                            />
                        </div>
                        
                        <!-- Filtres -->
                        <div class="sisme-search-filters">
                            
                            <!-- Sélecteur de genres -->
                            <div class="sisme-search-field sisme-search-field--genre">
                                <label for="<?php echo esc_attr($unique_id); ?>-genre" class="sisme-search-label">
                                    Genre
                                </label>
                                <select 
                                    id="<?php echo esc_attr($unique_id); ?>-genre"
                                    name="genre"
                                    class="sisme-search-select"
                                >
                                    <option value="">Tous les genres</option>
                                    <?php self::render_genre_options(); ?>
                                </select>
                            </div>
                            
                            <!-- Sélecteur de statut -->
                            <div class="sisme-search-field sisme-search-field--status">
                                <label for="<?php echo esc_attr($unique_id); ?>-status" class="sisme-search-label">
                                    Statut
                                </label>
                                <select 
                                    id="<?php echo esc_attr($unique_id); ?>-status"
                                    name="status"
                                    class="sisme-search-select"
                                >
                                    <option value="">Tous</option>
                                    <option value="released">Sortis</option>
                                    <option value="upcoming">À venir</option>
                                </select>
                            </div>
                            
                        </div>
                        
                        <!-- Bouton de recherche -->
                        <button type="submit" class="sisme-search-button">
                            <span class="sisme-search-button-text">Rechercher</span>
                            <span class="sisme-search-button-loading" style="display: none;">Recherche...</span>
                        </button>
                        
                    </form>
                    
                </div>
            </section>
            
            <!-- Zone de résultats -->
            <section class="sisme-search-results" id="<?php echo esc_attr($unique_id); ?>-results">
                <?php self::render_initial_results($atts); ?>
            </section>
            
            <!-- Bouton Charger plus -->
            <div class="sisme-search-load-more" style="display: none;">
                <button type="button" class="sisme-search-load-more-btn">
                    <span class="sisme-load-more-text">Charger plus de résultats</span>
                    <span class="sisme-load-more-loading" style="display: none;">Chargement...</span>
                </button>
            </div>
            
            <!-- Debug info -->
            <?php if ($debug): ?>
            <div class="sisme-search-debug">
                <h3>Debug Info</h3>
                <pre><?php echo esc_html(print_r($atts, true)); ?></pre>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Script inline pour initialiser -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof sismeSearchInit === 'function') {
                sismeSearchInit('<?php echo esc_js($unique_id); ?>', <?php echo wp_json_encode($atts); ?>);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Rendu des options de genres
     */
    private static function render_genre_options() {
        if (!class_exists('Sisme_Utils_Games')) {
            return;
        }
        
        // Récupérer la catégorie parent "jeux-video"
        $parent_category = get_category_by_slug('jeux-video');
        if (!$parent_category) {
            $parent_category = get_term_by('name', 'Jeux', 'category');
        }
        
        if (!$parent_category) {
            return;
        }
        
        // Récupérer les genres (catégories enfants)
        $genres = get_categories(array(
            'parent' => $parent_category->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        // Exclure les modes de jeu
        $excluded_slugs = array('jeux-solo', 'jeux-multijoueur', 'jeux-cooperatif', 'jeux-competitif');
        
        if (!is_wp_error($genres) && !empty($genres)) {
            foreach ($genres as $genre) {
                // Filtrer les modes de jeu
                if (in_array($genre->slug, $excluded_slugs)) {
                    continue;
                }
                
                // Nettoyer le nom (enlever le préfixe "jeux-")
                $genre_name = str_replace('jeux-', '', $genre->name);
                $genre_name = ucfirst($genre_name);
                
                echo '<option value="' . esc_attr($genre->slug) . '">' . esc_html($genre_name) . '</option>';
            }
        }
    }
    
    /**
     * Rendu des résultats initiaux
     * 
     * @param array $atts Attributs du shortcode
     */
    private static function render_initial_results($atts) {
        $max_results = intval($atts['max_results']);
        
        // Récupérer TOUS les jeux pour compter le total
        $all_games_criteria = array(
            'sort_by_date' => true,
            'sort_order' => 'desc',
            'max_results' => -1 // Tous les jeux
        );
        
        if (class_exists('Sisme_Utils_Games')) {
            $all_game_ids = Sisme_Utils_Games::get_games_by_criteria($all_games_criteria);
            $total_games = count($all_game_ids);
            $has_more = $total_games > $max_results;
            
            // Afficher seulement les premiers résultats
            $initial_args = array(
                'type' => 'normal',
                'cards_per_row' => intval($atts['columns']),
                'max_cards' => $max_results,
                'sort_by_date' => true,
                'sort_order' => 'desc',
                'container_class' => 'sisme-search-initial-results'
            );
            
            if (class_exists('Sisme_Cards_API')) {
                echo Sisme_Cards_API::render_cards_grid($initial_args);
            }
            
            // Ajouter les métadonnées de pagination en JSON caché
            echo '<script type="application/json" class="sisme-initial-pagination" style="display: none;">';
            echo wp_json_encode(array(
                'total_games' => $total_games,
                'current_page' => 1,
                'has_more' => $has_more,
                'max_results' => $max_results
            ));
            echo '</script>';
            
        } else {
            echo '<div class="sisme-search-error">Module cards non disponible</div>';
        }
    }
    
    /**
     * Effectuer une recherche (utilisé par AJAX)
     * 
     * @param array $params Paramètres de recherche
     * @return array Résultats de la recherche
     */
    public static function perform_search($params) {
        // Valider les paramètres
        $validated = self::validate_search_params($params);
        
        if (!$validated['valid']) {
            return array(
                'success' => false,
                'message' => $validated['message']
            );
        }
        
        // Construire les critères pour utils-games
        $criteria = self::build_search_criteria($validated['params']);
        
        // Effectuer la recherche
        if (class_exists('Sisme_Utils_Games')) {
            $game_ids = Sisme_Utils_Games::get_games_by_criteria($criteria);
        } else {
            return array(
                'success' => false,
                'message' => 'Module de recherche non disponible'
            );
        }
        
        // Générer le HTML des résultats
        $html = self::generate_results_html($game_ids, $validated['params']);
        
        return array(
            'success' => true,
            'html' => $html,
            'total' => count($game_ids),
            'params' => $validated['params']
        );
    }
    
    /**
     * Valider les paramètres de recherche
     * 
     * @param array $params Paramètres bruts
     * @return array Résultat de validation
     */
    private static function validate_search_params($params) {
        $validated = array(
            'query' => sanitize_text_field($params['query'] ?? ''),
            'genre' => sanitize_text_field($params['genre'] ?? ''),
            'status' => sanitize_text_field($params['status'] ?? ''),
            'columns' => max(1, min(6, intval($params['columns'] ?? 4))),
            'max_results' => max(1, min(50, intval($params['max_results'] ?? 12)))
        );
        
        // Vérifier qu'au moins un critère est fourni
        if (empty($validated['query']) && empty($validated['genre']) && empty($validated['status'])) {
            return array(
                'valid' => false,
                'message' => 'Aucun critère de recherche fourni'
            );
        }
        
        return array(
            'valid' => true,
            'params' => $validated
        );
    }
    
    /**
     * Construire les critères pour utils-games
     * 
     * @param array $params Paramètres validés
     * @return array Critères pour get_games_by_criteria
     */
    private static function build_search_criteria($params) {
        $criteria = array(
            'sort_by_date' => true,
            'sort_order' => 'desc',
            'max_results' => $params['max_results'],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        );
        
        // Recherche textuelle
        if (!empty($params['query'])) {
            $criteria['search'] = $params['query'];
        }
        
        // Filtre par genre
        if (!empty($params['genre'])) {
            $criteria[Sisme_Utils_Games::KEY_GENRES] = array($params['genre']);
        }
        
        // Filtre par statut
        if (!empty($params['status'])) {
            if ($params['status'] === 'released') {
                $criteria['released'] = 1;
            } elseif ($params['status'] === 'upcoming') {
                $criteria['released'] = -1;
            }
        }
        
        return $criteria;
    }
    
    /**
     * Générer le HTML des résultats
     * 
     * @param array $game_ids IDs des jeux trouvés
     * @param array $params Paramètres de recherche
     * @return string HTML des résultats
     */
    private static function generate_results_html($game_ids, $params) {
        if (empty($game_ids)) {
            return '<div class="sisme-search-no-results">' .
                   '<p>Aucun jeu trouvé pour vos critères de recherche.</p>' .
                   '</div>';
        }
        
        // Utiliser Cards API pour générer la grille
        $grid_args = array(
            'type' => 'normal',
            'cards_per_row' => $params['columns'],
            'max_cards' => count($game_ids),
            'container_class' => 'sisme-search-results-grid'
        );
        
        // Simuler les critères pour Cards API
        $grid_args['game_ids'] = $game_ids;
        
        if (class_exists('Sisme_Cards_API')) {
            return Sisme_Cards_API::render_cards_grid($grid_args);
        } else {
            return '<div class="sisme-search-error">Module cards non disponible</div>';
        }
    }
}