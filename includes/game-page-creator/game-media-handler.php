<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-media-handler.php
 * Gestionnaire des médias pour les pages de jeu
 * 
 * RESPONSABILITÉ:
 * - Extraction YouTube ID depuis URLs
 * - Gestion des screenshots avec URLs et thumbnails
 * - Validation des médias
 * - URLs de thumbnails YouTube
 * 
 * DÉPENDANCES:
 * - Aucune (fonctions autonomes)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Media_Handler {
    
    /**
     * Extraire l'ID YouTube depuis une URL
     * 
     * @param string $url URL YouTube
     * @return string|false ID YouTube ou false si invalide
     */
    public static function extract_youtube_id($url) {
        if (empty($url)) {
            return false;
        }
        
        $patterns = array(
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i',
            '/youtube\.com\/watch\?v=([^&\n?#]+)/i',
            '/youtu\.be\/([^&\n?#]+)/i',
            '/youtube\.com\/embed\/([^&\n?#]+)/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    /**
     * Obtenir l'URL du thumbnail YouTube
     * 
     * @param string $youtube_id ID YouTube
     * @param string $quality Qualité (default, mqdefault, hqdefault, sddefault, maxresdefault)
     * @return string URL du thumbnail
     */
    public static function get_youtube_thumbnail($youtube_id, $quality = 'maxresdefault') {
        if (empty($youtube_id)) {
            return '';
        }
        
        $valid_qualities = array('default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault');
        if (!in_array($quality, $valid_qualities)) {
            $quality = 'maxresdefault';
        }
        
        return "https://img.youtube.com/vi/{$youtube_id}/{$quality}.jpg";
    }
    
    /**
     * Valider une URL YouTube
     * 
     * @param string $url URL à valider
     * @return bool URL YouTube valide
     */
    public static function is_valid_youtube_url($url) {
        return self::extract_youtube_id($url) !== false;
    }
    
    /**
     * Obtenir l'URL d'embed YouTube
     * 
     * @param string $youtube_id ID YouTube
     * @param array $params Paramètres d'embed
     * @return string URL d'embed
     */
    public static function get_youtube_embed_url($youtube_id, $params = array()) {
        if (empty($youtube_id)) {
            return '';
        }
        
        $default_params = array(
            'enablejsapi' => '1',
            'rel' => '0',
            'showinfo' => '0'
        );
        
        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);
        
        return "https://www.youtube.com/embed/{$youtube_id}?{$query_string}";
    }
    
    /**
     * Traiter un screenshot WordPress
     * 
     * @param int $attachment_id ID de l'attachment WordPress
     * @return array|false Données du screenshot ou false si erreur
     */
    public static function process_screenshot($attachment_id) {
        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return false;
        }
        
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        $url = wp_get_attachment_url($attachment_id);
        $thumbnail = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        $medium = wp_get_attachment_image_url($attachment_id, 'medium');
        $large = wp_get_attachment_image_url($attachment_id, 'large');
        
        if (!$url) {
            return false;
        }
        
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        return array(
            'id' => $attachment_id,
            'url' => $url,
            'thumbnail' => $thumbnail ?: $url,
            'medium' => $medium ?: $url,
            'large' => $large ?: $url,
            'alt' => $alt_text ?: 'Screenshot',
            'title' => $attachment->post_title ?: 'Screenshot'
        );
    }
    
    /**
     * Traiter une liste de screenshots
     * 
     * @param array $screenshot_ids Liste des IDs d'attachments
     * @return array Screenshots traités
     */
    public static function process_screenshots($screenshot_ids) {
        if (!is_array($screenshot_ids)) {
            return array();
        }
        
        $processed = array();
        
        foreach ($screenshot_ids as $screenshot_id) {
            $screenshot_data = self::process_screenshot($screenshot_id);
            if ($screenshot_data) {
                $processed[] = $screenshot_data;
            }
        }
        
        return $processed;
    }
    
    /**
     * Valider qu'un attachment est une image
     * 
     * @param int $attachment_id ID de l'attachment
     * @return bool Est une image valide
     */
    public static function is_valid_image_attachment($attachment_id) {
        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return false;
        }
        
        $mime_type = get_post_mime_type($attachment_id);
        return strpos($mime_type, 'image/') === 0;
    }
    
    /**
     * Obtenir les métadonnées d'une image
     * 
     * @param int $attachment_id ID de l'attachment
     * @return array|false Métadonnées ou false si erreur
     */
    public static function get_image_metadata($attachment_id) {
        if (!self::is_valid_image_attachment($attachment_id)) {
            return false;
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!$metadata) {
            return false;
        }
        
        return array(
            'width' => $metadata['width'] ?? 0,
            'height' => $metadata['height'] ?? 0,
            'file' => $metadata['file'] ?? '',
            'filesize' => $metadata['filesize'] ?? 0,
            'sizes' => $metadata['sizes'] ?? array()
        );
    }
    
    /**
     * Vérifier si un attachment existe
     * 
     * @param int $attachment_id ID de l'attachment
     * @return bool Attachment existe
     */
    public static function attachment_exists($attachment_id) {
        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return false;
        }
        
        $attachment = get_post($attachment_id);
        return $attachment && $attachment->post_type === 'attachment';
    }
    
    /**
     * Nettoyer une liste d'IDs d'attachments
     * 
     * @param array $attachment_ids Liste des IDs
     * @return array IDs nettoyés et validés
     */
    public static function clean_attachment_ids($attachment_ids) {
        if (!is_array($attachment_ids)) {
            return array();
        }
        
        $cleaned = array();
        
        foreach ($attachment_ids as $id) {
            $id = intval($id);
            if ($id > 0 && self::attachment_exists($id)) {
                $cleaned[] = $id;
            }
        }
        
        return array_unique($cleaned);
    }
    
    /**
     * Obtenir l'URL optimale d'un attachment selon la taille demandée
     * 
     * @param int $attachment_id ID de l'attachment
     * @param string $size Taille demandée
     * @return string|false URL optimale ou false si erreur
     */
    public static function get_optimal_image_url($attachment_id, $size = 'large') {
        if (!self::is_valid_image_attachment($attachment_id)) {
            return false;
        }
        
        $url = wp_get_attachment_image_url($attachment_id, $size);
        if ($url) {
            return $url;
        }
        
        return wp_get_attachment_url($attachment_id);
    }
}