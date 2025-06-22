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
     * Shortcode pour afficher les jeux vedettes
     * 
     * Usage: [sisme_vedettes limit="5" template="carrousel"]
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML généré
     */
    public static function vedettes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'template' => 'simple',
            'show_priority' => false
        ), $atts);
        
        $featured_games = self::get_frontend_featured_games($atts['limit'], true);
        
        if (empty($featured_games)) {
            return '<p>Aucun jeu en vedette pour le moment.</p>';
        }
        
        $output = '<div class="sisme-vedettes-container">';
        
        foreach ($featured_games as $game) {
            // Tracker automatiquement la vue
            self::track_view($game['term_id']);
            
            $output .= '<div class="sisme-vedette-item" data-term-id="' . $game['term_id'] . '">';
            $output .= '<h3>' . esc_html($game['name']) . '</h3>';
            
            if ($atts['show_priority']) {
                $output .= '<span class="vedette-priority">Priorité: ' . $game['priority'] . '</span>';
            }
            
            if (!empty($game['sponsor'])) {
                $output .= '<span class="vedette-sponsor">Sponsorisé par: ' . esc_html($game['sponsor']) . '</span>';
            }
            
            // Lien vers la fiche du jeu
            $game_link = home_url('/tag/' . $game['slug'] . '/');
            $output .= '<a href="' . esc_url($game_link) . '" class="vedette-link" data-term-id="' . $game['term_id'] . '">Voir la fiche</a>';
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Ajouter le JavaScript pour tracker les clics
        $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const vedette_links = document.querySelectorAll(".vedette-link");
            vedette_links.forEach(function(link) {
                link.addEventListener("click", function() {
                    const term_id = this.getAttribute("data-term-id");
                    // Envoyer le tracking via AJAX (à implémenter)
                    console.log("Clic tracké pour jeu ID:", term_id);
                });
            });
        });
        </script>';
        
        return $output;
    }
}

?>