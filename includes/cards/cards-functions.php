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

class Sisme_Cards_Functions {
    
    /**
     * 📊 Récupérer les données complètes d'un jeu
     * 
     * @param int $term_id ID du jeu
     * @return array|false Données du jeu ou false si incomplet
     */
    public static function get_game_data($term_id) {
        
        // Vérifications de base
        $description = get_term_meta($term_id, 'game_description', true);
        $cover_id = get_term_meta($term_id, 'cover_main', true);
        
        if (empty($description) || empty($cover_id)) {
            return false;
        }
        
        // URL de la cover
        $cover_url = wp_get_attachment_image_url($cover_id, 'full');
        if (!$cover_url) {
            return false;
        }
        
        // Récupérer les infos de base
        $term = get_term($term_id);
        
        // Construire les données
        $game_data = array(
            'term_id' => $term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => wp_strip_all_tags($description),
            'cover_url' => $cover_url,
            'game_url' => home_url($term->slug . '/'),
            'genres' => self::get_game_genres($term_id),
            'modes' => self::get_game_modes($term_id),               
        	'platforms' => self::get_game_platforms_grouped($term_id),   
            'release_date' => get_term_meta($term_id, 'release_date', true),
            'last_update' => get_term_meta($term_id, 'last_update', true),
            'timestamp' => self::get_game_timestamp($term_id)
        );
        
        return $game_data;
    }

    /**
	 * 🎮 Récupérer les plateformes groupées par famille
	 * Retourne un tableau avec les groupes et les détails pour tooltips
	 */
	public static function get_game_platforms_grouped($term_id) {
	    $platforms = get_term_meta($term_id, 'game_platforms', true) ?: array();
	    
	    if (empty($platforms)) {
	        return array();
	    }
	    
	    // Définition des groupes
	    $groups = array(
	        'pc' => array(
	            'platforms' => array('windows', 'mac', 'linux'),
	            'icon' => '💻',
	            'label' => 'PC'
	        ),
	        'console' => array(
	            'platforms' => array('xbox', 'playstation', 'switch'),
	            'icon' => '🎮', 
	            'label' => 'Console'
	        ),
	        'mobile' => array(
	            'platforms' => array('ios', 'android'),
	            'icon' => '📱',
	            'label' => 'Mobile'
	        ),
	        'web' => array(
	            'platforms' => array('web'),
	            'icon' => '🌐',
	            'label' => 'Web'
	        )
	    );
	    
	    // Noms complets pour tooltips
	    $platform_names = array(
	        'windows' => 'Windows',
	        'mac' => 'macOS', 
	        'linux' => 'Linux',
	        'xbox' => 'Xbox',
	        'playstation' => 'PlayStation',
	        'switch' => 'Nintendo Switch',
	        'ios' => 'iOS',
	        'android' => 'Android',
	        'web' => 'Navigateur Web'
	    );
	    
	    $grouped_platforms = array();
	    
	    foreach ($groups as $group_key => $group_data) {
	        $found_platforms = array_intersect($platforms, $group_data['platforms']);
	        
	        if (!empty($found_platforms)) {
	            $platform_details = array();
	            foreach ($found_platforms as $platform) {
	                if (isset($platform_names[$platform])) {
	                    $platform_details[] = $platform_names[$platform];
	                }
	            }
	            
	            $grouped_platforms[] = array(
	                'group' => $group_key,
	                'icon' => $group_data['icon'],
	                'label' => $group_data['label'],
	                'platforms' => $found_platforms,
	                'tooltip' => implode(', ', $platform_details)
	            );
	        }
	    }
	    
	    return $grouped_platforms;
	}

	/**
	 * 🎯 Récupérer les modes de jeu
	 */
	public static function get_game_modes($term_id) {
	    $modes = get_term_meta($term_id, 'game_modes', true) ?: array();
	    
	    if (empty($modes)) {
	        return array();
	    }
	    
	    // Traduction des modes
	    $mode_labels = array(
	        'solo' => 'Solo',
	        'multijoueur' => 'Multijoueur',
	        'coop' => 'Coopération',
	        'competitif' => 'Compétitif',
	        'online' => 'En ligne',
	        'local' => 'Local'
	    );
	    
	    $formatted_modes = array();
	    foreach ($modes as $mode) {
	        if (isset($mode_labels[$mode])) {
	            $formatted_modes[] = array(
	                'key' => $mode,
	                'label' => $mode_labels[$mode]
	            );
	        }
	    }
	    
	    return $formatted_modes;
	}
    
    /**
     * 🏷️ Récupérer les genres du jeu
     */
    public static function get_game_genres($term_id) {
        $genre_ids = get_term_meta($term_id, 'game_genres', true) ?: array();
        $genres = array();
        
        foreach ($genre_ids as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                $genres[] = array(
                    'id' => $genre_id,
                    'name' => str_replace('jeux-', '', $genre->name), // Nettoyer le préfixe
                    'slug' => $genre->slug
                );
            }
        }
        
        return $genres;
    }
    
    /**
     * 🎮 Récupérer les plateformes du jeu
     */
    public static function get_game_platforms($term_id) {
        return get_term_meta($term_id, 'game_platforms', true) ?: array();
    }
    
    /**
     * ⏰ Déterminer le timestamp de référence du jeu
     */
    public static function get_game_timestamp($term_id) {
        $last_update = get_term_meta($term_id, 'last_update', true);
        $release_date = get_term_meta($term_id, 'release_date', true);
        
        // Priorité : last_update > release_date > maintenant
        if ($last_update) {
            return strtotime($last_update);
        } elseif ($release_date) {
            return strtotime($release_date);
        } else {
            return time();
        }
    }
    
    /**
     * 🏷️ Déterminer le badge du jeu selon sa fraîcheur
     */
    public static function get_game_badge($game_data) {
        $now = time();
        $game_time = $game_data['timestamp'];
        $diff_days = ($now - $game_time) / DAY_IN_SECONDS;
        
        if ($diff_days <= 7) {
            return array(
                'class' => 'sisme-badge-new',
                'text' => 'NOUVEAU'
            );
        } elseif ($diff_days <= 30 && !empty($game_data['last_update'])) {
            return array(
                'class' => 'sisme-badge-updated',
                'text' => 'MIS À JOUR'
            );
        }
        
        return null; // Pas de badge
    }
    
    /**
     * 🕒 Formater une date en format relatif
     */
    public static function format_relative_date($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < HOUR_IN_SECONDS) {
            $minutes = floor($diff / MINUTE_IN_SECONDS);
            return $minutes <= 1 ? 'Il y a 1 minute' : "Il y a {$minutes} minutes";
        } elseif ($diff < DAY_IN_SECONDS) {
            $hours = floor($diff / HOUR_IN_SECONDS);
            return $hours <= 1 ? 'Il y a 1 heure' : "Il y a {$hours} heures";
        } elseif ($diff < WEEK_IN_SECONDS) {
            $days = floor($diff / DAY_IN_SECONDS);
            return $days <= 1 ? 'Il y a 1 jour' : "Il y a {$days} jours";
        } elseif ($diff < MONTH_IN_SECONDS) {
            $weeks = floor($diff / WEEK_IN_SECONDS);
            return $weeks <= 1 ? 'Il y a 1 semaine' : "Il y a {$weeks} semaines";
        } else {
            $months = floor($diff / MONTH_IN_SECONDS);
            return $months <= 1 ? 'Il y a 1 mois' : "Il y a {$months} mois";
        }
    }
    
    /**
     * ✂️ Tronquer intelligemment un texte sur les mots
     */
    public static function truncate_smart($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return rtrim($truncated, '.,;:!?') . '...';
    }
    
    /**
     * 🎮 Obtenir l'icône d'une plateforme
     */
    public static function get_platform_icon($platform) {
        $icons = array(
            'windows' => '🖥️',
            'mac' => '🖥️',
            'linux' => '🖥️',
            'playstation' => '🎮',
            'xbox' => '🎮',
            'nintendo-switch' => '🎮',
            'ios' => '📱',
            'android' => '📱',
            'web' => '🌐'
        );
        
        return isset($icons[$platform]) ? $icons[$platform] : '💻';
    }
    
    /**
     * 🎨 Générer une classe CSS avec options
     */
    public static function build_css_class($base_class, $modifiers = array(), $custom_class = '') {
        $classes = array($base_class);
        
        // Ajouter les modificateurs
        foreach ($modifiers as $modifier) {
            if (!empty($modifier)) {
                $classes[] = $base_class . '--' . $modifier;
            }
        }
        
        // Ajouter la classe personnalisée
        if (!empty($custom_class)) {
            $classes[] = $custom_class;
        }
        
        return implode(' ', $classes);
    }

    /**
	 * 🔍 Récupérer les IDs des jeux selon les critères
	 * 
	 * @param array $criteria Critères de recherche
	 * @return array IDs des jeux trouvés
	 */
	public static function get_games_by_criteria($criteria = array()) {
	    
	    // Critères par défaut
	    $defaults = array(
	        'genres' => array(),
	        'is_team_choice' => false,
	        'sort_by_date' => true,
	        'max_results' => -1,
	        'debug' => false
	    );
	    
	    $criteria = array_merge($defaults, $criteria);
	    
	    // Debug
	    if ($criteria['debug'] && defined('WP_DEBUG') && WP_DEBUG) {
	        error_log('[Sisme Cards Functions] Critères de recherche: ' . print_r($criteria, true));
	    }
	    
	    // Arguments de base pour get_terms()
	    $get_terms_args = array(
	        'taxonomy' => 'game_tag',
	        'hide_empty' => false,
	        'fields' => 'ids',
	        'number' => ($criteria['max_results'] > 0) ? $criteria['max_results'] * 2 : 0, // Marge pour le tri
	    );
	    
	    // Construire la meta_query selon les critères
	    $meta_query = self::build_criteria_meta_query($criteria);
	    if (!empty($meta_query)) {
	        $get_terms_args['meta_query'] = $meta_query;
	    }
	    
	    // Récupérer les termes
	    $terms = get_terms($get_terms_args);
	    
	    if (is_wp_error($terms) || empty($terms)) {
	        if ($criteria['debug']) {
	            error_log('[Sisme Cards Functions] Aucun terme trouvé ou erreur: ' . (is_wp_error($terms) ? $terms->get_error_message() : 'vide'));
	        }
	        return array();
	    }
	    
	    // Filtrer pour ne garder que les jeux avec données complètes
	    $valid_games = self::filter_games_with_complete_data($terms);
	    
	    // Tri par date de sortie si demandé
	    if ($criteria['sort_by_date']) {
	        $valid_games = self::sort_games_by_release_date($valid_games);
	    }
	    
	    // Appliquer la limite finale
	    if ($criteria['max_results'] > 0) {
	        $valid_games = array_slice($valid_games, 0, $criteria['max_results']);
	    }
	    
	    if ($criteria['debug']) {
	        error_log('[Sisme Cards Functions] Jeux valides après filtrage: ' . count($valid_games));
	    }
	    
	    return $valid_games;
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
	    
	    $genre_query = array('relation' => 'OR');
	    
	    foreach ($genres as $genre) {
	        $genre = trim($genre);
	        if (empty($genre)) {
	            continue;
	        }
	        
	        // Si c'est un ID numérique
	        if (is_numeric($genre)) {
	            $genre_query[] = array(
	                'key' => 'game_genres',
	                'value' => '"term_id":"' . intval($genre) . '"',
	                'compare' => 'LIKE'
	            );
	        } else {
	            // Si c'est un slug ou nom, chercher par ces valeurs
	            $genre_query[] = array(
	                'key' => 'game_genres',
	                'value' => '"' . sanitize_text_field($genre) . '"',
	                'compare' => 'LIKE'
	            );
	        }
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
	        $game_data = self::get_game_data($term_id);
	        if ($game_data !== false) {
	            $valid_games[] = $term_id;
	        }
	    }
	    
	    return $valid_games;
	}

	/**
	 * 📅 Trier les jeux par date de sortie (plus récents en premier)
	 * 
	 * @param array $term_ids IDs des termes
	 * @return array IDs triés par date
	 */
	private static function sort_games_by_release_date($term_ids) {
	    
	    // Récupérer les dates pour chaque jeu
	    $games_with_dates = array();
	    
	    foreach ($term_ids as $term_id) {
	        $release_date = get_term_meta($term_id, 'release_date', true);
	        
	        // Convertir en timestamp pour le tri
	        if (!empty($release_date)) {
	            $timestamp = strtotime($release_date);
	        } else {
	            // Si pas de date, utiliser une date très ancienne pour mettre en fin
	            $timestamp = 0;
	        }
	        
	        $games_with_dates[] = array(
	            'term_id' => $term_id,
	            'timestamp' => $timestamp
	        );
	    }
	    
	    // Trier par timestamp décroissant (plus récent en premier)
	    usort($games_with_dates, function($a, $b) {
	        return $b['timestamp'] - $a['timestamp'];
	    });
	    
	    // Extraire les IDs triés
	    return array_column($games_with_dates, 'term_id');
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
	    
	    // Récupérer tous les jeux sans filtre
	    $all_terms = get_terms(array(
	        'taxonomy' => 'game_tag',
	        'hide_empty' => false,
	        'fields' => 'ids'
	    ));
	    
	    if (is_wp_error($all_terms)) {
	        return $stats;
	    }
	    
	    $stats['total_games'] = count($all_terms);
	    
	    // Analyser chaque jeu
	    foreach ($all_terms as $term_id) {
	        $game_data = self::get_game_data($term_id);
	        
	        if ($game_data) {
	            $stats['games_with_data']++;
	            
	            // Analyser les genres
	            if (!empty($game_data['genres'])) {
	                foreach ($game_data['genres'] as $genre) {
	                    $genre_name = $genre['name'];
	                    if (!isset($stats['games_by_genre'][$genre_name])) {
	                        $stats['games_by_genre'][$genre_name] = 0;
	                    }
	                    $stats['games_by_genre'][$genre_name]++;
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
	        'game_ids' => self::get_games_by_criteria($criteria),
	        'execution_time' => 0,
	        'stats' => array()
	    );
	    
	    $result['stats']['found_count'] = count($result['game_ids']);
	    $result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
	    
	    // Ajouter les données des premiers jeux pour debug
	    $result['sample_games'] = array();
	    $sample_ids = array_slice($result['game_ids'], 0, 3);
	    
	    foreach ($sample_ids as $game_id) {
	        $game_data = self::get_game_data($game_id);
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
