<?php
/**
 * File: /sisme-games-editor/includes/vedettes/vedettes-api.php
 * API pour récupérer les vedettes côté frontend
 * 
 * RESPONSABILITÉ:
 * - Fournir les données pour le carrousel frontend
 * - Tracking des vues/clics
 * - Cache des requêtes vedettes
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-data-manager.php';

class Sisme_Vedettes_API {
    
    /**
     * Cache key pour les vedettes
     */
    const CACHE_KEY = 'sisme_featured_games_cache';
    const CACHE_DURATION = 300; // 5 minutes
    
    /**
     * Récupérer les jeux vedettes pour le frontend
     * 
     * @param int $limit Nombre max de jeux à retourner
     * @param bool $include_game_data Inclure les Game Data complets
     * @return array Jeux vedettes formatés pour le frontend
     */
    public static function get_frontend_featured_games($limit = 10, $include_game_data = true) {
        // Vérifier le cache d'abord
        $cache_key = self::CACHE_KEY . '_' . $limit . '_' . ($include_game_data ? 'full' : 'light');
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            error_log("Sisme Vedettes API: Données récupérées depuis le cache");
            return $cached_data;
        }
        
        // Récupérer les vedettes actives
        $featured_games = Sisme_Vedettes_Data_Manager::get_featured_games(true);
        
        // Limiter le nombre si demandé
        if ($limit > 0) {
            $featured_games = array_slice($featured_games, 0, $limit);
        }
        
        $formatted_games = array();
        
        foreach ($featured_games as $game) {
            $formatted_game = array(
                'term_id' => $game['term_id'],
                'name' => $game['name'],
                'slug' => $game['slug'],
                'priority' => $game['vedette_data']['featured_priority'],
                'sponsor' => $game['vedette_data']['featured_sponsor'],
                'stats' => $game['vedette_data']['featured_stats']
            );
            
            // Inclure les Game Data si demandé
            if ($include_game_data) {
                $formatted_game['game_data'] = self::get_game_data_for_frontend($game['term_id']);
            }
            
            $formatted_games[] = $formatted_game;
        }
        
        // Mettre en cache
        set_transient($cache_key, $formatted_games, self::CACHE_DURATION);
        
        error_log("Sisme Vedettes API: " . count($formatted_games) . " jeux vedettes récupérés");
        
        return $formatted_games;
    }
    
    /**
     * Récupérer les Game Data d'un jeu pour le frontend
     * 
     * @param int $term_id ID du jeu
     * @return array Game Data formatées
     */
    private static function get_game_data_for_frontend($term_id) {
        $game_data = array(
            'description' => get_term_meta($term_id, 'game_description', true),
            'release_date' => get_term_meta($term_id, 'release_date', true),
            'trailer_link' => get_term_meta($term_id, 'trailer_link', true),
            'external_links' => get_term_meta($term_id, 'external_links', true) ?: array(),
            'covers' => array(
                'main' => get_term_meta($term_id, 'cover_main', true),
                'news' => get_term_meta($term_id, 'cover_news', true)
            ),
            'screenshots' => get_term_meta($term_id, 'screenshots', true) ?: array()
        );
        
        // Convertir screenshots string vers array
        if (is_string($game_data['screenshots'])) {
            $game_data['screenshots'] = explode(',', $game_data['screenshots']);
            $game_data['screenshots'] = array_map('intval', $game_data['screenshots']);
        }
        
        // Récupérer les genres
        $genres = get_term_meta($term_id, 'game_genres', true) ?: array();
        $game_data['genres'] = array();
        foreach ($genres as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                $game_data['genres'][] = array(
                    'id' => $genre->term_id,
                    'name' => $genre->name,
                    'slug' => $genre->slug
                );
            }
        }
        
        // Récupérer les plateformes
        $platforms = get_term_meta($term_id, 'game_platforms', true) ?: array();
        $game_data['platforms'] = $platforms;
        
        return $game_data;
    }
    
    /**
     * Enregistrer une vue sur un jeu vedette
     * 
     * @param int $term_id ID du jeu
     * @return bool Succès
     */
    public static function track_view($term_id) {
        // Vérifier que c'est bien un jeu vedette
        $vedette_data = Sisme_Vedettes_Data_Manager::get_vedette_data($term_id);
        if (!$vedette_data['is_featured']) {
            return false;
        }
        
        // Incrémenter les vues
        $success = Sisme_Vedettes_Data_Manager::increment_stat($term_id, 'views');
        
        if ($success) {
            // Invalider le cache
            self::clear_cache();
            error_log("Sisme Vedettes API: Vue trackée pour jeu ID $term_id");
        }
        
        return $success;
    }
    
    /**
     * Enregistrer un clic sur un jeu vedette
     * 
     * @param int $term_id ID du jeu
     * @return bool Succès
     */
    public static function track_click($term_id) {
        // Vérifier que c'est bien un jeu vedette
        $vedette_data = Sisme_Vedettes_Data_Manager::get_vedette_data($term_id);
        if (!$vedette_data['is_featured']) {
            return false;
        }
        
        // Incrémenter les clics
        $success = Sisme_Vedettes_Data_Manager::increment_stat($term_id, 'clicks');
        
        if ($success) {
            // Invalider le cache
            self::clear_cache();
            error_log("Sisme Vedettes API: Clic tracké pour jeu ID $term_id");
        }
        
        return $success;
    }
    
    /**
     * Vider le cache des vedettes
     */
    public static function clear_cache() {
        // Supprimer tous les caches possibles
        $cache_keys = array(
            self::CACHE_KEY . '_10_full',
            self::CACHE_KEY . '_10_light',
            self::CACHE_KEY . '_5_full',
            self::CACHE_KEY . '_5_light',
            self::CACHE_KEY . '_20_full',
            self::CACHE_KEY . '_20_light'
        );
        
        foreach ($cache_keys as $key) {
            delete_transient($key);
        }
        
        error_log("Sisme Vedettes API: Cache vidé");
    }
    
    /**
     * Obtenir les statistiques globales des vedettes
     * 
     * @return array Statistiques
     */
    public static function get_global_stats() {
        $featured_games = Sisme_Vedettes_Data_Manager::get_featured_games(false);
        
        $stats = array(
            'total_featured' => count($featured_games),
            'total_views' => 0,
            'total_clicks' => 0,
            'active_campaigns' => 0,
            'sponsored_games' => 0
        );
        
        $current_date = current_time('Y-m-d H:i:s');
        
        foreach ($featured_games as $game) {
            $vedette_data = $game['vedette_data'];
            
            // Additionner les stats
            $stats['total_views'] += $vedette_data['featured_stats']['views'];
            $stats['total_clicks'] += $vedette_data['featured_stats']['clicks'];
            
            // Compter les campagnes actives (avec dates)
            $is_active = true;
            if ($vedette_data['featured_start_date'] && 
                $current_date < $vedette_data['featured_start_date']) {
                $is_active = false;
            }
            if ($vedette_data['featured_end_date'] && 
                $current_date > $vedette_data['featured_end_date']) {
                $is_active = false;
            }
            
            if ($is_active) {
                $stats['active_campaigns']++;
            }
            
            // Compter les jeux sponsorisés
            if (!empty($vedette_data['featured_sponsor'])) {
                $stats['sponsored_games']++;
            }
        }
        
        return $stats;
    }

    /**
     * Générer un carrousel avec les covers des jeux vedettes
     * 
     * @param array $options Options du carrousel
     * @return string HTML du carrousel ou shortcode
     */
    public static function render_featured_carousel($options = array()) {
        $defaults = array(
            'limit' => 10,
            'height' => '600px',
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'cover_size' => 'large',
            'return_shortcode' => false,
            'css_class' => 'sisme-vedettes-carousel',
            'show_title' => true,
            'title' => 'Jeux à la Une'
        );
        
        $options = array_merge($defaults, $options);
        
        // Récupérer les jeux vedettes
        $featured_games = self::get_frontend_featured_games($options['limit'], false);
        
        // NOUVEAU: Si pas de vedettes, récupérer les derniers jeux par date de sortie
        if (empty($featured_games)) {
            error_log("Sisme Vedettes: Aucune vedette trouvée, activation du fallback");
            $featured_games = self::get_recent_games_fallback($options['limit']);
            
            // Modifier le titre pour indiquer que c'est un fallback
            $options['title'] = 'Derniers Jeux Sortis';
        }
        
        if (empty($featured_games)) {
            if ($options['return_shortcode']) {
                return '<!-- Aucun jeu vedette ou récent disponible -->';
            }
            
            if (!class_exists('Sisme_Carousel_Module')) {
                require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/carousel-module.php';
            }
            return Sisme_Carousel_Module::quick_render_vedettes(array(), $options);
        }
        
        // Préparer les items pour le carrousel
        $carousel_items = array();
        
        foreach ($featured_games as $game) {
            $cover_id = get_term_meta($game['term_id'], 'cover_main', true);
            
            if ($cover_id && wp_attachment_is_image($cover_id)) {
                $image_url = wp_get_attachment_image_url($cover_id, 'large');
                if ($image_url) {
                    // MODIFICATION: Récupérer la description du jeu
                    $game_description = get_term_meta($game['term_id'], 'game_description', true);
                    
                    $carousel_items[] = array(
                        'type' => 'image',
                        'id' => $cover_id,
                        'url' => $image_url,
                        'alt' => get_post_meta($cover_id, '_wp_attachment_image_alt', true) ?: $game['name'],
                        'title' => get_the_title($cover_id),
                        'caption' => wp_get_attachment_caption($cover_id),
                        'game_info' => array(
                            'name' => $game['name'],
                            'slug' => $game['slug'],
                            'term_id' => $game['term_id'],
                            'priority' => isset($game['priority']) ? $game['priority'] : 0,
                            'sponsor' => isset($game['sponsor']) ? $game['sponsor'] : '',
                            'description' => $game_description // NOUVEAU: Ajouter la description
                        )
                    );
                }
            }
        }
        
        if (empty($carousel_items)) {
            if ($options['return_shortcode']) {
                return '<!-- Aucune cover principale trouvée -->';
            }
            
            if (!class_exists('Sisme_Carousel_Module')) {
                require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/carousel-module.php';
            }
            return Sisme_Carousel_Module::quick_render_vedettes(array(), $options);
        }
        
        // Retourner shortcode si demandé
        if ($options['return_shortcode']) {
            $image_ids = array_column($carousel_items, 'id');
            
            $shortcode_atts = array(
                'images="' . implode(',', $image_ids) . '"',
                'height="' . esc_attr($options['height']) . '"',
                'autoplay="' . ($options['autoplay'] ? 'true' : 'false') . '"',
                'show_arrows="' . ($options['show_arrows'] ? 'true' : 'false') . '"',
                'show_dots="' . ($options['show_dots'] ? 'true' : 'false') . '"'
            );
            
            return '[sisme_carousel ' . implode(' ', $shortcode_atts) . ']';
        }
        
        // Charger le module carrousel
        if (!class_exists('Sisme_Carousel_Module')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/carousel-module.php';
        }
        
        $carousel_options = array(
            'height' => $options['height'],
            'autoplay' => $options['autoplay'],
            'autoplay_delay' => $options['autoplay_delay'],
            'show_arrows' => $options['show_arrows'],
            'show_dots' => $options['show_dots'],
            'css_class' => $options['css_class'],
            'item_type' => 'image',
            'show_title' => $options['show_title'],
            'title' => $options['title']
        );
        
        return Sisme_Carousel_Module::quick_render_vedettes($carousel_items, $carousel_options);
    }

    private static function get_recent_games_fallback($limit = 10) {
        // Récupérer tous les jeux avec une date de sortie
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'release_date',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'cover_main',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (is_wp_error($all_games) || empty($all_games)) {
            error_log("Sisme Fallback: Aucun jeu avec date de sortie trouvé");
            return array();
        }
        
        $games_with_dates = array();
        
        foreach ($all_games as $term) {
            $release_date = get_term_meta($term->term_id, 'release_date', true);
            $cover_id = get_term_meta($term->term_id, 'cover_main', true);
            
            // Vérifier que la date est valide et qu'il y a une cover
            if ($release_date && $cover_id && wp_attachment_is_image($cover_id)) {
                $games_with_dates[] = array(
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'release_date' => $release_date,
                    'timestamp' => strtotime($release_date)
                );
            }
        }
        
        // Trier par date de sortie (plus récent en premier)
        usort($games_with_dates, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limiter au nombre demandé
        if ($limit > 0) {
            $games_with_dates = array_slice($games_with_dates, 0, $limit);
        }
        
        error_log("Sisme Fallback: " . count($games_with_dates) . " jeux récents trouvés");
        
        return $games_with_dates;
    }

    /**
     * Shortcode pour le carrousel vedettes
     * Usage: [sisme_vedettes_carousel limit="5" height="500px" autoplay="true"]
     */
    public static function vedettes_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'height' => '400px',
            'autoplay' => 'true',
            'show_arrows' => 'true',
            'show_dots' => 'true',
            'css_class' => 'sisme-vedettes-carousel'
        ), $atts);
        
        $options = array(
            'limit' => intval($atts['limit']),
            'height' => sanitize_text_field($atts['height']),
            'autoplay' => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
            'show_arrows' => filter_var($atts['show_arrows'], FILTER_VALIDATE_BOOLEAN),
            'show_dots' => filter_var($atts['show_dots'], FILTER_VALIDATE_BOOLEAN),
            'css_class' => sanitize_html_class($atts['css_class'])
        );
        
        return self::render_featured_carousel($options);
    }

    /**
     * BONUS: Fonction utilitaire pour récupérer juste les IDs des covers
     * 
     * @param int $limit Nombre max de covers
     * @return array Array d'IDs d'images
     */
    public static function get_featured_covers_ids($limit = 10) {
        $featured_games = self::get_frontend_featured_games($limit, false);
        $cover_ids = array();
        
        foreach ($featured_games as $game) {
            $cover_id = get_term_meta($game['term_id'], 'cover_main', true);
            if ($cover_id && wp_attachment_is_image($cover_id)) {
                $cover_ids[] = intval($cover_id);
            }
        }
        
        return $cover_ids;
    }
}

?>