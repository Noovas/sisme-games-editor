<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-functions.php
 * Fonctions utilitaires partagées pour tous les types de cartes
 * 
 * RESPONSABILITÉ:
 * - Récupération des données de jeux
 * - Fonctions de formatage
 * - Utilitaires de calcul (badges, dates, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * 60 * 60); // 86400 secondes
}

class Sisme_Cards_Functions {   

    public static function get_games_by_criteria($criteria = array()) {
		return Sisme_Utils_Games::get_games_by_criteria($criteria);
    }

    /**
	 * 🛠️ FONCTION UTILITAIRE : Déterminer le statut depuis une date
	 * 
	 * @param string $release_date Date au format YYYY-MM-DD
	 * @return array Informations sur le statut
	 */
	private static function get_game_release_status_from_date($release_date) {
	    if (empty($release_date)) {
	        return array('is_released' => true);
	    }
	    
	    $release_timestamp = strtotime($release_date);
	    $current_timestamp = current_time('timestamp');
	    
	    return array(
	        'is_released' => $current_timestamp >= $release_timestamp,
	        'days_diff' => floor(($current_timestamp - $release_timestamp) / DAY_IN_SECONDS)
	    );
	}
    
	/**
	 * Obtenir des statistiques par statut de sortie
	 * 
	 * @return array Statistiques complètes
	 */
	public static function get_release_status_stats() {
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
	    
	    $stats = array(
	        'total' => 0,
	        'released' => 0,
	        'unreleased' => 0,
	        'no_date' => 0,
	        'released_this_week' => 0,
	        'releasing_this_week' => 0
	    );
	    
	    if (is_wp_error($all_games) || empty($all_games)) {
	        return $stats;
	    }
	    
	    $current_timestamp = current_time('timestamp');
	    $week_start = $current_timestamp - (7 * DAY_IN_SECONDS);
	    $week_end = $current_timestamp + (7 * DAY_IN_SECONDS);
	    
	    foreach ($all_games as $game) {
	        $stats['total']++;
	        
	        $status = Sisme_Utils_Games::get_game_release_status($game->term_id);
	        
	        if (empty($status['release_date'])) {
	            $stats['no_date']++;
	            continue;
	        }
	        
	        $release_timestamp = strtotime($status['release_date']);
	        
	        if ($status['is_released']) {
	            $stats['released']++;
	            
	            // Sorti cette semaine ?
	            if ($release_timestamp >= $week_start) {
	                $stats['released_this_week']++;
	            }
	        } else {
	            $stats['unreleased']++;
	            
	            // Sort cette semaine ?
	            if ($release_timestamp <= $week_end) {
	                $stats['releasing_this_week']++;
	            }
	        }
	    }
	    
	    return $stats;
	}

	/**
	 * ✅ Vérifier si un terme a les genres demandés
	 * 
	 * @param int $term_id ID du terme
	 * @param array $requested_genres Genres demandés (noms, slugs ou IDs)
	 * @return bool True si le terme a au moins un des genres demandés
	 */
	private static function term_has_genres($term_id, $requested_genres) {
	    
	    // Récupérer les IDs de genres du jeu
	    $game_genre_ids = get_term_meta($term_id, 'game_genres', true);
	    if (empty($game_genre_ids) || !is_array($game_genre_ids)) {
	        return false;
	    }
	    
	    // Convertir les genres demandés en IDs
	    $requested_genre_ids = self::convert_genres_to_ids($requested_genres);
	    if (empty($requested_genre_ids)) {
	        return false;
	    }
	    
	    // Vérifier s'il y a une intersection
	    $intersection = array_intersect($game_genre_ids, $requested_genre_ids);
	    return !empty($intersection);
	}

	/**
	 * 🎨 Convertir une liste de genres (noms/slugs/IDs) en IDs de catégories
	 * 
	 * @param array $genres Liste des genres
	 * @return array IDs de catégories
	 */
	private static function convert_genres_to_ids($genres) {
	    $genre_ids = array();
	    
	    foreach ($genres as $genre) {
	        $genre = trim($genre);
	        if (empty($genre)) {
	            continue;
	        }
	        
	        // Si c'est déjà un ID numérique
	        if (is_numeric($genre)) {
	            $genre_ids[] = intval($genre);
	            continue;
	        }
	        
	        // Sinon, chercher la catégorie par nom/slug
	        $category = null;
	        
	        // Essayer par slug exact
	        $category = get_category_by_slug($genre);
	        
	        // Essayer par slug avec préfixe "jeux-"
	        if (!$category) {
	            $category = get_category_by_slug('jeux-' . strtolower($genre));
	        }
	        
	        // Essayer par nom exact
	        if (!$category) {
	            $category = get_term_by('name', $genre, 'category');
	        }
	        
	        // Essayer par nom avec préfixe "jeux-"
	        if (!$category) {
	            $category = get_term_by('name', 'jeux-' . ucfirst(strtolower($genre)), 'category');
	        }
	        
	        // Essayer par nom sans casse
	        if (!$category) {
	            $category = get_term_by('name', ucfirst(strtolower($genre)), 'category');
	        }
	        
	        if ($category) {
	            $genre_ids[] = $category->term_id;
	        }
	    }
	    
	    return array_unique($genre_ids);
	}

	/**
	 * 🎭 Construire la meta_query selon les critères
	 * 
	 * @param array $criteria Critères de filtrage
	 * @return array Meta query pour get_terms()
	 */
	private static function build_criteria_meta_query($criteria) {
	    $meta_query = array();
	    
	    // Filtre par genres
	    if (!empty($criteria['genres'])) {
	        $genre_query = self::build_genres_meta_query($criteria['genres']);
	        if (!empty($genre_query)) {
	            $meta_query[] = $genre_query;
	        }
	    }
	    
	    // Filtre is_team_choice (quand disponible)
	    if ($criteria['is_team_choice']) {
	        $meta_query[] = array(
	            'key' => 'is_team_choice',
	            'value' => '1',
	            'compare' => '='
	        );
	        
	        if ($criteria['debug']) {
	            error_log('[Sisme Cards Functions] Filtre is_team_choice ajouté (si meta existe)');
	        }
	    }
	    
	    // Relation ET entre les critères
	    if (count($meta_query) > 1) {
	        $meta_query['relation'] = 'AND';
	    }
	    
	    return $meta_query;
	}

	/**
	 * 🎨 Construire la meta_query pour filtrer par genres
	 * 
	 * @param array $genres Liste des genres (slugs, IDs ou noms)
	 * @return array Meta query pour les genres
	 */
	private static function build_genres_meta_query($genres) {
	    if (empty($genres)) {
	        return array();
	    }
	    
	    // Convertir tous les genres en IDs de catégories
	    $genre_ids = array();
	    
	    foreach ($genres as $genre) {
	        $genre = trim($genre);
	        if (empty($genre)) {
	            continue;
	        }
	        
	        // Si c'est un ID numérique, l'utiliser directement
	        if (is_numeric($genre)) {
	            $genre_ids[] = intval($genre);
	        } else {
	            // Si c'est un nom/slug, trouver l'ID de la catégorie
	            $category = null;
	            
	            // Essayer par slug
	            $category = get_category_by_slug($genre);
	            if (!$category) {
	                // Essayer par slug avec préfixe "jeux-"
	                $category = get_category_by_slug('jeux-' . strtolower($genre));
	            }
	            if (!$category) {
	                // Essayer par nom exact
	                $category = get_term_by('name', $genre, 'category');
	            }
	            if (!$category) {
	                // Essayer par nom avec préfixe "jeux-"
	                $category = get_term_by('name', 'jeux-' . ucfirst(strtolower($genre)), 'category');
	            }
	            
	            if ($category) {
	                $genre_ids[] = $category->term_id;
	            }
	        }
	    }
	    
	    if (empty($genre_ids)) {
	        return array();
	    }
	    
	    // Construire la meta_query pour rechercher dans le tableau sérialisé
	    $genre_query = array('relation' => 'OR');
	    
	    foreach ($genre_ids as $genre_id) {
	        // game_genres est un tableau sérialisé d'IDs
	        // On cherche l'ID dans le tableau sérialisé
	        $genre_query[] = array(
	            'key' => 'game_genres',
	            'value' => sprintf('"%d"', $genre_id),
	            'compare' => 'LIKE'
	        );
	        
	        // Alternative : chercher aussi avec i:ID; (format sérialisé)
	        $genre_query[] = array(
	            'key' => 'game_genres',
	            'value' => sprintf('i:%d;', $genre_id),
	            'compare' => 'LIKE'
	        );
	    }
	    
	    return $genre_query;
	}

	/**
	 * ✅ Filtrer pour ne garder que les jeux avec données complètes
	 * 
	 * @param array $term_ids Liste d'IDs de termes
	 * @return array IDs des jeux avec données complètes
	 */
	private static function filter_games_with_complete_data($term_ids) {
	    $valid_games = array();
	    
	    foreach ($term_ids as $term_id) {
	        // Utiliser get_game_data pour vérifier la complétude
	        $game_data = Sisme_Utils_Games::get_game_data($term_id);
	        if ($game_data !== false) {
	            $valid_games[] = $term_id;
	        }
	    }
	    
	    return $valid_games;
	}

	/**
	 * 📊 Obtenir les statistiques des jeux selon critères (pour debug)
	 * 
	 * @param array $criteria Critères de recherche
	 * @return array Statistiques détaillées
	 */
	public static function get_games_stats_by_criteria($criteria = array()) {
	    $stats = array(
	        'total_games' => 0,
	        'games_with_data' => 0,
	        'games_by_genre' => array(),
	        'games_with_team_choice' => 0,
	        'date_range' => array('oldest' => null, 'newest' => null)
	    );
	    

	    $all_terms = get_terms(array(
	        'taxonomy' => 'post_tag',
	        'hide_empty' => false,
	        'fields' => 'ids',
	        'meta_query' => array(
	            array(
	                'key' => 'game_description',
	                'compare' => 'EXISTS'
	            )
	        )
	    ));
	    
	    if (is_wp_error($all_terms)) {
	        return $stats;
	    }
	    
	    $stats['total_games'] = count($all_terms);
	    
	    // Analyser chaque jeu
	    foreach ($all_terms as $term_id) {
	        $game_data = Sisme_Utils_Games::get_game_data($term_id);
	        
	        if ($game_data) {
	            $stats['games_with_data']++;
	            
	            // ✅ CORRECTION : Analyser TOUS les genres via get_term_meta directement
	            $all_genre_ids = get_term_meta($term_id, 'game_genres', true);
	            
	            if (!empty($all_genre_ids) && is_array($all_genre_ids)) {
	                foreach ($all_genre_ids as $genre_id) {
	                    $category = get_category($genre_id);
	                    if ($category) {
	                        $genre_name = str_replace('jeux-', '', $category->name);
	                        $genre_name = ucfirst($genre_name);
	                        if (!isset($stats['games_by_genre'][$genre_name])) {
	                            $stats['games_by_genre'][$genre_name] = 0;
	                        }
	                        $stats['games_by_genre'][$genre_name]++;
	                    }
	                }
	            }
	            
	            // Analyser les dates
	            if (!empty($game_data['release_date'])) {
	                $date = $game_data['release_date'];
	                if (is_null($stats['date_range']['oldest']) || $date < $stats['date_range']['oldest']) {
	                    $stats['date_range']['oldest'] = $date;
	                }
	                if (is_null($stats['date_range']['newest']) || $date > $stats['date_range']['newest']) {
	                    $stats['date_range']['newest'] = $date;
	                }
	            }
	        }
	    }
	    arsort($stats['games_by_genre']);
	    return $stats;
	}

	/**
	 * 🎯 Fonction helper pour tester les critères
	 * 
	 * @param array $criteria Critères à tester
	 * @return array Résultat du test avec détails
	 */
	public static function test_criteria($criteria = array()) {
	    $start_time = microtime(true);
	    
	    $result = array(
	        'criteria' => $criteria,
	        'game_ids' => Sisme_Utils_Games::get_games_by_criteria($criteria),
	        'execution_time' => 0,
	        'stats' => array()
	    );
	    
	    $result['stats']['found_count'] = count($result['game_ids']);
	    $result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
	    
	    // Ajouter les données des premiers jeux pour debug
	    $result['sample_games'] = array();
	    $sample_ids = array_slice($result['game_ids'], 0, 3);
	    
	    foreach ($sample_ids as $game_id) {
	        $game_data = Sisme_Utils_Games::get_game_data($game_id);
	        if ($game_data) {
	            $result['sample_games'][] = array(
	                'id' => $game_id,
	                'name' => $game_data['name'],
	                'release_date' => $game_data['release_date'],
	                'genres' => array_column($game_data['genres'], 'name')
	            );
	        }
	    }
	    
	    return $result;
	}
}
