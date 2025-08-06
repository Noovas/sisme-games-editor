<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-game-detector.php
 * Couche d'abstraction pour la détection et récupération des données de jeu
 * 
 * RESPONSABILITÉ:
 * - Détection unifiée des pages de jeu
 * - Récupération des données formatées pour le SEO
 * - Interface unique entre le SEO et le système game-page-creator
 * - Cache des résultats de détection et données
 * 
 * DÉPENDANCES:
 * - game-page-creator (game-data-formatter.php)
 * - game-page-creator (game-page-content-filter.php)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les dépendances du système
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';

class Sisme_SEO_Game_Detector {
    
    /**
     * Cache de détection pour éviter les requêtes multiples
     */
    private static $detection_cache = array();
    private static $data_cache = array();
    
    /**
     * Durée du cache transient (15 minutes)
     */
    const CACHE_DURATION = 900;
    
    /**
     * Détecter si on est sur une page de jeu
     * 
     * @param int|null $post_id ID du post (null = post actuel)
     * @return bool True si c'est une page de jeu
     */
    public static function is_game_page($post_id = null) {
        // Résoudre le post_id
        if (!$post_id) {
            global $post;
            if (!$post || !is_single()) {
                return false;
            }
            $post_id = $post->ID;
        }
        
        // Vérifier le cache en mémoire
        if (isset(self::$detection_cache[$post_id])) {
            return self::$detection_cache[$post_id];
        }
        
        // Vérifier le cache transient
        $cache_key = 'sisme_seo_is_game_' . $post_id;
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            self::$detection_cache[$post_id] = (bool)$cached_result;
            return self::$detection_cache[$post_id];
        }
        
        // Détecter via la méthode système
        $is_game = self::detect_game_page($post_id);
        
        // Sauvegarder en cache
        self::$detection_cache[$post_id] = $is_game;
        set_transient($cache_key, $is_game ? 1 : 0, self::CACHE_DURATION);
        
        return $is_game;
    }
    
    /**
     * Récupérer les données formatées d'un jeu pour le SEO
     * 
     * @param int|null $post_id ID du post (null = post actuel)
     * @return array|false Données formatées ou false si pas un jeu
     */
    public static function get_game_data($post_id = null) {
        // Vérifier d'abord si c'est une page de jeu
        if (!self::is_game_page($post_id)) {
            return false;
        }
        
        // Résoudre le post_id
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        
        // Vérifier le cache en mémoire
        if (isset(self::$data_cache[$post_id])) {
            return self::$data_cache[$post_id];
        }
        
        // Vérifier le cache transient
        $cache_key = 'sisme_seo_game_data_' . $post_id;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            self::$data_cache[$post_id] = $cached_data;
            return $cached_data;
        }
        
        // Récupérer les données via le système
        $game_data = self::fetch_game_data($post_id);
        
        // Sauvegarder en cache
        if ($game_data) {
            self::$data_cache[$post_id] = $game_data;
            set_transient($cache_key, $game_data, self::CACHE_DURATION);
        }
        
        return $game_data;
    }
    
    /**
     * Récupérer le term_id du jeu depuis un post
     * 
     * @param int $post_id ID du post
     * @return int|false Term ID ou false si non trouvé
     */
    public static function get_game_term_id($post_id) {
        // Utiliser la même logique que game-page-content-filter
        $tags = wp_get_post_tags($post_id);
        if (empty($tags)) {
            return false;
        }
        
        // Le premier tag est considéré comme le jeu
        return $tags[0]->term_id;
    }
    
    /**
     * Vérifier si un terme est un jeu valide
     * 
     * @param int $term_id ID du terme
     * @return bool True si c'est un jeu valide
     */
    public static function is_valid_game_term($term_id) {
        $term = get_term($term_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            return false;
        }
        
        // Vérifier qu'il a des données de jeu
        $description = get_term_meta($term_id, 'game_description', true);
        $sections = get_term_meta($term_id, 'game_sections', true);
        
        return !empty($description) || !empty($sections);
    }
    
    /**
     * Nettoyer le cache pour un post spécifique
     * 
     * @param int $post_id ID du post
     */
    public static function clear_cache($post_id) {
        // Nettoyer le cache en mémoire
        unset(self::$detection_cache[$post_id]);
        unset(self::$data_cache[$post_id]);
        
        // Nettoyer les transients
        delete_transient('sisme_seo_is_game_' . $post_id);
        delete_transient('sisme_seo_game_data_' . $post_id);
    }
    
    /**
     * Nettoyer tout le cache SEO
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        // Nettoyer le cache en mémoire
        self::$detection_cache = array();
        self::$data_cache = array();
        
        // Nettoyer tous les transients SEO
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_seo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sisme_seo_%'");
    }
    
    /**
     * MÉTHODES PRIVÉES - Logique de détection interne
     */
    
    /**
     * Détecter si un post est une page de jeu (logique interne)
     * 
     * @param int $post_id ID du post
     * @return bool True si c'est une page de jeu
     */
    private static function detect_game_page($post_id) {
        // Récupérer le term_id du jeu
        $term_id = self::get_game_term_id($post_id);
        if (!$term_id) {
            return false;
        }
        
        // Vérifier si c'est un terme de jeu valide
        return self::is_valid_game_term($term_id);
    }
    
    /**
     * Récupérer les données d'un jeu (logique interne)
     * 
     * @param int $post_id ID du post
     * @return array|false Données formatées ou false
     */
    private static function fetch_game_data($post_id) {
        // Récupérer le term_id
        $term_id = self::get_game_term_id($post_id);
        if (!$term_id) {
            return false;
        }
        
        // Utiliser le formatter du système
        return Sisme_Game_Data_Formatter::format_game_data($term_id);
    }
    
    /**
     * HOOKS WORDPRESS pour invalidation automatique du cache
     */
    
    /**
     * Initialiser les hooks d'invalidation de cache
     */
    public static function init_cache_hooks() {
        // Nettoyer le cache lors de modifications
        add_action('save_post', array(__CLASS__, 'on_post_save'));
        add_action('edited_term', array(__CLASS__, 'on_term_edit'), 10, 3);
        add_action('delete_term', array(__CLASS__, 'on_term_delete'), 10, 3);
        add_action('set_object_terms', array(__CLASS__, 'on_terms_change'), 10, 4);
    }
    
    /**
     * Hook lors de la sauvegarde d'un post
     */
    public static function on_post_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            self::clear_cache($post_id);
        }
    }
    
    /**
     * Hook lors de l'édition d'un terme
     */
    public static function on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            // Trouver tous les posts qui utilisent ce terme et nettoyer leur cache
            $posts = get_posts(array(
                'post_type' => 'post',
                'tag__in' => array($term_id),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($posts as $post_id) {
                self::clear_cache($post_id);
            }
        }
    }
    
    /**
     * Hook lors de la suppression d'un terme
     */
    public static function on_term_delete($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            self::on_term_edit($term_id, $tt_id, $taxonomy);
        }
    }
    
    /**
     * Hook lors du changement de termes d'un objet
     */
    public static function on_terms_change($object_id, $terms, $tt_ids, $taxonomy) {
        if ($taxonomy === 'post_tag' && get_post_type($object_id) === 'post') {
            self::clear_cache($object_id);
        }
    }
    
    /**
     * MÉTHODES UTILITAIRES pour le debug
     */
    
    /**
     * Obtenir les statistiques du cache
     * 
     * @return array Statistiques de cache
     */
    public static function get_cache_stats() {
        global $wpdb;
        
        $detection_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_seo_is_game_%'");
        $data_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_seo_game_data_%'");
        
        return array(
            'memory_detection' => count(self::$detection_cache),
            'memory_data' => count(self::$data_cache),
            'transient_detection' => (int)$detection_count,
            'transient_data' => (int)$data_count,
            'total_cached' => (int)($detection_count + $data_count)
        );
    }
    
    /**
     * Debug : forcer la re-détection d'un post
     * 
     * @param int $post_id ID du post
     * @return array Résultats de debug
     */
    public static function debug_detection($post_id) {
        // Nettoyer le cache
        self::clear_cache($post_id);
        
        // Re-détecter
        $is_game = self::is_game_page($post_id);
        $game_data = $is_game ? self::get_game_data($post_id) : false;
        $term_id = self::get_game_term_id($post_id);
        
        return array(
            'post_id' => $post_id,
            'is_game_page' => $is_game,
            'term_id' => $term_id,
            'has_game_data' => !empty($game_data),
            'game_name' => $game_data ? ($game_data['name'] ?? 'N/A') : 'N/A',
            'tags_count' => count(wp_get_post_tags($post_id))
        );
    }
}

// Initialiser les hooks de cache automatiquement
Sisme_SEO_Game_Detector::init_cache_hooks();