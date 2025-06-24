<?php
/**
 * File: /sisme-games-editor/includes/search/search-ajax.php
 * Module de recherche gaming - Handlers AJAX et interactions temps réel
 * 
 * Responsabilités :
 * - Gestion des requêtes AJAX de recherche
 * - Rendu des résultats en HTML
 * - Gestion de la pagination
 * - Suggestions et auto-complétion
 * - Statistiques des filtres rapides
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Ajax {
    
    /**
     * Initialisation des handlers AJAX
     */
    public static function init() {
        // Handlers AJAX pour les utilisateurs connectés et non connectés
        add_action('wp_ajax_sisme_search', array(__CLASS__, 'handle_search_request'));
        add_action('wp_ajax_nopriv_sisme_search', array(__CLASS__, 'handle_search_request'));
        
        add_action('wp_ajax_sisme_search_suggestions', array(__CLASS__, 'handle_suggestions_request'));
        add_action('wp_ajax_nopriv_sisme_search_suggestions', array(__CLASS__, 'handle_suggestions_request'));
        
        add_action('wp_ajax_sisme_search_quick_stats', array(__CLASS__, 'handle_quick_stats_request'));
        add_action('wp_ajax_nopriv_sisme_search_quick_stats', array(__CLASS__, 'handle_quick_stats_request'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search AJAX: Handlers registered');
        }
    }
    
    /**
     * Handler principal pour les requêtes de recherche
     */
    public static function handle_search_request() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            wp_send_json_error(array(
                'message' => __('Erreur de sécurité', 'sisme-games-editor')
            ));
        }
        
        // Récupérer et valider les paramètres
        $search_params = self::extract_search_params($_POST);
        
        // Vérifier que le module de filtres est disponible
        if (!class_exists('Sisme_Search_Filters')) {
            wp_send_json_error(array(
                'message' => __('Module de filtres non disponible', 'sisme-games-editor')
            ));
        }
        
        try {
            // Effectuer la recherche
            $results = Sisme_Search_Filters::perform_search($search_params);
            
            // Générer le HTML des résultats
            $html = self::render_results_html($results, $search_params);
            
            // Générer le résumé de recherche
            $summary = Sisme_Search_Filters::get_search_summary($search_params, $results['total']);
            
            // Réponse de succès
            wp_send_json_success(array(
                'html' => $html,
                'summary' => $summary,
                'total' => $results['total'],
                'page' => $results['page'],
                'per_page' => $results['per_page'],
                'total_pages' => $results['total_pages'],
                'has_more' => $results['has_more'],
                'params' => $search_params
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search AJAX Error: ' . $e->getMessage());
            }
            
            wp_send_json_error(array(
                'message' => __('Erreur lors de la recherche', 'sisme-games-editor'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Extraire les paramètres de recherche depuis $_POST
     * 
     * @param array $post_data Données POST
     * @return array Paramètres de recherche
     */
    private static function extract_search_params($post_data) {
        return array(
            'query' => sanitize_text_field($post_data['query'] ?? ''),
            'genres' => self::extract_array_param($post_data, 'genres', 'int'),
            'platforms' => self::extract_array_param($post_data, 'platforms', 'string'),
            'status' => sanitize_text_field($post_data['status'] ?? ''),
            'quick_filter' => sanitize_text_field($post_data['quick_filter'] ?? ''),
            'sort' => sanitize_text_field($post_data['sort'] ?? 'relevance'),
            'page' => max(1, intval($post_data['page'] ?? 1)),
            'per_page' => max(1, min(50, intval($post_data['per_page'] ?? 12))),
            'view' => sanitize_text_field($post_data['view'] ?? 'grid')
        );
    }
    
    /**
     * Extraire un paramètre de type tableau depuis $_POST
     * 
     * @param array $post_data Données POST
     * @param string $key Clé du paramètre
     * @param string $type Type de validation ('int' ou 'string')
     * @return array Valeurs validées
     */
    private static function extract_array_param($post_data, $key, $type = 'string') {
        if (!isset($post_data[$key]) || !is_array($post_data[$key])) {
            return array();
        }
        
        $values = array();
        foreach ($post_data[$key] as $value) {
            if ($type === 'int') {
                $validated = intval($value);
                if ($validated > 0) {
                    $values[] = $validated;
                }
            } else {
                $validated = sanitize_text_field($value);
                if (!empty($validated)) {
                    $values[] = $validated;
                }
            }
        }
        
        return array_unique($values);
    }
    
    /**
     * Générer le HTML des résultats de recherche
     * 
     * @param array $results Résultats de la recherche
     * @param array $params Paramètres de recherche
     * @return string HTML des résultats
     */
    private static function render_results_html($results, $params) {
        if (empty($results['games'])) {
            return self::render_no_results_html($params);
        }
        
        // Déterminer le type de vue
        $view_type = $params['view'] ?? 'grid';
        $card_type = ($view_type === 'list') ? 'details' : 'normal';
        $grid_class = ($view_type === 'list') ? 'sisme-search-list' : 'sisme-search-grid';
        
        // Vérifier que l'API Cards est disponible
        if (!class_exists('Sisme_Cards_API')) {
            return '<p>' . __('Erreur: API Cards non disponible', 'sisme-games-editor') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($grid_class); ?>" data-view="<?php echo esc_attr($view_type); ?>">
            <?php
            foreach ($results['games'] as $game) {
                if (isset($game['id']) && $game['id'] > 0) {
                    echo Sisme_Cards_API::render_card(
                        $game['id'], 
                        $card_type, 
                        array(
                            'show_description' => true,
                            'show_genres' => true,
                            'show_platforms' => ($view_type === 'list'),
                            'show_date' => true,
                            'css_class' => 'sisme-search-card',
                            'max_genres' => ($view_type === 'list') ? 5 : 3,
                            'max_modes' => 4
                        )
                    );
                }
            }
            ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Générer le HTML quand aucun résultat trouvé
     * 
     * @param array $params Paramètres de recherche
     * @return string HTML état vide
     */
    private static function render_no_results_html($params) {
        ob_start();
        ?>
        <div class="sisme-search-no-results">
            <div class="sisme-empty-icon">😞</div>
            <h3><?php esc_html_e('Aucun jeu trouvé', 'sisme-games-editor'); ?></h3>
            <p><?php esc_html_e('Essayez de modifier vos critères de recherche', 'sisme-games-editor'); ?></p>
            
            <?php if (!empty($params['query']) || !empty($params['genres']) || !empty($params['platforms'])): ?>
            <div class="sisme-search-suggestions">
                <p><?php esc_html_e('Suggestions :', 'sisme-games-editor'); ?></p>
                <div class="sisme-suggestion-tags">
                    <?php
                    // Suggestions alternatives
                    $suggestions = self::get_alternative_suggestions($params);
                    foreach ($suggestions as $suggestion) {
                        echo '<button class="sisme-suggestion-tag" data-suggestion="' . esc_attr(json_encode($suggestion)) . '">';
                        echo esc_html($suggestion['label']);
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Obtenir des suggestions alternatives quand aucun résultat
     * 
     * @param array $params Paramètres de recherche actuels
     * @return array Suggestions alternatives
     */
    private static function get_alternative_suggestions($params) {
        $suggestions = array();
        
        // Suggestions génériques
        $generic_suggestions = array(
            array('label' => 'Action', 'type' => 'genre', 'value' => 'action'),
            array('label' => 'RPG', 'type' => 'genre', 'value' => 'rpg'),
            array('label' => 'PC', 'type' => 'platform', 'value' => 'pc'),
            array('label' => 'Console', 'type' => 'platform', 'value' => 'console'),
            array('label' => 'Nouveautés', 'type' => 'quick_filter', 'value' => 'new')
        );
        
        // Filtrer les suggestions qui ne sont pas déjà appliquées
        foreach ($generic_suggestions as $suggestion) {
            $already_applied = false;
            
            switch ($suggestion['type']) {
                case 'platform':
                    $already_applied = in_array($suggestion['value'], $params['platforms']);
                    break;
                case 'quick_filter':
                    $already_applied = ($params['quick_filter'] === $suggestion['value']);
                    break;
            }
            
            if (!$already_applied) {
                $suggestions[] = $suggestion;
            }
        }
        
        return array_slice($suggestions, 0, 5); // Maximum 5 suggestions
    }
    
    /**
     * Handler pour les suggestions de recherche
     */
    public static function handle_suggestions_request() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            wp_send_json_error(array(
                'message' => __('Erreur de sécurité', 'sisme-games-editor')
            ));
        }
        
        $term = sanitize_text_field($_POST['term'] ?? '');
        
        if (strlen($term) < 2) {
            wp_send_json_success(array());
        }
        
        // Rechercher dans les noms de jeux
        $suggestions = self::get_search_suggestions($term);
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * Obtenir des suggestions de recherche
     * 
     * @param string $term Terme de recherche partiel
     * @return array Suggestions
     */
    private static function get_search_suggestions($term) {
        $suggestions = array();
        
        // Rechercher dans les tags de jeux
        $tags = get_tags(array(
            'search' => $term,
            'number' => 10,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        foreach ($tags as $tag) {
            // Vérifier si c'est un tag de jeu (a des métadonnées game_*)
            $has_game_meta = get_term_meta($tag->term_id, 'game_description', true);
            
            if ($has_game_meta) {
                $suggestions[] = array(
                    'label' => $tag->name,
                    'value' => $tag->name,
                    'type' => 'game'
                );
            }
        }
        
        // Rechercher dans les genres
        $parent_category = get_category_by_slug('jeux-vidéo');
        if ($parent_category) {
            $genres = get_categories(array(
                'parent' => $parent_category->term_id,
                'search' => $term,
                'number' => 5
            ));
            
            foreach ($genres as $genre) {
                $genre_name = preg_replace('/^jeux-/i', '', $genre->name);
                $suggestions[] = array(
                    'label' => $genre_name,
                    'value' => $genre_name,
                    'type' => 'genre'
                );
            }
        }
        
        return array_slice($suggestions, 0, 8); // Maximum 8 suggestions
    }
    
    /**
     * Handler pour les statistiques des filtres rapides
     */
    public static function handle_quick_stats_request() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            wp_send_json_error(array(
                'message' => __('Erreur de sécurité', 'sisme-games-editor')
            ));
        }
        
        if (!class_exists('Sisme_Search_Filters')) {
            wp_send_json_error(array(
                'message' => __('Module de filtres non disponible', 'sisme-games-editor')
            ));
        }
        
        try {
            $stats = Sisme_Search_Filters::get_quick_filter_stats();
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search AJAX Stats Error: ' . $e->getMessage());
            }
            
            wp_send_json_error(array(
                'message' => __('Erreur lors de la récupération des statistiques', 'sisme-games-editor')
            ));
        }
    }
    
    /**
     * Utilitaire pour générer des réponses JSON standardisées
     * 
     * @param array $data Données de la réponse
     * @param string $message Message optionnel
     * @return void
     */
    public static function send_success_response($data, $message = '') {
        $response = array(
            'success' => true,
            'data' => $data
        );
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        wp_send_json($response);
    }
    
    /**
     * Utilitaire pour générer des réponses d'erreur JSON standardisées
     * 
     * @param string $message Message d'erreur
     * @param array $data Données additionnelles
     * @return void
     */
    public static function send_error_response($message, $data = array()) {
        $response = array(
            'success' => false,
            'message' => $message
        );
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        wp_send_json($response);
    }
    
    /**
     * Valider une requête AJAX
     * 
     * @param array $required_params Paramètres requis
     * @return bool True si valide
     */
    public static function validate_ajax_request($required_params = array()) {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            self::send_error_response(__('Erreur de sécurité', 'sisme-games-editor'));
            return false;
        }
        
        // Vérifier les paramètres requis
        foreach ($required_params as $param) {
            if (!isset($_POST[$param])) {
                self::send_error_response(
                    sprintf(__('Paramètre requis manquant: %s', 'sisme-games-editor'), $param)
                );
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Logger les requêtes AJAX pour debug
     * 
     * @param string $action Action AJAX
     * @param array $params Paramètres
     */
    private static function log_ajax_request($action, $params = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = "Sisme Search AJAX: {$action}";
            
            if (!empty($params)) {
                $log_message .= ' - Params: ' . json_encode($params);
            }
            
            error_log($log_message);
        }
    }
}

// Initialisation des handlers AJAX
Sisme_Search_Ajax::init();