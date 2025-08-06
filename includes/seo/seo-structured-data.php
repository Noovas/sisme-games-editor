<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-structured-data.php
 * Module SEO - Données structurées Schema.org
 * 
 * RESPONSABILITÉ:
 * - Génération Schema.org pour les jeux référencés (VideoGame + Review)
 * - Support des avis et évaluations
 * - Métadonnées riches pour la recherche Google
 * - Cache des données structurées
 * - Format JSON-LD optimisé
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Structured_Data {
    
    /**
     * Durée du cache pour les données structurées (2 heures)
     */
    const CACHE_DURATION = 7200;
    
    /**
     * URL de base pour les schemas
     */
    const SCHEMA_CONTEXT = 'https://schema.org';
    
    public function __construct() {
        // Hook principal pour les données structurées
        add_action('wp_head', array($this, 'add_structured_data'), 50);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_cache_on_save'));
    }
    
    /**
     * Ajouter les données structurées dans le head
     */
    public function add_structured_data() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $structured_data = $this->generate_structured_data();
        if ($structured_data) {
            echo '<script type="application/ld+json">' . "\n";
            echo json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo "\n" . '</script>' . "\n";
        }
    }
    
    /**
     * Générer toutes les données structurées
     */
    private function generate_structured_data() {
        global $post;
        
        // Vérifier le cache
        $cache_key = 'sisme_structured_data_' . $post->ID;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        // Construire les différents schemas
        $schemas = array();
        
        // Schema principal : VideoGame
        $video_game_schema = $this->build_video_game_schema($game_data);
        if ($video_game_schema) {
            $schemas[] = $video_game_schema;
        }
        
        // Schema : Article (pour le guide/référencement)
        $article_schema = $this->build_article_schema($game_data);
        if ($article_schema) {
            $schemas[] = $article_schema;
        }
        
        // Schema : Review (si applicable)
        $review_schema = $this->build_review_schema($game_data);
        if ($review_schema) {
            $schemas[] = $review_schema;
        }
        
        // Schema : BreadcrumbList
        $breadcrumb_schema = $this->build_breadcrumb_schema($game_data);
        if ($breadcrumb_schema) {
            $schemas[] = $breadcrumb_schema;
        }
        
        // Wrapper dans un graph si multiple schemas
        $final_data = count($schemas) > 1 ? array(
            '@context' => self::SCHEMA_CONTEXT,
            '@graph' => $schemas
        ) : (isset($schemas[0]) ? $schemas[0] : null);
        
        // Sauvegarder en cache
        if ($final_data) {
            set_transient($cache_key, $final_data, self::CACHE_DURATION);
        }
        
        return $final_data;
    }
    
    /**
     * Construire le schema VideoGame
     */
    private function build_video_game_schema($game_data) {
        $schema = array(
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => 'VideoGame',
            'name' => $game_data['name'] ?? '',
            'url' => get_permalink(),
            'description' => wp_strip_all_tags($game_data['description'] ?? ''),
            'inLanguage' => get_locale()
        );
        
        // Genres
        if (!empty($game_data['genres'])) {
            $genres = array();
            foreach ($game_data['genres'] as $genre) {
                if (!empty($genre['name'])) {
                    $genres[] = $genre['name'];
                }
            }
            if (!empty($genres)) {
                $schema['genre'] = $genres;
            }
        }
        
        // Plateformes comme gamePlatform
        if (!empty($game_data['platforms'])) {
            $platforms = array();
            foreach ($game_data['platforms'] as $platform) {
                if (!empty($platform['label'])) {
                    $platforms[] = $platform['label'];
                }
            }
            if (!empty($platforms)) {
                $schema['gamePlatform'] = $platforms;
            }
        }
        
        // Date de sortie
        if (!empty($game_data['release_date'])) {
            // Convertir en format ISO si possible
            $date = $this->format_iso_date($game_data['release_date']);
            if ($date) {
                $schema['datePublished'] = $date;
            }
        }
        
        // Développeurs et éditeurs
        if (!empty($game_data['developers'])) {
            $developers = array();
            foreach ($game_data['developers'] as $dev) {
                if (!empty($dev['name'])) {
                    $dev_schema = array(
                        '@type' => 'Organization',
                        'name' => $dev['name']
                    );
                    if (!empty($dev['website'])) {
                        $dev_schema['url'] = $dev['website'];
                    }
                    $developers[] = $dev_schema;
                }
            }
            if (!empty($developers)) {
                $schema['developer'] = count($developers) === 1 ? $developers[0] : $developers;
            }
        }
        
        if (!empty($game_data['publishers'])) {
            $publishers = array();
            foreach ($game_data['publishers'] as $pub) {
                if (!empty($pub['name'])) {
                    $pub_schema = array(
                        '@type' => 'Organization',
                        'name' => $pub['name']
                    );
                    if (!empty($pub['website'])) {
                        $pub_schema['url'] = $pub['website'];
                    }
                    $publishers[] = $pub_schema;
                }
            }
            if (!empty($publishers)) {
                $schema['publisher'] = count($publishers) === 1 ? $publishers[0] : $publishers;
            }
        }
        
        // Images
        $images = $this->get_game_images($game_data);
        if (!empty($images)) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }
        
        // Trailer vidéo
        if (!empty($game_data['trailer_link'])) {
            $schema['trailer'] = array(
                '@type' => 'VideoObject',
                'embedUrl' => $game_data['trailer_link'],
                'name' => 'Trailer de ' . ($game_data['name'] ?? ''),
                'description' => 'Bande-annonce officielle du jeu'
            );
        }
        
        // Screenshots
        if (!empty($game_data['screenshots'])) {
            $screenshots = array();
            foreach (array_slice($game_data['screenshots'], 0, 5) as $screenshot) {
                if (!empty($screenshot['url'])) {
                    $screenshots[] = array(
                        '@type' => 'ImageObject',
                        'url' => $screenshot['url'],
                        'name' => $screenshot['alt'] ?? 'Screenshot de ' . ($game_data['name'] ?? '')
                    );
                }
            }
            if (!empty($screenshots)) {
                $schema['screenshot'] = $screenshots;
            }
        }
        
        return $schema;
    }
    
    /**
     * Construire le schema Article (pour le guide)
     */
    private function build_article_schema($game_data) {
        global $post;
        
        $schema = array(
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => wp_strip_all_tags($game_data['description'] ?? ''),
            'url' => get_permalink(),
            'datePublished' => get_post_time('c'),
            'dateModified' => get_post_modified_time('c'),
            'inLanguage' => get_locale(),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink()
            )
        );
        
        // Auteur
        $author_id = get_post_field('post_author');
        if ($author_id) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $author_id),
                'url' => get_author_posts_url($author_id)
            );
        }
        
        // Éditeur (site)
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_site_icon_url(512) ?: (get_template_directory_uri() . '/assets/images/logo.png')
            )
        );
        
        // Image principale
        $featured_image_url = get_the_post_thumbnail_url(null, 'full');
        if ($featured_image_url) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $featured_image_url
            );
        }
        
        // Article sur un jeu spécifique
        $schema['about'] = array(
            '@type' => 'VideoGame',
            'name' => $game_data['name'] ?? ''
        );
        
        // Catégorie
        $schema['articleSection'] = 'Jeux Indépendants';
        
        // Tags
        $tags = wp_get_post_tags();
        if (!empty($tags)) {
            $keywords = array();
            foreach ($tags as $tag) {
                $keywords[] = $tag->name;
            }
            $schema['keywords'] = implode(', ', $keywords);
        }
        
        return $schema;
    }
    
    /**
     * Construire le schema Review (si des sections d'avis sont présentes)
     */
    private function build_review_schema($game_data) {
        // Vérifier si le contenu contient des éléments d'avis
        $sections = $game_data['sections'] ?? array();
        $has_review_content = false;
        
        foreach ($sections as $section) {
            $title = strtolower($section['title'] ?? '');
            if (strpos($title, 'avis') !== false || 
                strpos($title, 'test') !== false || 
                strpos($title, 'critique') !== false ||
                strpos($title, 'verdict') !== false) {
                $has_review_content = true;
                break;
            }
        }
        
        if (!$has_review_content) {
            return null;
        }
        
        $schema = array(
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => 'Review',
            'name' => 'Présentation de ' . ($game_data['name'] ?? ''),
            'reviewBody' => 'Présentation et informations détaillées du jeu ' . ($game_data['name'] ?? ''),
            'url' => get_permalink(),
            'datePublished' => get_post_time('c'),
            'inLanguage' => get_locale()
        );
        
        // Auteur du test
        $author_id = get_post_field('post_author');
        if ($author_id) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $author_id)
            );
        }
        
        // Jeu testé
        $schema['itemReviewed'] = array(
            '@type' => 'VideoGame',
            'name' => $game_data['name'] ?? '',
            'description' => wp_strip_all_tags($game_data['description'] ?? '')
        );
        
        // Note (si disponible dans les custom fields)
        $rating = get_post_meta(get_the_ID(), 'game_rating', true);
        if ($rating && is_numeric($rating)) {
            $schema['reviewRating'] = array(
                '@type' => 'Rating',
                'ratingValue' => (float)$rating,
                'bestRating' => 5,
                'worstRating' => 1
            );
        }
        
        return $schema;
    }
    
    /**
     * Construire le schema BreadcrumbList
     */
    private function build_breadcrumb_schema($game_data) {
        $breadcrumbs = array();
        
        // Accueil
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Accueil',
            'item' => home_url()
        );
        
        // Jeux Indépendants
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Jeux Indépendants',
            'item' => home_url('/jeux-independants/')
        );
        
        // Genre principal si disponible
        if (!empty($game_data['genres']) && !empty($game_data['genres'][0]['url'])) {
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $game_data['genres'][0]['name'] ?? '',
                'item' => $game_data['genres'][0]['url']
            );
        }
        
        // Page actuelle
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => count($breadcrumbs) + 1,
            'name' => $game_data['name'] ?? get_the_title(),
            'item' => get_permalink()
        );
        
        return array(
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs
        );
    }
    
    /**
     * Obtenir les images du jeu pour Schema.org
     */
    private function get_game_images($game_data) {
        $images = array();
        
        // Cover principale
        if (!empty($game_data['covers']['main'])) {
            $images[] = array(
                '@type' => 'ImageObject',
                'url' => $game_data['covers']['main'],
                'name' => 'Cover de ' . ($game_data['name'] ?? '')
            );
        }
        
        // Featured image du post
        $featured_image_url = get_the_post_thumbnail_url(null, 'full');
        if ($featured_image_url && !in_array($featured_image_url, array_column($images, 'url'))) {
            $images[] = array(
                '@type' => 'ImageObject',
                'url' => $featured_image_url
            );
        }
        
        return $images;
    }
    
    /**
     * Formater une date au format ISO 8601
     */
    private function format_iso_date($date_string) {
        if (empty($date_string)) {
            return null;
        }
        
        // Essayer de parser la date
        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return null;
        }
        
        return date('c', $timestamp);
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            delete_transient('sisme_structured_data_' . $post_id);
        }
    }
    
    /**
     * Nettoyer le cache lors de l'édition d'un terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            $posts = get_posts(array(
                'post_type' => 'post',
                'tag__in' => array($term_id),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($posts as $post_id) {
                $this->clear_cache_on_save($post_id);
            }
        }
    }
}