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
        $this->init_news_seo_hooks();
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
            if (strpos($category->slug ?? '', 'jeux-') === 0) {
                $game_genres[] = str_replace('jeux-', '', $category->name ?? '');
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
                if (strpos($category->slug ?? '', 'jeux-') === 0) {
                    $keywords[] = str_replace('jeux-', '', $category->name ?? '');
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

    private function init_news_seo_hooks() {
        // Override SEO pour pages news
        add_action('wp_head', array($this, 'render_news_seo_meta_tags'), 1);
        
        // Filtres pour neutraliser les plugins SEO sur les pages news
        add_filter('wpseo_title', array($this, 'override_yoast_news_title'), 999);
        add_filter('wpseo_metadesc', array($this, 'override_yoast_news_description'), 999);
        add_filter('wpseo_opengraph_title', array($this, 'override_yoast_news_og_title'), 999);
        add_filter('wpseo_opengraph_desc', array($this, 'override_yoast_news_og_description'), 999);
        
        // RankMath
        add_filter('rank_math/frontend/title', array($this, 'override_rankmath_news_title'), 999);
        add_filter('rank_math/frontend/description', array($this, 'override_rankmath_news_description'), 999);
        
        // AIOSEO
        add_filter('aioseo_title', array($this, 'override_aioseo_news_title'), 999);
        add_filter('aioseo_description', array($this, 'override_aioseo_news_description'), 999);
        
        // SEOPress
        add_filter('seopress_titles_title', array($this, 'override_seopress_news_title'), 999);
        add_filter('seopress_titles_desc', array($this, 'override_seopress_news_description'), 999);
    }

    /**
     * 🎯 Détecter si on est sur une page news
     */
    private function is_news_page() {
        if (!is_single()) {
            return false;
        }
        
        global $post;
        
        // Méthode 1: Métadonnée explicite
        if (get_post_meta($post->ID, '_sisme_is_news_page', true)) {
            return true;
        }
        
        // Méthode 2: Pattern du titre
        if (preg_match('/^(.+?)\s*:\s*(News|Actualités?|Patch)$/i', $post->post_title)) {
            return true;
        }
        
        // Méthode 3: Catégorie page-news
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            if ($category->slug === 'page-news') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 🏗️ Générer les métadonnées SEO complètes pour une page news
     */
    private function generate_news_seo_data($post_id) {
        $game_name = get_post_meta($post_id, '_sisme_game_name', true);
        $parent_fiche_id = get_post_meta($post_id, '_sisme_parent_fiche_id', true);
        
        // Fallback si pas de game_name
        if (!$game_name) {
            global $post;
            if (preg_match('/^(.+?)\s*:\s*(News|Actualités?)$/i', $post->post_title, $matches)) {
                $game_name = trim($matches[1]);
            } else {
                $game_name = 'Ce jeu';
            }
        }
        
        // Récupérer les données de la fiche parente pour enrichir le SEO
        $platforms = array();
        $categories = array();
        $game_modes = array();
        
        if ($parent_fiche_id) {
            $plat = get_post_meta($parent_fiche_id, '_sisme_platforms', true) ?: array();
            $modes = get_post_meta($parent_fiche_id, '_sisme_game_modes', true) ?: array();
            $cats = get_the_category($parent_fiche_id);
            
            if (!empty($plat)) {
                $platform_names = array(
                    'pc' => 'PC', 'mac' => 'Mac', 'xbox' => 'Xbox', 
                    'playstation' => 'PlayStation', 'switch' => 'Nintendo Switch'
                );
                foreach ($plat as $p) {
                    $platforms[] = $platform_names[$p] ?? ucfirst($p);
                }
            }
            
            if (!empty($modes)) {
                $game_modes = array_map('ucfirst', $modes);
            }
            
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    if (strpos($cat->slug ?? '', 'jeux-') === 0) {
                        $categories[] = str_replace('jeux-', '', $cat->name ?? '');
                    }
                }
            }
        }
        
        // Construction du titre SEO optimisé
        $seo_title = "Actualités {$game_name} : News, Patches et Guides";
        if (!empty($platforms)) {
            $main_platform = $platforms[0];
            $seo_title = "Actualités {$game_name} {$main_platform} : News, Patches et Guides";
        }
        $seo_title .= " | Sisme Games";
        
        // Construction de la description SEO enrichie
        $description = "Découvrez toute l'actualité de {$game_name}";
        
        if (!empty($categories)) {
            $description .= ", jeu " . strtolower($categories[0]);
        }
        
        if (!empty($platforms)) {
            $description .= " disponible sur " . implode(', ', $platforms);
        }
        
        $description .= ". News, patches, guides et analyses détaillées";
        
        if (!empty($game_modes)) {
            $description .= ". Expérience " . strtolower(implode(' et ', $game_modes));
        }
        
        $description .= " par l'équipe Sisme Games. Restez informés des dernières mises à jour !";
        
        // Keywords SEO
        $keywords = array($game_name, 'actualités', 'news', 'patch notes', 'guides');
        
        if (!empty($categories)) {
            $keywords = array_merge($keywords, $categories);
        }
        
        if (!empty($platforms)) {
            $keywords = array_merge($keywords, $platforms);
        }
        
        $keywords = array_merge($keywords, array('Sisme Games', 'test jeu', 'critique gaming', 'jeux indépendants'));
        
        // Image par défaut ou depuis la fiche
        $image_url = '';
        if ($parent_fiche_id && has_post_thumbnail($parent_fiche_id)) {
            $image_url = get_the_post_thumbnail_url($parent_fiche_id, 'large');
        } elseif (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'large');
        } else {
            $image_url = 'https://games.sisme.fr/wp-content/uploads/2025/06/sisme-games-news-default.webp';
        }
        
        return array(
            'title' => $seo_title,
            'description' => $description,
            'keywords' => implode(', ', array_unique($keywords)),
            'image' => $image_url,
            'game_name' => $game_name,
            'platforms' => $platforms,
            'categories' => $categories,
            'parent_fiche_id' => $parent_fiche_id
        );
    }

    /**
     * 🎯 Rendre les balises meta SEO pour les pages news
     */
    public function render_news_seo_meta_tags() {
        if (!$this->is_news_page()) {
            return;
        }
        
        global $post;
        $seo_data = $this->generate_news_seo_data($post->ID);
        $current_url = get_permalink($post->ID);
        
        // Empêcher les autres plugins de générer leurs balises
        remove_action('wp_head', 'wp_generator');
        
        echo "\n<!-- Sisme Games - SEO Meta Tags pour Pages News -->\n";
        
        // Title (sera aussi utilisé par les filtres de titre)
        echo '<title>' . esc_html($seo_data['title']) . '</title>' . "\n";
        
        // Meta description
        echo '<meta name="description" content="' . esc_attr($seo_data['description']) . '">' . "\n";
        
        // Meta keywords
        echo '<meta name="keywords" content="' . esc_attr($seo_data['keywords']) . '">' . "\n";
        
        // Meta robots optimisé pour les pages news
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
        
        // Canonical URL
        echo '<link rel="canonical" href="' . esc_url($current_url) . '">' . "\n";
        
        // Open Graph pour réseaux sociaux
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($seo_data['title']) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($seo_data['description']) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($current_url) . '">' . "\n";
        echo '<meta property="og:site_name" content="Sisme Games">' . "\n";
        echo '<meta property="og:locale" content="fr_FR">' . "\n";
        
        if ($seo_data['image']) {
            echo '<meta property="og:image" content="' . esc_url($seo_data['image']) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
            echo '<meta property="og:image:alt" content="Actualités ' . esc_attr($seo_data['game_name']) . ' - Sisme Games">' . "\n";
        }
        
        // Twitter Cards
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:site" content="@SismeGames">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($seo_data['title']) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($seo_data['description']) . '">' . "\n";
        
        if ($seo_data['image']) {
            echo '<meta name="twitter:image" content="' . esc_url($seo_data['image']) . '">' . "\n";
            echo '<meta name="twitter:image:alt" content="Actualités ' . esc_attr($seo_data['game_name']) . '">' . "\n";
        }
        
        // Article meta tags
        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post->ID)) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post->ID)) . '">' . "\n";
        echo '<meta property="article:author" content="Sisme Games">' . "\n";
        echo '<meta property="article:publisher" content="https://games.sisme.fr">' . "\n";
        echo '<meta property="article:section" content="Gaming News">' . "\n";
        
        // Tags de l'article
        $post_tags = get_the_tags($post->ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n";
            }
        }
        
        // Schema.org JSON-LD
        $this->render_news_schema_jsonld($seo_data, $post);
        
        echo "<!-- /Sisme Games SEO -->\n\n";
    }

    /**
     * 📊 Générer le Schema.org JSON-LD pour les pages news
     */
    private function render_news_schema_jsonld($seo_data, $post) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => get_permalink($post->ID) . '#webpage',
            'url' => get_permalink($post->ID),
            'name' => $seo_data['title'],
            'description' => $seo_data['description'],
            'inLanguage' => 'fr-FR',
            'isPartOf' => array(
                '@type' => 'WebSite',
                '@id' => home_url() . '#website',
                'url' => home_url(),
                'name' => 'Sisme Games',
                'description' => 'Tests et actualités jeux vidéo indépendants',
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => 'Sisme Games',
                    'url' => home_url(),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => 'https://games.sisme.fr/wp-content/uploads/2025/06/sisme-games-logo.webp'
                    ),
                    'sameAs' => array(
                        'https://twitter.com/SismeGames'
                    )
                )
            ),
            'primaryImageOfPage' => array(
                '@type' => 'ImageObject',
                'url' => $seo_data['image'],
                'width' => 1200,
                'height' => 630
            ),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'about' => array(
                '@type' => 'VideoGame',
                'name' => $seo_data['game_name'],
                'applicationCategory' => 'Game',
                'operatingSystem' => implode(', ', $seo_data['platforms'])
            ),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'name' => 'Actualités ' . $seo_data['game_name'],
                'description' => 'Collection d\'articles et actualités sur ' . $seo_data['game_name'],
                'numberOfItems' => 0 // Sera calculé dynamiquement
            )
        );
        
        // Ajouter les articles news liés
        if ($seo_data['parent_fiche_id']) {
            $news_articles = $this->get_related_news_articles($seo_data['parent_fiche_id'], $post->ID);
            if (!empty($news_articles)) {
                $schema['mainEntity']['numberOfItems'] = count($news_articles);
                $schema['mainEntity']['itemListElement'] = array();
                
                foreach ($news_articles as $index => $article) {
                    $schema['mainEntity']['itemListElement'][] = array(
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => array(
                            '@type' => 'Article',
                            '@id' => get_permalink($article->ID),
                            'url' => get_permalink($article->ID),
                            'headline' => $article->post_title,
                            'datePublished' => get_the_date('c', $article->ID),
                            'dateModified' => get_the_modified_date('c', $article->ID),
                            'author' => array(
                                '@type' => 'Organization',
                                'name' => 'Sisme Games'
                            )
                        )
                    );
                }
            }
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n" . '</script>' . "\n";
    }

    /**
     * 🔗 Récupérer les articles news liés pour le Schema
     */
    private function get_related_news_articles($parent_fiche_id, $exclude_id) {
        $main_tag_id = get_post_meta($parent_fiche_id, '_sisme_main_tag', true);
        
        if (!$main_tag_id) {
            return array();
        }
        
        $news_category = get_category_by_slug('news');
        if (!$news_category) {
            return array();
        }
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'post__not_in' => array($exclude_id),
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $news_category->term_id
                ),
                array(
                    'taxonomy' => 'post_tag',
                    'field' => 'term_id',
                    'terms' => $main_tag_id
                )
            )
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * 🚫 FILTRES POUR OVERRIDE LES PLUGINS SEO
     */

    // Yoast SEO
    public function override_yoast_news_title($title) {
        if ($this->is_news_page()) {
            global $post;
            $seo_data = $this->generate_news_seo_data($post->ID);
            return $seo_data['title'];
        }
        return $title;
    }

    public function override_yoast_news_description($description) {
        if ($this->is_news_page()) {
            global $post;
            $seo_data = $this->generate_news_seo_data($post->ID);
            return $seo_data['description'];
        }
        return $description;
    }

    public function override_yoast_news_og_title($title) {
        return $this->override_yoast_news_title($title);
    }

    public function override_yoast_news_og_description($description) {
        return $this->override_yoast_news_description($description);
    }

    // RankMath
    public function override_rankmath_news_title($title) {
        return $this->override_yoast_news_title($title);
    }

    public function override_rankmath_news_description($description) {
        return $this->override_yoast_news_description($description);
    }

    // AIOSEO
    public function override_aioseo_news_title($title) {
        return $this->override_yoast_news_title($title);
    }

    public function override_aioseo_news_description($description) {
        return $this->override_yoast_news_description($description);
    }

    // SEOPress
    public function override_seopress_news_title($title) {
        return $this->override_yoast_news_title($title);
    }

    public function override_seopress_news_description($description) {
        return $this->override_yoast_news_description($description);
    }
}