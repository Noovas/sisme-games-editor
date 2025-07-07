<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-structured-data.php
 * Module SEO - Données structurées Schema.org
 * 
 * RESPONSABILITÉ:
 * - Génération JSON-LD pour VideoGame
 * - Données structurées SoftwareApplication
 * - Rich snippets pour Google
 * - Breadcrumbs structurés
 * - Cache des données JSON-LD
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
     * Version du schéma pour invalidation cache
     */
    const SCHEMA_VERSION = '1.0';
    
    public function __construct() {
        // Hook principal pour les données structurées
        add_action('wp_head', array($this, 'add_structured_data'), 20);
        
        // Hook pour les breadcrumbs
        add_action('wp_head', array($this, 'add_breadcrumbs_schema'), 25);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
    }
    
    /**
     * Ajouter les données structurées principales
     */
    public function add_structured_data() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $structured_data = $this->generate_structured_data();
        if ($structured_data) {
            echo $this->render_json_ld($structured_data);
        }
    }
    
    /**
     * Ajouter le schéma des breadcrumbs
     */
    public function add_breadcrumbs_schema() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $breadcrumbs_data = $this->generate_breadcrumbs_schema();
        if ($breadcrumbs_data) {
            echo $this->render_json_ld($breadcrumbs_data);
        }
    }
    
    /**
     * Générer les données structurées du jeu
     */
    private function generate_structured_data() {
        global $post;
        
        // Vérifier le cache
        $cache_key = 'sisme_structured_data_' . $post->ID . '_' . self::SCHEMA_VERSION;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $structured_data = $this->build_game_schema($game_data, $post);
        
        // Mettre en cache
        set_transient($cache_key, $structured_data, self::CACHE_DURATION);
        
        return $structured_data;
    }
    
    /**
     * Construire le schéma VideoGame/SoftwareApplication
     */
    private function build_game_schema($game_data, $post) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $description = $game_data[Sisme_Utils_Games::KEY_DESCRIPTION] ?? '';
        
        if (empty($game_name)) {
            return false;
        }
        
        // Structure de base VideoGame + SoftwareApplication
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => array('VideoGame', 'SoftwareApplication'),
            'name' => $game_name,
            'url' => get_permalink($post->ID),
            'applicationCategory' => 'Game',
            'applicationSubCategory' => 'Video Game'
        );
        
        // Description
        if ($description) {
            $schema['description'] = $this->clean_description($description);
        }
        
        // Date de publication
        $release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? '';
        if ($release_date) {
            $schema['datePublished'] = $release_date;
            $schema['dateCreated'] = $release_date;
        }
        
        // Date de modification
        $schema['dateModified'] = get_the_modified_date('c', $post->ID);
        
        // Systèmes d'exploitation
        $operating_systems = $this->extract_operating_systems($game_data);
        if (!empty($operating_systems)) {
            $schema['operatingSystem'] = $operating_systems;
        }
        
        // Genres de jeu
        $genres = $this->extract_genres_for_schema($game_data);
        if (!empty($genres)) {
            $schema['genre'] = $genres;
            $schema['gameCategory'] = $genres; // Propriété spécifique VideoGame
        }
        
        // Plateformes de jeu
        $platforms = $this->extract_platforms_for_schema($game_data);
        if (!empty($platforms)) {
            $schema['gamePlatform'] = $platforms;
        }
        
        // Image principale
        $image_data = $this->extract_image_data($game_data);
        if ($image_data) {
            $schema['image'] = $image_data;
            $schema['logo'] = $image_data; // Pour SoftwareApplication
        }
        
        // Développeur/Créateur
        $authors = $this->extract_developers_schema($game_data);
        if (!empty($authors)) {
            $schema['author'] = $authors;
            $schema['creator'] = $authors;
            $schema['developer'] = $authors; // Propriété spécifique SoftwareApplication
        }
        
        // Éditeur
        $publishers = $this->extract_publishers_schema($game_data);
        if (!empty($publishers)) {
            $schema['publisher'] = $publishers;
        }
        
        // Trailer vidéo
        $trailer_data = $this->extract_trailer_data($game_data);
        if ($trailer_data) {
            $schema['trailer'] = $trailer_data;
            $schema['video'] = $trailer_data; // Propriété générique
        }
        
        // Screenshots
        $screenshots = $this->extract_screenshots_data($game_data);
        if (!empty($screenshots)) {
            $schema['screenshot'] = $screenshots;
        }
        
        // Liens externes
        $external_links = $this->extract_external_links($game_data);
        if (!empty($external_links)) {
            $schema['sameAs'] = $external_links;
            $schema['downloadUrl'] = $external_links; // Pour SoftwareApplication
        }
        
        // Modes de jeu
        $game_modes = $this->extract_game_modes_schema($game_data);
        if (!empty($game_modes)) {
            $schema['gameMode'] = $game_modes;
        }
        
        // Langue du contenu
        $schema['inLanguage'] = get_locale();
        
        // Site web parent
        $schema['isPartOf'] = array(
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        );
        
        // Évaluations (si disponibles)
        $rating_data = $this->extract_rating_data($game_data);
        if ($rating_data) {
            $schema['aggregateRating'] = $rating_data;
        }
        
        return $schema;
    }
    
    /**
     * Générer le schéma des breadcrumbs
     */
    private function generate_breadcrumbs_schema() {
        global $post;
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $breadcrumbs = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        $position = 1;
        
        // 1. Accueil
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Accueil',
            'item' => home_url()
        );
        
        // 2. Section Jeux
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Jeux',
            'item' => home_url('/jeux/')
        );
        
        // 3. Genre principal (si disponible)
        $main_genre = $this->get_main_genre($game_data);
        if ($main_genre) {
            $breadcrumbs['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $main_genre['name'],
                'item' => $main_genre['url']
            );
        }
        
        // 4. Jeu actuel
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $game_data[Sisme_Utils_Games::KEY_NAME] ?? '',
            'item' => get_permalink($post->ID)
        );
        
        return $breadcrumbs;
    }
    
    /**
     * Extraire les systèmes d'exploitation
     */
    private function extract_operating_systems($game_data) {
        $platforms = $game_data[Sisme_Utils_Games::KEY_PLATFORMS] ?? array();
        $os_list = array();
        
        foreach ($platforms as $platform_group) {
            $group_key = $platform_group['group'] ?? '';
            switch ($group_key) {
                case 'pc':
                    $os_list[] = 'Windows';
                    $os_list[] = 'Linux';
                    $os_list[] = 'macOS';
                    break;
                case 'console':
                    $os_list[] = 'PlayStation';
                    $os_list[] = 'Xbox';
                    $os_list[] = 'Nintendo Switch';
                    break;
                case 'mobile':
                    $os_list[] = 'Android';
                    $os_list[] = 'iOS';
                    break;
                case 'web':
                    $os_list[] = 'Web Browser';
                    break;
            }
        }
        
        return array_unique($os_list);
    }
    
    /**
     * Extraire les genres pour le schéma
     */
    private function extract_genres_for_schema($game_data) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        $genre_names = array();
        
        foreach ($genres as $genre) {
            if (isset($genre['name']) && !empty($genre['name'])) {
                $genre_names[] = ucfirst($genre['name']);
            }
        }
        
        return $genre_names;
    }
    
    /**
     * Extraire les plateformes pour le schéma
     */
    private function extract_platforms_for_schema($game_data) {
        $platforms = $game_data[Sisme_Utils_Games::KEY_PLATFORMS] ?? array();
        $platform_names = array();
        
        foreach ($platforms as $platform_group) {
            if (isset($platform_group['label']) && !empty($platform_group['label'])) {
                $platform_names[] = $platform_group['label'];
            }
        }
        
        return array_unique($platform_names);
    }
    
    /**
     * Extraire les données d'image
     */
    private function extract_image_data($game_data) {
        $covers = $game_data[Sisme_Utils_Games::KEY_COVERS] ?? array();
        $cover_id = $covers[Sisme_Utils_Games::KEY_COVER_MAIN] ?? '';
        
        if (!$cover_id) {
            return false;
        }
        
        $image_url = wp_get_attachment_image_url($cover_id, 'large');
        if (!$image_url) {
            return false;
        }
        
        $image_meta = wp_get_attachment_metadata($cover_id);
        
        return array(
            '@type' => 'ImageObject',
            'url' => $image_url,
            'width' => $image_meta['width'] ?? 1200,
            'height' => $image_meta['height'] ?? 630,
            'caption' => 'Cover du jeu ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? '')
        );
    }
    
    /**
     * Extraire les développeurs pour le schéma
     */
    private function extract_developers_schema($game_data) {
        $developers = $game_data[Sisme_Utils_Games::KEY_DEVELOPERS] ?? array();
        $dev_schema = array();
        
        foreach ($developers as $dev_id) {
            $developer = get_category($dev_id);
            if ($developer && !is_wp_error($developer)) {
                $dev_data = array(
                    '@type' => 'Organization',
                    'name' => $developer->name
                );
                
                // Ajouter le site web si disponible
                $website = get_term_meta($dev_id, 'entity_website', true);
                if ($website) {
                    $dev_data['url'] = $website;
                }
                
                $dev_schema[] = $dev_data;
            }
        }
        
        return $dev_schema;
    }
    
    /**
     * Extraire les éditeurs pour le schéma
     */
    private function extract_publishers_schema($game_data) {
        $publishers = $game_data[Sisme_Utils_Games::KEY_PUBLISHERS] ?? array();
        $pub_schema = array();
        
        foreach ($publishers as $pub_id) {
            $publisher = get_category($pub_id);
            if ($publisher && !is_wp_error($publisher)) {
                $pub_data = array(
                    '@type' => 'Organization',
                    'name' => $publisher->name
                );
                
                // Ajouter le site web si disponible
                $website = get_term_meta($pub_id, 'entity_website', true);
                if ($website) {
                    $pub_data['url'] = $website;
                }
                
                $pub_schema[] = $pub_data;
            }
        }
        
        return $pub_schema;
    }
    
    /**
     * Extraire les données de trailer
     */
    private function extract_trailer_data($game_data) {
        $trailer_url = $game_data[Sisme_Utils_Games::KEY_TRAILER_LINK] ?? '';
        if (!$trailer_url) {
            return false;
        }
        
        // Extraire l'ID YouTube si c'est une vidéo YouTube
        $youtube_id = $this->extract_youtube_id($trailer_url);
        if ($youtube_id) {
            return array(
                '@type' => 'VideoObject',
                'name' => 'Trailer - ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? ''),
                'embedUrl' => "https://www.youtube.com/embed/{$youtube_id}",
                'url' => $trailer_url,
                'thumbnailUrl' => "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg",
                'uploadDate' => $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? date('c')
            );
        }
        
        // Autre type de vidéo
        return array(
            '@type' => 'VideoObject',
            'name' => 'Trailer - ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? ''),
            'url' => $trailer_url
        );
    }
    
    /**
     * Extraire les screenshots
     */
    private function extract_screenshots_data($game_data) {
        $screenshots = $game_data[Sisme_Utils_Games::KEY_SCREENSHOTS] ?? '';
        if (!$screenshots) {
            return array();
        }
        
        $screenshot_ids = explode(',', $screenshots);
        $screenshot_data = array();
        
        foreach ($screenshot_ids as $screenshot_id) {
            $screenshot_id = trim($screenshot_id);
            if (!$screenshot_id) continue;
            
            $image_url = wp_get_attachment_image_url($screenshot_id, 'large');
            if ($image_url) {
                $screenshot_data[] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                    'caption' => 'Screenshot du jeu ' . ($game_data[Sisme_Utils_Games::KEY_NAME] ?? '')
                );
            }
        }
        
        return $screenshot_data;
    }
    
    /**
     * Extraire les liens externes
     */
    private function extract_external_links($game_data) {
        $external_links = $game_data[Sisme_Utils_Games::KEY_EXTERNAL_LINKS] ?? array();
        $links = array();
        
        foreach ($external_links as $platform => $url) {
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $links[] = $url;
            }
        }
        
        return $links;
    }
    
    /**
     * Extraire les modes de jeu
     */
    private function extract_game_modes_schema($game_data) {
        $modes = $game_data[Sisme_Utils_Games::KEY_MODES] ?? array();
        $mode_names = array();
        
        foreach ($modes as $mode) {
            if (isset($mode['label']) && !empty($mode['label'])) {
                $mode_names[] = $mode['label'];
            }
        }
        
        return $mode_names;
    }
    
    /**
     * Extraire les données d'évaluation (si disponibles)
     */
    private function extract_rating_data($game_data) {
        // Pour l'instant, retourner false car pas de système de notation
        // Peut être étendu plus tard
        return false;
    }
    
    /**
     * Obtenir le genre principal pour les breadcrumbs
     */
    private function get_main_genre($game_data) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        if (empty($genres)) {
            return false;
        }
        
        $first_genre = $genres[0];
        if (!isset($first_genre['id'])) {
            return false;
        }
        
        $genre_category = get_category($first_genre['id']);
        if (!$genre_category || is_wp_error($genre_category)) {
            return false;
        }
        
        return array(
            'name' => $first_genre['name'] ?? $genre_category->name,
            'url' => get_category_link($genre_category->term_id)
        );
    }
    
    /**
     * Extraire l'ID YouTube d'une URL
     */
    private function extract_youtube_id($url) {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }
    
    /**
     * Nettoyer la description pour le schéma
     */
    private function clean_description($description) {
        $clean = strip_tags($description);
        $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }
    
    /**
     * Rendu du JSON-LD
     */
    private function render_json_ld($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = '<script type="application/ld+json">' . "\n";
        $output .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $output .= "\n" . '</script>' . "\n";
        
        return $output;
    }
    
    /**
     * Nettoyer le cache
     */
    public function clear_cache_on_save($post_id) {
        if (Sisme_SEO_Loader::is_game_page($post_id)) {
            delete_transient('sisme_structured_data_' . $post_id . '_' . self::SCHEMA_VERSION);
        }
    }
    
    /**
     * Nettoyer le cache lors de modification de terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            // Nettoyer le cache de tous les posts liés
            $posts = get_posts(array(
                'tag__in' => array($term_id),
                'post_type' => 'post',
                'meta_query' => array(
                    array(
                        'key' => '_sisme_game_sections',
                        'compare' => 'EXISTS'
                    )
                ),
                'fields' => 'ids',
                'numberposts' => -1
            ));
            
            foreach ($posts as $post_id) {
                delete_transient('sisme_structured_data_' . $post_id . '_' . self::SCHEMA_VERSION);
            }
        }
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_structured_data_%'"
        );
        
        return array(
            'module' => 'Structured Data',
            'schema_version' => self::SCHEMA_VERSION,
            'active_caches' => intval($cache_count),
            'cache_duration' => self::CACHE_DURATION
        );
    }
}