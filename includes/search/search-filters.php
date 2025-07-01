<?php
/**
 * File: /sisme-games-editor/includes/search/search-filters.php
 * Module de recherche gaming - Logique des filtres et recherche
 * 
 * Responsabilités :
 * - Traitement des paramètres de recherche
 * - Filtrage des jeux selon les critères
 * - Intégration avec le système Cards
 * - Validation et sanitisation des données
 * - Gestion du cache des résultats
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Filters {
    
    // Cache des résultats de recherche
    private static $cache = array();
    private static $cache_timeout = 300; // 5 minutes
    
    /**
     * Initialisation du module de filtres
     */
    public static function init() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search Filters: Initialized');
        }
    }
    
    /**
     * Effectuer une recherche avec les paramètres donnés
     * 
     * @param array $search_params Paramètres de recherche
     * @return array Résultats de la recherche
     */
    public static function perform_search($search_params) {
        $validated_params = self::validate_search_params($search_params);
        $cache_key = self::get_cache_key($validated_params);
        $cached_result = self::get_cached_result($cache_key);
        if ($cached_result !== false) {
            return $cached_result;
        }
        $results = self::execute_search($validated_params);
        self::cache_result($cache_key, $results);
        return $results;
    }
    
    /**
     * Valider et nettoyer les paramètres de recherche
     * 
     * @param array $params Paramètres bruts
     * @return array Paramètres validés
     */
    private static function validate_search_params($params) {
        $validated = array();
        $validated['query'] = isset($params['query']) ? sanitize_text_field($params['query']) : '';
        $validated[Sisme_Utils_Games::KEY_GENRES] = array();
        if (isset($params[Sisme_Utils_Games::KEY_GENRES]) && is_array($params[Sisme_Utils_Games::KEY_GENRES])) {
            $validated[Sisme_Utils_Games::KEY_GENRES] = array_map('intval', array_filter($params[Sisme_Utils_Games::KEY_GENRES]));
        }
        $validated['status'] = '';
        if (isset($params['status']) && in_array($params['status'], array('released', 'upcoming'))) {
            $validated['status'] = $params['status'];
        }
        $validated['quick_filter'] = '';
        if (isset($params['quick_filter']) && in_array($params['quick_filter'], array('popular', 'new', 'featured', 'exclusive'))) {
            $validated['quick_filter'] = $params['quick_filter'];
        }
        $validated['sort'] = isset($params['sort']) ? $params['sort'] : 'relevance';
        $allowed_sorts = array('relevance', 'name_asc', 'name_desc', 'date_asc', 'date_desc');
        if (!in_array($validated['sort'], $allowed_sorts)) {
            $validated['sort'] = 'relevance';
        }
        $validated['page'] = max(1, intval($params['page'] ?? 1));
        $validated['per_page'] = max(1, min(50, intval($params['per_page'] ?? 12)));
        return $validated;
    }
    
    /**
     * Exécuter la recherche avec les paramètres validés
     * 
     * @param array $params Paramètres validés
     * @return array Résultats de la recherche
     */
    private static function execute_search($params) {
        $criteria = self::build_cards_criteria($params);
        $all_games = Sisme_Utils_Games::get_games_by_criteria($criteria);
        $filtered_games = self::apply_custom_filters($all_games, $params);
        $sorted_games = Sisme_Utils_Filters::apply_sorting($filtered_games, $params['sort']);
        $total_games = count($sorted_games);
        $offset = ($params['page'] - 1) * $params['per_page'];
        $games_page = array_slice($sorted_games, $offset, $params['per_page']);
        return array(
            'games' => $games_page,
            'total' => $total_games,
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'total_pages' => ceil($total_games / $params['per_page']),
            'has_more' => ($offset + $params['per_page']) < $total_games,
            'params' => $params
        );
    }
    
    /**
     * Construire les critères pour le système Cards
     * 
     * @param array $params Paramètres de recherche
     * @return array Critères pour Cards
     */
    private static function build_cards_criteria($params) {
        $criteria = array();
        if (!empty($params['query'])) {
            $criteria['search'] = $params['query'];
        }
        if (!empty($params[Sisme_Utils_Games::KEY_GENRES])) {
            $genre_slugs = Sisme_Utils_Formatting::convert_genre_ids_to_slugs($params[Sisme_Utils_Games::KEY_GENRES]);
            if (!empty($genre_slugs)) {
                $criteria[Sisme_Utils_Games::KEY_GENRES] = $genre_slugs;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Sisme Search: Genre IDs ' . implode(',', $params[Sisme_Utils_Games::KEY_GENRES]) . ' → slugs ' . implode(',', $genre_slugs));
                }
            }
        }
        if (!empty($params['status'])) {
            if ($params['status'] === 'released') {
                $criteria['released'] = 1;
            } elseif ($params['status'] === 'upcoming') {
                $criteria['released'] = -1; 
            }
        }
        if (!empty($params['quick_filter'])) {
            switch ($params['quick_filter']) {
                case 'featured':
                    break;
                case 'upcoming':
                    $criteria['released'] = -1; 
                    break;
                case 'new':
                    $criteria['sort_by_date'] = true;
                    $criteria['max_results'] = 45;
                    break;
            }
        }
        return $criteria;
    }

    

    /**
     * 🔧 Méthode pour tester la conversion
     */
    public static function debug_genres_conversion($genre_ids) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        $slugs = Sisme_Utils_Formatting::convert_genre_ids_to_slugs($genre_ids);
        $criteria = array(Sisme_Utils_Games::KEY_GENRES => $slugs);
        $games = Sisme_Utils_Games::get_games_by_criteria($criteria);
    }
    
    /**
     * Appliquer des filtres personnalisés
     * 
     * @param array $games Liste des jeux
     * @param array $params Paramètres de recherche
     * @return array Jeux filtrés
     */
    private static function apply_custom_filters($games, $params) {
        if (empty($games)) {
            return $games;
        }
        $filtered = $games;
        if (!empty($params['query'])) {
            $filtered = Sisme_Utils_Filters::filter_by_search_term($filtered, $params['query']);
        }
        return array_values($filtered);
    }
    

    
    /**
     * Générer une clé de cache pour les paramètres de recherche
     * 
     * @param array $params Paramètres de recherche
     * @return string Clé de cache
     */
    private static function get_cache_key($params) {
        return 'sisme_search_' . md5(serialize($params));
    }
    
    /**
     * Récupérer un résultat depuis le cache
     * 
     * @param string $cache_key Clé de cache
     * @return mixed Résultat ou false si non trouvé/expiré
     */
    private static function get_cached_result($cache_key) {
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        if (isset(self::$cache[$cache_key])) {
            $cache_data = self::$cache[$cache_key];
            if (time() - $cache_data[Sisme_Utils_Games::KEY_TIMESTAMP] < self::$cache_timeout) {
                return $cache_data['data'];
            } else {
                unset(self::$cache[$cache_key]);
            }
        }
        return false;
    }
    
    /**
     * Mettre un résultat en cache
     * 
     * @param string $cache_key Clé de cache
     * @param array $result Résultat à cacher
     */
    private static function cache_result($cache_key, $result) {
        set_transient($cache_key, $result, self::$cache_timeout);
        self::$cache[$cache_key] = array(
            'data' => $result,
            'timestamp' => time()
        );
    }
    
    /**
     * Vider le cache de recherche
     */
    public static function clear_cache() {
        self::$cache = array();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Cache cleared');
        }
    }
    
    /**
     * Obtenir des statistiques sur les filtres rapides
     * 
     * @return array Statistiques
     */
    public static function get_quick_filter_stats() {
        $stats = array();
        $cache_key = 'sisme_search_quick_stats';
        $cached_stats = get_transient($cache_key);
        if ($cached_stats !== false) {
            return $cached_stats;
        }
        $stats = array(
            'popular' => 0,
            'new' => 12,
            Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => 8,
            'is_comming' => 6
        );
        set_transient($cache_key, $stats, 3600);
        return $stats;
    }
}
Sisme_Search_Filters::init();