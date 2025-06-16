<?php
/**
 * File: /sisme-games-editor/includes/seo-enhancements.php
 * Plugin: Sisme Games Editor
 * Author: Sisme Games
 * Description: Améliorations SEO spécifiques pour les fiches de jeu
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Enhancements {
    
    public function __construct() {
        add_action('wp', array($this, 'disable_aioseo_for_game_fiches'));
        add_action('wp_head', array($this, 'add_game_structured_data'), 1);
        add_filter('document_title_parts', array($this, 'optimize_title_for_games'), 20);
        add_action('wp_head', array($this, 'add_game_meta_tags'), 1);
        add_action('wp_head', array($this, 'add_open_graph_gaming'), 1);
        add_filter('aioseo_title', array($this, 'override_aioseo_title'), 999);
        add_filter('aioseo_description', array($this, 'override_aioseo_description'), 999);
        add_filter('aioseo_og_title', array($this, 'override_aioseo_og_title'), 999);
        add_filter('aioseo_og_description', array($this, 'override_aioseo_og_description'), 999);
    }
    
    public function disable_aioseo_for_game_fiches() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        if (function_exists('aioseo')) {
            remove_action('wp_head', array(aioseo()->meta->tags, 'output'), 1);
            remove_action('wp_head', array(aioseo()->meta->links, 'output'), 2);
        }
        
        if (class_exists('WPSEO_Frontend')) {
            $wpseo_front = WPSEO_Frontend::get_instance();
            remove_action('wp_head', array($wpseo_front, 'head'), 1);
        }
        
        if (class_exists('RankMath\Frontend\Frontend')) {
            remove_all_actions('rank_math/head');
        }
    }
    
    public function override_aioseo_title($title) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $title;
        }
        
        return get_the_title($post->ID) . ' - Jeu Indépendant | Sisme Games';
    }
    
    public function override_aioseo_description($description) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $description;
        }
        
        $excerpt = get_the_excerpt($post->ID);
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(get_post_field('post_content', $post->ID), 25);
        }
        return 'Découvrez ' . get_the_title($post->ID) . ', un jeu indépendant. ' . $excerpt;
    }
    
    public function override_aioseo_og_title($title) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $title;
        }
        
        return get_the_title($post->ID) . ' - Jeu Indépendant';
    }
    
    public function override_aioseo_og_description($description) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $description;
        }
        
        return get_the_excerpt($post->ID) ?: wp_trim_words(get_post_field('post_content', $post->ID), 25);
    }
    
    public function add_game_structured_data() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        $game_modes = get_post_meta($post_id, '_sisme_game_modes', true) ?: array();
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        $developers = get_post_meta($post_id, '_sisme_developers', true) ?: array();
        $editors = get_post_meta($post_id, '_sisme_editors', true) ?: array();
        $trailer_url = get_post_meta($post_id, '_sisme_trailer_url', true);
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Organization',
                'name' => 'Sisme Games',
                'url' => home_url()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => 'Sisme Games',
                'url' => home_url(),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(512) ?: (get_template_directory_uri() . '/assets/images/logo.png')
                )
            )
        );
        
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            $structured_data['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2]
            );
        }
        
        if (!empty($platforms)) {
            $platform_mapping = array(
                'pc' => 'PC',
                'mac' => 'macOS',
                'xbox' => 'Xbox',
                'playstation' => 'PlayStation',
                'switch' => 'Nintendo Switch'
            );
            
            $structured_data['gamePlatform'] = array();
            foreach ($platforms as $platform) {
                $structured_data['gamePlatform'][] = $platform_mapping[$platform] ?? ucfirst($platform);
            }
        }
        
        if (!empty($release_date)) {
            $structured_data['datePublished'] = $release_date;
        }
        
        if (!empty($developers)) {
            $game_developers = array();
            foreach ($developers as $dev) {
                if (is_array($dev) && !empty($dev['name'])) {
                    $developer = array(
                        '@type' => 'Organization',
                        'name' => $dev['name']
                    );
                    if (!empty($dev['url'])) {
                        $developer['url'] = $dev['url'];
                    }
                    $game_developers[] = $developer;
                }
            }
            
            if (!empty($game_developers)) {
                $structured_data['creator'] = count($game_developers) === 1 ? $game_developers[0] : $game_developers;
            }
        }
        
        if (!empty($editors)) {
            $game_publishers = array();
            foreach ($editors as $editor) {
                if (is_array($editor) && !empty($editor['name'])) {
                    $pub = array(
                        '@type' => 'Organization',
                        'name' => $editor['name']
                    );
                    if (!empty($editor['url'])) {
                        $pub['url'] = $editor['url'];
                    }
                    $game_publishers[] = $pub;
                }
            }
            
            if (!empty($game_publishers)) {
                $structured_data['publisher'] = array(
                    $structured_data['publisher'],
                    count($game_publishers) === 1 ? $game_publishers[0] : $game_publishers
                );
            }
        }
        
        if (!empty($trailer_url)) {
            $video_id = $this->get_youtube_video_id($trailer_url);
            
            if ($video_id) {
                $thumbnail_url = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
                
                $structured_data['trailer'] = array(
                    '@type' => 'VideoObject',
                    'name' => get_the_title() . ' - Trailer',
                    'description' => 'Trailer officiel de ' . get_the_title(),
                    'thumbnailUrl' => $thumbnail_url,
                    'contentUrl' => $trailer_url,
                    'embedUrl' => 'https://www.youtube.com/embed/' . $video_id,
                    'uploadDate' => get_the_date('c'),
                    'duration' => 'PT2M30S',
                    'author' => array(
                        '@type' => 'Organization',
                        'name' => 'Sisme Games',
                        'url' => home_url()
                    ),
                    'publisher' => array(
                        '@type' => 'Organization',
                        'name' => 'YouTube',
                        'url' => 'https://www.youtube.com'
                    )
                );
            } else {
                $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
                $thumbnail_fallback = $featured_image ? $featured_image[0] : get_site_icon_url(512);
                
                $structured_data['trailer'] = array(
                    '@type' => 'VideoObject',
                    'name' => get_the_title() . ' - Trailer',
                    'description' => 'Trailer officiel de ' . get_the_title(),
                    'thumbnailUrl' => $thumbnail_fallback,
                    'contentUrl' => $trailer_url,
                    'uploadDate' => get_the_date('c'),
                    'author' => array(
                        '@type' => 'Organization',
                        'name' => 'Sisme Games',
                        'url' => home_url()
                    )
                );
            }
        }
        
        $categories = get_the_category();
        $game_genres = array();
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $game_genres[] = str_replace('jeux-', '', $category->name);
            }
        }
        if (!empty($game_genres)) {
            $structured_data['genre'] = $game_genres;
        }
        
        $gaming_keywords = array(
            'jeu indépendant',
            'indie game',
            'fiche de jeu',
            'présentation de jeu',
            'information de jeu'
        );
        
        if (!empty($game_genres)) {
            $gaming_keywords = array_merge($gaming_keywords, $game_genres);
        }
        
        if (!empty($platforms)) {
            $gaming_keywords = array_merge($gaming_keywords, $platforms);
        }
        
        $structured_data['keywords'] = implode(', ', $gaming_keywords);
        
        echo '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        
        $this->add_article_structured_data($post_id);
    }
    
    private function add_article_structured_data($post_id) {
        $article_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
            'author' => array(
                '@type' => 'Organization',
                'name' => 'Sisme Games',
                'url' => home_url()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => 'Sisme Games',
                'url' => home_url(),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(512) ?: (get_template_directory_uri() . '/assets/images/logo.png')
                )
            ),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'url' => get_permalink(),
            'mainEntityOfPage' => get_permalink(),
            'articleSection' => 'Jeux Vidéo',
            'keywords' => array(
                'jeu indépendant',
                'indie game',
                'fiche de jeu',
                'présentation de jeu'
            )
        );
        
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            $article_data['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2]
            );
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($article_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    private function get_youtube_video_id($url) {
        if (empty($url)) {
            return false;
        }
        
        $patterns = array(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/',
            '/youtube\.com\/v\/([^&\n?#]+)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    public function optimize_title_for_games($title_parts) {
        if (!is_single() || !$this->is_game_fiche()) {
            return $title_parts;
        }
        
        $title_parts['title'] = $title_parts['title'] . ' - Jeu Indépendant';
        $title_parts['tagline'] = 'Fiche de Jeu Indé - Sisme Games';
        
        return $title_parts;
    }
    
    public function add_game_meta_tags() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        if (function_exists('aioseo') && !$this->should_override_seo()) {
            return;
        }
        
        if (!$this->meta_tag_exists('keywords')) {
            $categories = get_the_category();
            $keywords = array('jeu indépendant', 'indie game', 'gaming');
            
            foreach ($categories as $category) {
                if (strpos($category->slug, 'jeux-') === 0) {
                    $keywords[] = str_replace('jeux-', '', $category->name);
                }
            }
            
            $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
            foreach ($platforms as $platform) {
                $keywords[] = $platform;
            }
            
            echo '<meta name="keywords" content="' . esc_attr(implode(', ', $keywords)) . '">' . "\n";
        }
        
        if (!$this->meta_tag_exists('description')) {
            $description = get_the_excerpt();
            if (empty($description)) {
                $description = wp_trim_words(get_the_content(), 25);
            }
            $description = 'Découvrez ' . get_the_title() . ', un jeu indépendant. ' . $description;
            
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
    }
    
    public function add_open_graph_gaming() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        if (function_exists('aioseo') && !$this->should_override_seo()) {
            $this->add_gaming_specific_og_tags($post_id);
            return;
        }
        
        echo '<meta property="og:type" content="video.game">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_the_title() . ' - Jeu Indépendant') . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(get_the_excerpt()) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        echo '<meta property="og:site_name" content="Sisme Games">' . "\n";
        
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            echo '<meta property="og:image" content="' . esc_url($image[0]) . '">' . "\n";
            echo '<meta property="og:image:width" content="' . esc_attr($image[1]) . '">' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr($image[2]) . '">' . "\n";
        }
        
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(get_the_title() . ' - Jeu Indépendant') . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(get_the_excerpt()) . '">' . "\n";
        
        $this->add_gaming_specific_og_tags($post_id);
    }
    
    private function add_gaming_specific_og_tags($post_id) {
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        if (!empty($platforms)) {
            echo '<meta property="game:platform" content="' . esc_attr(implode(', ', $platforms)) . '">' . "\n";
        }
        
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        if (!empty($release_date)) {
            echo '<meta property="game:release_date" content="' . esc_attr($release_date) . '">' . "\n";
        }
        
        echo '<meta property="og:type" content="video.game">' . "\n";
    }
    
    private function should_override_seo() {
        return get_option('sisme_override_seo_for_games', true);
    }
    
    private function meta_tag_exists($name) {
        return false;
    }
    
    private function is_game_fiche($post_id = null) {
        if (!$post_id) {
            global $post;
            if (!$post) {
                return false;
            }
            $post_id = $post->ID;
        }
        
        $game_modes = get_post_meta($post_id, '_sisme_game_modes', true);
        return !empty($game_modes);
    }
}