<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-data-formatter.php
 * Formatage des données de jeu depuis term_meta
 * 
 * RESPONSABILITÉ:
 * - Récupération des données depuis WordPress term_meta
 * - Formatage pour l'affichage (dates, plateformes, genres, etc.)
 * - Transformation des IDs en données complètes
 * - Remplace les fonctions Utils pour ce module
 * 
 * DÉPENDANCES:
 * - game-data-creator-constants.php (pour les meta keys)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-data-creator/game-data-creator-constants.php';

class Sisme_Game_Data_Formatter {
    
    /**
     * Formater toutes les données d'un jeu pour affichage
     * 
     * @param int $term_id ID du terme jeu
     * @return array|false Données formatées ou false si erreur
     */
    public static function format_game_data($term_id) {
        $term = get_term($term_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            return false;
        }
        
        $formatted_data = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'title' => $term->name,
            'slug' => $term->slug,
            'game_url' => get_term_link($term),
            'description' => self::get_description($term_id),
            'release_date' => self::format_release_date($term_id),
            'platforms' => self::format_platforms($term_id),
            'genres' => self::format_genres($term_id),
            'modes' => self::format_modes($term_id),
            'developers' => self::format_developers($term_id),
            'publishers' => self::format_publishers($term_id),
            'trailer_link' => self::get_trailer_link($term_id),
            'screenshots' => self::format_screenshots($term_id),
            'external_links' => self::format_external_links($term_id),
            'sections' => self::format_sections($term_id),
            'covers' => self::format_covers($term_id)
        );
        
        return $formatted_data;
    }
    
    /**
     * Récupérer la description du jeu
     * 
     * @param int $term_id ID du terme
     * @return string Description formatée
     */
    private static function get_description($term_id) {
        $description = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_DESCRIPTION, true);
        return $description ? wp_strip_all_tags($description) : '';
    }
    
    /**
     * Formater la date de sortie
     * 
     * @param int $term_id ID du terme
     * @return string Date formatée en français
     */
    private static function format_release_date($term_id) {
        $date = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_RELEASE_DATE, true);
        if (!$date) {
            return 'Date inconnue';
        }
        
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return 'Date inconnue';
        }
        
        return date_i18n('j F Y', $timestamp);
    }
    
    /**
     * Formater les plateformes avec icônes
     * 
     * @param int $term_id ID du terme
     * @return array Plateformes formatées avec icônes et tooltips
     */
    private static function format_platforms($term_id) {
        $platforms = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_PLATFORMS, true);
        if (!is_array($platforms)) {
            return array();
        }
        
        $formatted = array();
        $icons_map = array(
            'windows' => '🖥️',
            'mac' => '🖥️',
            'macos' => '🖥️',
            'linux' => '🖥️',
            'xbox' => '🎮',
            'playstation' => '🎮',
            'switch' => '🎮',
            'ios' => '📱',
            'android' => '📱',
            'web' => '🌐'
        );
        
        foreach ($platforms as $platform) {
            $label = Sisme_Game_Creator_Constants::get_platform_label($platform);
            $formatted[] = array(
                'key' => $platform,
                'label' => $label,
                'icon' => $icons_map[$platform] ?? '🎮',
                'tooltip' => "Disponible sur {$label}"
            );
        }
        
        return $formatted;
    }
    
    /**
     * Formater les genres avec liens
     * 
     * @param int $term_id ID du terme
     * @return array Genres avec IDs, noms et URLs
     */
    private static function format_genres($term_id) {
        $genre_ids = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_GENRES, true);
        if (!is_array($genre_ids)) {
            return array();
        }
        
        $formatted = array();
        foreach ($genre_ids as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre && !is_wp_error($genre)) {
                $formatted[] = array(
                    'id' => $genre->term_id,
                    'name' => str_replace('jeux-', '', $genre->name),
                    'slug' => $genre->slug,
                    'url' => get_category_link($genre_id)
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formater les modes de jeu
     * 
     * @param int $term_id ID du terme
     * @return array Modes formatés
     */
    private static function format_modes($term_id) {
        $modes = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_MODES, true);
        if (!is_array($modes)) {
            return array();
        }
        
        $modes_labels = array(
            0 => 'Solo',
            1 => 'Multijoueur',
            2 => 'Coopératif',
            3 => 'Compétitif'
        );
        
        $formatted = array();
        foreach ($modes as $mode_key) {
            if (isset($modes_labels[$mode_key])) {
                $formatted[] = array(
                    'key' => $mode_key,
                    'label' => $modes_labels[$mode_key]
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formater les développeurs
     * 
     * @param int $term_id ID du terme
     * @return array Développeurs avec noms et sites web
     */
    private static function format_developers($term_id) {
        $dev_ids = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_DEVELOPERS, true);
        if (!is_array($dev_ids)) {
            return array();
        }
        
        return self::format_entities($dev_ids);
    }
    
    /**
     * Formater les éditeurs
     * 
     * @param int $term_id ID du terme
     * @return array Éditeurs avec noms et sites web
     */
    private static function format_publishers($term_id) {
        $pub_ids = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_PUBLISHERS, true);
        if (!is_array($pub_ids)) {
            return array();
        }
        
        return self::format_entities($pub_ids);
    }
    
    /**
     * Formater les entités (développeurs/éditeurs)
     * 
     * @param array $entity_ids IDs des entités
     * @return array Entités formatées
     */
    private static function format_entities($entity_ids) {
        $formatted = array();
        
        foreach ($entity_ids as $entity_id) {
            $entity = get_category($entity_id);
            if ($entity && !is_wp_error($entity)) {
                $website = get_term_meta($entity_id, Sisme_Game_Creator_Constants::META_ENTITY_WEBSITE, true);
                
                $formatted[] = array(
                    'id' => $entity->term_id,
                    'name' => $entity->name,
                    'website' => $website ?: ''
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Récupérer le lien trailer
     * 
     * @param int $term_id ID du terme
     * @return string URL du trailer
     */
    private static function get_trailer_link($term_id) {
        return get_term_meta($term_id, Sisme_Game_Creator_Constants::META_TRAILER_LINK, true) ?: '';
    }
    
    /**
     * Formater les screenshots
     * 
     * @param int $term_id ID du terme
     * @return array Screenshots avec URLs et métadonnées
     */
    private static function format_screenshots($term_id) {
        $screenshot_ids = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_SCREENSHOTS, true);
        if (!is_array($screenshot_ids)) {
            return array();
        }
        
        $formatted = array();
        foreach ($screenshot_ids as $attachment_id) {
            $url = wp_get_attachment_url($attachment_id);
            $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            
            if ($url) {
                $formatted[] = array(
                    'id' => $attachment_id,
                    'url' => $url,
                    'thumbnail' => $thumb_url ?: $url,
                    'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: 'Screenshot'
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formater les liens externes
     * 
     * @param int $term_id ID du terme
     * @return array Liens externes formatés
     */
    private static function format_external_links($term_id) {
        $links = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_EXTERNAL_LINKS, true);
        if (!is_array($links)) {
            return array();
        }
        
        $formatted = array();
        $platforms_config = array(
            'steam' => array(
                'label' => 'Steam',
                'icon' => 'https://games.sisme.fr/wp-content/uploads/2025/06/Logo-STEAM.webp'
            ),
            'epic' => array(
                'label' => 'Epic Games',
                'icon' => 'https://games.sisme.fr/wp-content/uploads/2025/06/Logo-EPIC.webp'
            ),
            'gog' => array(
                'label' => 'GOG',
                'icon' => 'https://games.sisme.fr/wp-content/uploads/2025/06/Logo-GOG.webp'
            )
        );
        
        foreach ($platforms_config as $platform => $config) {
            $formatted[$platform] = array(
                'platform' => $platform,
                'label' => $config['label'],
                'url' => $links[$platform] ?? '',
                'icon' => $config['icon'],
                'available' => !empty($links[$platform])
            );
        }
        
        return $formatted;
    }
    
    /**
     * Formater les sections personnalisées
     * 
     * @param int $term_id ID du terme
     * @return array Sections formatées
     */
    private static function format_sections($term_id) {
        $sections = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_SECTIONS, true);
        if (!is_array($sections)) {
            return array();
        }
        
        $formatted = array();
        foreach ($sections as $section) {
            if (!empty($section['title']) && !empty($section['content'])) {
                $image_url = '';
                if (!empty($section['image_id']) && is_numeric($section['image_id'])) {
                    $image_url = wp_get_attachment_url($section['image_id']) ?: '';
                }
                
                $formatted[] = array(
                    'title' => sanitize_text_field($section['title']),
                    'content' => wpautop($section['content']),
                    'image_id' => $section['image_id'] ?? '',
                    'image_url' => $image_url
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formater les covers
     * 
     * @param int $term_id ID du terme
     * @return array URLs des covers
     */
    private static function format_covers($term_id) {
        $covers = array();
        
        $cover_main_id = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_COVER_MAIN, true);
        if ($cover_main_id) {
            $covers['main'] = wp_get_attachment_url($cover_main_id) ?: '';
        }
        
        $cover_vertical_id = get_term_meta($term_id, 'cover_vertical', true);
        if ($cover_vertical_id) {
            $covers['vertical'] = wp_get_attachment_url($cover_vertical_id) ?: '';
        }
        
        return $covers;
    }
}