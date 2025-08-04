<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-sections-builder.php
 * Constructeur de sections personnalisées pour les pages de jeu
 * 
 * RESPONSABILITÉ:
 * - Construction du HTML des sections personnalisées
 * - Validation des sections
 * - Formatage du contenu (wpautop, sanitization)
 * - Gestion des images de sections
 * 
 * DÉPENDANCES:
 * - game-media-handler.php (pour les images)
 * - game-data-creator-constants.php (pour validation)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-media-handler.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-data-creator/game-data-creator-constants.php';

class Sisme_Game_Sections_Builder {
    
    /**
     * Construire le HTML complet des sections
     * 
     * @param array $sections Sections du jeu
     * @return string HTML des sections ou chaîne vide
     */
    public static function build_sections_html($sections) {
        if (empty($sections) || !is_array($sections)) {
            return '';
        }
        
        $valid_sections = self::filter_valid_sections($sections);
        if (empty($valid_sections)) {
            return '';
        }
        
        $output = '<div class="sisme-game-sections">';
        $output .= '<h2>Présentation complète du jeu</h2>';
        
        foreach ($valid_sections as $section) {
            $output .= self::build_single_section_html($section);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Construire le HTML d'une section individuelle
     * 
     * @param array $section Données de la section
     * @return string HTML de la section
     */
    public static function build_single_section_html($section) {
        $output = '<div class="sisme-game-section">';
        
        // Titre de la section
        if (!empty($section['title'])) {
            $output .= '<h3>' . esc_html($section['title']) . '</h3>';
        }
        
        // Contenu de la section
        if (!empty($section['content'])) {
            $formatted_content = self::format_section_content($section['content']);
            $output .= $formatted_content;
        }
        
        // Image de la section
        if (!empty($section['image_id']) && is_numeric($section['image_id'])) {
            $image_html = self::build_section_image_html($section['image_id']);
            if ($image_html) {
                $output .= $image_html;
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Construire le HTML de l'image d'une section
     * 
     * @param int $image_id ID de l'attachment
     * @return string|false HTML de l'image ou false si erreur
     */
    private static function build_section_image_html($image_id) {
        $image_data = Sisme_Game_Media_Handler::process_screenshot($image_id);
        if (!$image_data) {
            return false;
        }
        
        $output = '<div class="sisme-game-section-image">';
        $output .= '<img class="sisme-section-image" ';
        $output .= 'src="' . esc_attr($image_data['url']) . '" ';
        $output .= 'alt="' . esc_attr($image_data['alt']) . '">';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Filtrer les sections valides
     * 
     * @param array $sections Liste des sections
     * @return array Sections valides uniquement
     */
    private static function filter_valid_sections($sections) {
        $valid_sections = array();
        
        foreach ($sections as $section) {
            if (self::is_valid_section($section)) {
                $valid_sections[] = $section;
            }
        }
        
        return $valid_sections;
    }
    
    /**
     * Valider une section
     * 
     * @param array $section Données de la section
     * @return bool Section valide
     */
    public static function is_valid_section($section) {
        if (!is_array($section)) {
            return false;
        }
        
        $title = $section['title'] ?? '';
        $content = $section['content'] ?? '';
        
        // Au minimum titre et contenu requis
        if (empty($title) || empty($content)) {
            return false;
        }
        
        // Vérification des longueurs avec les constantes
        if (strlen($title) > Sisme_Game_Creator_Constants::MAX_SECTION_TITLE_LENGTH) {
            return false;
        }
        
        if (strlen($content) < Sisme_Game_Creator_Constants::MIN_SECTION_CONTENT_LENGTH) {
            return false;
        }
        
        if (strlen($content) > Sisme_Game_Creator_Constants::MAX_SECTION_CONTENT_LENGTH) {
            return false;
        }
        
        // Vérification de l'image si présente
        if (!empty($section['image_id'])) {
            $image_id = intval($section['image_id']);
            if ($image_id > 0 && !Sisme_Game_Media_Handler::is_valid_image_attachment($image_id)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Formater le contenu d'une section
     * 
     * @param string $content Contenu brut
     * @return string Contenu formaté
     */
    private static function format_section_content($content) {
        if (empty($content)) {
            return '';
        }
        
        // Nettoyer le contenu
        $content = wp_kses_post($content);
        
        // Appliquer wpautop pour les paragraphes automatiques
        $content = wpautop($content);
        
        // Nettoyer les paragraphes vides
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        return $content;
    }
    
    /**
     * Valider et nettoyer les données d'une section
     * 
     * @param array $section Données brutes de la section
     * @return array|false Section nettoyée ou false si invalide
     */
    public static function sanitize_section($section) {
        if (!is_array($section)) {
            return false;
        }
        
        $cleaned = array(
            'title' => '',
            'content' => '',
            'image_id' => ''
        );
        
        // Nettoyer le titre
        if (!empty($section['title'])) {
            $cleaned['title'] = sanitize_text_field($section['title']);
            
            // Vérifier la longueur
            if (strlen($cleaned['title']) > Sisme_Game_Creator_Constants::MAX_SECTION_TITLE_LENGTH) {
                $cleaned['title'] = substr($cleaned['title'], 0, Sisme_Game_Creator_Constants::MAX_SECTION_TITLE_LENGTH);
            }
        }
        
        // Nettoyer le contenu
        if (!empty($section['content'])) {
            $cleaned['content'] = wp_kses_post($section['content']);
            
            // Vérifier la longueur
            if (strlen($cleaned['content']) > Sisme_Game_Creator_Constants::MAX_SECTION_CONTENT_LENGTH) {
                $cleaned['content'] = substr($cleaned['content'], 0, Sisme_Game_Creator_Constants::MAX_SECTION_CONTENT_LENGTH);
            }
        }
        
        // Nettoyer l'ID image
        if (!empty($section['image_id'])) {
            $image_id = intval($section['image_id']);
            if ($image_id > 0 && Sisme_Game_Media_Handler::is_valid_image_attachment($image_id)) {
                $cleaned['image_id'] = $image_id;
            }
        }
        
        // Vérifier si la section nettoyée est valide
        if (!self::is_valid_section($cleaned)) {
            return false;
        }
        
        return $cleaned;
    }
    
    /**
     * Obtenir la structure par défaut d'une section
     * 
     * @return array Structure par défaut
     */
    public static function get_default_section() {
        return Sisme_Game_Creator_Constants::get_default_section_structure();
    }
    
    /**
     * Compter les sections valides
     * 
     * @param array $sections Liste des sections
     * @return int Nombre de sections valides
     */
    public static function count_valid_sections($sections) {
        if (!is_array($sections)) {
            return 0;
        }
        
        return count(self::filter_valid_sections($sections));
    }
    
    /**
     * Vérifier si le nombre de sections respecte les limites
     * 
     * @param array $sections Liste des sections
     * @return bool Nombre de sections valide
     */
    public static function is_valid_sections_count($sections) {
        $count = self::count_valid_sections($sections);
        
        return $count >= Sisme_Game_Creator_Constants::MIN_SECTIONS && 
               $count <= Sisme_Game_Creator_Constants::MAX_SECTIONS;
    }
    
    /**
     * Obtenir les statistiques des sections
     * 
     * @param array $sections Liste des sections
     * @return array Statistiques
     */
    public static function get_sections_stats($sections) {
        if (!is_array($sections)) {
            return array(
                'total' => 0,
                'valid' => 0,
                'with_images' => 0,
                'average_content_length' => 0
            );
        }
        
        $valid_sections = self::filter_valid_sections($sections);
        $sections_with_images = 0;
        $total_content_length = 0;
        
        foreach ($valid_sections as $section) {
            if (!empty($section['image_id'])) {
                $sections_with_images++;
            }
            
            $total_content_length += strlen($section['content'] ?? '');
        }
        
        $valid_count = count($valid_sections);
        
        return array(
            'total' => count($sections),
            'valid' => $valid_count,
            'with_images' => $sections_with_images,
            'average_content_length' => $valid_count > 0 ? round($total_content_length / $valid_count) : 0
        );
    }
}