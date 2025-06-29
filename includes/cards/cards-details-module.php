<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-details-module.php
 * Module spécialisé pour les cartes de type "Details"
 * 
 * RESPONSABILITÉ:
 * - Rendu HTML des cartes details uniquement
 * - Layout horizontal pour mode liste
 * - Description complète non tronquée
 * - Badge positionné dans le footer avec la date
 * - Plateformes groupées par famille
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Details_Module {
    
    /**
     * Options par défaut pour les cartes details
     */
    private static $default_options = array(
        'show_description' => true,
        'show_genres' => true,
        'show_platforms' => true,      // Par défaut activé pour details
        'show_date' => true,
        'css_class' => '',
        'max_genres' => -1,            // Tous les genres en mode details
        'max_modes' => -1,             // Tous les modes en mode details
        'date_format' => 'short'
    );
    
    /**
     * Rendre une carte details
     * 
     * @param array $game_data Données du jeu (depuis cards-functions.php)
     * @param array $options Options d'affichage
     * @return string HTML de la carte
     */
    public static function render($game_data, $options = array()) {
        
        // Fusionner avec les options par défaut
        $options = array_merge(self::$default_options, $options);
        
        // Construire les classes CSS
        $css_class = Sisme_Cards_Functions::build_css_class(
            'sisme-game-card',
            array('details'),
            $options['css_class']
        );
        
        // Commencer le rendu
        $output = '<article class="' . $css_class . '" data-game-id="' . $game_data['term_id'] . '">';
        
        // Déléguer le rendu par blocs
        $output .= self::render_image_block($game_data);
        $output .= self::render_content_block($game_data, $options);
        
        $output .= '</article>';
        
        return $output;
    }
    
    /**
     * 🖼️ Bloc image sans badge (badge maintenant dans footer)
     */
    private static function render_image_block($game_data) {

        $cover_vertical_id = get_term_meta($game_data['term_id'], 'cover_vertical', true);
    
        if ($cover_vertical_id) {
            $cover_url = wp_get_attachment_image_url($cover_vertical_id, 'full');
            $cover_url = $cover_url ?: $game_data['cover_url']; // Fallback sur cover_main
        } else {
            $cover_url = $game_data['cover_url']; // Fallback sur cover_main
        }
        
        $output = '<div class="sisme-card-image--details" style="background-image: url(\'' . esc_url($cover_url) . '\')"></div>';
        
        return $output;
    }
    
    /**
     * 📝 Bloc contenu principal - Layout details
     */
    private static function render_content_block($game_data, $options) {
        $output = '<div class="sisme-card-content--details">';
        
        // Header avec titre
        $output .= '<div class="sisme-card-header--details">';
        $output .= self::render_title($game_data);
        $output .= '</div>';
        
        // Description complète
        if ($options['show_description'] && !empty($game_data['description'])) {
            $output .= self::render_description($game_data);
        }
        
        // Tags sur 2 lignes séparées
        $output .= '<div class="sisme-card-tags--details">';
        
        // Ligne 1 : Genres
        if ($options['show_genres'] && !empty($game_data['genres']) && $options['max_genres'] !== 0) {
            $output .= self::render_genres($game_data, $options);
        }
        
        // Ligne 2 : Modes
        if (!empty($game_data['modes']) && $options['max_modes'] !== 0) {
            $output .= self::render_modes($game_data, $options);
        }
        
        $output .= '</div>';
        
        // Footer avec badge + date + plateformes
        $output .= self::render_footer($game_data, $options);
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📌 Titre cliquable - Réutilise la logique du module normal
     */
    private static function render_title($game_data) {
        $output = '<h3 class="sisme-card-title--details">';
        $output .= '<a href="' . esc_url($game_data['game_url']) . '" ';
        $output .= 'title="Découvrir ' . esc_attr($game_data['name']) . '">';
        $output .= esc_html($game_data['name']);
        $output .= '</a>';
        $output .= '</h3>';
        
        return $output;
    }
    
    /**
     * 📝 Description complète
     */
    private static function render_description($game_data) {
        // Mode details : description plus longue (250 caractères au lieu de 90)
        $long_description = Sisme_Utils_Formatting::truncate_smart($game_data['description'], 500);
        
        return '<div class="sisme-card-description--details">' . esc_html($long_description) . '</div>';
    }
    
    /**
     * 🏷️ Genres
     */
    private static function render_genres($game_data, $options) {
        $output = '<div class="sisme-card-genres--details">';
        
        $max_genres = isset($options['max_genres']) ? intval($options['max_genres']) : -1;
        
        if ($max_genres == 0) {
            return '';
        }
        
        $genres_to_show = ($max_genres == -1) ? 
            $game_data['genres'] :                             
            array_slice($game_data['genres'], 0, $max_genres);  
        
        foreach ($genres_to_show as $genre) {
            $output .= '<span class="sisme-badge-genre sisme-badge">' . esc_html($genre['name']) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🎯 Modes de jeu
     */
    private static function render_modes($game_data, $options) {
        $output = '<div class="sisme-card-modes--details">';
        
        $max_modes = isset($options['max_modes']) ? intval($options['max_modes']) : -1;
        
        if ($max_modes == 0) {
            return '';
        }
        
        $modes_to_show = ($max_modes == -1) ? 
            $game_data['modes'] :                          
            array_slice($game_data['modes'], 0, $max_modes);
        
        foreach ($modes_to_show as $mode) {
            $output .= '<span class="sisme-badge-mode sisme-badge">' . esc_html($mode['label']) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📊 Footer avec badge + date + plateformes - Spécifique au mode details
     */
    private static function render_footer($game_data, $options) {
        $output = '<div class="sisme-card-footer--details">';
        
        // Partie gauche : Badge + Date
        $output .= '<div class="sisme-card-date-badge--details">';
        
        // Badge selon la fraîcheur (réutilise la logique existante)
        $badge = Sisme_Cards_Functions::get_game_badge($game_data);
        if ($badge) {
            $output .= '<span class="sisme-card-badge--footer sisme-badge ' . $badge['class'] . '">' . $badge['text'] . '</span>';
        }
        
        // Date de sortie
        if ($options['show_date'] && !empty($game_data['release_date'])) {
            $output .= self::render_date($game_data, $options);
        }
        
        $output .= '</div>';
        
        // Partie droite : Plateformes groupées
        if ($options['show_platforms'] && !empty($game_data['platforms'])) {
            $output .= self::render_platforms_grouped($game_data);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📅 Date formatée avec icône
     */
    private static function render_date($game_data, $options) {
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'short';
        
        if ($date_format === 'long') {
            $formatted_date = Sisme_Cards_Functions::format_release_date_with_status($game_data['release_date'], true);
        } else {
            $formatted_date = Sisme_Cards_Functions::format_release_date($game_data['release_date']);
        }
        
        $output = '<div class="sisme-card-date--details">';
        $output .= '📅 ' . esc_html($formatted_date);
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🎮 Plateformes groupées par famille - Spécifique au mode details
     */
    private static function render_platforms_grouped($game_data) {
        $output = '<div class="sisme-card-platforms--details">';
        
        // Grouper les plateformes par famille
        $platform_families = self::group_platforms_by_family($game_data['platforms']);
        
        foreach ($platform_families as $family_key => $family_data) {
            $output .= '<div class="sisme-platform-group" title="' . esc_attr($family_data['tooltip']) . '">';
            $output .= '<span class="sisme-platform-group-label">' . esc_html($family_data['label']) . '</span>';
            $output .= '<span class="sisme-platform-icon">' . $family_data['icon'] . '</span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🏗️ Grouper les plateformes par famille (PC, Console, Mobile)
     * 
     * @param array $platforms Plateformes du jeu
     * @return array Plateformes groupées par famille
     */
    private static function group_platforms_by_family($platforms) {
        $families = array();
        
        // Mapping des plateformes vers leurs familles
        $family_mapping = array(
            // PC
            'windows' => 'pc',
            'mac' => 'pc', 
            'linux' => 'pc',
            'pc' => 'pc',
            
            // Console
            'playstation' => 'console',
            'xbox' => 'console',
            'nintendo-switch' => 'console',
            'ps4' => 'console',
            'ps5' => 'console',
            'xbox-one' => 'console',
            'xbox-series' => 'console',
            'switch' => 'console',
            
            // Mobile
            'ios' => 'mobile',
            'android' => 'mobile',
            'mobile' => 'mobile'
        );
        
        $family_configs = array(
            'pc' => array(
                'label' => 'PC',
                'icon' => '🖥️',
                'tooltip' => 'Windows, macOS, Linux'
            ),
            'console' => array(
                'label' => 'Console', 
                'icon' => '🎮',
                'tooltip' => 'PlayStation, Xbox, Nintendo Switch'
            ),
            'mobile' => array(
                'label' => 'Mobile',
                'icon' => '📱', 
                'tooltip' => 'iOS, Android'
            )
        );
        
        // Identifier les familles présentes
        $present_families = array();
        
        foreach ($platforms as $platform) {
            $platform_key = is_array($platform) ? $platform['key'] : $platform;
            
            if (isset($family_mapping[$platform_key])) {
                $family = $family_mapping[$platform_key];
                $present_families[$family] = true;
            }
        }
        
        // Construire la liste des familles avec leur config
        foreach ($present_families as $family_key => $present) {
            if (isset($family_configs[$family_key])) {
                $families[$family_key] = $family_configs[$family_key];
            }
        }
        
        return $families;
    }
}