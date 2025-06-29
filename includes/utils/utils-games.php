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
     * Migration depuis: Sisme_Cards_Functions::get_game_genres()
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
     * Migration depuis: Sisme_Cards_Functions::get_game_modes()
     * 
     * @param int $term_id ID du jeu (term_id)
     * @return array Modes formatés avec key et label
     */
    public static function get_game_modes($term_id) {
        $modes = get_term_meta($term_id, self::META_MODES, true) ?: array();
        
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
        
        // Définition des groupes avec icônes depuis Utils_Formatting
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
}

// TODO: Prochaines fonctions à migrer dans l'ordre
// - get_game_data() (complet avec dépendances)
// - get_games_by_criteria()
// - get_game_release_status()
// - get_game_badge()
// - sort_games_by_release_date()