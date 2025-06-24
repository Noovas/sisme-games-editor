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
        // Valider et nettoyer les paramètres
        $validated_params = self::validate_search_params($search_params);
        
        // Vérifier le cache
        $cache_key = self::get_cache_key($validated_params);
        $cached_result = self::get_cached_result($cache_key);
        
        if ($cached_result !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Using cached result for: ' . $cache_key);
            }
            return $cached_result;
        }
        
        // Effectuer la recherche
        $results = self::execute_search($validated_params);
        
        // Mettre en cache
        self::cache_result($cache_key, $results);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Found ' . count($results['games']) . ' games for query: ' . $validated_params['query']);
        }
        
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
        
        // Terme de recherche
        $validated['query'] = isset($params['query']) ? sanitize_text_field($params['query']) : '';
        
        // Genres (IDs de catégories)
        $validated['genres'] = array();
        if (isset($params['genres']) && is_array($params['genres'])) {
            $validated['genres'] = array_map('intval', array_filter($params['genres']));
        }
        /*
        // Plateformes 
        $validated['platforms'] = array();
        if (isset($params['platforms']) && is_array($params['platforms'])) {
            $allowed_platforms = array('pc', 'console', 'mobile');
            $validated['platforms'] = array_intersect($params['platforms'], $allowed_platforms);
        }
        */
        // Statut de sortie
        $validated['status'] = '';
        if (isset($params['status']) && in_array($params['status'], array('released', 'upcoming'))) {
            $validated['status'] = $params['status'];
        }
        
        // Filtres rapides
        $validated['quick_filter'] = '';
        if (isset($params['quick_filter']) && in_array($params['quick_filter'], array('popular', 'new', 'featured', 'exclusive'))) {
            $validated['quick_filter'] = $params['quick_filter'];
        }
        
        // Tri
        $validated['sort'] = isset($params['sort']) ? $params['sort'] : 'relevance';
        $allowed_sorts = array('relevance', 'name_asc', 'name_desc', 'date_asc', 'date_desc');
        if (!in_array($validated['sort'], $allowed_sorts)) {
            $validated['sort'] = 'relevance';
        }
        
        // Pagination
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
        // Construire les critères pour le système Cards
        $criteria = self::build_cards_criteria($params);
        
        // Utiliser le système Cards pour récupérer les jeux
        if (!class_exists('Sisme_Cards_Functions')) {
            return array(
                'games' => array(),
                'total' => 0,
                'error' => 'Module Cards non disponible'
            );
        }
        
        // Récupérer tous les jeux correspondants
        $all_games = Sisme_Cards_Functions::get_games_by_criteria($criteria);
        
        // Appliquer les filtres personnalisés
        $filtered_games = self::apply_custom_filters($all_games, $params);
        
        // Appliquer le tri
        $sorted_games = self::apply_sorting($filtered_games, $params['sort']);
        
        // Calculer la pagination
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
        
        // Recherche textuelle
        if (!empty($params['query'])) {
            $criteria['search'] = $params['query'];
        }
        
        // Filtres par genres - Convertir IDs en slugs
        if (!empty($params['genres'])) {
            $genre_slugs = self::convert_genre_ids_to_slugs($params['genres']);
            if (!empty($genre_slugs)) {
                $criteria['genres'] = $genre_slugs;
                
                // Debug pour vérifier la conversion
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Sisme Search: Genre IDs ' . implode(',', $params['genres']) . ' → slugs ' . implode(',', $genre_slugs));
                }
            }
        }
        
        /*
        // Filtres par plateformes (commenté temporairement)
        if (!empty($params['platforms'])) {
            $criteria['platforms'] = $params['platforms'];
        }
        */
        
        // Statut de sortie
        if (!empty($params['status'])) {
            $criteria['released'] = ($params['status'] === 'released') ? 1 : 0;
        }
        
        // Filtres rapides
        if (!empty($params['quick_filter'])) {
            switch ($params['quick_filter']) {
                case 'featured':
                    // $criteria['is_team_choice'] = true;
                    break;
                case 'upcoming':
                    // Logique pour jeux à venir
                    break;
                case 'new':
                    // Logique pour nouveautés
                    break;
            }
        }
        
        return $criteria;
    }

    /**
     * Convertir les IDs de genres en slugs
     * 
     * @param array $genre_ids Liste des IDs de genres
     * @return array Liste des slugs de genres (les noms en fait)
     */
    private static function convert_genre_ids_to_slugs($genre_ids) {
        $genre_names = array();
        
        foreach ($genre_ids as $genre_id) {
            $genre_id = intval($genre_id);
            if ($genre_id <= 0) {
                continue;
            }
            
            // Récupérer la catégorie par ID
            $category = get_category($genre_id);
            
            if ($category && !is_wp_error($category)) {
                // 🎯 FIX: Utiliser le NOM de la catégorie (pas le slug)
                $name = $category->name;
                
                // Supprimer le préfixe "jeux-" si présent pour être compatible
                $clean_name = preg_replace('/^jeux-/i', '', $name);
                
                $genre_names[] = $clean_name;
                
                // Debug pour tracer la conversion
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Sisme Search: Genre ID {$genre_id} → nom '{$clean_name}'");
                }
            } else {
                // Genre non trouvé, log en mode debug
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Sisme Search: Genre ID {$genre_id} non trouvé");
                }
            }
        }
        
        return array_unique($genre_names);
    }

    /**
     * 🔧 Méthode pour tester la conversion
     */
    public static function debug_genres_conversion($genre_ids) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log('=== DEBUG CONVERSION GENRES ===');
        error_log('IDs reçus: ' . implode(', ', $genre_ids));
        
        $slugs = self::convert_genre_ids_to_slugs($genre_ids);
        error_log('Slugs générés: ' . implode(', ', $slugs));
        
        // Tester avec Cards Functions
        if (class_exists('Sisme_Cards_Functions')) {
            $criteria = array('genres' => $slugs);
            $games = Sisme_Cards_Functions::get_games_by_criteria($criteria);
            error_log('Jeux trouvés avec les slugs: ' . count($games));
        }
        
        error_log('=== FIN DEBUG CONVERSION ===');
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
        /*
        // Filtrage par plateformes (si pas géré par Cards)
        if (!empty($params['platforms'])) {
            $filtered = array_filter($filtered, function($game) use ($params) {
                return self::game_matches_platforms($game, $params['platforms']);
            });
        }
        */
        // Autres filtres personnalisés peuvent être ajoutés ici
        
        return array_values($filtered); // Réindexer le tableau
    }
    
    /**
     * Vérifier si un jeu correspond aux plateformes demandées
     * 
     * @param array $game Données du jeu
     * @param array $platforms Plateformes recherchées
     * @return bool True si correspondance
     */
    private static function game_matches_platforms($game, $platforms) {
        if (!isset($game['platforms']) || empty($game['platforms'])) {
            return false;
        }
        
        $game_platforms = is_array($game['platforms']) ? $game['platforms'] : array($game['platforms']);
        
        foreach ($platforms as $platform) {
            switch ($platform) {
                case 'pc':
                    if (self::array_contains_any($game_platforms, array('PC', 'Steam', 'Epic', 'GOG'))) {
                        return true;
                    }
                    break;
                case 'console':
                    if (self::array_contains_any($game_platforms, array('PlayStation', 'PS5', 'PS4', 'Xbox', 'Nintendo', 'Switch'))) {
                        return true;
                    }
                    break;
                case 'mobile':
                    if (self::array_contains_any($game_platforms, array('Mobile', 'iOS', 'Android'))) {
                        return true;
                    }
                    break;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si un tableau contient au moins un élément d'un autre tableau
     * 
     * @param array $haystack Tableau à chercher
     * @param array $needles Éléments à trouver
     * @return bool True si au moins un élément trouvé
     */
    private static function array_contains_any($haystack, $needles) {
        foreach ($needles as $needle) {
            foreach ($haystack as $item) {
                if (stripos($item, $needle) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Appliquer le tri aux résultats
     * 
     * @param array $games Liste des IDs de jeux
     * @param string $sort_type Type de tri
     * @return array IDs des jeux triés
     */
    private static function apply_sorting($games, $sort_type) {
        if (empty($games)) {
            return $games;
        }
    
        if (empty($sort_type) || $sort_type === 'relevance') {
            return $games;
        }
        
        switch ($sort_type) {
            case 'name_asc':
                usort($games, function($id_a, $id_b) {
                    $name_a = get_term($id_a)->name ?? '';
                    $name_b = get_term($id_b)->name ?? '';
                    return strcasecmp($name_a, $name_b);
                });
                break;
                
            case 'name_desc':
                usort($games, function($id_a, $id_b) {
                    $name_a = get_term($id_a)->name ?? '';
                    $name_b = get_term($id_b)->name ?? '';
                    return strcasecmp($name_b, $name_a);
                });
                break;
                
            case 'date_desc':
                usort($games, function($id_a, $id_b) {
                    $date_a = get_term_meta($id_a, 'release_date', true) ?: '1970-01-01';
                    $date_b = get_term_meta($id_b, 'release_date', true) ?: '1970-01-01';
                    return strcmp($date_b, $date_a);
                });
                break;
                
            case 'date_asc':
                usort($games, function($id_a, $id_b) {
                    $date_a = get_term_meta($id_a, 'release_date', true) ?: '1970-01-01';
                    $date_b = get_term_meta($id_b, 'release_date', true) ?: '1970-01-01';
                    return strcmp($date_a, $date_b);
                });
                break;
                
            default:
                break;
        }
        
        return $games;
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
        
        // Vérifier le cache en mémoire
        if (isset(self::$cache[$cache_key])) {
            $cache_data = self::$cache[$cache_key];
            if (time() - $cache_data['timestamp'] < self::$cache_timeout) {
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
        // Cache WordPress (transient)
        set_transient($cache_key, $result, self::$cache_timeout);
        
        // Cache en mémoire pour cette requête
        self::$cache[$cache_key] = array(
            'data' => $result,
            'timestamp' => time()
        );
    }
    
    /**
     * Vider le cache de recherche
     */
    public static function clear_cache() {
        // Vider le cache en mémoire
        self::$cache = array();
        
        // Vider les transients (plus complexe, on peut ajouter une liste des clés)
        // Pour l'instant, on laisse WordPress gérer l'expiration automatique
        
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
        
        // Utiliser le cache si disponible
        $cache_key = 'sisme_search_quick_stats';
        $cached_stats = get_transient($cache_key);
        
        if ($cached_stats !== false) {
            return $cached_stats;
        }
        
        // Calculer les statistiques
        if (class_exists('Sisme_Cards_Functions')) {
            $stats['popular'] = 0; // Métrique à créer plus tard
            $stats['new'] = count(Sisme_Cards_Functions::get_games_by_criteria(array('sort_by_date' => true, 'limit' => 45)));
            $stats['is_team_choice'] = count(Sisme_Cards_Functions::get_games_by_criteria(array('is_team_choice' => true)));
            
            // Jeux à venir (dates de sortie futures)
            $stats['is_comming'] = self::count_upcoming_games();
            
        } else {
            // Valeurs par défaut si Cards non disponible
            $stats = array(
                'popular' => 0,
                'new' => 12,
                'is_team_choice' => 8,
                'is_comming' => 6
            );
        }
        
        // Mettre en cache pour 1 heure
        set_transient($cache_key, $stats, 3600);
        
        return $stats;
    }

    /**
     * Compter les jeux avec dates de sortie à venir
     * 
     * @return int Nombre de jeux à venir
     */
    private static function count_upcoming_games() {
        // Récupérer tous les jeux avec game_description (jeux complets)
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (is_wp_error($all_games) || empty($all_games)) {
            return 0;
        }
        
        $upcoming_count = 0;
        $today = date('Y-m-d');
        
        foreach ($all_games as $game_term) {
            $release_date = get_term_meta($game_term->term_id, 'release_date', true);
            
            // Vérifier si le jeu a une date de sortie future
            if (!empty($release_date) && $release_date > $today) {
                $upcoming_count++;
            }
        }
        
        return $upcoming_count;
    }
    
    /**
     * Générer un résumé de recherche lisible
     * 
     * @param array $params Paramètres de recherche
     * @param int $total_results Nombre total de résultats
     * @return string Résumé lisible
     */
    public static function get_search_summary($params, $total_results) {
        $summary_parts = array();
        
        // Nombre de résultats
        $summary_parts[] = sprintf(
            _n('%d jeu trouvé', '%d jeux trouvés', $total_results, 'sisme-games-editor'),
            $total_results
        );
        
        // Terme de recherche
        if (!empty($params['query'])) {
            $summary_parts[] = sprintf(
                __('pour "%s"', 'sisme-games-editor'),
                esc_html($params['query'])
            );
        }
        
        // Filtres actifs
        $active_filters = array();
        
        if (!empty($params['genres'])) {
            $active_filters[] = sprintf(
                _n('%d genre', '%d genres', count($params['genres']), 'sisme-games-editor'),
                count($params['genres'])
            );
        }
        
        if (!empty($params['platforms'])) {
            $active_filters[] = sprintf(
                _n('%d plateforme', '%d plateformes', count($params['platforms']), 'sisme-games-editor'),
                count($params['platforms'])
            );
        }
        
        if (!empty($params['status'])) {
            $status_text = ($params['status'] === 'released') ? __('sortis', 'sisme-games-editor') : __('à venir', 'sisme-games-editor');
            $active_filters[] = $status_text;
        }
        
        if (!empty($active_filters)) {
            $summary_parts[] = __('avec filtres:', 'sisme-games-editor') . ' ' . implode(', ', $active_filters);
        }
        
        return implode(' ', $summary_parts);
    }
}

// Initialisation du module
Sisme_Search_Filters::init();