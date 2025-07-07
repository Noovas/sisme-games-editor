<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-images.php
 * Module SEO - Optimisation SEO des images
 * 
 * RESPONSABILITÉ:
 * - Optimisation des attributs alt et title des images
 * - Génération de légendes SEO-friendly
 * - Lazy loading intelligent
 * - Optimisation des noms de fichiers
 * - Structured data pour les images
 * - Cache des métadonnées d'images
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Images {
    
    /**
     * Durée du cache pour les métadonnées d'images (1 heure)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Types d'images de jeu reconnus
     */
    const IMAGE_TYPE_COVER = 'cover';
    const IMAGE_TYPE_SCREENSHOT = 'screenshot';
    const IMAGE_TYPE_TRAILER_THUMB = 'trailer_thumb';
    const IMAGE_TYPE_SECTION = 'section';
    
    public function __construct() {
        // Hooks pour l'optimisation des images
        add_filter('wp_get_attachment_image_attributes', array($this, 'optimize_image_attributes'), 10, 3);
        add_filter('the_content', array($this, 'optimize_content_images'), 20);
        add_action('wp_head', array($this, 'add_image_structured_data'), 50);
        
        // Hooks pour les images de jeu spécifiques
        add_filter('sisme_game_image_alt', array($this, 'generate_game_image_alt'), 10, 3);
        add_filter('sisme_game_image_title', array($this, 'generate_game_image_title'), 10, 3);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('attachment_updated', array($this, 'clear_image_cache'));
    }
    
    /**
     * Optimiser les attributs des images WordPress
     */
    public function optimize_image_attributes($attr, $attachment, $size) {
        // Vérifier si on est sur une page de jeu
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $attr;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $attr;
        }
        
        // Déterminer le type d'image
        $image_type = $this->determine_image_type($attachment->ID, $game_data);
        
        // Optimiser alt si vide ou générique
        if (empty($attr['alt']) || $this->is_generic_alt($attr['alt'])) {
            $optimized_alt = $this->generate_optimized_alt($attachment, $game_data, $image_type);
            if ($optimized_alt) {
                $attr['alt'] = $optimized_alt;
            }
        }
        
        // Optimiser title si vide
        if (empty($attr['title'])) {
            $optimized_title = $this->generate_optimized_title($attachment, $game_data, $image_type);
            if ($optimized_title) {
                $attr['title'] = $optimized_title;
            }
        }
        
        // Ajouter lazy loading si pas déjà présent
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        
        // Ajouter decoding async pour les performances
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        
        return $attr;
    }
    
    /**
     * Optimiser les images dans le contenu
     */
    public function optimize_content_images($content) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $content;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $content;
        }
        
        // Traiter les images dans le contenu
        $content = preg_replace_callback(
            '/<img[^>]+>/i',
            function($matches) use ($game_data) {
                return $this->optimize_content_image_tag($matches[0], $game_data);
            },
            $content
        );
        
        return $content;
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
     * Déterminer le type d'image
     */
    private function determine_image_type($attachment_id, $game_data) {
        // Vérifier si c'est une cover
        $covers = $game_data[Sisme_Utils_Games::KEY_COVERS] ?? array();
        foreach ($covers as $cover_type => $cover_id) {
            if ($cover_id == $attachment_id) {
                return self::IMAGE_TYPE_COVER;
            }
        }
        
        // Vérifier si c'est un screenshot
        $screenshots = $game_data[Sisme_Utils_Games::KEY_SCREENSHOTS] ?? '';
        if ($screenshots) {
            $screenshot_ids = array_map('trim', explode(',', $screenshots));
            if (in_array($attachment_id, $screenshot_ids)) {
                return self::IMAGE_TYPE_SCREENSHOT;
            }
        }
        
        // Vérifier si c'est dans une section personnalisée
        global $post;
        $game_sections = get_post_meta($post->ID, '_sisme_game_sections', true) ?: array();
        foreach ($game_sections as $section) {
            if (($section['image_id'] ?? 0) == $attachment_id) {
                return self::IMAGE_TYPE_SECTION;
            }
        }
        
        // Type par défaut
        return self::IMAGE_TYPE_SCREENSHOT;
    }
    
    /**
     * Générer un alt optimisé
     */
    private function generate_optimized_alt($attachment, $game_data, $image_type) {
        $cache_key = 'sisme_image_alt_' . $attachment->ID . '_' . $image_type;
        $cached_alt = get_transient($cache_key);
        if ($cached_alt !== false) {
            return $cached_alt;
        }
        
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $genres = $this->get_primary_genres($game_data, 2);
        
        $alt_text = '';
        
        switch ($image_type) {
            case self::IMAGE_TYPE_COVER:
                $alt_text = $this->generate_cover_alt($game_name, $genres);
                break;
                
            case self::IMAGE_TYPE_SCREENSHOT:
                $alt_text = $this->generate_screenshot_alt($game_name, $genres);
                break;
                
            case self::IMAGE_TYPE_TRAILER_THUMB:
                $alt_text = $this->generate_trailer_thumb_alt($game_name);
                break;
                
            case self::IMAGE_TYPE_SECTION:
                $alt_text = $this->generate_section_alt($game_name, $attachment);
                break;
                
            default:
                $alt_text = $this->generate_generic_alt($game_name, $genres);
        }
        
        // Mettre en cache
        set_transient($cache_key, $alt_text, self::CACHE_DURATION);
        
        return $alt_text;
    }
    
    /**
     * Générer un title optimisé
     */
    private function generate_optimized_title($attachment, $game_data, $image_type) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $description = $game_data[Sisme_Utils_Games::KEY_DESCRIPTION] ?? '';
        
        $title_text = '';
        
        switch ($image_type) {
            case self::IMAGE_TYPE_COVER:
                $title_text = "Découvrez {$game_name} - Jeu indépendant à télécharger";
                break;
                
            case self::IMAGE_TYPE_SCREENSHOT:
                $title_text = "Gameplay de {$game_name} - Aperçu du jeu en action";
                break;
                
            case self::IMAGE_TYPE_TRAILER_THUMB:
                $title_text = "Regarder la bande-annonce de {$game_name}";
                break;
                
            case self::IMAGE_TYPE_SECTION:
                $title_text = "Image de {$game_name} - Détails du jeu";
                break;
                
            default:
                $title_text = "Image de {$game_name} - Jeu indépendant";
        }
        
        // Ajouter description courte si disponible
        if ($description && strlen($title_text) < 80) {
            $short_desc = $this->extract_short_description($description, 40);
            if ($short_desc) {
                $title_text .= " - " . $short_desc;
            }
        }
        
        return $this->truncate_text($title_text, 150);
    }
    
    /**
     * Générer alt pour cover
     */
    private function generate_cover_alt($game_name, $genres) {
        $alt = "Cover de {$game_name}";
        
        if (!empty($genres)) {
            $genre_text = strtolower(implode(' ', $genres));
            $alt .= ", jeu {$genre_text}";
        }
        
        $alt .= " indépendant à découvrir";
        
        return $alt;
    }
    
    /**
     * Générer alt pour screenshot
     */
    private function generate_screenshot_alt($game_name, $genres) {
        $alt = "Screenshot de gameplay de {$game_name}";
        
        if (!empty($genres)) {
            $genre_text = strtolower(implode(' ', $genres));
            $alt .= " - Jeu {$genre_text}";
        }
        
        $alt .= " en action";
        
        return $alt;
    }
    
    /**
     * Générer alt pour thumbnail de trailer
     */
    private function generate_trailer_thumb_alt($game_name) {
        return "Miniature de la bande-annonce de {$game_name} - Cliquez pour regarder";
    }
    
    /**
     * Générer alt pour image de section
     */
    private function generate_section_alt($game_name, $attachment) {
        $section_title = get_the_title($attachment->ID);
        $section_title = $section_title ?: 'section du jeu';
        
        return "Image de {$game_name} - {$section_title}";
    }
    
    /**
     * Générer alt générique
     */
    private function generate_generic_alt($game_name, $genres) {
        $alt = "Image de {$game_name}";
        
        if (!empty($genres)) {
            $genre_text = strtolower(implode(' ', $genres));
            $alt .= " - Jeu {$genre_text}";
        }
        
        $alt .= " indépendant";
        
        return $alt;
    }
    
    /**
     * Optimiser une balise img dans le contenu
     */
    private function optimize_content_image_tag($img_tag, $game_data) {
        // Extraire les attributs existants
        preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $img_tag, $matches, PREG_SET_ORDER);
        
        $attributes = array();
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        
        // Optimiser alt si manquant ou générique
        if (empty($attributes['alt']) || $this->is_generic_alt($attributes['alt'])) {
            $attributes['alt'] = "Image de {$game_name} - Jeu indépendant";
        }
        
        // Ajouter title si manquant
        if (empty($attributes['title'])) {
            $attributes['title'] = "Découvrez {$game_name} en images";
        }
        
        // Ajouter lazy loading
        if (!isset($attributes['loading'])) {
            $attributes['loading'] = 'lazy';
        }
        
        // Ajouter decoding async
        if (!isset($attributes['decoding'])) {
            $attributes['decoding'] = 'async';
        }
        
        // Reconstruire la balise img
        $new_img_tag = '<img';
        foreach ($attributes as $attr => $value) {
            $new_img_tag .= ' ' . $attr . '="' . esc_attr($value) . '"';
        }
        $new_img_tag .= '>';
        
        return $new_img_tag;
    }
    
    /**
     * Générer le schéma de collection d'images
     */
    private function generate_image_collection_schema($game_data) {
        $images_data = array();
        
        // Cover principale
        $covers = $game_data[Sisme_Utils_Games::KEY_COVERS] ?? array();
        $main_cover_id = $covers[Sisme_Utils_Games::KEY_COVER_MAIN] ?? '';
        
        if ($main_cover_id) {
            $cover_data = $this->get_image_schema_data($main_cover_id, self::IMAGE_TYPE_COVER, $game_data);
            if ($cover_data) {
                $images_data[] = $cover_data;
            }
        }
        
        // Screenshots
        $screenshots = $game_data[Sisme_Utils_Games::KEY_SCREENSHOTS] ?? '';
        if ($screenshots) {
            $screenshot_ids = array_map('trim', explode(',', $screenshots));
            foreach ($screenshot_ids as $screenshot_id) {
                if ($screenshot_id) {
                    $screenshot_data = $this->get_image_schema_data($screenshot_id, self::IMAGE_TYPE_SCREENSHOT, $game_data);
                    if ($screenshot_data) {
                        $images_data[] = $screenshot_data;
                    }
                }
            }
        }
        
        if (empty($images_data)) {
            return false;
        }
        
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => 'Images de ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? ''),
            'description' => 'Galerie d\'images du jeu ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? ''),
            'image' => $images_data
        );
    }
    
    /**
     * Obtenir les données schema pour une image
     */
    private function get_image_schema_data($attachment_id, $image_type, $game_data) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'large');
        if (!$image_url) {
            return false;
        }
        
        $image_meta = wp_get_attachment_metadata($attachment_id);
        $attachment = get_post($attachment_id);
        
        $alt_text = $this->generate_optimized_alt($attachment, $game_data, $image_type);
        $title_text = $this->generate_optimized_title($attachment, $game_data, $image_type);
        
        return array(
            '@type' => 'ImageObject',
            'url' => $image_url,
            'name' => $title_text,
            'description' => $alt_text,
            'width' => $image_meta['width'] ?? null,
            'height' => $image_meta['height'] ?? null,
            'encodingFormat' => $this->get_image_mime_type($attachment_id),
            'contentUrl' => $image_url
        );
    }
    
    /**
     * Extraire les genres principaux
     */
    private function get_primary_genres($game_data, $max = 2) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        $genre_names = array();
        
        foreach ($genres as $genre) {
            if (isset($genre['name']) && !empty($genre['name'])) {
                $genre_names[] = ucfirst($genre['name']);
            }
            
            if (count($genre_names) >= $max) {
                break;
            }
        }
        
        return $genre_names;
    }
    
    /**
     * Vérifier si l'alt est générique
     */
    private function is_generic_alt($alt) {
        $generic_patterns = array(
            'image',
            'photo',
            'picture',
            'img',
            'untitled',
            'screenshot',
            'capture'
        );
        
        $alt_lower = strtolower($alt);
        
        foreach ($generic_patterns as $pattern) {
            if ($alt_lower === $pattern || strpos($alt_lower, $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extraire une description courte
     */
    private function extract_short_description($description, $max_length) {
        $clean_desc = strip_tags($description);
        $clean_desc = preg_replace('/\s+/', ' ', $clean_desc);
        
        if (strlen($clean_desc) <= $max_length) {
            return $clean_desc;
        }
        
        $truncated = substr($clean_desc, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . '...';
    }
    
    /**
     * Tronquer du texte
     */
    private function truncate_text($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        return substr($text, 0, $max_length - 3) . '...';
    }
    
    /**
     * Obtenir le type MIME d'une image
     */
    private function get_image_mime_type($attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        return $mime_type ?: 'image/jpeg';
    }
    
    /**
     * Hooks personnalisés pour générer alt et title
     */
    public function generate_game_image_alt($alt, $attachment_id, $context) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $alt;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $alt;
        }
        
        $attachment = get_post($attachment_id);
        $image_type = $this->determine_image_type($attachment_id, $game_data);
        
        return $this->generate_optimized_alt($attachment, $game_data, $image_type);
    }
    
    public function generate_game_image_title($title, $attachment_id, $context) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $title;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $title;
        }
        
        $attachment = get_post($attachment_id);
        $image_type = $this->determine_image_type($attachment_id, $game_data);
        
        return $this->generate_optimized_title($attachment, $game_data, $image_type);
    }
    
    /**
     * Nettoyer le cache
     */
    public function clear_cache_on_save($post_id) {
        if (Sisme_SEO_Loader::is_game_page($post_id)) {
            // Nettoyer le cache de toutes les images liées
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sisme_image_alt_%'");
        }
    }
    
    /**
     * Nettoyer le cache d'une image spécifique
     */
    public function clear_image_cache($attachment_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_sisme_image_alt_' . $attachment_id . '_%'
        ));
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_image_alt_%'"
        );
        
        return array(
            'module' => 'Images SEO',
            'active_caches' => intval($cache_count),
            'cache_duration' => self::CACHE_DURATION,
            'supported_image_types' => array(
                self::IMAGE_TYPE_COVER,
                self::IMAGE_TYPE_SCREENSHOT,
                self::IMAGE_TYPE_TRAILER_THUMB,
                self::IMAGE_TYPE_SECTION
            )
        );
    }
    
    /**
     * Analyser les images d'une page pour les métriques SEO
     */
    public function analyze_page_images($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        
        if (!Sisme_SEO_Loader::is_game_page($post_id)) {
            return false;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data($post_id);
        if (!$game_data) {
            return false;
        }
        
        $analysis = array(
            'total_images' => 0,
            'optimized_images' => 0,
            'missing_alt' => 0,
            'generic_alt' => 0,
            'image_types' => array()
        );
        
        // Analyser cover
        $covers = $game_data[Sisme_Utils_Games::KEY_COVERS] ?? array();
        foreach ($covers as $cover_type => $cover_id) {
            if ($cover_id) {
                $analysis['total_images']++;
                $analysis['image_types'][self::IMAGE_TYPE_COVER]++;
                
                $alt = get_post_meta($cover_id, '_wp_attachment_image_alt', true);
                if (empty($alt)) {
                    $analysis['missing_alt']++;
                } elseif ($this->is_generic_alt($alt)) {
                    $analysis['generic_alt']++;
                } else {
                    $analysis['optimized_images']++;
                }
            }
        }
        
        // Analyser screenshots
        $screenshots = $game_data[Sisme_Utils_Games::KEY_SCREENSHOTS] ?? '';
        if ($screenshots) {
            $screenshot_ids = array_map('trim', explode(',', $screenshots));
            foreach ($screenshot_ids as $screenshot_id) {
                if ($screenshot_id) {
                    $analysis['total_images']++;
                    $analysis['image_types'][self::IMAGE_TYPE_SCREENSHOT]++;
                    
                    $alt = get_post_meta($screenshot_id, '_wp_attachment_image_alt', true);
                    if (empty($alt)) {
                        $analysis['missing_alt']++;
                    } elseif ($this->is_generic_alt($alt)) {
                        $analysis['generic_alt']++;
                    } else {
                        $analysis['optimized_images']++;
                    }
                }
            }
        }
        
        // Calculer le score d'optimisation
        if ($analysis['total_images'] > 0) {
            $analysis['optimization_score'] = round(
                ($analysis['optimized_images'] / $analysis['total_images']) * 100
            );
        } else {
            $analysis['optimization_score'] = 0;
        }
        
        return $analysis;
    }
}