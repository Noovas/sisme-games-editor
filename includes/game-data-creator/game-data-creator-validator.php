<?php
/**
 * File: /sisme-games-editor/includes/game-data-creator/game-data-creator-validator.php
 * Validation et sanitisation pour le module Game Creator
 * 
 * RESPONSABILITÉ:
 * - Validation complète des données de jeu
 * - Sanitisation et nettoyage des données
 * - Messages d'erreur lisibles
 * - Validation métier (plateformes, sections, etc.)
 * 
 * DÉPENDANCES:
 * - game-data-creator-constants.php
 * - WordPress sanitize functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Creator_Validator {
    
    /**
     * Valider les données complètes d'un jeu
     * @param array $game_data Données du jeu à valider
     * @return array ['valid' => bool, 'errors' => array, 'sanitized_data' => array]
     */
    public static function validate_game_data($game_data) {
        $errors = array();
        $sanitized_data = self::sanitize_game_data($game_data);
        
        // Validation du nom (obligatoire)
        if (empty($sanitized_data['name'])) {
            $errors[] = 'Le nom du jeu est obligatoire';
        } elseif (strlen($sanitized_data['name']) > Sisme_Game_Creator_Constants::MAX_GAME_NAME_LENGTH) {
            $errors[] = 'Le nom du jeu ne peut pas dépasser ' . Sisme_Game_Creator_Constants::MAX_GAME_NAME_LENGTH . ' caractères';
        }
        
        // Validation de la description
        if (!empty($sanitized_data['description'])) {
            if (strlen($sanitized_data['description']) > Sisme_Game_Creator_Constants::MAX_DESCRIPTION_LENGTH) {
                $errors[] = 'La description ne peut pas dépasser ' . Sisme_Game_Creator_Constants::MAX_DESCRIPTION_LENGTH . ' caractères';
            }
        }
        
        // Validation des plateformes
        if (!empty($sanitized_data['platforms'])) {
            $platform_errors = self::validate_platforms($sanitized_data['platforms']);
            $errors = array_merge($errors, $platform_errors);
        }
        
        // Validation des liens externes
        if (!empty($sanitized_data['external_links'])) {
            $links_errors = self::validate_external_links($sanitized_data['external_links']);
            $errors = array_merge($errors, $links_errors);
        }
        
        // Validation des sections
        if (!empty($sanitized_data['sections'])) {
            $sections_errors = self::validate_sections($sanitized_data['sections']);
            $errors = array_merge($errors, $sections_errors);
        }
        
        // Validation des développeurs/éditeurs
        if (!empty($sanitized_data['developers'])) {
            $dev_errors = self::validate_entities($sanitized_data['developers'], 'developers');
            $errors = array_merge($errors, $dev_errors);
        }
        
        if (!empty($sanitized_data['publishers'])) {
            $pub_errors = self::validate_entities($sanitized_data['publishers'], 'publishers');
            $errors = array_merge($errors, $pub_errors);
        }
        
        // Validation des médias
        if (!empty($sanitized_data['screenshots'])) {
            $screenshots_errors = self::validate_screenshots($sanitized_data['screenshots']);
            $errors = array_merge($errors, $screenshots_errors);
        }
        
        // Validation de la date de sortie
        if (!empty($sanitized_data['release_date'])) {
            if (!self::is_valid_date($sanitized_data['release_date'])) {
                $errors[] = 'La date de sortie doit être au format YYYY-MM-DD';
            }
        }
        
        // Validation du trailer
        if (!empty($sanitized_data['trailer_link'])) {
            if (!self::is_valid_video_url($sanitized_data['trailer_link'])) {
                $errors[] = 'Le lien trailer doit être une URL YouTube ou Vimeo valide';
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_data' => $sanitized_data
        );
    }
    
    /**
     * Sanitiser les données d'un jeu
     * @param array $game_data Données brutes
     * @return array Données nettoyées
     */
    public static function sanitize_game_data($game_data) {
        $sanitized = array();
        
        // Champs texte simples
        $text_fields = array('name', 'description', 'trailer_link', 'release_date');
        foreach ($text_fields as $field) {
            if (isset($game_data[$field])) {
                $sanitized[$field] = sanitize_text_field(trim($game_data[$field]));
            }
        }
        
        // URLs
        if (isset($game_data['trailer_link'])) {
            $sanitized['trailer_link'] = esc_url_raw($game_data['trailer_link']);
        }
        
        // Booléens
        if (isset($game_data['is_team_choice'])) {
            $sanitized['is_team_choice'] = (bool) $game_data['is_team_choice'];
        }
        
        // Arrays d'IDs
        $id_array_fields = array('genres', 'developers', 'publishers', 'screenshots');
        foreach ($id_array_fields as $field) {
            if (isset($game_data[$field]) && is_array($game_data[$field])) {
                $sanitized[$field] = array_map('intval', array_filter($game_data[$field]));
            }
        }
        
        // Modes de jeu (conserver les valeurs en chaînes)
        if (isset($game_data['modes']) && is_array($game_data['modes'])) {
            $sanitized['modes'] = array_filter($game_data['modes']);
        }
        
        // Plateformes (structure spéciale)
        if (isset($game_data['platforms']) && is_array($game_data['platforms'])) {
            $sanitized['platforms'] = self::sanitize_platforms($game_data['platforms']);
        }
        
        // Liens externes
        if (isset($game_data['external_links']) && is_array($game_data['external_links'])) {
            $sanitized['external_links'] = self::sanitize_external_links($game_data['external_links']);
        }
        
        // Sections
        if (isset($game_data['sections']) && is_array($game_data['sections'])) {
            $sanitized['sections'] = self::sanitize_sections($game_data['sections']);
        }
        
        // Covers
        if (isset($game_data['covers']) && is_array($game_data['covers'])) {
            $sanitized['covers'] = self::sanitize_covers($game_data['covers']);
        }
        
        return $sanitized;
    }
    
    /**
     * Valider les plateformes
     */
    private static function validate_platforms($platforms) {
        $errors = array();
        
        if (!is_array($platforms)) {
            $errors[] = 'Les plateformes doivent être un tableau';
            return $errors;
        }
        
        foreach ($platforms as $platform) {
            if (!self::is_valid_platform($platform)) {
                $errors[] = "Plateforme non supportée : {$platform}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Valider les liens externes
     */
    private static function validate_external_links($external_links) {
        $errors = array();
        
        if (!is_array($external_links)) {
            $errors[] = 'Les liens externes doivent être un tableau';
            return $errors;
        }
        
        if (count($external_links) > Sisme_Game_Creator_Constants::MAX_EXTERNAL_LINKS) {
            $errors[] = 'Nombre maximum de liens externes dépassé (' . Sisme_Game_Creator_Constants::MAX_EXTERNAL_LINKS . ')';
        }
        
        foreach ($external_links as $platform => $url) {
            if (!self::is_valid_external_platform($platform)) {
                $errors[] = "Plateforme de lien externe non supportée : {$platform}";
            }
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "URL invalide pour {$platform} : {$url}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Valider les sections
     */
    private static function validate_sections($sections) {
        $errors = array();
        
        if (!is_array($sections)) {
            $errors[] = 'Les sections doivent être un tableau';
            return $errors;
        }
        
        if (count($sections) > Sisme_Game_Creator_Constants::MAX_SECTIONS) {
            $errors[] = 'Nombre maximum de sections dépassé (' . Sisme_Game_Creator_Constants::MAX_SECTIONS . ')';
        }
        
        if (count($sections) < Sisme_Game_Creator_Constants::MIN_SECTIONS) {
            $errors[] = 'Au moins une section est requise';
        }
        
        foreach ($sections as $index => $section) {
            $section_errors = self::validate_single_section($section, $index + 1);
            $errors = array_merge($errors, $section_errors);
        }
        
        return $errors;
    }
    
    /**
     * Valider une section individuelle
     */
    private static function validate_single_section($section, $section_number) {
        $errors = array();
        
        if (!is_array($section)) {
            $errors[] = "Section {$section_number} : format invalide";
            return $errors;
        }
        
        // Titre obligatoire
        $title = $section['title'] ?? '';
        if (empty($title)) {
            $errors[] = "Section {$section_number} : titre obligatoire";
        } elseif (strlen($title) > Sisme_Game_Creator_Constants::MAX_SECTION_TITLE_LENGTH) {
            $errors[] = "Section {$section_number} : titre trop long (max " . Sisme_Game_Creator_Constants::MAX_SECTION_TITLE_LENGTH . " caractères)";
        }
        
        // Contenu obligatoire
        $content = $section['content'] ?? '';
        if (empty($content)) {
            $errors[] = "Section {$section_number} : contenu obligatoire";
        } elseif (strlen($content) < Sisme_Game_Creator_Constants::MIN_SECTION_CONTENT_LENGTH) {
            $errors[] = "Section {$section_number} : contenu trop court (min " . Sisme_Game_Creator_Constants::MIN_SECTION_CONTENT_LENGTH . " caractères)";
        } elseif (strlen($content) > Sisme_Game_Creator_Constants::MAX_SECTION_CONTENT_LENGTH) {
            $errors[] = "Section {$section_number} : contenu trop long (max " . Sisme_Game_Creator_Constants::MAX_SECTION_CONTENT_LENGTH . " caractères)";
        }
        
        // Image optionnelle mais si présente, doit être un ID valide
        if (!empty($section['image_id']) && !is_numeric($section['image_id'])) {
            $errors[] = "Section {$section_number} : ID d'image invalide";
        }
        
        return $errors;
    }
    
    /**
     * Valider les entités (développeurs/éditeurs)
     */
    private static function validate_entities($entities, $type) {
        $errors = array();
        $max_limit = ($type === 'developers') ? 
            Sisme_Game_Creator_Constants::MAX_DEVELOPERS : 
            Sisme_Game_Creator_Constants::MAX_PUBLISHERS;
        
        if (!is_array($entities)) {
            $errors[] = ucfirst($type) . ' doivent être un tableau';
            return $errors;
        }
        
        if (count($entities) > $max_limit) {
            $errors[] = "Nombre maximum de {$type} dépassé ({$max_limit})";
        }
        
        foreach ($entities as $entity_id) {
            if (!is_numeric($entity_id) || intval($entity_id) <= 0) {
                $errors[] = "ID invalide dans {$type} : {$entity_id}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Valider les screenshots
     */
    private static function validate_screenshots($screenshots) {
        $errors = array();
        
        if (!is_array($screenshots)) {
            $errors[] = 'Les screenshots doivent être un tableau';
            return $errors;
        }
        
        if (count($screenshots) > Sisme_Game_Creator_Constants::MAX_SCREENSHOTS) {
            $errors[] = 'Nombre maximum de screenshots dépassé (' . Sisme_Game_Creator_Constants::MAX_SCREENSHOTS . ')';
        }
        
        foreach ($screenshots as $screenshot_id) {
            if (!is_numeric($screenshot_id) || intval($screenshot_id) <= 0) {
                $errors[] = "ID de screenshot invalide : {$screenshot_id}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitiser les plateformes
     */
    private static function sanitize_platforms($platforms) {
        $sanitized = array();
        
        if (is_array($platforms)) {
            foreach ($platforms as $platform) {
                $clean_platform = sanitize_text_field($platform);
                if (!empty($clean_platform)) {
                    $sanitized[] = $clean_platform;
                }
            }
        }
        
        return array_unique($sanitized);
    }
    
    /**
     * Sanitiser les liens externes
     */
    private static function sanitize_external_links($external_links) {
        $sanitized = array();
        
        if (is_array($external_links)) {
            foreach ($external_links as $platform => $url) {
                $clean_platform = sanitize_text_field($platform);
                $clean_url = esc_url_raw(trim($url));
                
                if (!empty($clean_platform) && !empty($clean_url) && filter_var($clean_url, FILTER_VALIDATE_URL)) {
                    $sanitized[$clean_platform] = $clean_url;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiser les sections
     */
    private static function sanitize_sections($sections) {
        $sanitized = array();
        
        if (is_array($sections)) {
            foreach ($sections as $section) {
                if (is_array($section)) {
                    $clean_section = array(
                        'title' => sanitize_text_field($section['title'] ?? ''),
                        'content' => wp_kses_post($section['content'] ?? ''),
                        'image_id' => !empty($section['image_id']) ? intval($section['image_id']) : 
                                     (!empty($section['image_attachment_id']) ? intval($section['image_attachment_id']) : '')
                    );
                    
                    // Ne garder que les sections avec titre et contenu
                    if (!empty($clean_section['title']) && !empty($clean_section['content'])) {
                        $sanitized[] = $clean_section;
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiser les covers
     */
    private static function sanitize_covers($covers) {
        $sanitized = array();
        $valid_cover_types = array('main', 'news', 'patch', 'test', 'horizontal', 'vertical');
        
        if (is_array($covers)) {
            foreach ($covers as $type => $cover_id) {
                $clean_type = sanitize_text_field($type);
                if (in_array($clean_type, $valid_cover_types) && is_numeric($cover_id) && intval($cover_id) > 0) {
                    $sanitized[$clean_type] = intval($cover_id);
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Vérifier si une plateforme est supportée
     */
    public static function is_valid_platform($platform) {
        return array_key_exists($platform, Sisme_Game_Creator_Constants::PLATFORMS);
    }
    
    /**
     * Vérifier si une plateforme de lien externe est supportée
     */
    public static function is_valid_external_platform($platform) {
        return array_key_exists($platform, Sisme_Game_Creator_Constants::EXTERNAL_LINKS_PLATFORMS);
    }
    
    /**
     * Vérifier si une date est valide (format YYYY-MM-DD)
     */
    public static function is_valid_date($date) {
        if (empty($date)) {
            return false;
        }
        
        $date_parts = explode('-', $date);
        if (count($date_parts) !== 3) {
            return false;
        }
        
        return checkdate(intval($date_parts[1]), intval($date_parts[2]), intval($date_parts[0]));
    }
    
    /**
     * Vérifier si une URL vidéo est valide (YouTube/Vimeo)
     */
    public static function is_valid_video_url($url) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $parsed_url = parse_url($url);
        $host = $parsed_url['host'] ?? '';
        
        $valid_hosts = array(
            'youtube.com', 'www.youtube.com', 'youtu.be', 'm.youtube.com',
            'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'
        );
        
        return in_array(strtolower($host), $valid_hosts);
    }
    
    /**
     * Obtenir la structure par défaut d'une section
     */
    public static function get_default_section_structure() {
        return array(
            'title' => '',
            'content' => '',
            'image_id' => ''
        );
    }
    
    /**
     * Valider une section individuelle (helper public)
     */
    public static function is_valid_section($section) {
        $errors = self::validate_single_section($section, 1);
        return empty($errors);
    }
}