<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-games.php
 * Fonctions métier pour la gestion des jeux
 * 
 * RESPONSABILITÉ:
 * - Récupération des données complètes de jeux
 * - Recherche et filtrage de jeux par critères
 * - Gestion des plateformes, genres et modes
 * - Statut de sortie et badges de fraîcheur
 * - Tri et validation des données jeux
 * 
 * DÉPENDANCES:
 * - WordPress Meta API
 * - Taxonomies WordPress (post_tag)
 * - utils-formatting.php (pour formatage)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Games {
    
    // ✅ Constantes pour les collections
    const COLLECTION_FAVORITE = 'favorite';
    const COLLECTION_OWNED = 'owned';
    
    // ✅ META KEYS - Stockage WordPress
    const META_DESCRIPTION = 'game_description';
    const META_COVER_MAIN = 'cover_main';
    const META_COVER_NEWS = 'cover_news'; 
    const META_COVER_PATCH = 'cover_patch';
    const META_COVER_TEST = 'cover_test';
    const META_RELEASE_DATE = 'release_date';
    const META_LAST_UPDATE = 'last_update';
    const META_PLATFORMS = 'game_platforms';
    const META_GENRES = 'game_genres';
    const META_MODES = 'game_modes';
    const META_TEAM_CHOICE = 'is_team_choice';
    const META_EXTERNAL_LINKS = 'external_links';
    const META_TRAILER_LINK = 'trailer_link';
    const META_SCREENSHOTS = 'screenshots';
    const META_DEVELOPERS = 'game_developers';
    const META_PUBLISHERS = 'game_publishers';
    
    // ✅ KEY - Interface publique
    const KEY_TERM_ID = 'term_id';
    const KEY_ID = 'id';
    const KEY_NAME = 'name';
    const KEY_TITLE = 'title';
    const KEY_SLUG = 'slug';
    const KEY_DESCRIPTION = 'description';
    const KEY_COVER_URL = 'cover_url';
    const KEY_COVER_ID = 'cover_id';
    const KEY_GAME_URL = 'game_url';
    const KEY_RELEASE_DATE = 'release_date';
    const KEY_LAST_UPDATE = 'last_update';
    const KEY_TIMESTAMP = 'timestamp';
    const KEY_GENRES = 'genres';
    const KEY_MODES = 'modes';
    const KEY_PLATFORMS = 'platforms';
    const KEY_IS_TEAM_CHOICE = 'is_team_choice';
    const KEY_EXTERNAL_LINKS = 'external_links';
    const KEY_TRAILER_LINK = 'trailer_link';
    const KEY_SCREENSHOTS = 'screenshots';
    const KEY_DEVELOPERS = 'developers';
    const KEY_PUBLISHERS = 'publishers';
    const KEY_COVERS = 'covers';
    const KEY_RELEASE_STATUS = 'release_status';
    
    // ✅ COVERS SUB-KEYS
    const KEY_COVER_MAIN = 'main';
    const KEY_COVER_NEWS = 'news';
    const KEY_COVER_PATCH = 'patch';
    const KEY_COVER_TEST = 'test';
    
    /**
     * 🏷️ Récupérer les genres d'un jeu
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return array Genres formatés avec id, name, slug
     */
    public static function get_game_genres($term_id) {
        $genre_ids = get_term_meta($term_id, self::META_GENRES, true) ?: array();
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
     * 🎯 Récupérer les modes de jeu
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return array Modes formatés avec key et label
     */
    public static function get_game_modes($term_id) {
        $modes = get_term_meta($term_id, self::META_MODES, true) ?: array();
        if (empty($modes)) {
            return array();
        }
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
     * 🎮 Récupérer les plateformes groupées par famille
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return array Plateformes groupées avec icônes et tooltips
     */
    public static function get_game_platforms_grouped($term_id) {
        $platforms = get_term_meta($term_id, self::META_PLATFORMS, true) ?: array();
        if (empty($platforms)) {
            return array();
        }
        $groups = array(
            'pc' => array(
                'platforms' => array('windows', 'mac', 'linux'),
                'icon' => Sisme_Utils_Formatting::DEFAULT_PLATEFORM_PC,
                'label' => 'PC'
            ),
            'console' => array(
                'platforms' => array('xbox', 'playstation', 'switch'),
                'icon' => Sisme_Utils_Formatting::DEFAULT_PLATEFORM_CONSOLE, 
                'label' => 'Console'
            ),
            'mobile' => array(
                'platforms' => array('ios', 'android'),
                'icon' => Sisme_Utils_Formatting::DEFAULT_PLATEFORM_MOBILE,
                'label' => 'Mobile'
            ),
            'web' => array(
                'platforms' => array('web'),
                'icon' => Sisme_Utils_Formatting::DEFAULT_PLATEFORM_WEB,
                'label' => 'Web'
            )
        );
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
     * 📊 Récupérer les données complètes d'un jeu
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return array|false Données complètes du jeu avec clés API ou false si terme invalide
     */
    public static function get_game_data($term_id) {
        if (empty($term_id) || !is_numeric($term_id)) {
            return false;
        }
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return false;
        }
        $cover_id = get_term_meta($term_id, self::META_COVER_MAIN, true) ?: 0;
        $cover_url = $cover_id ? wp_get_attachment_image_url($cover_id, 'full') : '';
        $release_date = get_term_meta($term_id, self::META_RELEASE_DATE, true) ?: '';
        $timestamp = !empty($release_date) ? strtotime($release_date) : 0;
        
        return array(
            self::KEY_TERM_ID => $term_id,
            self::KEY_ID => $term_id,
            self::KEY_NAME => $term->name,
            self::KEY_TITLE => $term->name,
            self::KEY_SLUG => $term->slug,
            self::KEY_DESCRIPTION => get_term_meta($term_id, self::META_DESCRIPTION, true) ?: '',
            self::KEY_COVER_URL => $cover_url,
            self::KEY_COVER_ID => $cover_id,
            self::KEY_GAME_URL => home_url($term->slug . '/'),
            self::KEY_RELEASE_DATE => $release_date,
            self::KEY_LAST_UPDATE => get_term_meta($term_id, self::META_LAST_UPDATE, true) ?: '',
            self::KEY_TIMESTAMP => $timestamp,
            self::KEY_GENRES => self::get_game_genres($term_id),
            self::KEY_MODES => self::get_game_modes($term_id),
            self::KEY_PLATFORMS => self::get_game_platforms_grouped($term_id),
            self::KEY_IS_TEAM_CHOICE => self::is_team_choice($term_id),
            self::KEY_EXTERNAL_LINKS => get_term_meta($term_id, self::META_EXTERNAL_LINKS, true) ?: array(),
            self::KEY_TRAILER_LINK => get_term_meta($term_id, self::META_TRAILER_LINK, true) ?: '',
            self::KEY_SCREENSHOTS => get_term_meta($term_id, self::META_SCREENSHOTS, true) ?: array(),
            self::KEY_DEVELOPERS => get_term_meta($term_id, self::META_DEVELOPERS, true) ?: array(),
            self::KEY_PUBLISHERS => get_term_meta($term_id, self::META_PUBLISHERS, true) ?: array(),
            self::KEY_COVERS => array(
                self::KEY_COVER_MAIN => $cover_id,
                self::KEY_COVER_NEWS => get_term_meta($term_id, self::META_COVER_NEWS, true) ?: 0,
                self::KEY_COVER_PATCH => get_term_meta($term_id, self::META_COVER_PATCH, true) ?: 0,
                self::KEY_COVER_TEST => get_term_meta($term_id, self::META_COVER_TEST, true) ?: 0
            ),
            self::KEY_RELEASE_STATUS => self::get_game_release_status($term_id)
        );
    }

    /**
     * 📅 Déterminer le statut de sortie d'un jeu
     * 
     * @param int $term_id ID du jeu
     * @return array Statut complet avec is_released, release_date, days_diff, status_text
     */
    public static function get_game_release_status($term_id) {
        $release_date = get_term_meta($term_id, self::META_RELEASE_DATE, true);
        if (empty($release_date)) {
            return array(
                'is_released' => true,
                'release_date' => '',
                'days_diff' => 0,
                'status_text' => 'Date inconnue'
            );
        }
        $release_timestamp = strtotime($release_date);
        $current_timestamp = current_time('timestamp');
        $days_diff = floor(($current_timestamp - $release_timestamp) / DAY_IN_SECONDS);
        $is_released = $current_timestamp >= $release_timestamp;
        if ($is_released) {
            if ($days_diff === 0) {
                $status_text = 'Sorti aujourd\'hui';
            } elseif ($days_diff === 1) {
                $status_text = 'Sorti hier';
            } else {
                $status_text = "Sorti il y a {$days_diff} jours";
            }
        } else {
            $abs_days = abs($days_diff);
            if ($abs_days === 0) {
                $status_text = 'Sort aujourd\'hui';
            } elseif ($abs_days === 1) {
                $status_text = 'Sort demain';
            } else {
                $status_text = "Sort dans {$abs_days} jours";
            }
        }
        return array(
            'is_released' => $is_released,
            'release_date' => $release_date,
            'days_diff' => $days_diff,
            'status_text' => $status_text
        );
    }

    /**
     * 🏷️ Déterminer le badge d'un jeu selon sa fraîcheur
     * 
     * @param array $game_data Données complètes du jeu
     * @return array Badge avec class et text ou array vide
     */
    public static function get_game_badge($game_data) {
        $now = time();
        $release_time = strtotime($game_data['release_date']); 
        $last_update_time = strtotime($game_data['last_update']);
        $diff_days = floor(($now - $release_time) / 86400);
        if ($diff_days > 0 && $diff_days <= 7) {
            return array('class' => 'sisme-badge-new', 'text' => 'NOUVEAU');
        } elseif ($diff_days < 0) {
            return array('class' => 'sisme-badge-futur', 'text' => 'À VENIR');
        } elseif ($diff_days == 0) {
            return array('class' => 'sisme-badge-today', 'text' => 'AUJOURD\'HUI');
        }
        if ($last_update_time) {
            $update_diff = floor(($now - $last_update_time) / 86400);
            if ($update_diff <= 30) {
                return array('class' => 'sisme-badge-updated', 'text' => 'MIS À JOUR');
            }
        }
        return array('class' => 'sisme-display__none', 'text' => '');
    }

    /**
     * 📅 Trier les jeux par date de sortie
     * 
     * @param array $term_ids IDs des termes
     * @param string $order Ordre de tri : 'desc' (défaut) ou 'asc'
     * @return array IDs triés par date
     */
    public static function sort_games_by_release_date($term_ids, $order = 'desc') {
        $games_with_dates = array();
        foreach ($term_ids as $term_id) {
            $release_date = get_term_meta($term_id, self::META_RELEASE_DATE, true);
            if (!empty($release_date)) {
                $timestamp = strtotime($release_date);
            } else {
                $timestamp = ($order === 'desc') ? 0 : PHP_INT_MAX;
            }
            $games_with_dates[] = array(
                'term_id' => $term_id,
                'timestamp' => $timestamp,
                'release_date' => $release_date // Pour debug
            );
        }
        if ($order === 'asc') {
            usort($games_with_dates, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });
        } else {
            usort($games_with_dates, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }
        
        return array_column($games_with_dates, 'term_id');
    }

    /**
     * 🔍 Récupérer les IDs des jeux selon les critères
     * 
     * @param array $criteria Critères de recherche
     * @return array IDs des jeux trouvés
     */
    public static function get_games_by_criteria($criteria = array()) {
        $default_criteria = array(
            'genres' => array(),
            'search' => '', 
            'is_team_choice' => false,
            'sort_by_date' => true,
            'sort_order' => 'desc',
            'max_results' => -1,
            'released' => 0,
            'debug' => false
        );
        $criteria = array_merge($default_criteria, $criteria);
        if (!in_array($criteria['sort_order'], ['asc', 'desc'])) {
            $criteria['sort_order'] = 'desc';
        }
        if ($criteria['debug']) {
            error_log('[Sisme Utils Games] Critères reçus: ' . print_r($criteria, true));
        }
        
        // 1. Récupérer TOUS les jeux
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => self::META_DESCRIPTION,
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (is_wp_error($all_games) || empty($all_games)) {
            return array();
        }
        
        // 2. Filtrer par genres
        if (!empty($criteria['genres'])) {
            $genre_query = self::build_genres_meta_query($criteria['genres']);
            if (!empty($genre_query)) {
                $genre_filtered = get_terms(array(
                    'taxonomy' => 'post_tag',
                    'hide_empty' => false,
                    'fields' => 'ids',
                    'meta_query' => $genre_query
                ));
                $all_games = array_intersect($all_games, $genre_filtered);
            }
        }
        
        // 3. Filtrer par team choice
        if ($criteria[self::META_TEAM_CHOICE]) {
            $team_choice_games = array();
            foreach ($all_games as $game_id) {
                if (self::is_team_choice($game_id)) {
                    $team_choice_games[] = $game_id;
                }
            }
            $all_games = $team_choice_games;
        }
        
        // 4. ✅ NOUVEAU : Filtrer par statut de sortie AVANT le tri
        $filtered_games = array();
        foreach ($all_games as $game_id) {
            $should_include = true;
            
            // Filtrer par statut de sortie
            if ($criteria['released'] !== 0) {
                $release_status = self::get_game_release_status($game_id);
                
                if ($criteria['released'] === 1 && !$release_status['is_released']) {
                    $should_include = false;
                } elseif ($criteria['released'] === -1 && $release_status['is_released']) {
                    $should_include = false;
                }
            }
            
            // Vérifier que le jeu existe
            if ($should_include) {
                $game_data = self::get_game_data($game_id);
                if ($game_data) {
                    $filtered_games[] = $game_id;
                }
            }
        }
        
        if ($criteria['debug']) {
            error_log('[Sisme Utils Games] ' . count($filtered_games) . ' jeux après filtrage');
        } 
        
        // 5. Trier par date APRÈS le filtrage
        if ($criteria['sort_by_date']) {
            $filtered_games = self::sort_games_by_release_date($filtered_games, $criteria['sort_order']);
            
            if ($criteria['debug']) {
                error_log('[Sisme Utils Games] Tri ' . $criteria['sort_order'] . ' appliqué');
                // Debug des premières dates
                for ($i = 0; $i < min(5, count($filtered_games)); $i++) {
                    $game_id = $filtered_games[$i];
                    $release_date = get_term_meta($game_id, self::META_RELEASE_DATE, true);
                    $game_data = self::get_game_data($game_id);
                    $name = $game_data ? $game_data[self::KEY_NAME] : "Jeu $game_id";
                    error_log("  - Position $i: $name ($release_date)");
                }
            }
        }
        
        // 6. Limiter le nombre de résultats
        if ($criteria['max_results'] > 0) {
            $filtered_games = array_slice($filtered_games, 0, $criteria['max_results']);
        }
        
        // 7. Filtrer par recherche textuelle (si nécessaire)
        if (!empty($criteria['search'])) {
            $search_terms = get_terms(array(
                'taxonomy' => 'post_tag',
                'search' => $criteria['search'],
                'meta_query' => array(
                    array(
                        'key' => self::META_DESCRIPTION,
                        'compare' => 'EXISTS'
                    )
                )
            ));
            if (!empty($search_terms)) {
                $search_ids = wp_list_pluck($search_terms, 'term_id');
                $filtered_games = array_intersect($filtered_games, $search_ids);
            } else {
                return array(); // Aucun jeu trouvé
            }
        }
        
        return $filtered_games;
    }
    
    /**
     * 🎨 Convertir une liste de genres en meta_query
     * 
     * @param array $genres Liste des genres
     * @return array Meta query pour WordPress
     */
    private static function build_genres_meta_query($genres) {
        $genre_ids = array();
        foreach ($genres as $genre) {
            $genre = trim($genre);
            if (empty($genre)) {
                continue;
            }
            if (is_numeric($genre)) {
                $genre_ids[] = intval($genre);
                continue;
            }
            $category = get_category_by_slug($genre);
            if (!$category) {
                $category = get_category_by_slug('jeux-' . strtolower($genre));
            }
            if (!$category) {
                $category = get_term_by('name', $genre, 'category');
            }
            if (!$category) {
                $category = get_term_by('name', 'jeux-' . ucfirst(strtolower($genre)), 'category');
            }
            if ($category) {
                $genre_ids[] = $category->term_id;
            }
        }
        if (empty($genre_ids)) {
            return array();
        }
        $genre_query = array('relation' => 'OR');
        foreach ($genre_ids as $genre_id) {
            $genre_query[] = array(
                'key' => self::META_GENRES,
                'value' => sprintf('"%d"', $genre_id),
                'compare' => 'LIKE'
            );
            $genre_query[] = array(
                'key' => self::META_GENRES,
                'value' => sprintf('i:%d;', $genre_id),
                'compare' => 'LIKE'
            );
        }
        return $genre_query;
    }

    /**
     * 🏆 Vérifier si un jeu est un choix de l'équipe
     * Migration depuis: Sisme_Team_Choice_Functions::is_team_choice()
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return bool True si c'est un choix de l'équipe
     */
    public static function is_team_choice($term_id) {
        if (empty($term_id) || !is_numeric($term_id)) {
            return false;
        }
        $value = get_term_meta($term_id, self::META_TEAM_CHOICE, true);
        return $value === '1';
    }

    /**
     * 🏆 Obtenir tous les jeux marqués comme choix de l'équipe
     * Migration depuis: Sisme_Team_Choice_Functions::get_team_choice_games()
     * 
     * @param array $args Arguments supplémentaires pour get_terms
     * @return array Liste des jeux choix de l'équipe
     */
    public static function get_team_choice_games($args = array()) {
        $default_args = array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => self::META_TEAM_CHOICE,
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => self::META_DESCRIPTION,
                    'compare' => 'EXISTS'
                )
            )
        );
        $args = wp_parse_args($args, $default_args);
        return get_terms($args);
    }

    /**
     * 🏆 Obtenir le nombre de jeux choix de l'équipe
     * Migration depuis: Sisme_Team_Choice_Functions::count_team_choice_games()
     * 
     * @return int Nombre de jeux
     */
    public static function count_team_choice_games() {
        $games = self::get_team_choice_games(array('fields' => 'ids'));
        return count($games);
    }
}