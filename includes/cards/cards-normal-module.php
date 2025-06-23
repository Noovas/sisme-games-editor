<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-normal-module.php
 * Module spécialisé pour les cartes de type "Normal"
 * 
 * RESPONSABILITÉ:
 * - Rendu HTML des cartes normales uniquement
 * - Style "Dernières Découvertes"
 * - Gestion des options d'affichage
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Normal_Module {
    
    /**
     * Options par défaut pour les cartes normales
     */
    private static $default_options = array(
        'show_description' => true,
        'show_genres' => true,
        'show_platforms' => false,
        'show_date' => true,
        'css_class' => '',
        'max_genres' => 3,          
        'max_modes' => 4           
    );
    
    /**
     * Rendre une carte normale
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
            array('normal'),
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
     * 🖼️ Bloc image avec badge
     */
    private static function render_image_block($game_data) {
        $output = '<div class="sisme-card-image" style="background-image: url(\'' . esc_url($game_data['cover_url']) . '\')">';
        
        // Badge selon la fraîcheur
        $badge = Sisme_Cards_Functions::get_game_badge($game_data);
        if ($badge) {
            $output .= '<span class="sisme-card-badge ' . $badge['class'] . '">' . $badge['text'] . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📝 Bloc contenu principal
     */
    private static function render_content_block($game_data, $options) {
        $output = '<div class="sisme-card-content">';
        
        // Titre cliquable
        $output .= self::render_title($game_data);
        
        // Description
        if ($options['show_description'] && !empty($game_data['description'])) {
            $output .= self::render_description($game_data);
        }
        
        // Genres
        if ($options['show_genres'] && !empty($game_data['genres']) && $options['max_genres'] !== 0) {
            $output .= self::render_genres($game_data, $options);
        }

        // Modes
        if (!empty($game_data['modes']) && $options['max_modes'] !== 0) {
            $output .= self::render_modes($game_data, $options);
        }
        
        // Plateformes
        if ($options['show_platforms'] && !empty($game_data['platforms'])) {
            $output .= self::render_platforms_grouped($game_data);
        }
        
        // Meta footer
        $output .= self::render_meta_footer($game_data, $options);
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📌 Titre cliquable
     */
    private static function render_title($game_data) {
        $output = '<h3 class="sisme-card-title">';
        $output .= '<a href="' . esc_url($game_data['game_url']) . '" ';
        $output .= 'title="Découvrir ' . esc_attr($game_data['name']) . '">';
        $output .= esc_html($game_data['name']);
        $output .= '</a>';
        $output .= '</h3>';
        
        return $output;
    }
    
    /**
     * 📝 Description tronquée
     */
    private static function render_description($game_data) {
        $short_description = Sisme_Cards_Functions::truncate_smart($game_data['description'], 120);
        
        return '<p class="sisme-card-description">' . esc_html($short_description) . '</p>';
    }
    
    /**
     * 🏷️ Tags des genres
     */
    private static function render_genres($game_data) {
        $output = '<div class="sisme-card-genres">';
        $max_genres = isset($options['max_genres']) ? intval($options['max_genres']) : 3;
        if ($max_genres == 0) {
            return '';
        }
        $genres_to_show = ($max_genres == -1) ? 
            $game_data['genres'] :                             
            array_slice($game_data['genres'], 0, $max_genres);  
        
        foreach ($genres_to_show as $genre) {
            $output .= '<span class="sisme-badge sisme-badge-genre">' . esc_html($genre['name']) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * 🎯 Modes de jeu
     */
    private static function render_modes($game_data, $options = array()) {
        $output = '<div class="sisme-card-modes">';
        $max_modes = isset($options['max_modes']) ? intval($options['max_modes']) : 4;
        if ($max_modes == 0) {
            return '';
        }
        
        $modes_to_show = ($max_modes == -1) ? 
            $game_data['modes'] :                          
            array_slice($game_data['modes'], 0, $max_modes);
        
        foreach ($modes_to_show as $mode) {
            $output .= '<span class="sisme-badge sisme-badge-mode">' . esc_html($mode['label']) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🎮 Icônes des plateformes
     */
    private static function render_platforms_grouped($game_data) {
        $output = '<div class="sisme-card-platforms">';

        foreach ($game_data['platforms'] as $platform_group) {
            $output .= '<span class="sisme-badge-platform" ';
            $output .= 'title="' . esc_attr($platform_group['tooltip']) . '">';
            $output .= $platform_group['icon'];
            $output .= '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 📊 Footer avec métadonnées
     */
    private static function render_meta_footer($game_data, $options) {
        $output = '<div class="sisme-card-meta">';
        
        // Date relative (optionnelle)
        if ($options['show_date']) {
            $formatted_date = Sisme_Cards_Functions::format_relative_date($game_data['timestamp']);
            $output .= '<span class="sisme-card-date">' . esc_html($formatted_date) . '</span>';
        }
        
        // Lien d'action
        $output .= '<a href="' . esc_url($game_data['game_url']) . '" class="sisme-card-link">Découvrir →</a>';
        
        $output .= '</div>';
        
        return $output;
    }
}

?>