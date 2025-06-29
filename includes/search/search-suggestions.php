<?php
/**
 * File: /sisme-games-editor/includes/search/search-suggestions.php
 * Module de recherche gaming - Suggestions populaires et historique
 * 
 * Responsabilités :
 * - Gestion des suggestions de recherche populaires
 * - Calcul des tendances de recherche
 * - Recommandations intelligentes
 * - Support pour l'historique local (côté JavaScript)
 * - Analytics des recherches (optionnel)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Suggestions {
    
    // Options de configuration
    private static $option_name = 'sisme_search_suggestions';
    private static $analytics_option = 'sisme_search_analytics';
    private static $cache_timeout = 3600; // 1 heure
    
    /**
     * Initialisation du module de suggestions
     */
    public static function init() {
        // Hooks pour tracker les recherches (optionnel)
        add_action('sisme_search_performed', array(__CLASS__, 'track_search'));
        
        // Nettoyage périodique des données (hook WordPress)
        add_action('sisme_search_cleanup', array(__CLASS__, 'cleanup_old_data'));
        
        // Planifier le nettoyage si pas déjà fait
        if (!wp_next_scheduled('sisme_search_cleanup')) {
            wp_schedule_event(time(), 'daily', 'sisme_search_cleanup');
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search Suggestions: Initialized');
        }
    }
    
    /**
     * Obtenir les suggestions de recherche populaires
     * 
     * @param int $limit Nombre de suggestions à retourner
     * @return array Suggestions populaires
     */
    public static function get_popular_searches($limit = 5) {
        // Essayer de récupérer depuis le cache
        $cache_key = 'sisme_popular_searches_' . $limit;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Récupérer les suggestions depuis les options
        $saved_suggestions = get_option(self::$option_name, array());
        
        // Si pas de suggestions sauvegardées, utiliser les défauts
        if (empty($saved_suggestions)) {
            $suggestions = self::get_default_suggestions();
        } else {
            // Trier par popularité et limiter
            $suggestions = self::sort_suggestions_by_popularity($saved_suggestions, $limit);
        }
        
        // Compléter avec les suggestions par défaut si nécessaire
        if (count($suggestions) < $limit) {
            $default_suggestions = self::get_default_suggestions();
            $needed = $limit - count($suggestions);
            
            foreach ($default_suggestions as $term => $display) {
                if ($needed <= 0) break;
                
                // Ajouter seulement si pas déjà présent
                if (!isset($suggestions[$term])) {
                    $suggestions[$term] = $display;
                    $needed--;
                }
            }
        }
        
        // Limiter le nombre final
        $suggestions = array_slice($suggestions, 0, $limit, true);
        
        // Mettre en cache
        set_transient($cache_key, $suggestions, self::$cache_timeout);
        
        return $suggestions;
    }
    
    /**
     * Obtenir les suggestions par défaut
     * 
     * @return array Suggestions par défaut
     */
    private static function get_default_suggestions() {
        $defaults = array(
            'Action' => 'Action',
            'RPG' => 'RPG',
            'Aventure' => 'Aventure',
            'Stratégie' => 'Stratégie',
            'Simulation' => 'Simulation',
            'FPS' => 'FPS',
            'Plateforme' => 'Plateforme',
            'Course' => 'Course',
            'Multijoueur' => 'Multijoueur',
            '2024' => '2024'
        );
        
        // Possibilité d'ajouter des suggestions dynamiques basées sur les jeux populaires
        $dynamic_suggestions = self::get_dynamic_suggestions();
        
        return array_merge($defaults, $dynamic_suggestions);
    }
    
    /**
     * Obtenir des suggestions dynamiques basées sur les données du site
     * 
     * @return array Suggestions dynamiques
     */
    private static function get_dynamic_suggestions() {
        $suggestions = array();
        if (class_exists('Sisme_Utils_Games')) {
            try {
                $popular_games = Sisme_Utils_Games::get_games_by_criteria(array(
                    'is_team_choice' => true,
                    'limit' => 3
                ));
                
                foreach ($popular_games as $game) {
                    if (isset($game['name']) && !empty($game['name'])) {
                        $suggestions[$game['name']] = $game['name'];
                    }
                }
                
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Sisme Search Suggestions: Error getting dynamic suggestions - ' . $e->getMessage());
                }
            }
        }
        
        // Récupérer les genres les plus utilisés
        $popular_genres = self::get_popular_genres();
        $suggestions = array_merge($suggestions, $popular_genres);
        
        return $suggestions;
    }
    
    /**
     * Obtenir les genres les plus populaires
     * 
     * @return array Genres populaires
     */
    private static function get_popular_genres() {
        $genres = array();
        
        // Récupérer les genres avec le plus de jeux
        $parent_category = get_category_by_slug('jeux-vidéo');
        
        if ($parent_category) {
            $game_genres = get_categories(array(
                'parent' => $parent_category->term_id,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 5,
                'hide_empty' => true
            ));
            
            foreach ($game_genres as $genre) {
                $genre_name = preg_replace('/^jeux-/i', '', $genre->name);
                $genre_name = ucfirst(trim($genre_name));
                
                if (!empty($genre_name)) {
                    $genres[$genre_name] = $genre_name;
                }
            }
        }
        
        return $genres;
    }
    
    /**
     * Trier les suggestions par popularité
     * 
     * @param array $suggestions Suggestions avec compteurs
     * @param int $limit Limite du nombre de résultats
     * @return array Suggestions triées
     */
    private static function sort_suggestions_by_popularity($suggestions, $limit) {
        // Trier par nombre de recherches (décroissant)
        uasort($suggestions, function($a, $b) {
            $count_a = isset($a['count']) ? $a['count'] : 0;
            $count_b = isset($b['count']) ? $b['count'] : 0;
            return $count_b - $count_a;
        });
        
        // Convertir en format simple terme => affichage
        $result = array();
        foreach ($suggestions as $term => $data) {
            $display = isset($data['display']) ? $data['display'] : $term;
            $result[$term] = $display;
            
            if (count($result) >= $limit) {
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Tracker une recherche effectuée (pour analytics)
     * 
     * @param array $search_data Données de la recherche
     */
    public static function track_search($search_data) {
        // Ne pas tracker si pas de terme de recherche
        if (empty($search_data['query'])) {
            return;
        }
        
        $term = sanitize_text_field($search_data['query']);
        $results_count = intval($search_data['results_count'] ?? 0);
        
        // Récupérer les analytics actuelles
        $analytics = get_option(self::$analytics_option, array());
        
        // Initialiser les données pour ce terme si nécessaire
        if (!isset($analytics[$term])) {
            $analytics[$term] = array(
                'display' => $term,
                'count' => 0,
                'total_results' => 0,
                'last_searched' => current_time('mysql'),
                'first_searched' => current_time('mysql')
            );
        }
        
        // Mettre à jour les statistiques
        $analytics[$term]['count']++;
        $analytics[$term]['total_results'] += $results_count;
        $analytics[$term]['last_searched'] = current_time('mysql');
        
        // Sauvegarder
        update_option(self::$analytics_option, $analytics);
        
        // Mettre à jour les suggestions populaires
        self::update_popular_suggestions($analytics);
        
        // Nettoyer le cache
        self::clear_suggestions_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Sisme Search: Tracked search for '{$term}' ({$results_count} results)");
        }
    }
    
    /**
     * Mettre à jour les suggestions populaires basées sur les analytics
     * 
     * @param array $analytics Données analytics
     */
    private static function update_popular_suggestions($analytics) {
        // Filtrer les termes avec au moins 2 recherches
        $popular = array_filter($analytics, function($data) {
            return $data['count'] >= 2;
        });
        
        // Trier par popularité
        uasort($popular, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        // Garder seulement les 20 plus populaires
        $popular = array_slice($popular, 0, 20, true);
        
        // Sauvegarder
        update_option(self::$option_name, $popular);
    }
    
    /**
     * Obtenir des suggestions basées sur un terme partiel
     * 
     * @param string $partial_term Terme partiel
     * @param int $limit Nombre de suggestions
     * @return array Suggestions
     */
    public static function get_suggestions_for_term($partial_term, $limit = 8) {
        $suggestions = array();
        $term = strtolower(trim($partial_term));
        
        if (strlen($term) < 2) {
            return array();
        }
        
        // Rechercher dans les suggestions populaires
        $popular = self::get_popular_searches(20);
        foreach ($popular as $suggestion_term => $display) {
            if (stripos($suggestion_term, $term) !== false) {
                $suggestions[] = array(
                    'label' => $display,
                    'value' => $suggestion_term,
                    'type' => 'popular'
                );
            }
        }
        
        // Rechercher dans les noms de jeux
        $game_suggestions = self::get_game_name_suggestions($term, $limit - count($suggestions));
        $suggestions = array_merge($suggestions, $game_suggestions);
        
        // Rechercher dans les genres
        if (count($suggestions) < $limit) {
            $genre_suggestions = self::get_genre_suggestions($term, $limit - count($suggestions));
            $suggestions = array_merge($suggestions, $genre_suggestions);
        }
        
        return array_slice($suggestions, 0, $limit);
    }
    
    /**
     * Obtenir des suggestions de noms de jeux
     * 
     * @param string $term Terme de recherche
     * @param int $limit Limite
     * @return array Suggestions de jeux
     */
    private static function get_game_name_suggestions($term, $limit) {
        $suggestions = array();
        
        if ($limit <= 0) {
            return $suggestions;
        }
        
        // Rechercher dans les tags (noms de jeux)
        $tags = get_tags(array(
            'search' => $term,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        foreach ($tags as $tag) {
            // Vérifier si c'est un tag de jeu
            $has_game_data = get_term_meta($tag->term_id, 'game_description', true);
            
            if ($has_game_data) {
                $suggestions[] = array(
                    'label' => $tag->name,
                    'value' => $tag->name,
                    'type' => 'game'
                );
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Obtenir des suggestions de genres
     * 
     * @param string $term Terme de recherche
     * @param int $limit Limite
     * @return array Suggestions de genres
     */
    private static function get_genre_suggestions($term, $limit) {
        $suggestions = array();
        
        if ($limit <= 0) {
            return $suggestions;
        }
        
        $parent_category = get_category_by_slug('jeux-vidéo');
        if (!$parent_category) {
            return $suggestions;
        }
        
        $genres = get_categories(array(
            'parent' => $parent_category->term_id,
            'search' => $term,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        foreach ($genres as $genre) {
            $genre_name = preg_replace('/^jeux-/i', '', $genre->name);
            $genre_name = ucfirst(trim($genre_name));
            
            if (!empty($genre_name)) {
                $suggestions[] = array(
                    'label' => $genre_name,
                    'value' => $genre_name,
                    'type' => 'genre'
                );
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Obtenir les tendances de recherche
     * 
     * @param int $days Nombre de jours à considérer
     * @return array Tendances
     */
    public static function get_search_trends($days = 7) {
        $analytics = get_option(self::$analytics_option, array());
        
        if (empty($analytics)) {
            return array();
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $trends = array();
        
        foreach ($analytics as $term => $data) {
            if ($data['last_searched'] >= $cutoff_date) {
                $trends[$term] = array(
                    'term' => $term,
                    'count' => $data['count'],
                    'avg_results' => $data['total_results'] > 0 ? round($data['total_results'] / $data['count']) : 0
                );
            }
        }
        
        // Trier par nombre de recherches
        uasort($trends, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_slice($trends, 0, 10);
    }
    
    /**
     * Nettoyer les anciennes données
     */
    public static function cleanup_old_data() {
        $analytics = get_option(self::$analytics_option, array());
        
        if (empty($analytics)) {
            return;
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $cleaned = array();
        
        foreach ($analytics as $term => $data) {
            // Garder les données récentes ou très populaires
            if ($data['last_searched'] >= $cutoff_date || $data['count'] >= 5) {
                $cleaned[$term] = $data;
            }
        }
        
        update_option(self::$analytics_option, $cleaned);
        
        // Nettoyer le cache
        self::clear_suggestions_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $removed = count($analytics) - count($cleaned);
            error_log("Sisme Search: Cleaned up {$removed} old search entries");
        }
    }
    
    /**
     * Vider le cache des suggestions
     */
    public static function clear_suggestions_cache() {
        // Supprimer tous les transients de suggestions
        for ($i = 1; $i <= 20; $i++) {
            delete_transient('sisme_popular_searches_' . $i);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Suggestions cache cleared');
        }
    }
    
    /**
     * Réinitialiser toutes les données de suggestions
     */
    public static function reset_all_data() {
        delete_option(self::$option_name);
        delete_option(self::$analytics_option);
        self::clear_suggestions_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: All suggestions data reset');
        }
    }
    
    /**
     * Obtenir les statistiques des suggestions
     * 
     * @return array Statistiques
     */
    public static function get_suggestions_stats() {
        $analytics = get_option(self::$analytics_option, array());
        
        $stats = array(
            'total_searches' => 0,
            'unique_terms' => count($analytics),
            'top_term' => '',
            'top_count' => 0
        );
        
        foreach ($analytics as $term => $data) {
            $stats['total_searches'] += $data['count'];
            
            if ($data['count'] > $stats['top_count']) {
                $stats['top_count'] = $data['count'];
                $stats['top_term'] = $term;
            }
        }
        
        return $stats;
    }
}

// Initialisation du module
Sisme_Search_Suggestions::init();