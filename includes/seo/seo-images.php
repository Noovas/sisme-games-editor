<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-images.php
 * Module SEO - Optimisation des images pour référencement
 * 
 * RESPONSABILITÉ:
 * - Alt text automatique pour images de jeu
 * - Title attributes optimisés
 * - Lazy loading et attributs performance
 * - Données structurées pour images
 * - Cache des attributs d'images
 * 
 * DÉPENDANCES:
 * - seo-game-detector.php
 * - WordPress Media API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Images {
    
    /**
     * Durée du cache pour les attributs d'images (2 heures)
     */
    const CACHE_DURATION = 7200;
    
    /**
     * Types d'images reconnus
     */
    const IMAGE_TYPE_COVER = 'cover';
    const IMAGE_TYPE_SCREENSHOT = 'screenshot';
    const IMAGE_TYPE_SECTION = 'section';
    const IMAGE_TYPE_FEATURED = 'featured';
    
    /**
     * Templates ALT text selon le type d'image
     */
    const ALT_COVER = 'Cover de %s, jeu %s indépendant à découvrir sur Sisme Games';
    const ALT_SCREENSHOT = 'Screenshot de gameplay de %s - Jeu %s en action';
    const ALT_SECTION = 'Image de %s - %s';
    const ALT_FEATURED = '%s - Jeu indépendant mis en avant sur Sisme Games';
    const ALT_DEFAULT = '%s sur Sisme Games';
    
    /**
     * Templates TITLE attributes
     */
    const TITLE_COVER = 'Découvrir %s, jeu %s';
    const TITLE_SCREENSHOT = 'Voir %s en action';
    const TITLE_SECTION = '%s - %s';
    const TITLE_DEFAULT = '%s sur Sisme Games';
    
    public function __construct() {
        add_filter('wp_get_attachment_image_attributes', array($this, 'optimize_image_attributes'), 10, 3);
        add_filter('post_thumbnail_html', array($this, 'optimize_featured_image'), 10, 5);
        add_action('wp_head', array($this, 'add_image_structured_data'), 60);
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_cache_on_save'));
    }
    
    /**
     * Optimiser les attributs d'image automatiquement
     */
    public function optimize_image_attributes($attr, $attachment, $size) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $attr;
        }
        
        $attachment_id = $attachment->ID ?? 0;
        if (!$attachment_id) {
            return $attr;
        }
        
        $cache_key = 'sisme_image_attr_' . $attachment_id;
        $cached_attr = get_transient($cache_key);
        
        if ($cached_attr !== false) {
            return array_merge($attr, $cached_attr);
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $attr;
        }
        
        $image_type = $this->determine_image_type($attachment_id, $game_data);
        $optimized_attr = $this->generate_optimized_attributes($attachment, $game_data, $image_type, $size);
        
        if ($optimized_attr) {
            set_transient($cache_key, $optimized_attr, self::CACHE_DURATION);
            return array_merge($attr, $optimized_attr);
        }
        
        return $attr;
    }
    
    /**
     * Optimiser spécifiquement l'image à la une
     */
    public function optimize_featured_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!Sisme_SEO_Loader::is_game_page($post_id)) {
            return $html;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data($post_id);
        if (!$game_data) {
            return $html;
        }
        
        $attachment = get_post($post_thumbnail_id);
        if (!$attachment) {
            return $html;
        }
        
        $optimized_attr = $this->generate_optimized_attributes(
            $attachment, 
            $game_data, 
            self::IMAGE_TYPE_FEATURED,
            $size
        );
        
        if ($optimized_attr) {
            foreach ($optimized_attr as $key => $value) {
                $pattern = '/' . preg_quote($key, '/') . '="[^"]*"/';
                $replacement = $key . '="' . esc_attr($value) . '"';
                
                if (preg_match($pattern, $html)) {
                    $html = preg_replace($pattern, $replacement, $html);
                } else {
                    $html = str_replace('<img ', '<img ' . $key . '="' . esc_attr($value) . '" ', $html);
                }
            }
        }
        
        return $html;
    }
    
    /**
     * Ajouter les données structurées pour les images
     */
    public function add_image_structured_data() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return;
        }
        
        $image_collection = $this->generate_image_collection_schema($game_data);
        if ($image_collection) {
            echo '<script type="application/ld+json">' . "\n";
            echo json_encode($image_collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "\n" . '</script>' . "\n";
        }
    }
    
    /**
     * Déterminer le type d'une image
     */
    private function determine_image_type($attachment_id, $game_data) {
        $covers = $game_data['covers'] ?? array();
        
        foreach ($covers as $cover_type => $cover_url) {
            if ($this->url_matches_attachment($cover_url, $attachment_id)) {
                return self::IMAGE_TYPE_COVER;
            }
        }
        
        $screenshots = $game_data['screenshots'] ?? array();
        foreach ($screenshots as $screenshot) {
            if (isset($screenshot['id']) && $screenshot['id'] == $attachment_id) {
                return self::IMAGE_TYPE_SCREENSHOT;
            }
        }
        
        $sections = $game_data['sections'] ?? array();
        foreach ($sections as $section) {
            if (isset($section['image_id']) && $section['image_id'] == $attachment_id) {
                return self::IMAGE_TYPE_SECTION;
            }
        }
        
        global $post;
        if ($post && get_post_thumbnail_id($post->ID) == $attachment_id) {
            return self::IMAGE_TYPE_FEATURED;
        }
        
        return self::IMAGE_TYPE_COVER;
    }
    
    /**
     * Vérifier si une URL correspond à un attachment
     */
    private function url_matches_attachment($url, $attachment_id) {
        if (empty($url)) {
            return false;
        }
        
        $attachment_url = wp_get_attachment_url($attachment_id);
        return $url === $attachment_url;
    }
    
    /**
     * Générer les attributs optimisés pour une image
     */
    private function generate_optimized_attributes($attachment, $game_data, $image_type, $size) {
        $game_name = $game_data['name'] ?? '';
        $genres = $game_data['genres'] ?? array();
        $main_genre = !empty($genres) ? ($genres[0]['name'] ?? '') : '';
        
        $attr = array();
        
        switch ($image_type) {
            case self::IMAGE_TYPE_COVER:
                $attr['alt'] = sprintf(self::ALT_COVER, $game_name, $main_genre);
                $attr['title'] = sprintf(self::TITLE_COVER, $game_name, $main_genre);
                break;
                
            case self::IMAGE_TYPE_SCREENSHOT:
                $attr['alt'] = sprintf(self::ALT_SCREENSHOT, $game_name, $main_genre);
                $attr['title'] = sprintf(self::TITLE_SCREENSHOT, $game_name);
                break;
                
            case self::IMAGE_TYPE_SECTION:
                $section_title = $this->get_section_title_for_image($attachment->ID, $game_data);
                $attr['alt'] = sprintf(self::ALT_SECTION, $game_name, $section_title);
                $attr['title'] = sprintf(self::TITLE_SECTION, $game_name, $section_title);
                break;
                
            case self::IMAGE_TYPE_FEATURED:
                $attr['alt'] = sprintf(self::ALT_FEATURED, $game_name);
                $attr['title'] = sprintf(self::TITLE_DEFAULT, $game_name);
                break;
                
            default:
                $attr['alt'] = sprintf(self::ALT_DEFAULT, $game_name);
                $attr['title'] = sprintf(self::TITLE_DEFAULT, $game_name);
        }
        
        if (!isset($attr['loading']) && $size !== 'full') {
            $attr['loading'] = 'lazy';
        }
        
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        
        return $attr;
    }
    
    /**
     * Obtenir le titre de section pour une image
     */
    private function get_section_title_for_image($attachment_id, $game_data) {
        $sections = $game_data['sections'] ?? array();
        
        foreach ($sections as $section) {
            if (isset($section['image_id']) && $section['image_id'] == $attachment_id) {
                return $section['title'] ?? 'Section du jeu';
            }
        }
        
        return 'Contenu du jeu';
    }
    
    /**
     * Générer le schéma des images pour données structurées
     */
    private function generate_image_collection_schema($game_data) {
        $images = array();
        
        $covers = $game_data['covers'] ?? array();
        foreach ($covers as $cover_type => $cover_url) {
            if ($cover_url) {
                $images[] = array(
                    '@type' => 'ImageObject',
                    'url' => $cover_url,
                    'name' => 'Cover de ' . ($game_data['name'] ?? ''),
                    'description' => 'Image de couverture du jeu ' . ($game_data['name'] ?? ''),
                    'contentUrl' => $cover_url
                );
            }
        }
        
        $screenshots = $game_data['screenshots'] ?? array();
        foreach (array_slice($screenshots, 0, 5) as $screenshot) {
            if (!empty($screenshot['url'])) {
                $images[] = array(
                    '@type' => 'ImageObject',
                    'url' => $screenshot['url'],
                    'name' => $screenshot['alt'] ?? 'Screenshot de ' . ($game_data['name'] ?? ''),
                    'description' => 'Capture d\'écran du jeu ' . ($game_data['name'] ?? ''),
                    'contentUrl' => $screenshot['url']
                );
            }
        }
        
        if (empty($images)) {
            return null;
        }
        
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => 'Images de ' . ($game_data['name'] ?? ''),
            'description' => 'Collection d\'images du jeu ' . ($game_data['name'] ?? ''),
            'image' => $images
        );
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            $this->clear_images_cache();
        }
    }
    
    /**
     * Nettoyer le cache lors de l'édition d'un terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            $this->clear_images_cache();
        }
    }
    
    /**
     * Nettoyer le cache des images
     */
    private function clear_images_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_image_attr_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sisme_image_attr_%'");
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme SEO] Cache images nettoyé');
        }
    }
    
    /**
     * Obtenir les statistiques des images optimisées
     */
    public function get_images_stats() {
        global $wpdb;
        
        $cached_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_image_attr_%'");
        
        return array(
            'cached_images' => (int)$cached_images,
            'cache_duration' => self::CACHE_DURATION,
            'supported_types' => array(
                self::IMAGE_TYPE_COVER,
                self::IMAGE_TYPE_SCREENSHOT,
                self::IMAGE_TYPE_SECTION,
                self::IMAGE_TYPE_FEATURED
            )
        );
    }
    
    /**
     * Debug : analyser les images d'une page de jeu
     */
    public function debug_page_images($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if (!Sisme_SEO_Loader::is_game_page($post_id)) {
            return array('error' => 'Pas une page de jeu');
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data($post_id);
        if (!$game_data) {
            return array('error' => 'Pas de données de jeu');
        }
        
        $analysis = array(
            'game_name' => $game_data['name'] ?? '',
            'covers' => array(),
            'screenshots' => array(),
            'sections_with_images' => array(),
            'featured_image' => null
        );
        
        $covers = $game_data['covers'] ?? array();
        foreach ($covers as $type => $url) {
            $analysis['covers'][$type] = array(
                'url' => $url,
                'accessible' => !empty($url)
            );
        }
        
        $screenshots = $game_data['screenshots'] ?? array();
        $analysis['screenshots'] = array_slice($screenshots, 0, 3);
        
        $sections = $game_data['sections'] ?? array();
        foreach ($sections as $section) {
            if (!empty($section['image_id'])) {
                $analysis['sections_with_images'][] = array(
                    'title' => $section['title'] ?? '',
                    'image_id' => $section['image_id'],
                    'image_url' => wp_get_attachment_url($section['image_id'])
                );
            }
        }
        
        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $analysis['featured_image'] = array(
                'id' => $featured_id,
                'url' => wp_get_attachment_url($featured_id)
            );
        }
        
        return $analysis;
    }
}