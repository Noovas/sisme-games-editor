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
    
    /**
     * Constantes pour les collections
     */
    const COLLECTION_FAVORITE = 'favorite';
    const COLLECTION_OWNED = 'owned';
    
    /**
     * Meta keys de base
     */
    const META_DESCRIPTION = 'game_description';
    const META_COVER_MAIN = 'cover_main';
    const META_RELEASE_DATE = 'release_date';
    const META_LAST_UPDATE = 'last_update';
    const META_PLATFORMS = 'game_platforms';
    const META_GENRES = 'game_genres';
    const META_MODES = 'game_modes';
    
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
     * Migration depuis: Sisme_Cards_Functions::get_game_platforms_grouped()
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
     * @return array|false Données complètes du jeu ou false si incomplet
     */
    public static function get_game_data($term_id) {
        // Validation de base
        if (empty($term_id) || !is_numeric($term_id)) {
            return false;
        }
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return false;
        }
        $description = get_term_meta($term_id, self::META_DESCRIPTION, true);
        $cover_id = get_term_meta($term_id, self::META_COVER_MAIN, true);
        
        if (empty($description) || empty($cover_id)) {
            return false;
        }
        $cover_url = wp_get_attachment_image_url($cover_id, 'full');
        if (!$cover_url) {
            return false;
        }
        $release_date = get_term_meta($term_id, self::META_RELEASE_DATE, true);
        $timestamp = !empty($release_date) ? strtotime($release_date) : 0;
        $game_data = array(
            'term_id' => $term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => wp_strip_all_tags($description),
            'cover_url' => $cover_url,
            'game_url' => home_url($term->slug . '/'),
            'release_date' => $release_date ?: '',
            'last_update' => get_term_meta($term_id, self::META_LAST_UPDATE, true) ?: '',
            'timestamp' => $timestamp,
            'genres' => array(),
            'modes' => array(),
            'platforms' => array()
        );
        return $game_data;
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
        if ($criteria['is_team_choice']) {
            $team_choice_games = array();
            foreach ($all_games as $game_id) {
                $is_team_choice = get_term_meta($game_id, 'is_team_choice', true);
                if ($is_team_choice) {
                    $team_choice_games[] = $game_id;
                }
            }
            $all_games = $team_choice_games;
        }
        $filtered_games = array();
        foreach ($all_games as $game_id) {
            $should_include = true;
            if ($criteria['released'] !== 0) {
                $release_status = self::get_game_release_status($game_id);
                
                if ($criteria['released'] === 1 && !$release_status['is_released']) {
                    $should_include = false;
                } elseif ($criteria['released'] === -1 && $release_status['is_released']) {
                    $should_include = false;
                }
            }
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
        if ($criteria['sort_by_date']) {
            $filtered_games = self::sort_games_by_release_date($filtered_games, $criteria['sort_order']);
        }
        if ($criteria['max_results'] > 0) {
            $filtered_games = array_slice($filtered_games, 0, $criteria['max_results']);
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
}