<?php
/**
 * File: /sisme-games-editor/includes/search/search-ajax.php
 * Module de recherche gaming - Handlers AJAX simples
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Ajax {
    
    /**
     * Initialisation des handlers AJAX
     */
    public static function init() {
        add_action('wp_ajax_sisme_search', array(__CLASS__, 'handle_search_request'));
        add_action('wp_ajax_nopriv_sisme_search', array(__CLASS__, 'handle_search_request'));
    }
    
    /**
     * Handler principal pour les requêtes de recherche
     */
    public static function handle_search_request() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sisme_search_nonce')) {
            wp_send_json_error(array('message' => 'Erreur de sécurité'));
        }
        
        // Récupérer les paramètres
        $search_params = self::extract_search_params($_POST);
        
        // Vérifier que le module de filtres est disponible
        if (!class_exists('Sisme_Search_Filters')) {
            wp_send_json_error(array('message' => 'Module de filtres non disponible'));
        }
        
        try {
            // Effectuer la recherche
            $results = Sisme_Search_Filters::perform_search($search_params);
            
            // Générer le HTML selon le mode
            $is_pagination = ($search_params['page'] ?? 1) > 1;
            
            if ($is_pagination) {
                // Mode pagination : seulement les nouvelles cartes
                $html = self::render_cards_only($results['games']);
            } else {
                // Mode normal : grille complète avec classes cards-grid
                $html = self::render_full_grid($results['games']);
            }
            
            // Réponse de succès
            wp_send_json_success(array(
                'html' => $html,
                'total' => $results['total'],
                'page' => $results['page'],
                'has_more' => $results['has_more'],
                'is_pagination' => $is_pagination
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Erreur lors de la recherche'));
        }
    }
    
    /**
     * Extraire les paramètres de recherche depuis $_POST
     */
    private static function extract_search_params($post_data) {
        return array(
            'query' => sanitize_text_field($post_data['query'] ?? ''),
            Sisme_Utils_Games::KEY_GENRES => self::extract_array_param($post_data, 'genres', 'int'),
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
     * Rendu grille complète avec classes cards-grid (mode normal)
     */
    private static function render_full_grid($games) {
        if (empty($games)) {
            return self::render_no_results();
        }
        
        if (!class_exists('Sisme_Cards_API')) {
            return '<p>Erreur: API Cards non disponible</p>';
        }
        
        ob_start();
        ?>
        <div class="sisme-cards-grid sisme-cards-grid--cols-3">
            <?php foreach ($games as $game_id): ?>
                <?php if (is_numeric($game_id) && $game_id > 0): ?>
                    <?php echo Sisme_Cards_API::render_card($game_id, 'normal'); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu cartes seulement (mode pagination)
     */
    private static function render_cards_only($games) {
        if (empty($games)) {
            return '';
        }
        
        if (!class_exists('Sisme_Cards_API')) {
            return '';
        }
        
        ob_start();
        foreach ($games as $game_id) {
            if (is_numeric($game_id) && $game_id > 0) {
                echo Sisme_Cards_API::render_card($game_id, 'normal');
            }
        }
        return ob_get_clean();
    }
    
    /**
     * Rendu quand aucun résultat
     */
    private static function render_no_results() {
        return '<div class="sisme-search-no-results"><h3>Aucun jeu trouvé</h3><p>Essayez de modifier vos critères de recherche</p></div>';
    }
}

// Initialisation
Sisme_Search_Ajax::init();