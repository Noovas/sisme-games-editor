<?php
/**
 * File: /sisme-games-editor/includes/seo-enhancements.php
 * Améliorations SEO spécifiques pour les fiches de jeu
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Enhancements {
    
    public function __construct() {
        // Désactiver All in One SEO pour les fiches de jeu
        add_action('wp', array($this, 'disable_aioseo_for_game_fiches'));
        
        // Nos propres hooks SEO
        add_action('wp_head', array($this, 'add_game_structured_data'), 1);
        add_filter('document_title_parts', array($this, 'optimize_title_for_games'), 20);
        add_action('wp_head', array($this, 'add_game_meta_tags'), 1);
        add_action('wp_head', array($this, 'add_open_graph_gaming'), 1);
        
        // Filtres spécifiques All in One SEO - VERSION CORRIGÉE
        add_filter('aioseo_title', array($this, 'override_aioseo_title'), 999);
        add_filter('aioseo_description', array($this, 'override_aioseo_description'), 999);
        add_filter('aioseo_og_title', array($this, 'override_aioseo_og_title'), 999);
        add_filter('aioseo_og_description', array($this, 'override_aioseo_og_description'), 999);
    }
    
    /**
     * Désactiver All in One SEO pour les fiches de jeu
     */
    public function disable_aioseo_for_game_fiches() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        // Désactiver les meta tags All in One SEO
        if (function_exists('aioseo')) {
            remove_action('wp_head', array(aioseo()->meta->tags, 'output'), 1);
            remove_action('wp_head', array(aioseo()->meta->links, 'output'), 2);
        }
        
        // Désactiver aussi d'autres plugins SEO populaires si présents
        if (class_exists('WPSEO_Frontend')) {
            $wpseo_front = WPSEO_Frontend::get_instance();
            remove_action('wp_head', array($wpseo_front, 'head'), 1);
        }
        
        if (class_exists('RankMath\Frontend\Frontend')) {
            remove_all_actions('rank_math/head');
        }
    }
    
    /**
     * Override titre All in One SEO - VERSION SIMPLIFIÉE
     */
    public function override_aioseo_title($title) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $title;
        }
        
        return get_the_title($post->ID) . ' - Jeu Indépendant | Sisme Games';
    }
    
    /**
     * Override description All in One SEO - VERSION SIMPLIFIÉE
     */
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
    
    /**
     * Override Open Graph titre - VERSION SIMPLIFIÉE
     */
    public function override_aioseo_og_title($title) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $title;
        }
        
        return get_the_title($post->ID) . ' - Jeu Indépendant';
    }
    
    /**
     * Override Open Graph description - VERSION SIMPLIFIÉE
     */
    public function override_aioseo_og_description($description) {
        global $post;
        
        if (!$post || !is_single() || !$this->is_game_fiche($post->ID)) {
            return $description;
        }
        
        return get_the_excerpt($post->ID) ?: wp_trim_words(get_post_field('post_content', $post->ID), 25);
    }
    
    /**
     * Ajouter les données structurées JSON-LD pour les jeux
     */
    public function add_game_structured_data() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        // Récupérer les métadonnées
        $game_modes = get_post_meta($post_id, '_sisme_game_modes', true) ?: array();
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        $developers = get_post_meta($post_id, '_sisme_developers', true) ?: array();
        $editors = get_post_meta($post_id, '_sisme_editors', true) ?: array();
        $trailer_url = get_post_meta($post_id, '_sisme_trailer_url', true);
        
        // Construire les données structurées
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
        
        // Ajouter l'image si disponible
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            $structured_data['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2]
            );
        }
        
        // Ajouter les plateformes comme gamePlatform
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
        
        // Ajouter la date de sortie
        if (!empty($release_date)) {
            $structured_data['datePublished'] = $release_date;
        }
        
        // Ajouter les développeurs
        if (!empty($developers)) {
            $structured_data['developer'] = array();
            foreach ($developers as $dev) {
                if (is_array($dev) && !empty($dev['name'])) {
                    $developer = array(
                        '@type' => 'Organization',
                        'name' => $dev['name']
                    );
                    if (!empty($dev['url'])) {
                        $developer['url'] = $dev['url'];
                    }
                    $structured_data['developer'][] = $developer;
                }
            }
        }
        
        // Ajouter les éditeurs
        if (!empty($editors)) {
            $structured_data['publisher'] = array();
            foreach ($editors as $editor) {
                if (is_array($editor) && !empty($editor['name'])) {
                    $pub = array(
                        '@type' => 'Organization',
                        'name' => $editor['name']
                    );
                    if (!empty($editor['url'])) {
                        $pub['url'] = $editor['url'];
                    }
                    $structured_data['publisher'][] = $pub;
                }
            }
        }
        
        // Ajouter le trailer comme VideoObject
        if (!empty($trailer_url)) {
            $structured_data['trailer'] = array(
                '@type' => 'VideoObject',
                'name' => get_the_title() . ' - Trailer',
                'description' => 'Trailer officiel de ' . get_the_title(),
                'embedUrl' => $this->convert_youtube_to_embed($trailer_url),
                'url' => $trailer_url,
                'uploadDate' => get_the_date('c')
            );
        }
        
        // Ajouter les catégories comme genre
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
        
        // Ajouter une section "review" pour améliorer le SEO
        $structured_data['review'] = array(
            '@type' => 'Review',
            'reviewBody' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 50),
            'author' => array(
                '@type' => 'Organization',
                'name' => 'Sisme Games',
                'url' => home_url()
            ),
            'datePublished' => get_the_date('c'),
            'reviewRating' => array(
                '@type' => 'Rating',
                'ratingValue' => '4.5',
                'bestRating' => '5',
                'worstRating' => '1'
            )
        );
        
        // Ajouter des liens vers test et news pour le référencement
        $structured_data['relatedLink'] = array(
            array(
                '@type' => 'WebPage',
                'name' => 'Test de ' . get_the_title(),
                'description' => 'Test complet et avis détaillé sur ' . get_the_title(),
                'url' => get_permalink() . '#test'
            ),
            array(
                '@type' => 'WebPage', 
                'name' => 'News de ' . get_the_title(),
                'description' => 'Actualités et mises à jour pour ' . get_the_title(),
                'url' => get_permalink() . '#news'
            )
        );
        
        // Ajouter les mots-clés gaming pour le SEO
        $gaming_keywords = array(
            'jeu indépendant',
            'indie game',
            'test jeu vidéo',
            'avis gaming',
            'critique jeu'
        );
        
        // Ajouter les genres comme mots-clés
        if (!empty($game_genres)) {
            $gaming_keywords = array_merge($gaming_keywords, $game_genres);
        }
        
        // Ajouter les plateformes comme mots-clés
        if (!empty($platforms)) {
            $gaming_keywords = array_merge($gaming_keywords, $platforms);
        }
        
        $structured_data['keywords'] = implode(', ', $gaming_keywords);
        
        echo '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Optimiser le titre pour le SEO gaming
     */
    public function optimize_title_for_games($title_parts) {
        if (!is_single() || !$this->is_game_fiche()) {
            return $title_parts;
        }
        
        global $post;
        
        // Ajouter "Jeu Indépendant" au titre pour le SEO
        $title_parts['title'] = $title_parts['title'] . ' - Jeu Indépendant';
        
        // Personnaliser le tagline si c'est une fiche
        $title_parts['tagline'] = 'Fiche de Jeu Indé - Sisme Games';
        
        return $title_parts;
    }
    
    /**
     * Ajouter les meta tags spécifiques aux jeux (seulement si pas de conflit)
     */
    public function add_game_meta_tags() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        // Vérifier qu'All in One SEO n'a pas déjà ajouté ses tags
        if (function_exists('aioseo') && !$this->should_override_seo()) {
            return;
        }
        
        // Meta keywords gaming (seulement si pas géré par AIOSEO)
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
        
        // Meta description optimisée (seulement si pas géré par AIOSEO)
        if (!$this->meta_tag_exists('description')) {
            $description = get_the_excerpt();
            if (empty($description)) {
                $description = wp_trim_words(get_the_content(), 25);
            }
            $description = 'Découvrez ' . get_the_title() . ', un jeu indépendant. ' . $description;
            
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        // Meta robots pour indexation optimale
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
    }
    
    /**
     * Ajouter les Open Graph tags pour les réseaux sociaux
     */
    public function add_open_graph_gaming() {
        if (!is_single() || !$this->is_game_fiche()) {
            return;
        }
        
        global $post;
        $post_id = $post->ID;
        
        // Vérifier qu'All in One SEO n'a pas déjà ajouté ses OG tags
        if (function_exists('aioseo') && !$this->should_override_seo()) {
            // Ajouter seulement les tags gaming spécifiques
            $this->add_gaming_specific_og_tags($post_id);
            return;
        }
        
        // Ajouter tous les tags OG si on override complètement
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
        
        // Twitter Cards
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(get_the_title() . ' - Jeu Indépendant') . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(get_the_excerpt()) . '">' . "\n";
        
        // Tags gaming spécifiques
        $this->add_gaming_specific_og_tags($post_id);
    }
    
    /**
     * Ajouter uniquement les tags gaming spécifiques
     */
    private function add_gaming_specific_og_tags($post_id) {
        // Gaming specific meta
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        if (!empty($platforms)) {
            echo '<meta property="game:platform" content="' . esc_attr(implode(', ', $platforms)) . '">' . "\n";
        }
        
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        if (!empty($release_date)) {
            echo '<meta property="game:release_date" content="' . esc_attr($release_date) . '">' . "\n";
        }
        
        // Forcer le type video.game même si AIOSEO est actif
        echo '<meta property="og:type" content="video.game">' . "\n";
    }
    
    /**
     * Vérifier si on doit override complètement le SEO
     */
    private function should_override_seo() {
        // Option pour forcer l'override (à ajouter dans les réglages du plugin)
        return get_option('sisme_override_seo_for_games', true);
    }
    
    /**
     * Vérifier si un meta tag existe déjà
     */
    private function meta_tag_exists($name) {
        // Simple check - dans un vrai plugin on pourrait parser le head
        return false; // Pour l'instant on assume qu'ils n'existent pas
    }
    
    /**
     * Convertir URL YouTube en embed URL
     */
    private function convert_youtube_to_embed($url) {
        if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        } elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        return $url;
    }
    
    /**
     * Vérifier si l'article actuel est une fiche de jeu
     */
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